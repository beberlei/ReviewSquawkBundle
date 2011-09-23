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
     * @Route("/project/{projectId}/github/post-receive", name="rs_project_github_postrecieve")
     * @Method("POST")
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     * @return void
     */
    public function githubCommitsAction($projectId)
    {
        $request =  $this->getRequest();
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $project = $em->getRepository('Whitewashing\ReviewSquawkBundle\Entity\Project')
                ->findOneBy(array('token' => $request->get('token')));

        if (!$project || $project->getId() != $projectId) {
            throw $this->createNotFoundException("No project found.");
        }

        if (!$request->request->has('payload')) {
            throw \RuntimeEception("No payload given");
        }

        $payload = $request->request->get('payload');
        if (strpos($payload, "{") === 0) {
            $payload = json_decode($payload, true);
        } else {
            throw \RuntimeException("Invalid payload given");
        }

        /* @var $githubService \Whitewashing\ReviewSquawkBundle\Model\GithubReviewService */
        $githubService = $this->container->get('whitewashing.review_squawk.github_review_service');

        foreach ($payload['commits'] AS $commitData) {
            if ($this->commitExists($project->getId(), $commitData['id'])) {
                continue;
            }

            $commit = new Commit($commitData['id'], $project, $project->getUser());
            $em->persist($commit);
            $githubService->reviewCommit($project->toProjectStruct(), $commit->getRevision());
        }
        $em->flush();

        return new Response('{"ok":true}', 200);
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
        $currentUser = $this->container->get('security.context')->getToken()->getUser();
        $commitId = $request->request->get('commitId');

        if (!$commitId) {
            throw new HttpException(400, "Bad request with 'commitId' parameter missing.");
        }

        if ($project->getUser() != $currentUser) {
            throw new AccessDeniedHttpException("Invalid user to review this commit.");
        }

        if ($this->commitExists($project->getId(), $commitId)) {
            $this->container->get('session')->setFlash('rs', 'Commit ' . $commitId . ' was already reviewed.');
            return $this->redirect($this->generateUrl('rs_github_project_show', array('id' => $project->getId())));
        }

        $commit = new Commit($commitId, $project, $currentUser);

        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $em->persist($commit);
        $em->flush();

        /* @var $githubService \Whitewashing\ReviewSquawkBundle\Model\GithubReviewService */
        $githubService = $this->container->get('whitewashing.review_squawk.github_review_service');
        $githubService->reviewCommit($project->toProjectStruct(), $commit->getRevision());

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
        $currentUser = $this->container->get('security.context')->getToken()->getUser();

        if ($project->getUser() != $currentUser) {
            throw new AccessDeniedHttpException("Invalid user to review this commit.");
        }

        if ($this->commitExists($project->getId(), $commitId)) {
            $this->container->get('session')->setFlash('rs', 'Commit ' . $commitId . ' was already reviewed.');
            return $this->redirect($this->generateUrl('rs_github_project_show', array('id' => $project->getId())));
        }

        return array('project' => $project, 'commitId' => $commitId);
    }

    /**
     * @Route("/project/{projectId}/review/commits/{commitId}.html", name="rs_review_commit_view")
     * @Template()
     * @param $projectId
     * @param $commitId
     * @return void
     */
    public function getCommitAction($projectId, $commitId)
    {
        $project = $this->getProject($projectId);
        $currentUser = $this->container->get('security.context')->getToken()->getUser();

        if ($project->getUser() != $currentUser) {
            throw new AccessDeniedHttpException("Invalid user to review this commit.");
        }

        $client = $this->container->get('whitewashing.review_squawk.github_client');
        $diffs = $client->getCommitDiffs($project->getRepositoryUrl(), $commitId);

        return array('project' => $project, 'diffs' => $diffs, 'commitId' => $commitId);
    }

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

    private function commitExists($projectId, $commitId)
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $exists = $em->getRepository('Whitewashing\ReviewSquawkBundle\Entity\Commit')
                     ->findOneBy(array('project' => $projectId, 'revision' => $commitId));
        return ($exists) ? true : false;
    }
}