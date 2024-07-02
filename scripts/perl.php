<?php
$site_id = $argv[1];
$link_id = $argv[2];
$student_choices = json_decode($argv[3], true);
$groups = json_decode($argv[4], true);

// Temporary cleanup /tmp
echo(shell_exec ( 'rm -f /tmp/i* /tmp/o*' ));


$projects = array();
foreach($groups as $group ) {
    $projects[$group['group_id']] = $group['group_size'];
}

$students = array();
foreach ($student_choices as $choice) {
    $user_id = $choice['user_id'];
    $group_id = $choice['group_id'];

    $students[$user_id][] = 'p' . $group_id;
}

// Produce output
echo("projects.txt:\n\n");
foreach($projects as $group => $occupancy) {
    echo("$group $occupancy\n");
}

echo("\nstudents.txt:\n\n");
foreach($students as $student => $prefs) {
    $prefsString = implode(' ', $prefs);
    echo("s$student $prefsString\n");
}


$istu = tempnam('/tmp', 'istu');
$ipro = tempnam('/tmp', 'ipro');
$ilec = tempnam('/tmp', 'ilec');

$ostu = tempnam('/tmp', 'ostu');
$opro = tempnam('/tmp', 'opro');
$olec = tempnam('/tmp', 'olec');

$file = fopen($ipro, "w");
foreach($projects as $group => $occupancy) {
    fwrite($file, "p$group $occupancy l1\n");
}
fclose($file);

$file = fopen($istu, "w");
foreach($students as $student => $prefs) {
    $prefsString = implode(' ', $prefs);
    fwrite($file, "s$student $prefsString\n");
}
fclose($file);

$file = fopen($ilec, "w");
fwrite($file, "l1 100000\n");
fclose($file);

$cmd = "perl /var/www/vhosts/tsugidev.uct.ac.za/mod/tsugi-student-allocation/scripts/allocate.pl $istu $ipro $ilec $ostu $opro $olec";
echo($cmd);
echo(shell_exec ( $cmd ));

$file = fopen($ostu, "r");
$assignments = [];
if ($file) {
    echo("<hr/>\n");
    echo readfile($ostu);
    echo("</pre>\n");

    while (($line = fgets($file)) !== false) {
        $parts = explode(" ", $line);
        
        $student_id = $parts[0];
        $project_id = $parts[1];
        
        $assignments[] = [
            'student_id' => $student_id,
            'assigned_group' => $project_id
        ];
    }

    fclose($file);

} else {
    echo "Error opening file: $ostu";
}

// Output $assignments array as JSON
echo json_encode($assignments);
