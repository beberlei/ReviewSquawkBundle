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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Whitewashing\ReviewSquawkBundle\Entity\Commit;

class ReviewController extends Controller
{
    /**
     * @param int $projectId
     * @return \Whitewashing\ReviewSquawkBundle\Entity\Project
     */
    private function getProject($projectId)
    {
        $project = $this->container->get('doctrine.orm.default_entity_manager')
                    ->find('Whitewashing\ReviewSquawkBundle\Entity\Project', $projectId);
        if (!$project) {
            throw $this->createNotFoundException("No project found!");
        }
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user !== $project->getUser()) {
            throw new AccessDeniedHttpException("Not your own project");
        }

        return $project;
    }

    /**
     * Creates a new commit event for the project.
     *
     * Verifies commit really exists and was not indexed before and then queues it into processing.
     *
     * @todo Remove Technical Debt here
     * @Route("/project/{projectId}/review/commits", name="rs_review_commit")
     * @Method("POST")
     * @param int $projectId
     * @return Response
     */
    public function postCommitsAction($projectId)
    {
        $project = $this->getProject($projectId);

        $request = $this->getRequest();
        $commitId = $request->request->get('commitId');

        if (!$commitId) {
            throw new HttpException(400, "Bad request with 'commitId' parameter missing.");
        }

        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $commit = new Commit($commitId, $project, $this->container->get('security.context')->getToken()->getUser());
        $em->persist($commit);
        $em->flush();

        $parts = explode("/", $project->getRepositoryUrl());
        $repository = array_pop($parts);
        $userOrg = array_pop($parts);

        /** @var $api \Whitewashing\ReviewSquawkBundle\Model\Github\ClientAPI */
        $api = $this->container->get('whitewashing.review_squawk.github_client');
        $diffs = $api->getCommitDiffs($userOrg, $repository, $commit->getRevision());

        $cs = $this->container->get('whitewashing.review_squawk.code_sniffer');
        $comments = array();
        $positions = array();
        foreach ($diffs AS $diff) {
            $violations = $cs->scan($project->toProjectStruct(), $diff);

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
                $api->commentCommit(
                    $project->getUser()->getAccessToken(),
                    $userOrg,
                    $repository,
                    $commit->getRevision(),
                    $path,
                    $line,
                    $positions[$path][$line],
                    rtrim($comment)
                );
            }
        }

        return $this->redirect($this->generateUrl('rs_github_project_show', array('id' => $project->getId())));
    }

    /**
     * @Route("/project/{projectId}/review/commits/{commitId}/new.html", name="rs_review_commit_new")
     * @Template()
     * @param $projectId
     * @param $commitId
     * @return void
     */
    public function newCommitAction($projectId, $commitId)
    {
        $project = $this->getProject($projectId);
        return array('project' => $project, 'commitId' => $commitId);
    }
}