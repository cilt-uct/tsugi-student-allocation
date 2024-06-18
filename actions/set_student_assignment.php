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

    if (isset($_POST['type'])) {

        switch ($_POST['type']) {
            case 'change':
                $result['success'] = $allocationDAO->changeStudentAssign($current_project['project_id'], $_POST['assignedGroup'], $_POST['studentId']) ? 1 : 0;
                $result['data'] = $result['success']===1 ? 'Inserted' : 'Error Inserting';
              break;
            case 'check':
                $result['data'] = $allocationDAO->getGroupStatus($current_project['project_id'], $_POST['assignedGroup']);
                $result['success'] = isset($result['data']['c']);
              break;
            default:
              //code block
        }
    } else {
        $result['data'] = $_POST;
    }

}

echo json_encode($result);
exit;
