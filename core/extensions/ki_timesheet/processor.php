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

// ================
// = TS PROCESSOR =
// ================

// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates/';
require('../../includes/kspi.php');
global $view, $database, $kga;

function timesheetAccessAllowed($entry, $action, &$errors)
{   // SECURITY //
    global $database, $kga;

    if (!array_key_exists('user', $kga)) {
        $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

        return false;
    }
    if ($kga['is_user_root']) {
        return true;
    }


    if ($kga['conf']['edit_limit'] !== '-' && time() - $entry['end'] > $kga['conf']['edit_limit'] && $entry['end'] != 0) {
        $errors[''] = $kga['dict']['editLimitError'];

        return false;
    }


    if ((int)$entry['user_id'] === (int)$kga['user']['user_id']) {
        $permissionName = "ki_timesheet__own_entry__${action}";
        if ($database->global_role_allows(any_get_global_role_id(), $permissionName)) {
            return true;
        }
        else {
            Logger::logfile("missing global permission $permissionName for user " . $kga['user']['name']);
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

            return false;
        }
    }

    $groups = $database->user_get_group_ids($entry['user_id'], false);
    //CN..current-user groups already in $kga// $assignedOwnGroups = array_intersect($groups, $database->user_get_group_ids($kga['user']['user_id']));
    $assignedOwnGroups = array_intersect($groups, $kga['user']['groups']);

    if (count($assignedOwnGroups) > 0) {
        $permissionName = "ki_timesheet__other_entry__own_group__${action}";
        if ($database->checkMembershipPermission($kga['user']['user_id'], $assignedOwnGroups, $permissionName)) {
            return true;
        }
        else {
            Logger::logfile("missing membership permission $permissionName of own group(s) " .
                            implode(', ', $assignedOwnGroups) . ' for user ' . $kga['user']['name']);
            $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

            return false;
        }

    }

    $permissionName = "ki_timesheet__other_entry__other_group__${action}";
    if ($database->global_role_allows(any_get_global_role_id(), $permissionName)) {
        return true;
    }
    else {
        Logger::logfile("missing global permission $permissionName for user " . $kga['user']['name']);
        $errors[''] = $kga['dict']['errorMessages']['permissionDenied'];

        return false;
    }

}

