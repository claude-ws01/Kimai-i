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
 * Query the Kimaii project server for information about a new version.
 * The response will simply be passed through.
 */
error_reporting(-1);
require('../includes/basics.php');
global $kga;

header('Content-Type: text/html; charset=utf-8');

$check  = new Kimai_Update_Check();
$result = $check->checkForUpdate($kga['core.version'], $kga['core.revision']);

if ($result == Kimai_Update_Check::RELEASE) {
    echo $kga['dict']['updatecheck']['release'];
}
elseif ($result == Kimai_Update_Check::BETA) {
    echo $kga['dict']['updatecheck']['beta'];
}
elseif ($result == Kimai_Update_Check::CURRENT) {
    echo $kga['dict']['updatecheck']['current'];
}
