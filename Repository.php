<?php
namespace GitView;

use VcsCommon\BaseRepository;
use VcsCommon\exception\CommonException;

/**
 * Repository class
 * Provides access control to project repository
 */
class Repository extends BaseRepository
{
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
     * @return BaseCommit
     * @throws CommonException
     */
    public function getCommit($id)
    {
        $prettyFormat = '';

        $result = $this->wrapper->execute([
            'show', $id, '--pretty=format:\'%H%n%an%n%ae%n%ad%n%f%n\''
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
}