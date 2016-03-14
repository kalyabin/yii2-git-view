<?php

namespace tests;

use GitView\GitWrapper;
use GitView\Repository;
use GitView\Commit;
use PHPUnit_Framework_TestCase;
use Yii;
use VcsCommon\File;
use GitView\Diff;

/**
 * Test commit
 */
class CaseCommitTest extends PHPUnit_Framework_TestCase
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
     * @var Commit commit model
     */
    protected $commit;


    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->variables = Yii::$app->params['commit'];

        $wrapper = new GitWrapper();
        $repoPath = Yii::$app->params['wrapper']['availRepository'];
        $this->repository = $wrapper->getRepository($repoPath);

        $this->commit = $this->repository->getCommit($this->variables['id']);
    }


    /**
     * Tests commit variables
     */
    public function testCommitVariables()
    {
        $commit = $this->commit;

        $this->assertInstanceOf('DateTime', $commit->getDate());

        /* @var $wrapper GitWrapper */
        $wrapper = $this->repository->getWrapper();
        $this->assertTrue($wrapper->checkIsSha1($commit->getId()));
        $this->assertNotEmpty($commit->contributorName);
        $this->assertNotEmpty($commit->contributorEmail);
        $this->assertNotEmpty($commit->message);
        $this->assertNotEmpty($commit->getParentsId());
        foreach ($commit->getParentsId() as $parentId) {
            $this->assertTrue($wrapper->checkIsSha1($parentId));
        }
        $this->assertNotEmpty($commit->getChangedFiles());
        $this->assertContainsOnly('array', $commit->getChangedFiles());
        foreach ($commit->getChangedFiles() as $item) {
            $this->assertArrayHasKey('path', $item);
            $this->assertArrayHasKey('status', $item);
            $this->assertInstanceOf(File::className(), $item['path']);
            $this->assertInternalType('string', $item['status']);
        }

        return $commit;
    }

    /**
     * Test commit diff
     *
     * @depends testCommitVariables
     * @param Commit $commit
     */
    public function testCommitDiff(Commit $commit)
    {
        $diffs = $commit->getDiff();
        $this->assertNotEmpty($diffs);
        $this->assertContainsOnlyInstancesOf(Diff::className(), $diffs);
        foreach ($diffs as $diff) {
            /* @var $diff Diff */
            $this->assertInternalType('string', $diff->getDescription());
            $this->assertInternalType('string', $diff->getNewFilePath());
            $this->assertNotEmpty($diff->getNewFilePath());
            $this->assertNotEmpty($diff->getPreviousFilePath());
            $this->assertContainsOnly('array', $diff->getLines());
            foreach ($diff->getLines() as $diffKey => $lines) {
                $this->assertInternalType('string', $diffKey);
                $this->assertRegExp('#^@@[\s]\-([\d]+),?([\d]+)?[\s]\+([\d]+),?([\d]+)?[\s]@@#i', $diffKey);
                $this->assertArrayHasKey('beginA', $lines);
                $this->assertArrayHasKey('beginB', $lines);
                $this->assertArrayHasKey('cntA', $lines);
                $this->assertArrayHasKey('cntB', $lines);
                $this->assertInternalType('integer', $lines['beginA']);
                $this->assertInternalType('integer', $lines['beginB']);
                $this->assertInternalType('integer', $lines['cntA']);
                $this->assertInternalType('integer', $lines['cntB']);
                $this->assertArrayHasKey('lines', $lines);
                $this->assertInternalType('array', $lines['lines']);
                $this->assertNotEmpty($lines['lines']);
                $this->assertContainsOnly('string', $lines['lines']);
                foreach ($lines['lines'] as $line) {
                    if (!empty($line)) {
                        $this->assertRegExp('#^([\s]|\+|\-|\\\\){1}#i', $line);
                    }
                }
            }
        }

        return $commit;
    }

    /**
     * Test commit raw file
     *
     * @depends testCommitDiff
     */
    public function testCommitRawFile(Commit $commit)
    {
        $rawFile = $commit->getRawFile('composer.json');
        $this->assertInternalType('string', $rawFile);
        $this->assertJson($rawFile);
    }
}
