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

// =====================
// = INVOICE PROCESSOR =
// =====================

// insert KSPI
$isCoreProcessor = 0;
$dir_templates   = 'templates/';
global $database, $kga, $view;
require('../../includes/kspi.php');

// ==================
// = handle request =
// ==================
switch ($axAction) {

    // =====================================
    // = Reload the timespan and return it =
    // =====================================
    case 'reload_timespan':

        $timeframe = get_timeframe();
        $view->in  = $timeframe[0];
        $view->out = $timeframe[1];

        echo $view->render('timespan.php');
        break;

    // ==========================
    // = Change the default vat =
    // ==========================
    case 'editVatRate':

        $vat_rate = str_replace($kga['conf']['decimal_separator'], '.', $_REQUEST['vat_rate']);

        if (!is_numeric($vat_rate)) {
            echo '0';

            return;
        }

        config_set('vat_rate', $vat_rate, false, 'dec', 4);
        $database->config_replace();
        //cn // $database->configuration_edit(array('vat_rate'=>$vat));
        echo '1';
        break;

    // ==========================
    // = Change the default vat =
    // ==========================
    case 'projects':
        if (array_key_exists('user', $kga)) {
            $db_projects = $database->get_projects_by_customer($_GET['customer_id'], any_get_group_ids());
        }
        else {
            $db_projects = $database->get_projects_by_customer($kga['customer']['customer_id'], any_get_group_ids());
        }
        $js_projects = array();
        foreach ($db_projects as $project) {
            $js_projects[$project['project_id']] = $project['name'];
        }
        header('Content-Type: application/json');
        echo json_encode($js_projects);
        break;
}
