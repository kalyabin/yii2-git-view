<?php
namespace GitView;

use VcsCommon\BaseRepository;
use VcsCommon\exception\CommonException;
use VcsCommon\File;
use VcsCommon\Graph;

/**
 * Repository class
 * Provides access control to project repository
 */
class Repository extends BaseRepository
{
    const LOG_FORMAT = "%H%n%P%n%an%n%ae%n%ad%n%s%n";

    /**
     * @var GitWrapper common GIT interface
     */
    protected $wrapper;

    /**
     * Defines a commit diff command
     */
    const DIFF_COMMIT = 'commit';

    /**
     * Defines a compare diff command
     */
    const DIFF_COMPARE = 'compare';

    /**
     * Defines a path diff command
     */
    const DIFF_PATH = 'path';

    /**
     * Defines a full repository diff command
     */
    const DIFF_REPOSITORY = 'repository';

    /**
     * Check repository status and returns it.
     *
     * @return string
     * @throws CommonException
     */
    public function checkStatus()
    {
        $result = $this->wrapper->execute(['status', '--short'], $this->projectPath);
        return $result;
    }

    /**
     * Returns repository branches models.
     *
     * @return Branch[]
     * @throws CommonException
     */
    public function getBranches()
    {
        $ret = [];

        $list = $this->wrapper->execute(['branch', '-v'], $this->projectPath, true);

        $pattern = '#^[\s]+|[\t]+(^[\s]+)[\s]+([a-fA-F0-9]+)[\s]+(.*)$#iU';

        foreach ($list as $str) {
            if ($isCurrent = $str[0] == '*') {
                // replace * for common splitting string
                $str = mb_substr($str, 1, mb_strlen($str));
            }
            if (preg_match($pattern, $str)) {
                list ($id, $head, $message) = (preg_split('/[\s]+|[\t]+/', trim($str), 3));
                $ret[] = new Branch($this, [
                    'id' => $id,
                    'head' => $head,
                    'isCurrent' => $isCurrent,
                ]);
            }
        }

        return $ret;
    }

    /**
     * Returns commit object by commit id.
     *
     * @return Commit
     * @throws CommonException
     */
    public function getCommit($id)
    {
        $result = $this->wrapper->execute([
            'show', $id, '--pretty=format:\'' . self::LOG_FORMAT . '\'', '--name-status'
        ], $this->projectPath, true);
        list ($id, $parent, $contributorName, $contributorEmail, $date, $message) = $result;
        $commit = new Commit($this, [
            'id' => $id,
            'parentsId' => $parent,
            'contributorName' => $contributorName,
            'contributorEmail' => $contributorEmail,
            'date' => $date,
            'message' => $message,
        ]);

        // get changed files
        if (count($result) > 7) {
            for ($x = 7; $x < count($result); $x++) {
                $pieces = preg_split('#[\s]+#', trim($result[$x]), 2);
                if (count($pieces) == 2) {
                    // first item is a file status, second item is a file path
                    $commit->appendChangedFile(new File(
                        $this->getProjectPath() . DIRECTORY_SEPARATOR . $pieces[1],
                        $this,
                        $pieces[0]
                    ));
                }
            }
        }

        return $commit;
    }

