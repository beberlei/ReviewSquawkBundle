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

class Violation
{
    private $path;
    private $line;
    private $message;

    function __construct($path, $line, $message)
    {
        $this->path = $path;
        $this->line = $line;
        $this->message = $message;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function getMessage()
    {
        return $this->message;
    }
}