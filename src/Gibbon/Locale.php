<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon;

use Gibbon\Contracts\Services\Locale as LocaleInterface;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session as SessionInterface;

/**
 * Localization & Internationalization Class
 *
 * @version	v13
 * @since	v13
 */
class Locale implements LocaleInterface
{
    protected $i18ncode;

    protected $absolutePath;

    protected $session;

    protected $stringReplacements;

    protected $supportsGetText = true;


    /**
     * Construct
     *
     * @param string  $absolutePath Absolute path to the Gibbon installation
     * @param Session $session      Global session object for string
     *                              replacement cache.
     */
    public function __construct(string $absolutePath, SessionInterface $session)
    {
        $this->absolutePath = $absolutePath;
        $this->session = $session;
        $this->supportsGetText = function_exists('gettext') && function_exists('dgettext');
    }

    /**
     * Set the current i18n code
     *
     * @param   string $i18ncode
     */
    public function setLocale($i18ncode)
    {
        // Cancel if there's no code set
        if (empty($i18ncode)) return;

        $this->i18ncode = $i18ncode;

        putenv('LC_ALL='.$this->i18ncode.'.utf8');
        putenv('LANG='.$this->i18ncode.'.utf8');
        putenv('LANGUAGE='.$this->i18ncode.'.utf8');
        $localeSet = setlocale(LC_ALL, $this->i18ncode.'.utf8',
                                       $this->i18ncode.'.UTF8',
                                       $this->i18ncode.'.utf-8',
                                       $this->i18ncode.'.UTF-8',
                                       $this->i18ncode);
    }

    /**
     * Get the current i18n code
     *
     * @return  string
     */
    public function getLocale() {
        return $this->i18ncode;
    }

    public function setTimezone($timezone)
    {
        date_default_timezone_set($timezone);
    }

    public function getTimezone()
    {
        return date_default_timezone_get();
    }

    /**
     * Set the default domain and load module domains
     *
     * @param   Gibbon\Contracts\Database\Connection  $pdo
     */
    public function setTextDomain(Connection $pdo) {
        
        $this->setSystemTextDomain($this->absolutePath);

        // Parse additional modules, adding domains for those
        if ($pdo->getConnection() != null) {
            $sql = "SELECT name FROM gibbonModule WHERE active='Y' AND type='Additional'";
            $modules = $pdo->select($sql)->fetchAll();

            foreach ($modules as $module) {
                $this->setModuleTextDomain($module['name'], $this->absolutePath);
            }
        }
    }

    /**
     * Binds the system default text domain.
     *
     * @param string $domain
     * @param string $absolutePath
     */
    public function setSystemTextDomain($absolutePath)
    {
        if (!$this->supportsGetText) return;

        bindtextdomain('gibbon', $absolutePath.'/i18n');
        bind_textdomain_codeset('gibbon', 'UTF-8');
        textdomain('gibbon');
    }

    /**
     * Binds a text domain for a given module by name.
     *
     * @param string $module
     * @param string $absolutePath
     */
    public function setModuleTextDomain($module, $absolutePath)
    {
        if (!$this->supportsGetText) return;
        
        bindtextdomain($module, $absolutePath.'/modules/'.$module.'/i18n');
    }

    /**
     * Get and store custom string replacements in session
     *
     * @param   Gibbon\Contracts\Database\Connection  $pdo
     */
    public function setStringReplacementList(Connection $pdo, $forceRefresh = false)
    {
        $stringReplacements = $this->session->get('stringReplacement', null);

        // Do this once per session, only if the value doesn't exist
        if ($forceRefresh || $stringReplacements === null) {

            $stringReplacements = array();

            if ($pdo->getConnection() != null) {
                $data = array();
                $sql="SELECT original, replacement, mode, caseSensitive FROM gibbonString ORDER BY priority DESC, original";

                $result = $pdo->executeQuery($data, $sql);

                if ($result->rowCount()>0) {
                    $stringReplacements = $result->fetchAll();
                }
            }

            $this->session->set('stringReplacement', $stringReplacements );
        }

        $this->stringReplacements = $stringReplacements;
    }

