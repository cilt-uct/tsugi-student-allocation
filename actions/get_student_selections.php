<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function getOrderColumnName($order, $columns) {
    $orderColumnIndex = $order[0]["column"];

    if ($orderColumnIndex >= 0 && $orderColumnIndex < count($columns)) {
        $orderColumnName = $columns[$orderColumnIndex]["data"];
        return [$orderColumnName, $order[0]["dir"]];
    } else {
        return [null, 'asc'];
    }
}

$result = ['success' => 0, 'data' => 'Invalid request method. Please use POST.'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   // Retrieve the raw POST data : Content-Type: application/json

    $jsonData = file_get_contents('php://input');

    // Decode the JSON data into a PHP associative array
    $data = json_decode($jsonData, true);

    // Check if decoding was successful
    if ($data !== null) {
        // Access the data
        // $result['data']  = $data; // raw
        $site_id = $LAUNCH->ltiRawParameter('context_id','none');
        $EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

        $allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $LINK->id, $site_id, $USER->id, $EID, 1000);

        list($order_column, $order_direction) = getOrderColumnName($data['order'], $data['columns']);
        $result = $allocationDAO->getAllStudentChoices($data['project_id'],
                                                                $data['draw'],
                                                                $data['start'], $data['length'],
                                                                $order_column, $order_direction,
                                                                $data['search']['value'], $data['search']['regex']);

    } else {
        // JSON decoding failed
        http_response_code(400); // Bad Request
        $result['data'] = 'Invalid JSON data';
    }


}

echo json_encode($result);
exit;
