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
 * Handle all AJAX calls from the installer.
 */


// from php documentation at http://www.php.net/manual/de/function.ini-get.php
function return_bytes($val)
{
    $val  = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
        case 'm':
        case 'k':
            $val *= 1024;
    }

    return $val;
}



$axAction = strip_tags($_REQUEST['axAction']);

$javascript = '';
$errors     = 0;

switch ($axAction) {


    /**
     * Check for the requirements of Kimai:
     *  - PHP major version >= 5
     *  - MySQL extension available
     *  - memory limit should be at least 20 MB for reliable PDF export
     */
    case 'checkRequirements':
        if (version_compare(PHP_VERSION, '5.3') < 0) {
            $errors++;
            $javascript .= "$('div.sp_phpversion').addClass('fail');";
        }

        if (!extension_loaded('mysql')) {
            $errors++;
            $javascript .= "$('div.sp_mysql').addClass('fail');";
        }

        // magic quotes was removed in 5.4.0 - so we only check it in lower versions
        if (version_compare(PHP_VERSION, '5.4.0') < 0 &&
            (get_magic_quotes_gpc() == 1 || get_magic_quotes_runtime() == 1)
        ) {
            $errors++;
            $javascript .= "$('div.sp_magicquotes').addClass('fail');";
        }

        if (return_bytes(ini_get('memory_limit')) < 20000000) {
            $javascript .= "$('div.sp_memory').addClass('fail');";
        }


        if (empty($javascript)) {
            $javascript = "$('#installsteps button.sp-button').hide();";
        }

        if (!$errors) {
            $javascript .= "$('#installsteps button.proceed').show();";
        }

        $javascript .= 'resetRequirementsIndicators();' . $javascript;
        echo $javascript;

        break;

    /**
     * Check access rights to autoconf.php, the logfile and the temporary folder.
     */
    case 'checkRights':
        if (!is_writable('../includes/') || (file_exists('../includes/autoconf.php') && !is_writable('../includes/autoconf.php'))) {
            $errors++;
            $javascript .= "$('span.ch_autoconf').addClass('fail');";
        }

        if (!is_writable('../temporary/') || (file_exists('../temporary/logfile.txt') && !is_writable('../temporary/logfile.txt'))) {
            $errors++;
            $javascript .= "$('span.ch_logfile').addClass('fail');";
        }

        if (!is_writable('../temporary/')) {
            $errors++;
            $javascript .= "$('span.ch_temporary').addClass('fail');";
        }

        if ($errors) {
            $javascript .= "$('span.ch_correctit').fadeIn(500);";
        }
        else {
            $javascript = "$('#installsteps button.cp-button').hide();$('#installsteps button.proceed').show();";
        }

        echo $javascript;
        break;


    ////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Create the autoconf.php file.
     */
    case ('write_config'):
        include('../includes/func.php');
        // special characters " and $ are escaped
        $hostname = $_REQUEST['hostname'];
        $database = $_REQUEST['database'];
        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];
        $salt     = password_create(20);
        $prefix   = addcslashes($_REQUEST['prefix'], '"$');
        $language = $_REQUEST['language'];
        $timezone = $_REQUEST['timezone'];

        write_config_file(
            $hostname,
            $database,
            $username,
            $password,
            $salt,
            $prefix,
            'Mysql',
            $language,
            $timezone);

        break;

    /**
     * Create the database.
     */
    case ('make_database');
        $database = $_REQUEST['database'];
        $hostname = $_REQUEST['hostname'];
        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];

        $db_error = false;
        $result   = false;

        $con    = mysqli_connect($hostname, $username, $password);
        $query  = 'CREATE DATABASE `' . $database . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;';
        $result = mysqli_query($con, $query);

        if ($result !== false) {
            echo '1'; // <-- hat geklappt
        }
        else {
            echo '0'; // <-- schief gegangen
        }

        break;

}
