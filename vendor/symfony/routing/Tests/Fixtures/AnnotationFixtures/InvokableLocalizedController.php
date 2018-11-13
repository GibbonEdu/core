<?php

namespace Symfony\Component\Routing\Tests\Fixtures\AnnotationFixtures;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path={"nl": "/hier", "en": "/here"}, name="action")
 */
class InvokableLocalizedController
{
    public function __invoke()
    {
    }
}
