<?php

/**
 * Plugin Name: APCu Object Cache Backend
 * Description: APCu backend for the WordPress Object Cache.
 * Version: 2.0.0
 * Author: Pierre Schmitz
 * Author URI: https://pierre-schmitz.com/
 * Plugin URI: https://wordpress.org/plugins/apcu/
 */

if ( function_exists( 'wp_cache_add' ) ) {
	throw new \RuntimeException(
		'<strong>ERROR:</strong> This is <em>not</em> a plugin, and it should not be activated as one.<br /><br />Instead, <code>'
		. str_replace( $_SERVER['DOCUMENT_ROOT'], '', __FILE__ )
		. '</code> must be moved to <code>'
		. str_replace( $_SERVER['DOCUMENT_ROOT'], '', WP_CONTENT_DIR . '/' )
		. 'object-cache.php</code>'
	);
} else { // We cannot redeclare these functions if cache.php was loaded. Declaration must be kept dynamic.
	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;

		return $wp_object_cache->add( $key, $data, $group, (int) $expire );
	}

	function wp_cache_close() {
		return true;
	}

	function wp_cache_decr( $key, $offset = 1, $group = '' ) {
		global $wp_object_cache;

		return $wp_object_cache->decr( $key, $offset, $group );
	}

	function wp_cache_delete( $key, $group = '' ) {
		global $wp_object_cache;

		return $wp_object_cache->delete( $key, $group );
	}

	function wp_cache_flush() {
		global $wp_object_cache;

		return $wp_object_cache->flush();
	}

	function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
		global $wp_object_cache;

		return $wp_object_cache->get( $key, $group, $force, $found );
	}

	function wp_cache_incr( $key, $offset = 1, $group = '' ) {
		global $wp_object_cache;

		return $wp_object_cache->incr( $key, $offset, $group );
	}

	function wp_cache_init() {
		if ( ! function_exists( 'apcu_fetch' ) ) {
			$error = 'APCu is not configured correctly. Please refer to https://wordpress.org/extend/plugins/apcu/installation/ for instructions.';

			if ( function_exists( 'wp_die' ) ) {
				wp_die( $error, 'APCu Object Cache', [ 'response' => 503 ] );
			} else {
				header( 'HTTP/1.0 503 Service Unavailable' );
				header( 'Content-Type: text/plain; charset=UTF-8' );
				throw new \RuntimeException( $error );
			}
		}

		$GLOBALS['wp_object_cache'] = new APCu_Object_Cache();
	}

	function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;

		return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
	}

	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		global $wp_object_cache;

		return $wp_object_cache->set( $key, $data, $group, (int) $expire );
	}

	function wp_cache_switch_to_blog( $blog_id ) {
		global $wp_object_cache;

		$wp_object_cache->switch_to_blog( $blog_id );
	}

	function wp_cache_add_global_groups( $groups ) {
		global $wp_object_cache;

		$wp_object_cache->add_global_groups( $groups );
	}

	function wp_cache_add_non_persistent_groups( $groups ) {
		global $wp_object_cache;

		$wp_object_cache->add_non_persistent_groups( $groups );
	}

	function wp_cache_reset() {
		global $wp_object_cache;

		$wp_object_cache->reset();
	}
}

class APCu_Object_Cache {

	/** @var string */
	private $prefix = '';
	/** @var array */
	private $local_cache = [];
	/** @var array */
	private $global_groups = [];
	/** @var array */
	private $non_persistent_groups = [];
	/** @var bool */
	private $multisite = false;
	/** @var string */
	private $blog_prefix = '';

	public function __construct() {
		global $table_prefix;

		$this->multisite   = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';
		$this->prefix      = DB_HOST . '.' . DB_NAME . '.' . $table_prefix;
	}

	/**
	 * @param string $key
	 * @param        $data
	 * @param string $group
	 * @param int    $expire
	 *
	 * @return bool
	 */
	public function add( string $key, $data, string $group = 'default', int $expire = 0 ): bool {
		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		if ( function_exists( 'wp_suspend_cache_addition' ) && wp_suspend_cache_addition() ) {
			return false;
		}
		if ( isset( $this->local_cache[ $group ][ $key ] ) ) {
			return false;
		}
		// FIXME: Somehow apcu_add does not return false if key already exists
		if ( ! isset( $this->non_persistent_groups[ $group ] ) && apcu_exists( $key ) ) {
			return false;
		}

		if ( is_object( $data ) ) {
			$this->local_cache[ $group ][ $key ] = clone $data;
		} else {
			$this->local_cache[ $group ][ $key ] = $data;
		}

		if ( ! isset( $this->non_persistent_groups[ $group ] ) ) {
			return apcu_add( $key, $data, (int) $expire );
		}

		return true;
	}

	/**
	 * @param string $group
	 *
	 * @return string
	 */
	private function get_group( string $group ): string {
		return empty( $group ) ? 'default' : $group;
	}

