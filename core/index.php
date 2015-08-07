<?php
/** @var $view Zend_View */
/** @var $authPlugin Kimai_Auth_Kimai */

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

if (!isset($_POST['password']) || is_array($_POST['password'])) {
    $password = '';
}
else {
    $password = $_POST['password'];
}

ob_start();

// =====================
// = standard includes =
// =====================
global $database, $kga, $translations, $view;
require('includes/basics.php');


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



// =========================
// = User requested logout =
// =========================
$justLoggedOut = false;
if ($_REQUEST['a'] === 'logout') {
    cookie_set('ki_key', '0');
    cookie_set('ki_user', '0');
    $justLoggedOut = true;
}

// ===========================
// = User already logged in? =
// ===========================
if (isset($_COOKIE['ki_user'], $_COOKIE['ki_key'])
    && $_COOKIE['ki_user'] !== '0'
    && $_COOKIE['ki_key'] !== '0'
    && !$_REQUEST['a'] === 'logout'
    && $database->get_seq($_COOKIE['ki_user']) === $_COOKIE['ki_key']
) {
    header('Location: core/kimai.php');
    exit;
}

// ======================================
// = if possible try an automatic login =
// ======================================
if (!$justLoggedOut
    && $authPlugin->autoLoginPossible()
    && $authPlugin->performAutoLogin($userId)
) {
    if ($userId === false) {
        $userId = $database->user_create(array(
                                             'name'           => $name,
                                             'global_role_id' => any_get_global_role_id(),
                                             'active'         => 1,
                                         ));
        $database->setGroupMemberships($userId, array($authPlugin->getDefaultGroups()));
    }
    $userData = $database->user_get_data($userId);

    $keymai = random_code(30);
    cookie_set('ki_key', $keymai);
    cookie_set('ki_user', $userData['name']);

    $database->user_loginSetKey($userId, $keymai);

    header('Location: core/kimai.php');
}

// =================================================================
// = processing login and displaying either login screen or errors =
// =================================================================

switch ($_REQUEST['a']) {

    case 'chg_lang':
        if (isset($_REQUEST['language'])) {
            cookie_set('ki_language', $_REQUEST['language']);
            header("location:http://${_SERVER['SERVER_NAME']}/index.php");

        }
        break;

    case 'checklogin':
        $name = htmlspecialchars(trim($name));

        $is_customer = $database->is_customer_name($name);

        Logger::logfile('login Attempt: ' . $name . ($is_customer ? ' as customer' : ' as user'));

        if ($is_customer) {
            //      C U S T O M E R        //
            $passCrypt = password_encrypt($password);
            $id        = $database->customer_nameToID($name);
            $data      = $database->customer_get_data($id);

            // TODO: add BAN support
            if ($data['password'] === $passCrypt && $name !== '' && $passCrypt !== '') {
                Logger::logfile('login OK: ' . $name . ($is_customer ? ' as customer' : ' as user'));
                $keymai = random_code(30);
                cookie_set('ki_key', $keymai);
                cookie_set('ki_user', 'customer_' . $name);
                $database->customer_loginSetKey($id, $keymai);
                header('Location: core/kimai.php');
            }
            else {
                Logger::logfile('login failed: ' . $name . ($is_customer ? ' as customer' : ' as user'));
                cookie_set('ki_key', '0');
                cookie_set('ki_user', '0');
                $view->headline = $kga['dict']['accessDenied'];
                $view->message  = $kga['dict']['wrongPass'];
                $view->refresh  = '<meta http-equiv="refresh" content="5;URL=index.php">';
                echo $view->render('misc/error.php');
            }
        }


        elseif ($authPlugin->authenticate($name, $password, $userId)) {
            //      U S E R        //

            $userData = $database->user_get_data($userId);

            if (empty($kga['conf']['login_tries']) ||
                ($userData['ban'] < ($kga['conf']['login_tries'])
                    || (time() - $userData['ban_time']) > $kga['conf']['login_ban_time'])
            ) {
                Logger::logfile('login OK: ' . $name . ($is_customer ? ' as customer' : ' as user'));
                $keymai = random_code(30);
                cookie_set('ki_key', $keymai);
                cookie_set('ki_user', $userData['name']);
                $database->user_loginSetKey($userId, $keymai);
                header('Location: core/kimai.php');
            }

            else {
                // login attempt even though login_tries are used up and bantime is not over => deny
                Logger::logfile('login Failed: ' . $name . ($is_customer ? ' as customer' : ' as user'));
                cookie_set('ki_key', '0');
                cookie_set('ki_user', '0');
                $database->login_update_ban($userId);

                $view->headline = $kga['dict']['banned'];
                $view->message  = $kga['dict']['tooManyLogins'];
                $view->refresh  = '<meta http-equiv="refresh" content="5;URL=index.php">';
                echo $view->render('misc/error.php');
            }
        }


        else {
            // wrong username/password => deny
            Logger::logfile('login banned: ' . $name . ($is_customer ? ' as customer' : ' as user'));
            cookie_set('ki_key', '0');
            cookie_set('ki_user', '0');
            if ($userId !== false) {
                $database->login_update_ban($userId, true);
            }

            $view->headline = $kga['dict']['accessDenied'];
            $view->message  = $kga['dict']['wrongPass'];
            $view->refresh  = '<meta http-equiv="refresh" content="5;URL=index.php">';
            echo $view->render('misc/error.php');
        }

        break;

    // ============================================
    // = Show login panel depending on (demo)mode =
    // ============================================
    default:

        $view->selectbox = '';


        echo $view->render('login/panel.php');
}

ob_end_flush();
