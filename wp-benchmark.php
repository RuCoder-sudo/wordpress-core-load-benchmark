<?php
define('WP_USE_THEMES', false);

/**
 * Старт таймера ДО загрузки WP
 */
$start = microtime(true);
require_once __DIR__ . '/wp-load.php';
$load_time = microtime(true) - $start;

global $wpdb, $wp_object_cache;

echo '<h2>WordPress Performance Check</h2>';

/**
 * Время загрузки ядра
 */
echo '<strong>Load time:</strong> ' . round($load_time, 3) . " sec<br>";

/**
 * Плагины
 */
$active_plugins = (array) get_option('active_plugins', []);
echo '<strong>Active plugins:</strong> ' . count($active_plugins) . '<br>';

/**
 * Object Cache
 */
echo '<strong>Object cache:</strong> ';
if (wp_using_ext_object_cache()) {
    echo '✓ External (' . get_class($wp_object_cache) . ')<br>';
} else {
    echo '✗ Default WP cache<br>';
}

/**
 * Redis (через Object Cache, а не просто extension)
 */
if (class_exists('Redis')) {
    echo '<strong>Redis PHP extension:</strong> ✓ Installed<br>';
} else {
    echo '<strong>Redis PHP extension:</strong> ✗ Not installed<br>';
}

/**
 * База данных
 */
echo '<h3>Database</h3>';
echo '<strong>Queries:</strong> ' . get_num_queries() . '<br>';
echo '<strong>Query time:</strong> ' . round($wpdb->timer_stop(false), 4) . " sec<br>";

/**
 * Тест запроса
 */
$q_start = microtime(true);
$post_count = wp_count_posts('post')->publish;
$q_time = microtime(true) - $q_start;

echo '<strong>Post count query:</strong> ' . round($q_time, 4) . " sec ($post_count posts)<br>";

/**
 * Память
 */
echo '<h3>Memory</h3>';
echo '<strong>Usage:</strong> ' . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
echo '<strong>Peak:</strong> ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB<br>";

/**
 * PHP
 */
echo '<h3>PHP</h3>';
echo '<strong>Version:</strong> ' . PHP_VERSION . '<br>';
echo '<strong>OPcache:</strong> ' . (function_exists('opcache_get_status') && opcache_get_status(false) ? '✓ Enabled' : '✗ Disabled') . '<br>';
