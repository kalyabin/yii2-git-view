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

        return $history;
    }

    /**
     * Tests history wrong params
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
     * Test diff
     */
    public function testDiff()
    {
        $diff = $this->repository->getDiff(
            Repository::DIFF_COMMIT,
            $this->variables['commitDiff']
        );
        $this->assertNotEmpty($diff);
        $this->assertContainsOnly('string', $diff);

        $diff = $this->repository->getDiff(
            Repository::DIFF_COMPARE,
            $this->variables['commitCompare'][0],
            $this->variables['commitCompare'][1]
        );
        $this->assertNotEmpty($diff);
        $this->assertContainsOnly('string', $diff);

        $diff = $this->repository->getDiff(
            Repository::DIFF_PATH,
            $this->variables['commitFileDiff'][1],
            $this->variables['commitFileDiff'][0]
        );
        $this->assertNotEmpty($diff);
        $this->assertContainsOnly('string', $diff);
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
}
