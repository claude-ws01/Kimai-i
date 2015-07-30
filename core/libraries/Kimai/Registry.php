<?php

/**
 * Registry to fetch several global Kimai objects.
 *
 * @author Kevin Papst
 */
class Kimai_Registry extends Zend_Registry
{
    /**
     * Sets the configuration to use.
     *
     * @param Zend_Config $config
     */
    public static function setConfig(Zend_Config $config)
    {
        self::set('Zend_Config', $config);
    }

    /**
     * Return the global configuration, merged with all user related configurations.
     *
     * @return Zend_Config
     */
    public static function getConfig()
    {
        return self::get('Zend_Config');
    }

    public static function getDatabase()
    {
        return self::get('database');
    }

    public static function setDatabase($database)
    {
        self::set('database', $database);
    }

    public static function setUser(Kimai_User $user)
    {
        self::set('Kimai_User', $user);
    }

}
