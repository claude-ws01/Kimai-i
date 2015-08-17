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

    $billable_str     = $kga['dict']['billable'];
    $timeBillable_str = $kga['dict']['time_billable'];

    /*
     * sum up expenses
     */
    foreach ($projects as $project) {
        if (is_array($projectsFilter) && !empty($projectsFilter)
            && !in_array($project['project_id'], $projectsFilter, false)
        ): continue; endif;

        $pId = $project['project_id'];
        // in "activity 0" we will track the available budget, while in the project array directly,
        // we will track the total budget for the project
        $wages[$pId][0]['budget']             = $project['budget'];
        $wages[$pId][0]['approved']           = $project['approved'];
        $wages[$pId]['budget']                = $project['budget'];
        $wages[$pId]['approved']              = $project['approved'];
        $wages[$pId]['billable_total']        = 0;
        $wages[$pId]['total']                 = 0;
        $wages[$pId][$timeBillable_str] = 0;

        $expenses = calculate_expenses_sum($project['project_id']);
        if ($expenses > 0) {
            $wages[$pId][0]['expenses'] = $expenses;
        }

        $expensesOccured = $expenses > 0;

        if ($wages[$pId][0]['budget'] < 0) {
            //Costs over budget, set remaining budget to 0.
            $wages[$pId][0]['budget']   = 0;
            $wages[$pId][0]['exceeded'] = true;
        }

        $projectActivities = $database->get_activities_by_project($pId);
        foreach ($projectActivities as $activity) {
            if (is_array($activitiesFilter) && !empty($activitiesFilter)
                && !in_array($activity['activity_id'], $activitiesFilter, false)
            ) {
                continue;
            }
            $wages[$pId][$activity['activity_id']] =
                array('name'           => $activity['name'],
                      'budget'         => 0,
                      'budget_total'   => 0,
                      'approved'       => 0,
                      'approved_total' => 0,
                      'total'          => 0);

            if (!isset($activity['budget']) || $activity['budget'] <= 0): continue; endif;

            $wages[$pId][$activity['activity_id']]['budget']       = $activity['budget'];
            $wages[$pId][$activity['activity_id']]['budget_total'] = $activity['budget'];

            // this budget shall not be added, otherwise we have the project budget in all activities
            // so they would be doubled.
            //  	$wages[$pId][$activity['evt_ID']]['budget_total'] += $project['pct_budget'];
            //  	$wages[$pId][$activity['evt_ID']]['approved_total'] = $project['pct_approved'];

            $wages[$pId][$activity['activity_id']]['approved_total'] += $activity['approved'];
            $wages[$pId][$activity['activity_id']]['approved'] = $activity['approved'];
            $wages[$pId][$activity['activity_id']]['total']    = 0;

            // add to the project budget
            $wages[$pId][0]['budget'] += $activity['budget'];
            $wages[$pId][0]['approved'] += $activity['approved'];

            // add to the total budget
            $wages[$pId]['budget'] += $activity['budget'];
            $wages[$pId]['approved'] += $activity['approved'];
        }
    }
    /*
     * sum up wages for every project and every activity
     */
    foreach ($projects as $project) {
        $pId        = $project['project_id'];
        $timesheets = $database->get_timesheet(0, time(), null, null, array($pId));
        foreach ($timesheets as $ts) {
            if (isset($wages[$pId][$ts['activity_id']]) && is_array($wages[$pId][$ts['activity_id']])) {
                $tmpCost = $ts['wage_decimal'] * $ts['billable'] / 100;
                if ($tmpCost <= 0 && ($ts['wage_decimal'] - $tmpCost) <= 0) {
                    continue;
                }

                // decrease budget by "already used up" amount
                $wages[$pId][$ts['activity_id']]['budget_total'] += $ts['budget'];
                $wages[$pId][$ts['activity_id']]['budget'] -= $ts['wage_decimal'];
                $wages[$pId][$ts['activity_id']]['budget'] += $ts['budget'];
                $wages[$pId][$ts['activity_id']]['approved'] += $ts['approved'];
                $wages[$pId][$ts['activity_id']]['approved_total'] += $ts['approved'];
                $wages[$pId][$ts['activity_id']]['approved'] -= $tmpCost;
                $wages[$pId][$ts['activity_id']]['total'] += $ts['wage_decimal'];

                // decrease budget by "already used up" amount also for the total budget for the project
                $wages[$pId][0]['budget'] -= $ts['wage_decimal'];
                $wages[$pId][0]['approved'] -= $tmpCost;
                $wages[$pId][0]['budget'] += $ts['budget'];
                $wages[$pId][0]['approved'] += $ts['approved'];

                if ($tmpCost > 0) {
                    $user_string = $ts['username'] . ' ' . $billable_str;

                    if (!isset($wages[$pId][0][$user_string])) {
                        $wages[$pId][0][$user_string] = 0;
                    }

                    if (isset($wages[$pId][0][$user_string])) {
                        $wages[$pId][0][$user_string] += $tmpCost;
                    }
                    else {
                        $wages[$pId][0][$user_string] = $tmpCost;
                    }

                    if (isset($wages[$pId][$ts['activity_id']][$billable_str])) {
                        $wages[$pId][$ts['activity_id']][$billable_str] += $tmpCost;
                    }
                    else {
                        $wages[$pId][$ts['activity_id']][$billable_str] = $tmpCost;
                    }
                }
                if ($ts['wage_decimal'] - $tmpCost > 0) {
                    if (!isset($wages[$pId][0][$ts['username']])) {
                        $wages[$pId][0][$ts['username']] = 0;
                    }

                    $wages[$pId][0][$ts['username']] += $ts['wage_decimal'] - $tmpCost;

                    if (!isset($wages[$pId][$ts['activity_id']][$ts['username']])) {
                        $wages[$pId][$ts['activity_id']][$ts['username']] = 0;
                    }

                    $wages[$pId][$ts['activity_id']][$ts['username']] += $ts['wage_decimal'] - $tmpCost;
                }
                // add to the total budget
                $wages[$pId]['budget'] += $ts['budget'];
                $wages[$pId]['approved'] += $ts['approved'];
                $wages[$pId]['billable_total'] += $tmpCost;
                $wages[$pId]['total'] += $ts['wage_decimal'];
                $wages[$pId][$timeBillable_str] += $tmpCost;
                // mark entries which are over budget
                if ($wages[$pId][$ts['activity_id']]['budget'] < 0) {
                    $wages[$pId][$ts['activity_id']]['budget']   = 0;
                    $wages[$pId][$ts['activity_id']]['exceeded'] = true;
                }
                if ($wages[$pId][$ts['activity_id']]['approved'] < 0) {
                    $wages[$pId][$ts['activity_id']]['approved']          = 0;
                    $wages[$pId][$ts['activity_id']]['approved_exceeded'] = true;
                }
            }
        }

        if (!isset($wages[$pId])) {
            continue;
        }

        //cleanup: don't show charts without any data
        foreach ($wages[$pId] as $activityId => $entry) {
            if ((int)$activityId === 0) {
                continue;
            }
            if (!isset($entry['total']) || $entry['total'] === null) {
                unset($wages[$pId][$activityId]);
            }
        }

        if ($wages[$pId][0]['budget'] < 0) {
            //Costs over budget, set remaining budget to 0.
            $wages[$pId][0]['budget']   = 0;
            $wages[$pId][0]['exceeded'] = true;
        }
        if ($wages[$pId][0]['approved'] < 0) {
            //Costs over budget approved, set remaining approved to 0.
            $wages[$pId][0]['approved']          = 0;
            $wages[$pId][0]['approved_exceeded'] = true;
        }
    }

    return $wages;
}

