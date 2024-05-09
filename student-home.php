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
$is_student = true;

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);

$allocation_details = $allocationDAO->getSettings($LINK->id,$site_id);
$allocation_groups = $allocationDAO->getGroups($LINK->id,$site_id);
$selected_groups = $allocationDAO->getChoices($LINK->id, $USER->id);

$context = [
    'instructor' => $USER->instructor,
    'styles' => [addSession('static/css/app.min.css'), addSession('static/css/custom.css')],
    'scripts' => [addSession('static/js/Sortable.min.js'), addSession('static/js/moment.min.js'), addSession('static/js/Chart.bundle.min.js'), 
                    addSession('static/js/app.js'), addSession('static/js/tmpl.min.js'), 'https://code.jquery.com/jquery-3.6.0.min.js'],
    'debug' => $debug,
    'allocationdetails' => json_encode($allocation_details),
    'allocationgroups' => json_encode($allocation_groups),
    'addchoices' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/processchoices.php'))),
    'selectedgroups' => json_encode($selected_groups),
    'userid' => $USER->id,
];

if ($USER->instructor) {
    header('Location: ' . addSession('instructor-home.php'));
}

// Start of the output
$OUTPUT->header();

Template::view('templates/header.html', $context);

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

Template::view('templates/student-body.html', $context);

$OUTPUT->footerStart();

Template::view('templates/student-footer.html', $context);

$OUTPUT->footerEnd();
?>
