<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) 2006-2012 Kimai-Development-Team
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
     * @param $kga
     * @param Kimai_Database_Mysql $database
     */
    public function __construct($kga, $database)
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

        $id = $database->SQLValue($id, MySQL::SQLVALUE_NUMBER);

        $tbl_expense  = TBL_EXPENSE;
        $tbl_project  = TBL_PROJECT;
        $tbl_customer = TBL_CUSTOMER;

        $query = "SELECT * FROM $tbl_expense
	              LEFT JOIN $tbl_project USING(project_id)
	              LEFT JOIN $tbl_customer USING(customer_id)
	              WHERE $tbl_expense.`expense_id` = $id LIMIT 1;";

        $database->Query($query);

        return $database->RowArray(0, MYSQL_ASSOC);
    }

    /*
     * Returns the data of a certain expense record
     *
     * @param array $expense_id expenseID of the record
     *
     * @return array the record's data as array, false on failure
     * @author ob
     */
    public function expense_get($expId)
    {
        global $database, $kga;

        $tbl_expense = TBL_EXPENSE;
        $expId = $database->SQLValue($expId, MySQL::SQLVALUE_NUMBER);

        if ($expId) {
            $result = $database->Query("SELECT * FROM $tbl_expense WHERE `expense_id` = " . $expId);
        }
        else {
            $result = $database->Query("SELECT * FROM $tbl_expense WHERE `user_id` = " . $kga['user']['userID'] . " ORDER BY `expense_id` DESC LIMIT 1");
        }

        if (!$result) {
            return false;
        }

            return $database->RowArray(0, MYSQL_ASSOC);
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
            $filterCleared = $kga['conf']['hideClearedEntries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
        }

        $start = $database->SQLValue($start, MySQL::SQLVALUE_NUMBER);
        $end   = $database->SQLValue($end, MySQL::SQLVALUE_NUMBER);

        $p = $kga['server_prefix'];

        $whereClauses = $this->expenses_widthhereClausesFromFilters($users, $customers, $projects);

        if (isset($kga['customer'])) {
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
            $startRows = (int) $startRows;
            $limit     = "LIMIT $startRows, $limitRows";
        }
        else {
            $limit = "";
        }

        $select = "SELECT `expense_id`, timestamp, multiplier, value, project_id, designation, `user_id`,
  					customer_name, customer_id, project_name, comment, refundable,
  					comment_type, user_name, cleared";

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

        $database->Query($query);

        // return only the number of rows, ignoring LIMIT
        if ($countOnly) {
            $database->MoveFirst();
            $row = $database->Row();

            return $row->total;
        }


        $i   = 0;
        $arr = array();
        $database->MoveFirst();
        // toArray();
        while (!$database->EndOfSeek()) {
            $row     = $database->Row();
            $arr[$i] = (array) $row;
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
        
        
        if (!is_array($users)) $users = array();
        if (!is_array($customers)) $customers = array();
        if (!is_array($projects)) $projects = array();

        foreach ($users as $i => $user) {
            $users[$i] = $database->SQLValue($user, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($customers as $i => $customer) {
            $customers[$i] = $database->SQLValue($customer, MySQL::SQLVALUE_NUMBER);
        }

        foreach ($projects as $i => $project) {
            $projects[$i] = $database->SQLValue($project, MySQL::SQLVALUE_NUMBER);
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
    function expense_create(Array $data)
    {
        global $database;
        
        $data = $database->clean_data($data);
        $values = array();

        if (isset($data ['timestamp'])) {
            $values ['timestamp'] = $database->SQLValue($data['timestamp'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['user_id'])) {
            $values ['user_id'] = $database->SQLValue($data['user_id'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['project_id'])) {
            $values ['project_id'] = $database->SQLValue($data['project_id'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['designation'])) {
            $values ['designation'] = $database->SQLValue($data['designation']);
        }
        if (isset($data ['comment'])) {
            $values ['comment'] = $database->SQLValue($data['comment']);
        }
        if (isset($data ['comment_type'])) {
            $values ['comment_type'] = $database->SQLValue($data['comment_type'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['refundable'])) {
            $values ['refundable'] = $database->SQLValue($data['refundable'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['cleared'])) {
            $values ['cleared'] = $database->SQLValue($data['cleared'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['multiplier'])) {
            $values ['multiplier'] = $database->SQLValue($data['multiplier'], MySQL::SQLVALUE_NUMBER);
        }
        if (isset($data ['value'])) {
            $values ['value'] = $database->SQLValue($data['value'], MySQL::SQLVALUE_NUMBER);
        }

        return $database->InsertRow(TBL_EXPENSE, $values);
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
    function expense_edit($id, Array $data)
    {
        global $database;

        $data = $database->clean_data($data);

        $original_array = $this->expense_get($id);
        $new_array      = array();

        foreach ($original_array as $key => $value) {
            if (isset($data[$key]) == true) {
                $new_array[$key] = $data[$key];
            }
            else {
                $new_array[$key] = $original_array[$key];
            }
        }

        $values ['project_id']   = $database->SQLValue($new_array ['project_id'], MySQL::SQLVALUE_NUMBER);
        $values ['designation'] = $database->SQLValue($new_array ['designation']);
        $values ['comment']     = $database->SQLValue($new_array ['comment']);
        $values ['comment_type'] = $database->SQLValue($new_array ['comment_type'], MySQL::SQLVALUE_NUMBER);
        $values ['timestamp']   = $database->SQLValue($new_array ['timestamp'], MySQL::SQLVALUE_NUMBER);
        $values ['multiplier']  = $database->SQLValue($new_array ['multiplier'], MySQL::SQLVALUE_NUMBER);
        $values ['value']       = $database->SQLValue($new_array ['value'], MySQL::SQLVALUE_NUMBER);
        $values ['refundable']  = $database->SQLValue($new_array ['refundable'], MySQL::SQLVALUE_NUMBER);
        $values ['cleared']     = $database->SQLValue($new_array ['cleared'], MySQL::SQLVALUE_NUMBER);

        $filter ['`expense_id`'] = $database->SQLValue($id, MySQL::SQLVALUE_NUMBER);
        $query                = MySQL::BuildSQLUpdate(TBL_EXPENSE, $values, $filter);

        return $database->Query($query);
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
    function expense_delete($id)
    {
        global $database;

        $filter["`expense_id`"] = $database->SQLValue($id, MySQL::SQLVALUE_NUMBER);

        $query = MySQL::BuildSQLDelete(TBL_EXPENSE, $filter);

        return $database->Query($query);
    }


}
