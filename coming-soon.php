<?php
require_once('../config.php');
include 'src/Template.php';

// Start of the output
$OUTPUT->header();

$context = [
    'styles'     => [ addSession('static/css/app.min.css'), ],
];

Template::view('templates/header.html', $context);

$OUTPUT->bodyStart();

?>
<div class="bgnew"></div>
<?php
$OUTPUT->splashPage(
  "", 
    __("<h2>Coming Soon!<h2>")
);

$OUTPUT->footerStart();

$OUTPUT->footerEnd();
