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
use Whitewashing\ReviewSquawkBundle\Form\Type\GithubProjectType;
use Whitewashing\ReviewSquawkBundle\Entity\Project;

class GithubProjectController extends Controller
{
    /**
     * @Route("/projects/github/create", name="rs_github_project_create")
     * @Template()
     */
    public function createAction()
    {
        $project = new Project();
        $projectForm = $this->createForm(new GithubProjectType(), $project);

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $projectForm->bindRequest($request);

            if ($projectForm->isValid()) {
                $project->setUser($this->container->get('security.context')->getToken()->getUser());
                $em = $this->container->get('doctrine.orm.default_entity_manager');
                $em->persist($project);
                $em->flush();

                return $this->redirect($this->generateUrl('rs_github_user_dashboard'));
            }
        }

        return array('projectForm' => $projectForm->createView());
    }

    /**
     * @Route("/projects/{id}", name="rs_github_project_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $project = $em->find('Whitewashing\ReviewSquawkBundle\Entity\Project', $id);

        if (!$project) {
            throw $this->createNotFoundException('No project found with this id.');
        }

        $api = $this->container->get('whitewashing.review_squawk.github_client');
        $commits = $api->getCommits($project->getRepositoryUrl());

        return array('project' => $project, 'commits' => $commits);
    }
}