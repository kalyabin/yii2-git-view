<?php
/**
 * Local variables to test
 */

return [
    // repository wich to be tested
    'repositoryUrl' => 'https://kalyabin@bitbucket.org/kalyabin/yii2-git-view-testing.git',
    'repositoryPath' => __DIR__ . '/repo/testing-repo',

    // type all branches of repository
    'branches' => [
        'master', 'branch1', 'branch2',
    ],

    // variables for GitWrapper tests
    'wrapper' => [
        'availCmd' => 'git',
        'errorCmd' => 'wrong-git',
        'availRepository' => __DIR__ . '/repo/testing-repo',
        'errorRepository' => '/tmp',
    ],

    // variables for Repository tests
    'repository' => [
        'commitDiff' => '6a894ae1f3ff3abe492328189530b184f92034e5',
        'commitCompare' => [
            'cf5a9c387f1524c61545d33d0dc952318dc78395',
            'ec4265ea9b02f5e28bb2cbf474f453d506b6022a',
        ],
        'commitFileDiff' => [
            '291a3d9bffcfede772f9a47fa87cca224fdebb85', 'second_testing.txt',
        ],
        'pathHistory' => 'testing.txt',
        'ignoredPath' => 'ignored.txt',
        'notIgnoredPath' => 'contributors.txt',
        'branches' => [
            'master', 'branch1', 'branch2',
        ],
        // type key as branch and value as commit id, wich branch contains
        'branchHistory' => [
            'branch2' => '062e3c143c5966a39af1fdc927c62e9a21c0093a',
            'branch1' => '73534852d7afb64300d4a85951c4f095e9b35968',
        ],
    ],

    // variables for Commit tests
    'commit' => [
        'diff' => 'ec4265ea9b02f5e28bb2cbf474f453d506b6022a',
        'rawFileNew' => [
            'commitId' => '013cbcfa4c70a53e48034f2ae63e53953204d1d5',
            'file' => 'file_to_remove.txt',
        ],
        'rawFileDeleted' => [
            'commitId' => '2f6ba1bad7cca0badc1592dc29ed80592b64f1e6',
            'file' => 'file_to_remove.txt',
        ],
        'rawFileUpdated' => [
            'commitId' => '6a894ae1f3ff3abe492328189530b184f92034e5',
            'file' => 'testing.txt',
        ],
        'rawFileNotUpdated' => [
            'commitId' => '2f6ba1bad7cca0badc1592dc29ed80592b64f1e6',
            'file' => 'testing.txt',
        ],
        'binaryTest' => [
            'commitId' => '6bebc2800bf4364960fb97ea43778f56a3cfe074',
            'filePath' => 'binary_file.png',
            'fileSize' => 42969,
        ],
    ],
];
