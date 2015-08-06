<?php
// require WEBROOT . 'libraries/Kimai/Database/kimai.php';

/**
 * Ultimate MySQL Wrapper Class
 *
 * @version 2.5
 * @author  Jeff L. Williams
 * @link    http://www.phpclasses.org/ultimatemysql
 *
 * Contributions from
 *   Frank P. Walentynowicz
 *   Larry Wakeman
 *   Nicola Abbiuso
 *   Douglas Gintz
 *   Emre Erkan
 */
class MySQL
{
    // SET THESE VALUES TO MATCH YOUR DATA CONNECTION
    const SQLVALUE_BIT = 'bit'; // server name
    const SQLVALUE_BOOLEAN = 'boolean'; // user name
    const SQLVALUE_DATE = 'date'; // password
    const SQLVALUE_DATETIME = 'datetime'; // database name
    const SQLVALUE_NUMBER = 'number'; // optional character set (i.e. utf8)
    const SQLVALUE_T_F = 't-f'; // use persistent connection?

    // constants for SQLValue function
    const SQLVALUE_TEXT = 'text';
    const SQLVALUE_TIME = 'time';
    const SQLVALUE_Y_N = 'y-n';
    public $link; // mysql link resource
    public $num_rows = 0;
/*  Determines if an error throws an exception
    @var boolean Set to true to throw error exceptions */
    public $ThrowExceptions = false; //    was protected $kga;
    private $db_host = 'localhost';
    private $db_user = '';
    private $db_pass = '';
    private $db_name = '';

    // class-internal variables - do not change
    private $db_charset = '';
    private $db_pcon = false; //perseverant connection
    private $active_row = -1;
    private $error_desc = '';
    private $error_number = 0;
    private $in_transaction = 0; // level, number of transaction begin requested
    private $last_insert_id;
    private $last_result;
    private $last_sql = '';

    /**
     * Constructor: Opens the connection to the database
     *
     * @param string  $database Database name
     * @param string  $host     Host address
     * @param string  $username User name
     * @param string  $password (Optional) Password
     * @param string  $charset  (Optional) Character set
     * @param boolean $pcon     (Optional) Persistent connection
     */
    public function __construct($host, $database, $username, $password = '', $charset = null, $pcon = false)

    {
        $this->db_host = $host;
        $this->db_name = $database;
        $this->db_user = $username;
        $this->db_pass = $password;
        if ($charset !== null) {
            $this->db_charset = $charset;
        }
        if (is_bool($pcon)) {
            $this->db_pcon = $pcon;
        }

        $this->connect();
    }

    public function connect()
    {
        if ($this->db_pcon) {
            // persistent connection
            $this->link = @mysqli_connect(
                'p:' . $this->db_host, $this->db_user, $this->db_pass, $this->db_name);
        }
        else {
            // normal connection
            $this->link = mysqli_connect(
                $this->db_host, $this->db_user, $this->db_pass, $this->db_name);


        }
    }

    /**
     * Automatically does an INSERT or UPDATE depending if an existing record
     * exists in a table
     *
     * @param string $tableName   The name of the table
     * @param array  $valuesArray An associative array containing the column
     *                            names as keys and values as data. The values
     *                            must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect)
     * @param array  $whereArray  An associative array containing the column
     *                            names as keys and values as data. The values
     *                            must be SQL ready (i.e. quotes around strings,
     *                            formatted dates, ect).
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function autoInsertUpdate($tableName, $valuesArray, $whereArray)
    {
        $this->resetError();
        $this->selectRows($tableName, $whereArray);
        if (!$this->error()) {
            if ($this->hasRecords()) {
                return $this->updateRows($tableName, $valuesArray, $whereArray);
            }
            else {
                return $this->insertRow($tableName, $valuesArray);
            }
        }
        else {
            return false;
        }
    }

    /**
     * Close current MySQL connection
     *
     * @return object Returns TRUE on success or FALSE on error
     */
    public function close()
    {
        $this->resetError();
        $this->active_row = -1;

        if ($this->in_transaction > 0) {
            $this->setError('Warning: in_transation > 0 when closing dbConnection. Missing a transactionEnd?');
            Logger::logfile('Warning: in_transation > 0 when closing dbConnection. Missing a transactionEnd?');
            $this->in_transaction = 0;
        }

        if (!$this->link) {
            $success = @mysqli_close($this->link);
            if (!$success) {
                $this->setError();

                return false;
            }
        }

        unset($this->last_sql, $this->last_result, $this->link);

        return true;
    }

