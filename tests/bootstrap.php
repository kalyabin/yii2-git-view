<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@tests', __DIR__);

// enable debug
define('VCS_DEBUG', true);
define('VCS_DEBUG_FILE', __DIR__ . '/repo/vcs_debug.log');

if (is_file(VCS_DEBUG_FILE)) {
    unlink(VCS_DEBUG_FILE);
}

$testingVariables = include __DIR__ . '/testing.variables.php';

// install testing repository first
$repoPath = $testingVariables['repositoryPath'];
$repoUrl = $testingVariables['repositoryUrl'];
if (!is_dir($repoPath)) {
    // create repository if not exists
    mkdir($repoPath, 0755);
    $cmd = "git clone $repoUrl $repoPath";
    exec($cmd, $output, $statusCode);
    if ($statusCode !== 0) {
        echo "\nCan\'t create repository from $repoUrl to $repoPath\n";
        exit(1);
    }
} else {
    // pull repository if exists
    $currentPath = getcwd();
    chdir($repoPath);
    $cmd = "git pull origin";
    exec($cmd, $output, $statusCode);
    chdir($currentPath);
    if ($statusCode !== 0) {
        echo "\Can\'t pull repository from $repoUrl to $repoPath\n";
        exit(1);
    }
}

new \yii\console\Application([
    'id' => 'unit',
    'basePath' => __DIR__,
    'params' => $testingVariables,
]);
