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
 * Functions defined here are not directly accessing the database.
 */

function checkDBversion()
{
    global $kga, $database;

    // check for versions before 0.7.13r96
    $installedVersion = $database->get_DBversion();
    $db_version       = (string)$installedVersion[0];
    $db_revision      = (int)$installedVersion[1];


    if ($db_version === '0.5.1'
        && count($database->users_get()) === 0
        && strpos(basename($_SERVER['DOCUMENT_URI']), 'installer') === 0
    ) {
        // fresh install
        header("Location: http://${_SERVER['SERVER_NAME']}/installer");
        exit;
    }


    // the check for revision is much simpler ...
    if ($db_revision < (int)$kga['core.revision']
        && strpos(basename($_SERVER['DOCUMENT_URI']), 'updater') === 0
    ) {
        header("Location: http://${_SERVER['SERVER_NAME']}/updater.php");
        exit;
    }


    if ($db_version !== $kga['core.version']) {
        // only need to update conf values
        config_set('core.version', $kga['core.version'], true);
        $database->config_replace();
    }

    if (preg_match('/^(?:updater|install)$/', basename($_SERVER['DOCUMENT_URI'], '.php'))) {
        header("Location: http://${_SERVER['SERVER_NAME']}/index.php");
        exit;
    }
}

/**
 * Check if a user is logged in or kick them.
 */
function checkUser()
{
    global $database;

    if (array_key_exists('ki_user', $_COOKIE)
        && array_key_exists('ki_key', $_COOKIE)
        && $_COOKIE['ki_user'] !== '0'
        && $_COOKIE['ki_key'] !== '0'
    ) {
        $kimai_user = addslashes($_COOKIE['ki_user']);
        $kimai_key  = addslashes($_COOKIE['ki_key']);

        $db_key = $database->get_seq($kimai_user);
        if ($db_key !== $kimai_key) {
            error_log('<<========================================>>');
            error_log('<<== DB KEY ==>>'.$db_key.'<<>>');
            error_log('<<== COOKIE ==>>'.$kimai_key.'<<>>');
            error_log('<<========================================>>');
            Logger::logfile("Kicking user $kimai_user because of authentication key mismatch.");
            kickUser();
        }

        elseif (($user = $database->checkUserInternal($kimai_user)) === false) {
            Logger::logfile('Kicking user. Failed checkUserInternal.');
            kickUser();
        }

        else {
            Kimai_Registry::setUser(new Kimai_User($user));

            return;
        }
    }

    Logger::logfile('Kicking user because of missing cookie.');
    kickUser();
}

function clean_data($data)
{
    $return = array();

    foreach ($data as $key => $value) {
        if ($key !== 'pw') {
            $return[$key] = urldecode(strip_tags($data[$key]));
            $return[$key] = str_replace('"', '_', $data[$key]);
            $return[$key] = str_replace("'", '_', $data[$key]);
            $return[$key] = str_replace('\\', '', $data[$key]);
        }
        else {
            $return[$key] = $data[$key];
        }
    }

    return $return;
}

