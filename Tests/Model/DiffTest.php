<?php

namespace Whitewashing\ReviewSquawkBundle\Tests\Model;

use Whitewashing\ReviewSquawkBundle\Model\Diff;

class DiffTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPosition()
    {
        $diff = <<<'DIFF'
diff --git a/tests/Doctrine/Tests/ORM/Functional/QueryTest.php b/tests/Doctrine/Tests/ORM/Functional/QueryTest.php
--- a/tests/Doctrine/Tests/ORM/Functional/QueryTest.php
+++ b/tests/Doctrine/Tests/ORM/Functional/QueryTest.php
@@ -501,4 +501,36 @@ class QueryTest extends \Doctrine\Tests\OrmFunctionalTestCase

         $this->assertEquals(0, count($users));
     }
+
+    public function testQueryWithArrayOfEntitiesAsParameter()
+    {
+        $userA = new CmsUser;
+        $userA->name = 'Benjamin';
+        $userA->username = 'beberlei';
+        $userA->status = 'developer';
+        $this->_em->persist($userA);
+
+        $userB = new CmsUser;
+        $userB->name = 'Roman';
+        $userB->username = 'romanb';
+        $userB->status = 'developer';
+        $this->_em->persist($userB);
+
+        $userC = new CmsUser;
+        $userC->name = 'Jonathan';
+        $userC->username = 'jwage';
+        $userC->status = 'developer';
+        $this->_em->persist($userC);
+
+        $this->_em->flush();
+        $this->_em->clear();
+
+        $query = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u IN (?0) OR u.username = ?1');
+        $query->setParameter(0, array($userA, $userC));
+        $query->setParameter(1, 'beberlei');
+
+        $users = $query->execute();
+
+        $this->assertEquals(2, count($users));
+    }
 }
DIFF;

        $diff = new Diff("foo.txt", "", "", $diff);

        $this->assertEquals(20, $diff->getDiffPositionForLine(520));
    }
}