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

/**
 * @covers AssetBundle
 */
class AssetBundleTest extends TestCase
{
    public function testRegistersAssets()
    {
        $assets = new AssetBundle();

        $assets->register('foo', 'bar/baz');

        $this->assertArrayNotHasKey('foo', $assets->getAssets());
    }

    public function testAddsNewAssets()
    {
        $assets = new AssetBundle();

        $assets->add('foo', 'bar/baz');

        $this->assertArrayHasKey('foo', $assets->getAssets());
    }

    public function testAddsRegisteredAssets()
    {
        $assets = new AssetBundle();

        $assets->register('foo', 'bar/baz');
        $assets->add('foo');

        $this->assertArrayHasKey('foo', $assets->getAssets());
    }

    public function testGetsAllAssets()
    {
        $assets = new AssetBundle();

        $assets->register('foo', 'bar/baz');
        $assets->add('fiz', 'bar/baz');
        $assets->add('bus', 'bar/baz');

        $this->assertEquals(['fiz', 'bus'], array_keys($assets->getAssets()));
    }

    public function testFiltersAssetsByContext()
    {
        $assets = new AssetBundle();

        $assets->add('foo', 'bar/baz', ['context' => 'head']);
        $assets->add('fiz', 'bar/baz', ['context' => 'foot']);
        $assets->add('bus', 'bar/baz', ['context' => 'head']);
        $assets->add('biz', 'bar/baz', ['context' => 'foot']);

        $this->assertEquals(['foo', 'bus'], array_keys($assets->getAssets('head')));
    }

    public function testSortsAssetsByWeight()
    {
        $assets = new AssetBundle();

        $assets->add('foo', 'bar/baz', ['weight' => 42]);
        $assets->add('fiz', 'bar/baz', ['weight' => 3]);
        $assets->add('bus', 'bar/baz', ['weight' => 128]);
        $assets->add('biz', 'bar/baz', ['weight' => 99]);

        $this->assertEquals(['fiz', 'foo', 'biz', 'bus'], array_keys($assets->getAssets()));
    }

    public function testSortsAssetsByWeightAndOriginalOrder()
    {
        $assets = new AssetBundle();

        $assets->add('foo', 'bar/baz', ['weight' => 1]);

        $assets->add('bar', 'bar/baz');
        $assets->add('baz', 'bar/baz');
        $assets->add('bus', 'bar/baz');

        $assets->add('fiz', 'bar/baz', ['weight' => -1]);

        $this->assertEquals(['fiz', 'bar', 'baz', 'bus', 'foo'], array_keys($assets->getAssets()));
    }
}
