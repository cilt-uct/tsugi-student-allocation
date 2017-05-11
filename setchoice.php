<?php

require_once "../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
$LAUNCH = LTIX::requireData();
//$PDOX = LTIX::getConnection();

$p = $CFG->dbprefix;

//if not instructor, send 403
//if not "logged in", send 401
//if $_POST['entries'] not set, send 400

if (!isset($_POST['selection'])) {
  header('HTTP/1.1 400 selection parameter not set');
  echo '<h1>Form submission invalid<h1>';
  echo '<p>Please submit an `selection` post parameter, with form:</p>';
  echo '<p>entriselectiones=[{}]</p>';
  exit();
}

$selection = '{ "result": {}, "selection": '. json_encode($_POST['selection']) .'}';
//var_dump($_POST['selection']);
//var_dump($RESULT->getJSON());

if (json_last_error() == JSON_ERROR_NONE) {

  $RESULT->setJSON($selection);
  print '{status: 1}';
} else {
  print '{status: 0}';
}


