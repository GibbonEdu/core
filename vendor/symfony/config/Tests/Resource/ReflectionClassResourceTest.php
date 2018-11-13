<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Config\Tests\Resource;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\ReflectionClassResource;

class ReflectionClassResourceTest extends TestCase
{
    public function testToString()
    {
        $res = new ReflectionClassResource(new \ReflectionClass('ErrorException'));

        $this->assertSame('reflection.ErrorException', (string) $res);
    }

    public function testSerializeUnserialize()
    {
        $res = new ReflectionClassResource(new \ReflectionClass(DummyInterface::class));
        $ser = unserialize(serialize($res));

        $this->assertTrue($res->isFresh(0));
        $this->assertTrue($ser->isFresh(0));

        $this->assertSame((string) $res, (string) $ser);
    }

    public function testIsFresh()
    {
        $res = new ReflectionClassResource(new \ReflectionClass(__CLASS__));
        $mtime = filemtime(__FILE__);

        $this->assertTrue($res->isFresh($mtime), '->isFresh() returns true if the resource has not changed in same second');
        $this->assertTrue($res->isFresh($mtime + 10), '->isFresh() returns true if the resource has not changed');
        $this->assertTrue($res->isFresh($mtime - 86400), '->isFresh() returns true if the resource has not changed');
    }

    public function testIsFreshForDeletedResources()
    {
        $now = time();
        $tmp = sys_get_temp_dir().'/tmp.php';
        file_put_contents($tmp, '<?php class ReflectionClassResourceTestClass {}');
        require $tmp;

        $res = new ReflectionClassResource(new \ReflectionClass('ReflectionClassResourceTestClass'));
        $this->assertTrue($res->isFresh($now));

        unlink($tmp);
        $this->assertFalse($res->isFresh($now), '->isFresh() returns false if the resource does not exist');
    }

    /**
     * @dataProvider provideHashedSignature
     */
    public function testHashedSignature($changeExpected, $changedLine, $changedCode)
    {
        $code = <<<'EOPHP'
/* 0*/
/* 1*/  class %s extends ErrorException
/* 2*/  {
/* 3*/      const FOO = 123;
/* 4*/
/* 5*/      public $pub = array();
/* 6*/
/* 7*/      protected $prot;
/* 8*/
/* 9*/      private $priv;
/*10*/
/*11*/      public function pub($arg = null) {}
/*12*/
/*13*/      protected function prot($a = array()) {}
/*14*/
/*15*/      private function priv() {}
/*16*/  }
EOPHP;

        static $expectedSignature, $generateSignature;

        if (null === $expectedSignature) {
            eval(sprintf($code, $class = 'Foo'.str_replace('.', '_', uniqid('', true))));
            $r = new \ReflectionClass(ReflectionClassResource::class);
            $generateSignature = $r->getMethod('generateSignature');
            $generateSignature->setAccessible(true);
            $generateSignature = $generateSignature->getClosure($r->newInstanceWithoutConstructor());
            $expectedSignature = implode("\n", iterator_to_array($generateSignature(new \ReflectionClass($class))));
        }

        $code = explode("\n", $code);
        $code[$changedLine] = $changedCode;
        eval(sprintf(implode("\n", $code), $class = 'Foo'.str_replace('.', '_', uniqid('', true))));
        $signature = implode("\n", iterator_to_array($generateSignature(new \ReflectionClass($class))));

        if ($changeExpected) {
            $this->assertTrue($expectedSignature !== $signature);
        } else {
            $this->assertSame($expectedSignature, $signature);
        }
    }

    public function provideHashedSignature()
    {
        yield array(0, 0, "// line change\n\n");
        yield array(1, 0, '/** class docblock */');
        yield array(1, 1, 'abstract class %s');
        yield array(1, 1, 'final class %s');
        yield array(1, 1, 'class %s extends Exception');
        yield array(1, 1, 'class %s implements '.DummyInterface::class);
        yield array(1, 3, 'const FOO = 456;');
        yield array(1, 3, 'const BAR = 123;');
        yield array(1, 4, '/** pub docblock */');
        yield array(1, 5, 'protected $pub = array();');
        yield array(1, 5, 'public $pub = array(123);');
        yield array(1, 6, '/** prot docblock */');
        yield array(1, 7, 'private $prot;');
        yield array(0, 8, '/** priv docblock */');
        yield array(0, 9, 'private $priv = 123;');
        yield array(1, 10, '/** pub docblock */');
        if (\PHP_VERSION_ID >= 50600) {
            yield array(1, 11, 'public function pub(...$arg) {}');
        }
        if (\PHP_VERSION_ID >= 70000) {
            yield array(1, 11, 'public function pub($arg = null): Foo {}');
        }
        yield array(0, 11, "public function pub(\$arg = null) {\nreturn 123;\n}");
        yield array(1, 12, '/** prot docblock */');
        yield array(1, 13, 'protected function prot($a = array(123)) {}');
        yield array(0, 14, '/** priv docblock */');
        yield array(0, 15, '');
    }
}

interface DummyInterface
{
}
