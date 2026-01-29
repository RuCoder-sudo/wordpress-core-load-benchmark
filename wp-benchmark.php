<?php
// Измерим время загрузки ядра WordPress
$start = microtime(true);
require_once('wp-load.php');
$core_load = microtime(true) - $start;

echo "<h2>Тест скорости WordPress</h2>";
echo "Ядро WordPress загрузилось за: " . round($core_load, 3) . " сек<br>";

// Проверим активные плагины
$active_plugins = get_option('active_plugins');
echo "Активных плагинов: " . count($active_plugins) . "<br>";

// Проверим Redis
if (class_exists('Redis')) {
    echo "Redis PHP extension: ✓ Установлен<br>";
    
    try {
        $redis = new Redis();
        if ($redis->connect('127.0.0.1', 6379)) {
            echo "Redis соединение: ✓ Работает<br>";
            echo "Ping: " . $redis->ping() . "<br>";
        }
    } catch (Exception $e) {
        echo "Redis ошибка: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Redis PHP extension: ✗ Отсутствует<br>";
}

// Проверим память
echo "<h3>Память:</h3>";
echo "Использовано: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
echo "Пиковое: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB<br>";

// Проверим, какой объектный кэш используется
global $wp_object_cache;
if (isset($wp_object_cache) && is_object($wp_object_cache)) {
    echo "Объектный кэш: " . get_class($wp_object_cache) . "<br>";
}

// Простой тест запросов
echo "<h3>Простой тест запросов:</h3>";
$query_start = microtime(true);
$post_count = wp_count_posts()->publish;
$query_time = microtime(true) - $query_start;
echo "Запрос на подсчет постов: " . round($query_time, 4) . " сек (постов: $post_count)<br>"; 
