<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../config.php";
    $CURRENT_FILE = __FILE__;
}

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    "drop table if exists `{$CFG->dbprefix}allocation_site`",
    "drop table if exists `{$CFG->dbprefix}allocation_group`",
    "drop table if exists `{$CFG->dbprefix}allocation_choice`"
);

$DATABASE_INSTALL = array(
    array("{$CFG->dbprefix}allocation_site",
        "CREATE TABLE `{$CFG->dbprefix}allocation_site` (
            `link_id` INT(11) NOT NULL,
            `site_id` VARCHAR(99) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `instructions` TEXT NULL DEFAULT NULL,
            `min_selections` INT(11) NOT NULL,
            `max_selections` INT(11) NOT NULL,
            `release_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `closing_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `groups_doc` VARCHAR(255),
            `state` ENUM('open','waiting','running','assigned','error') NOT NULL DEFAULT 'open',

            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT(11) NOT NULL,
            `modified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modified_by` INT(11) NOT NULL,

            PRIMARY KEY (`link_id`, `site_id`),
            INDEX `idx_site_id` (`site_id` ASC)
        ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb3"
    ),
    array("{$CFG->dbprefix}allocation_group",
        "CREATE TABLE `{$CFG->dbprefix}allocation_group` (
            `group_id` VARCHAR(5) NOT NULL,
            `link_id` INT(11) NOT NULL,
            `group_name` VARCHAR(255) NOT NULL,
            `group_size` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT(11) NOT NULL,
            `modified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modified_by` INT(11) NOT NULL,

            PRIMARY KEY (`group_id`, `link_id`),
            INDEX `fk_allocation_group_allocation_site_idx` (`link_id` ASC),
            CONSTRAINT `fk_allocation_group_allocation_site`
                FOREIGN KEY (`link_id`)
                REFERENCES `{$CFG->dbprefix}allocation_site` (`link_id`)
                ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3"
    ),
    array( "{$CFG->dbprefix}allocation_choice",
        "CREATE TABLE `{$CFG->dbprefix}allocation_choice` (
            `link_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL,
            `group_id` VARCHAR(5) NOT NULL,
            `choice_id` INT(11) NOT NULL,
            `assigned` TINYINT(1) NOT NULL DEFAULT '0',

            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` VARCHAR(99) NOT NULL,
            `modified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `modified_by` VARCHAR(99) NOT NULL,
            
            PRIMARY KEY (`link_id`, `user_id`, `choice_id`),
            INDEX `fk_allocation_choice_allocation_group_idx` (`group_id` ASC),
            INDEX `idx_user_id` (`user_id` ASC),
            INDEX `idx_group_id` (`group_id` ASC),
            CONSTRAINT `fk_allocation_choice_allocation_group` 
                FOREIGN KEY (`group_id`) 
                REFERENCES `{$CFG->dbprefix}allocation_group` (`group_id`) 
                ON UPDATE NO ACTION,
            CONSTRAINT `fk_allocation_choice_allocation_site`
                FOREIGN KEY (`link_id`)
                REFERENCES `{$CFG->dbprefix}allocation_site` (`link_id`)
                ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3"
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

        // array('allocation_assignment', 'link_id', 'int(11) NOT NULL'),
        // Add more


        // array('allocation_choice', 'link_id', 'int(11) NOT NULL'),
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
