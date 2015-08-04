<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/cloudeasy/Kimai-i
 * (c) 2015 Claude Nadon
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
 * This script performs updates of the database from any version
 * to the current version.
 */
define('WEBROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

if (file_exists(WEBROOT . 'includes/autoconf.php')) {
    require(WEBROOT . 'includes/autoconf.php');
}
else {
    header('location:installer/index.php');
    exit;
}

//=================== GLOBAL ======================//
global $database, $kga, $translations;

require(WEBROOT . 'includes/vars.php');
require WEBROOT . 'libraries/Kimai/Database/kimai.php';
require(WEBROOT . 'includes/classes/format.class.php');
require(WEBROOT . 'includes/classes/logger.class.php');
require(WEBROOT . 'includes/classes/translations.class.php');
require(WEBROOT . 'includes/classes/rounding.class.php');
require(WEBROOT . 'includes/classes/extensions.class.php');
require(WEBROOT . 'includes/func.php');

$database = new Kimai_Database_Mysql(
    $kga['server_hostname'],
    $kga['server_database'],
    $kga['server_username'],
    $kga['server_password'],
    $kga['utf8']);

// both may be needed to log mysql errors
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR);

if (!is_object($database) || !$database->isConnected()) {
    die('Kimai-i could not connect to database. Check your autoconf.php.');
}

config_init();

// pre-check. redirect if no need to db-update
checkDBversion();


$translations = new Translations(false);


if (!file_exists(WEBROOT . 'includes/autoconf.php')) {
    die('Updater needs  an existing kimai configuration. Missing file: includes/autoconf.php');
}

if (!is_writable(WEBROOT . 'includes/autoconf.php')) {
    die('Please fix write permission for file : ' . WEBROOT . 'includes/autoconf.php');
}

if (!file_exists(WEBROOT . 'temporary/logfile.txt') && !is_writable(WEBROOT . 'temporary/')) {
    die('Please fix write permission for directory: ' . WEBROOT . 'temporary/');
}

if (file_exists(WEBROOT . 'temporary/logfile.txt') && !is_writable(WEBROOT . 'temporary/logfile.txt')) {
    die('Please fix write permission for file : ' . WEBROOT . 'temporary/logfile.txt');
}

if (!$kga['core.revision']) {
    die('Database update failed. (Revision not defined!)');
}

$V = $database->get_DBversion();
$versionDB = $V[0];
$revisionDB = (int) $V[1];
error_log(serialize($V));
unset($V);

$min_php_version = '5.4';

if (1385 < $revisionDB && $revisionDB < 2000) {
    // version belongs to original kimai at a version not covered here.
    ?>
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="robots" content="noindex,nofollow"/>
        <title>Kimai Update</title>
        <style type="text/css" media="screen">
            body {
            background: #111 url('grfx/ki_twitter_bg.png') no-repeat;
                font-family: sans-serif;
                color: #333;
            }

            body > div {
                background-color: rgba(255, 254, 254, 0.13);
                position: absolute;
                top: 50%;
                left: 50%;
                width: 500px;
                height: 250px;
                margin-left: -250px;
                margin-top: -125px;
                padding: 10px;
                border-radius: 20px;
                color: rgb(255, 255, 255);
                text-shadow: #000 2px 2px 6px;
            }
        </style>
    </head>
    <body>
    <div style="text-align:center">
        <img src="grfx/caution.png" width="70" height="63" alt="Caution"><br/>

        <h1>Update not possible.</h1>
        <div>This version of Kimai-i can update the original Kimai up to v0.9.3-rc1, with database version <b>1984</b>.
             Your current database version is <b><?php echo $revisionDB; ?></b>.
        </div>
        <br/><br/>
        <div>
        If you wish to bypass this validation and attempt an update anyway, bring down the *<b>version</b>* value to
        *<b>1984</b>* in the database table *<b>configuration</b>*.<br/>Do it at your own risks.
        </div>
    </div>
    </body>
    </html>
    <?php
}


elseif (version_compare(PHP_VERSION, $min_php_version) < 0) { ?>
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="robots" content="noindex,nofollow"/>
        <title>Kimai Update</title>
        <style type="text/css" media="screen">
            body {
            background: #111 url('grfx/ki_twitter_bg.png') no-repeat;
                font-family: sans-serif;
                color: #333;
            }

            div {
                background-color: rgba(255, 254, 254, 0.13);
                position: absolute;
                top: 50%;
                left: 50%;
                width: 500px;
                height: 250px;
                margin-left: -250px;
                margin-top: -125px;
                padding: 10px;
                border-radius: 20px;
                color: rgb(255, 255, 255);
                text-shadow: #000 2px 2px 6px;
            }
        </style>
    </head>
    <body>
    <div style="text-align:center">
        <img src="grfx/caution.png" width="70" height="63" alt="Caution"><br/>

        <h1>newer PHP version required</h1>
        You are using PHP version <?php echo phpversion(); ?> but Kimai requires at least <b>PHP
                                                                                             version <?php echo $min_php_version ?></b>.
        Please update your PHP installation, the updater can not continue otherwise.
    </div>
    </body>
    </html>
<?php }

elseif (!isset($_REQUEST['a']) && $kga['conf']['show_update_warn'] == 1) {
    $RUsure = $kga['lang']['updater'][0];

    ?>
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="robots" content="noindex,nofollow"/>
        <title>Kimai Update</title>
        <style type="text/css" media="screen">
            body {
            background: #111 url('grfx/ki_twitter_bg.png') no-repeat;
                font-family: sans-serif;
                color: #333;
            }

            div {
                background-color: rgba(255, 254, 254, 0.13);
                position: absolute;
                top: 50%;
                left: 50%;
                width: 500px;
                height: 250px;
                margin-left: -250px;
                margin-top: -125px;
                padding: 10px;
                border-radius: 20px;
                color: rgb(255, 255, 255);
                text-shadow: #000 2px 2px 6px;
            }
        </style>
    </head>
    <body>
    <div style="text-align:center">
        <img src="grfx/caution.png" width="70" height="63" alt="Caution"><br/>

        <h1>UPDATE</h1>
        <?= $RUsure ?>
        <?php if (is_writable(__DIR__ . '/includes/autoconf.php')) { ?>
            <FORM action="" method="post">
                <br/><br/>
                <INPUT type="hidden" name="a" value="1">
                <INPUT type="submit" value="START UPDATE">
            </FORM>
        <?php }
        else { ?>
            <h2 style="color:red">Cannot update:<br>includes/autoconf.php not writable</h2>
        <?php } ?>
<!--        <a href="db_restore.php" id="dbrecover">Database Backup Recover Utility</a>-->
</div>
    </body>
    </html>
<?php }

elseif ($revisionDB < 1219 && !isset($_REQUEST['timezone'])) { ?>
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="robots" content="noindex,nofollow"/>
        <title>Kimai Update</title>
        <style type="text/css" media="screen">
            body {
            background: #111 url('grfx/ki_twitter_bg.png') no-repeat;
                font-family: sans-serif;
                color: #333;
            }

            div {
                background-color: rgba(255, 254, 254, 0.13);
                position: absolute;
                top: 50%;
                left: 50%;
                width: 500px;
                height: 250px;
                margin-left: -250px;
                margin-top: -125px;
                padding: 10px;
                border-radius: 20px;
                color: rgb(255, 255, 255);
                text-shadow: #000 2px 2px 6px;
            }
        </style>
    </head>
    <body>
    <div style="text-align:center">
        <FORM action="" method="post">
            <h1> <?= $kga['lang']['timezone'] ?></h1>
            <?= $kga['lang']['updater']['timezone'] ?>
            <br/><br/>
            <select name="timezone">
                <?php
                $serverZone = @date_default_timezone_get();

                foreach (timezoneList() as $name) {
                    if ($name == $serverZone) {
                        echo "<option selected=\"selected\">$name</option>";
                    }
                    else {
                        echo "<option>$name</option>";
                    }
                }
                ?>
            </select>
            <br/><br/>
            <INPUT type="hidden" name="a" value="1">
            <INPUT type="submit" value="START UPDATE">

        </FORM>
    </div>
    </body>
    </html>

<?php }

else {?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Kimai Update <?php echo $kga['core.version'] . "." . $kga['core.revision']; ?></title>
        <style type="text/css" media="screen">
            html {
                font-family: sans-serif;
                font-size: 80%;
                background-color: #3A3A3A;
                    color: #ddd;
            }

            .red {
                background-color: #f00;
                color: #fff;
                font-weight: bold;
            }

            .green {
                background-color: #090;
            }

            .orange {
                background-color: #44d;
            }

            .machtnix {
                color: #1FA100;
            }

            .error_info {
                color: #888;
            }

            .abst {
                padding: 10px;
                margin-bottom: 10px;
                font-weight: bold;
            }

            table {
                padding: 2px;
            }

            td {
                border-bottom: 1px dotted #9E9E9E;
                padding: 5px 0;
            }

            .success {
                 background-color: rgba(0, 154, 0, 0.46);
                }

            .fail {
                background-color: rgba(234, 0, 0, 0.38);;
            }
            .success,
            .fail {
                padding: 10px;
                width: 300px;
                margin-bottom: 10px;
                border-radius: 15px;
            }

            .red,
            .green,
            .orange {
                width: 25px;
                text-align: center;
                border-radius: 25px;
            }

            #queries {
                background-color: #090;
                color: white;
                font-weight: bold;
                padding: 10px;
                margin-bottom: 20px;
            }

            #important_message {
                background-color: #a00;
                color: white;
                font-weight: bold;
                padding: 10px;
                margin-bottom: 20px;
                display: none;
            }

            .important_block_head {
                background-color: #44D;
                color: white;
                font-weight: bold;
                padding: 10px;
            }

            a {
                color: #ddd;

                padding: 5px;
                border: 1px dotted gray;
            }

            a:hover {
                color: #000;
                border: 1px solid black;
            }

            #logo {
                width: 135px;
                height: 52px;
                position: absolute;
                top: 10px;
                right: 10px;
                background-image: url('grfx/logo.png');
            }

            #restore {
                display: block;
                margin-bottom: 15px;
                width: 100px;
            }
        </style>
        <script src="libraries/jQuery/jquery-1.9.1.min.js" type="text/javascript" charset="utf-8"></script>
    </head>
    <body>
    <h1>Kimai-i Auto Updater v<?php echo $kga['core.version'] . "." . $kga['core.revision']; ?></h1>

    <div id="logo">&nbsp;</div>
    <div id="link">&nbsp;</div>
    <!--<a href="db_restore.php" id="restore" title="db_restore">Database Utility</a>-->

    <div id="queries"></div>
    <div id="important_message"></div>
    <table>
        <tr>
            <td colspan='2'>
                <strong><?php echo $kga['lang']['updater'][10]; ?></strong>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $kga['lang']['updater'][20]; ?>
            </td>
            <td class='green'>
                &nbsp;&nbsp;
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $kga['lang']['updater'][30]; ?>
            </td>
            <td class='orange'>
                &nbsp;&nbsp;
            </td>
        </tr>
        <tr>
            <td>
                <?php echo $kga['lang']['updater'][40]; ?>
            </td>
            <td class='red'>
                !
            </td>
        </tr>
    </table>

    <br/>
    <br/>

    <table cellspacing='0' cellpadding='2'>
<?php