function config_init()
{
    /*
     * this is entry point to manager the whole list of configuration.  This is the one to maintain.
     * What goes through here:
     *      - basics.php (all interactions initialize with basics.php)
     *      - admin..advanced edition
     *      - installation
     *      - updater
     */

    global $kga;

    //DEFAULT CONFIG VALUES
    $K['admin_mail']                  = 'admin@example.com';
    $K['allow_round_down']            = '0';
    $K['bill_pct']                    = '0,25,50,75,100';
    $K['check_at_startup']            = '0';
    $K['core.ident']                  = 'kimai-i';
    $K['core.version']                = '0';
    $K['core.revision']               = '0';
    $K['core.status']                 = '0';
    $K['currency_first']              = '0';
    $K['currency_name']               = 'Euro';
    $K['currency_sign']               = '€';
    $K['date_format_0']               = '%d.%m.%Y';
    $K['date_format_1']               = '%d.%m.';
    $K['date_format_2']               = '%d.%m.%Y';
    $K['decimal_separator']           = ',';
    $K['default_status_id']           = '4';
    $K['duration_with_seconds']       = '0';
    $K['edit_limit']                  = '-';
    $K['exact_sums']                  = '0';
    $K['lastdbbackup']                = '0';
    $K['login']                       = '1';
    $K['login_ban_time']              = '900';
    $K['login_tries']                 = '3';
    $K['round_minutes']               = '0';
    $K['round_precision']             = '0';
    $K['round_seconds']               = '0';
    $K['round_timesheet_entries']     = '0';
    $K['show_day_separator_lines']    = '1';
    $K['show_gab_breaks']             = '0';
    $K['show_record_again']           = '1';
    $K['show_sensible_data']          = '0';
    $K['show_update_warn']            = '1';
    $K['ref_num_editable']            = '1';
    $K['vat_rate']                    = '0';
    $K['ud.autoselection']            = '1';
    $K['ud.flip_project_display']     = '0';
    $K['ud.hide_cleared_entries']     = '0';
    $K['ud.hide_overlap_lines']       = '1';
    $K['ud.language']                 = 'en';
    $K['ud.no_fading']                = '0';
    $K['ud.open_after_recorded']      = '0';
    $K['ud.project_comment_flag']     = '0';
    $K['ud.quickdelete']              = '0';
    $K['ud.rowlimit']                 = '100';
    $K['ud.show_comments_by_default'] = '0';
    $K['ud.show_ids']                 = '0';
    $K['ud.show_ref_code']            = '0';
    $K['ud.skin']                     = 'standard';
    $K['ud.sublist_annotations']      = '2';
    $K['ud.timezone']                 = 'Europe/Berlin';
    $K['ud.user_list_hidden']         = '0';


    if (!isset($kga['conf'])) {
        $kga['conf'] = array();
    }

    // $kga overwrites $K
    $kga['conf'] = array_merge($K, $kga['conf']);
}

function config_bill_pct()
{
    global $kga;

    $kga['bill_pct'] = null;

    $bill_pct = explode(',', $kga['conf']['bill_pct']);
    foreach ($bill_pct as $value) {
        $val_i                   = (int)$value;
        $kga['bill_pct'][$val_i] = (string)$value . '%';
    }

    // safety
    if (null === ($kga['bill_pct'])) {
        $kga['bill_pct'][] = array(100 => '100%');
    }
}

function config_set($option, $value = null, $force_set = false, $type = 'str', $decimals = 2)
{
    global $kga;

    if (!array_key_exists($option, $kga['conf'])) {
        Logger::logfile("Error: Option *$option* does not exist. Can not set it.");

        return false;
    }

    if (!in_array($type, array('bool', 'str', 'int', 'dec'), false)) {
        Logger::logfile("Error: Option type *$type* does not exist. Valid values: bool, str, int, dec.");

        return false;
    }

    if ($value === null) {
        if ($force_set) {
            switch ($type) {
                case 'str':
                    $value = '';
                    break;
                case 'bool':
                    $value = 0;
                    break;
                case 'int':
                    $value = 0;
                    break;
                case 'dec':
                    $value = number_format((float)0, (int)$decimals, '.', '');
                    break;
            }
        }
        else {
            return true;
        }
    }
    else {
        switch ($type) {
            case 'str':
                break;
            case 'bool':
                $value = (bool)$value ? 1 : 0;
                break;
            case 'int':
                $value = (int)($value);
                break;
            case 'dec':
                $value = number_format((float)($value), $decimals, '.', '');
                break;
        }
    }

    $kga['conf'][$option] = (string)$value;

    return true;
}

function convert_time_strings($in, $out)
{

    $explode_in  = explode('-', $in);
    $explode_out = explode('-', $out);

    $date_in  = explode('.', $explode_in[0]);
    $date_out = explode('.', $explode_out[0]);

    $time_in  = explode(':', $explode_in[1]);
    $time_out = explode(':', $explode_out[1]);

    $time['in']   = mktime($time_in[0], $time_in[1], $time_in[2], $date_in[1], $date_in[0], $date_in[2]);
    $time['out']  = mktime($time_out[0], $time_out[1], $time_out[2], $date_out[1], $date_out[0], $date_out[2]);
    $time['diff'] = (int)$time['out'] - (int)$time['in'];

    return $time;
}

