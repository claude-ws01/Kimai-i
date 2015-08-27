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
 * Execute an sql query in the database. The correct database connection
 * will be chosen and the query will be logged with the success status.
 *
 * @param string $query query to execute as string
 */
function exec_query($query)
{
    global $database;

    $result   = $database->query($query);
    $errorInfo = serialize($database->error());

    Logger::logfile($query);
    if ($result === false) {
        Logger::logfile($errorInfo);

        return false;
    }

    return true;
}

/**
 * This file allows the user to create and restore backups. The backups are
 * kept within the database, so they aren't true backups but more like
 * snapshots.
 */

global $database, $kga, $executed_queries, $translations;

require('includes/basics.php');


//CN..blocked feature in demo mode.
if (DEMO_MODE) {
    header("Location: http://${_SERVER['SERVER_NAME']}/index.php");
}


if (isset($_REQUEST['submit'], $_REQUEST['salt'])
    && $_REQUEST['submit'] === $kga['dict']['login']
    && $_REQUEST['salt'] === $kga['password_salt']
) {
    $cookieValue = sha1($kga['password_salt']);
    cookie_set('db_restore_authCode', $cookieValue);
    $_COOKIE['db_restore_authCode'] = $cookieValue;
}

$authenticated = (isset($_COOKIE['db_restore_authCode']) && $_COOKIE['db_restore_authCode'] === sha1($kga['password_salt']));

if ($authenticated && isset($_REQUEST['submit'])) {
    $version_temp = $database->get_DBversion();
    $versionDB    = $version_temp[0];
    $revisionDB   = $version_temp[1];
    $p            = $kga['server_prefix'];

    if ($_REQUEST['submit'] === $kga['dict']['backup'][8]) {
        /**
         * Create a backup.
         */

        Logger::logfile('-- begin backup -----------------------------------');
        $backup_stamp = time();
        $query        = ('SHOW TABLES;');

        if (is_object($database)) {
            $result = $database->query($query);
            $tables  = $database->recordsArray();

            $prefix_length = strlen($p);

            if (is_array($tables)) {
                foreach ((array)$tables as $row) {

                    if (substr($row[0], 0, $prefix_length) === $p
                        && substr($row[0], 0, 10) !== 'kimai_bak_'
                    ) {

                        $backupTable = "kimai_bak_${backup_stamp}_${row[0]}";

                        $query       = "CREATE TABLE IF NOT EXISTS $backupTable LIKE $row[0]";
                        $ok = exec_query($query);

                        if (!$ok) {
                            Logger::logfile('-- backup error - ' . $query);
                        }
                        else {
                            $query = "INSERT INTO $backupTable SELECT * FROM $row[0]";
                            $ok    = exec_query($query);
                        }

                        if (!$ok) {
                            Logger::logfile('-- restore error - ' . $query);
                            die($kga['dict']['updater'][60]);
                        }
                    }
                }

                Logger::logfile('-- backup finished -----------------------------------');
            }

            else {
                Logger::logfile('-- backup - no tables found --------------------------');
            }
        }

        else {
            Logger::logfile('-- backup failed - no DB connection -----------------------------------');
        }

        header('location: db_restore.php');
    }

    if ($_REQUEST['submit'] === $kga['dict']['backup'][3]) {
        /**
         * Delete backups.
         */
        $dates = $_REQUEST['dates'];

        $query = ('SHOW TABLES;');
        $tables = array();
        if (is_object($database)) {
            $result = $database->query($query);
            $tables  = $database->recordsArray();
        }

        if (!$ok = count($tables) > 0) {
            Logger::logfile('-- delete backup - no tables found --------------------------');
        }
        else {
            foreach ((array)$tables as $row) {
                if ((substr($row[0], 0, 10) === 'kimai_bak_')
                    && in_array(substr($row[0], 10, 10), $dates, true)
                ) {
                    $arr2[] = "drop table `${row[0]}`;";
                }
            }

            if (!is_object($database)) {
                Logger::logfile('-- delete backup error - no database connection --------------------------');
            }
            else {
                foreach ($arr2 AS $row) {
                    $result = $database->query($row);
                    if ($result === false) {
                        Logger::logfile('-- delete backup error - ' . $row);
                        break;
                    }
                }
            }
            header('location: db_restore.php');
        }
    }
}

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="robots" content="noindex,nofollow"/>
    <title>Kimai Backup Restore Utility</title>
    <style type="text/css" media="screen">
        body {
            background: #111 url('grfx/kii_twitter_bg.png') no-repeat;
            font-family: sans-serif;
        }

        a {
            color: #ccc700;
        }

        a:hover {
            color: white;
        }

        h1 {
            margin: 0;
        }

        div.main {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 500px;
            height: 250px;
            margin-left: -250px;
            margin-top: -125px;
            background-color: rgba(64, 64, 64, 0.7);;
            border-radius: 20px;
            color: #ccc;
            padding: 10px;
            text-shadow: #000 1px 1px 1px;
        }

        div.warn {
            padding: 5px;
            background-color: rgba(255, 0, 0, 0.7);
            color: yellow;
            font-weight: bold;
            text-align: center;
            border-top: 2px solid yellow;
            border-bottom: 2px solid yellow;
        }

        p.label_checkbox input {
            float: left;
        }

        p.label_checkbox label {
            display: block;
            float: left;
            margin-left: 10px;
            width: 300px;
        }

        h1.message {
            border: 3px solid white;
            padding: 10px;
            background-color: rgba(64, 64, 64, 0.7);;
            margin-right: 20px;
        }

        h1.fail {
            border: 3px solid red;
            padding: 10px;
            background-color: rgba(64, 64, 64, 0.7);;
            color: red;
            margin-right: 20px;
        }

        p.submit {
            margin-top: 25px;
        }

        p.caution {
            font-size: 80%;
            width: 100%;
        }
    </style>
