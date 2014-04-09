<?php

/**
 * Plugin Name: APCu Object Cache Backend
 * Description: APCu backend for the WordPress Object Cache.
 * Version: 1.0.0
 * Author: Pierre Schmitz
 * Author URI: https://pierre-schmitz.com/
 * Plugin URI: https://wordpress.org/extend/plugins/apcu/
 */

if (function_exists( 'wp_cache_add')) {
	// Regular die, not wp_die(), because it gets sandboxed and shown in a small iframe
	die('<strong>ERROR:</strong> This is <em>not</em> a plugin, and it should not be activated as one.<br /><br />Instead, <code>' . str_replace( $_SERVER['DOCUMENT_ROOT'], '', __FILE__ ) . '</code> must be moved to <code>' . str_replace( $_SERVER['DOCUMENT_ROOT'], '', trailingslashit( WP_CONTENT_DIR ) ) . 'object-cache.php</code>' );
} else {

function wp_cache_add($key, $data, $group = '', $expire = 0) {
	global $wp_object_cache;

	return $wp_object_cache->add($key, $data, $group, (int) $expire);
}

function wp_cache_close() {
	return true;
}

function wp_cache_decr($key, $offset = 1, $group = '') {
	global $wp_object_cache;

	return $wp_object_cache->decr($key, $offset, $group);
}

function wp_cache_delete($key, $group = '') {
	global $wp_object_cache;

	return $wp_object_cache->delete($key, $group);
}

function wp_cache_flush() {
	global $wp_object_cache;

	return $wp_object_cache->flush();
}

function wp_cache_get($key, $group = '', $force = false, &$found = null) {
	global $wp_object_cache;

	return $wp_object_cache->get($key, $group, $force, $found);
}

function wp_cache_incr($key, $offset = 1, $group = '') {
	global $wp_object_cache;

	return $wp_object_cache->incr($key, $offset, $group);
}

function wp_cache_init() {
	if (!function_exists('apcu_fetch')) {
		$error = 'APCu is not configured correctly. Please refer to https://wordpress.org/extend/plugins/apcu/installation/ for instructions.';
		if (function_exists('wp_die')) {
			wp_die($error, 'APCu Object Cache', array('response' => 503));
		} else {
			header('HTTP/1.0 503 Service Unavailable');
			header('Content-Type: text/plain; charset=UTF-8');
			die($error);
		}
	} else {
		$GLOBALS['wp_object_cache'] = new APCu_Object_Cache();
	}
}

function wp_cache_replace($key, $data, $group = '', $expire = 0) {
	global $wp_object_cache;

	return $wp_object_cache->replace($key, $data, $group, (int) $expire);
}

function wp_cache_set($key, $data, $group = '', $expire = 0) {
	global $wp_object_cache;

	return $wp_object_cache->set($key, $data, $group, (int) $expire);
}

function wp_cache_switch_to_blog($blog_id) {
	global $wp_object_cache;

	return $wp_object_cache->switch_to_blog($blog_id);
}

function wp_cache_add_global_groups($groups) {
	global $wp_object_cache;

	return $wp_object_cache->add_global_groups($groups);
}

function wp_cache_add_non_persistent_groups($groups) {
	global $wp_object_cache;

	return $wp_object_cache->wp_cache_add_non_persistent_groups($groups);
}

function wp_cache_reset() {
	global $wp_object_cache;

	return $wp_object_cache->reset();
}

class APCu_Object_Cache {

	private $prefix = '';
	private $local_cache = array();
	private $global_groups = array();
	private $non_persistent_groups = array();
	private $multisite = false;
	private $blog_prefix = '';

	public function __construct() {
		global $table_prefix, $blog_id;

		$this->multisite = is_multisite();
		$this->blog_prefix =  $this->multisite ? intval($blog_id) : '';
		$this->prefix = DB_HOST.'.'.DB_NAME.'.'.$table_prefix;
	}

	private function get_group($group) {
		return empty($group) ? 'default' : $group;
	}

	private function get_key($group, $key) {
		if ($this->multisite && !isset($this->global_groups[$group])) {
			return $this->prefix.'.'.$group.'.'.$this->blog_prefix.':'.$key;
		} else {
			return $this->prefix.'.'.$group.'.'.$key;
		}
	}

	public function add($key, $data, $group = 'default', $expire = 0) {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		if (function_exists('wp_suspend_cache_addition') && wp_suspend_cache_addition()) {
			return false;
		}
		if (isset($this->local_cache[$group][$key])) {
			return false;
		}
		// FIXME: Somehow apcu_add does not return false if key already exists
		if (!isset($this->non_persistent_groups[$group]) && apcu_exists($key)) {
			return false;
		}

		if (is_object($data)) {
			$this->local_cache[$group][$key] = clone $data;
		} else {
			$this->local_cache[$group][$key] = $data;
		}

		if (!isset($this->non_persistent_groups[$group])) {
			return apcu_add($key, $data, (int) $expire);
		}

		return true;
	}

	public function add_global_groups($groups) {
		if (is_array($groups)) {
			foreach ($groups as $group) {
				$this->global_groups[$group] = true;
			}
		} else {
			$this->global_groups[$groups] = true;
		}
	}

	public function wp_cache_add_non_persistent_groups($groups) {
		if (is_array($groups)) {
			foreach ($groups as $group) {
				$this->non_persistent_groups[$group] = true;
			}
		} else {
			$this->non_persistent_groups[$groups] = true;
		}
	}

	public function decr($key, $offset = 1, $group = 'default') {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		if (isset($this->local_cache[$group][$key]) && $this->local_cache[$group][$key] - $offset >= 0) {
			$this->local_cache[$group][$key] -= $offset;
		} else {
			$this->local_cache[$group][$key] = 0;
		}

		if (isset($this->non_persistent_groups[$group])) {
			return $this->local_cache[$group][$key];
		} else {
			$value = apcu_dec($key, $offset);
			if ($value < 0) {
				apcu_store($key, 0);
				return 0;
			}
			return $value;
		}
	}

	public function delete($key, $group = 'default', $force = false) {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		unset($this->local_cache[$group][$key]);
		if (!isset($this->non_persistent_groups[$group])) {
			return apcu_delete($key);
		}
		return true;
	}

	public function flush() {
		$this->local_cache = array ();
		// TODO: only clear our own entries
		apcu_clear_cache();
		return true;
	}

	public function get($key, $group = 'default', $force = false, &$found = null) {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		if (!$force && isset($this->local_cache[$group][$key])) {
			$found = true;
			if (is_object($this->local_cache[$group][$key])) {
				return clone $this->local_cache[$group][$key];
			} else {
				return $this->local_cache[$group][$key];
			}
		} elseif (isset($this->non_persistent_groups[$group])) {
			$found = false;
			return false;
		} else {
			$value = apcu_fetch($key, $found);
			if ($found) {
				if ($force) {
					$this->local_cache[$group][$key] = $value;
				}
				return $value;
			} else {
				return false;
			}
		}
	}

	public function incr($key, $offset = 1, $group = 'default') {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		if (isset($this->local_cache[$group][$key]) && $this->local_cache[$group][$key] + $offset >= 0) {
			$this->local_cache[$group][$key] += $offset;
		} else {
			$this->local_cache[$group][$key] = 0;
		}

		if (isset($this->non_persistent_groups[$group])) {
			return $this->local_cache[$group][$key];
		} else {
			$value = apcu_inc($key, $offset);
			if ($value < 0) {
				apcu_store($key, 0);
				return 0;
			}
			return $value;
		}
	}

	public function replace($key, $data, $group = 'default', $expire = 0) {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		if (isset($this->non_persistent_groups[$group])) {
			if (!isset($this->local_cache[$group][$key])) {
				return false;
			}
		} else {
			if (!isset($this->local_cache[$group][$key]) && !apcu_exists($key)) {
				return false;
			}
			apcu_store($key, $data, (int) $expire);
		}

		if (is_object($data)) {
			$this->local_cache[$group][$key] = clone $data;
		} else {
			$this->local_cache[$group][$key] = $data;
		}

		return true;
	}

	public function reset() {
		// This function is deprecated as of WordPress 3.5
		// Be safe and flush the cache if this function is still used
		$this->flush();
	}

	public function set($key, $data, $group = 'default', $expire = 0) {
		$group = $this->get_group($group);
		$key = $this->get_key($group, $key);

		if (is_object($data)) {
			$this->local_cache[$group][$key] = clone $data;
		} else {
			$this->local_cache[$group][$key] = $data;
		}

		if (!isset($this->non_persistent_groups[$group])) {
			return apcu_store($key, $data, (int) $expire);
		}

		return true;
	}

	public function stats() {
		// Only implemented because the default cache class provides this.
		// This method is never called.
		echo '';
	}

	public function switch_to_blog($blog_id) {
		$this->blog_prefix = $this->multisite ? intval($blog_id) : '';
	}

}

}

?>
