<?php

namespace tests;

use GitView\Branch;
use GitView\Commit;
use GitView\GitWrapper;
use GitView\Repository;
use PHPUnit_Framework_TestCase;
use VcsCommon\exception\CommonException;
use VcsCommon\File;
use Yii;
use VcsCommon\Graph;
use GitView\Diff;

/**
 * Test repository
 */
class CaseRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array testing variables
     */
    protected $variables = [];

    /**
     * @var Repository repository model
     */
    protected $repository;

    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->variables = Yii::$app->params['repository'];

        $wrapper = new GitWrapper();
        $repoPath = Yii::$app->params['wrapper']['availRepository'];
        $this->repository = $wrapper->getRepository($repoPath);
    }

    /**
     * Tests file list
     */
    public function testFileList()
    {
        $fileList = $this->repository->getFilesList();
        $this->assertNotEmpty($fileList);
        $this->assertContainsOnlyInstancesOf(File::className(), $fileList);
    }

    /**
     * Tests file list exception
     *
     * @expectedException VcsCommon\exception\CommonException
     */
    public function testFileListOutbandException()
    {
        $this->repository->getFilesList('/tmp/');
    }

    /**
     * Tests file list exception
     *
     * @expectedException VcsCommon\exception\CommonException
     */
    public function testFileListRepositoryException()
    {
        $this->repository->getFilesList($this->repository->getRepositoryPath());
    }

    /**
     * Tests check status
     */
    public function testCheckStatus()
    {
        $this->repository->checkStatus();
    }

    /**
     * Test branches
     */
    public function testBranches()
    {
        $branches = $this->repository->getBranches();
        $this->assertNotEmpty($branches);
        $this->assertContainsOnlyInstancesOf(Branch::className(), $branches);

        // check if current branch exists
        $currentBranchExists = false;
        foreach ($branches as $branch) {
            /* @var $branch Branch */
            if ($branch->getIsCurrent()) {
                $currentBranchExists = true;
                break;
            }
        }

        $this->assertTrue($currentBranchExists);
    }

    /**
     * Tests history getter
     *
     * @return Commit[]
     */
    public function testHistory()
    {
        $history = $this->repository->getHistory(10, 5);
        $this->assertNotEmpty($history);
        $this->assertContainsOnlyInstancesOf(Commit::className(), $history);
        $this->assertCount(10, $history);

        return $history;
    }

    /**
     * Tests history getter for sepcified path
     */
    public function testPathHistory()
    {
        $history = $this->repository->getHistory(2, 0, $this->variables['pathHistory']);
        $this->assertNotEmpty($history);
        $this->assertContainsOnlyInstancesOf(Commit::className(), $history);
        $this->assertCount(2, $history);

        foreach ($history as $commit) {
            /* @var $commit Commit */
            $commit = $this->repository->getCommit($commit->getId());
            $this->assertNotEmpty($commit->getChangedFiles());
            $hasCurrentFile = false;
            foreach ($commit->getChangedFiles() as $file) {
                /* @var $file File */
                $this->assertInstanceOf(File::className(), $file);
                if ($this->variables['pathHistory'] === $file->getPathname()) {
                    $hasCurrentFile = true;
                }
            }
            $this->assertTrue($hasCurrentFile);
        }

        return $history;
    }

    /**
     * Tests history wrong params
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testHistoryExceptionWrongParams()
    {
        $history = $this->repository->getHistory('a', 'b');
        $this->assertInternalType('array', $history);
        $this->assertEmpty($history);
    }

    /**
     * Test commit exception
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testCommitException()
    {
        $this->repository->getCommit('xxx');
    }

    /**
     * Check diff array strings by diff git command
     *
     * @param array $diff
     */
    protected function diffContainsSpecialIdentifiers($diff)
    {
        $this->assertInternalType('array', $diff);
        $this->assertNotEmpty($diff);
        $this->assertContainsOnly('string', $diff);

        $hasDiff = false;
        $hasHeads = false;
        $hasADiff = false;
        $hasBDiff = false;

        foreach ($diff as $row) {
            if (mb_substr($row, 0, 4) === 'diff') {
                $hasDiff = true;
            }
            else if (mb_substr($row, 0, 3) === '---') {
                $hasADiff = true;
            }
            else if (mb_substr($row, 0, 3) === '+++') {
                $hasBDiff = true;
            }
            else if (mb_substr($row, 0, 2) === '@@') {
                $hasHeads = true;
            }
        }

        $this->assertTrue($hasDiff && $hasHeads && $hasADiff && $hasBDiff);
    }

    /**
     * Test diff
     */
    public function testDiff()
    {
        $diff = $this->repository->getDiff(
            Repository::DIFF_COMMIT,
            $this->variables['commitDiff']
        );
        $this->diffContainsSpecialIdentifiers($diff);

        $diff = $this->repository->getDiff(
            Repository::DIFF_COMPARE,
            $this->variables['commitCompare'][0],
            $this->variables['commitCompare'][1]
        );
        $this->diffContainsSpecialIdentifiers($diff);

        $diff = $this->repository->getDiff(
            Repository::DIFF_PATH,
            $this->variables['commitFileDiff'][1],
            $this->variables['commitFileDiff'][0]
        );
        $this->diffContainsSpecialIdentifiers($diff);
    }

    /**
     * Tests first wrong diff param
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testDiffWrongFirstParamException()
    {
        $this->repository->getDiff(-1);
    }

    /**
     * Tests second wrong diff param
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testDiffWrongSecondParamException()
    {
        $this->repository->getDiff(Repository::DIFF_COMMIT, 'xxx');
    }

    /**
     * Tests third wrong diff param
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testDiffWrongThirdParamException()
    {
        $this->repository->getDiff(Repository::DIFF_COMPARE, 'xxx1', 'xxx2');
    }

    /**
     * Tests empty diff params
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testDiffEmptySecondParamException()
    {
        $this->repository->getDiff(Repository::DIFF_COMMIT);
    }

    /**
     * Tests empty diff params
     *
     * @expectedException \VcsCommon\exception\CommonException
     */
    public function testDIffEmptySecondAndThirdParamException()
    {
        $this->repository->getDiff(Repository::DIFF_COMPARE);
    }

    /**
     * Tests graph history
     */
    public function testGraphHistory()
    {
        $graph = $this->repository->getGraphHistory(10, 1);
        $this->assertInstanceOf(Graph::className(), $graph);
        $this->assertContainsOnlyInstancesOf(Commit::className(), $graph->getCommits());
        $this->assertEquals(10, count($graph->getCommits()));
        $this->assertGreaterThanOrEqual(0, $graph->getLevels());
        $this->assertLessThan(9, $graph->getLevels());
        foreach ($graph->getCommits() as $commit) {
            /* @var $commit Commit */
            $this->assertInstanceOf(Commit::className(), $commit);
            $this->assertGreaterThanOrEqual(0, $commit->graphLevel);
            $this->assertLessThanOrEqual($graph->getLevels(), $commit->graphLevel);
        }
    }

    /**
     * Tests ignored and not ignored files
     */
    public function testIgnore()
    {
        $this->assertFalse($this->repository->pathIsNotIgnored($this->variables['ignoredPath']));
        $this->assertTrue($this->repository->pathIsNotIgnored($this->variables['notIgnoredPath']));
    }

    /**
     * Tests binary binary file
     */
    public function testBinary()
    {
        $wrapper = new GitWrapper();

        $fileSize = $this->variables['binaryTest']['fileSize'];

        $repository = $wrapper->getRepository($this->variables['binaryTest']['projectPath']);
        $repository->getBinaryFile($this->variables['binaryTest']['commitId'], $this->variables['binaryTest']['filePath'], function($data) use (&$fileSize) {
            $fileSize -= strlen($data);
        });

        $this->assertEquals(0, $fileSize);
    }
}
