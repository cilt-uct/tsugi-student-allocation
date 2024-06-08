#! /usr/bin/perl

## Checks the DB for sites that are waiting for allocation to be executed
## REF:

# * * * * * /usr/bin/flock -n /tmp/allocation_check.lockfile /
#                   perl /var/www/vhosts/tsugidev.uct.ac.za/mod/tsugi-student-allocation/scripts/check.pl /
#                   >> /usr/local/scripts/log/allocation.log /
#                   2>> /usr/local/scripts/log/allocation.ERR

use strict;
use warnings;
use DBI;
use Data::Dumper;
use Time::Duration;
use JSON;
use POSIX qw/strftime/;

require '/usr/local/serverconfig/auth.pl';

my $debug = 1;
my $start = time();
my $current_timestamp = strftime('%Y-%m-%d %H:%M:%S', localtime);

my ($host, $dbname, $user, $password) = getTsugiDev();

my $db = DBI->connect("DBI:mysql:database=$dbname;host=$host;port=3306;mysql_socket=/var/lib/mysql/mysql.sock", $user, $password,
                                {'RaiseError' => 1, mysql_auto_reconnect => 1})
    or die "Could not connect to archive database $dbname: $DBI::errstr";

print "<pre>\n";

print "connected to the database\n";


print "</pre>\n";

# search for "wating" sites
my $get_waiting_sites_stmt = $db->prepare("SELECT * FROM allocation_site  where state='waiting' ORDER BY created_at ASC;")
                or die "Prepare get_waiting_sites_stmt: ". $db->errstr;

$get_waiting_sites_stmt->execute()
    or die "Exec get_waiting_sites_stmt: ". $get_waiting_sites_stmt->errstr;

# Fetch all rows into an array
my @waiting_sites;
while (my $row_ref = $get_waiting_sites_stmt->fetchrow_hashref()) {
    push @waiting_sites, $row_ref;
}

# Handle the case when no results are found
if (!@waiting_sites) {
    print "No waiting sites found.\n";
    exit;
}

# Update each waiting site to "running" and call perl.php
my $update_stmt = $db->prepare("UPDATE allocation_site SET state = 'running' WHERE site_id = ? AND link_id = ?")
    or die "Prepare update_stmt: " . $db->errstr;

