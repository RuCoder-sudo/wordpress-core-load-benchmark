# wordpress-core-load-benchmark

---

# WordPress Performance Benchmark Tool

![Version](https://img.shields.io/badge/Version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-orange)

## 📋 Description

A comprehensive benchmarking tool for WordPress that measures:
- Core WordPress loading time
- Database query performance
- Caching system status (Redis/Memcached)
- Memory usage and limits
- Plugin impact analysis
- Filesystem performance
- Overall performance score

## 🚀 Quick Start

1. **Place the file** `wp-benchmark.php` in your WordPress root directory (same level as `wp-load.php`)
2. **Access the benchmark** by visiting: `https://your-site.com/wp-benchmark.php`
3. **Review results** and implement recommendations
4. **IMPORTANT**: Remove the file from production servers after testing!

## 🔧 Features

### Core Tests
- WordPress core load time measurement
- PHP version and configuration check
- Server software detection
- WordPress version check

### Database Performance
- Simple query speed test
- Complex JOIN query test
- Meta query performance
- Sequential query test
- Database statistics (posts, users, comments, etc.)

### Caching System
- Redis extension and connection test
- Memcached extension check
- Object cache class detection
- Transient operations speed
- Cache ping and info

### Resource Analysis
- Memory usage tracking
- Peak memory detection
- Server load monitoring
- Filesystem write/read speed

### Plugin & Theme
- Active plugin count and list
- Must-use plugins detection
- Theme information
- Template file count

### Performance Score
- Automatic scoring system (0-100)
- Color-coded recommendations
- Exportable JSON data

## 📊 Interpretation Guide

### Performance Scores
- **90-100**: Excellent - Well optimized
- **70-89**: Good - Minor optimizations possible
- **50-69**: Average - Needs optimization
- **Below 50**: Poor - Significant improvements needed

### Key Metrics Targets
- Core load: < 0.2 seconds
- Database queries: < 0.01 seconds
- Active plugins: < 20
- Memory usage: < 50 MB
- Redis connection: < 0.1 seconds

## ⚠️ Security Notes

1. **REMOVE AFTER TESTING**: This file exposes sensitive system information
2. **Production use**: Never leave this file on live websites
3. **Access control**: Consider adding IP restriction if needed for development
4. **Data exposure**: Results show server configuration details

## 🛠️ Troubleshooting

### File not working?
- Ensure file is in WordPress root directory
- Check PHP version (7.4+ required)
- Verify file permissions (644 recommended)

### No results displayed?
- Check WordPress is properly installed
- Verify `wp-load.php` exists in same directory
- Check for PHP errors in server logs

### Redis/Memcached not detected?
- Ensure extensions are installed and enabled
- Check service is running on server
- Verify connection parameters

## 📈 Use Cases

1. **Before/After optimization comparisons**
2. **Server migration validation**
3. **Plugin performance impact testing**
4. **Caching system verification**
5. **Development environment benchmarking**

## 🔄 Updates

Check for updates on GitHub repository:


# WordPress Performance Benchmark Tool (Russian)
## 📋 Описание

Комплексный инструмент для тестирования производительности WordPress:
- Время загрузки ядра WordPress
- Производительность запросов к базе данных
- Статус системы кэширования (Redis/Memcached)
- Использование памяти и лимиты
- Анализ влияния плагинов
- Производительность файловой системы
- Общая оценка производительности

## 🚀 Быстрый старт

1. **Поместите файл** `wp-benchmark.php` в корневую директорию WordPress (там же где `wp-load.php`)
2. **Откройте бенчмарк** по адресу: `https://ваш-сайт/wp-benchmark.php`
3. **Изучите результаты** и выполните рекомендации
4. **ВАЖНО**: Удалите файл с продакшн-сервера после тестирования!

## 🎯 Ключевые метрики

### Целевые показатели
- Загрузка ядра: < 0.2 секунд
- Запросы к БД: < 0.01 секунд
- Активные плагины: < 20
- Использование памяти: < 50 МБ
- Подключение Redis: < 0.1 секунд

### Оценка производительности
- **90-100**: Отлично - Хорошо оптимизировано
- **70-89**: Хорошо - Небольшая оптимизация возможна
- **50-69**: Средне - Требуется оптимизация
- **Ниже 50**: Плохо - Требуются значительные улучшения

## 🔒 Безопасность

1. **УДАЛЯЙТЕ ПОСЛЕ ТЕСТИРОВАНИЯ**: Файл раскрывает чувствительную информацию
2. **Продакшн**: Никогда не оставляйте файл на работающих сайтах
3. **Контроль доступа**: Добавьте ограничение по IP при необходимости
4. **Данные**: Результаты показывают детали конфигурации сервера

## 📊 Примеры использования

1. **Сравнение до/после оптимизации**
2. **Проверка после миграции сервера**
3. **Тестирование влияния плагинов**
4. **Верификация системы кэширования**
5. **Бенчмаркинг среды разработки**

---

## 📁 File Structure

