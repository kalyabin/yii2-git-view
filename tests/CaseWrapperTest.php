<?php
namespace tests;

use GitView\GitWrapper;
use GitView\Repository;
use PHPUnit_Framework_TestCase;
use Yii;

/**
 * Test wrapper
 */
class CaseWrapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array testing variables
     */
    protected $variables = [];

    /**
     * @inheritdoc
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->variables = Yii::$app->params['wrapper'];
    }

    /**
     * Test valid sha1 response
     */
    public function testValidSha1()
    {
        $cmd = $this->variables['availCmd'];

        $wrapper = new GitWrapper([
            'cmd' => $cmd,
        ]);

        // not valid sha1
        $this->assertEquals(false, $wrapper->checkIsSha1(1));
        $this->assertEquals(false, $wrapper->checkIsSha1(0xaaa1));
        $this->assertEquals(false, $wrapper->checkIsSha1('0xaaa1'));
        $this->assertEquals(false, $wrapper->checkIsSha1(true));
        $this->assertEquals(false, $wrapper->checkIsSha1(false));
        $this->assertEquals(false, $wrapper->checkIsSha1($wrapper));
        $this->assertEquals(false, $wrapper->checkIsSha1([]));
        $this->assertEquals(false, $wrapper->checkIsSha1(null));
        $this->assertEquals(true, $wrapper->checkIsSha1(md5('123')));
    }

    /**
     * Wrapper constructor test
     *
     * @return GitWrapper
     */
    public function testConstructor()
    {
        $cmd = $this->variables['availCmd'];

        // set variables using constructor
        $wrapper = new GitWrapper([
            'cmd' => $cmd,
        ]);
        $this->assertInstanceOf(GitWrapper::className(), $wrapper);
        $this->assertEquals($cmd, $wrapper->getCmd());

        // set variables without constructor
        $wrapper->setCmd($cmd);
        $this->assertEquals($cmd, $wrapper->getCmd());

        // check version
        $wrapper->checkVersion();
        $this->assertRegExp('/^([\d]+)\.?([\d]+)?\.?([\d]+)?$/', $wrapper->getVersion());

        return $wrapper;
    }

    /**
     * Tests wrapper constructor exceptions
     *
     * @param GitWrapper $wrapper
     *
     * @depends testConstructor
     * @expectedException VcsCommon\exception\CommonException
     */
    public function testContructorException(GitWrapper $wrapper)
    {
        $cmd = $this->variables['errorCmd'];
        $wrapper->setCmd($cmd);
    }

    /**
     * Tests command exceptions
     *
     * @param GitWrapper $wrapper
     *
     * @depends testConstructor
     * @expectedException VcsCommon\exception\CommonException
     */
    public function testCommandException(GitWrapper $wrapper)
    {
        $cmd = $this->variables['availCmd'];
        $wrapper->setCmd($cmd);
        $wrapper->execute(['random-command']);
    }

    /**
     * Test random command using repository
     *
     * @param GitWrapper $wrapper
     *
     * @depends testConstructor
     */
    public function testRandomCommand(GitWrapper $wrapper)
    {
        $cmd = $this->variables['availCmd'];
        $wrapper->setCmd($cmd);

        $command = ['log', '--skip' => 1, '-n', '1'];
        $result = $cmd . ' log --skip=1 -n 1';

        $this->assertEquals($result, $wrapper->buildCommand($command));

        $this->assertInternalType(
            'string',
            $wrapper->execute($command, $this->variables['availRepository'], false)
        );
        $this->assertInternalType(
            'array',
            $wrapper->execute($command, $this->variables['availRepository'], true)
        );

        $this->assertNotEmpty($wrapper->execute($command, $this->variables['availRepository'], false));
        $this->assertNotEmpty($wrapper->execute($command, $this->variables['availRepository'], true));
    }

    /**
     * Tests repository getter
     *
     * @param GitWrapper $wrapper
     * @depends testConstructor
     */
    public function testRepository(GitWrapper $wrapper)
    {
        $cmd = $this->variables['availCmd'];
        $repoPath = $this->variables['availRepository'];

        $wrapper->setCmd($cmd);
        $repository = $wrapper->getRepository($repoPath);
        $this->assertInstanceOf(Repository::className(), $repository);
    }

    /**
     * Tests repository getter error
     *
     * @param GitWrapper $wrapper
     * @depends testConstructor
     * @expectedException VcsCommon\exception\CommonException
     */
    public function testRepositoryException(GitWrapper $wrapper)
    {
        $cmd = $this->variables['availCmd'];
        $repoPath = $this->variables['errorRepository'];

        $wrapper->setCmd($cmd);
        $wrapper->getRepository($repoPath);
    }
}
