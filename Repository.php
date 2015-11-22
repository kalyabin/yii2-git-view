<?php
namespace GitView;

use VcsCommon\BaseRepository;
use VcsCommon\exception\CommonException;
use VcsCommon\Graph;

/**
 * Repository class
 * Provides access control to project repository
 */
class Repository extends BaseRepository
{
    const LOG_FORMAT = "%H%n%an%n%ae%n%ad%n%f%n";

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
            'show', $id, '--pretty=format:\'' . self::LOG_FORMAT . '\''
        ], $this->projectPath, true);
        list ($id, $contributorName, $contributorEmail, $date, $message) = $result;
        return new Commit($this, [
            'id' => $id,
            'contributorName' => $contributorName,
            'contributorEmail' => $contributorEmail,
            'date' => $date,
            'message' => $message,
        ]);
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
            $command[] = $arg1;
        }
        else if ($type == self::DIFF_REPOSITORY) {
            // full repo diff
            $command[] = '.';
        }
        else {
            // nobody
            throw new CommonException('Type a valid command');
        }

        return $this->wrapper->execute($command, $this->projectPath, true);
    }

    /**
     * @inheritdoc
     */
    public function getHistory($limit, $skip)
    {
        $ret = [];

        $result = $this->wrapper->execute([
            'log', '--pretty=format:\'' . self::LOG_FORMAT . '\'',
            '-n', (int) $limit, '--skip' => (int) $skip
        ], $this->projectPath, true);

        $commit = [];
        foreach ($result as $row) {
            if (trim($row)) {
                $commit[] = $row;
            }
            else if (!empty($commit)) {
                list ($id, $contributorName, $contributorEmail, $date, $message) = $commit;
                $ret[] = new Commit($this, [
                    'id' => $id,
                    'contributorName' => $contributorName,
                    'contributorEmail' => $contributorEmail,
                    'date' => $date,
                    'message' => $message,
                ]);
            }
        }

        return $ret;
    }

    /**
     * @inheritdoc
     * @todo now for each graph item runs git show command - this not powerful
     */
    public function getGraphHistory($limit, $skip)
    {
        /* @var $ret Graph */
        $ret = [];

        $result = $this->wrapper->execute([
            'log', '--graph', '--format' => "format:'{delim}%H'",
            '-n', (int) $limit, '--skip' => (int) $skip,
        ], $this->projectPath, true);

        // commit description pieces
        $commitPieces = [];
        // skip chars count for commit description parsing
        $commitSkipChars = 0;

        // parse each rows
        foreach ($result as $row) {
            if (!trim($row)) {
                continue;
            }
            $item = new Graph();
            // explode row by {delim} to detect start of commit description
            $pieces = explode('{delim}', $row);
            $commitId = isset($pieces[1]) ? $pieces[1] : null;
            $row = $pieces[0];
            // parse row chars
            for ($x = 0; $x <= strlen($row); $x++) {
                $char = substr($row, $x, 1);
                if ($char == '\\') {
                    $item->appendPiece(Graph::LEFT);
                }
                else if ($char == '/') {
                    $item->appendPiece(Graph::RIGHT);
                }
                else if ($char == '*') {
                    $item->appendPiece(Graph::COMMIT);
                }
                else if ($char == '|') {
                    $item->appendPiece(Graph::DIRECT);
                }
                else {
                    $item->appendPiece(Graph::SPACE);
                }
            }
            if ($commitId) {
                $item->setCommit($this->getCommit($commitId));
            }
            $ret[] = $item;
        }

        return $ret;
    }
}