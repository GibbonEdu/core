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

use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Locale fallback translation path
 */
class LocaleFallbackTest extends TestCase
{
    public function testFallsBackToTranslationCatalogWhenSystemLocaleUnavailable()
    {
        $root = realpath(__DIR__ . '/../../../..');
        $moFile = $root . '/i18n/ar_SA/LC_MESSAGES/gibbon.mo';
        if (!is_readable($moFile)) {
            $this->markTestSkipped('Arabic gibbon.mo is not installed.');
        }

        // Force a locale code that will not exist on the host OS.
        $locale = new class($root) extends Locale {
            protected function activateSystemLocale(string $i18ncode): bool
            {
                // Simulate CloudLinux / CageFS: putenv appears fine, setlocale fails.
                if (function_exists('putenv')) {
                    putenv('LC_ALL='.$i18ncode.'.utf8');
                    putenv('LANG='.$i18ncode.'.utf8');
                    putenv('LANGUAGE='.$i18ncode);
                }
                return false;
            }
        };

        $locale->setLocale('ar_SA');
        $locale->setSystemTextDomain($root);

        $this->assertFalse($locale->isNativeLocaleActive());
        $this->assertSame('الرئيسية', $locale->translate('Home'));
        $this->assertSame('الطلبة', $locale->translate('Students'));
        $this->assertSame('تسجيل الدخول', $locale->translate('Login'));
    }
}
