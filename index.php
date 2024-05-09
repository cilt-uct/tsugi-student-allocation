<?php
require_once('../config.php');
include 'tool-config_dist.php';

require_once "dao/AllocationDAO.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);

$allocation_details = $allocationDAO->getSettings($LINK->id,$site_id);
$allocation_groups = $allocationDAO->getGroups($LINK->id,$site_id);

$context = [
  'allocationdetails' => $allocation_details,
  'allocationgroups' => $allocation_groups,
  'siteid' => $site_id,
];

if ($USER->instructor) {
  header('Location: ' . addSession('instructor-home.php', $context));
} else {
    header('Location: ' . addSession('student-home.php', $context));
}