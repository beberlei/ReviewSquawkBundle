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

namespace Whitewashing\ReviewSquawkBundle\Model\Platforms;

use Whitewashing\ReviewSquawkBundle\Model\Project;
use Github_Api_Commit;

class Github implements CodeReviewTool
{
    /**
     * @var Github_ApiInterface
     */
    private $commitApi;

    public function __construct(Github_Api_Commit $commitApi)
    {
        $this->commitApi = $commitApi;
    }

    public function getDiffs(Project $project, $commitId)
    {
        $url = $project->getRepositoryUrl();
        if (strpos($url, "https://github.com/") === 0) {
            $parts = parse_url($url);
            list($username, $repo, $rest) = explode("/", ltrim($parts['path'], "/"), 3);
        }

        $commit = $this->commitApi->getCommit($username, $repo, $commitId);
    }
    
    public function reportViolation(Project $project, Violation $violation)
    {

    }
}