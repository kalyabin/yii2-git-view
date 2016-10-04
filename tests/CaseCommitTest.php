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
     * @inheritdoc
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->variables = Yii::$app->params['commit'];

        $wrapper = new GitWrapper();
        $repoPath = Yii::$app->params['wrapper']['availRepository'];
        $this->repository = $wrapper->getRepository($repoPath);
    }


    /**
     * Tests commit variables
     */
    public function testCommitVariables()
    {
        $commit = $this->repository->getCommit($this->variables['diff']);

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
        $this->assertContainsOnly(File::className(), $commit->getChangedFiles());
        $lastFilePath = null;
        $lastFileStatus = null;
        foreach ($commit->getChangedFiles() as $item) {
            $this->assertInstanceOf(File::className(), $item);
            $this->assertInternalType('string', $item->getStatus());
            $this->assertInternalType('string', $item->getPath());
            $this->assertInternalType('string', $item->getPathname());
            $lastFilePath = $item->getPathname();
            $lastFileStatus = $item->getStatus();
        }

        $this->assertEquals($commit->getFileStatus($lastFilePath), $lastFileStatus);
        $lastFile = $commit->getFileByPath($lastFilePath);
        $this->assertInstanceOf(File::className(), $lastFile);
        $this->assertEquals($lastFilePath, $lastFile->getPathname());
        $this->assertEquals($lastFileStatus, $lastFile->getStatus());

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
     * Test commit new raw file
     *
     * @depends testCommitDiff
     */
    public function testCommitNewRawFile()
    {
        $newFileCommitId = $this->variables['rawFileNew']['commitId'];
        $newFilePath = $this->variables['rawFileNew']['file'];

        $commit = $this->repository->getCommit($newFileCommitId);
        $rawFile = $commit->getRawFile($newFilePath);
        $this->assertInternalType('string', $rawFile);
        $this->assertEquals($commit->getFileStatus($newFilePath), File::STATUS_ADDITION);
    }

    /**
     * Test commit deleted raw file
     *
     * @depends testCommitNewRawFile
     */
    public function testCommitDeletedRawFile()
    {
        $deletedFileCommitId = $this->variables['rawFileDeleted']['commitId'];
        $deletedFilePath = $this->variables['rawFileDeleted']['file'];

        $commit = $this->repository->getCommit($deletedFileCommitId);
        $rawFile = $commit->getPreviousRawFile($deletedFilePath);
        $this->assertInternalType('string', $rawFile);
        $this->assertEquals($commit->getFileStatus($deletedFilePath), File::STATUS_DELETION);
    }

    /**
     * Test commit updated raw file
     *
     * @depends testCommitDeletedRawFile
     */
    public function testCommitUpdatedRawFile()
    {
        $updatedFileCommitId = $this->variables['rawFileUpdated']['commitId'];
        $updatedFilePath = $this->variables['rawFileUpdated']['file'];

        $commit = $this->repository->getCommit($updatedFileCommitId);
        $rawFile = $commit->getRawFile($updatedFilePath);
        $this->assertInternalType('string', $rawFile);
        $this->assertEquals($commit->getFileStatus($updatedFilePath), File::STATUS_MODIFIED);
    }

    /**
     * Test commit not updated raw file
     *
     * @depends testCommitUpdatedRawFile
     */
    public function testCommitNotUpdatedRawFile()
    {
        $notUpdatedFileCommitId = $this->variables['rawFileNotUpdated']['commitId'];
        $notUpdatedFilePath =$this->variables['rawFileNotUpdated']['file'];

        $commit = $this->repository->getCommit($notUpdatedFileCommitId);
        $rawFile = $commit->getRawFile($notUpdatedFilePath);
        $this->assertInternalType('string', $rawFile);
        $this->assertNull($commit->getFileStatus($notUpdatedFilePath));
    }

    /**
     * Tests binary binary file
     */
    public function testBinary()
    {
        $fileSize = $this->variables['binaryTest']['fileSize'];

        $commit = $this->repository->getCommit($this->variables['binaryTest']['commitId']);
        $commit->getRawBinaryFile($this->variables['binaryTest']['filePath'], function($data) use (&$fileSize) {
            $fileSize -= strlen($data);
        });

        $this->assertEquals(0, $fileSize);
    }
}
