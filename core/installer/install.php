<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) 2006-2009 Kimai-Development-Team
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Perform the installation by creating all necessary tables
 * and some basic entries.
 */

/**
 * Execute an sql query in the database. The correct database connection
 * will be chosen and the query will be logged with the success status.
 *
 * @param string $query query to execute as string
 */
function exec_query($query)
{
    global $database;

    $success = $database->query($query);

    //Logger::logfile($query);
    if (!$success) {
        $errorInfo = serialize($database->error());
        Logger::logfile('[ERROR] in [' . $query . '] => ' . $errorInfo);
    }
}

function quoteForSql($input)
{
    global $database;

    return '\'' . mysqli_real_escape_string($database->link, $input) . '\'';
}


//        MAIN        MAIN        MAIN        MAIN        MAIN        MAIN        //
//        MAIN        MAIN        MAIN        MAIN        MAIN        MAIN        //
if (!isset($_REQUEST['accept'])) {
    header("Location: http://${_SERVER['SERVER_NAME']}/installer/index.php?disagreedGPL=1");
    exit;
}
global $database, $kga, $view;
include('../includes/basics.php');


Logger::logfile('-- begin install ----------------------------------');

// if any of the queries fails, this will be true
$errors = false;
$p      = $kga['server_prefix'];


//     STRUCTURE          STRUCTURE          STRUCTURE          STRUCTURE     //
$query =
    "CREATE TABLE IF NOT EXISTS `${p}user` (
        `user_id` int(10) unsigned NOT NULL PRIMARY KEY,
        `trash` TINYINT(1) unsigned NOT NULL default '0',
        `active` TINYINT(1) unsigned NOT NULL default '1',
        `ban` int(1) unsigned NOT NULL default '0',
        `ban_time` int(10) unsigned NOT NULL default '0',
        `last_project` int(10) unsigned NOT NULL default '1',
        `last_activity` int(10) unsigned NOT NULL default '1',
        `last_record` int(10) unsigned NOT NULL default '0',
        `global_role_id` int(10) unsigned NOT NULL,
        `password_reset_hash` char(32) NULL DEFAULT NULL,
        `name` varchar(160) NOT NULL,
        `alias` varchar(160),
        `mail` varchar(80) NOT NULL DEFAULT '',
        `password` varchar(64) NULL DEFAULT NULL,
        `secure` varchar(60) NOT NULL default '0',
        `timeframe_begin` varchar(60) NOT NULL default '0',
        `timeframe_end` varchar(60) NOT NULL default '0',
        `apikey` varchar(30) NULL DEFAULT NULL,
        UNIQUE KEY `name` (`name`),
        UNIQUE KEY `apikey` (`apikey`)
    ) ENGINE=InnoDB;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}preference` (
        `user_id` int(10) unsigned NOT NULL,
        `option` varchar(255) NOT NULL,
        `value` varchar(255) NOT NULL,
        PRIMARY KEY (`user_id`,`option`)
    ) ENGINE=InnoDB;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}activity` (
        `activity_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `visible` TINYINT(1) unsigned NOT NULL DEFAULT '1',
        `filter` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `trash` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL,
        `comment` TEXT NOT NULL
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}group` (
        `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `trash` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `name` varchar(160) NOT NULL
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}group_user` (
        `group_id` int(10) unsigned NOT NULL,
        `user_id` int(10) unsigned NOT NULL,
        `membership_role_id` int(10) unsigned NOT NULL,
        PRIMARY KEY (`group_id`,`user_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

// group/customer cross-table (groups n:m customers)
$query =
    "CREATE TABLE IF NOT EXISTS `${p}group_customer` (
        `group_id` int(10) unsigned NOT NULL,
        `customer_id` int(10) unsigned NOT NULL,
        UNIQUE (`group_id` ,`customer_id`)
    ) ENGINE=InnoDB;";
exec_query($query);

// group/project cross-table (groups n:m projects)
$query =
    "CREATE TABLE IF NOT EXISTS `${p}group_project` (
        `group_id` int(10) unsigned NOT NULL,
        `project_id` int(10) unsigned NOT NULL,
        UNIQUE (`group_id` ,`project_id`)
    ) ENGINE=InnoDB;";
exec_query($query);

// group/event cross-table (groups n:m events)
$query =
    "CREATE TABLE IF NOT EXISTS `${p}group_activity` (
        `group_id` int(10) unsigned NOT NULL,
        `activity_id` int(10) unsigned NOT NULL,
        UNIQUE (`group_id` ,`activity_id`)
    ) ENGINE=InnoDB;";
exec_query($query);

// project/event cross-table (projects n:m events)
$query =
    "CREATE TABLE IF NOT EXISTS `${p}project_activity` (
        `project_id` int(10) unsigned NOT NULL,
        `activity_id` int(10) unsigned NOT NULL,
        `budget` DECIMAL( 10, 2 ) NULL DEFAULT '0.00',
        `effort` DECIMAL( 10, 2 ) NULL ,
        `approved` DECIMAL( 10, 2 ) NULL,
        UNIQUE (`project_id` ,`activity_id`)
    ) ENGINE=InnoDB;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}customer` (
        `customer_id` int(10) unsigned NOT NULL PRIMARY KEY,
        `visible` TINYINT(1) unsigned NOT NULL DEFAULT '1',
        `filter` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `trash` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `password_reset_hash` char(32) NULL DEFAULT NULL,
        `name` varchar(80) NOT NULL,
        `password` varchar(64),
        `secure` varchar(60) NOT NULL default '0',
        `comment` TEXT NOT NULL,
        `company` varchar(80) NOT NULL,
        `vat_rate` varchar(10) DEFAULT '0',
        `contact` varchar(80) NOT NULL,
        `street` varchar(120) NOT NULL,
        `zipcode` varchar(16) NOT NULL,
        `city` varchar(80) NOT NULL,
        `phone` varchar(16) NOT NULL,
        `fax` varchar(16) NOT NULL,
        `mobile` varchar(16) NOT NULL,
        `mail` varchar(80) NOT NULL,
        `homepage` varchar(255) NOT NULL,
        `timezone` varchar(32) NOT NULL
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}project` (
        `project_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `customer_id` int(3) NOT NULL,
        `visible` TINYINT(1) unsigned NOT NULL DEFAULT '1',
        `filter` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `trash` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `internal` TINYINT( 1 ) NOT NULL DEFAULT 0,
        `budget` decimal(10,2) NOT NULL DEFAULT '0.00',
        `effort` DECIMAL( 10, 2 ) NULL,
        `approved` DECIMAL( 10, 2 ) NULL,
        `name` varchar(80) NOT NULL,
        `comment` TEXT NOT NULL,
        INDEX ( `customer_id` )
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}timesheet` (
        `time_entry_id` int(10) unsigned unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `start` int(10) unsigned NOT NULL default '0',
        `end` int(10) unsigned NOT NULL default '0',
        `duration` int(6) NOT NULL default '0',
        `user_id` int(10) unsigned NOT NULL,
        `project_id` int(10) unsigned NOT NULL,
        `activity_id` int(10) unsigned NOT NULL,
        `comment_type` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `cleared` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `billable` TINYINT(1) unsigned  NULL,
        `status_id` SMALLINT NOT NULL,
        `rate` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0',
        `fixed_rate` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0',
        `budget` DECIMAL( 10, 2 ) NULL,
        `approved` DECIMAL( 10, 2 ) NULL,
        `description` TEXT NULL,
        `location` VARCHAR(50),
        `ref_code` varchar(30),
        `comment` TEXT NULL DEFAULT NULL,
        INDEX ( `user_id` ),
        INDEX ( `project_id` ),
        INDEX ( `activity_id` )
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}configuration` (
        `option` varchar(255) NOT NULL,
        `value` varchar(255) NOT NULL,
        PRIMARY KEY  (`option`)
    ) ENGINE=InnoDB;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}rate` (
        `user_id` int(10) unsigned DEFAULT NULL,
        `project_id` int(10) unsigned DEFAULT NULL,
        `activity_id` int(10) unsigned DEFAULT NULL,
        `rate` decimal(10,2) NOT NULL,
        UNIQUE KEY(`user_id`, `project_id`, `activity_id`)
    ) ENGINE=InnoDB;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}fixed_rate` (
        `project_id` int(10) unsigned DEFAULT NULL,
        `activity_id` int(10) unsigned DEFAULT NULL,
        `rate` decimal(10,2) NOT NULL,
        UNIQUE KEY(`project_id`, `activity_id`)
    ) ENGINE=InnoDB;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}expense` (
        `expense_id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
        `user_id` int(10) unsigned NOT NULL,
        `project_id` int(10) unsigned NOT NULL,
        `comment_type` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `refundable` tinyint(1) unsigned NOT NULL default '0' COMMENT 'expense refundable to employee (0 = no, 1 = yes)',
        `cleared` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `multiplier` decimal(10,2) NOT NULL DEFAULT '1.00',
        `value` decimal(10,2) NOT NULL DEFAULT '0.00',
        `description` text NOT NULL,
        `comment` text NOT NULL,
        INDEX ( `user_id` ),
        INDEX ( `project_id` )
    ) ENGINE=InnoDB AUTO_INCREMENT=1;";
