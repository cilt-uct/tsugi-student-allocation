<?php
require_once "../config.php";
include 'tool-config_dist.php';

include 'src/Template.php';
require_once "dao/AllocationDAO.php";

# display All errors if in debug mode
if ($tool['debug']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$menu = false;

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

$name = $LAUNCH->ltiRawParameter('lis_person_name_given');

$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 0);

// In future this might be mulitple projects per site.
$current_project = $allocationDAO->getProject();

if ($USER->instructor) {
    header('Location: ' . addSession('instructor-home.php'));
}

// Start of the output
$OUTPUT->header();

$context = [
    'instructor' => $USER->instructor,
    'styles' => [addSession('static/css/app.min.css'), addSession('static/css/custom.css'), addSession('static/css/jquery-msgpopup.css')],
    'scripts' => [addSession('static/js/app.js'), addSession('static/js/jquery-msgpopup.js')]
];

Template::view('templates/header.html', $context);

$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

$project_started_yet = (isset($current_project['release_date'])) &&
                            ( (new DateTime($current_project['release_date'])) <= (new DateTime()) );

$project_selection_closed = (isset($current_project['release_date'])) &&
                                ( (new DateTime($current_project['closing_date'])) <= (new DateTime()) );

if ($project_started_yet) {

    $groups =
    // $selected_groups =

    $releaseDate = $st = isset($current_project['release_date']) ? (new DateTime($current_project['release_date']))->format("l, jS \of F Y") : '';
    $closingDate = $st = isset($current_project['closing_date']) ? (new DateTime($current_project['release_date']))->format("l, jS \of F Y") : '';

    $instructions = $current_project['instructions'];
        $instructions = str_replace("{(ReleaseDate)}", $releaseDate, $instructions);
        $instructions = str_replace("{(ClosingDate)}", $closingDate, $instructions);
        $instructions = str_replace("{(SelectMin)}", $current_project['min_selections'], $instructions);
        $instructions = str_replace("{(SelectMax)}", $current_project['max_selections'], $instructions);

    $current_project['instructions'] = $instructions;

    $context = array_merge($context, [
        'current_project' => $current_project,
        'state' => isset($current_project['state']) ? $current_project['state'] : 'open',

        'allocation_settings' => [
            'min_selections' => $current_project['min_selections'],
            'max_selections' => $current_project['max_selections'],
        ],
        'allocation_groups' => $allocationDAO->getGroups($current_project['project_id']),
        'selected_groups' => $allocationDAO->getChoices($current_project['project_id'], $USER->id),
        'add_choice_url' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/process_choices.php'))),
        'project_closed' => $project_selection_closed
    ]);
    Template::view('templates/student-body.html', $context);

    $OUTPUT->footerStart();

    if($project_selection_closed) {
        Template::view('templates/student-footer-display.html', $context);
    } else {
        Template::view('templates/student-footer.html', $context);
    }
    include('templates/student_tmpl.html');

} else {
    // style=" background-image: linear-gradient(to right top, #d16ba5, #c777b9, #ba83ca, #aa8fd8, #9a9ae1, #8aa7ec, #79b3f4, #69bff8, #52cffe, #41dfff, #46eefa, #5ffbf1);"
    ?>
    <div class="bgnew"></div>
    <?php

    $st = isset($current_project['release_date']) ? '<br/> It will be available on ' . (new DateTime($current_project['release_date']))->format("l, jS \of F Y") .'.' : '';

    $OUTPUT->splashPage(
        "Hi ". $name ."!",
        __("The selection has not been released yet." . $st)
    );
    $OUTPUT->footerStart();
}

$OUTPUT->footerEnd();
?>
