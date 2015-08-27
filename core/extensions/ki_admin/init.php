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

// Include Basics
include('../../includes/basics.php');
global $database, $kga, $view;

$dir_templates = 'templates/';
$datasrc       = 'config.ini';
$settings      = parse_ini_file($datasrc);
$dir_ext       = $settings['EXTENSION_DIR'];

checkUser();
require 'functions.php';
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


prep_customer_list_render();
$view->customer_display = $view->render('customers.php');


prep_project_list_render();
$view->project_display = $view->render('projects.php');


prep_activity_list_render();
$view->activity_display = $view->render('activities.php');


prep_user_list_render();
$admin['users'] = $view->render('users.php');


prep_group_list_render();
$admin['groups'] = $view->render('groups.php');


prep_global_list_render();
$view->globalRoles_display = $view->render('globalRoles.php');


prep_membership_list_render();
$view->membershipRoles_display = $view->render('membershipRoles.php');


prep_status_list_render();
$admin['status'] = $view->render('status.php');


$view->showAdvancedTab = $database->gRole_allows($kga['who']['global_role_id'], 'ki_admin__edit_advanced');
if ($view->showAdvancedTab) {
    prep_advanced_render();
    $admin['advanced'] = $view->render('advanced.php');
    $admin['database'] = $view->render('database.php');
}


$view->admin = $admin;

prep__subtabs_render();
echo $view->render('main.php');
