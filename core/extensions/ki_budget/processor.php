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
global $view, $database, $kga;

global $axAction, $axValue, $id, $timeframe, $in, $out;
require('../../includes/kspi.php');
require('private_func.php');

$filters = explode('|', $axValue);

if ($filters[0] === '') {
    $filterUsers = array();
}
else {
    $filterUsers = explode(':', $filters[0]);
}

$filterCustomers = array_map(
    function ($customer) {
        return $customer['customer_id'];
    },
    $database->customers_get($kga['who']['groups']));
if (isset($filters[1]) && $filters[1] !== '') {
    $filterCustomers = array_intersect($filterCustomers, explode(':', $filters[1]));
}

$filterProjects = array_map(function ($project) {
    return $project['project_id'];
}, $database->get_projects($kga['who']['groups']));
if (isset($filters[2]) && $filters[2] !== '') {
    $filterProjects = array_intersect($filterProjects, explode(':', $filters[2]));
}

$filterActivities = array_map(function ($activity) {
    return $activity['activity_id'];
}, $database->get_activities($kga['who']['groups']));
if (isset($filters[3]) && $filters[3] !== '') {
    $filterActivities = array_intersect($filterActivities, explode(':', $filters[3]));
}

// if no userfilter is set, set it to current user
if (is_customer()) {
    $filterCustomers = array($kga['who']['id']);
}
elseif (count($filterUsers) === 0) {
    $filterUsers[] = $kga['who']['id'];
}

// ==================
// = handle request =
// ==================
switch ($axAction) {
    // ===========================================
    // = Filter the charts by projects and activities =
    // ===========================================
    case 'reload':
        // track which activities we want to see, so we can exclude them when we create the plot
        $activitiesFilter   = false;
        $projectsFilter     = false;
        $projectsSelected   = array();
        $activitiesSelected = array();

        if (is_array($filterProjects) && count($filterProjects) > 0) {
            $projectsFilter   = $filterProjects;
            $projectsSelected = $projectsFilter;
        }
        if (is_array($filterActivities) && count($filterActivities) > 0) {
            $activitiesFilter   = $filterActivities;
            $activitiesSelected = $activitiesFilter;
        }
        // Get all project for the logged in customer or the current user.
        if (is_customer()) {
            $projects   = $database->get_projects_by_customer(($kga['who']['id']));
            $activities = $database->get_activities();
        }
        else {
            $customers = $database->customers_get($kga['who']['groups']);
            if (is_array($filterCustomers) && count($filterCustomers) > 0) {
                $projects = array();
                foreach ($filterCustomers as $customerId) {
                    $projects = array_merge($database->get_projects_by_customer($customerId), $projects);
                }
            }
            else {
                $projects = $database->get_projects($kga['who']['groups']);
            }
            $activities = $database->get_activities($kga['who']['groups']);
        }
        if (is_array($projects)) {
            foreach ((array)$projects as $index => $project) {
                if ($projectsFilter === false) {
                    $projectsSelected[] = $project['project_id'];
                }
                $projects[ $index ]['activities'] = $database->get_activities_by_project($project['project_id']);

                foreach ($projects[ $index ]['activities'] as $index2 => $activity) {
                    if ($activitiesFilter === false) {
                        $activitiesSelected[] = $activity['activity_id'];
                    }
                }
            }
        }
        $expensesOccured = false;
        // If there are any projects create the plot data.
        if (count($projects) > 0) {
            $arr_plotdata                  = budget_plot_data($projects, $projectsSelected, $activitiesSelected, $expensesOccured);
            $view->javascript_arr_plotdata = json_encode($arr_plotdata);
            $view->arr_plotdata            = $arr_plotdata;
            $view->projects                = $projects;
            $view->activities              = $activities;
        }
        else {
            $view->projects = array();
        }
        $view->projects_selected   = $projectsSelected;
        $view->activities_selected = $activitiesSelected;

        $chartColors       = array('#efefef', '#4bb2c5', '#EAA228', '#c5b47f', '#579575', '#839557', '#958c12', '#953579', '#4b5de4', '#d8b83f', '#ff5800', '#0085cc');
        $view->chartColors = json_encode($chartColors);
        // Create the keys which explain to the user which color means what for the project based charts
        $keys   = array();
        $keys[] = array('color' => $chartColors[0], 'name' => $kga['dict']['ext_budget']['unusedBudget']);
        if ($expensesOccured) {
            $keys[] = array('color' => $chartColors[1], 'name' => $kga['dict']['export_extension']['expenses']);
        }
        /*for ($i = 0; $i < count($usedEvents); $i++) {
            $keys[] = array('color' => $chartColors[($i + 2) % (count($chartColors) - 1)], 'name' => $usedEvents[$i]['evt_name']);
        }*/
        // the activity based charts only need numbers
        $view->arr_keys = $keys;
        echo $view->render('charts.php');

        break;
}
