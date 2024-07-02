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

    // Retrieve the raw POST data : Content-Type: application/json
    $jsonData = file_get_contents('php://input');

    // Decode the JSON data into a PHP associative array
    $data = json_decode($jsonData, true);

    $result['success'] = $allocationDAO->setState($data['tool-state'])? 1 : 0;;
    $result['data'] = $result['success']===1 ? 'Inserted' : 'Error Inserting';

}

echo json_encode($result);
exit;