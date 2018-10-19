<?php

class MonkeyList
{
    protected $_list = array();

    function __construct()
    {
    }

    /**
     * Adds an object into the beginning of the list
     *
     * @param    object $object
     */
    public function AddFirst($object)
    {
        array_unshift($this->_list, $object);
    }

    /**
     * Adds an object on the end of the list
     *
     * @param    object $object
     */
    public function AddLast($object)
    {
        $this->_list[] = $object;
    }

    /**
     * Returns the first object from the list
     *
     * @return    object
     */
    public function First()
    {
        return $this->_list[0];
    }

    /**
     * Returns the last object from the list
     *
     * @return    object
     */
    public function Last()
    {
        return end($this->_list);
    }

    /**
     * Clears this object's objects
     *
     * @return    int    0
     */
    public function Clear()
    {
        $this->_list = array();
    }

    /**
     * Returns true if the object is empty or false if it isn't
     *
     * @return    bool
     */
    public function IsEmpty()
    {
        return $this->Count() < 1;
    }

    /**
     * Returns the number of objects in this object
     *
     * @return    int
     */
    public function Count()
    {
        return count($this->_list);
    }

    /**
     * Removes the first object from the list and returns it
     *
     * @return    object
     */
    public function RemoveFirst()
    {
        return array_shift($this->_list);
    }

    /**
     * Removes the last object from the list and returns it
     *
     * @return    object
     */
    public function RemoveLast()
    {
        return array_pop($this->_list);
    }

    /**
     * Returns the list backwards
     *
     * @return    array object
     */
    public function Backwards()
    {
        return array_reverse($this->_list);
    }

    /**
     * Returns the list
     *
     * @return    array object
     */
    public function Objects()
    {
        return $this->_list;
    }


    /**
     * Return new ArrayIterator of object
     *
     * @return ArrayIterator
     */
    public function ObjectIterator()
    {
        return new ArrayIterator($this->_list);
    }


    /**
     * Returns the debug information to the immediate buffer
     *
     */
    public function GetDebug()
    {
        echo '<pre>' . print_r($this->_list, true) . '</pre>';
    }
}
