<?php

namespace Slc\SeoLinksCrawler\Container;

/**
 * Simple dependency injection container with singleton support.
 */
class DependencyInjectionContainer {

	/**
	 * Registered service class names.
	 *
	 * @var array<string, string>
	 */
	private $services = [];

	/**
	 * Cached singleton instances.
	 *
	 * @var array<string, object>
	 */
	private $singletons = [];

	/**
	 * Keys that should be treated as singletons.
	 *
	 * @var array<string, bool>
	 */
	private $shared = [];

	/**
	 * Register a service class.
	 *
	 * @param string $key      Service identifier (class or interface name).
	 * @param string $class    Fully-qualified class name.
	 * @param bool   $shared   Whether the service should be a singleton.
	 */
	public function register( $key, $class, $shared = false ) {
		$this->services[ $key ] = $class;

		if ( $shared ) {
			$this->shared[ $key ] = true;
		}
	}

	/**
	 * Resolve and return an instance of the registered service.
	 *
	 * For shared services the same instance is returned on every call.
	 * Additional arguments after $key are passed to the constructor.
	 *
	 * @param string $key Service identifier.
	 *
	 * @return object|null Instance of the service, or null if not registered.
	 */
	public function get( $key ) {
		if ( ! isset( $this->services[ $key ] ) ) {
			return null;
		}

		if ( isset( $this->singletons[ $key ] ) ) {
			return $this->singletons[ $key ];
		}

		$args             = array_slice( func_get_args(), 1 );
		$class            = $this->services[ $key ];
		$reflection_class = new \ReflectionClass( $class );
		$instance         = $reflection_class->newInstanceArgs( $args );

		if ( ! empty( $this->shared[ $key ] ) ) {
			$this->singletons[ $key ] = $instance;
		}

		return $instance;
	}
}
