<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$result = ['success' => 0, 'data' => 'Invalid request method. Please use POST.'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $site_id = $LAUNCH->ltiRawParameter('context_id','none');
    $EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

    $allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 1000);
    $current_project = $allocationDAO->getProject();

    $student_list = [];
    if (isset($_POST['allocation'])) {
        if ($_POST['allocation'] !== '') {
          $student_list = explode(',', $_POST['allocation']);
        }
    }

    $result['success'] = $allocationDAO->changeGroupStudentAssignment($current_project['project_id'],
                                                              $_POST['group_id'], $student_list) ? 1 : 0;
    $result['data'] = $result['success']===1 ? 'Updated Group' : 'Error Updating Group';
    // $result['data'] = $_POST;
}

echo json_encode($result);
exit;
