<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);

$result = ['success' => 0, 'msg' => 'requires POST'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result['msg'] = $_POST;
  $selectedProjects = json_decode(file_get_contents('php://input'), true);
  $choices = array();

  if ($selectedProjects === null) {
      $result['success'] = 0;
      $result['msg'] = "Error parsing JSON data";
  } else {
    try {
      $result['success'] = $allocationDAO->addChoices($LINK->id, $USER->id, $selectedProjects) ? 1 : 0;
      
      $result['msg'] = "Group choices inserted successfully.";
    } catch (Exception $e) {
        $result['success'] = 0;
        $result['msg'] = "Error inserting group choices: " . $e->getMessage();
    }
  }
}

echo json_encode($result);
exit;
