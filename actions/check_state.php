<?php
require_once "../../config.php";
include "../tool-config_dist.php";
require_once("../dao/AllocationDAO.php");

use \Tsugi\Core\LTIX;
use \Allocation\DAO\AllocationDAO;

$LAUNCH = LTIX::requireData();

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$allocationDAO = new AllocationDAO($PDOX, $CFG->dbprefix, $tool);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result['msg'] = $_GET;

    $result['success'] = $allocationDAO->checkState($LINK->id, $site_id);

}

echo json_encode($result);
exit;