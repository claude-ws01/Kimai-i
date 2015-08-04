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
define('WEBROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

if (file_exists(WEBROOT . 'includes/autoconf.php')) {
    //CN safety from re-installing
    header("location:http://${_SERVER['SERVER_NAME']}/index.php");
    exit;
}

$installsteps = 8;
$kga = array();
require('../includes/version.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="styles.css" type="text/css" media="screen" title="no title" charset="utf-8">
    <script src="../libraries/jQuery/jquery-1.9.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="installscript.min.js" type="text/javascript" charset="utf-8"></script>
    <title>Kimai-i Installation</title>

    <script type="text/javascript" charset="utf-8">
        var step = 1;
        var back = "";
        var new_database = -1;

        var hostname = "";
        var database = "";
        var username = "";
        var password = "";
        var prefix = "";
        var language = "";
        var timezone = "";
    </script>
</head>

<body>
<div id="wrapper" class="invisible">
    <div id="header">
        <div id="progressbar">
            <?php
            for ($i = 0; $i < $installsteps; $i++) {
                echo "<span class=\"step_nope\">&nbsp;</span>";
            }
            $width = $i * 15;
            echo "<script type=\"text/javascript\" charset=\"utf-8\">
                $('#progressbar').css('width','${width}px');
            </script>";
            ?>
        </div>
        <h1>Installation <?php echo 'v' . $GLOBALS['kga']['core.version'] . '.' . $GLOBALS['kga']['core.revision'] ?></h1>
    </div>
    <div id="body">

        <div id="jswarn">
            JavaScript MUST be activated!<br/>
            JavaScript MUSS aktiviert sein!
        </div>

        <div class="invisible" id="installsteps">
            <?php include 'steps/10_language.php'; ?>
        </div>
    </div>
    <div id="footer" class="invisible"></div>
</div>
</body>
</html>
