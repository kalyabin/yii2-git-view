<?php
namespace gitView;

use vcsCommon\BaseWrapper;
use vcsCommon\exception\CommonException;
use vcsCommon\exception\RepositoryException;

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
     * @throws RepositoryException
     */
    public function getRepository($dir)
    {
        return new Repository($dir, $this);
    }
}