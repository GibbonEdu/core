<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\Tests\Functional\Bundle\TestBundle\TestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return array(
    new FrameworkBundle(),
    new TestBundle(),
);