    /**
     * Deletes rows in a table based on a WHERE filter
     * (can be just one or many rows based on the filter)
     *
     * @param string $tableName  The name of the table
     * @param array  $whereArray (Optional) An associative array containing the
     *                           column names as keys and values as data. The
     *                           values must be SQL ready (i.e. quotes around
     *                           strings, formatted dates, ect). If not specified
     *                           then all values in the table are deleted.
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function deleteRows($tableName, $whereArray = null)
    {
        $this->resetError();
        if (!$this->isConnected()) {
            $this->setError('No connection');

            return false;
        }
        else {
            $query = self::buildSqlDelete($tableName, $whereArray);

            return $this->query($query) !== false;
        }
    }

    /**
     * Returns true if the internal pointer is at the end of the records
     *
     * @return boolean TRUE if at the last row or FALSE if not
     */
    public function endOfSeek()
    {
        $this->resetError();
        if ($this->isConnected()) {
            if ($this->rowCount() === 0 || $this->active_row >= ($this->rowCount())) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            $this->setError('No connection');

            return false;
        }
    }

    /**
     * Returns the last MySQL error as text
     *
     * @return string Error text from last known error
     */
    public function error()
    {
        $error = $this->error_desc;
        if (!$error) {
            $error = false;
            if ($this->error_number !== 0) {
                $error = 'Unknown Error (#' . $this->error_number . ')';
            }
        }
        else {
            if ($this->error_number > 0) {
                $error .= ' (#' . $this->error_number . ')';
            }
        }

        return $error;
    }

    /**
     * This function returns the number of columns or returns FALSE on error
     *
     * @param string $table (Optional) If a table name is not specified, the
     *                      column count is returned from the last query
     *
     * @return integer The total count of columns
     */
    public function getColumnCount($table = '')
    {
        $this->resetError();
        if (!$table) {
            $result = mysqli_num_fields($this->last_result);
            if (!$result) {
                $this->setError();
            }
        }
        else {
            $records = mysqli_query($this->link, 'SELECT * FROM $table LIMIT 1');
            if (!$records) {
                $this->setError();
                $result = false;
            }
            else {
                $result = mysqli_num_fields($records);
                mysqli_free_result($records);
            }
        }

        return $result;
    }

    /**
     * Returns the last autonumber ID field from a previous INSERT query
     *
     * @return  integer ID number from previous INSERT query
     */
    public function getLastInsertID()
    {
        return $this->last_insert_id;
    }

    /**
     * Determines if a query contains any rows
     *
     * @param string $sql [Optional] If specified, the query is first executed
     *                    Otherwise, the last query is used for comparison
     *
     * @return boolean TRUE if records exist, FALSE if not or query error
     */
    public function hasRecords($sql = '')
    {
        if (strlen($sql) > 0) {
            $result = $this->query($sql);
            if ($this->error()) {
                return false;
            }

            return ($result->num_rows > 0);
        }

        return false;
    }

    /**
     * Inserts a row into a table in the connected database
     *
     * @param string $tableName   The name of the table
     * @param array  $valuesArray An associative array containing the column
     *                            names as keys and values as data. The values
     *                            must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect)
     *
     * @return integer Returns last insert ID on success or FALSE on failure
     */
    public function insertRow($tableName, $valuesArray)
    {
        $this->resetError();
        if (!$this->isConnected()) {
            $this->setError('No connection');

            return false;
        }
        else {
            // Execute the query
            $sql = self::buildSqlInsert($tableName, $valuesArray);
            if ($this->query($sql) === false) {
                return false;
            }
            else {
                return $this->getLastInsertID();
            }
        }
    }

