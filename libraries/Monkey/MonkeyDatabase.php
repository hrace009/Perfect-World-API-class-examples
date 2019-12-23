<?php
if ( !defined('ROOT') ) exit;

/*require_once(ROOT . '/libs/pdo2mysql/MySQL_Definitions.php');
require_once(ROOT . '/libs/pdo2mysql/MySQL.php');
require_once(ROOT . '/libs/pdo2mysql/MySQL_Functions.php');*/

define('QueryReturnSuccess', 0);
define('QueryReturnResults', 1);
define('QueryReturnSelf', 2);

define('QueryForceExecution', true);
define('QueryAllowCacheExecution', false);

# these flags are taken straight out of mysql_com.h
define('CLIENT_LONG_PASSWORD', 1);	/* new more secure passwords */
define('CLIENT_FOUND_ROWS', 2);	/* Found instead of affected rows */
define('CLIENT_LONG_FLAG', 4);	/* Get all column flags */
define('CLIENT_CONNECT_WITH_DB', 8);	/* One can specify db on connect */
define('CLIENT_NO_SCHEMA', 16);	/* Don't allow database.table.column */
define('CLIENT_COMPRESS', 32);	/* Can use compression protocol */
define('CLIENT_ODBC', 64);	/* Odbc client */
define('CLIENT_LOCAL_FILES', 128);	/* Can use LOAD DATA LOCAL */
define('CLIENT_IGNORE_SPACE', 256);	/* Ignore spaces before '(' */
define('CLIENT_PROTOCOL_41', 512);	/* New 4.1 protocol */
define('CLIENT_INTERACTIVE', 1024);	/* This is an interactive client */
define('CLIENT_SSL', 2048);	/* Switch to SSL after handshake */
define('CLIENT_IGNORE_SIGPIPE', 4096);    /* IGNORE sigpipes */
define('CLIENT_TRANSACTIONS', 8192);	/* Client knows about transactions */
define('CLIENT_RESERVED', 16384);   /* Old flag for 4.1 protocol  */
define('CLIENT_SECURE_CONNECTION', 32768);  /* New 4.1 authentication */
define('CLIENT_MULTI_STATEMENTS', 65536); /* Enable/disable multi-stmt support */
define('CLIENT_MULTI_RESULTS', 131072); /* Enable/disable multi-results */
define('CLIENT_PS_MULTI_RESULTS', 262144); /* Multi-results in PS-protocol */


/**
 * MySQL database query controller (Internal Use Only)
 * 
 * @author Nicholas R. Grant
 * @version 2.0 rev 3
 * @copyright NRGsoft (c) 2003-2015
 */
final class MonkeyDatabaseQuery
{
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var MonkeyDatabaseQuery */
	private static $Instance;
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var MonkeyDatabaseData */
	private $_data;
	
	/**
	 * @var int */
	private $_queryIndex;
	
