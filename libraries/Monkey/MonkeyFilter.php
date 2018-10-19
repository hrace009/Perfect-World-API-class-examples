<?php
// Everything is best viewed in Zend Studio for Eclipse

// FU FU FU FU FU FU FU FU!
class MonkeyFilter
{
    const HTML_ENTITY = 1;
    const MYSQL_SAFE = 2;
    const DISABLE_NULL = 4; // Active MySQL connection needed
    const HTML_BRACKETS = 8;
    const HTML_SLASHES = 16;
    const DISABLE_MULTI_BYTE = 32;
    const HTML_DOLLAR = 64; // UTF-8 -> ASCII
    const DEFAULT_FILTER = 95;
    public static $Instance; // HTML_ENTITY | MYSQL_SAFE | DISABLE_NULL | HTML_BRACKETS | HTML_SLASHES | HTML_DOLLAR or ~DISABLE_MULTI_BYTE
    private $_filter;

    public function __construct($filter)
    {
        $this->_filter = $filter;
    }

    /**
     * Return singleton instance of class
     *
     * @return MonkeyFilter
     */
    public static function Get()
    {
        if (!self::$Instance)
            self::$Instance = self::Create();
        return self::$Instance;
    }

    /**
     * Create new instance of class with default filter
     *
     * @param int $filter
     * @return MonkeyFilter
     */
    public static function Create($filter = MonkeyFilter::DEFAULT_FILTER)
    {
        return new MonkeyFilter($filter);
    }

    /**
     * Return singleton instance of class
     *
     * @return MonkeyFilter
     */
    public static function Instance()
    {
        if (!self::$Instance)
            self::$Instance = self::Create();
        return self::$Instance;
    }

    /**
     * Set text filter
     *
     * @param int $filter
     * @return void
     */
    public function SetFilter($filter)
    {
        $this->_filter = $filter;
    }

    /**
     * Add filter to text filter
     *
     * @param int $filter
     * @return void
     */
    public function AddFilter($filter)
    {
        $this->_filter = $this->_filter | $filter;
    }

    /**
     * Remove filter from text filter
     *
     * @param int $filter
     * @return void
     */
    public function RemoveFilter($filter)
    {
        $this->_filter = $this->_filter & (~$filter);
    }

    /**
     * Text will be filtered through filter settings and returned
     *
     * @param string $input
     * @return string
     */
    public function FilterText($input)
    {
        if ($this->_filter & self::DISABLE_MULTI_BYTE) $input = $this->filterDisableMultibyte($input);
        if ($this->_filter & self::DISABLE_NULL) $input = $this->filterNull($input);
        if ($this->_filter & self::HTML_ENTITY) $input = $this->filterHtmlEntities($input);
        if ($this->_filter & self::HTML_DOLLAR) $input = $this->filterHtmlDollar($input);
        if ($this->_filter & self::HTML_SLASHES) $input = $this->filterHtmlSlashes($input);
        if ($this->_filter & self::HTML_BRACKETS) $input = $this->filterHtmlBrackets($input);
        if ($this->_filter & self::MYSQL_SAFE) $input = $this->filterMysqlSafe($input);
        return $input;
    }

    /**
     * Disable multibyte encoding by forcing incoming character set to ASCII
     *
     * @param    string $input
     * @return    string
     */
    private function filterDisableMultibyte($input)
    {
        return mb_convert_encoding($input, 'ASCII');
    }

    /**
     * Filter out \00 0x00, A.K.A. null.
     *
     * @param string $input
     * @return string
     */
    private function filterNull($input)
    {
        $char = '';

        for ($cnt = 0; $cnt < strlen($input); $cnt++) {
            $char = ord(substr($input, $cnt, 1));

            if (!$char) {
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
    private function filterHtmlEntities($input)
    {
        $ret = '';
        if ($this->_filter & self::DISABLE_MULTI_BYTE) {
            //php 5.4.0 return htmlentities($input, ENT_QUOTES | ENT_DISALLOWED | ENT_XHTML, 'ASCII');
            $ret = htmlentities($input, ENT_QUOTES, 'ASCII');
        } else {
            //php 5.4.0 return htmlentities($input, ENT_QUOTES | ENT_DISALLOWED | ENT_XHTML, 'UTF-8');
            $ret = htmlentities($input, ENT_QUOTES, 'UTF-8');
        }
        $ret = str_replace(array('&lt;', '&gt;'), array('<', '>'), $ret);

        return $ret;
    }

    /**
     * Filter $ symbol to HTML entity
     *
     * @param string $input
     * @return string
     */
    private function filterHtmlDollar($input)
    {
        $dollar = '&#36;';
        $char = '';

        for ($cnt = 0; $cnt < strlen($input); $cnt++) {
            $char = ord(substr($input, $cnt, 1));

            if ($char == 36) {
                $input = substr($input, 0, $cnt) . $dollar . substr($input, $cnt + 1);
            }
        }

        return $input;
    }

    /**
     * Transform slashes (back and forward and dash) to HTML entities
     *
     * @param    string $input
     * @return    string
     */
    private function filterHtmlSlashes($input)
    {
        $filterList = new MonkeyStack();
        $filterList->Push(45, 47, 92);
        $char = '';

        for ($inputIndex = 0; $inputIndex < strlen($input); $inputIndex++) {
            $char = ord(substr($input, $inputIndex, 1));
            for ($filterIndex = 0; $filterIndex < $filterList->Count(); $filterIndex++) {
                if ($char == $filterList->Get($filterIndex)) {
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
    private function filterHtmlBrackets($input)
    {
        $filterList = new MonkeyStack();
        $filterList->Push(40, 41, 60, 62, 91, 93, 123, 125);
        $char = '';

        for ($inputIndex = 0; $inputIndex < strlen($input); $inputIndex++) {
            $char = ord(substr($input, $inputIndex, 1));
            for ($filterIndex = 0; $filterIndex < $filterList->Count(); $filterIndex++) {
                if ($char == $filterList->Get($filterIndex)) {
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
    private function filterMysqlSafe($input)
    {
        if (@mysql_real_escape_string($input))
            return mysql_real_escape_string($input);
        return $input;
    }

    /**
     * Force incoming character set to UTF-8
     *
     * @deprecated 2/10/2012
     * @param    string $input
     * @return    string
     */
    private function filterForceMultibyte($input)
    {
        return mb_convert_encoding($input, 'UTF-8');
    }
}

if (!isset($filter))
    $filter = MonkeyFilter::Instance();
