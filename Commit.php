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
                $diff = new Diff();
                $diff->setResults($previewFile);
                $ret[] = $diff;
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

        $appendFileDiff();

        return $ret;
    }
}