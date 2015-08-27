<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) 2006-2009 Kimai-Development-Team // http://www.kimai.org
 *
 * Kimai-i is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai-i is distributed in the hope that it will be useful,
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

    $result = $database->query($query);

    //Logger::logfile($query);
    if ($result === false) {
        $errorInfo = serialize($database->error());
        Logger::logfile('[ERROR] in [' . $query . '] => ' . $errorInfo);
    }
}

function quoteForSql($input)
{
    global $database;

    return '\'' . mysqli_real_escape_string($database->link, $input) . '\'';
}

/*
 *         MAIN        MAIN        MAIN        MAIN        MAIN        MAIN
 *         MAIN        MAIN        MAIN        MAIN        MAIN        MAIN
 *         MAIN        MAIN        MAIN        MAIN        MAIN        MAIN
 *
*/

if (!isset($_REQUEST['accept'])) {
    header("Location: http://${_SERVER['SERVER_NAME']}/installer/index.php?disagreedGPL=1");
    exit;
}
global $database, $kga, $view;
include('../includes/basics.php');

if (isset($kga['admin_mail'])) {
    //CN safety from re-installing
    header("location:http://${_SERVER['SERVER_NAME']}/index.php");
    exit;
}


Logger::logfile('-- begin install ----------------------------------');

// if any of the queries fails, this will be true
$errors = false;
$p      = $kga['server_prefix'];


//     STRUCTURE          STRUCTURE          STRUCTURE          STRUCTURE     //
$kimaii_sql_file = WEBROOT . 'installer/kimaii.sql';
if (!file_exists($kimaii_sql_file)) {
    Logger::logfile('kimaii.sql is missing... exiting');
    die('Database schema is missing, can not continue, exiting.');
}
$query = file_get_contents($kimaii_sql_file);
$query = str_replace('kimaii__', $p, $query);
$result = mysqli_multi_query($database->link, $query);
if ($result === false) {
    Logger::logfile('Failed table creation, exiting.');
    die('Failed table creation, exiting.');
}
while (mysqli_more_results($database->link)) {
    mysqli_next_result($database->link);  //flush multi_query
}


//     DB DATA         DB DATA         DB DATA         DB DATA         DB DATA         DB DATA    //

// The included script only sets up the initial permissions.
// Permissions that were later added follow below.
require('installPermissions.php');

foreach (array('customer', 'project', 'activity', 'group', 'user') as $object) {
    exec_query("ALTER TABLE `{$p}global_role` ADD `core__${object}__other_group__view` tinyint unsigned DEFAULT 0;");
    exec_query("UPDATE `{$p}global_role` SET `core__${object}__other_group__view` = 1 WHERE `name` = 'Admin';");
}

exec_query("INSERT INTO `{$p}status` (`status_id` ,`status`) VALUES ('1', 'open'), ('2', 'review'), ('3', 'closed');");

// GROUPS   //
$defaultGroup = $kga['dict']['defaultGroup'];
$query        = "INSERT INTO `{$p}group` (`name`) VALUES ('admin');";
exec_query($query);


// ACTIVITY //
$query = "INSERT INTO `{$p}activity` (`activity_id`, `name`, `comment`) VALUES (1, '" . $kga['dict']['testActivity'] . "', '');";
exec_query($query);

//  CUSTOMER  //
$query = "INSERT INTO `{$p}customer`
(`customer_id`, `name`, `comment`, `company`, `vat_rate`, `contact`, `street`, `zipcode`, `city`, `phone`, `fax`, `mobile`, `mail`, `homepage`, `timezone`) VALUES
(1, '{$kga['dict']['testCustomer']}', '', '', '', '', '', '', '', '', '', '', '',''," . quoteForSql($_REQUEST['timezone']) . ');';
exec_query($query);
//  CUSTOMER - PREFERENCES  //
$query = "INSERT INTO `{$p}preference` (`user_id`,`option`,`value`) VALUES
            ('1','ui.autoselection','1'),
            ('1','ui.flip_project_display','0'),
            ('1','ui.hide_cleared_entries','0'),
            ('1','ui.hide_overlap_lines','1'),
            ('1','ui.language','{$kga['pref']['language']}'),
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
$query = "INSERT INTO `{$p}project` (`project_id`, `customer_id`, `name`, `comment`) VALUES (1, 1, '" . $kga['dict']['testProject'] . "', '');";
exec_query($query);


//  USER - ADMIN  //
$adminPassword = password_encrypt('changeme');
$randomAdminID = random_number(9);
$query         = "INSERT INTO `{$p}user` (`user_id`,`name`,`mail`,`password`, `global_role_id` ) VALUES ('$randomAdminID','admin','admin@example.com','$adminPassword',1);";
exec_query($query);


//  PREFERENCES - ADMIN  //
$query = "INSERT INTO `{$p}preference` (`user_id`,`option`,`value`) VALUES
            ('$randomAdminID','ui.autoselection','1'),
            ('$randomAdminID','ui.flip_project_display','0'),
            ('$randomAdminID','ui.hide_cleared_entries','0'),
            ('$randomAdminID','ui.hide_overlap_lines','1'),
            ('$randomAdminID','ui.language','{$kga['pref']['language']}'),
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
$query = "INSERT INTO `{$p}group_user` (`group_id`,`user_id`, `membership_role_id`) VALUES ('1','$randomAdminID','1');";
exec_query($query);

$query = "INSERT INTO `{$p}group_activity` (`group_id`, `activity_id`) VALUES (1, 1);";
exec_query($query);

$query = "INSERT INTO `{$p}group_customer` (`group_id`, `customer_id`) VALUES (1, 1);";
exec_query($query);

$query = "INSERT INTO `{$p}group_project` (`group_id`, `project_id`) VALUES (1, 1);";
exec_query($query);


// ADVANCED CONFIGURATION  //
$sql_timezone = quoteForSql($_REQUEST['timezone']);
$query        = "INSERT INTO `{$p}configuration` (`option`, `value`) VALUES
            ('core.revision', '{$kga['core.revision']}'),
            ('core.version', '{$kga['core.version']}'),
            ('core.status', '{$kga['core.status']}'),
            ('core.ident', '{$kga['core.ident']}'),
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
            ('ud.language','{$kga['pref']['language']}'),
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
            ('ud.timezone',{$sql_timezone}),
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

    $view->headline = $kga['dict']['errors'][1]['hdl'];
    $view->message  = $kga['dict']['errors'][1]['txt'];
    echo $view->render('misc/error.php');
    Logger::logfile('-- showing install error --------------------------');
}
else {
    Logger::logfile('-- installation finished without error ------------');
    header('Location: ../index.php');
}
