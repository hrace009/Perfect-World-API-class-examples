<?php

/**
 * MySQL database and query managing class
 *
 * @version 1.2
 * @author Nicholas R. Grant
 */
class MonkeyDatabase extends MonkeyStack
{
    /**
     *
     * @var MonkeyDatabase
     */
    public static $Instance;
    protected $_databaseConnection,
        $_databaseSelected,
        $_cachedResults = array(),
        $_queries = array(),
        $_rowsAffected = array();

    /**
     * Return singleton instance of class
     *
     * @return MonkeyDatabase
     */
    public static function Instance($host = '', $username = '', $password = '', $dbname = '')
    {
        if (!self::$Instance)
            self::$Instance = self::Create($host, $username, $password, $dbname);
        return self::$Instance;
    }

    /**
     * Returns a new MonkeyDatabase with a database connection
     *
     * @param    string $host
     * @param    string $username
     * @param    string $password
     * @param    string $dbname
     * @return    MonkeyDatabase
     */
    static function Create($host, $username, $password, $dbname)
    {
        $self = new MonkeyDatabase();
        if (!$self->CreateConnection($host, $username, $password, $dbname)) {
            die('&lt;MonkeyDatabase&gt;Error: An attempt made to establish a connection to the database failed!');
        }
        return $self;
    }

    /**
     * Attempts to connect to the database
     *
     * @access    protected
     * @param    string $host
     * @param    string $username
     * @param    string $password
     * @param    string $dbname
     * @return    bool
     */
    public function CreateConnection($host, $username, $password, $dbname)
    {
        $this->_databaseConnection = @mysql_connect($host, $username, $password);

        if ($this->_databaseConnection) {
            $this->_databaseSelected = @mysql_select_db($dbname);

            return (boolean)$this->_databaseSelected;
        }
        return false;
    }

    /**
     * Will perform a query and return the results
     *
     * @param    string $query
     * @param    boolean $returnResults
     * @return    array string
     */
    public function Query($query, $returnResults = true)
    {
        $obj = $this->Objects();
        $skip = false;
        for ($cnt = 0; $cnt < count($obj); $cnt++) {
            if ($obj[$cnt] == $query) {
                return $this->GetResults($cnt);
            }
        }

        $index = $this->Count();
        $this->Push($query);
        $this->_queries[$index] = @mysql_query($this->Get($index));
        $this->_rowsAffected[$index] = @mysql_affected_rows();

        if ($returnResults)
            return $this->GetResults();
        return $this->_queries[$index];
    }

    /**
     * Returns the result from a stored query
     *
     * @param    int $index
     * @return    array string
     */
    public function GetResults($index = 0)
    {
        $index = $this->_GetIndex($index);
        $results = array();

        //echo '<pre>'.print_r($this->_cachedResults,true).'</pre>';
        if (!isset($this->_cachedResults[$index])) {
            if (!$this->IsEmptyResult($index)) {
                while ($currentRow = @mysql_fetch_array($this->_queries[$index])) {
                    $results[] = $currentRow;
                }
                $this->_cachedResults[$index] = $results;
            }
        } else {
            $results = $this->_cachedResults[$index];
        }

        return $results;
    }

    /**
     * Get valid index
     *
     * @param int $index
     * @return int
     */
    private function _GetIndex($index)
    {
        if (empty($index)) return $this->Count() - 1;
        return $index;
    }

    /**
     * Returns true if empty result set.
     *
     * @param int $index
     * @return bool
     */
    public function IsEmptyResult($index = 0)
    {
        return $this->GetRowCount($this->_GetIndex($index)) <= 0;
    }

    /**
     * Returns the number of rows for the given query
     *
     * @param    int $index
     * @return    int
     */
    public function GetRowCount($index = 0)
    {
        return ceil(@mysql_num_rows($this->_queries[$this->_GetIndex($index)]));
    }

    /**
     * Returns the query statement at $index
     *
     * @see MonkeyStack::Get()
     * @param    int $index
     * @return    mysql_query
     */
    public function Get($index = 0)
    {
        return $this->_list[$this->_GetIndex($index)];
    }

    /**
     * Returns true or false on whether or not the input query was malformed
     *
     * @param int $index
     * @return bool
     */
    public function IsMalformedQuery($index = 0)
    {
        return !$this->_queries[$this->_GetIndex($index)];
    }

    /**
     * Alias of Get, see Get for more information
     *
     * @param    int $index
     * @see    MonkeyDatabase::Get()
     */
    public function GetQuery($index = 0)
    {
        return $this->Get($index);
    }

    /**
     * Return the number of affected rows
     *
     * @param int $index
     * @return int
     */
    public function RowsAffected($index = 0)
    {
        return $this->_rowsAffected[$this->_GetIndex($index)];
    }
}