exec_query($query);

$query =
    "CREATE TABLE IF NOT EXISTS `${p}status` (
        `status_id` TINYINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `status` VARCHAR( 200 ) NOT NULL
    ) ENGINE = InnoDB;";
exec_query($query);


//     DB DATA         DB DATA         DB DATA         DB DATA         DB DATA         DB DATA    //

// The included script only sets up the initial permissions.
// Permissions that were later added follow below.
require('installPermissions.php');

foreach (array('customer', 'project', 'activity', 'group', 'user') as $object) {
    exec_query("ALTER TABLE `${p}global_role` ADD `core__${object}__other_group__view` tinyint unsigned DEFAULT 0;");
    exec_query("UPDATE `${p}global_role` SET `core__${object}__other_group__view` = 1 WHERE `name` = 'Admin';");
}

exec_query("INSERT INTO `${p}status` (`status_id` ,`status`) VALUES ('1', 'open'), ('2', 'review'), ('3', 'closed');");

// GROUPS   //
$defaultGroup = $kga['lang']['defaultGroup'];
$query        = "INSERT INTO `${p}group` (`name`) VALUES ('admin');";
exec_query($query);


// ACTIVITY //
$query = "INSERT INTO `${p}activity` (`activity_id`, `name`, `comment`) VALUES (1, '" . $kga['lang']['testActivity'] . "', '');";
exec_query($query);

