<?php
// Configuration file - copy from tool-config_dist.php to tool-config.php
// and then edit.

if ((basename(__FILE__, '.php') != 'tool-config') && (file_exists('tool-config.php'))) {
    include 'tool-config.php';
    return;
}

# The configuration file - stores the paths to the scripts
$tool = array();
$tool['debug'] = FALSE;
$tool['active'] = TRUE; # if false will show coming soon page
