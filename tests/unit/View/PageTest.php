<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\View;

use PHPUnit\Framework\TestCase;
use Gibbon\Domain\System\Theme;
use Gibbon\Domain\System\Module;

/**
 * @covers Page
 */
class PageTest extends TestCase
{
    protected $mockModule;
    protected $mockTheme;

    public function setUp(): void
    {
        $this->prebuiltPage = new Page(null, [
            'module' => new Module(),
            'theme'  => new Theme(),
        ]);
    }

    public function testCanConstructFromParams()
    {
        $params = ['title' => 'Foo Bar', 'address' => 'fiz/buzz'];
        $page = new Page(null, $params);

        $this->assertEquals('Foo Bar', $page->getTitle());
        $this->assertEquals('fiz/buzz', $page->getAddress());
    }

    public function testAddsErrors()
    {
        $page = new Page();
        $page->addError('This is an error!');
     
        $this->assertContains('This is an error!', $page->getAlerts('error'));
    }

    public function testAddsWarnings()
    {
        $page = new Page();
        $page->addWarning('This is a warning?');
     
        $this->assertContains('This is a warning?', $page->getAlerts('warning'));
    }

    public function testAddsMessages()
    {
        $page = new Page();
        $page->addMessage('This is (maybe) a message.');
     
        $this->assertContains('This is (maybe) a message.', $page->getAlerts('message'));
    }

    public function testGetsAlerts()
    {
        $page = new Page();
        $page->addError('This is an error!');
        $page->addError('This is an error!!');
        $page->addError('This is an error!!!');
        
        $this->assertCount(3, $page->getAlerts('error'));

        $page->addWarning('This is a warning?');
     
        $this->assertCount(1, $page->getAlerts('warning'));
    }

    public function testAddsHeadExtra()
    {
        $page = new Page();
        $page->addHeadExtra('<style></style>');
     
        $this->assertContains('<style></style>', $page->getExtraCode('head'));
    }

    public function testAddsFootExtra()
    {
        $page = new Page();
        $page->addFootExtra('<script></script>');
     
        $this->assertContains('<script></script>', $page->getExtraCode('foot'));
    }

    public function testAddsSidebarExtra()
    {
        $page = new Page();
        $page->addSidebarExtra('<div></div>');
     
        $this->assertContains('<div></div>', $page->getExtraCode('sidebar'));
    }

    public function testCanAddStylesheets()
    {
        $page = new Page();
        $page->stylesheets->add('foo', 'bar/baz');

        $this->assertArrayHasKey('foo', $page->getAllStylesheets());
    }

    public function testCanGetAllStylesheets()
    {
        $page = $this->prebuiltPage;

        $page->stylesheets->add('foo', 'bar/baz');
        $page->getTheme()->stylesheets->add('buz', 'bar/baz');
        $page->getModule()->stylesheets->add('fiz', 'bar/baz');

        $this->assertArrayHasKey('foo', $page->getAllStylesheets());
        $this->assertArrayHasKey('buz', $page->getAllStylesheets());
        $this->assertArrayHasKey('fiz', $page->getAllStylesheets());
    }

    public function testCanAddScripts()
    {
        $page = new Page();
        $page->scripts->add('fiz', 'bar/baz', ['context' => 'head']);

        $this->assertArrayHasKey('fiz', $page->getAllScripts('head'));
    }

    public function testCanGetAllScripts()
    {
        $page = $this->prebuiltPage;

        $page->scripts->add('foo', 'bar/baz', ['context' => 'head']);
        $page->getModule()->scripts->add('fiz', 'bar/baz', ['context' => 'foot']);
        $page->getTheme()->scripts->add('buz', 'bar/baz', ['context' => 'head']);

        $this->assertArrayHasKey('foo', $page->getAllScripts('head'));
        $this->assertArrayHasKey('buz', $page->getAllScripts('head'));
    }
}
