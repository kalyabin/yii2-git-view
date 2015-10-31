<?php
namespace gitView;

use gitView\exception\CommonException;
use gitView\exception\RepositoryException;
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
        $projectPath = FileHelper::normalizePath(realpath($dir));
        $gitPath = FileHelper::normalizePath($projectPath . '/' . self::GIT_PATH);
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
     * Returns project path
     *
     * @return string
     */
    public function getProjectPath()
    {
        return $this->projectPath;
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

    /**
     * Returns repository files list.
     * Param $subDir must be a subdirectory of project repository.
     *
     * @param string $subDir
     * @return \gitView\File[]
     * @throws RepositoryException
     */
    public function getFilesList($subDir = null)
    {
        $list = [];

        $dir = FileHelper::normalizePath(realpath($this->projectPath . '/' . $subDir));

        if (!is_dir($dir) || $dir == $this->gitPath) {
            throw new RepositoryException("Path $dir is not a directory");
        }

        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $path) {
            try {
                $file = null;
                if (
                    ($path->isDir() && !$path->isDot() && $path->getFilename() != self::GIT_PATH) ||
                    ($path->isDot() && $path->getFilename() != '.')
                ) {
                    $file = new Directory($path, $this);
                }
                else if ($path->isFile()) {
                    $file = new File($path, $this);
                }
                else if ($path->isLink()) {
                    $file = new FileLink($path, $this);
                }
                if ($file instanceof File) {
                    $list[] = $file;
                }
            }
            catch (RepositoryException $ex) { }
        }

        return $list;
    }
}