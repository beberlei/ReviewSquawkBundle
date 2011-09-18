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

namespace Whitewashing\ReviewSquawkBundle\Model\Github;

interface ClientAPI
{
    public function claimOAuthAccessToken($temporaryCode);

    public function getCommitDiffs($username, $repository, $sha1);

    /** http://developer.github.com/v3/repos/commits/ */
    public function commentCommit($accessToken, $username, $repository, $sha1, $path, $line, $position, $message);

    /** http://developer.github.com/v3/pulls/comments/ */
    public function commentPullRequest($accessToken, $username, $repository, $prId, $sha1, $path, $line, $message);

    public function getCurrentUser($accessToken);

    public function getProject($username, $repository);

    public function getCommits($username, $repository);
}