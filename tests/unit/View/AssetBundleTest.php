<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\View;

use PHPUnit\Framework\TestCase;

/**
 * @covers AssetBundle
 */
class AssetBundleTest extends TestCase
{
    public function testRegistersAssets()
    {
        $assets = new AssetBundle();

        $assets->register('foo', 'bar/baz', 'head');

        $this->assertArrayNotHasKey('foo', $assets->getAssets());
    }

    public function testAddsNewAssets()
    {
        $assets = new AssetBundle();

        $assets->add('foo', 'bar/baz', 'head');

        $this->assertArrayHasKey('foo', $assets->getAssets());
    }

    public function testAddsRegisteredAssets()
    {
        $assets = new AssetBundle();

        $assets->register('foo', 'bar/baz', 'head');
        $assets->add('foo');

        $this->assertArrayHasKey('foo', $assets->getAssets());
    }

    public function testGetsAllAssets()
    {
        $assets = new AssetBundle();

        $assets->register('foo', 'bar/baz', 'head');
        $assets->add('fiz', 'bar/baz', 'head');
        $assets->add('bus', 'bar/baz', 'head');

        $this->assertEquals(['fiz', 'bus'], array_keys($assets->getAssets()));
    }

    public function testFiltersAssetsByContext()
    {
        $assets = new AssetBundle();

        $assets->add('foo', 'bar/baz', 'head');
        $assets->add('fiz', 'bar/baz', 'foot');
        $assets->add('bus', 'bar/baz', 'head');
        $assets->add('biz', 'bar/baz', 'foot');

        $this->assertEquals(['foo', 'bus'], array_keys($assets->getAssets('head')));
    }
}
