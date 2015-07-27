<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) 2006-2009 Kimai-Development-Team
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

// Include Basics
include('../../includes/basics.php');
global $database, $kga, $view;

$dir_templates = "templates/";
$datasrc       = "config.ini";
$settings      = parse_ini_file($datasrc);
$dir_ext       = $settings['EXTENSION_DIR'];

//PREV// $kga['user'] = checkUser();
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

$viewOtherGroupsAllowed = $database->global_role_allows(any_get_global_role_id(), 'core__group__other_group__view');


// ==========================
// = display customer table =
// ==========================
if ($database->global_role_allows(any_get_global_role_id(), 'core__customer__other_group__view')) {
    $customers = $database->get_customers();
}
else {
    $customers = $database->get_customers(any_get_group_ids());
}

foreach ($customers as $row => $data) {
    $groupNames = array();
    $groups     = $database->customer_get_group_ids($data['customer_id']);
    if (is_array($groups)) {
        foreach ($groups as $groupID) {
            if (!$viewOtherGroupsAllowed && array_search($groupID, any_get_group_ids()) === false) {
                continue;
            }
            $data         = $database->group_get_data($groupID);
            $groupNames[] = $data['name'];
        }
        $customers[$row]['groups'] = implode(", ", $groupNames);
    }
}

$view->customers        = $customers;
$view->customer_display = $view->render("customers.php");


// =========================
// = display project table =
// =========================
if ($database->global_role_allows(any_get_global_role_id(), 'core__project__other_group__view')) {
    $projects = $database->get_projects();
}
else {
    $projects = $database->get_projects(any_get_group_ids());
}

$view->projects = array();
if (is_array($projects)) {
    foreach ($projects as $row => $project) {
        $groupNames = array();

        $groupIDs = $database->project_get_groupIDs($project['project_id']);

        if (is_array($groupIDs)) {
            foreach ($groupIDs as $groupID) {
                if (!$viewOtherGroupsAllowed && array_search($groupID, any_get_group_ids()) === false) {
                    continue;
                }
                $data         = $database->group_get_data($groupID);
                $groupNames[] = $data['name'];
            }
        }
        $projects[$row]['groups'] = implode(", ", $groupNames);
    }
    $view->projects = $projects;
}
$view->project_display = $view->render("projects.php");


// ========================
// = display activity table =
// ========================
if ($database->global_role_allows(any_get_global_role_id(), 'core__activity__other_group__view')) {
    $activities = $database->get_activities_by_project(-2);
}
else {
    $activities = $database->get_activities_by_project(-2, any_get_group_ids());
}

foreach ($activities as $row => $activity) {
    $groupNames = array();

    $groupIDs = $database->activity_get_groups($activity['activity_id']);

    if (is_array($groupIDs)) {
        foreach ($groupIDs as $groupID) {
            if (!$viewOtherGroupsAllowed && array_search($groupID, any_get_group_ids()) === false) {
                continue;
            }
            $data         = $database->group_get_data($groupID);
            $groupNames[] = $data['name'];
        }
    }
    $activities[$row]['groups'] = implode(", ", $groupNames);
}

$view->activities               = $activities;
$view->activity_display         = $view->render("activities.php");
$view->selected_activity_filter = -2;
$view->curr_user                = $kga['user']['name'];


$groups = $database->get_groups(get_cookie('adm_ext_show_deleted_groups', 0));
if ($database->global_role_allows(any_get_global_role_id(), 'core__group__other_group__view')) {
    $view->groups = $groups;
}
else {
    $view->groups = array_filter($groups, function ($group) {
        global $kga;

        return array_search($group['group_id'], any_get_group_ids()) !== false;
    });
}


$view->arr_statuses = $database->get_statuses();

if ($database->global_role_allows(any_get_global_role_id(), 'core__user__other_group__view')) {
    $users = $database->get_users(get_cookie('adm_ext_show_deleted_users', 0));
}
else {
    $users = $database->get_users(get_cookie('adm_ext_show_deleted_users', 0), any_get_group_ids());
}


// get group names
foreach ($users as &$user) {
    $user['groups'] = array();
    $groups         = $database->user_get_group_ids($user['user_id']);
    if (is_array($groups)) {
        foreach ($groups as $group) {
            if (!$viewOtherGroupsAllowed && array_search($group, any_get_group_ids()) === false) {
                continue;
            }
            $groupData        = $database->group_get_data($group);
            $user['groups'][] = $groupData['name'];
        }
    }
}

$view->users = $users;

// ==============================
// = display global roles table =
// ==============================
$view->globalRoles         = $database->global_roles();
$view->globalRoles_display = $view->render("globalRoles.php");


// ==================================
// = display membership roles table =
// ==================================
$view->membershipRoles         = $database->membership_roles();
$view->membershipRoles_display = $view->render("membershipRoles.php");


$view->showDeletedGroups = get_cookie('adm_ext_show_deleted_groups', 0);
$view->showDeletedUsers  = get_cookie('adm_ext_show_deleted_users', 0);
$view->languages         = Translations::langs();

$view->timezones  = timezoneList();
$status           = $database->get_statuses();
$view->arr_status = $status;

$admin['users']  = $view->render("users.php");
$admin['groups'] = $view->render("groups.php");
$admin['status'] = $view->render("status.php");


if ($kga['conf']['edit_limit'] != '-') {
    $view->edit_limit_enabled = true;
    $editLimit              = $kga['conf']['edit_limit'] / (60 * 60); // convert to hours
    $view->edit_limit_days    = (int)($editLimit / 24);
    $view->edit_limit_hours   = (int)($editLimit % 24);
}
else {
    $view->edit_limit_enabled = false;
    $view->edit_limit_days    = '';
    $view->edit_limit_hours   = '';
}
if (boolval($kga['conf']['round_timesheet_entries'])) {
    $view->round_timesheet_entries = true;
    $view->round_minutes          = $kga['conf']['round_minutes'];
    $view->round_seconds          = $kga['conf']['round_seconds'];
}
else {
    $view->round_timesheet_entries = false;
    $view->round_minutes          = '';
    $view->round_seconds          = '';
}

$view->showAdvancedTab = $database->global_role_allows(any_get_global_role_id(), 'admin_panel_extension__edit_advanced');
if ($view->showAdvancedTab) {
    $skins = array();
    $langs = array();

    $allSkins = glob(WEBROOT . "/skins/*", GLOB_ONLYDIR);
    foreach ($allSkins as $skin) {
        $name         = basename($skin);
        $skins[$name] = $name;
    }

    foreach (Translations::langs() as $lang) {
        $langs[$lang] = $lang;
    }

    $view->skins = $skins;
    $view->langs = $langs;

    $admin['advanced'] = $view->render("advanced.php");
    $admin['database'] = $view->render("database.php");
}

$view->admin = $admin;

echo $view->render('main.php');