</head>
<body>

<?php if (!empty($kga['dict']['backup'][0])) { ?>
    <div class="warn"><?php echo $kga['dict']['backup'][0] ?></div><?php } ?>
<div class="main">
    <?php
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // restore

    if ($authenticated
        && isset($_REQUEST['submit'], $_REQUEST['dates'])
        && $_REQUEST['submit'] === $kga['dict']['backup'][2]
    ) {

        if (count($_REQUEST['dates']) > 1) {
            echo '<h1 class="fail">' . $kga['dict']['backup'][5] . '</h1>';
        }
        else {
            $restoreDate = (int)$_REQUEST['dates'][0];
            $query       = ('SHOW TABLES;');


            $tables = array();
            $ok     = true;

            if (is_object($database)) {
                $result = $database->query($query);
                $tables  = $database->recordsArray();
            }

            $arr  = array();
            $arr2 = array();

            if (!$ok = count($tables) > 0) {
                Logger::logfile('-- restore error - no tables found --------------------------');
            }
            else {
                foreach ($tables as $row) {
                    if ((substr($row[0], 0, 10) === 'kimai_bak_')
                        && substr($row[0], 10, 10) === $restoreDate
                    ) {
                        $table  = $row[0];
                        $arr[]  = $table;
                        $arr2[] = substr($row[0], 21, 100);
                    }
                }

                $i = 0;
                foreach ($arr2 AS $newTable) {
                    $query = "DROP TABLE $arr2[$i]";
                    $ok    = exec_query($query);

                    if (!$ok) {
                        Logger::logfile('-- restore error - ' . $query);
                    }
                    else {
                        $query = "CREATE TABLE IF NOT EXISTS $newTable LIKE $arr[$i]";
                        $ok    = exec_query($query);
                    }

                    if (!$ok) {
                        Logger::logfile('-- restore error - ' . $query);
                    }
                    else {
                        $query = "INSERT INTO $newTable SELECT * FROM $arr[$i]";
                        $ok    = exec_query($query);
                    }

                    if (!$ok) {
                        Logger::logfile('-- restore error - ' . $query);
                        break;
                    }
                    $i++;
                }
            }

            if (!$ok) {
                echo '<h1 class="message">' . $kga['dict']['backup'][12] . '</h1>';
            }
            else {
                $date = @date('d. M Y, H:i:s', $restoreDate);
                echo '<h1 class="message">' . $kga['dict']['backup'][6] . ' ' . $date . '<br>' . $kga['dict']['backup'][7] . '</h1>';
            }
        }
    }
    ?>
    <form method="post" accept-charset="utf-8" action=""><?php

        if (!$authenticated) {
            echo '<h1>' . $kga['dict']['backup'][10] . '</h1>',
            '<p class="caution">', $kga['dict']['backup'][11], '</p>'; ?>
            <input type="text" name="salt" placeholder="salt password"/>
            <input type="submit" name="submit" style="cursor:pointer;" value="<?php echo $kga['dict']['login'] ?>"/>
        <?php }
        else {
            echo '<h1>' . $kga['dict']['backup'][1] . '</h1>';

            $query = ('SHOW TABLES;');

            $result_backup = $database->queryArray($query);

            $arr  = array();
            $arr2 = array();

            foreach ($result_backup as $row) {
                if ((substr($row[0], 0, 10) === 'kimai_bak_')) {
                    $time  = substr($row[0], 10, 10);
                    $arr[] = $time;
                }
            }

            $neues_array = array_unique($arr);


            foreach ($neues_array AS $date) {
                $value = @date('d. M Y - H:i:s', $date);

                $label = $value;
                if (@date('dMY', $date) === @date('dMY', time())) {
                    $label = $kga['dict']['today'] . @date(' - H:i:s', $date);
                }

                echo <<<EOD
        <p class="label_checkbox">
        <input type="checkbox" id="$value " name="dates[]" value="$date">
        <label for="$value">$label</label>
        </p>
EOD;
            }

            ?><p class="submit">
            <input type="submit" name="submit" value="<?php echo $kga['dict']['backup'][2]; ?>"> <!-- restore -->
            <input type="submit" name="submit" value="<?php echo $kga['dict']['backup'][3]; ?>"> <!-- delete -->
            <input type="submit" name="submit" value="<?php echo $kga['dict']['backup'][8]; ?>"> <!-- backup -->
            </p><?php
        }
        ?>

    </form>
    <br/>
    <a href="index.php"><?php echo $kga['dict']['backup'][13]; ?></a>

    <p class="caution"><?php echo $kga['dict']['backup'][9]; ?></p>
</div>
</body>
</html>