    /**
     * Determines if a valid connection to the database exists
     *
     * @return boolean TRUE idf connectect or FALSE if not connected
     */
    public function isConnected()
    {
        if (is_object($this->link)) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Seeks to the beginning of the records
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function moveFirst()
    {
        $this->resetError();
        if (!$this->seek(0)) {
            $this->setError();

            return false;
        }
        else {
            $this->active_row = 0;

            return true;
        }
    }

    /**
     * Executes the given SQL query and returns the records
     *
     * @param string $sql The query string should not end with a semicolon
     *
     * @return mysqli_result|bool PHP 'mysqlI result' resource object containing the records
     *                              on SELECT, SHOW, DESCRIBE or EXPLAIN queries and returns;
     *                              TRUE or FALSE for all others i.e. UPDATE, DELETE, DROP
     *                              AND FALSE on all errors (setting the local Error message)
     */
    public function query($sql)
    {
        // error_reporting(E_ALL);
        $this->resetError();
        $this->last_sql = $sql;
        $this->last_insert_id = 0;
        $this->active_row = -1;


        $this->last_result = mysqli_query($this->link, $sql);


        if ($this->last_result === false) {
            // ERROR  ===>>> RETURN FALSE
            $this->setError(mysqli_error($this->link));

            return false;
        }

        elseif (strpos(strtolower($sql), 'insert')  === 0) {
            $this->last_insert_id = mysqli_insert_id($this->link);

            if ($this->last_insert_id === false) {
                $this->setError();
                // ERROR ===>>> RETURN FALSE
                return false;
            }
            else {
                // INSERTED ===>>> RETURN result array
                return $this->last_result;
            }
        }

        elseif (strpos(strtolower($sql), 'select') === 0) {

            $this->num_rows = mysqli_num_rows($this->last_result);

            if ($this->num_rows > 0) {
                $this->active_row = 0;
            }

            // SELECTED ===>>> RETURN result array (even if no records)
            return $this->last_result;
        }

        else {

            // ANY OTHER SUCCESFUL OPERATIONS  ===>>> RETURN result array //
            return $this->last_result;
        }
    }

    /**
     * Executes the given SQL query and returns a multi-dimensional array
     *
     * @param string  $sql        The query string should not end with a semicolon
     * @param integer $resultType (Optional) The type of array
     *                            Values can be: MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH
     *
     * @return array A multi-dimensional array containing all the data
     *               returned from the query or FALSE on all errors
     */
    protected function queryArray($sql, $resultType = MYSQLI_BOTH)
    {
        $result = $this->query($sql);
        if (!$this->error()
            && $result->num_rows > 0
        ) {
            return $this->recordsArray($resultType);
        }
        else {
            return false;
        }
    }

    /**
     * Returns all records from last query and returns contents as array
     * or FALSE on error
     *
     * @param integer $resultType (Optional) The type of array
     *                            Values can be: MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH
     *
     * @return array||boolean   Records in array form
     */
    public function recordsArray($resultType = MYSQLI_BOTH)
    {
        $this->resetError();
        if ($this->last_result !== false && mysqli_num_rows($this->last_result) >= 1) {

            $result = mysqli_data_seek($this->last_result, 0);
            if ($result !== true) {
                $this->setError();

                return false;
            }
            else {
                $members = array();

                //while($member = mysqli_fetch_object($this->last_result)){
                while ($member = mysqli_fetch_array($this->last_result, $resultType)) {
                    $members[] = $member;
                }
                mysqli_data_seek($this->last_result, 0);
                $this->active_row = 0;

                return $members;
            }
        }
        else {
            $this->active_row = -1;
            $this->setError('No query results exist', -1);

            return false;
        }
    }

    /**
     * Reads the current row and returns contents as a
     * PHP object or returns false on error
     *
     * @param integer $optional_row_number (Optional) Use to specify a row
     *
     * @return object PHP object or FALSE on error
     */
    public function row($optional_row_number = null)
    {
        $this->resetError();
        if (!$this->last_result) {
            $this->setError('No query results exist', -1);

            return false;
        }
        elseif ($optional_row_number === null) {
            if (($this->active_row) > $this->rowCount()) {
                $this->setError('Cannot read past the end of the records', -1);

                return false;
            }
            else {
                $this->active_row++;
            }
        }
        else {
            if ($optional_row_number >= $this->rowCount()) {
                $this->setError('Row number is greater than the total number of rows', -1);

                return false;
            }
            else {
                $this->active_row = $optional_row_number;
                $this->seek($optional_row_number);
            }
        }
        $row = mysqli_fetch_object($this->last_result);
        if (!$row) {
            $this->setError();

            return false;
        }
        else {
            return $row;
        }
    }

    /**
     * Reads the current row and returns contents as an
     * array or returns false on error
     *
     * @param integer $optional_row_number (Optional) Use to specify a row
     * @param integer $resultType          (Optional) The type of array
     *                                     Values can be: MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH
     *
     * @return array Array that corresponds to fetched row or FALSE if no rows
     */
    public function rowArray($optional_row_number = null, $resultType = MYSQLI_BOTH)
    {
        $this->resetError();
        if (!$this->last_result) {
            $this->setError('No query results exist', -1);

            return false;
        }
        elseif ($optional_row_number === null) {
            if (($this->active_row) > $this->rowCount()) {
                $this->setError('Cannot read past the end of the records', -1);

                return false;
            }
            else {
                $this->active_row++;
            }
        }
        else {
            if ($optional_row_number >= $this->rowCount()) {
                $this->setError('Row number is greater than the total number of rows', -1);

                return false;
            }
            else {
                $this->active_row = $optional_row_number;
                $this->seek($optional_row_number);
            }
        }
        $row = mysqli_fetch_array($this->last_result, $resultType);
        if (!$row) {
            $this->setError();

            return false;
        }
        else {
            return $row;
        }
    }

    /**
     * Returns the last query row count
     *
     * @return integer Row count or FALSE on error
     */
    public function rowCount()
    {
        return $this->num_rows;
    }

    /**
     * [STATIC] Formats any value into a string suitable for SQL statements
     * (NOTE: Also supports data types returned from the gettype function)
     *
     * @param mixed  $value     Any value of any type to be formatted to SQL
     * @param string $datatype  Use SQLVALUE constants or the strings:
     *                          string, text, varchar, char, boolean, bool,
     *                          Y-N, T-F, bit, date, datetime, time, integer,
     *                          int, number, double, float
     *
     * @return string
     */
    public function sqlValue($value, $datatype = self::SQLVALUE_TEXT)
    {
        $return_value = 'NULL'; // DEFAULT VALUE - KEEP THERE

        switch (strtolower(trim($datatype))) {
            case 'text':
            case 'string':
            case 'varchar':
            case 'char':
                if (get_magic_quotes_gpc()) {
                    $value = stripslashes($value);
                }
                $return_value = "'" . mysqli_real_escape_string($this->link, $value) . "'";
                break;

            case 'number':
            case 'integer':
            case 'int':
            case 'double':
            case 'float':
                $return_value = 0;
                if (is_numeric($value)) {
                    $return_value = $value;
                }
                break;

            case 'boolean': //boolean to use this with a bit field
            case 'bool':
            case 'bit':
                $return_value = '0';
                if (self::getBooleanValue($value)) {
                    $return_value = '1';
                }
                break;

            case 'y-n': //boolean to use this with a char(1) field
                $return_value = '\'N\'';
                if (self::getBooleanValue($value)) {
                    $return_value = '\'Y\'';
                }
                break;

            case 't-f': //boolean to use this with a char(1) field
                $return_value = '\'F\'';
                if (self::getBooleanValue($value)) {
                    $return_value = '\'T\'';
                }
                break;

            case 'date':
                if (self::isDate($value)) {
                    $return_value = '\'' . date('Y-m-d', strtotime($value)) . '\'';
                }
                break;

            case 'datetime':
                if (self::isDate($value)) {
                    $return_value = '\'' . date('Y-m-d H:i:s', strtotime($value)) . '\'';
                }
                break;

            case 'time':
                if (self::isDate($value)) {
                    $return_value = '\'' . date('H:i:s', strtotime($value)) . '\'';
                }
                break;

            default:
                exit('ERROR: Invalid data type specified in SQLValue method');
        }

        return $return_value;
    }

    /**
     * Sets the internal database pointer to the
     * specified row number and returns the result
     *
     * @param integer $row_number Row number
     *
     * @return object Fetched row as PHP object
     */
    private function seek($row_number)
    {
        $this->resetError();
        $row_count = $this->rowCount();
        if (!$row_count) {
            return false;
        }
        elseif ($row_number >= $row_count) {
            $this->setError('Seek parameter is greater than the total number of rows', -1);

            return false;
        }
        else {
            $this->active_row = $row_number;
            $result           = mysqli_data_seek($this->last_result, $row_number);
            if (!$result) {
                $this->setError();

                return false;
            }
            else {
                $record = mysqli_fetch_row($this->last_result);
                if (!$record) {
                    $this->setError();

                    return false;
                }
                else {
                    // Go back to the record after grabbing it
                    mysqli_data_seek($this->last_result, $row_number);

                    return $record;
                }
            }
        }
    }

    /**
     * Gets rows in a table based on a WHERE filter
     *
     * @param string  $tableName     The name of the table
     * @param array   $whereArray    (Optional) An associative array containing the
     *                               column names as keys and values as data. The
     *                               values must be SQL ready (i.e. quotes around
     *                               strings, formatted dates, ect)
     * @param         array          /string $columns (Optional) The column or list of columns to select
     * @param         array          /string $sortColumns (Optional) Column or list of columns to sort by
     * @param boolean $sortAscending (Optional) TRUE for ascending; FALSE for descending
     *                               This only works if $sortColumns are specified
     * @param         integer        /string $limit (Optional) The limit of rows to return
     *
     * @return boolean|mysqli_result         Returns records on success or FALSE on error
     */
    public function selectRows($tableName, $whereArray = null, $columns = null, $sortColumns = null,
                               $sortAscending = true, $limit = null)
    {
        $this->resetError();
        if (!$this->isConnected()) {
            $this->setError('No connection');

            return false;
        }
        else {
            $sql = self::buildSqlSelect($tableName, $whereArray,
                                        $columns, $sortColumns, $sortAscending, $limit);

            return $this->query($sql);
        }
    }

    /**
     * Starts a transaction
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function transactionBegin()
    {
        $this->resetError();
        if (!$this->isConnected()) {
            $this->setError('No connection');

            return false;
        }

        if ($this->in_transaction > 0) {
            $this->in_transaction++;

            return true;
        }

        // set autocommit OFF
        if (!mysqli_autocommit($this->link, false)) {
            $this->setError();

            return false;
        }

        // start transaction
        if (phpversion() < '5.5.0') {
            if (!mysqli_query($this->link, 'START TRANSACTION')) {
                $this->setError();

                // autocommit back ON
                if (!($result = mysqli_autocommit($this->link, true))) {
                    $this->setError();
                }

                return false;
            }
        }
        elseif (!mysqli_begin_transaction($this->link)) {
            $this->setError();

            // autocommit back ON
            if (!($result = mysqli_autocommit($this->link, true))) {
                $this->setError();
            }

            return false;
        }

        $this->in_transaction++;

        return true;
    }

    /**
     * Ends a transaction and commits the queries
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    public function transactionEnd()
    {
        $rtn = true;
        $this->resetError();

        if (!$this->isConnected()) {
            $this->setError('No connection');
            $this->in_transaction = 0;
            $rtn                  = false;
        }

        elseif ($this->in_transaction > 1) {
            // still other transactionEnd to come
            $this->in_transaction--;
            $rtn = true;
        }
        elseif ($this->in_transaction === 1) {
            if (!mysqli_commit($this->link)) {
                $this->setError();
                $rtn = false;

                // failed commit, let's rollback
                mysqli_rollback($this->link);
            }

            if (!($result = mysqli_autocommit($this->link, true))) {
                $this->setError();
                $rtn = false;
            }
            $this->in_transaction = 0;
        }

        else {
            // safety - should get here - so let's check autocommit
            $this->setError('Not in a transaction', -1);
            $rtn = false;

            // check autocommit status
            if ($result = mysqli_query($this->link, 'SELECT @@autocommit')) {
                $row = mysqli_fetch_row($result);

                if (!is_bool($result)) {
                    mysqli_free_result($result);
                }

                // autocommit is OFF, needs to be ON
                if (is_array($row[0]) && !$row[0] && !mysqli_autocommit($this->link, true)) {
                    $this->setError();

                }
            }
            $this->in_transaction = 0;
        }

        return $rtn;
    }

    /**
     * Rolls the transaction back
     *
     * @return boolean Returns TRUE on success or FALSE on failure
     */
    public function transactionRollback()
    {
        $rtn = true;
        $this->resetError();

        if (!$this->isConnected()) {
            $this->setError('No connection');
            $rtn = false;
        }
        elseif (!mysqli_rollback($this->link)) {
            $this->setError('Could not rollback transaction');
            $rtn = false;
        }

        if (!($result = mysqli_autocommit($this->link, true))) {
            $this->setError();
            $rtn = false;
        }


        $this->in_transaction = $rtn ? 1 : 0;

        return $rtn;
    }

    /**
     * Updates rows in a table based on a WHERE filter
     * (can be just one or many rows based on the filter)
     *
     * @param string $tableName   The name of the table
     * @param array  $valuesArray An associative array containing the column
     *                            names as keys and values as data. The values
     *                            must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect)
     * @param array  $whereArray  (Optional) An associative array containing the
     *                            column names as keys and values as data. The
     *                            values must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect). If not specified
     *                            then all values in the table are updated.
     *
     * @return boolean Returns TRUE on success or FALSE on error
     */
    private function updateRows($tableName, $valuesArray, $whereArray = null)
    {
        $this->resetError();
        if (!$this->isConnected()) {
            $this->setError('No connection');

            return false;
        }
        else {
            $query = self::buildSqlUpdate($tableName, $valuesArray, $whereArray);

            return ($this->query($query) !== false);
        }
    }

    /**
     * Destructor: Closes the connection to the database
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * [STATIC] Builds a SQL DELETE statement
     *
     * @param string $tableName  The name of the table
     * @param array  $whereArray (Optional) An associative array containing the
     *                           column names as keys and values as data. The
     *                           values must be SQL ready (i.e. quotes around
     *                           strings, formatted dates, ect). If not specified
     *                           then all values in the table are deleted.
     *
     * @return string Returns the SQL DELETE statement
     */
    public static function buildSqlDelete($tableName, $whereArray = null)
    {
        $sql = "DELETE FROM `${tableName}`";
        if ($whereArray !== null) {
            $sql .= self::buildSqlWhereClause($whereArray);
        }

        return $sql;
    }

    /**
     * [STATIC] Builds a SQL INSERT statement
     *
     * @param string $tableName   The name of the table
     * @param array  $valuesArray An associative array containing the column
     *                            names as keys and values as data. The values
     *                            must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect)
     *
     * @return string Returns a SQL INSERT statement
     */
    public static function buildSqlInsert($tableName, $valuesArray)
    {
        $columns = self::buildSqlColumns(array_keys($valuesArray));
        $values  = self::buildSqlColumns($valuesArray, false, false);
        $sql     = "INSERT INTO `$tableName` ($columns) VALUES ($values)";

        return $sql;
    }

    /**
     * Builds a simple SQL SELECT statement
     *
     * @param string  $tableName     The name of the table
     * @param array   $whereArray    (Optional) An associative array containing the
     *                               column names as keys and values as data. The
     *                               values must be SQL ready (i.e. quotes around
     *                               strings, formatted dates, ect)
     * @param         array          /string $columns (Optional) The column or list of columns to select
     * @param         array          /string $sortColumns (Optional) Column or list of columns to sort by
     * @param boolean $sortAscending (Optional) TRUE for ascending; FALSE for descending
     *                               This only works if $sortColumns are specified
     * @param         integer        /string $limit (Optional) The limit of rows to return
     *
     * @return string Returns a SQL SELECT statement
     */
    private static function buildSqlSelect($tableName, $whereArray = null, $columns = null,
                                           $sortColumns = null, $sortAscending = true, $limit = null)
    {
        $sql = '*';
        if ($columns !== null) {
            $sql = self::buildSqlColumns($columns);
        }

        $sql = 'SELECT ' . $sql . ' FROM `' . $tableName . '`';
        if (is_array($whereArray)) {
            $sql .= self::buildSqlWhereClause($whereArray);
        }
        if ($sortColumns !== null) {
            $sql .= ' ORDER BY ' .
                self::buildSqlColumns($sortColumns, true, false) .
                ' ' . ($sortAscending ? 'ASC' : 'DESC');
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $sql;
    }

    /**
     * [STATIC] Builds a SQL UPDATE statement
     *
     * @param string $tableName   The name of the table
     * @param array  $valuesArray An associative array containing the column
     *                            names as keys and values as data. The values
     *                            must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect)
     * @param array  $whereArray  (Optional) An associative array containing the
     *                            column names as keys and values as data. The
     *                            values must be SQL ready (i.e. quotes around
     *                            strings, formatted dates, ect). If not specified
     *                            then all values in the table are updated.
     *
     * @return string Returns a SQL UPDATE statement
     */
    public static function buildSqlUpdate($tableName, $valuesArray, $whereArray = null)
    {
        $sql  = '';
        $coma = '';
        foreach ($valuesArray as $key => $value) {
            $sql  .= $coma . '`' . $key . '` = ' . $value;
            $coma = ', ';
        }

        $sql = "UPDATE `${tableName}` SET " . $sql;
        if (is_array($whereArray)) {
            $sql .= self::buildSqlWhereClause($whereArray);
        }

        return $sql;
    }

    protected static function buildSqlReplace($tableName, $columnArray, $valueArray)
    {
        $columns = '';
        $coma1   = '';
        foreach ($columnArray as $col) {
            $columns .= "${coma1} `${col}`";
            $coma1 = ',';
        }

        $values = '';
        $coma1  = '';
        foreach ($valueArray as $values1) {

            $coma2 = '';
            $val2  = '(';
            foreach ($values1 as $value) {

                $val2 .= "$coma2 $value";
                $coma2 = ',';
            }

            $values .= $coma1 . $val2 . ')';
            $coma1 = ',';
        }

        $sql = "REPLACE INTO `${tableName}` (${columns}) VALUES ${values};";

        return $sql;
    }

    /**
     * [STATIC] Builds a SQL WHERE clause from an array.
     * If a key is specified, the key is used at the field name and the value
     * as a comparison. If a key is not used, the value is used as the clause.
     *
     * @param array $whereArray  An associative array containing the column
     *                           names as keys and values as data. The values
     *                           must be SQL ready (i.e. quotes around
     *                           strings, formatted dates, ect)
     *
     * @return string Returns a string containing the SQL WHERE clause
     */
    private static function buildSqlWhereClause($whereArray)
    {
        $sql   = '';
        $where = ' WHERE ';
        foreach ($whereArray as $key => $value) {

            if (is_string($key)) {
                $sql .= $where . '`' . $key . '` = ' . $value;
            }
            else {
                $sql .= $where . ' ' . $value;
            }
            $where = ' AND ';
        }

        return $sql;
    }

    /**
     * [STATIC] Converts any value of any datatype into boolean (true or false)
     *
     * @param mixed $value Value to analyze for TRUE or FALSE
     *
     * @return boolean Returns TRUE or FALSE
     */
    private static function getBooleanValue($value)
    {
        if (gettype($value) === 'boolean') {
            if ($value === true) {
                return true;
            }
            else {
                return false;
            }
        }
        elseif (is_numeric($value)) {
            if ($value > 0) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            $cleaned = strtoupper(trim($value));

            if ($cleaned === 'ON') {
                return true;
            }
            elseif ($cleaned === 'SELECTED' || $cleaned === 'CHECKED') {
                return true;
            }
            elseif ($cleaned === 'YES' || $cleaned === 'Y') {
                return true;
            }
            elseif ($cleaned === 'TRUE' || $cleaned === 'T') {
                return true;
            }
            else {
                return false;
            }
        }
    }

    /**
     * [STATIC] Determines if a value of any data type is a date PHP can convert
     *
     * @param date /string $value
     *
     * @return boolean Returns TRUE if value is date or FALSE if not date
     */
    private static function isDate($value)
    {
        $date = date('Y', strtotime($value));
        if ($date === '1969' || $date === '') {
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Clears the internal variables from any error information
     *
     */
    private function resetError()
    {
        $this->error_desc   = '';
        $this->error_number = 0;
    }

    /*
     * Sets the local variables with the last error information
     *
     * @param string  $errorMessage The error description
     * @param integer $errorNumber  The error number
     */
    private function setError($errorMessage = '', $errorNumber = 0)
    {
        try {
            if (strlen($errorMessage) > 0) {
                $this->error_desc = $errorMessage;
            }
            elseif ($this->isConnected()) {
                $this->error_desc = mysqli_error($this->link);
            }
            else {
                $this->error_desc = mysqli_error($this->link);
            }


            if ($errorNumber !== 0) {
                $this->error_number = $errorNumber;
            }
            elseif ($this->isConnected()) {
                $this->error_number = @mysqli_errno($this->link);
            }
            else {
                $this->error_number = @mysqli_errno($this->link);
            }

        } catch (Exception $e) {
            $this->error_desc   = $e->getMessage();
            $this->error_number = -999;
        }


        if ($this->ThrowExceptions) {
            throw new Exception($this->error_desc);
        }
    }

    /**
     * [STATIC] Builds a comma delimited list of columns for use with SQL
     *
     * @param array   $columns   An array containing the column names.
     * @param boolean $addQuotes (Optional) TRUE to add quotes
     * @param boolean $showAlias (Optional) TRUE to show column alias
     *
     * @return string Returns the SQL column list
     */
    private static function buildSqlColumns($columns, $addQuotes = true, $showAlias = true)
    {
        $quote = '';
        if ($addQuotes) {
            $quote = '`';
        }


        switch (gettype($columns)) {
            case 'array':
                $sql  = '';
                $coma = '';

                foreach ($columns as $key => $value) {
                    // Build the columns
                    $sql .= $coma . $quote . $value . $quote;

                    if ($showAlias && is_string($key)) {
                        $sql .= " AS '$key'";
                    }
                    $coma = ', ';
                }

                //DEBUG// error_log('<<==== QUERY ====>>' . PHP_EOL . $sql);
                return $sql;
                break;

            case 'string':
                return $quote . $columns . $quote;
                break;

            default:
                return false;
                break;
        }
    }
}

