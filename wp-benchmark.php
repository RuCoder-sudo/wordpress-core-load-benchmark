<?php
/**
 * WordPress Performance Benchmark Tool
 * Version: 2.0
 * 
 * WARNING: Remove this file from production servers after testing!
 * Этот файл должен быть удален с продакшн-серверов после тестирования!
 */

// Prevent direct access with warning
if (count(get_included_files()) === 1) {
    die('<h1>WordPress Performance Benchmark</h1>
         <p>This file must be placed in WordPress root directory</p>
         <p>Этот файл должен быть размещен в корневой директории WordPress</p>');
}

// Start total benchmark
$total_start = microtime(true);
$benchmark_results = [];

/**
 * 1. CORE LOAD TEST
 */
$start = microtime(true);
if (!defined('ABSPATH')) {
    require_once('wp-load.php');
}
$core_load = microtime(true) - $start;
$benchmark_results['core_load'] = $core_load;

/**
 * 2. ENVIRONMENT CHECK
 */
$benchmark_results['environment'] = [
    'php_version' => PHP_VERSION,
    'php_memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'wordpress_version' => get_bloginfo('version'),
    'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
    'script_memory_usage' => round(memory_get_usage() / 1024 / 1024, 2)
];

/**
 * 3. DATABASE PERFORMANCE TESTS
 */
$db_tests = [];
global $wpdb;

// Test 1: Simple query
$start = microtime(true);
$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish'");
$db_tests['simple_query'] = microtime(true) - $start;

// Test 2: Complex query with JOIN
$start = microtime(true);
$wpdb->get_results("
    SELECT p.ID, p.post_title, u.user_login 
    FROM {$wpdb->posts} p 
    LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID 
    WHERE p.post_type = 'post' 
    LIMIT 10
");
$db_tests['complex_query'] = microtime(true) - $start;

// Test 3: Meta query
$start = microtime(true);
$wpdb->get_results("
    SELECT meta_key, COUNT(*) as count 
    FROM {$wpdb->postmeta} 
    GROUP BY meta_key 
    ORDER BY count DESC 
    LIMIT 5
");
$db_tests['meta_query'] = microtime(true) - $start;

// Test 4: Multiple sequential queries
$start = microtime(true);
for ($i = 0; $i < 10; $i++) {
    $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'siteurl'");
}
$db_tests['sequential_queries'] = microtime(true) - $start;

$benchmark_results['database'] = $db_tests;
$benchmark_results['database_stats'] = [
    'posts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}"),
    'users' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
    'comments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments}"),
    'options' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}"),
    'postmeta' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}")
];

/**
 * 4. CACHING SYSTEM TESTS
 */
$caching_tests = [];

// Redis check
if (class_exists('Redis')) {
    $caching_tests['redis_extension'] = true;
    try {
        $redis = new Redis();
        $connect_start = microtime(true);
        $connected = $redis->connect('127.0.0.1', 6379, 1);
        $caching_tests['redis_connection_time'] = microtime(true) - $connect_start;
        
        if ($connected) {
            $caching_tests['redis_connected'] = true;
            $caching_tests['redis_ping'] = $redis->ping();
            $caching_tests['redis_info'] = $redis->info();
            
            // Test Redis speed
            $start = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $redis->set("benchmark_test_$i", "value_$i");
                $redis->get("benchmark_test_$i");
                $redis->delete("benchmark_test_$i");
            }
            $caching_tests['redis_operations_100'] = microtime(true) - $start;
        }
    } catch (Exception $e) {
        $caching_tests['redis_error'] = $e->getMessage();
    }
} else {
    $caching_tests['redis_extension'] = false;
}

// Memcached check
if (class_exists('Memcached')) {
    $caching_tests['memcached_extension'] = true;
    $memcached = new Memcached();
    $memcached->addServer('127.0.0.1', 11211);
    $caching_tests['memcached_stats'] = $memcached->getStats();
} else {
    $caching_tests['memcached_extension'] = false;
}

// Object Cache status
global $wp_object_cache;
if (isset($wp_object_cache) && is_object($wp_object_cache)) {
    $caching_tests['object_cache_class'] = get_class($wp_object_cache);
    $caching_tests['object_cache_global_groups'] = $wp_object_cache->global_groups ?? 'N/A';
    $caching_tests['object_cache_stats'] = $wp_object_cache->stats() ?? 'No stats available';
}

// Page caching check
$caching_tests['transients'] = [
    'set_time' => null,
    'get_time' => null
];

$start = microtime(true);
set_transient('benchmark_test', 'test_value', 60);
$caching_tests['transients']['set_time'] = microtime(true) - $start;

$start = microtime(true);
get_transient('benchmark_test');
$caching_tests['transients']['get_time'] = microtime(true) - $start;

delete_transient('benchmark_test');

