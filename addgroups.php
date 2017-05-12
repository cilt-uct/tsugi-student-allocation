<?php

require_once "../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
$LAUNCH = LTIX::requireData();
//$PDOX = LTIX::getConnection();

$p = $CFG->dbprefix;

$json = Settings::linkGet('json');

//if not instructor, send 403
//if not "logged in", send 401
//if $_POST['entries'] not set, send 400

if (!isset($_POST['entries'])) {
  header('HTTP/1.1 400 entries parameter not set');
  echo '<h1>Form submission invalid<h1>';
  echo '<p>Please submit an `entries` post parameter, with form:</p>';
  echo '<p>entries={"expiry":[expiry time and date], "group":[{name:<string group name>,id:<string group id>,occupancy:<numbertype user limit>]}}</p>';
  exit();
}

$entries = $_POST['entries'];
$entries['active'] = true;

//$input = json_decode($_POST['entries'], true);
//check the input?
//$insertStmt = "INSERT INTO {$CFG->prefix}lti_context (json) values (:json)";
//$PDOX->queryDie(
//  $insertStmt,
//  array('json' => $_POST['entries'])
//);

Settings::linkSet('json', $active);

var_dump(Settings::linkGet('json'));

