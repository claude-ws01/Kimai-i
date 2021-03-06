<?php

class Extensions
{

    private $php_include_files = array();
    private $css_extension_files = array();
    private $js_extension_files = array();
    private $extensions = array();
    private $tab_change_trigger = array();
    private $timeframe_changed_hooks = array();
    private $record_hooks = array();
    private $buzzer_stop_hooks = array();
    private $users_changed_hooks = array();
    private $customers_changed_hooks = array();
    private $projects_changed_hooks = array();
    private $activities_changed_hooks = array();
    private $filter_hooks = array(); // list filter hooks
    private $resize_hooks = array(); // resize hooks
    private $timeouts = array();

    private $extensionsDir;


    public function __construct($dir)
    {
        $this->extensionsDir = $dir;
    }

    public function activitiesChangedHooks()
    {
        return implode($this->activities_changed_hooks);
    }

    public function buzzerRecordHooks()
    {
        return implode($this->record_hooks);
    }

    public function buzzerStopHooks()
    {
        return implode($this->buzzer_stop_hooks);
    }

    public function cssExtensionFiles()
    {
        foreach ($this->css_extension_files as $key => &$file) {
            if (!preg_match('/^.+(?:\.debug|\.min)\.css$/', $file)) {
                $test = preg_replace('/\.css$/', DEBUG_JS . '.css', $file);
                //DEV//
                if (IN_DEV) {
                    error_log('<<==KEY==>>' . $key . '<<==FILE==>>' . $file . '<<==NEW==>>' . $test . '<<= DO NOT HAVE .min .debug ==>>');
                }

                $file = $test;
            }
        }

        return $this->css_extension_files;
    }

    public function customersChangedHooks()
    {
        return implode($this->customers_changed_hooks);
    }

    public function extensionsTabData()
    {
        return $this->extensions;
    }

    public function filterHooks()
    {
        return implode($this->filter_hooks);
    }

    public function jsExtensionFiles()
    {
        foreach ($this->js_extension_files as $key => $file) {
            $test = preg_replace('/\.js$/', DEBUG_JS . '.js', $file);
            //DEBUG// error_log('<<==KEY==>>'.$key.'<<==FILE==>>'.$file.'<<==NEW==>>'.$test.'<<=>>');
            $this->js_extension_files[$key] = $test;
        }

        return $this->js_extension_files;
    }

    /**
     * PARSE EXTENSION CONFIGS (ext_configs)
     */
    public function loadConfigurations()
    {
        global $database, $kga;


        $handle = opendir($this->extensionsDir);

        if (!$handle) {
            return;
        }


        while (false !== ($dir = readdir($handle))) {

            if (($dir === '..') || ($dir === '.') || is_file($dir) || (substr($dir, 0) === '.') || (substr($dir, 0, 1) === '#')) {
                continue;
            }

            // make path absolute
            $dir = $this->extensionsDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;

            if (file_exists($dir . 'kimai_include.php')) {
                $this->addValue($this->extensionsDir . $dir . 'kimai_include.php', $php_include_files);
            }

            if (!file_exists($dir . 'config.ini')) {
                continue;
            }

            $settings = parse_ini_file($dir . 'config.ini');

            // Check if user has the correct rank to use this extension
            if (is_user()) {
                if (!$database->gRole_allows($kga['user']['global_role_id'], $settings['EXTENSION_KEY'] . '__access')) {
                    continue;
                }
            }
            else {
                if ($settings['CUSTOMER_ALLOWED'] !== '1') {
                    continue;
                }
            }

            $this->extensions[$settings['EXTENSION_KEY']] =
                array('name'             => $settings['EXTENSION_NAME'],
                      'key'              => $settings['EXTENSION_KEY'],
                      'initFile'         => $settings['EXTENSION_INIT_FILE'],
                      'tabChangeTrigger' => isset($settings['TAB_CHANGE_TRIGGER'])
                          ? $settings['TAB_CHANGE_TRIGGER'] : '',
                );

            //CN, check if the skin has it's own css/grfx folder
            $this->addValue(self::skinCssDir($settings), $this->css_extension_files);

            // add JavaScript files
            $this->addOptionalValue($settings, 'JS_INCLUDE_FILES', $this->js_extension_files);

            // read trigger function for tab change
            $this->addOptionalValue($settings, 'TAB_CHANGE_TRIGGER', $this->tab_change_trigger);

            // read hook triggers
            $this->addOptionalValue($settings, 'TIMEFRAME_CHANGE_TRIGGER', $this->timeframe_changed_hooks);
            $this->addOptionalValue($settings, 'BUZZER_RECORD_TRIGGER', $this->record_hooks);
            $this->addOptionalValue($settings, 'BUZZER_STOP_TRIGGER', $this->buzzer_stop_hooks);
            $this->addOptionalValue($settings, 'CHANGE_USER_TRIGGER', $this->users_changed_hooks);
            $this->addOptionalValue($settings, 'CHANGE_CUSTOMER_TRIGGER', $this->customers_changed_hooks);
            $this->addOptionalValue($settings, 'CHANGE_PROJECT_TRIGGER', $this->projects_changed_hooks);
            $this->addOptionalValue($settings, 'CHANGE_ACTIVITY_TRIGGER', $this->activities_changed_hooks);
            $this->addOptionalValue($settings, 'LIST_FILTER_TRIGGER', $this->filter_hooks);
            $this->addOptionalValue($settings, 'RESIZE_TRIGGER', $this->resize_hooks);

            // add Timeout clearing
            $this->addOptionalValue($settings, 'REG_TIMEOUTS', $this->timeouts);

        }

        //CN - keeping tab order in relation with the folder name. I got some differences between linux and windows (?!)
        $iniFile = array();
        foreach ($this->extensions as $row) {
            $iniFile[] = $row['key'];
        }
        array_multisort($iniFile, SORT_ASC, $this->extensions);

        closedir($handle);
    }