// ==================
// = handle request =
// ==================
switch ($axAction) {

    // ==============================================
    // = start a new recording based on another one =
    // ==============================================
    case 'record':
        $response = array();

        $timesheet_entry = $database->timesheet_get_data($id);

        $timesheet_entry['start']    = time();
        $timesheet_entry['end']      = 0;
        $timesheet_entry['duration'] = 0;
        $timesheet_entry['cleared']  = 0;

        $errors = array();
        timesheetAccessAllowed($timesheet_entry, 'edit', $errors);
        $response['errors'] = $errors;

        if (count($errors) == 0) {

            $newTimeSheetEntryID = $database->timeEntry_create($timesheet_entry);

            $userData                  = array();
            $userData['last_record']   = $newTimeSheetEntryID;
            $userData['last_project']  = $timesheet_entry['project_id'];
            $userData['last_activity'] = $timesheet_entry['activity_id'];
            $database->user_edit($kga['user']['user_id'], $userData);


            $project  = $database->project_get_data($timesheet_entry['project_id']);
            $customer = $database->customer_get_data($project['customer_id']);
            $activity = $database->activity_get_data($timesheet_entry['activity_id']);

            $response['customer']          = $customer['customer_id'];
            $response['project_name']      = $project['name'];
            $response['customer_name']     = $customer['name'];
            $response['activity_name']     = $activity['name'];
            $response['current_recording'] = $newTimeSheetEntryID;
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($response);
        break;

    // ==================
    // = stop recording =
    // ==================
    case 'stop':
        $errors = array();

        $data = $database->timesheet_get_data($id);

        timesheetAccessAllowed($data, 'edit', $errors);

        if (count($errors) == 0) {
            $database->stopRecorder($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    // =======================================
    // = set comment for a running recording =
    // =======================================
    case 'edit_running':
        $errors = array();

        $data = $database->timesheet_get_data($id);

        timesheetAccessAllowed($data, 'edit', $errors);

        if (count($errors) == 0) {
            if (isset($_REQUEST['project'])) {
                $database->timeEntry_edit_project($id, $_REQUEST['project']);
            }

            if (isset($_REQUEST['activity'])) {
                $database->timeEntry_edit_activity($id, $_REQUEST['activity']);
            }
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    // =========================================
    // = Erase timesheet entry via quickdelete =
    // =========================================
    case 'quickdelete':
        $errors = array();

        $data = $database->timesheet_get_data($id);

        timesheetAccessAllowed($data, 'delete', $errors);

        if (count($errors) == 0) {
            $database->timeEntry_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    // ==================================================
    // = Get the best rate for the project and activity =
    // ==================================================
    case 'bestFittingRates':
        $data = array('errors' => array());

        if (!array_key_exists('user', $kga)) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (!$database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__show_rates')) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (count($data['errors']) == 0) {
            $data['hourly_rate'] = $database->get_best_fitting_rate($kga['user']['user_id'], $_REQUEST['project_id'], $_REQUEST['activity_id']);
            $data['fixed_rate']  = $database->get_best_fitting_fixed_rate($_REQUEST['project_id'], $_REQUEST['activity_id']);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        break;


    // ==============================================================
    // = Get the new budget data after changing project or activity =
    // ==============================================================
    case 'budgets':
        $data = array('errors' => array());

        if (!array_key_exists('user', $kga)) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (!$database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__show_rates')) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (count($data['errors']) == 0) {
            $timesheet_entry = $database->timesheet_get_data($_REQUEST['timeSheetEntryID']);
            // we subtract the used data in case the activity is the same as in the db, otherwise
            // it would get counted twice. For all aother cases, just set the values to 0
            // so we don't subtract too much
            if ($timesheet_entry['activity_id'] != $_REQUEST['activity_id'] || $timesheet_entry['project_id'] != $_REQUEST['project_id']) {
                $timesheet_entry['budget']   = 0;
                $timesheet_entry['approved'] = 0;
                $timesheet_entry['rate']     = 0;
            }
            $data['activityBudgets'] = $database->get_activity_budget($_REQUEST['project_id'], $_REQUEST['activity_id']);
            $data['activityUsed']    = $database->get_budget_used($_REQUEST['project_id'], $_REQUEST['activity_id']);
            $data['timeSheetEntry']  = $timesheet_entry;
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        break;

    // ==============================================
    // = Get all rates for the project and activity =
    // ==============================================
    case 'allFittingRates':
        $data = array('errors' => array());

        if (!array_key_exists('user', $kga)) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (!$database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__show_rates')) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (count($data['errors']) === 0) {
            $rates = $database->allFittingRates($kga['user']['user_id'], $_REQUEST['project'], $_REQUEST['activity']);

            if (is_array($rates)) {
                foreach ((array)$rates as $rate) {
                    $line = Format::formatCurrency($rate['rate']);

                    $setFor = array(); // contains the list of 'types' for which this rate was set
                    if ($rate['user_id'] !== null) {
                        $setFor[] = $kga['dict']['username'];
                    }
                    if ($rate['project_id'] !== null) {
                        $setFor[] = $kga['dict']['project'];
                    }
                    if ($rate['activity_id'] !== null) {
                        $setFor[] = $kga['dict']['activity'];
                    }

                    if (count($setFor) !== 0) {
                        $line .= ' (' . implode($setFor, ', ') . ')';
                    }

                    $data['rates'][] = array('value' => $rate['rate'], 'desc' => $line);
                }
            }
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        break;

    // ==============================================
    // = Get all rates for the project and activity =
    // ==============================================
    case 'allFittingFixedRates':
        $data = array('errors' => array());

        if (!array_key_exists('user', $kga)) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (!$database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__show_rates')) {
            $data['errors'][] = $kga['dict']['editLimitError'];
        }

        if (count($data['errors']) === 0) {
            $rates = $database->allFittingFixedRates($_REQUEST['project'], $_REQUEST['activity']);

            if (is_array($rates)) {
                foreach ((array)$rates as $rate) {
                    $line = Format::formatCurrency($rate['rate']);

                    $setFor = array(); // contains the list of 'types' for which this rate was set
                    if ($rate['project_id'] !== null) {
                        $setFor[] = $kga['dict']['project'];
                    }
                    if ($rate['activity_id'] !== null) {
                        $setFor[] = $kga['dict']['activity'];
                    }

                    if (count($setFor) !== 0) {
                        $line .= ' (' . implode($setFor, ', ') . ')';
                    }

                    $data['rates'][] = array('value' => $rate['rate'], 'desc' => $line);
                }
            }
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        break;

    // ==================================================
    // = Get the best rate for the project and activity =
    // ==================================================
    case 'reload_activities_options':
        if (array_key_exists('customer', $kga)) {
            die();
        }
        $activities = $database->get_activities_by_project($_REQUEST['project'], any_get_group_ids());
        if (is_array($activities)) {
            foreach ((array)$activities as $activity) {
                if (!$activity['visible']) {
                    continue;
                }
                echo '<option value="' . $activity['activity_id'] . '">' .
                    $activity['name'] . '</option>\n';
            }
        }
        break;

    // =============================================
    // = Load timesheet data from DB and return it =
    // =============================================
    case 'reload_timeSheet':
        $filters = explode('|', $axValue);
        if ($filters[0] === '') {
            $filterUsers = array();
        }
        else {
            $filterUsers = explode(':', $filters[0]);
        }

        $filterCustomers = array_map(function ($customer) {
            return $customer['customer_id'];
        }, $database->get_customers(any_get_group_ids()));
        if ($filters[1] !== '') {
            $filterCustomers = array_intersect($filterCustomers, explode(':', $filters[1]));
        }

        $filterProjects = array_map(function ($project) {
            return $project['project_id'];
        }, $database->get_projects(any_get_group_ids()));
        if ($filters[2] !== '') {
            $filterProjects = array_intersect($filterProjects, explode(':', $filters[2]));
        }

        $filterActivities = array_map(function ($activity) {
            return $activity['activity_id'];
        }, $database->get_activities(any_get_group_ids()));
        if ($filters[3] !== '') {
            $filterActivities = array_intersect($filterActivities, explode(':', $filters[3]));
        }

        // if no userfilter is set, set it to current user
        if (array_key_exists('user', $kga) && (!is_array($filterUsers) || count($filterUsers) === 0)) {
            $filterUsers[] = $kga['user']['user_id'];
        }

        if (array_key_exists('customer', $kga)) {
            $filterCustomers = array($kga['customer']['customer_id']);
        }

        $timesheet_entries = $database->get_timesheet($in, $out, $filterUsers, $filterCustomers, $filterProjects, $filterActivities, 1);
        if (count($timesheet_entries) > 0) {
            $view->timeSheetEntries = $timesheet_entries;
        }
        else {
            $view->timeSheetEntries = 0;
        }
        if (array_key_exists('user', $kga)) {
            $view->latest_running_entry = $database->get_latest_running_entry();
            $view->total                = Format::formatDuration($database->get_duration($in, $out, $filterUsers, $filterCustomers, $filterProjects, $filterActivities));
        }
        else {
            $view->latest_running_entry = null;
            $view->total                = null;
        }

        $ann = $database->get_time_users($in, $out, $filterUsers, $filterCustomers, $filterProjects, $filterActivities);
        Format::formatAnnotations($ann);
        $view->user_annotations = $ann;

        $ann = $database->get_time_customers($in, $out, $filterUsers, $filterCustomers, $filterProjects, $filterActivities);
        Format::formatAnnotations($ann);
        $view->customer_annotations = $ann;

        $ann = $database->get_time_projects($in, $out, $filterUsers, $filterCustomers, $filterProjects, $filterActivities);
        Format::formatAnnotations($ann);
        $view->project_annotations = $ann;

        $ann = $database->get_time_activities($in, $out, $filterUsers, $filterCustomers, $filterProjects, $filterActivities);
        Format::formatAnnotations($ann);
        $view->activity_annotations = $ann;

        $view->hideComments     = true;
        $view->showOverlapLines = false;
        $view->show_ref_code    = true;

        // user can change these settings
        if (isset($kga['pref'])) {
            $view->hideComments     = $kga['pref']['show_comments_by_default'] != 1;
            $view->showOverlapLines = $kga['pref']['hide_overlap_lines'] != 1;
            $view->show_ref_code    = $kga['pref']['show_ref_code'] != 0;
        }

        $view->showRates = array_key_exists('user', $kga) && $database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__show_rates');

        echo $view->render('timesheet.php');
        break;


    // ==============================
    // = add / edit timesheet entry =
    // ==============================
    case 'add_edit_timeSheetEntry':
        header('Content-Type: application/json;charset=utf-8');
        $errors = array();

        $action = 'add';
        if ($id) {
            $action = 'edit';
        }
        if (isset($_REQUEST['erase'])) {
            $action = 'delete';
        }

        if ($id) {
            $data = $database->timesheet_get_data($id);

            // check if editing or deleting with the old values would be allowed
            if (!timesheetAccessAllowed($data, $action, $errors)) {
                echo json_encode(array('errors' => $errors));
                break;
            }
        }

        if (isset($_REQUEST['erase'])) {
            // delete checkbox set ?
            // then the record is simply dropped and processing stops at this point
            $database->timeEntry_delete($id);
            echo json_encode(array('errors' => $errors));
            break;
        }

        $data['project_id']   = $_REQUEST['project_id'];
        $data['activity_id']  = $_REQUEST['activity_id'];
        $data['location']     = $_REQUEST['location'];
        $data['ref_code']     = $_REQUEST['ref_code'];
        $data['description']  = $_REQUEST['description'];
        $data['comment']      = $_REQUEST['comment'];
        $data['comment_type'] = $_REQUEST['comment_type'];

        if ($database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__edit_rates')) {
            $data['rate']       = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['rate']);
            $data['fixed_rate'] = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['fixed_rate']);
        }
        elseif (!$id) {
            $data['rate']       = $database->get_best_fitting_rate($kga['user']['user_id'], $data['project_id'], $data['activity_id']);
            $data['fixed_rate'] = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['fixed_rate']);
        }
        $data['cleared']   = isset($_REQUEST['cleared']);
        $data['status_id'] = $_REQUEST['status_id'];
        $data['billable']  = $_REQUEST['billable'];
        $data['budget']    = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['budget']);
        $data['approved']  = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['approved']);
        $data['user_id']   = $_REQUEST['user_id'];


        // check if the posted time values are possible

        $validateDate = new Zend_Validate_Date(array('format' => 'dd.MM.yyyy'));
        $validateTime = new Zend_Validate_Date(array('format' => 'HH:mm:ss'));

        if (!$validateDate->isValid($_REQUEST['start_day'])) {
            $errors['start_day'] = $kga['dict']['TimeDateInputError'];
        }

        if (!$validateTime->isValid($_REQUEST['start_time'])) {
            $_REQUEST['start_time'] = $_REQUEST['start_time'] . ':00';
            if (!$validateTime->isValid($_REQUEST['start_time'])) {
                $errors['start_time'] = $kga['dict']['TimeDateInputError'];
            }
        }

        if ($_REQUEST['end_day'] !== '' && !$validateDate->isValid($_REQUEST['end_day'])) {
            $errors['end_day'] = $kga['dict']['TimeDateInputError'];
        }

        if ($_REQUEST['end_time'] !== '' && !$validateTime->isValid($_REQUEST['end_time'])) {
            $_REQUEST['end_time'] = $_REQUEST['end_time'] . ':00';
            if (!$validateTime->isValid($_REQUEST['end_time'])) {
                $errors['end_time'] = $kga['dict']['TimeDateInputError'];
            }
        }

        if (!is_numeric($data['activity_id'])) {
            $errors['activity_id'] = $kga['dict']['errorMessages']['noActivitySelected'];
        }

        if (!is_numeric($data['project_id'])) {
            $errors['project_id'] = $kga['dict']['errorMessages']['noProjectSelected'];
        }

        if (count($errors) > 0) {
            echo json_encode(array('errors' => $errors));

            return;
        }

        $edit_in_day  = Zend_Locale_Format::getDate($_REQUEST['start_day'],
                                                    array('date_format' => 'dd.MM.yyyy'));
        $edit_in_time = Zend_Locale_Format::getTime($_REQUEST['start_time'],
                                                    array('date_format' => 'HH:mm:ss'));

        $edit_in = array_merge($edit_in_day, $edit_in_time);

        $inDate = new Zend_Date($edit_in);

        if ($_REQUEST['end_day'] != '' || $_REQUEST['end_time'] != '') {
            $edit_out_day  = Zend_Locale_Format::getDate($_REQUEST['end_day'],
                                                         array('date_format' => 'dd.MM.yyyy'));
            $edit_out_time = Zend_Locale_Format::getTime($_REQUEST['end_time'],
                                                         array('date_format' => 'HH:mm:ss'));

            $edit_out = array_merge($edit_out_day, $edit_out_time);

            $outDate = new Zend_Date($edit_out);
        }
        else {
            $outDate = null;
        }

        $data['start'] = $inDate->getTimestamp();

        if ($outDate != null) {
            $data['end']      = $outDate->getTimestamp();
            $data['duration'] = $data['end'] - $data['start'];
        }

        if ($id) { // existing entry
            if (!timesheetAccessAllowed($data, $action, $errors)) {
                echo json_encode(array('errors' => $errors));
                break;
            }

            // TIME RIGHT - EDIT ENTRY
            Logger::logfile('timeEntry_edit: ' . $id);
            $database->timeEntry_edit($id, $data);
        }

        else {  // new entry
            $database->transaction_begin();

            if (is_array($_REQUEST['user_id'])) {
                foreach ($_REQUEST['user_id'] as $userID) {
                    $data['user_id'] = $userID;

                    if (!timesheetAccessAllowed($data, $action, $errors)) {
                        echo json_encode(array('errors' => $errors));
                        $database->transaction_rollback();
                        break 2;
                    }
                    Logger::logfile('timeEntry_create');
                    $database->timeEntry_create($data);
                }
            }
            $database->transaction_end();
        }

        echo json_encode(array('errors' => $errors));
        break;
}

