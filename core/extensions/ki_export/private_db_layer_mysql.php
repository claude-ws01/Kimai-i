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
 * set cleared state for timeSheet entry
 *
 * @param integer $id      -> ID of record
 * @param boolean $cleared -> true if record is cleared, otherwise false
 *
 * @global array  $kga     kimai-global-array
 * @return boolean
 */
function export_timesheet_entry_set_cleared($id, $cleared)
{
    global $kga, $database;

    $values['cleared']       = $cleared ? 1 : 0;
    $filter['time_entry_id'] = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);
    $query                   = MySQL::buildSqlUpdate(TBL_TIMESHEET, $values, $filter);

    if ($database->query($query)) {
        return true;
    }
    else {
        return false;
    }
}

function export_expense_set_cleared($id, $cleared)
{
    global $database;

    $values['cleared']    = $cleared ? 1 : 0;
    $filter['expense_id'] = $database->sqlValue($id, MySQL::SQLVALUE_NUMBER);
    $query                = MySQL::buildSqlUpdate(TBL_EXPENSE, $values, $filter);

    if ($database->query($query)) {
        return true;
    }
    else {
        return false;
    }
}


/**
 * save deselection of columns
 *
 * @param string $header             -> header name
 *
 * @global array $kga                kimai-global-array
 * @global array $all_column_headers array containing all columns
 * @return boolean
 */
function export_toggle_header($header)
{
    global $kga, $database, $all_column_headers;

    $header_number = array_search($header, $all_column_headers);
    if ($header_number != false) {
        $table  = TBL_PREFERENCE;
        $userID = $database->sqlValue($kga['user']['user_id'], MySQL::SQLVALUE_NUMBER);

        $query = "INSERT INTO $table
                    (`user_id`, `option`, `value`) VALUES
                    ($userID, 'export_disabled_columns', POWER(2,$header_number))
                    ON DUPLICATE KEY UPDATE `value`=`value`^POWER(2,$header_number)";

        if ($database->query($query)) {
            return true;
        }
    }

    return false;
}

/**
 * get list of deselected columns
 *
 * @param integer $userID             -> header name
 *
 * @global array  $kga                kimai-global-array
 * @global array  $all_column_headers array containing all columns
 * @return boolean
 */
function export_get_disabled_headers($userID)
{
    global $database, $all_column_headers;

    $disabled_headers = array();
    foreach( $all_column_headers as $key) {
        $disabled_headers[$key] = null;
    }

    $filter['user_id'] = $database->sqlValue($userID, MySQL::SQLVALUE_NUMBER);
    $filter['option']  = $database->sqlValue('export_disabled_columns');

    if (!$database->selectRows(TBL_PREFERENCE, $filter)) return 0;

    $result_array = $database->rowArray(0, MYSQL_ASSOC);
    $code         = $result_array['value'];

    $i = 0;
    while ($code > 0) {
        if ($code % 2 == 1) // bit set?
        {
            $disabled_headers[$all_column_headers[$i]] = 'disabled';
        }

        // next bit and array element
        $code = $code / 2;
        $i++;
    }

    return $disabled_headers;
}
