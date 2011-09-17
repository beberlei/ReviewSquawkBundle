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

class Project
{
    private $repositoryUrl;

    private $accessToken;

    function __construct($repositoryUrl, $accessToken)
    {
        $this->repositoryUrl = $repositoryUrl;
        $this->accessToken = $accessToken;
    }

    public function getRepositoryUrl()
    {
        return $this->repositoryUrl;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }
}