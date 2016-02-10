<?php
/*  FUNCTIONS COMMON TO BOTH init & processor */
function prep_activity_list_render()
{
    global $database, $kga, $view;

    // select which activities I can see
    $groups = null;
    if (!$database->gRole_allows($kga['who']['global_role_id'], 'core__activity__other_group__view')) {
        $groups = $kga['who']['groups'];
    }

    // -2 is to get unassigned activities. As -2 is never an id of a project this will give us all unassigned activities.
    $activity_filter = isset($_REQUEST['activity_filter']) ? (int)($_REQUEST['activity_filter']) : -2;
    if ($activity_filter === -1) {
        $activities = $database->get_activities($groups);
    }
    else {
        $activities = $database->get_activities_by_project($activity_filter, $groups);
    }


    // select what groups I can see on the selected activities
    if (is_array($activities) && count($activities) > 0) {

        $viewOtherGroupsAllowed = $database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view');

        foreach ($activities as $row => &$activity) {
            $groupNames = array();

            $groupIDs = $database->activity_get_groups($activity['activity_id']);

            if (is_array($groupIDs)) {
                foreach ($groupIDs as $groupID) {
                    if (!$viewOtherGroupsAllowed && in_array($groupID, $kga['who']['groups'], false) === false) {
                        continue;
                    }
                    $data         = $database->group_get_data($groupID);
                    $groupNames[] = $data['name'];
                }
            }
            $activity['groups'] = implode(', ', $groupNames);
        }
        unset($activity);
    }

    //CN...not sure about the '0' instead of array()
    //$view->activities = '0';
    //if (count($activities) > 0) {
    //    $view->activities = $activities;
    //}

    $view->activities               = $activities;
    $view->projects                 = $database->get_projects($groups);
    $view->selected_activity_filter = $activity_filter;
}

function prep_advanced_render()
{
    global $kga, $view;


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

    if ((bool)($kga['conf']['round_timesheet_entries'])) {
        $view->round_timesheet_entries = true;
        $view->round_minutes           = $kga['conf']['round_minutes'];
        $view->round_seconds           = $kga['conf']['round_seconds'];
    }
    else {
        $view->round_timesheet_entries = false;
        $view->round_minutes           = '';
        $view->round_seconds           = '';
    }


    $skins = array();
    $langs = array();

    $allSkins = glob(WEBROOT . '/skins/*', GLOB_ONLYDIR);
    foreach ($allSkins as $skin) {
        $name         = basename($skin);
        $skins[$name] = $name;
    }

    foreach (Translations::langs() as $lang) {
        $langs[$lang] = $lang;
    }

    $view->skins = $skins;
    $view->langs = $langs;
}

function prep_customer_list_render()
{
    global $database, $kga, $view;


    // select which customer I can see
    if ($database->gRole_allows($kga['who']['global_role_id'], 'core__customer__other_group__view')) {
        $customers = $database->customers_get();
    }
    else {
        $customers = $database->customers_get($kga['who']['groups']);
    }

    // select what groups I can see on the selected project
    if (is_array($customers) && count($customers) > 0) {

        $viewOtherGroupsAllowed = $database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view');

        foreach ($customers as $row => &$cust) {
            $groupNames = array();
            $groups     = $database->customer_get_group_ids($cust['customer_id']);
            if (is_array($groups)) {
                foreach ($groups as $groupID) {
                    if (!$viewOtherGroupsAllowed && in_array($groupID, $kga['who']['groups'], false) === false) {
                        continue;
                    }
                    $grp          = $database->group_get_data($groupID);
                    $groupNames[] = $grp['name'];
                }
                $cust['groups'] = implode(', ', $groupNames);
            }
        }
        unset($cust);
    }

    $view->customers = $customers;

    //CN...?? why the = '0' and not an empty array ??
    //    $view->customers = '0';
    //    if (count($customers) > 0) {
    //        $view->customers = $customers;
    //    }
}

function prep_global_list_render()
{
    global $database, $view;

    $view->globalRoles = $database->global_roles();
}

function prep_group_list_render()
{
    global $database, $kga, $view;

    $groups                 = $database->groups_get();
    $viewOtherGroupsAllowed = $database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view');
    if ($viewOtherGroupsAllowed) {
        $view->groups = $groups;
    }
    else {
        $view->groups = array_filter($groups,
            function ($group) {
                global $kga;

                return in_array($group['group_id'], $kga['who']['groups'], true) !== false;
            });
    }
}

function prep_membership_list_render()
{
    global $database, $view;

    $view->membershipRoles = $database->membership_roles();
}

function prep_project_list_render()
{
    global $database, $kga, $view;


    // select which projects I can see
    if ($database->gRole_allows($kga['who']['global_role_id'], 'core__project__other_group__view')) {
        $projects = $database->get_projects();
    }
    else {
        $projects = $database->get_projects($kga['who']['groups']);
    }

    // select what groups I can see on the selected project
    if (is_array($projects) && count($projects) > 0) {

        $viewOtherGroupsAllowed = $database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view');

        foreach ($projects as $row => &$project) {
            $groupNames = array();

            $groupIDs = $database->project_get_groupIDs($project['project_id']);

            if (is_array($groupIDs)) {
                foreach ($groupIDs as $groupID) {
                    if (!$viewOtherGroupsAllowed && in_array($groupID, $kga['who']['groups'], false) === false) {
                        continue;
                    }
                    $data         = $database->group_get_data($groupID);
                    $groupNames[] = is_array($data) ? $data['name'] : $groupID;
                }
            }
            $project['groups'] = implode(', ', $groupNames);
        }
        unset($project);
    }

    $view->projects = $projects;

    // projects need be created BEFORE editing to filter groups/activity in the edit-floater
    // so... filter customers for which I can create a project
    $user_grps       = $database->user_object_actions__allowed_groups('project', 'add');
    $customers = $database->customers_get($user_grps, 'select');
    $view->customers = array_replace(['0' => $kga['dict']['select_customer']], $customers);
}

function prep_status_list_render()
{
    global $database, $view;

    $view->arr_status = $database->status_get_all();
}

function prep_user_list_render()
{
    global $database, $kga, $view;


    // select which users I can see
    $view->showDeletedUsers = cookie_get('adm_ext_show_deleted_users', 0);
    if ($database->gRole_allows($kga['who']['global_role_id'], 'core__user__other_group__view')) {
        $users = $database->users_get($view->showDeletedUsers);
    }
    else {
        $users = $database->users_get($view->showDeletedUsers, $kga['who']['groups']);
    }

    // get group names for user list
    if (is_array($users) && count($users) > 0) {
        $viewOtherGroupsAllowed = $database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view');

        foreach ($users as &$user) {
            $user['groups'] = array();

            $groups = $database->user_get_group_ids($user['user_id'], false);
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    if (!$viewOtherGroupsAllowed && in_array($group, $kga['who']['groups'], true) === false) {
                        continue;
                    }
                    $groupData        = $database->group_get_data($group);
                    $user['groups'][] = $groupData['name'];
                }
            }
        }
        unset($user);
    }

    $view->users = $users;
}

function prep__subtabs_render()
{
    global $view;
    $view->languages = Translations::langs(); //?? is used ??
    $view->timezones = timezoneList();
}
