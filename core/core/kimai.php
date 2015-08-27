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

// =============================
// = Smarty (initialize class) =
// =============================

global $database, $kga, $view, $extensions;
include('../includes/basics.php');

$view = new Zend_View();
$view->setBasePath(WEBROOT . '/templates');

// prevent IE from caching the response
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// ==================================
// = implementing standard includes =
// ==================================

checkUser();

// Jedes neue update schreibt seine Versionsnummer in die Datenbank.
// Beim nächsten Update kommt dann in der Datei /includes/var.php die neue V-Nr. mit.
// der updater.php weiss dann welche Aenderungen an der Datenbank vorgenommen werden muessen. 
checkDBversion();

$extensions = new Extensions(WEBROOT . '/extensions/');
$extensions->loadConfigurations();

// ============================================
// = initialize currently displayed timeframe =
// ============================================
$timeframe = get_timeframe();
$in        = $timeframe[0];
$out       = $timeframe[1];


// ===============================================
// = get time for the probably running stopwatch =
// ===============================================
$current_timer = array();
if (is_customer()) {
    $current_timer['all']  = 0;
    $current_timer['hour'] = 0;
    $current_timer['min']  = 0;
    $current_timer['sec']  = 0;
}
else {
    $current_timer = $database->get_current_timer();
}

// =======================================
// = Display date and time in the header =
// =======================================
$wd = $kga['dict']['weekdays_short'][date('w', time())];

$dp_start = 0;
if ($kga['calender_start'] !== '') {
    $dp_start = $kga['calender_start'];
}
else {
    if (is_user()) {
        $dp_start = date('d/m/Y', $database->getjointime($kga['who']['id']));
    }
}


$dp_today = date('d/m/Y', time());

$view->dp_start = $dp_start;
$view->dp_today = $dp_today;

if (is_customer()) {
    $view->total = Format::formatDuration($database->get_duration($in, $out, null, array($kga['who']['id'])));
}
else {
    $view->total = Format::formatDuration($database->get_duration($in, $out, $kga['who']['id']));
}

// ===========================
// = DatePicker localization =
// ===========================
$localized_DatePicker = '';

$view->weekdays_array = sprintf("['%s','%s','%s','%s','%s','%s','%s']\n"
    , $kga['dict']['weekdays'][0], $kga['dict']['weekdays'][1], $kga['dict']['weekdays'][2], $kga['dict']['weekdays'][3], $kga['dict']['weekdays'][4], $kga['dict']['weekdays'][5], $kga['dict']['weekdays'][6]);

$view->weekdays_short_array = sprintf("['%s','%s','%s','%s','%s','%s','%s']\n"
    , $kga['dict']['weekdays_short'][0], $kga['dict']['weekdays_short'][1], $kga['dict']['weekdays_short'][2], $kga['dict']['weekdays_short'][3], $kga['dict']['weekdays_short'][4], $kga['dict']['weekdays_short'][5], $kga['dict']['weekdays_short'][6]);

$view->months_array = sprintf("['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']\n",
                              $kga['dict']['months'][0], $kga['dict']['months'][1], $kga['dict']['months'][2], $kga['dict']['months'][3], $kga['dict']['months'][4], $kga['dict']['months'][5], $kga['dict']['months'][6], $kga['dict']['months'][7], $kga['dict']['months'][8], $kga['dict']['months'][9], $kga['dict']['months'][10], $kga['dict']['months'][11]);

$view->months_short_array = sprintf('[\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\']', $kga['dict']['months_short'][0], $kga['dict']['months_short'][1], $kga['dict']['months_short'][2], $kga['dict']['months_short'][3], $kga['dict']['months_short'][4], $kga['dict']['months_short'][5], $kga['dict']['months_short'][6], $kga['dict']['months_short'][7], $kga['dict']['months_short'][8], $kga['dict']['months_short'][9], $kga['dict']['months_short'][10], $kga['dict']['months_short'][11]);


// ==============================
// = assign smarty placeholders =
// ==============================
$view->current_timer_hour  = $current_timer['hour'];
$view->current_timer_min   = $current_timer['min'];
$view->current_timer_sec   = $current_timer['sec'];
$view->current_timer_start = $current_timer['all'] ?: time();
$view->current_time        = time();

$view->timeframe_in  = $in;
$view->timeframe_out = $out;

$view->kga = $kga;

$view->extensions          = $extensions->extensionsTabData();
$view->css_extension_files = $extensions->cssExtensionFiles();
$view->js_extension_files  = $extensions->jsExtensionFiles();

