<?php
/**
 * Exercises PP_Cache and the PP_Helpers cache index against stubbed WordPress functions.
 * Run from the repository root:  php tests/test-cache.php
 *
 * These stubs are deliberately minimal - just enough of WordPress to exercise the
 * decision logic in PP_Cache and the cache key index in PP_Helpers without a database.
 */

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);

// ---------------------------------------------------------------- WordPress stubs
$GLOBALS['options']    = array();
$GLOBALS['transients'] = array();
$GLOBALS['actions']    = array();
$GLOBALS['fired']      = array();
$GLOBALS['posts']      = array();   // id => array(type, is_revision, is_autosave)
$GLOBALS['sql']        = array();

function add_action($hook, $cb, $prio = 10, $args = 1) { $GLOBALS['actions'][$hook][] = $cb; }
function do_action($hook) { $GLOBALS['fired'][] = $hook; }
function apply_filters($hook, $value) { return $value; }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['options']) ? $GLOBALS['options'][$name] : $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['options'][$name] = $value; return true; }
function delete_option($name) { unset($GLOBALS['options'][$name]); return true; }
function set_transient($k, $v, $exp = 0) { $GLOBALS['transients'][$k] = $v; return true; }
function get_transient($k) { return array_key_exists($k, $GLOBALS['transients']) ? $GLOBALS['transients'][$k] : false; }
function delete_transient($k) { if (array_key_exists($k, $GLOBALS['transients'])) { unset($GLOBALS['transients'][$k]); return true; } return false; }
function get_post_type($id) { return isset($GLOBALS['posts'][$id]) ? $GLOBALS['posts'][$id]['type'] : false; }
function wp_is_post_revision($id) { return !empty($GLOBALS['posts'][$id]['is_revision']); }
function wp_is_post_autosave($id) { return !empty($GLOBALS['posts'][$id]['is_autosave']); }
function sanitize_key($k) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $k)); }
function __($s, $d = null) { return $s; }

class WP_Post { public $post_type; public $ID; }

class FakeWpdb {
    public $options = 'wp_options';
    public function prepare($q, $a) { return str_replace('%s', "'" . $a . "'", $q); }
    public function query($q) { $GLOBALS['sql'][] = $q; return 0; }
}
$GLOBALS['wpdb'] = new FakeWpdb();

require __DIR__ . '/../includes/class-helpers.php';
require __DIR__ . '/../includes/class-cache.php';

// ---------------------------------------------------------------- test harness
$pass = 0; $fail = 0;
function check($label, $cond) {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS  $label\n"; }
    else { $fail++; echo "  FAIL  $label\n"; }
}

function reset_state($settings = array()) {
    $GLOBALS['options']    = $settings ? array('pp_settings' => $settings) : array();
    $GLOBALS['transients'] = array();
    $GLOBALS['fired']      = array();
    $GLOBALS['sql']        = array();
    $ref = new ReflectionClass('PP_Cache');
    $prop = $ref->getProperty('cleared_this_request');
    $prop->setAccessible(true);
    $prop->setValue(null, false);
}

function seed_cache($n = 3) {
    for ($i = 0; $i < $n; $i++) {
        PP_Helpers::cache_query('pp_query_' . md5('grid' . $i), array('posts' => array()), 15);
    }
}

echo "Cache key index\n";
reset_state();
seed_cache(3);
check('3 transients written', count($GLOBALS['transients']) === 3);
check('index records 3 keys', count(get_option('pp_cache_keys', array())) === 3);
PP_Helpers::cache_query('pp_query_' . md5('grid0'), array(), 15);
check('duplicate key not double-recorded', count(get_option('pp_cache_keys', array())) === 3);
PP_Helpers::cache_query('pp_query_nope', array(), 0);
check('cache_minutes=0 writes nothing', count($GLOBALS['transients']) === 3);

