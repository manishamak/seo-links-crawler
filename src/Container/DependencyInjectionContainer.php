<?php

namespace Slc\SeoLinksCrawler\Container;

class DependencyInjectionContainer {

	/**
	 * Contains list of classes.
	 *
	 * @var array
	 */
	private $services = [];

	/**
	 * Insert dependencies(classes) in the array.
	 *
	 * @param string $key   class/interface name as the key to services array.
	 * @param string $class class name as the value to services array.
	 */
	public function register( $key, $class ) {
		$this->services[ $key ] = $class;
	}

	/**
	 * Create object of the registered classes.
	 *
	 * @param  string $key  registered class/interface name.
	 *
	 * @return instance/object  Instance of the registered class.
	 */
	public function get( $key ) {
		$args = array_slice( func_get_args(), 1 ); // Get the arguments starting from the second argument.
		if ( isset( $this->services[ $key ] ) ) {
			$class            = $this->services[ $key ];
			$reflection_class = new \ReflectionClass( $class );
			return $reflection_class->newInstanceArgs( $args );
		}
		return null;
	}
}
