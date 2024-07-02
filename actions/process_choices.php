<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();
$result = ['success' => 0, 'msg' => 'Invalid request method. Please use POST.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $site_id = $LAUNCH->ltiRawParameter('context_id','none');
    $EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

    $allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 0);

    $project_id = intval($_POST['project_id']);
    $selectedChoices = $_POST['project-selection'];
    $choiceNumbers = $_POST['choice-number'];

    $selectedGroups = [];
    for ($i = 0; $i < count($selectedChoices); $i++) {
        if (!empty($selectedChoices[$i])) {
            $selectedGroups[] = [
                'choice_number' => $choiceNumbers[$i],
                'group_id' => $selectedChoices[$i]
            ];
        }
    }

    if (count($selectedGroups) > 0) {
        // $result = ['success' => 1, 'msg' => array_merge($_POST), 'groups' => $selectedGroups];

        $out = $allocationDAO->addChoices($project_id, $USER->id, $selectedGroups);

        $result['success'] = $out ? 1 : 0;
        $result['msg'] = $out ? 'success' : 'Group choices inserted successfully.';
    } else {
        $result = ['success' => 0, 'msg' => 'Please fill in a selection choice.'];
    }
}

echo json_encode($result);
exit;
