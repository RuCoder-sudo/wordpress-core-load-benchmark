<?php
require_once('wp-load.php');

echo "<h2>Проверка реальной работы Redis</h2>";

// Способ 1: Проверка через функцию плагина
if (function_exists('wp_redis_get_status')) {
    $status = wp_redis_get_status();
    echo "1. Статус плагина Redis: " . ($status ? '✅ Подключено' : '❌ Не подключено') . "<br>";
} else {
    echo "1. ❌ Функция wp_redis_get_status не найдена<br>";
}

// Способ 2: Проверка drop-in файла
$dropin_file = WP_CONTENT_DIR . '/object-cache.php';
if (file_exists($dropin_file)) {
    $dropin_data = get_file_data($dropin_file, ['Plugin Name' => 'Plugin Name', 'Version' => 'Version']);
    echo "2. Drop-in файл: ✅ Найден (" . ($dropin_data['Plugin Name'] ?? 'Unknown') . ")<br>";
} else {
    echo "2. Drop-in файл: ❌ Отсутствует<br>";
}

// Способ 3: Тест реального кэширования через WordPress API
$test_key = 'redis_real_test_' . time();
$test_value = 'test_value_' . rand(1000, 9999);

// Записываем в кэш через set_transient
set_transient($test_key, $test_value, 60);
echo "3. Запись в кэш: ✅ Выполнено (ключ: $test_key)<br>";

// Читаем из кэша
$retrieved_value = get_transient($test_key);
if ($retrieved_value === $test_value) {
    echo "4. Чтение из кэша: ✅ Значение совпадает ('$retrieved_value')<br>";
} else {
    echo "4. Чтение из кэша: ❌ Ошибка (ожидалось '$test_value', получено '$retrieved_value')<br>";
}

// Способ 4: Проверка через wp_cache
$cache_key = 'wp_cache_test_' . time();
$cache_value = ['data' => 'test', 'time' => time()];

wp_cache_set($cache_key, $cache_value, '', 60);
$cached_data = wp_cache_get($cache_key);

if ($cached_data === $cache_value) {
    echo "5. WP Cache работа: ✅ Корректная<br>";
} else {
    echo "5. WP Cache работа: ❌ Ошибка<br>";
}

// Способ 5: Проверка через прямой запрос к Redis
if (class_exists('Redis')) {
    $redis = new Redis();
    if ($redis->connect('127.0.0.1', 6379, 1)) {
        // Ищем ключи WordPress в Redis
        $keys = $redis->keys('*');
        $wp_keys = array_filter($keys, function($key) {
            return strpos($key, 'wphl_') === 0; // Префикс вашего сайта
        });
        
        echo "6. Ключей WordPress в Redis: " . count($wp_keys) . "<br>";
        
        if (count($wp_keys) > 0) {
            echo "   Примеры ключей: " . implode(', ', array_slice($wp_keys, 0, 5)) . "...<br>";
        }
    }
}

// Способ 6: Проверка статистики
global $wp_object_cache;
if (isset($wp_object_cache->cache_hits) || isset($wp_object_cache->stats)) {
    echo "7. Статистика кэша доступна<br>";
    
    if (isset($wp_object_cache->cache_hits)) {
        $hits = $wp_object_cache->cache_hits;
        $misses = $wp_object_cache->cache_misses ?? 0;
        $total = $hits + $misses;
        $ratio = $total > 0 ? ($hits / $total * 100) : 0;
        
        echo "   - Попаданий в кэш: $hits<br>";
        echo "   - Промахов: $misses<br>";
        echo "   - Эффективность: " . round($ratio, 2) . "%<br>";
    }
}

echo "<h3>Информация о системе:</h3>";
echo "PHP: " . PHP_VERSION . "<br>";
echo "Redis extension: " . (extension_loaded('redis') ? '✅' : '❌') . "<br>";
echo "WP_REDIS_CLIENT: " . (defined('WP_REDIS_CLIENT') ? WP_REDIS_CLIENT : 'Не определено') . "<br>";

// Проверка работы админки (имитация)
echo "<h3>Тест скорости типичных админских операций:</h3>";

// Тест 1: Загрузка списка пользователей
$start = microtime(true);
$users = get_users(['number' => 10]);
$time1 = microtime(true) - $start;
echo "- Получение 10 пользователей: " . round($time1, 4) . " сек<br>";

// Тест 2: Загрузка плагинов
$start = microtime(true);
$plugins = get_plugins();
$time2 = microtime(true) - $start;
echo "- Получение списка плагинов: " . round($time2, 4) . " сек<br>";

// Тест 3: Загрузка настроек
$start = microtime(true);
$options = get_options(['siteurl', 'home', 'blogname']);
$time3 = microtime(true) - $start;
echo "- Получение настроек: " . round($time3, 4) . " сек<br>";
