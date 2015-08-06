<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/cloudeasy/Kimai-i
 * (c) 2015 Claude Nadon
 * (c) Kimai-Development-Team // http://www.kimai.org
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
 * All things related to translations.
 * It's currently just listing available languages and loading them into the $kga.
 */
class Translations
{
    private $load_status = true;

    public function __construct($load_status_from_db = true)
    {
        global $kga;

        $this->load_status = (bool)$load_status_from_db;
        $kga['dict']       = array();
        $this->load();
    }

    public static function languageExists($language)
    {

        return file_exists(WEBROOT . '/language/' . $language . '.php');
    }

    /**
     * returns array of language files
     *
     * @param none
     *
     * @return array
     * @author unknown/th
     */
    public static function langs()
    {
        $arr_files = array();

        $handle = opendir(WEBROOT . '/language/');
        while (false !== ($readdir = readdir($handle))) {
            if ($readdir !== '.' && $readdir !== '..' && substr($readdir, 0, 1) !== '.' && endsWith($readdir, '.php')) {
                $arr_files[] = str_replace('.php', '', $readdir);
            }
        }
        closedir($handle);
        sort($arr_files);

        return $arr_files;
    }

    public function load()
    {
        global $database, $kga;

        $selected = self::setLanguage();
        $current  = isset($kga['language']) ? $kga['language'] : '';
        if ($current !== $selected) {

            $kga['dict'] = require WEBROOT . 'language/' . $selected . '.php';;

            if ($this->load_status) {
                $database->status_def_load();
            }
            $kga['language'] = $selected;
        }

        $cookie = isset($_COOKIE['ki_language']) ? $_COOKIE['ki_language'] : '';
        if ($cookie !== $selected) {
            cookie_set('ki_language', $selected);
        }
    }

    public static function setLanguage()
    {
        global $kga;

        $selected = false;

        // URI ARGS /
        if (isset($_REQUEST['language'])) {
            $code2 = basename($_REQUEST['language']);  // prevents potential directory traversal
            if (self::languageExists($code2)) {
                $selected = $code2;
            }
        }

        // COOKIE //
        if (!$selected && ($code2 = get_cookie('ki_language'))) {
            $code2 = basename($code2);
            if (self::languageExists($code2)) {
                $selected = $code2;
            }
        }

        // PREF //
        if (!$selected && isset($kga['pref']['language'])) {
            $code2 = basename($kga['pref']['language']);
            if (self::languageExists($code2)) {
                $selected = $code2;
            }
        }

        if (!$selected && isset($kga['conf']['ud.language'])) {
            $code2 = basename($kga['conf']['ud.language']);
            if (self::languageExists($code2)) {
                $selected = $code2;
            }
        }

        return $selected ?: 'en';
    }
}
