<?php
namespace GitView;

use VcsCommon\BaseWrapper;
use VcsCommon\exception\CommonException;

/**
 * This class provides access to git console command and implements common methods.
 */
class GitWrapper extends BaseWrapper
{
    /**
     * @var string path to console git command
     */
    protected $cmd = 'git';

    /**
     * Returns repository path name like .git, .hg, etc.
     *
     * @return string
     */
    public function getRepositoryPathName()
    {
        return '.git';
    }

    /**
     * Checks git version and set it to version property.
     *
     * @throws CommonException
     */
    public function checkVersion()
    {
        $pattern = '#^git[\s]version[\s]([\d]+\.?([\d]+)?\.([\d]+)?)$#';

        $result = $this->execute('--version');
        if (!preg_match($pattern, $result)) {
            throw new CommonException('Git command not found');
        }
        $this->version = preg_replace($pattern, '$1', $result);
    }

    /**
     * Create repository instance by provided directory.
     * Directory must be a path of project (not a .git path).
     *
     * @param string $dir project directory
     * @return Repository
     * @throws CommonException
     */
    public function getRepository($dir)
    {
        return new Repository($dir, $this);
    }

    /**
     * Return true if param is valid sha1 identifier.
     *
     * @param string $str
     * @return boolean
     */
    public function checkIsSha1($str)
    {
        return is_string($str) && preg_match('#^[a-f|A-F|0-9]+$#i', $str);
    }

    /**
     * @inheritdoc
     */
    public function buildCommand($params)
    {
        // prepend postfix --no-pager for all commands
        if (is_scalar($params)) {
            $params = '--no-pager ' . $params;
        } elseif (is_array($params)) {
            $params = \yii\helpers\ArrayHelper::merge([
                '--no-pager'
            ], $params);
        }

        return parent::buildCommand($params);
    }
}
