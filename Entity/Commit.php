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

/**
 * @ORM\Entity
 * @ORM\Table(name="commits")
 */
class Commit
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @var string
     */
    protected $revision;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="commits")
     * @var Project
     */
    protected $project;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User")
     * @var User
     */
    protected $reviewer;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    protected $reviewDate;

    /**
     * @param string $revision
     * @param Project $project
     * @param User $reviewer
     */
    public function __construct($revision, $project, $reviewer)
    {
        $this->revision = $revision;
        $this->project = $project;
        $this->reviewer = $reviewer;
        $this->reviewDate = new \DateTime("now");
    }
    
    public function getRevision()
    {
        return $this->revision;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getReviewer()
    {
        return $this->reviewer;
    }

    public function getReviewDate()
    {
        return $this->reviewDate;
    }
}