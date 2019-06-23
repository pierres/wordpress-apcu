<?php

! defined( 'DB_HOST' ) && define( 'DB_HOST', 'localhost' );
! defined( 'DB_NAME' ) && define( 'DB_NAME', 'WordPress' );

function is_multisite() {
	return false;
}

function get_current_blog_id() {
	return 0;
}

function wp_suspend_cache_addition() {
	return true;
}

class ObjectCacheInitTest extends \PHPUnit\Framework\TestCase {

	public function test_wp_cache_init() {
		/** @var $wp_object_cache APCu_Object_Cache */
		global $wp_object_cache;

		wp_cache_init();

		$this->assertInstanceOf( APCu_Object_Cache::class, $wp_object_cache );
		$this->assertFalse( $wp_object_cache->add( 'foo', 'bar' ) );
	}
}
