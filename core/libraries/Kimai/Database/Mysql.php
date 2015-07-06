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

require(WEBROOT . 'libraries/mysql.class.php');

/**
 * Provides the database layer for MySQL.
 *
 * @author th
 * @author sl
 * @author Kevin Papst
 */
class Kimai_Database_Mysql extends Kimai_Database_Abstract
{

    /**
     * Adds a new activity
     *
     * @param array $data name, comment and other data of the new activity
     *
     * @return int          the activityID of the new project, false on failure
     * @author th
     */
    public function activity_create($data)
    {
        $data = $this->clean_data($data);

        $values['name']    = $this->MySQL->SQLValue($data['name']);
        $values['comment'] = $this->MySQL->SQLValue($data['comment']);
        $values['visible'] = $this->MySQL->SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter']  = $this->MySQL->SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $table  = $this->getActivityTable();
        $result = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('activity_create');

            return false;
        }

        $activityID = $this->MySQL->GetLastInsertID();

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
                $this->save_rate($this->kga['user']['userID'], null, $activityID, $data['myRate']);
            }
            else {
                $this->remove_rate($this->kga['user']['userID'], null, $activityID);
            }
        }

        if (isset($data['fixedRate'])) {
            if (is_numeric($data['fixedRate'])) {
                $this->save_fixed_rate(null, $activityID, $data['fixedRate']);
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


        $values['trash']      = 1;
        $filter['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table                = $this->getActivityTable();

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    /**
     * Edits an activity by replacing its data by the new array
     *
     * @param array $activityID activityID of the project to be edited
     * @param array $data       name, comment and other new data of the activity
     *
     * @return boolean       true on success, false on failure
     * @author th
     */
    public function activity_edit($activityID, $data)
    {


        $data = $this->clean_data($data);


        $strings = array('name', 'comment');
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key]);
            }
        }

        $numbers = array('visible', 'filter');
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter  ['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table                  = $this->getActivityTable();

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('activity_edit');

            return false;
        }

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        if ($this->MySQL->Query($query)) {

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
                    $this->save_rate($this->kga['user']['userID'], null, $activityID, $data['myRate']);
                }
                else {
                    $this->remove_rate($this->kga['user']['userID'], null, $activityID);
                }
            }

            if (isset($data['fixedRate'])) {
                if (is_numeric($data['fixedRate'])) {
                    $this->save_fixed_rate(null, $activityID, $data['fixedRate']);
                }
                else {
                    $this->remove_fixed_rate(null, $activityID);
                }
            }

            if (!$this->MySQL->TransactionEnd()) {
                $this->logLastError('activity_edit');

                return false;
            }

            return true;
        }
        else {
            $this->logLastError('activity_edit');
            if (!$this->MySQL->TransactionRollback()) {
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
        $filter['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table                = $this->kga['server_prefix'] . "activities";
        $result               = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('activity_get_data');

            return false;
        }


        $result_array = $this->MySQL->RowArray(0, MYSQL_ASSOC);

        $result_array['defaultRate'] = $this->get_rate(null, null, $result_array['activityID']);
        $result_array['myRate']      = $this->get_rate($this->kga['user']['userID'], null, $result_array['activityID']);
        $result_array['fixedRate']   = $this->get_fixed_rate(null, $result_array['activityID']);

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
        $filter['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]            = "groupID";
        $table                = $this->kga['server_prefix'] . "groups_activities";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groupIDs');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($this->MySQL->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
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
        $filter ['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]             = "groupID";
        $table                 = $this->kga['server_prefix'] . "groups_activities";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groups');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($this->MySQL->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
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
        $filter ['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]             = "projectID";
        $table                 = $this->kga['server_prefix'] . "projects_activities";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_projects');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($this->MySQL->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['projectID'];
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
        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate, projectID, activityID FROM " . $this->kga['server_prefix'] . "fixedRates WHERE
    (projectID = $projectID OR projectID IS NULL)  AND
    (activityID = $activityID OR activityID IS NULL)
    ORDER BY activityID DESC , projectID DESC;";

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('allFittingFixedRates');

            return false;
        }

        return $this->MySQL->RecordsArray(MYSQL_ASSOC);
    }

    /**
     * Query the database for all fitting rates for the given user, project and activity.
     *
     * @author sl
     */
    public function allFittingRates($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate, userID, projectID, activityID FROM " . $this->kga['server_prefix'] . "rates WHERE
    (userID = $userID OR userID IS NULL)  AND
    (projectID = $projectID OR projectID IS NULL)  AND
    (activityID = $activityID OR activityID IS NULL)
    ORDER BY userID DESC, activityID DESC , projectID DESC;";

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('allFittingRates');

            return false;
        }

        return $this->MySQL->RecordsArray(MYSQL_ASSOC);
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
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_activityToGroups');

            return false;
        }

        $table                = $this->kga['server_prefix'] . "groups_activities";
        $filter['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result             = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_activityToGroups');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['groupID']    = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->MySQL->BuildSQLInsert($table, $values);
            $result               = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_activityToGroups');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_activityToProjects');

            return false;
        }

        $table                = $this->kga['server_prefix'] . "projects_activities";
        $filter['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result             = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_activityToProjects');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['projectID']  = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $values['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->MySQL->BuildSQLInsert($table, $values);
            $result               = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_activityToProjects');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_customerToGroups');

            return false;
        }

        $table                = $this->kga['server_prefix'] . "groups_customers";
        $filter['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result             = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_customerToGroups');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['groupID']    = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->MySQL->BuildSQLInsert($table, $values);
            $result               = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_customerToGroups');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
     * @param array $activityID contains one or more activityIDs
     *
     * @return boolean            true on success, false on failure
     * @author ob
     */
    public function assign_groupToActivities($groupID, $activityID)
    {
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_groupToActivities');

            return false;
        }

        $table             = $this->kga['server_prefix'] . "groups_activities";
        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query           = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result          = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToActivities');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($activityID as $activityID) {
            $values['groupID']    = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->MySQL->BuildSQLInsert($table, $values);
            $result               = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToActivities');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_groupToCustomers');

            return false;
        }

        $table             = $this->kga['server_prefix'] . "groups_customers";
        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query           = $this->MySQL->BuildSQLDelete($table, $filter);

        $d_result = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToCustomers');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($customerIDs as $customerID) {
            $values['groupID']    = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->MySQL->BuildSQLInsert($table, $values);
            $result               = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToCustomers');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_groupToProjects');

            return false;
        }

        $table             = $this->kga['server_prefix'] . "groups_projects";
        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query           = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result          = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_groupToProjects');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['groupID']   = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query               = $this->MySQL->BuildSQLInsert($table, $values);
            $result              = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_groupToProjects');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_projectToActivities');

            return false;
        }

        $table               = $this->kga['server_prefix'] . "projects_activities";
        $filter['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query             = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result            = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_projectToActivities');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
            $values['projectID']  = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->MySQL->BuildSQLInsert($table, $values);
            $result               = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_projectToActivities');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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


        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('assign_projectToGroups');

            return false;
        }

        $table               = $this->kga['server_prefix'] . "groups_projects";
        $filter['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query             = $this->MySQL->BuildSQLDelete($table, $filter);
        $d_result            = $this->MySQL->Query($d_query);

        if ($d_result == false) {
            $this->logLastError('assign_projectToGroups');
            $this->MySQL->TransactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {

            $values['groupID']   = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query               = $this->MySQL->BuildSQLInsert($table, $values);
            $result              = $this->MySQL->Query($query);

            if ($result == false) {
                $this->logLastError('assign_projectToGroups');
                $this->MySQL->TransactionRollback();

                return false;
            }
        }

        if ($this->MySQL->TransactionEnd() == true) {
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
     * @param                  integer the ID of the user
     * @param                  array   list of group IDs of the object to check
     * @param                  string  name of the permission to check for
     * @param string (all|any) whether the permission must be present for all groups or at least one
     */
    public function checkMembershipPermission($userId, $objectGroups, $permission, $requiredFor = 'all')
    {
        $userGroups   = $this->getGroupMemberships($userId);
        $commonGroups = array_intersect($userGroups, $objectGroups);

        if (count($commonGroups) == 0) {
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
        global $translations;
        $p = $this->kga['server_prefix'];

        if (strncmp($kimai_user, 'customer_', 9) == 0) {
            $customerName = $this->MySQL->SQLValue(substr($kimai_user, 9));
            $query        = "SELECT customerID FROM ${p}customers WHERE name = $customerName AND NOT trash = '1';";
            $this->MySQL->Query($query);
            $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

            $customerID = $row['customerID'];
            if ($customerID < 1) {
                Logger::logfile("Kicking customer $customerName because he is unknown to the system.");
                kickUser();
            }
        }
        else {
            $query = "SELECT userID FROM ${p}users WHERE name = '$kimai_user' AND active = '1' AND NOT trash = '1';";
            $this->MySQL->Query($query);
            $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

            $userID = $row['userID'];
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
        if (isset($this->kga['conf']['skin'])
            && file_exists(WEBROOT . "/skins/" . $this->kga['conf']['skin'])
        ) {
            $skin = $this->kga['conf']['skin'];
        }
        $this->kga['conf']['skin'] = $skin;


        // override autoconf language if admin has chosen a language in the advanced tab
        if ($this->kga['conf']['language'] != "") {
            $translations->load($this->kga['conf']['language']);
            $this->kga['language'] = $this->kga['conf']['language'];
        }

        // override language if user has chosen a language in the prefs
        if ($this->kga['conf']['lang'] != "") {
            $translations->load($this->kga['conf']['lang']);
            $this->kga['language'] = $this->kga['conf']['lang'];
        }

        return (isset($this->kga['user']) ? $this->kga['user'] : null);
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
        $data = $this->clean_data($data);

        $table = $this->kga['server_prefix'] . "configuration";

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('configuration_edit');

            return false;
        }

        foreach ($data as $key => $value) {
            $filter['option'] = $this->MySQL->SQLValue($key);
            $values ['value'] = $this->MySQL->SQLValue($value);

            $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

            $result = $this->MySQL->Query($query);

            if ($result === false) {
                $this->logLastError('configuration_edit');

                return false;
            }
        }

        if (!$this->MySQL->TransactionEnd()) {
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
        $table  = $this->kga['server_prefix'] . "configuration";
        $result = $this->MySQL->SelectRows($table);

        $config_data = array();

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row                       = $this->MySQL->Row();
            $config_data[$row->option] = $row->value;
        }

        return $config_data;
    }

    /**
     * Connect to the database.
     */
    public function connect($host, $database, $username, $password, $utf8, $serverType)
    {
        if (isset($utf8) && $utf8) {
            $this->MySQL = new MySQL(true, $database, $host, $username, $password, "utf8");
        }
        else {
            $this->MySQL = new MySQL(true, $database, $host, $username, $password);
        }
    }

    /**
     * Add a new customer to the database.
     *
     * @param array $data name, address and other data of the new customer
     *
     * @return int         the customerID of the new customer, false on failure
     * @author th
     */
    public function customer_create($data)
    {

        $data = $this->clean_data($data);

        $values     ['name']    = $this->MySQL->SQLValue($data   ['name']);
        $values     ['comment'] = $this->MySQL->SQLValue($data   ['comment']);
        if (isset($data['password'])) {
            $values   ['password'] = $this->MySQL->SQLValue($data   ['password']);
        }
        else {
            $values   ['password'] = "''";
        }
        $values     ['company']  = $this->MySQL->SQLValue($data   ['company']);
        $values     ['vat']      = $this->MySQL->SQLValue($data   ['vat']);
        $values     ['contact']  = $this->MySQL->SQLValue($data   ['contact']);
        $values     ['street']   = $this->MySQL->SQLValue($data   ['street']);
        $values     ['zipcode']  = $this->MySQL->SQLValue($data   ['zipcode']);
        $values     ['city']     = $this->MySQL->SQLValue($data   ['city']);
        $values     ['phone']    = $this->MySQL->SQLValue($data   ['phone']);
        $values     ['fax']      = $this->MySQL->SQLValue($data   ['fax']);
        $values     ['mobile']   = $this->MySQL->SQLValue($data   ['mobile']);
        $values     ['mail']     = $this->MySQL->SQLValue($data   ['mail']);
        $values     ['homepage'] = $this->MySQL->SQLValue($data   ['homepage']);
        $values     ['timezone'] = $this->MySQL->SQLValue($data   ['timezone']);

        $values['visible'] = $this->MySQL->SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter']  = $this->MySQL->SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $table  = $this->getCustomerTable();
        $result = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('customer_create');

            return false;
        }
        else {
            return $this->MySQL->GetLastInsertID();
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
        $values['trash']      = 1;
        $filter['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $table                = $this->getCustomerTable();

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    /**
     * Edits a customer by replacing his data by the new array
     *
     * @param int   $customerID id of the customer to be edited
     * @param array $data       name, address and other new data of the customer
     *
     * @return boolean       true on success, false on failure
     * @author ob/th
     */
    public function customer_edit($customerID, $data)
    {
        $data = $this->clean_data($data);

        $values = array();

        $strings = array(
            'name', 'comment', 'password', 'company', 'vat',
            'contact', 'street', 'zipcode', 'city', 'phone',
            'fax', 'mobile', 'mail', 'homepage', 'timezone',
            'passwordResetHash');
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key]);
            }
        }

        $numbers = array('visible', 'filter');
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);

        $table = $this->getCustomerTable();
        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
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
        $filter['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $table                = $this->getCustomerTable();
        $result               = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('customer_get_data');

            return false;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
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
        $filter['customerID'] = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $columns[]            = "groupID";
        $table                = $this->kga['server_prefix'] . "groups_customers";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($this->MySQL->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
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
        $p = $this->kga['server_prefix'];
        $customerId = mysqli_real_escape_string($this->MySQL->mysql_link, $customerId);

        $query = "UPDATE ${p}customers SET secure='$keymai' WHERE customerID='" . $customerId . "';";
        $this->MySQL->Query($query);
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
        return $this->name2id($this->kga['server_prefix'] . "customers", 'customerID', 'name', $name);
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
        $filter['userID'] = $this->MySQL->SQLValue($userId);
        $columns[]        = "groupID";
        $table            = $this->kga['server_prefix'] . "groups_users";
        $result           = $this->MySQL->SelectRows($table, $filter, $columns);

        if (!$result) {
            $this->logLastError('getGroupMemberships');

            return null;
        }

        $arr = array();
        if ($this->MySQL->RowCount()) {
            $this->MySQL->MoveFirst();
            while (!$this->MySQL->EndOfSeek()) {
                $row   = $this->MySQL->Row();
                $arr[] = $row->groupID;
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
        if (!$apikey || strlen(trim($apikey)) == 0) {
            return null;
        }

        $table            = $this->kga['server_prefix'] . "users";
        $filter['apikey'] = $this->MySQL->SQLValue($apikey, MySQL::SQLVALUE_TEXT);
        $filter['trash']  = $this->MySQL->SQLValue(0, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "userID";
        $columns[] = "name";

        $this->MySQL->SelectRows($table, $filter, $columns);
        $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

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
        $filter['option'] = $this->MySQL->SQLValue('version');
        $columns[]        = "value";
        $table            = $this->kga['server_prefix'] . "configuration";
        $result           = $this->MySQL->SelectRows($table, $filter, $columns);

        if ($result == false) {
            // before database revision 1369 (503 + 866)
            $table = $this->kga['server_prefix'] . "var";
            unset($filter);
            $filter['var'] = $this->MySQL->SQLValue('version');
            $result        = $this->MySQL->SelectRows($table, $filter, $columns);
        }

        $row      = $this->MySQL->RowArray(0, MYSQL_ASSOC);
        $return[] = $row['value'];

        if ($result == false) $return[0] = "0.5.1";

        $filter['option'] = $this->MySQL->SQLValue('revision');
        $result           = $this->MySQL->SelectRows($table, $filter, $columns);

        if ($result == false) {
            // before database revision 1369 (503 + 866)
            unset($filter);
            $filter['var'] = $this->MySQL->SQLValue('revision');
            $result        = $this->MySQL->SelectRows($table, $filter, $columns);
        }

        $row      = $this->MySQL->RowArray(0, MYSQL_ASSOC);
        $return[] = $row['value'];

        return $return;
    }

    public function get_activities(array $groups = null)
    {
        $p = $this->kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT activityID, name, visible
              FROM ${p}activities
              WHERE trash=0
              ORDER BY visible DESC, name;";
        }
        else {
            $query = "SELECT DISTINCT activityID, name, visible
              FROM ${p}activities
              JOIN ${p}groups_activities AS g_a USING(activityID)
              WHERE g_a.groupID IN (" . implode($groups, ',') . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        $result = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities');

            return false;
        }

        $arr = array();
        $i   = 0;
        if ($this->MySQL->RowCount()) {
            $this->MySQL->MoveFirst();
            while (!$this->MySQL->EndOfSeek()) {
                $row                   = $this->MySQL->Row();
                $arr[$i]['activityID'] = $row->activityID;
                $arr[$i]['name']       = $row->name;
                $arr[$i]['visible']    = $row->visible;
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
        $p = $this->kga['server_prefix'];

        $customer_ID = $this->MySQL->SQLValue($customer_ID, MySQL::SQLVALUE_NUMBER);

        $query = "SELECT DISTINCT activityID, name, visible
          FROM ${p}activities
          WHERE activityID IN
              (SELECT activityID FROM ${p}timeSheet
                WHERE projectID IN (SELECT projectID FROM ${p}projects WHERE customerID = $customer_ID))
            AND trash=0";

        $result = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities_by_customer');

            return false;
        }

        $arr = array();
        $i   = 0;

        if ($this->MySQL->RowCount()) {
            $this->MySQL->MoveFirst();
            while (!$this->MySQL->EndOfSeek()) {
                $row                   = $this->MySQL->Row();
                $arr[$i]['activityID'] = $row->activityID;
                $arr[$i]['name']       = $row->name;
                $arr[$i]['visible']    = $row->visible;
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
        $projectID = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM ${p}activities AS activity
            LEFT JOIN ${p}projects_activities AS p_a USING(activityID)
            WHERE activity.trash=0
              AND (projectID = $projectID OR projectID IS NULL)
            ORDER BY visible DESC, name;";
        }
        else {
            $query = "SELECT DISTINCT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM ${p}activities AS activity
            JOIN ${p}groups_activities USING(activityID)
            LEFT JOIN ${p}projects_activities p_a USING(activityID)
            WHERE `${p}groups_activities`.`groupID`  IN (" . implode($groups, ',') . ")
              AND activity.trash=0
              AND (projectID = $projectID OR projectID IS NULL)
            ORDER BY visible DESC, name;";
        }

        $result = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('get_activities_by_project');

            return false;
        }

        $arr = array();
        if ($this->MySQL->RowCount()) {
            $this->MySQL->MoveFirst();
            while (!$this->MySQL->EndOfSeek()) {
                $row                                 = $this->MySQL->Row();
                $arr[$row->activityID]['activityID'] = $row->activityID;
                $arr[$row->activityID]['name']       = $row->name;
                $arr[$row->activityID]['visible']    = $row->visible;
                $arr[$row->activityID]['budget']     = $row->budget;
                $arr[$row->activityID]['approved']   = $row->approved;
                $arr[$row->activityID]['effort']     = $row->effort;
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
        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";

        $query = "SELECT budget, approved, effort FROM " . $this->kga['server_prefix'] . "projects_activities WHERE " .
            (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
            (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('get_activity_budget');

            return false;
        }
        $data = $this->MySQL->rowArray(0, MYSQL_ASSOC);
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
        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "fixedRates WHERE
    (projectID = $projectID OR projectID IS NULL)  AND
    (activityID = $activityID OR activityID IS NULL)
    ORDER BY activityID DESC , projectID DESC
    LIMIT 1;";

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('get_best_fitting_fixed_rate');

            return false;
        }

        if ($this->MySQL->RowCount() == 0) {
            return false;
        }

        $data = $this->MySQL->rowArray(0, MYSQL_ASSOC);

        return $data['rate'];
    }

    /**
     * Query the database for the best fitting rate for the given user, project and activity.
     *
     * @author sl
     */
    public function get_best_fitting_rate($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "rates WHERE
    (userID = $userID OR userID IS NULL)  AND
    (projectID = $projectID OR projectID IS NULL)  AND
    (activityID = $activityID OR activityID IS NULL)
    ORDER BY userID DESC, activityID DESC , projectID DESC
    LIMIT 1;";

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('get_best_fitting_rate');

            return false;
        }

        if ($this->MySQL->RowCount() == 0) {
            return false;
        }

        $data = $this->MySQL->rowArray(0, MYSQL_ASSOC);

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

        $p      = $this->kga['server_prefix'];
        $userID = $this->MySQL->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $result = $this->MySQL->Query("SELECT timeEntryID FROM ${p}timeSheet WHERE userID = $userID AND start > 0 AND end = 0");

        if ($result === false) {
            $this->logLastError('get_current_recordings');

            return array();
        }

        $IDs = array();

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row   = $this->MySQL->Row();
            $IDs[] = $row->timeEntryID;
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
        $user = $this->MySQL->SQLValue($this->kga['user']['userID'], MySQL::SQLVALUE_NUMBER);
        $p    = $this->kga['server_prefix'];

        $this->MySQL->Query("SELECT timeEntryID, start FROM ${p}timeSheet WHERE userID = $user AND end = 0;");

        if ($this->MySQL->RowCount() == 0) {
            $current_timer['all']  = 0;
            $current_timer['hour'] = 0;
            $current_timer['min']  = 0;
            $current_timer['sec']  = 0;
        }
        else {

            $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

            $start = (int) $row['start'];

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
        if (!$user) return;

        $table                = $this->kga['server_prefix'] . "customers";
        $filter['customerID'] = $this->MySQL->SQLValue($user, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "customerID";
        $columns[] = "name";
        $columns[] = "comment";
        $columns[] = "visible";
        $columns[] = "filter";
        $columns[] = "company";
        $columns[] = "street";
        $columns[] = "zipcode";
        $columns[] = "city";
        $columns[] = "phone";
        $columns[] = "fax";
        $columns[] = "mobile";
        $columns[] = "mail";
        $columns[] = "homepage";
        $columns[] = "trash";
        $columns[] = "password";
        $columns[] = "secure";
        $columns[] = "timezone";

        $this->MySQL->SelectRows($table, $filter, $columns);
        $rows = $this->MySQL->RowArray(0, MYSQL_ASSOC);
        foreach ($rows as $key => $value) {
            $this->kga['customer'][$key] = $value;
        }

        date_default_timezone_set($this->kga['customer']['timezone']);
    }

    public function get_customer_watchable_users($customer)
    {
        $customerID = $this->MySQL->SQLValue($customer['customerID'], MySQL::SQLVALUE_NUMBER);
        $p          = $this->kga['server_prefix'];
        $query      = "SELECT * FROM ${p}users WHERE trash=0 AND `userID` IN (SELECT DISTINCT `userID` FROM `${p}timeSheet` WHERE `projectID` IN (SELECT `projectID` FROM `${p}projects` WHERE `customerID` = $customerID)) ORDER BY name";
        $result     = $this->MySQL->Query($query);

        return $this->MySQL->RecordsArray(MYSQL_ASSOC);
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
        $p = $this->kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT customerID, name, contact, visible
              FROM ${p}customers
              WHERE trash=0
              ORDER BY visible DESC, name;";
        }
        else {
            $query = "SELECT DISTINCT customerID, name, contact, visible
              FROM ${p}customers
              JOIN ${p}groups_customers AS g_c USING (customerID)
              WHERE g_c.groupID IN (" . implode($groups, ',') . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        $result = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('get_customers');

            return false;
        }

        $i = 0;
        if ($this->MySQL->RowCount()) {
            $arr = array();
            $this->MySQL->MoveFirst();
            while (!$this->MySQL->EndOfSeek()) {
                $row                   = $this->MySQL->Row();
                $arr[$i]['customerID'] = $row->customerID;
                $arr[$i]['name']       = $row->name;
                $arr[$i]['contact']    = $row->contact;
                $arr[$i]['visible']    = $row->visible;
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
        if (!is_numeric($filterCleared)) {
            $filterCleared = $this->kga['conf']['hideClearedEntries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start = $this->MySQL->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->MySQL->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

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

        $query = "SELECT start,end,duration FROM ${p}timeSheet
              Join ${p}projects USING(projectID)
              Join ${p}customers USING(customerID)
              Join ${p}users USING(userID)
              Join ${p}activities USING(activityID) "
            . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);
        $this->MySQL->Query($query);

        $this->MySQL->MoveFirst();
        $sum             = 0;
        $consideredStart = 0; // Consider start of selected range if real start is before
        $consideredEnd   = 0; // Consider end of selected range if real end is afterwards
        while (!$this->MySQL->EndOfSeek()) {
            $row = $this->MySQL->Row();
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
            $sum += (int) ($consideredEnd - $consideredStart);
        }

        return $sum;
    }

    /**
     * Read fixed rate from database.
     *
     * @author sl
     */
    public function get_fixed_rate($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "fixedRates WHERE " .
            (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
            (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('get_fixed_rate');

            return false;
        }

        if ($this->MySQL->RowCount() == 0) {
            return false;
        }

        $data = $this->MySQL->rowArray(0, MYSQL_ASSOC);

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
        // get values from global configuration
        $table = $this->kga['server_prefix'] . "configuration";
        $this->MySQL->SelectRows($table);

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row                             = $this->MySQL->Row();
            $this->kga['conf'][$row->option] = $row->value;
        }


        $this->kga['conf']['rowlimit']             = 100;
        $this->kga['conf']['skin']                 = 'standard';
        $this->kga['conf']['autoselection']        = 1;
        $this->kga['conf']['quickdelete']          = 0;
        $this->kga['conf']['flip_project_display'] = 0;
        $this->kga['conf']['project_comment_flag'] = 0;
        $this->kga['conf']['showIDs']              = 0;
        $this->kga['conf']['noFading']             = 0;
        $this->kga['conf']['lang']                 = '';
        $this->kga['conf']['user_list_hidden']     = 0;
        $this->kga['conf']['hideClearedEntries']   = 0;


        $table = $this->kga['server_prefix'] . "statuses";
        $this->MySQL->SelectRows($table);

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row                                         = $this->MySQL->Row();
            $this->kga['conf']['status'][$row->statusID] = $row->status;
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
        $p = $this->kga['server_prefix'];

        // Lock tables for alles queries executed until the end of this public function
        $lock   = "LOCK TABLE ${p}users READ, ${p}groups READ, ${p}groups_users READ;";
        $result = $this->MySQL->Query($lock);
        if (!$result) {
            $this->logLastError('get_groups');

            return false;
        }

        //------

        if (!$trash) {
            $trashoption = "WHERE ${p}groups.trash !=1";
        }

        $query = "SELECT * FROM ${p}groups $trashoption ORDER BY name;";
        $this->MySQL->Query($query);

        // rows into array
        $groups = array();
        $i      = 0;

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        foreach ($rows as $row) {
            $groups[] = $row;

            // append user count
            $groups[$i]['count_users'] = $this->group_count_users($row['groupID']);

            $i++;
        }

        //------

        // Unlock tables
        $unlock = "UNLOCK TABLES;";
        $result = $this->MySQL->Query($unlock);
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

        $p = $this->kga['server_prefix'];

        $table         = $this->getTimeSheetTable();
        $projectTable  = $this->getProjectTable();
        $activityTable = $this->getActivityTable();
        $customerTable = $this->getCustomerTable();

        $select = "SELECT $table.*, $projectTable.name AS projectName, $customerTable.name AS customerName, $activityTable.name AS activityName, $customerTable.customerID AS customerID
          FROM $table
              JOIN $projectTable USING(projectID)
              JOIN $customerTable USING(customerID)
              JOIN $activityTable USING(activityID)";

        $result = $this->MySQL->Query("$select WHERE end = 0 AND userID = " . $this->kga['user']['userID'] . " ORDER BY timeEntryID DESC LIMIT 1");

        if (!$result) {
            return null;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
        }
    }

    /**
     * returns list of projects for specific group as array
     *
     * @param integer $user ID of user in database
     *
     * @return array
     * @author th
     */
    public function get_projects(array $groups = null)
    {
        $p = $this->kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT project.*, customer.name AS customerName
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  WHERE project.trash=0";
        }
        else {
            $query = "SELECT DISTINCT project.*, customer.name AS customerName
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  JOIN ${p}groups_projects USING(projectID)
                  WHERE ${p}groups_projects.groupID IN (" . implode($groups, ',') . ")
                  AND project.trash=0";
        }

        if ($this->kga['conf']['flip_project_display']) {
            $query .= " ORDER BY project.visible DESC, customerName, name;";
        }
        else {
            $query .= " ORDER BY project.visible DESC, name, customerName;";
        }

        $result = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('get_projects');

            return false;
        }

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($rows) {
            $arr = array();
            $i   = 0;
            foreach ($rows as $row) {
                $arr[$i]['projectID']    = $row['projectID'];
                $arr[$i]['customerID']   = $row['customerID'];
                $arr[$i]['name']         = $row['name'];
                $arr[$i]['comment']      = $row['comment'];
                $arr[$i]['visible']      = $row['visible'];
                $arr[$i]['filter']       = $row['filter'];
                $arr[$i]['trash']        = $row['trash'];
                $arr[$i]['budget']       = $row['budget'];
                $arr[$i]['effort']       = $row['effort'];
                $arr[$i]['approved']     = $row['approved'];
                $arr[$i]['internal']     = $row['internal'];
                $arr[$i]['customerName'] = $row['customerName'];
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
        $customerID = $this->MySQL->SQLValue($customerID, MySQL::SQLVALUE_NUMBER);
        $p          = $this->kga['server_prefix'];

        if ($this->kga['conf']['flip_project_display']) {
            $sort = "customerName, name";
        }
        else {
            $sort = "name, customerName";
        }

        if ($groups === null) {
            $query = "SELECT project.*, customer.name AS customerName
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  WHERE customerID = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        }
        else {
            $query = "SELECT DISTINCT project.*, customer.name AS customerName
                  FROM ${p}projects AS project
                  JOIN ${p}customers AS customer USING(customerID)
                  JOIN ${p}groups_projects USING(projectID)
                  WHERE ${p}groups_projects.groupID  IN (" . implode($groups, ',') . ")
                    AND customerID = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        }

        $this->MySQL->Query($query);

        $arr = array();
        $i   = 0;

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row                     = $this->MySQL->Row();
            $arr[$i]['projectID']    = $row->projectID;
            $arr[$i]['name']         = $row->name;
            $arr[$i]['customerName'] = $row->customerName;
            $arr[$i]['customerID']   = $row->customerID;
            $arr[$i]['visible']      = $row->visible;
            $arr[$i]['budget']       = $row->budget;
            $arr[$i]['effort']       = $row->effort;
            $arr[$i]['approved']     = $row->approved;
            $i++;
        }

        return $arr;
    }

    /**
     * Read rate from database.
     *
     * @author sl
     */
    public function get_rate($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "SELECT rate FROM " . $this->kga['server_prefix'] . "rates WHERE " .
            (($userID == "NULL") ? "userID is NULL" : "userID = $userID") . " AND " .
            (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
            (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->MySQL->Query($query);

        if ($this->MySQL->RowCount() == 0) {
            return false;
        }

        $data = $this->MySQL->rowArray(0, MYSQL_ASSOC);

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
        if (strncmp($user, 'customer_', 9) == 0) {
            $filter['name']  = $this->MySQL->SQLValue(substr($user, 9));
            $filter['trash'] = 0;
            $table           = $this->getCustomerTable();
        }
        else {
            $filter['name']  = $this->MySQL->SQLValue($user);
            $filter['trash'] = 0;
            $table           = $this->getUserTable();
        }

        $columns[] = "secure";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('get_seq');

            return false;
        }

        $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

        return $row['secure'];
    }

    /**
     * return status names
     *
     * @param integer $statusIds
     */
    public function get_status($statusIds)
    {
        $p         = $this->kga['server_prefix'];
        $statusIds = implode(',', $statusIds);
        $query     = "SELECT status FROM ${p}statuses where statusID in ( $statusIds ) order by statusID";
        $result    = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('get_status');

            return false;
        }

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);
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
        $p = $this->kga['server_prefix'];

        $query = "SELECT * FROM ${p}statuses
        ORDER BY status;";
        $this->MySQL->Query($query);

        $arr = array();
        $i   = 0;

        $this->MySQL->MoveFirst();
        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($rows === false) {
            return array();
        }

        foreach ($rows as $row) {
            $arr[]                          = $row;
            $arr[$i]['timeSheetEntryCount'] = $this->status_timeSheetEntryCount($row['statusID']);
            $i++;
        }

        return $arr;
    }

    /**
     * returns timesheet for specific user as multidimensional array
     *
     * @TODO   : needs new comments
     *
     * @param integer $user          ID of user in table users
     * @param integer $start         start of timeframe in unix seconds
     * @param integer $end           end of timeframe in unix seconds
     * @param integer $filterCleared where -1 (default) means no filtering, 0 means only not cleared entries, 1 means only cleared entries
     * @param
     *
     * @return array
     * @author th
     */
    public function get_timeSheet($start, $end, $users = null, $customers = null, $projects = null, $activities = null, $limit = false, $reverse_order = false, $filterCleared = null, $startRows = 0, $limitRows = 0, $countOnly = false)
    {
        if (!is_numeric($filterCleared)) {
            $filterCleared = $this->kga['conf']['hideClearedEntries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start         = $this->MySQL->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end           = $this->MySQL->SQLValue($end, MySQL::SQLVALUE_NUMBER);
        $filterCleared = $this->MySQL->SQLValue($filterCleared, MySQL::SQLVALUE_NUMBER);
        $limit         = $this->MySQL->SQLValue($limit, MySQL::SQLVALUE_BOOLEAN);

        $p = $this->kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if (isset($this->kga['customer'])) {
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
                $startRows = (int) $startRows;
                $limit     = "LIMIT $startRows, $limitRows";
            }
            else {
                if (isset($this->kga['conf']['rowlimit'])) {
                    $limit = "LIMIT " . $this->kga['conf']['rowlimit'];
                }
                else {
                    $limit = "LIMIT 100";
                }
            }
        }
        else {
            $limit = "";
        }


        $select = "SELECT timeSheet.*, status.status, customer.name AS customerName, customer.customerID as customerID, activity.name AS activityName,
                        project.name AS projectName, project.comment AS projectComment, user.name AS userName, user.alias AS userAlias ";

        if ($countOnly) {
            $select = "SELECT COUNT(*) AS total";
            $limit  = "";
        }

        $query = "$select
                FROM ${p}timeSheet AS timeSheet
                Join ${p}projects AS project USING (projectID)
                Join ${p}customers AS customer USING (customerID)
                Join ${p}users AS user USING(userID)
                Join ${p}statuses AS status USING(statusID)
                Join ${p}activities AS activity USING(activityID) "
            . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) .
            ' ORDER BY start ' . ($reverse_order ? 'ASC ' : 'DESC ') . $limit . ';';

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('get_timeSheet');
        }

        if ($countOnly) {
            $this->MySQL->MoveFirst();
            $row = $this->MySQL->Row();

            return $row->total;
        }

        $i   = 0;
        $arr = array();

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row                    = $this->MySQL->Row();
            $arr[$i]['timeEntryID'] = $row->timeEntryID;

            // Start time should not be less than the selected start time. This would confuse the user.
            if ($start && $row->start <= $start) {
                $arr[$i]['start'] = $start;
            }
            else {
                $arr[$i]['start'] = $row->start;
            }

            // End time should not be less than the selected start time. This would confuse the user.
            if ($end && $row->end >= $end) {
                $arr[$i]['end'] = $end;
            }
            else {
                $arr[$i]['end'] = $row->end;
            }

            if ($row->end != 0) {
                // only calculate time after recording is complete
                $arr[$i]['duration']          = $arr[$i]['end'] - $arr[$i]['start'];
                $arr[$i]['formattedDuration'] = Format::formatDuration($arr[$i]['duration']);
                $arr[$i]['wage_decimal']      = $arr[$i]['duration'] / 3600 * $row->rate;
                $arr[$i]['wage']              = sprintf("%01.2f", $arr[$i]['wage_decimal']);
            }
            else {
                $arr[$i]['duration']          = null;
                $arr[$i]['formattedDuration'] = null;
                $arr[$i]['wage_decimal']      = null;
                $arr[$i]['wage']              = null;
            }
            $arr[$i]['budget']         = $row->budget;
            $arr[$i]['approved']       = $row->approved;
            $arr[$i]['rate']           = $row->rate;
            $arr[$i]['projectID']      = $row->projectID;
            $arr[$i]['activityID']     = $row->activityID;
            $arr[$i]['userID']         = $row->userID;
            $arr[$i]['projectID']      = $row->projectID;
            $arr[$i]['customerName']   = $row->customerName;
            $arr[$i]['customerID']     = $row->customerID;
            $arr[$i]['activityName']   = $row->activityName;
            $arr[$i]['projectName']    = $row->projectName;
            $arr[$i]['projectComment'] = $row->projectComment;
            $arr[$i]['location']       = $row->location;
            $arr[$i]['trackingNumber'] = $row->trackingNumber;
            $arr[$i]['statusID']       = $row->statusID;
            $arr[$i]['status']         = $row->status;
            $arr[$i]['billable']       = $row->billable;
            $arr[$i]['description']    = $row->description;
            $arr[$i]['comment']        = $row->comment;
            $arr[$i]['cleared']        = $row->cleared;
            $arr[$i]['commentType']    = $row->commentType;
            $arr[$i]['userAlias']      = $row->userAlias;
            $arr[$i]['userName']       = $row->userName;
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
        $start = $this->MySQL->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->MySQL->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}activities.trash = 0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT start, end, activityID, (end - start) / 3600 * rate AS costs
          FROM ${p}timeSheet
          Left Join ${p}activities USING(activityID)
          Left Join ${p}projects USING(projectID)
          Left Join ${p}customers USING(customerID) " .
            (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $this->MySQL->Query($query);
        if (!$result) {
            $this->logLastError('get_time_activities');

            return array();
        }
        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);
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

            if (isset($arr[$row['activityID']])) {
                $arr[$row['activityID']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['activityID']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['activityID']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['activityID']]['costs'] = (double) $row['costs'];
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
        $start = $this->MySQL->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->MySQL->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}customers.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }


        $query = "SELECT start,end, customerID, (end - start) / 3600 * rate AS costs
              FROM ${p}timeSheet
              Left Join ${p}projects USING(projectID)
              Left Join ${p}customers USING(customerID) " .
            (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $this->MySQL->Query($query);
        if (!$result) {
            $this->logLastError('get_time_customers');

            return array();
        }
        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);
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

            if (isset($arr[$row['customerID']])) {
                $arr[$row['customerID']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['customerID']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['customerID']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['customerID']]['costs'] = (double) $row['costs'];
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
        $start = $this->MySQL->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->MySQL->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}projects.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT start, end ,projectID, (end - start) / 3600 * rate AS costs
          FROM ${p}timeSheet
          Left Join ${p}projects USING(projectID)
          Left Join ${p}customers USING(customerID) " .
            (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        $result = $this->MySQL->Query($query);
        if (!$result) {
            $this->logLastError('get_time_projects');

            return array();
        }
        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);
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

            if (isset($arr[$row['projectID']])) {
                $arr[$row['projectID']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['projectID']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['projectID']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['projectID']]['costs'] = (double) $row['costs'];
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
        $start = $this->MySQL->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->MySQL->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $this->kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "${p}users.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query  = "SELECT start,end, userID, (end - start) / 3600 * rate AS costs
              FROM ${p}timeSheet
              Join ${p}projects USING(projectID)
              Join ${p}customers USING(customerID)
              Join ${p}users USING(userID)
              Join ${p}activities USING(activityID) "
            . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) . " ORDER BY start DESC;";
        $result = $this->MySQL->Query($query);

        if (!$result) {
            $this->logLastError('get_time_users');

            return array();
        }

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);
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

            if (isset($arr[$row['userID']])) {
                $arr[$row['userID']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['userID']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['userID']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['userID']]['costs'] = (double) $row['costs'];
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
        if (!$user) return;

        $table            = $this->kga['server_prefix'] . "users";
        $filter['userID'] = $this->MySQL->SQLValue($user, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = "userID";
        $columns[] = "name";
        $columns[] = "trash";
        $columns[] = "active";
        $columns[] = "mail";
        $columns[] = "password";
        $columns[] = "ban";
        $columns[] = "banTime";
        $columns[] = "secure";
        $columns[] = "lastProject";
        $columns[] = "lastActivity";
        $columns[] = "lastRecord";
        $columns[] = "timeframeBegin";
        $columns[] = "timeframeEnd";
        $columns[] = "apikey";
        $columns[] = "globalRoleID";

        $this->MySQL->SelectRows($table, $filter, $columns);
        $rows = $this->MySQL->RowArray(0, MYSQL_ASSOC);
        foreach ($rows as $key => $value) {
            $this->kga['user'][$key] = $value;
        }

        $this->kga['user']['groups'] = $this->getGroupMemberships($user);

        // get values from user configuration (user-preferences)
        unset($columns);
        unset($filter);

        $this->kga['conf'] = array_merge($this->kga['conf'], $this->user_get_preferences_by_prefix('ui.'));
        $userTimezone      = $this->user_get_preference('timezone');
        if ($userTimezone != '') {
            $this->kga['timezone'] = $userTimezone;
        }
        else {
            $this->kga['timezone'] = $this->kga['defaultTimezone'];
        }

        date_default_timezone_set($this->kga['timezone']);
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
        $userID = $this->MySQL->SQLValue($user['userID'], MySQL::SQLVALUE_NUMBER);
        $p      = $this->kga['server_prefix'];
        $that   = $this;

        if ($this->global_role_allows($user['globalRoleID'], 'core-user-otherGroup-view')) {
            // If user may see other groups we need to filter out groups he's part of but has no permission to see users in.
            $forbidden_groups = array_filter($user['groups'], function ($groupID) use ($userID, $that) {
                $roleID = $that->user_get_membership_role($userID, $groupID);

                return !$that->membership_role_allows($roleID, 'core-user-view');
            });

            $group_filter = "";
            if (count($forbidden_groups) > 0) {
                $group_filter = " AND count(SELECT * FROM ${p}groups_users AS p WHERE u.`userID` = p.`userID` AND `groupID` NOT IN (" . implode(', ', $forbidden_groups) . ")) > 0";
            }

            $query  = "SELECT * FROM ${p}users AS u WHERE trash=0 $group_filter ORDER BY name";
            $result = $this->MySQL->Query($query);

            return $this->MySQL->RecordsArray(MYSQL_ASSOC);
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
        $p = $this->kga['server_prefix'];


        $trash = $this->MySQL->SQLValue($trash, MySQL::SQLVALUE_NUMBER);

        if ($groups === null) {
            $query = "SELECT * FROM ${p}users
        WHERE trash = $trash
        ORDER BY name ;";
        }
        else {
            $query = "SELECT DISTINCT u.* FROM ${p}users AS u
         JOIN ${p}groups_users AS g_u USING(userID)
        WHERE g_u.groupID IN (" . implode($groups, ',') . ") AND
         trash = $trash
        ORDER BY name ;";
        }
        $this->MySQL->Query($query);

        $rows = $this->MySQL->RowArray(0, MYSQL_ASSOC);

        $i   = 0;
        $arr = array();

        $this->MySQL->MoveFirst();
        while (!$this->MySQL->EndOfSeek()) {
            $row                     = $this->MySQL->Row();
            $arr[$i]['userID']       = $row->userID;
            $arr[$i]['name']         = $row->name;
            $arr[$i]['globalRoleID'] = $row->globalRoleID;
            $arr[$i]['mail']         = $row->mail;
            $arr[$i]['active']       = $row->active;
            $arr[$i]['trash']        = $row->trash;

            if ($row->password != '' && $row->password != '0') {
                $arr[$i]['passwordSet'] = "yes";
            }
            else {
                $arr[$i]['passwordSet'] = "no";
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
        $userID = $this->MySQL->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $p      = $this->kga['server_prefix'];

        $query = "SELECT start FROM ${p}timeSheet WHERE userID = $userID ORDER BY start ASC LIMIT 1;";

        $result = $this->MySQL->Query($query);
        if ($result == false) {
            $this->logLastError('getjointime');

            return false;
        }

        $result_array = $this->MySQL->RowArray(0, MYSQL_NUM);

        if ($result_array[0] == 0) {
            return mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        }
        else {
            return $result_array[0];
        }
    }

    public function globalRole_find($filter)
    {
        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = $this->MySQL->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
            else {
                $value = $this->MySQL->SQLValue($value);
            }
        }
        $table  = $this->kga['server_prefix'] . "globalRoles";
        $result = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('globalRole_find');

            return false;
        }
        else {
            return $this->MySQL->RecordsArray(MYSQL_ASSOC);
        }
    }

    public function globalRole_get_data($globalRoleID)
    {
        $filter['globalRoleID'] = $this->MySQL->SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $table                  = $this->kga['server_prefix'] . "globalRoles";
        $result                 = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('globalRole_get_data');

            return false;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
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
        $filter['globalRoleID'] = $this->MySQL->SQLValue($roleID, MySQL::SQLVALUE_NUMBER);
        $filter[$permission]    = 1;
        $columns[]              = "globalRoleID";
        $table                  = $this->kga['server_prefix'] . "globalRoles";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);

        if ($result === false) {
            $this->logLastError('global_role_allows');

            return false;
        }

        $result = $this->MySQL->RowCount() > 0;

        Logger::logfile("Global role $roleID gave " . ($result ? 'true' : 'false') . " for $permission.");

        return $result;
    }

    public function global_role_create($data)
    {
        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->MySQL->SQLValue($value);
            }
            else {
                $values[$key] = $this->MySQL->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $table  = $this->kga['server_prefix'] . "globalRoles";
        $result = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('global_role_create');

            return false;
        }

        return $this->MySQL->GetLastInsertID();
    }

    public function global_role_delete($globalRoleID)
    {
        $table                  = $this->kga['server_prefix'] . "globalRoles";
        $filter['globalRoleID'] = $this->MySQL->SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $query                  = MySQL::BuildSQLDelete($table, $filter);
        $result                 = $this->MySQL->Query($query);

        if ($result == false) {
            $this->logLastError('global_role_delete');

            return false;
        }

        return true;
    }

    public function global_role_edit($globalRoleID, $data)
    {

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->MySQL->SQLValue($value);
            }
            else {
                $values[$key] = $this->MySQL->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['globalRoleID'] = $this->MySQL->SQLValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $table                  = $this->kga['server_prefix'] . "globalRoles";

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        $result = $this->MySQL->Query($query);

        if ($result == false) {
            $this->logLastError('global_role_edit');

            return false;
        }

        return true;
    }

    public function global_roles()
    {
        $p = $this->kga['server_prefix'];

        $query = "SELECT a.*, COUNT(b.globalRoleID) AS count_users FROM `${p}globalRoles` a LEFT JOIN `${p}users` b USING(globalRoleID) GROUP BY a.globalRoleID";

        $result = $this->MySQL->Query($query);

        if ($result == false) {
            $this->logLastError('global_roles');

            return false;
        }

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

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
        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table             = $this->kga['server_prefix'] . "groups_users";
        $result            = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('group_count_data');

            return false;
        }

        return $this->MySQL->RowCount() === false ? 0 : $this->MySQL->RowCount();
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
        $data = $this->clean_data($data);

        $values ['name'] = $this->MySQL->SQLValue($data ['name']);
        $table           = $this->kga['server_prefix'] . "groups";
        $result          = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('group_create');

            return false;
        }
        else {
            return $this->MySQL->GetLastInsertID();
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
        $values['trash']   = 1;
        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table             = $this->kga['server_prefix'] . "groups";
        $query             = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
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
        $data = $this->clean_data($data);

        $values ['name'] = $this->MySQL->SQLValue($data ['name']);

        $filter ['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table              = $this->kga['server_prefix'] . "groups";

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
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


        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $table             = $this->kga['server_prefix'] . "groups";
        $result            = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('group_get_data');

            return false;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
        }
    }

    public function isConnected()
    {
        return $this->MySQL->IsConnected();
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

        $table  = $this->getActivityTable();
        $filter = array('activityID' => $activityId, 'trash' => 0);

        return $this->rowExists($table, $filter);
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

        $table  = $this->getProjectTable();
        $filter = array('projectID' => $projectId, 'trash' => 0);

        return $this->rowExists($table, $filter);
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
        $name = $this->MySQL->SQLValue($name);
        $p    = $this->kga['server_prefix'];

        $query = "SELECT customerID FROM ${p}customers WHERE name = $name AND trash = 0";

        $this->MySQL->Query($query);

        return $this->MySQL->RowCount() == 1;
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
        $userID = $this->MySQL->SQLValue($user['userID'], MySQL::SQLVALUE_NUMBER);

        $watchableUsers = $this->get_watchable_users($user);
        foreach ($watchableUsers as $watchableUser) {
            if ($watchableUser['userID'] == $userID) {
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
        $table = $this->getUserTable();

        $filter ['userID'] = $this->MySQL->SQLValue($userId);

        $values ['ban'] = "ban+1";
        if ($resetTime) {
            $values ['banTime'] = $this->MySQL->SQLValue(time(), MySQL::SQLVALUE_NUMBER);
        }

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('loginUpdateBan');
        }
    }

    public function membershipRole_find($filter)
    {
        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = $this->MySQL->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
            else {
                $value = $this->MySQL->SQLValue($value);
            }
        }
        $table  = $this->kga['server_prefix'] . "membershipRoles";
        $result = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('membershipRole_find');

            return false;
        }
        else {
            return $this->MySQL->RecordsArray(MYSQL_ASSOC);
        }
    }

    public function membershipRole_get_data($membershipRoleID)
    {
        $filter['membershipRoleID'] = $this->MySQL->SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $table                      = $this->kga['server_prefix'] . "membershipRoles";
        $result                     = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('membershipRole_get_data');

            return false;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
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
        $filter['membershipRoleID'] = $this->MySQL->SQLValue($roleID, MySQL::SQLVALUE_NUMBER);
        $filter[$permission]        = 1;
        $columns[]                  = "membershipRoleID";
        $table                      = $this->kga['server_prefix'] . "membershipRoles";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);

        if ($result === false) {
            return false;
        }

        return $this->MySQL->RowCount() > 0;
    }

    public function membership_role_create($data)
    {
        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->MySQL->SQLValue($value);
            }
            else {
                $values[$key] = $this->MySQL->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $table  = $this->kga['server_prefix'] . "membershipRoles";
        $result = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('membership_role_create');

            return false;
        }

        return $this->MySQL->GetLastInsertID();
    }

    public function membership_role_delete($membershipRoleID)
    {
        $table                      = $this->kga['server_prefix'] . "membershipRoles";
        $filter['membershipRoleID'] = $this->MySQL->SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $query                      = $this->MySQL->BuildSQLDelete($table, $filter);
        $result                     = $this->MySQL->Query($query);

        if ($result == false) {
            $this->logLastError('membership_role_delete');

            return false;
        }

        return true;
    }

    public function membership_role_edit($membershipRoleID, $data)
    {

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->MySQL->SQLValue($value);
            }
            else {
                $values[$key] = $this->MySQL->SQLValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['membershipRoleID'] = $this->MySQL->SQLValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $table                      = $this->kga['server_prefix'] . "membershipRoles";

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    public function membership_roles()
    {
        $p = $this->kga['server_prefix'];

        $query = "SELECT a.*, COUNT(DISTINCT b.userID) as count_users FROM ${p}membershipRoles a LEFT JOIN ${p}groups_users b USING(membershipRoleID) GROUP BY a.membershipRoleID";

        $result = $this->MySQL->Query($query);

        if ($result == false) {
            $this->logLastError('membership_roles');

            return false;
        }

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        return $rows;
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
        $data = $this->clean_data($data);

        $filter['projectID']  = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $filter['activityID'] = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $table                = $this->kga['server_prefix'] . "projects_activities";

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('project_activity_edit [1]');

            return false;
        }

        $query = $this->MySQL->BuildSQLUpdate($table, $data, $filter);
        if ($this->MySQL->Query($query)) {
            if (!$this->MySQL->TransactionEnd()) {
                $this->logLastError('project_activity_edit [2]');

                return false;
            }

            return true;
        }

        $this->logLastError('project_activity_edit [3]');

        if (!$this->MySQL->TransactionRollback()) {
            $this->logLastError('project_activity_edit [4]');

            return false;
        }

        return false;
    }

    /**
     * Adds a new project
     *
     * @param array $data name, comment and other data of the new project
     *
     * @return int         the ID of the new project, false on failure
     * @author th
     */
    public function project_create($data)
    {
        $data = $this->clean_data($data);

        $values['name']       = $this->MySQL->SQLValue($data['name']);
        $values['comment']    = $this->MySQL->SQLValue($data['comment']);
        $values['budget']     = $this->MySQL->SQLValue($data['budget'], MySQL::SQLVALUE_NUMBER);
        $values['effort']     = $this->MySQL->SQLValue($data['effort'], MySQL::SQLVALUE_NUMBER);
        $values['approved']   = $this->MySQL->SQLValue($data['approved'], MySQL::SQLVALUE_NUMBER);
        $values['customerID'] = $this->MySQL->SQLValue($data['customerID'], MySQL::SQLVALUE_NUMBER);
        $values['visible']    = $this->MySQL->SQLValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['internal']   = $this->MySQL->SQLValue($data['internal'], MySQL::SQLVALUE_NUMBER);
        $values['filter']     = $this->MySQL->SQLValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $table  = $this->kga['server_prefix'] . "projects";
        $result = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('project_create');

            return false;
        }

        $projectID = $this->MySQL->GetLastInsertID();

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
                $this->save_rate($this->kga['user']['userID'], $projectID, null, $data['myRate']);
            }
            else {
                $this->remove_rate($this->kga['user']['userID'], $projectID, null);
            }
        }

        if (isset($data['fixedRate'])) {
            if (is_numeric($data['fixedRate'])) {
                $this->save_fixed_rate($projectID, null, $data['fixedRate']);
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


        $values['trash']     = 1;
        $filter['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $table               = $this->getProjectTable();

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    /**
     * Edits a project by replacing its data by the new array
     *
     * @param int   $projectID id of the project to be edited
     * @param array $data      name, comment and other new data of the project
     *
     * @return boolean        true on success, false on failure
     * @author ob/th
     */
    public function project_edit($projectID, $data)
    {
        $data = $this->clean_data($data);

        $strings = array('name', 'comment');
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key]);
            }
        }

        $numbers = array(
            'budget', 'customerID', 'visible', 'internal', 'filter', 'effort', 'approved');
        foreach ($numbers as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            if ($data[$key] == null) {
                $values[$key] = 'NULL';
            }
            else {
                $values[$key] = $this->MySQL->SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter ['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $table                = $this->kga['server_prefix'] . "projects";


        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('project_edit');

            return false;
        }

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        if ($this->MySQL->Query($query)) {

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
                    $this->save_rate($this->kga['user']['userID'], $projectID, null, $data['myRate']);
                }
                else {
                    $this->remove_rate($this->kga['user']['userID'], $projectID, null);
                }
            }

            if (isset($data['fixedRate'])) {
                if (is_numeric($data['fixedRate'])) {
                    $this->save_fixed_rate($projectID, null, $data['fixedRate']);
                }
                else {
                    $this->remove_fixed_rate($projectID, null);
                }
            }

            if (!$this->MySQL->TransactionEnd()) {
                $this->logLastError('project_edit');

                return false;
            }

            return true;
        }
        else {
            $this->logLastError('project_edit');
            if (!$this->MySQL->TransactionRollback()) {
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
        $projectId = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $p         = $this->kga['server_prefix'];

        $query = "SELECT activity.*, activityID, budget, effort, approved
                FROM ${p}projects_activities AS p_a
                JOIN ${p}activities AS activity USING(activityID)
                WHERE projectID = $projectId AND activity.trash=0;";

        $result = $this->MySQL->Query($query);

        if ($result == false) {
            $this->logLastError('project_get_activities');

            return false;
        }

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

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
        if (!is_numeric($projectID)) {
            return false;
        }

        $filter['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $table               = $this->getProjectTable();
        $result              = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('project_get_data');

            return false;
        }

        $result_array                = $this->MySQL->RowArray(0, MYSQL_ASSOC);
        $result_array['defaultRate'] = $this->get_rate(null, $projectID, null);
        $result_array['myRate']      = $this->get_rate($this->kga['user']['userID'], $projectID, null);
        $result_array['fixedRate']   = $this->get_fixed_rate($projectID, null);

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


        $filter['projectID'] = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $columns[]           = "groupID";
        $table               = $this->kga['server_prefix'] . "groups_projects";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('project_get_groupIDs');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->MySQL->RecordsArray(MYSQL_ASSOC);

        if ($this->MySQL->RowCount()) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['groupID'];
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
        return $this->MySQL->QueryArray($query);
    }

    /**
     * Remove fixed rate from database.
     *
     * @author sl
     */
    public function remove_fixed_rate($projectID, $activityID)
    {
        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "DELETE FROM " . $this->kga['server_prefix'] . "fixedRates WHERE " .
            (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
            (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('remove_fixed_rate');

            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Remove rate from database.
     *
     * @author sl
     */
    public function remove_rate($userID, $projectID, $activityID)
    {
        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";


        $query = "DELETE FROM " . $this->kga['server_prefix'] . "rates WHERE " .
            (($userID == "NULL") ? "userID is NULL" : "userID = $userID") . " AND " .
            (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
            (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");

        $result = $this->MySQL->Query($query);

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
        // validate input
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        if (!is_numeric($rate)) return false;


        // build update or insert statement
        if ($this->get_fixed_rate($projectID, $activityID) === false) {
            $query = "INSERT INTO " . $this->kga['server_prefix'] . "fixedRates VALUES($projectID,$activityID,$rate);";
        }
        else {
            $query = "UPDATE " . $this->kga['server_prefix'] . "fixedRates SET rate = $rate WHERE " .
                (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");
        }

        $result = $this->MySQL->Query($query);

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
        // validate input
        if ($userID == null || !is_numeric($userID)) $userID = "NULL";
        if ($projectID == null || !is_numeric($projectID)) $projectID = "NULL";
        if ($activityID == null || !is_numeric($activityID)) $activityID = "NULL";
        if (!is_numeric($rate)) return false;


        // build update or insert statement
        if ($this->get_rate($userID, $projectID, $activityID) === false) {
            $query = "INSERT INTO " . $this->kga['server_prefix'] . "rates VALUES($userID,$projectID,$activityID,$rate);";
        }
        else {
            $query = "UPDATE " . $this->kga['server_prefix'] . "rates SET rate = $rate WHERE " .
                (($userID == "NULL") ? "userID is NULL" : "userID = $userID") . " AND " .
                (($projectID == "NULL") ? "projectID is NULL" : "projectID = $projectID") . " AND " .
                (($activityID == "NULL") ? "activityID is NULL" : "activityID = $activityID");
        }

        $result = $this->MySQL->Query($query);

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

        $values['timeframeBegin'] = $this->MySQL->SQLValue($timeframeBegin, MySQL::SQLVALUE_NUMBER);
        $values['timeframeEnd']   = $this->MySQL->SQLValue($timeframeEnd, MySQL::SQLVALUE_NUMBER);

        $table              = $this->kga['server_prefix'] . "users";
        $filter  ['userID'] = $this->MySQL->SQLValue($user, MySQL::SQLVALUE_NUMBER);


        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        if (!$this->MySQL->Query($query)) {
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
        $table = $this->kga['server_prefix'] . "groups_users";

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('setGroupMemberships');

            return false;
        }

        $data ['userID'] = $this->MySQL->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $result          = $this->MySQL->DeleteRows($table, $data);

        if (!$result) {
            $this->logLastError('setGroupMemberships');
            if (!$this->MySQL->TransactionRollback()) {
                $this->logLastError('setGroupMemberships');
            }

            return false;
        }

        foreach ($groups as $group => $role) {
            $data['groupID']          = $this->MySQL->SQLValue($group, MySQL::SQLVALUE_NUMBER);
            $data['membershipRoleID'] = $this->MySQL->SQLValue($role, MySQL::SQLVALUE_NUMBER);
            $result                   = $this->MySQL->InsertRow($table, $data);
            if ($result === false) {
                $this->logLastError('setGroupMemberships');
                if (!$this->MySQL->TransactionRollback()) {
                    $this->logLastError('setGroupMemberships');
                }

                return false;
            }
        }

        if (!$this->MySQL->TransactionEnd()) {
            $this->logLastError('setGroupMemberships');

            return false;
        }
    }

    /**
     * starts timesheet record
     *
     * @param integer $projectID ID of project to record
     *
     * @author th, sl
     * @return id of the new entry or false on failure
     */
    public function startRecorder($projectID, $activityID, $user, $startTime)
    {
        $projectID  = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);
        $activityID = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);
        $user       = $this->MySQL->SQLValue($user, MySQL::SQLVALUE_NUMBER);
        $startTime  = $this->MySQL->SQLValue($startTime, MySQL::SQLVALUE_NUMBER);

        $values ['projectID']  = $projectID;
        $values ['activityID'] = $activityID;
        $values ['start']      = $startTime;
        $values ['userID']     = $user;
        $values ['statusID']   = $this->kga['conf']['defaultStatusID'];

        $rate = $this->get_best_fitting_rate($user, $projectID, $activityID);
        if ($rate) {
            $values ['rate'] = $rate;
        }

        $table  = $this->kga['server_prefix'] . "timeSheet";
        $result = $this->MySQL->InsertRow($table, $values);

        if (!$result) {
            $this->logLastError('startRecorder');

            return false;
        }

        return $this->MySQL->GetLastInsertID();
    }

    /**
     * add a new status
     *
     * @param Array $statusArray
     */
    public function status_create($status)
    {
        $values['status'] = $this->MySQL->SQLValue(trim($status['status']));

        $table  = $this->kga['server_prefix'] . "statuses";
        $result = $this->MySQL->InsertRow($table, $values);
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
        $filter['statusID'] = $this->MySQL->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table              = $this->kga['server_prefix'] . "statuses";
        $query              = $this->MySQL->BuildSQLDelete($table, $filter);

        return $this->MySQL->Query($query);
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
        $data = $this->clean_data($data);

        $values ['status'] = $this->MySQL->SQLValue($data ['status']);

        $filter ['statusID'] = $this->MySQL->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table               = $this->kga['server_prefix'] . "statuses";

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
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

        $filter['statusID'] = $this->MySQL->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table              = $this->kga['server_prefix'] . "statuses";
        $result             = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('status_get_data');

            return false;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
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
        $filter['statusID'] = $this->MySQL->SQLValue($statusID, MySQL::SQLVALUE_NUMBER);
        $table              = $this->kga['server_prefix'] . "timeSheet";
        $result             = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('status_timeSheetEntryCount');

            return false;
        }

        return $this->MySQL->RowCount() === false ? 0 : $this->MySQL->RowCount();
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
        ## stop running recording |
        $table = $this->kga['server_prefix'] . "timeSheet";

        $activity = $this->timeSheet_get_data($id);

        $filter['timeEntryID'] = $activity['timeEntryID'];
        $filter['end']         = 0; // only update running activities

        $rounded = Rounding::roundTimespan($activity['start'], time(), $this->kga['conf']['roundPrecision'], $this->kga['conf']['allowRoundDown']);

        $values['start']    = $rounded['start'];
        $values['end']      = $rounded['end'];
        $values['duration'] = $values['end'] - $values['start'];


        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    /**
     * create time sheet entry
     *
     * @param integer $id   ID of record
     * @param integer $data array with record data
     *
     * @author th
     */
    public function timeEntry_create($data)
    {
        $data = $this->clean_data($data);

        $values ['location']    = $this->MySQL->SQLValue($data ['location']);
        $values ['comment']     = $this->MySQL->SQLValue($data ['comment']);
        $values ['description'] = $this->MySQL->SQLValue($data ['description']);
        if ($data ['trackingNumber'] == '') {
            $values ['trackingNumber'] = 'NULL';
        }
        else {
            $values ['trackingNumber'] = $this->MySQL->SQLValue($data ['trackingNumber']);
        }
        $values ['userID']      = $this->MySQL->SQLValue($data ['userID'], MySQL::SQLVALUE_NUMBER);
        $values ['projectID']   = $this->MySQL->SQLValue($data ['projectID'], MySQL::SQLVALUE_NUMBER);
        $values ['activityID']  = $this->MySQL->SQLValue($data ['activityID'], MySQL::SQLVALUE_NUMBER);
        $values ['commentType'] = $this->MySQL->SQLValue($data ['commentType'], MySQL::SQLVALUE_NUMBER);
        $values ['start']       = $this->MySQL->SQLValue($data ['start'], MySQL::SQLVALUE_NUMBER);
        $values ['end']         = $this->MySQL->SQLValue($data ['end'], MySQL::SQLVALUE_NUMBER);
        $values ['duration']    = $this->MySQL->SQLValue($data ['duration'], MySQL::SQLVALUE_NUMBER);
        $values ['rate']        = $this->MySQL->SQLValue($data ['rate'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']     = $this->MySQL->SQLValue($data ['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values ['budget']      = $this->MySQL->SQLValue($data ['budget'], MySQL::SQLVALUE_NUMBER);
        $values ['approved']    = $this->MySQL->SQLValue($data ['approved'], MySQL::SQLVALUE_NUMBER);
        $values ['statusID']    = $this->MySQL->SQLValue($data ['statusID'], MySQL::SQLVALUE_NUMBER);
        $values ['billable']    = $this->MySQL->SQLValue($data ['billable'], MySQL::SQLVALUE_NUMBER);

        $table   = $this->getTimeSheetTable();
        $success = $this->MySQL->InsertRow($table, $values);
        if ($success) {
            return $this->MySQL->GetLastInsertID();
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

        $filter["timeEntryID"] = $this->MySQL->SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $table                 = $this->getTimeSheetTable();
        $query                 = $this->MySQL->BuildSQLDelete($table, $filter);

        return $this->MySQL->Query($query);
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
        $data = $this->clean_data($data);

        $original_array = $this->timeSheet_get_data($id);
        $new_array      = array();
        $budgetChange   = 0;
        $approvedChange = 0;

        foreach ($original_array as $key => $value) {
            if (isset($data[$key]) == true) {
                // buget is added to total budget for activity. So if we change the budget, we need
                // to first subtract the previous entry before adding the new one
                //          	if($key == 'budget') {
                //          		$budgetChange = - $value;
                //          	} else if($key == 'approved') {
                //          		$approvedChange = - $value;
                //          	}
                $new_array[$key] = $data[$key];
            }
            else {
                $new_array[$key] = $original_array[$key];
            }
        }

        $values ['description'] = $this->MySQL->SQLValue($new_array ['description']);
        $values ['comment']     = $this->MySQL->SQLValue($new_array ['comment']);
        $values ['location']    = $this->MySQL->SQLValue($new_array ['location']);
        if ($new_array ['trackingNumber'] == '') {
            $values ['trackingNumber'] = 'NULL';
        }
        else {
            $values ['trackingNumber'] = $this->MySQL->SQLValue($new_array ['trackingNumber']);
        }
        $values ['userID']      = $this->MySQL->SQLValue($new_array ['userID'], MySQL::SQLVALUE_NUMBER);
        $values ['projectID']   = $this->MySQL->SQLValue($new_array ['projectID'], MySQL::SQLVALUE_NUMBER);
        $values ['activityID']  = $this->MySQL->SQLValue($new_array ['activityID'], MySQL::SQLVALUE_NUMBER);
        $values ['commentType'] = $this->MySQL->SQLValue($new_array ['commentType'], MySQL::SQLVALUE_NUMBER);
        $values ['start']       = $this->MySQL->SQLValue($new_array ['start'], MySQL::SQLVALUE_NUMBER);
        $values ['end']         = $this->MySQL->SQLValue($new_array ['end'], MySQL::SQLVALUE_NUMBER);
        $values ['duration']    = $this->MySQL->SQLValue($new_array ['duration'], MySQL::SQLVALUE_NUMBER);
        $values ['rate']        = $this->MySQL->SQLValue($new_array ['rate'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']     = $this->MySQL->SQLValue($new_array ['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values ['budget']      = $this->MySQL->SQLValue($new_array ['budget'], MySQL::SQLVALUE_NUMBER);
        $values ['approved']    = $this->MySQL->SQLValue($new_array ['approved'], MySQL::SQLVALUE_NUMBER);
        $values ['statusID']    = $this->MySQL->SQLValue($new_array ['statusID'], MySQL::SQLVALUE_NUMBER);
        $values ['billable']    = $this->MySQL->SQLValue($new_array ['billable'], MySQL::SQLVALUE_NUMBER);

        $filter ['timeEntryID'] = $this->MySQL->SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $table                  = $this->kga['server_prefix'] . "timeSheet";

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('timeEntry_edit');

            return false;
        }
        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        $success = true;

        if (!$this->MySQL->Query($query)) $success = false;

        if ($success) {
            if (!$this->MySQL->TransactionEnd()) {
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
            if (!$this->MySQL->TransactionRollback()) {
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
        $timeEntryID = $this->MySQL->SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $activityID  = $this->MySQL->SQLValue($activityID, MySQL::SQLVALUE_NUMBER);

        $table = $this->kga['server_prefix'] . "timeSheet";

        $filter['timeEntryID'] = $timeEntryID;

        $values['activityID'] = $activityID;

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    /**
     * Just edit the project for an entry. This is used for changing the project
     * of a running entry.
     *
     * @param $timeEntryID id of the timesheet entry
     * @param $projectID   id of the project to change to
     */
    public function timeEntry_edit_project($timeEntryID, $projectID)
    {
        $timeEntryID = $this->MySQL->SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $projectID   = $this->MySQL->SQLValue($projectID, MySQL::SQLVALUE_NUMBER);

        $table = $this->kga['server_prefix'] . "timeSheet";

        $filter['timeEntryID'] = $timeEntryID;

        $values['projectID'] = $projectID;

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        return $this->MySQL->Query($query);
    }

    /**
     * Returns the data of a certain time record
     *
     * @param array $timeEntryID timeEntryID of the record
     *
     * @return array         the record's data (time, activity id, project id etc) as array, false on failure
     * @author th
     */
    public function timeSheet_get_data($timeEntryID)
    {
        $p = $this->kga['server_prefix'];

        $timeEntryID = $this->MySQL->SQLValue($timeEntryID, MySQL::SQLVALUE_NUMBER);

        $table         = $this->getTimeSheetTable();
        $projectTable  = $this->getProjectTable();
        $activityTable = $this->getActivityTable();
        $customerTable = $this->getCustomerTable();

        $select = "SELECT $table.*, $projectTable.name AS projectName, $customerTable.name AS customerName, $activityTable.name AS activityName, $customerTable.customerID AS customerID
      				FROM $table
                	JOIN $projectTable USING(projectID)
                	JOIN $customerTable USING(customerID)
                	JOIN $activityTable USING(activityID)";


        if ($timeEntryID) {
            $result = $this->MySQL->Query("$select WHERE timeEntryID = " . $timeEntryID);
        }
        else {
            $result = $this->MySQL->Query("$select WHERE userID = " . $this->kga['user']['userID'] . " ORDER BY timeEntryID DESC LIMIT 1");
        }

        if (!$result) {
            $this->logLastError('timeSheet_get_data');

            return false;
        }
        else {
            return $this->MySQL->RowArray(0, MYSQL_ASSOC);
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

        if (!is_array($users)) $users = array();
        if (!is_array($customers)) $customers = array();
        if (!is_array($projects)) $projects = array();
        if (!is_array($activities)) $activities = array();


        foreach ($users as $i => $user) {
            $users[$i] = $this->MySQL->SQLValue($user, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($customers as $i => $customer) {
            $customers[$i] = $this->MySQL->SQLValue($customer, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($projects as $i => $project) {
            $projects[$i] = $this->MySQL->SQLValue($project, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($activities as $i => $activity) {
            $activities[$i] = $this->MySQL->SQLValue($activity, MySQL::SQLVALUE_NUMBER);
        }

        $whereClauses = array();

        if (count($users) > 0) {
            $whereClauses[] = "userID in (" . implode(',', $users) . ")";
        }

        if (count($customers) > 0) {
            $whereClauses[] = "customerID in (" . implode(',', $customers) . ")";
        }

        if (count($projects) > 0) {
            $whereClauses[] = "projectID in (" . implode(',', $projects) . ")";
        }

        if (count($activities) > 0) {
            $whereClauses[] = "activityID in (" . implode(',', $activities) . ")";
        }

        return $whereClauses;
    }

    public function transaction_begin()
    {
        return $this->MySQL->TransactionBegin();
    }

    public function transaction_end()
    {
        return $this->MySQL->TransactionEnd();
    }

    public function transaction_rollback()
    {
        return $this->MySQL->TransactionRollback();
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
        $filter ['userID'] = $this->MySQL->SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $columns[]         = "name";
        $table             = $this->kga['server_prefix'] . "users";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('userIDToName');

            return false;
        }

        $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

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
        // find random but unused user id
        do {
            $data['userID'] = random_number(9);
        } while ($this->user_get_data($data['userID']));

        $data = $this->clean_data($data);

        $values ['name']         = $this->MySQL->SQLValue($data['name']);
        $values ['userID']       = $this->MySQL->SQLValue($data['userID'], MySQL::SQLVALUE_NUMBER);
        $values ['globalRoleID'] = $this->MySQL->SQLValue($data['globalRoleID'], MySQL::SQLVALUE_NUMBER);
        $values ['active']       = $this->MySQL->SQLValue($data['active'], MySQL::SQLVALUE_NUMBER);

        // 'mail' and 'password' are just set when actually provided because of compatibility reasons
        if (array_key_exists('mail', $data)) {
            $values['mail'] = $this->MySQL->SQLValue($data['mail']);
        }

        if (array_key_exists('password', $data)) {
            $values['password'] = $this->MySQL->SQLValue($data['password']);
        }

        $table  = $this->kga['server_prefix'] . "users";
        $result = $this->MySQL->InsertRow($table, $values);

        if ($result === false) {
            $this->logLastError('user_create');

            return false;
        }

        if (isset($data['rate'])) {
            if (is_numeric($data['rate'])) {
                $this->save_rate($data['userID'], null, null, $data['rate']);
            }
            else {
                $this->remove_rate($data['userID'], null, null);
            }
        }

        return $data['userID'];
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
        $userID = $this->MySQL->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        if ($moveToTrash) {
            $values['trash']  = 1;
            $filter['userID'] = $userID;
            $table            = $this->kga['server_prefix'] . "users";

            $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

            return $this->MySQL->Query($query);
        }

        $query  = "DELETE FROM " . $this->kga['server_prefix'] . "groups_users WHERE userID = " . $userID;
        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('groups_user_delete');

            return false;
        }

        $query  = "DELETE FROM " . $this->kga['server_prefix'] . "preferences WHERE userID = " . $userID;
        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('preferences_delete');

            return false;
        }

        $query  = "DELETE FROM " . $this->kga['server_prefix'] . "rates WHERE userID = " . $userID;
        $result = $this->MySQL->Query($query);

        if ($result === false) {
            $this->logLastError('rates_delete');

            return false;
        }

        $query  = "DELETE FROM " . $this->kga['server_prefix'] . "users WHERE userID = " . $userID;
        $result = $this->MySQL->Query($query);

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
        $data    = $this->clean_data($data);
        $strings = array('name', 'mail', 'alias', 'password', 'apikey', 'passwordResetHash');
        $values  = array();

        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key]);
            }
        }

        $numbers = array('status', 'trash', 'active', 'lastProject', 'lastActivity', 'lastRecord', 'globalRoleID');
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->MySQL->SQLValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['userID'] = $this->MySQL->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $table            = $this->getUserTable();

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('user_edit transaction begin');

            return false;
        }

        $query = $this->MySQL->BuildSQLUpdate($table, $values, $filter);

        if ($this->MySQL->Query($query)) {
            if (isset($data['rate'])) {
                if (is_numeric($data['rate'])) {
                    $this->save_rate($userID, null, null, $data['rate']);
                }
                else {
                    $this->remove_rate($userID, null, null);
                }
            }

            if (!$this->MySQL->TransactionEnd()) {
                $this->logLastError('user_edit transaction end');

                return false;
            }

            return true;
        }

        if (!$this->MySQL->TransactionRollback()) {
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
        $filter['userID'] = $this->MySQL->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $table            = $this->kga['server_prefix'] . "users";
        $result           = $this->MySQL->SelectRows($table, $filter);

        if (!$result) {
            $this->logLastError('user_get_data');

            return false;
        }

        // return  $this->MySQL->getHTML();
        return $this->MySQL->RowArray(0, MYSQL_ASSOC);
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
        $filter['userID']  = $this->MySQL->SQLValue($userID, MySQL::SQLVALUE_NUMBER);
        $filter['groupID'] = $this->MySQL->SQLValue($groupID, MySQL::SQLVALUE_NUMBER);
        $columns[]         = "membershipRoleID";
        $table             = $this->kga['server_prefix'] . "groups_users";

        $result = $this->MySQL->SelectRows($table, $filter, $columns);

        if ($result === false) {
            return false;
        }

        $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

        return $row['membershipRoleID'];
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
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        $table  = $this->kga['server_prefix'] . "preferences";
        $userId = $this->MySQL->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $key2   = $this->MySQL->SQLValue($key);

        $query = "SELECT value FROM $table WHERE userID = $userId AND option = $key2";

        $this->MySQL->Query($query);

        if ($this->MySQL->RowCount() == 0) {
            return null;
        }

        if ($this->MySQL->RowCount() == 1) {
            $row = $this->MySQL->RowArray(0, MYSQL_NUM);

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
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        $table  = $this->kga['server_prefix'] . "preferences";
        $userId = $this->MySQL->SQLValue($userId, MySQL::SQLVALUE_NUMBER);

        $preparedKeys = array();
        foreach ($keys as $key) {
            $preparedKeys[] = $this->MySQL->SQLValue($key);
        }

        $keysString = implode(",", $preparedKeys);

        $query = "SELECT `option`,`value` FROM $table WHERE userID = $userId AND `option` IN ($keysString)";

        $this->MySQL->Query($query);

        $preferences = array();

        while (!$this->MySQL->EndOfSeek()) {
            $row                         = $this->MySQL->RowArray();
            $preferences[$row['option']] = $row['value'];
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
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        $prefixLength = strlen($prefix);

        $table  = $this->kga['server_prefix'] . "preferences";
        $userId = $this->MySQL->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $prefix = $this->MySQL->SQLValue($prefix . '%');

        $query = "SELECT `option`,`value` FROM $table WHERE userID = $userId AND `option` LIKE $prefix";
        $this->MySQL->Query($query);

        $preferences = array();

        while (!$this->MySQL->EndOfSeek()) {
            $row               = $this->MySQL->RowArray();
            $key               = substr($row['option'], $prefixLength);
            $preferences[$key] = $row['value'];
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
        $p = $this->kga['server_prefix'];
        $u = mysqli_real_escape_string($this->MySQL->mysql_link, $userId);

        $query = "UPDATE ${p}users SET secure='$keymai',ban=0,banTime=0 WHERE userID='" . $u . "';";
        $this->MySQL->Query($query);
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
        return $this->name2id($this->kga['server_prefix'] . "users", 'userID', 'name', $name);
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
        if ($userId === null) {
            $userId = $this->kga['user']['userID'];
        }

        if (!$this->MySQL->TransactionBegin()) {
            $this->logLastError('user_set_preferences');

            return false;
        }

        $table = $this->kga['server_prefix'] . "preferences";

        $filter['userID'] = $this->MySQL->SQLValue($userId, MySQL::SQLVALUE_NUMBER);
        $values['userID'] = $filter['userID'];
        foreach ($data as $key => $value) {
            $values['option'] = $this->MySQL->SQLValue($prefix . $key);
            $values['value']  = $this->MySQL->SQLValue($value);
            $filter['option'] = $values['option'];

            $this->MySQL->AutoInsertUpdate($table, $values, $filter);
        }

        return $this->MySQL->TransactionEnd();
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
        $select = $this->MySQL->SelectRows($table, $filter);

        if (!$select) {
            $this->logLastError('rowExists');

            return false;
        }
        else {
            $rowExits = (bool) $this->MySQL->RowArray(0, MYSQL_ASSOC);

            return $rowExits;
        }
    }

    private function logLastError($scope)
    {
        Logger::logfile($scope . ': ' . $this->MySQL->Error());
    }

    /**
     * Query a table for an id by giving the name of an entry.
     *
     * @author sl
     */
    private function name2id($table, $endColumn, $filterColumn, $value)
    {
        $filter [$filterColumn] = $this->MySQL->SQLValue($value);
        $filter ['trash']       = 0;
        $columns[]              = $endColumn;

        $result = $this->MySQL->SelectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('name2id');

            return false;
        }

        $row = $this->MySQL->RowArray(0, MYSQL_ASSOC);

        if ($row === false) {
            return false;
        }

        return $row[$endColumn];
    }

}