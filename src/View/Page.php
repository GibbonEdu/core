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

use Twig\Environment;
use Gibbon\View\View;
use Gibbon\View\AssetBundle;
use Gibbon\View\Components\Breadcrumbs;

/**
 * Holds the details for rendering the current page.
 *
 * @version  v17
 * @since    v17
 */
class Page extends View
{
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
    protected $breadcrumbs;
    protected $alerts = ['error' => [], 'warning' => [], 'success' => [], 'message' => []];
    protected $extra = ['head' => [], 'foot' => [], 'sidebar' => []];

    /**
     * Create a new page from a variable set of constructor params.
     *
     * @param array $params Essential parameters for building a page.
     */
    public function __construct(Environment $templateEngine = null, array $params = [])
    {
        parent::__construct($templateEngine);
        
        $this->breadcrumbs = new Breadcrumbs();
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
     * Allow read-only access of page properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->$name)? $this->$name : null;
    }

    /**
     * Check if a page property exists.
     *
     * @param string $name
     * @return mixed
     */
    public function __isset(string $name)
    {
        return isset($this->$name);
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
        if (!empty($this->getTheme())) {
            $this->stylesheets->addMultiple($this->getTheme()->stylesheets->getAssets($context));
        }

        if (!empty($this->getModule())) {
            $this->stylesheets->addMultiple($this->getModule()->stylesheets->getAssets($context));
        }

        return $this->stylesheets->getAssets($context);
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
        $scripts = $this->scripts->getAssets($context);

        if (!empty($this->getTheme())) {
            $scripts = array_replace(
                $scripts,
                $this->getTheme()->scripts->getAssets($context)
            );
        }

        if (!empty($this->getModule())) {
            $scripts = array_replace(
                $scripts,
                $this->getModule()->scripts->getAssets($context)
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
     * Add user feedback as a success message displayed on this page.
     *
     * @param string $text Success message text.
     */
    public function addSuccess(string $text)
    {
        $this->alerts['success'][] = $text;
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
        // This is for backwards compatibility with pages that still have hardcoded breadcrumbs.
        // It currently only displays the new breadcrumbs if some have been added via this class.
        // Eg: more than one on a non-module page, more than two on a module-page.
        $breadcrumbs = $this->breadcrumbs->getItems();
        $displayTrail = ((empty($this['isLoggedIn']) || empty($this->getModule())) && count($breadcrumbs) > 1) || (!empty($this->getModule()) && count($breadcrumbs) > 2);
        
        return [
            'title'        => $this->getTitle(),
            'breadcrumbs'  => $displayTrail ? $breadcrumbs : [],
            'alerts'       => $this->getAlerts(),
            'stylesheets'  => $this->getAllStylesheets(),
            'scriptsHead'  => $this->getAllScripts('head'),
            'scriptsFoot'  => $this->getAllScripts('foot'),
            'extraHead'    => $this->getExtraCode('head'),
            'extraFoot'    => $this->getExtraCode('foot'),
            'extraSidebar' => $this->getExtraCode('sidebar'),
        ];
    }

    /**
     * Initializes the page class with some usable defaults. Useful for error pages where the full index has not been initialized.
     *
     * @param string $absolutePath
     * @return self
     */
    public function setDefaults($absolutePath)
    {
        $server = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $absoluteURL = $server . dirname($_SERVER['PHP_SELF']);
        $this->addData([
            'title'           => __('Gibbon'),
            'gibbonThemeName' => 'Default',
            'absolutePath'    => $absolutePath,
            'absoluteURL'     => $absoluteURL,
        ]);

        $this->stylesheets->add('main', 'themes/Default/css/main.css');
        $this->stylesheets->add('theme', 'resources/assets/css/theme.min.css');
        $this->stylesheets->add('core', 'resources/assets/css/core.min.css', ['weight' => 10]);
        $this->stylesheets->add(
            'personal-background',
            'body { background: url("'.$absoluteURL.'/themes/Default/img/backgroundPage.jpg'.'") repeat fixed center top #626cd3!important; }',
            ['type' => 'inline']
        );

        return $this;
    }

    /**
     * Check is a given address is valid and does not contain illegal strings.
     *
     * @param string $address
     * @return bool
     */
    public function isAddressValid($address) : bool
    {
        return !(stripos($address, '..') !== false
            || strstr($address, 'installer')
            || strstr($address, 'uploads')
            || in_array($address, array('index.php', '/index.php', './index.php'))
            || substr($address, -11) == '// index.php'
            || substr($address, -11) == './index.php');
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

        return parent::render($template, $data);
    }
}
