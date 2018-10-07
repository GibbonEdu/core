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

namespace Gibbon\View;

use Gibbon\View\AssetBundle;

/**
 * Holds the details for rendering the current page.
 *
 * @version  v17
 * @since    v17
 */
class Page
{
    protected $templateEngine;

    /**
     * After constructing these class properties are publicly read-only.
     */
    protected $title = '';
    protected $address = '';
    protected $action;
    protected $module;
    protected $theme;

    /**
     * These properties can be modified during the runtime of a script,
     * and will be output at the end during template rendering.
     */
    protected $content = [];
    protected $stylesheets;
    protected $scripts;
    protected $alerts = ['error' => [], 'warning' => [], 'message' => []];
    protected $extra = ['head' => [], 'foot' => [], 'sidebar' => []];

    /**
     * Create a new page from a variable set of constructor params.
     *
     * @param array $params Essential parameters for building a page.
     */
    public function __construct($templateEngine = null, array $params = [])
    {
        $this->templateEngine = $templateEngine;

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
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the current page address (?q=).
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Get the action instance for the current page.
     *
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the module instance for the current page.
     *
     * @return Module|null
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get the theme instance for the current page.
     *
     * @return Theme|null
     */
    public function getTheme()
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
     */
    public function getAllStylesheets(string $context = null): array
    {
        $stylesheets = $this->stylesheets()->getAssets($context);

        if (!empty($this->getTheme())) {
            $stylesheets = array_replace(
                $stylesheets,
                $this->getTheme()->stylesheets()->getAssets($context)
            );
        }

        if (!empty($this->getModule())) {
            $stylesheets = array_replace(
                $stylesheets,
                $this->getModule()->stylesheets()->getAssets($context)
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
     */
    public function getAllScripts(string $context = null): array
    {
        $scripts = $this->scripts()->getAssets($context);

        if (!empty($this->getTheme())) {
            $scripts = array_replace(
                $scripts,
                $this->getTheme()->scripts()->getAssets($context)
            );
        }

        if (!empty($this->getModule())) {
            $scripts = array_replace(
                $scripts,
                $this->getModule()->scripts()->getAssets($context)
            );
        }

        return $scripts;
    }

    /**
     * Add user feedback as an error message displayed on this page.
     *
     * @param string $text Error message text.
     */
    public function addError(string $text)
    {
        $this->alerts['error'][] = $text;
    }

    /**
     * Add user feedback as a warning message displayed on this page.
     *
     * @param string $text Warning message text.
     */
    public function addWarning(string $text)
    {
        $this->alerts['warning'][] = $text;
    }

    /**
     * Add user feedback as an info message displayed on this page.
     *
     * @param string $text Info message text.
     */
    public function addMessage(string $text)
    {
        $this->alerts['message'][] = $text;
    }

    /**
     * Add user feedback as an alert displayed on this page.
     *
     * @param string $context   Contexts: error, warning, message, code
     * @param string $text      General notice message text.
     */
    public function addAlert(string $context, string $text)
    {
        $this->alerts[$context][] = $text;
    }

    /**
     * Get all alerts generated by this page, optionally by context.
     *
     * @param string $context Contexts: error, warning, message, code
     * @return array
     */
    public function getAlerts(string $context = null) : array
    {
        return !empty($context)
            ? $this->alerts[$context]
            : $this->alerts;
    }

    /**
     * Add a section of raw HTML to the HEAD tag.
     *
     * @param string $code Raw HTML code to render in the HEAD region.
     */
    public function addHeadExtra($code)
    {
        $this->extra['head'][] = $code;
    }

    /**
     * Add a section of raw HTML to bottom of the BODY tag.
     *
     * @param string $code Raw HTML code to render at the bottom of BODY region.
     */
    public function addFootExtra($code)
    {
        $this->extra['foot'][] = $code;
    }

    /**
     * Add a section of raw HTML to the page sidebar.
     *
     * @param string $code Raw HTML code to render in the page sidebar region.
     */
    public function addSidebarExtra($code)
    {
        $this->extra['sidebar'][] = $code;
    }

    /**
     * Get all raw HTML code sections by context.
     *
     * @param string $context Contexts: head, foot, sidebar
     * @return array
     */
    public function getExtraCode($context): array
    {
        return !empty($context)
            ? $this->extra[$context]
            : $this->extra;
    }

    /**
     * Builds an array of page data to be passed to the template engine.
     *
     * @return array
     */
    public function gatherData() : array
    {
        return [
            'title'        => $this->getTitle(),
            'alerts'       => $this->getAlerts(),
            'stylesheets'  => $this->getAllStylesheets(),
            'scriptsHead'  => $this->getAllScripts('head'),
            'scriptsFoot'  => $this->getAllScripts('foot'),
            'extraHead'    => $this->getExtraCode('head'),
            'extraFoot'    => $this->getExtraCode('foot'),
            'extraSidebar' => $this->getExtraCode('sidebar'),
            'content'      => $this->content,
        ];
    }

    /**
     * Writes a string to the page's internal content property.
     *
     * @param string $value
     */
    public function write(string $value)
    {
        $this->content[] = $value;
    }

    /**
     * Writes the output buffered result from a PHP script to the page's content.
     *
     * @param string $filepath
     * @param array $data
     */
    public function writeFromFile(string $filepath, array $data = [])
    {
        $this->write($this->fetchFromFile($filepath, $data));
    }

    /**
     * Writes a rendered template file to the page's content.
     *
     * @param string $template
     * @param array $data
     */
    public function writeFromTemplate(string $template, array $data = [])
    {
        $this->write($this->fetchFromTemplate($template, $data));
    }

    /**
     * Includes a PHP file in a protected scope, and returns the
     * output-buffered contents as a string.
     *
     * @param string $filepath
     * @param array  $data
     * @return string
     */
    public function fetchFromFile(string $filepath, array $data = []) : string
    {
        // TODO: Check $filepath for validity

        if (!is_file($filepath)) {
            // Throw an exception?
            return '';
        }

        global $guid, $gibbon, $caching, $version, $pdo, $connection2, $autoloader, $container;

        extract($data);

        try {
            ob_start();
            include $filepath;
            $output = ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        return $output;
    }

    /**
     * Renders a given template using the template engine + provided data
     * and returns the result as a string.
     *
     * @param string $template
     * @param array  $data
     * @return string
     */
    public function fetchFromTemplate(string $template, array $data = []) : string
    {
        return $this->templateEngine->render($template, $data);
    }

    /**
     * Render the entire page with the given template and return the result as a string.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []) : string
    {
        $data['page'] = $this->gatherData();
        $data['content'] = $this->content;

        return $this->templateEngine->render($template, $data);
    }

    /**
     * Returns the collection of stylesheets used by this page.
     *
     * @return AssetBundle
     */
    public function stylesheets(): AssetBundle
    {
        return $this->stylesheets;
    }

    /**
     * Returns the collection of scripts used by this page.
     *
     * @return AssetBundle
     */
    public function scripts(): AssetBundle
    {
        return $this->scripts;
    }
}
