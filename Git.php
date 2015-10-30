<?php
namespace gitView;

use exception\CommonException;
use exception\RepositoryException;
use yii\base\Object;

/**
 * This class provides access to git console command and
 * implements common methods.
 */
class Git extends Object
{
    /**
     * @var string path to console git command
     */
    protected $cmd = 'git';

    /**
     * @var string git version
     */
    protected $version;

    /**
     * Sets cmd property and checks git version
     *
     * @param string $cmd
     */
    public function setCmd($cmd)
    {
        $this->cmd = $cmd;
        $this->checkVersion();
    }

    /**
     * Returns cmd property
     *
     * @return string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * Returns git version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Build console command.
     * Params may be array, or string.
     *
     * If it's array like this:
     * ['log', '--skip' => 10, '-L', 10]
     *
     * then result be: 'git log --skip=10 -L 10'.
     *
     * @param type $params
     * @return string
     */
    public function buildCommand($params)
    {
        $ret = '';

        if (is_scalar($params)) {
            $ret = $params;
        }
        else if (is_array($params)) {
            $ret = [];
            foreach ($params as $k => $v) {
                if (is_string($k) && trim($k) && is_scalar($v)) {
                    $ret[] = "$k=$v";
                }
                else if (is_scalar($v)) {
                    $ret[] = $v;
                }
            }
            $ret = implode(' ', $ret);
        }

        return !empty($ret) ? $this->cmd . ' ' . $ret : $this->cmd;
    }

    /**
     * Execute git command with params.
     *
     * @param string|array $params command prefix, see buildCommand method for details.
     * @param string $dir directory in which the command is executed
     * @param boolean $getArray returns execution result as array if true, or string if false
     * @return string|array
     * @throws CommonException
     */
    public function execute($params, $dir = null, $getArray = false)
    {
        $currentDirectory = getcwd();
        $result = [];
        $exitCode = 0;
        $cmd = $this->buildCommand($params);
        if ($dir) {
            chdir($dir);
        }
        exec($cmd, $result, $exitCode);
        if ($exitCode != 0) {
            chdir($currentDirectory);
            throw new CommonException('Command ' . $cmd . ' ended with ' . $exitCode . ' status code', $exitCode);
        }
        chdir($currentDirectory);
        return $getArray ? $result : implode("\n", $result);
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