<?php

define('FILTER_MODE_NORMAL', 0);
define('FILTER_MODE_APPEND', 1);
define('FILTER_DEFAULT_FILTER', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_');

/**
 * Returns a string that only contains the characters specified in $filter or the default filter
 * Note: This is NOT the main filter handler, it's a simple character filter
 *
 * @param string $text
 * @param string $filter
 * @return string
 */
function FilterText( $text, $filter = '', $mode = FILTER_MODE_NORMAL )
{
	// Setup filter
	$defaultFilter = FILTER_DEFAULT_FILTER;

	if ( $filter )
	{
		if ( $mode === FILTER_MODE_APPEND )
		{
			$filter .= $defaultFilter;
		}
	}
	else
	{
		$filter = $defaultFilter;
	}

	$validChars = preg_split('//', $filter, -1, PREG_SPLIT_NO_EMPTY);
	$valid = false;
	$filteredText = '';

	$textlen = strlen($text);
	$filterCount = count($validChars);

	for ( $index = 0; $index < $textlen; $index++ )
	{
		$char = substr($text, $index, 1);
		for ( $validCharIndex = 0; $validCharIndex < $filterCount; $validCharIndex++ )
		{
			if ( $char === $validChars[$validCharIndex] )
			{
				$valid = true;
				break;
			}
		}
		if ( $valid )
		{
			$filteredText .= $char;
			$valid = false;
		}
	}

	return $filteredText;
}

/**
 * Text filter management
 *
 * @author Nicholas R. Grant
 * @version 1.0 rev 0
 * @copyright NRGsoft (c) 2003-2012
 */
class MonkeyFilter
{
	private $_filter;
	
	const HTML_ENTITY = 1;
	const MYSQL_SAFE = 2; // Active MySQL connection needed
	const DISABLE_NULL = 4;
	const HTML_BRACKETS = 8;
	const HTML_SLASHES = 16;
	const DISABLE_MULTI_BYTE = 32; // UTF-8 -> ASCII
	const HTML_DOLLAR = 64;
	
	const DEFAULT_FILTER = 95; // HTML_ENTITY | MYSQL_SAFE | DISABLE_NULL | HTML_BRACKETS | HTML_SLASHES | HTML_DOLLAR or ~DISABLE_MULTI_BYTE
	
	public static $Instance;
	
	public function __construct( $filter )
	{
		$this->_filter = $filter;
	}
	
	/**
	 * Create new instance of class with default filter
	 * 
	 * @param int $filter
	 * @return MonkeyFilter
	 */
	public static function Create( $filter = MonkeyFilter::DEFAULT_FILTER )
	{
		return new MonkeyFilter($filter);
	}
	
	/**
	 * Return singleton instance of class
	 * 
	 * @return MonkeyFilter
	 */
	public static function Get()
	{
		if ( !self::$Instance )
			self::$Instance = self::Create();
		return self::$Instance;
	}
	
	/**
	 * Return singleton instance of class
	 * 
	 * @return MonkeyFilter
	 */
	public static function Instance()
	{
		if ( !self::$Instance )
			self::$Instance = self::Create();
		return self::$Instance;
	}
	
	/**
	 * Set text filter
	 * 
	 * @param int $filter
	 * @return void
	 */
	public function SetFilter( $filter )
	{
		$this->_filter = $filter;
	}
	
	/**
	 * Add filter to text filter
	 * 
	 * @param int $filter
	 * @return void
	 */
	public function AddFilter( $filter )
	{
		$this->_filter = $this->_filter | $filter;
	}
	
	/**
	 * Remove filter from text filter
	 * 
	 * @param int $filter
	 * @return void
	 */
	public function RemoveFilter( $filter )
	{
		$this->_filter = $this->_filter & (~$filter);
	}
	
	/**
	 * Text will be filtered through filter settings and returned
	 * 
	 * @param string $input
	 * @return string
	 */
	public function FilterText( $input )
	{
		if ( $this->_filter & self::DISABLE_MULTI_BYTE ) $input = $this->filterDisableMultibyte($input);
		if ( $this->_filter & self::DISABLE_NULL ) $input = $this->filterNull($input);
		if ( $this->_filter & self::HTML_ENTITY ) $input = $this->filterHtmlEntities($input);
		if ( $this->_filter & self::HTML_DOLLAR ) $input = $this->filterHtmlDollar($input);
		if ( $this->_filter & self::HTML_SLASHES ) $input = $this->filterHtmlSlashes($input);
		if ( $this->_filter & self::HTML_BRACKETS ) $input = $this->filterHtmlBrackets($input);
		if ( $this->_filter & self::MYSQL_SAFE ) $input = $this->filterMysqlSafe($input);
		return $input;
	}
	
	/**
	 * Filter out \00 0x00, A.K.A. null.
	 * 
	 * @param string $input
	 * @return string
	 */
	private function filterNull( $input )
	{
		$char = '';
		
		for ( $cnt = 0; $cnt < strlen($input); $cnt++ )
		{
			$char = ord(substr($input, $cnt, 1));
			
			if ( !$char )
			{
				$input = substr($input, 0, $cnt) . substr($input, $cnt + 1);
			}
		}
		
		return $input;
	}
	
	/**
	 * Filter through HTML Entities ignoring brackets.
	 * 
	 * @param string $input
	 */
	private function filterHtmlEntities( $input )
	{
		$ret = '';
		if ( $this->_filter & self::DISABLE_MULTI_BYTE )
		{
			//php 5.4.0 return htmlentities($input, ENT_QUOTES | ENT_DISALLOWED | ENT_XHTML, 'ASCII');
			$ret = htmlentities($input, ENT_QUOTES, 'ASCII');
		}
		else
		{
			//php 5.4.0 return htmlentities($input, ENT_QUOTES | ENT_DISALLOWED | ENT_XHTML, 'UTF-8');
			$ret = htmlentities($input, ENT_QUOTES, 'UTF-8');
		}
		$ret = str_replace(array('&lt;','&gt;'), array('<','>'), $ret);
		
		return $ret;
	}
	
	/**
	 * Disable multibyte encoding by forcing incoming character set to ASCII
	 * 
	 * @param	string $input
	 * @return	string
	 */
	private function filterDisableMultibyte( $input )
	{
		return mb_convert_encoding($input, 'ASCII');
	}
	
	/**
	 * Force incoming character set to UTF-8
	 * 
	 * @deprecated 2/10/2012
	 * @param	string $input
	 * @return	string
	 */
	private function filterForceMultibyte( $input )
	{
		return mb_convert_encoding($input, 'UTF-8');
	}
	
	/**
	 * Transform slashes (back and forward and dash) to HTML entities
	 * 
	 * @param	string $input
	 * @return	string
	 */
	private function filterHtmlSlashes( $input )
	{
		$filterList = new MonkeyStack();
		$filterList->Push(45, 47, 92);
		$char = '';
		
		for ( $inputIndex = 0; $inputIndex < strlen($input); $inputIndex++ )
		{
			$char = ord(substr($input, $inputIndex, 1));
			for ( $filterIndex = 0; $filterIndex < $filterList->Count(); $filterIndex++ )
			{
				if ( $char == $filterList->Get($filterIndex) )
				{
					$input = substr($input, 0, $inputIndex) . '&#' . $filterList->Get($filterIndex) . ';' . substr($input, $inputIndex + 1);
					break;
				}
			}
		}
		
		return $input;
	}
	
	/**
	 * Transforms all brackets ({, }, (, ), <, >, [, ]) to HTML entities
	 * 
	 * @param string $input
	 * @return string
	 */
	private function filterHtmlBrackets( $input )
	{
		$filterList = new MonkeyStack();
		$filterList->Push(40, 41, 60, 62, 91, 93, 123, 125);
		$char = '';
		
		for ( $inputIndex = 0; $inputIndex < strlen($input); $inputIndex++ )
		{
			$char = ord(substr($input, $inputIndex, 1));
			for ( $filterIndex = 0; $filterIndex < $filterList->Count(); $filterIndex++ )
			{
				if ( $char == $filterList->Get($filterIndex) )
				{
					$input = substr($input, 0, $inputIndex) . '&#' . $filterList->Get($filterIndex) . ';' . substr($input, $inputIndex + 1);
					break;
				}
			}
		}
		
		return $input;
	}
	
	/**
	 * Filter for MySQL safe. It is recommended that other filters also be used to ensure safety.
	 * This filter also requires an active MySQL connection or else it will skip this safety
	 * measure.
	 * 
	 * @param string $input $input
	 * @return string
	 */
	private function filterMysqlSafe( $input )
	{
		if ( @mysqli_real_escape_string(MonkeyDatabase::DBLink(), $input) )
			return mysqli_real_escape_string(MonkeyDatabase::DBLink(), $input);
		return $input;
	}
	
	/**
	 * Filter $ symbol to HTML entity
	 * 
	 * @param string $input
	 * @return string
	 */
	private function filterHtmlDollar( $input )
	{
		$dollar = '&#36;';
		$char = '';
		
		for ( $cnt = 0; $cnt < strlen($input); $cnt++ )
		{
			$char = ord(substr($input, $cnt, 1));
			
			if ( $char == 36 )
			{
				$input = substr($input, 0, $cnt) . $dollar . substr($input, $cnt + 1);
			}
		}
		
		return $input;
	}
}

if ( !isset($filter) )
	$filter = MonkeyFilter::Instance();