    /**
     * Returns diff by specific command line params.
     *
     * Can receive everybody params for command line like this:
     *
     * ```php
     * $wrapper = new GitWrapper();
     * $repo = $wrapper->getRepository('/path/to/repository');
     *
     * // get commit diff:
     * print_r($repo->getDiff('commit', '<commit_sha1>'));
     *
     * // get commit compare
     * print_r($repo->getDiff('compare', '<commit_sha1_first_commit>', '<commit_sha1_last_commit>');
     *
     * // get file diff
     * print_r($repo->getDiff('file', '/path/to/file');
     *
     * // get file diff by specific commit
     * print_r($repo->getDiff('file', '/path/to/file', '<sha1>');
     *
     * // get full repo diff
     * print_r($repo->getDiff('repository');
     * ```
     *
     * @see \kalyabin\VcsCommon\BaseRepository::getDiff()
     * @return string[]
     * @throws CommonException
     */
    public function getDiff()
    {
        $command = ['show', "--format=''"];

        $type = func_num_args() >= 1 ? func_get_arg(0) : null;
        $arg1 = func_num_args() >= 2 ? func_get_arg(1) : null;
        $arg2 = func_num_args() >= 3 ? func_get_arg(2) : null;

        if ($type == self::DIFF_COMMIT && $this->wrapper->checkIsSha1($arg1)) {
            // commit diff command requires second param a commit sha1
            $command[] = $arg1;
        }
        else if ($type == self::DIFF_COMPARE && $this->wrapper->checkIsSha1($arg1) && $this->wrapper->checkIsSha1($arg2)) {
            // commits compare requires second param a commit sha1 and third param too
            $command[] = $arg1 . '..' . $arg2;
        }
        else if ($type == self::DIFF_PATH && is_string($arg1)) {
            // path diff requires second param like a string
            // if this is not a valid path - GitWrapper throws CommonException
            if (is_string($arg2) && $this->wrapper->checkIsSha1($arg2)) {
                // specific file commit
                $command[] = $arg2;
            }
            $command[] = '--';
            $command[] = $arg1;
        }
        else if ($type == self::DIFF_REPOSITORY) {
            // full repo diff
            $command[] = '--';
            $command[] = '.';
        }
        else {
            // nobody
            throw new CommonException('Type a valid command');
        }

        $result = $this->wrapper->execute($command, $this->projectPath, true);
        if (empty($result)) {
            // force git diff command
            $command[0] = 'diff';
            $command[1] = "--pretty=format:''";
            $result = $this->wrapper->execute($command, $this->projectPath, true);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getHistory($limit, $skip, $path = null)
    {
        $ret = [];

        $command = [
            'log', '--format=\'' . self::LOG_FORMAT . '\'',
            '-n', (int) $limit, '--skip' => (int) $skip
        ];
        if (!is_null($path)) {
            $command[] = '-- ' . $path;
        }

        $result = $this->wrapper->execute($command, $this->projectPath, true);

        $commit = [];
        foreach ($result as $row) {
            if (count($commit) < 6) {
                $commit[] = $row;
            }
            else if (!empty($commit)) {
                list ($id, $parent, $contributorName, $contributorEmail, $date, $message) = $commit;
                $ret[] = new Commit($this, [
                    'id' => $id,
                    'parentsId' => $parent,
                    'contributorName' => $contributorName,
                    'contributorEmail' => $contributorEmail,
                    'date' => $date,
                    'message' => $message,
                ]);
                $commit = [];
            }
        }

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function getGraphHistory($limit, $skip, $path = null)
    {
        $ret = new Graph();

        $rawHistory = $this->getHistory($limit, $skip);

        $result = $this->wrapper->execute([
            'log', '--graph', '--format' => "format:''",
            '-n', (int) $limit, '--skip' => (int) $skip,
        ], $this->projectPath, true);

        $cursor = 0;
        foreach ($result as $row) {
            $row = str_replace(' ', '', $row);
            if (strpos($row, '*') !== false && isset($rawHistory[$cursor])) {
                $rawHistory[$cursor]->graphLevel = strpos($row, '*');
                $ret->pushCommit($rawHistory[$cursor]);
                $cursor++;
            }
        }

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function pathIsNotIgnored($filePath)
    {
        $ret = true;

        $filePath = ltrim($filePath, DIRECTORY_SEPARATOR);

        static $ignoredFilesList = [];

        $dirname = dirname($filePath);

        if (!isset($ignoredFilesList[$dirname])) {
            $command = [
                'ls-files', $dirname, '--ignored', '--exclude-standard', '--others',
            ];
            $ignoredFilesList[$dirname] = $this->wrapper->execute($command, $this->projectPath, true);
        }

        return isset($ignoredFilesList[$dirname]) && !in_array($filePath, $ignoredFilesList[$dirname]);
    }
}