function cookie_get($cookie_name, $default = null)
{
    return isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : $default;
}

function cookie_set($name, $value, $expire = 0, $secure = false, $httponly = false)
{
    global $kga;
    //DEBUG// error_log('<<== COOKIE == NAME >>'.$name.'<<value>>'.$value.'<<expire>>'.$expire);

    if ($kga['https']): $secure = true; endif;

    if (!headers_sent()) {
        setcookie($name, $value, $expire, '/',
                  $_SERVER['SERVER_NAME'], $secure, $httponly);
    }
}

function devTimeSpan()
{
    $y = date('y');

    return ($y === '15' ? '2015' : '2015' . $y);
}

function endsWith($haystack, $needle)
{
    return strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0;
}

/**
 * Returns the boolean value as integer, submitted via checkbox.
 *
 * @param $name
 *
 * @return int
 */
function getRequestBool($name)
{
    if (isset($_REQUEST[$name])) {
        if (strtolower($_REQUEST[$name]) === 'on') {
            return 1;
        }

        $temp = (int)($_REQUEST[$name]);
        if ($temp === 1 || $temp === 0) {
            return $temp;
        }

        return 1;
    }

    return 0;
}

/**
 * Returns the decimal value from a request value where the number is still represented
 * in the location specific way.
 *
 * @param $value the value from the request
 *
 * @return parsed floating point value
 */
function getRequestDecimal($value)
{
    global $kga;

    if (trim($value) === '') {
        return null;
    }
    else {
        return (double)str_replace($kga['conf']['decimal_separator'], '.', $value);
    }
}

/**
 * get in and out unix seconds of specific user
 *
 * <pre>
 * returns:
 * [0] -> in
 * [1] -> out
 * </pre>
 *
 * @param string $user ID of user
 *
 * @global array $kga  kimai-global-array
 * @return array
 */
function get_timeframe()
{
    global $kga;

    $timeframe = array(null, null);
    $timeframe[0] = $kga['who']['timeframe_begin'] ?: null;
    $timeframe[1] = $kga['who']['timeframe_end'] ?: null;


    /* database has no entries? */
    $mon = date('n');
    $day = date('j');
    $Y   = date('Y');
    if (!$timeframe[0]) {
        $timeframe[0] = mktime(0, 0, 0, $mon, 1, $Y);
    }
    if (!$timeframe[1]) {
        $timeframe[1] = mktime(23, 59, 59, $mon, $day, $Y);
    }

    return $timeframe;
}

function is_customer()
{
    global $kga;

    return ($kga['who']['type'] === 'customer');
}

function is_user()
{
    global $kga;

    return ($kga['who']['type'] === 'user');
}

function kickUser()
{
    die('<script type="text/javascript">window.location.href = "../index.php?a=logout";</script>');
}

function ki_iconv_set_encoding($type, $charset = 'UTF-8')
{
    // iconv_set_encoding deprecated WARNINGS //

    if (PHP_VERSION_ID < 50600) {
        iconv_set_encoding($type, $charset);
    }
    else {
        ini_set('default_charset', $charset);
    }

    return true;
}

