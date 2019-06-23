<?php

/**
 * This is a PHPUnit test class.
 */

global $wp_object_cache, $table_prefix, $multisite, $suspend_cache;
! defined( 'DB_HOST' ) && define( 'DB_HOST', 'localhost' );
! defined( 'DB_NAME' ) && define( 'DB_NAME', 'WordPress' );
! defined( 'WP_CONTENT_DIR' ) && define( 'WP_CONTENT_DIR', '.' );
function is_multisite() {
	global $multisite;

	return isset( $multisite ) && $multisite;
}

function wp_suspend_cache_addition() {
	global $suspend_cache;

	return (bool) $suspend_cache;
}

function _deprecated_function() {
}

function get_current_blog_id() {
	return 0;
}

require( __DIR__ . '/object-cache.php' );

class ObjectCacheTest extends \PHPUnit\Framework\TestCase {

	// @codingStandardsIgnoreLine
	protected $backupGlobalsBlacklist = [ 'multisite' ];

	public function setUp() {
		wp_cache_init();
	}

	public function tearDown() {
		global $multisite;
		$multisite = false;
		wp_cache_flush();
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_add( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
		$this->assertFalse( wp_cache_add( $key, 'test', $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
	}

	public function test_wp_cache_close() {
		$this->assertTrue( wp_cache_close() );
	}

	public function test_wp_cache_decr() {
		$key   = 'key';
		$group = 'group';

		$this->assertTrue( wp_cache_add( $key, 10, $group ) );
		$this->assertEquals( 7, wp_cache_decr( $key, 3, $group ) );
		$this->assertEquals( 6, wp_cache_decr( $key, 1, $group ) );
		$this->assertEquals( 6, wp_cache_get( $key, $group ) );
		$this->assertEquals( 0, wp_cache_decr( $key, 45, $group ) );
		$this->assertEquals( 0, wp_cache_get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_delete( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertFalse( wp_cache_delete( $key, $group ) );
		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertTrue( wp_cache_delete( $key, $group ) );
		$this->assertFalse( wp_cache_get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_flush( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertTrue( wp_cache_flush() );
		$this->assertFalse( wp_cache_get( $key, $group ) );
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

		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group, false, $found ) );
		$this->assertTrue( $found );

		$this->assertFalse( wp_cache_get( 'test', $group ) );
		$this->assertFalse( wp_cache_get( 'test', $group, false, $found ) );
		$this->assertFalse( $found );
	}

	public function test_wp_cache_incr() {
		$key   = 'key';
		$group = 'group';

		$this->assertTrue( wp_cache_add( $key, 10, $group ) );
		$this->assertEquals( 13, wp_cache_incr( $key, 3, $group ) );
		$this->assertEquals( 14, wp_cache_incr( $key, 1, $group ) );
		$this->assertEquals( 14, wp_cache_get( $key, $group ) );
		$this->assertEquals( 0, wp_cache_incr( $key, - 45, $group ) );
		$this->assertEquals( 0, wp_cache_get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_replace( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertTrue( wp_cache_replace( $key, 'test', $group ) );
		$this->assertEquals( 'test', wp_cache_get( $key, $group ) );
		$this->assertFalse( wp_cache_replace( 'nokey', $data, $group ) );
		$this->assertFalse( wp_cache_get( 'nokey', $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_set( $data ) {
		$group = 'group';
		$key   = 0;

		$this->assertTrue( wp_cache_set( $key, $data, $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_switch_to_blog( $data ) {
		global $multisite;
		$multisite = true;
		wp_cache_init();
		$this->assertNull( wp_cache_switch_to_blog( 2 ) );
		$group = 'group';
		$key   = 'foo';

		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
		$this->assertNull( wp_cache_switch_to_blog( 3 ) );
		$this->assertFalse( wp_cache_get( $key, $group ) );
		$this->assertNull( wp_cache_switch_to_blog( 2 ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_add_global_groups( $data ) {
		global $multisite;
		$multisite = true;
		wp_cache_init();
		$group = 'group';
		$key   = 'foo';

		$this->assertNull( wp_cache_add_global_groups( $group ) );
		$this->assertNull( wp_cache_switch_to_blog( 2 ) );
		$this->assertTrue( wp_cache_add( $key, $data, $group ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
		$this->assertNull( wp_cache_switch_to_blog( 3 ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
		$this->assertNull( wp_cache_switch_to_blog( 2 ) );
		$this->assertEquals( $data, wp_cache_get( $key, $group ) );
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideTestData
	 */
	public function test_wp_cache_add_non_persistent_groups( $data ) {
		if ( $GLOBALS['wp_object_cache'] instanceof WP_Object_Cache ) {
			$this->markTestSkipped( 'Test Skipped for In Memory Cache implementation' );

			return;
		}

		$key = 'foo';

		$this->assertNull( wp_cache_add_non_persistent_groups( 'temp' ) );
		$this->assertTrue( wp_cache_add( $key, $data, 'temp' ) );
		$this->assertEquals( $data, wp_cache_get( $key, 'temp' ) );
		$this->assertTrue( wp_cache_add( $key, $data, 'group' ) );
		$this->assertEquals( $data, wp_cache_get( $key, 'group' ) );
		// Re-init the cache. This deletes the local cache but keeps the persistent one
		wp_cache_init();
		$this->assertFalse( wp_cache_get( $key, 'temp' ) );
		$this->assertEquals( $data, wp_cache_get( $key, 'group' ) );
	}

	public function test_wp_cache_reset() {
		$this->assertNull( wp_cache_reset() );
	}

	public function test_groups() {
		$key = 'foo';
		$this->assertTrue( wp_cache_add( $key, 'test1', 'group1' ) );
		$this->assertTrue( wp_cache_add( $key, 'test2', 'group2' ) );
		$this->assertEquals( 'test1', wp_cache_get( $key, 'group1' ) );
		$this->assertEquals( 'test2', wp_cache_get( $key, 'group2' ) );

		$this->assertTrue( wp_cache_set( $key, 'test12', 'group1' ) );
		$this->assertTrue( wp_cache_set( $key, 'test22', 'group2' ) );
		$this->assertEquals( 'test12', wp_cache_get( $key, 'group1' ) );
		$this->assertEquals( 'test22', wp_cache_get( $key, 'group2' ) );

		$this->assertTrue( wp_cache_delete( $key, 'group2' ) );
		$this->assertFalse( wp_cache_get( $key, 'group2' ) );
		$this->assertEquals( 'test12', wp_cache_get( $key, 'group1' ) );

		$this->assertTrue( wp_cache_replace( $key, 'test13', 'group1' ) );
		$this->assertEquals( 'test13', wp_cache_get( $key, 'group1' ) );
		$this->assertFalse( wp_cache_get( $key, 'group2' ) );
	}

	public function testSuspendCacheAddition() {
		global $suspend_cache;

		$suspend_cache = true;
		$group         = 'group';
		$key           = 0;

		$this->assertFalse( wp_cache_add( $key, 'some data', $group ) );
		$this->assertFalse( wp_cache_get( $key, $group ) );
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
