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

namespace Whitewashing\ReviewSquawkBundle\Tests\Validator;

class GithubProjectValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $client;
    private $validator;
    private $constraint;

    public function setUp()
    {
        $this->client = $this->getMock('Whitewashing\ReviewSquawkBundle\Model\Github\ClientAPI');
        $this->validator = new \Whitewashing\ReviewSquawkBundle\Validator\GithubProjectValidator($this->client);
        $this->constraint = new \Whitewashing\ReviewSquawkBundle\Validator\GithubProject();
    }

    public function testValidProject()
    {
        $repUrl = 'https://github.com/beberlei/githubpr_to_jira';

        $this->client->expects($this->once())
                     ->method('getProject')
                     ->with($this->equalTo($repUrl))
                     ->will($this->returnValue(array('html_url' => $repUrl, 'fork' => false, 'private' => false)));

        $this->assertTrue($this->validator->isValid($repUrl, $this->constraint));
    }

    public function testPrivate()
    {
        $repUrl = 'https://github.com/beberlei/githubpr_to_jira';

        $this->client->expects($this->once())
                     ->method('getProject')
                     ->with($this->equalTo($repUrl))
                     ->will($this->returnValue(array('html_url' => $repUrl, 'fork' => false, 'private' => true)));

        $this->assertFalse($this->validator->isValid($repUrl, $this->constraint));
    }

    public function testFork()
    {
        $repUrl = 'https://github.com/beberlei/githubpr_to_jira';

        $this->client->expects($this->once())
                     ->method('getProject')
                     ->with($this->equalTo($repUrl))
                     ->will($this->returnValue(array('html_url' => $repUrl, 'fork' => true, 'private' => false)));

        $this->assertFalse($this->validator->isValid($repUrl, $this->constraint));
    }

    public function testInvalidValue()
    {
        $this->assertFalse($this->validator->isValid("Foo", $this->constraint));
    }

    public function testInvalidGithubUrl()
    {
        $this->assertFalse($this->validator->isValid("https://www.beberlei.de", $this->constraint));
    }

    public function testInvalidUrlThroughClient()
    {
        $repUrl = 'https://github.com/beberlei/githubpr_to_jira';

        $this->client->expects($this->once())
                     ->method('getProject')
                     ->with($this->equalTo($repUrl))
                     ->will($this->throwException(new \RuntimeException("Invalid Request!")));

        $this->assertFalse($this->validator->isValid($repUrl, $this->constraint));
    }
}