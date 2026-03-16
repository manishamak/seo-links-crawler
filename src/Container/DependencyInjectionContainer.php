<?php

namespace Slc\SeoLinksCrawler\Container;

/**
 * Auto-wiring dependency injection container.
 *
 * Resolves class dependencies by reading constructor type-hints via
 * Reflection. Interfaces are mapped to concrete implementations with
 * bind(). Concrete classes can be resolved without registration.
 *
 * Shared (singleton) bindings return the same instance on every call.
 */
class DependencyInjectionContainer {

	/**
	 * Interface/abstract → concrete class mappings.
	 *
	 * @var array<string, string>
	 */
	private $bindings = [];

	/**
	 * Keys that should be treated as singletons.
	 *
	 * @var array<string, bool>
	 */
	private $shared = [];

	/**
	 * Cached singleton instances.
	 *
	 * @var array<string, object>
	 */
	private $instances = [];

	/**
	 * Map an abstract (interface or key) to a concrete class.
	 *
	 * @param string $abstract Interface or identifier.
	 * @param string $concrete Fully-qualified concrete class name.
	 * @param bool   $shared   Return the same instance on every resolve.
	 */
	public function bind( string $abstract, string $concrete, bool $shared = false ) {
		$this->bindings[ $abstract ] = $concrete;

		if ( $shared ) {
			$this->shared[ $abstract ] = true;
		}
	}

	/**
	 * Resolve and return an instance for the given abstract or class.
	 *
	 * 1. If a singleton exists, return it immediately.
	 * 2. Look up any registered binding, otherwise treat the key as a
	 *    concrete class name.
	 * 3. Use Reflection to read constructor parameter type-hints and
	 *    recursively resolve each dependency.
	 *
	 * @param string $abstract Interface, key, or concrete class name.
	 *
	 * @return object Resolved instance.
	 *
	 * @throws \RuntimeException If a parameter cannot be resolved.
	 */
	public function get( string $abstract ) {
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		$concrete = $this->bindings[ $abstract ] ?? $abstract;

		$reflection  = new \ReflectionClass( $concrete );
		$constructor = $reflection->getConstructor();

		if ( ! $constructor || 0 === $constructor->getNumberOfParameters() ) {
			$instance = $reflection->newInstance();
		} else {
			$dependencies = $this->resolve_parameters( $constructor, $concrete );
			$instance     = $reflection->newInstanceArgs( $dependencies );
		}

		if ( ! empty( $this->shared[ $abstract ] ) ) {
			$this->instances[ $abstract ] = $instance;
		}

		return $instance;
	}

	/**
	 * Read constructor parameter type-hints and resolve each one.
	 *
	 * @param \ReflectionMethod $constructor Constructor to inspect.
	 * @param string            $concrete    Class being built (for error messages).
	 *
	 * @return array Ordered list of resolved constructor arguments.
	 *
	 * @throws \RuntimeException If a parameter has no type-hint and no default.
	 */
	private function resolve_parameters( \ReflectionMethod $constructor, string $concrete ): array {
		$dependencies = [];

		foreach ( $constructor->getParameters() as $param ) {
			$type = $param->getType();

			if ( $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ) {
				$dependencies[] = $this->get( $type->getName() );
				continue;
			}

			if ( $param->isDefaultValueAvailable() ) {
				$dependencies[] = $param->getDefaultValue();
				continue;
			}

			throw new \RuntimeException(
				sprintf(
					'Cannot auto-resolve parameter $%s in %s.',
					$param->getName(),
					$concrete
				)
			);
		}

		return $dependencies;
	}
}
