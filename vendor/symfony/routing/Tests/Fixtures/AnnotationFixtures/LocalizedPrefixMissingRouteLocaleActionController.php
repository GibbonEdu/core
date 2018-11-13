<?php

namespace Symfony\Component\Routing\Tests\Fixtures\AnnotationFixtures;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path={"nl": "/nl", "en": "/en"})
 */
class LocalizedPrefixMissingRouteLocaleActionController
{
    /**
     * @Route(path={"nl": "/actie"}, name="action")
     */
    public function action()
    {
    }
}
