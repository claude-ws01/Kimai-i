<?php
require WEBROOT . 'libraries/Kimai/Database/kimai.php';

class Roles_Mysql extends Kimai_Mysql
{

    public function customer_watchable_users($customer)
    {
        global $kga;

        $customerID = $this->sqlValue($customer['customer_id'], MySQL::SQLVALUE_NUMBER);
        $p          = $kga['server_prefix'];
        $query      =
            "select * from {$p}user
            where `trash` = 0
            and `user_id` in (
                select distinct `user_id` from `{$p}timesheet`
                where `project_id` in (
                    select `project_id` from `{$p}project`
                    where `customer_id` = {$customerID}))
            order by name";

        $result = $this->query($query);

        if ($result->num_rows === 0) {
            return array();
        }
        else {
            return $this->recordsArray(MYSQLI_ASSOC);
        }
    }

    public function checkUserInternal($user_name, $customer_allowed = true)
    {
        global $kga, $translations;

        $p = $kga['server_prefix'];

        if (strncmp($user_name, 'customer_', 9) === 0) {
            //      CUSTOMER        //

            if (!$customer_allowed) {
                die;
            }

            $who_name = $this->sqlValue(substr($user_name, 9));
            $query    = "select `customer_id` from {$p}customer where `name` = {$who_name} and not `trash` = '1';";
            $this->query($query);
            $row = $this->rowArray(0, MYSQLI_ASSOC);

            $customer_id = (int)$row['customer_id'];

            if ($customer_id < 1) {
                Logger::logfile("Kicking customer $who_name because he is unknown to the system.");
                kickUser();

                return false;
            }

            $kga['is_user_root'] = false;

            $this->customer_data_load($customer_id);
            $this->pref_load($customer_id);
            $kga['who']['type']            = 'customer';
            $kga['who']['data']            = &$kga['customer'];
            $kga['who']['id']              = $customer_id;
            $kga['who']['name']            = &$kga['customer']['name'];
            $kga['who']['groups']          = &$kga['customer']['groups'];
            $kga['who']['timeframe_begin'] = &$kga['customer']['timeframe_begin'];
            $kga['who']['timeframe_end']   = &$kga['customer']['timeframe_end'];
            $kga['who']['global_role_id']  = 0;
        }

        else {
            //      USER        //
            $who_name = $this->sqlValue($user_name);
            $query    = "select `user_id` from {$p}user where `name` = {$who_name} and `active` = '1' and not `trash` = '1';";
            $this->query($query);
            $row = $this->rowArray(0, MYSQLI_ASSOC);

            $user_id = (int)$row['user_id'];

            if ($user_id < 1) {
                Logger::logfile("Kicking user $who_name because he is unknown to the system.");
                kickUser();

                return false;
            }

            $kga['is_user_root'] = $this->is_user_root($user_id);

            $this->user_data_load($user_id);
            $this->pref_load($user_id);
            $kga['who']['type']            = 'user';
            $kga['who']['data']            = &$kga['user'];
            $kga['who']['id']              = $user_id;
            $kga['who']['name']            = &$kga['user']['name'];
            $kga['who']['groups']          = &$kga['user']['groups'];
            $kga['who']['timeframe_begin'] = &$kga['user']['timeframe_begin'];
            $kga['who']['timeframe_end']   = &$kga['user']['timeframe_end'];
            $kga['who']['global_role_id']  = (int)$kga['user']['global_role_id'];
        }


        // skin fallback
        $skin = 'standard';
        if (isset($kga['pref']['skin'])
            && file_exists(WEBROOT . '/skins/' . $kga['pref']['skin'])
        ) {
            $skin = $kga['pref']['skin'];
        }
        $kga['pref']['skin'] = $skin;

        $translations->load();

        return $kga['who']['data'];
    }

