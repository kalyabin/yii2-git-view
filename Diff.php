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
     * Sets public properties from command line using string variable.
     *
     * @param string[] $str
     */
    public function setResults($str)
    {
        $diffId = null;
        foreach ($str as $n => $row) {
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
                $this->newLines[$row] = [];
                $this->previousLines[$row] = [];
                $matches = [];
                $pattern = '#^@@[\s]\-([\d]+),?([\d]+)?[\s]\+([\d]+),?([\d]+)?[\s]@@#i';
                preg_match($pattern, $row, $matches);

                $lineANum = isset($matches[1]) ? (int) $matches[1] : 1;
                $lineBNum = isset($matches[3]) ? (int) $matches[3] : 1;

                $diffId = $row;
            }
            else if (StringHelper::startsWith($row, ' ')) {
                // a and b line version
                $lineANum++;
                $lineBNum++;

                $this->previousLines[$diffId][$lineANum] = substr($row, 1);
                $this->newLines[$diffId][$lineBNum] = substr($row, 1);
            }
            else if (StringHelper::startsWith($row, '+')) {
                // b line version
                $lineBNum++;

                $this->newLines[$diffId][$lineBNum] = substr($row, 1);
            }
            else if (StringHelper::startsWith($row, '-')) {
                // a line version
                $lineANum++;

                $this->previousLines[$diffId][$lineANum] = substr($row, 1);
            }
        }
    }
}