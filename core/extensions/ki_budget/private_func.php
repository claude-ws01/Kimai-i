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

include('../ki_expense/private_db_layer_mysql.php');


/*
 * Sum up expenses for the project.
 */
function calculate_expenses_sum($projectId)
{
    $expenseSum = 0;
    $expenses   = get_expenses(0, time(), null, null, array($projectId));

    foreach ($expenses as $expense) {
        $expenseSum += $expense['value'];
    }

    return $expenseSum;
}

/*
 * Create an array of arrays which hold the size of the pie chart elements
 * for every projects.
 * The first element in the inner arrays represents the unused budget costs,
 * the second element in the inner arrays represents the expense costs,
 * the third and all other elements in the inner arrays represents the
 * costs for individual activities.
 *
 * An visual example for two projects with the ID 2 and 5:
 * $array = {
 *   2 => array (budget left , expenses cost, activity1, activity2 ),
 *   5 => array (budget left , expenses cost, activity1, activity2 ),
 * };
 *
 * @param array $projects       IDs of all projects to include in the plot data
 * @param array $usedActivities array of all used activities (each as an array of its data)
 *
 * @return array containing arrays for every project which hold the size of the pie chart elements
 *
 */
function budget_plot_data($projects, $projectsFilter, $activitiesFilter, &$expensesOccured)
{
    global $database, $kga;

    $wages           = array();
    $expensesOccured = false;

    $billableLangString     = $kga['dict']['billable'];
    $timebillableLangString = $kga['dict']['time_billable'];

    /*
     * sum up expenses
     */
    foreach ($projects as $project) {
        if (is_array($projectsFilter) && !empty($projectsFilter)) {
            if (!in_array($project['project_id'], $projectsFilter)) {
                continue;
            }
        }

        $projectID = $project['project_id'];
        // in "activity 0" we will track the available budget, while in the project array directly,
        // we will track the total budget for the project
        $wages[$projectID][0]['budget']             = $project['budget'];
        $wages[$projectID][0]['approved']           = $project['approved'];
        $wages[$projectID]['budget']                = $project['budget'];
        $wages[$projectID]['approved']              = $project['approved'];
        $wages[$projectID]['billable_total']        = 0;
        $wages[$projectID]['total']                 = 0;
        $wages[$projectID][$timebillableLangString] = 0;

        $expenses = calculate_expenses_sum($project['project_id']);
        if ($expenses > 0) {
            $wages[$projectID][0]['expenses'] = $expenses;
        }

        if ($expenses > 0) {
            $expensesOccured = true;
        }

        if ($wages[$projectID][0]['budget'] < 0) {
            //Costs over budget, set remaining budget to 0.
            $wages[$projectID][0]['budget']   = 0;
            $wages[$projectID][0]['exceeded'] = true;
        }

        $projectActivities = $database->get_activities_by_project($projectID);
        foreach ($projectActivities as $activity) {
            if (is_array($activitiesFilter) && !empty($activitiesFilter)) {
                if (!in_array($activity['activity_id'], $activitiesFilter)) {
                    continue;
                }
            }
            $wages[$projectID][$activity['activity_id']] = array('name' => $activity['name'], 'budget' => 0, 'budget_total' => 0, 'approved' => 0, 'approved_total' => 0, 'total' => 0);
            if (!isset($activity['budget']) || $activity['budget'] <= 0) {
                continue;
            }
            $wages[$projectID][$activity['activity_id']]['budget']       = $activity['budget'];
            $wages[$projectID][$activity['activity_id']]['budget_total'] = $activity['budget'];
            // this budget shall not be added, otherwise we have the project budget in all activities
            // so they would be doubled.
            //  	$wages[$projectID][$activity['evt_ID']]['budget_total'] += $project['pct_budget'];
            //  	$wages[$projectID][$activity['evt_ID']]['approved_total'] = $project['pct_approved'];
            $wages[$projectID][$activity['activity_id']]['approved_total'] += $activity['approved'];
            $wages[$projectID][$activity['activity_id']]['approved'] = $activity['approved'];
            $wages[$projectID][$activity['activity_id']]['total']    = 0;
            // add to the project budget
            $wages[$projectID][0]['budget'] += $activity['budget'];
            $wages[$projectID][0]['approved'] += $activity['approved'];
            // add to the total budget
            $wages[$projectID]['budget'] += $activity['budget'];
            $wages[$projectID]['approved'] += $activity['approved'];
        }
    }
    /*
     * sum up wages for every project and every activity
     */
    foreach ($projects as $project) {
        $projectId  = $project['project_id'];
        $timesheets = $database->get_timesheet(0, time(), null, null, array($projectId));
        foreach ($timesheets as $timesheet) {
            $projectID = $projectId;
            if (isset($wages[$projectID][$timesheet['activity_id']]) && is_array($wages[$projectID][$timesheet['activity_id']])) {
                $tmpCost = $timesheet['wage_decimal'] * $timesheet['billable'] / 100;
                if ($tmpCost <= 0 && ($timesheet['wage_decimal'] - $tmpCost) <= 0) {
                    continue;
                }

                // decrease budget by "already used up" amount
                $wages[$projectID][$timesheet['activity_id']]['budget_total'] += $timesheet['budget'];
                $wages[$projectID][$timesheet['activity_id']]['budget'] -= $timesheet['wage_decimal'];
                $wages[$projectID][$timesheet['activity_id']]['budget'] += $timesheet['budget'];
                $wages[$projectID][$timesheet['activity_id']]['approved'] += $timesheet['approved'];
                $wages[$projectID][$timesheet['activity_id']]['approved_total'] += $timesheet['approved'];
                $wages[$projectID][$timesheet['activity_id']]['approved'] -= $tmpCost;
                $wages[$projectID][$timesheet['activity_id']]['total'] += $timesheet['wage_decimal'];
                // decrease budget by "already used up" amount also for the total budget for the project
                $wages[$projectID][0]['budget'] -= $timesheet['wage_decimal'];
                $wages[$projectID][0]['approved'] -= $tmpCost;
                $wages[$projectID][0]['budget'] += $timesheet['budget'];
                $wages[$projectID][0]['approved'] += $timesheet['approved'];

                if ($tmpCost > 0) {
                    $user_string = $timesheet['username'] . ' ' . $billableLangString;

                    if (!isset($wages[$projectID][0][$user_string])) {
                        $wages[$projectID][0][$timesheet['username'] . ' ' . $billableLangString] = 0;
                    }

                    if (isset($wages[$projectID][0][$user_string])) {
                        $wages[$projectID][0][$timesheet['username'] . ' ' . $billableLangString] += $tmpCost;
                    }
                    else {
                        $wages[$projectID][0][$timesheet['username'] . ' ' . $billableLangString] = $tmpCost;
                    }

                    if (isset($wages[$projectID][$timesheet['activity_id']][$billableLangString])) {
                        $wages[$projectID][$timesheet['activity_id']][$billableLangString] += $tmpCost;
                    }
                    else {
                        $wages[$projectID][$timesheet['activity_id']][$billableLangString] = $tmpCost;
                    }
                }
                if ($timesheet['wage_decimal'] - $tmpCost > 0) {
                    if (!isset($wages[$projectID][0][$timesheet['username']])) {
                        $wages[$projectID][0][$timesheet['username']] = 0;
                    }

                    $wages[$projectID][0][$timesheet['username']] += $timesheet['wage_decimal'] - $tmpCost;

                    if (!isset($wages[$projectID][$timesheet['activity_id']][$timesheet['username']])) {
                        $wages[$projectID][$timesheet['activity_id']][$timesheet['username']] = 0;
                    }

                    $wages[$projectID][$timesheet['activity_id']][$timesheet['username']] += $timesheet['wage_decimal'] - $tmpCost;
                }
                // add to the total budget
                $wages[$projectID]['budget'] += $timesheet['budget'];
                $wages[$projectID]['approved'] += $timesheet['approved'];
                $wages[$projectID]['billable_total'] += $tmpCost;
                $wages[$projectID]['total'] += $timesheet['wage_decimal'];
                $wages[$projectID][$timebillableLangString] += $tmpCost;
                // mark entries which are over budget
                if ($wages[$projectID][$timesheet['activity_id']]['budget'] < 0) {
                    $wages[$projectID][$timesheet['activity_id']]['budget']   = 0;
                    $wages[$projectID][$timesheet['activity_id']]['exceeded'] = true;
                }
                if ($wages[$projectID][$timesheet['activity_id']]['approved'] < 0) {
                    $wages[$projectID][$timesheet['activity_id']]['approved']          = 0;
                    $wages[$projectID][$timesheet['activity_id']]['approved_exceeded'] = true;
                }
            }
        }

        if (!isset($wages[$projectId])) {
            continue;
        }

        //cleanup: don't show charts without any data
        foreach ($wages[$projectId] as $activityId => $entry) {
            if ((int)$activityId === 0) {
                continue;
            }
            if (!isset($entry['total']) || $entry['total'] === null) {
                unset($wages[$projectId][$activityId]);
            }
        }

        if ($wages[$projectId][0]['budget'] < 0) {
            //Costs over budget, set remaining budget to 0.
            $wages[$projectId][0]['budget']   = 0;
            $wages[$projectId][0]['exceeded'] = true;
        }
        if ($wages[$projectId][0]['approved'] < 0) {
            //Costs over budget approved, set remaining approved to 0.
            $wages[$projectId][0]['approved']          = 0;
            $wages[$projectId][0]['approved_exceeded'] = true;
        }
    }

    return $wages;
}

