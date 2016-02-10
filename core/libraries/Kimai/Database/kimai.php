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
require WEBROOT . 'libraries/Kimai/Database/mysql.php';

/**
 * Provides the database layer for MySQL.
 *
 * @author th
 * @author sl
 * @author Kevin Papst
 */
class Kimai_Mysql extends MySQL {

    public function activity_create($data) {
        global $kga;

        $data = clean_data($data);

        $values['name']    = $this->sqlValue($data['name']);
        $values['comment'] = $this->sqlValue($data['comment']);
        $values['visible'] = $this->sqlValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter']  = $this->sqlValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $result = $this->insertRow(TBL_ACTIVITY, $values);

        if ( ! $result) {
            $this->logLastError('activity_create');

            return false;
        }

        $activityID = $this->getLastInsertID();

        if (isset($data['default_rate'])) {
            if (is_numeric($data['default_rate'])) {
                $this->save_rate(null, null, $activityID, $data['default_rate']);
            }
            else {
                $this->remove_rate(null, null, $activityID);
            }
        }

        if (isset($data['my_rate'])) {
            if (is_numeric($data['my_rate'])) {
                $this->save_rate($kga['who']['id'], null, $activityID, $data['my_rate']);
            }
            else {
                $this->remove_rate($kga['who']['id'], null, $activityID);
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

    public function activity_delete($activityID) {
        global $kga;

        $values['trash']       = 1;
        $filter['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_ACTIVITY, $values, $filter);

        return $this->query($query);
    }

    public function activity_edit($activityID, $data) {
        global $kga;

        $data   = clean_data($data);
        $values = array();

        $strings = array('name', 'comment');
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key]);
            }
        }

        $numbers = array('visible', 'filter');
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter  ['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);

        if ( ! $this->transactionBegin()) {
            $this->logLastError('activity_edit');

            return false;
        }

        $query = $this->buildSqlUpdate(TBL_ACTIVITY, $values, $filter);

        if ($this->query($query) !== false) {

            if (isset($data['default_rate'])) {
                if (is_numeric($data['default_rate'])) {
                    $this->save_rate(null, null, $activityID, $data['default_rate']);
                }
                else {
                    $this->remove_rate(null, null, $activityID);
                }
            }

            if (isset($data['my_rate'])) {
                if (is_numeric($data['my_rate'])) {
                    $this->save_rate($kga['who']['id'], null, $activityID, $data['my_rate']);
                }
                else {
                    $this->remove_rate($kga['who']['id'], null, $activityID);
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

            if ( ! $this->transactionEnd()) {
                $this->logLastError('activity_edit');

                return false;
            }
        }
        else {
            $this->logLastError('activity_edit');
            if ( ! $this->transactionRollback()) {
                $this->logLastError('activity_edit');

                return false;
            }

            return false;
        }

        return true;
    }

    public function activity_get_data($activityID) {
        global $kga;

        $filter['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $result                = $this->selectRows(TBL_ACTIVITY, $filter);

        if ( ! $result) {
            $this->logLastError('activity_get_data');

            return false;
        }


        $result_array = $this->rowArray(0, MYSQLI_ASSOC);

        $result_array['default_rate'] = $this->get_rate(null, null, $result_array['activity_id']);
        $result_array['my_rate']      = $this->get_rate($kga['who']['id'], null, $result_array['activity_id']);
        $result_array['fixed_rate']   = $this->get_fixed_rate(null, $result_array['activity_id']);

        return $result_array;
    }

    public function activity_get_groupIDs($activityID) {
        global $kga;

        $filter['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]             = "group_id";

        $result = $this->selectRows(TBL_GROUP_ACTIVITY, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groupIDs');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if ($this->num_rows) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['group_id'];
                $counter ++;
            }

            return $groupIDs;
        }
        else {
            return false;
        }
    }

    public function activity_get_groups($activityID) {
        global $kga;

        $filter ['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]              = "group_id";

        $result = $this->selectRows(TBL_GROUP_ACTIVITY, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_groups');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if ($this->num_rows) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['group_id'];
                $counter ++;
            }

            return $groupIDs;
        }
        else {
            return false;
        }
    }

    public function activity_get_projects($activityID) {
        global $kga;

        $filter ['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $columns[]              = "project_id";

        $result = $this->selectRows(TBL_PROJECT_ACTIVITY, $filter, $columns);
        if ($result == false) {
            $this->logLastError('activity_get_projects');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if ($this->num_rows) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['project_id'];
                $counter ++;
            }
        }

        return $groupIDs;
    }

    public function allFittingFixedRates($projectID, $activityID) {
        global $kga;

        // validate input
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        $p = $kga['server_prefix'];

        $query = "SELECT `rate`, `project_id`, `activity_id` FROM {$p}fixed_rate WHERE
                (project_id = $projectID OR project_id IS NULL)  AND
                (activity_id = $activityID OR activity_id IS NULL)
                ORDER BY activity_id DESC , project_id DESC;";

        $result = $this->query($query);

        if ($result === false) {
            $this->logLastError('allFittingFixedRates');

            return false;
        }

        return $this->recordsArray(MYSQLI_ASSOC);
    }

    public function allFittingRates($userID, $projectID, $activityID) {
        global $kga;

        // validate input
        if ($userID == null || ! is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        $p = $kga['server_prefix'];

        $query = "SELECT rate, user_id, project_id, activity_id FROM {$p}rate WHERE
    (user_id = $userID OR user_id IS NULL)  AND
    (project_id = $projectID OR project_id IS NULL)  AND
    (activity_id = $activityID OR activity_id IS NULL)
    ORDER BY user_id DESC, activity_id DESC , project_id DESC;";

        $result = $this->query($query);

        if ($result === false) {
            $this->logLastError('allFittingRates');

            return false;
        }

        return $this->recordsArray(MYSQLI_ASSOC);
    }

    public function assignActivityToProjectsForGroup($activityID, $projectIDs, $group) {
        $projectIds = array_merge($projectIDs, $this->getNonManagableAssignedElementIds("activity", "project", $activityID, $group));

        return $this->assign_activityToProjects($activityID, $projectIds);
    }

    public function assignProjectToActivitiesForGroup($projectID, $activityIDs, $group) {
        $activityIds = array_merge($activityIDs, $this->getNonManagableAssignedElementIds("project", "activity", $projectID, $group));

        return $this->assign_projectToActivities($projectID, $activityIds);
    }

    public function assign_activityToGroups($activityID, $groupIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_activityToGroups');

            return false;
        }

        $filter['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query               = $this->buildSqlDelete(TBL_GROUP_ACTIVITY, $filter);
        $result                = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_activityToGroups');
            $this->transactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['group_id']    = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                 = $this->buildSqlInsert(TBL_GROUP_ACTIVITY, $values);
            $result                = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_activityToGroups');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_activityToGroups');

        return false;
    }

    public function assign_activityToProjects($activityID, $projectIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_activityToProjects');

            return false;
        }

        $filter['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $d_query               = $this->buildSqlDelete(TBL_PROJECT_ACTIVITY, $filter);
        $result                = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_activityToProjects');
            $this->transactionRollback();

            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['project_id']  = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
            $values['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                 = $this->buildSqlInsert(TBL_PROJECT_ACTIVITY, $values);
            $result                = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_activityToProjects');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_activityToProjects');

        return false;
    }

    public function assign_customerToGroups($customerID, $groupIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_customerToGroups');

            return false;
        }

        $filter['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);
        $d_query               = $this->buildSqlDelete(TBL_GROUP_CUSTOMER, $filter);
        $result                = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_customerToGroups');
            $this->transactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {
            $values['group_id']    = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query                 = $this->buildSqlInsert(TBL_GROUP_CUSTOMER, $values);
            $result                = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_customerToGroups');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_customerToGroups');

        return false;
    }

    public function assign_groupToActivities($groupID, $activityIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_groupToActivities');

            return false;
        }

        $filter['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query            = $this->buildSqlDelete(TBL_GROUP_ACTIVITY, $filter);
        $result             = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_groupToActivities');
            $this->transactionRollback();

            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['group_id']    = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
            $query                 = $this->buildSqlInsert(TBL_GROUP_ACTIVITY, $values);
            $result                = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_groupToActivities');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_groupToActivities');

        return false;
    }

    public function assign_groupToCustomers($groupID, $customerIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_groupToCustomers');

            return false;
        }

        $filter['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query            = $this->buildSqlDelete(TBL_GROUP_CUSTOMER, $filter);

        $result = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_groupToCustomers');
            $this->transactionRollback();

            return false;
        }

        foreach ($customerIDs as $customerID) {
            $values['group_id']    = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);
            $query                 = $this->buildSqlInsert(TBL_GROUP_CUSTOMER, $values);
            $result                = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_groupToCustomers');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_groupToCustomers');

        return false;
    }

    public function assign_groupToProjects($groupID, $projectIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_groupToProjects');

            return false;
        }

        $filter['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
        $d_query            = $this->buildSqlDelete(TBL_GROUP_PROJECT, $filter);
        $result             = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_groupToProjects');
            $this->transactionRollback();

            return false;
        }

        foreach ($projectIDs as $projectID) {
            $values['group_id']   = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->buildSqlInsert(TBL_GROUP_PROJECT, $values);
            $result               = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_groupToProjects');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_groupToProjects');

        return false;
    }

    public function assign_projectToActivities($projectID, $activityIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_projectToActivities');

            return false;
        }

        $filter['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $this->buildSqlDelete(TBL_PROJECT_ACTIVITY, $filter);
        $result               = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_projectToActivities');
            $this->transactionRollback();

            return false;
        }

        foreach ($activityIDs as $activityID) {
            $values['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
            $values['project_id']  = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query                 = $this->buildSqlInsert(TBL_PROJECT_ACTIVITY, $values);
            $result                = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_projectToActivities');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd() == true) {
            return true;
        }

        $this->logLastError('assign_projectToActivities');

        return false;
    }

    public function assign_projectToGroups($projectID, $groupIDs) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('assign_projectToGroups');

