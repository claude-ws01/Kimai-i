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
$dir_templates = "templates/";
global $database, $kga, $translations, $view;

require("../../includes/kspi.php");

include('private_db_layer_mysql.php');

switch ($axAction)
{

    case "add_edit_record":
        if (isset($GLOBALS['kga']['customer'])) {
            die();
        }

        $view->commentTypes = $commentTypes;
        $view->projects     = makeSelectBox("project",any_get_group_ids()); // select for projects
        $view->activities   = makeSelectBox("activity",any_get_group_ids()); // select for activities

        // ==============================================
        // = display edit dialog for timesheet record   =
        // ==============================================
        if ($id)
        {
            $expense                = get_expense($id);
            $view->id               = $id;
            $view->comment          = $expense['comment'];
            $view->edit_day         = date("d.m.Y",$expense['timestamp']);
            $view->edit_time        = date("H:i",$expense['timestamp']);
            $view->multiplier       = $expense['multiplier'];
            $view->edit_value       = $expense['value'];
            $view->description      = $expense['description'];
            $view->selected_project = $expense['project_id'];
            $view->comment_type      = $expense['comment_type'];
            $view->refundable       = $expense['refundable'];

            // check if this entry may be edited
            if (!$database->global_role_allows(any_get_global_role_id(),'ki_expense__own_entry__edit'))
              break;

            if (!isset($view->projects[$expense['project_id']])) {
              // add the currently assigned project to the list
              $projectData = $database->project_get_data($expense['project_id']);
              $customerData = $database->customer_get_data($projectData['customer_id']);
              $view->projects[$projectData['project_id']] = $customerData['name'] . ':' . $projectData['name'];
            }
        }
        else
        {
          $view->id         = 0;
          $view->edit_day   = date("d.m.Y");
          $view->edit_time  = date("H:i");
          $view->multiplier = '1'.$GLOBALS['kga']['conf']['decimal_separator'].'0';

          // check if this entry may be added
          if (!$database->global_role_allows(any_get_global_role_id(),'ki_expense__own_entry__add'))
            break;
        }

        echo $view->render("floaters/add_edit_record.php");

    break;

}
