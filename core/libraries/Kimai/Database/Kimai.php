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

/**
 * Provides the database layer for MySQL.
 *
 * @author th
 * @author sl
 * @author Kevin Papst
 */
class Kimai_Database_Mysql
{
//    protected $kga;

    /**
     * Adds a new activity
     *
     * @param array $data `name`, comment and other data of the new activity
     *
     * @return int          the activityID of the new project, false on failure
     * @author th
     */
    public function activity_create($data)
    {
        global $database, $kga;

        $data = $this->clean_data($data);

        $values['name']    = $database->SQLValue($data['name']);
        $values['comment'] = $database->SQLValue($data['comment']);
        $values['visible'] = $database->SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter']  = $database->SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $result = $database->InsertRow(TBL_ACTIVITY, $values);

        if (!$result) {
            $this->logLastError('activity_create');

            return false;
        }

        $activityID = $database->GetLastInsertID();

        if (isset($data['defaultRate'])) {
            if (is_numeric($data['defaultRate'])) {
                $this->save_rate(null, null, $activityID, $data['defaultRate']);
            }
            else {
                $this->remove_rate(null, null, $activityID);
            }
        }

        if (isset($data['myRate'])) {
            if (is_numeric($data['myRate'])) {
                $this->save_rate($kga['user']['user_id'], null, $activityID, $data['myRate']);
            }
            else {
                $this->remove_rate($kga['user']['user_id'], null, $activityID);
            }
        }

        if (isset($data['fixed_rate'])) {
            if (is_numeric($data['fixed_rate'])) {
                $this->save_fixed_rate(null, $activityID, $data['fixed_rate']);
            }
            else {
                $this->remove_fixed_rate(null, $activityID);
            }
        }

        return $activityID;
    }

    /**
     * deletes an activity
     *
     * @param array $activityID activityID of the activity
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function activity_delete($activityID)
    {
        global $database;

        $values['trash']      = 1;
        $filter['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_ACTIVITY, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Edits an activity by replacing its data by the new array
     *
     * @param array $activityID activityID of the project to be edited
     * @param array $data       `name`, comment and other new data of the activity
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function activity_edit($activityID, $data)
    {
        global $database, $kga;

        $data = $this->clean_data($data);
        $values = array();

        $strings = array('name', 'comment');
        foreach ($strings as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ]);
            }
        }

        $numbers = array('visible', 'filter');
        foreach ($numbers as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter  ['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);

        if (!$database->TransactionBegin()) {
            $this->logLastError('activity_edit');

            return false;
        }

        $query = $database->BuildSQLUpdate(TBL_ACTIVITY, $values, $filter);

        if ($database->Query($query)) {

            if (isset($data['defaultRate'])) {
                if (is_numeric($data['defaultRate'])) {
                    $this->save_rate(null, null, $activityID, $data['defaultRate']);
                }
                else {
                    $this->remove_rate(null, null, $activityID);
                }
            }

            if (isset($data['myRate'])) {
                if (is_numeric($data['myRate'])) {
                    $this->save_rate($kga['user']['user_id'], null, $activityID, $data['myRate']);
                }
                else {
                    $this->remove_rate($kga['user']['user_id'], null, $activityID);
                }
            }

            if (isset($data['fixed_rate'])) {
                if (is_numeric($data['fixed_rate'])) {
                    $this->save_fixed_rate(null, $activityID, $data['fixed_rate']);
                }
                else {
                    $this->remove_fixed_rate(null, $activityID);
                }
            }

            if (!$database->TransactionEnd()) {
                $this->logLastError('activity_edit');

                return false;
            }

            return true;
        }
        else {
            $this->logLastError('activity_edit');
            if (!$database->TransactionRollback()) {
                $this->logLastError('activity_edit');

                return false;
            }

            return false;
        }
    }

    /**
     * Returns the data of a certain activity
     *
     * @param array $activityID activityID of the project
     *
     * @return array         the activity's data (name, comment etc) as array, false on failure
     * @author th
     */
    public function activity_get_data($activityID)
    {
        global $database, $kga;

        $filter['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $result               = $database->SelectRows(TBL_ACTIVITY, $filter);

        if (!$result) {
            $this->logLastError('activity_get_data');

            return false;
        }


        $result_array = $database->RowArray(0, MYSQL_ASSOC);

        $result_array['defaultRate'] = $this->get_rate(null, null, $result_array['activity_id']);
        $result_array['myRate']      = $this->get_rate($kga['user']['user_id'], null, $result_array['activity_id']);
        $result_array['fixed_rate']   = $this->get_fixed_rate(null, $result_array['activity_id']);

        return $result_array;
    }

    /**
     * returns all the groups of the given activity
     *
     * @param int $activityID ID of the activity
     *
     * @return array         contains the groupIDs of the groups or false on error
     * @author sl
     */
    public function activity_get_groupIDs($activityID)
    {
        global $database;

        $filter['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]            = "group_id";

        $result = $database->SelectRows(TBL_GROUP_ACTIVITY, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groupIDs');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($database->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[ $counter ] = $row['group_id'];
                $counter++;
            }

            return $groupIDs;
        }
        else {
            return false;
        }
    }

    /**
     * returns all the groups of the given activity
     *
     * @param array $activityID activityID of the project
     *
     * @return array         contains the groupIDs of the groups or false on error
     * @author th
     */
    public function activity_get_groups($activityID)
    {
        global $database;

        $filter ['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]             = "group_id";

        $result = $database->SelectRows(TBL_GROUP_ACTIVITY, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groups');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($database->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[ $counter ] = $row['group_id'];
                $counter++;
            }

            return $groupIDs;
        }
        else {
            return false;
        }
    }

    /**
     * returns all the projects to which the activity was assigned
     *
     * @param array $activityID activityID of the project
     *
     * @return array         contains the IDs of the projects or false on error
     * @author th
     */
    public function activity_get_projects($activityID)
    {
        global $database;

        $filter ['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]             = "project_id";

        $result = $database->SelectRows(TBL_PROJECT_ACTIVITY, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_projects');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($database->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[ $counter ] = $row['project_id'];
                $counter++;
            }
        }

        return $groupIDs;
    }

    /**
     * Query the database for all fitting fixed rates for the given user, project and activity.
     *
     * @author sl
     */
    public function allFittingFixedRates($projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        $p = $kga['server_prefix'];

        $query = "SELECT `rate`, `project_id`, `activity_id` FROM ${p}fixed_rate WHERE
                (project_id = $projectID OR project_id IS NULL)  AND
                (activity_id = $activityID OR activity_id IS NULL)
                ORDER BY activity_id DESC , project_id DESC;";

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('allFittingFixedRates');

            return false;
        }

        return $database->RecordsArray(MYSQL_ASSOC);
    }

    /**
     * Query the database for all fitting rates for the given user, project and activity.
     *
     * @author sl
     */
    public function allFittingRates($userID, $projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        $p = $kga['server_prefix'];

        $query = "SELECT rate, user_id, project_id, activity_id FROM ${p}rate WHERE
    (user_id = $userID OR user_id IS NULL)  AND
    (project_id = $projectID OR project_id IS NULL)  AND
    (activity_id = $activityID OR activity_id IS NULL)
    ORDER BY user_id DESC, activity_id DESC , project_id DESC;";

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('allFittingRates');

            return false;
        }