$benchmark_results['caching'] = $caching_tests;

/**
 * 5. PLUGINS & THEME ANALYSIS
 */
$benchmark_results['plugins'] = [
    'active_count' => count(get_option('active_plugins', [])),
    'must_use_count' => count(get_mu_plugins()),
    'all_plugins_count' => count(get_plugins()),
    'active_list' => array_map(function($plugin) {
        return [
            'name' => get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin)['Name'] ?? $plugin,
            'file' => $plugin
        ];
    }, get_option('active_plugins', []))
];

$current_theme = wp_get_theme();
$benchmark_results['theme'] = [
    'name' => $current_theme->get('Name'),
    'version' => $current_theme->get('Version'),
    'parent' => $current_theme->parent() ? $current_theme->parent()->get('Name') : 'None',
    'template_files' => count($current_theme->get_files('php', 1))
];

/**
 * 6. FILESYSTEM PERFORMANCE
 */
$filesystem_tests = [];

// File read/write test
$test_file = ABSPATH . 'wp-content/benchmark_test.txt';
$start = microtime(true);
file_put_contents($test_file, str_repeat('Test content ', 1000));
$filesystem_tests['write_1kb'] = microtime(true) - $start;

$start = microtime(true);
file_get_contents($test_file);
$filesystem_tests['read_1kb'] = microtime(true) - $start;

unlink($test_file);

// Check filesystem method
$filesystem_tests['filesystem_method'] = get_filesystem_method();
$filesystem_tests['wp_content_writable'] = is_writable(WP_CONTENT_DIR);
$filesystem_tests['uploads_writable'] = is_writable(wp_upload_dir()['path']);

$benchmark_results['filesystem'] = $filesystem_tests;

/**
 * 7. LOAD TESTS (Simulated)
 */
$load_tests = [];

// Simulate multiple WP_Query executions
$start = microtime(true);
for ($i = 0; $i < 5; $i++) {
    $query = new WP_Query([
        'post_type' => 'post',
        'posts_per_page' => 5,
        'cache_results' => true
    ]);
    wp_reset_postdata();
}
$load_tests['multiple_wp_query'] = microtime(true) - $start;

// User query test
$start = microtime(true);
get_users(['number' => 10]);
$load_tests['user_query'] = microtime(true) - $start;

// Option autoload test
$start = microtime(true);
$alloptions = wp_load_alloptions();
$load_tests['load_alloptions'] = microtime(true) - $start;

$benchmark_results['load_tests'] = $load_tests;

/**
 * 8. PERFORMANCE SCORE CALCULATION
 */
$performance_score = 100;

// Penalize slow core load
if ($core_load > 1.0) $performance_score -= 30;
elseif ($core_load > 0.5) $performance_score -= 15;
elseif ($core_load > 0.3) $performance_score -= 5;

// Reward fast core load
if ($core_load < 0.1) $performance_score += 10;
if ($core_load < 0.05) $performance_score += 10;

// Penalize many plugins
$plugin_count = $benchmark_results['plugins']['active_count'];
if ($plugin_count > 40) $performance_score -= 20;
elseif ($plugin_count > 20) $performance_score -= 10;
elseif ($plugin_count > 10) $performance_score -= 5;

// Check caching
if ($caching_tests['redis_connected'] ?? false) $performance_score += 15;
if ($caching_tests['memcached_extension'] ?? false) $performance_score += 10;

$performance_score = max(0, min(100, $performance_score));

/**
 * 9. FINALIZE RESULTS
 */
$benchmark_results['total_time'] = microtime(true) - $total_start;
$benchmark_results['performance_score'] = $performance_score;
$benchmark_results['timestamp'] = current_time('mysql');
$benchmark_results['server_load'] = function_exists('sys_getloadavg') ? sys_getloadavg() : 'N/A';
$benchmark_results['memory'] = [
    'used' => round(memory_get_usage() / 1024 / 1024, 2),
    'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2),
    'limit' => ini_get('memory_limit')
];

