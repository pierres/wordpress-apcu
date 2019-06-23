<?php

class ObjectCacheTest extends \PHPUnit\Framework\TestCase {

	/** @var APCu_Object_Cache */
	private $object_cache;

	public function setUp() {
		$this->object_cache = $this->create_object_cache();
	}

	/**
	 * @param bool $multisite
	 * @param bool $suspended
	 *
	 * @return APCu_Object_Cache
	 */
	private function create_object_cache( bool $multisite = false, bool $suspended = false ) {
		return new APCu_Object_Cache(
			'',
			$multisite,
			0,
			function () use ( $suspended ) {
				return $suspended;
			}
		);
	}

	public function tearDown() {
		$this->object_cache->flush();
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_add( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
		$this->assertFalse( $this->object_cache->add( $key, 'test', $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
	}

	public function test_wp_cache_close() {
		$this->assertTrue( wp_cache_close() );
	}

	public function test_wp_cache_decr() {
		$key   = 'key';
		$group = 'group';

		$this->assertTrue( $this->object_cache->add( $key, 10, $group ) );
		$this->assertEquals( 7, $this->object_cache->decr( $key, 3, $group ) );
		$this->assertEquals( 6, $this->object_cache->decr( $key, 1, $group ) );
		$this->assertEquals( 6, $this->object_cache->get( $key, $group ) );
		$this->assertEquals( 0, $this->object_cache->decr( $key, 45, $group ) );
		$this->assertEquals( 0, $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_delete( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertFalse( $this->object_cache->delete( $key, $group ) );
		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertTrue( $this->object_cache->delete( $key, $group ) );
		$this->assertFalse( $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_flush( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertTrue( $this->object_cache->flush() );
		$this->assertFalse( $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_get( $data ) {
		// TODO: test force parameter
		$group = 'group';
		$key   = 0;

		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group, false, $found ) );
		$this->assertTrue( $found );

		$this->assertFalse( $this->object_cache->get( 'test', $group ) );
		$this->assertFalse( $this->object_cache->get( 'test', $group, false, $found ) );
		$this->assertFalse( $found );
	}

	public function test_wp_cache_incr() {
		$key   = 'key';
		$group = 'group';

		$this->assertTrue( $this->object_cache->add( $key, 10, $group ) );
		$this->assertEquals( 13, $this->object_cache->incr( $key, 3, $group ) );
		$this->assertEquals( 14, $this->object_cache->incr( $key, 1, $group ) );
		$this->assertEquals( 14, $this->object_cache->get( $key, $group ) );
		$this->assertEquals( 0, $this->object_cache->incr( $key, - 45, $group ) );
		$this->assertEquals( 0, $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_replace( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertTrue( $this->object_cache->replace( $key, 'test', $group ) );
		$this->assertEquals( 'test', $this->object_cache->get( $key, $group ) );
		$this->assertFalse( $this->object_cache->replace( 'nokey', $data, $group ) );
		$this->assertFalse( $this->object_cache->get( 'nokey', $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_set( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( $this->object_cache->set( $key, $data, $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_switch_to_blog( $data ) {
		$this->object_cache = $this->create_object_cache( true );
		$this->object_cache->switch_to_blog( 2 );
		$group = 'group';
		$key   = 'foo';

		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
		$this->object_cache->switch_to_blog( 3 );
		$this->assertFalse( $this->object_cache->get( $key, $group ) );
		$this->object_cache->switch_to_blog( 2 );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_add_global_groups( $data ) {
		$this->object_cache = $this->create_object_cache( true );
		$group              = 'group';
		$key                = 'foo';

		$this->object_cache->add_global_groups( $group );
		$this->object_cache->switch_to_blog( 2 );
		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
		$this->object_cache->switch_to_blog( 3 );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
		$this->object_cache->switch_to_blog( 2 );
		$this->assertEquals( $data, $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_add_non_persistent_groups( $data ) {
		$key = 'foo';

		$this->object_cache->add_non_persistent_groups( 'temp' );
		$this->assertTrue( $this->object_cache->add( $key, $data, 'temp' ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, 'temp' ) );
		$this->assertTrue( $this->object_cache->add( $key, $data, 'group' ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, 'group' ) );
		// Re-init the cache. This deletes the local cache but keeps the persistent one
		$this->object_cache = $this->create_object_cache();
		$this->assertFalse( $this->object_cache->get( $key, 'temp' ) );
		$this->assertEquals( $data, $this->object_cache->get( $key, 'group' ) );
	}

	public function test_wp_cache_reset() {
		$group = 'group';
		$key   = 0;
		$data  = 'some data';

		$this->assertTrue( $this->object_cache->add( $key, $data, $group ) );
		$this->object_cache->reset();
		$this->assertFalse( $this->object_cache->get( $key, $group ) );
	}

	public function test_groups() {
		$key = 'foo';
		$this->assertTrue( $this->object_cache->add( $key, 'test1', 'group1' ) );
		$this->assertTrue( $this->object_cache->add( $key, 'test2', 'group2' ) );
		$this->assertEquals( 'test1', $this->object_cache->get( $key, 'group1' ) );
		$this->assertEquals( 'test2', $this->object_cache->get( $key, 'group2' ) );

		$this->assertTrue( $this->object_cache->set( $key, 'test12', 'group1' ) );
		$this->assertTrue( $this->object_cache->set( $key, 'test22', 'group2' ) );
		$this->assertEquals( 'test12', $this->object_cache->get( $key, 'group1' ) );
		$this->assertEquals( 'test22', $this->object_cache->get( $key, 'group2' ) );

		$this->assertTrue( $this->object_cache->delete( $key, 'group2' ) );
		$this->assertFalse( $this->object_cache->get( $key, 'group2' ) );
		$this->assertEquals( 'test12', $this->object_cache->get( $key, 'group1' ) );

		$this->assertTrue( $this->object_cache->replace( $key, 'test13', 'group1' ) );
		$this->assertEquals( 'test13', $this->object_cache->get( $key, 'group1' ) );
		$this->assertFalse( $this->object_cache->get( $key, 'group2' ) );
	}

	public function testSuspendCacheAddition() {
		$group = 'group';
		$key   = 0;

		$this->object_cache = $this->create_object_cache( false, true );
		$this->assertFalse( $this->object_cache->add( $key, 'some data', $group ) );
		$this->assertFalse( $this->object_cache->get( $key, $group ) );
	}

	/**
	 * @return array
	 */
	public function provideTestData(): array {
		return [
			[ 'foo' ],
			[ 43 ],
			[ 1.234 ],
			[ 1.2e3 ],
			[ 7E-10 ],
			[ 0123 ],
			[ - 123 ],
			[ 0x1A ],
			[ [ 'a', 'b' ] ],
			[ [ 'foo' => [ 'a', 'b' ] ] ],
			[ null ],
			[ false ],
			[ true ],
			[ new ArrayObject( [ 'foo', 'bar' ] ) ],
			[ new StdClass() ],
		];
	}
}
