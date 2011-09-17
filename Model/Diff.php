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

    public function __construct($path, $oldCode, $newCode)
    {
        $this->path = $path;
        $this->oldCode = $oldCode;
        $this->newCode = $newCode;
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
}