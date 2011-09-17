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

class CodeSnifferService
{
    private $standard;

    private $showWarnings;

    public function __construct($standard, $showWarnings = false)
    {
        $this->standard = $standard;
        $this->showWarnings = $showWarnings;
    }

    /**
     * Scan the diff and report all new violations.
     *
     * @param Diff $diff
     * @return Violation[]
     */
    public function scan(Diff $diff)
    {
        $oldReport = $this->getCodeReport($diff->getPath(), $diff->getOldCode());
        $newReport = $this->getCodeReport($diff->getPath(), $diff->getNewCode());

        if (!$newReport['totals']['warnings'] && !$newReport['totals']['errors']) {
            return array();
        }

        $oldViolations = $oldReport['files'][$diff->getPath()]['messages'];
        $newViolations = $newReport['files'][$diff->getPath()]['messages'];

        $foundViolations = array();
        foreach ($newViolations AS $line => $violations) {
            foreach ($violations AS $column => $messages) {
                if (!isset($oldViolations[$line][$column])) {
                    $oldViolations[$line][$column] = array();
                }
                
                // both old and new code have violations at that line and column, are they the same?
                foreach ($messages AS $idx => $message) {
                    foreach ($oldViolations[$line][$column] AS $oldMessage) {
                        if ($oldMessage['source'] == $message['source'] && $oldMessage['message'] == $message['message']) {
                            continue 2;
                        }
                    }

                    $foundViolations[] = new Violation($diff->getPath(), $line, $message['source'].": " . $message['message']);
                }
            }
        }

        return $foundViolations;
    }

    private function getCodeReport($file, $code)
    {
        $cli = new \PHP_CodeSniffer_CLI();
        $sniffer = new \PHP_CodeSniffer();
        $sniffer->setTokenListeners($this->standard, array());

        $sniffer->populateTokenListeners();

        $sniffer->processFile($file, $code);
        return $sniffer->prepareErrorReport($this->showWarnings);
    }
}