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
use Psr\Container\ContainerInterface;

/**
 * Localization & Internationalization Class
 *
 * @version	v13
 * @since	v13
 */
class Locale implements LocaleInterface
{
    protected $i18ncode;

    protected $session;

    protected $stringReplacements;

    /**
     * Construct
     */
    public function __construct(ContainerInterface $container)
    {
        $this->session = $container->get('session');
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
        
        $this->setSystemTextDomain($this->session->get('absolutePath'));

        // Parse additional modules, adding domains for those
        if ($pdo->getConnection() != null) {
            $sql = "SELECT name FROM gibbonModule WHERE active='Y' AND type='Additional'";
            $modules = $pdo->select($sql)->fetchAll();

            foreach ($modules as $module) {
                $this->setModuleTextDomain($module['name'], $this->session->get('absolutePath'));
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

        // get domain from options.
        $domain = $options['domain'] ?? '';

        // get raw translated string with or without domain.
        $text = empty($domain) ? _($text) : dgettext($domain, $text);

        // apply named replacement parameters, if presents.
        $text = strtr($text, $params);

        // apply custom string replacement logic.
        if (isset($this->stringReplacements) && is_array($this->stringReplacements)) {
            foreach ($this->stringReplacements as $replacement) {
                if ($replacement["mode"]=="Partial") { //Partial match
                    if ($replacement["caseSensitive"]=="Y") {
                        if (strpos($text, $replacement["original"]) !== false) {
                            $text=str_replace($replacement["original"], $replacement["replacement"], $text);
                        }
                    } else {
                        if (stripos($text, $replacement["original"]) !== false) {
                            $text=str_ireplace($replacement["original"], $replacement["replacement"], $text);
                        }
                    }
                } else { //Whole match
                    if ($replacement["caseSensitive"]=="Y") {
                        if ($replacement["original"]==$text) {
                            $text=$replacement["replacement"];
                        }
                    } else {
                        if (strtolower($replacement["original"])==strtolower($text)) {
                            $text=$replacement["replacement"];
                        }
                    }
                }
            }
        }

        return $text;
    }
}
