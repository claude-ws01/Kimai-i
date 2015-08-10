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
 * delete expense entry
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

    $filter['expense_id'] = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);
    $query                = MySQL::buildSqlDelete(TBL_EXPENSE, $filter);

    return $database->query($query);
}

/**
 * create exp entry
 *
 * @param integer $id   ID of record
 * @param integer $data array with record data
 *
 * @global array  $kga  kimai-global-array
 * @author sl
 */
function expense_create($userID, array $data)
{
    global $database;

    $data = clean_data($data);

    $values ['project_id']   = $database->sqlValue($data ['project_id'], MySQL::SQLVALUE_NUMBER);
    $values ['description']  = $database->sqlValue($data ['description']);
    $values ['comment']      = $database->sqlValue($data ['comment']);
    $values ['comment_type'] = $database->sqlValue($data ['comment_type'], MySQL::SQLVALUE_NUMBER);
    $values ['timestamp']    = $database->sqlValue($data ['timestamp'], MySQL::SQLVALUE_NUMBER);
    $values ['multiplier']   = $database->sqlValue($data ['multiplier'], MySQL::SQLVALUE_NUMBER);
    $values ['value']        = $database->sqlValue($data ['value'], MySQL::SQLVALUE_NUMBER);
    $values ['user_id']      = $database->sqlValue($userID, MySQL::SQLVALUE_NUMBER);
    $values ['refundable']   = $database->sqlValue($data ['refundable'], MySQL::SQLVALUE_NUMBER);

    $result = $database->insertRow(TBL_EXPENSE, $values);

    if (!$result) {
        Logger::logfile('expense_create: ' . $database->error());

        return false;
    }

    return $result;
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

function expenses_widthhereClausesFromFilters($users, $customers, $projects)
{
    global $database;

    if (!is_array($users)) {
        $users = array();;
    }
    if (!is_array($customers)) {
        $customers = array();;
    }
    if (!is_array($projects)) {
        $projects = array();;
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

    $whereClauses = array();

    if (count($users) > 0) {
        $whereClauses[] = 'user_id in (' . implode(',', $users) . ')';
    }

    if (count($customers) > 0) {
        $whereClauses[] = 'customer_id in (' . implode(',', $customers) . ')';
    }

    if (count($projects) > 0) {
        $whereClauses[] = 'project_id in (' . implode(',', $projects) . ')';
    }

    return $whereClauses;

}

/*
 * returns expenses for specific user as multidimensional array
 *
 * @param integer $user ID of user in table users
 *
 * @global array  $kga  kimai-global-array
 * @return array
 * @author th
 */
// TODO: Test it!
function get_expenses($start, $end, $users = null, $customers = null, $projects = null, $limit = false,
                      $reverse_order = false, $filter_refundable = -1, $filterCleared = null)
{
    global $kga, $database;

    if (!is_numeric($filterCleared)) {
        $filterCleared = $kga['pref']['hide_cleared_entries'] - 1; // 0 gets -1 for disabled, 1 gets 0 for only not cleared entries
    }

    $start = $database->sqlValue($start, MySQL::SQLVALUE_NUMBER);
    $end   = $database->sqlValue($end, MySQL::SQLVALUE_NUMBER);
    $limit = $database->sqlValue($limit, MySQL::SQLVALUE_NUMBER);

    $p = $kga['server_prefix'];

    $whereClauses = expenses_widthhereClausesFromFilters($users, $customers, $projects);

    if (is_customer()) {
        $whereClauses[] = 'project.internal = 0';
    }

    if ($start) {
        $whereClauses[] = "timestamp >= $start";
    }
    if ($end) {
        $whereClauses[] = "timestamp <= $end";
    }
    if ($filterCleared > -1) {
        $whereClauses[] = "cleared = $filterCleared";
    }

    switch ($filter_refundable) {
        case 0:
            $whereClauses[] = 'refundable > 0';
            break;
        case 1:
            $whereClauses[] = 'refundable <= 0';
            break;
        case -1:
        default:
            // return all expenses - refundable and non refundable
    }
    if ($limit) {
        $limit = 'LIMIT 100';
        if (isset($kga['pref']['rowlimit'])) {
            $limit = 'LIMIT ' . $kga['pref']['rowlimit'];
        }
    }
    else {
        $limit = '';
    }

    $query = "SELECT expense.*,
                    customer.name AS customer_name,
                    customer.customer_id,
                    project.name AS project_name,
                    project.comment AS project_comment,
                    `user`.name AS username,
                    `user`.alias AS user_alias
             FROM {$p}expense AS expense
             LEFT Join {$p}project AS project USING(project_id)
             LEFT Join {$p}customer AS customer USING(customer_id)
             LEFT Join `{$p}user` AS user USING(user_id) "
        . (count($whereClauses) > 0 ? ' WHERE ' : ' ') . implode(' AND ', $whereClauses) .
        ' ORDER BY timestamp ' . ($reverse_order ? 'ASC ' : 'DESC ') . $limit . ';';

    $database->query($query);

    $i   = 0;
    $arr = array();
    /* TODO: needs revision as foreach loop */
    $database->moveFirst();
    while (!$database->endOfSeek()) {
        $row                     = $database->row();
        $arr[$i]['expense_id']   = $row->expense_id;
        $arr[$i]['timestamp']    = $row->timestamp;
        $arr[$i]['multiplier']   = $row->multiplier;
        $arr[$i]['value']        = $row->value;
        $arr[$i]['description']  = $row->description;
        $arr[$i]['comment']      = $row->comment;
        $arr[$i]['comment_type'] = $row->comment_type;
        $arr[$i]['refundable']   = $row->refundable;
        $arr[$i]['cleared']      = $row->cleared;

        $arr[$i]['customer_id']   = $row->customer_id;
        $arr[$i]['customer_name'] = $row->customer_name;

        $arr[$i]['project_id']      = $row->project_id;
        $arr[$i]['project_name']    = $row->project_name;
        $arr[$i]['project_comment'] = $row->project_comment;

        $arr[$i]['user_id']    = $row->user_id;
        $arr[$i]['username']   = $row->username;
        $arr[$i]['user_alias'] = $row->user_alias;
        $i++;
    }

    return $arr;
}


/**
 * returns single expense entry as array
 *
 * @param integer $id  ID of entry in table exp
 *
 * @global array  $kga kimai-global-array
 * @return array
 * @author sl
 */
function get_expense($id)
{
    global $kga, $database;

    $id = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);
    $p  = $kga['server_prefix'];

    $query = "SELECT * FROM {$p}expense WHERE expense_id = $id LIMIT 1;";

    $database->query($query);

    return $database->rowArray(0, MYSQLI_ASSOC);
}


/**
 * Returns the data of a certain expense record
 *
 * @param        int    expense_id expense_id of the record
 *
 * @global array $kga   kimai-global-array
 * @return array               the record's data as array, false on failure
 * @author ob
 */
function expense_get($expenseID)
{
    global $kga, $database;

    $p = $kga['server_prefix'];

    $expenseID = $database->sqlValue($expenseID, MySQL::SQLVALUE_NUMBER);

    if ($expenseID) {
        $result = $database->query("SELECT * FROM {$p}expense WHERE expense_id = " . $expenseID);
    }
    else {
        $result = $database->query("SELECT * FROM {$p}expense WHERE user_id = {$kga['who']['id']} ORDER BY expense_id DESC LIMIT 1");
    }

    if ($result->num_rows === 0) {
        return false;
    }
    else {
        return $database->rowArray(0, MYSQLI_ASSOC);
    }
}


/*
 * edit exp entry
 *
 * @param integer $id   ID of record
 *
 * @global array  $kga  kimai-global-array
 *
 * @param integer $data array with new record data
 *
 * @author th
 */
function expense_edit($id, array $data)
{
    global $kga, $database;

    $data = clean_data($data);

    $original_array = expense_get($id);
    $new_array      = array();

    foreach ($original_array as $key => $value) {
        if (isset($data[$key])) {
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

    $filter ['expense_id'] = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);
    $query                 = MySQL::buildSqlUpdate(TBL_EXPENSE, $values, $filter);


    return ($database->query($query) !== false);
}

/**
 * Get the sum of expenses for every `user`.
 *
 * @param int   $start     Time from which to take the expenses into account.
 * @param int   $end       Time until which to take the expenses into account.
 * @param array $users     Array of user IDs to filter the expenses by.
 * @param array $customers Array of customer IDs to filter the expenses by.
 * @param array $projects  Array of project IDs to filter the expenses by.
 *
 * @return array Array which assigns every user (via his ID) the sum of his expenses.
 */
function expenses_by_user($start, $end, $users = null, $customers = null, $projects = null)
{
    global $kga, $database;

    $start = $database->sqlValue($start, MySQL::SQLVALUE_NUMBER);
    $end   = $database->sqlValue($end, MySQL::SQLVALUE_NUMBER);

    $p              = $kga['server_prefix'];
    $whereClauses   = expenses_widthhereClausesFromFilters($users, $customers, $projects);
    $whereClauses[] = "`{$p}user`.trash = 0";

    if ($start) {
        $whereClauses[] = "timestamp >= $start";
    }
    if ($end) {
        $whereClauses[] = "timestamp <= $end";
    }

    $query = "SELECT SUM(value*multiplier) as expenses, user_id
             FROM {$p}expense
             Join {$p}project USING (project_id)
             Join {$p}customer USING (customer_id)
             Join `{$p}user` USING (user_id) " . (count($whereClauses) > 0 ? ' WHERE '
            : ' ') . implode(' AND ', $whereClauses) .
        ' GROUP BY user_id;';

    $result = $database->query($query);

    if ($result->num_rows === 0) {
        return array();
    }
    $rows = $database->recordsArray(MYSQLI_ASSOC);
    if (!$rows) {
        return array();
    }


    $arr = array();
    foreach ($rows as $row) {
        $arr[$row['user_id']] = $row['expenses'];
    }

    return $arr;
}


/**
 * Get the sum of expenses for every customer.
 *
 * @param int   $start     Time from which to take the expenses into account.
 * @param int   $end       Time until which to take the expenses into account.
 * @param array $users     Array of user IDs to filter the expenses by.
 * @param array $customers Array of customer IDs to filter the expenses by.
 * @param array $projects  Array of project IDs to filter the expenses by.
 *
 * @return array Array which assigns every customer (via his ID) the sum of his expenses.
 */
function expenses_by_customer($start, $end, $users = null, $customers = null, $projects = null)
{
    global $kga, $database;

    $start = $database->sqlValue($start, MySQL::SQLVALUE_NUMBER);
    $end   = $database->sqlValue($end, MySQL::SQLVALUE_NUMBER);

    $p = $kga['server_prefix'];

    $whereClauses   = expenses_widthhereClausesFromFilters($users, $customers, $projects);
    $whereClauses[] = "{$p}customer.trash = 0";

    if ($start) {
        $whereClauses[] = "timestamp >= $start";
    }
    if ($end) {
        $whereClauses[] = "timestamp <= $end";
    }

    $query = "SELECT SUM(`value` * `multiplier`) as expenses, customer_id FROM {$p}expense
            Left Join {$p}project USING (project_id)
            Left Join {$p}customer USING (customer_id) " . (count($whereClauses) > 0 ? ' WHERE '
            : ' ') . implode(' AND ', $whereClauses) .
        ' GROUP BY customer_id;';

    $result = $database->query($query);

    if ($result->num_rows === 0) {
        return array();
    }

    $rows = $database->recordsArray(MYSQLI_ASSOC);
    if (!$rows) {
        return array();
    }

    $arr = array();
    foreach ($rows as $row) {
        $arr[$row['customer_id']] = $row['expenses'];
    }

    return $arr;
}

