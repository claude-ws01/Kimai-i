<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) 2006-2009 Kimai-Development-Team
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
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
$dir_templates   = "templates/core/";
global $kga, $database, $view;
require "../includes/kspi.php";

switch ($axAction) {

    /**
     * Append a new entry to the logfile.
     */
    case 'logfile':
        Logger::logfile("JavaScript: " . $axValue);
        break;

    /**
     * Remember which project and activity the user has selected for
     * the quick recording via the buzzer.
     */
    case 'saveBuzzerPreselection':
        if (array_key_exists('customer', $kga)) {
            return;
        }

        $data = array();
        if (isset($_REQUEST['project'])) {
            $data['last_project'] = $_REQUEST['project'];
        }
        if (isset($_REQUEST['activity'])) {
            $data['last_activity'] = $_REQUEST['activity'];
        }

        $database->user_edit($kga['user']['user_id'], $data);
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


        if (array_key_exists('customer', $kga)) {
            $database->pref_replace($pref, 'ui.', $kga['customer']['customer_id']);

            if (!empty($_REQUEST['password'])) {
                $userData['password'] = password_encrypt($_REQUEST['password']);
                $database->customer_edit($kga['customer']['customer_id'], $userData);
            }
        }


        else {
            $database->pref_replace($pref, 'ui.', $kga['user']['user_id']);

            $rate = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['rate']);
            if (is_numeric($rate)) {
                $database->save_rate($kga['user']['user_id'], null, null, $rate);
            }
            else {
                $database->remove_rate($kga['user']['user_id'], null, null);
            }

            if (!empty($_REQUEST['password'])) {
                $userData['password'] = password_encrypt($_REQUEST['password']);
                $database->user_edit($kga['user']['user_id'], $userData);
            }
        }


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
        if (array_key_exists('customer', $kga)) {
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

    /**
     * Return a list of users. Customers are not shown any users. The
     * type of the current user decides which users are shown to him.
     * See get_user_watchable_users.
     */
    case 'reload_users':
        if (array_key_exists('customer', $kga)) {
            $view->users = array();
        }
        else {
            $view->users = $database->get_user_watchable_users($kga['user']);
        }

        echo $view->render("filter/users.php");
        break;

    /**
     * Return a list of customers. A customer can only see himself.
     */
    case 'reload_customers':
        if (array_key_exists('customer', $kga)) {
            $view->customers = array($database->customer_get_data($kga['customer']['customer_id']));
        }
        else {
            $view->customers = $database->get_customers(any_get_group_ids());
        }

        $view->show_customer_edit_button = coreObjectActionAllowed('customer', 'edit');

        echo $view->render("filter/customers.php");
        break;

    /**
     * Return a list of projects. Customers are only shown their projects.
     */
    case 'reload_projects':
        if (array_key_exists('customer', $kga)) {
            $view->projects = $database->get_projects_by_customer(($kga['customer']['customer_id']));
        }
        else {
            $view->projects = $database->get_projects(any_get_group_ids());
        }

        $view->show_project_edit_button = coreObjectActionAllowed('project', 'edit');

        echo $view->render("filter/projects.php");
        break;

    /**
     * Return a list of activities. Customers are only shown activities which are
     * used for them. If a project is set as filter via the project parameter
     * only activities for that project are shown.
     */
    case 'reload_activities':
        if (array_key_exists('customer', $kga)) {
            $view->activities = $database->get_activities_by_customer($kga['customer']['customer_id']);
        }
        else {
            if (isset($_REQUEST['project'])) {
                $view->activities = $database->get_activities_by_project($_REQUEST['project'], any_get_group_ids());
            }
            else {
                $view->activities = $database->get_activities(any_get_group_ids());
            }
        }

        $view->show_activity_edit_button = coreObjectActionAllowed('activity', 'edit');

        echo $view->render("filter/activities.php");
        break;

    /**
     * Add a new customer, project or activity. This is a core function as it's
     * used at least by the admin panel and the timesheet extension.
     */
    case 'add_edit_CustomerProjectActivity':
        switch ($axValue) {
            /**
             * add or edit a customer
             */
            case "customer":
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
                if (isset($_REQUEST['password']) && $_REQUEST['password'] != "") {
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
                    $errorMessages['name'] = $kga['lang']['errorMessages']['userWithSameName'];
                }

                if (count($_REQUEST['customer_groups']) == 0) {
                    $errorMessages['customer_groups'] = $kga['lang']['atLeastOneGroup'];
                }

                if (!checkGroupedObjectPermission('customer', $id ? 'edit' : 'add', $oldGroups, $_REQUEST['customer_groups'])) {
                    $errorMessages[''] = $kga['lang']['errorMessages']['permissionDenied'];
                }


                if (count($errorMessages) == 0) {

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
            case "project":
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

                $oldGroups = array();
                if ($id) {
                    $oldGroups = $database->project_get_groupIDs($id);
                }

                // validate data
                $errorMessages = array();

                if (count($_REQUEST['projectGroups']) == 0) {
                    $errorMessages['projectGroups'] = $kga['lang']['atLeastOneGroup'];
                }

                if (!checkGroupedObjectPermission('project', $id ? 'edit' : 'add', $oldGroups, $_REQUEST['projectGroups'])) {
                    $errorMessages[''] = $kga['lang']['errorMessages']['permissionDenied'];
                }

                if (empty($errorMessages)) {
                    if (!$database->transactionBegin()) {
                        $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                    }

                    // add or update the project
                    if (empty($errorMessages)) {
                        if (!$id) {
                            if (!$id = $database->project_create($data)) {
                                $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                            }
                        }
                        else {
                            if (!$database->project_edit($id, $data)) {
                                $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                            }
                        }
                    }


                    if (empty($errorMessages) && isset($_REQUEST['projectGroups'])) {
                        if (!$database->assign_projectToGroups($id, $_REQUEST['projectGroups'])) {
                            $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                        }
                    }

                    if (empty($errorMessages) && isset($_REQUEST['assignedActivities'])) {
                        if (!$database->assignProjectToActivitiesForGroup(
                            $id, array_values($_REQUEST['assignedActivities']), any_get_group_ids())
                        ) {
                            $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                        }

                        if (empty($errorMessages)) {

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
                                        $data[$key] = "null";
                                    }
                                }

                                if (!$database->project_activity_edit($id, $activityID, $data)) {
                                    $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                                    break;
                                }
                            }
                        }
                    }

                    if (empty($errorMessages) && !$database->transactionEnd()) {
                        $errorMessages[''] = $kga['lang']['errorMessages']['internalError'];
                    }

                    if (!empty($errorMessages)) {
                        $database->transactionRollback();
                    }
                }

                header('Content-Type: application/json;charset=utf-8');
                echo json_encode(array('errors' => $errorMessages));
                break;

            /**
             * add or edit a activity
             */
            case "activity":
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

                if (count($_REQUEST['activityGroups']) == 0) {
                    $errorMessages['activityGroups'] = $kga['lang']['atLeastOneGroup'];
                }

                if (!checkGroupedObjectPermission('activity', $id ? 'edit' : 'add', $oldGroups, $_REQUEST['activityGroups'])) {
                    $errorMessages[''] = $kga['lang']['errorMessages']['permissionDenied'];
                }

                if (count($errorMessages) == 0) {
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
                        $database->assignActivityToProjectsForGroup($id, $_REQUEST['projects'], any_get_group_ids());
                    }
                    else {
                        $database->assignActivityToProjectsForGroup($id, array(), any_get_group_ids());
                    }
                }

                header('Content-Type: application/json;charset=utf-8');
                echo json_encode(array(
                                     'errors' => $errorMessages));
                break;
        }
        break;

}
