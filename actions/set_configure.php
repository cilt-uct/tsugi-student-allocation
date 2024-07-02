<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();
$result = ['success' => 0, 'msg' => 'Invalid request method. Please use POST.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // $data = $_POST;
    // unset($data['instructions']);
    // $result = ['success' => 1, 'msg' => $data];

    $site_id = $LAUNCH->ltiRawParameter('context_id','none');
    $EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

    $allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 1000);

    $project_id = intval($_POST['project_id']);
    $instructions = $_POST['instructions']; //htmlspecialchars($_POST['instructions'], ENT_QUOTES, 'UTF-8');
    $min_selections = intval($_POST['min-selections']);
    $max_selections = intval($_POST['max-selections']);
    $release_date = isset($_POST['release-date']) && $_POST['release-date'] != '' ? date('Y-m-d 00:00:00', strtotime($_POST['release-date'])) : NULL;
    $closing_date = isset($_POST['closing-date']) && $_POST['closing-date'] != '' ? date('Y-m-d 23:59:59', strtotime($_POST['closing-date'])) : NULL;

    $groups = array();
    if (isset($_POST['group-id']) && isset($_POST['group-title']) && isset($_POST['group-size'])) {
        $group_ids = $_POST['group-id'];
        $group_titles = $_POST['group-title'];
        $group_sizes = $_POST['group-size'];

        for ($i = 0; $i < count($group_ids); $i++) {
            $groups[] = array(
                'id' => $group_ids[$i],
                'title' => htmlspecialchars($group_titles[$i], ENT_QUOTES, 'UTF-8'),
                'size' => intval($group_sizes[$i])
            );
        }
    }
    $result = ['success' => 1, 'msg' => $groups];

    // Include parameters in the result
    $result['parameters'] = array(
        'instructions' => $instructions,
        'min_selections' => $min_selections,
        'max_selections' => $max_selections,
        'release_date' => $release_date,
        'closing_date' => $closing_date,
        'groups' => $groups
    );

    try {
        $out = $allocationDAO->updateAllocation($project_id, $LINK->id, $USER->id, $site_id,
                                                        $instructions,
                                                        $min_selections, $max_selections,
                                                        $release_date, $closing_date,
                                                        $groups);
        $result['success'] = $out ? 1 : 0;
        $result['msg'] = $out ? 'success' : 'Could not save the topic information.';

        $current_project = $allocationDAO->getProject();
        $result['parameters']['state'] = isset($current_project['state']) ? $current_project['state'] : 'open';
    } catch (Exception $e) {
        $result['success'] = 0;
    }

    $result['msg'] = $result['success'] ? 'Inserted' : 'Error Inserting';
}

echo json_encode($result);
exit;