            return false;
        }

        $filter['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $d_query              = $this->buildSqlDelete(TBL_GROUP_PROJECT, $filter);
        $result               = $this->query($d_query);

        if ($result === false) {
            $this->logLastError('assign_projectToGroups');
            $this->transactionRollback();

            return false;
        }

        foreach ($groupIDs as $groupID) {

            $values['group_id']   = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
            $values['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
            $query                = $this->buildSqlInsert(TBL_GROUP_PROJECT, $values);
            $result               = $this->query($query);

            if ($result === false) {
                $this->logLastError('assign_projectToGroups');
                $this->transactionRollback();

                return false;
            }
        }

        if ($this->transactionEnd()) {
            return true;
        }

        $this->logLastError('assign_projectToGroups');

        return false;
    }

    public function config_load($delete_deprecated = true) {
        global $kga;
        $entries = array();

        $this->selectRows(TBL_CONFIGURATION);

        $this->moveFirst();
        while ( ! $this->endOfSeek()) {
            $row = $this->row();
            if ( ! config_set($row->option, $row->value)) {
                $entries[$row->option] = $row->value;
            }
        }

        config_bill_pct();

        $this->pref_defaults_load();

        //delete deprecated configuration entries
        if ($delete_deprecated) {
            $tbl = TBL_CONFIGURATION;
            foreach ($entries as $opt => $val) {
                logger::logfile("Configuration: deleted deprecated entry <${opt}>, value <${val}>.");
                $this->query("DELETE FROM `${tbl}` WHERE `option` = '${opt}';");
            }
        }
    }

    public function config_replace() {
        global $kga;

        $K = $kga['conf'];

        $values  = array();
        $columns = array('option', 'value');
        foreach ($K as $opt => $val) {
            $values [] = array(
                $this->sqlValue($opt),
                $this->sqlValue($val),
            );
        }

        if (count($values) > 0) {
            $query = $this->buildSqlReplace(TBL_CONFIGURATION, $columns, $values);

            if ($this->query($query) === false) {
                logger::logfile('There was an error updating configuration table.');

                return false;
            }

            return true;
        }

        return false;
    }

    public function customer_create($data) {
        global $kga;

        // find random but unused user id
        do {
            $values['customer_id'] = random_number(9);
        } while ($this->pref_exists($values['customer_id']));
        $this->pref_replace(array('is_customer' => '1'), '', $values['customer_id']);

        if (DEMO_MODE) {
            $data['password'] = password_encrypt('demo');
        }

        $data = clean_data($data);

        $values['name']    = $this->sqlValue($data   ['name']);
        $values['comment'] = $this->sqlValue($data   ['comment']);
        if (isset($data['password'])) {
            $values['password'] = $this->sqlValue($data   ['password']);
        }
        else {
            $values['password'] = "''";
        }
        $values['company']  = $this->sqlValue($data['company']);
        $values['vat_rate'] = $this->sqlValue($data['vat_rate']);
        $values['contact']  = $this->sqlValue($data['contact']);
        $values['street']   = $this->sqlValue($data['street']);
        $values['zipcode']  = $this->sqlValue($data['zipcode']);
        $values['city']     = $this->sqlValue($data['city']);
        $values['phone']    = $this->sqlValue($data['phone']);
        $values['fax']      = $this->sqlValue($data['fax']);
        $values['mobile']   = $this->sqlValue($data['mobile']);
        $values['mail']     = $this->sqlValue($data['mail']);
        $values['homepage'] = $this->sqlValue($data['homepage']);
        $values['timezone'] = $this->sqlValue($data['timezone']);

        $values['visible'] = $this->sqlValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['filter']  = $this->sqlValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        $result = $this->insertRow(TBL_CUSTOMER, $values);

        if ( ! $result) {
            $this->logLastError('customer_create');

            return false;
        }

        $this->pref_defaults_to_user($data['customer_id']);
        $this->pref_replace(array('timezone' => $data['timezone']), 'ui.', $data['customer_id']);

        return $data['customer_id'];
    }

    public function customer_data_load($customer_id) {
        global $kga;

        if ( ! $customer_id) {
            return;
        }

        $filter['customer_id'] = $this->sqlValue($customer_id, MySQL::SQLVALUE_NUMBER);

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
        $columns[] = "timeframe_begin";
        $columns[] = "timeframe_end";

        $this->selectRows(TBL_CUSTOMER, $filter, $columns);
        $rows = $this->rowArray(0, MYSQLI_ASSOC);
        foreach ($rows as $key => $value) {
            $kga['customer'][$key] = $value;
        }

        $kga['customer']['groups'] = $this->customer_get_group_ids($customer_id);

    }

    public function customer_delete($customerID) {
        global $kga;

        $values['trash']       = 1;
        $filter['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_CUSTOMER, $values, $filter);

        return $this->query($query);
    }

    public function customer_edit($customerID, $data) {
        global $kga;

        if (DEMO_MODE) {
            $data['password'] = password_encrypt('demo');
        }

        $data = clean_data($data);

        $values = array();

        $strings = array(
            'name',
            'comment',
            'password',
            'company',
            'contact',
            'street',
            'zipcode',
            'city',
            'phone',
            'fax',
            'mobile',
            'mail',
            'homepage',
            'timezone',
            'password_reset_hash',
        );
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key]);
            }
        }

        $numbers = array(
            'visible',
            'vat_rate',
            'filter',
        );
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_CUSTOMER, $values, $filter);

        return $this->query($query);
    }

    public function customer_get_data($customerID) {
        global $kga;

        $filter['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);
        $result                = $this->selectRows(TBL_CUSTOMER, $filter);

        if ( ! $result) {
            $this->logLastError('customer_get_data');

            return false;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function customer_get_group_ids($customerID) {
        global $kga;

        $filter['customer_id'] = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);
        $columns[]             = 'group_id';


        $result = $this->selectRows(TBL_GROUP_CUSTOMER, $filter, $columns);

        if ($result == false) {
            $this->logLastError('customer_get_group_ids');

            return null;
        }

        $grp_id = array();
        if ($this->num_rows) {
            while ( ! $this->endOfSeek()) {
                $row      = $this->row();
                $grp_id[] = $row->group_id;
            }
        }

        return $grp_id;
    }

    public function customer_get_groups($customer_id, $use_for = null) {
        global $kga;

        $p = $kga['server_prefix'];

        if ($use_for === 'select') {
            $query = "select `g`.`group_id`, `g`.`name`
                    from `{$p}group` as `g`
                    inner join `{$p}group_customer` as `gc` on gc.group_id = g.group_id
                            and `gc`.`customer_id` = {$customer_id}
                    where  `g`.`trash` = 0
                    order by `g`.`name`;";
        }

        if ($this->query($query) === false) {
            $this->logLastError(__FUNCTION__);

            return false;
        }

        $groups = array();
        if ($this->num_rows) {
            if ($use_for === 'select') {
                while ( ! $this->endOfSeek()) {
                    $row                    = $this->row();
                    $groups[$row->group_id] = $row->name;
                }

            }
        }

        return $groups;
    }

    public function customer_loginSetKey($customerId, $keymai) {
        global $kga;

        $p          = $kga['server_prefix'];
        $customerId = mysqli_real_escape_string($this->link, $customerId);

        $query = "UPDATE {$p}customer
                SET secure='$keymai'
                WHERE customer_id='" . $customerId . "';";

        //DEBUG// error_log('<<== SETTING CUSTOMER SECURE KEY ==>>' . PHP_EOL . $query);

        $result = $this->query($query);

        if (mysqli_affected_rows($this->link) < 1) {
            error_log('<<== FAILED SETTING CUSTOMER SECURE KEY ==>>' . PHP_EOL . $query);
        }
    }

    public function customer_nameToID($name) {
        global $kga;

        return $this->name2id(TBL_CUSTOMER, 'customer_id', 'name', $name);
    }

    public function customers_get(array $groups = null, $use_for = null) {
        global $kga;

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "select `customer_id`, `name`, `contact`, `visible`
              from {$p}customer
              where trash=0
              order by visible desc, name;";
        }
        else {
            $G     = implode($groups, ',');
            $query = "select distinct `customer_id`, `name`, `contact`, `visible`
              from {$p}customer
              inner join {$p}group_customer as g_c using (customer_id)
              where g_c.group_id in ({$G})
                and trash=0
              order by visible desc, name;";
        }

        if ($this->query($query) === false) {
            $this->logLastError('customers_get');

            return false;
        }

        if ($this->num_rows > 0) {
            $arr = array();
            if ($use_for === 'select') {
                while ( ! $this->endOfSeek()) {
                    $row                    = $this->row();
                    $arr[$row->customer_id] = $row->name;
                }
            }
            else {
                $i = 0;
                while ( ! $this->endOfSeek()) {
                    $row                    = $this->row();
                    $arr[$i]['customer_id'] = $row->customer_id;
                    $arr[$i]['name']        = $row->name;
                    $arr[$i]['contact']     = $row->contact;
                    $arr[$i]['visible']     = $row->visible;
                    $i ++;
                }
            }

            return $arr;

        }

        return array();
    }

    public function getNonManagableAssignedElementIds($parentSubject, $subject, $parentId, $group) {
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
                    if ( ! $seen) {
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

    public function getUserByApiKey($apikey) {
        global $kga;

        if ( ! $apikey || strlen(trim($apikey)) === 0) {
            return null;
        }

        $filter['apikey'] = $this->sqlValue($apikey, MySQL::SQLVALUE_TEXT);
        $filter['trash']  = $this->sqlValue(0, MySQL::SQLVALUE_NUMBER);

        // get values from user record
        $columns[] = 'user_id';
        $columns[] = 'name';

        $this->selectRows(TBL_USER, $filter, $columns);
        $row = $this->rowArray(0, MYSQLI_ASSOC);

        return $row['name'];
    }

    public function get_DBversion() {
        global $kga;

        $columns[] = 'value';
        $table     = TBL_CONFIGURATION;
        $test      = 0;

        // VERSION //
        $test             = 1;
        $filter['option'] = $this->sqlValue('core.version'); //kimai-i v0.9.4+
        $result           = $this->selectRows($table, $filter, $columns);

        if ($result === false || $this->num_rows === 0) {
            $test             = 2;
            $filter['option'] = $this->sqlValue('version'); //kimai v0.9.3-
            $result           = $this->selectRows($table, $filter, $columns);
        }

        if ($result === false || $this->num_rows === 0) {
            // before database revision 1369 (503 + 866)
            $test          = 3;
            $table         = $kga['server_prefix'] . 'var';
            $filter        = array();
            $filter['var'] = $this->sqlValue('version');
            $result        = $this->selectRows($table, $filter, $columns);
        }

        if ($result === false || $this->num_rows === 0) {
            $test     = 4;
            $return[] = '0.5.1'; // [0]
        }
        else {
            $row      = $this->rowArray(0, MYSQLI_ASSOC);
            $return[] = $row['value']; // [0]
        }

        $filter = array();
        $result = false;


        // DB REVISION //
        switch ($test) {
            case 0:
                die('Could not find the version in the database.');

            case 1:
                $filter['option'] = $this->sqlValue('core.revision'); //kimai-i v0.9.4+
                $result           = $this->selectRows($table, $filter, $columns);
                break;

            case 2:
                $filter['option'] = $this->sqlValue('revision'); //kimai v0.9.3-
                $result           = $this->selectRows($table, $filter, $columns);
                break;

            case 3:
            case 4:
            case 5:
            default:
                // before database revision 1369 (503 + 866)
                $filter['var'] = $this->sqlValue('revision');
                $this->selectRows($table, $filter, $columns);
        }

        if ($this->num_rows > 0) {
            $row      = $this->rowArray(0, MYSQLI_ASSOC);
            $return[] = $row['value']; // [1]
        }
        else {
            $return[] = '9999'; // error, did not find db-version
        }


        //DEBUG//
        if ($_SERVER['DOCUMENT_URI'] === '/updater.php'
            && IN_DEV
        ) {
            error_log('<<== ' . __FUNCTION__ . '  VERSION ==>' . $return[0] . '<== REVISION ==>' . $return[1] . '<==>>');
        }

        return $return;
    }

    public function get_activities(array $groups = []) {
        global $kga;

        $p = $kga['server_prefix'];

        if (count($groups) === 0) {
            $query = "SELECT activity_id, `name`, visible
              FROM {$p}activity
              WHERE trash=0
              ORDER BY visible DESC, `name`;";
        }
        else {
            $query = "SELECT DISTINCT `activity_id`, `name`, `visible`
              FROM {$p}activity
              INNER JOIN {$p}group_activity AS g_a USING(activity_id)
              WHERE g_a.group_id IN (" . implode($groups, ',') . ")
                AND trash=0
              ORDER BY visible DESC, name;";
        }

        if ($this->query($query) === false) {
            $this->logLastError('get_activities');

            return false;
        }

        $arr = array();
        $i   = 0;
        if ($this->num_rows) {
            $this->moveFirst();
            while ( ! $this->endOfSeek()) {
                $row                    = $this->row();
                $arr[$i]['activity_id'] = $row->activity_id;
                $arr[$i]['name']        = $row->name;
                $arr[$i]['visible']     = $row->visible;
                $i ++;
            }
        }

        return $arr;
    }

    public function get_activities_by_customer($customer_ID) {
        global $kga;

        $p = $kga['server_prefix'];

        $customer_ID = $this->sqlValue($customer_ID, MySQL::SQLVALUE_NUMBER);

        $query = "SELECT DISTINCT `activity_id`, `name`, `visible`
          FROM {$p}activity
          WHERE activity_id IN
              (SELECT activity_id FROM {$p}timesheet
                WHERE project_id IN (SELECT project_id FROM {$p}project WHERE customer_id = $customer_ID))
            AND trash=0";

        if ($this->query($query) === false) {
            $this->logLastError('get_activities_by_customer');

            return false;
        }

        $arr = array();
        $i   = 0;

        if ($this->num_rows) {
            $this->moveFirst();
            while ( ! $this->endOfSeek()) {
                $row                    = $this->row();
                $arr[$i]['activity_id'] = $row->activity_id;
                $arr[$i]['name']        = $row->name;
                $arr[$i]['visible']     = $row->visible;
                $i ++;
            }

            return $arr;
        }
        else {
            return array();
        }
    }

    public function get_activities_by_project($projectID, array $groups = null) {
        global $kga;

        $projectID = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM {$p}activity AS activity
            LEFT JOIN {$p}project_activity AS p_a USING(activity_id)
            WHERE activity.trash=0
              AND (project_id = $projectID OR project_id IS NULL)
            ORDER BY visible DESC, name;";
        }
        else {
            $query = "SELECT DISTINCT activity.*, p_a.budget, p_a.approved, p_a.effort
            FROM {$p}activity AS activity
            INNER JOIN {$p}group_activity USING(activity_id)
            LEFT JOIN {$p}project_activity p_a USING(activity_id)
            WHERE `{$p}group_activity`.`group_id`  IN (" . implode($groups, ',') . ")
              AND activity.trash=0
              AND (project_id = $projectID OR project_id IS NULL)
            ORDER BY visible DESC, name;";
        }

        if ($this->query($query) === false) {
            $this->logLastError('get_activities_by_project');

            return false;
        }

        $arr = array();
        if ($this->num_rows) {
            $this->moveFirst();
            while ( ! $this->endOfSeek()) {
                $row                                   = $this->row();
                $arr[$row->activity_id]['activity_id'] = $row->activity_id;
                $arr[$row->activity_id]['name']        = $row->name;
                $arr[$row->activity_id]['visible']     = $row->visible;
                $arr[$row->activity_id]['budget']      = $row->budget;
                $arr[$row->activity_id]['approved']    = $row->approved;
                $arr[$row->activity_id]['effort']      = $row->effort;
            }

            return $arr;
        }
        else {
            return array();
        }
    }

    public function get_activity_budget($projectID, $activityID) {
        global $kga;

        // validate input
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        $p = $kga['server_prefix'];

        $query = "SELECT budget, approved, effort FROM {$p}project_activity WHERE " .
                 (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");


        if ($this->query($query) === false) {
            $this->logLastError('get_activity_budget');

            return false;
        }
        $data = $this->rowArray(0, MYSQLI_ASSOC);
        if ( ! isset($data['budget'])) {
            $data['budget'] = 0;
        }
        if ( ! isset($data['approved'])) {
            $data['approved'] = 0;
        }

        $timeSheet = $this->get_timesheet(0, time(), null, null, array($projectID), array($activityID));
        foreach ($timeSheet as $timesheet_entry) {
            if (isset($timesheet_entry['budget'])) {
                $data['budget'] += $timesheet_entry['budget'];
            }
            if (isset($timesheet_entry['approved'])) {
                $data['approved'] += $timesheet_entry['approved'];
            }
        }

        return $data;
    }

    public function get_best_fitting_fixed_rate($projectID, $activityID) {
        global $kga;

        // validate input
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $p     = $kga['server_prefix'];
        $query = "SELECT rate FROM {$p}fixed_rate
                    WHERE (project_id = $projectID OR project_id IS NULL)
                        AND (activity_id = $activityID OR activity_id IS NULL)
                    ORDER BY activity_id DESC,
                            project_id DESC
                    LIMIT 1;";

        if ($this->query($query) === false) {
            $this->logLastError('get_best_fitting_fixed_rate');

            return false;
        }

        if ($this->num_rows === 0) {
            return false;
        }

        $data = $this->rowArray(0, MYSQLI_ASSOC);

        return $data['rate'];
    }

    public function get_best_fitting_rate($userID, $projectID, $activityID) {
        global $kga;

        // validate input
        if ($userID == null || ! is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        $p = $kga['server_prefix'];


        $query = "SELECT rate FROM {$p}rate WHERE
                (user_id = $userID OR user_id IS NULL)  AND
                (project_id = $projectID OR project_id IS NULL)  AND
                (activity_id = $activityID OR activity_id IS NULL)
                ORDER BY user_id DESC, activity_id DESC , project_id DESC
                LIMIT 1;";

        if ($this->query($query) === false) {
            $this->logLastError('get_best_fitting_rate');

            return false;
        }

        if ($this->num_rows === 0) {
            return false;
        }

        $data = $this->rowArray(0, MYSQLI_ASSOC);

        return $data['rate'];
    }

    public function get_budget_used($projectID, $activityID) {
        $timeSheet  = $this->get_timesheet(0, time(), null, null, array($projectID), array($activityID));
        $budgetUsed = 0;
        if (is_array($timeSheet)) {
            foreach ($timeSheet as $timesheet_entry) {
                $budgetUsed += $timesheet_entry['wage_decimal'];
            }
        }

        return $budgetUsed;
    }

    public function get_current_recordings($userID) {
        global $kga;

        $p      = $kga['server_prefix'];
        $userID = $this->sqlValue($userID, MySQL::SQLVALUE_NUMBER);
        $query  = "SELECT time_entry_id FROM {$p}timesheet WHERE user_id = $userID AND start > 0 AND end = 0";

        if ($this->query($query) === false) {
            $this->logLastError('get_current_recordings');

            return array();
        }

        $IDs = array();

        $this->moveFirst();
        while ( ! $this->endOfSeek()) {
            $row   = $this->row();
            $IDs[] = $row->time_entry_id;
        }

        return $IDs;
    }

    public function get_current_timer() {   //CN..needed when reopening webpage while one timer is active, to show time at current timer time.
        global $kga;

        $user = $this->sqlValue($kga['who']['id'], MySQL::SQLVALUE_NUMBER);
        $p    = $kga['server_prefix'];

        $result = $this->query("SELECT time_entry_id, start FROM {$p}timesheet WHERE user_id = $user AND end = 0;");

        if ($result->num_rows === 0) {
            $current_timer['all']  = 0;
            $current_timer['hour'] = 0;
            $current_timer['min']  = 0;
            $current_timer['sec']  = 0;
        }
        else {

            $row = $this->rowArray(0, MYSQLI_ASSOC);

            $start = (int) $row['start'];

            $currentTime           = Format::hourminsec(time() - $start);
            $current_timer['all']  = $start;
            $current_timer['hour'] = $currentTime['h'];
            $current_timer['min']  = $currentTime['i'];
            $current_timer['sec']  = $currentTime['s'];
        }

        return $current_timer;
    }

    public function get_duration($start, $end, $users = null, $customers = null, $projects = null, $activities = null, $filterCleared = null) {
        global $kga;

        if ( ! is_numeric($filterCleared)) {
            $filterCleared = $kga['pref']['hide_cleared_entries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start = $this->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->sqlValue($end, MySQL::SQLVALUE_NUMBER);


        $p            = $kga['server_prefix'];
        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }
        if ($filterCleared > - 1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        $query = "SELECT `start`, `end`, `duration` FROM `{$p}timesheet`
              Join {$p}project USING(project_id)
              Join {$p}customer USING(customer_id)
              Join {$p}user USING(user_id)
              Join {$p}activity USING(activity_id) "
                 . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);
        $this->query($query);

        $this->moveFirst();
        $sum             = 0;
        $consideredStart = 0; // Consider start of selected range if real start is before
        $consideredEnd   = 0; // Consider end of selected range if real end is afterwards
        while ( ! $this->endOfSeek()) {
            $row = $this->row();
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

    public function get_fixed_rate($projectID, $activityID) {
        global $kga;


        $p = $kga['server_prefix'];
        $P = ($projectID == null || ! is_numeric($projectID)) ? "project_id is NULL" : "project_id = $projectID";
        $A = ($activityID == null || ! is_numeric($activityID)) ? "activity_id is NULL" : "project_id = $activityID";

        $query = "SELECT `rate` FROM {$p}fixed_rate WHERE ${P} AND ${A}";

        if ($this->query($query) === false) {
            $this->logLastError('get_fixed_rate');

            return false;
        }

        if ($this->num_rows === 0) {
            return false;
        }

        $data = $this->rowArray(0, MYSQLI_ASSOC);

        return $data['rate'];
    }

    public function get_latest_running_entry() {
        global $kga;

        $table         = TBL_TIMESHEET;
        $projectTable  = TBL_PROJECT;
        $activityTable = TBL_ACTIVITY;
        $customerTable = TBL_CUSTOMER;

        $select = "SELECT $table.*,
                        $projectTable.name AS project_name,
                        $customerTable.name AS customer_name,
                        $activityTable.name AS activity_name,
                        $customerTable.customer_id AS customer_id
                  FROM $table
                  INNER JOIN $projectTable USING(project_id)
                  INNER JOIN $customerTable USING(customer_id)
                  INNER JOIN $activityTable USING(activity_id)";

        $result = $this->query("$select WHERE end = 0 AND user_id = {$kga['who']['id']} ORDER BY time_entry_id DESC LIMIT 1");

        if ($result->num_rows === 0) {
            return null;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function get_projects(array $groups = null) {
        global $kga;

        $p = $kga['server_prefix'];

        if ($groups === null) {
            $query = "SELECT project.*, customer.name AS customer_name
                  FROM {$p}project AS project
                  LEFT JOIN {$p}customer AS customer USING(customer_id)
                  WHERE project.trash=0";
        }
        else {
            $query = "SELECT DISTINCT project.*, customer.name AS customer_name
                  FROM {$p}project AS project
                  LEFT JOIN {$p}customer AS customer USING (customer_id)
                  INNER JOIN {$p}group_project USING (project_id)
                  WHERE {$p}group_project.group_id IN (" . implode($groups, ',') . ")
                  AND project.trash=0";
        }

        if ($kga['pref']['flip_project_display']) {
            $query .= " ORDER BY project.visible DESC, customer_name, name;";
        }
        else {
            $query .= " ORDER BY project.visible DESC, `name`, customer_name;";
        }

        //DEBUG error_log(__FUNCTION__ . '== QUERY ==>' . $query, 0);

        if ($this->query($query) === false) {
            $this->logLastError('get_projects');

            return false;
        }

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if ($rows) {
            $arr = array();
            $i   = 0;
            foreach ($rows as $row) {
                $arr[$i]['project_id']    = $row['project_id'];
                $arr[$i]['customer_id']   = $row['customer_id'];
                $arr[$i]['name']          = $row['name'];
                $arr[$i]['comment']       = $row['comment'];
                $arr[$i]['visible']       = $row['visible'];
                $arr[$i]['filter']        = $row['filter'];
                $arr[$i]['trash']         = $row['trash'];
                $arr[$i]['budget']        = $row['budget'];
                $arr[$i]['effort']        = $row['effort'];
                $arr[$i]['approved']      = $row['approved'];
                $arr[$i]['internal']      = $row['internal'];
                $arr[$i]['customer_name'] = $row['customer_name'];
                $i ++;
            }

            return $arr;
        }

        return array();
    }

    public function get_projects_by_customer($customerID, array $groups = null) {
        global $kga;

        $customerID = $this->sqlValue($customerID, MySQL::SQLVALUE_NUMBER);
        $p          = $kga['server_prefix'];

        if ($kga['pref']['flip_project_display']) {
            $sort = "customer_name, name";
        }
        else {
            $sort = "name, customer_name";
        }

        if ($groups === null) {
            $query = "SELECT project.*, customer.name AS customer_name
                  FROM {$p}project AS project
                  INNER JOIN {$p}customer AS customer USING(customer_id)
                  WHERE customer_id = $customerID
                    AND project.internal=0
                    AND project.trash=0
                  ORDER BY $sort;";
        }
        else {
            $query = "SELECT DISTINCT project.*, customer.name AS customer_name
                    FROM {$p}project AS project
                    INNER JOIN {$p}customer AS customer USING(customer_id)
                    INNER JOIN {$p}group_project USING(project_id)
                    WHERE {$p}group_project.group_id  IN (" . implode($groups, ',') . ")
                        AND customer_id = $customerID
                        AND project.internal=0
                        AND project.trash=0
                    ORDER BY $sort;";
        }

        $this->query($query);

        $arr = array();
        $i   = 0;

        $this->moveFirst();
        while ( ! $this->endOfSeek()) {
            $row                      = $this->row();
            $arr[$i]['project_id']    = $row->project_id;
            $arr[$i]['name']          = $row->name;
            $arr[$i]['customer_name'] = $row->customer_name;
            $arr[$i]['customer_id']   = $row->customer_id;
            $arr[$i]['visible']       = $row->visible;
            $arr[$i]['budget']        = $row->budget;
            $arr[$i]['effort']        = $row->effort;
            $arr[$i]['approved']      = $row->approved;
            $i ++;
        }

        return $arr;
    }

    public function get_rate($userID, $projectID, $activityID) {
        global $kga;

        // validate input
        if ($userID == null || ! is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }

        $p = $kga['server_prefix'];
        $U = (($userID == "NULL") ? "user_id is NULL" : "user_id = $userID");
        $P = (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID");
        $A = (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");

        $query = "SELECT rate
                    FROM {$p}rate
                    WHERE ${U}
                        AND ${P}
                        AND ${A}";

        $result = $this->query($query);

        if ($result->num_rows === 0) {
            return false;
        }

        $data = $this->rowArray(0, MYSQLI_ASSOC);

        return $data['rate'];
    }

    public function get_seq($user) {
        global $kga;

        if (strncmp($user, 'customer_', 9) === 0) {
            $filter['name']  = $this->sqlValue(substr($user, 9));
            $filter['trash'] = 0;
            $table           = TBL_CUSTOMER;
        }
        else {
            $filter['name']  = $this->sqlValue($user);
            $filter['trash'] = 0;
            $table           = TBL_USER;
        }

        $columns[] = "secure";

        $result = $this->selectRows($table, $filter, $columns);
        //DEBUG// error_log('<<== FETCH SECURE ==>'. $this->last_sql);

        if ($result == false) {
            $this->logLastError('get_seq');

            return false;
        }

        $row = $this->rowArray(0, MYSQLI_ASSOC);

        //DEBUG// error_log('<<== SECURE IS ==>'. $row['secure']);
        return $row['secure'];
    }

    public function get_time_activities($start, $end, $users = null, $customers = null, $projects = null, $activities = null) {
        global $kga;

        $start = $this->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->sqlValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "{$p}activity.trash = 0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT `start`, `end`, activity_id, (`end` - `start`) / 3600 * rate AS costs
          FROM {$p}timesheet
          Left Join {$p}activity USING(activity_id)
          Left Join {$p}project USING(project_id)
          Left Join {$p}customer USING(customer_id) " .
                 (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        if ($this->query($query) === false) {
            $this->logLastError('get_time_activities');

            return array();
        }

        $rows = $this->recordsArray(MYSQLI_ASSOC);
        if ( ! $rows) {
            return array();
        }

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

            if (isset($arr[$row['activity_id']])) {
                $arr[$row['activity_id']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['activity_id']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['activity_id']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['activity_id']]['costs'] = (double) $row['costs'];
            }
        }

        return $arr;
    }

    public function get_time_customers($start, $end, $users = null, $customers = null, $projects = null, $activities = null) {
        global $kga;

        $start = $this->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->sqlValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "{$p}customer.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }


        $query = "SELECT `start`,`end`, customer_id, (`end` - `start`) / 3600 * rate AS costs
              FROM {$p}timesheet
              Left Join {$p}project USING(project_id)
              Left Join {$p}customer USING(customer_id) " .
                 (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        if ($this->query($query) === false) {
            $this->logLastError('get_time_customers');

            return array();
        }
        $rows = $this->recordsArray(MYSQLI_ASSOC);
        if ( ! $rows) {
            return array();
        }

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

            if (isset($arr[$row['customer_id']])) {
                $arr[$row['customer_id']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['customer_id']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['customer_id']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['customer_id']]['costs'] = (double) $row['costs'];
            }
        }

        return $arr;
    }

    public function get_time_projects($start, $end, $users = null, $customers = null, $projects = null, $activities = null) {
        global $kga;

        $start = $this->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->sqlValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "{$p}project.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT `start`, `end` ,project_id, (`end` - `start`) / 3600 * rate AS costs
          FROM {$p}timesheet
          Left Join {$p}project USING(project_id)
          Left Join {$p}customer USING(customer_id) " .
                 (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses);

        if ($this->query($query) === false) {
            $this->logLastError('get_time_projects');

            return array();
        }
        $rows = $this->recordsArray(MYSQLI_ASSOC);
        if ( ! $rows) {
            return array();
        }

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

            if (isset($arr[$row['project_id']])) {
                $arr[$row['project_id']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['project_id']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['project_id']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['project_id']]['costs'] = (double) $row['costs'];
            }
        }

        return $arr;
    }

    public function get_time_users($start, $end, $users = null, $customers = null, $projects = null, $activities = null) {
        global $kga;

        $start = $this->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $this->sqlValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses   = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);
        $whereClauses[] = "`{$p}user`.trash=0";

        if ($start) {
            $whereClauses[] = "end > $start";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }

        $query = "SELECT `start`, `end`, `user_id`, (`end` - `start`) / 3600 * `rate` AS costs
              FROM {$p}timesheet
              Join {$p}project USING(project_id)
              Join {$p}customer USING(customer_id)
              Join `{$p}user` USING(user_id)
              Join {$p}activity USING(activity_id) "
                 . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) . " ORDER BY `start` DESC;";

        if ($this->query($query) === false) {
            $this->logLastError('get_time_users');

            return array();
        }

        $rows = $this->recordsArray(MYSQLI_ASSOC);
        if ( ! $rows) {
            return array();
        }

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

            if (isset($arr[$row['user_id']])) {
                $arr[$row['user_id']]['time'] += (int) ($consideredEnd - $consideredStart);
                $arr[$row['user_id']]['costs'] += (double) $row['costs'];
            }
            else {
                $arr[$row['user_id']]['time']  = (int) ($consideredEnd - $consideredStart);
                $arr[$row['user_id']]['costs'] = (double) $row['costs'];
            }
        }

        return $arr;
    }

    public function get_timesheet(
        $start, $end, $users = null, $customers = null, $projects = null, $activities = null,
        $limit = false, $reverse_order = false, $filterCleared = null, $startRows = 0,
        $limitRows = 0, $countOnly = false
    ) {
        global $kga;

        if ( ! is_numeric($filterCleared)) {
            $filterCleared = $kga['pref']['hide_cleared_entries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start         = $this->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end           = $this->sqlValue($end, MySQL::SQLVALUE_NUMBER);
        $filterCleared = $this->sqlValue($filterCleared, MySQL::SQLVALUE_NUMBER);
        $limit         = $this->sqlValue($limit, MySQL::SQLVALUE_BOOLEAN);

        $p = $kga['server_prefix'];

        $whereClauses = $this->timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities);

        if (is_customer()) {
            $whereClauses[] = "project.internal = 0";
        }

        if ($start) {
            $whereClauses[] = "(end > $start || end = 0)";
        }
        if ($end) {
            $whereClauses[] = "start < $end";
        }
        if ($filterCleared > - 1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        $limit = "";
        if ($limit) {
            if ( ! empty($limitRows)) {
                $startRows = (int) $startRows;
                $limit     = "LIMIT $startRows, $limitRows";
            }
            else {
                if (isset($kga['pref']['rowlimit'])) {
                    $limit = "LIMIT " . $kga['pref']['rowlimit'];
                }
                else {
                    $limit = "LIMIT 100";
                }
            }
        }


        if ($countOnly) {
            $query = "SELECT COUNT(*) AS total
                        FROM {$p}timesheet AS timesheet
                        LEFT Join {$p}project AS project USING (project_id)
                        LEFT Join {$p}customer AS customer USING (customer_id)
                        LEFT Join {$p}user AS user USING(user_id)
                        LEFT Join {$p}activity AS activity USING(activity_id) "
                     . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) .
                     ' ORDER BY start ' . ($reverse_order ? 'ASC ' : 'DESC ') . ';';
        }
        else {
            $query = "SELECT timesheet.*,
                            customer.name AS customer_name,
                            customer.customer_id as customer_id,
                            activity.name AS activity_name,
                            project.name AS project_name,
                            project.comment AS project_comment,
                            user.name AS username,
                            user.alias AS user_alias,
                            ref_code
                        FROM {$p}timesheet AS timesheet
                        LEFT Join {$p}project AS project USING (project_id)
                        LEFT Join {$p}customer AS customer USING (customer_id)
                        LEFT Join {$p}user AS user USING(user_id)
                        LEFT Join {$p}activity AS activity USING(activity_id) "
                     . (count($whereClauses) > 0 ? " WHERE " : " ") . implode(" AND ", $whereClauses) .
                     ' ORDER BY start ' . ($reverse_order ? 'ASC ' : 'DESC ') . $limit . ';';

        }


        if ($this->query($query) === false) {
            $this->logLastError('get_timesheet');
        }

        if ($countOnly) {
            $this->moveFirst();
            $row = $this->row();

            return $row->total;
        }

        $i   = 0;
        $arr = array();

        $this->moveFirst();
        while ( ! $this->endOfSeek()) {
            $row                      = $this->row();
            $arr[$i]['time_entry_id'] = $row->time_entry_id;

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
                $arr[$i]['duration']           = $arr[$i]['end'] - $arr[$i]['start'];
                $arr[$i]['formatted_duration'] = Format::formatDuration($arr[$i]['duration']);
                $arr[$i]['wage_decimal']       = $arr[$i]['duration'] / 3600 * $row->rate;
                $arr[$i]['wage']               = sprintf("%01.2f", $arr[$i]['wage_decimal']);
            }
            else {
                $arr[$i]['duration']           = null;
                $arr[$i]['formatted_duration'] = null;
                $arr[$i]['wage_decimal']       = null;
                $arr[$i]['wage']               = null;
            }
            $arr[$i]['budget']          = $row->budget;
            $arr[$i]['approved']        = $row->approved;
            $arr[$i]['rate']            = $row->rate;
            $arr[$i]['project_id']      = $row->project_id;
            $arr[$i]['activity_id']     = $row->activity_id;
            $arr[$i]['user_id']         = $row->user_id;
            $arr[$i]['project_id']      = $row->project_id;
            $arr[$i]['customer_name']   = $row->customer_name;
            $arr[$i]['customer_id']     = $row->customer_id;
            $arr[$i]['activity_name']   = $row->activity_name;
            $arr[$i]['project_name']    = $row->project_name;
            $arr[$i]['project_comment'] = $row->project_comment;
            $arr[$i]['location']        = $row->location;
            $arr[$i]['ref_code']        = $row->ref_code;
            $arr[$i]['status_id']       = $row->status_id;
            $arr[$i]['status']          =
                isset($kga['status'][$row->status_id])
                    ? $kga['status'][$row->status_id]
                    : $row->status_id;
            $arr[$i]['billable']        = $row->billable;
            $arr[$i]['description']     = $row->description;
            $arr[$i]['comment']         = $row->comment;
            $arr[$i]['cleared']         = $row->cleared;
            $arr[$i]['comment_type']    = $row->comment_type;
            $arr[$i]['user_alias']      = $row->user_alias;
            $arr[$i]['username']        = $row->username;
            $i ++;
        }

        return $arr;
    }

    public function getjointime($userID) {
        global $kga;

        $userID = $this->sqlValue($userID, MySQL::SQLVALUE_NUMBER);
        $p      = $kga['server_prefix'];

        $query = "SELECT start FROM {$p}timesheet WHERE user_id = $userID ORDER BY start ASC LIMIT 1;";

        if ($this->query($query) === false) {
            $this->logLastError('getjointime');

            return false;
        }

        $result_array = $this->rowArray(0, MYSQLI_NUM);

        if ($result_array[0] === 0) {
            return mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        }
        else {
            return $result_array[0];
        }
    }

    public function globalRole_find($filter) {
        global $kga;

        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = $this->sqlValue($value, MySQL::SQLVALUE_NUMBER);
            }
            else {
                $value = $this->sqlValue($value);
            }
        }
        $result = $this->selectRows(TBL_GLOBAL_ROLE, $filter);

        if ( ! $result) {
            $this->logLastError('globalRole_find');

            return false;
        }
        else {
            return $this->recordsArray(MYSQLI_ASSOC);
        }
    }

    public function globalRole_get_data($globalRoleID) {
        global $kga;

        $filter['global_role_id'] = $this->sqlValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $result                   = $this->selectRows(TBL_GLOBAL_ROLE, $filter);

        if ( ! $result) {
            $this->logLastError('globalRole_get_data');

            return false;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function global_role_create($data) {
        global $kga;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->sqlValue($value);
            }
            else {
                $values[$key] = $this->sqlValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $result = $this->insertRow(TBL_GLOBAL_ROLE, $values);

        if ( ! $result) {
            $this->logLastError('global_role_create');

            return false;
        }

        return $this->getLastInsertID();
    }

    public function global_role_delete($globalRoleID) {
        global $kga;

        $filter['global_role_id'] = $this->sqlValue($globalRoleID, MySQL::SQLVALUE_NUMBER);
        $query                    = MySQL::buildSqlDelete(TBL_GLOBAL_ROLE, $filter);

        if ($this->query($query) === false) {
            $this->logLastError('global_role_delete');

            return false;
        }

        return true;
    }

    public function global_role_edit($globalRoleID, $data) {
        global $kga;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->sqlValue($value);
            }
            else {
                $values[$key] = $this->sqlValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['global_role_id'] = $this->sqlValue($globalRoleID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_GLOBAL_ROLE, $values, $filter);

        if ($this->query($query) === false) {
            $this->logLastError('global_role_edit');

            return false;
        }

        return true;
    }

    public function global_roles() {
        global $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT a.*,
                    COUNT(b.global_role_id) AS count_users
                 FROM `{$p}global_role` a
                 LEFT JOIN `{$p}user` b USING(global_role_id)
                 GROUP BY a.global_role_id";

        if ($this->query($query) === false) {
            $this->logLastError('global_roles');

            return array();
        }

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        return $rows;
    }

    public function group_count_customers($groupID) {
        global $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT count(*) as total
                FROM {$p}customer as c
                LEFT JOIN {$p}group_customer as g_c USING (customer_id)
                WHERE c.trash = 0 AND g_c.group_id = {$groupID}";

        $this->query($query);

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if (is_array($rows)) {
            return (int) $rows[0]['total'];
        }

        return 0;
    }

    public function group_count_users($groupID) {
        global $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT count(*) as total
                FROM {$p}user as u
                LEFT JOIN {$p}group_user as g_u USING (user_id)
                WHERE u.trash = 0 AND g_u.group_id = {$groupID}";

        $this->query($query);

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if (is_array($rows)) {
            return (int) $rows[0]['total'];
        }

        return 0;
    }

    public function group_create($data) {
        global $kga;

        $data = clean_data($data);

        $values ['name'] = $this->sqlValue($data ['name']);
        $result          = $this->insertRow(TBL_GROUP, $values);

        if ( ! $result) {
            $this->logLastError('group_create');

            return false;
        }
        else {
            return $this->getLastInsertID();
        }
    }

    public function group_delete($groupID) {
        global $kga;

        $values['trash']    = 1;
        $filter['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
        $query              = $this->buildSqlUpdate(TBL_GROUP, $values, $filter);

        return $this->query($query);
    }

    public function group_edit($groupID, $data) {
        global $kga;

        $data = clean_data($data);

        $values ['name'] = $this->sqlValue($data ['name']);

        $filter ['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_GROUP, $values, $filter);

        return $this->query($query);
    }

    public function group_get_data($groupID) {
        global $kga;

        $filter['group_id'] = $this->sqlValue($groupID, MySQL::SQLVALUE_NUMBER);
        $result             = $this->selectRows(TBL_GROUP, $filter);

        if ( ! $result) {
            $this->logLastError('group_get_data');

            return false;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function group_ids_get($trash = 0) {
        global $kga;
        static $group_ids;

        if (isset($group_ids)): return $group_ids;
        else: $groups = array(); endif;

        $p = $kga['server_prefix'];

        $where = '';
        if ( ! (boolean) $trash) {
            $where = "WHERE {$p}group.trash != 1";
        }

        $query = "SELECT `group_id`
                    FROM {$p}group
                    ${where} ORDER BY name;";
        $this->query($query);

        // rows into array
        $groups = array();
        $i      = 0;

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $group_ids[] = $row['group_id'];
            }
        }

        return $group_ids;
    }

    public function groups_get($trash = 0) {
        global $kga;

        $p = $kga['server_prefix'];

        $where = '';
        if ( ! (boolean) $trash) {
            $where = "WHERE {$p}group.trash != 1";
        }

        $query = "SELECT * FROM {$p}group ${where} ORDER BY name;";
        $this->query($query);

        // rows into array
        $groups = array();
        $i      = 0;

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $groups[] = $row;

                // append user count
                $groups[$i]['count_users'] = $this->group_count_users($row['group_id']);
                $groups[$i]['count_customers'] = $this->group_count_customers($row['group_id']);

                $i ++;
            }
        }

        return $groups;
    }

    public function is_customer_name($name) {
        global $kga;

        $name = $this->sqlValue($name);
        $p    = $kga['server_prefix'];

        $query = "SELECT customer_id FROM {$p}customer WHERE name = $name AND trash = 0";

        $result = $this->query($query);

        return $result->num_rows > 0;
    }

    public function is_valid_activity_id($activityId) {
        $filter = array('activity_id' => $activityId, 'trash' => 0);

        return $this->rowExists(TBL_ACTIVITY, $filter);
    }

    public function is_valid_project_id($projectId) {
        $filter = array('project_id' => $projectId, 'trash' => 0);

        return $this->rowExists(TBL_PROJECT, $filter);
    }

    public function login_update_ban($userId, $resetTime = false) {
        global $kga;


        $filter ['user_id'] = $this->sqlValue($userId);

        $values ['ban'] = "ban+1";
        if ($resetTime) {
            $values ['ban_time'] = $this->sqlValue(time(), MySQL::SQLVALUE_NUMBER);
        }

        $query = $this->buildSqlUpdate(TBL_USER, $values, $filter);

        if ($this->query($query) === false) {
            $this->logLastError('login_update_ban');
        }
    }

    public function membershipRole_find($filter) {
        foreach ($filter as $key => &$value) {
            if (is_numeric($value)) {
                $value = $this->sqlValue($value, MySQL::SQLVALUE_NUMBER);
            }
            else {
                $value = $this->sqlValue($value);
            }
        }
        $result = $this->selectRows(TBL_MEMBERSHIP_ROLE, $filter);

        if ( ! $result) {
            $this->logLastError('membershipRole_find');

            return false;
        }
        else {
            return $this->recordsArray(MYSQLI_ASSOC);
        }
    }

    public function membershipRole_get_data($membershipRoleID) {
        global $kga;

        $filter['membership_role_id'] = $this->sqlValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $result                       = $this->selectRows(TBL_MEMBERSHIP_ROLE, $filter);

        if ( ! $result) {
            $this->logLastError('membershipRole_get_data');

            return false;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function membership_role_create($data) {
        global $kga;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->sqlValue($value);
            }
            else {
                $values[$key] = $this->sqlValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $result = $this->insertRow(TBL_MEMBERSHIP_ROLE, $values);

        if ( ! $result) {
            $this->logLastError('membership_role_create');

            return false;
        }

        return $this->getLastInsertID();
    }

    public function membership_role_delete($membershipRoleID) {
        global $kga;

        $filter['membership_role_id'] = $this->sqlValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);
        $query                        = $this->buildSqlDelete(TBL_MEMBERSHIP_ROLE, $filter);

        if ($this->query($query) === false) {
            $this->logLastError('membership_role_delete');

            return false;
        }

        return true;
    }

    public function membership_role_edit($membershipRoleID, $data) {
        global $kga;

        $values = array();

        foreach ($data as $key => $value) {
            if ($key == 'name') {
                $values[$key] = $this->sqlValue($value);
            }
            else {
                $values[$key] = $this->sqlValue($value, MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['membership_role_id'] = $this->sqlValue($membershipRoleID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_MEMBERSHIP_ROLE, $values, $filter);

        return $this->query($query);
    }

    public function membership_roles() {
        global $kga;

        $p = $kga['server_prefix'];

        $query = "select a.*,
                        count(distinct b.user_id) as count_users
                    from {$p}membership_role a
                    left join {$p}group_user b using (membership_role_id)
                    group by a.membership_role_id";

        if ($this->query($query) === false) {
            $this->logLastError('membership_roles');

            return array();
        }

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        return $rows;
    }

    public function pref_exists($any_id) {
        global $kga;


        $table  = TBL_PREFERENCE;
        $any_id = $this->sqlValue($any_id, MySQL::SQLVALUE_NUMBER);

        $query = "SELECT * FROM $table WHERE user_id = $any_id LIMIT 1";

        $result = $this->query($query);

        if ($result->num_rows === 0) {
            return false;
        }

        return true;
    }

    public function pref_get_by_prefix($prefix, $userId = null) {
        global $kga;

        if ($userId === null) {
            $userId = $kga['who']['id'];
        }

        $prefixLength = strlen($prefix);

        $table  = TBL_PREFERENCE;
        $userId = $this->sqlValue($userId, MySQL::SQLVALUE_NUMBER);
        $prefix = $this->sqlValue($prefix . '%');

        $query = "SELECT `option`,`value` FROM $table WHERE user_id = $userId AND `option` LIKE $prefix";
        $this->query($query);

        $prefs = array();

        while ( ! $this->endOfSeek()) {
            $row         = $this->rowArray();
            $key         = substr($row['option'], $prefixLength);
            $prefs[$key] = $row['value'];
        }

        return $prefs;
    }

    public function pref_load($id) {
        global $kga;

        if ( ! $id) {
            return;
        }

        $prefs = $this->pref_get_by_prefix('ui.', $id);

        $kga['pref'] = array_merge($kga['pref'], $prefs);

        // track number: configuration overrides preference
        if (isset($kga['conf']['ref_num_editable']) && (int) $kga['conf']['ref_num_editable'] === 0) {
            $kga['pref']['show_ref_code'] = 0;
        }

        date_default_timezone_set($kga['pref']['timezone']);
    }

    public function pref_replace(array $prefs, $prefix = '', $any_id = null) {
        global $kga;

        if (is_null($any_id)) {
            $any_id = $kga['who']['id'];
        }

        $values  = array();
        $columns = array('user_id', 'option', 'value');
        $any_id  = $this->sqlValue($any_id, MySQL::SQLVALUE_NUMBER);
        foreach ($prefs as $opt => $val) {
            $values [] = array(
                $any_id,
                $this->sqlValue($prefix . $opt),
                $this->sqlValue($val),
            );
        }

        if (count($values) > 0) {
            $query = $this->buildSqlReplace(TBL_PREFERENCE, $columns, $values);

            //DEBUG// error_log('<<== QUERY pref_replace ==>>' . PHP_EOL . $query);

            if ($this->query($query) === false) {
                $this->logLastError(__FUNCTION__);
                logger::logfile('There was an error updating preference table.');

                return false;
            }

            return true;
        }

        return false;
    }

    public function project_activity_edit($projectID, $activityID, $data) {
        global $kga;

        $data = clean_data($data);

        $filter['project_id']  = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $filter['activity_id'] = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);


        $query = $this->buildSqlUpdate(TBL_PROJECT_ACTIVITY, $data, $filter);
        if ($this->query($query) === false) {
            $this->logLastError('project_activity_edit');

            return false;
        }

        return true;
    }

    public function project_create($data) {
        global $kga;

        $data = clean_data($data);

        $values['name']        = $this->sqlValue($data['name']);
        $values['comment']     = $this->sqlValue($data['comment']);
        $values['budget']      = $this->sqlValue($data['budget'], MySQL::SQLVALUE_NUMBER);
        $values['effort']      = $this->sqlValue($data['effort'], MySQL::SQLVALUE_NUMBER);
        $values['approved']    = $this->sqlValue($data['approved'], MySQL::SQLVALUE_NUMBER);
        $values['customer_id'] = $this->sqlValue($data['customer_id'], MySQL::SQLVALUE_NUMBER);
        $values['visible']     = $this->sqlValue($data['visible'], MySQL::SQLVALUE_NUMBER);
        $values['internal']    = $this->sqlValue($data['internal'], MySQL::SQLVALUE_NUMBER);

        $values['filter'] = $this->sqlValue($data['filter'], MySQL::SQLVALUE_NUMBER);

        if ( ! $this->transactionBegin()) {
            $this->logLastError(__FUNCTION__ . " [1]");

            return false;
        }

        // INSERT SQL
        if ( ! $result = $this->insertRow(TBL_PROJECT, $values)) {
            $this->logLastError(__FUNCTION__ . " [2]");
        }

        // PROJECT ID
        if ($result) {
            $projectID = $this->getLastInsertID();
        }

        // DEFAULT RATE
        if ($result && isset($data['default_rate'])) {
            if (is_numeric($data['default_rate'])) {
                $result = $this->save_rate(null, $projectID, null, $data['default_rate']);
            }
            else {
                $result = $this->remove_rate(null, $projectID, null);
            }
            if ( ! $result) {
                $this->logLastError(__FUNCTION__ . " - default_rate [3]");
            }
        }

        // MY RATE
        if ($result && isset($data['my_rate'])) {
            if (is_numeric($data['my_rate'])) {
                $result = $this->save_rate($kga['who']['id'], $projectID, null, $data['my_rate']);
            }
            else {
                $result = $this->remove_rate($kga['who']['id'], $projectID, null);
            }
            if ( ! $result) {
                $this->logLastError(__FUNCTION__ . " - my_rate [4]");
            }
        }

        // FIXED RATE
        if ($result && isset($data['fixed_rate'])) {
            if (is_numeric($data['fixed_rate'])) {
                $result = $this->save_fixed_rate($projectID, null, $data['fixed_rate']);
            }
            else {
                $result = $this->remove_fixed_rate($projectID, null);
            }
            if ( ! $result) {
                $this->logLastError(__FUNCTION__ . " - fixed_rate [5]");
            }
        }

        // OK DONE
        if ($result) {
            if ($this->transactionEnd()) {
                return $projectID;
            }
            $this->logLastError(__FUNCTION__ . " transactionEnd [6]");
        }

        $this->transactionRollback();

        return false;
    }

    public function project_delete($projectID) {
        global $kga;

        $values['trash']      = 1;
        $filter['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_PROJECT, $values, $filter);

        return $this->query($query);
    }

    public function project_edit($projectID, $data) {
        global $kga;

        $data   = clean_data($data);
        $values = array();

        // STRINGS //
        $strings = array(
            'name',
            'comment',
        );
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key]);
            }
        }

        // NUMERICAL //
        $numbers = array(
            'customer_id',
            'visible',
            'filter',
            //'trash',
            'budget',
            'effort',
            'approved',
            'internal',
        );

        foreach ($numbers as $key) {
            if ( ! isset($data[$key])) {
                continue;
            }

            $values[$key] = 'NULL';
            if ($data[$key] !== null) {
                $values[$key] = $this->sqlValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter ['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);


        if ( ! $this->transactionBegin()) {
            $this->logLastError('project_edit');

            return false;
        }

        $query = $this->buildSqlUpdate(TBL_PROJECT, $values, $filter);
        //DEBUG// error_log(__FUNCTION__ . '<<== QUERY ==>>' . PHP_EOL . $query);

        if ($this->query($query) !== false) {

            if (isset($data['default_rate'])) {
                if (is_numeric($data['default_rate'])) {
                    $this->save_rate(null, $projectID, null, $data['default_rate']);
                }
                else {
                    $this->remove_rate(null, $projectID, null);
                }
            }

            if (isset($data['my_rate'])) {
                if (is_numeric($data['my_rate'])) {
                    $this->save_rate($kga['who']['id'], $projectID, null, $data['my_rate']);
                }
                else {
                    $this->remove_rate($kga['who']['id'], $projectID, null);
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

            if ( ! $this->transactionEnd()) {
                $this->logLastError('project_edit');

                return false;
            }

            return true;
        }
        else {
            $this->logLastError('project_edit');
            if ( ! $this->transactionRollback()) {
                $this->logLastError('project_edit');

                return false;
            }

            return false;
        }
    }

    public function project_get_activities($projectID) {
        global $kga;

        $projectId = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $p         = $kga['server_prefix'];

        $query = "SELECT activity.*, activity_id, budget, effort, approved
                FROM {$p}project_activity AS p_a
                INNER JOIN {$p}activity AS activity USING(activity_id)
                WHERE project_id = $projectId AND activity.trash=0;";

        if ($this->query($query) === false) {
            $this->logLastError('project_get_activities');

            return false;
        }

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        return $rows;
    }

    public function project_get_data($projectID) {
        global $kga;

        if ( ! is_numeric($projectID)) {
            return false;
        }

        $filter['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $result               = $this->selectRows(TBL_PROJECT, $filter);

        if ( ! $result) {
            $this->logLastError('project_get_data');

            return false;
        }

        $result_array                 = $this->rowArray(0, MYSQLI_ASSOC);
        $result_array['default_rate'] = $this->get_rate(null, $projectID, null);
        $result_array['my_rate']      = $this->get_rate($kga['who']['id'], $projectID, null);
        $result_array['fixed_rate']   = $this->get_fixed_rate($projectID, null);

        return $result_array;
    }

    public function project_get_groupIDs($projectID) {
        global $kga;

        $filter['project_id'] = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $columns[]            = "group_id";

        $result = $this->selectRows(TBL_GROUP_PROJECT, $filter, $columns);
        if ($result == false) {
            $this->logLastError('project_get_groupIDs');

            return false;
        }

        $groupIDs = array();
        $counter  = 0;

        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if ($this->num_rows) {
            foreach ($rows as $row) {
                $groupIDs[$counter] = $row['group_id'];
                $counter ++;
            }

            return $groupIDs;
        }
        else {
            return false;
        }
    }

    public function remove_fixed_rate($projectID, $activityID) {
        global $kga;

        // validate input
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        $p = $kga['server_prefix'];


        $query = "DELETE FROM {$p}fixed_rate WHERE " .
                 (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
                 (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");

        if ($this->query($query) === false) {
            $this->logLastError('remove_fixed_rate');

            return false;
        }
        else {
            return true;
        }
    }

    public function remove_rate($userID, $projectID, $activityID) {
        global $kga;

        // validate input
        $U = ($userID == null || ! is_numeric($userID)) ? "user_id is NULL" : "user_id = $userID";
        $P = ($projectID == null || ! is_numeric($projectID)) ? "project_id is NULL" : "project_id = $projectID";
        $A = ($activityID == null || ! is_numeric($activityID)) ? "activity_id is NULL" : "activity_id = $activityID";
        $p = $kga['server_prefix'];

        $query = "DELETE FROM {$p}rate WHERE ${U} AND ${P} AND ${A}";

        if ($this->query($query) === false) {
            $this->logLastError('remove_rate');

            return false;
        }

        return true;
    }

    public function save_fixed_rate($projectID, $activityID, $rate) {
        global $kga;

        // validate input
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        if ( ! is_numeric($rate)) {
            return false;
        }
        $p = $kga['server_prefix'];

        // build update or insert statement
        if ($this->get_fixed_rate($projectID, $activityID) === false) {
            $query = "INSERT INTO {$p}fixed_rate VALUES($projectID,$activityID,$rate);";
        }
        else {
            $query = "UPDATE {$p}fixed_rate SET rate = $rate WHERE " .
                     (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
                     (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");
        }

        if ($this->query($query) === false) {
            $this->logLastError('save_fixed_rate');

            return false;
        }
        else {
            return true;
        }
    }

    public function save_rate($userID, $projectID, $activityID, $rate) {
        global $kga;

        // validate input
        if ($userID == null || ! is_numeric($userID)) {
            $userID = "NULL";
        }
        if ($projectID == null || ! is_numeric($projectID)) {
            $projectID = "NULL";
        }
        if ($activityID == null || ! is_numeric($activityID)) {
            $activityID = "NULL";
        }
        if ( ! is_numeric($rate)) {
            return false;
        }
        $p = $kga['server_prefix'];


        // build update or insert statement
        if ($this->get_rate($userID, $projectID, $activityID) === false) {
            $query = "INSERT INTO {$p}rate VALUES($userID,$projectID,$activityID,$rate);";
        }
        else {
            $query = "UPDATE {$p}rate SET rate = $rate WHERE " .
                     (($userID == "NULL") ? "user_id is NULL" : "user_id = $userID") . " AND " .
                     (($projectID == "NULL") ? "project_id is NULL" : "project_id = $projectID") . " AND " .
                     (($activityID == "NULL") ? "activity_id is NULL" : "activity_id = $activityID");
        }

        if ($this->query($query) === false) {
            $this->logLastError('save_rate');

            return false;
        }
        else {
            return true;
        }
    }

    public function save_timeframe($timeframeBegin, $timeframeEnd) {
        global $kga;

        if ((int) $timeframeBegin === 0 && (int) $timeframeEnd === 0) {
            $mon            = date("n");
            $day            = date("j");
            $Y              = date("Y");
            $timeframeBegin = mktime(0, 0, 0, $mon, $day, $Y);
            $timeframeEnd   = mktime(23, 59, 59, $mon, $day, $Y);
        }

        if ($timeframeEnd == mktime(23, 59, 59, date('n'), date('j'), date('Y'))) {
            $timeframeEnd = 0;
        }

        $values['timeframe_begin'] = $this->sqlValue($timeframeBegin, MySQL::SQLVALUE_NUMBER);
        $values['timeframe_end']   = $this->sqlValue($timeframeEnd, MySQL::SQLVALUE_NUMBER);

        if (is_customer()) {
            $filter  ['customer_id'] = $this->sqlValue($kga['who']['id'], MySQL::SQLVALUE_NUMBER);
            $query                   = $this->buildSqlUpdate(TBL_CUSTOMER, $values, $filter);
        }
        else {
            $filter  ['user_id'] = $this->sqlValue($kga['who']['id'], MySQL::SQLVALUE_NUMBER);
            $query               = $this->buildSqlUpdate(TBL_USER, $values, $filter);
        }

        if ($this->query($query) === false) {
            $this->logLastError('save_timeframe');

            return false;
        }

        return true;
    }

    public function setGroupMemberships($userId, array $groups = null) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError('setGroupMemberships');

            return false;
        }

        $data ['user_id'] = $this->sqlValue($userId, MySQL::SQLVALUE_NUMBER);
        $result           = $this->deleteRows(TBL_GROUP_USER, $data);

        if ( ! $result) {
            $this->logLastError('setGroupMemberships');
            if ( ! $this->transactionRollback()) {
                $this->logLastError('setGroupMemberships');
            }

            return false;
        }

        foreach ($groups as $group => $role) {
            $data['group_id']           = $this->sqlValue($group, MySQL::SQLVALUE_NUMBER);
            $data['membership_role_id'] = $this->sqlValue($role, MySQL::SQLVALUE_NUMBER);
            $result                     = $this->insertRow(TBL_GROUP_USER, $data);
            if ($result === false) {
                $this->logLastError('setGroupMemberships');
                if ( ! $this->transactionRollback()) {
                    $this->logLastError('setGroupMemberships');
                }

                return false;
            }
        }

        if ( ! $this->transactionEnd()) {
            $this->logLastError('setGroupMemberships');

            return false;
        }

        return true;
    }

    public function startRecorder($projectID, $activityID, $user) {
        global $kga;

        $projectID  = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);
        $activityID = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);
        $user       = $this->sqlValue($user, MySQL::SQLVALUE_NUMBER);
        $startTime  = $this->sqlValue(time(), MySQL::SQLVALUE_NUMBER);

        $values ['project_id']  = $projectID;
        $values ['activity_id'] = $activityID;
        $values ['start']       = $startTime;
        $values ['user_id']     = $user;
        $values ['status_id']   = $kga['conf']['default_status_id'];

        $rate = $this->get_best_fitting_rate($user, $projectID, $activityID);
        if ($rate) {
            $values ['rate'] = $rate;
        }

        $result = $this->insertRow(TBL_TIMESHEET, $values);

        if ( ! $result) {
            $this->logLastError('startRecorder');

            return false;
        }

        return $this->getLastInsertID();
    }

    public function status_create($status) {
        global $kga;

        $values['status'] = $this->sqlValue(trim($status['status']));

        $result = $this->insertRow(TBL_STATUS, $values);
        if ( ! $result) {
            $this->logLastError('add_status');

            return false;
        }

        return true;
    }

    public function status_def_load() {
        global $kga;

        // load status table
        $this->selectRows(TBL_STATUS);

        $this->moveFirst();
        while ( ! $this->endOfSeek()) {
            $row                            = $this->row();
            $status_name                    = isset($kga['dict']['status_name'][$row->status])
                ? $kga['dict']['status_name'][$row->status]
                : $row->status;
            $kga['status'][$row->status_id] = $status_name;
        }

    }

    public function status_delete($statusID) {
        global $kga;

        $filter['status_id'] = $this->sqlValue($statusID, MySQL::SQLVALUE_NUMBER);
        $query               = $this->buildSqlDelete(TBL_STATUS, $filter);

        return $this->query($query);
    }

    public function status_edit($statusID, $data) {
        global $kga;

        $data = clean_data($data);

        $values ['status'] = $this->sqlValue($data ['status']);

        $filter ['status_id'] = $this->sqlValue($statusID, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_STATUS, $values, $filter);

        return $this->query($query);
    }

    public function status_get_all() {
        global $kga;

        $p = $kga['server_prefix'];

        $query = "SELECT * FROM {$p}status
                    ORDER BY status;";
        $this->query($query);

        $arr = array();
        $i   = 0;

        $this->moveFirst();
        $rows = $this->recordsArray(MYSQLI_ASSOC);

        if ($rows === false) {
            return array();
        }

        foreach ($rows as $row) {
            $arr[]                          = $row;
            $arr[$i]['timeSheetEntryCount'] = $this->status_timeSheetEntryCount($row['status_id']);
            $i ++;
        }

        return $arr;
    }

    public function status_get_data($statusID) {
        global $kga;

        $filter['status_id'] = $this->sqlValue($statusID, MySQL::SQLVALUE_NUMBER);
        $result              = $this->selectRows(TBL_STATUS, $filter);

        if ( ! $result) {
            $this->logLastError('status_get_data');

            return false;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function status_timeSheetEntryCount($statusID) {
        global $kga;

        $filter['status_id'] = $this->sqlValue($statusID, MySQL::SQLVALUE_NUMBER);
        $result              = $this->selectRows(TBL_TIMESHEET, $filter);

        if ( ! $result) {
            $this->logLastError('status_timeSheetEntryCount');

            return false;
        }

        return (int) $result->num_rows;
    }

    public function stopRecorder($id) {
        global $kga;

        ## stop running recording |

        $activity = $this->timesheet_get_data($id);

        $filter['time_entry_id'] = $activity['time_entry_id'];
        $filter['end']           = 0; // only update running activities

        $rounded = Rounding::roundTimespan($activity['start'], (integer) time(), $kga['conf']['round_precision'], $kga['conf']['allow_round_down']);

        $values['start']    = $rounded['start'];
        $values['end']      = $rounded['end'];
        $values['duration'] = $values['end'] - $values['start'];


        $query = $this->buildSqlUpdate(TBL_TIMESHEET, $values, $filter);

        return $this->query($query);
    }

    public function timeEntry_create($data) {
        global $kga;

        $data = clean_data($data);

        $values ['location']    = $this->sqlValue($data ['location']);
        $values ['comment']     = $this->sqlValue($data ['comment']);
        $values ['description'] = $this->sqlValue($data ['description']);

        if ($data ['ref_code'] == '') {
            $values ['ref_code'] = 'NULL';
        }
        else {
            $values ['ref_code'] = $this->sqlValue($data ['ref_code']);
        }

        $values ['user_id']      = $this->sqlValue($data ['user_id'], MySQL::SQLVALUE_NUMBER);
        $values ['project_id']   = $this->sqlValue($data ['project_id'], MySQL::SQLVALUE_NUMBER);
        $values ['activity_id']  = $this->sqlValue($data ['activity_id'], MySQL::SQLVALUE_NUMBER);
        $values ['comment_type'] = $this->sqlValue($data ['comment_type'], MySQL::SQLVALUE_NUMBER);
        $values ['start']        = $this->sqlValue($data ['start'], MySQL::SQLVALUE_NUMBER);
        $values ['end']          = $this->sqlValue($data ['end'], MySQL::SQLVALUE_NUMBER);
        $values ['duration']     = $this->sqlValue($data ['duration'], MySQL::SQLVALUE_NUMBER);
        $values ['rate']         = $this->sqlValue($data ['rate'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']      = $this->sqlValue($data ['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values ['budget']       = $this->sqlValue($data ['budget'], MySQL::SQLVALUE_NUMBER);
        $values ['approved']     = $this->sqlValue($data ['approved'], MySQL::SQLVALUE_NUMBER);
        $values ['status_id']    = $this->sqlValue($data ['status_id'], MySQL::SQLVALUE_NUMBER);
        $values ['billable']     = $this->sqlValue($data ['billable'], MySQL::SQLVALUE_NUMBER);
        $values ['fixed_rate']   = $this->sqlValue($data ['fixed_rate'], MySQL::SQLVALUE_NUMBER);

        $success = $this->insertRow(TBL_TIMESHEET, $values);
        if ($success) {
            return $this->getLastInsertID();
        }
        else {
            $this->logLastError('timeEntry_create');

            return false;
        }
    }

    public function timeEntry_delete($id) {
        global $kga;

        $filter["time_entry_id"] = $this->sqlValue($id, MySQL::SQLVALUE_NUMBER);
        $query                   = $this->buildSqlDelete(TBL_TIMESHEET, $filter);

        return $this->query($query);
    }

    public function timeEntry_edit($id, Array $data) {
        global $kga;

        $data = clean_data($data);

        $original_array = $this->timesheet_get_data($id);
        $new_array      = array();

        foreach ($original_array as $key => $value) {
            if (isset($data[$key])) {
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

        $values ['description'] = $this->sqlValue($new_array ['description']);
        $values ['comment']     = $this->sqlValue($new_array ['comment']);
        $values ['location']    = $this->sqlValue($new_array ['location']);

        if ($new_array ['ref_code'] == '') {
            $values ['ref_code'] = 'NULL';
        }
        else {
            $values ['ref_code'] = $this->sqlValue($new_array ['ref_code']);
        }

        $values ['user_id']      = $this->sqlValue($new_array ['user_id'], MySQL::SQLVALUE_NUMBER);
        $values ['project_id']   = $this->sqlValue($new_array ['project_id'], MySQL::SQLVALUE_NUMBER);
        $values ['activity_id']  = $this->sqlValue($new_array ['activity_id'], MySQL::SQLVALUE_NUMBER);
        $values ['comment_type'] = $this->sqlValue($new_array ['comment_type'], MySQL::SQLVALUE_NUMBER);
        $values ['start']        = $this->sqlValue($new_array ['start'], MySQL::SQLVALUE_NUMBER);
        $values ['end']          = $this->sqlValue($new_array ['end'], MySQL::SQLVALUE_NUMBER);
        $values ['duration']     = $this->sqlValue($new_array ['duration'], MySQL::SQLVALUE_NUMBER);
        $values ['rate']         = $this->sqlValue($new_array ['rate'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']      = $this->sqlValue($new_array ['cleared'] ? 1 : 0, MySQL::SQLVALUE_NUMBER);
        $values ['budget']       = $this->sqlValue($new_array ['budget'], MySQL::SQLVALUE_NUMBER);
        $values ['approved']     = $this->sqlValue($new_array ['approved'], MySQL::SQLVALUE_NUMBER);
        $values ['status_id']    = $this->sqlValue($new_array ['status_id'], MySQL::SQLVALUE_NUMBER);
        $values ['billable']     = $this->sqlValue($new_array ['billable'], MySQL::SQLVALUE_NUMBER);
        $values ['fixed_rate']   = $this->sqlValue($data ['fixed_rate'], MySQL::SQLVALUE_NUMBER);

        $filter ['time_entry_id'] = $this->sqlValue($id, MySQL::SQLVALUE_NUMBER);

        $query = $this->buildSqlUpdate(TBL_TIMESHEET, $values, $filter);


        if ($this->query($query) === false) {
            $this->logLastError('timeEntry_edit');
            if ( ! $this->transactionRollback()) {
                $this->logLastError('timeEntry_edit');

                return false;
            }
        }

        return true;
    }

    public function timeEntry_edit_activity($timeEntryID, $activityID) {
        global $kga;

        $timeEntryID = $this->sqlValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $activityID  = $this->sqlValue($activityID, MySQL::SQLVALUE_NUMBER);


        $filter['time_entry_id'] = $timeEntryID;

        $values['activity_id'] = $activityID;

        $query = $this->buildSqlUpdate(TBL_TIMESHEET, $values, $filter);

        return $this->query($query);
    }

    public function timeEntry_edit_project($timeEntryID, $projectID) {
        global $kga;

        $timeEntryID = $this->sqlValue($timeEntryID, MySQL::SQLVALUE_NUMBER);
        $projectID   = $this->sqlValue($projectID, MySQL::SQLVALUE_NUMBER);


        $filter['time_entry_id'] = $timeEntryID;

        $values['project_id'] = $projectID;

        $query = $this->buildSqlUpdate(TBL_TIMESHEET, $values, $filter);

        return $this->query($query);
    }

    public function timeSheet_whereClausesFromFilters($users, $customers, $projects, $activities) {
        global $kga;

        if ( ! is_array($users)) {
            $users = array();
        }
        if ( ! is_array($customers)) {
            $customers = array();
        }
        if ( ! is_array($projects)) {
            $projects = array();
        }
        if ( ! is_array($activities)) {
            $activities = array();
        }


        foreach ($users as $i => $user) {
            $users[$i] = $this->sqlValue($user, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($customers as $i => $customer) {
            $customers[$i] = $this->sqlValue($customer, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($projects as $i => $project) {
            $projects[$i] = $this->sqlValue($project, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($activities as $i => $activity) {
            $activities[$i] = $this->sqlValue($activity, MySQL::SQLVALUE_NUMBER);
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

    public function timesheet_get_data($timeEntryID) {
        global $kga;

        $timeEntryID = $this->sqlValue($timeEntryID, MySQL::SQLVALUE_NUMBER);

        $table         = TBL_TIMESHEET;
        $projectTable  = TBL_PROJECT;
        $activityTable = TBL_ACTIVITY;
        $customerTable = TBL_CUSTOMER;

        $select = "SELECT $table.*,
                        $projectTable.name AS project_name,
                        $customerTable.name AS customer_name,
                        $activityTable.name AS activity_name,
                        $customerTable.customer_id AS customer_id
      				FROM $table
                	INNER JOIN $projectTable USING(project_id)
                	INNER JOIN $customerTable USING(customer_id)
                	INNER JOIN $activityTable USING(activity_id)";


        if ($timeEntryID) {
            $result = $this->query("$select WHERE time_entry_id = " . $timeEntryID);
        }
        else {
            $result = $this->query("$select WHERE user_id = " . $kga['who']['id'] . " ORDER BY time_entry_id DESC LIMIT 1");
        }

        if ($result === false) {
            $this->logLastError('timesheet_get_data');

            return false;
        }
        else {
            return $this->rowArray(0, MYSQLI_ASSOC);
        }
    }

    public function userIDToName($id) {
        global $kga;

        $filter ['user_id'] = $this->sqlValue($id, MySQL::SQLVALUE_NUMBER);
        $columns[]          = "name";

        $result = $this->selectRows(TBL_USER, $filter, $columns);
        if ($result == false) {
            $this->logLastError('userIDToName');

            return false;
        }

        $row = $this->rowArray(0, MYSQLI_ASSOC);

        return $row['name'];
    }

    public function user_create($data) {
        global $kga;

        // find random and unused user id
        do {$data['user_id'] = random_number(9);}
        while ($this->pref_exists($data['user_id']));

        $this->pref_replace(array('is_customer' => '0'), '', $data['user_id']);

        if (DEMO_MODE) {
            $data['password'] = password_encrypt('demo');
        }

        $data = clean_data($data);

        $values['name']           = $this->sqlValue($data['name']);
        $values['user_id']        = $this->sqlValue($data['user_id'], MySQL::SQLVALUE_NUMBER);
        $values['global_role_id'] = $this->sqlValue($data['global_role_id'], MySQL::SQLVALUE_NUMBER);
        $values['active']         = $this->sqlValue($data['active'], MySQL::SQLVALUE_NUMBER);

        // 'mail' and 'password' are just set when actually provided because of compatibility reasons
        if (array_key_exists('mail', $data)) {
            $values['mail'] = $this->sqlValue($data['mail']);
        }

        if (array_key_exists('password', $data)) {
            $values['password'] = $this->sqlValue($data['password']);
        }

        $result = $this->insertRow(TBL_USER, $values);

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

        $this->pref_defaults_to_user($data['user_id']);

        return (string) $data['user_id'];
    }

    public function user_data_load($user_id) {
        global $kga;

        if ( ! $user_id) {
            return;
        }

        $filter['user_id'] = $this->sqlValue($user_id, MySQL::SQLVALUE_NUMBER);

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

        $this->selectRows(TBL_USER, $filter, $columns);
        $rows = $this->rowArray(0, MYSQLI_ASSOC);
        foreach ($rows as $key => $value) {
            $kga['user'][$key] = $value;
        }

        $kga['user']['groups'] = $this->user_get_group_ids($user_id, true);
    }

    public function user_delete($userID, $moveToTrash = false) {
        global $kga;

        if ( ! $this->transactionBegin()) {
            $this->logLastError(__FUNCTION__ . " [1]");

            return false;
        }

        $userID = $this->sqlValue($userID, MySQL::SQLVALUE_NUMBER);

        if ($moveToTrash) {
            $values['trash']   = 1;
            $filter['user_id'] = $userID;

            $query = $this->buildSqlUpdate(TBL_USER, $values, $filter);

            $result = true;
            if ($this->query($query) === false) {
                $this->logLastError(__FUNCTION__ . " [2]");
                $result = false;
            }
        }

        else {
            $p      = $kga['server_prefix'];
            $result = true;

            if ($result) {
                $query = "DELETE FROM {$p}group_user WHERE user_id = " . $userID;

                if (($result = $this->query($query)) === false) {
                    $this->logLastError(__FUNCTION__ . " [3]");
                }
            }

            if ($result !== false) {
                $query = "DELETE FROM {$p}preference WHERE user_id = " . $userID;

                if (($result = $this->query($query)) === false) {
                    $this->logLastError(__FUNCTION__ . " [4]");
                }
            }

            if ($result !== false) {
                $query = "DELETE FROM {$p}rate WHERE user_id = " . $userID;

                if (($result = $this->query($query)) === false) {
                    $this->logLastError(__FUNCTION__ . " [5]");
                }
            }

            if ($result !== false) {
                $query = "DELETE FROM {$p}user WHERE user_id = " . $userID;

                if (($result = $this->query($query)) === false) {
                    $this->logLastError(__FUNCTION__ . " [6]");
                }
            }
        }


        if ($result !== false) {
            if ($this->transactionEnd()) {
                return true;
            }
            $this->logLastError(__FUNCTION__ . " transactionEnd [6]");
        }

        $this->transactionRollback();

        return false;
    }

    public function user_edit($userID, $data) {
        global $kga;

        if (DEMO_MODE) {
            $data['password'] = password_encrypt('demo');
        }

        $data    = clean_data($data);
        $values  = array();
        $strings = array(
            'name',
            'alias',
            'mail',
            'password',
            'password_reset_hash',
            'apikey',
        );

        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key]);
            }
        }

        $numbers = array(
            'status',
            'trash',
            'active',
            'last_project',
            'last_activity',
            'last_record',
            'global_role_id',
        );
        foreach ($numbers as $key) {
            if (isset($data[$key])) {
                $values[$key] = $this->sqlValue($data[$key], MySQL::SQLVALUE_NUMBER);
            }
        }

        $filter['user_id'] = $this->sqlValue($userID, MySQL::SQLVALUE_NUMBER);

        if ( ! $this->transactionBegin()) {
            $this->logLastError('user_edit transaction begin');

            return false;
        }

        $query = $this->buildSqlUpdate(TBL_USER, $values, $filter);

        //DEBUG// error_log('<<=== SAVE USER QUERY ==>>'. PHP_EOL . $query);

        if ($ok = (bool) $this->query($query) !== false) {
            if (isset($data['rate'])) {

                if (is_numeric($data['rate'])) {
                    if ( ! (bool) $this->save_rate($userID, null, null, $data['rate'])) {
                        $this->logLastError('user_edit save_rate');
                        $ok = false;
                    }
                }

                elseif ( ! (bool) $this->remove_rate($userID, null, null)) {
                    $this->logLastError('user_edit remove_rate');
                    $ok = false;
                }
            }

            if ($ok && ! $this->transactionEnd()) {
                $this->logLastError('user_edit transaction end');
                $ok = false;
            }
        }

        if ( ! $ok && ! $this->transactionRollback()) {
            $this->logLastError('user_edit rollback');
            $ok = false;
        }

        if ( ! $ok) {
            $this->logLastError('user_edit failed');

            return false;
        }

        return true;
    }

    public function user_get_data($userID) {
        global $kga;

        $filter['user_id'] = $this->sqlValue($userID, MySQL::SQLVALUE_NUMBER);
        $result            = $this->selectRows(TBL_USER, $filter);

        if ( ! $result) {
            $this->logLastError('user_get_data');

            return false;
        }

        return $this->rowArray(0, MYSQLI_ASSOC);
    }

    public function user_get_group_ids($userId, $root_bypass = false) {
        global $kga;

        $filter = null;

        if ( ! $kga['is_user_root'] || ! $root_bypass) {
            $filter['user_id'] = $this->sqlValue($userId);
        }

        $columns[] = "group_id";
        $result    = $this->selectRows(TBL_GROUP_USER, $filter, $columns);

        if ( ! $result) {
            $this->logLastError('user_get_group_ids');

            return null;
        }

        $grp_id = array();
        if ($result->num_rows) {
            $this->moveFirst();
            while ( ! $this->endOfSeek()) {
                $row                    = $this->row();
                $grp_id[$row->group_id] = $row->group_id;
            }
        }

        //CN..needed with user = root, avoid duplications.
        $groups = array();
        foreach ($grp_id as $key => $val) {
            $groups[] = $key;
        }

        return $groups;
    }

    public function user_loginSetKey($userId, $keymai) {
        global $kga;

        $p = $kga['server_prefix'];
        $u = mysqli_real_escape_string($this->link, $userId);

        $query = "UPDATE {$p}user
                SET secure='$keymai',
                    ban=0,
                    ban_time=0
                WHERE user_id='" . $u . "';";

        //DEBUG//
        error_log('<<== SETTING USER SECURE KEY ==>>' . PHP_EOL . $query);
        $result = $this->query($query);

        if (mysqli_affected_rows($this->link) < 1) {
            error_log('<<== FAILED SETTING USER SECURE KEY ==>>' . PHP_EOL . $query);
        }

    }

    public function user_name2id($name) {
        return $this->name2id(TBL_USER, 'user_id', 'name', $name);
    }

    public function users_get($trash = 0, array $groups = null) {
        global $kga;

        $p = $kga['server_prefix'];


        $trash = $this->sqlValue($trash, MySQL::SQLVALUE_NUMBER);

        if (count($groups) === 0) {
            $query = "select * from {$p}user
                        where trash = $trash
                        order by name ;";
        }

        else {
            $G     = implode($groups, ',');
            $query = "select distinct `u`.*
                        from `{$p}user` as `u`
                        inner join {$p}group_user as g_u using(user_id)
                        where g_u.group_id in ({$G})
                            and trash = $trash
                        order by name ;";
        }
        $this->query($query);

        $this->rowArray(0, MYSQLI_ASSOC);

        $i   = 0;
        $arr = array();

        $this->moveFirst();
        while ( ! $this->endOfSeek()) {
            $row                       = $this->row();
            $arr[$i]['user_id']        = $row->user_id;
            $arr[$i]['name']           = $row->name;
            $arr[$i]['global_role_id'] = $row->global_role_id;
            $arr[$i]['mail']           = $row->mail;
            $arr[$i]['active']         = $row->active;
            $arr[$i]['trash']          = $row->trash;

            if ($row->password != '' && $row->password != '0') {
                $arr[$i]['passwordSet'] = "yes";
            }
            else {
                $arr[$i]['passwordSet'] = "no";
            }
            $i ++;
        }

        return $arr;
    }

    protected function logLastError($scope) {
        global $kga;

        Logger::logfile($scope . ': ' . $this->error());
    }

    protected function rowExists($table, Array $filter) {
        global $kga;

        $select = $this->selectRows($table, $filter);

        if ( ! $select) {
            $this->logLastError('rowExists');

            return false;
        }
        else {
            $rowExits = (bool) $this->rowArray(0, MYSQLI_ASSOC);

            return $rowExits;
        }
    }

    private function name2id($table, $endColumn, $filterColumn, $value) {
        global $kga;

        $filter [$filterColumn] = $this->sqlValue($value);
        $filter ['trash']       = 0;
        $columns[]              = $endColumn;

        $result = $this->selectRows($table, $filter, $columns);
        if ($result == false) {
            $this->logLastError('name2id');

            return false;
        }

        $row = $this->rowArray(0, MYSQLI_ASSOC);

        if ($row === false) {
            return false;
        }

        return $row[$endColumn];
    }

    private function pref_defaults_load() {
        global $kga;

        //create preferences from conf->user default
        $K = &$kga;
        foreach ($K['conf'] as $option => $value) {
            $opt3 = substr($option, 0, 3);
            if ('ud.' === $opt3) {
                $opt             = substr($option, 3);
                $K['pref'][$opt] = $value;
            }
        }
    }

    private function pref_defaults_to_user($id) {
        global $kga;

        //create preferences from conf->user default
        $pref = array();
        foreach ($kga['conf'] as $option => $value) {
            $opt3 = substr($option, 0, 3);
            if ('ud.' === $opt3) {
                $opt        = substr($option, 3);
                $pref[$opt] = $value;
            }
        }

        if ( ! empty($pref)) {
            $this->pref_replace($pref, 'ui.', $id);
        }
    }
}
