<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Container\DependencyInjectionContainer;

// --- Test fixture classes (used only by DependencyInjectionContainerTest) ---

class DIFixtureNoConstructor {
	public string $value = 'default';
}

interface DIFixtureInterface {
	public function getValue(): string;
}

class DIFixtureImplementation implements DIFixtureInterface {
	public function getValue(): string {
		return 'concrete';
	}
}

class DIFixtureDependsOnInterface {
	public DIFixtureInterface $dep;
	public function __construct( DIFixtureInterface $dep ) {
		$this->dep = $dep;
	}
}

class DIFixtureDependsOnConcrete {
	public DIFixtureNoConstructor $dep;
	public function __construct( DIFixtureNoConstructor $dep ) {
		$this->dep = $dep;
	}
}

class DIFixtureOptionalParam {
	public string $name;
	public function __construct( string $name = 'fallback' ) {
		$this->name = $name;
	}
}

class DIFixtureUnresolvable {
	public function __construct( string $required ) {
	}
}

// --- Test class ---

#[CoversClass( DependencyInjectionContainer::class )]
class DependencyInjectionContainerTest extends TestCase {

	private DependencyInjectionContainer $container;

	protected function setUp(): void {
		parent::setUp();
		$this->container = new DependencyInjectionContainer();
	}

	public function test_resolves_class_without_constructor(): void {
		$instance = $this->container->get( DIFixtureNoConstructor::class );

		$this->assertInstanceOf( DIFixtureNoConstructor::class, $instance );
		$this->assertSame( 'default', $instance->value );
	}

	public function test_resolves_class_with_concrete_dependency(): void {
		$instance = $this->container->get( DIFixtureDependsOnConcrete::class );

		$this->assertInstanceOf( DIFixtureDependsOnConcrete::class, $instance );
		$this->assertInstanceOf( DIFixtureNoConstructor::class, $instance->dep );
	}

	public function test_bind_maps_interface_to_concrete(): void {
		$this->container->bind( DIFixtureInterface::class, DIFixtureImplementation::class );

		$instance = $this->container->get( DIFixtureDependsOnInterface::class );

		$this->assertInstanceOf( DIFixtureDependsOnInterface::class, $instance );
		$this->assertInstanceOf( DIFixtureImplementation::class, $instance->dep );
		$this->assertSame( 'concrete', $instance->dep->getValue() );
	}

	public function test_shared_returns_same_instance(): void {
		$this->container->bind( DIFixtureInterface::class, DIFixtureImplementation::class, true );

		$a = $this->container->get( DIFixtureInterface::class );
		$b = $this->container->get( DIFixtureInterface::class );

		$this->assertSame( $a, $b );
	}

	public function test_non_shared_returns_different_instances(): void {
		$this->container->bind( DIFixtureInterface::class, DIFixtureImplementation::class, false );

		$a = $this->container->get( DIFixtureInterface::class );
		$b = $this->container->get( DIFixtureInterface::class );

		$this->assertNotSame( $a, $b );
	}

	public function test_uses_default_value_for_optional_scalar(): void {
		$instance = $this->container->get( DIFixtureOptionalParam::class );

		$this->assertSame( 'fallback', $instance->name );
	}

	public function test_throws_on_unresolvable_parameter(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot auto-resolve parameter' );

		$this->container->get( DIFixtureUnresolvable::class );
	}
}
