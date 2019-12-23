<?php

/**
 * Map data structure management
 *
 * @author Nicholas R. Grant
 * @version 1.0 rev 8
 * @copyright NRGsoft (c) 2003-2012
 */
class MonkeyMap extends MonkeyList
{
	/**
	 * 
	 * 
	 * @param mixed $mapArrayOrMonkeyMap
	 */
	public function __construct( $mapArrayOrMonkeyMap = null )
	{
		$args = func_get_args();
		$this->SetFromMap(null, $args);
	}
	
	
	/**
	 * Returns the object with the specified $key
	 * 
	 * @param	mixed	$key
	 * @return	mixed
	 */
	public function Get( $key )
	{
		if (isset($this->_list[$key]))
			return $this->_list[$key];
		return null;
	}
	
	/**
	 * Sets the $value at $key in the map
	 * Note: New version now supports recursive arrays!
	 * 
	 * @param	mixed	$key
	 * @param	mixed	$value
	 */
	public function Set( $key, $value )
	{
		if ( is_array($key) )
		{
			if ( is_array($value) )
			{
				$keylen = count($key);
				$valuelen = count($value);
				
				for ( $i = 0; $i < $keylen; $i++ )
				{
					if ( is_array($key[$i]) )
						$this->Set($key[$i], $value[$i % $valuelen]);
					else
						$this->_list[$key[$i]] = $value[$i % $valuelen];
				}
			}
			else
			{
				$keylen = count($key);
				
				for ( $i = 0; $i < $keylen; $i++ )
				{
					$this->_list[$key[$i]] = $value;
				}
			}
		}
		else
		{
			$this->_list[$key] = $value;
		}
	}
	
	/**
	 * Checks if the $key exists and returns true if it
	 * does exist and zoro if it doesn't exist.
	 * 
	 * @param	mixed	$key
	 * @return	bool	key's existance
	 */
	public function Contains( $key )
	{
		return array_key_exists( $key, $this->_list );
	}
	
	/**
	 * Removes the value at key and the key
	 * 
	 * @param	mixed	$key
	 */
	public function Remove( $key )
	{
		if ( $this->Contains( $key ) )
			unset( $this->_list[$key] );
	}
	
	/**
	 * Return array of keys
	 *
	 * @return	array mixed
	 */
	public function Keys()
	{
		return array_keys($this->_list);
	}
	
	/**
	 * Return array values
	 *
	 * @return	array mixed
	 */
	public function Values()
	{
		return array_values($this->_list);
	}
	
	/**
	 * @desc Set map from array map (useful for JSON data)<br><br>
	 * <b>Note:</b> Can take unlimited Map Arrays / Monkey Maps as arguments.
	 * When first argument is null, it treats the other arguments as being
	 * a list of arguments themselves. This is useful in the constructor
	 * so that it can take in multiple map arrays / monkey maps and pass
	 * them to this method.
	 * 
	 * @param array $array , ...
	 */
	public function SetFromMap( $mapArrayOrMonkeyMap )
	{
		$args = func_get_args();
		
		if ( is_null($mapArrayOrMonkeyMap) )
		{
			$actualArgs = array();
			
			foreach ( $args as $arg => &$val )
			{
				if ( $val === null ) Continue;
				foreach ( $val as $key => $value )
				{
					$actualArgs[$key] = $value;
				}
			}
				
			$args = $actualArgs;
		}
		
		foreach ( $args as &$map )
		{
			if ( !is_null($map) )
			{
				if ( is_array($map) )
				{
					$this->Set(array_keys($map), array_values($map));
				}
				elseif ( is_object($map) && get_class($map) === 'MonkeyMap' )
				{
					$this->Set($map->Keys(), $map->Values());
				}
			}
		}
	}
}
