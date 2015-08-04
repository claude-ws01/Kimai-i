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
global $all_column_headers;
$all_column_headers = array(
    'date',
    'from',
    'to',
    'time',
    'dec_time',
    'rate',
    'wage',
    'budget',
    'approved',
    'status',
    'billable',
    'customer',
    'project',
    'activity',
    'description',
    'comment',
    'location',
    'ref_code',
    'user',
    'cleared',
);


// Determine if the expenses extension is used.
$expense_ext_available = false;

if (file_exists('../ki_expense/private_db_layer_mysql.php')) {
    include('../ki_expense/private_db_layer_mysql.php');
    $expense_ext_available = true;
}
include('private_db_layer_mysql.php');

/*
 * Get a combined array with time recordings and expenses to export.
 *
 * @param int    $start            Time from which to take entries into account.
 * @param int    $end              Time until which to take entries into account.
 * @param array  $users            Array of user IDs to filter by.
 * @param array  $customers        Array of customer IDs to filter by.
 * @param array  $projects         Array of project IDs to filter by.
 * @param array  $activities       Array of activity IDs to filter by.
 * @param bool   $limit            sbould the amount of entries be limited
 * @param bool   $reverse_order    should the entries be put out in reverse order
 * @param string $default_location use this string if no location is set for the entry
 * @param int    $filter_cleared   (-1: show all, 0:only cleared 1: only not cleared) entries
 * @param int    $filter_type      (-1 show time and expenses, 0: only show time entries, 1: only show expenses)
 * @param int    $limitCommentSize should comments be cut off, when they are too long
 *
 * @return array with time recordings and expenses chronologically sorted
 */
function export_get_data($start, $end, $users = null, $customers = null, $projects = null,
                         $activities = null, $limit = false, $reverse_order = false, $default_location = '', $filter_cleared = -1,
                         $filter_type = -1, $limitCommentSize = true, $filter_refundable = -1)
{
    global $expense_ext_available, $database;

    $blank = array(
        'type'               => null,
        'id'                 => null,
        'time_in'            => null,
        'time_out'           => null,
        'duration'           => null,
        'formatted_duration' => null,
        'decimal_duration'   => null,
        'rate'               => null,
        'wage'               => null,
        'wage_decimal'       => null,
        'budget'             => null,
        'approved'           => null,
        'status_id'          => null,
        'status'             => null,
        'billable'           => null,
        'customer_id'        => null,
        'customer_name'      => null,
        'project_id'         => null,
        'project_name'       => null,
        'description'        => null,
        'project_comment'    => null,
        'activity_id'        => null,
        'activity_name'      => null,
        'comment'            => null,
        'comment_type'       => null,
        'location'           => $default_location,
        'ref_code'           => null,
        'username'           => null,
        'cleared'            => null,
    );


    $ts_entries = $xpe_entries = array();
    if ($filter_type !== 1) {
        $ts_entries = $database->get_timesheet($start, $end, $users, $customers, $projects, $activities, $limit,
                                               $reverse_order, $filter_cleared);
    }

    if ($filter_type !== 0 && $expense_ext_available) {
        $xpe_entries = get_expenses($start, $end, $users, $customers, $projects, $limit, $reverse_order,
                                    $filter_refundable, $filter_cleared);
    }


    $m_time = $m_type = $m_data = array();
    // fill in timesheets
    foreach ($ts_entries as $key => $ts_entry) {
        $m_time[] = $ts_entry['start'];
        $m_type[] = 'ts';
        $m_data[] = &$ts_entries[$key];
    }
    // fill in expenses
    foreach ($xpe_entries as $key => $xpe_entry) {
        $m_time[] = $xpe_entry['timestamp'];
        $m_type[] = 'xpe';
        $m_data[] = &$xpe_entries[$key];
    }

    // safety
    if (count($m_time) === 0) {
        return array();
    }

    // sort array
    if ($reverse_order) {
        array_multisort($m_time, SORT_DESC, SORT_NUMERIC, $m_type, $m_data);
    }
    else {
        array_multisort($m_time, SORT_ASC, SORT_NUMERIC, $m_type, $m_data);
    }


    $cnt        = 0;
    $result_arr = array();
    foreach ($m_data as $K => $row) {
        $result_arr[$cnt] = $blank;
        $arr              = &$result_arr[$cnt];

        if ($m_type[$K] === 'ts') {
            if ($row['end'] !== 0) {
                // active recordings will be omitted
                $arr['type']               = 'timesheet';
                $arr['id']                 = $row['time_entry_id'];
                $arr['time_in']            = $row['start'];
                $arr['time_out']           = $row['end'];
                $arr['duration']           = $row['duration'];
                $arr['formatted_duration'] = $row['formatted_duration'];
                $arr['decimal_duration']   = sprintf("%01.2f", $row['duration'] / 3600);
                $arr['rate']               = $row['rate'];
                $arr['wage']               = $row['wage'];
                $arr['wage_decimal']       = $row['wage_decimal'];
                $arr['budget']             = $row['budget'];
                $arr['approved']           = $row['approved'];
                $arr['status_id']          = $row['status_id'];
                $arr['status']             = $row['status'];
                $arr['billable']           = $row['billable'];
                $arr['customer_id']        = $row['customer_id'];
                $arr['customer_name']      = $row['customer_name'];
                $arr['project_id']         = $row['project_id'];
                $arr['project_name']       = $row['project_name'];
                $arr['description']        = $row['description'];
                $arr['project_comment']    = $row['project_comment'];
                $arr['activity_id']        = $row['activity_id'];
                $arr['activity_name']      = $row['activity_name'];

                if ($limitCommentSize) {
                    $arr['comment'] = Format::addEllipsis($row['comment'], 150);
                }
                else {
                    $arr['comment'] = $row['comment'];
                }

                $arr['comment_type'] = $row['comment_type'];
                $arr['location']     = $row['location'];
                $arr['ref_code']     = $row['ref_code'];
                $arr['username']     = $row['username'];
                $arr['cleared']      = $row['cleared'];
            }
        }


        else {
            $arr['type']            = 'expense';
            $arr['id']              = $row['expense_id'];
            $arr['time_in']         = $row['timestamp'];
            $arr['time_out']        = $row['timestamp'];
            $arr['wage']            = sprintf("%01.2f", $row['value'] * $row['multiplier']);
            $arr['customer_id']     = $row['customer_id'];
            $arr['customer_name']   = $row['customer_name'];
            $arr['project_id']      = $row['project_id'];
            $arr['project_name']    = $row['project_name'];
            $arr['description']     = $row['description'];
            $arr['project_comment'] = $row['project_comment'];

            if ($limitCommentSize) {
                $arr['comment'] = Format::addEllipsis($row['comment'], 150);
            }
            else {
                $arr['comment'] = $row['comment'];
            }

            $arr['comment']      = $row['comment'];
            $arr['comment_type'] = $row['comment_type'];
            $arr['username']     = $row['username'];
            $arr['cleared']      = $row['cleared'];
        }

        $cnt++;
    }

    return $result_arr;
}

