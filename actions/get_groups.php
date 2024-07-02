<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$result = ['success' => 0, 'data' => 'Invalid request method. Please use GET.'];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $site_id = $LAUNCH->ltiRawParameter('context_id','none');
    $EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

    $allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 1000);

    $current_project = $allocationDAO->getProject();
    $result['data'] = $allocationDAO->getGroups($current_project['project_id']);
    $result['success'] = 1;

}

echo json_encode($result);
exit;