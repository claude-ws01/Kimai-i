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

// ==================================
// = implementing standard includes =
// ==================================
global $kga, $database, $view;
include('../../includes/basics.php');

require('private_func.php');

checkUser();

$dir_templates = 'templates/';
$datasrc       = 'config.ini';
$settings      = parse_ini_file($datasrc);
$dir_ext       = $settings['EXTENSION_DIR'];

// ============================================
// = initialize currently displayed timeframe =
// ============================================
$timeframe = get_timeframe();
$in        = $timeframe[0];
$out       = $timeframe[1];

$view = new Zend_View();
$view->setBasePath(WEBROOT . 'extensions/' . $dir_ext . '/' . $dir_templates);
$view->addHelperPath(WEBROOT . '/templates/helpers', 'Zend_View_Helper');

$view->kga = $kga;

// prevent IE from caching the response
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$timeformat       = 'H:M';
$dateformat       = 'd.m.';
$view->timeformat = $timeformat;
$view->dateformat = $dateformat;

echo $view->render('panel.php');


$view->timeformat = preg_replace('/([A-Za-z])/', '%$1', $timeformat);
$view->dateformat = preg_replace('/([A-Za-z])/', '%$1', $dateformat);

// Get the total amount of time shown in the table.
if (is_customer()) {
    $total = Format::formatDuration($database->get_duration($in, $out, null, array($kga['who']['id']), null));
}
else {
    $total = Format::formatDuration($database->get_duration($in, $out, array($kga['who']['id']), null, null));
}

if (is_customer()) {
    $view->exportData = export_get_data($in, $out, null, array($kga['who']['id']));
}
else {
    $view->exportData = export_get_data($in, $out, array($kga['who']['id']));
}

$view->total = $total;


// Get the annotations for the user sub list.
if (is_customer()) {
    $ann = export_get_user_annotations($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = export_get_user_annotations($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->user_annotations = $ann;


// Get the annotations for the customer sub list.
if (is_customer()) {
    $ann = export_get_customer_annotations($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = export_get_customer_annotations($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->customer_annotations = $ann;


// Get the annotations for the project sub list.
if (is_customer()) {
    $ann = export_get_project_annotations($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = export_get_project_annotations($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->project_annotations = $ann;


// Get the annotations for the activity sub list.
if (is_customer()) {
    $ann = export_get_activity_annotations($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = export_get_activity_annotations($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->activity_annotations = $ann;


// Get the columns the user had disabled last time.
if (is_user()) {
    $view->disabled_columns = export_get_disabled_headers($kga['who']['id']);
}

$view->table_display = $view->render('table.php');

echo $view->render('main.php');