    public function core_action_group_allowed($for_object, $for_action, $old_groups, $new_groups = null)
    {   // SECURITY //
        /*
         * @brief Check the permission to access an object.
         *
         * This method is meant to check permissions for
         *          ACTIONS:  add, edit, delete
         *          OBJECTS:  customers, projects, activities, users.
         *
         * The input is not checked whether it falls within those boundaries since
         * it can also work with others, if the permissions match the pattern.
         *
         * @param string $for_object string name of the object type being edited (e.g. Project)
         * @param array  $for_action      the action being performed (e.g. add)
         * @param array  $old_groups      the old groups of the object (empty array for new objects)
         * @param array  $new_groups      the new groups of the object (same as oldGroups if nothing should
         *                                be changed in group assignment)
         *
         * @return bool                   true if the permission is granted, false otherwise
         */
        global $database, $kga;

        if ($kga['who']['type'] !== 'user') {
            return false;
        }

        if ($kga['is_user_root']) {
            return true;
        }

        //                              OLD GROUPS                              //
        $other_groups = array_diff($old_groups, $kga['who']['groups']);
        // for other groups   ===  GLOBAL ===
        if (count($other_groups) > 0) {

            $permission_key = "core__{$for_object}__other_group__{$for_action}";

            if (!$database->gRole_allows($kga['user']['global_role_id'], $permission_key)) {
                return false;
            }
        }

        $own_groups = array_intersect($old_groups, $kga['who']['groups']);
        // for own group    === MEMBERSHIP ===
        if (count($own_groups) > 0) {

            $permission_key = "core__{$for_object}__{$for_action}";

            if (!$database->mRole_permission_ok($own_groups, $permission_key)) {
                $G = implode(', ', $own_groups);

                Logger::logfile("core_action_group_allowed $for_object ~ FALSE ~ {$permission_key}, OLD OWN_GROUPS ({$G}), USER ({$kga['who']['name']})");

                return false;
            }
        }


        //                              NEW GROUPS                              //
        if ($new_groups !== null
            && count($old_groups) !== array_intersect($old_groups, $new_groups)
        ) { // group assignment has changed


            $add_groups = array_diff($new_groups, $old_groups);
            //          ADD GROUPS        //
            $other_groups_add = array_diff($add_groups, $kga['who']['groups']);
            if (count($other_groups_add) > 0) {

                $permission_key = "core__{$for_object}__other_group__assign";

                if (!$database->gRole_allows($kga['user']['global_role_id'], $permission_key)) {

                    return false;
                }
            }

            $own_groups_add = array_intersect($add_groups, $kga['who']['groups']);
            if (count($own_groups_add) > 0) {

                $permission_key = "core__{$for_object}__assign";

                if (!$database->mRole_permission_ok($own_groups_add, $permission_key)) {
                    $G = implode(', ', $own_groups_add);
                    Logger::logfile("core_action_group_allowed $for_object ~ FALSE ~ {$permission_key}, NEW OWN_GROUPS ({$G}), USER ({$kga['who']['name']})");

                    return false;
                }
            }


            $remove_groups = array_diff($old_groups, $new_groups);
            //          REMOVE GROUPS          //
            $other_groups_remove = array_diff($remove_groups, $kga['who']['groups']);
            if (count($other_groups_remove) > 0) {

                $permission_key = "core__{$for_object}__other_group__unassign";

                if (!$database->gRole_allows($kga['user']['global_role_id'], $permission_key)) {
                    return false;
                }
            }

            $own_groups_remove = array_intersect($remove_groups, $kga['who']['groups']);
            if (count($own_groups_remove) > 0) {

                $permission_key = "core__{$for_object}__unassign";

                if (!$database->mRole_permission_ok($own_groups_remove, $permission_key)) {
                    $G = implode(', ', $own_groups_remove);
                    Logger::logfile("core_action_group_allowed $for_object ~ FALSE ~ {$permission_key}, OLD OWN_GROUPS ({$G}), USER ({$kga['who']['name']})");

                    return false;
                }
            }
        }

        return true;
    }

