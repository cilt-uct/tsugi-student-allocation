<?php
require_once "../config.php";

use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;

// Sanity checks
$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

if ( isset($_POST['json']) ) {
    Settings::linkSet('json', $_POST['json']);
    header( 'Location: '.addSession('index.php') ) ;
    return;
}


$json = Settings::linkGet('json');
$json = LTI::jsonIndent($json);

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
if ( ! $USER->instructor ) die("Requires instructor role");

?>
<form method="post" style="margin-left:5%;">
<textarea name="json" rows="25" cols="80" style="width:95%" >
<?php echo($json); ?>
</textarea>
<p>
<input type="submit" value="Save">
<input type=submit name=doCancel onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Cancel"></p>
</form>
<?php

$OUTPUT->footer();
