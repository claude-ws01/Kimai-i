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

// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates/';
require('../../includes/kspi.php');
global $database, $kga, $view;

switch ($axAction) {

    case 'add_edit_timeSheetEntry':
        if (array_key_exists('customer', $kga)) {die();}

        // ==============================================
        // = display edit dialog for timesheet record   =
        // ==============================================
        $selected = explode('|', $axValue);

        $view->projects   = makeSelectBox('project', any_get_group_ids());
        $view->activities = makeSelectBox('activity', any_get_group_ids());

        // edit record
        if ($id) {
            $timesheet_entry = $database->timesheet_get_data($id);
            $view->id       = $id;
            $view->location = $timesheet_entry['location'];

            // check if this entry may be edited
            if ($kga['is_user_root']) {
                // nothing more to check
                $dummy = true;
            }
            elseif ((int)$timesheet_entry['user_id'] === (int)$kga['user']['user_id']) {
                // the user's own entry
                if (!$database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__own_entry__edit')) {
                    break;
                }
            }
            else {
                if (count(array_intersect(
                              //CN..original// $database->user_get_group_ids($kga['user']['user_id']),
                              $kga['user']['groups'],
                              $database->user_get_group_ids($timesheet_entry['user_id'], false)
                          )) !== 0
                ) {
                    // same group as the entry's user
                    if (!$database->checkMembershipPermission(
                        $kga['user']['user_id'],
                        $database->user_get_group_ids($timesheet_entry['user_id'], false),
                        'ki_timesheet__other_entry__own_group__edit')
                    ) {
                        break;
                    }
                }
                else {
                    if (!$database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__other_entry__other_group__edit')) {
                        break;
                    }
                }
            }

            // set list of users to what the user may do
            $users = array();
            if ($database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__other_entry__other_group__edit')) {
                $users = makeSelectBox('allUser', any_get_group_ids());
            }
            else {
                if ($database->checkMembershipPermission(
                    $kga['user']['user_id'],
                    //CN..original, groups allready in $kga// $database->user_get_group_ids($kga['user']['user_id']),
                    $kga['user']['groups'],
                    'ki_timesheet__other_entry__own_group__edit')
                ) {
                    $users = makeSelectBox('sameGroupUser', any_get_group_ids());
                    if ($database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__own_entry__edit')) {
                        $users[$kga['user']['user_id']] = $kga['user']['name'];
                    }
                }
            }

            $view->users = $users;
            $view->customer_name = $timesheet_entry['customer_name'];
            $view->ref_code = $timesheet_entry['ref_code'];
            $view->description     = $timesheet_entry['description'];
            $view->comment         = $timesheet_entry['comment'];

            $view->showRate   = $database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__edit_rates');
            $view->rate       = $timesheet_entry['rate'];
            $view->fixed_rate = $timesheet_entry['fixed_rate'];

            $view->cleared = $timesheet_entry['cleared'] != 0;

            $view->user_id = $timesheet_entry['user_id'];

            $view->start_day  = date('d.m.Y', $timesheet_entry['start']);
            $view->start_time = date('H:i', $timesheet_entry['start']);

            if ($timesheet_entry['end'] == 0) {
                $view->end_day  = '';
                $view->end_time = '';
            }
            else {
                $view->end_day  = date('d.m.Y', $timesheet_entry['end']);
                $view->end_time = date('H:i', $timesheet_entry['end']);
            }

            $view->approved = $timesheet_entry['approved'];
            $view->budget   = $timesheet_entry['budget'];

            // preselected
            $view->project_id  = $timesheet_entry['project_id'];
            $view->activity_id = $timesheet_entry['activity_id'];

            $view->comment_type    = $timesheet_entry['comment_type'];
            $view->status_id       = $timesheet_entry['status_id'];
            $view->billable_active = $timesheet_entry['billable'];

            // budget
            $activityBudgets            = $database->get_activity_budget($timesheet_entry['project_id'], $timesheet_entry['activity_id']);
            $activityUsed               = $database->get_budget_used($timesheet_entry['project_id'], $timesheet_entry['activity_id']);
            $view->budget_activity      = round($activityBudgets['budget'], 2);
            $view->approved_activity    = round($activityBudgets['approved'], 2);
            $view->budget_activity_used = $activityUsed;


            if (!isset($view->projects[$timesheet_entry['project_id']])) {
                // add the currently assigned project to the list
                $projectData                                = $database->project_get_data($timesheet_entry['project_id']);
                $customerData                               = $database->customer_get_data($projectData['customer_id']);
                $view->projects[$projectData['project_id']] = $customerData['name'] . ':' . $projectData['name'];
            }

        }
        else {
            // create new record
            //$view->id = 0;

            $view->status_id = $kga['conf']['default_status_id'];


            $users = array();
            if ($database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__other_entry__other_group__add')) {
                $users = makeSelectBox('allUser', any_get_group_ids());
            }
            else {
                if ($database->checkMembershipPermission(
                    $kga['user']['user_id'],
                    //CN..original, groups allready in $kga// $database->user_get_group_ids($kga['user']['user_id']),
                    $kga['user']['groups'],
                    'ki_timesheet__other_entry__own_group__add')
                ) {
                    $users = makeSelectBox('sameGroupUser', any_get_group_ids());
                }
            }
            if ($database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__own_entry__add')) {
                $users[$kga['user']['user_id']] = $kga['user']['name'];
            }

            $view->users = $users;

            $view->start_day = date('d.m.Y');
            $view->end_day   = date('d.m.Y');

            $view->user_id = $kga['user']['user_id'];

            if ($kga['user']['last_record'] != 0 && $kga['conf']['round_timesheet_entries'] !== '') {
                $timeSheetData = $database->timesheet_get_data($kga['user']['last_record']);
                $minutes       = date('i');
                if ($kga['conf']['round_minutes'] < 60) {
                    if ($kga['conf']['round_minutes'] <= 0) {
                        $minutes = 0;
                    }
                    else {
                        while ($minutes % $kga['conf']['round_minutes'] != 0) {
                            if ($minutes >= 60) {
                                $minutes = 0;
                            }
                            else {
                                $minutes++;
                            }
                        }
                    }
                }
                $seconds = date('s');
                if ($kga['conf']['round_seconds'] < 60) {
                    if ($kga['conf']['round_seconds'] <= 0) {
                        $seconds = 0;
                    }
                    else {
                        while ($seconds % $kga['conf']['round_seconds'] != 0) {
                            if ($seconds >= 60) {
                                $seconds = 0;
                            }
                            else {
                                $seconds++;
                            }
                        }
                    }
                }
                $end      = mktime(date('H'), $minutes, $seconds);
                $day      = date('d');
                $dayEntry = date('d', $timeSheetData['end']);

                if ($day == $dayEntry) {
                    $view->start_time = date('H:i', $timeSheetData['end']);
                }
                else {
                    $view->start_time = date('H:i');
                }
                $view->end_time = date('H:i', $end);
            }
            else {
                $view->start_time = date('H:i');
                $view->end_time   = date('H:i');
            }

            $view->showRate   = $database->global_role_allows(any_get_global_role_id(), 'ki_timesheet__edit_rates');
            $view->rate       = $database->get_best_fitting_rate($kga['user']['user_id'], $selected[0], $selected[1]);
            $view->fixed_rate = $database->get_best_fitting_fixed_rate($selected[0], $selected[1]);

            // budget
            $view->budget_activity      = 0;
            $view->approved_activity    = 0;
            $view->budget_activity_used = 0;

            $view->cleared = false;
        }

        $view->status = $kga['status'];
        $view->commentTypes = $commentTypes;

        echo $view->render('floaters/add_edit_timeSheetEntry.php');

        break;

}