/**
 * Get the sum of expenses for every project.
 *
 * @param int   $start     Time from which to take the expenses into account.
 * @param int   $end       Time until which to take the expenses into account.
 * @param array $users     Array of user IDs to filter the expenses by.
 * @param array $customers Array of customer IDs to filter the expenses by.
 * @param array $projects  Array of project IDs to filter the expenses by.
 *
 * @return array Array which assigns every project (via his ID) the sum of his expenses.
 */
function expenses_by_project($start, $end, $users = null, $customers = null, $projects = null)
{
    global $kga, $database;

    $start = $database->sqlValue($start, MySQL::SQLVALUE_NUMBER);
    $end   = $database->sqlValue($end, MySQL::SQLVALUE_NUMBER);

    $p              = $kga['server_prefix'];
    $whereClauses   = expenses_widthhereClausesFromFilters($users, $customers, $projects);
    $whereClauses[] = "{$p}project.trash = 0";

    if ($start) {
        $whereClauses[] = "timestamp >= $start";
    }
    if ($end) {
        $whereClauses[] = "timestamp <= $end";
    }

    $query = "SELECT sum(`value` * `multiplier`) as expenses, project_id FROM {$p}expense
            Left Join {$p}project USING(project_id)
            Left Join {$p}customer USING(customer_id) " . (count($whereClauses) > 0 ? ' WHERE '
            : ' ') . implode(' AND ', $whereClauses) .
        ' GROUP BY project_id;';

    $result = $database->query($query);

    if ($result->num_rows === 0) {
        return array();
    }

    $rows = $database->recordsArray(MYSQLI_ASSOC);
    if (!$rows) {
        return array();
    }

    $arr = array();
    foreach ($rows as $row) {
        $arr[$row['project_id']] = $row['expenses'];
    }

    return $arr;
}


