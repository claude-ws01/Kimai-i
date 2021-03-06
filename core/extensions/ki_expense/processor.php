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

// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates/';

global $database, $kga, $view;


global $axAction, $axValue, $id, $timeframe, $in, $out;
require('../../includes/kspi.php');
include('private_db_layer_mysql.php');


switch ($axAction) {

    // ===========================================
    // = Load expense data from DB and return it =
    // ===========================================
    case 'reload_exp':
        $filters = explode('|', $axValue);
        if ($filters[0] === '') {
            $filterUsers = array();
        }
        else {
            $filterUsers = explode(':', $filters[0]);
        }

        $filterCustomers = array_map(function ($customer) {
            return $customer['customer_id'];
        }, $database->customers_get($kga['who']['groups']));
        if ($filters[1] !== '') {
            $filterCustomers = array_intersect($filterCustomers, explode(':', $filters[1]));
        }

        $filterProjects = array_map(function ($project) {
            return $project['project_id'];
        }, $database->get_projects($kga['who']['groups']));
        if ($filters[2] !== '') {
            $filterProjects = array_intersect($filterProjects, explode(':', $filters[2]));
        }

        // if no userfilter is set, set it to current user
        if (is_customer()) {
            $filterCustomers = array($kga['who']['id']);
        }
        elseif (count($filterUsers) === 0) {
            $filterUsers[] = $kga['who']['id'];
        }

        $view->expenses = get_expenses($in, $out, $filterUsers, $filterCustomers, $filterProjects, 1);
        $view->total    = Format::formatCurrency(array_reduce($view->expenses, function ($sum, $expense) {
            return $sum + $expense['multiplier'] * $expense['value'];
        }, 0));


        $ann                    = expenses_by_user($in, $out, $filterUsers, $filterCustomers, $filterProjects);
        $ann                    = Format::formatCurrency($ann);
        $view->user_annotations = $ann;

        // TODO: function for loops or convert it in template with new function
        $ann                        = expenses_by_customer($in, $out, $filterUsers, $filterCustomers, $filterProjects);
        $ann                        = Format::formatCurrency($ann);
        $view->customer_annotations = $ann;

        $ann                       = expenses_by_project($in, $out, $filterUsers, $filterCustomers, $filterProjects);
        $ann                       = Format::formatCurrency($ann);
        $view->project_annotations = $ann;

        $view->activity_annotations = array();

        $view->hideComments = true;
        if (is_user()) {
            $view->hideComments = (int)$kga['pref']['show_comments_by_default'] !== 1;
        }

        echo $view->render('expenses.php');
        break;

    // =======================================
    // = Erase expense entry via quickdelete =
    // =======================================
    case 'quickdelete':
        $errors = array();

        $data = expense_get($id);

        $database->xpe_access_allowed($data, 'delete', $errors);

        if (count($errors) === 0) {
            expense_delete($id);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errors));
        break;

    // =============================
    // = add / edit expense record =
    // =============================
    case 'add_edit_record':
        header('Content-Type: application/json;charset=utf-8');
        $errors = array();

        // determine action for permission check
        $action = 'add';
        if ($id) {
            $action = 'edit';
        }
        if (isset($_REQUEST['erase'])) {
            $action = 'delete';
        }

        if ($id) {
            $data = expense_get($id);

            // check if editing or deleting with the old values would be allowed
            if (!$database->xpe_access_allowed($data, $action, $errors)) {
                echo json_encode(array('errors' => $errors));
                break;
            }
        }

        // delete now because next steps don't need to be taken for deleted entries
        if (isset($_REQUEST['erase'])) {
            expense_delete($id);
            echo json_encode(array('errors' => $errors));
            break;
        }

        // get new data
        $data['project_id']   = array_key_exists('project_id', $_REQUEST) ? $_REQUEST['project_id'] : '';
        $data['description']  = $_REQUEST['description'];
        $data['comment']      = $_REQUEST['comment'];
        $data['comment_type'] = $_REQUEST['comment_type'];
        $data['refundable']   = getRequestBool('refundable');
        $data['multiplier']   = getRequestDecimal($_REQUEST['multiplier']);
        $data['value']        = getRequestDecimal($_REQUEST['edit_value']);
        $data['user_id']      = $kga['who']['id'];

        // parse new day and time
        $edit_day  = Format::expand_date_shortcut($_REQUEST['edit_day']);
        $edit_time = Format::expand_time_shortcut($_REQUEST['edit_time']);

        // validate day and time
        $new = "${edit_day}-${edit_time}";
        if (!Format::check_time_format($new)) {
            $errors[''] = $kga['dict']['timeDateInputError'];
            break;
        }

        // convert to internal time format
        $new_time          = convert_time_strings($new, $new);
        $data['timestamp'] = $new_time['in'];

        if (!is_numeric($data['project_id'])) {
            $errors['project_id'] = $kga['dict']['errorMessages']['noProjectSelected'];
        }

        if (!is_numeric($data['multiplier']) || $data['multiplier'] <= 0) {
            $errors['multiplier'] = $kga['dict']['errorMessages']['multiplierNegative'];
        }

        $database->xpe_access_allowed($data, $action, $errors);

        if (count($errors) > 0) {
            echo json_encode(array('errors' => $errors));
            break;
        }

        if ($id) {
            expense_edit($id, $data);
        }
        else {
            expense_create($kga['who']['id'], $data);
        }


        echo json_encode(array('errors' => $errors));
        break;

}
