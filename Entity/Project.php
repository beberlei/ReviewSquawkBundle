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

namespace Whitewashing\ReviewSquawkBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Whitewashing\ReviewSquawkBundle\Validator as AssertSquawk;
use Symfony\Bridge\Doctrine\Validator\Constraints as AssertDoctrine;

/**
 * @ORM\Entity
 * @ORM\Table(name="projects")
 * @AssertDoctrine\UniqueEntity(fields="repositoryUrl", message="This github project was already created either by you or somebody else. A project can only be reviewed once.")
 */
class Project
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var string
     */
    protected $id;
    /**
     * @Assert\NotBlank(groups={"Github"})
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;
    /**
     * @Assert\NotBlank(groups={"Github"})
     * @AssertSquawk\GithubProject(groups={"Github"})
     * @ORM\Column(type="string", unique=true, name="repository_url")
     * @var string
     */
    protected $repositoryUrl;
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="projects")
     * @var User
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="Commit", mappedBy="project", indexBy="revision")
     * @var Commit[]
     */
    protected $commits;

    /**
     * @ORM\Column(type="string", name="coding_standard")
     * @Assert\Choice(message="No valid coding standard selected", callback={"Whitewashing\ReviewSquawkBundle\Model\CodeSnifferService", "getInstalledStandards"})
     * @return string
     */
    protected $codingStandard = 'Zend';

    /**
     * @ORM\Column(type="string", name="show_warnings")
     * @return bool
     */
    protected $showWarnings = true;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getRepositoryUrl()
    {
        return $this->repositoryUrl;
    }

    /**
     * @param string $repositoryUrl
     */
    public function setRepositoryUrl($repositoryUrl)
    {
        // TODO: Remove github specifices when necessary
        $repositoryUrl = rtrim($repositoryUrl, "/");
        if (strpos($repositoryUrl, "://github.com") !== false) {
            if (substr($repositoryUrl, -4) == ".git") {
                $repositoryUrl = substr($repositoryUrl, 0, -4);
            }
            if (strpos($repositoryUrl, "git://") === 0) {
                $repositoryUrl = str_replace("git://", "https://", $repositoryUrl);
            }
            $parts = explode("/", $repositoryUrl);
            $this->name = array_pop($parts);
        }

        $this->repositoryUrl = $repositoryUrl;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getCodingStandard()
    {
        return $this->codingStandard;
    }

    public function setCodingStandard($codingStandard)
    {
        $this->codingStandard = $codingStandard;
    }

    public function getShowWarnings()
    {
        return $this->showWarnings;
    }

    public function setShowWarnings($showWarnings)
    {
        $this->showWarnings = (bool)$showWarnings;
    }

    /**
     * @return \Whitewashing\ReviewSquawkBundle\Model\Project
     */
    public function toProjectStruct()
    {
        return new \Whitewashing\ReviewSquawkBundle\Model\Project(
            $this->repositoryUrl, $this->user->getAccessToken(), $this->codingStandard, $this->showWarnings
        );
    }

    public function hasCommit($revision)
    {
        return isset($this->commits[$revision]);
    }
}