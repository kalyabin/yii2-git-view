<?php
namespace gitView;

use exception\CommonException;
use exception\RepositoryException;
use yii\base\Object;
use yii\helpers\FileHelper;

/**
 * Repository class
 * Provides access control to project repository
 */
class Repository extends Object
{
    /**
     * Directory name of git repository
     */
    const GIT_PATH = '.git';

    /**
     * @var Git common Git interface
     */
    protected $git;

    /**
     * @var string path to project
     */
    protected $projectPath;

    /**
     * @var string path to .git
     */
    protected $gitPath;

    /**
     * Get repository from directory.
     * Throws RepositoryException if repository not found at dir.
     *
     * @param string $dir project path (not a path to .git!)
     * @param Git $git
     * @throws RepositoryException
     */
    public function __construct($dir, Git $git)
    {
        $projectPath = FileHelper::normalizePath($dir);
        $gitPath = FileHelper::normalizePath($projectPath . DIRECTORY_SEPARATOR . self::GIT_PATH);
        if (!is_dir($gitPath)) {
            throw new RepositoryException('Repository not found at ' . $dir);
        }
        $this->projectPath = $projectPath;
        $this->gitPath = $gitPath;
        $this->git = $git;
        $this->checkStatus();
        parent::__construct([]);
    }

    /**
     * Returns Git common interface
     *
     * @return Git
     */
    public function getGit()
    {
        return $this->git;
    }

    /**
     * Check repository status and returns it.
     *
     * @return string
     * @throws RepositoryException
     */
    public function checkStatus()
    {
        try {
            $result = $this->git->execute(['status', '--short'], $this->projectPath);
        } catch (CommonException $ex) {
            throw new RepositoryException("Can't get repository status", $ex->getCode(), $ex);
        }
        return $result;
    }
}