$cleared = PP_Helpers::clear_all_cache();
check('clear_all_cache removed all transients', count($GLOBALS['transients']) === 0);
check('clear_all_cache reported 3 cleared', $cleared === 3);
check('index option removed', get_option('pp_cache_keys', null) === null);
check('options-table fallback sweep still ran', count($GLOBALS['sql']) === 2);

echo "\nSetting: is_enabled()\n";
reset_state();
check('defaults to enabled when unset (existing installs)', PP_Cache::is_enabled() === true);
reset_state(array('auto_clear_cache' => true));
check('enabled when true', PP_Cache::is_enabled() === true);
reset_state(array('auto_clear_cache' => false));
check('disabled when false', PP_Cache::is_enabled() === false);

echo "\nsave_post behaviour\n";
$cache = new PP_Cache();
$GLOBALS['posts'] = array(
    1 => array('type' => 'post'),
    2 => array('type' => 'page'),
    3 => array('type' => 'post', 'is_revision' => true),
    4 => array('type' => 'post', 'is_autosave' => true),
);

reset_state(); seed_cache(2);
$cache->on_save_post(1);
check('saving a post clears the cache', count($GLOBALS['transients']) === 0);

reset_state(); seed_cache(2);
$cache->on_save_post(2);
check('saving a page leaves the cache alone', count($GLOBALS['transients']) === 2);

reset_state(); seed_cache(2);
$cache->on_save_post(3);
check('a revision does not clear the cache', count($GLOBALS['transients']) === 2);

reset_state(); seed_cache(2);
$cache->on_save_post(4);
check('an autosave does not clear the cache', count($GLOBALS['transients']) === 2);

reset_state(array('auto_clear_cache' => false)); seed_cache(2);
$cache->on_save_post(1);
check('setting off means no clearing', count($GLOBALS['transients']) === 2);

echo "\nPost object passed by the hook\n";
reset_state(); seed_cache(2);
$wp_post = new WP_Post(); $wp_post->post_type = 'page'; $wp_post->ID = 99;
$cache->on_save_post(99, $wp_post);
check('post type read from the object, page ignored', count($GLOBALS['transients']) === 2);

reset_state(); seed_cache(2);
$wp_post2 = new WP_Post(); $wp_post2->post_type = 'post'; $wp_post2->ID = 98;
$cache->on_save_post(98, $wp_post2);
check('post type read from the object, post clears', count($GLOBALS['transients']) === 0);

echo "\nTrash / untrash / delete\n";
reset_state(); seed_cache(2);
$cache->on_post_removed(1);
check('trashing a post clears the cache', count($GLOBALS['transients']) === 0);

reset_state(); seed_cache(2);
$cache->on_post_removed(2);
check('trashing a page leaves the cache alone', count($GLOBALS['transients']) === 2);

reset_state(); seed_cache(2);
$cache->on_post_removed(12345); // fully deleted: get_post_type() returns false
check('fully deleted post still clears the cache', count($GLOBALS['transients']) === 0);

echo "\nOnce per request\n";
reset_state(); seed_cache(2);
$cache->on_save_post(1);
$first_sql = count($GLOBALS['sql']);
for ($i = 0; $i < 50; $i++) { $cache->on_save_post(1); }
check('50 further saves do no extra work (bulk import safe)', count($GLOBALS['sql']) === $first_sql);

echo "\nforce_clear ignores the setting\n";
reset_state(array('auto_clear_cache' => false)); seed_cache(3);
$n = PP_Cache::force_clear();
check('force_clear works with the setting off', count($GLOBALS['transients']) === 0);
check('force_clear reports 3 cleared', $n === 3);

echo "\nHook registration\n";
$GLOBALS['actions'] = array();
new PP_Cache();
foreach (array('save_post', 'deleted_post', 'trashed_post', 'untrashed_post') as $hook) {
    check("hooks $hook", isset($GLOBALS['actions'][$hook]));
}

echo "\npp_cache_cleared action\n";
reset_state(); seed_cache(1);
$cache->on_save_post(1);
check('fires pp_cache_cleared', in_array('pp_cache_cleared', $GLOBALS['fired'], true));

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
