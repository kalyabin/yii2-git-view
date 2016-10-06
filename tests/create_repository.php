<?php
/**
 * Install repository
 */

$testingVariables = include __DIR__ . '/testing.variables.php';

$currentPath = getcwd();

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
    chdir($repoPath);
    $cmd = "git pull origin";
    exec($cmd, $output, $statusCode);
    chdir($currentPath);
    if ($statusCode !== 0) {
        echo "\nCan\'t pull repository from $repoUrl to $repoPath\n";
        exit(1);
    }
}

// fetch branches
chdir($repoPath);
$cmd = "git fetch --all";
exec($cmd);

foreach ($testingVariables['branches'] as $branch) {
    chdir($repoPath);
    $cmd = "git checkout $branch";
    exec($cmd, $output, $statusCode);
    chdir($currentPath);
    if ($statusCode !== 0) {
        echo "\nCan\'t checkout branch: $branch\n";
        exit(1);
    }
}

chdir($repoPath);
$cmd = "git checkout {$testingVariables['branches'][0]}";
exec($cmd);
chdir($currentPath);

return $testingVariables['repositoryPath'];
