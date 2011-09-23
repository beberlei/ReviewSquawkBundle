<?php

namespace Whitewashing\ReviewSquawkBundle\Tests\Model;

use Whitewashing\ReviewSquawkBundle\Model\Diff;
use Whitewashing\ReviewSquawkBundle\Model\CodeSnifferService;
use Whitewashing\ReviewSquawkBundle\Model\Project;

class CodeSnifferServiceTest extends \PHPUnit_Framework_TestCase
{
    private $service;

    public function setUp()
    {
        $this->service = new CodeSnifferService();
        $this->project = new Project("http", "asdf", "Zend", true);
    }

    public function testScanSimpleFilesNoViolation()
    {
        $diff = new Diff("/foo.php", "<?php\nphpinfo();", "<?php\necho 'Hello World';");
        $violations = $this->service->scan($this->project, $diff);

        $this->assertEquals(0, count($violations));
    }

    public function testExistingViolationsIgnored()
    {
        $longstring = '$a = "' . str_repeat("a", 140) . '";';

        $diff = new Diff("/foo.php", "<?php\n" . $longstring, "<?php\n" . $longstring);
        $violations = $this->service->scan($this->project, $diff);

        $this->assertEquals(0, count($violations));
    }

    public function testNewViolationsEmitted()
    {
        $longstring = '$a = "' . str_repeat("a", 140) . '";' . "\n";
        
        $diff = new Diff("/foo.php", "<?php\nphpinfo();", "<?php\n" . $longstring);
        $violations = $this->service->scan($this->project, $diff);

        $this->assertEquals(1, count($violations));
        $this->assertContainsOnly('Whitewashing\ReviewSquawkBundle\Model\Violation', $violations);
        $this->assertEquals('Generic.Files.LineLength.MaxExceeded: Line exceeds maximum limit of 100 characters; contains 149 characters', $violations[0]->getMessage());
    }

    public function testChangedViolationsEmitted()
    {
        $longstring = '$a = "' . str_repeat("a", 140) . '";' . "\n";
        $longstringNew = '$a = "' . str_repeat("a", 142) . '";' . "\n";

        $diff = new Diff("/foo.php", "<?php\n" . $longstring, "<?php\n" . $longstringNew);
        $violations = $this->service->scan($this->project, $diff);

        $this->assertEquals(1, count($violations));
        $this->assertContainsOnly('Whitewashing\ReviewSquawkBundle\Model\Violation', $violations);
        $this->assertEquals('Generic.Files.LineLength.MaxExceeded: Line exceeds maximum limit of 100 characters; contains 151 characters', $violations[0]->getMessage());
    }
}