    public function phpIncludeFiles()
    {
        return $this->php_include_files;
    }

    public function projectsChangedHooks()
    {
        return implode($this->projects_changed_hooks);
    }

    public function resizeHooks()
    {
        return implode($this->resize_hooks);
    }

    public function timeframeChangedHooks()
    {
        return implode($this->timeframe_changed_hooks);
    }

    public function timeoutList()
    {
        $timeoutlist = '';
        foreach ($this->timeouts as $timeout) {
            $timeoutlist .= "kill_timeout('{$timeout}');";
        }

        return $timeoutlist;
    }

    public function usersChangedHooks()
    {
        return implode($this->users_changed_hooks);
    }

    public static function skinCssDir($settings)
    {   //CN, check if the skin has it's own css/grfx file for current extension

        global $kga;

        $skin_extension_css_file = 'skins/' . $kga['pref']['skin'] . '/' . $settings['EXTENSION_DIR'] . DEBUG_JS . '.css';


        if (file_exists(WEBROOT . $skin_extension_css_file)) {
            return '../' . $skin_extension_css_file;
        }

        //DEV//
        if (IN_DEV) {
            error_log('<<== EXTENSIONS - SKIN CSS FILE MISSING==>>' . $skin_extension_css_file);
        }


        return '../extensions/' . $settings['EXTENSION_DIR'] . '/css/styles' . DEBUG_JS . '.css';
    }

    public static function skinDir($settings)
    {   //CN, check if the skin has it's own css/grfx folder for current extension

        global $kga;

        $skin_extension_dir = 'skins/' . $kga['pref']['skin'] . '/' . $settings['EXTENSION_DIR'];

        if (file_exists(WEBROOT . $skin_extension_dir . '/css/styles.css')) {
            return '../' . $skin_extension_dir;
        }

        return '../extensions/' . $settings['EXTENSION_DIR'];
    }

    private function addOptionalValue(&$settings, $key, &$list)
    {
        if (isset($settings[$key])) {
            $this->addValue($settings[$key], $list);
        }
    }

    /*
     * Add a settings value to the list. Duplicate entries will be prevented.
     * If the settings value is an array each item in the entry will be added.
     */
    private function addValue($value, &$list)
    {
        if (is_array($value)) {
            foreach ($value as $subvalue) {
                if (!in_array($subvalue, $list)) {
                    $list[] = $subvalue;
                }
            }
        }
        else {
            if (!in_array($value, $list)) {
                $list[] = $value;
            }
        }
    }

}

