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
    private static $standards;
    private $standard;
    private $showWarnings;

    /**
     * Scan the diff and report all new violations.
     *
     * @param Diff $diff
     * @return Violation[]
     */
    public function scan(Project $project, Diff $diff)
    {
        $this->standard = $project->getCodingStandard();
        $this->showWarnings = $project->getShowWarnings();

        $oldReport = $this->getCodeReport($diff->getPath(), $diff->getOldCode());
        $newReport = $this->getCodeReport($diff->getPath(), $diff->getNewCode());

        if (!$newReport['totals']['warnings'] && !$newReport['totals']['errors']) {
            return array();
        }

        $foundViolations = $this->computeNewViolations($diff->getPath(), $oldReport['errors'], $newReport['errors']);

        if ($this->showWarnings) {
            foreach ($this->computeNewViolations($diff->getPath(), $oldReport['warnings'], $newReport['warnings']) AS $violation) {
                $foundViolations[] = $violation;
            }
        }
        return $foundViolations;
    }

    private function computeNewViolations($path, $oldViolations, $newViolations)
    {
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

                    $foundViolations[] = new Violation($path, $line, $message['source'].": " . $message['message']);
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
        $sniffer->populateCustomRules($this->standard);
        $sniffer->populateTokenListeners();

        if (strlen($code)) {
            $phpcsFile = $sniffer->processFile($file, $code);
            return array(
                'totals' => array('warnings' => $phpcsFile->getWarningCount(), 'errors' => $phpcsFile->getErrorCount()),
                'errors' => $phpcsFile->getErrors(),
                'warnings' => $phpcsFile->getWarnings(),
            );
        } else {
            return array('totals' => array('warnings' => 0, 'errors' => 0), 'errors' => array(), 'warnings' => array());
        }
    }

    static public function getInstalledStandards()
    {
        if (self::$standards === null) {
            $cs = new \PHP_CodeSniffer();
            self::$standards = $cs->getInstalledStandards();
        }
        return self::$standards;
    }
}