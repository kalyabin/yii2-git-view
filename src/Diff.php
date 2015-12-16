<?php
namespace GitView;

use VcsCommon\BaseDiff;
use yii\helpers\StringHelper;

/**
 * Diff object. Parse command line results to public variables.
 */
class Diff extends BaseDiff
{
    /**
     * @inheritdoc
     */
    protected function initialize($rows)
    {
        $diffId = null;

        $this->previousFilePath = self::NULL_PATH;
        $this->newFilePath = self::NULL_PATH;

        foreach ($rows as $n => $row) {
            if ($n >= 0 && $n <= 3) {
                // first 3 lines are description
                if (trim($this->description)) {
                    $this->description .= PHP_EOL . $row;
                }
                else {
                    $this->description = $row;
                }

                if (StringHelper::startsWith($row, 'Binary files')) {
                    // stop parsing if diff is parsing
                    $this->isBinary = true;
                    break;
                }

                // old or new file path
                $matches = [];
                if (preg_match('#^\-\-\-[\s]a(.*)$#i', $row, $matches)) {
                    $this->previousFilePath = $matches[1];
                }
                else if (preg_match('#^\+\+\+[\s]b(.*)$#i', $row, $matches)) {
                    $this->newFilePath = $matches[1];
                }
            }
            else if (StringHelper::startsWith($row, '@@')) {
                // new diff line
                $diffId = $row;

                $matches = [];
                $pattern = '#^@@[\s]\-([\d]+),?([\d]+)?[\s]\+([\d]+),?([\d]+)?[\s]@@#i';
                preg_match($pattern, $row, $matches);

                $this->lines[$diffId] = [
                    'beginA' => isset($matches[1]) ? (int) $matches[1] : 1,
                    'beginB' => isset($matches[3]) ? (int) $matches[3] : 1,
                    'cntA' => isset($matches[2]) ? (int) $matches[2] : 0,
                    'cntB' => isset($matches[4]) ? (int) $matches[4] : 0,
                    'lines' => [],
                ];
            }
            else if ($diffId && isset($this->lines[$diffId])) {
                // changed row
                $this->lines[$diffId]['lines'][] = $row;
            }
        }
    }
}
