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
        
    $tool_id = htmlspecialchars($_POST['tool-id'], ENT_QUOTES, 'UTF-8');
    $tool_name = htmlspecialchars($_POST['tool-name'], ENT_QUOTES, 'UTF-8');
    $instructions = htmlspecialchars($_POST['instructions'], ENT_QUOTES, 'UTF-8');
    $min_selections = intval($_POST['min-selections']);
    $max_selections = intval($_POST['max-selections']);
    $release_date = date('Y-m-d', strtotime($_POST['release-date']));
    $closing_date = date('Y-m-d', strtotime($_POST['closing-date']));
    $groups_doc =  isset($_POST['groups-doc']) ? htmlspecialchars($_POST['groups-doc'], ENT_QUOTES, 'UTF-8') : '';

    $groups = array();
    if (isset($_POST['group-id']) && isset($_POST['group-title']) && isset($_POST['group-size'])) {
        $group_ids = $_POST['group-id'];
        $group_titles = $_POST['group-title'];
        $group_sizes = $_POST['group-size'];
        for ($i = 0; $i < count($group_ids); $i++) {
            $groups[] = array(
                'id' => $group_ids[$i],
                'title' => htmlspecialchars($group_titles[$i], ENT_QUOTES, 'UTF-8'),
                'size' => intval($group_sizes[$i])
            );
        }
    }

    // Include parameters in the result
    $result['parameters'] = array(
        'tool_id' => $tool_id,
        'tool_name' => $tool_name,
        'instructions' => $instructions,
        'min_selections' => $min_selections,
        'max_selections' => $max_selections,
        'release_date' => $release_date,
        'closing_date' => $closing_date,
        'groups_doc' => $groups_doc,
        'groups' => $groups
    );

    $result['success'] = $allocationDAO->configureAllocation($LINK->id, $USER->id, $site_id, $tool_name, $instructions, 
                        $min_selections, $max_selections, $release_date, $closing_date, $groups_doc, $groups) ? 1 : 0;

    $result['msg'] = $result['success'] ? 'Inserted' : 'Error Inserting';    
}

echo json_encode($result);
exit;
