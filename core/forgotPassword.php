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
 * Show an login window or process the login request. On succes the user
 * will be redirected to core/kimai.php.
 */

if ( ! isset($_REQUEST['a'])) {
    $_REQUEST['a'] = '';
}

if ( ! isset($_REQUEST['name']) || is_array($_REQUEST['name'])) {
    $name = '';
}
else {
    $name = $_REQUEST['name'];
}

if ( ! isset($_REQUEST['key']) || is_array($_REQUEST['key'])) {
    $key = 'nokey';  // will never match since hash values are either NULL or 32 characters
}
else {
    $key = $_REQUEST['key'];
}


// =====================
// = standard includes =
// =====================
global $database, $kga;
require('includes/basics.php');

$view = new Zend_View();
$view->setBasePath(WEBROOT . '/templates');

// =========================
// = authentication method =
// =========================
$authClass = 'Kimai_Auth_' . ucfirst($GLOBALS['kga']['authenticator']);
if ( ! class_exists($authClass)) {
    $authClass = 'Kimai_Auth_' . ucfirst($GLOBALS['kga']['authenticator']);
}
$authPlugin = new $authClass($database, $kga);

$view->kga = $kga;

// ===================================
// = current database setup correct? =
// ===================================
checkDBversion();

// =================================================================
// = processing login and displaying either login screen or errors =
// =================================================================
//
$name        = htmlspecialchars(trim($name));
$is_customer = $database->is_customer_name($name);
if ($is_customer) {
    $id         = $database->customer_nameToID($name);
    $customer   = (array) $database->customer_get_data($id);
    $keyCorrect = $key === $customer['password_reset_hash'];
}
else {
    $id         = $database->user_name2id($name);
    $user       = (array) $database->user_get_data($id);
    $keyCorrect = $key === $user['password_reset_hash'];
}


if ($_REQUEST['a'] === 'request') {
    Logger::logfile('password reset: ' . $name . ($is_customer ? ' as customer' : ' as user'));
}

else {
    // ============================
    // = Show password reset page =
    // ============================
    $view->keyCorrect  = $keyCorrect;
    $view->requestData = array(
        'key'  => $key,
        'name' => $name,
    );

    echo $view->render('login/forgotPassword.php');

}
