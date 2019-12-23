<?php

/**
 * Stack data structure management
 *
 * @author Nicholas R. Grant
 * @version 1.0 rev 4
 * @copyright NRGsoft (c) 2003-2012
 */
class MonkeyStack extends MonkeyList
{
	/**
	 * Returns the item at the stack index
	 * 
	 * @param	int	$index
	 * @return	mixed
	 */
	public function Get( $index )
	{
		return $this->_list[$index];
	}
	
	/**
	 * Returns the item at the top minus offset
	 *
	 * @param	int	$offset
	 * @return	mixed
	 */
	public function GetFromTop( $offset )
	{
		return $this->_list[$this->Count() - $offset - 1];
	}
	
	/**
	 * Sets the value at the specified index
	 * 
	 * @param	int	$index
	 * @param	mixed	$object
	 */
	public function Set( $index, $object )
	{
		$this->_list[$index] = $object;
	}
	
	/**
	 * Pushes a value onto the end of the stack
	 * 
	 * @param	mixed	$object
	 */
	public function Push( $object )
	{
		if ( func_num_args() === 1 )
		{
			$this->_list[] = $object;
		}
		elseif ( func_num_args() > 1 )
		{
			$args = func_get_args();
			foreach ( $args as $list => $arg )
			{
				$this->_list[] = $arg;
			}
		}
	}
	
	/**
	 * Remove the object at index and return it
	 * 
	 * @param int $index
	 * @return mixed
	 */
	public function Remove( $index )
	{
		$length = $this->Count() - 1;
		$ret = $this->_list[$index];
		
		for ( $i = $index; $i < $length; $i++ )
		{
			$this->_list[$i] = $this->_list[$i + 1];
		}
		
		unset($this->_list[$length]);
		return $ret;
	}
	
	/**
	 * Pops the value on the top of the stack
	 * 
	 * @return	mixed
	 */
	public function Pop()
	{
		return array_pop( $this->_list );
	}
	
	
	/**
	 * Returns the value at the top of the stack
	 *
	 * @return	mixed
	 */
	public function Top()
	{
		return $this->_list[$this->Count() - 1];
	}
}