//  CUSTOMER  //
$query = "INSERT INTO `${p}customer`
(`customer_id`, `name`, `comment`, `company`, `vat_rate`, `contact`, `street`, `zipcode`, `city`, `phone`, `fax`, `mobile`, `mail`, `homepage`, `timezone`) VALUES
(1, '" . $kga['lang']['testCustomer'] . "', '', '', '', '', '', '', '', '', '', '', '',''," . quoteForSql($_REQUEST['timezone']) . ');';
exec_query($query);
//  CUSTOMER - PREFERENCES  //
$query = "INSERT INTO `${p}preference` (`user_id`,`option`,`value`) VALUES
            ('1','ui.autoselection','1'),
            ('1','ui.flip_project_display','0'),
            ('1','ui.hide_cleared_entries','0'),
            ('1','ui.hide_overlap_lines','1'),
            ('1','ui.language','" . $kga['pref']['language'] . "'),
            ('1','ui.no_fading','0'),
            ('1','ui.open_after_recorded','0'),
            ('1','ui.project_comment_flag','0'),
            ('1','ui.quickdelete','0'),
            ('1','ui.rowlimit','100'),
            ('1','ui.show_comments_by_default','0'),
            ('1','ui.show_ids','0'),
            ('1','ui.show_ref_code','1'),
            ('1','ui.skin','standard'),
            ('1','ui.sublist_annotations','2'),
            ('1','ui.timezone'," . quoteForSql($_REQUEST['timezone']) . "),
            ('1','ui.user_list_hidden','100')
            ;";


//  PROJET  //
$query = "INSERT INTO `${p}project` (`project_id`, `customer_id`, `name`, `comment`) VALUES (1, 1, '" . $kga['lang']['testProject'] . "', '');";
exec_query($query);


//  USER - ADMIN  //
$adminPassword = password_encrypt('changeme');
$randomAdminID = random_number(9);
$query         = "INSERT INTO `${p}user` (`user_id`,`name`,`mail`,`password`, `global_role_id` ) VALUES ('$randomAdminID','admin','admin@example.com','$adminPassword',1);";
exec_query($query);


//  PREFERENCES - ADMIN  //
$query = "INSERT INTO `${p}preference` (`user_id`,`option`,`value`) VALUES
            ('$randomAdminID','ui.autoselection','1'),
            ('$randomAdminID','ui.flip_project_display','0'),
            ('$randomAdminID','ui.hide_cleared_entries','0'),
            ('$randomAdminID','ui.hide_overlap_lines','1'),
            ('$randomAdminID','ui.language','" . $kga['pref']['language'] . "'),
            ('$randomAdminID','ui.no_fading','0'),
            ('$randomAdminID','ui.open_after_recorded','0'),
            ('$randomAdminID','ui.project_comment_flag','0'),
            ('$randomAdminID','ui.quickdelete','0'),
            ('$randomAdminID','ui.rowlimit','100'),
            ('$randomAdminID','ui.show_comments_by_default','0'),
            ('$randomAdminID','ui.show_ids','0'),
            ('$randomAdminID','ui.show_ref_code','1'),
            ('$randomAdminID','ui.skin','standard'),
            ('$randomAdminID','ui.sublist_annotations','2'),
            ('$randomAdminID','ui.timezone'," . quoteForSql($_REQUEST['timezone']) . "),
            ('$randomAdminID','ui.user_list_hidden','0')
            ;";
exec_query($query);


// CROSS TABLES
$query = "INSERT INTO `${p}group_user` (`group_id`,`user_id`, `membership_role_id`) VALUES ('1','$randomAdminID','1');";
exec_query($query);

$query = "INSERT INTO `${p}group_activity` (`group_id`, `activity_id`) VALUES (1, 1);";
exec_query($query);

$query = "INSERT INTO `${p}group_customer` (`group_id`, `customer_id`) VALUES (1, 1);";
exec_query($query);

$query = "INSERT INTO `${p}group_project` (`group_id`, `project_id`) VALUES (1, 1);";
exec_query($query);


// ADVANCED CONFIGURATION  //
$query = "INSERT INTO `${p}configuration` (`option`, `value`) VALUES 
            ('core.revision', '" . $kga['core.revision'] . "'),
            ('core.version', '" . $kga['core.version'] . "'),
            ('core.status', '" . $kga['core.status'] . "'),
            ('core.ident', '" . $kga['core.ident'] . "'),
            ('admin_mail', 'admin@example.com'),
            ('allow_round_down', '0'),
            ('bill_pct','0,25,50,75,100'),
            ('check_at_startup','0'),
            ('currency_first','0'),
            ('currency_name','Euro'),
            ('currency_sign','€'),
            ('date_format_0','%d.%m.%Y'),
            ('date_format_1','%d.%m.'),
            ('date_format_2','%d.%m.%Y'),
            ('decimal_separator',','),
            ('default_status_id', '4'),
            ('duration_with_seconds','0'),
            ('edit_limit','-'),
            ('exact_sums','0'),
            ('lastdbbackup', '0'),
            ('login', '1'),
            ('login_ban_time', '900'),
            ('login_tries', '3'),
            ('round_minutes', '0'),
            ('round_precision','0'),
            ('round_seconds', '0'),
            ('round_timesheet_entries', '0' ),
            ('show_day_separator_lines','1'),
            ('show_gab_breaks','0'),
            ('show_record_again','1'),
            ('show_sensible_data','0'),
            ('show_update_warn','1'),
            ('ref_num_editable','1'),
            ('vat_rate','0'),

            ('ud.autoselection','1'),
            ('ud.flip_project_display','0'),
            ('ud.hide_cleared_entries','0'),
            ('ud.hide_overlap_lines','1'),
            ('ud.language','" . $kga['pref']['language'] . "'),
            ('ud.no_fading','0'),
            ('ud.open_after_recorded','0'),
            ('ud.project_comment_flag','0'),
            ('ud.quickdelete','0'),
            ('ud.rowlimit','100'),
            ('ud.show_comments_by_default','0'),
            ('ud.show_ids','0'),
            ('ud.show_ref_code','0'),
            ('ud.skin','standard'),
            ('ud.sublist_annotations','2'),
            ('ud.timezone'," . quoteForSql($_REQUEST['timezone']) . "),
            ('ud.user_list_hidden','0')
            ;";
// 17 x ud.preferences
exec_query($query);

if ($errors) {

    set_include_path(
        implode(
            PATH_SEPARATOR,
            array(
                realpath(WEBROOT . '/libraries/'),
            )
        )
    );

    require_once 'Zend/Loader/Autoloader.php';
    Zend_Loader_Autoloader::getInstance();

    $view = new Zend_View();
    $view->setBasePath(WEBROOT . '/templates');

    $view->headline = $kga['lang']['errors'][1]['hdl'];
    $view->message  = $kga['lang']['errors'][1]['txt'];
    echo $view->render('misc/error.php');
    Logger::logfile('-- showing install error --------------------------');
}
else {
    Logger::logfile('-- installation finished without error ------------');
    header('Location: ../index.php');
}