        return $database->RecordsArray(MYSQL_ASSOC);
    }

    /**
     * Assigns an activity to 1-n groups by adding entries to the cross table
     *
     * @param int   $activityID activityID of the project to which the groups will be assigned
     * @param array $groupIDs   contains one or more groupIDs
     *
     * @return boolean            true on success, false on failure
     * @author ob/th
     */
    public function assign_activityToGroups($activityID, $groupIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_activityToGroups');

            return false;
        }

        $filter['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $database->BuildSQLDelete(TBL_GROUP_ACTIVITY, $filter);
        $d_result             = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_activityToGroups');
            $database->TransactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['group_id']    = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                = $database->BuildSQLInsert(TBL_GROUP_ACTIVITY, $values);
            $result               = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_activityToGroups');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_activityToGroups');

            return false;
        }
    }

    /**
     * Assigns an activity to 1-n projects by adding entries to the cross table
     *
     * @param int   $activityID id of the activity to which projects will be assigned
     * @param array $projectIDs contains one or more projectIDs
     *
     * @return boolean            true on success, false on failure
     * @author ob/th
     */
    public function assign_activityToProjects($activityID, $projectIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_activityToProjects');

            return false;
        }

        $filter['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $database->BuildSQLDelete(TBL_PROJECT_ACTIVITY, $filter);
        $d_result             = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_activityToProjects');
            $database->TransactionRollback();

            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['project_id']  = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $values['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                = $database->BuildSQLInsert(TBL_PROJECT_ACTIVITY, $values);
            $result               = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_activityToProjects');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_activityToProjects');

            return false;
        }
    }

    /**
     * Assigns a customer to 1-n groups by adding entries to the cross table
     *
     * @param int   $customerID id of the customer to which the groups will be assigned
     * @param array $groupIDs   contains one or more groupIDs
     *
     * @return boolean            true on success, false on failure
     * @author ob/th
     */
    public function assign_customerToGroups($customerID, $groupIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_customerToGroups');

            return false;
        }

        $filter['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $database->BuildSQLDelete(TBL_GROUP_CUSTOMER, $filter);
        $d_result             = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_customerToGroups');
            $database->TransactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['group_id']    = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query                = $database->BuildSQLInsert(TBL_GROUP_CUSTOMER, $values);
            $result               = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_customerToGroups');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_customerToGroups');

            return false;
        }
    }

    /**
     * Assigns a group to 1-n activities by adding entries to the cross table
     * (counterpart to assign_activityToGroups)
     *
     * @param array $groupID    groupID of the group to which the activities will be assigned
     * @param array $activityIDs contains one or more activityIDs
     *
     * @return boolean            true on success, false on failure
     * @author ob
     */
    public function assign_groupToActivities($groupID, $activityIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_groupToActivities');

            return false;
        }

        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query           = $database->BuildSQLDelete(TBL_GROUP_ACTIVITY, $filter);
        $d_result          = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToActivities');
            $database->TransactionRollback();

            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['group_id']    = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                = $database->BuildSQLInsert(TBL_GROUP_ACTIVITY, $values);
            $result               = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToActivities');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_groupToActivities');

            return false;
        }
    }

    /**
     * Assigns a group to 1-n customers by adding entries to the cross table
     * (counterpart to assign_customerToGroups)
     *
     * @param array $groupID     ID of the group to which the customers will be assigned
     * @param array $customerIDs contains one or more IDs of customers
     *
     * @return boolean            true on success, false on failure
     * @author ob/th
     */
    public function assign_groupToCustomers($groupID, $customerIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_groupToCustomers');

            return false;
        }

        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query           = $database->BuildSQLDelete(TBL_GROUP_CUSTOMER, $filter);

        $d_result = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToCustomers');
            $database->TransactionRollback();

            return false;
        }

        foreach ($customerIDs as $customerID) {
            $values['group_id']    = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query                = $database->BuildSQLInsert(TBL_GROUP_CUSTOMER, $values);
            $result               = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToCustomers');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_groupToCustomers');

            return false;
        }
    }

    /**
     * Assigns a group to 1-n projects by adding entries to the cross table
     * (counterpart to assign_projectToGroups)
     *
     * @param array $groupID    groupID of the group to which the projects will be assigned
     * @param array $projectIDs contains one or more project IDs
     *
     * @return boolean            true on success, false on failure
     * @author ob
     */
    public function assign_groupToProjects($groupID, $projectIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_groupToProjects');

            return false;
        }

        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query           = $database->BuildSQLDelete(TBL_GROUP_PROJECT, $filter);
        $d_result          = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToProjects');
            $database->TransactionRollback();

            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['group_id']   = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query               = $database->BuildSQLInsert(TBL_GROUP_PROJECT, $values);
            $result              = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToProjects');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_groupToProjects');

            return false;
        }
    }

    /**
     * Assigns 1-n activities to a project by adding entries to the cross table
     *
     * @param int   $projectID  id of the project to which activities will be assigned
     * @param array $activityID contains one or more activityIDs
     *
     * @return boolean            true on success, false on failure
     * @author sl
     */
    public function assign_projectToActivities($projectID, $activityIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_projectToActivities');

            return false;
        }

        $filter['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query             = $database->BuildSQLDelete(TBL_PROJECT_ACTIVITY, $filter);
        $d_result            = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_projectToActivities');
            $database->TransactionRollback();

            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $values['project_id']  = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query                = $database->BuildSQLInsert(TBL_PROJECT_ACTIVITY, $values);
            $result               = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_projectToActivities');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_projectToActivities');

            return false;
        }
    }

    /**
     * Assigns a project to 1-n groups by adding entries to the cross table
     *
     * @param int   $projectID ID of the project to which the groups will be assigned
     * @param array $groupIDs  contains one or more groupIDs
     *
     * @return boolean            true on success, false on failure
     * @author ob/th
     */
    public function assign_projectToGroups($projectID, $groupIDs)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('assign_projectToGroups');

            return false;
        }

        $filter['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query             = $database->BuildSQLDelete(TBL_GROUP_PROJECT, $filter);
        $d_result            = $database->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_projectToGroups');
            $database->TransactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {

            $values['group_id']   = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query               = $database->BuildSQLInsert(TBL_GROUP_PROJECT, $values);
            $result              = $database->Query($query);

            if ($result == false) {
                $this->logLastError('assign_projectToGroups');
                $database->TransactionRollback();

                return false;
            }
        }

        if ($database->TransactionEnd() == true) {
            return true;
        }
        else {
            $this->logLastError('assign_projectToGroups');

            return false;
        }
    }

    /**
     * Check if a user is allowed to access an object for a given action.
     *
     * @param       $userId                 integer      the ID of the user
     * @param       $objectGroups           array    list of group IDs of the object to check
     * @param       $permission             string      name of the permission to check for
     * @param       $requiredFor            string     (all|any) whether the permission must be present for all groups
     *                                      or at least one
     *
     * @return  string
     */
    public function checkMembershipPermission($userId, $objectGroups, $permission, $requiredFor = 'all')
    {
        $userGroups   = $this->getGroupMemberships($userId);
        $commonGroups = array_intersect($userGroups, $objectGroups);

        if (!is_array($commonGroups) || count($commonGroups) == 0) {
            return false;
        }

        foreach ($commonGroups as $commonGroup) {
            $roleId = $this->user_get_membership_role($userId, $commonGroup);

            if ($requiredFor == 'any' && $this->membership_role_allows($roleId, $permission)) {
                return true;
            }
            if ($requiredFor == 'all' && !$this->membership_role_allows($roleId, $permission)) {
                return false;
            }
        }

        return $requiredFor == 'all';
    }

    /**
     * A drop-in function to replace checkuser() and be compatible with none-cookie environments.
     *
     * @author th/kp
     */
    public function checkUserInternal($kimai_user)
    {
        global $database, $translations, $kga;

        $p = $kga['server_prefix'];

        if (strncmp($kimai_user, 'customer_', 9) == 0) {
            $customerName = $database->SQLValue(substr($kimai_user, 9));
            $query        = "SELECT customer_id FROM ${p}customer WHERE name = $customerName AND NOT trash = '1';";
            $database->Query($query);
            $row = $database->RowArray(0, MYSQL_ASSOC);

            $customerID = $row['customer_id'];
            if ($customerID < 1) {
                Logger::logfile("Kicking customer $customerName because he is unknown to the system.");
                kickUser();
            }
        }
        else {
            $query = "SELECT user_id FROM ${p}user WHERE name = '$kimai_user' AND active = '1' AND NOT trash = '1';";
            $database->Query($query);
            $row = $database->RowArray(0, MYSQL_ASSOC);

            $userID = $row['user_id'];
            $name   = $kimai_user;

            if ($userID < 1) {
                Logger::logfile("Kicking user $name because he is unknown to the system.");
                kickUser();
            }
        }

        // load configuration and language
        $this->get_global_config();
        if (strncmp($kimai_user, 'customer_', 9) == 0) {
            $this->get_customer_config($customerID);
        }
        else {
            $this->get_user_config($userID);
        }

        // skin fallback
        $skin = 'standard';
        if (isset($kga['conf']['skin'])
            && file_exists(WEBROOT . "/skins/" . $kga['conf']['skin'])
        ) {
            $skin = $kga['conf']['skin'];
        }
        $kga['conf']['skin'] = $skin;


        // override autoconf language if admin has chosen a language in the advanced tab
        if ($kga['conf']['language'] != "") {
            $translations->load($kga['conf']['language']);
            $kga['language'] = $kga['conf']['language'];
        }

        // override language if user has chosen a language in the prefs
        if ($kga['conf']['lang'] != "") {
            $translations->load($kga['conf']['lang']);
            $kga['language'] = $kga['conf']['lang'];
        }

        return (isset($kga['user']) ? $kga['user'] : null);
    }

    /**
     * Edits a configuration variables by replacing the data by the new array
     *
     * @param array $data variables array
     *
     * @return boolean       true on success, false on failure
     * @author ob
     */
    public function configuration_edit($data)
    {
        global $database;

        $data = $this->clean_data($data);


        if (!$database->TransactionBegin()) {
            $this->logLastError('configuration_edit');

            return false;
        }

        foreach ($data as $key => $value) {
            $filter['option'] = $database->SQLValue($key);
            $values ['value'] = $database->SQLValue($value);

            $query = $database->BuildSQLUpdate(TBL_CONFIGURATION, $values, $filter);

            $result = $database->Query($query);

            if ($result === false) {
                $this->logLastError('configuration_edit');

                return false;
            }
        }

        if (!$database->TransactionEnd()) {
            $this->logLastError('configuration_edit');

            return false;
        }

        return true;
    }

    /**
     * Returns all configuration variables
     *
     * @return array       array with the options from the configuration table
     * @author th
     */
    public function configuration_get_data()
    {
        global $database;

        $database->SelectRows(TBL_CONFIGURATION);

        $config_data = array();

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row                         = $database->Row();
            $config_data[ $row->option ] = $row->value;
        }

        return $config_data;
    }

    /**
     * Connect to the database.
     */
    //    public function connect($host, $database, $username, $password, $utf8, $serverType)
    //    {
    //        if (isset($utf8) && $utf8) {
    //            $this->MySQL = new MySQL(true, $database, $host, $username, $password, "utf8");
    //        }
    //        else {
    //            $this->MySQL = new MySQL(true, $database, $host, $username, $password);
    //        }
    //    }

    /**
     * Add a new customer to the database.
     *
     * @param array $data `name`, address and other data of the new customer
     *
     * @return int         the customerID of the new customer, false on failure
     * @author th
     */
    public function customer_create($data)
    {
        global $database;

        $data = $this->clean_data($data);

        $values     ['name']    = $database->SQLValue($data   ['name']);
        $values     ['comment'] = $database->SQLValue($data   ['comment']);
        if (isset($data['password'])) {
            $values   ['password'] = $database->SQLValue($data   ['password']);
        }
        else {
            $values   ['password'] = "''";
        }
        $values     ['company']  = $database->SQLValue($data   ['company']);
        $values     ['vat']      = $database->SQLValue($data   ['vat']);
        $values     ['contact']  = $database->SQLValue($data   ['contact']);
        $values     ['street']   = $database->SQLValue($data   ['street']);
        $values     ['zipcode']  = $database->SQLValue($data   ['zipcode']);
        $values     ['city']     = $database->SQLValue($data   ['city']);
        $values     ['phone']    = $database->SQLValue($data   ['phone']);
        $values     ['fax']      = $database->SQLValue($data   ['fax']);
        $values     ['mobile']   = $database->SQLValue($data   ['mobile']);
        $values     ['mail']     = $database->SQLValue($data   ['mail']);
        $values     ['homepage'] = $database->SQLValue($data   ['homepage']);
        $values     ['timezone'] = $database->SQLValue($data   ['timezone']);

        $values['visible'] = $database->SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter']  = $database->SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $result = $database->InsertRow(TBL_CUSTOMER, $values);

        if (!$result) {
            $this->logLastError('customer_create');

            return false;
        }
        else {
            return $database->GetLastInsertID();
        }
    }

    /**
     * deletes a customer
     *
     * @param int $customerID id of the customer
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function customer_delete($customerID)
    {
        global $database;

        $values['trash']      = 1;
        $filter['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_CUSTOMER, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Edits a customer by replacing his data by the new array
     *
     * @param int   $customerID id of the customer to be edited
     * @param array $data       `name`, address and other new data of the customer
     *
     * @return boolean       true on success, false on failure
     * @author ob/th
     */
    public function customer_edit($customerID, $data)
    {
        global $database;

        $data = $this->clean_data($data);

        $values = array();

        $strings = array(
            'name', 'comment', 'password', 'company', 'vat',
            'contact', 'street', 'zipcode', 'city', 'phone',
            'fax', 'mobile', 'mail', 'homepage', 'timezone',
            'password_reset_hash');
        foreach ($strings as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ]);
            }
        }

        $numbers = array('visible', 'filter');
        foreach ($numbers as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_CUSTOMER, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Returns the data of a certain customer
     *
     * @param array $customerID id of the customer
     *
     * @return array         the customer's data (name, address etc) as array, false on failure
     * @author th
     */
    public function customer_get_data($customerID)
    {
        global $database;

        $filter['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $result               = $database->SelectRows(TBL_CUSTOMER, $filter);

        if (!$result) {
            $this->logLastError('customer_get_data');

            return false;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     * returns all IDs of the groups of the given customer
     *
     * @param int $id id of the customer
     *
     * @return array         contains the groupIDs of the groups or false on error
     * @author th
     */
    public function customer_get_groupIDs($customerID)
    {
        global $database;

        $filter['customer_id'] = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $columns[]            = "group_id";

        $result = $database->SelectRows(TBL_GROUP_CUSTOMER, $filter, $columns);
        if ($result == false) {
            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($database->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[ $counter ] = $row['group_id'];
                $counter++;
            }

            return $groupIDs;
        }
        else {
            $this->logLastError('customer_get_groupIDs');

            return false;
        }
    }

    /**
     * Save a new secure key for a customer to the database. This key is stored in the clients cookie and used
     * to reauthenticate the customer.
     *
     * @author sl
     */
    public function customer_loginSetKey($customerId, $keymai)
    {
        global $database, $kga;

        $p          = $kga['server_prefix'];
        $customerId = mysqli_real_escape_string($database->mysql_link, $customerId);

        $query = "UPDATE ${p}customer SET secure='$keymai' WHERE customer_id='" . $customerId . "';";
        $database->Query($query);
    }

    /**
     * return ID of specific user named 'XXX'
     *
     * @param integer $name name of user in table users
     *
     * @return id of the customer
     */
    public function customer_nameToID($name)
    {
        global $kga;
        return $this->name2id($kga['server_prefix'] . "customers", 'customer_id', 'name', $name);
    }

    /**
     * Get the groups in which the user is a member in.
     *
     * @param int $userId id of the user
     *
     * @return array        list of group ids
     */
    public function getGroupMemberships($userId)
    {
        global $database;

        $filter['user_id'] = $database->SQLValue($userId);
        $columns[]        = "group_id";
        $result           = $database->SelectRows(TBL_GROUP_USER, $filter, $columns);

        if (!$result) {
            $this->logLastError('getGroupMemberships');

            return null;
        }

        $arr = array();
        if ($database->RowCount()) {
            $database->MoveFirst();
            while (!$database->EndOfSeek()) {
                $row   = $database->Row();
                $arr[] = $row->group_id;
            }
        }

        return $arr;
    }

    /**
     * Returns a username for the given $apikey.
     *
     * @param string $apikey
     *
     * @return string|null
     */
    public function getUserByApiKey($apikey)
    {
        global $database;

        if (!$apikey || strlen(trim($apikey)) == 0) {
            return null;
        }

        $filter['apikey'] = $database->SQLValue($apikey, MySQL::SQLVALUE_TEXT);
        $filter['trash']  = $database->SQLValue(0, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "user_id";
        $columns[] = "name";

        $database->SelectRows(TBL_USER, $filter, $columns);
        $row = $database->RowArray(0, MYSQL_ASSOC);

        return $row['name'];
    }

    /**
     * returns the version of the installed Kimai database to compare it with the package version
     *
     * @return array
     * @author th
     *
     * [0] => version number (x.x.x)
     * [1] => svn revision number
     *
     */
    public function get_DBversion()
    {
        global $database, $kga;

        $filter['option'] = $database->SQLValue('version');
        $columns[]        = "value";
        $table            = TBL_CONFIGURATION;
        $result           = $database->SelectRows($table, $filter, $columns);

        if ($result == false) {
            // before database revision 1369 (503 + 866)
            $table = $kga['server_prefix'] . "var";
            unset($filter);
            $filter['var'] = $database->SQLValue('version');
            $result        = $database->SelectRows($table, $filter, $columns);
        }

        $row      = $database->RowArray(0, MYSQL_ASSOC);
        $return[] = $row['value'];

        if ($result == false) $return[0] = "0.5.1";

        $filter['option'] = $database->SQLValue('revision');
        $result           = $database->SelectRows($table, $filter, $columns);

        if ($result == false) {
            // before database revision 1369 (503 + 866)
            unset($filter);
            $filter['var'] = $database->SQLValue('revision');
            $database->SelectRows($table, $filter, $columns);
        }

        $row      = $database->RowArray(0, MYSQL_ASSOC);
        $return[] = $row['value'];

        return $return;
    }

    /**
     * @param array|null $groups
     *
     * @return array|bool
     */
    public function get_activities(array $groups = null)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT activity_id, `name`, visible
              FROM ${p}activity
              WHERE trash=0
              ORDER BY visible DESC, `name`;";
        }
        else {
            $query = "SELECT DISTINCT `activity_id`, `name`, `visible`
              FROM ${p}activity
              JOIN ${p}group_activity AS g_a USING(activity_id)
              WHERE g_a.group_id IN (" . implode($groups, ',') . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        $result = $database->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities');

            return false;
        }

        $arr = array();
        $i   = 0;
        if ($database->RowCount()) {
            $database->MoveFirst();
            while (!$database->EndOfSeek()) {
                $row                     = $database->Row();
                $arr[ $i ]['activity_id'] = $row->activity_id;
                $arr[ $i ]['name']       = $row->name;
                $arr[ $i ]['visible']    = $row->visible;
                $i++;
            }

            return $arr;
        }
        else {
            return array();
        }
    }

    /**
     * returns list of activities used with specified customer
     *
     * @param integer $customer filter for only this ID of a customer
     *
     * @return array
     * @author sl
     */
    public function get_activities_by_customer($customer_ID)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        $customer_ID = $database->SQLValue($customer_ID, MySQL::SQLVALUE_NUMBER);

        $query = "SELECT DISTINCT `activity_id`, `name`, `visible`
          FROM ${p}activity
          WHERE activity_id IN
              (SELECT activity_id FROM ${p}timesheet
                WHERE project_id IN (SELECT project_id FROM ${p}project WHERE customer_id = $customer_ID))
            AND trash=0";

        $result = $database->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities_by_customer');

            return false;
        }

        $arr = array();
        $i   = 0;

        if ($database->RowCount()) {
            $database->MoveFirst();
            while (!$database->EndOfSeek()) {
                $row                     = $database->Row();
                $arr[ $i ]['activity_id'] = $row->activity_id;
                $arr[ $i ]['name']       = $row->name;
                $arr[ $i ]['visible']    = $row->visible;
                $i++;
            }

            return $arr;
        }
        else {
            return array();
        }
    }

    /**
     * Get an array of activities, which should be displayed for a specific project.
     * Those are activities which were assigned to the project or which are assigned to
     * no project.
     *
     * Two joins can occur:
     *  The JOIN is for filtering the activities by groups.
     *
     *  The LEFT JOIN gives each activity row the project id which it has been assigned
     *  to via the projects_activities table or NULL when there is no assignment. So we only
     *  take rows which have NULL or the project id in that column.
     *
     * @author sl
     */
    public function get_activities_by_project($projectID, array $groups = null)
    {
        global $database, $kga;

        $projectID = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM ${p}activity AS activity
            LEFT JOIN ${p}project_activity AS p_a USING(activity_id)
            WHERE activity.trash=0
              AND (project_id = $projectID OR project_id IS NULL)
            ORDER BY visible DESC, name;";
        }
        else {
            $query = "SELECT DISTINCT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM ${p}activity AS activity
            JOIN ${p}group_activity USING(activity_id)
            LEFT JOIN ${p}project_activity p_a USING(activity_id)
            WHERE `${p}group_activity`.`group_id`  IN (" . implode($groups, ',') . ")
              AND activity.trash=0
              AND (project_id = $projectID OR project_id IS NULL)
            ORDER BY visible DESC, name;";
        }

        $result = $database->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities_by_project');

            return false;
        }

        $arr = array();
        if ($database->RowCount()) {
            $database->MoveFirst();
            while (!$database->EndOfSeek()) {
                $row                                   = $database->Row();
                $arr[ $row->activity_id ]['activity_id'] = $row->activity_id;
                $arr[ $row->activity_id ]['name']       = $row->name;
                $arr[ $row->activity_id ]['visible']    = $row->visible;
                $arr[ $row->activity_id ]['budget']     = $row->budget;
                $arr[ $row->activity_id ]['approved']   = $row->approved;
                $arr[ $row->activity_id ]['effort']     = $row->effort;
            }

            return $arr;
        }
        else {
            return array();
        }
    }

    /**
     * Read activity budgets
     *
     * @author mo
     */
    public function get_activity_budget($projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        $p = $kga['server_prefix'];

        $query = "SELECT budget, approved, effort FROM ${p}project_activity WHERE " .
            (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
            (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('get_activity_budget');

            return false;
        }
        $data = $database->rowArray(0, MYSQL_ASSOC);
        if (!isset($data['budget'])) $data['budget'] = 0;
        if (!isset($data['approved'])) $data['approved'] = 0;

        $timeSheet = $this->get_timeSheet(0, time(), null, null, array($projectID), array($activityID));
        foreach ($timeSheet as $timeSheetEntry) {
            if (isset($timeSheetEntry['budget'])) {
                $data['budget'] += $timeSheetEntry['budget'];
            }
            if (isset($timeSheetEntry['approved'])) {
                $data['approved'] += $timeSheetEntry['approved'];
            }
        }

        return $data;
    }

    /**
     * Query the database for the best fitting fixed rate for the given user, project and activity.
     *
     * @author sl
     */
    public function get_best_fitting_fixed_rate($projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate FROM " . $kga['server_prefix'] . "fixed_rate WHERE
    (project_id = $projectID OR project_id IS NULL)  AND
    (activity_id = $activityID OR activity_id IS NULL)
    ORDER BY activity_id DESC , project_id DESC
    LIMIT 1;";

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('get_best_fitting_fixed_rate');

            return false;
        }

        if ($database->RowCount() == 0) {
            return false;
        }

        $data = $database->rowArray(0, MYSQL_ASSOC);

        return $data['rate'];
    }

    /**
     * Query the database for the best fitting rate for the given user, project and activity.
     *
     * @author sl
     */
    public function get_best_fitting_rate($userID, $projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        $p = $kga['server_prefix'];


        $query = "SELECT rate FROM ${p}rate WHERE
                (user_id = $userID OR user_id IS NULL)  AND
                (project_id = $projectID OR project_id IS NULL)  AND
                (activity_id = $activityID OR activity_id IS NULL)
                ORDER BY user_id DESC, activity_id DESC , project_id DESC
                LIMIT 1;";

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('get_best_fitting_rate');

            return false;
        }

        if ($database->RowCount() == 0) {
            return false;
        }

        $data = $database->rowArray(0, MYSQL_ASSOC);

        return $data['rate'];
    }

    /**
     *
     * get the whole budget used for the activity
     *
     * @param integer $projectID
     * @param integer $activityID
     */
    public function get_budget_used($projectID, $activityID)
    {
        $timeSheet  = $this->get_timeSheet(0, time(), null, null, array($projectID), array($activityID));
        $budgetUsed = 0;
        if (is_array($timeSheet)) {
            foreach ($timeSheet as $timeSheetEntry) {
                $budgetUsed += $timeSheetEntry['wage_decimal'];
            }
        }

        return $budgetUsed;
    }

    /**
     * Returns a list of IDs of all current recordings.
     *
     * @param integer $user ID of user in table users
     *
     * @return array with all IDs of current recordings. This array will be empty if there are none.
     * @author sl
     */
    public function get_current_recordings($userID)
    {
        global $database, $kga;

        $p      = $kga['server_prefix'];
        $userID = $database->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $result = $database->Query("SELECT time_entry_id FROM ${p}timesheet WHERE user_id = $userID AND start > 0 AND end = 0");

        if ($result === false) {
            $this->logLastError('get_current_recordings');

            return array();
        }

        $IDs = array();

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row   = $database->Row();
            $IDs[] = $row->time_entry_id;
        }

        return $IDs;
    }

    /**
     * returns time of currently running activity recording as array
     *
     * result is meant as params for the stopwatch if the window is reloaded
     *
     * <pre>
     * returns:
     * [all] start time of entry in unix seconds (forgot why I named it this way, sorry ...)
     * [hour]
     * [min]
     * [sec]
     * </pre>
     *
     * @param integer $user ID of user in table users
     *
     * @return array
     * @author th
     */
    public function get_current_timer()
    {
        global $database, $kga;

        $user = $database->SQLValue($kga['user']['user_id'], MySQL::SQLVALUE_NUMBER);
        $p    = $kga['server_prefix'];

        $database->Query("SELECT time_entry_id, start FROM ${p}timesheet WHERE user_id = $user AND end = 0;");

        if ($database->RowCount() == 0) {
            $current_timer['all']  = 0;
            $current_timer['hour'] = 0;
            $current_timer['min']  = 0;
            $current_timer['sec']  = 0;
        }
        else {

            $row = $database->RowArray(0, MYSQL_ASSOC);

            $start = (int)$row['start'];

            $aktuelleMessung       = Format::hourminsec(time() - $start);
            $current_timer['all']  = $start;
            $current_timer['hour'] = $aktuelleMessung['h'];
            $current_timer['min']  = $aktuelleMessung['i'];
            $current_timer['sec']  = $aktuelleMessung['s'];
        }

        return $current_timer;
    }

    /**
     * write details of a specific customer into $this->kga
     *
     * @param integer $user ID of user in table users
     *
     * @return array $this->kga
     * @author sl
     *
     */
    public function get_customer_config($user)
    {
        global $database, $kga;

        if (!$user) return;

        $filter['customer_id'] = $database->SQLValue($user, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "city";
        $columns[] = "comment";
        $columns[] = "company";
        $columns[] = "customer_id";
        $columns[] = "fax";
        $columns[] = "filter";
        $columns[] = "homepage";
        $columns[] = "mail";
        $columns[] = "mobile";
        $columns[] = "name";
        $columns[] = "password";
        $columns[] = "phone";
        $columns[] = "secure";
        $columns[] = "street";
        $columns[] = "timezone";
        $columns[] = "trash";
        $columns[] = "visible";
        $columns[] = "zipcode";

        $database->SelectRows(TBL_CUSTOMER, $filter, $columns);
        $rows = $database->RowArray(0, MYSQL_ASSOC);
        foreach ($rows as $key => $value) {
            $kga['customer'][ $key ] = $value;
        }

        date_default_timezone_set($kga['customer']['timezone']);
    }

    public function get_customer_watchable_users($customer)
    {
        global $database, $kga;

        $customerID = $database->SQLValue($customer['customer_id'], MySQL::SQLVALUE_NUMBER);
        $p          = $kga['server_prefix'];
        $query      = "SELECT * FROM ${p}user WHERE trash=0 AND `user_id` IN (SELECT DISTINCT `user_id` FROM `${p}timesheet` WHERE `project_id` IN (SELECT `project_id` FROM `${p}project` WHERE `customer_id` = $customerID)) ORDER BY name";
        $database->Query($query);

        return $database->RecordsArray(MYSQL_ASSOC);
    }

    /**
     * returns list of customers in a group as array
     *
     * @param integer $group ID of group in table groups or "all" for all groups
     *
     * @return array
     * @author th
     */
    public function get_customers(array $groups = null)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT customer_id, `name`, contact, visible
              FROM ${p}customer
              WHERE trash=0
              ORDER BY visible DESC, name;";
        }
        else {
            $query = "SELECT DISTINCT customer_id, `name`, contact, visible
              FROM ${p}customer
              JOIN ${p}group_customer AS g_c USING (customer_id)
              WHERE g_c.group_id IN (" . implode($groups, ',') . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        $result = $database->Query($query);
        if ($result == false) {
            $this->logLastError('get_customers');

            return false;
        }

        $i = 0;
        if ($database->RowCount()) {
            $arr = array();
            $database->MoveFirst();
            while (!$database->EndOfSeek()) {
                $row                     = $database->Row();
                $arr[ $i ]['customer_id'] = $row->customer_id;
                $arr[ $i ]['name']       = $row->name;
                $arr[ $i ]['contact']    = $row->contact;
                $arr[ $i ]['visible']    = $row->visible;
                $i++;
            }

            return $arr;

        }

        return array();
    }

    /**
     * returns time summary of current timesheet
     *
     * @param integer $user  ID of user in table users
     * @param integer $start start of timeframe in unix seconds
     * @param integer $end   end of timeframe in unix seconds
     *
     * @return integer
     * @author th
     */
    public function get_duration($start, $end, $users = null, $customers = null, $projects = null, $activities = null, $filterCleared = null)
    {
        global $database, $kga;

        if (!is_numeric($filterCleared)) {
            $filterCleared = $kga['conf']['hideClearedEntries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);


        $p            = $kga['server_prefix'];
        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }
        if ($filterCleared > -1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        $query = "SELECT start,end,duration FROM ${p}timesheet
              Join ${p}project USING(project_id)
              Join ${p}customer USING(customer_id)
              Join ${p}user USING(user_id)
              Join ${p}activity USING(activity_id) "
            . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);
        $database->Query($query);

        $database->MoveFirst();
        $sum             = 0;
        $consideredStart = 0; // Consider start of selected range if real start is before
        $consideredEnd   = 0; // Consider end of selected range if real end is afterwards
        while (!$database->EndOfSeek()) {
            $row = $database->Row();
            if ($row->start <= $start && $row->end < $end) {
                $consideredStart = $start;
                $consideredEnd   = $row->end;
            }
            else {
                if ($row->start <= $start && $row->end >= $end) {
                    $consideredStart = $start;
                    $consideredEnd   = $end;
                }
                else {
                    if ($row->start > $start && $row->end < $end) {
                        $consideredStart = $row->start;
                        $consideredEnd   = $row->end;
                    }
                    else {
                        if ($row->start > $start && $row->end >= $end) {
                            $consideredStart = $row->start;
                            $consideredEnd   = $end;
                        }
                    }
                }
            }
            $sum += (int)($consideredEnd - $consideredStart);
        }

        return $sum;
    }

    /**
     * Read fixed rate from database.
     *
     * @param $projectID
     * @param $activityID
     *
     * @return bool
     */
    public function get_fixed_rate($projectID, $activityID)
    {
        global $database, $kga;


        $p = $kga['server_prefix'];
        $P = ($projectID == null || !is_numeric($projectID)) ? "project_id is NULL" : "project_id = $projectID";
        $A = ($activityID == null || !is_numeric($activityID)) ? "activity_id is NULL" : "project_id = $activityID";

        $query = "SELECT `rate` FROM ${p}fixed_rate WHERE ${P} AND ${A}";

        if ($database->Query($query) === false) {
            $this->logLastError('get_fixed_rate');

            return false;
        }

        if ($database->RowCount() == 0) {
            return false;
        }

        $data = $database->rowArray(0, MYSQL_ASSOC);

        return $data['rate'];
    }

    /**
     * write global configuration into $this->kga including defaults for user settings.
     *
     * @param integer $user ID of user in table users
     *
     * @return array $this->kga
     * @author th
     *
     */
    public function get_global_config()
    {
        global $database, $kga;

        // get values from global configuration
        $database->SelectRows(TBL_CONFIGURATION);

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row                               = $database->Row();
            $kga['conf'][ $row->option ] = $row->value;
        }


        $kga['conf']['rowlimit']             = 100;
        $kga['conf']['skin']                 = 'standard';
        $kga['conf']['autoselection']        = 1;
        $kga['conf']['quickdelete']          = 0;
        $kga['conf']['flip_project_display'] = 0;
        $kga['conf']['project_comment_flag'] = 0;
        $kga['conf']['showIDs']              = 0;
        $kga['conf']['noFading']             = 0;
        $kga['conf']['lang']                 = '';
        $kga['conf']['user_list_hidden']     = 0;
        $kga['conf']['hideClearedEntries']   = 0;


        $database->SelectRows(TBL_STATUS);

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row                                           = $database->Row();
            $kga['conf']['status'][ $row->status_id ] = $row->status;
        }
    }

    /**
     * returns array of all groups
     *
     * [0]=> array(6) {
     *      ["groupID"]      =>  string(1) "1"
     *      ["groupName"]    =>  string(5) "admin"
     *      ["userID"]  =>  string(9) "1234"
     *      ["trash"]   =>  string(1) "0"
     *      ["count_users"] =>  string(1) "2"
     * }
     *
     * [1]=> array(6) {
     *      ["groupID"]      =>  string(1) "2"
     *      ["groupName"]    =>  string(4) "Test"
     *      ["userID"]  =>  string(9) "12345"
     *      ["trash"]   =>  string(1) "0"
     *      ["count_users"] =>  string(1) "1"
     *  }
     *
     * @return array
     * @author th
     *
     */
    public function get_groups($trash = 0)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        // Lock tables for alles queries executed until the end of this public function
        $lock   = "LOCK TABLE ${p}user READ, ${p}group READ, ${p}group_user READ;";
        $result = $database->Query($lock);
        if (!$result) {
            $this->logLastError('get_groups');

            return false;
        }

        //------

        if (!$trash) {
            $trashoption = "WHERE ${p}group.trash !=1";
        }

        $query = "SELECT * FROM ${p}group $trashoption ORDER BY name;";
        $database->Query($query);

        // rows into array
        $groups = array();
        $i      = 0;

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        foreach ($rows as $row) {
            $groups[] = $row;

            // append user count
            $groups[ $i ]['count_users'] = $this->group_count_users($row['group_id']);

            $i++;
        }

        //------

        // Unlock tables
        $unlock = "UNLOCK TABLES;";
        $result = $database->Query($unlock);
        if (!$result) {
            $this->logLastError('get_groups');

            return false;
        }

        return $groups;
    }

    /**
     * Return the latest running entry with all information required for the buzzer.
     *
     * @return array with all data
     * @author sl
     */
    public function get_latest_running_entry()
    {
        global $database, $kga;

        $table         = TBL_TIMESHEET;
        $projectTable  = TBL_PROJECT;
        $activityTable = TBL_ACTIVITY;
        $customerTable = TBL_CUSTOMER;

        $select = "SELECT $table.*, $projectTable.name AS projectName, $customerTable.name AS customerName, $activityTable.name AS activityName, $customerTable.customer_id AS customer_id
          FROM $table
              JOIN $projectTable USING(project_id)
              JOIN $customerTable USING(customer_id)
              JOIN $activityTable USING(activity_id)";

        $result = $database->Query("$select WHERE end = 0 AND user_id = " . $kga['user']['user_id'] . " ORDER BY time_entry_id DESC LIMIT 1");

        if (!$result) {
            return null;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     * returns list of projects for specific group as array
     *
     * @param array $groups
     *
     * @return array
     * @author th
     */
    public function get_projects(array $groups = null)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT project.*, customer.name AS customerName
                  FROM ${p}project AS project
                  JOIN ${p}customer AS customer USING(customer_id)
                  WHERE project.trash=0";
        }
        else {
            $query = "SELECT DISTINCT project.*, customer.name AS customerName
                  FROM ${p}project AS project
                  JOIN ${p}customer AS customer USING(customer_id)
                  JOIN ${p}group_project USING(project_id)
                  WHERE ${p}group_project.group_id IN (" . implode($groups, ',') . ")
                  AND project.trash=0";
        }

        if ($kga['conf']['flip_project_display']) {
            $query .= " ORDER BY project.visible DESC, customerName, name;";
        }
        else {
            $query .= " ORDER BY project.visible DESC, `name`, customerName;";
        }

        $result = $database->Query($query);
        if ($result == false) {
            $this->logLastError('get_projects');

            return false;
        }

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($rows) {
            $arr = array();
            $i   = 0;
            foreach ($rows as $row) {
                $arr[ $i ]['project_id']    = $row['project_id'];
                $arr[ $i ]['customer_id']   = $row['customer_id'];
                $arr[ $i ]['name']         = $row['name'];
                $arr[ $i ]['comment']      = $row['comment'];
                $arr[ $i ]['visible']      = $row['visible'];
                $arr[ $i ]['filter']       = $row['filter'];
                $arr[ $i ]['trash']        = $row['trash'];
                $arr[ $i ]['budget']       = $row['budget'];
                $arr[ $i ]['effort']       = $row['effort'];
                $arr[ $i ]['approved']     = $row['approved'];
                $arr[ $i ]['internal']     = $row['internal'];
                $arr[ $i ]['customerName'] = $row['customerName'];
                $i++;
            }

            return $arr;
        }

        return array();
    }

    /**
     * returns list of projects for specific group and specific customer as array
     *
     * @param integer $customerID customer id
     * @param array   $groups     list of group ids
     *
     * @return array
     * @author ob
     */
    public function get_projects_by_customer($customerID, array $groups = null)
    {
        global $database, $kga;

        $customerID = $database->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $p          = $kga['server_prefix'];

        if ($kga['conf']['flip_project_display']) {
            $sort = "customerName, name";
        }
        else {
            $sort = "name, customerName";
        }

        if ($groups === null) {
            $query = "SELECT project.*, customer.name AS customerName
                  FROM ${p}project AS project
                  JOIN ${p}customer AS customer USING(customer_id)
                  WHERE customer_id = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        }
        else {
            $query = "SELECT DISTINCT project.*, customer.name AS customerName
                  FROM ${p}project AS project
                  JOIN ${p}customer AS customer USING(customer_id)
                  JOIN ${p}group_project USING(project_id)
                  WHERE ${p}group_project.group_id  IN (" . implode($groups, ',') . ")
                    AND customer_id = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        }

        $database->Query($query);

        $arr = array();
        $i   = 0;

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row                       = $database->Row();
            $arr[ $i ]['project_id']    = $row->project_id;
            $arr[ $i ]['name']         = $row->name;
            $arr[ $i ]['customerName'] = $row->customerName;
            $arr[ $i ]['customer_id']   = $row->customer_id;
            $arr[ $i ]['visible']      = $row->visible;
            $arr[ $i ]['budget']       = $row->budget;
            $arr[ $i ]['effort']       = $row->effort;
            $arr[ $i ]['approved']     = $row->approved;
            $i++;
        }

        return $arr;
    }

    /**
     * Read rate from database.
     *
     * @param integer $userID
     * @param integer $projectID
     * @param integer $activityID
     *
     * @return bool|string
     */
    public function get_rate($userID, $projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";

        $p = $kga['server_prefix'];
        $U   = (($userID == "NULL") ? "user_id is NULL" : "user_id = $userID");
        $P   = (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID");
        $A   = (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");

        $query = "SELECT rate
                    FROM ${p}rate
                    WHERE ${U}
                        AND ${P}
                        AND ${A}";

        $database->Query($query);

        if ($database->RowCount() == 0) {
            return false;
        }

        $data = $database->rowArray(0, MYSQL_ASSOC);

        return $data['rate'];
    }

    /**
     * returns the key for the session of a specific user
     *
     * the key is both stored in the database (users table) and a cookie on the client.
     * when the keys match the user is allowed to access the Kimai GUI.
     * match test is performed via public function userCheck()
     *
     * @param integer $user ID of user in table users
     *
     * @return string
     * @author th
     */
    public function get_seq($user)
    {
        global $database;

        if (strncmp($user, 'customer_', 9) == 0) {
            $filter['name']  = $database->SQLValue(substr($user, 9));
            $filter['trash'] = 0;
            $table           = TBL_CUSTOMER;
        }
        else {
            $filter['name']  = $database->SQLValue($user);
            $filter['trash'] = 0;
            $table           = TBL_USER;
        }

        $columns[] = "secure";

        $result = $database->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('get_seq');

            return false;
        }

        $row = $database->RowArray(0, MYSQL_ASSOC);

        return $row['secure'];
    }

    /**
     * return status names
     *
     * @param integer $statusIds
     */
    public function get_status($statusIds)
    {
        global $database, $kga;

        $p         = $kga['server_prefix'];
        $statusIds = implode(',', $statusIds);
        $query     = "SELECT `status` FROM ${p}status where status_id in ( $statusIds ) order by status_id";
        $result    = $database->Query($query);
        if ($result == false) {
            $this->logLastError('get_status');

            return false;
        }

        $res  = array();
        $rows = $database->RecordsArray(MYSQL_ASSOC);
        foreach ($rows as $row) {
            $res[] = $row['status'];
        }

        return $res;
    }

    /**
     * returns array of all status with the status id as key
     *
     * @return array
     * @author mo
     */
    public function get_statuses()
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT * FROM ${p}status
                    ORDER BY status;";
        $database->Query($query);

        $arr = array();
        $i   = 0;

        $database->MoveFirst();
        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($rows === false) {
            return array();
        }

        foreach ($rows as $row) {
            $arr[]                            = $row;
            $arr[ $i ]['timeSheetEntryCount'] = $this->status_timeSheetEntryCount($row['status_id']);
            $i++;
        }

        return $arr;
    }

    /**
     * returns timesheet for specific user as multidimensional array
     *
     * @TODO   : needs new comments
     *
     * @param integer $start         start of timeframe in unix seconds
     * @param integer $end           end of timeframe in unix seconds
     * @param array   $users         ID of user in table users
     * @param array   $customers
     * @param array   $projects
     * @param array   $activities
     * @param boolean $limit
     * @param boolean $reverse_order
     * @param integer $filterCleared where -1 (default) means no filtering, 0 means only not cleared entries, 1 means
     *                               only cleared entries
     * @param integer $startRows
     * @param integer $limitRows
     * @param boolean $countOnly
     *
     * @return array
     */
    public function get_timeSheet($start, $end, $users = null, $customers = null, $projects = null, $activities = null,
                                  $limit = false, $reverse_order = false, $filterCleared = null, $startRows = 0,
                                  $limitRows = 0, $countOnly = false)
    {
        global $database, $kga;

        if (!is_numeric($filterCleared)) {
            $filterCleared = $kga['conf']['hideClearedEntries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start         = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end           = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);
        $filterCleared = $database->SQLValue($filterCleared, MySQL::SQLVALUE_NUMBER);
        $limit         = $database->SQLValue($limit, MySQL::SQLVALUE_BOOLEAN);

        $p = $kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if (isset($kga['customer'])) {
            $whereClauses[] = "project.internal = 0";
        }

        if ($start) {
            $whereClauses[] = "(end > $start || end = 0)";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }
        if ($filterCleared > -1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        if ($limit) {
            if (!empty($limitRows)) {
                $startRows = (int)$startRows;
                $limit     = "LIMIT $startRows, $limitRows";
            }
            else {
                if (isset($kga['conf']['rowlimit'])) {
                    $limit = "LIMIT " . $kga['conf']['rowlimit'];
                }
                else {
                    $limit = "LIMIT 100";
                }
            }
        }
        else {
            $limit = "";
        }


        $select = "SELECT timeSheet.*, status.status, customer.name AS customerName, customer.customer_id as customer_id, activity.name AS activityName,
                        project.name AS projectName, project.comment AS projectComment, user.name AS userName, user.alias AS userAlias ";

        if ($countOnly) {
            $select = "SELECT COUNT(*) AS total";
            $limit  = "";
        }

        $query = "$select
                FROM ${p}timesheet AS timeSheet
                Join ${p}project AS project USING (project_id)
                Join ${p}customer AS customer USING (customer_id)
                Join ${p}user AS user USING(user_id)
                Join ${p}status AS status USING(status_id)
                Join ${p}activity AS activity USING(activity_id) "
            . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) .
            ' ORDER BY start ' . ($reverse_order ? 'ASC ' : 'DESC ') . $limit . ';';

        $result = $database->Query($query);

        //DEBUG// error_log('<<================================== QUERY TIMESHEET ==================================>');
        //DEBUG// error_log('<<== QUERY ==>>'.__FUNCTION__.'====' . PHP_EOL .$query);

        if ($result === false) {
            $this->logLastError('get_timeSheet');
        }

        if ($countOnly) {
            $database->MoveFirst();
            $row = $database->Row();

            return $row->total;
        }

        $i   = 0;
        $arr = array();

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row                      = $database->Row();
            $arr[ $i ]['time_entry_id'] = $row->time_entry_id;

            // Start time should not be less than the selected start time. This would confuse the user.
            if ($start && $row->start <= $start) {
                $arr[ $i ]['start'] = $start;
            }
            else {
                $arr[ $i ]['start'] = $row->start;
            }

            // End time should not be less than the selected start time. This would confuse the user.
            if ($end && $row->end >= $end) {
                $arr[ $i ]['end'] = $end;
            }
            else {
                $arr[ $i ]['end'] = $row->end;
            }

            if ($row->end != 0) {
                // only calculate time after recording is complete
                $arr[ $i ]['duration']          = $arr[ $i ]['end'] - $arr[ $i ]['start'];
                $arr[ $i ]['formattedDuration'] = Format::formatDuration($arr[ $i ]['duration']);
                $arr[ $i ]['wage_decimal']      = $arr[ $i ]['duration'] / 3600 * $row->rate;
                $arr[ $i ]['wage']              = sprintf("%01.2f", $arr[ $i ]['wage_decimal']);
            }
            else {
                $arr[ $i ]['duration']          = null;
                $arr[ $i ]['formattedDuration'] = null;
                $arr[ $i ]['wage_decimal']      = null;
                $arr[ $i ]['wage']              = null;
            }
            $arr[ $i ]['budget']         = $row->budget;
            $arr[ $i ]['approved']       = $row->approved;
            $arr[ $i ]['rate']           = $row->rate;
            $arr[ $i ]['project_id']      = $row->project_id;
            $arr[ $i ]['activity_id']     = $row->activity_id;
            $arr[ $i ]['user_id']         = $row->user_id;
            $arr[ $i ]['project_id']      = $row->project_id;
            $arr[ $i ]['customerName']   = $row->customerName;
            $arr[ $i ]['customer_id']     = $row->customer_id;
            $arr[ $i ]['activityName']   = $row->activityName;
            $arr[ $i ]['projectName']    = $row->projectName;
            $arr[ $i ]['projectComment'] = $row->projectComment;
            $arr[ $i ]['location']       = $row->location;
            $arr[ $i ]['tracking_number'] = $row->tracking_number;
            $arr[ $i ]['status_id']       = $row->status_id;
            $arr[ $i ]['status']         = $row->status;
            $arr[ $i ]['billable']       = $row->billable;
            $arr[ $i ]['description']    = $row->description;
            $arr[ $i ]['comment']        = $row->comment;
            $arr[ $i ]['cleared']        = $row->cleared;
            $arr[ $i ]['comment_type']    = $row->comment_type;
            $arr[ $i ]['userAlias']      = $row->userAlias;
            $arr[ $i ]['userName']       = $row->userName;
            $i++;
        }

        return $arr;
    }

    /**
     * returns list of time summary attached to activity ID's within specific timeframe as array
     *
     * @param integer $start    start time in unix seconds
     * @param integer $end      end time in unix seconds
     * @param integer $user     filter for only this ID of auser
     * @param integer $customer filter for only this ID of a customer
     * @param integer $project  filter for only this ID of a project
     *
     * @return array
     * @author sl
     */
    public function get_time_activities($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        global $database, $kga;

        $start = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}activity.trash = 0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT `start`, `end`, activity_id, (`end` - `start`) / 3600 * rate AS costs
          FROM ${p}timesheet
          Left Join ${p}activity USING(activity_id)
          Left Join ${p}project USING(project_id)
          Left Join ${p}customer USING(customer_id) " .
            (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $database->Query($query);
        if (!$result) {
            $this->logLastError('get_time_activities');

            return array();
        }
        $rows = $database->RecordsArray(MYSQL_ASSOC);
        if (!$rows) return array();

        $arr             = array();
        $consideredStart = 0;
        $consideredEnd   = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd   = $row['end'];
            }
            else {
                if ($row['start'] <= $start && $row['end'] >= $end) {
                    $consideredStart = $start;
                    $consideredEnd   = $end;
                }
                else {
                    if ($row['start'] > $start && $row['end'] < $end) {
                        $consideredStart = $row['start'];
                        $consideredEnd   = $row['end'];
                    }
                    else {
                        if ($row['start'] > $start && $row['end'] >= $end) {
                            $consideredStart = $row['start'];
                            $consideredEnd   = $end;
                        }
                    }
                }
            }

            if (isset($arr[ $row['activity_id'] ])) {
                $arr[ $row['activity_id'] ]['time'] += (int)($consideredEnd - $consideredStart);
                $arr[ $row['activity_id'] ]['costs'] += (double)$row['costs'];
            }
            else {
                $arr[ $row['activity_id'] ]['time']  = (int)($consideredEnd - $consideredStart);
                $arr[ $row['activity_id'] ]['costs'] = (double)$row['costs'];
            }
        }

        return $arr;
    }

    /**
     * returns list of time summary attached to customer ID's within specific timeframe as array
     *
     * @param integer $start    start of timeframe in unix seconds
     * @param integer $end      end of timeframe in unix seconds
     * @param integer $user     filter for only this ID of auser
     * @param integer $customer filter for only this ID of a customer
     * @param integer $project  filter for only this ID of a project
     *
     * @return array
     * @author sl
     */
    public function get_time_customers($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        global $database, $kga;

        $start = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}customer.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }


        $query = "SELECT `start`,`end`, customer_id, (`end` - `start`) / 3600 * rate AS costs
              FROM ${p}timesheet
              Left Join ${p}project USING(project_id)
              Left Join ${p}customer USING(customer_id) " .
            (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $database->Query($query);
        if (!$result) {
            $this->logLastError('get_time_customers');

            return array();
        }
        $rows = $database->RecordsArray(MYSQL_ASSOC);
        if (!$rows) return array();

        $arr             = array();
        $consideredStart = 0;
        $consideredEnd   = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd   = $row['end'];
            }
            else {
                if ($row['start'] <= $start && $row['end'] >= $end) {
                    $consideredStart = $start;
                    $consideredEnd   = $end;
                }
                else {
                    if ($row['start'] > $start && $row['end'] < $end) {
                        $consideredStart = $row['start'];
                        $consideredEnd   = $row['end'];
                    }
                    else {
                        if ($row['start'] > $start && $row['end'] >= $end) {
                            $consideredStart = $row['start'];
                            $consideredEnd   = $end;
                        }
                    }
                }
            }

            if (isset($arr[ $row['customer_id'] ])) {
                $arr[ $row['customer_id'] ]['time'] += (int)($consideredEnd - $consideredStart);
                $arr[ $row['customer_id'] ]['costs'] += (double)$row['costs'];
            }
            else {
                $arr[ $row['customer_id'] ]['time']  = (int)($consideredEnd - $consideredStart);
                $arr[ $row['customer_id'] ]['costs'] = (double)$row['costs'];
            }
        }

        return $arr;
    }

    /**
     * returns list of time summary attached to project ID's within specific timeframe as array
     *
     * @param integer $start    start time in unix seconds
     * @param integer $end      end time in unix seconds
     * @param integer $user     filter for only this ID of auser
     * @param integer $customer filter for only this ID of a customer
     * @param integer $project  filter for only this ID of a project
     *
     * @return array
     * @author sl
     */
    public function get_time_projects($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        global $database, $kga;

        $start = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}project.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT `start`, `end` ,project_id, (`end` - `start`) / 3600 * rate AS costs
          FROM ${p}timesheet
          Left Join ${p}project USING(project_id)
          Left Join ${p}customer USING(customer_id) " .
            (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $database->Query($query);
        if (!$result) {
            $this->logLastError('get_time_projects');

            return array();
        }
        $rows = $database->RecordsArray(MYSQL_ASSOC);
        if (!$rows) return array();

        $arr             = array();
        $consideredStart = 0;
        $consideredEnd   = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd   = $row['end'];
            }
            else {
                if ($row['start'] <= $start && $row['end'] >= $end) {
                    $consideredStart = $start;
                    $consideredEnd   = $end;
                }
                else {
                    if ($row['start'] > $start && $row['end'] < $end) {
                        $consideredStart = $row['start'];
                        $consideredEnd   = $row['end'];
                    }
                    else {
                        if ($row['start'] > $start && $row['end'] >= $end) {
                            $consideredStart = $row['start'];
                            $consideredEnd   = $end;
                        }
                    }
                }
            }

            if (isset($arr[ $row['project_id'] ])) {
                $arr[ $row['project_id'] ]['time'] += (int)($consideredEnd - $consideredStart);
                $arr[ $row['project_id'] ]['costs'] += (double)$row['costs'];
            }
            else {
                $arr[ $row['project_id'] ]['time']  = (int)($consideredEnd - $consideredStart);
                $arr[ $row['project_id'] ]['costs'] = (double)$row['costs'];
            }
        }

        return $arr;
    }

    /**
     * returns assoc. array where the index is the ID of a user and the value the time
     * this user has accumulated in the given time with respect to the filtersettings
     *
     * @param integer $start    from this timestamp
     * @param integer $end      to this  timestamp
     * @param integer $user     ID of user in table users
     * @param integer $customer ID of customer in table customers
     * @param integer $project  ID of project in table projects
     *
     * @return array
     * @author sl
     */
    public function get_time_users($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
    {
        global $database, $kga;

        $start = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "`${p}user`.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query  = "SELECT `start`, `end`, `user_id`, (`end` - `start`) / 3600 * `rate` AS costs
              FROM ${p}timesheet
              Join ${p}project USING(project_id)
              Join ${p}customer USING(customer_id)
              Join `${p}user` USING(user_id)
              Join ${p}activity USING(activity_id) "
            . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) . " ORDER BY `start` DESC;";
        $result = $database->Query($query);

        if (!$result) {
            $this->logLastError('get_time_users');

            return array();
        }

        $rows = $database->RecordsArray(MYSQL_ASSOC);
        if (!$rows) return array();

        $arr             = array();
        $consideredStart = 0;
        $consideredEnd   = 0;
        foreach ($rows as $row) {
            if ($row['start'] <= $start && $row['end'] < $end) {
                $consideredStart = $start;
                $consideredEnd   = $row['end'];
            }
            else {
                if ($row['start'] <= $start && $row['end'] >= $end) {
                    $consideredStart = $start;
                    $consideredEnd   = $end;
                }
                else {
                    if ($row['start'] > $start && $row['end'] < $end) {
                        $consideredStart = $row['start'];
                        $consideredEnd   = $row['end'];
                    }
                    else {
                        if ($row['start'] > $start && $row['end'] >= $end) {
                            $consideredStart = $row['start'];
                            $consideredEnd   = $end;
                        }
                    }
                }
            }

            if (isset($arr[ $row['user_id'] ])) {
                $arr[ $row['user_id'] ]['time'] += (int)($consideredEnd - $consideredStart);
                $arr[ $row['user_id'] ]['costs'] += (double)$row['costs'];
            }
            else {
                $arr[ $row['user_id'] ]['time']  = (int)($consideredEnd - $consideredStart);
                $arr[ $row['user_id'] ]['costs'] = (double)$row['costs'];
            }
        }

        return $arr;
    }

    /**
     * write details of a specific user into $this->kga
     *
     * @param integer $user ID of user in table users
     *
     * @return array $this->kga
     * @author th
     *
     */
    public function get_user_config($user)
    {
        global $database, $kga;

        if (!$user) return;

        $filter['user_id'] = $database->SQLValue($user, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "active";
        $columns[] = "apikey";
        $columns[] = "ban";
        $columns[] = "ban_time";
        $columns[] = "global_role_id";
        $columns[] = "last_activity";
        $columns[] = "last_project";
        $columns[] = "last_record";
        $columns[] = "mail";
        $columns[] = "name";
        $columns[] = "password";
        $columns[] = "secure";
        $columns[] = "timeframe_begin";
        $columns[] = "timeframe_end";
        $columns[] = "trash";
        $columns[] = "user_id";

        $database->SelectRows(TBL_USER, $filter, $columns);
        $rows = $database->RowArray(0, MYSQL_ASSOC);
        foreach ($rows as $key => $value) {
            $kga['user'][ $key ] = $value;
        }

        $kga['user']['groups'] = $this->getGroupMemberships($user);

        // get values from user configuration (user-preferences)
        unset($columns);
        unset($filter);

        $kga['conf'] = array_merge($kga['conf'], $this->user_get_preferences_by_prefix('ui.'));
        $userTimezone      = $this->user_get_preference('timezone');
        if ($userTimezone != '') {
            $kga['timezone'] = $userTimezone;
        }
        else {
            $kga['timezone'] = $kga['defaultTimezone'];
        }

        date_default_timezone_set($kga['timezone']);
    }

    /**
     * returns list of users the given user can watch
     *
     * @param integer $user ID of user in table users
     *
     * @return array
     * @author sl
     */
    public function get_user_watchable_users($user)
    {
        global $database, $kga;

        $userID = $database->SQLValue($user['user_id'], MySQL::SQLVALUE_NUMBER);
        $p      = $kga['server_prefix'];
        $that   = $this;

        //DEBUG// error_log('<<== QUERY ==>>'.__FUNCTION__);
        if ($this->global_role_allows($user['global_role_id'], 'core-user-otherGroup-view')) {
            // If user may see other groups we need to filter out groups he's part of but has no permission to see users in.
            $forbidden_groups = array_filter($user['groups'], function ($groupID) use ($userID, $that) {
                $roleID = $that->user_get_membership_role($userID, $groupID);

                return !$that->membership_role_allows($roleID, 'core-user-view');
            });

            $group_filter = "";
            if (count($forbidden_groups) > 0) {
                $group_filter = " AND count(SELECT * FROM ${p}group_user AS p WHERE u.`user_id` = p.`user_id` AND `group_id` NOT IN (" . implode(', ', $forbidden_groups) . ")) > 0";
            }

            $query = "SELECT * FROM ${p}user AS u WHERE trash=0 $group_filter ORDER BY name";
            $database->Query($query);

            //DEBUG// error_log('<<== QUERY ==>>'.__FUNCTION__.'====' . PHP_EOL .$query);

            return $database->RecordsArray(MYSQL_ASSOC);
        }

        $allowed_groups = array_filter($user['groups'], function ($groupID) use ($userID, $that) {
            $roleID = $that->user_get_membership_role($userID, $groupID);

            return $that->membership_role_allows($roleID, 'core-user-view');
        });

        return $this->get_users(0, $allowed_groups);
    }

    /**
     * returns array of all users
     *
     * [userID] => 23103741
     * [name] => admin
     * [mail] => 0
     * [active] => 0
     *
     *
     * @param array $groups list of group ids the users must be a member of
     *
     * @return array
     * @author th
     */
    public function get_users($trash = 0, array $groups = null)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];


        $trash = $database->SQLValue($trash, MySQL::SQLVALUE_NUMBER);

        if ($groups === null) {
            $query = "SELECT * FROM ${p}user
        WHERE trash = $trash
        ORDER BY name ;";
        }
        else {
            $query = "SELECT DISTINCT u.* FROM ${p}user AS u
         JOIN ${p}group_user AS g_u USING(user_id)
        WHERE g_u.group_id IN (" . implode($groups, ',') . ") AND
         trash = $trash
        ORDER BY name ;";
        }
        $database->Query($query);

        $database->RowArray(0, MYSQL_ASSOC);

        $i   = 0;
        $arr = array();

        $database->MoveFirst();
        while (!$database->EndOfSeek()) {
            $row                       = $database->Row();
            $arr[ $i ]['user_id']       = $row->user_id;
            $arr[ $i ]['name']         = $row->name;
            $arr[ $i ]['global_role_id'] = $row->global_role_id;
            $arr[ $i ]['mail']         = $row->mail;
            $arr[ $i ]['active']       = $row->active;
            $arr[ $i ]['trash']        = $row->trash;

            if ($row->password != '' && $row->password != '0') {
                $arr[ $i ]['passwordSet'] = "yes";
            }
            else {
                $arr[ $i ]['passwordSet'] = "no";
            }
            $i++;
        }

        return $arr;
    }

    /**
     * returns the date of the first timerecord of a user (when did the user join?)
     * this is needed for the datepicker
     *
     * @param integer $id of user
     *
     * @return integer unix seconds of first timesheet record
     * @author th
     */
    public function getjointime($userID)
    {
        global $database, $kga;

        $userID = $database->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $p      = $kga['server_prefix'];

        $query = "SELECT start FROM ${p}timesheet WHERE user_id = $userID ORDER BY start ASC LIMIT 1;";

        $result = $database->Query($query);
        if ($result == false) {
            $this->logLastError('getjointime');

            return false;
        }

        $result_array = $database->RowArray(0, MYSQL_NUM);

        if ($result_array[0] == 0) {
            return mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        }
        else {
            return $result_array[0];
        }
    }

    public function globalRole_find($filter)
    {
        global $database;

        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = $database->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
            else {
                $value = $database->SQLValue($value);
            }
        }
        $result = $database->SelectRows(TBL_GLOBAL_ROLE, $filter);

        if (!$result) {
            $this->logLastError('globalRole_find');

            return false;
        }
        else {
            return $database->RecordsArray(MYSQL_ASSOC);
        }
    }

    public function globalRole_get_data($globalRoleID)
    {
        global $database;

        $filter['global_role_id'] = $database->SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $result                 = $database->SelectRows(TBL_GLOBAL_ROLE, $filter);

        if (!$result) {
            $this->logLastError('globalRole_get_data');

            return false;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     * Check if a global role gives permission for a specific action.
     *
     * @param integer the ID of the global role
     * @param string  name of the action / permission
     *
     * @return bool true if permissions is granted, false otherwise
     */
    public function global_role_allows($roleID, $permission)
    {
        global $database;

        $filter['global_role_id'] = $database->SQLValue($roleID, MySQL::SQLVALUE_NUMBER);
        $filter[ $permission ]  = 1;
        $columns[]              = "global_role_id";

        $result = $database->SelectRows(TBL_GLOBAL_ROLE, $filter, $columns);

        if ($result === false) {
            $this->logLastError('global_role_allows');

            return false;
        }

        $result = $database->RowCount() > 0;

        Logger::logfile("Global role $roleID gave " . ($result ? 'true' : 'false') . " for $permission.");

        return $result;
    }

    public function global_role_create($data)
    {
        global $database;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[ $key ] = $database->SQLValue($value);
            }
            else {
                $values[ $key ] = $database->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $result = $database->InsertRow(TBL_GLOBAL_ROLE, $values);

        if (!$result) {
            $this->logLastError('global_role_create');

            return false;
        }

        return $database->GetLastInsertID();
    }

    public function global_role_delete($globalRoleID)
    {
        global $database;

        $filter['global_role_id'] = $database->SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $query                  = MySQL::BuildSQLDelete(TBL_GLOBAL_ROLE, $filter);
        $result                 = $database->Query($query);

        if ($result == false) {
            $this->logLastError('global_role_delete');

            return false;
        }

        return true;
    }

    public function global_role_edit($globalRoleID, $data)
    {
        global $database;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[ $key ] = $database->SQLValue($value);
            }
            else {
                $values[ $key ] = $database->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['global_role_id'] = $database->SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_GLOBAL_ROLE, $values, $filter);

        $result = $database->Query($query);

        if ($result == false) {
            $this->logLastError('global_role_edit');

            return false;
        }

        return true;
    }

    public function global_roles()
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT a.*, COUNT(b.global_role_id) AS count_users FROM `${p}global_role` a LEFT JOIN `${p}user` b USING(global_role_id) GROUP BY a.global_role_id";

        $result = $database->Query($query);

        if ($result == false) {
            $this->logLastError('global_roles');

            return false;
        }

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        return $rows;
    }

    /**
     * Returns the number of users in a certain group
     *
     * @param array $groupID groupID of the group
     *
     * @return int            the number of users in the group
     * @author th
     */
    public function group_count_users($groupID)
    {
        global $database;

        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $result            = $database->SelectRows(TBL_GROUP_USER, $filter);

        if (!$result) {
            $this->logLastError('group_count_data');

            return false;
        }

        return $database->RowCount() === false ? 0 : $database->RowCount();
    }

    /**
     * Adds a new group
     *
     * @param array $data name and other data of the new group
     *
     * @return int         the groupID of the new group, false on failure
     * @author th
     */
    public function group_create($data)
    {
        global $database;

        $data = $this->clean_data($data);

        $values ['name'] = $database->SQLValue($data ['name']);
        $result          = $database->InsertRow(TBL_GROUP, $values);

        if (!$result) {
            $this->logLastError('group_create');

            return false;
        }
        else {
            return $database->GetLastInsertID();
        }
    }

    ## Load into Array: Activities

    /**
     * deletes a group
     *
     * @param array $groupID groupID of the group
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function group_delete($groupID)
    {
        global $database;

        $values['trash']   = 1;
        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $query             = $database->BuildSQLUpdate(TBL_GROUP, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Edits a group by replacing its data by the new array
     *
     * @param array $groupID groupID of the group to be edited
     * @param array $data    name and other new data of the group
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function group_edit($groupID, $data)
    {
        global $database;

        $data = $this->clean_data($data);

        $values ['name'] = $database->SQLValue($data ['name']);

        $filter ['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_GROUP, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Returns the data of a certain group
     *
     * @param array $groupID groupID of the group
     *
     * @return array         the group's data (name, etc) as array, false on failure
     * @author th
     */
    public function group_get_data($groupID)
    {
        global $database;

        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $result            = $database->SelectRows(TBL_GROUP, $filter);

        if (!$result) {
            $this->logLastError('group_get_data');

            return false;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    public function isConnected()
    {
        return $this->IsConnected();
    }

    /**
     * checks if given $activityId exists in the db
     *
     * @param int $activityId
     *
     * @return bool
     */
    public function isValidActivityId($activityId)
    {
        $filter = array('activity_id' => $activityId, 'trash' => 0);

        return $this->rowExists(TBL_ACTIVITY, $filter);
    }

    /**
     * checks if given $projectId exists in the db
     *
     * @param int $projectId
     *
     * @return bool
     */
    public function isValidProjectId($projectId)
    {
        $filter = array('project_id' => $projectId, 'trash' => 0);

        return $this->rowExists(TBL_PROJECT, $filter);
    }

    /**
     * checks if a customer with this name exists
     *
     * @param string name
     *
     * @return integer
     * @author sl
     */
    public function is_customer_name($name)
    {
        global $database, $kga;

        $name = $database->SQLValue($name);
        $p    = $kga['server_prefix'];

        $query = "SELECT customer_id FROM ${p}customer WHERE name = $name AND trash = 0";

        $database->Query($query);

        return $database->RowCount() > 0;
    }

    /**
     * Checks if a user (given by user ID) can be accessed by another user (given by user array):
     *
     * @see    get_watchable_users
     *
     * @param integer $user   user to check for
     * @param integer $userID user to check if watchable
     *
     * @return true if watchable, false otherwiese
     * @author sl
     */
    public function is_watchable_user($user, $userID)
    {
        global $database;

        $userID = $database->SQLValue($user['user_id'], MySQL::SQLVALUE_NUMBER);

        $watchableUsers = $this->get_watchable_users($user);
        foreach ($watchableUsers as $watchableUser) {
            if ($watchableUser['user_id'] == $userID) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the ban status of a user. This increments the ban counter.
     * Optionally it sets the start time of the ban to the current time.
     *
     * @author sl
     */
    public function loginUpdateBan($userId, $resetTime = false)
    {
        global $database;


        $filter ['user_id'] = $database->SQLValue($userId);

        $values ['ban'] = "ban+1";
        if ($resetTime) {
            $values ['ban_time'] = $database->SQLValue(time(), MySQL::SQLVALUE_NUMBER);
        }

        $query = $database->BuildSQLUpdate(TBL_USER, $values, $filter);

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('loginUpdateBan');
        }
    }

    public function membershipRole_find($filter)
    {
        global $database;

        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = $database->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
            else {
                $value = $database->SQLValue($value);
            }
        }
        $result = $database->SelectRows(TBL_MEMBERSHIP_ROLE, $filter);

        if (!$result) {
            $this->logLastError('membershipRole_find');

            return false;
        }
        else {
            return $database->RecordsArray(MYSQL_ASSOC);
        }
    }

    public function membershipRole_get_data($membershipRoleID)
    {
        global $database;

        $filter['membership_role_id'] = $database->SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $result                     = $database->SelectRows(TBL_MEMBERSHIP_ROLE, $filter);

        if (!$result) {
            $this->logLastError('membershipRole_get_data');

            return false;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     * Check if a membership role gives permission for a specific action.
     *
     * @param integer the ID of the membership role
     * @param string  name of the action / permission
     *
     * @return bool true if permissions is granted, false otherwise
     */
    public function membership_role_allows($roleID, $permission)
    {
        global $database;

        $filter['membership_role_id'] = $database->SQLValue($roleID, MySQL::SQLVALUE_NUMBER);
        $filter[ $permission ]      = 1;
        $columns[]                  = "membership_role_id";

        $result = $database->SelectRows(TBL_MEMBERSHIP_ROLE, $filter, $columns);

        if ($result === false) {
            return false;
        }

        return $database->RowCount() > 0;
    }

    public function membership_role_create($data)
    {
        global $database;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[ $key ] = $database->SQLValue($value);
            }
            else {
                $values[ $key ] = $database->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $result = $database->InsertRow(TBL_MEMBERSHIP_ROLE, $values);

        if (!$result) {
            $this->logLastError('membership_role_create');

            return false;
        }

        return $database->GetLastInsertID();
    }

    public function membership_role_delete($membershipRoleID)
    {
        global $database;

        $filter['membership_role_id'] = $database->SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $query                      = $database->BuildSQLDelete(TBL_MEMBERSHIP_ROLE, $filter);
        $result                     = $database->Query($query);

        if ($result == false) {
            $this->logLastError('membership_role_delete');

            return false;
        }

        return true;
    }

    public function membership_role_edit($membershipRoleID, $data)
    {
        global $database;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[ $key ] = $database->SQLValue($value);
            }
            else {
                $values[ $key ] = $database->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['membership_role_id'] = $database->SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_MEMBERSHIP_ROLE, $values, $filter);

        return $database->Query($query);
    }

    public function membership_roles()
    {
        global $database, $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT a.*, COUNT(DISTINCT b.user_id) as count_users FROM ${p}membership_role a LEFT JOIN ${p}group_user b USING(membership_role_id) GROUP BY a.membership_role_id";

        $result = $database->Query($query);

        if ($result == false) {
            $this->logLastError('membership_roles');

            return false;
        }

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        return $rows;
    }

    public function preferences_user_exists($userId = null)
    {
        global $database, $kga;

        if ($userId === null) {
            $userId = $kga['user']['user_id'];
        }

        $table  = TBL_PREFERENCE;
        $userId = $database->SQLValue($userId, MySQL::SQLVALUE_NUMBER);

        $query = "SELECT * FROM $table WHERE user_id = $userId LIMIT 1";

        $database->Query($query);

        if ($database->RowCount() == 0) {
            return false;
        }

        return true;
    }

    /**
     *
     * update the data for activity per project, which is budget, approved and effort
     *
     * @param integer $projectID
     * @param integer $activityID
     * @param array   $data
     */
    public function project_activity_edit($projectID, $activityID, $data)
    {
        global $database;

        $data = $this->clean_data($data);

        $filter['project_id']  = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $filter['activity_id'] = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);

        if (!$database->TransactionBegin()) {
            $this->logLastError('project_activity_edit [1]');

            return false;
        }

        $query = $database->BuildSQLUpdate(TBL_PROJECT_ACTIVITY, $data, $filter);
        if ($database->Query($query)) {
            if (!$database->TransactionEnd()) {
                $this->logLastError('project_activity_edit [2]');

                return false;
            }

            return true;
        }

        $this->logLastError('project_activity_edit [3]');

        if (!$database->TransactionRollback()) {
            $this->logLastError('project_activity_edit [4]');

            return false;
        }

        return false;
    }

    /**
     * Adds a new project
     *
     * @param array $data `name`, comment and other data of the new project
     *
     * @return int         the ID of the new project, false on failure
     * @author th
     */
    public function project_create($data)
    {
        global $database, $kga;

        $data = $this->clean_data($data);

        $values['name']       = $database->SQLValue($data['name']);
        $values['comment']    = $database->SQLValue($data['comment']);
        $values['budget']     = $database->SQLValue($data['budget'], MySQL::SQLVALUE_NUMBER);
        $values['effort']     = $database->SQLValue($data['effort'], MySQL::SQLVALUE_NUMBER);
        $values['approved']   = $database->SQLValue($data['approved'], MySQL::SQLVALUE_NUMBER);
        $values['customer_id'] = $database->SQLValue($data['customer_id'], MySQL::SQLVALUE_NUMBER);
        $values['visible']    = $database->SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['internal']   = $database->SQLValue($data['internal'], MySQL::SQLVALUE_NUMBER);
        $values['filter']     = $database->SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $result = $database->InsertRow(TBL_PROJECT, $values);

        if (!$result) {
            $this->logLastError('project_create');

            return false;
        }

        $projectID = $database->GetLastInsertID();

        if (isset($data['defaultRate'])) {
            if (is_numeric($data['defaultRate'])) {
                $this->save_rate(null, $projectID, null, $data['defaultRate']);
            }
            else {
                $this->remove_rate(null, $projectID, null);
            }
        }

        if (isset($data['myRate'])) {
            if (is_numeric($data['myRate'])) {
                $this->save_rate($kga['user']['user_id'], $projectID, null, $data['myRate']);
            }
            else {
                $this->remove_rate($kga['user']['user_id'], $projectID, null);
            }
        }

        if (isset($data['fixed_rate'])) {
            if (is_numeric($data['fixed_rate'])) {
                $this->save_fixed_rate($projectID, null, $data['fixed_rate']);
            }
            else {
                $this->remove_fixed_rate($projectID, null);
            }
        }

        return $projectID;
    }

    /**
     * deletes a project
     *
     * @param array $projectID ID of the project
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function project_delete($projectID)
    {
        global $database;

        $values['trash']     = 1;
        $filter['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_PROJECT, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Edits a project by replacing its data by the new array
     *
     * @param int   $projectID id of the project to be edited
     * @param array $data      `name`, comment and other new data of the project
     *
     * @return boolean        true on success, false on failure
     * @author ob/th
     */
    public function project_edit($projectID, $data)
    {
        global $database, $kga;

        $data = $this->clean_data($data);
        $values = array();

        $strings = array('name', 'comment');
        foreach ($strings as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ]);
            }
        }

        $numbers = array(
            'budget', 'customer_id', 'visible', 'internal', 'filter', 'effort', 'approved');
        foreach ($numbers as $key) {
            if (!isset($data[ $key ])) {
                continue;
            }

            if ($data[ $key ] == null) {
                $values[ $key ] = 'NULL';
            }
            else {
                $values[ $key ] = $database->SQLValue($data[ $key ], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter ['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);


        if (!$database->TransactionBegin()) {
            $this->logLastError('project_edit');

            return false;
        }

        $query = $database->BuildSQLUpdate(TBL_PROJECT, $values, $filter);

        if ($database->Query($query)) {

            if (isset($data['defaultRate'])) {
                if (is_numeric($data['defaultRate'])) {
                    $this->save_rate(null, $projectID, null, $data['defaultRate']);
                }
                else {
                    $this->remove_rate(null, $projectID, null);
                }
            }

            if (isset($data['myRate'])) {
                if (is_numeric($data['myRate'])) {
                    $this->save_rate($kga['user']['user_id'], $projectID, null, $data['myRate']);
                }
                else {
                    $this->remove_rate($kga['user']['user_id'], $projectID, null);
                }
            }

            if (isset($data['fixed_rate'])) {
                if (is_numeric($data['fixed_rate'])) {
                    $this->save_fixed_rate($projectID, null, $data['fixed_rate']);
                }
                else {
                    $this->remove_fixed_rate($projectID, null);
                }
            }

            if (!$database->TransactionEnd()) {
                $this->logLastError('project_edit');

                return false;
            }

            return true;
        }
        else {
            $this->logLastError('project_edit');
            if (!$database->TransactionRollback()) {
                $this->logLastError('project_edit');

                return false;
            }

            return false;
        }
    }

    /**
     * returns all the activities which were assigned to a project
     *
     * @param integer $projectID ID of the project
     *
     * @return array         contains the activityIDs of the activities or false on error
     * @author sl
     */
    public function project_get_activities($projectID)
    {
        global $database, $kga;

        $projectId = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $p         = $kga['server_prefix'];

        $query = "SELECT activity.*, activity_id, budget, effort, approved
                FROM ${p}project_activity AS p_a
                JOIN ${p}activity AS activity USING(activity_id)
                WHERE project_id = $projectId AND activity.trash=0;";

        $result = $database->Query($query);

        if ($result == false) {
            $this->logLastError('project_get_activities');

            return false;
        }

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        return $rows;
    }

    /**
     * Returns the data of a certain project
     *
     * @param int $projectID ID of the project
     *
     * @return array         the project's data (name, comment etc) as array, false on failure
     * @author th
     */
    public function project_get_data($projectID)
    {
        global $database, $kga;

        if (!is_numeric($projectID)) {
            return false;
        }

        $filter['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $result              = $database->SelectRows(TBL_PROJECT, $filter);

        if (!$result) {
            $this->logLastError('project_get_data');

            return false;
        }

        $result_array                = $database->RowArray(0, MYSQL_ASSOC);
        $result_array['defaultRate'] = $this->get_rate(null, $projectID, null);
        $result_array['myRate']      = $this->get_rate($kga['user']['user_id'], $projectID, null);
        $result_array['fixed_rate']   = $this->get_fixed_rate($projectID, null);

        return $result_array;
    }

    /**
     * returns all the groups of the given project
     *
     * @param array $projectID ID of the project
     *
     * @return array         contains the groupIDs of the groups or false on error
     * @author th
     */
    public function project_get_groupIDs($projectID)
    {
        global $database;

        $filter['project_id'] = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $columns[]           = "group_id";

        $result = $database->SelectRows(TBL_GROUP_PROJECT, $filter, $columns);
        if ($result == false) {
            $this->logLastError('project_get_groupIDs');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $database->RecordsArray(MYSQL_ASSOC);

        if ($database->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[ $counter ] = $row['group_id'];
                $counter++;
            }

            return $groupIDs;
        }
        else {
            return false;
        }
    }

    /**
     * Return all rows for the given sql query.
     *
     * @param string $query the sql query to execute
     */
    public function queryAll($query)
    {
        global $database;

        return $database->QueryArray($query);
    }

    /**
     * Remove fixed rate from database.
     *
     * @author sl
     */
    public function remove_fixed_rate($projectID, $activityID)
    {
        global $database, $kga;

        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        $p = $kga['server_prefix'];


        $query = "DELETE FROM ${p}fixed_rate WHERE " .
            (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
            (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('remove_fixed_rate');

            return false;
        }
        else {
            return true;
        }
    }

    /*
     * Remove rate from database.
     *
     * @author sl
     */
    public function remove_rate($userID, $projectID, $activityID)
    {
        global $kga, $database;

        // validate input
        $U =  ($userID == null || !is_numeric($userID))  ? "user_id is NULL" : "user_id = $userID";
        $P =  ($projectID == null || !is_numeric($projectID)) ? "project_id is NULL" : "project_id = $projectID";
        $A =  ($activityID == null || !is_numeric($activityID))? "activity_id is NULL" : "activity_id = $activityID";
        $p = $kga['server_prefix'];

        $query = "DELETE FROM ${p}rate WHERE ${U} AND ${P} AND ${A}";

        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('remove_rate');

            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Save fixed rate to database.
     *
     * @author sl
     */
    public function save_fixed_rate($projectID, $activityID, $rate)
    {
        global $kga, $database;

        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        if (!is_numeric($rate)) return false;
        $p = $kga['server_prefix'];

        // build update or insert statement
        if ($this->get_fixed_rate($projectID, $activityID) === false) {
            $query = "INSERT INTO ${p}fixed_rate VALUES($projectID,$activityID,$rate);";
        }
        else {
            $query = "UPDATE ${p}fixed_rate SET rate = $rate WHERE " .
                (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
                (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");
        }

        $result = $database->Query($query);

        if ($result == false) {
            $this->logLastError('save_fixed_rate');

            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Save rate to database.
     *
     * @author sl
     */
    public function save_rate($userID, $projectID, $activityID, $rate)
    {
        global $database, $kga;

        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        if (!is_numeric($rate)) return false;
        $p = $kga['server_prefix'];


        // build update or insert statement
        if ($this->get_rate($userID, $projectID, $activityID) === false) {
            $query = "INSERT INTO ${p}rate VALUES($userID,$projectID,$activityID,$rate);";
        }
        else {
            $query = "UPDATE ${p}rate SET rate = $rate WHERE " .
                (($userID == "NULL") ? "user_id is NULL" : "user_id = $userID") . " AND " .
                (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
                (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");
        }

        $result = $database->Query($query);

        if ($result == false) {
            $this->logLastError('save_rate');

            return false;
        }
        else {
            return true;
        }
    }

    /**
     * saves timeframe of user in database (table conf)
     *
     * @param string $timeframeBegin unix seconds
     * @param string $timeframeEnd   unix seconds
     * @param string $user           ID of user
     *
     * @author th
     */
    public function save_timeframe($timeframeBegin, $timeframeEnd, $user)
    {
        global $database;

        if ($timeframeBegin == 0 && $timeframeEnd == 0) {
            $mon            = date("n");
            $day            = date("j");
            $Y              = date("Y");
            $timeframeBegin = mktime(0, 0, 0, $mon, $day, $Y);
            $timeframeEnd   = mktime(23, 59, 59, $mon, $day, $Y);
        }

        if ($timeframeEnd == mktime(23, 59, 59, date('n'), date('j'), date('Y'))) {
            $timeframeEnd = 0;
        }

        $values['timeframe_begin'] = $database->SQLValue($timeframeBegin, MySQL::SQLVALUE_NUMBER);
        $values['timeframe_end']   = $database->SQLValue($timeframeEnd, MySQL::SQLVALUE_NUMBER);

        $filter  ['user_id'] = $database->SQLValue($user, MySQL::SQLVALUE_NUMBER);


        $query = $database->BuildSQLUpdate(TBL_USER, $values, $filter);

        if (!$database->Query($query)) {
            $this->logLastError('save_timeframe');

            return false;
        }

        return true;
    }

    /**
     * Set the groups in which the user is a member in.
     *
     * @param int   $userId id of the user
     * @param array $groups map from group ID to membership role ID
     *
     * @return boolean       true on success, false on failure
     * @author sl
     */
    public function setGroupMemberships($userId, array $groups = null)
    {
        global $database;

        if (!$database->TransactionBegin()) {
            $this->logLastError('setGroupMemberships');

            return false;
        }

        $data ['user_id'] = $database->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $result          = $database->DeleteRows(TBL_GROUP_USER, $data);

        if (!$result) {
            $this->logLastError('setGroupMemberships');
            if (!$database->TransactionRollback()) {
                $this->logLastError('setGroupMemberships');
            }

            return false;
        }

        foreach ($groups as $group => $role) {
            $data['group_id']          = $database->SQLValue($group, MySQL::SQLVALUE_NUMBER);
            $data['membership_role_id'] = $database->SQLValue($role, MySQL::SQLVALUE_NUMBER);
            $result                   = $database->InsertRow(TBL_GROUP_USER, $data);
            if ($result === false) {
                $this->logLastError('setGroupMemberships');
                if (!$database->TransactionRollback()) {
                    $this->logLastError('setGroupMemberships');
                }

                return false;
            }
        }

        if (!$database->TransactionEnd()) {
            $this->logLastError('setGroupMemberships');

            return false;
        }
    }

    /**
     * starts timesheet record
     *
     * @param integer $projectID ID of project to record
     *
     * @return boolean|integer of the new entry or false on failure
     */
    public function startRecorder($projectID, $activityID, $user, $startTime)
    {
        global $database, $kga;

        $projectID  = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $activityID = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $user       = $database->SQLValue($user, MySQL::SQLVALUE_NUMBER);
        $startTime  = $database->SQLValue($startTime, MySQL::SQLVALUE_NUMBER);

        $values ['project_id']  = $projectID;
        $values ['activity_id'] = $activityID;
        $values ['start']      = $startTime;
        $values ['user_id']     = $user;
        $values ['status_id']   = $kga['conf']['defaultStatusID'];

        $rate = $this->get_best_fitting_rate($user, $projectID, $activityID);
        if ($rate) {
            $values ['rate'] = $rate;
        }

        $result = $database->InsertRow(TBL_TIMESHEET, $values);

        if (!$result) {
            $this->logLastError('startRecorder');

            return false;
        }

        return $database->GetLastInsertID();
    }

    /**
     * add a new status
     *
     * @param Array $status Array
     *
     * @return bool
     */
    public function status_create($status)
    {
        global $database;

        $values['status'] = $database->SQLValue(trim($status['status']));

        $result = $database->InsertRow(TBL_STATUS, $values);
        if (!$result) {
            $this->logLastError('add_status');

            return false;
        }

        return true;
    }

    /**
     * deletes a status
     *
     * @param array $statusID statusID of the status
     *
     * @return boolean         true on success, false on failure
     * @author mo
     */
    public function status_delete($statusID)
    {
        global $database;

        $filter['status_id'] = $database->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $query              = $database->BuildSQLDelete(TBL_STATUS, $filter);

        return $database->Query($query);
    }

    /**
     * Edits a status by replacing its data by the new array
     *
     * @param array $statusID groupID of the status to be edited
     * @param array $data     name and other new data of the status
     *
     * @return boolean       true on success, false on failure
     * @author mo
     */
    public function status_edit($statusID, $data)
    {
        global $database;

        $data = $this->clean_data($data);

        $values ['status'] = $database->SQLValue($data ['status']);

        $filter ['status_id'] = $database->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);

        $query = $database->BuildSQLUpdate(TBL_STATUS, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Returns the data of a certain status
     *
     * @param array $statusID ID of the group
     *
     * @return array             the group's data (name) as array, false on failure
     * @author mo
     */
    public function status_get_data($statusID)
    {
        global $database;

        $filter['status_id'] = $database->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $result             = $database->SelectRows(TBL_STATUS, $filter);

        if (!$result) {
            $this->logLastError('status_get_data');

            return false;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     * Returns the number of time sheet entries with a certain status
     *
     * @param integer $statusID ID of the status
     *
     * @return int                    the number of timesheet entries with this status
     * @author mo
     */
    public function status_timeSheetEntryCount($statusID)
    {
        global $database;

        $filter['status_id'] = $database->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $result             = $database->SelectRows(TBL_TIMESHEET, $filter);

        if (!$result) {
            $this->logLastError('status_timeSheetEntryCount');

            return false;
        }

        return $database->RowCount() === false ? 0 : $database->RowCount();
    }

    /**
     * Performed when the stop buzzer is hit.
     *
     * @param integer $id id of the entry to stop
     *
     * @author th, sl
     * @return boolean
     */
    public function stopRecorder($id)
    {
        global $database, $kga;

        ## stop running recording |

        $activity = $this->timeSheet_get_data($id);

        $filter['time_entry_id'] = $activity['time_entry_id'];
        $filter['end']         = 0; // only update running activities

        $rounded = Rounding::roundTimespan($activity['start'], (integer)time(), $kga['conf']['roundPrecision'], $kga['conf']['allowRoundDown']);

        $values['start']    = $rounded['start'];
        $values['end']      = $rounded['end'];
        $values['duration'] = $values['end'] - $values['start'];


        $query = $database->BuildSQLUpdate(TBL_TIMESHEET, $values, $filter);

        return $database->Query($query);
    }

    /**
     * create time sheet entry
     *
     * @param integer $id   ID of record
     * @param array   $data array with record data
     *
     * @author th
     */
    public function timeEntry_create($data)
    {
        global $database;

        $data = $this->clean_data($data);

        $values ['location']    = $database->SQLValue($data ['location']);
        $values ['comment']     = $database->SQLValue($data ['comment']);
        $values ['description'] = $database->SQLValue($data ['description']);
        if ($data ['tracking_number'] == '') {
            $values ['tracking_number'] = 'NULL';
        }
        else {
            $values ['tracking_number'] = $database->SQLValue($data ['tracking_number']);
        }
        $values ['user_id']      = $database->SQLValue($data ['user_id'], MySQL::SQLVALUE_NUMBER);
        $values ['project_id']   = $database->SQLValue($data ['project_id'], MySQL::SQLVALUE_NUMBER);
        $values ['activity_id']  = $database->SQLValue($data ['activity_id'], MySQL::SQLVALUE_NUMBER);
        $values ['comment_type'] = $database->SQLValue($data ['comment_type'], MySQL::SQLVALUE_NUMBER);
        $values ['start']       = $database->SQLValue($data ['start'], MySQL::SQLVALUE_NUMBER);
        $values ['end']         = $database->SQLValue($data ['end'], MySQL::SQLVALUE_NUMBER);
        $values ['duration']    = $database->SQLValue($data ['duration'], MySQL::SQLVALUE_NUMBER);
        $values ['rate']        = $database->SQLValue($data ['rate'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']     = $database->SQLValue($data ['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values ['budget']      = $database->SQLValue($data ['budget'], MySQL::SQLVALUE_NUMBER);
        $values ['approved']    = $database->SQLValue($data ['approved'], MySQL::SQLVALUE_NUMBER);
        $values ['status_id']    = $database->SQLValue($data ['status_id'], MySQL::SQLVALUE_NUMBER);
        $values ['billable']    = $database->SQLValue($data ['billable'], MySQL::SQLVALUE_NUMBER);

        $success = $database->InsertRow(TBL_TIMESHEET, $values);
        if ($success) {
            return $database->GetLastInsertID();
        }
        else {
            $this->logLastError('timeEntry_create');

            return false;
        }
    }

    /**
     * delete time sheet entry
     *
     * @param integer $id -> ID of record
     *
     * @author th
     */
    public function timeEntry_delete($id)
    {
        global $database;

        $filter["time_entry_id"] = $database->SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $query                 = $database->BuildSQLDelete(TBL_TIMESHEET, $filter);

        return $database->Query($query);
    }

    /**
     * edit time sheet entry
     *
     * @param integer $id   ID of record
     * @param array   $data array with new record data
     *
     * @author th
     */
    public function timeEntry_edit($id, Array $data)
    {
        global $database;

        $data = $this->clean_data($data);

        $original_array = $this->timeSheet_get_data($id);
        $new_array      = array();

        foreach ($original_array as $key => $value) {
            if (isset($data[ $key ]) == true) {
                // buget is added to total budget for activity. So if we change the budget, we need
                // to first subtract the previous entry before adding the new one
                //          	if($key == 'budget') {
                //          		$budgetChange = - $value;
                //          	} else if($key == 'approved') {
                //          		$approvedChange = - $value;
                //          	}
                $new_array[ $key ] = $data[ $key ];
            }
            else {
                $new_array[ $key ] = $original_array[ $key ];
            }
        }

        $values ['description'] = $database->SQLValue($new_array ['description']);
        $values ['comment']     = $database->SQLValue($new_array ['comment']);
        $values ['location']    = $database->SQLValue($new_array ['location']);
        if ($new_array ['tracking_number'] == '') {
            $values ['tracking_number'] = 'NULL';
        }
        else {
            $values ['tracking_number'] = $database->SQLValue($new_array ['tracking_number']);
        }
        $values ['user_id']      = $database->SQLValue($new_array ['user_id'], MySQL::SQLVALUE_NUMBER);
        $values ['project_id']   = $database->SQLValue($new_array ['project_id'], MySQL::SQLVALUE_NUMBER);
        $values ['activity_id']  = $database->SQLValue($new_array ['activity_id'], MySQL::SQLVALUE_NUMBER);
        $values ['comment_type'] = $database->SQLValue($new_array ['comment_type'], MySQL::SQLVALUE_NUMBER);
        $values ['start']       = $database->SQLValue($new_array ['start'], MySQL::SQLVALUE_NUMBER);
        $values ['end']         = $database->SQLValue($new_array ['end'], MySQL::SQLVALUE_NUMBER);
        $values ['duration']    = $database->SQLValue($new_array ['duration'], MySQL::SQLVALUE_NUMBER);
        $values ['rate']        = $database->SQLValue($new_array ['rate'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']     = $database->SQLValue($new_array ['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values ['budget']      = $database->SQLValue($new_array ['budget'], MySQL::SQLVALUE_NUMBER);
        $values ['approved']    = $database->SQLValue($new_array ['approved'], MySQL::SQLVALUE_NUMBER);
        $values ['status_id']    = $database->SQLValue($new_array ['status_id'], MySQL::SQLVALUE_NUMBER);
        $values ['billable']    = $database->SQLValue($new_array ['billable'], MySQL::SQLVALUE_NUMBER);

        $filter ['time_entry_id'] = $database->SQLValue($id, MySQL::SQLVALUE_NUMBER);

        if (!$database->TransactionBegin()) {
            $this->logLastError('timeEntry_edit');

            return false;
        }
        $query = $database->BuildSQLUpdate(TBL_TIMESHEET, $values, $filter);

        $success = true;

        if (!$database->Query($query)) $success = false;

        if ($success) {
            if (!$database->TransactionEnd()) {
                $this->logLastError('timeEntry_edit');

                return false;
            }
        }
        else {
            //      	$budgetChange += $values['budget'];
            //      	$approvedChange += $values['approved'];
            //      	$this->update_evt_budget($values['projectID'], $values['activityID'], $budgetChange);
            //      	$this->update_evt_approved($values['projectID'], $values['activityID'], $budgetChange);
            $this->logLastError('timeEntry_edit');
            if (!$database->TransactionRollback()) {
                $this->logLastError('timeEntry_edit');

                return false;
            }
        }

        return $success;
    }

    /**
     * Just edit the activity for an entry. This is used for changing the activity
     * of a running entry.
     *
     * @param $timeEntryID id of the timesheet entry
     * @param $activityID  id of the activity to change to
     */
    public function timeEntry_edit_activity($timeEntryID, $activityID)
    {
        global $database;

        $timeEntryID = $database->SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $activityID  = $database->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);


        $filter['time_entry_id'] = $timeEntryID;

        $values['activity_id'] = $activityID;

        $query = $database->BuildSQLUpdate(TBL_TIMESHEET, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Just edit the project for an entry. This is used for changing the project
     * of a running entry.
     *
     * @param int $timeEntryID id of the timesheet entry
     * @param int $projectID   id of the project to change to
     *
     * @return array
     */
    public function timeEntry_edit_project($timeEntryID, $projectID)
    {
        global $database;

        $timeEntryID = $database->SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $projectID   = $database->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);


        $filter['time_entry_id'] = $timeEntryID;

        $values['project_id'] = $projectID;

        $query = $database->BuildSQLUpdate(TBL_TIMESHEET, $values, $filter);

        return $database->Query($query);
    }

    /**
     * Returns the data of a certain time record
     *
     * @param int $timeEntryID timeEntryID of the record
     *
     * @return array         the record's data (time, activity id, project id etc) as array, false on failure
     * @author th
     */
    public function timeSheet_get_data($timeEntryID)
    {
        global $database, $kga;

        $timeEntryID = $database->SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);

        $table         = TBL_TIMESHEET;
        $projectTable  = TBL_PROJECT;
        $activityTable = TBL_ACTIVITY;
        $customerTable = TBL_CUSTOMER;

        $select = "SELECT $table.*, $projectTable.name AS projectName, $customerTable.name AS customerName, $activityTable.name AS activityName, $customerTable.customer_id AS customer_id
      				FROM $table
                	JOIN $projectTable USING(project_id)
                	JOIN $customerTable USING(customer_id)
                	JOIN $activityTable USING(activity_id)";


        if ($timeEntryID) {
            $result = $database->Query("$select WHERE time_entry_id = " . $timeEntryID);
        }
        else {
            $result = $database->Query("$select WHERE user_id = " . $kga['user']['user_id'] . " ORDER BY time_entry_id DESC LIMIT 1");
        }

        if (!$result) {
            $this->logLastError('timeSheet_get_data');

            return false;
        }
        else {
            return $database->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     *  Creates an array of clauses which can be joined together in the WHERE part
     *  of a sql query. The clauses describe whether a line should be included
     *  depending on the filters set.
     *
     *  This method also makes the values SQL-secure.
     *
     * @param Array list of IDs of users to include
     * @param Array list of IDs of customers to include
     * @param Array list of IDs of projects to include
     * @param Array list of IDs of activities to include
     *
     * @return Array list of where clauses to include in the query
     *
     */
    public function timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities)
    {
        global $database;

        if (!is_array($users)) $users = array();
        if (!is_array($customers)) $customers = array();
        if (!is_array($projects)) $projects = array();
        if (!is_array($activities)) $activities = array();


        foreach ($users as $i => $user) {
            $users[ $i ] = $database->SQLValue($user, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($customers as $i => $customer) {
            $customers[ $i ] = $database->SQLValue($customer, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($projects as $i => $project) {
            $projects[ $i ] = $database->SQLValue($project, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($activities as $i => $activity) {
            $activities[ $i ] = $database->SQLValue($activity, MySQL::SQLVALUE_NUMBER);
        }

        $whereClauses = array();

        if (count($users) > 0) {
            $whereClauses[] = "user_id in (" . implode(',', $users) . ")";
        }

        if (count($customers) > 0) {
            $whereClauses[] = "customer_id in (" . implode(',', $customers) . ")";
        }

        if (count($projects) > 0) {
            $whereClauses[] = "project_id in (" . implode(',', $projects) . ")";
        }

        if (count($activities) > 0) {
            $whereClauses[] = "activity_id in (" . implode(',', $activities) . ")";
        }

        return $whereClauses;
    }

    public function transaction_begin()
    {
        global $database;

        return $database->TransactionBegin();
    }

    public function transaction_end()
    {
        global $database;

        return $database->TransactionEnd();
    }

    public function transaction_rollback()
    {
        global $database;

        return $database->TransactionRollback();
    }

    /**
     * return name of a user with specific ID
     *
     * @param string $id the user's userID
     *
     * @return int
     * @author th
     */
    public function userIDToName($id)
    {
        global $database;

        $filter ['user_id'] = $database->SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $columns[]         = "name";

        $result = $database->SelectRows(TBL_USER, $filter, $columns);
        if ($result == false) {
            $this->logLastError('userIDToName');

            return false;
        }

        $row = $database->RowArray(0, MYSQL_ASSOC);

        return $row['name'];
    }

    /**
     * Adds a new user
     *
     * @param array $data username, email, and other data of the new user
     *
     * @return boolean|integer     false on failure, otherwise the new user id
     * @author th
     */
    public function user_create($data)
    {
        global $database;

        // find random but unused user id
        do {
            $data['user_id'] = random_number(9);
        } while ($this->user_get_data($data['user_id']));

        $data = $this->clean_data($data);

        $values ['name']         = $database->SQLValue($data['name']);
        $values ['user_id']       = $database->SQLValue($data['user_id'], MySQL::SQLVALUE_NUMBER);
        $values ['global_role_id'] = $database->SQLValue($data['global_role_id'], MySQL::SQLVALUE_NUMBER);
        $values ['active']       = $database->SQLValue($data['active'], MySQL::SQLVALUE_NUMBER);

        // 'mail' and 'password' are just set when actually provided because of compatibility reasons
        if (array_key_exists('mail', $data)) {
            $values['mail'] = $database->SQLValue($data['mail']);
        }

        if (array_key_exists('password', $data)) {
            $values['password'] = $database->SQLValue($data['password']);
        }

        $result = $database->InsertRow(TBL_USER, $values);

        if ($result === false) {
            $this->logLastError('user_create');

            return false;
        }

        if (isset($data['rate'])) {
            if (is_numeric($data['rate'])) {
                $this->save_rate($data['user_id'], null, null, $data['rate']);
            }
            else {
                $this->remove_rate($data['user_id'], null, null);
            }
        }

        return $data['user_id'];
    }

    /**
     * deletes a user
     *
     * @param array   $userID      userID of the user
     * @param boolean $moveToTrash whether to delete user or move to trash
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function user_delete($userID, $moveToTrash = false)
    {
        global $database, $kga;

        $userID = $database->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        if ($moveToTrash) {
            $values['trash']  = 1;
            $filter['user_id'] = $userID;

            $query = $database->BuildSQLUpdate(TBL_USER, $values, $filter);

            return $database->Query($query);
        }
        $p = $kga['server_prefix'];

        $query  = "DELETE FROM ${p}group_user WHERE user_id = " . $userID;
        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('groups_user_delete');

            return false;
        }

        $query  = "DELETE FROM ${p}preference WHERE user_id = " . $userID;
        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('preferences_delete');

            return false;
        }

        $query  = "DELETE FROM ${p}rate WHERE user_id = " . $userID;
        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('rates_delete');

            return false;
        }

        $query  = "DELETE FROM ${p}user WHERE user_id = " . $userID;
        $result = $database->Query($query);

        if ($result === false) {
            $this->logLastError('user_delete');

            return false;
        }

        return true;
    }

    /**
     * Edits a user by replacing his data and preferences by the new array
     *
     * @param array $userID userID of the user to be edited
     * @param array $data   username, email, and other new data of the user
     *
     * @return boolean       true on success, false on failure
     * @author ob/th
     */
    public function user_edit($userID, $data)
    {
        global $database;

        $data    = $this->clean_data($data);
        $strings = array('name', 'mail', 'alias', 'password', 'apikey', 'password_reset_hash');
        $values  = array();

        foreach ($strings as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ]);
            }
        }

        $numbers = array('status', 'trash', 'active', 'last_project', 'last_activity', 'last_record', 'global_role_id');
        foreach ($numbers as $key) {
            if (isset($data[ $key ])) {
                $values[ $key ] = $database->SQLValue($data[ $key ], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['user_id'] = $database->SQLValue($userID, MySQL::SQLVALUE_NUMBER);

        if (!$database->TransactionBegin()) {
            $this->logLastError('user_edit transaction begin');

            return false;
        }

        $query = $database->BuildSQLUpdate(TBL_USER, $values, $filter);

        if ($database->Query($query)) {
            if (isset($data['rate'])) {
                if (is_numeric($data['rate'])) {
                    $this->save_rate($userID, null, null, $data['rate']);
                }
                else {
                    $this->remove_rate($userID, null, null);
                }
            }

            if (!$database->TransactionEnd()) {
                $this->logLastError('user_edit transaction end');

                return false;
            }

            return true;
        }

        if (!$database->TransactionRollback()) {
            $this->logLastError('user_edit rollback');

            return false;
        }

        $this->logLastError('user_edit failed');

        return false;
    }

    /**
     * Returns the data of a certain user
     *
     * @param array $userID ID of the user
     *
     * @return array         the user's data (username, email-address, status etc) as array, false on failure
     * @author th
     */
    public function user_get_data($userID)
    {
        global $database;

        $filter['user_id'] = $database->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $result           = $database->SelectRows(TBL_USER, $filter);

        if (!$result) {
            $this->logLastError('user_get_data');

            return false;
        }

        // return  $this->getHTML();
        return $database->RowArray(0, MYSQL_ASSOC);
    }

    /**
     * Returns the membership roleID the user has in the given group.
     *
     * @param integer the ID of the user
     * @param integer the ID of the group
     *
     * @return integer|bool membership roleID or false if user is not in the group
     */
    public function user_get_membership_role($userID, $groupID)
    {
        global $database;

        $filter['user_id']  = $database->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $filter['group_id'] = $database->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $columns[]         = "membership_role_id";

        $result = $database->SelectRows(TBL_GROUP_USER, $filter, $columns);

        if ($result === false) {
            return false;
        }

        $row = $database->RowArray(0, MYSQL_ASSOC);

        return $row['membership_role_id'];
    }

    /**
     * Get a preference for a user. If no user ID is given the current user is used.
     *
     * @param string  $key    name of the preference to fetch
     * @param integer $userId (optional) id of the user to fetch the preference for
     *
     * @return string value of the preference or null if there is no such preference
     * @author sl
     */
    public function user_get_preference($key, $userId = null)
    {
        global $database, $kga;

        if ($userId === null) {
            $userId = $kga['user']['user_id'];
        }

        $table  = TBL_PREFERENCE;
        $userId = $database->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $key2   = $database->SQLValue($key);

        $query = "SELECT `value` FROM $table WHERE user_id = $userId AND `option` = $key2";

        $database->Query($query);

        if ($database->RowCount() == 0) {
            return null;
        }

        if ($database->RowCount() == 1) {
            $row = $database->RowArray(0, MYSQL_NUM);

            return $row[0];
        }
    }

    /**
     * Get several preferences for a user. If no user ID is given the current user is used.
     *
     * @param array   $keys   names of the preference to fetch in an array
     * @param integer $userId (optional) id of the user to fetch the preference for
     *
     * @return array  with keys for every found preference and the found value
     * @author sl
     */
    public function user_get_preferences(array $keys, $userId = null)
    {
        global $database, $kga;

        if ($userId === null) {
            $userId = $kga['user']['user_id'];
        }

        $table  = TBL_PREFERENCE;
        $userId = $database->SQLValue($userId, MySQL::SQLVALUE_NUMBER);

        $preparedKeys = array();
        foreach ($keys as $key) {
            $preparedKeys[] = $database->SQLValue($key);
        }

        $keysString = implode(",", $preparedKeys);

        $query = "SELECT `option`,`value` FROM $table WHERE user_id = $userId AND `option` IN ($keysString)";

        $database->Query($query);

        $preferences = array();

        while (!$database->EndOfSeek()) {
            $row                           = $database->RowArray();
            $preferences[ $row['option'] ] = $row['value'];
        }

        return $preferences;
    }

    /**
     * Get several preferences for a user which have a common prefix. The returned preferences are striped off
     * the prefix.
     * If no user ID is given the current user is used.
     *
     * @param string  $prefix prefix all preferenc keys to fetch have in common
     * @param integer $userId (optional) id of the user to fetch the preference for
     *
     * @return array  with keys for every found preference and the found value
     * @author sl
     */
    public function user_get_preferences_by_prefix($prefix, $userId = null)
    {
        global $database, $kga;

        if ($userId === null) {
            $userId = $kga['user']['user_id'];
        }

        $prefixLength = strlen($prefix);

        $table  = TBL_PREFERENCE;
        $userId = $database->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $prefix = $database->SQLValue($prefix . '%');

        $query = "SELECT `option`,`value` FROM $table WHERE user_id = $userId AND `option` LIKE $prefix";
        $database->Query($query);

        $preferences = array();

        while (!$database->EndOfSeek()) {
            $row                 = $database->RowArray();
            $key                 = substr($row['option'], $prefixLength);
            $preferences[ $key ] = $row['value'];
        }

        return $preferences;
    }

    /**
     * Save a new secure key for a user to the database. This key is stored in the users cookie and used
     * to reauthenticate the user.
     *
     * @author sl
     */
    public function user_loginSetKey($userId, $keymai)
    {
        global $database, $kga;

        $p = $kga['server_prefix'];
        $u = mysqli_real_escape_string($database->mysql_link, $userId);

        $query = "UPDATE ${p}user SET secure='$keymai',ban=0,ban_time=0 WHERE user_id='" . $u . "';";
        $database->Query($query);
    }

    /**
     * return ID of specific user named 'XXX'
     *
     * @param integer $name name of user in table users
     *
     * @return string
     * @author th
     */
    public function user_name2id($name)
    {
        return $this->name2id(TBL_USER, 'user_id', 'name', $name);
    }

    /**
     * Save one or more preferences for a user. If no user ID is given the current user is used.
     * The array has to assign every preference key a value to store.
     * Example: array ( 'setting1' => 'value1', 'setting2' => 'value2');
     *
     * A prefix can be specified, which will be prepended to every preference key.
     *
     * @param array   $data   key/value pairs to store
     * @param string  $prefix prefix for all preferences
     * @param integer $userId (optional) id of another user than the current
     *
     * @return boolean        true on success, false on failure
     * @author sl
     */
    public function user_set_preferences(array $data, $prefix = '', $userId = null)
    {
        global $database, $kga;

        if ($userId === null) {
            $userId = $kga['user']['user_id'];
        }

        if (!$database->TransactionBegin()) {
            $this->logLastError('user_set_preferences');

            return false;
        }

        $filter['user_id'] = $database->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $values['user_id'] = $filter['user_id'];
        foreach ($data as $key => $value) {
            $values['option'] = $database->SQLValue($prefix . $key);
            $values['value']  = $database->SQLValue($value);
            $filter['option'] = $values['option'];

            $database->AutoInsertUpdate(TBL_PREFERENCE, $values, $filter);
        }

        return $database->TransactionEnd();
    }

    /**
     * checks if a given db row based on the $idColumn & $id exists
     *
     * @param string $table
     * @param array  $filter
     *
     * @return bool
     */
    protected function rowExists($table, Array $filter)
    {
        global $database;

        $select = $database->SelectRows($table, $filter);

        if (!$select) {
            $this->logLastError('rowExists');

            return false;
        }
        else {
            $rowExits = (bool)$database->RowArray(0, MYSQL_ASSOC);

            return $rowExits;
        }
    }

    private function logLastError($scope)
    {
        global $database;

        Logger::logfile($scope . ': ' . $database->Error());
    }

    /**
     * Query a table for an id by giving the name of an entry.
     *
     * @author sl
     */
    private function name2id($table, $endColumn, $filterColumn, $value)
    {
        global $database;

        $filter [ $filterColumn ] = $database->SQLValue($value);
        $filter ['trash']         = 0;
        $columns[]                = $endColumn;

        $result = $database->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('name2id');

            return false;
        }

        $row = $database->RowArray(0, MYSQL_ASSOC);

        if ($row === false) {
            return false;
        }

        return $row[ $endColumn ];
    }

    /**
     * associates an Activity with a collection of Projects in the context of a user group.
     * Projects that are currently associated with the Activity but not mentioned in the specified id collection, will get un-assigned.
     * The fundamental difference to assign_activityToProjects(activityID, projectIDs) is that this method is aware of potentially existing assignments
     * that are invisible and thus unmanagable to the user as the user lacks access to the Projects.
     * It is implicitly assumed that the user has access to the Activity and the Projects designated by the method parameters.
     *
     * @param integer $activityID the id of the Activity to associate
     * @param array   $projectIDs the array of Project ids to associate
     * @param integer $group      the user's group id
     */
    function assignActivityToProjectsForGroup($activityID, $projectIDs, $group)
    {
        $projectIds = array_merge($projectIDs, $this->getNonManagableAssignedElementIds("activity", "project", $activityID, $group));

        return $this->assign_activityToProjects($activityID, $projectIds);
    }

    /**
     * associates a Project with a collection of Activities in the context of a user group.
     * Activities that are currently associated with the Project but not mentioned in the specified id collection, will get un-assigned.
     * The fundamental difference to assign_projectToActivities($projectID, $activityIDs) is that this method is aware of potentially existing assignments
     * that are invisible and thus unmanagable to the user as the user lacks access to the Activities.
     * It is implicitly assumed that the user has access to the Project and the Activities designated by the method parameters.
     *
     * @param integer $projectID   the id of the Project to associate
     * @param array   $activityIDs the array of Activity ids to associate
     * @param integer $group       the user's group id
     */
    function assignProjectToActivitiesForGroup($projectID, $activityIDs, $group)
    {
        $activityIds = array_merge($activityIDs, $this->getNonManagableAssignedElementIds("project", "activity", $projectID, $group));

        return $this->assign_projectToActivities($projectID, $activityIds);
    }

    /**
     * Prepare all values of the array so it's save to put them into an sql query.
     * The conversion to utf8 is done here as well, if configured.
     *
     * This method is public since ki_expenses private database layers use it.
     *
     * @param array $data Array which values are being prepared.
     *
     * @return array The same array, except all values are being escaped correctly.
     */
    public function clean_data($data)
    {
        $return = array();

        foreach ($data as $key => $value) {
            if ($key != "pw") {
                $return[$key] = urldecode(strip_tags($data[$key]));
                $return[$key] = str_replace('"', '_', $data[$key]);
                $return[$key] = str_replace("'", '_', $data[$key]);
                $return[$key] = str_replace('\\', '', $data[$key]);
            }
            else {
                $return[$key] = $data[$key];
            }
        }

        return $return;
    }

    /**
     * computes an array of (project- or activity-) ids for Project-Activity-Assignments that are unmanage-able for the given group.
     * This method supports Project-Activity-Assignments as seen from both end points.
     * The returned array contains the ids of all those Projects or Activities that are assigned to Activities or Projects but cannot be seen by the user that
     * looks at the assignments.
     *
     * @param string  $parentSubject a string designating the parent in the assignment, must be one of "project" or "activity"
     * @param string  $subject       a string designating the child in the assignment, must be one of "project" or "activity"
     * @param integer $parentId      the id of the parent
     * @param integer $group         the id of the user's group
     *
     * @return array the array of ids of those child Projects or Activities that are assigned to the parent Activity or Project but are invisible to the user
     */
    function getNonManagableAssignedElementIds($parentSubject, $subject, $parentId, $group)
    {
        $resultIds        = array();
        $selectedIds      = array();
        $allElements      = array();
        $viewableElements = array();

        switch ($parentSubject . "_" . $subject) {
            case 'project_activity':
                $selectedIds = $this->project_get_activities($parentId);
                break;
            case 'activity_project':
                $selectedIds = $this->activity_get_projects($parentId);
                break;
        }

        //if there are no assignments currently, there's nothing too much that could get deleted :)
        if (count($selectedIds) > 0) {
            switch ($parentSubject . "_" . $subject) {
                case 'project_activity':
                    $allElements      = $this->get_activities();
                    $viewableElements = $this->get_activities($group);
                    break;
                case 'activity_project':
                    $allElements      = $this->get_projects();
                    $viewableElements = $this->get_projects($group);
                    break;
            }
            //if there are no elements hidden from the group, there's nothing too much that could get deleted either
            $count_all      = is_array($allElements) ? count($allElements) : 0;
            $count_viewable = is_array($viewableElements) ? count($viewableElements) : 0;

            if ($count_all > $count_viewable) {
                //1st, find the ids of the elements that are invisible for the group
                $startvisibleIds = array();
                $idField         = $subject . "_ID";
                foreach ($allElements as $allElement) {
                    $seen = false;
                    foreach ($viewableElements as $viewableElement) {
                        if ($viewableElement[$idField] == $allElement[$idField]) {
                            $seen = true;
                            break; //element is viewable, so we can stop here
                        }
                    }
                    if (!$seen) {
                        $startvisibleIds[] = $allElement[$idField];
                    }
                }
                if (count($startvisibleIds) > 0) {
                    //2nd, find the invisible assigned elements and add them to the result array
                    foreach ($selectedIds as $selectedId) {
                        if (in_array($selectedId, $startvisibleIds)) {
                            $resultIds[] = $selectedId;
                        }
                    }
                }
            }
        }

        return $resultIds;
    }


}
