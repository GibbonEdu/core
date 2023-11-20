<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon;

use Gibbon\Http\Url;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Http\Url class
 */
class UrlTest extends TestCase
{
    public function setUp(): void
    {
        Url::setBaseUrl('http://foobar.com/some/path/');
    }

    public function tearDown(): void
    {
        Url::setBaseUrl('');
    }

    /**
     * @covers \Gibbon\Http\Url::fromRoute()
     */
    public function testFromRoute()
    {
        $url = Url::fromRoute('some_action');
        $this->assertEquals('/some/path/index.php?q=some_action.php', (string) $url);

        $url = Url::fromRoute()->withQuery('hello=world');
        $this->assertEquals('/some/path/index.php?hello=world', (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::fromAbsoluteRoute()
     */
    public function testFromAbsoluteRoute()
    {
        $url = Url::fromRoute('some_action')->withAbsoluteUrl();
        $this->assertEquals('http://foobar.com/some/path/index.php?q=some_action.php', (string) $url);

        $url = Url::fromRoute()->withAbsoluteUrl()->withQuery('hello=world');
        $this->assertEquals('http://foobar.com/some/path/index.php?hello=world', (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::fromRoute()
     * @covers \Gibbon\Http\Url::withQueryParams()
     */
    public function testFromRouteWithQueryParams()
    {
        $url = Url::fromRoute('some_action')->withQueryParams([
            'q' => 'mistaken/override',
            'foo' => 'bar',
        ]);
        $this->assertSame(static::parseUrlQuery((string) $url), [
            'q' => 'some_action.php',
            'foo' => 'bar',
        ], 'Explicit "q" parameter should be ignored if URL has a route path.');

        $url2 = $url->withQueryParams([
            'hello' => 'there',
        ]);
        $this->assertSame(static::parseUrlQuery((string) $url2), [
            'q' => 'some_action.php',
            'hello' => 'there',
        ], 'The query params from the old url should not be carried over to the new one');
    }

    /**
     * @covers \Gibbon\Http\Url::fromRoute()
     * @covers \Gibbon\Http\Url::withQuery()
     */
    public function testFromRouteWithQuery()
    {
        $url = Url::fromRoute('some_action')->withQuery('q=mistkane/override&foo=bar');
        $this->assertSame(static::parseUrlQuery((string) $url), [
            'q' => 'some_action.php',
            'foo' => 'bar',
        ], 'Explicit "q" parameter should be ignored if URL has a route path.');

        $url2 = $url->withQuery('hello=there');
        $this->assertSame(static::parseUrlQuery((string) $url2), [
            'q' => 'some_action.php',
            'hello' => 'there',
        ], 'The query params from the old url should not be carried over to the new one');
    }

    /**
     * @covers \Gibbon\Http\Url::fromModuleRoute()
     */
    public function testFromModuleRoute()
    {
        $url = Url::fromModuleRoute('My Module', 'some_action');
        $this->assertEquals('/some/path/index.php?' . http_build_query([
            'q' => '/modules/My Module/some_action.php',
        ]), (string) $url);

        $url = Url::fromModuleRoute('My Module');
        $this->assertEquals('/some/path/index.php?' . http_build_query([
            'q' => '/modules/My Module/',
        ]), (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::fromModuleRoute()
     */
    public function testFromAbsoluteModuleRoute()
    {
        $url = Url::fromModuleRoute('My Module', 'some_action')->withAbsoluteUrl();
        $this->assertEquals('http://foobar.com/some/path/index.php?' . http_build_query([
            'q' => '/modules/My Module/some_action.php',
        ]), (string) $url);

        $url = Url::fromModuleRoute('My Module')->withAbsoluteUrl();
        $this->assertEquals('http://foobar.com/some/path/index.php?' . http_build_query([
            'q' => '/modules/My Module/',
        ]), (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::fromModuleRoute()
     * @covers \Gibbon\Http\Url::withQueryParams()
     */
    public function testFromModuleRouteWithQueryParams()
    {
        $url = Url::fromModuleRoute('My Module', 'some_action')->withQueryParams([
            'q' => 'mistaken/override',
            'foo' => 'bar',
        ]);
        $this->assertSame(static::parseUrlQuery((string) $url), [
            'q' => '/modules/My Module/some_action.php',
            'foo' => 'bar',
        ], 'Explicit "q" parameter should be ignored if URL has a module + route path.');

        $url2 = $url->withQueryParams([
            'hello' => 'there',
        ]);
        $this->assertSame(static::parseUrlQuery((string) $url2), [
            'q' => '/modules/My Module/some_action.php',
            'hello' => 'there',
        ], 'The query params from the old url should not be carried over to the new one');
    }

    /**
     * @covers \Gibbon\Http\Url::fromModuleRoute()
     * @covers \Gibbon\Http\Url::withQuery()
     */
    public function testFromModuleRouteWithQuery()
    {
        $url = Url::fromModuleRoute('My Module', 'some_action')->withQuery('q=mistaken/override&foo=bar');
        $this->assertSame(static::parseUrlQuery((string) $url), [
            'q' => '/modules/My Module/some_action.php',
            'foo' => 'bar',
        ], 'Explicit "q" parameter should be ignored if URL has a module + route path.');

        $url2 = $url->withQuery('hello=there');
        $this->assertSame(static::parseUrlQuery((string) $url2), [
            'q' => '/modules/My Module/some_action.php',
            'hello' => 'there',
        ], 'The query params from the old url should not be carried over to the new one');
    }

    /**
     * @covers \Gibbon\Http\Url::fromHandlerPath()
     */
    public function testFromHandlerPath()
    {
        $url = Url::fromHandlerRoute('fullscreen.php');
        $this->assertEquals('/some/path/fullscreen.php', (string) $url);

        $url = Url::fromHandlerRoute('fullscreen.php', 'some_action');
        $this->assertEquals('/some/path/fullscreen.php?q=some_action.php', (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::fromHandlerPath()
     */
    public function testFromAbsoluteHandlerPath()
    {
        $url = Url::fromHandlerRoute('fullscreen.php')->withAbsoluteUrl();
        $this->assertEquals('http://foobar.com/some/path/fullscreen.php', (string) $url);

        $url = Url::fromHandlerRoute('fullscreen.php', 'some_action')->withAbsoluteUrl();
        $this->assertEquals('http://foobar.com/some/path/fullscreen.php?q=some_action.php', (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::fromHandlerModulePath()
     */
    public function testFromHandlerModulePath()
    {
        $url = Url::fromHandlerModuleRoute('fullscreen.php', 'Some Module', 'some_action');
        $this->assertSame(static::parseUrlQuery((string) $url), [
            'q' => '/modules/Some Module/some_action.php',
        ], 'The query params from the old url should not be carried over to the new one');
        $parsed = parse_url($url);
        $this->assertSame('/some/path/fullscreen.php', $parsed['path'] ?? '', (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::withQueryParam()
     */
    public function testWithQueryParam()
    {
        $url = Url::fromRoute('some_action')->withQueryParam('foo', 'bar');
        $this->assertEquals('/some/path/index.php?q=some_action.php&foo=bar', (string) $url);

        $url = Url::fromRoute()->withQueryParam('foo', 'world');
        $this->assertEquals('/some/path/index.php?foo=world', (string) $url);
    }

    /**
     * @covers \Gibbon\Http\Url::withReturn()
     */
    public function testWithReturn()
    {
        $url = Url::fromRoute('some_action')->withReturn('successx');
        $this->assertEquals('/some/path/index.php?q=some_action.php&return=successx', (string) $url);

        $url = Url::fromRoute()->withReturn('successx');
        $this->assertEquals('/some/path/index.php?return=successx', (string) $url);
    }

    /**
     * Parse the query portion of a URL string into an assoc-array of the
     * key-values in it.
     *
     * @param string $url_string
     *
     * @return array
     */
    private static function parseUrlQuery(string $url_string): array
    {
        $parsed = parse_url($url_string);
        parse_str($parsed['query'] ?? '', $results);
        return $results;
    }
}
