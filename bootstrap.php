<?php

/**
 * Bootstrap файл для тестов
 */

// Определяем константы
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Загружаем Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Загружаем Yii
require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

// Создаем тестовое приложение Yii console
new yii\console\Application([
    'id' => 'test',
    'basePath' => __DIR__,
    'vendorPath' => __DIR__ . '/vendor',
    'components' => [
        'assetManager' => [
            'basePath' => '@runtime/assets',
            'baseUrl' => '/assets',
        ],
    ],
]);
