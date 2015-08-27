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
 * Basic initialization takes place here.
 * From loading the configuration to connecting to the database this all is done
 * here.
 */

define('WEBROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);


if (file_exists(WEBROOT . 'includes/autoconf.php')) {
    require WEBROOT . 'includes/autoconf.php';
}
else { // no autoconf... goto installer //
    header('location:installer/index.php');
    exit;
}


//CN..deactivate some features in demo mode.
if (file_exists(WEBROOT . '_demo')) {
    define('DEMO_MODE', true);
    include WEBROOT . '_demo';
}
else {
    define('DEMO_MODE', false);
}


//CN..deactivates some features in development. Follow the breadcrumbs.
if (file_exists(WEBROOT . '_dev')) {
    define('IN_DEV', true);
}
else {
    define('IN_DEV', false);
}


//CN..use '.debug.' files instead of '.min.' files
if (file_exists(WEBROOT . '_debug')) {
    define('DEBUG_JS', '.debug');
}
else {
    define('DEBUG_JS', '.min');
}


/*
 *
 * */
global $database, $kga, $translations, $view;

require WEBROOT . 'includes/func.php';


//  initialize $kga (conf & pref) //
config_init();

// more config
$kga['error_log_mail_from'] = '';
$kga['error_log_mail_to']   = '';
$kga['force_ssl']           = false;


// local private config that may replace some $kga values
// called twice. needed here and later
if (file_exists(WEBROOT . 'includes/_localconf.php')) {
    include WEBROOT . 'includes/_localconf.php';
}





require WEBROOT . 'includes/vars.php';
require WEBROOT . 'libraries/Kimai/Database/roles.php';
require WEBROOT . 'includes/classes/format.class.php';
require WEBROOT . 'includes/classes/logger.class.php';
require WEBROOT . 'includes/classes/translations.class.php';
require WEBROOT . 'includes/classes/rounding.class.php';
require WEBROOT . 'includes/classes/extensions.class.php';


$database = new Roles_Mysql(
    $kga['server_hostname'],
    $kga['server_database'],
    $kga['server_username'],
    $kga['server_password'],
    $kga['utf8']);


// PHP & MYSQL WARNINGS //
if (IN_DEV) {
    ini_set('display_errors', '1');
    mysqli_report(MYSQLI_REPORT_ERROR);
}
else {
    ini_set('display_errors', '0');
}

if (!is_object($database) || !$database->isConnected()) {
    die('Kimai-i could not connect to database. Check your autoconf.php.');
}


//  DATABASE UPDATE  //
$tranlastion_load_from_db = false;
if ($_SERVER['DOCUMENT_URI'] !== '/db_restore.php'
    && $_SERVER['DOCUMENT_URI'] !== '/installer/install.php'
) {
    checkDBversion();
    $tranlastion_load_from_db = true;
}


//################################################//
// FROM THIS POINT ON, WE NEED AN UP-TO-DATE DB   //
//################################################//


//  DBget the config and prefs  //
if ($_SERVER['DOCUMENT_URI'] !== '/installer/install.php') {
    $database->config_load();
}

// local private config that may replace some $kga values
// called twice. needed earlier and here
if (file_exists(WEBROOT . 'includes/_localconf.php')) {
    include WEBROOT . 'includes/_localconf.php';
}




defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', $_SERVER['DOCUMENT_ROOT']);
set_include_path(
    implode(
        PATH_SEPARATOR,
        array(realpath(APPLICATION_PATH . '/libraries/'),
        )
    )
);


require_once WEBROOT . 'libraries/Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Kimai');
$autoloader->registerNamespace('MySQL');

Kimai_Registry::setDatabase($database);


// TRANSLATION //
$translations = new Translations($tranlastion_load_from_db);

