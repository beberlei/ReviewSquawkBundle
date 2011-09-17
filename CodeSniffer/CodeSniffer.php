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

namespace Whitewashing\ReviewSquawkBundle\CodeSniffer;

class CodeSniffer
{
    public function clean()
    {
        // Reset the members.
        $this->listeners       = array();
        $this->files           = array();
        $this->_tokenListeners = array(
                                  'file'      => array(),
                                  'multifile' => array(),
                                 );
    }
}