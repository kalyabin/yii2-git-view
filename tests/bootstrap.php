<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@tests', __DIR__);

// enable debug
define('VCS_DEBUG', true);
define('VCS_DEBUG_FILE', 'php://output');

require __DIR__ . '/create_repository.php';

new \yii\console\Application([
    'id' => 'unit',
    'basePath' => __DIR__,
    'params' => include __DIR__ . '/testing.variables.php',
]);
