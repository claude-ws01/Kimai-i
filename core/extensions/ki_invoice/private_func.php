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

// Determine if the expenses extension is used.
$expense_ext_available = false;
if (file_exists('../ki_expense/private_db_layer_mysql.php')) {
    include('../ki_expense/private_db_layer_mysql.php');
    $expense_ext_available = true;
}


$activityIndexMap = array(); // when creating the short form contains index of each activity in the array
function invoice_add_to_array(&$array, $row, $short_form)
{
    global $activityIndexMap;

    if ($short_form && $row['type'] === 'timesheet') {
        if (isset($activityIndexMap[$row['desc']])) {
            $index         = $activityIndexMap[$row['desc']];
            $totalTime     = $array[$index]['hour'];
            $totalAmount   = $array[$index]['amount'];
            $array[$index] = array(
                'type'         => 'timesheet',
                'project_name' => $row['project_name'],
                'location'     => $row['location'],
                'desc'         => $row['desc'],
                'hour'         => $totalTime + $row['hour'],
                'fduration'    => $row['fduration'],
                'amount'       => $totalAmount + $row['amount'],
                'date'         => $row['date'],
                'description'  => $row['description'],
                'rate'         => ($totalAmount + $row['amount']) / ($totalTime + $row['hour']),
                'ref_code'     => $row['ref_code'],
                'comment'      => $row['comment'],
                'username'     => $row['username'],
                'user_alias'   => $row['user_alias'],
            );

            return;
        }
        else {
            $activityIndexMap[$row['desc']] = count($array);
        }
    }
    $array[] = $row;
}

/**
 * Get a combined array with time recordings and expenses to export.
 *
 *
 * @param int   $start          Time from which to take entries into account.
 * @param int   $end            Time until which to take entries into account.
 * @param array $projects       Array of project IDs to filter by.
 * @param int   $filter_cleared (-1: show all, 0:only cleared 1: only not cleared) entries
 * @param bool  $short_form     should the short form be created
 *
 * @return array with time recordings and expenses chronologically sorted
 */
function invoice_get_details($start, $end, $projects, $filter_cleared, $short_form)
{
    global $expense_ext_available, $database;

    $blank = array(
        'type'          => null,
        'project_name'  => null,
        'location'      => null,
        'desc'          => null,
        'hour'          => null,
        'fduration'     => null,
        'amount'        => null,
        'date'          => null,
        'description'   => null,
        'rate'          => null,
        'ref_code'      => null,
        'comment'       => null,
        'activity_name' => null,
        'username'      => null,
        'user_alias'    => null,
    );

    $limit             = false;
    $reverse_order     = false;
    $limitCommentSize  = true;
    $filter_refundable = -1;

    $ts_entries = $database->get_timesheet($start, $end, null, null, $projects, null, $limit, $reverse_order,
                                           $filter_cleared);

    $xpe_entries = array();
    if ($expense_ext_available) {
        $xpe_entries = get_expenses($start, $end, null, null, $projects, $limit, $reverse_order,
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


    $result_arr = array();
    foreach ($m_data as $K => $row) {
        $arr = $blank;

        if ($m_type[$K] === 'ts') {
            if ($row['end'] !== 0) {
                // active recordings will be omitted
                $arr['type']         = 'timesheet';
                $arr['project_name'] = $row['project_name'];
                $arr['location']     = $row['location'];
                $arr['hour']         = $row['duration'] / 3600;
                $arr['desc']         = $row['activity_name']; // use as short
                $arr['fDuration']    = $row['formatted_duration'];
                $arr['amount']       = $row['wage'];
                $arr['date']         = date('m/d/Y', $row['start']);
                $arr['description']  = $row['description'];
                $arr['rate']         = $row['rate'];
                $arr['ref_code']     = $row['ref_code'];
                if ($limitCommentSize) {
                    $arr['comment'] = Format::addEllipsis($row['comment'], 150);
                }
                else {
                    $arr['comment'] = $row['comment'];
                }
                $arr['activity_name'] = $row['activity_name'];
                $arr['username']      = $row['username'];
                $arr['user_alias']    = $row['user_alias'];

                invoice_add_to_array($result_arr, $arr, $short_form);
            }
        }

        else {
            $arr['type']         = 'expense';
            $arr['project_name'] = $row['project_name'];
            $arr['desc']         = $row['description']; // use as short
            $arr['multiplier']   = $row['multiplier'];
            $arr['value']        = $row['value'];
            $arr['amount']       = sprintf('%01.2f', $row['value'] * $row['multiplier']);
            $arr['date']         = date('m/d/Y', $row['timestamp']);
            $arr['description']  = $row['description'];

            if ($limitCommentSize) {
                $arr['comment'] = Format::addEllipsis($row['comment'], 150);
            }
            else {
                $arr['comment'] = $row['comment'];
            }
            $arr['username']   = $row['username'];
            $arr['user_alias'] = $row['user_alias'];

            invoice_add_to_array($result_arr, $arr, $short_form);
        }

    }

    return $result_arr;
}
