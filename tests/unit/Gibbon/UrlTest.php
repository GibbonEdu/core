<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon;

use PHPUnit\Framework\TestCase;
use Gibbon\Url;

/**
 * @covers \Gibbon\Url class
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
     * @covers \Gibbon\Url::fromRoute()
     */
    public function testFromRoute()
    {
        $url = Url::fromRoute('some_action');
        $this->assertEquals('/some/path/index.php?q=some_action.php', (string) $url);

        $url = Url::fromRoute()->withQuery('hello=world');
        $this->assertEquals('/some/path/index.php?hello=world', (string) $url);
    }

    /**
     * @covers \Gibbon\Url::fromRoute()
     * @covers \Gibbon\Url::withQueryParams()
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
     * @covers \Gibbon\Url::fromRoute()
     * @covers \Gibbon\Url::withQuery()
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
     * @covers \Gibbon\Url::fromModuleRoute()
     */
    public function testFromModuleRoute()
    {
        $url = Url::fromModuleRoute('My Module', 'some_action');
        $this->assertEquals('/some/path/index.php?' . http_build_query([
            'q' => '/modules/My Module/some_action.php',
        ]), (string) $url);
    }

    /**
     * @covers \Gibbon\Url::fromModuleRoute()
     * @covers \Gibbon\Url::withQueryParams()
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
     * @covers \Gibbon\Url::fromModuleRoute()
     * @covers \Gibbon\Url::withQuery()
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
     * @covers \Gibbon\Url::fromHandlerPath()
     */
    public function testFromHandlerPath()
    {
        $url = Url::fromHandlerRoute('fullscreen.php');
        $this->assertEquals('/some/path/fullscreen.php', (string) $url);

        $url = Url::fromHandlerRoute('fullscreen.php', 'some_action');
        $this->assertEquals('/some/path/fullscreen.php?q=some_action.php', (string) $url);
    }

    /**
     * @covers \Gibbon\Url::fromHandlerModulePath()
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
     * @covers \Gibbon\Url::withQueryParam()
     */
    public function testWithQueryParam()
    {
        $url = Url::fromRoute('some_action')->withQueryParam('foo', 'bar');
        $this->assertEquals('/some/path/index.php?q=some_action.php&foo=bar', (string) $url);

        $url = Url::fromRoute()->withQueryParam('foo', 'world');
        $this->assertEquals('/some/path/index.php?foo=world', (string) $url);
    }

    /**
     * @covers \Gibbon\Url::withReturn()
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
