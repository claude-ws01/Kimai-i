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

// ===================
// = DEBUG PROCESSOR =
// ===================

// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates/';
global $database, $kga, $view;

global $axAction, $axValue, $id, $timeframe, $in, $out;
require('../../includes/kspi.php');


switch ($axAction) {

    /**
     * Return the logfile in reverse order, so the last entries are shown first.
     */
    case 'reloadLogfile':
        $logdatei = WEBROOT . 'temporary/logfile.txt';
        $fh       = fopen($logdatei, 'r');

        $theData = '';
        $i       = 0;

        $lines     = $kga['logfile_lines'];
        $filearray = '';

        while (!feof($fh)) {
            $filearray[$i] = fgets($fh);
            $i++;
        }

        fclose($fh);

        if ($kga['logfile_lines'] !== '@') {
            $start = count($filearray);
            $goal  = $start - $lines;
            for ($line = $start - 1; ($line > $goal && $line > 0); $line--) {
                if ($filearray[$line] !== '') {
                    $theData .= $filearray[$line] . '<br/>';
                }
            }
        }
        else {
            foreach ((array)$filearray as $line) {
                if ($line !== '') {
                    $theData .= $line . '<br/>';
                }
            }
        }

        echo $theData;
        break;

    /**
     * Empty the logfile.
     */
    case 'clearLogfile':
        if ($kga['delete_logfile']) {
            $logdatei = fopen(WEBROOT . 'temporary/logfile.txt', 'w');
            fwrite($logdatei, '');
            fclose($logdatei);
            echo $kga['dict']['log_delete'];
        }
        else {
            die();
        }
        break;

    /**
     * Write some message to the logfile.
     */
    case 'shoutbox':
        Logger::logfile('text: ' . $axValue);
        break;

    /**
     * Return the $kga variable (Kimaii Global Array). Strip out some sensitive
     * information if not configured otherwise.
     */
    case 'reloadKGA':
        // read kga ---------------------------------------
        $output = $kga;
        // clean out some data that is way too private to be shown in the frontend ...

        if (DEMO_MODE
            || !$kga['conf']['show_sensible_data']
        ) {
            $output['server_hostname'] = 'xxx';
            $output['server_database'] = 'xxx';
            $output['server_username'] = 'xxx';
            $output['server_password'] = 'xxx';
            $output['user']['secure']  = 'xxx';
            $output['user']['user_id'] = 'xxx';
            $output['user']['pw']      = 'xxx';
            $output['password_salt']   = 'xxxxxxxxxxxxx';
            $output['admin_mail']      = 'xxxxxxxxxxxxx';
        }
        echo '<pre>';
        print_r($output);
        echo '</pre>';
        // /read kga --------------------------------------
        break;
}