	/**
	 * @param string $group
	 * @param string $key
	 *
	 * @return string
	 */
	private function get_key( string $group, string $key ): string {
		if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
			return $this->prefix . '.' . $group . '.' . $this->blog_prefix . ':' . $key;
		} else {
			return $this->prefix . '.' . $group . '.' . $key;
		}
	}

	/**
	 * @param string|array $groups
	 */
	public function add_global_groups( $groups ): void {
		if ( is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				$this->global_groups[ $group ] = true;
			}
		} else {
			$this->global_groups[ $groups ] = true;
		}
	}

	/**
	 * @param string|array $groups
	 */
	public function add_non_persistent_groups( $groups ): void {
		if ( is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				$this->non_persistent_groups[ $group ] = true;
			}
		} else {
			$this->non_persistent_groups[ $groups ] = true;
		}
	}

	/**
	 * @param string $key
	 * @param int    $offset
	 * @param string $group
	 *
	 * @return int
	 */
	public function decr( string $key, int $offset = 1, string $group = 'default' ): int {
		if ( $offset < 0 ) {
			return $this->incr( $key, abs( $offset ), $group );
		}

		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		if ( isset( $this->local_cache[ $group ][ $key ] ) && $this->local_cache[ $group ][ $key ] - $offset >= 0 ) {
			$this->local_cache[ $group ][ $key ] -= $offset;
		} else {
			$this->local_cache[ $group ][ $key ] = 0;
		}

		if ( isset( $this->non_persistent_groups[ $group ] ) ) {
			return $this->local_cache[ $group ][ $key ];
		} else {
			$value = apcu_dec( $key, $offset );
			if ( $value < 0 ) {
				apcu_store( $key, 0 );

				return 0;
			}

			return $value;
		}
	}

	/**
	 * @param string $key
	 * @param int    $offset
	 * @param string $group
	 *
	 * @return int
	 */
	public function incr( string $key, int $offset = 1, string $group = 'default' ): int {
		if ( $offset < 0 ) {
			return $this->decr( $key, abs( $offset ), $group );
		}

		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		if ( isset( $this->local_cache[ $group ][ $key ] ) && $this->local_cache[ $group ][ $key ] + $offset >= 0 ) {
			$this->local_cache[ $group ][ $key ] += $offset;
		} else {
			$this->local_cache[ $group ][ $key ] = 0;
		}

		if ( isset( $this->non_persistent_groups[ $group ] ) ) {
			return $this->local_cache[ $group ][ $key ];
		} else {
			$value = apcu_inc( $key, $offset );
			if ( $value < 0 ) {
				apcu_store( $key, 0 );

				return 0;
			}

			return $value;
		}
	}

	/**
	 * @param string $key
	 * @param string $group
	 * @param bool   $force
	 *
	 * @return bool
	 */
	public function delete( string $key, string $group = 'default', bool $force = false ): bool {
		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		unset( $this->local_cache[ $group ][ $key ] );
		if ( ! isset( $this->non_persistent_groups[ $group ] ) ) {
			return apcu_delete( $key );
		}

		return true;
	}

	/**
	 * @param string    $key
	 * @param string    $group
	 * @param bool      $force
	 * @param bool|null $found
	 *
	 * @return bool|mixed
	 */
	public function get( string $key, string $group = 'default', bool $force = false, bool &$found = null ) {
		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		if ( ! $force && isset( $this->local_cache[ $group ][ $key ] ) ) {
			$found = true;
			if ( is_object( $this->local_cache[ $group ][ $key ] ) ) {
				return clone $this->local_cache[ $group ][ $key ];
			} else {
				return $this->local_cache[ $group ][ $key ];
			}
		} elseif ( isset( $this->non_persistent_groups[ $group ] ) ) {
			$found = false;

			return false;
		} else {
			$value = apcu_fetch( $key, $found );
			if ( $found ) {
				if ( $force ) {
					$this->local_cache[ $group ][ $key ] = $value;
				}

				return $value;
			} else {
				return false;
			}
		}
	}

	/**
	 * @param string $key
	 * @param        $data
	 * @param string $group
	 * @param int    $expire
	 *
	 * @return bool
	 */
	public function replace( string $key, $data, string $group = 'default', int $expire = 0 ): bool {
		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		if ( isset( $this->non_persistent_groups[ $group ] ) ) {
			if ( ! isset( $this->local_cache[ $group ][ $key ] ) ) {
				return false;
			}
		} else {
			if ( ! isset( $this->local_cache[ $group ][ $key ] ) && ! apcu_exists( $key ) ) {
				return false;
			}
			apcu_store( $key, $data, (int) $expire );
		}

		if ( is_object( $data ) ) {
			$this->local_cache[ $group ][ $key ] = clone $data;
		} else {
			$this->local_cache[ $group ][ $key ] = $data;
		}

		return true;
	}

	public function reset(): void {
		// This function is deprecated as of WordPress 3.5
		// Be safe and flush the cache if this function is still used
		$this->flush();
	}

	/**
	 * @return bool
	 */
	public function flush(): bool {
		$this->local_cache = [];
		// TODO: only clear our own entries
		apcu_clear_cache();

		return true;
	}

	/**
	 * @param string $key
	 * @param        $data
	 * @param string $group
	 * @param int    $expire
	 *
	 * @return bool
	 */
	public function set( string $key, $data, string $group = 'default', int $expire = 0 ): bool {
		$group = $this->get_group( $group );
		$key   = $this->get_key( $group, $key );

		if ( is_object( $data ) ) {
			$this->local_cache[ $group ][ $key ] = clone $data;
		} else {
			$this->local_cache[ $group ][ $key ] = $data;
		}

		if ( ! isset( $this->non_persistent_groups[ $group ] ) ) {
			return apcu_store( $key, $data, (int) $expire );
		}

		return true;
	}

	/**
	 * @param int $blog_id
	 */
	public function switch_to_blog( int $blog_id ): void {
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
	}
}