    public function gRole_allows($roleId, $for_permission)
    {
        global $kga;
        static $cache;


        if ($kga['is_user_root']): return true; endif;

        $key    = $roleId . $for_permission;
        $cached = ' (cached)';

        if (!isset($cache[$key])) {

            $tbl = TBL_GLOBAL_ROLE;

            $query = "select * from `{$tbl}`
                     where `global_role_id` = {$roleId}
                        and (`{$for_permission}` = 1)";

            if (($result = $this->query($query)) === false) {
                $this->logLastError(__FUNCTION__);

                return false;
            }
            $cache[$key] = $result->num_rows > 0;
            $cached      = '';
        }


        $rtn_str = $cache[$key] ? 'TRUE' : 'FALSE';

        Logger::logfile("GLOBAL role {$roleId}, {$rtn_str} ~ $for_permission{$cached}");

        return $cache[$key];
    }

    public function is_user_root($user_id)
    {
        global $kga;

        $p = $kga['server_prefix'];

        $query =
            "SELECT `user_id` FROM {$p}user as u
                    INNER JOIN {$p}global_role as g ON u.global_role_id = g.global_role_id
                        AND g.`ki_admin__edit_advanced` = 1
                WHERE u.`user_id` = {$user_id}
                    AND trash = 0";

        $result = $this->query($query);

        return (bool)$result->num_rows > 0;
    }

    public function mRole_allows($roleId, $for_permission)
    {
        global $kga;
        static $cache;

        $key    = $roleId . $for_permission;
        $cached = ' (cached)';

        if (!isset($cache[$key])) {
            $filter['membership_role_id'] = $this->sqlValue($roleId, MySQL::SQLVALUE_NUMBER);
            $filter[$for_permission]      = 1;
            $columns[]                    = 'membership_role_id';

            $result = $this->selectRows(TBL_MEMBERSHIP_ROLE, $filter, $columns);

            if ($result === false) { // error
                return false;
            }

            $cache[$key] = $result->num_rows > 0;
            $cached      = '';

        }
        $rtn_str = $cache[$key] ? 'TRUE' : 'FALSE';

        Logger::logfile("member role {$roleId}, {$rtn_str} ~ {$for_permission}, USER ~ {$kga['who']['name']}{$cached}");

        return $cache[$key];
    }

    public function mRole_permission_ok($for_groups, $for_permission, $requiredFor = 'all')
    {
        global $kga;

        $userId       = $kga['who']['id'];
        $userGroups   = $this->user_get_group_ids($userId, false);
        $commonGroups = array_intersect($userGroups, $for_groups);

        if (!is_array($commonGroups) || count($commonGroups) === 0) {
            return false;
        }

        foreach ($commonGroups as $commonGroup) {
            $roleId = $this->user_get_mRole_id($userId, $commonGroup);

            if ($requiredFor === 'who' && $this->mRole_allows($roleId, $for_permission)) {
                return true;
            }
            if ($requiredFor === 'all' && !$this->mRole_allows($roleId, $for_permission)) {
                return false;
            }
        }

        return $requiredFor === 'all';
    }

    public function ts_access_allowed($entry, $for_action, &$errors)
    {   // SECURITY //
        global $database, $kga;

        if ($kga['who']['type'] !== 'user') {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

            return false;
        }
        if ($kga['is_user_root']) {
            return true;
        }


        if ((int)$entry['end'] !== 0
            && $kga['conf']['edit_limit'] !== '-'
            && time() - $entry['end'] > $kga['conf']['edit_limit']
        ) {
            $errors[''] = $kga['dict']['editLimitError'];

            return false;
        }


        if ((int)$entry['user_id'] === (int)$kga['who']['id']) {
            $permission_key = "ki_timesheet__own_entry__{$for_action}";
            if ($database->gRole_allows($kga['who']['global_role_id'], $permission_key)) {
                return true;
            }
            else {

                return false;
            }
        }

        $groups     = $database->user_get_group_ids($entry['user_id'], false);
        $own_groups = array_intersect($groups, $kga['who']['groups']);

        if (count($own_groups) > 0) {
            $permission_key = "ki_timesheet__other_entry__own_group__{$for_action}";
            if ($database->mRole_permission_ok($own_groups, $permission_key)) {
                return true;
            }
            else {
                $G = implode(', ', $own_groups);
                Logger::logfile("ts_access_allowed ~ FALSE ~ {$permission_key}, OWN_GROUPS ({$G}), USER ({$kga['who']['name']})");
                $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

                return false;
            }

        }

        $permission_key = "ki_timesheet__other_entry__other_group__{$for_action}";
        $grps           = $kga['who']['global_role_id'];
        if ($database->gRole_allows($grps, $permission_key)) {
            return true;
        }
        else {
            Logger::logfile("ts_access_allowed ~ FALSE ~ {$permission_key}, OWN_GROUPS ({$grps}), USER ({$kga['who']['name']})");
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

            return false;
        }

    }

