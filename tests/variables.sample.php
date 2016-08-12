<?php
/**
 * Local variables to test
 */

return [
    'wrapper' => [
        'availCmd' => 'git',
        'errorCmd' => 'wrong-git',
        'availRepository' => dirname(__DIR__),
        'errorRepository' => '/tmp',
    ],
    'repository' => [
        'commitDiff' => '1244f5a7409604e4027e9d97538f65d32767fb14',
        'commitCompare' => [
            'a580783d8c3eb462c730faf1da73a8ce0d31d470',
            '1244f5a7409604e4027e9d97538f65d32767fb14',
        ],
        'commitFileDiff' => [
            '1244f5a7409604e4027e9d97538f65d32767fb14', 'Commit.php',
        ],
        'pathHistory' => 'src/Diff.php',
        'ignoredPath' => 'tests/variables.local.php',
        'notIgnoredPath' => 'tests/variables.local.sample.php',
    ],
    'commit' => [
        'diff' => '1244f5a7409604e4027e9d97538f65d32767fb14',
        'rawFile' => [
            'commitId' => '95c5af88b6b83e66f67e09962d92a289d7916a18',
            'file' => 'Directory.php'
        ],
        'binaryTest' => [
            'projectPath' => '/path/to/git/project',
            'commitId' => 'sha1',
            'filePath' => '/path/to/binary/file',
            'fileSize' => 63969,
        ],
    ],
];
