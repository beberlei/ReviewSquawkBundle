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

class GithubReviewService
{
    private $client;
    private $csService;

    public function __construct(ClientAPI $client, CodeSnifferService $csService)
    {
        $this->client = $client;
        $this->csService = $csService;
    }

    public function reviewCommit(Project $project, $commitId)
    {
        $parts = explode("/", $project->getRepositoryUrl);
        $repository = array_pop($parts);
        $userOrg = array_pop($parts);

        /** @var $api \Whitewashing\ReviewSquawkBundle\Model\Github\ClientAPI */
        $diffs = $this->client->getCommitDiffs($userOrg, $repository, $commitId);

        $comments = array();
        $positions = array();
        foreach ($diffs AS $diff) {
            $violations = $this->csService->scan($project, $diff);

            foreach ($violations AS $violation) {
                $position = $diff->getDiffPositionForLine($violation->getLine());
                if ($position === false) {
                    continue;
                }
                $positions[$violation->getPath()][$violation->getLine()] = $position;

                /** @var $violation \Whitewashing\ReviewSquawkBundle\Model\Violation */
                if (!isset($comments[$violation->getPath()][$violation->getLine()])) {
                    $comments[$violation->getPath()][$violation->getLine()] = "";
                }
                $comments[$violation->getPath()][$violation->getLine()] .= $violation->getMessage() . "\n";
            }
        }

        foreach ($comments AS $path => $fileComments) {
            foreach ($fileComments AS $line => $comment) {
                $this->client->commentCommit(
                    $project->getUser()->getAccessToken(),
                    $userOrg,
                    $repository,
                    $$commitId,
                    $path,
                    $line,
                    $positions[$path][$line],
                    rtrim($comment)
                );
            }
        }
    }
}