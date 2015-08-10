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
global $stime;

// ==================================
// = implementing standard includes =
// ==================================
global $kga, $database, $view;
include('../../includes/basics.php');

$dir_templates = 'templates/';
$datasrc       = 'config.ini';
$settings      = parse_ini_file($datasrc);
$dir_ext       = $settings['EXTENSION_DIR'];

checkUser();

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


// Get the total time displayed in the table.
if (is_customer()) {
    $total = Format::formatDuration($database->get_duration($in, $out, null, array($kga['who']['id']),
                                                            null));
}
else {
    $total = Format::formatDuration($database->get_duration($in, $out, array($kga['who']['id']), null, null));
}
$view->total = $total;


// Get the array of timesheet entries.
if (is_customer()) {

    //DEBUG// error_log('<<===== timesheet - customer =====>>');

    $timesheet_entries          = $database->get_timesheet($in, $out, null, array($kga['who']['id']),
                                                           null, 1);
    $view->latest_running_entry = null;
}
else {

    //DEBUG// error_log('<<===== timesheet - user =====>>');

    $timesheet_entries          = $database->get_timesheet($in, $out, array($kga['who']['id']), null, null, 1);
    $view->latest_running_entry = $database->get_latest_running_entry();
}

if (count($timesheet_entries) > 0) {
    $view->timeSheetEntries = $timesheet_entries;
}
else {
    $view->timeSheetEntries = 0;
}


// Get the annotations for the user sub list.
if (is_customer()) {
    $ann = $database->get_time_users($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = $database->get_time_users($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->user_annotations = $ann;


// Get the annotations for the customer sub list.
if (is_customer()) {
    $ann = $database->get_time_customers($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = $database->get_time_customers($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->customer_annotations = $ann;


// Get the annotations for the project sub list.
if (is_customer()) {
    $ann = $database->get_time_projects($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = $database->get_time_projects($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->project_annotations = $ann;


// Get the annotations for the activity sub list.
if (is_customer()) {
    $ann = $database->get_time_activities($in, $out, null, array($kga['who']['id']));
}
else {
    $ann = $database->get_time_activities($in, $out, array($kga['who']['id']));
}
Format::formatAnnotations($ann);
$view->activity_annotations = $ann;


$view->hideComments     = true;
$view->showOverlapLines = false;
$view->show_ref_code    = false;


if (isset($kga['pref'])) {
    $view->hideComments     = $kga['pref']['show_comments_by_default'] !== '1';
    $view->showOverlapLines = $kga['pref']['hide_overlap_lines'] !== '1';
    $view->show_ref_code    = $kga['pref']['show_ref_code'] !== '0';
}

$view->showRates         = is_user() && $database->gRole_allows($kga['who']['global_role_id'], 'ki_timesheet__show_rates');
$view->timeSheet_display = $view->render('timesheet.php');
$view->buzzerAction      = 'startRecord()';

// select for projects
if (is_customer()) {
    $view->projects = array();
}
else {
    $sel            = makeSelectBox('project', $kga['who']['groups']);
    $view->projects = $sel;
}

// select for activities
if (is_customer()) {
    $view->activities = array();
}
else {
    $sel              = makeSelectBox('activity', $kga['who']['groups']);
    $view->activities = $sel;
}

echo $view->render('main.php');
