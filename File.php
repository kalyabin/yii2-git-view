<?php
namespace gitView;

use DirectoryIterator;
use gitView\exception\RepositoryException;
use yii\base\Object;
use yii\helpers\StringHelper;

/**
 * Implements file operations
 */
class File extends Object
{
    /**
     * @var string file name
     */
    protected $name;

    /**
     * @var string file path
     */
    protected $path;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Constructor
     *
     * @param DirectoryIterator $iterator
     * @param Repository $repository
     * @throws RepositoryException
     */
    public function __construct(DirectoryIterator $iterator, Repository $repository)
    {
        $this->name = $iterator->getFilename();
        $this->path = realpath($iterator->getPathname());
        $this->repository = $repository;
        if (!StringHelper::startsWith($this->path, $repository->getProjectPath())) {
            throw new RepositoryException("Path $this->path outband of repository");
        }
        parent::__construct([]);
    }

    /**
     * Returns file path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns directory path
     *
     * @return string
     */
    public function getDirectory()
    {
        return dirname($this->path);
    }

    /**
     * Returns file base name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->name;
    }
}