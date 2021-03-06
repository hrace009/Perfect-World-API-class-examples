<?php

class MonkeyString
{
    private $_string;

    /**
     * MonkeyString constructor
     *
     * @param string $string
     * @return void
     */
    function __construct($string)
    {
        $this->_string = $string;
    }

    /**
     * Create new instance of string
     *
     * @param string $string
     * @return MonkeyString
     */
    static function Create($string)
    {
        return new MonkeyString($string);
    }

    /**
     * Get character from ASCII character code
     *
     * @param int $charCode
     * @return string
     */
    static function FromChar($charCode)
    {
        return chr($charCode);
    }

    /**
     * Get substring of string
     *
     * @param int $start
     * @param int $length
     * @return string
     */
    public function Get($start = 0, $length = -1)
    {
        return substr($this->_string, $start, $length);
    }

    /**
     * Set string to input string
     *
     * @param string $string
     * @return void
     */
    public function Set($string)
    {
        $this->_string = $string;
    }

    /**
     * Join pieces to string
     *
     * @param string $pieces
     * @return void
     */
    public function Join($pieces)
    {
        $args = new ArrayIterator(func_get_args());
        do {
            $this->_string .= $args->current();
        } while ($args->next());
    }

    /**
     * Length of string
     *
     * @return int
     */
    public function Length()
    {
        return strlen($this->_string);
    }

    /**
     * Replace given string with replacement string in this string
     *
     * @param string $findString
     * @param string $replaceString
     */
    public function Replace($findString, $replaceString)
    {
        return str_replace($findString, $replaceString, $this->_string);
    }

    /**
     * Return string split by split delimiter
     *
     * @param string $separator
     * @return string[]
     */
    public function Split($separator)
    {
        return str_split($this->_string, $separator);
    }
}
