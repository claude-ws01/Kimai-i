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

/**
 * Provides the database layer for remote API calls.
 * This was implemented due to the bad maintainability of MySQL and PDO Classes.
 * This class serves as a bridge and currently ONLY for API calls.
 *
 * @author Kevin Papst
 * @author Alexander Bauer
 */
// used in core/includes/classes/remote.class.php         class Kimai_Remote_Api
class ApiDatabase
{

    /**
     * @param                      $kga
     * @param Kimai_Database_Mysql $database
     */
    public function __construct()
    {
    }

    public function __call($fnName, $arguments)
    {
        global $database;

        return call_user_func_array(array($database, $fnName), $arguments);
    }

    /*
     * returns single expense entry as array
     *
     * @param integer $id  ID of entry in table exp
     *
     * @global array  $kga kimai-global-array
     * @return array
     * @author sl
     */
    public function get_expense($id)
    {
        global $database;

        $id = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);

        $tbl_expense  = TBL_EXPENSE;
        $tbl_project  = TBL_PROJECT;
        $tbl_customer = TBL_CUSTOMER;

        $query = "SELECT * FROM $tbl_expense
	              LEFT JOIN $tbl_project USING(project_id)
	              LEFT JOIN $tbl_customer USING(customer_id)
	              WHERE $tbl_expense.`expense_id` = $id LIMIT 1;";

        $result = $database->query($query);