/**
 * Execute an sql query in the database. The correct database connection
 * will be chosen and the query will be logged with the success status.
 *
 * As third parameter an alternative query can be passed, which should be
 * displayed instead of the executed query. This prevents leakage of
 * confidential information like password salts. The logfile will still
 * contain the executed query.
*
*@param string $query
* @param bool|true $errorProcessing
* @param null $displayQuery
*/
function exec_query($query, $errorProcessing = true, $displayQuery = null)
{
    global $database, $errors, $executed_queries;

    $executed_queries++;
    $success = $database->query($query);
    Logger::logfile($query);

    $err = $database->error();

    $query        = htmlspecialchars($query);
    $displayQuery = htmlspecialchars($displayQuery);

    if ($success) {
        $level = 'green';
    }
    elseif ($errorProcessing) {
        $level = 'red';
        $errors++;
    }
    else {
        $level = 'orange'; // something went wrong but it's not an error
    }

    printLine($level, ($displayQuery == null ? $query : $displayQuery), $err);

    if (!$success) {
        Logger::logfile("An error has occured in query: $query");
        $err = $database->error();

        Logger::logfile("Error text: $err");
    }

}

function printLine($level, $text, $errorInfo = '')
{
    echo '<tr>';
    echo '<td>' . $text . '<br/>';
    echo '<span class="error_info">' . $errorInfo . '</span>';
    echo '</td>';

    switch ($level) {
        case 'green':
            echo '<td class="green">&nbsp;&nbsp;</td>';
            break;
        case 'red':
            echo '<td class="red">!</td>';
            break;
        case 'orange':
            echo '<td class="orange">&nbsp;&nbsp;</td>';
            break;
    }

    echo '</tr>';
}

function quoteForSql($input)
{
    global $database;

        return "'" . mysqli_real_escape_string($database->link, $input) . "'";
}

