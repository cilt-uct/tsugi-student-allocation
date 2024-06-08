<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../config.php";
    $CURRENT_FILE = __FILE__;
}

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    "drop table if exists `{$CFG->dbprefix}allocation_site`",
    "drop table if exists `{$CFG->dbprefix}allocation_project`",
    "drop table if exists `{$CFG->dbprefix}allocation_group`",
    "drop table if exists `{$CFG->dbprefix}allocation_choice`",
    "drop table if exists `{$CFG->dbprefix}allocation_user`",
);

$DATABASE_INSTALL = array(
    array("{$CFG->dbprefix}allocation_site",
        "CREATE TABLE `{$CFG->dbprefix}allocation_site` (
            `link_id` int NOT NULL,
            `site_id` varchar(99) NOT NULL,
        PRIMARY KEY (`link_id`,`site_id`),
        CONSTRAINT `fk_allocation_site_lti_link1` FOREIGN KEY (`link_id`) REFERENCES `lti_link` (`link_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;"
    ),
    array("{$CFG->dbprefix}allocation_project",
        "CREATE TABLE `{$CFG->dbprefix}allocation_project` (
            `project_id` int NOT NULL AUTO_INCREMENT,
            `link_id` int NOT NULL,
            `site_id` varchar(99) NOT NULL,
            `name` varchar(99) NOT NULL DEFAULT 'Default',
            `instructions` text,
            `min_selections` int NOT NULL DEFAULT '1',
            `max_selections` int NOT NULL DEFAULT '1',
            `release_date` datetime DEFAULT NULL,
            `closing_date` datetime DEFAULT NULL,
            `state` enum('open','waiting','running','assigned','error') NOT NULL DEFAULT 'open',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` int NOT NULL DEFAULT '0',
            `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `modified_by` int NOT NULL DEFAULT '0',
            PRIMARY KEY (`project_id`),
            KEY `idx_site_id` (`name`),
            KEY `fk_allocation_site_lti_link1_idx` (`project_id`),
            KEY `fk_allocation_project_allocation_site1_idx` (`link_id`,`site_id`),
            CONSTRAINT `fk_allocation_project_allocation_site1` FOREIGN KEY (`link_id`, `site_id`) REFERENCES `allocation_site` (`link_id`, `site_id`) ON DELETE CASCADE
          ) ENGINE=InnoDB;"
    ),
    array("{$CFG->dbprefix}allocation_group",
        "CREATE TABLE `{$CFG->dbprefix}allocation_group` (
            `group_id` varchar(5) NOT NULL,
            `project_id` int NOT NULL,
            `group_name` varchar(255) NOT NULL,
            `group_size` varchar(255) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` int NOT NULL,
            `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modified_by` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`group_id`,`project_id`),
        KEY `fk_allocation_group_allocation_site1_idx` (`project_id`),
        CONSTRAINT `fk_allocation_group_allocation_site1` FOREIGN KEY (`project_id`) REFERENCES `allocation_project` (`project_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;"
    ),
    array( "{$CFG->dbprefix}allocation_choice",
        "CREATE TABLE `{$CFG->dbprefix}allocation_choice` (
            `group_id` varchar(5) NOT NULL,
            `user_id` varchar(99) NOT NULL,
            `project_id` int NOT NULL,
            `choice_rank` int NOT NULL DEFAULT '1',
            `assigned` tinyint(1) NOT NULL DEFAULT '0',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` varchar(99) NOT NULL,
            `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modified_by` varchar(99) NOT NULL DEFAULT '0',
        PRIMARY KEY (`group_id`,`user_id`,`project_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `fk_allocation_choice_allocation_group1_idx` (`group_id`),
        KEY `fk_allocation_choice_lti_link1_idx` (`project_id`),
        CONSTRAINT `fk_allocation_choice_lti_link1` FOREIGN KEY (`project_id`) REFERENCES `allocation_project` (`project_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;"
    ),
    array( "{$CFG->dbprefix}allocation_user",
        "CREATE TABLE `{$CFG->dbprefix}allocation_user` (
            `user_id` int NOT NULL,
            `EID` varchar(99) NOT NULL,
            `role` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`user_id`,`EID`),
        CONSTRAINT `fk_allocation_student_lti_user1` FOREIGN KEY (`user_id`) REFERENCES `lti_user` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;"
    )
);
// Database upgrade
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // This is a place to make sure added fields are present
    // if you add a field to a table, put it in here and it will be auto-added
    $add_some_fields = array(
        // --------- Examples below ------
        // array('allocation_group', 'link_id', 'int(11) NOT NULL'),
        // Add more
    );

    foreach ( $add_some_fields as $add_field ) {
        if (count($add_field) != 3 ) {
            echo("Badly formatted add_field");
            var_dump($add_field);
            continue;
        }
        $table = $add_field[0];
        $column = $add_field[1];
        $type = $add_field[2];
        $sql = false;
        if ( $PDOX->columnExists($column, $CFG->dbprefix.$table ) ) {
            if ( $type == 'DROP' ) {
                $sql= "ALTER TABLE {$CFG->dbprefix}$table DROP COLUMN $column";
            } else {
                // continue;
                $sql= "ALTER TABLE {$CFG->dbprefix}$table MODIFY $column $type";
            }
        } else {
            if ( $type == 'DROP' ) continue;
            $sql= "ALTER TABLE {$CFG->dbprefix}$table ADD $column $type";
        }
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    return 202404111000;
};
