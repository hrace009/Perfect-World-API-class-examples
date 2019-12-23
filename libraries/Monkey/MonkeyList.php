<?php

/**
 * List data structure management
 * Note: This is not a linked list data structure
 *
 * @author Nicholas R. Grant
 * @version 1.0 rev 5
 * @copyright NRGsoft (c) 2003-2012
 */
class MonkeyList
{
	protected $_list = array();
	
	function __construct(){}
	
	/**
	 * Adds an object into the beginning of the list 
	 * 
	 * @param	mixed	$object
	 */
	public function AddFirst( $object )
	{
		array_unshift( $this->_list, $object );
	}
	
	/**
	 * Adds an object on the end of the list
	 * 
	 * @param	mixed	$object
	 */
	public function AddLast( $object )
	{
		$this->_list[] = $object;
	}
	
	/**
	 * Returns the number of objects in this object
	 * 
	 * @return	int
	 */
	public function Count()
	{
		return count( $this->_list );
	}
	
	/**
	 * Returns the first object from the list
	 * 
	 * @return	mixed
	 */
	public function First()
	{
		return $this->_list[0];
	}
	
	/**
	 * Returns the last object from the list
	 * 
	 * @return	mixed
	 */
	public function Last()
	{
		return $this->_list[$this->Count() - 1];
	}
	
	/**
	 * Clears this object's objects
	 * 
	 * @return	int	0
	 */
	public function Clear()
	{
		$this->_list = array();
	}
	
	/**
	 * Returns true if the object is empty or false if it isn't
	 * 
	 * @return	bool
	 */
	public function IsEmpty()
	{
		return $this->Count() < 1;
	}
	
	/**
	 * Removes the first object from the list and returns it
	 * 
	 * @return	mixed
	 */
	public function RemoveFirst()
	{
		return array_shift( $this->_list );
	}
	
	/**
	 * Removes the last object from the list and returns it
	 * 
	 * @return	mixed
	 */
	public function RemoveLast()
	{
		return array_pop( $this->_list );
	}
	
	/**
	 * Removes each object from the list that matches parameter object
	 * Note: There's probably a better way to do this...
	 * 
	 * @param mixed $object
	 */
	public function RemoveEach( $object )
	{
		$length = $this->Count();
		$popTo = $length;
		$i = 0;
		
		while ( $i < $length )
		{
			if ( $this->_list[$i] !== $object )
			{
				$i++;
				Continue;
			}
			
			$b = $i; $e = $i + 1;
			
			while ( $e < $length && $this->_list[$e] === $object ) $e++;
			
			while ( $e < $length )
			{
				$this->_list[$b] = $this->_list[$e];
				$b++; $e++;
			}
			
			$length -= $e - $b;
			$i++;
		}
		
		$popTo -= $length;
		
		for ( $i = 0; $i < $popTo; $i++ )
		{
			array_pop($this->_list);
		}
	}
	
	
	/**
	 * Returns the list backwards
	 * 
	 * @return	array mixed
	 */
	public function Backwards()
	{
		return array_reverse( $this->_list );
	}
	
	/**
	 * Returns the list
	 * 
	 * @return	array mixed
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
	 * Returns this objects formatted data to the immediate buffer
	 * 
	 */
	public function PrintDebugData()
	{
		echo '<pre>' . print_r( $this, true ) . '</pre>';
	}
}
