<?php

/**
 * This is a PHPUnit test class.
 */

global $wp_object_cache, $table_prefix, $blog_id, $multisite;
!defined('DB_HOST') && define('DB_HOST', 'localhost');
!defined('DB_NAME') && define('DB_NAME', 'wordpress');
function is_multisite()
{
    global $multisite;

    return isset($multisite) && $multisite;
}

function wp_suspend_cache_addition()
{
}

function _deprecated_function()
{
}

require(__DIR__ . '/object-cache.php');

class ObjectCacheTest extends PHPUnit_Framework_TestCase
{

    protected $backupGlobalsBlacklist = array('multisite');

    public function setUp()
    {
        wp_cache_init();
    }

    public function tearDown()
    {
        global $multisite;
        $multisite = false;
        wp_cache_flush();
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_add($data)
    {
        $group = 'group';
        $key = 0;

        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertEquals($data, wp_cache_get($key, $group));
        $this->assertFalse(wp_cache_add($key, 'test', $group));
        $this->assertEquals($data, wp_cache_get($key, $group));

    }

    public function test_wp_cache_close()
    {
        $this->assertTrue(wp_cache_close());
    }

    public function test_wp_cache_decr()
    {
        $key = 'key';
        $group = 'group';

        $this->assertTrue(wp_cache_add($key, 10, $group));
        $this->assertEquals(7, wp_cache_decr($key, 3, $group));
        $this->assertEquals(6, wp_cache_decr($key, 1, $group));
        $this->assertEquals(6, wp_cache_get($key, $group));
        $this->assertEquals(0, wp_cache_decr($key, 45, $group));
        $this->assertEquals(0, wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_delete($data)
    {
        $group = 'group';
        $key = 0;

        $this->assertFalse(wp_cache_delete($key, $group));
        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertTrue(wp_cache_delete($key, $group));
        $this->assertFalse(wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_flush($data)
    {
        $group = 'group';
        $key = 0;

        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertTrue(wp_cache_flush());
        $this->assertFalse(wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_get($data)
    {
        // TODO: test force parameter
        $group = 'group';
        $key = 0;

        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertEquals($data, wp_cache_get($key, $group));
        $this->assertEquals($data, wp_cache_get($key, $group, false, $found));
        $this->assertTrue($found);

        $this->assertFalse(wp_cache_get('test', $group));
        $this->assertFalse(wp_cache_get('test', $group, false, $found));
        $this->assertFalse($found);
    }

    public function test_wp_cache_incr()
    {
        $key = 'key';
        $group = 'group';

        $this->assertTrue(wp_cache_add($key, 10, $group));
        $this->assertEquals(13, wp_cache_incr($key, 3, $group));
        $this->assertEquals(14, wp_cache_incr($key, 1, $group));
        $this->assertEquals(14, wp_cache_get($key, $group));
        $this->assertEquals(0, wp_cache_incr($key, -45, $group));
        $this->assertEquals(0, wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_replace($data)
    {
        $group = 'group';
        $key = 0;

        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertTrue(wp_cache_replace($key, 'test', $group));
        $this->assertEquals('test', wp_cache_get($key, $group));
        $this->assertFalse(wp_cache_replace('nokey', $data, $group));
        $this->assertFalse(wp_cache_get('nokey', $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_set($data)
    {
        $group = 'group';
        $key = 0;

        $this->assertTrue(wp_cache_set($key, $data, $group));
        $this->assertEquals($data, wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_switch_to_blog($data)
    {
        global $multisite;
        $multisite = true;
        wp_cache_init();
        $this->assertNull(wp_cache_switch_to_blog(2));
        $group = 'group';
        $key = 'foo';

        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertEquals($data, wp_cache_get($key, $group));
        $this->assertNull(wp_cache_switch_to_blog(3));
        $this->assertFalse(wp_cache_get($key, $group));
        $this->assertNull(wp_cache_switch_to_blog(2));
        $this->assertEquals($data, wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_add_global_groups($data)
    {
        global $multisite;
        $multisite = true;
        wp_cache_init();
        $group = 'group';
        $key = 'foo';

        $this->assertNull(wp_cache_add_global_groups($group));
        $this->assertNull(wp_cache_switch_to_blog(2));
        $this->assertTrue(wp_cache_add($key, $data, $group));
        $this->assertEquals($data, wp_cache_get($key, $group));
        $this->assertNull(wp_cache_switch_to_blog(3));
        $this->assertEquals($data, wp_cache_get($key, $group));
        $this->assertNull(wp_cache_switch_to_blog(2));
        $this->assertEquals($data, wp_cache_get($key, $group));
    }

    /**
     * @param mixed $data
     * @dataProvider getTestData
     */
    public function test_wp_cache_add_non_persistent_groups($data)
    {
        if ($GLOBALS['wp_object_cache'] instanceof WP_Object_Cache) {
            $this->markTestSkipped('Test Skipped for In Memory Cache implementation');
            return;
        }

        $key = 'foo';

        $this->assertNull(wp_cache_add_non_persistent_groups('temp'));
        $this->assertTrue(wp_cache_add($key, $data, 'temp'));
        $this->assertEquals($data, wp_cache_get($key, 'temp'));
        $this->assertTrue(wp_cache_add($key, $data, 'group'));
        $this->assertEquals($data, wp_cache_get($key, 'group'));
        // Re-init the cache. This deletes the local cache but keeps the persistent one
        wp_cache_init();
        $this->assertFalse(wp_cache_get($key, 'temp'));
        $this->assertEquals($data, wp_cache_get($key, 'group'));
    }

    public function test_wp_cache_reset()
    {
        $this->assertNull(wp_cache_reset());
    }

    public function test_groups()
    {
        $key = 'foo';
        $this->assertTrue(wp_cache_add($key, 'test1', 'group1'));
        $this->assertTrue(wp_cache_add($key, 'test2', 'group2'));
        $this->assertEquals('test1', wp_cache_get($key, 'group1'));
        $this->assertEquals('test2', wp_cache_get($key, 'group2'));

        $this->assertTrue(wp_cache_set($key, 'test12', 'group1'));
        $this->assertTrue(wp_cache_set($key, 'test22', 'group2'));
        $this->assertEquals('test12', wp_cache_get($key, 'group1'));
        $this->assertEquals('test22', wp_cache_get($key, 'group2'));

        $this->assertTrue(wp_cache_delete($key, 'group2'));
        $this->assertFalse(wp_cache_get($key, 'group2'));
        $this->assertEquals('test12', wp_cache_get($key, 'group1'));

        $this->assertTrue(wp_cache_replace($key, 'test13', 'group1'));
        $this->assertEquals('test13', wp_cache_get($key, 'group1'));
        $this->assertFalse(wp_cache_get($key, 'group2'));
    }

    /**
     * @return array
     */
    public function getTestData()
    {
        return array(
            array('foo'),
            array(43),
            array(1.234),
            array(1.2e3),
            array(7E-10),
            array(0123),
            array(-123),
            array(0x1A),
            array(array('a', 'b')),
            array(array('foo' => array('a', 'b'))),
            array(null),
            array(false),
            array(true),
            array(new ArrayObject(array('foo', 'bar'))),
            array(new StdClass())
        );
    }
}