foreach my $site (@waiting_sites) {
    my $site_id = $site->{site_id};
    my $link_id = $site->{link_id};

    # Update the state to "running"
    $update_stmt->execute($site_id, $link_id)
        or die "Exec update_stmt: " . $update_stmt->errstr;

    # Retrieve student choices for this site
    my $choices_query = "
        SELECT `choice`.*,
               `group`.`group_name` AS `group_name`,
               `group`.`group_size` AS `group_size`,
               `user`.`displayname` AS `user_name`
        FROM `allocation_choice` AS `choice`
        LEFT JOIN `allocation_group` AS `group` ON `choice`.`group_id` = `group`.`group_id`
        LEFT JOIN `lti_user` AS `user` ON `choice`.`user_id` = `user`.`user_id`
        WHERE `choice`.`link_id` = ?
    ";

    my $choices_stmt = $db->prepare($choices_query)
        or die "Prepare choices_stmt: " . $db->errstr;
    $choices_stmt->execute($link_id)
        or die "Exec choices_stmt: " . $choices_stmt->errstr;

    my @student_choices;
    while (my $choice = $choices_stmt->fetchrow_hashref) {
        push @student_choices, $choice;
    }
    $choices_stmt->finish();

    print "Student choices for site_id $site_id:\n";
    print Dumper(\@student_choices);

    # Retrieve groups for this site
    my $groups_query = "
        SELECT *
    FROM `allocation_group`
        WHERE `link_id` = ?
        ORDER BY `group_id` ASC
    ";

    my $groups_stmt = $db->prepare($groups_query)
        or die "Prepare groups_stmt: " . $db->errstr;
    $groups_stmt->execute($link_id)
        or die "Exec groups_stmt: " . $groups_stmt->errstr;

    my @groups;
    while (my $group = $groups_stmt->fetchrow_hashref) {
        push @groups, $group;
    }
    $groups_stmt->finish();

    print "Groups for site_id $site_id:\n";
    print Dumper(\@groups);

    # Serialize data structures to JSON
    my $student_choices_json = encode_json(\@student_choices);
    my $groups_json = encode_json(\@groups);

    # Call perl.php to run allocation details from the site
    my $output = `php /var/www/vhosts/tsugidev.uct.ac.za/mod/tsugi-student-allocation/scripts/perl.php $site_id '$link_id' '$student_choices_json' '$groups_json'`;


    print "Full output:\n$output";

    print "------ end of full output --- \n";

    if ($? == 0) {
        print "Successfully called perl.php for site_id: $site_id\n";

    my $assignments_json;

    # Extract assignments JSON array from $output
    if ($output =~ /(\[.*\])\s*$/) {
            $assignments_json = $1;
    } else {
            warn "No assignments array found at the end of the output";
    }


    my $assignments;

    eval {
        $assignments = decode_json($assignments_json);
    };

    if ($@) {
            warn "Error decoding JSON: $@";
    } else {
            if ($assignments && @$assignments) {
                # Begin transaction
                $db->begin_work;

            # Process assignments and insert into database
                foreach my $assignment (@$assignments) {
                    my $student_id = $assignment->{'student_id'};
                        my $assigned_group = $assignment->{'assigned_group'};


                    # Skip the "UNASSIGNED" entry
                next if $student_id eq 'UNASSIGNED:';

            $student_id =~ s/^s//;
            $assigned_group =~ s/^p//;
            my $existing_assigned_group = get_assigned_group($link_id, $student_id);

                    # If there's already an assigned group, reset it to 0
                    if ($existing_assigned_group) {
                        my $reset_query = "
                             UPDATE allocation_choice
                             SET assigned = 0, modified_by = ?, modified_at = ?
                             WHERE link_id = ? AND user_id = ? AND group_id = ?
                          ";
                        my $reset_stmt = $db->prepare($reset_query)
                            or die "Prepare reset_stmt: " . $db->errstr;
                       $reset_stmt->execute('script', $current_timestamp, $link_id, $student_id, $existing_assigned_group)
                            or die "Exec reset_stmt: " . $reset_stmt->errstr;
                       $reset_stmt->finish();
                    }

                    # Insert assignment into database
            my $insert_query = "
                UPDATE allocation_choice
                SET assigned = 1, modified_by = ?, modified_at = ?
                WHERE link_id = ? AND user_id = ? AND group_id = ?
                ";

            my $insert_stmt = $db->prepare($insert_query)
                    or die "Prepare insert_stmt: " . $db->errstr;
                $insert_stmt->execute('script', $current_timestamp, $link_id, $student_id, $assigned_group)
                    or die "Exec insert_stmt: " . $insert_stmt->errstr;
                $insert_stmt->finish();
            }
        # Update the state of the site
        my $update_site_query = "
            UPDATE allocation_site
            SET state = 'assigned'
            WHERE link_id = ? AND site_id = ?
        ";

        my $update_site_stmt = $db->prepare($update_site_query)
            or die "Prepare update_site_stmt: " . $db->errstr;
        $update_site_stmt->execute($link_id, $site_id)
            or die "Exec update_site_stmt: " . $update_site_stmt->errstr;
        $update_site_stmt->finish();

        # Commit transaction
        $db->commit;
    }
    }
    } else {
        warn "Failed to call perl.php for site_id: $site_id. Error:  $output\n";

    # Revert the state back to 'waiting' since the call to perl.php failed
        my $error_stmt = $db->prepare("UPDATE allocation_site SET state = 'error' WHERE site_id = ? AND link_id = ?")
            or die "Prepare revert_stmt: " . $db->errstr;
        $error_stmt->execute($site_id, $link_id)
            or die "Exec error_stmt: " . $error_stmt->errstr;
        $error_stmt->finish();

        # Exit or break the loop
        last;
    }

    # Debugging information
    print Dumper($site) if $debug;
}

# Additional debug info
if ($debug) {
    print "\nExecution: ", duration(time() - $start), ".\n";
    print "------------------------------------------------------------------------\n";
}

$get_waiting_sites_stmt->finish();
$update_stmt->finish();

sub get_assigned_group {
    my ($link_id, $student_id) = @_;
    my $existing_assigned_group;

    my $query = "
        SELECT group_id
        FROM allocation_choice
        WHERE link_id = ? AND user_id = ? AND assigned = 1
    ";
    my $stmt = $db->prepare($query)
        or die "Prepare get_assigned_group: " . $db->errstr;
    $stmt->execute($link_id, $student_id)
        or die "Exec get_assigned_group: " . $stmt->errstr;
    if (my $row = $stmt->fetchrow_hashref()) {
        $existing_assigned_group = $row->{'group_id'};
    }
    $stmt->finish();

    return $existing_assigned_group;
}

# run allocation.pl with details from the site
# if error set site state to "error"
# else
#  run through results and update student choices
#  set site state to "assigned"

print "All Done\n"
