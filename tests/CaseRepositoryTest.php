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
        $this->assertContainsOnlyInstancesOf(File::class, $fileList);
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
        $result = $this->repository->checkStatus();
        $this->assertInternalType('string', $result);
    }

    /**
     * Test branches
     */
    public function testBranches()
    {
        $branches = $this->repository->getBranches();
        $this->assertNotEmpty($branches);
        $this->assertContainsOnlyInstancesOf(Branch::class, $branches);
        $this->assertCount(count($this->variables['branches']), $branches);

        // check if current branch exists
        $currentBranchExists = false;
        foreach ($branches as $branch) {
            /* @var $branch Branch */
            if ($branch->getIsCurrent()) {
                $currentBranchExists = true;
            }
            // check branch identifier
            $this->assertContains($branch->getId(), $this->variables['branches']);
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
        $this->assertContainsOnlyInstancesOf(Commit::class, $history);
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
        $this->assertContainsOnlyInstancesOf(Commit::class, $history);
        $this->assertCount(2, $history);

        foreach ($history as $commit) {
            /* @var $commit Commit */
            $commit = $this->repository->getCommit($commit->getId());
            $this->assertNotEmpty($commit->getChangedFiles());
            $hasCurrentFile = false;
            foreach ($commit->getChangedFiles() as $file) {
                /* @var $file File */
                $this->assertInstanceOf(File::class, $file);
                if ($this->variables['pathHistory'] === $file->getPathname()) {
                    $hasCurrentFile = true;
                }
            }
            $this->assertTrue($hasCurrentFile);
        }

        return $history;
    }

    /**
     * Test branch history and all branches history
     */
    public function testBranchHistory()
    {
        // test commits for specified branch
        foreach ($this->variables['branchHistory'] as $branch => $commitId) {
            $commitsInOtherBranches = [];
            foreach ($this->variables['branchHistory'] as $otherBranch => $otherCommitId) {
                if ($otherBranch != $branch) {
                    $commitsInOtherBranches[] = $otherCommitId;
                }
            }
            $this->assertNotEmpty($commitsInOtherBranches);
            $history = $this->repository->getHistory(1000, 0, null, $branch);
            $this->assertNotEmpty($history);
            $this->assertContainsOnlyInstancesOf(Commit::class, $history);

            $hasCommit = false;
            foreach ($history as $commit) {
                if ($commit->getId() == $commitId) {
                    $hasCommit = true;
                }
                $this->assertNotContains($commit->getId(), $commitsInOtherBranches);
            }
            $this->assertTrue($hasCommit, "Branch $branch has no commit with: $commitId\n");
        }

        // test commits for all branches
        $commitsForAllBranches = array_flip($this->variables['branchHistory']);
        $history = $this->repository->getHistory(1000, 0, null, null);
        $this->assertNotEmpty($history);
        $this->assertContainsOnlyInstancesOf(Commit::class, $history);
        foreach ($history as $commit) {
            if (isset($commitsForAllBranches[$commit->getId()])) {
                unset ($commitsForAllBranches[$commit->getId()]);
            } elseif (empty($commitsForAllBranches)) {
                break;
            }
        }
        $this->assertEmpty($commitsForAllBranches, "Not all branches in all branches history: " . print_r($commitsForAllBranches, true));
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
        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertContainsOnlyInstancesOf(Commit::class, $graph->getCommits());
        $this->assertEquals(10, count($graph->getCommits()));
        $this->assertGreaterThanOrEqual(0, $graph->getLevels());
        $this->assertLessThan(9, $graph->getLevels());
        foreach ($graph->getCommits() as $commit) {
            /* @var $commit Commit */
            $this->assertInstanceOf(Commit::class, $commit);
            $this->assertGreaterThanOrEqual(0, $commit->graphLevel);
            $this->assertLessThanOrEqual($graph->getLevels(), $commit->graphLevel);
        }
    }

    /**
     * Tests ignored and not ignored files
     */
    public function testIgnore()
    {
        // create file if not exists
        $filePath = $this->repository->getProjectPath() . DIRECTORY_SEPARATOR . $this->variables['ignoredPath'];
        if (!file_exists($filePath)) {
            file_put_contents($filePath, 'ignored file');
        }

        $this->assertFalse($this->repository->pathIsNotIgnored($this->variables['ignoredPath']));
        $this->assertTrue($this->repository->pathIsNotIgnored($this->variables['notIgnoredPath']));
    }
}
