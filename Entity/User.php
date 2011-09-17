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
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $accessToken;

    /**
     * @ORM\OneToMany(targetEntity="Project", mappedBy="user")
     * @var Project[]
     */
    protected $projects;

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->name;
    }

    public function setUsername($name)
    {
        $this->name = $name;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function equals(UserInterface $user)
    {
        return $this->getUsername() == $user->getUsername();
    }

    public function eraseCredentials()
    {
        $this->accessToken = null;
    }

    public function getPassword()
    {
        return null;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function getSalt()
    {
        return "";
    }

    public function getProjects()
    {
        return $this->projects;
    }
}