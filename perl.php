<?php

require_once "../config.php";
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;

// Sanity checks
$LAUNCH = LTIX::requireData();

if ( ! $USER->instructor ) die('Must be instructor');

echo("<pre>\n");

// Temporary cleanup /tmp
echo(shell_exec ( 'rm -f /tmp/i* /tmp/o*' ));

$json = Settings::linkGet('json'); // LTI_LINK JSON > contains the course settings

$projects = array();
$groups = $json['groups'];
foreach($json['groups'] as $group ) {
    $projects[$group['id']] = $group['occupancy'];
}

$stmt = $PDOX->queryDie(
    "SELECT user_id, json FROM {$CFG->dbprefix}lti_result
        WHERE link_id = :LID",
    array(":LID" => $LINK->id)
);
$rows = $stmt->fetchAll();

$students = array();
foreach($rows as $row) {
    $json = json_decode($row['json']);
    // s1 p1 p13 p5 p13 p15
    // s2 p7 p1 p16 p15 p14
    $prefstr = '';
    foreach($json->selection as $pref) {
        if ( strlen($prefstr) > 0 ) $prefstr .= ' ';
        $prefstr .= 'p'.$pref->id;
    }
    $students[$row['user_id']] = $prefstr;
}
/*
// Produce output
echo("projects.txt:\n\n");
foreach($projects as $group => $occupancy) {
    echo("$group $occupancy\n");
}

echo("\nstudents.txt:\n\n");
foreach($students as $student => $prefs) {
    echo("$student $prefs\n");
}
*/

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
    fwrite($file, "s$student $prefs\n");
}
fclose($file);

$file = fopen($ilec, "w");
fwrite($file, "l1 100000\n");
fclose($file);

$cmd = "cd scripts; perl allocate.pl $istu $ipro $ilec $ostu $opro $olec";
echo($cmd);
echo(shell_exec ( $cmd ));

$file = fopen($ostu, "r");
echo("<hr/>\n");
echo readfile($ostu);

echo("</pre>\n");
/*
echo("Hello\n");
echo(shell_exec ( '/bin/bash zap.sh bob' ));
echo("Back from shell\n");
*/