    /**
     * Format given string with the parameter array.
     *
     * @param string $text   A string template for parameter substitution. The placeholder in
     *                       '{key}' format will be replaced by 'value' for the given parameter
     *                       array: ['key' => 'value'].
     * @param array  $params An array of key-value pairs to be used for parameter substitutions.
     *
     * @return string The substituted version of $text string.
     */
    protected static function formatString(string $text, array $params = [])
    {
        return strtr($text, array_reduce(array_keys($params), function ($carry, $key) use ($params) {
            $placeholder = stripos($key, '$s') !== false ? $key : '{'.$key.'}';
            $carry[$placeholder] = $params[$key]; // apply quote to the keys for replacement
            return $carry;
        }, []));
    }

    /**
     * Apply custom string replacement logic from database.
     *
     * @param string  $text Raw string to apply the string replacement logics
     *
     * @return string The substituted version of $text string.
     */
    protected function doStringReplacement(string $text)
    {
        if (isset($this->stringReplacements) && is_array($this->stringReplacements)) {
            foreach ($this->stringReplacements as $replacement) {
                if ($replacement['mode'] == 'Partial') { //Partial match
                    if ($replacement['caseSensitive'] == 'Y') {
                        if (strpos($text, $replacement['original']) !== false) {
                            $text = str_replace($replacement['original'], $replacement['replacement'], $text);
                        }
                    } else {
                        if (stripos($text, $replacement['original']) !== false) {
                            $text = str_ireplace($replacement['original'], $replacement['replacement'], $text);
                        }
                    }
                } else { //Whole match
                    if ($replacement['caseSensitive'] == 'Y') {
                        if ($replacement['original'] == $text) {
                            $text = $replacement['replacement'];
                        }
                    } else {
                        if (strtolower($replacement['original']) == strtolower($text)) {
                            $text = $replacement['replacement'];
                        }
                    }
                }
            }
        }
        return $text;
    }

    /**
     * Custom translation function to allow custom string replacement
     *
     * @param string $text    Text to Translate.
     * @param array  $params  Assoc array of key value pairs for named
     *                        string replacement.
     * @param array  $options Options for translations (e.g. domain).
     *
     * @return string Translated Text
     */
    public function translate(string $text, array $params = [], array $options = [])
    {
        if ($text === '') {
            return $text;
        }

        if (empty($this->i18ncode)) {
            return $text;
        }

        // get domain from options.
        $domain = $options['domain'] ?? '';

        // get raw translated string with or without domain.
        if ($this->supportsGetText) {
            $text = empty($domain) ?
                gettext($text) :
                dgettext($domain, $text);
        }

        // apply named replacement parameters, if presents.
        $text = static::formatString($text, $params);

        // apply custom string replacement logics and return.
        return $this->doStringReplacement($text);
    }

    /**
     * Custom translation function to allow custom string replacement with
     * plural string.
     *
     * @param string $singular The singular message ID.
     * @param string $plural   The plural message ID.
     * @param int    $n        The number (e.g. item count) to determine
     *                         the translation for the respective grammatical
     *                         number.
     * @param array  $params   Assoc array of key value pairs for named
     *                         string replacement.
     * @param array  $options  Options for translations (e.g. domain).
     *
     * @return string Translated Text
     */
    public function translateN(string $singular, string $plural, int $n, array $params = [], array $options = [])
    {
        if ($singular === '') {
            return $singular;
        }

        // Automatically set the named {count} parameter.
        if (!isset($params['count'])) {
            $params['count'] = $n;
        }

        // get domain from options.
        $domain = $options['domain'] ?? '';

        // get raw translated string with or without domain.
        if ($this->supportsGetText) {
            $text = empty($domain) ?
                ngettext($singular, $plural, $n) :
                dngettext($domain, $singular, $plural, $n);
        } else {
            $text = $n > 1 ? $plural : $singular;
        }

        // apply named replacement parameters, if presents.
        $text = static::formatString($text ?? '', $params);

        // apply custom string replacement logics and return.
        return $this->doStringReplacement($text);
    }
}
