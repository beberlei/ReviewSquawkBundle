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

namespace Whitewashing\ReviewSquawkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    /**
     * Creates a new commit event for the project.
     *
     * Verifies commit really exists and was not indexed before and then queues it into processing.
     *
     * @Route("/project/{projectId}/review/commits")
     * @param type $projectId
     */
    public function postCommitAction($projectId)
    {
        return new Response(json_encode(array('ok' => true)));
    }

    /**
     * Returns information on the commit and all its diffs that were analyzed with all the violations.
     *
     * @Route("/project/{projectId}/review/commit/{commitId}")
     * @param type $projectId
     * @param type $commitId
     */
    public function getCommitAction($projectId, $commitId)
    {
        
    }

    /**
     * Request processing a change request from internal or external sources.
     *
     * A change request can contain one or many commits not necessarily managed in the projects repository.
     * An example are Github repositories or SVN branches
     *
     * @Route("/project/{projectId}/review/changes")
     * @param type $projectId
     * @return Response
     */
    public function postChangeRequestAction($projectId)
    {
        return new Response(json_encode(array('ok' => true)));
    }
}