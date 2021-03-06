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
 * This file is the base class for remote calls.
 * It can and should be utilized for all remote APIs, currently:
 * - Soap // WORK IN PROGRESS
 * - JSON // WORK IN PROGRESS
 *
 */

/**
 * The real class, answering all SOAP methods.
 *
 * Every public method in here, will be available for SOAP/JSON Requests and auto-discovered for WSDL queries.
 */
class Kimai_Remote_Api {
    private $ApiDatabase;

    public function __construct() {
        // Bootstrap Kimaii the old fashioned way ;-)
        require(__DIR__ . '/../basics.php');
        require(__DIR__ . '/database/ApiDatabase.php');

        $this->ApiDatabase = new ApiDatabase;
    }


    /*
     * Checks if the given $apiKey is allowed to fetch data from this system.
     * If so, sets all internal values to their needed state and returns true.
     *
     * @param string $apiKey
     * @return boolean
     */
    private function init($apiKey, $permission = null, $allowCustomer = false) {
        global $database;

        if ( ! is_object($database)) {
            return false;
        }

        $uName = $database->getUserByApiKey($apiKey);
        if ($uName === null || $uName === false) {
            return false;
        }

        $database->checkUserInternal($uName, false);

        if ($permission !== null) {
            // if we ever want to check permissions!
            $dummyTest = true;
        }

        // do not let customers access the SOAP API

        return ! ( ! $allowCustomer && is_customer());
    }

    /**
     * Returns the configured Authenticator for Kimai.
     *
     * @return Kimai_Auth_Abstract
     */
    protected function getAuthenticator() {
        global $database, $kga;

        // load authenticator
        $authClass = 'Kimai_Auth_' . ucfirst($kga['authenticator']);
        if ( ! class_exists($authClass)) {
            $authClass = 'Kimai_Auth_' . ucfirst($kga['authenticator']);
        }

        $authPlugin = new $authClass();
        $authPlugin->setDatabase($database);
        $authPlugin->setKga($kga);

        return $authPlugin;
    }

    /**
     * Authenticates a user and returns the API key.
     *
     * The result is either an empty string (not allowed or need to login first via web-interface) or
     * a string with max 30 character, representing the users API key.
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function authenticate($username, $password) {
        global $database;

        $userId     = null;
        $authPlugin = $this->getAuthenticator();
        $result     = $authPlugin->authenticate($username, $password, $userId);

        // user could not be authenticated or has no account yet ...
        // ... like an SSO account, where the user has to login at least once in web-frontend before using remote API
        if ($result === false || $userId === false || $userId === null) {
            return $this->getAuthErrorResult();
        }

        $apiKey = null;

        // if the user already has an API key, only return the existing one
        $user = $database->checkUserInternal($username);
        if ($user !== null && ! empty($user['apikey'])) {
            return $this->getSuccessResult(array(array('apiKey' => $user['apikey'])));
        }

        // if the user has no api key yet, create one
        while ($apiKey === null) {
            $apiKey = substr(md5(mt_rand()) . sha1(mt_rand()), 0, 25);
            $uid    = $database->getUserByApiKey($apiKey);
            // if the apiKey already exists, we cannot use it again!
            if ($uid !== null && $uid !== false) {
                $apiKey = null;
            }
        }

        // set the apiKey to the user
        $database->user_edit($userId, array('apikey' => $apiKey));

        return $this->getSuccessResult(array(array('apiKey' => $apiKey)));
    }

    /**
     * Returns the result array for failed authentication.
     *
     * @return array
     */
    protected function getAuthErrorResult() {
        return $this->getErrorResult('Unknown user or no permissions.');
    }

    /**
     * Returns the array for failure messages.
     * Returned messages will always be a string, but might be empty!
     *
     * @param string $msg
     *
     * @return array
     */
    protected function getErrorResult($msg = null) {
        if ($msg === null) {
            $msg = 'An unhandled error occured.';
        }

        return array('success' => false, 'error' => array('msg' => $msg));
    }

    /*
     * Returns the array for success responses.
     *
     * @param array $items
     * @param int   $total = 0
     *
     * @return array
     */
    protected function getDebugResult(Array $items, Array $debugItems) {
        $total = count($items);

        return array('success' => true, 'items' => $items, 'total' => $total, 'debug' => $debugItems);
    }


    /**
     * Returns the array for success responses.
     *
     * @param array $items
     * @param int   $total = 0
     *
     * @return array
     */
    protected function getSuccessResult(Array $items, $total = 0) {
        if (empty($total)) {
            $total = count($items);
        }

        return array('success' => true, 'items' => $items, 'total' => $total);
    }

