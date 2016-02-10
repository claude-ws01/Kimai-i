<?php
/** @var $view Zend_View */

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
 * =============================
 * = Floating Window Generator =
 * =============================
 *
 * Called via AJAX from the Kimaii user interface. Depending on $axAction
 * some HTML will be returned, which will then be shown in a floater.
 */

// insert KSPI
global $database, $kga, $translations, $view;

$isCoreProcessor = 1;
$dir_templates   = 'templates/scripts/'; // folder of the template files

global $axAction, $axValue, $id, $timeframe, $in, $out;
require('../includes/kspi.php');


switch ($axAction) {

    /**
     * Display the credits floater. The copyright will automatically be
     * set from 2006 to the current year.
     */
    case 'credits':

        echo $view->render('floaters/credits.php');
        break;

    /**
     * Display a warning in case the installer is still present.
     */
    case 'securityWarning':
        if ($axValue === 'installer') {
            echo $view->render('floaters/security_warning.php');
        }
        break;

    /**
     * Display the preferences dialog.
     */
    case 'prefs':

        //$skins = array();
        //$langs = array();

        $allSkins = glob(__DIR__ . '/../skins/*', GLOB_ONLYDIR);
        foreach ($allSkins as $skin) {
            $name         = basename($skin);
            $skins[$name] = $name;
        }

        foreach (Translations::langs() as $lang) {
            $langs[$lang] = $lang;
        }

        $view->skins     = $skins;
        $view->langs     = $langs;

        $view->timezones = timezoneList();

        $view->user = $kga['who']['data'];
        $view->rate = null;
        if (is_user()) {
            $view->rate = $database->get_rate($kga['who']['id'], null, null);
        }

        echo $view->render('floaters/preferences.php');
        break;

    /**
     * Display the dialog to add or edit a customer.
     */
    case 'add_edit_customer':
        $oldGroups = array();
        if ($id) {
            $oldGroups = $database->customer_get_group_ids($id);
        }

        if (!$database->core_action_group_allowed('customer', $id ? 'edit' : 'add', $oldGroups)) {
            die();
        }

        if ($id) {
            // Edit mode. Fill the dialog with the data of the customer.

            $data = $database->customer_get_data($id);
            if ($data) {
                $view->name           = $data['name'];
                $view->comment        = $data['comment'];
                $view->password       = $data['password'];
                $view->timezone       = $data['timezone'];
                $view->company        = $data['company'];
                $view->vat_rate       = $data['vat_rate'];
                $view->contact        = $data['contact'];
                $view->street         = $data['street'];
                $view->zipcode        = $data['zipcode'];
                $view->city           = $data['city'];
                $view->phone          = $data['phone'];
                $view->fax            = $data['fax'];
                $view->mobile         = $data['mobile'];
                $view->mail           = $data['mail'];
                $view->homepage       = $data['homepage'];
                $view->visible        = $data['visible'];
                $view->filter         = $data['filter'];
                $view->selectedGroups = $database->customer_get_group_ids($id);
                $view->id             = $id;
            }
        }
        else {
            $view->timezone = $kga['pref']['timezone'];
            $view->vat_rate = $kga['conf']['vat_rate'];
        }

        $view->timezones = timezoneList();
        $allowed_groups  = $database->user_object_actions__allowed_groups('project', 'assign');
        $view->groups    = makeSelectBox('group', $allowed_groups);

        // A new customer is assigned to the group of the current user by default.
        if (!$id) {
            $view->selectedGroups = array();
            foreach ($kga['who']['groups'] as $group) {
                $membershipRoleID = $database->user_get_mRole_id($kga['who']['id'], $group);
                if ($database->mRole_allows($membershipRoleID, 'core__user__add')) {
                    $view->selectedGroups[] = $group;
                }
            }
            $view->id = 0;
        }

        echo $view->render('floaters/add_edit_customer.php');
        break;

    /**
     * Display the dialog to add or edit a project.
     */
    case 'add_edit_project':
        // no adding project here.
        if (empty($id)) {
            die();
        };

        $oldGroups = $database->project_get_groupIDs($id);
        if (!$database->core_action_group_allowed('project', 'edit', $oldGroups)) {
            die();
        }

        $data = $database->project_get_data($id);
        if ($data) {
            $cust_groups         = $database->customer_get_group_ids($data['customer_id']);
            $cust_data           = $database->customer_get_data($data['customer_id']);
            $view->groups        = $database->customer_get_groups($data['customer_id'],'select');
            $view->allActivities = $database->get_activities($cust_groups);

            $view->name               = $data['name'];
            $view->comment            = $data['comment'];
            $view->customer_name      = $cust_data['name'];
            $view->customer_id        = $data['customer_id'];
            $view->visible            = $data['visible'];
            $view->internal           = $data['internal'];
            $view->filter             = $data['filter'];
            $view->budget             = $data['budget'];
            $view->effort             = $data['effort'];
            $view->approved           = $data['approved'];
            $view->selectedActivities = $database->project_get_activities($id);
            $view->default_rate       = $data['default_rate'];
            $view->my_rate            = $data['my_rate'];
            $view->fixed_rate         = $data['fixed_rate'];
            $view->selectedGroups     = $database->project_get_groupIDs($id);
            $view->id                 = $id;

        }
        else {
            Logger::logfile('Floater-add_edit_project ERROR. Did not find project_id.');
            exit();
        }

        echo $view->render('floaters/add_edit_project.php');
        break;

    /**
     * Display the dialog to add or edit an activity.
     */
    case 'add_edit_activity':
        $oldGroups = array();
        if ($id) {
            $oldGroups = $database->activity_get_groupIDs($id);
        }

        if (!$database->core_action_group_allowed('activity', $id ? 'edit' : 'add', $oldGroups)) {
            die();
        }

        if ($id) {
            $data = $database->activity_get_data($id);
            if ($data) {
                $view->name             = $data['name'];
                $view->comment          = $data['comment'];
                $view->visible          = $data['visible'];
                $view->filter           = $data['filter'];
                $view->default_rate     = $data['default_rate'];
                $view->my_rate          = $data['my_rate'];
                $view->fixed_rate       = $data['fixed_rate'];
                $view->selectedGroups   = $database->activity_get_groups($id);
                $view->selectedProjects = $database->activity_get_projects($id);
                $view->id               = $id;

            }
        }
        $allowed_groups =
            $database->user_object_actions__allowed_groups('activity', 'add,edit,delete');

        // Create a <select> element to chosse the groups.
        $view->groups = makeSelectBox('group', $allowed_groups);

        // Create a <select> element to chosse the projects.
        $view->projects = makeSelectBox('project', $allowed_groups);

        // Set defaults for a new project.
        if (!$id) {
            $view->selectedGroups = array();
            foreach ($kga['who']['groups'] as $group) {
                $membershipRoleID = $database->user_get_mRole_id($kga['who']['id'], $group);
                if ($database->mRole_allows($membershipRoleID, 'core__activity__add')) {
                    $view->selectedGroups[] = $group;
                }
            }
            $view->id = 0;
        }

        echo $view->render('floaters/add_edit_activity.php');
        break;

}
