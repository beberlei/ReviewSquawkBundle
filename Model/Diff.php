<?php
/**
 * Whitewashing
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Whitewashing\ReviewSquawkBundle\Model;

class Diff
{
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $oldCode;
    /**
     * @var string
     */
    private $newCode;
    /**
     * @var string
     */
    private $diff;

    /**
     * @param $path
     * @param $oldCode
     * @param $newCode
     * @param $patch
     */
    public function __construct($path, $oldCode, $newCode, $patch = "")
    {
        $this->path = $path;
        $this->oldCode = $oldCode;
        $this->newCode = $newCode;
        $this->patch = $patch;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getOldCode()
    {
        return $this->oldCode;
    }

    public function getNewCode()
    {
        return $this->newCode;
    }

    public function getDiff()
    {
        return $this->patch;
    }

    public function getPatchPositionForLine($linePos)
    {
        if (strlen($this->patch) == 0) {
            return $linePos;
        } else {
            $lines = explode("\n", str_replace("\r\n", "\n", $this->patch));

            $pos = 0;
            $currentLine = 0;
            for ($i = 2; $i < count($lines); $i++) {
                if (preg_match('(@@ \-([0-9]+),([0-9]+) \+([0-9]+),([0-9]+))', $lines[$i], $match)) {
                    $currentLine = $match[1];
                } else {
                    if ($linePos == $currentLine) {
                        return $pos;
                    }

                    $currentLine++;
                    $pos++;
                }
            }
            return false;
        }
    }
}