    /**
     * The user started the recording of an activity via the buzzer. If this method
     * is called while another recording is running the first one will be stopped.
     *
     * @param string  $apiKey
     * @param integer $projectId
     * @param integer $activityId
     *
     * @return array
     */
    public function startRecord($apiKey, $projectId, $activityId) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'start_record')) {
            return $this->getAuthErrorResult();
        }

        // check for valid params
        if ( ! $database->is_valid_project_id($projectId) ||
             ! $database->is_valid_activity_id($activityId)
        ) {
            return $this->getErrorResult('Invalid project or task');
        }

        $uid = $kga['who']['id'];

        /*
        if (count($database->get_current_recordings($uid)) > 0) {
            $database->stopRecorder();
        }
        */

        $result = $database->startRecorder($projectId, $activityId, $uid);
        if ($result) {
            return $this->getSuccessResult(array());
        }

        return $this->getErrorResult('Unable to start, invalid params?');
    }

    /**
     * Stops the currently running recording.
     *
     * @param string  $apiKey
     * @param integer $entryId
     *
     * @return boolean
     */
    public function stopRecord($apiKey, $entryId) {
        global $database;

        if ( ! $this->init($apiKey, 'stop_record')) {
            return $this->getAuthErrorResult();
        }

        $result = $database->stopRecorder($entryId);
        if ($result) {
            return $this->getSuccessResult(array());
        }

        return $this->getErrorResult('Unable to stop, not recording?');
    }


    /**
     * Return a list of users. Customers are not shown any users. The
     * type of the current user decides which users are shown to him.
     *
     * Returns false if the call could not be executed, null if no users
     * could be found or an array of users.
     *
     * @param string $apiKey
     *
     * @see get_watchable_users
     * @see processor.php: 'reload_users'
     * @return array|boolean
     */
    public function getUsers($apiKey) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'getUsers')) {
            return $this->getAuthErrorResult();
        }

        $users = $database->user_watchable_users($kga['user']);

        if (count($users) > 0) {
            $results = array();
            foreach ($users as $row) {
                $results[] = array(
                    'user_id' => $row['user_id'],
                    'name'    => $row['name'],
                );
            }

            return $this->getSuccessResult($results);
        }

        return $this->getErrorResult();
    }


    /**
     * Return a list of customers. A customer can only see himself.
     *
     * @param string $apiKey
     *
     * @see 'reload_customers'
     * @return array|boolean
     */
    public function getCustomers($apiKey) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'getCustomers', true)) {
            return $this->getAuthErrorResult();
        }

        if (is_customer()) {
            return array(
                'customer_id' => $kga['who']['id'],
                'name'        => $kga['who']['name'],
            );
        }

        $customers = $database->customers_get($kga['who']['groups']);

        if (count($customers) > 0) {
            $results = array();
            foreach ($customers as $row) {
                $results[] = array(
                    'customer_id' => $row['customer_id'],
                    'name'        => $row['name'],
                );
            }

            return $this->getSuccessResult($results);
        }

        return $this->getErrorResult();
    }

    /**
     * Return a list of projects. Customers are only shown their projects.
     *
     * @param string $apiKey
     *
     * @see 'reload_projects'
     * @return array|boolean
     */
    public function getProjects($apiKey) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'getProjects', true)) {
            return $this->getAuthErrorResult();
        }

        if (is_customer()) {
            $projects = $database->get_projects_by_customer($kga['who']['id']);
        }
        elseif ($kga['is_user_root']) {
            $projects = $database->get_projects();
        }
        else {
            $projects = $database->get_projects($kga['who']['groups']);
        }

        if (is_array($projects) && count($projects) > 0) {
            return $this->getSuccessResult($projects);
        }

        return $this->getErrorResult();
    }


    /**
     * Return a list of tasks. Customers are only shown tasks which are
     * used for them. If a project is set as filter via the project parameter
     * only tasks for that project are shown.
     *
     * @param string        $apiKey
     * @param integer|array $projectId
     *
     * @see 'reload_activities'
     * @return array|boolean
     */
    public function getTasks($apiKey, $projectId = null) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'getTasks', true)) {
            return $this->getAuthErrorResult();
        }

        if (is_customer()) {
            $tasks = $database->get_activities_by_customer($kga['who']['id']);
        }
        else {
            if ($projectId !== null) {
                $tasks = $database->get_activities_by_project($projectId, $kga['who']['groups']);
                /**
                 * we need to copy the array with new keys (remove the customerID key)
                 * if we do not do this, soap server will break our response scheme
                 */
                $tempTasks = array();
                foreach ($tasks as $task) {
                    $tempTasks[] = array(
                        'activity_id' => $task['activity_id'],
                        'name'        => $task['name'],
                        'visible'     => $task['visible'],
                        'budget'      => $task['budget'],
                        'approved'    => $task['approved'],
                        'effort'      => $task['effort'],
                    );
                }
                $tasks = $tempTasks;
            }
            else {
                $tasks = $database->get_activities($kga['who']['groups']);
            }
        }

        if ( ! empty($tasks)) {
            return $this->getSuccessResult($tasks);
        }

        return $this->getErrorResult();
    }

    /**
     * Returns an array with values of the currently active recording.
     *
     * @param string $apiKey
     *
     * @return array
     */
    public function getActiveRecording($apiKey) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'getActiveTask', true)) {
            return $this->getAuthErrorResult();
        }

        $result = $database->get_current_recordings($kga['who']['id']);

        // no 'last' activity existing
        if ( ! is_array($result) || count($result) === 0) {
            return $this->getErrorResult('No active recording.');
        }

        // get the data of the first active recording
        $result = $database->timesheet_get_data($result[0]);

        // do not expose all values, but only the public visible ones
        $keys    = array('time_entry_id', 'activity_id', 'project_id', 'start', 'end', 'duration');
        $current = array();

        if (is_array($result)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $result)) {
                    $current[$key] = $result[$key];
                }
            }
        }

        /*
            add current server time
            this is needed to synchronize time on any extern api calls
        */

        $current['servertime'] = time();


        // add customerId & Name
        $timeSheet                = $database->get_timesheet($current['start'], $current['end'], array($kga['who']['id']));
        $current['customer_id']   = $timeSheet[0]['customer_id'];
        $current['customer_name'] = $timeSheet[0]['customer_name'];
        $current['project_name']  = $timeSheet[0]['project_name'];
        $current['activity_name'] = $timeSheet[0]['activity_name'];

        /*
        $debugItems = array();
        $debugItems['get_timesheet'] = $timeSheet;
        $result = $this->getDebugResult(array($current), array($debugItems));
        */

        $result = $this->getSuccessResult(array($current));

        return $result;
    }

    /*
     * Returns a list of recorded times.
     *
     * @param string $apiKey
     * @param string $from    a MySQL DATE/DATETIME/TIMESTAMP
     * @param string $to      a MySQL DATE/DATETIME/TIMESTAMP
     * @param int    $cleared -1 no filtering, 0 uncleared only, 1 cleared only
     * @param int    $start   limit start
     * @param int    $limit   count rows to select
     *
     * @return array
     */
    public function getTimesheet($apiKey, $from = 0, $to = 0, $cleared = - 1, $start = 0, $limit = 0) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'getTimesheet', true)) {
            return $this->getAuthErrorResult();
        }

        $user = $kga['who']['id'];

        $in  = (int) strtotime($from);
        $out = (int) strtotime($to);

        // Get the array of timesheet entries.
        if (is_customer()) {
            $timesheet_entries = $database->get_timesheet($in, $out, null, array($kga['who']['id']), false, $cleared, $start, $limit);
            $totalCount        = $database->get_timesheet($in, $out, null, array($kga['who']['id']), false, $cleared, $start, $limit, true);

            return $this->getSuccessResult($timesheet_entries, $totalCount);
        }
        else {
            $timesheet_entries = $database->get_timesheet($in, $out, array($user['user_id']), null, null, null, true, false, $cleared, $start, $limit);
            $totalCount        = $database->get_timesheet($in, $out, array($user['user_id']), null, null, null, true, false, $cleared, $start, $limit, true);

            return $this->getSuccessResult($timesheet_entries, $totalCount);
        }
    }

    /**
     * @param string $apiKey
     * @param int    $id
     *
     * @return array
     */
    public function getTimesheetRecord($apiKey, $id) {
        global $database;

        if ( ! $this->init($apiKey, 'getTimesheetRecord', true)) {
            return $this->getAuthErrorResult();
        }

        $id = (int) $id;
        // valid id?
        if (empty($id)) {
            return $this->getErrorResult('Invalid ID');
        }

        $timesheet_entry = $database->timesheet_get_data($id);

        // valid entry?
        if ( ! empty($timesheet_entry)) {
            return $this->getSuccessResult(array($timesheet_entry));
        }

        $result = $this->getErrorResult();

        return $result;
    }

    /**
     * @param string $apiKey
     * @param array  $record
     * @param bool   $doUpdate
     *
     * @return array
     */
    public function setTimesheetRecord($apiKey, Array $record, $doUpdate) {
        global $database, $kga;

        if ( ! $this->init($apiKey, 'setTimesheetRecord', true)) {
            return $this->getAuthErrorResult();
        }

        // valid $record?
        if (empty($record)) {
            return $this->getErrorResult('Invalid record');
        }

        // check for project
        $record['projectId'] = (int) $record['projectId'];
        if (empty($record['projectId'])) {
            return $this->getErrorResult('Invalid projectId.');
        }
        //check for task
        $record['taskId'] = (int) $record['taskId'];
        if (empty($record['taskId'])) {
            return $this->getErrorResult('Invalid taskId.');
        }

        // check from/to
        $in  = (int) strtotime($record['start']); // has to be a MySQL DATE/DATETIME/TIMESTAMP
        $out = (int) strtotime($record['end']); // has to be a MySQL DATE/DATETIME/TIMESTAMP

        // make sure the timestamp is not negative
        if ($in <= 0 || $out <= 0 || $out - $in <= 0) {
            return $this->getErrorResult('Invalid from/to, make sure there is at least a second difference.');
        }

        // prepare data array
        // requried
        $data['user_id']     = $kga['who']['id'];
        $data['project_id']  = $record['projectId'];
        $data['activity_id'] = $record['taskId'];
        $data['start']       = $in;
        $data['end']         = $out;
        $data['duration']    = $out - $in;


        // optional
        if (isset($record['location'])) {
            $data['location'] = $record['location'];
        }

        if (isset($record['ref_code'])) {
            $data['ref_code'] = $record['ref_code'];
        }
        if (isset($record['description'])) {
            $data['description'] = $record['description'];
        }
        if (isset($record['comment'])) {
            $data['comment'] = $record['comment'];
        }
        if (isset($record['comment_type'])) {
            $data['comment_type'] = (int) $record['comment_type'];
        }
        if (isset($record['rate'])) {
            $data['rate'] = (double) $record['rate'];
        }
        if (isset($record['fixed_rate'])) {
            $data['fixed_rate'] = (double) $record['fixed_rate'];
        }
        if (isset($record['flagCleared'])) {
            $data['cleared'] = (int) $record['flagCleared'];
        }
        if (isset($record['statusId'])) {
            $data['status_id'] = (int) $record['statusId'];
        }
        if (isset($record['flagBillable'])) {
            $data['billable'] = (int) $record['flagBillable'];
        }
        if (isset($record['budget'])) {
            $data['budget'] = (double) $record['budget'];
        }
        if (isset($record['approved'])) {
            $data['approved'] = (double) $record['approved'];
        }


        if ($doUpdate) {
            $id = isset($record['id']) ? (int) $record['id'] : 0;
            if ( ! empty($id)) {
                $database->timeEntry_edit($id, $data);

                return $this->getSuccessResult(array());
            }
            else {

                return $this->getErrorResult('Performed an update, but missing id property.');
            }
        }
        else {
            $id = $database->timeEntry_create($data);
            if ( ! empty($id)) {

                return $this->getSuccessResult(array(array('id' => $id)));
            }
            else {

                return $this->getErrorResult('Failed to add entry.');
            }
        }
    }

    /**
     * @param string $apiKey
     * @param int    $id
     *
     * @return array
     */
    public function removeTimesheetRecord($apiKey, $id) {
        global $database;

        if ( ! $this->init($apiKey, 'removeTimesheetRecord', true)) {
            return $this->getAuthErrorResult();
        }


        $id     = (int) $id;
        $result = $this->getErrorResult('Invalid ID');
        // valid id?
        if (empty($id)) {
            return $result;
        }


        if ($database->timeEntry_delete($id)) {
            $result = $this->getSuccessResult(array());
        }

        return $result;
    }

    /*
     * Returns a list of expenses.
     *
     * @param string $apiKey
     * @param string $from       a MySQL DATE/DATETIME/TIMESTAMP
     * @param string $to         a MySQL DATE/DATETIME/TIMESTAMP
     * @param int    $refundable -1 all, 0 only refundable
     * @param int    $cleared    -1 no filtering, 0 uncleared only, 1 cleared only
     * @param int    $start      limit start
     * @param int    $limit      count rows to select
     *
     * @return array
     */
    public function getExpenses($apiKey, $from = 0, $to = 0, $refundable = - 1, $cleared = - 1, $start = 0, $limit = 0) {
        global $kga;

        if ( ! $this->init($apiKey, 'getExpenses', true)) {
            return $this->getAuthErrorResult();
        }

        $user = $kga['who']['id'];

        $in  = (int) strtotime($from);
        $out = (int) strtotime($to);


        // Get the array of timesheet entries.
        if (is_customer()) {
            $arr_exp    = $this->ApiDatabase->get_expenses($in, $out, array($kga['who']['id']), null, null, false,
                                                           $refundable, $cleared, $start, $limit);
            $totalCount = $this->ApiDatabase->get_expenses($in, $out, array($kga['who']['id']), null, null, false,
                                                           $refundable, $cleared, $start, $limit, true);
        }
        else {
            $arr_exp    = $this->ApiDatabase->get_expenses($in, $out, array($user['user_id']), null, null, false, $refundable,
                                                           $cleared, $start, $limit);
            $totalCount = $this->ApiDatabase->get_expenses($in, $out, array($user['user_id']), null, null, false, $refundable,
                                                           $cleared, $start, $limit, true);
        }
        $result = $this->getSuccessResult($arr_exp, $totalCount);

        return $result;
    }

    /**
     * @param string $apiKey
     * @param int    $id
     *
     * @return array
     */
    public function getExpenseRecord($apiKey, $id) {
        if ( ! $this->init($apiKey, 'getExpenseRecord', true)) {
            return $this->getAuthErrorResult();
        }

        $id = (int) $id;
        // valid id?
        if (empty($id)) {
            return $this->getErrorResult('Invalid ID');
        }

        $expense = $this->ApiDatabase->get_expense($id);

        // valid entry?
        if ( ! empty($expense)) {
            return $this->getSuccessResult(array($expense));
        }

        $result = $this->getErrorResult();

        return $result;
    }

    /**
     * @param string $apiKey
     * @param array  $record
     * @param bool   $doUpdate
     *
     * @return array
     */
    public function setExpenseRecord($apiKey, Array $record, $doUpdate) {
        if ( ! $this->init($apiKey, 'setTimesheetRecord', true)) {
            return $this->getAuthErrorResult();
        }

        // valid $record?
        if (empty($record)) {
            return $this->getErrorResult('Invalid record');
        }


        // check for project
        $record['projectId'] = (int) $record['projectId'];
        if (empty($record['projectId'])) {
            return $this->getErrorResult('Invalid projectId.');
        }

        // converto to timestamp
        $timestamp = (int) strtotime($record['date']); // has to be a MySQL DATE/DATETIME/TIMESTAMP

        // make sure the timestamp is not negative
        if ($timestamp <= 0) {
            return $this->getErrorResult('Invalid date, make sure there is a valid date property.');
        }

        // prepare data array
        // requried

        $data['project_id'] = (int) $record['projectId'];
        $data['timestamp']  = $timestamp;


        // optional
        if (isset($record['description'])) {
            $data['description'] = $record['description'];
        }
        if (isset($record['comment'])) {
            $data['comment'] = $record['comment'];
        }
        if (isset($record['comment_type'])) {
            $data['comment_type'] = (int) $record['comment_type'];
        }
        if (isset($record['refundable'])) {
            $data['refundable'] = (int) $record['refundable'];
        }
        if (isset($record['cleared'])) {
            $data['cleared'] = (int) $record['cleared'];
        }
        if (isset($record['multiplier'])) {
            $data['multiplier'] = (double) $record['multiplier'];
        }
        if (isset($record['value'])) {
            $data['value'] = (double) $record['value'];
        }


        if ($doUpdate) {
            $id = isset($record['id']) ? (int) $record['id'] : 0;
            if ( ! empty($id)) {
                $this->ApiDatabase->expense_edit($id, $data);

                return $this->getSuccessResult(array());
            }
            else {

                return $this->getErrorResult('Performed an update, but missing id property.');
            }
        }
        else {
            $id = $this->ApiDatabase->expense_create($data);
            if ( ! empty($id)) {

                return $this->getSuccessResult(array(array('id' => $id)));
            }
            else {

                return $this->getErrorResult('Failed to add entry.');
            }
        }
    }

    /**
     * @param string $apiKey
     * @param int    $id
     *
     * @return array
     */
    public function removeExpenseRecord($apiKey, $id) {
        if ( ! $this->init($apiKey, 'removeTimesheetRecord', true)) {
            return $this->getAuthErrorResult();
        }

        $id     = (int) $id;
        $result = $this->getErrorResult('Invalid ID');
        // valid id?
        if (empty($id)) {
            return $result;
        }


        if ($this->ApiDatabase->expense_delete($id)) {
            $result = $this->getSuccessResult(array());
        }

        return $result;
    }


}
