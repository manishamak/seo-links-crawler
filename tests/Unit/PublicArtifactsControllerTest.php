<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Slc\SeoLinksCrawler\Public\PublicArtifactsController;

#[CoversClass( PublicArtifactsController::class )]
class PublicArtifactsControllerTest extends TestCase {

	private PublicArtifactsController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = new PublicArtifactsController();
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_register_hooks_does_nothing_when_not_vip(): void {
		Actions\expectAdded( 'init' )->never();

		$this->controller->register_hooks();
	}

	public function test_register_hooks_adds_actions_when_vip(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		Actions\expectAdded( 'init' )->twice();
		Filters\expectAdded( 'query_vars' )->once();
		Actions\expectAdded( 'template_redirect' )->once();

		$this->controller->register_hooks();
	}

	public function test_register_post_type_registers_cpt(): void {
		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'slc_artifact',
				Mockery::on( function ( $args ) {
					return false === $args['public']
						&& false === $args['show_ui']
						&& true === $args['exclude_from_search'];
				} )
			);

		$this->controller->register_post_type();
	}

	public function test_register_rewrite_rules_adds_rules(): void {
		Functions\expect( 'add_rewrite_rule' )
			->once()
			->with( '^home\\.html$', Mockery::type( 'string' ), 'top' );

		Functions\expect( 'add_rewrite_rule' )
			->once()
			->with( '^sitemap\\.html$', Mockery::type( 'string' ), 'top' );

		$this->controller->register_rewrite_rules();
	}

	public function test_register_query_var_adds_slc_artifact(): void {
		$result = $this->controller->register_query_var( [ 'existing_var' ] );

		$this->assertContains( 'slc_artifact', $result );
		$this->assertContains( 'existing_var', $result );
	}

	public function test_maybe_serve_artifact_returns_early_when_no_query_var(): void {
		Functions\expect( 'get_query_var' )
			->once()
			->with( 'slc_artifact' )
			->andReturn( '' );

		$this->controller->maybe_serve_artifact();

		$this->assertTrue( true );
	}
}
