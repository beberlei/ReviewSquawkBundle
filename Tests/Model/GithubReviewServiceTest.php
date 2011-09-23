<?php

namespace Whitewashing\ReviewSquawkBundle\Tests\Model;

use Whitewashing\ReviewSquawkBundle\Model\GithubReviewService;
use Whitewashing\ReviewSquawkBundle\Model\Project;
use Whitewashing\ReviewSquawkBundle\Model\Violation;

class GithubReviewServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GithubReviewService
     */
    private $reviewService;
    private $client;
    private $csService;

    public function setUp()
    {
        $this->client = $this->getMock('Whitewashing\ReviewSquawkBundle\Model\Github\ClientAPI');
        $this->csService = $this->getMock('Whitewashing\ReviewSquawkBundle\Model\CodeSnifferService', array('scan'));
        $this->reviewService = new GithubReviewService($this->client, $this->csService);
    }

    private function clientExpectsGetCommitDiffs($project, $commit, $returnDiffs)
    {
        $this->client->expects($this->once())
                     ->method('getCommitDiffs')
                     ->with($this->equalTo($project->getRepositoryUrl()), $this->equalTo($commit))
                     ->will($this->returnValue( $returnDiffs ));
    }

    public function testNoDiffsDoNothing()
    {
        $project = new Project("http://github.com/beberlei/ReviewSquawkBundle", "asdf", "Zend", false);
        $commit = "1234";

        $this->clientExpectsGetCommitDiffs($project, $commit, array());
        $this->client->expects($this->never())->method('commentCommit');
        $this->csService->expects($this->never())->method('scan');

        $this->reviewService->reviewCommit($project, $commit);
    }

    private function csServiceExpectsScanAt($pos, $project, $diff, $returnViolations)
    {
        $this->csService
                ->expects($this->at($pos))
                ->method('scan')
                ->with($this->equalTo($project), $this->equalTo($diff))
                ->will($this->returnValue($returnViolations));
    }

    public function testDiffNoViolationDoNothing()
    {
        $project = new Project("http://github.com/beberlei/ReviewSquawkBundle", "asdf", "Zend", false);
        $commit = "1234";
        $diff = $this->createDiff();

        $this->clientExpectsGetCommitDiffs($project, $commit, array($diff));
        $this->client->expects($this->never())->method('commentCommit');
        $this->csServiceExpectsScanAt(0, $project, $diff, array());

        $this->reviewService->reviewCommit($project, $commit);
    }

    public function testDiffOneViolationOneComment()
    {
        $project = new Project("http://github.com/beberlei/ReviewSquawkBundle", "asdf", "Zend", false);
        $violation = new Violation("foo.txt", 1, "violation yadda");
        $commit = "1234";
        $diff = $this->createDiff();

        $this->clientExpectsGetCommitDiffs($project, $commit, array($diff));
        $this->csServiceExpectsScanAt(0, $project, $diff, array($violation));
        $this->clientExpectsCommentAt(1, $project, $commit, "foo.txt", 1, 1, "violation yadda");

        $this->reviewService->reviewCommit($project, $commit);
    }

    public function testDiffTwoViolationsSameLineOneComments()
    {
        $project = new Project("http://github.com/beberlei/ReviewSquawkBundle", "asdf", "Zend", false);
        $violation1 = new Violation("foo.txt", 1, "violation yadda");
        $violation2 = new Violation("foo.txt", 1, "violation omnom");
        $commit = "1234";
        $diff = $this->createDiff();

        $this->clientExpectsGetCommitDiffs($project, $commit, array($diff));
        $this->csServiceExpectsScanAt(0, $project, $diff, array($violation1, $violation2));
        $this->clientExpectsCommentAt(1, $project, $commit, "foo.txt", 1, 1, "violation yadda\nviolation omnom");

        $this->reviewService->reviewCommit($project, $commit);
    }

    public function testTwoDiffsTwoViolationsTwoCommments()
    {
        $project = new Project("http://github.com/beberlei/ReviewSquawkBundle", "asdf", "Zend", false);
        $violation1 = new Violation("foo.txt", 1, "violation yadda");
        $violation2 = new Violation("bar.txt", 1, "violation omnom");
        $commit = "1234";
        $diff1 = $this->createDiff("foo.txt");
        $diff2 = $this->createDiff("bar.txt");

        $this->clientExpectsGetCommitDiffs($project, $commit, array($diff1, $diff2));
        $this->csServiceExpectsScanAt(0, $project, $diff1, array($violation1));
        $this->csServiceExpectsScanAt(1, $project, $diff2, array($violation2));
        $this->clientExpectsCommentAt(1, $project, $commit, "foo.txt", 1, 1, "violation yadda");
        $this->clientExpectsCommentAt(2, $project, $commit, "bar.txt", 1, 1, "violation omnom");

        $this->reviewService->reviewCommit($project, $commit);
    }

    private function clientExpectsCommentAt($pos, $project, $commitId, $path, $line, $position, $comment)
    {
        $this->client->expects($this->at($pos))
            ->method('commentCommit')
            ->with(
                    $this->equalTo($project->getAccessToken()),
                    $this->equalTo($project->getRepositoryUrl()),
                    $this->equalTo($commitId),
                    $this->equalTo($path),
                    $this->equalTo($line),
                    $this->equalTo($position),
                    $this->equalTo($comment)
                );
    }

    public function createDiff($file = "foo.txt")
    {
        return new \Whitewashing\ReviewSquawkBundle\Model\Diff($file, "foo", "bar", "diff --git a/$file b/$file
--- a/$file
+++ b/$file
@@ -1,1 +1,1 @@
-foo
+bar
");
    }
}