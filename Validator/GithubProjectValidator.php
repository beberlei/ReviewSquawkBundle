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

namespace Whitewashing\ReviewSquawkBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Whitewashing\ReviewSquawkBundle\Entity\Project;
use Whitewashing\ReviewSquawkBundle\Model\Github\ClientAPI;

class GithubProjectValidator extends ConstraintValidator
{
    /**
     * @var ClientAPI
     */
    private $clientApi;

    /**
     * @param ClientAPI $clientApi
     */
    function __construct(ClientAPI $clientApi)
    {
        $this->clientApi = $clientApi;
    }

    /**
     * @param string $repositoryUrl
     * @param Constraint $constraint
     */
    public function isValid($repositoryUrl, Constraint $constraint)
    {
        if (!is_string($repositoryUrl)) {
            $this->setMessage($constraint->message);
            return false;
        }

        if (strpos($repositoryUrl, "https://github.com") !== 0) {
            $this->setMessage($constraint->message);
            return false;
        }
        $parts = parse_url($repositoryUrl);

        if (substr_count($parts['path'], "/") != 2) {
            $this->setMessage($constraint->message);
            return false;
        }

        try {
            $projectDetails = $this->clientApi->getProject($repositoryUrl);
            if (strtolower($projectDetails['html_url']) != strtolower($repositoryUrl)) {
                $this->setMessage($constraint->message);

                return false;
            }

            if ($projectDetails['fork']) {
                $this->setMessage($constraint->forkMessage);

                return false;
            }
            if ($projectDetails['private']) {
                $this->setMessage($constraint->privateMessage);

                return false;
            }
        } catch(\RuntimeException $e) {
            $this->setMessage($constraint->message);
            return false;
        }

        return true;
    }
}