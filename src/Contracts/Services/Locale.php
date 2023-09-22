<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Contracts\Services;

/**
 * Locale Interface
 *
 * @version	v17
 * @since	v17
 */
interface Locale
{
    /**
     * Sets the locale for a given code.
     *
     * @param string $i18nCode
     */
    public function setLocale($i18nCode);

    /**
     * Gets the current locale code.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Sets the default timezone.
     *
     * @param string $timezoneIdentifier
     */
    public function setTimezone($timezoneIdentifier);

    /**
     * Gets the default timezone.
     *
     * @return string
     */
    public function getTimezone();

    /**
     * Binds the system default text domain.
     *
     * @param string $domain
     * @param string $absolutePath
     */
    public function setSystemTextDomain($absolutePath);

    /**
     * Binds a text domain for a given module by name.
     *
     * @param string $module
     * @param string $absolutePath
     */
    public function setModuleTextDomain($module, $absolutePath);

    /**
     * Translate a string using the current locale and string replacements.
     *
     * @param string $text    Text to Translate.
     * @param array  $params  Assoc array of key value pairs for named
     *                        string replacement.
     * @param array  $options Options for translations (e.g. domain).
     *
     * @return string Translated Text
     */
    public function translate(string $text, array $params = [], array $options = []);
}
