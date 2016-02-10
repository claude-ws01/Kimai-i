<?php

/**
 * Responsible for logging messages to the logfile.
 */
class Logger {

    private static $instance;
    private $file;

    /**
     * Create a new logger instance.
     */
    private function __construct() {
        if ( ! file_exists(WEBROOT . 'temporary/')
             && ! mkdir(WEBROOT . 'temporary')
        ) {
            error_log('LOGGER ERROR. Failed creating/opening logfile.log');
        }
        else {
            $this->file = fopen(WEBROOT . 'temporary/logfile.txt', 'a');
            set_error_handler('Logger::errorHandler');
            set_exception_handler('Logger::exceptionHandler');
        }
    }

    /**
     * Close the file if the instance is destroyed.
     */
    public function __destruct() {
        if ( ! is_resource($this->file)) {
            return;
        }

        fclose($this->file);
    }

    /**
     * Initialize the logger.
     *
     */
    public static function init() {

        if (self::$instance === null) {
            self::$instance = new Logger();
        }
    }

    /**
     * Simple static method to log lines to the logfile.
     *
     * @param string  $value message
     * @param string  $path  relative path to temporary directory
     * @param boolean $success
     *
     */
    public static function logfile($value) {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }

        $value = preg_replace('/\\n|\\s{2,}/i', '', $value);
        self::$instance->log($value);
    }

    /**
     * Write a line to the logfile.
     *
     * @param string $line line to log
     *
     */
    public function log($line) {
        if ( ! is_resource($this->file)) {
            return;
        }

        fwrite($this->file, date('[d.m.Y H:i:s] ', time()) . $line . "\n");
    }

    public static function exceptionHandler($exception) {
        Logger::logfile('Uncaught exception: ' . $exception->getMessage());
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline) {// function err_log_error($num, $str, $file, $line)


        // If the @ error-control operator is set don't log the error.
        if (error_reporting() === 0) {
            return false;
        }

        $line = '';
        switch ($errno) {
            case E_WARNING:
                $line .= 'E_WARNING';
                break;
            case E_NOTICE:
                $line .= 'E_NOTICE';
                break;
            case E_USER_ERROR:
                $line .= 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $line .= 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $line .= 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $line .= 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR:
                $line .= 'E_RECOVERABLE_ERROR';
                break;
        }

        $line .= ' ' . $errstr;

        $line .= " @${errfile} line ${errline}";

        Logger::logfile($line);

        self::err_MessageToMail(new ErrorException($errstr, 0, $errno, $errfile, $errline));

        return false; // let PHP do it's error handling as well
    }


    private static function err_MessageToMail(exception $e) {   // \r\n are necessary for email content
        global $kga;

        if ( ! IN_DEV
             && ! empty($kga['error_log_mail_from'])
             && ! empty($kga['error_log_mail_to'])
        ) {

            $message = PHP_EOL . 'Hostname: ' . `hostname`;
            $message .= 'Message: ' . $e->getMessage() . PHP_EOL;
            $message .= 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
            $message .= 'SCRIPT_FILENAME: ' . $_SERVER['SCRIPT_FILENAME'] . PHP_EOL;
            $message .= 'QUERY_STRING: ' . $_SERVER['QUERY_STRING'] . PHP_EOL;
            $message .= 'File:    ' . $e->getFile() . PHP_EOL;
            $message .= 'Line:    ' . $e->getLine() . PHP_EOL;
            $message .= 'Trace:' . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            $message .= 'PHP ' . PHP_VERSION . ' (' . PHP_OS . ')' . PHP_EOL;

            $headers =
                'From: ' . $kga['error_log_mail_from'] . "\r\n" .
                'Reply-To: ' . $kga['error_log_mail_to'] . "\r\n" .
                'X-Mailer: PHP/' . phpversion() . "\r\n" .
                'Content-type: text/plain; charset=UTF-8';
            error_log($message, 1, $kga['error_log_mail_to'], $headers);
        }
    }
}

