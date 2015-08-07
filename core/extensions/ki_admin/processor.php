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
// ================
// = AP PROCESSOR =
// ================
// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates/';
global $database, $kga, $view;
require('../../includes/kspi.php');


switch ($axAction) {

    case 'banUser' :
        // Ban a user from login
        $sts['active'] = 0;
        $database->user_edit($id, $sts);
        echo sprintf('<img border="0" title="%s" alt="%s" src="../grfx/lock.png" width="16" height="16" />',
                     $kga['dict']['banneduser'], $kga['dict']['banneduser']);
        break;

    case 'createUser' :
        // create new user account
        $userData['name']           = trim($axValue);
        $userData['global_role_id'] = any_get_global_role_id();
        $userData['active']         = 1;

        $groupsWithAddPermission = array();
        foreach ($kga['user']['groups'] as $group) {
            $membershipRoleID = $database->user_get_membership_role($kga['user']['user_id'], $group);
            if ($database->membership_role_allows($membershipRoleID, 'core__user__add')) {
                $groupsWithAddPermission[$group] = $membershipRoleID;
                break;
            }
        }

        // validate data
        $errors = array();
        if ($database->customer_nameToID($userData['name']) !== false) {
            $errors[] = $kga['dict']['errorMessages']['customerWithSameName'];
        }

        if (count($groupsWithAddPermission) === 0) {
            $errors[] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        $userId = false;
        if (count($errors) === 0) {
            $userId = $database->user_create($userData);
            $database->setGroupMemberships($userId, $groupsWithAddPermission);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors'  => $errors,
                             'user_id' => $userId));

        break;

    case 'createStatus' :
        $status_data['status'] = trim($axValue);

        // validate data
        $errors = array();

        if (array_key_exists('customer', $kga) || !$database->global_role_allows(any_get_global_role_id(), 'core__status__add')) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        // create new status
        $new_status_id = $database->status_create($status_data);

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors'   => $errors,
                             'statusId' => $new_status_id));
        break;

    case 'createGroup' :
        $group['name'] = trim($axValue);

        // validate data
        $errors = array();

        if (array_key_exists('customer', $kga) || !$database->global_role_allows(any_get_global_role_id(), 'core__group__add')) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        // create new group
        $newGroupID = $database->group_create($group);

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors'  => $errors,
                             'groupId' => $newGroupID));
        break;

    case 'createGlobalRole':
        $role_data['name'] = trim($axValue);

        $errors = array();

        if (array_key_exists('customer', $kga)) {
            $errors[] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        else {
            if ($database->globalRole_find($role_data)) {
                $errors[] = $kga['dict']['errorMessages']['sameGlobalRoleName'];
            }
        }

        if (count($errors) === 0) {
            // create new status
            $database->global_role_create($role_data);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'createMembershipRole':
        $role_data['name'] = trim($axValue);

        $errors = array();

        if (array_key_exists('customer', $kga)) {
            $errors[] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if ($database->membershipRole_find($role_data)) {
            $errors[] = $kga['dict']['errorMessages']['sameMembershipRoleName'];
        }

        if (count($errors) === 0) {
            // create new status
            $database->membership_role_create($role_data);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteActivity' :
        $errors    = array();
        $oldGroups = $database->activity_get_groupIDs($id);

        if (!checkGroupedObjectPermission('activity', 'delete', $oldGroups)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            // If the confirmation is returned the activity gets the trash-flag.
            $database->activity_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteGlobalRole':
        $errors = array();

        if (array_key_exists('customer', $kga)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            $database->global_role_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteMembershipRole':
        $errors = array();

        if (array_key_exists('customer', $kga)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            $database->membership_role_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteGroup' :
        $errors = array();

        if (!checkGroupedObjectPermission('group', 'delete', array($id))) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            // removes a group
            $database->group_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteProject' :
        $errors    = array();
        $oldGroups = $database->project_get_groupIDs($id);

        if (!checkGroupedObjectPermission('project', 'delete', $oldGroups)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            // If the confirmation is returned the project gets the trash-flag.
            $database->project_delete($id);
            break;
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteCustomer' :
        $errors    = array();
        $oldGroups = $database->customer_get_group_ids($id);

        if (!checkGroupedObjectPermission('project', 'delete', $oldGroups)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            // If the confirmation is returned the customer gets the trash-flag.
            $database->customer_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteUser':
        $oldGroups = $database->user_get_group_ids($id, false);
        $errors    = array();

        if (!checkGroupedObjectPermission('user', 'delete', $oldGroups)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            switch ($axValue) {
                case 1 :
                    // If the confirmation is returned the user gets the trash-flag.
                    $database->user_delete($id, true);
                    break;
                case 2 :
                    // User is finally deleted after confirmed through trash view
                    $database->user_delete($id, false);
                    break;
            }
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'deleteStatus' :
        $errors = array();
        if (array_key_exists('customer', $kga) || !$database->global_role_allows(any_get_global_role_id(), 'core__status__delete')) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            // If the confirmation is returned the status gets deleted.
            $database->status_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'editGlobalRole':
        $id      = $_REQUEST['id'];
        $newData = $_REQUEST;
        unset($newData['id'], $newData['axAction']);

        $roleData = $database->globalRole_get_data($id);

        foreach ($roleData as $key => &$value) {
            if (isset($newData[$key])) {
                $value = $newData[$key];
            }
            else {
                if ($key !== 'global_role_id' && $key !== 'name') {
                    $value = 0;
                }
            }
        }

        $errors = array();

        if (array_key_exists('customer', $kga)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            $database->global_role_edit($id, $roleData);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'editMembershipRole':
        $id      = $_REQUEST['id'];
        $newData = $_REQUEST;
        unset($newData['id'], $newData['axAction']);

        $roleData = $database->membershipRole_get_data($id);

        foreach ($roleData as $key => &$value) {
            if (isset($newData[$key])) {
                $value = $newData[$key];
            }
            else {
                if ($key !== 'membership_role_id' && $key !== 'name') {
                    $value = 0;
                }
            }
        }
        unset($value);

        $errors = array();

        if (array_key_exists('customer', $kga)) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            $database->membership_role_edit($id, $roleData);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'refreshSubtab' :
        // builds either user/group/advanced/DB subtab
        $view->curr_user        = $kga['user']['name'];
        $groups                 = $database->get_groups(get_cookie('adm_ext_show_deleted_groups', 0));
        $viewOtherGroupsAllowed = $database->global_role_allows(any_get_global_role_id(), 'core__group__other_group__view');
        if ($viewOtherGroupsAllowed) {
            $view->groups = $groups;
        }
        else {
            $view->groups = array_filter($groups, function ($group) {
                return in_array($group['group_id'], any_get_group_ids(), true) !== false;
            });
        }

        if ($database->global_role_allows(any_get_global_role_id(), 'core__user__other_group__view')) {
            $users = $database->get_users(get_cookie('adm_ext_show_deleted_users', 0));
        }
        else {
            $users = $database->get_users(get_cookie('adm_ext_show_deleted_users', 0), any_get_group_ids());
        }

        // get group names for user list
        foreach ($users as &$user) {
            $user['groups'] = array();

            $groups = $database->user_get_group_ids($user['user_id'], false);
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    if (!$viewOtherGroupsAllowed && in_array($group, any_get_group_ids(), true) === false) {
                        continue;
                    }
                    $groupData        = $database->group_get_data($group);
                    $user['groups'][] = $groupData['name'];
                }
            }
        }
        unset($user);

        $arr_status              = $database->get_statuses();
        $view->users             = $users;
        $view->arr_status        = $arr_status;
        $view->showDeletedGroups = get_cookie('adm_ext_show_deleted_groups', 0);
        $view->showDeletedUsers  = get_cookie('adm_ext_show_deleted_users', 0);

        switch ($axValue) {
            case 'users' :
                echo $view->render('users.php');
                break;

            case 'groups' :
                echo $view->render('groups.php');
                break;

            case 'status' :
                echo $view->render('status.php');
                break;

            case 'advanced' :
                if ($kga['conf']['edit_limit'] !== '-') {
                    $view->edit_limit_enabled = true;
                    $editLimit                = $kga['conf']['edit_limit'] / (60 * 60); // convert to hours
                    $view->edit_limit_days    = (int)($editLimit / 24);
                    $view->edit_limit_hours   = (int)($editLimit % 24);
                }
                else {
                    $view->edit_limit_enabled = false;
                    $view->edit_limit_days    = '';
                    $view->edit_limit_hours   = '';
                }

                $skins = array();
                $langs = array();

                $allSkins = glob(__DIR__ . '/../skins/*', GLOB_ONLYDIR);
                foreach ($allSkins as $skin) {
                    $name         = basename($skin);
                    $skins[$name] = $name;
                }

                foreach (Translations::langs() as $lang) {
                    $langs[$lang] = $lang;
                }

                $view->skins = $skins;
                $view->langs = $langs;

                echo $view->render('advanced.php');
                break;

            case 'database' :
                echo $view->render('database.php');
                break;

            case 'customers' :
                $viewOtherGroupsAllowed = $database->global_role_allows(any_get_global_role_id(), 'core__group__other_group__view');
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
                        $customers[$row]['groups'] = implode(', ', $groupNames);
                    }
                }
                $view->customers = '0';
                if (count($customers) > 0) {
                    $view->customers = $customers;
                }

                echo $view->render('customers.php');
                break;

            case 'projects' :
                $viewOtherGroupsAllowed = $database->global_role_allows(any_get_global_role_id(), 'core__group__other_group__view');
                if ($database->global_role_allows(any_get_global_role_id(), 'core__project__other_group__view')) {
                    $projects = $database->get_projects();
                }
                else {
                    $projects = $database->get_projects(any_get_group_ids());
                }

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

                        $projects[$row]['groups'] = implode(', ', $groupNames);
                    }

                    $view->projects = $projects;
                }

                echo $view->render('projects.php');
                break;

            case 'activities' :
                $viewOtherGroupsAllowed = $database->global_role_allows(any_get_global_role_id(), 'core__group__other_group__view');
                $groups                 = null;
                if (!$database->global_role_allows(any_get_global_role_id(), 'core__activity__other_group__view')) {
                    $groups = $kga['user']['groups'];
                }

                $activity_filter = isset($_REQUEST['activity_filter']) ? intval($_REQUEST['activity_filter']) : -2;

                switch ($activity_filter) {
                    case -2:
                        // -2 is to get unassigned activities. As -2 is never
                        // an id of a project this will give us all unassigned
                        // activities.
                        $activities = $database->get_activities_by_project(-2, $groups);
                        break;
                    case -1:
                        $activities = $database->get_activities($groups);
                        break;
                    default:
                        $activities = $database->get_activities_by_project($activity_filter, $groups);
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

                    $activities[$row]['groups'] = implode(', ', $groupNames);
                }

                if (count($activities) > 0) {
                    $view->activities = $activities;
                }
                else {
                    $view->activities = '0';
                }

                $projects                       = $database->get_projects($groups);
                $view->projects                 = $projects;
                $view->selected_activity_filter = isset($_REQUEST['activity_filter']) ? $_REQUEST['activity_filter']
                    : -2;
                echo $view->render('activities.php');
                break;

            case 'globalRoles':
                $view->globalRoles = $database->global_roles();
                echo $view->render('globalRoles.php');
                break;

            case 'membershipRoles':
                $view->membershipRoles = $database->membership_roles();
                echo $view->render('membershipRoles.php');
                break;
        }
        break;

    case 'sendEditUser' :

        // process editUser form
        $userData['name']           = trim($_REQUEST['name']);
        $userData['mail']           = $_REQUEST['mail'];
        $userData['alias']          = $_REQUEST['alias'];
        $userData['global_role_id'] = $_REQUEST['global_role_id'];
        $userData['rate']           = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['rate']);
        // if password field is empty => password unchanged (not overwritten with '')
        if ($_REQUEST['password'] !== '') {
            $userData['password'] = password_encrypt($_REQUEST['password']);
        }

        $oldGroups = $database->user_get_group_ids($id, false);

        // validate data
        $errorMessages = array();

        if ($database->customer_nameToID($userData['name']) !== false) {
            $errorMessages['name'] = $kga['dict']['errorMessages']['customerWithSameName'];
        }

        $assignedGroups  = isset($_REQUEST['assignedGroups']) ? $_REQUEST['assignedGroups'] : array();
        $membershipRoles = isset($_REQUEST['membershipRoles']) ? $_REQUEST['membershipRoles'] : array();


        if (!checkGroupedObjectPermission('user', 'edit', $oldGroups, $assignedGroups)) {
            $errorMessages[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errorMessages) === 0) {
            $database->user_edit($id, $userData);
            $groups = array_combine($assignedGroups, $membershipRoles);
            $database->setGroupMemberships($id, $groups);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errorMessages));
        break;

    case 'sendEditGroup' :
        // process editGroup form
        $group['name'] = trim($_REQUEST['name']);

        $errors = array();

        if (!checkGroupedObjectPermission('group', 'edit', array($id))) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            $database->group_edit($id, $group);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'sendEditStatus' :
        // process editStatus form
        $status_data['status'] = trim($_REQUEST['status']);

        $errors = array();

        if (array_key_exists('customer', $kga)
            || !$database->global_role_allows(any_get_global_role_id(), 'core__status__edit')
        ) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            $database->status_edit($id, $status_data);
            config_set('default_status_id', $id, 'int');
            $database->config_replace();
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    case 'sendEditAdvanced' :
        $errors = array();
        if (array_key_exists('customer', $kga)
            || !$database->global_role_allows(any_get_global_role_id(), 'ki_admin__edit_advanced')
        ) {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errors) === 0) {
            // process AdvancedOptions form
            // @formatter:off
            config_set('admin_mail',                isset($_REQUEST['admin_mail']               )? $_REQUEST['admin_mail']               : null );
            config_set('login_tries',               isset($_REQUEST['login_tries']              )? $_REQUEST['login_tries']              : null );
            config_set('login_ban_time',            isset($_REQUEST['login_ban_time']           )? $_REQUEST['login_ban_time']           : null );
            config_set('show_sensible_data',        isset($_REQUEST['show_sensible_data']       )? $_REQUEST['show_sensible_data']       : null ,true,'bool');
            config_set('show_update_warn',          isset($_REQUEST['show_update_warn']         )? $_REQUEST['show_update_warn']         : null ,true,'bool');
            config_set('check_at_startup',          isset($_REQUEST['check_at_startup']         )? $_REQUEST['check_at_startup']         : null ,true,'bool');
            config_set('check_at_startup',          isset($_REQUEST['check_at_startup']         )? $_REQUEST['check_at_startup']         : null ,true,'bool');
            config_set('show_day_separator_lines',  isset($_REQUEST['show_day_separator_lines'] )? $_REQUEST['show_day_separator_lines'] : null ,true,'bool');
            config_set('show_gab_breaks',           isset($_REQUEST['show_gab_breaks']          )? $_REQUEST['show_gab_breaks']          : null ,true,'bool');
            config_set('show_record_again',         isset($_REQUEST['show_record_again']        )? $_REQUEST['show_record_again']        : null ,true,'bool');
            config_set('ref_num_editable',          isset($_REQUEST['ref_num_editable']         )? $_REQUEST['ref_num_editable']         : null ,true,'bool');
            config_set('currency_name',             isset($_REQUEST['currency_name']            )? $_REQUEST['currency_name']            : null );
            config_set('currency_sign',             isset($_REQUEST['currency_sign']            )? $_REQUEST['currency_sign']            : null );
            config_set('currency_first',            isset($_REQUEST['currency_first']           )? $_REQUEST['currency_first']           : null ,true,'bool');
            config_set('date_format_0',             isset($_REQUEST['date_format_0']            )? $_REQUEST['date_format_0']            : null );
            config_set('date_format_1',             isset($_REQUEST['date_format_1']            )? $_REQUEST['date_format_1']            : null );
            config_set('date_format_2',             isset($_REQUEST['date_format_2']            )? $_REQUEST['date_format_2']            : null );
            config_set('round_precision',           isset($_REQUEST['round_precision']          )? $_REQUEST['round_precision']          : null );
            config_set('allow_round_down',          isset($_REQUEST['allow_round_down']         )? $_REQUEST['allow_round_down']         : null ,true,'bool');
            config_set('round_minutes',             isset($_REQUEST['round_minutes']            )? $_REQUEST['round_minutes']            : null ,true,'int');
            config_set('round_seconds',             isset($_REQUEST['round_seconds']            )? $_REQUEST['round_seconds']            : null ,true,'int');
            config_set('round_timesheet_entries',   isset($_REQUEST['round_timesheet_entries']  )? $_REQUEST['round_timesheet_entries']  : null ,true,'bool');
            config_set('decimal_separator',         isset($_REQUEST['decimal_separator']        )? $_REQUEST['decimal_separator']        : null ,true,'str');
            config_set('duration_with_seconds',     isset($_REQUEST['duration_with_seconds']    )? $_REQUEST['duration_with_seconds']    : null ,true,'bool');
            config_set('exact_sums',                isset($_REQUEST['exact_sums']               )? $_REQUEST['exact_sums']               : null ,true,'bool');
            config_set('vat_rate',                  isset($_REQUEST['vat_rate']                 )? $_REQUEST['vat_rate']                 : null ,true);
            // @formatter:on

            $editLimit = false;
            if (isset($_REQUEST['edit_limit_enabled'])) {
                $hours     = (int)$_REQUEST['edit_limit_hours'];
                $days      = (int)$_REQUEST['edit_limit_days'];
                $editLimit = $hours + $days * 24;
                $editLimit *= 60 * 60; // convert to seconds
            }
            if ($editLimit === false || $editLimit === 0) {
                config_set('edit_limit', '-');
            }
            else {
                config_set('edit_limit', $editLimit, false, 'int');
            }

            // NEW USER DEFAULTS //
            // @formatter:off
            config_set('ud.autoselection',              isset($_REQUEST['ud_autoselection']            ) ? $_REQUEST['ud_autoselection']            : null , true, 'bool');
            config_set('ud.flip_project_display',       isset($_REQUEST['ud_flip_project_display']     ) ? $_REQUEST['ud_flip_project_display']     : null , true, 'bool');
            config_set('ud.hide_cleared_entries',       isset($_REQUEST['ud_hide_cleared_entries']     ) ? $_REQUEST['ud_hide_cleared_entries']     : null , true, 'bool');
            config_set('ud.hide_overlap_lines',         isset($_REQUEST['ud_hide_overlap_lines']       ) ? $_REQUEST['ud_hide_overlap_lines']       : null , true, 'bool');
            config_set('ud.language',                   isset($_REQUEST['ud_language']                 ) ? $_REQUEST['ud_language']                 : null );
            config_set('ud.no_fading',                  isset($_REQUEST['ud_no_fading']                ) ? $_REQUEST['ud_no_fading']                : null , true, 'bool');
            config_set('ud.open_after_recorded',        isset($_REQUEST['ud_open_after_recorded']      ) ? $_REQUEST['ud_open_after_recorded']      : null , true, 'bool');
            config_set('ud.project_comment_flag',       isset($_REQUEST['ud_project_comment_flag']     ) ? $_REQUEST['ud_project_comment_flag']     : null , true, 'bool');
            config_set('ud.quickdelete',                isset($_REQUEST['ud_quickdelete']              ) ? $_REQUEST['ud_quickdelete']              : null , true, 'bool');
            config_set('ud.rowlimit',                   isset($_REQUEST['ud_rowlimit']                 ) ? $_REQUEST['ud_rowlimit']                 : null , false, 'int');
            config_set('ud.show_comments_by_default',   isset($_REQUEST['ud_show_comments_by_default'] ) ? $_REQUEST['ud_show_comments_by_default'] : null , true, 'bool');
            config_set('ud.show_ids',                   isset($_REQUEST['ud_show_ids']                 ) ? $_REQUEST['ud_show_ids']                 : null , true, 'bool');
            config_set('ud.show_ref_code',              isset($_REQUEST['ud_show_ref_code']            ) ? $_REQUEST['ud_show_ref_code']            : null , true, 'bool');
            config_set('ud.skin',                       isset($_REQUEST['ud_skin']                     ) ? $_REQUEST['ud_skin']                     : null );
            config_set('ud.sublist_annotations',        isset($_REQUEST['ud_sublist_annotations']      ) ? $_REQUEST['ud_sublist_annotations']      : null );
            config_set('ud.timezone',                   isset($_REQUEST['ud_timezone']                 ) ? $_REQUEST['ud_timezone']                 : null );
            config_set('ud.user_list_hidden',           isset($_REQUEST['ud_user_list_hidden']         ) ? $_REQUEST['ud_user_list_hidden']         : null , true, 'bool');
            // @formatter:on
            // 17 x user preferences - check.

            // save config //
            if (!$database->config_replace()) {
                $errors[''] = $kga['dict']['error'];
            }
        }

        if (count($errors) === 0) {
            write_config_file(
                $kga['server_hostname'],
                $kga['server_database'],
                $kga['server_username'],
                $kga['server_password'],
                $kga['password_salt'],
                $kga['server_prefix'],
                $kga['authenticator'],
                $kga['conf']['ud.language'],
                $kga['conf']['ud.timezone']
            );
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array('errors' => $errors));
        break;

    case 'toggleDeletedUsers' :
        cookie_set('adm_ext_show_deleted_users', $axValue);
        echo 'ok';
        break;

    case 'unbanUser' :
        // Unban a user from login
        $sts['active'] = 1;
        $database->user_edit($id, $sts);
        echo sprintf('<img border="0" title="%s" alt="%s" src="../grfx/jipp.gif" width="16" height="16" />',
                     $kga['dict']['activeAccount'], $kga['dict']['activeAccount']);
        break;
}
