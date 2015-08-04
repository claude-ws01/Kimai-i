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
    private $loaded_lang = '';

    public function __construct($load_status_from_db = true, $language = 'en')
    {
        global $kga;

        $this->load_status = (bool)$load_status_from_db;
        $kga['lang'] = array();
        $this->load($language);
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
        $arr_files   = array();
        $arr_files[] = '';
        $handle      = opendir(WEBROOT . '/language/');
        while (false !== ($readdir = readdir($handle))) {
            if ($readdir !== '.' && $readdir !== '..' && substr($readdir, 0, 1) !== '.' && endsWith($readdir, '.php')) {
                $arr_files[] = str_replace('.php', '', $readdir);
            }
        }
        closedir($handle);
        sort($arr_files);

        return $arr_files;
    }

    public function load($name)
    {
        global $database;

        $languageName = basename($name); // prevents potential directory traversal
        $languageFile = WEBROOT . 'language/' . $languageName . '.php';

        if (file_exists($languageFile)) {
            $GLOBALS['kga']['lang'] = array_replace_recursive($GLOBALS['kga']['lang'], include($languageFile));
            $this->loaded_lang = $languageName;
        }
        elseif ($this->loaded_lang === '') {
            $GLOBALS['kga']['lang'] = require(WEBROOT . 'language/en.php');
            $this->loaded_lang = 'en';
        }

        if ($this->load_status) {
            $database->status_def_load();
        }

    }
}
