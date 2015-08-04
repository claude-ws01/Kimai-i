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

// ==================================
// = implementing standard includes =
// ==================================
global $kga, $database, $view;
include('../../includes/basics.php');
include('private_db_layer_mysql.php');

checkUser();

$dir_templates = "templates/";
$datasrc       = "config.ini";
$settings      = parse_ini_file($datasrc);
$dir_ext       = $settings['EXTENSION_DIR'];

// ============================================
// = initialize currently displayed timeframe =
// ============================================
$timeframe = get_timeframe();
$in        = $timeframe[0];
$out       = $timeframe[1];

$view = new Zend_View();
$view->setBasePath(WEBROOT . 'extensions/' . $dir_ext . '/' . $dir_templates);
$view->addHelperPath(WEBROOT . '/templates/helpers', 'Zend_View_Helper');

$view->kga = $kga;

// prevent IE from caching the response
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (array_key_exists('user', $kga)) // user logged in
{
    $view->expenses = get_expenses($in, $out, array($kga['user']['user_id']), null, null, 1);
}
else // customer logged in
{
    $view->expenses = get_expenses($in, $out, null, array($kga['customer']['customer_id']), null, 1);
}

$view->total = Format::formatCurrency(array_reduce($view->expenses, function ($sum, $expense) {
    return $sum + $expense['multiplier'] * $expense['value'];
}, 0));


if (array_key_exists('user', $kga)) // user logged in
{
    $ann = expenses_by_user($in, $out, array($kga['user']['user_id']));
}
else // customer logged in
{
    $ann = expenses_by_user($in, $out, null, array($kga['customer']['customer_id']));
}
$ann                    = Format::formatCurrency($ann);
$view->user_annotations = $ann;

// TODO: function for loops or convert it in template with new function
if (array_key_exists('user', $kga)) // user logged in
{
    $ann = expenses_by_customer($in, $out, array($kga['user']['user_id']));
}
else // customer logged in
{
    $ann = expenses_by_customer($in, $out, null, array($kga['customer']['customer_id']));
}
$ann                        = Format::formatCurrency($ann);
$view->customer_annotations = $ann;

if (array_key_exists('user', $kga)) // user logged in
{
    $ann = expenses_by_project($in, $out, array($kga['user']['user_id']));
}
else // customer logged in
{
    $ann = expenses_by_project($in, $out, null, array($kga['customer']['customer_id']));
}
$ann                       = Format::formatCurrency($ann);
$view->project_annotations = $ann;

if (array_key_exists('user', $kga)) {
    $view->hideComments = $kga['pref']['show_comments_by_default'] != 1;
}
else {
    $view->hideComments = true;
}

$view->expenses_display = $view->render("expenses.php");

echo $view->render('main.php');

