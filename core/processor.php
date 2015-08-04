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

/**
 * Show an login window or process the login request. On succes the user
 * will be redirected to core/kimai.php.
 */

if (!isset($_REQUEST['a'])) {
    $_REQUEST['a'] = '';
}

if (!isset($_POST['name']) || is_array($_POST['name'])) {
    $name = '';
}
else {
    $name = $_POST['name'];
}

// =====================
// = standard includes =
// =====================
require('includes/basics.php');
global $kga, $database, $view;
$view = new Zend_View();
$view->setBasePath(WEBROOT . '/templates');

// =========================
// = authentication method =
// =========================
$authClass = 'Kimai_Auth_' . ucfirst($kga['authenticator']);
if (!class_exists($authClass)) {
    $authClass = 'Kimai_Auth_' . ucfirst($kga['authenticator']);
}
$authPlugin = new $authClass($database, $kga);

$view->kga = $kga;

// =================================================================
// = processing login and displaying either login screen or errors =
// =================================================================

switch ($_REQUEST['a']) {

    case 'forgotPassword':
        $name = htmlspecialchars(trim($name));

        $is_customer = $database->is_customer_name($name);

        Logger::logfile('password reset: ' . $name . ($is_customer ? ' as customer' : ' as user'));

        if ($is_customer) {
            $id = $database->customer_nameToID($name);

            $customer          = $database->customer_get_data($id);
            $passwordResetHash = str_shuffle(MD5(microtime()));

            $database->customer_edit($id, array('password_reset_hash' => $passwordResetHash));

            $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
            $url = ($ssl ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/forgotPassword.php?name=' . urlencode($name) . '&key=' . $passwordResetHash;

            $message = $kga['lang']['passwordReset']['mailMessage'];
            $message = str_replace('%{URL}', $url, $message);
            mail($customer['mail'],
                 $kga['lang']['passwordReset']['mailSubject'],
                 $message);

            echo json_encode(array(
                                 'message' => $kga['lang']['passwordReset']['mailConfirmation'],
                             ));

        }
        else {
            if (!method_exists($authPlugin, 'forgotPassword')) {
                echo json_encode(array(
                                     'message' => $kga['lang']['passwordReset']['notSupported'],
                                 ));
            }
            else {
                echo json_encode(array(
                                     'message' => $authPlugin->forgotPassword($name),
                                 ));
            }
        }
        break;


    case 'resetPassword':
        $key = $_REQUEST['key'];

        $name        = htmlspecialchars(trim($name));
        $password    = $_REQUEST['password'];
        $is_customer = $database->is_customer_name($name);

        if ($is_customer) {
            $id       = $database->customer_nameToID($name);
            $customer = $database->customer_get_data($id);
            if ($key != $customer['password_reset_hash']) {
                echo json_encode(array(
                                     'message' => $kga['lang']['passwordReset']['invalidKey'],
                                 ));
                break;
            }

            $data                        = array();
            $data['password']            = password_encrypt($password);
            $data['password_reset_hash'] = null;
            $database->customer_edit($id, $data);
            echo json_encode(array(
                                 'message'       => $kga['lang']['passwordReset']['success'],
                                 'showLoginLink' => true,
                             ));
        }
        else {
            echo json_encode($authPlugin->resetPassword($name, $password, $key));
        }
        break;

}

