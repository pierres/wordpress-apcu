<?php

class ObjectCacheInterfaceTest extends \PHPUnit\Framework\TestCase {

	/** @var APCu_Object_Cache|\PHPUnit\Framework\MockObject\MockObject */
	private $object_cache;

	public function setUp() {
		$this->object_cache         = $this->createMock( APCu_Object_Cache::class );
		$GLOBALS['wp_object_cache'] = $this->object_cache;
	}

	public function tearDown() {
		$this->object_cache = null;
		unset( $GLOBALS['wp_object_cache'] );
	}

	public function test_wp_cache_add() {
		$data   = 'some data';
		$group  = 'group';
		$key    = 2;
		$expire = 1;
		$this->object_cache
			->expects( $this->once() )
			->method( 'add' )
			->with( $key, $data, $group, $expire )
			->willReturn( true );

		$this->assertTrue( wp_cache_add( $key, $data, $group, $expire ) );
	}

	public function test_wp_cache_decr() {
		$group  = 'group';
		$key    = 2;
		$offset = 1;
		$value  = 1;
		$this->object_cache
			->expects( $this->once() )
			->method( 'decr' )
			->with( $key, $offset, $group )
			->willReturn( $value - 1 );

		$this->assertEquals( $value - 1, wp_cache_decr( $key, $offset, $group ) );
	}

	public function test_wp_cache_delete() {
		$group = 'group';
		$key   = 2;
		$this->object_cache
			->expects( $this->once() )
			->method( 'delete' )
			->with( $key, $group )
			->willReturn( true );

		$this->assertTrue( wp_cache_delete( $key, $group ) );
	}

	public function test_wp_cache_flush() {
		$this->object_cache
			->expects( $this->once() )
			->method( 'flush' )
			->with()
			->willReturn( true );

		$this->assertTrue( wp_cache_flush() );
	}

	public function test_wp_cache_get() {
		$group = 'group';
		$key   = 2;
		$force = false;
		$found = false;
		$data  = 'some data';
		$this->object_cache
			->expects( $this->once() )
			->method( 'get' )
			->with( $key, $group, $force, $found )
			->willReturn( $data );

		$this->assertEquals( $data, wp_cache_get( $key, $group, $force, $found ) );
	}

	public function test_wp_cache_incr() {
		$group  = 'group';
		$key    = 2;
		$offset = 1;
		$value  = 1;
		$this->object_cache
			->expects( $this->once() )
			->method( 'incr' )
			->with( $key, $offset, $group )
			->willReturn( $value + 1 );

		$this->assertEquals( $value + 1, wp_cache_incr( $key, $offset, $group ) );
	}

	public function test_wp_cache_init() {
		$this->assertTrue( function_exists( 'wp_cache_init' ) );
	}

	public function test_wp_cache_replace() {
		$data   = 'some data';
		$group  = 'group';
		$key    = 2;
		$expire = 1;
		$this->object_cache
			->expects( $this->once() )
			->method( 'replace' )
			->with( $key, $data, $group, $expire )
			->willReturn( true );

		$this->assertTrue( wp_cache_replace( $key, $data, $group, $expire ) );
	}

	public function test_wp_cache_set() {
		$data   = 'some data';
		$group  = 'group';
		$key    = 2;
		$expire = 1;
		$this->object_cache
			->expects( $this->once() )
			->method( 'set' )
			->with( $key, $data, $group, $expire )
			->willReturn( true );

		$this->assertTrue( wp_cache_set( $key, $data, $group, $expire ) );
	}

	public function test_wp_cache_switch_to_blog() {
		$blog_id = 2;
		$this->object_cache
			->expects( $this->once() )
			->method( 'switch_to_blog' )
			->with( $blog_id );

		wp_cache_switch_to_blog( $blog_id );
	}

	public function test_wp_cache_add_global_groups() {
		$groups = [ 'some group', 'another group' ];
		$this->object_cache
			->expects( $this->once() )
			->method( 'add_global_groups' )
			->with( $groups );

		wp_cache_add_global_groups( $groups );
	}

	public function test_wp_cache_add_non_persistent_groups() {
		$groups = [ 'some group', 'another group' ];
		$this->object_cache
			->expects( $this->once() )
			->method( 'add_non_persistent_groups' )
			->with( $groups );

		wp_cache_add_non_persistent_groups( $groups );
	}

	public function test_wp_cache_reset() {
		$this->object_cache
			->expects( $this->once() )
			->method( 'reset' );

		wp_cache_reset();
	}
}
