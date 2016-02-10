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
 * The Kimaii Global Array ($kga) is initialized here. It is used throught
 * all functions, processors, etc.
 */

global $kga;

require(__DIR__ . '/version.php');

// ------------------------------------------------------------------------------------------------------------------------------------------------
$kga['show_sensible_data'] = 0;         // set to 1 to display sensible data in the debug/developer extension
                                        // CAUTION - THINK TWICE IF YOU REALLY WANNA DO THIS AND DON'T FORGET TO TURN IT OFF IN A PRODUCTION ENVIRONMENT!!!
                                        // DON'T BLAME US - YOU HAVE BEEN WARNED!

$kga['logfile_lines']  = 100;           // number of lines shown from the logfile in debug extension. Set to "@" to display the entire file (might freeze your browser...)
$kga['delete_logfile'] = 1;             // can the logfile be cleaned via debug_ext?

$kga['utf8'] = 0;                       // set to 1 if utf-8 CONVERSION (!) is needed - this is not always the case,
                                        // depends on server settings

$kga['calender_start'] = '0';           // here you can set a custom start day for the date-picker.
                                        // if this is not set the day of the users first day in the system will be taken
                                        // Format: ... = "DD/MM/YYYY";

$kga['pwdMinLength'] = 4;               // set your password's minimum length


// ------------------------------------------------------------------------------------------------------------------------------------------------
// load vars from autoconf.php into kga
$kga['server_hostname'] = $server_hostname;
$kga['server_database'] = $server_database;
$kga['server_username'] = $server_username;
$kga['server_password'] = $server_password;
$kga['server_prefix']   = $server_prefix;
$kga['password_salt']   = isset($password_salt) ? $password_salt : '';
$kga['authenticator']   = isset($authenticator) ? trim($authenticator) : 'Mysql';


// LANGUAGE default.
if ( ! empty($lang)) {                // previous version of kimaiiuse $lang in autoconf.php
    $kga['pref']['language'] = $lang;
}
if ( ! empty($language)) {            // since v0.10.x $language in autoconf.php
    $kga['pref']['language'] = $language;
}
$kga['pref']['language'] = isset($kga['pref']['language']) ? $kga['pref']['language'] : 'en';


// TIME ZONE default
if ( ! empty($defaultTimezone)) {     // previous version of kimaiiuse $defaultTimezone in autoconf.php
    $kga['pref']['timezone'] = $defaultTimezone;
}
if ( ! empty($timezone)) {            // since v0.10.x $timezone in autoconf.php
    $kga['pref']['timezone'] = $timezone;
}
$kga['pref']['timezone'] = isset($kga['pref']['timezone']) ? $kga['pref']['timezone'] : 'Europe/Berlin';

date_default_timezone_set($kga['pref']['timezone']);


//  SSL - HTTPS  GLOBAL (do not set elsewhere)  //
$kga['https'] = false;
$server_https = ( ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

if ($server_https || $kga['force_ssl']) {
    $kga['https'] = true;
}

if ( ! $server_https && $kga['https']) { // force ssl.
    header("location:https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
}
//DEBUG//
//error_log('<<== SERVER_NAME ==>>'.$_SERVER['SERVER_NAME']);
//error_log('<<== REQUEST_URI ==>>'.$_SERVER['REQUEST_URI']);
//error_log('<<== dirname(SCRIPT_NAME) ==>>'.dirname($_SERVER['SCRIPT_NAME']));


// cleanup global var area.
unset(
    $server_hostname,
    $server_database,
    $server_username,
    $server_password,
    $server_prefix,
    $password_salt,
    $authenticator,
    $lang,
    $language,
    $timezone,
    $defaultTimezone,
    $server_https
);

// @formatter:off
// TABLES NAME CONSTANTS
define('TBL_ACTIVITY',              $kga['server_prefix'] . 'activity');
define('TBL_CONFIGURATION',         $kga['server_prefix'] . 'configuration');
define('TBL_CUSTOMER',              $kga['server_prefix'] . 'customer');
define('TBL_EXPENSE',               $kga['server_prefix'] . 'expense');
define('TBL_FIXED_RATE',            $kga['server_prefix'] . 'fixed_rate');
define('TBL_GLOBAL_ROLE',           $kga['server_prefix'] . 'global_role');
define('TBL_GROUP',                 $kga['server_prefix'] . 'group');
define('TBL_GROUP_ACTIVITY',        $kga['server_prefix'] . 'group_activity');
define('TBL_GROUP_CUSTOMER',        $kga['server_prefix'] . 'group_customer');
define('TBL_GROUP_PROJECT',         $kga['server_prefix'] . 'group_project');
define('TBL_GROUP_USER',            $kga['server_prefix'] . 'group_user');
define('TBL_MEMBERSHIP_ROLE',       $kga['server_prefix'] . 'membership_role');
define('TBL_PREFERENCE',            $kga['server_prefix'] . 'preference');
define('TBL_PROJECT',               $kga['server_prefix'] . 'project');
define('TBL_PROJECT_ACTIVITY',      $kga['server_prefix'] . 'project_activity');
define('TBL_RATE',                  $kga['server_prefix'] . 'rate');
define('TBL_STATUS',                $kga['server_prefix'] . 'status');
define('TBL_TIMESHEET',             $kga['server_prefix'] . 'timesheet');
define('TBL_USER',                  $kga['server_prefix'] . 'user');
// @formatter:on

