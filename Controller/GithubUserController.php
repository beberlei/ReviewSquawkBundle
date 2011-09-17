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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class GithubUserController extends Controller
{
    /**
     * @Route("/dashboard", name="rs_github_user_dashboard")
     * @Template()
     */
    public function indexAction()
    {
        if (!$this->container->get('security.context')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('rs_github_user_create'));
        }

        return array();
    }

    /**
     * @Route("/user/create", name="rs_github_user_create")
     * @Template()
     */
    public function createAction()
    {
        return array();
    }

    /**
     * @Route("/user/github/authorize", name="rs_github_user_authorize")
     */
    public function authorizeAction()
    {
        $clientId = $this->container->getParameter('whitewashing.review_squawk.github.client_id');
        $redirectUrl = urlencode($this->generateUrl('rs_github_user_authorize_register'));
        return $this->redirect("https://github.com/login/oauth/authorize?client_id=" . $clientId . "&redirect_url=" . $redirectUrl."&scope=public_repo");
    }

    /**
     * @Route("/user/github/register", name="rs_github_user_authorize_register")
     */
    public function registerAction()
    {
        $code = $this->getRequest()->query->get('code');

        $clientId = $this->container->getParameter('whitewashing.review_squawk.github.client_id');
        $clientSecret = $this->container->getParameter('whitewashing.review_squawk.github.client_secret');
        $service = new \Whitewashing\ReviewSquawkBundle\Model\Github\RestV3API($clientId, $clientSecret);
        $accessToken = $service->claimOAuthAccessToken($code);

        $userDetails = $service->getCurrentUser($accessToken);

        $em = $this->container->get('doctrine.orm.default_entity_manager');

        $userRepository = $em->getRepository('Whitewashing\ReviewSquawkBundle\Entity\User');
        $user = $userRepository->findOneBy(array('name' => $userDetails['login']));

        if (!$user) {
            $user = new \Whitewashing\ReviewSquawkBundle\Entity\User();
            $user->setAccessToken($accessToken);
            $user->setUsername($userDetails['login']);

            $em->persist($user);
            $em->flush();

        }
        $this->authenticateUser($user);

        return $this->redirect($this->generateUrl('rs_github_user_dashboard'));
    }

    protected function authenticateUser($user)
    {
        $token = new UsernamePasswordToken($user, null, 'squawkd', $user->getRoles());
        $this->container->get('security.context')->setToken($token);
    }

}