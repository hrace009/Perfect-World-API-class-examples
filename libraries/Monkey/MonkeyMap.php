<?php

class MonkeyMap extends MonkeyList
{
    /**
     * Returns the object with the specified $key
     *
     * @param    object $key
     * @return    object
     */
    public function Get($key)
    {
        return $this->_list[$key];
    }

    /**
     * Sets the $value at $key in the map
     *
     * @param    object $key
     * @param    object $value
     */
    public function Set($key, $value)
    {
        $this->_list[$key] = $value;
    }

    /**
     * Removes the value at key and the key
     *
     * @param    object $key
     */
    public function Remove($key)
    {
        if ($this->Contains($key))
            unset($this->_list[$key]);
    }

    /**
     * Checks if the $key exists and returns true if it
     * does exist and zoro if it doesn't exist.
     *
     * @param    object $key
     * @return    bool    key's existance
     */
    public function Contains($key)
    {
        return array_key_exists($key, $this->_list);
    }

    /**
     * Return array of keys
     *
     * @return    array    array of keys
     */
    public function Keys()
    {
        return array_keys($this->_list);
    }

    /**
     * Return array values
     *
     * @return    array    array of values
     */
    public function Values()
    {
        return array_values($this->_list);
    }
}
