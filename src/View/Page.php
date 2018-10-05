<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version >= 7.0
 *
 * @category File
 * @package  Gibbon
 * @author   Ross Parker <ross@rossparker.org>
 * @license  GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link     https://gibbonedu.org/
 */

namespace Gibbon\View;

use Gibbon\Domain\System\Module;
use Gibbon\Domain\System\Theme;
use Gibbon\View\AssetBundle;

/**
 * Holds the details for rendering the current page.
 *
 * @category Class
 * @package  Gibbon\View
 * @author   Ross Parker <ross@rossparker.org>
 * @license  GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @version  Release: v17
 * @link     https://gibbonedu.org/
 * @since    Release: v17 
 */
class Page
{
    /**
     * After constructing these class properties are publicly read-only.
     */
    protected $title;
    protected $address;
    protected $action;
    protected $module;
    protected $theme;

    /**
     * These properties can be modified during the runtime of a script,
     * and will be output at the end during template rendering.
     */
    protected $stylesheets;
    protected $scripts;
    protected $alerts = ['error' => [], 'warning' => [], 'message' => []];
    protected $extra = ['head' => [], 'foot' => [], 'sidebar' => []];

    /**
     * Create a new page from a variable set of constructor params.
     *
     * @param array $params Essential parameters for building a page.
     */
    public function __construct(array $params = [])
    {
        $this->stylesheets = new AssetBundle();
        $this->scripts = new AssetBundle();

        // Merge constructor params into class properties
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the HTML page title.
     *
     * @return string Page title
     *
     * @since Release: v17 
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the current page address (?q=).
     *
     * @return string Current page address.
     *
     * @since Release: v17 
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Get the action instance for the current page.
     *
     * @return Action The action instance for the current page.
     *
     * @since Release: v17 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the module instance for the current page.
     *
     * @return Module The module instance for the page.
     *
     * @since Release: v17 
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Get the theme instance for the current page.
     *
     * @return Theme
     *
     * @since Release: v17 
     */
    public function getTheme(): Theme
    {
        return $this->theme;
    }

    /**
     * Get an array of all stylesheet asset used by this page (system,
     * module & theme).
     *
     * @param string|null $context Optional filter by context.
     *                             Either 'head' or 'foot'.
     *
     * @return array
     *
     * @since Release: v17 
     */
    public function getAllStylesheets(?string $context = null): array
    {
        $stylesheets = $this->stylesheets()->getAssets($context);

        if (!empty($this->getModule())) {
            $stylesheets = array_replace(
                $stylesheets, $this->getModule()->stylesheets()->getAssets($context)
            );
        }

        if (!empty($this->getTheme())) {
            $stylesheets = array_replace(
                $stylesheets, $this->getTheme()->stylesheets()->getAssets($context)
            );
        }

        return $stylesheets;
    }

    /**
     * Get an array of all script assets used by this page (system, module & theme).
     *
     * @param string|null $context Optionally filter by context.
     *                             Either 'head' or 'foot'.
     *
     * @return array
     *
     * @since Release: v17 
     */
    public function getAllScripts(?string $context = null): array
    {
        $scripts = $this->scripts()->getAssets($context);

        if (!empty($this->getModule())) {
            $scripts = array_replace(
                $scripts,
                $this->getModule()->scripts()->getAssets($context)
            );
        }

        if (!empty($this->getTheme())) {
            $scripts = array_replace(
                $scripts,
                $this->getTheme()->scripts()->getAssets($context)
            );
        }

        return $scripts;
    }

    /**
     * Add user feedback as an error message displayed on this page.
     *
     * @param string $text Error message text.
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function addError(string $text): void
    {
        $this->alerts['error'][] = $text;
    }

    /**
     * Add user feedback as a warning message displayed on this page.
     *
     * @param string $text Warning message text.
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function addWarning(string $text): void
    {
        $this->alerts['warning'][] = $text;
    }

    /**
     * Add user feedback as an info message displayed on this page.
     *
     * @param string $text General notice message text.
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function addMessage(string $text): void
    {
        $this->alerts['message'][] = $text;
    }

    /**
     * Get all alerts generated by this page, optionally by context.
     *
     * @param string $context Contexts: error, warning, message, code
     *
     * @return array
     *
     * @since Release: v17 
     */
    public function getAlerts(?string $context = null)
    {
        return !empty($context)
        ? $this->alerts[$context]
        : $this->alerts;
    }

    /**
     * Add a section of raw HTML to the HEAD tag.
     *
     * @param string $code Raw HTML code to render in the HEAD region.
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function addHeadExtra($code): void
    {
        $this->extra['head'][] = $code;
    }

    /**
     * Add a section of raw HTML to bottom of the BODY tag.
     *
     * @param string $code Raw HTML code to render at the bottom of BODY region.
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function addFootExtra($code)
    {
        $this->extra['foot'][] = $code;
    }

    /**
     * Add a section of raw HTML to the page sidebar.
     *
     * @param string $code Raw HTML code to render in the page sidebar region.
     *
     * @return void
     *
     * @since Release: v17 
     */
    public function addSidebarExtra($code): void
    {
        $this->extra['sidebar'][] = $code;
    }

    /**
     * Get all raw HTML code sections by context.
     *
     * @param string $context Contexts: head, foot, sidebar
     *
     * @return array
     *
     * @since Release: v17 
     */
    public function getExtraCode($context): array
    {
        return !empty($context)
        ? $this->extra[$context]
        : $this->extra;
    }

    /**
     * Returns the collection of stylesheets used by this page.
     *
     * @return AssetBundle Assetbundle of stylesheets.
     *
     * @since Release: v17 
     */
    public function stylesheets(): AssetBundle
    {
        return $this->stylesheets;
    }

    /**
     * Returns the collection of scripts used by this page.
     *
     * @return AssetBundle Assetbundle of javascripts.
     *
     * @since Release: v17 
     */
    public function scripts(): AssetBundle
    {
        return $this->scripts;
    }
}
