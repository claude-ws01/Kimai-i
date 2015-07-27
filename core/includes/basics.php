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
 * Basic initialization takes place here.
 * From loading the configuration to connecting to the database this all is done
 * here.
 */

define('WEBROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

if (file_exists(WEBROOT . 'includes/autoconf.php')) {
    require WEBROOT . 'includes/autoconf.php';
}
else {
    header('location:installer/index.php');
    exit;
}

if (file_exists(WEBROOT . 'dev')) {
    define('IN_DEV', 1);
}
else {
    define('IN_DEV', 0);
}

if (file_exists(WEBROOT . 'debug')) {
    define('DEBUG_JS', '.debug');
}
else {
    define('DEBUG_JS', '.min');
}

//DEBUG// error_log('<<================================== kimai testing error log ==================================>');
global $database, $kga, $translations, $view;

require WEBROOT . 'includes/vars.php';
require WEBROOT . 'libraries/Kimai/Database/kimai.php';
require WEBROOT . 'includes/classes/format.class.php';
require WEBROOT . 'includes/classes/logger.class.php';
require WEBROOT . 'includes/classes/translations.class.php';
require WEBROOT . 'includes/classes/rounding.class.php';
require WEBROOT . 'includes/classes/extensions.class.php';
require WEBROOT . 'includes/func.php';

$database = new Kimai_Database_Mysql(
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


//  initialize $kga (conf & pref) //
config_init();

//  DB need an update?   //
$tranlastion_load_from_db = false;
if ($_SERVER['DOCUMENT_URI'] !== '/db_restore.php'
    && $_SERVER['DOCUMENT_URI'] !== '/installer/install.php'
) {
    checkDBversion('.');
    $tranlastion_load_from_db = true;
}


//################################################//
// FROM THIS POINT ON, WE NEED AN UP-TO-DATE DB   //
//################################################//


//  DBget the config and prefs  //
if ($_SERVER['DOCUMENT_URI'] !== '/installer/install.php') {
    $database->config_load();
}


defined('APPLICATION_PATH') ||
define('APPLICATION_PATH', realpath(__DIR__ . '/../'));
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
$translations = new Translations($tranlastion_load_from_db, $kga['pref']['language']);

