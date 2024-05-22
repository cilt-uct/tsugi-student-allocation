<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);

$result = ['success' => 0, 'msg' => 'requires POST'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result['msg'] = $_POST;
    $student_id = $_POST['studentId'];
    $group_id = $_POST['assignedGroup'];

    $result['success'] = $allocationDAO->assignUser($LINK->id, $USER->id, $student_id, $group_id) ? 1 : 0;

    $result['msg'] = $result['success']===1 ? 'Inserted' : 'Error Inserting';    
}

echo json_encode($result);
exit;
