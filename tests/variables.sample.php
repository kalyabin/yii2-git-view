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
    ],
    'commit' => [
        'id' => '1244f5a7409604e4027e9d97538f65d32767fb14',
    ],
];
