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

// This function does not work at this point in time (2024-06-06)
// So we have slight work around - later this can be implemented when it works again
// $hasRosters = LTIX::populateRoster(true, true, null);
const ROLE_LEARNER = 0;
const ROLE_INSTRUCTOR = 1000;

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 1000);

// In future this might be mulitple projects per site.
$current_project = $allocationDAO->getProject();
$groups = $allocationDAO->getGroups($current_project['project_id']);

$records = $PDOX->allRowsDie("SELECT ifnull(count(distinct `choice`.`user_id`),0) as 'c'
    FROM {$CFG->dbprefix}allocation_choice `choice`
    where `choice`.`project_id` = :project_id", array(':project_id' => $current_project['project_id']));
$no_users = $records[0]['c'];

/*
const ROLE_LEARNER = 0;
const ROLE_INSTRUCTOR = 1000;
*/
$project_is_open = (isset($current_project['state']) ? $current_project['state'] : 'open') == 'open';

$project_started_yet = (isset($current_project['release_date'])) &&
                            ( (new DateTime($current_project['release_date'])) <= (new DateTime()) );

$project_selection_closed = (isset($current_project['release_date'])) &&
                                ( (new DateTime($current_project['closing_date'])) <= (new DateTime()) );

if ($project_selection_closed && !$project_is_open) {
    $instructions = $current_project['instructions'];
        $instructions = str_replace("{(ReleaseDate)}", $current_project['release_date'], $instructions);
        $instructions = str_replace("{(ClosingDate)}", $current_project['closing_date'], $instructions);
        $instructions = str_replace("{(SelectMin)}", $current_project['min_selections'], $instructions);
        $instructions = str_replace("{(SelectMax)}", $current_project['max_selections'], $instructions);

    $current_project['instructions'] = $instructions;
}

$context = [
    'instructor' => $USER->instructor,
    'styles' => [addSession('static/css/app.min.css'), addSession('static/css/custom.css'),
                    addSession('static/css/jquery-msgpopup.css'),
                    addSession('static/css/datatables.min.css'),
                    addSession('static/css/dataTables.bootstrap4.min.css'),
                    addSession('static/third-party/trumbowyg/ui/trumbowyg.min.css'),
                    addSession('static/third-party/trumbowyg/plugins/mention/ui/trumbowyg.mention.min.css')],
    'scripts' => [addSession('static/js/app.js'),
                    addSession('static/js/moment.min.js'),
                    addSession('static/js/jquery-msgpopup.js'),
                    addSession('static/js/datatables.min.js'),
                    addSession('static/js/Sortable.min.js'),
                    addSession('static/third-party/trumbowyg/trumbowyg.min.js'),
                    addSession('static/third-party/trumbowyg/plugins/mention/trumbowyg.mention.min.js')],
    'debug' => $tool['debug'],

    'current_project' => $current_project,
    'state' => isset($current_project['state']) ? $current_project['state'] : 'open',

    'allocation_groups' => $groups,
    'participants' => $no_users,

    'project_started' => $project_started_yet,
    'project_closed' => $project_selection_closed,

    'configure_url' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/set_configure.php'))),
    'get_student_selections_url' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/get_student_selections.php'))),

    /////////////////////
    'allocate' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('scripts/perl.php'))),

    'updatestate' =>addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/set_state.php'))),
    'assign' =>addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/assign.php'))),
    'checkwaitingsites' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('scripts/check.pl'))),
    'get_state_url' => addSession(str_replace("\\","/",$CFG->getCurrentFileUrl('actions/check_state.php'))),
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