    public function user_get_mRole_id($userID, $groupID)
    {   // SECURITY //

        $filter['user_id']  = $this->sqlValue($userID, MySQL::SQLVALUE_NUMBER);
        $filter['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
        $columns[]          = 'membership_role_id';

        $result = $this->selectRows(TBL_GROUP_USER, $filter, $columns);

        if ($result === false) {
            return false;
        }

        $row = $this->rowArray(0, MYSQLI_ASSOC);

        return $row['membership_role_id'];
    }

    public function user_object_action__allowed($for_object, $for_action)
    {
        /*
         * Check if an action on a core object is allowed either
         *   - for other groups or
         *   - for any group the current user is a member of.
         *
         *  This is helpfull to check if an option to do the action should be presented to the user.
         *
         * @param $for_object string name of the object type being edited (e.g. Project)
         * @param $for_action         the action being performed (e.g. add)
         *
         * @return true if allowed, false otherwise
         */

        global $database, $kga;

        if ($database->gRole_allows($kga['who']['global_role_id'], "core__{$for_object}__other_group__{$for_action}")) {
            return true;
        }

        return (is_user()
            && $database->mRole_permission_ok($kga['who']['groups'],
                                              "core__{$for_object}__{$for_action}", 'who'));
    }

    public function user_object_actions__allowed_groups($for_object, $for_actions)
    {   // user_id=33, $for_object='customer', $for_actions=['assign','unassign']

        global $database, $kga;

        if ($kga['is_user_root']) {
            return $database->group_ids_get();
        }

        // validate
        $action_array = explode(',', $for_actions);
        if (count($action_array) === 0) {
            return array();
        }

        $user_groups = $kga['who']['groups'];

        $allowed_groups = array();


        // for own group    === MEMBERSHIP === 1 user ~ many mroles-groups
        $mrole_groups = null;
        foreach ($action_array as $action) {
            if ($mrole_groups === null) {
                $mrole_groups = $this->user_object_action__mRole_groups($kga['who']['id'], $for_object, $action);
            }
            else {
                $mrole_groups =
                    array_intersect($mrole_groups,
                                    $this->user_object_action__mRole_groups($kga['who']['id'],
                                                                            $for_object, $action)
                    );
            }
        }
        if (count($mrole_groups) > 0) {
            $allowed_groups = $mrole_groups;
        }


        // for ALL other groups   ===  GLOBAL === 1 user ~ 1 grole

        $other_groups_allowed = true;
        foreach ($action_array as $action) {
            $permission_key = "core__{$for_object}__other_group__{$action}";
            if (!$database->gRole_allows($kga['who']['id'], $permission_key)) {
                $other_groups_allowed = false;
            }
        }

        if ($other_groups_allowed) {
            $other_groups   = array_diff($database->group_ids_get(), $user_groups);
            $allowed_groups = array_merge($other_groups, $allowed_groups);
        }


        return $allowed_groups;
    }

    public function user_watchable_users($user)
    {
        global $kga;

        $userID = $this->sqlValue($user['user_id'], MySQL::SQLVALUE_NUMBER);


        if ($kga['is_user_root']
            || $this->gRole_allows($user['global_role_id'], 'core__user__other_group__view')
        ) { /*      gROLE & mROLE  ~~  OWN & OTHER      */


            /*  forbidden groups  */
            $forbidden_groups = array();
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$kga['is_user_root']) {
                $that = $this;
                // If user may see other groups we need to filter
                // out groups he's part of but has no permission to see users in.
                $forbidden_groups =
                    array_filter($user['groups'],
                        function ($groupID) use ($userID, $that) {
                            $roleID = $that->user_get_mRole_id($userID, $groupID);

                            return !$that->mRole_allows($roleID, 'core__user__view');
                        }
                    );
            }

            $p = $kga['server_prefix'];

            if (count($forbidden_groups) > 0) {
                $grp_str = implode(',', $forbidden_groups);

                $query = "SELECT *
                            FROM {$p}user AS u
                            INNER JOIN {$p}group_user AS p ON u.`user_id` = p.`user_id`
                                AND `group_id` NOT IN ({$grp_str})
                            WHERE u.`trash` = 0
                            ORDER BY `name`";
            }

            else {
                $query = "SELECT *
                            FROM {$p}user
                            WHERE `trash` = 0
                            ORDER BY `name`";
            }

            $this->query($query);

            return $this->recordsArray(MYSQLI_ASSOC);
        }


        /*      ONLY mROLE OWN GROUP        */
        $that           = $this;
        $allowed_groups =
            array_filter($user['groups'],
                function ($groupID) use ($userID, $that) {
                    $roleID = $that->user_get_mRole_id($userID, $groupID);

                    return $that->mRole_allows($roleID, 'core__user__view');
                });

        return $this->users_get(0, $allowed_groups); // array of users[]
    }

