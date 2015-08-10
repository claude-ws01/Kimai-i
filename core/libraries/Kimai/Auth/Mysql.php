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

require_once WEBROOT . 'libraries/Kimai/Database/kimai.php';

class Kimai_Auth_Mysql extends Kimai_Auth_Abstract
{
    /**
     * @param $username
     * @param $password
     * @param $userId
     *
     * @return bool
     */
    public function authenticate($username, $password, &$userId)
    {
        global $database;

        $userId = $database->user_name2id($username);

        if ($userId === false) {
            return false;
        }

        $passCrypt = password_encrypt($password);
        $userData  = $database->user_get_data($userId);
        $pass      = $userData['password'];

        if ($pass !== $passCrypt) {
            //CN..use to set password in database for admin, if something went wrong (update).
            Logger::logfile('login failed: passCrypt = ' . $passCrypt);
        }

        return $pass === $passCrypt && !empty($username);
    }

    public function forgotPassword($name)
    {
        global $kga, $database;

        $id = $database->user_name2id($name);

        $user              = $database->user_get_data($id);
        $passwordResetHash = str_shuffle(md5(microtime()));

        $database->user_edit($id, array('password_reset_hash' => $passwordResetHash));

        $url = ($kga['https'] ? 'https://' : 'http://') . $_SERVER['SERVER_NAME']
            . dirname($_SERVER['SCRIPT_NAME']) . '/forgotPassword.php?name='
            . urlencode($name) . '&key=' . $passwordResetHash;

        $message = $kga['dict']['passwordReset']['mailMessage'];
        $message = str_replace('%{URL}', $url, $message);

        //TRACE//
        error_log('<<== FORGOT PASSWORD CONFIRMATION EMAIL ==>>' . $user['mail']);
        mail($user['mail'],
             $kga['dict']['passwordReset']['mailSubject'],
             $message);

        return $kga['dict']['passwordReset']['mailConfirmation'];
    }

    public function resetPassword($username, $password, $key)
    {
        global $kga, $database;

        $id   = $database->user_name2id($username);
        $user = $database->user_get_data($id);
        if ($key !== $user['password_reset_hash']) {
            return array(
                'message' => $kga['dict']['passwordReset']['invalidKey'],
            );
        }

        $data                        = array();
        $data['password']            = password_encrypt($password);
        $data['password_reset_hash'] = null;
        $database->user_edit($id, $data);

        return array(
            'message'       => $kga['dict']['passwordReset']['success'],
            'showLoginLink' => true,
        );
    }

}
