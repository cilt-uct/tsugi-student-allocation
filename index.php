<?php
require_once('../config.php');
include 'tool-config_dist.php';

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

$site_id = $LAUNCH->ltiRawParameter('context_id','none');
$EID = $LAUNCH->ltiRawParameter('ext_d2l_username', $LAUNCH->ltiRawParameter('lis_person_sourcedid', $LAUNCH->ltiRawParameter('ext_sakai_eid', $USER->id)));

if ($USER->instructor) {
    $PDOX->queryDie("REPLACE INTO {$CFG->dbprefix}allocation_user (user_id, EID, role) VALUES (:user_id, :EID, :role)",
                        array(':user_id' => $USER->id, ':EID' => $EID, ':role' => 1000));

    header('Location: ' . addSession('instructor-home.php'));
} else {
  $PDOX->queryDie("REPLACE INTO {$CFG->dbprefix}allocation_user (user_id, EID, role) VALUES (:user_id, :EID, :role)",
                        array(':user_id' => $USER->id, ':EID' => $EID, ':role' => 0));
    header('Location: ' . addSession('student-home.php'));
}
