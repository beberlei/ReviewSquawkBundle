<?php

namespace Whitewashing\ReviewSquawkBundle\Tests\Controller;

class ReviewControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGithubPostRecieve()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $project = new \Whitewashing\ReviewSquawkBundle\Entity\Project();
        $project->generateToken();
        $ro = new \ReflectionObject($project);
        $rp = $ro->getProperty('id');
        $rp->setAccessible(true);
        $rp->setValue($project, 1);

        $or = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $or->expects($this->once())->method('findOneBy')->will($this->returnValue($project));

        $om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $om->expects($this->once())->method('getRepository')->will($this->returnValue($or));

        $payload = json_encode(array('commits' => array()));

        $request = new \Symfony\Component\HttpFoundation\Request(array('token' => $project->getToken()), array('payload' => $payload));

        $container->expects($this->at(0))->method('get')->with($this->equalTo('request'))->will($this->returnValue($request));
        $container->expects($this->at(1))->method('get')->with($this->equalTo('doctrine.orm.default_entity_manager'))->will($this->returnValue($om));

        $controller = new \Whitewashing\ReviewSquawkBundle\Controller\ReviewController();
        $controller->setContainer($container);
        $response = $controller->githubCommitsAction(1);

        $this->assertEquals('{"ok":true}', $response->getContent());
    }
}