<?php
/**
 * Install repository
 */

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