$view->current_recording = -1;

if (is_user()) {
    $currentRecordings = $database->get_current_recordings($kga['who']['id']);
    if (count($currentRecordings) > 0) {
        $view->current_recording = $currentRecordings[0];
    }
}

$view->open_after_recorded = isset($kga['conf']['open_after_recorded']) && $kga['conf']['open_after_recorded'];

$customerData = array('customer_id' => false, 'name' => '');
$projectData  = array('project_id' => false, 'name' => '');
$activityData = array('activity_id' => false, 'name' => '');

if (is_user()) {
    //$lastTimeSheetRecord = $database->timesheet_get_data(false);
    $lastProject  = $database->project_get_data($kga['user']['last_project']);
    $lastActivity = $database->activity_get_data($kga['user']['last_activity']);
    if (!$lastProject['trash']) {
        $projectData  = $lastProject;
        $customerData = $database->customer_get_data($lastProject['customer_id']);
    }
    if (!$lastActivity['trash']) {
        $activityData = $lastActivity;
    }
}
$view->customerData = $customerData;
$view->projectData  = $projectData;
$view->activityData = $activityData;

// =========================================
// = INCLUDE EXTENSION PHP FILE            =
// =========================================
foreach ($extensions->phpIncludeFiles() as $includeFile) {
    require_once($includeFile);
}

// =======================
// = display user table =
// =======================
if (is_customer()) {
    $view->users = $database->customer_watchable_users($kga['customer']);
}
else {
    $view->users = $database->user_watchable_users($kga['user']);
}
$view->user_display = $view->render('filter/users.php');

// ==========================
// = display customer table =
// ========================
if (is_customer()) {
    $view->customers = array(array(
        'customer_id' => $kga['who']['id'],
        'name'        => $kga['who']['name'],
        'visible'     => $kga['customer']['visible']));
}
elseif ($kga['is_user_root']) {
    $view->customers = $database->customers_get();
}
else {
    $view->customers = $database->customers_get($kga['who']['groups']);
}

$view->show_customer_add_button  = is_user() && $database->user_object_action__allowed('customer', 'add');
$view->show_customer_edit_button = is_user() && $database->user_object_action__allowed('customer', 'edit');

$view->customer_display = $view->render('filter/customers.php');

// =========================
// = display project table =
// =========================
if (is_customer()) {
    $view->projects = $database->get_projects_by_customer($kga['who']['id']);
}
elseif ($kga['is_user_root']) {
    $view->projects = $database->get_projects($kga['who']['groups']);
}
else {
    $view->projects = $database->get_projects($kga['who']['groups']);
}

$view->show_project_add_button  = is_user() && $database->user_object_action__allowed('project', 'add');
$view->show_project_edit_button = is_user() && $database->user_object_action__allowed('project', 'edit');

$view->project_display = $view->render('filter/projects.php');

// ========================
// = display activity table =
// ========================
if (is_customer()) {
    $view->activities = $database->get_activities_by_customer($kga['who']['id']);
}
elseif ($kga['is_user_root']) {
    $view->activities = $database->get_activities();
}
elseif ($projectData['project_id']) {
    $view->activities = $database->get_activities_by_project($projectData['project_id'], $kga['who']['groups']);
}
else {
    $view->activities = $database->get_activities($kga['who']['groups']);
}

$view->show_activity_add_button  = is_user() && $database->user_object_action__allowed('activity', 'add');
$view->show_activity_edit_button = is_user() && $database->user_object_action__allowed('activity', 'edit');

$view->activity_display = $view->render('filter/activities.php');

$view->showInstallWarning = false;
if (!IN_DEV && is_user()) {
        $view->showInstallWarning = file_exists(WEBROOT . 'installer/');
}


// ========================
// = BUILD HOOK FUNCTIONS =
// ========================


$view->hook_timeframe_changed  = $extensions->timeframeChangedHooks();
$view->hook_buzzer_record      = $extensions->buzzerRecordHooks();
$view->hook_buzzer_stopped     = $extensions->buzzerStopHooks();
$view->hook_users_changed      = $extensions->usersChangedHooks();
$view->hook_customers_changed  = $extensions->customersChangedHooks();
$view->hook_projects_changed   = $extensions->projectsChangedHooks();
$view->hook_activities_changed = $extensions->activitiesChangedHooks();
$view->hook_filter             = $extensions->filterHooks();
$view->hook_resize             = $extensions->resizeHooks();
$view->timeoutlist             = $extensions->timeoutList();

echo $view->render('core/main.php');

