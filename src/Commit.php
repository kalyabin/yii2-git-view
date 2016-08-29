<?php
namespace GitView;

use DateTime;
use GitView\Diff;
use VcsCommon\BaseCommit;
use yii\helpers\StringHelper;

/**
 * Represents GIT commit model
 */
class Commit extends BaseCommit
{
    const DATE_TIME_FORMAT = 'D M d H:i:s Y O';

    /**
     * @inheritdoc
     */
    protected function parseDateInternal($value)
    {
        return DateTime::createFromFormat(self::DATE_TIME_FORMAT, $value);
    }

    /**
     * @inheritdoc
     */
    public function getDiff($file = null)
    {
        $previewFile = [];
        $ret = [];

        $appendFileDiff = function() use (&$previewFile, &$ret) {
            if (!empty($previewFile)) {
                $ret[] = new Diff($previewFile);
                $previewFile = [];
            }
        };

        $fullDiff = [];
        if (!is_null($file)) {
            $fullDiff = $this->repository->getDiff(Repository::DIFF_PATH, $file, $this->id);
        }
        else {
            $fullDiff = $this->repository->getDiff(Repository::DIFF_COMMIT, $this->id);
        }

        foreach ($fullDiff as $row) {
            if (StringHelper::startsWith($row, 'diff')) {
                // the new file diff, append to $ret
                $appendFileDiff();
            }
            $previewFile[] = $row;
        }

        // append last file diff to full array
        $appendFileDiff();

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function getRawFile($filePath)
    {
        $params = [
            'show', $this->id . ':' . escapeshellcmd($filePath),
        ];
        return $this->repository->getWrapper()->execute($params, $this->repository->getProjectPath());
    }

    /**
     * @inheritdoc
     */
    public function getRawBinaryFile($filePath, $streamHandler)
    {
        $params = [
            'show', $this->id . ':' . escapeshellcmd($filePath)
        ];
        $this->repository->getWrapper()->executeBinary($streamHandler, $params, $this->repository->getProjectPath());
    }

    /**
     * @inheritdoc
     */
    public function getPreviousRawFile($filePath)
    {
        $params = [
            'show', $this->id . '^:' . escapeshellcmd($filePath),
        ];
        return $this->repository->getWrapper()->execute($params, $this->repository->getProjectPath());
    }

    /**
     * @inheritdoc
     */
    protected function getChangedFilesInternal()
    {
        $result = $this->repository->getWrapper()->execute([
            'show', $this->getId(), '--pretty=format:\'\'', '--name-status'
        ], $this->repository->projectPath, true);

        foreach ($result as $row) {
            $pieces = preg_split('#[\s]+#', trim($row), 2);
            if (count($pieces) == 2) {
                yield $pieces[1] => $pieces[0];
            }
        }
    }
}