//     MAIN          MAIN          MAIN          MAIN          MAIN          MAIN     //
//     MAIN          MAIN          MAIN          MAIN          MAIN          MAIN     //

    $errors = 0;
    $executed_queries = 0;
    $database->link->autocommit(true);
    mysqli_autocommit($database->link,true);

    Logger::logfile('-- begin update -----------------------------------');

    $p = $kga['server_prefix'];

    if ($revisionDB < $kga['core.revision']) {
        /**
         * Perform an backup (or snapshot) of the current tables.
         */
        Logger::logfile('-- begin backup -----------------------------------');

        $backup_stamp = time(); // as an individual backup label the timestamp should be enough for now...
        // by using this type of label we can also exactly identify when it was done
        // may be shown by a recovering script in human-readable format

        $query = ('SHOW TABLES;');


        $result_backup = $database->queryAll($query);
        Logger::logfile($query, $result_backup);
        $prefix_length = strlen($p);

        echo '</table>';

        echo '<strong>' . $kga['lang']['updater'][50] . '</strong>';
        echo '<table style="width:100%">';
        

        foreach ($result_backup as $row) {
            if ((substr($row[0], 0, $prefix_length) == $p) && (substr($row[0], 0, 10) != 'kimai_bak_')) {
                $backupTable = 'kimai_bak_' . $backup_stamp . '_' . $row[0];
                $query       = "CREATE TABLE ${backupTable} LIKE " . $row[0];
                exec_query($query);

                $query = "INSERT INTO ${backupTable} SELECT * FROM " . $row[0];
                exec_query($query);

                if ($errors) {
                    die($kga['lang']['updater'][60]);
                }
            }
        }

        Logger::logfile('-- backup finished -----------------------------------');

        echo '</table><br /><br />';
        echo '<strong>' . $kga['lang']['updater'][70] . '</strong></br>';
        echo "<table style='width:100%'>";
    }
    //////// ---------------------------------------------------------------------------------------------------
    //////// ---------------------------------------------------------------------------------------------------

    $versionDB_e = explode(".", $versionDB);

    if (((int) $versionDB_e[1] == 7 && (int) $versionDB_e[2] < 12)) {
        Logger::logfile('-- update to 0.7.12');
        exec_query("ALTER TABLE `${p}evt` ADD `evt_visible` TINYINT NOT NULL DEFAULT '1'", 1);
        exec_query("ALTER TABLE `${p}knd` ADD `knd_visible` TINYINT NOT NULL DEFAULT '1'", 1);
        exec_query("ALTER TABLE `${p}pct` ADD `pct_visible` TINYINT NOT NULL DEFAULT '1'", 1);
        exec_query("ALTER TABLE `${p}evt` ADD `evt_filter` TINYINT NOT NULL DEFAULT '0'", 1);
        exec_query("ALTER TABLE `${p}knd` ADD `knd_filter` TINYINT NOT NULL DEFAULT '0'", 1);
        exec_query("ALTER TABLE `${p}pct` ADD `pct_filter` TINYINT NOT NULL DEFAULT '0'", 1);
        exec_query("INSERT INTO ${p}var (`var`, `value`) VALUES ('revision','0')", 1);
    }

    if ($revisionDB < 96) {
        Logger::logfile('-- update to 0.7.13r96');
        exec_query("ALTER TABLE `${p}conf` ADD `allvisible` TINYINT(1) NOT NULL DEFAULT '1'", 1);
        // a proper installed database throws errors from here. don't worry - no problem. We ignore those ...
        exec_query("ALTER TABLE `${p}evt` CHANGE `visible` `evt_visible` TINYINT(1) NOT NULL DEFAULT '1'", 0);
        exec_query("ALTER TABLE `${p}knd` CHANGE `visible` `knd_visible` TINYINT(1) NOT NULL DEFAULT '1'", 0);
        exec_query("ALTER TABLE `${p}pct` CHANGE `visible` `pct_visible` TINYINT(1) NOT NULL DEFAULT '1'", 0);
        exec_query("ALTER TABLE `${p}evt` CHANGE `filter` `evt_filter` TINYINT(1) NOT NULL DEFAULT '0'", 0);
        exec_query("ALTER TABLE `${p}knd` CHANGE `filter` `knd_filter` TINYINT(1) NOT NULL DEFAULT '0'", 0);
        exec_query("ALTER TABLE `${p}pct` CHANGE `filter` `pct_filter` TINYINT(1) NOT NULL DEFAULT '0'", 0);
    }

    if ($revisionDB < 221) {
        Logger::logfile('-- update to 0.8');
        // drop views
        exec_query("DROP VIEW IF EXISTS ${p}get_arr_grp, ${p}get_usr_count_in_grp", 0);
        // Set news group name length
        exec_query("ALTER TABLE `${p}grp` CHANGE `grp_name` `grp_name` VARCHAR(160)", 1);

        // Merge usr and conf tables
        $query = "CREATE TABLE IF NOT EXISTS `${p}usr_tmp` (
                `usr_ID` int(10) NOT NULL,
                `usr_name` varchar(160) NOT NULL,
                `usr_grp` int(5) NOT NULL default '1',
                `usr_sts` tinyint(1) NOT NULL default '2',
                `usr_trash` tinyint(1) NOT NULL default '0',
                `usr_active` tinyint(1) NOT NULL default '1',
                `usr_mail` varchar(160) NOT NULL,
                `pw` varchar(254) NOT NULL,
                `ban` int(1) NOT NULL default '0',
                `banTime` int(7) NOT NULL default '0',
                `secure` varchar(60) NOT NULL default '0',
                `rowlimit` int(3) NOT NULL,
                `skin` varchar(20) NOT NULL,
                `recordingstate` tinyint(1) NOT NULL default '1',
                `lastProject` int(10) NOT NULL default '1',
                `lastEvent` int(10) NOT NULL default '1',
                `lastRecord` int(10) NOT NULL default '0',
                `filter` int(10) NOT NULL default '0',
                `filter_knd` int(10) NOT NULL default '0',
                `filter_pct` int(10) NOT NULL default '0',
                `filter_evt` int(10) NOT NULL default '0',
                `view_knd` int(10) NOT NULL default '0',
                `view_pct` int(10) NOT NULL default '0',
                `view_evt` int(10) NOT NULL default '0',
                `zef_anzahl` int(10) NOT NULL default '0',
                `timespace_in` varchar(60) NOT NULL default '0',
                `timespace_out` varchar(60) NOT NULL default '0',
                `autoselection` tinyint(1) NOT NULL default '1',
                `quickdelete` tinyint(1) NOT NULL default '0',
                `allvisible` tinyint(1) NOT NULL default '1',
                `lang` varchar(6) NOT NULL,
                PRIMARY KEY (`usr_name`))";
        exec_query($query, 1);

        //////// ---------------------------------------------------------------------------------------------------

        $query = "SELECT * FROM `${p}usr` JOIN `${p}conf` ON `${p}usr`.usr_ID = `${p}conf`.conf_usrID";

        if (is_object($database)) {
            $success = $database->query($query);
            $executed_queries++;

            $arr  = array();
            $rows = $database->recordsArray(MYSQL_ASSOC);
            foreach ($rows as $row) {
                echo '<tr>';
                $query   =
                    <<<SQL
                    INSERT INTO ${p}usr_tmp (
                    `usr_ID`,`usr_name`,`usr_grp`,`usr_sts`,`usr_trash`,`usr_active`,`usr_mail`,`pw`,`ban`,`banTime`,
                    `secure`,`rowlimit`,`skin`,`lastProject`,`lastEvent`,`lastRecord`,`filter`,`filter_knd`,`filter_pct`,`filter_evt`,
                    `view_knd`,`view_pct`,`view_evt`,`zef_anzahl`,`timespace_in`,`timespace_out`,`autoselection`,`quickdelete`,`allvisible`,`lang`
                    ) VALUES (
                      $row[usr_ID],'$row[usr_name]',$row[usr_grp],$row[usr_sts],$row[usr_trash],$row[usr_active],'$row[usr_mail]','$row[pw]',$row[ban],$row[banTime],
                      '$row[secure]',$row[rowlimit],'$row[skin]',$row[lastProject],$row[lastEvent],$row[lastRecord],$row[filter],$row[filter_knd],$row[filter_pct],$row[filter_evt],
                      $row[view_knd],$row[view_pct],$row[view_evt],$row[zef_anzahl],'$row[timespace_in]','$row[timespace_out]',$row[autoselection],$row[quickdelete],$row[allvisible],'$row[lang]');
SQL;
                $success = $database->query($query);
                $executed_queries++;
                echo '<td>' . $query . '<br/>';
                echo '<span class="error_info">' . $database->error() . '</span>';
                echo '</td>';

                if ($success) {
                    echo '<td class="green">&nbsp;&nbsp;</td>';
                }
                else {
                    echo '<td class="red">!</td>';
                }

                echo '</tr>';
            }
        }

        //////// ---------------------------------------------------------------------------------------------------

        exec_query("DROP TABLE `${p}usr`", 1);
        exec_query("DROP TABLE `${p}conf`", 1);
        exec_query("RENAME TABLE `${p}usr_tmp` TO `${p}usr`", 1);

        exec_query("ALTER TABLE `${p}knd` CHANGE `knd_telephon` `knd_tel` VARCHAR(255)", 0);
        exec_query("ALTER TABLE `${p}knd` CHANGE `knd_mobilphon` `knd_mobile` VARCHAR(255)", 0);

        // Add field for icon/logo filename to customer, project and task table
        exec_query("ALTER TABLE `${p}knd` ADD `knd_logo` VARCHAR(80)", 1);

        exec_query("ALTER TABLE `${p}pct` ADD `pct_logo` VARCHAR(80)", 1);
        exec_query("ALTER TABLE `${p}evt` ADD `evt_logo` VARCHAR(80)", 1);

        // Add trash field for customer, project and task tables
        exec_query("ALTER TABLE `${p}knd` ADD `knd_trash` TINYINT(1) NOT NULL DEFAULT '0'", 1);

        exec_query("ALTER TABLE `${p}pct` ADD `pct_trash` TINYINT(1) NOT NULL DEFAULT '0'", 1);
        exec_query("ALTER TABLE `${p}evt` ADD `evt_trash` TINYINT(1) NOT NULL DEFAULT '0'", 1);
        exec_query("ALTER TABLE `${p}zef` ADD `zef_cleared` TINYINT(1) NOT NULL DEFAULT '0'", 1);


        //////// ---------------------------------------------------------------------------------------------------

        // put the existing group-customer-relations into the new table
        exec_query("CREATE TABLE `${p}grp_knd` (`uid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `grp_ID` INT NOT NULL, `knd_ID` INT NOT NULL)", 0);

        //////// ---------------------------------------------------------------------------------------------------

        $query = "SELECT `knd_ID`, `knd_grpID` FROM ${p}knd";

        if (is_object($database)) {
            $success = $database->query($query);
            $executed_queries++;

            $arr  = array();
            $rows = $database->recordsArray(MYSQL_ASSOC);
            foreach ($rows as $row) {
                echo "<tr>";
                $query   = "INSERT INTO ${p}grp_knd (`grp_ID`, `knd_ID`) VALUES (" . $row['knd_grpID'] . ", " . $row[knd_ID] . ")";
                $success = $database->query($query);
                $executed_queries++;
                echo "<td>" . $query . "<br/>";
                echo "<span class='error_info'>" . $database->error() . "</span>";
                echo "</td>";

                if ($success) {
                    echo "<td class='green'>&nbsp;&nbsp;</td>";
                }
                else {
                    echo "<td class='red'>!</td>";
                }
                echo "</tr>";

                echo $database->error();
            }
        }

        //////// ---------------------------------------------------------------------------------------------------

        // put the existing group-project-relations into the new table
        exec_query("CREATE TABLE `${p}grp_pct` (`uid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `grp_ID` INT NOT NULL, `pct_ID` INT NOT NULL)");

        //////// ---------------------------------------------------------------------------------------------------

        $query = "SELECT `pct_ID`, `pct_grpID` FROM ${p}pct";

        if (is_object($database)) {
            $success = $database->query($query);
            $executed_queries++;

            $arr  = array();
            $rows = $database->recordsArray(MYSQL_ASSOC);
            foreach ($rows as $row) {
                echo "<tr>";
                $query   = "INSERT INTO ${p}grp_pct (`grp_ID`, `pct_ID`) VALUES (" . $row['pct_grpID'] . ", " . $row[pct_ID] . ")";
                $success = $database->query($query);
                $executed_queries++;
                echo "<td>" . $query . "<br/>";
                echo "<span class='error_info'>" . $database->error() . "</span>";
                echo "</td>";

                if ($success) {
                    echo "<td class='green'>&nbsp;&nbsp;</td>";
                }
                else {
                    echo "<td class='red'>!</td>";
                }
                echo "</tr>";
            }
        }

        //////// ---------------------------------------------------------------------------------------------------

        // put the existing group-event-relations into the new table
        exec_query("CREATE TABLE `${p}grp_evt` (`uid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `grp_ID` INT NOT NULL, `evt_ID` INT NOT NULL)");

        //////// ---------------------------------------------------------------------------------------------------


        $query = "SELECT `evt_ID`, `evt_grpID` FROM ${p}evt";

        if (is_object($database)) {
            $success = $database->query($query);
            $executed_queries++;

            $arr  = array();
            $rows = $database->recordsArray(MYSQL_ASSOC);
            foreach ($rows as $row) {
                echo "<tr>";
                $query   = "INSERT INTO ${p}grp_evt (`grp_ID`, `evt_ID`) VALUES (" . $row['evt_grpID'] . ", " . $row[evt_ID] . ")";
                $success = $database->query($query);
                $executed_queries++;
                echo "<td>" . $query;
                echo "</td>";

                if ($success) {
                    echo "<td class='green'>&nbsp;&nbsp;</td>";
                }
                else {
                    echo "<td class='red'>!</td>";
                }
                echo "</tr>";
            }
        }

        //////// ---------------------------------------------------------------------------------------------------

        // delete old single-group fields in knd, pct and evt
        exec_query("ALTER TABLE ${p}knd DROP `knd_grpID`");
        exec_query("ALTER TABLE ${p}pct DROP `pct_grpID`");
        exec_query("ALTER TABLE ${p}evt DROP `evt_grpID`");
    }

    //////// ---------------------------------------------------------------------------------------------------

    if ($revisionDB < 733) {

        Logger::logfile("-- update to 0.8.0a");

        exec_query("ALTER TABLE `${p}evt` CHANGE `evt_visible` `evt_visible` TINYINT(1) NOT NULL DEFAULT '1';", 0);
        exec_query("ALTER TABLE `${p}knd` CHANGE `knd_visible` `knd_visible` TINYINT(1) NOT NULL DEFAULT '1';", 0);
        exec_query("ALTER TABLE `${p}pct` CHANGE `pct_visible` `pct_visible` TINYINT(1) NOT NULL DEFAULT '1';", 0);
        exec_query("ALTER TABLE `${p}evt` CHANGE `evt_filter` `evt_filter` TINYINT(1) NOT NULL DEFAULT '0';", 0);
        exec_query("ALTER TABLE `${p}knd` CHANGE `knd_filter` `knd_filter` TINYINT(1) NOT NULL DEFAULT '0';", 0);
        exec_query("ALTER TABLE `${p}pct` CHANGE `pct_filter` `pct_filter` TINYINT(1) NOT NULL DEFAULT '0';", 0);
        exec_query("ALTER TABLE `${p}evt` CHANGE `evt_ID` `evt_ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY;", 0);
        exec_query("ALTER TABLE `${p}grp` CHANGE `grp_ID` `grp_ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY;", 0);
        exec_query("ALTER TABLE `${p}grp` DROP `grp_leader`;", 0);
        exec_query("ALTER TABLE `${p}knd` CHANGE `knd_ID` `knd_ID` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY;", 0);
        exec_query("ALTER TABLE `${p}ldr` ADD `uid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;", 0);
        exec_query("ALTER TABLE `${p}pct` CHANGE `pct_ID` `pct_ID` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY;", 0);
        exec_query("ALTER TABLE `${p}usr` DROP `recordingstate`;", 0);
        exec_query("ALTER TABLE `${p}var` ADD PRIMARY KEY (`var`);", 0);
        exec_query("ALTER TABLE `${p}zef` CHANGE `zef_ID` `zef_ID` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY;", 0);
    }

    if ($revisionDB < 817) {
        Logger::logfile("-- update to r817");
        exec_query("ALTER TABLE `${p}usr` ADD `showIDs` TINYINT(1) NOT NULL DEFAULT '0'", 1);
    }

    if ($revisionDB < 837) {
        Logger::logfile("-- update to r837");
        exec_query("ALTER TABLE `${p}usr` ADD `usr_alias` VARCHAR(10)", 0);
        exec_query("ALTER TABLE `${p}zef` ADD `zef_location` varchar(50)", 1);
    }

    if ($revisionDB < 848) {
        Logger::logfile("-- update to r848");
        exec_query("ALTER TABLE `${p}zef` ADD `zef_trackingnr` int(20)", 1);
    }

    if ($revisionDB < 898) {
        Logger::logfile("-- update to r898");
        exec_query("CREATE TABLE `${p}rates` (
                  `user_id` int(10) DEFAULT NULL,
                  `project_id` int(10) DEFAULT NULL,
                  `event_id` int(10) DEFAULT NULL,
                  `rate` decimal(10,2) NOT NULL
                );", 1);
        exec_query("ALTER TABLE `${p}zef` ADD `zef_rate` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';", 1);
    }

    if ($revisionDB < 922) {
        Logger::logfile("-- update to r922");
        exec_query("ALTER TABLE `${p}knd` ADD `knd_password` VARCHAR(255);", 1);
        exec_query("ALTER TABLE `${p}knd` ADD `knd_secure` varchar(60) NOT NULL default '0';", 1);
    }

    if ($revisionDB < 935) {
        Logger::logfile("-- update to r935");
        exec_query("CREATE TABLE `${p}exp` (
                  `exp_ID` int(10) NOT NULL AUTO_INCREMENT,
                  `exp_timestamp` int(10) NOT NULL DEFAULT '0',
                  `exp_usrID` int(10) NOT NULL,
                  `exp_pctID` int(10) NOT NULL,
                  `exp_designation` text NOT NULL,
                  `exp_comment` text NOT NULL,
                  `exp_comment_type` tinyint(1) NOT NULL DEFAULT '0',
                  `exp_cleared` tinyint(1) NOT NULL DEFAULT '0',
                  `exp_value` decimal(10,2) NOT NULL DEFAULT '0.00',
                  PRIMARY KEY (`exp_ID`)
                ) AUTO_INCREMENT=1;");
    }

    if ($revisionDB < 1067) {
        Logger::logfile("-- update to r1067");

        /*
         *  Write new config file with password salt
         */
        $kga['password_salt'] = createPassword(20);
        if (write_config_file(
            $kga['server_hostname'],
            $kga['server_database'],
            $kga['server_username'],
            $kga['server_password'],
            $kga['password_salt'],
            $kga['server_prefix'],
            $kga['authenticator'],
            $kga['pref']['language'],
            'Europe/Berlin')
        ) {
            echo '<tr><td>' . $kga['lang']['updater'][140] . '</td><td class="green">&nbsp;&nbsp;</td></tr>';
        }
        else {
            die($kga['lang']['updater'][130]);
        }


        /*
         *  Reset all passwords
         */
        $new_passwords = array();

        $users = $database->queryAll("SELECT * FROM ${p}usr");

        foreach ($users as $user) {
            if ($user['usr_name'] === 'admin') {
                $new_password = 'changeme';
            }
            else {
                $new_password = createPassword(8);
            }
            exec_query("UPDATE ${p}usr SET pw = '" .
                       password_encrypt($new_password) .
                       "' WHERE usr_ID = $user[usr_ID]");
            if ($result) {
                $new_passwords[$user['usr_name']] = $new_password;
            }
        }

    }

    if ($revisionDB < 1068) {
        Logger::logfile("-- update to r1068");
        exec_query("ALTER TABLE `${p}usr` CHANGE `autoselection` `autoselection` TINYINT( 1 ) NOT NULL default '0';");
    }

    if ($revisionDB < 1077) {
        Logger::logfile("-- update to r1076");
        exec_query("ALTER TABLE `${p}usr` CHANGE `usr_mail` `usr_mail` varchar(160) DEFAULT ''");
        exec_query("ALTER TABLE `${p}usr` CHANGE `pw` `pw` varchar(254) NULL DEFAULT NULL");
        exec_query("ALTER TABLE `${p}usr` CHANGE `lang` `lang` varchar(6) DEFAULT ''");
        exec_query("ALTER TABLE `${p}zef` CHANGE `zef_comment` `zef_comment` TEXT NULL DEFAULT NULL");
    }

    if ($revisionDB < 1086) {
        Logger::logfile("-- update to r1086");
        exec_query("ALTER TABLE `${p}pct` ADD `pct_budget` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }

    if ($revisionDB < 1088) {
        Logger::logfile("-- update to r1088");
        exec_query("ALTER TABLE `${p}usr` ADD `noFading` TINYINT(1) NOT NULL DEFAULT '0'");
    }

    if ($revisionDB < 1089) {
        Logger::logfile("-- update to r1089");
        exec_query("ALTER TABLE `${p}usr` ADD `export_disabled_columns` INT NOT NULL DEFAULT '0'");
    }

    if ($revisionDB < 1103) {
        Logger::logfile("-- update to r1103");
        exec_query("ALTER TABLE ${p}usr DROP `allvisible`");
    }

if ($revisionDB < 1112) {
    Logger::logfile("-- update to r1112");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('currency_name','Euro')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('currency_sign','€')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('show_sensible_data','1')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('show_update_warn','1')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('check_at_startup','0')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('show_daySeperatorLines','1')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('show_gabBreaks','0')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('show_RecordAgain','1')");
    exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('show_TrackingNr','1')");
}

    if ($revisionDB < 1113) {
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('date_format_0','%d.%m.%Y')");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('date_format_1','%d.%m.')");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('date_format_2','%d.%m.%Y')");
        exec_query("DELETE FROM ${p}var WHERE `var` = 'charset' LIMIT 1");
    }

    if ($revisionDB < 1115) {
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('language','" . $kga['pref']['language'] . "')");
    }

    if ($revisionDB < 1126) {
        Logger::logfile("-- update to r1126");
        exec_query("ALTER TABLE `${p}grp_evt` ADD UNIQUE (`grp_ID` ,`evt_ID`);");
        exec_query("ALTER TABLE `${p}grp_knd` ADD UNIQUE (`grp_ID` ,`knd_ID`);");
        exec_query("ALTER TABLE `${p}grp_pct` ADD UNIQUE (`grp_ID` ,`pct_ID`);");
        exec_query("ALTER TABLE `${p}ldr` ADD UNIQUE (`grp_ID` ,`grp_leader`);");
    }

    if ($revisionDB < 1132) {
        Logger::logfile("-- update to r1132");
            exec_query("UPDATE ${p}usr, ${p}ldr SET usr_sts = 2 WHERE usr_sts = 1");
            exec_query("UPDATE ${p}usr, ${p}ldr SET usr_sts = 1 WHERE usr_sts = 2 AND grp_leader = usr_ID");
    }

    if ($revisionDB < 1139) {
        Logger::logfile("-- update to r1139");
        exec_query("ALTER TABLE `${p}usr` ADD `user_list_hidden` INT NOT NULL DEFAULT '0'");
    }

    if ($revisionDB < 1142) {
        Logger::logfile("-- update to r1142");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('roundPrecision','0')");
    }

    if ($revisionDB < 1145) {
        Logger::logfile("-- update to r1145");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('currency_first','0')");
    }

    if ($revisionDB < 1176) {
        Logger::logfile("-- update to r1176");
        exec_query("ALTER TABLE `${p}exp` ADD INDEX ( `exp_usrID` ) ");
        exec_query("ALTER TABLE `${p}exp` ADD INDEX ( `exp_pctID` ) ");
        exec_query("ALTER TABLE `${p}pct` ADD INDEX ( `pct_kndID` ) ");
        exec_query("ALTER TABLE `${p}zef` ADD INDEX ( `zef_usrID` ) ");
        exec_query("ALTER TABLE `${p}zef` ADD INDEX ( `zef_pctID` ) ");
        exec_query("ALTER TABLE `${p}zef` ADD INDEX ( `zef_evtID` ) ");
    }

    if ($revisionDB < 1183) {
        Logger::logfile("-- update to r1183");
        exec_query("ALTER TABLE `${p}zef` CHANGE `zef_trackingnr` `zef_trackingnr` varchar(30) DEFAULT ''");
    }

    if ($revisionDB < 1184) {
        Logger::logfile("-- update to r1184");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('decimalSeparator',',')");
    }

    if ($revisionDB < 1185) {
        Logger::logfile("-- update to r1185");
        exec_query("CREATE TABLE ${p}pct_evt (`uid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `pct_ID` INT NOT NULL, `evt_ID` INT NOT NULL, UNIQUE (`pct_ID` ,`evt_ID`)) ;");
    }

    if ($revisionDB < 1206) {
        Logger::logfile("-- update to r1206");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('durationWithSeconds','0')");
    }

    if ($revisionDB < 1207) {
        Logger::logfile("-- update to r1207");
        exec_query("ALTER TABLE `${p}exp` ADD `exp_multiplier` INT NOT NULL DEFAULT '1'");

    }

    if ($revisionDB < 1213) {
        Logger::logfile("-- update to r1213");
        exec_query("ALTER TABLE ${p}knd DROP `knd_logo`");
        exec_query("ALTER TABLE ${p}pct DROP `pct_logo`");
        exec_query("ALTER TABLE ${p}evt DROP `evt_logo`");
    }

    if ($revisionDB < 1216) {
        Logger::logfile("-- update to r1216");
        exec_query("ALTER TABLE `${p}exp`
                  ADD `exp_refundable` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'expense refundable to employee (0 = no, 1 = yes)' AFTER `exp_comment_type`;");
    }

    if ($revisionDB < 1219) {
        $timezone = quoteForSql($_REQUEST['timezone']);
        Logger::logfile("-- update to r1219");
        exec_query("ALTER TABLE `${p}usr` ADD `timezone` VARCHAR( 40 ) NOT NULL DEFAULT ''");
        exec_query("UPDATE `${p}usr` SET `timezone` = $timezone");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('defaultTimezone',$timezone)");
    }

    if ($revisionDB < 1225) {
        Logger::logfile("-- update to r1225");
        exec_query("CREATE TABLE `${p}preferences` (
                  `userID` int(10) NOT NULL,
                  `var` varchar(255) NOT NULL,
                  `value` varchar(255) NOT NULL,
                  PRIMARY KEY (`userID`,`var`)
                  );");

        $columns = array('rowlimit', 'skin', 'autoselection', 'quickdelete',
                         'lang', 'flip_pct_display', 'pct_comment_flag', 'showIDs', 'noFading',
                         'export_disabled_columns', 'user_list_hidden', 'timezone');

        // move user configuration over to preferences table, which are still in use
        foreach ($columns as $column) {
            exec_query("INSERT INTO ${p}preferences (`userID`,`var`,`value`) SELECT `usr_ID` , \"$column\", `$column` FROM `${p}usr`");
        }


        // add unused columns and drop all in usr table
        $columns = array_merge($columns, array('zef_anzahl', 'filter', 'filter_knd', 'filter_pct', 'filter_evt', 'view_knd', 'view_pct', 'view_evt'));
        foreach ($columns as $column) {
            exec_query("ALTER TABLE ${p}usr DROP $column");
        }
    }

    if ($revisionDB < 1227) {
        Logger::logfile("-- update to r1227");
        exec_query("ALTER TABLE `${p}knd` ADD `knd_vat` VARCHAR( 255 ) NOT NULL");
        exec_query("ALTER TABLE `${p}knd` ADD `knd_contact` VARCHAR( 255 ) NOT NULL");
    }

    if ($revisionDB < 1229) {
        Logger::logfile("-- update to r1229");
        exec_query("ALTER TABLE `${p}usr` CHANGE `banTime` `banTime` int(10) NOT NULL DEFAULT 0");
    }

    if ($revisionDB < 1236) {
        Logger::logfile("-- update to r1236");
        exec_query("ALTER TABLE `${p}pct` ADD `pct_internal` TINYINT( 1 ) NOT NULL DEFAULT 0");
    }

    if ($revisionDB < 1240) {
        Logger::logfile("-- update to r1240");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('exactSums','0')");
    }

    if ($revisionDB < 1256) {
        Logger::logfile("-- update to r1256");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('defaultVat','0')");
    }

    if ($revisionDB < 1257) {
        Logger::logfile("-- update to r1257");
        exec_query("UPDATE ${p}preferences SET var = CONCAT('ui.',var) WHERE var
                    IN ('skin', 'rowlimit', 'lang', 'autoselection', 'quickdelete', 'flip_pct_display',
                    'pct_comment_flag', 'showIDs', 'noFading', 'user_list_hidden', 'hideClearedEntries')");
    }

    if ($revisionDB < 1284) {
        Logger::logfile("-- update to r1284");
        exec_query("ALTER TABLE `${p}exp` CHANGE `exp_multiplier` `exp_multiplier` decimal(10,2) NOT NULL DEFAULT '1.00'");
    }

    if ($revisionDB < 1291) {
        Logger::logfile("-- update to r1291");
        $salt  = $kga['password_salt'];
        $query = "UPDATE `${p}usr` SET pw=MD5(CONCAT('${salt}',pw,'${salt}')) WHERE pw REGEXP '^[0-9a-f]{32}$' = 0 AND pw != ''";
        exec_query($query, false, str_replace($salt, 'salt was stripped', $query));
    }

    if ($revisionDB < 1305) {
        Logger::logfile("-- update to r1305");

        // update knd_name
        $result = $database->queryAll("SELECT knd_ID,knd_name FROM ${p}knd");

        foreach ($result as $customer) {
            $name = htmlspecialchars_decode($customer['knd_name']);

            if ($name == $customer['knd_name']) {
                continue;
            }

            exec_query("UPDATE ${p}knd SET knd_name = " .
                       quoteForSql($name) .
                       " WHERE knd_ID = $customer[knd_ID]");
        }

        // update pct_name
        $result = $database->queryAll("SELECT pct_ID,pct_name FROM ${p}pct");

        foreach ($result as $project) {
            $name = htmlspecialchars_decode($project['pct_name']);

            if ($name == $project['pct_name']) {
                continue;
            }

            exec_query("UPDATE ${p}pct SET pct_name = " .
                       quoteForSql($name) .
                       " WHERE pct_ID = $project[pct_ID]");
        }

        // update evt_name
        $result = $database->queryAll("SELECT evt_ID,evt_name FROM ${p}evt");

        foreach ($result as $event) {
            $name = htmlspecialchars_decode($event['evt_name']);

            if ($name == $event['evt_name']) {
                continue;
            }

            exec_query("UPDATE ${p}evt SET evt_name = " .
                       quoteForSql($name) .
                       " WHERE evt_ID = $event[evt_ID]");
        }

        // update usr_name
        $result = $database->queryAll("SELECT usr_ID,usr_name FROM ${p}usr");

        foreach ($result as $user) {
            $name = htmlspecialchars_decode($user['usr_name']);

            if ($name == $user['usr_name']) {
                continue;
            }

            exec_query("UPDATE ${p}usr SET usr_name = " .
                       quoteForSql($name) .
                       " WHERE usr_ID = $user[usr_ID]");
        }

        // update grp_name
        $result = $database->queryAll("SELECT grp_ID,grp_name FROM ${p}grp");

        foreach ($result as $group) {
            $name = htmlspecialchars_decode($group['grp_name']);

            if ($name == $group['grp_name']) {
                continue;
            }

            exec_query("UPDATE ${p}grp SET grp_name = " .
                       quoteForSql($name) .
                       " WHERE grp_ID = $group[grp_ID]");
        }

    }

    if ($revisionDB < 1326) {
        Logger::logfile("-- update to r1326");
        exec_query("INSERT INTO ${p}var (`var`,`value`) VALUES('editLimit','-')");
    }

    if ($revisionDB < 1327) {
        Logger::logfile("-- update to r1327");
        $result   = $database->queryAll("SELECT value FROM ${p}var WHERE var = 'defaultTimezone'");
        $timezone = quoteForSql($result[0][0]);
        exec_query("ALTER TABLE ${p}knd ADD COLUMN `knd_timezone` varchar(255) NOT NULL DEFAULT $timezone");
        exec_query("ALTER TABLE ${p}knd ALTER COLUMN `knd_timezone` DROP DEFAULT");
    }

    if ($revisionDB < 1328) {
        Logger::logfile("-- update to r1328");
        exec_query("DELETE FROM ${p}var WHERE var='login' LIMIT 1;");
    }

    if ($revisionDB < 1331) {
        Logger::logfile("-- update to r1331");
        exec_query("ALTER TABLE ${p}evt ADD COLUMN `evt_assignable` TINYINT(1) NOT NULL DEFAULT '0';");
        $result = $database->queryAll("SELECT DISTINCT evt_ID FROM ${p}pct_evt");
        foreach ($result as $row) {
            exec_query("UPDATE ${p}evt SET evt_assignable=1 WHERE evt_ID=" . $row[0]);
        }
    }

    if ($revisionDB < 1332) {
        Logger::logfile("-- update to r1332");
        $query =
            "CREATE TABLE `${p}fixed_rates` (
              `project_id` int(10) DEFAULT NULL,
              `event_id` int(10) DEFAULT NULL,
              `rate` decimal(10,2) NOT NULL
            );";
        exec_query($query);
        exec_query("ALTER TABLE ${p}zef ADD COLUMN `zef_fixed_rate` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0';");
    }

    if ($revisionDB < 1333) {
        Logger::logfile("-- update to r1333");
        $query =
            "CREATE TABLE `${p}grp_usr` (
              `grp_ID` int(10) NOT NULL,
              `usr_ID` int(10) NOT NULL,
              PRIMARY KEY (`grp_ID`,`usr_ID`)
            ) AUTO_INCREMENT=1;";
        exec_query($query);

        $result = $database->queryAll("SELECT usr_ID,usr_grp FROM ${p}usr");
        foreach ($result as $row) {
            exec_query("INSERT INTO ${p}grp_usr (`grp_ID`,`usr_ID`) VALUES($row[usr_grp],$row[usr_ID]);");
        }

        exec_query("ALTER TABLE ${p}usr DROP `usr_grp`;");
    }

    if ($revisionDB < 1347) {
        Logger::logfile("-- update to r1347");
        
        exec_query("ALTER TABLE `${p}pct_evt` ADD `evt_budget` DECIMAL( 10, 2 ) NULL ,
                    ADD `evt_effort` DECIMAL( 10, 2 ) NULL ,
                    ADD `evt_approved` DECIMAL( 10, 2 ) NULL ;");

        exec_query("ALTER TABLE `${p}pct`
                    ADD `pct_effort` DECIMAL( 10, 2 ) NULL AFTER `pct_budget` ,
                    ADD `pct_approved` DECIMAL( 10, 2 ) NULL AFTER `pct_effort` ");

        exec_query("ALTER TABLE `${p}zef`
                        ADD `zef_status` SMALLINT DEFAULT 1,
                        ADD `zef_billable` TINYINT NULL");

        exec_query("CREATE TABLE `${p}status` (
                    `status_id` TINYINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `status` VARCHAR( 200 ) NOT NULL
                    ) ENGINE = InnoDB ");

        exec_query("INSERT INTO `${p}status` (`status_id` ,`status`) VALUES ('1', 'open'), ('2', 'review'), ('3', 'closed');");

        exec_query("ALTER TABLE `${p}zef`
                    ADD `zef_budget` DECIMAL( 10, 2 ) NULL AFTER `zef_fixed_rate` ,
                    ADD `zef_approved` DECIMAL( 10, 2 ) NULL AFTER `zef_budget` ;");

        exec_query("ALTER TABLE `${p}zef` ADD `zef_description` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `zef_evtID` ");

        exec_query("UPDATE ${p}zef SET zef_status = 3 WHERE zef_cleared = 1");

        exec_query("INSERT INTO `${p}var` (`var` ,`value`) VALUES ('roundTimesheetEntries', '0' );");

        exec_query("INSERT INTO `${p}var` (`var` ,`value`) VALUES ('roundMinutes', '0');");

        exec_query("INSERT INTO `${p}var` (`var` ,`value`) VALUES ('roundSeconds', '0');");

        exec_query("DELETE FROM `${p}var` WHERE `var` = 'status';");
    }

    if ($revisionDB < 1349) {
        Logger::logfile('-- update to r1350');
        exec_query("ALTER TABLE `${p}usr` ADD `apikey` VARCHAR( 30 ) NULL AFTER `timespace_out`");
        exec_query("ALTER TABLE `${p}usr` ADD UNIQUE (`apikey`)");
    }

    if ($revisionDB < 1368) {
        Logger::logfile('-- update to r1368');

        // some users don't seem to have these columns so we add them here (if they don't exist yet).
        exec_query("ALTER TABLE  `${p}evt` ADD `evt_budget`     decimal(10,2) DEFAULT NULL;", false);
        exec_query("ALTER TABLE  `${p}evt` ADD `evt_effort`     decimal(10,2) DEFAULT NULL;", false);
        exec_query("ALTER TABLE  `${p}evt` ADD `evt_approved`   decimal(10,2) DEFAULT NULL;", false);

        exec_query("ALTER TABLE `${p}evt` RENAME TO `${p}activities`,
                    CHANGE `evt_ID`         `activityID` int(10) NOT NULL AUTO_INCREMENT,
                    CHANGE `evt_name`       `name`       varchar(255) NOT NULL,
                    CHANGE `evt_comment`    `comment`    text NOT NULL,
                    CHANGE `evt_visible`    `visible`    tinyint(1) NOT NULL DEFAULT '1',
                    CHANGE `evt_filter`     `filter`     tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `evt_trash`      `trash`      tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `evt_assignable` `assignable` tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `evt_budget`     `budget`     decimal(10,2) DEFAULT NULL,
                    CHANGE `evt_effort`     `effort`     decimal(10,2) DEFAULT NULL,
                    CHANGE `evt_approved`   `approved`   decimal(10,2) DEFAULT NULL
                    ;");

        exec_query("ALTER TABLE `${p}exp` RENAME TO `${p}expenses`,
                    CHANGE `exp_ID`           `expenseID`   int(10) NOT NULL AUTO_INCREMENT,
                    CHANGE `exp_timestamp`    `timestamp`   int(10) NOT NULL DEFAULT '0',
                    CHANGE `exp_usrID`        `userID`      int(10) NOT NULL,
                    CHANGE `exp_pctID`        `projectID`   int(10) NOT NULL,
                    CHANGE `exp_designation`  `designation` text NOT NULL,
                    CHANGE `exp_comment`      `comment`     text NOT NULL,
                    CHANGE `exp_comment_type` `commentType` tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `exp_refundable`   `refundable`  tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'expense refundable to employee (0 = no, 1 = yes)',
                    CHANGE `exp_cleared`      `cleared`     tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `exp_multiplier`   `multiplier`  decimal(10,2) NOT NULL DEFAULT '1.00',
                    CHANGE `exp_value`        `value`       decimal(10,2) NOT NULL DEFAULT '0.00'
                    ;");

        exec_query("ALTER TABLE `${p}fixed_rates` RENAME TO `${p}fixedRates`,
                    CHANGE `project_id` `projectID`  int(10) DEFAULT NULL,
                    CHANGE `event_id`   `activityID` int(10) DEFAULT NULL
                    ;");

        exec_query("ALTER TABLE `${p}grp` RENAME TO `${p}groups`,
                    CHANGE `grp_ID`    `groupID` int(10) NOT NULL AUTO_INCREMENT,
                    CHANGE `grp_name`  `name`    varchar(160) NOT NULL,
                    CHANGE `grp_trash` `trash`   tinyint(1) NOT NULL DEFAULT '0'
                    ;");

        exec_query("ALTER TABLE `${p}grp_evt` RENAME TO `${p}groups_activities`,
                    CHANGE `grp_ID` `groupID`    int(10) NOT NULL,
                    CHANGE `evt_ID` `activityID` int(10) NOT NULL,
                    DROP `uid`,
                    ADD PRIMARY KEY (`groupID`, `activityID`);");

        exec_query("ALTER TABLE `${p}grp_knd` RENAME TO `${p}groups_customers`,
                    CHANGE `grp_ID` `groupID`    int(10) NOT NULL,
                    CHANGE `knd_ID` `customerID` int(10) NOT NULL,
                    DROP `uid`,
                    ADD PRIMARY KEY (`groupID`, `customerID`);");

        exec_query("ALTER TABLE `${p}grp_pct` RENAME TO `${p}groups_projects`,
                    CHANGE `grp_ID` `groupID`    int(10) NOT NULL,
                    CHANGE `pct_ID` `projectID` int(10) NOT NULL,
                    DROP `uid`,
                    ADD PRIMARY KEY (`groupID`, `projectID`);");

        exec_query("ALTER TABLE `${p}grp_usr` RENAME TO `${p}groups_users`,
                    CHANGE `grp_ID` `groupID`    int(10) NOT NULL,
                    CHANGE `usr_ID` `userID` int(10) NOT NULL;");

        exec_query("ALTER TABLE `${p}knd` RENAME TO `${p}customers`,
                    CHANGE `knd_ID`       `customerID` int(10) NOT NULL AUTO_INCREMENT,
                    CHANGE `knd_name`     `name`       varchar(255) NOT NULL,
                    CHANGE `knd_password` `password`   varchar(255) DEFAULT NULL,
                    CHANGE `knd_secure`   `secure`     varchar(60) NOT NULL DEFAULT '0',
                    CHANGE `knd_comment`  `comment`    text NOT NULL,
                    CHANGE `knd_visible`  `visible`    tinyint(1) NOT NULL DEFAULT '1',
                    CHANGE `knd_filter`   `filter`     tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `knd_company`  `company`    varchar(255) NOT NULL,
                    CHANGE `knd_vat`      `vat`        varchar(255) NOT NULL,
                    CHANGE `knd_contact`  `contact`    varchar(255) NOT NULL,
                    CHANGE `knd_street`   `street`     varchar(255) NOT NULL,
                    CHANGE `knd_zipcode`  `zipcode`    varchar(255) NOT NULL,
                    CHANGE `knd_city`     `city`       varchar(255) NOT NULL,
                    CHANGE `knd_tel`      `phone`      varchar(255) NOT NULL,
                    CHANGE `knd_fax`      `fax`        varchar(255) NOT NULL,
                    CHANGE `knd_mobile`   `mobile`     varchar(255) NOT NULL,
                    CHANGE `knd_mail`     `mail`       varchar(255) NOT NULL,
                    CHANGE `knd_homepage` `homepage`   varchar(255) NOT NULL,
                    CHANGE `knd_trash`    `trash`      tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `knd_timezone` `timezone`   varchar(255) NOT NULL
                    ;");

        exec_query("ALTER TABLE `${p}ldr` RENAME TO `${p}groupleaders`,
                    CHANGE `grp_ID`     `groupID` int(10) NOT NULL,
                    CHANGE `grp_leader` `userID`  int(10) NOT NULL,
                    DROP `uid`,
                    ADD PRIMARY KEY (`groupID`, `userID`)
                    ;");

        exec_query("ALTER TABLE `${p}pct` RENAME TO `${p}projects`,
                    CHANGE `pct_ID`       `projectID`  int(10) NOT NULL AUTO_INCREMENT,
                    CHANGE `pct_kndID`    `customerID` int(3) NOT NULL,
                    CHANGE `pct_name`     `name`       varchar(255) NOT NULL,
                    CHANGE `pct_comment`  `comment`    text NOT NULL,
                    CHANGE `pct_visible`  `visible`    tinyint(1) NOT NULL DEFAULT '1',
                    CHANGE `pct_filter`   `filter`     tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `pct_trash`    `trash`      tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `pct_budget`   `budget`     decimal(10,2) NOT NULL DEFAULT '0.00',
                    CHANGE `pct_effort`   `effort`     decimal(10,2) DEFAULT NULL,
                    CHANGE `pct_approved` `approved`   decimal(10,2) DEFAULT NULL,
                    CHANGE `pct_internal` `internal`   tinyint(1) NOT NULL DEFAULT '0'
                    ;");

        // fix ER_WARN_DATA_TRUNCATED for evt_budget
        exec_query("UPDATE `${p}pct_evt` SET `evt_budget` = 0.00 WHERE `evt_budget` IS NULL");

        exec_query("ALTER TABLE `${p}pct_evt` RENAME TO `${p}projects_activities`,
                    CHANGE `pct_ID` `projectID`  int(10) NOT NULL,
                    CHANGE `evt_ID` `activityID` int(10) NOT NULL,
                    CHANGE `evt_budget`   `budget`     decimal(10,2) NOT NULL DEFAULT '0.00',
                    CHANGE `evt_effort`   `effort`     decimal(10,2) DEFAULT NULL,
                    CHANGE `evt_approved` `approved`   decimal(10,2) DEFAULT NULL,
                    DROP `uid`,
                    ADD PRIMARY KEY (`projectID`, `activityID`)
                    ;");

        exec_query("ALTER TABLE `${p}preferences`
                    CHANGE `var` `option` varchar(255) NOT NULL
                    ;");

        exec_query("ALTER TABLE `${p}rates`
                    CHANGE `user_id`    `userID`     int(10) DEFAULT NULL,
                    CHANGE `project_id` `projectID`  int(10) DEFAULT NULL,
                    CHANGE `event_id`   `activityID` int(10) DEFAULT NULL
                    ;");

        exec_query("ALTER TABLE `${p}status` RENAME TO `${p}statuses`,
CHANGE `status_id` `statusID` tinyint(4) NOT NULL AUTO_INCREMENT
;");

        exec_query("ALTER TABLE `${p}usr` RENAME TO `${p}users`,
                    CHANGE `usr_ID`        `userID`   int(10) NOT NULL,
                    CHANGE `usr_name`      `name`     varchar(160) COLLATE latin1_general_ci NOT NULL,
                    CHANGE `usr_alias`     `alias`    varchar(10) COLLATE latin1_general_ci DEFAULT NULL,
                    CHANGE `usr_sts`       `status`   tinyint(1) NOT NULL DEFAULT '2',
                    CHANGE `usr_trash`     `trash`    tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `usr_active`    `active`   tinyint(1) NOT NULL DEFAULT '1',
                    CHANGE `usr_mail`      `mail`     varchar(160) COLLATE latin1_general_ci NOT NULL DEFAULT '',
                    CHANGE `pw`            `password` varchar(254) COLLATE latin1_general_ci DEFAULT NULL,
                    CHANGE `ban`           `ban`      int(1) NOT NULL DEFAULT '0',
                    CHANGE `banTime`       `banTime`  int(10) NOT NULL DEFAULT '0',
                    CHANGE `secure`        `secure`   varchar(60) COLLATE latin1_general_ci NOT NULL DEFAULT '0',
                    CHANGE `lastEvent`     `lastActivity` int(10) NOT NULL DEFAULT '1',
                    CHANGE `timespace_in`  `timeframeBegin` varchar(60) COLLATE latin1_general_ci NOT NULL DEFAULT '0',
                    CHANGE `timespace_out` `timeframeEnd`   varchar(60) COLLATE latin1_general_ci NOT NULL DEFAULT '0',
                    DROP PRIMARY KEY,
                    ADD PRIMARY KEY (`userID`),
                    ADD UNIQUE KEY `name` (`name`)
                    ;");

        exec_query("ALTER TABLE `${p}var` RENAME TO `${p}configuration`,
                    CHANGE `var` `option` varchar(255) NOT NULL
                    ;");

        exec_query("UPDATE `${p}configuration` SET `option` = 'project_comment_flag' WHERE `option` = 'pct_comment_flag';");

        exec_query("ALTER TABLE `${p}zef` RENAME TO `${p}timeSheet`,
                    CHANGE `zef_ID`           `timeEntryID`     int(10) NOT NULL AUTO_INCREMENT,
                    CHANGE `zef_in`           `start`           int(10) NOT NULL DEFAULT '0',
                    CHANGE `zef_out`          `end`             int(10) NOT NULL DEFAULT '0',
                    CHANGE `zef_time`         `duration`        int(6) NOT NULL DEFAULT '0',
                    CHANGE `zef_usrID`        `userID`          int(10) NOT NULL,
                    CHANGE `zef_pctID`        `projectID`       int(10) NOT NULL,
                    CHANGE `zef_evtID`        `activityID`      int(10) NOT NULL,
                    CHANGE `zef_description`  `description`     text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                    CHANGE `zef_comment`      `comment`         text COLLATE latin1_general_ci,
                    CHANGE `zef_comment_type` `commentType`     tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `zef_cleared`      `cleared`         tinyint(1) NOT NULL DEFAULT '0',
                    CHANGE `zef_location`     `location`        varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
                    CHANGE `zef_trackingnr`   `trackingNumber`  varchar(30) COLLATE latin1_general_ci DEFAULT NULL,
                    CHANGE `zef_rate`         `rate`            decimal(10,2) NOT NULL DEFAULT '0.00',
                    CHANGE `zef_fixed_rate`   `fixedRate`       decimal(10,2) NOT NULL DEFAULT '0.00',
                    CHANGE `zef_budget`       `budget`          decimal(10,2) DEFAULT NULL,
                    CHANGE `zef_approved`     `approved`        decimal(10,2) DEFAULT NULL,
                    CHANGE `zef_status`       `statusID`        smallint(6) NOT NULL,
                    CHANGE `zef_billable`     `billable`        tinyint(4) DEFAULT NULL COMMENT 'how many percent are billable to customer'
                    ;");

    }

    if ($revisionDB < 1370) {
        $result          = $database->queryAll("SELECT `value` FROM ${p}configuration WHERE `option` = 'defaultTimezone'");
        $timezone = $result[0][0];

        $success = write_config_file(
            $kga['server_database'],
            $kga['server_hostname'],
            $kga['server_username'],
            $kga['server_password'],
            $kga['password_salt'],
            $kga['server_prefix'],
            $kga['authenticator'],
            $kga['pref']['language'],
            $timezone);

        if ($success) {
            $level      = 'green';
            $additional = 'Timezone: ' . $timezone;
        }
        else {
            $level      = 'red';
            $additional = 'Unable to write to file.';
        }

        printLine($level, 'Store default timezone in configuration file <i>autoconf.php</i>.', $additional);

        if ($success) {
            exec_query("DELETE FROM `${p}configuration` WHERE `option` = 'defaultTimezone'");
        }
    }

    if ($revisionDB < 1371) {
        // The mentioned columns were accidentially removed by the update script. But there was no release since then.
        // Therefore this updater was fixed to to the right thing now: Keep the column and rename it correctly.
        // But there might be people using the development version. They lost their data but we have to add the columns again.
        // That's why these queries are allowed to fail. This will happen for all not using a development version.

        exec_query("ALTER TABLE `${p}activities`
                    DROP `budget`,
                    DROP `effort`,
                    DROP `approved`
                    ;", false);
    }

    if ($revisionDB < 1372) {
        exec_query("ALTER TABLE `${p}users` CHANGE `alias` `alias` varchar(160);");
    }

    if ($revisionDB < 1373) {
        exec_query("ALTER TABLE `${p}activities` DROP `assignable`;");
    }

    if ($revisionDB < 1374) {

        require('installer/installPermissions.php');

        // add membershipRoleID column, initialized with user role
        exec_query("ALTER TABLE `${p}groups_users` ADD `membershipRoleID` int(10) DEFAULT $membershipUserRoleID;");
        exec_query("ALTER TABLE `${p}groups_users` CHANGE `membershipRoleID` `membershipRoleID` int(10) NOT NULL;");

        // add globalRoleID column, initialized with user role
        exec_query("ALTER TABLE `${p}users` ADD `globalRoleID` int(10) DEFAULT $globalUserRoleID;");
        exec_query("ALTER TABLE `${p}users` CHANGE `globalRoleID` `globalRoleID` int(10) NOT NULL;");
        exec_query("UPDATE `${p}users` SET `globalRoleID` = (SELECT globalRoleID FROM `${p}globalRoles` WHERE name = 'Admin') WHERE status=0;");

        // set groupleader role
        exec_query("UPDATE `${p}groups_users` SET membershipRoleID=(SELECT membershipRoleID FROM `${p}membershipRoles` WHERE name = 'Groupleader') WHERE (groupID,userID) IN (SELECT groupID, userID FROM `${p}groupleaders`)");

        // set admin role
        exec_query("UPDATE `${p}groups_users` SET membershipRoleID=(SELECT membershipRoleID FROM `${p}membershipRoles` WHERE name = 'Admin') WHERE userID IN (SELECT userID FROM `${p}users` WHERE status=0)");
    }


    if ($revisionDB < 1375) {
        foreach (array('customer', 'project', 'activity', 'group', 'user') as $object) {
            exec_query("ALTER TABLE `${p}globalRoles` ADD `core-$object-otherGroup-view` tinyint DEFAULT 1;");
            exec_query("ALTER TABLE `${p}globalRoles` CHANGE `core-$object-otherGroup-view` `core-$object-otherGroup-view` tinyint DEFAULT 0;");
      }

        exec_query("DROP TABLE `${p}groupleaders`;");
    }


    if ($revisionDB < 1376) {
        exec_query("UPDATE `${p}globalRoles` SET `demo_ext-access` = 1 WHERE `name` = 'Admin';");
    }

    if ($revisionDB < 1377) {
        exec_query("ALTER TABLE `${p}rates` ADD UNIQUE KEY(`userID`, `projectID`, `activityID`);");
    }

    if ($revisionDB < 1378) {
        exec_query("UPDATE `${p}configuration` SET `value` = '0' WHERE `option` = 'show_sensible_data';");
    }

    if ($revisionDB < 1379) {

        if (!isset($timezone) && isset($kga['pref']['timezone'])) {
            $timezone = $kga['pref']['timezone'];
        }
        if (!isset($timezone)) {
            $timezone = null;
        }

        $success = write_config_file(
            $kga['server_hostname'],
            $kga['server_database'],
            $kga['server_username'],
            $kga['server_password'],
            $kga['password_salt'],
            $kga['server_prefix'],
            $kga['authenticator'],
            $kga['pref']['language'],
            $timezone);

        if ($success) {
            $level = 'green';
        }
        else {
            $level = 'red';
        }

        printLine($level, 'Updated autoconf.php to use MYSQL configuration in <i>autoconf.php</i>.');
    }

    if ($revisionDB < 1380) {
        Logger::logfile('-- update to r1380');
        exec_query("INSERT INTO `${p}configuration` VALUES('allowRoundDown', '1');");
    }

    if ($revisionDB < 1381) {
        Logger::logfile('-- update to r1381');
        // make sure all keys are defined correctly
        exec_query("ALTER TABLE `${p}expenses`            ADD INDEX      (`userID`);", false);
        exec_query("ALTER TABLE `${p}expenses`            ADD INDEX      (`projectID`);", false);
        exec_query("ALTER TABLE `${p}fixedRates`          ADD UNIQUE  KEY(`projectID`, `activityID`);", false);
        exec_query("ALTER TABLE `${p}groups_activities`   ADD UNIQUE  KEY(`groupID`, `activityID`);", false);
        exec_query("ALTER TABLE `${p}groups_customers`    ADD UNIQUE  KEY(`groupID`, `customerID`);", false);
        exec_query("ALTER TABLE `${p}groups_projects`     ADD UNIQUE  KEY(`groupID`, `projectID`);", false);
        exec_query("ALTER TABLE `${p}groups_users`        ADD UNIQUE  KEY(`groupID`, `userID`);", false);



        exec_query("ALTER TABLE `${p}projects`            ADD INDEX      (`customerID`);", false);
        exec_query("ALTER TABLE `${p}projects_activities` ADD UNIQUE  KEY(`projectID`, `activityID`);", false);
        exec_query("ALTER TABLE `${p}rates`               ADD UNIQUE  KEY(`userID`, `projectID`, `activityID`);", false);


        exec_query("ALTER TABLE `${p}timeSheet`           ADD INDEX      (`userID`);", false);
        exec_query("ALTER TABLE `${p}timeSheet`           ADD INDEX      (`projectID`);", false);
        exec_query("ALTER TABLE `${p}timeSheet`           ADD INDEX      (`activityID`);", false);

        exec_query("ALTER TABLE `${p}users`               ADD UNIQUE  KEY(`name`);", false);
        exec_query("ALTER TABLE `${p}users`               ADD UNIQUE  KEY(`apiKey`);", false);

    }

if ($revisionDB < 1382) {
    Logger::logfile('-- update to r1382');

    exec_query("UPDATE `${p}membershipRoles` SET `core-user-view` = 1 WHERE `name` = 'Admin';");
    exec_query("UPDATE `${p}membershipRoles` SET `core-user-view` = 1 WHERE `name` = 'Groupleader';");
}

if ($revisionDB < 1383) {
    Logger::logfile('-- update to r1383');
    exec_query("INSERT INTO `${p}configuration` VALUES('defaultStatusID', '1');");
}

if ($revisionDB < 1384) {
    Logger::logfile('-- update to r1384');
    exec_query("ALTER TABLE ${p}users ADD COLUMN `passwordResetHash` char(32) NULL DEFAULT NULL AFTER `password`");
    exec_query("ALTER TABLE ${p}customers ADD COLUMN `passwordResetHash` char(32) NULL DEFAULT NULL AFTER `password`");
}

    /*      2015 07 11      */
    if ($revisionDB < 1385) {
        Logger::logfile('-- update to r2000 Kimai-i');

        $database->close();
        $database->connect();

        // RENAME TABLES TO SAFE lower-case & underscore, and singular
        exec_query("RENAME TABLE `${p}activities` TO `${p}activity`                 ;",1);
        exec_query("RENAME TABLE `${p}customers` TO `${p}customer`                  ;",1);
        exec_query("RENAME TABLE `${p}expenses` TO `${p}expense`                    ;",1);
        exec_query("RENAME TABLE `${p}fixedRates` TO `${p}fixed_rate`               ;",1);
        exec_query("RENAME TABLE `${p}globalroles` TO `${p}global_role`             ;",1);
        exec_query("RENAME TABLE `${p}groups_activities` TO `${p}group_activity`    ;",1);
        exec_query("RENAME TABLE `${p}groups_customers` TO `${p}group_customer`     ;",1);
        exec_query("RENAME TABLE `${p}groups_projects` TO `${p}group_project`       ;",1);
        exec_query("RENAME TABLE `${p}groups_users` TO `${p}group_user`             ;",1);
        exec_query("RENAME TABLE `${p}groups` TO `${p}group`                        ;",1);
        exec_query("RENAME TABLE `${p}membershipRoles` TO `${p}membership_role`     ;",1);
        exec_query("RENAME TABLE `${p}preferences` TO `${p}preference`              ;",1);
        exec_query("RENAME TABLE `${p}projects_activities` TO `${p}project_activity`;",1);
        exec_query("RENAME TABLE `${p}projects` TO `${p}project`                    ;",1);
        exec_query("RENAME TABLE `${p}rates` TO `${p}rate`                          ;",1);
        exec_query("RENAME TABLE `${p}statuses` TO `${p}status`                     ;",1);
        exec_query("RENAME TABLE `${p}users` TO `${p}user`                          ;",1);



        // STANDARDIZE LOWERCASE & UNDERSCORE COLUMN NAMES
        exec_query(
            "ALTER TABLE `${p}user`
            CHANGE `userID`             `user_id` int(10) unsigned NOT NULL,
            CHANGE `banTime`            `ban_time` int(10) unsigned NOT NULL default '0',
            CHANGE `lastProject`        `last_project` int(10) unsigned NOT NULL default '1',
            CHANGE `lastActivity`       `last_activity` int(10) unsigned NOT NULL default '1',
            CHANGE `lastRecord`         `last_record` int(10) unsigned NOT NULL default '0',
            CHANGE `globalRoleID`       `global_role_id` int(10) unsigned NOT NULL,
            CHANGE `passwordResetHash`  `password_reset_hash` char(32) NULL DEFAULT NULL,
            CHANGE `timeframeBegin`     `timeframe_begin` varchar(60) NOT NULL default '0',
            CHANGE `timeframeEnd`       `timeframe_end` varchar(60) NOT NULL default '0'
            ;");

        exec_query(
            "ALTER TABLE `${p}preference`
            CHANGE `userID`             `user_id` int(10) unsigned NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}activity`
            CHANGE `activityID`             `activity_id` int(10) unsigned NOT NULL AUTO_INCREMENT
            ;");

        exec_query(
            "ALTER TABLE `${p}global_role`
            CHANGE `globalRoleID`                                `global_role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `deb_ext-access`                              `ki_debug__access`tinyint(4) DEFAULT '0',
            CHANGE `adminPanel_extension-access`                 `ki_admin__access`tinyint(4) DEFAULT '0',
            CHANGE `ki_budget-access`                            `ki_budget__access`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-access`                          `ki_expense__access`tinyint(4) DEFAULT '0',
            CHANGE `ki_export-access`                            `ki_export__access`tinyint(4) DEFAULT '0',
            CHANGE `ki_invoice-access`                           `ki_invoice__access`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheet-access`                         `ki_timesheet__access`tinyint(4) DEFAULT '0',
            CHANGE `demo_ext-access`                             `demo_ext__access`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-otherGroup-add`                `core__customer__other_group__add`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-otherGroup-edit`               `core__customer__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-otherGroup-delete`             `core__customer__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-otherGroup-assign`             `core__customer__other_group__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-otherGroup-unassign`           `core__customer__other_group__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-project-otherGroup-add`                 `core__project__other_group__add`tinyint(4) DEFAULT '0',
            CHANGE `core-project-otherGroup-edit`                `core__project__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-project-otherGroup-delete`              `core__project__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-project-otherGroup-assign`              `core__project__other_group__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-project-otherGroup-unassign`            `core__project__other_group__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-otherGroup-add`                `core__activity__other_group__add`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-otherGroup-edit`               `core__activity__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-otherGroup-delete`             `core__activity__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-otherGroup-assign`             `core__activity__other_group__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-otherGroup-unassign`           `core__activity__other_group__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-user-otherGroup-add`                    `core__user__other_group__add`tinyint(4) DEFAULT '0',
            CHANGE `core-user-otherGroup-edit`                   `core__user__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-user-otherGroup-delete`                 `core__user__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-user-otherGroup-assign`                 `core__user__other_group__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-user-otherGroup-unassign`               `core__user__other_group__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-status-add`                             `core__status__add`tinyint(4) DEFAULT '0',
            CHANGE `core-status-edit`                            `core__status__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-status-delete`                          `core__status__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-group-add`                              `core__group__add`tinyint(4) DEFAULT '0',
            CHANGE `core-group-otherGroup-edit`                  `core__group__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-group-otherGroup-delete`                `core__group__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `adminPanel_extension-editAdvanced`           `ki_admin__edit_advanced`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-ownEntry-add`                  `ki_timesheet__own_entry__add`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-otherEntry-otherGroup-add`     `ki_timesheet__other_entry__other_group__add`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-ownEntry-edit`                 `ki_timesheet__own_entry__edit`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-otherEntry-otherGroup-edit`    `ki_timesheet__other_entry__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-ownEntry-delete`               `ki_timesheet__own_entry__delete`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-otherEntry-otherGroup-delete`  `ki_timesheet__other_entry__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-showRates`                     `ki_timesheet__show_rates`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-editRates`                     `ki_timesheet__edit_rates`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-ownEntry-add`                    `ki_expense__own_entry__add`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-otherEntry-otherGroup-add`       `ki_expense__other_entry__other_group__add`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-ownEntry-edit`                   `ki_expense__own_entry__edit`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-otherEntry-otherGroup-edit`      `ki_expense__other_entry__other_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-ownEntry-delete`                 `ki_expense__own_entry__delete`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-otherEntry-otherGroup-delete`    `ki_expense__other_entry__other_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-otherGroup-view`               `core__customer__other_group__view`tinyint(4) DEFAULT '0',
            CHANGE `core-project-otherGroup-view`                `core__project__other_group__view`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-otherGroup-view`               `core__activity__other_group__view`tinyint(4) DEFAULT '0',
            CHANGE `core-group-otherGroup-view`                  `core__group__other_group__view`tinyint(4) DEFAULT '0',
            CHANGE `core-user-otherGroup-view`                   `core__user__other_group__view`tinyint(4) DEFAULT '0'
            ;");

        exec_query(
            "ALTER TABLE `${p}group`
            CHANGE `groupID`             `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT
            ;");

        exec_query(
            "ALTER TABLE `${p}group_user`
            CHANGE `groupID`             `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `userID`              `user_id` int(10) unsigned NOT NULL,
            CHANGE `membershipRoleID`    `membership_role_id` int(10) unsigned NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}group_customer`
            CHANGE `groupID`             `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `customerID`          `customer_id` int(10) unsigned NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}group_project`
            CHANGE `groupID`             `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `projectID`           `project_id` int(10) unsigned NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}group_activity`
            CHANGE `groupID`             `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `activityID`          `activity_id` int(10) unsigned NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}membership_role`
            CHANGE `membershipRoleID`                            `membership_role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `core-customer-add`                           `core__customer__add`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-edit`                          `core__customer__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-delete`                        `core__customer__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-assign`                        `core__customer__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-customer-unassign`                      `core__customer__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-project-add`                            `core__project__add`tinyint(4) DEFAULT '0',
            CHANGE `core-project-edit`                           `core__project__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-project-delete`                         `core__project__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-project-assign`                         `core__project__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-project-unassign`                       `core__project__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-add`                           `core__activity__add`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-edit`                          `core__activity__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-delete`                        `core__activity__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-assign`                        `core__activity__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-activity-unassign`                      `core__activity__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-user-add`                               `core__user__add`tinyint(4) DEFAULT '0',
            CHANGE `core-user-edit`                              `core__user__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-user-delete`                            `core__user__delete`tinyint(4) DEFAULT '0',
            CHANGE `core-user-assign`                            `core__user__assign`tinyint(4) DEFAULT '0',
            CHANGE `core-user-unassign`                          `core__user__unassign`tinyint(4) DEFAULT '0',
            CHANGE `core-user-view`                              `core__user__view`tinyint(4) DEFAULT '0',
            CHANGE `core-group-edit`                             `core__group__edit`tinyint(4) DEFAULT '0',
            CHANGE `core-group-delete`                           `core__group__delete`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-otherEntry-ownGroup-add`       `ki_timesheet__other_entry__own_group__add`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-otherEntry-ownGroup-edit`      `ki_timesheet__other_entry__own_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `ki_timesheets-otherEntry-ownGroup-delete`    `ki_timesheet__other_entry__own_group__delete`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-otherEntry-ownGroup-add`         `ki_expense__other_entry__own_group__add`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-otherEntry-ownGroup-edit`        `ki_expense__other_entry__own_group__edit`tinyint(4) DEFAULT '0',
            CHANGE `ki_expenses-otherEntry-ownGroup-delete`      `ki_expense__other_entry__own_group__delete`tinyint(4) DEFAULT '0'
            ;");

        exec_query(
            "ALTER TABLE `${p}project_activity`
            CHANGE `projectID`           `project_id` int(10) unsigned NOT NULL,
            CHANGE `activityID`          `activity_id` int(10) unsigned NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}customer`
            CHANGE `customerID`           `customer_id` int(10) unsigned NOT NULL,
            CHANGE `passwordResetHash`    `password_reset_hash` char(32) NULL DEFAULT NULL,
            CHANGE `vat`                  `vat_rate` varchar(6) DEFAULT '0',
            ADD `timeframe_begin` VARCHAR( 60 ) NOT NULL DEFAULT '0',
            ADD `timeframe_end` VARCHAR( 60 ) NOT NULL DEFAULT '0'
            ;");

        exec_query(
            "ALTER TABLE `${p}project`
            CHANGE `projectID`           `project_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `customerID`          `customer_id` int(3) NOT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}timesheet`
            CHANGE `timeEntryID`           `time_entry_id` int(10) unsigned unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `userID`                `user_id` int(10) unsigned NOT NULL,
            CHANGE `projectID`             `project_id` int(10) unsigned NOT NULL,
            CHANGE `activityID`            `activity_id` int(10) unsigned NOT NULL,
            CHANGE `commentType`           `comment_type` TINYINT(1) unsigned NOT NULL DEFAULT '0',
            CHANGE `statusID`              `status_id` SMALLINT NOT NULL,
            CHANGE `fixedRate`             `fixed_rate` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0',
            CHANGE `trackingNumber`        `ref_code` varchar(30)
            ;");

        exec_query(
            "ALTER TABLE `${p}rate`
            CHANGE `userID`                `user_id` int(10) unsigned DEFAULT NULL,
            CHANGE `projectID`             `project_id` int(10) unsigned DEFAULT NULL,
            CHANGE `activityID`            `activity_id` int(10) unsigned DEFAULT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}fixed_rate`
            CHANGE `projectID`             `project_id` int(10) unsigned DEFAULT NULL,
            CHANGE `activityID`            `activity_id` int(10) unsigned DEFAULT NULL
            ;");

        exec_query(
            "ALTER TABLE `${p}expense`
            CHANGE `expenseID`             `expense_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            CHANGE `userID`                `user_id` int(10) unsigned NOT NULL,
            CHANGE `projectID`             `project_id` int(10) unsigned NOT NULL,
            CHANGE `commentType`           `comment_type` TINYINT(1) unsigned NOT NULL DEFAULT '0',
            CHANGE `designation`           `description` TEXT  NOT NULL DEFAULT ''
            ;");

        exec_query(
            "ALTER TABLE `${p}status`
            CHANGE `statusID`             `status_id` TINYINT NOT NULL AUTO_INCREMENT
            ;");


        // gather all userID in preferences
        // customerID checked in this array for uniqueness
        $users = $database->queryAll("SELECT `user_id` FROM `${p}preference` group by `user_id`");
        $userIds = array_column($users,'user_id');

        // convert customerID if necessary & create preference entries              //
        $customers = $database->queryAll("SELECT customer_id, timezone FROM ${p}customer");
        $customerIds = array_column($customers,'customer_id');
        $timezones =  array_column($customers,'timezone');

        foreach ($customerIds as $key => $customerId) {

            $newId = $customerId;
            if (in_array($customerId,$userIds)) {
                // need new ID for customer
                do {
                    $newId = random_number(9);
                } while (in_array($newId,$userIds) || in_array($newId,$customerIds));

                // update all related tables
                exec_query("UPDATE `${p}customer` SET `customer_id` = ${newId} WHERE `customer_id` = ${customerId};", 1);
                exec_query("UPDATE `${p}group_customer` SET `customer_id` = ${newId} WHERE `customer_id` = ${customerId};", 1);
                exec_query("UPDATE `${p}project` SET `customer_id` = ${newId} WHERE `customer_id` = ${customerId};", 1);
            }

            // default preferences  NEW FOR CUSTOMERS
            exec_query("REPLACE INTO `${p}preference` (`user_id`,`option`,`value`) VALUES
            ('${newId}','ui.autoselection','1'),
            ('${newId}','ui.flip_project_display','0'),
            ('${newId}','ui.hide_cleared_entries','0'),
            ('${newId}','ui.hide_overlap_lines','1'),
            ('${newId}','ui.language','" . $kga['pref']['language'] . "'),
            ('${newId}','ui.no_fading','0'),
            ('${newId}','ui.open_after_recorded','0'),
            ('${newId}','ui.project_comment_flag','0'),
            ('${newId}','ui.quickdelete','0'),
            ('${newId}','ui.rowlimit','100'),
            ('${newId}','ui.show_comments_by_default','0'),
            ('${newId}','ui.show_ids','0'),
            ('${newId}','ui.show_ref_code','1'),
            ('${newId}','ui.skin','standard'),
            ('${newId}','ui.sublist_annotations','2'),
            ('${newId}','ui.timezone','" . $timezones[$key] . "'),
            ('${newId}','ui.user_list_hidden','0')
            ;");
        }


        // PREFERENCE UPDATE //
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.hide_cleared_entries' WHERE `option` = 'ui.hideClearedEntries'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.hide_overlap_lines' WHERE `option` = 'ui.hideOverlapLines'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.language' WHERE `option` = 'ui.lang'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.no_fading' WHERE `option` = 'ui.noFading'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.open_after_recorded' WHERE `option` = 'ui.openAfterRecorded'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.show_comments_by_default' WHERE `option` = 'ui.showCommentsByDefault'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.show_ids' WHERE `option` = 'ui.showIDs'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.show_ref_code' WHERE `option` = 'ui.showTrackingNumber'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.sublist_annotations' WHERE `option` = 'ui.sublistAnnotations'");
        exec_query("UPDATE `${p}preference` SET `option` = 'ui.timezone' WHERE `option` = 'timezone'");



        // CONFIGURATION UPDATE //
        exec_query("UPDATE `${p}configuration` SET `option` = 'admin_mail' WHERE `option` = 'adminmail'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'allow_round_down' WHERE `option` = 'allowRoundDown'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'decimal_separator' WHERE `option` = 'decimalSeparator'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'default_status_id' WHERE `option` = 'defaultStatusID'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'vat_rate' WHERE `option` = 'defaultVat'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'duration_with_seconds' WHERE `option` = 'durationWithSeconds'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'edit_limit' WHERE `option` = 'editLimit'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'exact_sums' WHERE `option` = 'exactSums'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'login_ban_time' WHERE `option` = 'loginBanTime'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'login_tries' WHERE `option` = 'loginTries'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'round_minutes' WHERE `option` = 'roundMinutes'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'round_precision' WHERE `option` = 'roundPrecision'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'round_seconds' WHERE `option` = 'roundSeconds'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'round_timesheet_entries' WHERE `option` = 'roundTimesheetEntries'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'show_day_separator_lines' WHERE `option` = 'show_daySeperatorLines'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'show_gab_breaks' WHERE `option` = 'show_gabBreaks'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'show_record_again' WHERE `option` = 'show_RecordAgain'");

        exec_query("UPDATE `${p}configuration` SET `option` = 'core.revision' WHERE `option` = 'revision'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'core.status' WHERE `option` = 'status'");
        exec_query("UPDATE `${p}configuration` SET `option` = 'core.version' WHERE `option` = 'version'");

        exec_query("DELETE FROM `${p}configuration` WHERE `option` = 'language';");
        exec_query("DELETE FROM `${p}configuration` WHERE `option` = 'show_TrackingNr';");
        exec_query("DELETE FROM `${p}configuration` WHERE `option` = 'kimail';");

        exec_query("INSERT INTO `${p}configuration` (`option`, `value`) VALUES
                    ('core.ident','kimai-i'),
                    ('bill_pct','0,25,50,75,100'),
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
                    ('ud.timezone','" . $timezones[$key] . "'),
                    ('ud.user_list_hidden','0');");
        
        // load & clean config table
        $database->config_load(true);
    }

    //CN, NEXT DB REVISION SHOULD CHECK core.ident


    // ============================
    // = update DB version number =
    // ============================
    if (!$errors
    && ($revisionDB < $kga['core.revision'] || version_compare($versionDB,$kga['core.version'],'<'))) {


        $V = $kga['core.version'];
        exec_query("REPLACE INTO `${p}configuration` SET `value` = '${V}', `option` = 'core.version';", 0);

        $R = $kga['core.revision'];
        exec_query("REPLACE INTO `${p}configuration` SET `value` = '${R}', `option` = 'core.revision';", 0);

        $S = $kga['core.status'];
        exec_query("REPLACE INTO `${p}configuration` SET `value` = '${S}', `option` = 'core.status';", 0);

    }

    Logger::logfile("-- update finished --------------------------------");

    if ($revisionDB == $kga['core.revision']) {
        echo "<script type=\"text/javascript\">window.location.href = \"index.php\";</script>";
    }
    else {

        $login = $kga['lang']['login'];
        $updater_90 = $kga['lang']['updater'][90];

        if (!$errors) {

            $updater_80 = $kga['lang']['updater'][80];

            echo <<<HTML
<script type="text/javascript">
$("#link").append("<p><strong>$updater_80</strong></p>");
$("#link").append("<h1><a href='index.php'>$login</a></h1>");
$("#link").addClass("success");
$("#queries").append("$executed_queries $updater_90</p>");
</script>
HTML;

        }
        else {

            $updater_100 = $kga['lang']['updater'][100];

            echo <<<HTML
<script type="text/javascript">
$("#link").append("<p><strong>$updater_100</strong></p>");
$("#link").append("<h1><a href='index.php'>$login</a></h1>");
$("#link").addClass("fail");
$("#queries").append("$executed_queries $updater_90");
</script>
HTML;
        }
    } ?>

    </table>

    <?php
    if (isset($new_passwords)) {
        ?>
        <br/><br/>
        <script type="text/javascript">
            $("#important_message").append("<?php echo $kga['lang']['updater'][120];?> <br/>");
            $("#important_message").show();
        </script>
        <div class="important_block_head"> <?php echo $kga['lang']['updater'][110]; ?>:</div>
        <table style="width:100%">
            <tr>
                <td><i> <?php echo $kga['lang']['username']; ?> </i></td>
                <td><i> <?php echo $kga['lang']['password']; ?> </i></td>
            </tr>
            <?php
            foreach ($new_passwords as $username => $password) {
                echo "<tr><td>$username</td><td>$password</td></tr>";
            }
            ?>
        </table><br/>
    <?php
    }
    ?>


    <?php echo "$executed_queries " . $kga['lang']['updater'][90]; ?>

    <h1><a href='index.php'><?php echo $kga['lang']['login']; ?></a></h1>

    </body>
    </html>

<?php
} // end of "do you have a backup blah" condition
