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

require '/usr/local/serverconfig/auth.pl';

my $debug = 1;
my $start = time();

my ($host, $dbname, $user, $password) = getTsugiDev();

my $db = DBI->connect("DBI:mysql:database=$dbname;host=$host;port=3306;mysql_socket=/var/lib/mysql/mysql.sock", $user, $password,
                                {'RaiseError' => 1, mysql_auto_reconnect => 1})
    or die "Could not connect to archive database $dbname: $DBI::errstr";

print "<pre>\n";

print "connected to the database\n";


print "</pre>\n";

# search for "wating" sites
my $get_waiting_sites_stmt = $db->prepare("SELECT * FROM allocation_site  where state='waiting'")
                or die "Prepare get_waiting_sites_stmt: ". $db->errstr;

$get_waiting_sites_stmt->execute()
    or die "Exec get_waiting_sites_stmt: ". $get_waiting_sites_stmt->errstr;

my $list = $get_waiting_sites_stmt->fetchrow_array();
$list = "[]" if (!defined($list)); # if no DB result;


print Dumper($list) if $debug;

print "\nExecution: ", duration(time() - $start), ".\n" if $debug;
print "------------------------------------------------------------------------\n" if $debug;



# updates site to "running"

# run allocation.pl with details from the site
# if error set site state to "error"
# else
#  run through results and update student choices
#  set site state to "assigned"

print "All Done\n"