	/**
	 * @var string */
	private $_query;
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @access private */
	private function __construct(){}
	
	
	///////////////////////////////////////////////////////////////////////////
	public static function Initialize()
	{
		if ( !self::$Instance )
			self::$Instance = new MonkeyDatabaseQuery();
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * (Note: No longer using lazy singleton, so it should be minutely faster)
	 * 
	 * @param mixed $queryOrQueryIndex
	 * @return MonkeyDatabaseQuery
	 */
	public static function Instance( $queryOrQueryIndex = -1 )
	{
		self::$Instance->_SetIndex($queryOrQueryIndex);
		return self::$Instance;
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Internal Structure Use Only!
	 * 
	 * @param MonkeyDatabaseData $data
	 */
	public function PassData( $data )
	{
		if ( is_object($data) && get_class($data) === 'MonkeyDatabaseData' )
			$this->_data = $data;
		
		if ( is_null($this->_data) )
			die('&lt;MonkeyDatabaseQuery&gt;Error: Data not initialized');
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @desc Perform a query on the database. If the query has already been
	 * 			performed, it will get the results from that query that has
	 * 			already been executed unless the 'forceExecution' parameter
	 * 			is true. If the 'returnResults' parameter is true, it will
	 * 			return an array of data from the query. Otherwise the success
	 * 			of the query will be returned.
	 *
	 * @param string $queryOrQueryIndex <br>
	 * 			Query is the query to be executed on the database or a index to a query
	 * 			already executed.
	 * @param int $returnType = QueryReturnResults [optional]<br>
	 * 			If return type is QueryReturnResults, an array of results will be returned.<br><br>
	 * 			If return type is QueryReturnSuccess, the execution success of the query will be
	 * 			returned.<br><br>
	 * 			If return type is QueryReturnSelf, this object type will be returned.
	 * @param bool $executionType = false [optional]<br>
	 * 			If force execution is true, the SQL query will be performed even if
	 * 			it's already been executed one or more times before.<br>
	 * 			If force execution is false and the SQL query has been executed before
	 * 			it will return the results or success of the query.
	 * @return mixed
	 */
	public function Execute( $returnType = QueryReturnResults, $executionType = QueryAllowCacheExecution )
	{
		if ( !$executionType )
		{
			$this->_CheckIfExists();
			
			# yes, these need to be seperate statements
			if ( !is_null($this->_data->queries[$this->_queryIndex]) )
			{
				return $this->_FromExecution($returnType);
			}
		}
		
		$index =& $this->_queryIndex;
		$this->_data->executionTime[$index] = microtime(true);
		$this->_data->queries[$index] = @mysqli_query(MonkeyDatabase::DBLink(), $this->_query);
		$this->_data->executionTime[$index] = microtime(true) - $this->_data->executionTime[$index];
		$this->_data->totalExecutionTime += $this->_data->executionTime[$index];
		
		$this->_CacheResults();
		$this->_data->rowsAffected[$index] = @mysqli_affected_rows(MonkeyDatabase::DBLink());
		
		return $this->_FromExecution($returnType);
	}
	
	/**
	 * Return data depending on type
	 * 
	 * @param int $type
	 * @return mixed
	 */
	private function _FromExecution( $type )
	{
		switch ( $type )
		{
			case QueryReturnResults:
				return $this->Results();
			case QueryReturnSelf:
				return $this;
		}
		
		return $this->Succeeded();
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns true or false on whether or not the query succeeded
	 * 
	 * @return bool
	 */
	public function Succeeded()
	{
		return (bool)$this->_data->queries[$this->_queryIndex];
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns true or false on whether or not the query succeeded
	 * 
	 * @return bool
	 */
	public function Failed()
	{
		return !(bool)$this->_data->queries[$this->_queryIndex];
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns an array of result arrays from a query
	 * 
	 * @return array string
	 */
	public function Results()
	{
		return $this->_data->cachedResults[$this->_queryIndex];
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @desc Returns a single result array from the current result row or the row
	 * specified in row.
	 * 
	 * @param int $row = null [optional]
	 * @return array string
	 */
	public function Result( $row = null )
	{
		$resRow = $this->_data->resultRow[$this->_queryIndex];
		$row = is_int($row) ? (int)$row : ($resRow < 0 ? -1 : $resRow);
		return $row > -1 ? $this->_data->cachedResults[$this->_queryIndex][$row] : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns truth of next row existing while incrementing the result row counter.
	 * 
	 * @param boolean $returnNumber = false [optional]<br>
	 * 			If true, the next result row number will be returned, otherwise true is returned
	 * 			if the row number can be increased.
	 * @return mixed
	 */
	public function NextRow( $returnNumber = false )
	{
		$resRow = ++$this->_data->resultRow[$this->_queryIndex];
		return $resRow < $this->RowsFound() ? ($returnNumber ? $resRow : true) : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns truth of last row existing while decrementing the result row counter.
	 *
	 * @param boolean $returnNumber = false [optional]<br>
	 * 			If true, the next result row number will be returned, otherwise true is returned
	 * 			if the row number can be decreased.
	 * @return mixed
	 */
	public function LastRow( $returnNumber = false )
	{
		$resRow = --$this->_data->resultRow[$this->_queryIndex];
		return $resRow > -1 ? ($returnNumber ? $resRow : true) : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns a single result array from the next result row.
	 * 
	 * @return mixed
	 */
	public function NextResult()
	{
		$nextRow = $this->_data->resultRow[$this->_queryIndex] + 1; 
		return $nextRow < $this->RowsFound() ? $this->Result($nextRow) : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns a single result array from the next result row.
	 *
	 * @return mixed
	 */
	public function LastResult()
	{
		$lastRow = $this->_data->resultRow[$this->_queryIndex] - 1;
		return $lastRow > -1 ? $this->Result($lastRow) : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns a single result array from the first result row.
	 *
	 * @return mixed
	 */
	public function FirstResult()
	{
		return 0 < $this->RowsFound() ? $this->Result(0) : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Reset the result row counter for re-looping.
	 * 
	 */
	public function ResetResultRow()
	{
		$this->_data->resultRow[$this->_queryIndex] = -1;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get the value of an item by name in the current result.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function ResultItem( $name )
	{
		$result = $this->Result();
		return is_array($result) && isset($result[$name]) ? $result[$name] : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get the value of an item by name in the next result.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function NextResultItem( $name )
	{
		$result = $this->NextResult();
		return is_array($result) && isset($result[$name]) ? $result[$name] : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get the value of an item by name in the last result.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function LastResultItem( $name )
	{
		$result = $this->LastResult();
		return is_array($result) && isset($result[$name]) ? $result[$name] : false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get the value of an item by name in the first result.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function FirstResultItem( $name )
	{
		$result = $this->FirstResult();
		return is_array($result) && isset($result[$name]) ? $result[$name] : false;
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns true if empty result set.
	 * 
	 * @return bool
	 */
	public function ResultEmpty()
	{
		return $this->RowsFound() <= 0;
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get raw input SQL string
	 * 
	 * @return string
	 */
	public function RawSQL()
	{
		return $this->_data->rawSQL[$this->_queryIndex];
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns the number of rows found
	 * 
	 * @return	int
	 */
	public function RowsFound()
	{
		return count($this->_data->cachedResults[$this->_queryIndex]);
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Return the number of affected rows
	 * 
	 * @return int
	 */
	public function RowsAffected()
	{
		return $this->_data->rowsAffected[$this->_queryIndex];
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get execution time of a query
	 * 
	 * @return double
	 */
	public function ExecutionTime()
	{
		return $this->_data->executionTime[$this->_queryIndex];
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Free up memory from query including cached results.
	 */
	public function FreeMemory()
	{
		$index =& $this->_queryIndex;
		
		@mysqli_free_result(MonkeyDatabase::DBLink(), $this->_data->queries[$index]);
		
		$this->_data->rawSQL[$index] = null;
		$this->_data->queries[$index] = null;
		$this->_data->cachedResults[$index] = null;
		$this->_data->executionTime[$index] = null;
		$this->_data->rowsAffected[$index] = null;
		$this->_data->resultRow[$index] = -1;
	}
	
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Return current query index
	 * 
	 * @return int
	 */
	public function Index()
	{
		return $this->_queryIndex;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Return current row number
	 * 
	 * @return int
	 */
	public function RowNumber()
	{
		return $this->_data->resultRow[$this->_queryIndex];
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Checks if query already exists and creates references to them in this query
	 *
	 * @access private
	 */
	private function _CheckIfExists()
	{
		if ( is_null($this->_data->queries[$this->_queryIndex]) )
		{
			$queries =& $this->_data->rawSQL;
			$numOfQueries = count($queries);
			$index =& $this->_queryIndex;
	
			for ( $queryIndex = 0; $queryIndex < $numOfQueries; $queryIndex++ )
			{
				if ( $queryIndex === $index ) Continue;
					
				if ( $queries[$queryIndex] == $this->_query )
				{
					if ( !is_null($this->_data->queries[$queryIndex]) )
					{
						$this->_data->queries[$index] =& $this->_data->queries[$queryIndex];
						$this->_data->cachedResults[$index] =& $this->_data->cachedResults[$queryIndex];
						$this->_data->executionTime[$index] =& $this->_data->executionTime[$queryIndex];
						$this->_data->rowsAffected[$index] =& $this->_data->rowsAffected[$queryIndex];
					}
				}
			}
		}
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @desc Get (likely) valid index<br><br>
	 * <b>Note:</b> There's a possibility a wrong index could be gotten if 0 is put
	 * in while there have been no queries.
	 * 
	 * @param int $index
	 * @return int
	 */
	private function _GetIndex( $index )
	{
		return $index < 0 ? count($this->_data->rawSQL) + $index : $index;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @desc Set query index by either direct index input or add a new query and set the
	 * 			query index to that index.
	 * @access private
	 * @param mixed $queryOrQueryIndex
	 */
	private function _SetIndex( $queryOrQueryIndex )
	{
		if ( !is_null($queryOrQueryIndex) )
		{
			if ( is_int($queryOrQueryIndex) )
			{
				# get real index
				$queryOrQueryIndex = $this->_GetIndex(intval($queryOrQueryIndex));
				
				if ( $this->_queryIndex !== $queryOrQueryIndex )
				{
					$this->_queryIndex = $queryOrQueryIndex;
					$this->ResetResultRow();
				}
			}
			elseif ( is_string($queryOrQueryIndex) )
			{
				$this->_data->rawSQL[] = trim($queryOrQueryIndex);
				$this->_data->queries[] = null;
				$this->_data->cachedResults[] = null;
				$this->_data->executionTime[] = null;
				$this->_data->rowsAffected[] = null;
				$this->_data->resultRow[] = -1;
				
				$this->_queryIndex = $this->_GetIndex(-1);
			}
			
			$this->_query = $this->_data->rawSQL[$this->_queryIndex];
		}
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Cache query results
	 */
	private function _CacheResults()
	{
		$queries =& $this->_data->queries;
		
		if ( $this->_data->cachedResults[$this->_queryIndex] === null )
		{
			if ( @mysqli_num_rows($queries[$this->_queryIndex]) > 0 )
			{
				while ( $currentRow = @mysqli_fetch_assoc($queries[$this->_queryIndex]) )
				{
					$results[] = $currentRow;
				}
				$this->_data->cachedResults[$this->_queryIndex] = $results;
			}
		}
	}
}




/**
 * MySQL database data management (Internal Use Only)
 *
 * @author Nicholas R. Grant
 * @version 2.0 rev 0
 * @copyright NRGsoft (c) 2003-2012
 */
final class MonkeyDatabaseData
{
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var array */
	public $cachedResults = array(),
		   $queries = array(),
		   $rowsAffected = array(),
		   $executionTime = array(),
		   $rawSQL = array(),
		   $resultRow = array();
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var float */
	public $totalExecutionTime = 0.0;
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var boolean */
	private static $StopNew = false;
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @access private */
	private function __construct(){}
	
	///////////////////////////////////////////////////////////////////////////
	public static function Instance()
	{
		if ( self::$StopNew )
			return;
		
		self::$StopNew = true;
		return new MonkeyDatabaseData();
	}
}




/**
 * mysqli database controller
 *
 * @author Nicholas R. Grant
 * @version 2.0 rev 0
 * @copyright NRGsoft (c) 2003-2012
 */
class MonkeyDatabase
{
	///////////////////////////////////////////////////////////////////////////
	protected $_dbLink, $_dbSelected;
	protected $_hasNoConnection = true;
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var MonkeyDatabaseData */
	private $_data;
	
	/**
	 * @var MonkeyDatabaseQuery */
	private $_queryController;
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * @var MonkeyDatabase */
	private static $Instance;
	
	///////////////////////////////////////////////////////////////////////////
	public static function Initialize()
	{
		if ( !self::$Instance )
			self::$Instance = new MonkeyDatabase();
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns a MonkeyDatabase with a database connection
	 * 
	 */
	private function __construct()
	{
		$this->_data = MonkeyDatabaseData::Instance();
		$this->_queryController = MonkeyDatabaseQuery::Instance(null);
		$this->_queryController->PassData($this->_data);
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Returns a new MonkeyDatabase with a database connection
	 * 
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $dbname
	 * @param int $flags
	 * @return MonkeyDatabase
	 */
	public static function Instance( $host = '', $username = '', $password = '', $dbname = '', $newLink = false, $flags = 0 )
	{
		if ( $newLink || self::$Instance->_hasNoConnection )
			self::$Instance->_Connect($host, $username, $password, $dbname, $newLink, $flags);
		return self::$Instance;
	}
	
	/**
	 * Returns the Database Link created by the MySQL php based mysqli adapter.
	 */
	public static function DBLink()
	{
		return self::$Instance->_dbLink;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get number of queries executed
	 * 
	 * @see MonkeyDatabase::QueriesExecuted()
	 * @return int
	 */
	public function Count()
	{
		return count( $this->_data->queries );		
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get number of queries executed
	 *
	 * @return int
	 */
	public function QueriesExecuted()
	{
		return count( $this->_data->queries );
	}
	
	public static function ErrorMessage()
	{
		return @mysqli_error(self::$Instance->_dbLink);
	}
	
	public static function ErrorNumber()
	{
		return @mysqli_errno(self::$Instance->_dbLink);
	}
	
	public static function ConnectErrorMessage()
	{
		return mysqli_connect_error();
	}
	
	public static function ConnectErrorNumber()
	{
		return mysqli_connect_errno();
	}
	
	public static function CloseConnection()
	{
		$ret = @mysqli_close(self::$Instance->_dbLink);
		self::$Instance = null;
		return $ret;
	}
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Attempts to connect to the database
	 *
	 * @param	string	$host
	 * @param	string	$username
	 * @param	string	$password
	 * @param	string	$dbname
	 * @access	private
	 * @return	bool
	 */
	private function _Connect( $host, $username, $password, $dbname, $port = 8080, $socket = 0 )
	{
		$this->_dbLink = @mysqli_connect($host, $username, $password);
		
		if ( $this->_dbLink )
		{
			$this->_dbSelected = @mysqli_select_db($this->_dbLink, $dbname);
			
			if ( $this->_dbSelected )
			{
				$this->_hasNoConnection = false;
				return true;
			}
		}
		die('&lt;MonkeyDatabase&gt;Error: An attempt made to establish a connection to the database failed!');
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Retrieves a MonkeyDatabaseQuery object to manipulate the query entered or index put in
	 * 
	 * @param mixed $queryOrQueryIndex = -1 [optional]<br>
	 * 			Query is a query string to be manipulated inside the database.<br><br>
	 * 			Query Index is a reference to the queries executed in order starting from 0<br>
	 * 			If a number below 0 is set, it will use the last query index plus one and the
	 * 			input index. That's to say, if you put no parameters in the Query() function,
	 * 			it will by defualt access the last query put in.<br><br>
	 * 			e.g. last query index + 1 + index = last query<br>
	 * 			Where last query index is 5, and index is -1<br>
	 * 			5 + 1 + (-1) = 5
	 * @return MonkeyDatabaseQuery
	 */
	public function Query( $queryOrQueryIndex = -1 )
	{
		error_reporting(E_ALL);
		return MonkeyDatabaseQuery::Instance($queryOrQueryIndex);
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Get total execution time of all queries
	 *
	 * @return float
	 */
	public function TotalExecutionTime()
	{
		return $this->_data->totalExecutionTime;
	}
}

MonkeyDatabaseQuery::Initialize();
MonkeyDatabase::Initialize();

///////////////////////////////////////////////////////////////////////////
/**
 * Attempts to connect to the database
 * 
 * @param string $host
 * @param string $username
 * @param string $password
 * @param string $dbname
 * @param bool $newLink
 * @param int $flags
 * @return MonkeyDatabase
 */
function CreateConnection( $host, $username, $password, $dbname, $newLink = false, $flags = 0 )
{
	return MonkeyDatabase::Instance($host, $username, $password, $dbname, $newLink, $flags);
}

///////////////////////////////////////////////////////////////////////////
/**
 * Retrieves a MonkeyDatabaseQuery object to manipulate the query entered or index put in
 * 
 * @param mixed $queryOrQueryIndex = -1 [optional]<br>
 * 			Query is a query string to be manipulated inside the database.<br><br>
 * 			Query Index is a reference to the queries executed in order starting from 0<br>
 * 			If a number below 0 is set, it will use the last query index plus one and the
 * 			input index. That's to say, if you put no parameters in the Query() function,
 * 			it will by defualt access the last query put in.<br><br>
 * 			e.g. last query index + 1 + index = last query<br>
 * 			Where last query index is 5, and index is -1<br>
 * 			5 + 1 + (-1) = 5
 * @return MonkeyDatabaseQuery
 */
function Query( $queryOrQueryIndex = -1 )
{
	return MonkeyDatabaseQuery::Instance($queryOrQueryIndex);
}


