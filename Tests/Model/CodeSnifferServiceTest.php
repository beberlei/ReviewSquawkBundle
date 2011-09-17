<?php

namespace Whitewashing\ReviewSquawkBundle\Tests\Model;

use Whitewashing\ReviewSquawkBundle\Model\Diff;
use Whitewashing\ReviewSquawkBundle\Model\CodeSnifferService;

class CodeSnifferServiceTest extends \PHPUnit_Framework_TestCase
{
    private $service;

    public function setUp()
    {
        $this->service = new CodeSnifferService('Zend');
    }

    public function testScanSimpleFilesNoViolation()
    {
        $diff = new Diff("/foo.php", "<?php\nphpinfo();", "<?php\necho 'Hello World';");
        $violations = $this->service->scan($diff);

        $this->assertEquals(0, count($violations));
    }

    public function testExistingViolationsIgnored()
    {
        $longstring = '$a = "' . str_repeat("a", 140) . '";';

        $diff = new Diff("/foo.php", "<?php\n" . $longstring, "<?php\n" . $longstring);
        $violations = $this->service->scan($diff);

        $this->assertEquals(0, count($violations));
    }

    public function testNewViolationsEmitted()
    {
        $longstring = '$a = "' . str_repeat("a", 140) . '";' . "\n";
        
        $diff = new Diff("/foo.php", "<?php\nphpinfo();", "<?php\n" . $longstring);
        $violations = $this->service->scan($diff);

        $this->assertEquals(1, count($violations));
        $this->assertContainsOnly('Whitewashing\ReviewSquawkBundle\Model\Violation', $violations);
        $this->assertEquals('Zend.Files.LineLengthSniff: Line exceeds maximum limit of 120 characters; contains 149 characters', $violations[0]->getMessage());
    }

    public function testChangedViolationsEmitted()
    {
        $longstring = '$a = "' . str_repeat("a", 140) . '";' . "\n";
        $longstringNew = '$a = "' . str_repeat("a", 142) . '";' . "\n";

        $diff = new Diff("/foo.php", "<?php\n" . $longstring, "<?php\n" . $longstringNew);
        $violations = $this->service->scan($diff);

        $this->assertEquals(1, count($violations));
        $this->assertContainsOnly('Whitewashing\ReviewSquawkBundle\Model\Violation', $violations);
        $this->assertEquals('Zend.Files.LineLengthSniff: Line exceeds maximum limit of 120 characters; contains 151 characters', $violations[0]->getMessage());
    }
}