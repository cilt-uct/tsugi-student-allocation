<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Allocation\DAO\AllocationDAO;

// Sanity checks
$LAUNCH = LTIX::requireData();

if ( ! $USER->instructor ) die('Must be instructor');

echo("<pre>\n");

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);
$groups = $allocationDAO->getGroups($LINK->id);
$student_choices = $allocationDAO->getChoices($LINK->id);

// Temporary cleanup /tmp
echo(shell_exec ( 'rm -f /tmp/i* /tmp/o*' ));

$json = Settings::linkGet('json'); // LTI_LINK JSON > contains the course settings

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

$cmd = "perl allocate.pl $istu $ipro $ilec $ostu $opro $olec";
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

    $allocationDAO->addAssignments($LINK->id, $USER->id, $assignments);
    fclose($file);

} else {
    echo "Error opening file: $ostu";
}
/*
echo("Hello\n");
echo(shell_exec ( '/bin/bash zap.sh bob' ));
echo("Back from shell\n");
*/