/**
 * OUTPUT RESULTS
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Performance Benchmark</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6; 
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }
        .header .subtitle {
            opacity: 0.9;
            font-size: 1.1em;
        }
        .score-card {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .score {
            font-size: 5em;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        .score-label {
            font-size: 1.2em;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .results {
            padding: 30px;
        }
        .section {
            margin-bottom: 40px;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            transition: transform 0.3s ease;
        }
        .section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #495057;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .metric {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .metric:last-child {
            border-bottom: none;
        }
        .metric-name {
            color: #5f6368;
        }
        .metric-value {
            font-weight: 600;
            color: #202124;
        }
        .good { color: #28a745 !important; }
        .warning { color: #ffc107 !important; }
        .bad { color: #dc3545 !important; }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-good { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-bad { background-color: #dc3545; }
        .recommendations {
            background: #e7f3ff;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin-top: 30px;
            border-radius: 0 8px 8px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9em;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        .json-toggle {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            color: #667eea;
            font-weight: 600;
        }
        pre {
            display: none;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
            font-size: 0.9em;
        }
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            .header {
                padding: 20px;
            }
            .header h1 {
                font-size: 1.8em;
            }
            .score {
                font-size: 3.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 WordPress Performance Benchmark</h1>
            <div class="subtitle">Comprehensive system analysis and performance metrics</div>
        </div>
        
        <div class="score-card">
            <div class="score <?php echo $performance_score >= 80 ? 'good' : ($performance_score >= 60 ? 'warning' : 'bad'); ?>">
                <?php echo $performance_score; ?>/100
            </div>
            <div class="score-label">Performance Score</div>
        </div>
        
        <div class="results">
            <!-- Core Performance -->
            <div class="section">
                <h2>⚡ Core Performance</h2>
                <div class="metric">
                    <span class="metric-name">WordPress Core Load Time</span>
                    <span class="metric-value <?php echo $core_load < 0.2 ? 'good' : ($core_load < 0.5 ? 'warning' : 'bad'); ?>">
                        <?php echo round($core_load, 3); ?> seconds
                    </span>
                </div>
                <div class="metric">
                    <span class="metric-name">Total Benchmark Time</span>
                    <span class="metric-value"><?php echo round($benchmark_results['total_time'], 3); ?> seconds</span>
                </div>
                <div class="metric">
                    <span class="metric-name">WordPress Version</span>
                    <span class="metric-value"><?php echo $benchmark_results['environment']['wordpress_version']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-name">PHP Version</span>
                    <span class="metric-value"><?php echo $benchmark_results['environment']['php_version']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-name">Server Software</span>
                    <span class="metric-value"><?php echo htmlspecialchars($benchmark_results['environment']['server_software']); ?></span>
                </div>
            </div>
            
            <!-- Database Performance -->
            <div class="section">
                <h2>🗄️ Database Performance</h2>
                <?php foreach ($benchmark_results['database'] as $test => $time): ?>
                <div class="metric">
                    <span class="metric-name"><?php echo ucfirst(str_replace('_', ' ', $test)); ?></span>
                    <span class="metric-value <?php echo $time < 0.01 ? 'good' : ($time < 0.05 ? 'warning' : 'bad'); ?>">
                        <?php echo round($time, 4); ?> seconds
                    </span>
                </div>
                <?php endforeach; ?>
                
                <h3 style="margin-top: 20px; color: #6c757d;">Database Statistics</h3>
                <?php foreach ($benchmark_results['database_stats'] as $table => $count): ?>
                <div class="metric">
                    <span class="metric-name"><?php echo ucfirst($table); ?></span>
                    <span class="metric-value"><?php echo number_format($count); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Caching System -->
            <div class="section">
                <h2>⚙️ Caching System</h2>
                <div class="metric">
                    <span class="metric-name">Redis Extension</span>
                    <span class="metric-value <?php echo $benchmark_results['caching']['redis_extension'] ? 'good' : 'bad'; ?>">
                        <?php echo $benchmark_results['caching']['redis_extension'] ? '✓ Installed' : '✗ Not Installed'; ?>
                    </span>
                </div>
                <?php if ($benchmark_results['caching']['redis_extension'] && isset($benchmark_results['caching']['redis_connected'])): ?>
                <div class="metric">
                    <span class="metric-name">Redis Connection</span>
                    <span class="metric-value good">✓ Connected (<?php echo round($benchmark_results['caching']['redis_connection_time'] ?? 0, 3); ?>s)</span>
                </div>
                <?php endif; ?>
                
                <div class="metric">
                    <span class="metric-name">Memcached Extension</span>
                    <span class="metric-value <?php echo $benchmark_results['caching']['memcached_extension'] ? 'good' : 'bad'; ?>">
                        <?php echo $benchmark_results['caching']['memcached_extension'] ? '✓ Installed' : '✗ Not Installed'; ?>
                    </span>
                </div>
                
                <?php if (!empty($benchmark_results['caching']['object_cache_class'])): ?>
                <div class="metric">
                    <span class="metric-name">Object Cache Class</span>
                    <span class="metric-value"><?php echo $benchmark_results['caching']['object_cache_class']; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="metric">
                    <span class="metric-name">Transient Set Time</span>
                    <span class="metric-value"><?php echo round($benchmark_results['caching']['transients']['set_time'] ?? 0, 4); ?>s</span>
                </div>
                <div class="metric">
                    <span class="metric-name">Transient Get Time</span>
                    <span class="metric-value"><?php echo round($benchmark_results['caching']['transients']['get_time'] ?? 0, 4); ?>s</span>
                </div>
            </div>
            
            <!-- Plugins & Theme -->
            <div class="section">
                <h2>🧩 Plugins & Theme</h2>
                <div class="metric">
                    <span class="metric-name">Active Plugins</span>
                    <span class="metric-value <?php echo $benchmark_results['plugins']['active_count'] < 20 ? 'good' : ($benchmark_results['plugins']['active_count'] < 40 ? 'warning' : 'bad'); ?>">
                        <?php echo $benchmark_results['plugins']['active_count']; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="metric-name">Must-Use Plugins</span>
                    <span class="metric-value"><?php echo $benchmark_results['plugins']['must_use_count']; ?></span>
                </div>
                <div class="metric">
                    <span class="metric-name">Current Theme</span>
                    <span class="metric-value"><?php echo $benchmark_results['theme']['name']; ?> (v<?php echo $benchmark_results['theme']['version']; ?>)</span>
                </div>
                <div class="metric">
                    <span class="metric-name">Parent Theme</span>
                    <span class="metric-value"><?php echo $benchmark_results['theme']['parent']; ?></span>
                </div>
            </div>
            
            <!-- Memory & Resources -->
            <div class="section">
                <h2>💾 Memory & Resources</h2>
                <div class="metric">
                    <span class="metric-name">Memory Used</span>
                    <span class="metric-value <?php echo $benchmark_results['memory']['used'] < 50 ? 'good' : ($benchmark_results['memory']['used'] < 100 ? 'warning' : 'bad'); ?>">
                        <?php echo $benchmark_results['memory']['used']; ?> MB
                    </span>
                </div>
                <div class="metric">
                    <span class="metric-name">Peak Memory Usage</span>
                    <span class="metric-value"><?php echo $benchmark_results['memory']['peak']; ?> MB</span>
                </div>
                <div class="metric">
                    <span class="metric-name">PHP Memory Limit</span>
                    <span class="metric-value"><?php echo $benchmark_results['memory']['limit']; ?></span>
                </div>
                <?php if (is_array($benchmark_results['server_load'])): ?>
                <div class="metric">
                    <span class="metric-name">Server Load (1/5/15 min)</span>
                    <span class="metric-value">
                        <?php echo round($benchmark_results['server_load'][0], 2); ?> / 
                        <?php echo round($benchmark_results['server_load'][1], 2); ?> / 
                        <?php echo round($benchmark_results['server_load'][2], 2); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recommendations -->
            <div class="recommendations">
                <h3>💡 Recommendations</h3>
                <ul style="margin-top: 10px; padding-left: 20px;">
                    <?php if ($core_load > 0.3): ?>
                    <li>WordPress core load time is high. Consider implementing object caching.</li>
                    <?php endif; ?>
                    
                    <?php if ($benchmark_results['plugins']['active_count'] > 30): ?>
                    <li>Too many active plugins. Consider disabling unused plugins.</li>
                    <?php endif; ?>
                    
                    <?php if (!$benchmark_results['caching']['redis_extension'] && !$benchmark_results['caching']['memcached_extension']): ?>
                    <li>No caching extension detected. Install Redis or Memcached for better performance.</li>
                    <?php endif; ?>
                    
                    <?php if ($benchmark_results['memory']['used'] > 100): ?>
                    <li>High memory usage. Optimize plugins and consider increasing memory limit.</li>
                    <?php endif; ?>
                    
                    <?php if ($benchmark_results['environment']['wp_debug']): ?>
                    <li>WP_DEBUG is enabled. Disable on production for better performance.</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- JSON Output Toggle -->
            <div class="json-toggle" onclick="toggleJson()">
                📋 Toggle JSON Data Export
            </div>
            <pre id="json-output"><?php echo json_encode($benchmark_results, JSON_PRETTY_PRINT); ?></pre>
        </div>
        
        <div class="footer">
            <p>Generated on <?php echo $benchmark_results['timestamp']; ?></p>
            <p><strong>⚠️ WARNING:</strong> This file exposes sensitive information. Remove it from production servers immediately after testing.</p>
            <p>Benchmark Tool v2.0 | For testing purposes only</p>
        </div>
    </div>
    
    <script>
        function toggleJson() {
            const pre = document.getElementById('json-output');
            pre.style.display = pre.style.display === 'block' ? 'none' : 'block';
            
            if (pre.style.display === 'block') {
                // Copy to clipboard
                navigator.clipboard.writeText(pre.textContent)
                    .then(() => alert('JSON data copied to clipboard!'))
                    .catch(err => console.error('Failed to copy: ', err));
            }
        }
        
        // Add color coding to metrics
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.metric-value').forEach(el => {
                const text = el.textContent.toLowerCase();
                if (text.includes('not installed') || text.includes('✗')) {
                    el.classList.add('bad');
                } else if (text.includes('installed') || text.includes('✓')) {
                    el.classList.add('good');
                }
            });
        });
    </script>
</body>
</html>
