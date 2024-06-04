<?php
require_once "../config.php";
include 'tool-config_dist.php';
include 'src/Template.php';
require_once "dao/AllocationDAO.php";

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$debug = 1;
$menu = false;

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$user_eid = $LAUNCH->ltiRawParameter('user_id', 'none');
$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);

$allocation_settings = $allocationDAO->getSettings($LINK->id,$site_id);
$groups = $allocationDAO->getGroups($LINK->id);
$student_choices = $allocationDAO->getChoices($LINK->id);
$tool_id = $LINK->id;

$context = [
    'instructor' => $USER->instructor,
    'styles' => [addSession('static/css/app.min.css'), addSession('static/css/custom.css')],
    'scripts' => [addSession('static/js/app.js'),  addSession('static/js/Sortable.min.js')],
    'debug' => $debug,
    'allocate' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('scripts/perl.php'))),
    'configure' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/configure.php'))),
    'updatestate' =>addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/set_state.php'))),
    'assign' =>addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/assign.php'))),
    'checkwaitingsites' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('scripts/check.pl'))),
    'toolid' => $tool_id,
    'allocationsettings' => json_encode($allocation_settings),
    'allocationgroups' => json_encode($groups),
    'studentchoices' => json_encode($student_choices), 
    'eid' => $user_eid,
    'state' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/check_state.php'))),
];

if (!$USER->instructor) {
    header('Location: ' . addSession('student-home.php'));
}

$OUTPUT->header();

Template::view('templates/header.html', $context);

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

Template::view('templates/instructor-body.html', $context);

$OUTPUT->footerStart();

Template::view('templates/instructor-footer.html', $context);
include('templates/instructor_tmpl.html');
$OUTPUT->footerEnd();
?>
