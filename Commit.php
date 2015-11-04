<?php
namespace GitView;

use DateTime;
use VcsCommon\BaseCommit;

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
}