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
 * This file is the SOAP interface to Kimai to be used by
 * external APPs to allow remote access.
 *
 * Please read the following page to know how this server works:
 * http://framework.zend.com/manual/en/zend.soap.server.html
 *
 * @author Kevin Papst <kpapst@gmx.net>
 */

/**
 * ==================================================================
 * Bootstrap Zend
 * ==================================================================
 *
 * - Ensure library/ is on include_path
 * - Register Autoloader
 */
defined('WEBROOT')
|| define('WEBROOT', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', $_SERVER['DOCUMENT_ROOT']);

set_include_path(
    implode(
        PATH_SEPARATOR,
        array(
            realpath(APPLICATION_PATH . '/libraries/'),
        )
    )
);

//DEBUG// error_log('<<<========== SOAP ==========>>>');
require_once WEBROOT . 'libraries/Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();

/**
 * ==================================================================
 * Prepare environment and execute SOAP mode to execute
 * ==================================================================
 */

require(APPLICATION_PATH . '/includes/classes/remote.class.php');

ini_set('soap.wsdl_cache_enabled', 0);                              // @TODO
ini_set('soap.wsdl_cache_dir', APPLICATION_PATH . '/temporary/');     // @TODO
ini_set('soap.wsdl_cache', WSDL_CACHE_NONE);                        // WSDL_CACHE_DISK
ini_set('soap.wsdl_cache_ttl', 0);                                  // cache lifetime

// @TODO check what works better, with or without?
//$soapOpts = array('soap_version' => SOAP_1_2, 'encoding' => 'UTF-8'/*, 'uri' => $wsdlUrl*/);
$soapOpts = array();

if (isset($_GET['wsdl']) || isset($_GET['WSDL'])) {
    $autodiscover = new Zend_Soap_AutoDiscover();
    $autodiscover->setClass('Kimai_Remote_Api');
    $autodiscover->handle();
}
else {
    $wsdlUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?wsdl';
    $server  = new Kimai_Remote_Api();

    $soap = new Zend_Soap_Server($wsdlUrl, $soapOpts);
    $soap->setObject($server);
    $soap->handle();
}
