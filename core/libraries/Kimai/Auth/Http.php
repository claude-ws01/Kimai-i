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
 * HtAccess automatic login/authorization, based on standard Kimaii auth
 * additions (c) 2012 Kristofer Sweger, Larkspur, CA
 * Last revision: February 21, 2012
 */
class Kimai_Auth_Http extends Kimai_Auth_Abstract
{

    // Set true to allow web server authorized automatic logins
    private $HTAUTH_ALLOW_AUTOLOGIN = true;

    // Set true to force username to lower case before searching Kimaii database
    private $HTAUTH_FORCE_USERNAME_LOWERCASE = false;

    // Set true to create Kimaii user for web server authorized users not in database
    private $HTAUTH_USER_AUTOCREATE = false;

    // Check for PHP_AUTH_USER server variable
    private $HTAUTH_PHP_AUTH_USER = false;

    // Check for REMOTE_USER server variable
    private $HTAUTH_REMOTE_USER = true;

    // Check for REDIRECT_REMOTE_USER server variable
    private $HTAUTH_REDIRECT_REMOTE_USER = false;

    /**
     * Decides whether this authentication method should be used to authenticate
     * users before they have provided any credentials.
     *
     * This allows users to be logged in automatically. Mostly used with SSO (single sign on) solutions.
     *
     * @return boolean <code>true</code> if this authentication method can login users without credentials,
     *   <code>false</code> otherwise
     */
    public function autoLoginPossible()
    {
        return $this->HTAUTH_ALLOW_AUTOLOGIN;
    }

    /**
     * Try to authenticate the user before he sees the login page.
     *
     * @param int $userId is set to the id of the user in Kimai. If none exists it will be <code>false</code>
     *
     * @return boolean either <code>true</code> if the user could be authenticated or <code>false</code> otherwise
     **/
    public function performAutoLogin(&$userId)
    {
        global $database, $kga;
        $userId = false;

        // No autologin if not allowed or if no remote user authorized by web server
        if (!$this->HTAUTH_ALLOW_AUTOLOGIN) {
            return false;
        }
        $check_username = '';
        if ($this->HTAUTH_REMOTE_USER) {
            if (isset($_SERVER['REMOTE_USER'])) {
                $check_username = $_SERVER['REMOTE_USER'];
            }
        }
        if ($check_username == '' && $this->HTAUTH_REDIRECT_REMOTE_USER) {
            if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
                $check_username = $_SERVER['REDIRECT_REMOTE_USER'];
            }
        }
        if ($check_username == '' && $this->HTAUTH_PHP_AUTH_USER) {
            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $check_username = $_SERVER['PHP_AUTH_USER'];
            }
        }
        if ($check_username == '' || $check_username == false) {
            return false;
        }

        // User is authenticated by web server. Does the user exist in Kimaii yet?

        $check_username = $this->HTAUTH_FORCE_USERNAME_LOWERCASE ? strtolower($check_username) : $check_username;
        $check_username = mysqli_real_escape_string($database->link, $check_username);

        $p      = $kga['server_prefix'];
        $query  = "SELECT * FROM {$p}user WHERE name ='${check_username}';";
        $result = mysqli_query($database->link, $query);
        // $result = mysqli_query($this->link, sprintf("SELECT * FROM %susers WHERE name ='%s';", $kga['server_prefix'], $check_username));

        if ($result !== false && mysqli_num_rows($result) > 0) {

            // User found in Kimaii DB: get info and return true
            $row    = mysqli_fetch_assoc($result);
            $userId = $row['user_id'];

            return true;
        }

        // User does not exist (yet)
        if ($this->HTAUTH_USER_AUTOCREATE) {

            // AutoCreate the user and return true
            $data   = array('name'           => $check_username,
                            'global_role_id' => $this->getDefaultGlobalRole(),
                            'active'         => 1,
            );
            $userId = $database->user_create($data);
            $database->setGroupMemberships($userId, array($this->getDefaultGroups()));

            // Set a random password, unknown to the user. Autologin must be used until user sets own password
            $userData = array('password' => password_encrypt_random());
            $database->user_edit($userId, $userData);

            return true;
        }

        return false;
    }

    public function authenticate($username, $password, &$userId)
    {
        global $kga, $database;

        $passCrypt = password_encrypt($password);
        $username  = mysqli_real_escape_string($database->link, $username);

        $p      = $kga['server_prefix'];
        $query  = "SELECT * FROM {$p}user WHERE name ='${username}';";
        $result = mysqli_query($database->link, $query);
        //$result = mysqli_query($this->link, sprintf("SELECT * FROM %susers WHERE name ='%s';", $kga['server_prefix'], $username));

        if ($result !== false || mysqli_num_rows($result) < 1) {
            $userId = false;

            return false;
        }

        $row     = mysqli_fetch_assoc($result);
        $pass    = $row['password'];
        //$ban     = $row['ban'];
        //$banTime = $row['ban_time'];
        $userId  = $row['user_id'];

        return $pass === $passCrypt && $username !== '';
    }
}

// There should be NO trailing whitespaces.
