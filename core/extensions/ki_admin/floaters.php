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

// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates';
global $database, $kga, $view;

global $axAction, $axValue, $id, $timeframe, $in, $out;
require('../../includes/kspi.php');

$datasrc  = 'config.ini';
$settings = parse_ini_file($datasrc);
$dir_ext  = $settings['EXTENSION_DIR'];
$view->addHelperPath(WEBROOT . 'extensions/' . $dir_ext . '/templates/helpers', 'Zend_View_Helper');

switch ($axAction) {

    case 'editUser':
        // =============================
        // = Builds edit-user dialogue =
        // =============================

        $userDetails = $database->user_get_data($id);

        $userDetails['rate'] = $database->get_rate($userDetails['user_id'], null, null);

        $view->globalRoles = array();
        foreach ($database->global_roles() as $role) {
            $view->globalRoles[$role['global_role_id']] = $role['name'];
        }

        $view->memberships = array();
        foreach ($database->user_get_group_ids($id, false) as $groupId) {
            $view->memberships[$groupId] = $database->user_get_mRole_id($id, $groupId);
        }

        $groups = $database->groups_get();
        if ($database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view')) {
            $view->groups = $groups;
        }
        else {
            $view->groups = array_filter($groups, function ($group) {
                global $kga;

                return in_array($group['group_id'], $kga['who']['groups'], true);
            });
        }

        $view->membershipRoles = array();
        foreach ($database->membership_roles() as $role)
            $view->membershipRoles[$role['membership_role_id']] = $role['name'];

        $view->user_details = $userDetails;
        echo $view->render('floaters/edituser.php');

        break;

    case 'editGroup':
        // =============================
        // = Builds edit-group dialogue =
        // =============================

        $groupDetails = $database->group_get_data($_REQUEST['id']);

        $view->users = makeSelectBox('sameGroupUser', null, null, true);

        $view->group_details = $groupDetails;
        echo $view->render('floaters/editgroup.php');

        break;

    case 'editStatus':
        // =============================
        // = Builds edit-status dialogue =
        // =============================

        $statusDetails = $database->status_get_data($_REQUEST['id']);

        $view->status_details = $statusDetails;
        echo $view->render('floaters/editstatus.php');

        break;

    case 'editGlobalRole':
        // =============================
        // = Builds edit-group dialogue =
        // =============================

        $globalRoleDetails = $database->globalRole_get_data($_REQUEST['id']);

        $view->id           = $globalRoleDetails['global_role_id'];
        $view->name         = $globalRoleDetails['name'];
        $view->action       = 'editGlobalRole';
        $view->reloadSubtab = 'globalRoles';
        $view->title        = $kga['dict']['editGlobalRole'] . ':&nbsp;&nbsp;' . $globalRoleDetails['name'];
        $view->permissions  = $globalRoleDetails;
        unset($view->permissions['global_role_id'], $view->permissions['name']);
        echo $view->render('floaters/editglobalrole.php');

        break;

    case 'editMembershipRole':
        // =============================
        // = Builds edit-group dialogue =
        // =============================

        $membershipRoleDetails = $database->membershipRole_get_data($_REQUEST['id']);

        $view->id           = $membershipRoleDetails['membership_role_id'];
        $view->name         = $membershipRoleDetails['name'];
        $view->action       = 'editMembershipRole';
        $view->reloadSubtab = 'membershipRoles';
        $view->title        = $kga['dict']['editMembershipRole'] . ':&nbsp;&nbsp;' . $membershipRoleDetails['name'];
        $view->permissions  = $membershipRoleDetails;

        unset($view->permissions['membership_role_id'], $view->permissions['name']);

        echo $view->render('floaters/editglobalrole.php');

        break;

}

