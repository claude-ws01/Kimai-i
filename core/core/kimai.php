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
if (array_key_exists('customer', $kga)) {
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
$wd = $kga['lang']['weekdays_short'][date('w', time())];

$dp_start = 0;
if ($kga['calender_start'] != '') {
    $dp_start = $kga['calender_start'];
}
else {
    if (array_key_exists('user', $kga)) {
        $dp_start = date('d/m/Y', $database->getjointime($kga['user']['user_id']));
    }
}


$dp_today = date('d/m/Y', time());

$view->dp_start = $dp_start;
$view->dp_today = $dp_today;

if (array_key_exists('customer', $kga)) {
    $view->total = Format::formatDuration($database->get_duration($in, $out, null, array($kga['customer']['customer_id'])));
}
else {
    $view->total = Format::formatDuration($database->get_duration($in, $out, $kga['user']['user_id']));
}

// ===========================
// = DatePicker localization =
// ===========================
$localized_DatePicker = '';

$view->weekdays_array = sprintf("['%s','%s','%s','%s','%s','%s','%s']\n"
    , $kga['lang']['weekdays'][0], $kga['lang']['weekdays'][1], $kga['lang']['weekdays'][2], $kga['lang']['weekdays'][3], $kga['lang']['weekdays'][4], $kga['lang']['weekdays'][5], $kga['lang']['weekdays'][6]);

$view->weekdays_short_array = sprintf("['%s','%s','%s','%s','%s','%s','%s']\n"
    , $kga['lang']['weekdays_short'][0], $kga['lang']['weekdays_short'][1], $kga['lang']['weekdays_short'][2], $kga['lang']['weekdays_short'][3], $kga['lang']['weekdays_short'][4], $kga['lang']['weekdays_short'][5], $kga['lang']['weekdays_short'][6]);

$view->months_array = sprintf("['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']\n",
                              $kga['lang']['months'][0], $kga['lang']['months'][1], $kga['lang']['months'][2], $kga['lang']['months'][3], $kga['lang']['months'][4], $kga['lang']['months'][5], $kga['lang']['months'][6], $kga['lang']['months'][7], $kga['lang']['months'][8], $kga['lang']['months'][9], $kga['lang']['months'][10], $kga['lang']['months'][11]);

$view->months_short_array = sprintf('[\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\']', $kga['lang']['months_short'][0], $kga['lang']['months_short'][1], $kga['lang']['months_short'][2], $kga['lang']['months_short'][3], $kga['lang']['months_short'][4], $kga['lang']['months_short'][5], $kga['lang']['months_short'][6], $kga['lang']['months_short'][7], $kga['lang']['months_short'][8], $kga['lang']['months_short'][9], $kga['lang']['months_short'][10], $kga['lang']['months_short'][11]);


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

if (array_key_exists('user', $kga)) {
    $currentRecordings = $database->get_current_recordings($kga['user']['user_id']);
    if (count($currentRecordings) > 0) {
        $view->current_recording = $currentRecordings[0];
    }
}

$view->open_after_recorded = isset($kga['conf']['open_after_recorded']) && $kga['conf']['open_after_recorded'];

$customerData = array('customer_id' => false, 'name' => '');
$projectData  = array('project_id' => false, 'name' => '');
$activityData = array('activity_id' => false, 'name' => '');

if (array_key_exists('user', $kga)) {
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
if (array_key_exists('customer', $kga)) {
    $view->users = $database->get_customer_watchable_users($kga['customer']);
}
else {
    $view->users = $database->get_user_watchable_users($kga['user']);
}
$view->user_display = $view->render('filter/users.php');

// ==========================
// = display customer table =
// ========================
if (array_key_exists('customer', $kga)) {
    $view->customers = array(array(
        'customer_id' => $kga['customer']['customer_id'],
        'name'        => $kga['customer']['name'],
        'visible'     => $kga['customer']['visible']));
}
elseif ($kga['is_user_root']) {
    $view->customers = $database->get_customers();
}
else {
    $view->customers = $database->get_customers(any_get_group_ids());
}

$view->show_customer_add_button  = array_key_exists('user', $kga) && coreObjectActionAllowed('customer', 'add');
$view->show_customer_edit_button = array_key_exists('user', $kga) && coreObjectActionAllowed('customer', 'edit');

$view->customer_display = $view->render('filter/customers.php');

// =========================
// = display project table =
// =========================
if (array_key_exists('customer', $kga)) {
    $view->projects = $database->get_projects_by_customer($kga['customer']['customer_id']);
}
elseif ($kga['is_user_root']) {
    $view->projects = $database->get_projects(any_get_group_ids());
}
else {
    $view->projects = $database->get_projects(any_get_group_ids());
}

$view->show_project_add_button  = array_key_exists('user', $kga) && coreObjectActionAllowed('project', 'add');
$view->show_project_edit_button = array_key_exists('user', $kga) && coreObjectActionAllowed('project', 'edit');

$view->project_display = $view->render('filter/projects.php');

// ========================
// = display activity table =
// ========================
if (array_key_exists('customer', $kga)) {
    $view->activities = $database->get_activities_by_customer($kga['customer']['customer_id']);
}
elseif ($kga['is_user_root']) {
    $view->activities = $database->get_activities();
}
elseif ($projectData['project_id']) {
    $view->activities = $database->get_activities_by_project($projectData['project_id'], any_get_group_ids());
}
else {
    $view->activities = $database->get_activities(any_get_group_ids());
}

$view->show_activity_add_button  = array_key_exists('user', $kga) && coreObjectActionAllowed('activity', 'add');
$view->show_activity_edit_button = array_key_exists('user', $kga) && coreObjectActionAllowed('activity', 'edit');

$view->activity_display = $view->render('filter/activities.php');

if (array_key_exists('user', $kga)&& !array_key_exists('customer', $kga)) {
    if (!IN_DEV) {
        $view->showInstallWarning = file_exists(WEBROOT . 'installer/');
    }
}
else {
    $view->showInstallWarning = false;
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