        if ($result->num_rows === 0) {
            return array();
        }
        else {
            return $database->rowArray(0, MYSQLI_ASSOC);
        }
    }

    /*
     * Returns the data of a certain expense record
     *
     * @param array $expense_id expense_id of the record
     *
     * @return array the record's data as array, false on failure
     * @author ob
     */
    public function expense_get($expId)
    {
        global $database, $kga;

        $tbl_expense = TBL_EXPENSE;
        $expId       = $database->sqlValue($expId, MySQL::SQLVALUE_NUMBER);

        if ($expId) {
            $result = $database->query("SELECT * FROM $tbl_expense WHERE `expense_id` = " . $expId);
        }
        else {
            $result = $database->query("SELECT * FROM $tbl_expense WHERE `user_id` = " . $kga['user']['user_id'] . " ORDER BY `expense_id` DESC LIMIT 1");
        }

        if ($result->num_rows === 0) {
            return false;
        }

        return $database->rowArray(0, MYSQLI_ASSOC);
    }

    /*
     * returns expense for specific user as multidimensional array
     *
     * @TODO   : needs comments
     *
     * @param integer $user ID of user in table user
     *
     * @return array
     * @author th
     * @author Alexander Bauer
     */
    public function get_expenses($start, $end, $users = null, $customers = null, $projects = null,
                                 $reverse_order = false, $filter_refundable = -1, $filterCleared = null,
                                 $startRows = 0, $limitRows = 0, $countOnly = false)
    {
        global $database, $kga;

        if (!is_numeric($filterCleared)) {
            $filterCleared = $kga['pref']['hide_cleared_entries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start = $database->sqlValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->sqlValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses = $this->expenses_widthhereClausesFromFilters($users, $customers, $projects);

        if (array_key_exists('customer', $kga)) {
            $whereClauses[] = "${p}project.internal = 0";
        }

        if (!empty($start)) {
            $whereClauses[] = "timestamp >= $start";
        }
        if (!empty($end)) {
            $whereClauses[] = "timestamp <= $end";
        }
        if ($filterCleared > -1) {
            $whereClauses[] = "cleared = $filterCleared";
        }

        switch ($filter_refundable) {
            case 0:
                $whereClauses[] = "refundable > 0";
                break;
            case 1:
                $whereClauses[] = "refundable <= 0";
                break;
            case -1:
            default:
                // return all expense - refundable and non refundable
        }

        if (!empty($limitRows)) {
            $startRows = (int)$startRows;
            $limit     = "LIMIT $startRows, $limitRows";
        }
        else {
            $limit = "";
        }

        $select = "SELECT `expense_id`, timestamp, multiplier, value, project_id, description, `user_id`,
  					customer_name, customer_id, project_name, comment, refundable,
  					comment_type, username, cleared";

        $where          = empty($whereClauses) ? '' : "WHERE " . implode(" AND ", $whereClauses);
        $orderDirection = $reverse_order ? 'ASC' : 'DESC';

        if ($countOnly) {
            $select = "SELECT COUNT(*) AS total";
            $limit  = "";
        }

        $query = "$select
  			FROM ${p}expense
	  		Join ${p}project USING(project_id)
	  		Join ${p}customer USING(customer_id)
	  		Join ${p}user USING(`user_id`)
	  		$where
	  		ORDER BY timestamp $orderDirection $limit";

        $result = $database->query($query);

        // return only the number of rows, ignoring LIMIT
        if ($countOnly) {
            return $result->num_rows;
        }


        $i   = 0;
        $arr = array();
        $database->moveFirst();
        // toArray();
        while (!$database->endOfSeek()) {
            $row     = $database->row();
            $arr[$i] = (array)$row;
            $i++;
        }

        return $arr;
    }

    /*
     *  Creates an array of clauses which can be joined together in the WHERE part
     *  of a sql query. The clauses describe whether a line should be included
     *  depending on the filters set.
     *
     *  This method also makes the values SQL-secure.
     *
     * @param Array list of IDs of user to include
     * @param Array list of IDs of customer to include
     * @param Array list of IDs of project to include
     * @param Array list of IDs of activities to include
     *
     * @return Array list of where clauses to include in the query
     */
    public function expenses_widthhereClausesFromFilters($users, $customers, $projects)
    {
        global $database;


        if (!is_array($users)) {
            $users = array();
        }
        if (!is_array($customers)) {
            $customers = array();
        }
        if (!is_array($projects)) {
            $projects = array();
        }

        foreach ($users as $i => $user) {
            $users[$i] = $database->sqlValue($user, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($customers as $i => $customer) {
            $customers[$i] = $database->sqlValue($customer, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($projects as $i => $project) {
            $projects[$i] = $database->sqlValue($project, MySQL::SQLVALUE_NUMBER);
        }


        $whereClauses = [];

        if (count($users) > 0) {
            $whereClauses[] = "`user_id` in (" . implode(',', $users) . ")";
        }

        if (count($customers) > 0) {
            $whereClauses[] = "customer_id in (" . implode(',', $customers) . ")";
        }

        if (count($projects) > 0) {
            $whereClauses[] = "project_id in (" . implode(',', $projects) . ")";
        }

        return $whereClauses;

    }

    /*
     * create exp entry
     *
     * @param integer $userId
     * @param Array   $data
     *
     * @author sl
     * @author Alexander Bauer
     */
    public function expense_create(Array $data)
    {
        global $database;

        $data   = $database->clean_data($data);
        $values = array();

        if (isset($data ['timestamp'])) {
            $values ['timestamp'] = $database->sqlValue($data['timestamp'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['user_id'])) {
            $values ['user_id'] = $database->sqlValue($data['user_id'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['project_id'])) {
            $values ['project_id'] = $database->sqlValue($data['project_id'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['description'])) {
            $values ['description'] = $database->sqlValue($data['description']);
        }
        if (isset($data ['comment'])) {
            $values ['comment'] = $database->sqlValue($data['comment']);
        }
        if (isset($data ['comment_type'])) {
            $values ['comment_type'] = $database->sqlValue($data['comment_type'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['refundable'])) {
            $values ['refundable'] = $database->sqlValue($data['refundable'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['cleared'])) {
            $values ['cleared'] = $database->sqlValue($data['cleared'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['multiplier'])) {
            $values ['multiplier'] = $database->sqlValue($data['multiplier'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['value'])) {
            $values ['value'] = $database->sqlValue($data['value'], MySQL::SQLVALUE_NUMBER);
        }

        return $database->insertRow(TBL_EXPENSE, $values);
    }

    /*
     * edit exp entry
     *
     * @param integer $id
     * @param array   $data
     *
     * @author th
     * @author Alexander Bauer
     */
    public function expense_edit($id, Array $data)
    {
        global $database;

        $data = $database->clean_data($data);

        $original_array = $this->expense_get($id);
        $new_array      = array();

        foreach ($original_array as $key => $value) {
            if (isset($data[$key]) === true) {
                $new_array[$key] = $data[$key];
            }
            else {
                $new_array[$key] = $original_array[$key];
            }
        }

        $values ['project_id']   = $database->sqlValue($new_array ['project_id'], MySQL::SQLVALUE_NUMBER);
        $values ['description']  = $database->sqlValue($new_array ['description']);
        $values ['comment']      = $database->sqlValue($new_array ['comment']);
        $values ['comment_type'] = $database->sqlValue($new_array ['comment_type'], MySQL::SQLVALUE_NUMBER);
        $values ['timestamp']    = $database->sqlValue($new_array ['timestamp'], MySQL::SQLVALUE_NUMBER);
        $values ['multiplier']   = $database->sqlValue($new_array ['multiplier'], MySQL::SQLVALUE_NUMBER);
        $values ['value']        = $database->sqlValue($new_array ['value'], MySQL::SQLVALUE_NUMBER);
        $values ['refundable']   = $database->sqlValue($new_array ['refundable'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']      = $database->sqlValue($new_array ['cleared'], MySQL::SQLVALUE_NUMBER);

        $filter ['`expense_id`'] = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);
        $query                   = MySQL::buildSqlUpdate(TBL_EXPENSE, $values, $filter);

        return $database->query($query);
    }

    /*
     * delete exp entry
     *
     * @param integer $userID
     * @param integer $id  -> ID of record
     *
     * @global array  $kga kimai-global-array
     * @author th
     */
    public function expense_delete($id)
    {
        global $database;

        $filter["`expense_id`"] = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);

        $query = MySQL::buildSqlDelete(TBL_EXPENSE, $filter);

        return $database->query($query);
    }


}