    public function xpe_access_allowed($entry, $for_action, &$errors)
    {   // SECURITY //
        global $database, $kga;

        if ($kga['who']['type'] !== 'user') {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

            return false;
        }

        if ($kga['is_user_root']) {
            return true;
        }


        // check if expense is too far in the past to allow editing (or deleting)
        if (isset($entry['id']) && $kga['conf']['edit_limit'] !== '-' && time() - $entry['timestamp'] > $kga['conf']['edit_limit']) {
            $errors[''] = $kga['dict']['editLimitError'];
        }


        if ($entry['user_id'] === $kga['who']['id']) {
            $permission_key = "ki_expense__own_entry__{$for_action}";
            if ($database->gRole_allows($kga['who']['global_role_id'], $permission_key)) {
                return true;
            }
            else {
                $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

                return false;
            }
        }

        $groups     = $database->user_get_group_ids($entry['user_id'], false);
        $own_groups = array_intersect($groups, $kga['who']['groups']);

        if (count($own_groups) > 0) {
            $permission_key = "ki_expense__other_entry__own_group__{$for_action}";
            if ($database->mRole_permission_ok($own_groups, $permission_key)) {
                return true;
            }
            else {
                $G = implode(', ', $own_groups);
                Logger::logfile("xpe_access_allowed, FALSE ~ {$permission_key}, OWN_GROUPS ({$G}), USER ({$kga['who']['name']})");
                $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

                return false;
            }

        }

        $permission_key = "ki_expense__other_entry__other_group__{$for_action}";
        if ($database->gRole_allows($kga['who']['global_role_id'], $permission_key)) {
            return true;
        }
        else {
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

            return false;
        }

    }

    protected function user_object_action__mRole_groups($user_id, $for_object, $for_action)
    {
        global $kga;

        $p = $kga['server_prefix'];

        $query = "select gu.group_id
                    from {$p}group_user as gu
                    inner join {$p}membership_role as mr on mr.membership_role_id = gu.membership_role_id
                        and core__{$for_object}__{$for_action} = 1
                    where gu.user_id = {$user_id}";

        if ($this->query($query) === false) {
            $this->logLastError('user_object_action__mRole_groups');

            return array();
        }

        $rows             = $this->recordsArray(MYSQLI_ASSOC);
        $own_groups_array = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $own_groups_array[] = $row['group_id'];
            }
        }

        return $own_groups_array;
    }

}