function makeSelectBox($subject, $groups, $selection = null, $includeDeleted = false)
{
    /*
     * Returns array for smarty's html_options funtion.
     *
     * <pre>
     * returns:
     * [0] -> project/activity names
     * [1] -> values as IDs
     * </pre>
     *
     * @param string either 'project', 'activity', 'customer', 'group'
     *
     * @return array
     */

    global $database, $kga;

    $sel = array();

    switch ($subject) {
        case 'project':
            $projects = $database->get_projects($groups);
            if (is_array($projects)) {

                foreach ($projects as $project) {
                    if ($project['visible']) {

                        if ($kga['pref']['flip_project_display']) {
                            $projectName = $project['customer_name'] . ': ' . $project['name'];
                        }
                        else {
                            $projectName = $project['name'] . ' (' . $project['customer_name'] . ')';
                        }

                        if ($kga['pref']['project_comment_flag']) {
                            $projectName .= '(' . $project['comment'] . ')';
                        }

                        $sel[$project['project_id']] = $projectName;
                    }
                }
            }
            break;

        case 'activity':
            $activities = $database->get_activities($groups);
            if (is_array($activities)) {
                foreach ($activities as $activity) {
                    if ($activity['visible']) {
                        $sel[$activity['activity_id']] = $activity['name'];
                    }
                }
            }
            break;

        case 'customer':
            $customers      = $database->customers_get($groups);
            $selectionFound = false;
            if (is_array($customers)) {
                foreach ($customers as $customer) {
                    if ($customer['visible']) {
                        $sel[$customer['customer_id']] = $customer['name'];
                        if ($selection === $customer['customer_id']) {
                            $selectionFound = true;
                        }
                    }
                }
            }
            if ($selection !== null && !$selectionFound) {
                $data                      = $database->customer_get_data($selection);
                $sel[$data['customer_id']] = $data['name'];
            }
            break;


        case 'group':
            $groups = $database->groups_get();
            if (!$database->gRole_allows($kga['who']['global_role_id'], 'core__group__other_group__view')) {
                $groups = array_filter($groups,
                    function ($group) {
                        global $kga;
                        return in_array($group['group_id'], $kga['who']['groups'], true) !== false;
                    }
                );
            }

            foreach ($groups as $group) {
                if ($includeDeleted || !$group['trash']) {
                    $sel[$group['group_id']] = $group['name'];
                }
            }
            break;

        case 'sameGroupUser':
            //CN..current-user groups already in $kga//
            $users = $database->users_get(0, $kga['who']['groups']);

            foreach ($users as $user) {
                if ($includeDeleted || !$user['trash']) {
                    $sel[$user['user_id']] = $user['name'];
                }
            }
            break;

        case 'allUser':
            $users = $database->users_get($kga['user']);

            foreach ($users as $user) {
                if ($includeDeleted || !$user['trash']) {
                    $sel[$user['user_id']] = $user['name'];
                }
            }
            break;

        default:
            // TODO leave default options empty ???
            break;
    }

    return $sel;

}

function password_create($length)
{
    $chars    = '234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $i        = 0;
    $password = '';
    while ($i <= $length) {
        $password .= $chars{mt_rand(0, strlen($chars) - 1)};
        $i++;
    }

    return $password;
}

function password_encrypt($new_password)
{
    global $kga;

    $encrypted = md5($kga['password_salt'] . $new_password . $kga['password_salt']);

    return $encrypted;
}

function password_encrypt_random()
{
    global $kga;

    $random = md5($kga['password_salt'] . md5(uniqid(mt_rand(), true)) . $kga['password_salt']);

    return $random;
}

function random_code($length)
{
    $code   = '';
    $string = 'ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz0123456789';
    mt_srand((double)microtime() * 1000000);
    for ($i = 1; $i <= $length; $i++) {
        $code .= substr($string, mt_rand(0, strlen($string) - 1), 1);
    }

    return $code;
}

function random_number($length)
{
    $number = '';
    $string = '0123456789';
    mt_srand((double)microtime() * 1000000);
    for ($i = 1; $i <= $length; $i++) {
        $number .= substr($string, mt_rand(0, strlen($string) - 1), 1);
    }

    return $number;
}

function timezoneList()
{
    return DateTimeZone::listIdentifiers();
}

function write_config_file($hostname, $database, $username, $password, $salt, $prefix, $authenticator, $language, $timezone = null)
{
    $file = fopen(realpath(__DIR__) . '/autoconf.php', 'w');
    if (!$file) {
        return false;
    }

    if (empty($timezone)) {
        $timezone = date_default_timezone_get();
    }

    $config = <<<PHP
<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) 2006-2013 Kimai-Development-Team // http://www.kimai.org
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

// This file was automatically generated by the installer

\$server_hostname = "$hostname";
\$server_database = "$database";
\$server_username = "$username";
\$server_password = "$password";
\$server_prefix   = "$prefix";
\$password_salt   = "$salt";
\$authenticator   = "$authenticator";
\$language        = "$language";
\$timezone        = "$timezone";

PHP;
    //NOTE: no dbl-quotes around $timezone.

    fwrite($file, $config);
    fclose($file);

    return true;
}
