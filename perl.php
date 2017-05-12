<?php

require_once "../config.php";
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;

// Sanity checks
$LAUNCH = LTIX::requireData();

if ( ! $USER->instructor ) die('Must be instructor');

echo("<pre>\n");
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
        $prefstr .= $pref->id;
    }
    $students['s'.$row['user_id']] = $prefstr;
}

// Produce output
echo("projects.txt:\n\n");
foreach($projects as $group => $occupancy) {
    echo("$group $occupancy\n");
}

echo("\nstudents.txt:\n\n");
foreach($students as $student => $prefs) {
    echo("$student $prefs\n");
}

echo("</pre>\n");

/*
echo("Hello\n");
echo(shell_exec ( '/bin/bash zap.sh bob' ));
echo("Back from shell\n");
*/
