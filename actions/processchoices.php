<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);
$eid = $LAUNCH->ltiRawParameter('user_id', 'none');
$user_name = $LAUNCH->ltiRawParameter('lis_person_sourcedid', 'none');

$result = ['success' => 0, 'msg' => 'requires POST'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = ['msg' => $_POST, 'success' => false];
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

  try {
    $result['success'] = $allocationDAO->addChoices($LINK->id, $eid, $user_name, $selectedGroups);
    
    $result['msg'] = "Group choices inserted successfully.";
  } catch (Exception $e) {
      $result['success'] = 0;
      $result['msg'] = "Error inserting group choices: " . $e->getMessage();
  }
}

echo json_encode($result);
exit;
