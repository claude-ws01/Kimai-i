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

/**
 * ==================
 * = Core Processor =
 * ==================
 *
 * Called via AJAX from the Kimai user interface. Depending on $axAction
 * actions are performed, e.g. editing preferences or returning a list
 * of customers.
 */

// insert KSPI 
$isCoreProcessor = 1;
$dir_templates   = 'templates/core/';
global $kga, $database, $view;

global $axAction, $axValue, $id, $timeframe, $in, $out;
require '../includes/kspi.php';

switch ($axAction) {

    /**
     * Add a new customer, project or activity. This is a core function as it's
     * used at least by the admin panel and the timesheet extension.
     */
    /**
     * add or edit a customer
     */
    case 'add_edit_customer':
        $data['name']     = $_REQUEST['name'];
        $data['comment']  = $_REQUEST['comment'];
        $data['company']  = $_REQUEST['company'];
        $data['vat_rate'] = $_REQUEST['vat_rate'];
        $data['contact']  = $_REQUEST['contactPerson'];
        $data['timezone'] = $_REQUEST['timezone'];
        $data['street']   = $_REQUEST['street'];
        $data['zipcode']  = $_REQUEST['zipcode'];
        $data['city']     = $_REQUEST['city'];
        $data['phone']    = $_REQUEST['phone'];
        $data['fax']      = $_REQUEST['fax'];
        $data['mobile']   = $_REQUEST['mobile'];
        $data['mail']     = $_REQUEST['mail'];
        $data['homepage'] = $_REQUEST['homepage'];
        $data['visible']  = getRequestBool('visible');
        $data['filter']   = $_REQUEST['customerFilter'];

        // If password field is empty dont overwrite the password.
        if (isset($_REQUEST['password']) && $_REQUEST['password'] !== '') {
            $data['password'] = password_encrypt($_REQUEST['password']);
        }
        if (isset($_REQUEST['no_password']) && $_REQUEST['no_password']) {
            $data['password'] = '';
        }

        $oldGroups = array();
        if ($id) {
            $oldGroups = $database->customer_get_group_ids($id);
        }

        // validate data
        $errorMessages = array();

        if ($database->user_name2id($data['name']) !== false) {
            $errorMessages['name'] = $kga['dict']['errorMessages']['userWithSameName'];
        }

        if (count($_REQUEST['customer_groups']) === 0) {
            $errorMessages['customer_groups'] = $kga['dict']['atLeastOneGroup'];
        }

        if (!$database->core_action_group_allowed('customer', $id ? 'edit' : 'add', $oldGroups, $_REQUEST['customer_groups'])) {
            $errorMessages[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }


        if (count($errorMessages) === 0) {

            // add or update the customer
            if (!$id) {
                $id = $database->customer_create($data);
            }
            else {
                $database->customer_edit($id, $data);
            }

            // set the customer group mappings
            $database->assign_customerToGroups($id, $_REQUEST['customer_groups']);
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array('errors' => $errorMessages));

        break;

    /**
     * add or edit a project
     */
    case 'add_edit_project':
        //CN..no adding here, only edit.
        $data['customer_id'] = $_REQUEST['customer_id'];
        $data['name']        = $_REQUEST['name'];
        $data['comment']     = $_REQUEST['project_comment'];
        $data['visible']     = getRequestBool('visible');
        $data['filter']      = $_REQUEST['project_filter'];

        $data['budget']   = getRequestDecimal($_REQUEST['project_budget']);
        $data['effort']   = getRequestDecimal($_REQUEST['project_effort']);
        $data['approved'] = getRequestDecimal($_REQUEST['project_approved']);
        $data['internal'] = getRequestBool('internal');

        $data['default_rate'] = getRequestDecimal($_REQUEST['default_rate']);
        $data['my_rate']      = getRequestDecimal($_REQUEST['my_rate']);
        $data['fixed_rate']   = getRequestDecimal($_REQUEST['fixed_rate']);

        $oldGroups = $database->project_get_groupIDs($id);

        // VALIDATE //
        $errorMessages = array();

        if (count($_REQUEST['projectGroups']) === 0) {
            $errorMessages['projectGroups'] = $kga['dict']['atLeastOneGroup'];
        }

        if (!$database->core_action_group_allowed('project', 'edit', $oldGroups, $_REQUEST['projectGroups'])) {
            $errorMessages[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        // PROCESS //
        if (empty($errorMessages)
            && !$database->transactionBegin()
        ) {
            $errorMessages[''] = $kga['dict']['errorMessages']['internalError'];
        }

        if (empty($errorMessages)
            && !$database->project_edit($id, $data)) {
                    $errorMessages[''] = $kga['dict']['errorMessages']['internalError'];
        }


        if (empty($errorMessages) && isset($_REQUEST['projectGroups'])
            && !$database->assign_projectToGroups($id, $_REQUEST['projectGroups'])
        ) {
            $errorMessages[''] = $kga['dict']['errorMessages']['internalError'];
        }


        if (empty($errorMessages)
            && isset($_REQUEST['assignedActivities'])
            && !$database->assignProjectToActivitiesForGroup(
                $id, array_values($_REQUEST['assignedActivities']), $kga['who']['groups'])
        ) {
            $errorMessages[''] = $kga['dict']['errorMessages']['internalError'];
        }


        if (empty($errorMessages)
            && isset($_REQUEST['assignedActivities'])
            && is_array($_REQUEST['assignedActivities'])
        ) {

            foreach ($_REQUEST['assignedActivities'] as $index => $activityID) {

                if ($activityID <= 0) {
                    continue;
                }

                $data = array();
                foreach (array('budget', 'effort', 'approved') as $key) {
                    $value = getRequestDecimal($_REQUEST[$key][$index]);
                    if ($value !== null) {
                        $data[$key] = max(0, $value);
                    }
                    else {
                        $data[$key] = 'null';
                    }
                }

                if (!$database->project_activity_edit($id, $activityID, $data)) {
                    $errorMessages[''] = $kga['dict']['errorMessages']['internalError'];
                    break;
                }
            }
        }


        if (empty($errorMessages) && !$database->transactionEnd()) {
            $errorMessages[''] = $kga['dict']['errorMessages']['internalError'];
        }


        if (!empty($errorMessages)) {
            $database->transactionRollback();
        }


        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array('errors' => $errorMessages));
        break;

    /**
     * add or edit a activity
     */
    case 'add_edit_activity':
        $data['name']         = $_REQUEST['name'];
        $data['comment']      = $_REQUEST['comment'];
        $data['visible']      = getRequestBool('visible');
        $data['filter']       = $_REQUEST['activityFilter'];
        $data['default_rate'] = getRequestDecimal($_REQUEST['default_rate']);
        $data['my_rate']      = getRequestDecimal($_REQUEST['my_rate']);
        $data['fixed_rate']   = getRequestDecimal($_REQUEST['fixed_rate']);

        $oldGroups = array();
        if ($id) {
            $oldGroups = $database->activity_get_groupIDs($id);
        }

        // validate data
        $errorMessages = array();

        if (count($_REQUEST['activityGroups']) === 0) {
            $errorMessages['activityGroups'] = $kga['dict']['atLeastOneGroup'];
        }

        if (!$database->core_action_group_allowed('activity', $id ? 'edit' : 'add', $oldGroups, $_REQUEST['activityGroups'])) {
            $errorMessages[''] = $kga['dict']['errorMessages']['permissionDenied'];
        }

        if (count($errorMessages) === 0) {
            // add or update the project
            if (!$id) {
                $id = $database->activity_create($data);
            }
            else {
                $database->activity_edit($id, $data);
            }

            // set the activity group and activity project mappings
            if (isset($_REQUEST['activityGroups'])) {
                $database->assign_activityToGroups($id, $_REQUEST['activityGroups']);
            }

            if (isset($_REQUEST['projects'])) {
                $database->assignActivityToProjectsForGroup($id, $_REQUEST['projects'], $kga['who']['groups']);
            }
            else {
                $database->assignActivityToProjectsForGroup($id, array(), $kga['who']['groups']);
            }
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(array(
                             'errors' => $errorMessages));
        break;

    /**
     * Store the user preferences entered in the preferences dialog.
     */
    case 'editPrefs':

        $pref['autoselection']        = getRequestBool('autoselection');
        $pref['flip_project_display'] = getRequestBool('flip_project_display');
        $pref['hide_cleared_entries'] = getRequestBool('hide_cleared_entries');
        $pref['hide_overlap_lines']   = getRequestBool('hide_overlap_lines');
        $pref['language']             = $_REQUEST['language'];
        $pref['no_fading']            = getRequestBool('no_fading');
        $pref['open_after_recorded']  = getRequestBool('open_after_recorded');
        $pref['project_comment_flag'] = getRequestBool('project_comment_flag');
        if (isset($_REQUEST['quickdelete'])) {
            $pref['quickdelete'] = $_REQUEST['quickdelete'];
        }
        $pref['rowlimit']                 = $_REQUEST['rowlimit'];
        $pref['show_comments_by_default'] = getRequestBool('show_comments_by_default');
        $pref['show_ids']                 = getRequestBool('show_ids');
        $pref['show_ref_code']            = getRequestBool('show_ref_code');
        $pref['skin']                     = $_REQUEST['skin'];
        $pref['sublist_annotations']      = $_REQUEST['sublist_annotations'];
        $pref['timezone']                 = $_REQUEST['timezone'];
        $pref['user_list_hidden']         = getRequestBool('user_list_hidden');


        if (is_customer()) {
            $database->pref_replace($pref, 'ui.', $kga['who']['id']);

            if (!empty($_REQUEST['password'])) {
                $userData['password'] = password_encrypt($_REQUEST['password']);
                $database->customer_edit($kga['who']['id'], $userData);
            }
        }


        else {
            $database->pref_replace($pref, 'ui.', $kga['who']['id']);

            $rate = isset($_REQUEST['rate']) ? $_REQUEST['rate'] : '0';
            $rate = str_replace($kga['conf']['decimal_separator'], '.', $rate);
            if (is_numeric($rate)) {
                $database->save_rate($kga['who']['id'], null, null, $rate);
            }
            else {
                $database->remove_rate($kga['who']['id'], null, null);
            }

            if (!empty($_REQUEST['password'])) {
                $userData['password'] = password_encrypt($_REQUEST['password']);
                $database->user_edit($kga['who']['id'], $userData);
            }
        }


        break;

    /**
     * Append a new entry to the logfile.
     */
    case 'logfile':
        Logger::logfile('JavaScript: ' . $axValue);
        break;

    /**
     * Return a list of users. Customers are not shown any users. The
     * type of the current user decides which users are shown to him.
     * See user_watchable_users.
     */
    case 'reload_users':
        if (is_customer()) {
            $view->users = $database->customer_watchable_users($kga['who']['data']);
        }
        else {
            $view->users = $database->user_watchable_users($kga['who']['data']);
        }

        echo $view->render('filter/users.php');
        break;

    /**
     * Return a list of customers. A customer can only see himself.
     */
    case 'reload_customers':
        if (is_customer()) {
            $view->customers = array($database->customer_get_data($kga['who']['id']));
        }
        else {
            $view->customers = $database->customers_get($kga['who']['groups']);
        }

        $view->show_customer_edit_button = $database->user_object_action__allowed('customer', 'edit');

        echo $view->render('filter/customers.php');
        break;

    /**
     * Return a list of projects. Customers are only shown their projects.
     */
    case 'reload_projects':
        if (is_customer()) {
            $view->projects = $database->get_projects_by_customer(($kga['who']['id']));
        }
        else {
            $view->projects = $database->get_projects($kga['who']['groups']);
        }

        $view->show_project_edit_button = $database->user_object_action__allowed('project', 'edit');

        echo $view->render('filter/projects.php');
        break;

    /**
     * Return a list of activities. Customers are only shown activities which are
     * used for them. If a project is set as filter via the project parameter
     * only activities for that project are shown.
     */
    case 'reload_activities':
        if (is_customer()) {
            $view->activities = $database->get_activities_by_customer($kga['who']['id']);
        }
        else {
            if (isset($_REQUEST['project'])) {
                $view->activities = $database->get_activities_by_project($_REQUEST['project'], $kga['who']['groups']);
            }
            else {
                $view->activities = $database->get_activities($kga['who']['groups']);
            }
        }

        $view->show_activity_edit_button = $database->user_object_action__allowed('activity', 'edit');

        echo $view->render('filter/activities.php');
        break;

    /**
     * Remember which project and activity the user has selected for
     * the quick recording via the buzzer.
     */
    case 'saveBuzzerPreselection':
        if (is_customer()) {
            return;
        }

        $data = array();
        if (isset($_REQUEST['project'])) {
            $data['last_project'] = $_REQUEST['project'];
        }
        if (isset($_REQUEST['activity'])) {
            $data['last_activity'] = $_REQUEST['activity'];
        }

        $database->user_edit($kga['who']['id'], $data);
        break;

    /**
     * When the user changes the timeframe it is stored in the database so
     * it can be restored, when the user reloads the page.
     */
    case 'setTimeframe':

        $timeframe = explode('|', $axValue);

        $timeframe_in = explode('-', $timeframe[0]);
        $timeframe_in = (int)mktime(0, 0, 0, $timeframe_in[0], $timeframe_in[1], $timeframe_in[2]);
        if ($timeframe_in < 950000000) {
            $timeframe_in = $in;
        }

        $timeframe_out = explode('-', $timeframe[1]);
        $timeframe_out = (int)mktime(23, 59, 59, $timeframe_out[0], $timeframe_out[1], $timeframe_out[2]);
        if ($timeframe_out < 950000000) {
            $timeframe_out = $out;
        }

        $database->save_timeframe($timeframe_in, $timeframe_out);
        break;

    /**
     * The user started the recording of an activity via the buzzer. If this method
     * is called while another recording is running the first one will be stopped.
     */
    case 'start_record':
        if (is_customer()) {
            die();
        }

        $IDs   = explode('|', $axValue);
        $newID = $database->startRecorder($IDs[0], $IDs[1], $id);
        echo json_encode(array('id' => $newID));
        break;

    /**
     * Stop the running recording.
     */
    case 'stop_record':
        $database->stopRecorder($id);
        break;

}