/**
 * Merge the expense annotations with the timesheet annotations. The result will
 * be the timesheet array, which has to be passed as the first argument.
 *
 * @param array the timesheet annotations array
 * @param array the expense annotations array
 */
function merge_annotations(&$ts_entries, &$xpe_entries)
{
    foreach ($xpe_entries as $id => $costs) {
        if (!isset($ts_entries[$id])) {
            $ts_entries[$id]['costs'] = $costs;
        }
        else {
            $ts_entries[$id]['costs'] += $costs;
        }
    }
}

/**
 * Get annotations for the user sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int   $start      Time from which to take entries into account.
 * @param int   $end        Time until which to take entries into account.
 * @param array $users      Array of user IDs to filter by.
 * @param array $customers  Array of customer IDs to filter by.
 * @param array $projects   Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 *
 * @return array Array which assigns every user (via his ID) the data to show.
 */
function export_get_user_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $expense_ext_available, $database;

    $arr = $database->get_time_users($start, $end, $users, $customers, $projects, $activities);
    if ($expense_ext_available) {
        $xpe_entries = expenses_by_user($start, $end, $users, $customers, $projects);
        merge_annotations($arr, $xpe_entries);
    }

    return $arr;
}

/**
 * Get annotations for the customer sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int   $start      Time from which to take entries into account.
 * @param int   $end        Time until which to take entries into account.
 * @param array $users      Array of user IDs to filter by.
 * @param array $customers  Array of customer IDs to filter by.
 * @param array $projects   Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 *
 * @return array Array which assigns every customer (via his ID) the data to show.
 */
function export_get_customer_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $expense_ext_available, $database;

    $arr = $database->get_time_customers($start, $end, $users, $customers, $projects, $activities);
    if ($expense_ext_available) {
        $xpe_entries = expenses_by_customer($start, $end, $users, $customers, $projects);
        merge_annotations($arr, $xpe_entries);
    }

    return $arr;
}

/**
 * Get annotations for the project sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int   $start      Time from which to take entries into account.
 * @param int   $end        Time until which to take entries into account.
 * @param array $users      Array of user IDs to filter by.
 * @param array $customers  Array of customer IDs to filter by.
 * @param array $projects   Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 *
 * @return array Array which assigns every project (via his ID) the data to show.
 */
function export_get_project_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $expense_ext_available, $database;

    $arr = $database->get_time_projects($start, $end, $users, $customers, $projects, $activities);
    if ($expense_ext_available) {
        $xpe_entries = expenses_by_project($start, $end, $users, $customers, $projects);
        merge_annotations($arr, $xpe_entries);
    }

    return $arr;
}

/**
 * Get annotations for the activity sub list. Currently it's just the time, like
 * in the timesheet extension.
 *
 * @param int   $start      Time from which to take entries into account.
 * @param int   $end        Time until which to take entries into account.
 * @param array $users      Array of user IDs to filter by.
 * @param array $customers  Array of customer IDs to filter by.
 * @param array $projects   Array of project IDs to filter by.
 * @param array $activities Array of activity IDs to filter by.
 *
 * @return array Array which assigns every taks (via his ID) the data to show.
 */
function export_get_activity_annotations($start, $end, $users = null, $customers = null, $projects = null, $activities = null)
{
    global $database;

    $arr = $database->get_time_activities($start, $end, $users, $customers, $projects, $activities);

    return $arr;
}

/**
 * Prepare a string to be printed as a single field in the csv file.
 *
 * @param string $field            String to prepare.
 * @param string $column_delimiter Character used to delimit columns.
 * @param string $quote_char       Character used to quote strings.
 *
 * @return string Correctly formatted string.
 */
function csv_prepare_field($field, $column_delimiter, $quote_char)
{
    if (strpos($field, $column_delimiter) === false && strpos($field, $quote_char) === false && strpos($field, "\n") === false) {
        return $field;
    }
    $field = str_replace($quote_char, $quote_char . $quote_char, $field);
    $field = $quote_char . $field . $quote_char;

    return $field;
}


