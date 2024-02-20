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

namespace Gibbon\View;

use Gibbon\View\View;
use Gibbon\View\AssetBundle;
use Gibbon\View\Components\Breadcrumbs;
use Gibbon\View\Components\ReturnMessage;
use Psr\Container\ContainerInterface;
use Gibbon\View\Components\Navigator;
use Gibbon\Services\Format;

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

    /**
     * Address of the page.
     *
     * @var string
     */
    protected $address = '';

    /**
     * Action of the page
     *
     * @var Action
     */
    protected $action;

    /**
     * Module that the page belongs to.
     *
     * @var Module
     */
    protected $module;

    /**
     * Theme to render the page with.
     *
     * @var Theme
     */
    protected $theme;

    /**
     * These properties can be modified during the runtime of a script,
     * and will be output at the end during template rendering.
     */
    protected $content = [];

    /**
     * Stylesheet asset.
     *
     * @var AssetBundle
     */
    protected $stylesheets;

    /**
     * Stylesheet asset.
     *
     * @var AssetBundle
     */
    protected $scripts;

    /**
     * Breadcrumb for the page.
     *
     * @var Breadcrumbs
     */
    protected $breadcrumbs;

    /**
     * School Year and Search Result navigation.
     *
     * @var Navigator
     */
    protected $navigator;

    /**
     * Return message, if any.
     *
     * @var ReturnMessage
     */
    protected $return;

    protected $alerts = ['error' => [], 'warning' => [], 'success' => [], 'message' => []];
    protected $extra = ['head' => [], 'foot' => [], 'sidebar' => []];

    /**
     * Create a new page from a variable set of constructor params.
     *
     * @param ContainerInterface $container
     * @param array $params Essential parameters for building a page.
     */
    public function __construct(ContainerInterface $container = null, array $params = [])
    { 
        parent::__construct($container ? $container->get('twig') : null);

        $this->breadcrumbs = new Breadcrumbs();
        $this->stylesheets = new AssetBundle();
        $this->scripts = new AssetBundle();
        $this->return = new ReturnMessage();
        $this->navigator = $container && $container->has('db') ? $container->get(Navigator::class) : null;

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
        return preg_replace('/[^a-zA-Z0-9_\-\.\/\s&=%]/', '', $this->address);
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
     * Returns a message when there are no records to display (but not an error message).
     *
     * @param string $text Error message text.
     */
    public function getBlankSlate(string $text = null)
    {
        return Format::alert($text ?? __('There are no records to display.'), 'message');
    }

    /**
     * Add user feedback as an error message displayed on this page.
     *
     * @param string $text Error message text.
     */
    public function addError(string $text)
    {
        // Override to always display the sidebar when pages are inaccessible
        if ($text == __('You do not have access to this action.') && empty($this['isLoggedIn'])) {
            $this['showSidebar'] = true;
        }

        $this->addAlert($text, 'error');
    }

    /**
     * Add user feedback as a warning message displayed on this page.
     *
     * @param string $text Warning message text.
     */
    public function addWarning(string $text)
    {
        $this->addAlert($text, 'warning');
    }

    /**
     * Add user feedback as an info message displayed on this page.
     *
     * @param string $text Info message text.
     */
    public function addMessage(string $text)
    {
        $this->addAlert($text, 'message');
    }

    /**
     * Add user feedback as a success message displayed on this page.
     *
     * @param string $text Success message text.
     */
    public function addSuccess(string $text)
    {
        $this->addAlert($text, 'success');
    }

    /**
     * Add user feedback as an alert displayed on this page.
     *
     * @param string $text      General notice message text.
     * @param string $context   Contexts: error, warning, message, code
     */
    public function addAlert(string $text, string $context = 'message')
    {
        $this->alerts[$context][] = strip_tags($text, '<a><b><i><u><strong><br><ul><ol><li>');
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
            'navigator'    => $this->navigator ? $this->navigator->getData() : [],
            'helpLink'     => $this->action['helpURL'] ?? '',
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
            'themeColour'     => 'purple',
        ]);

        $this->stylesheets->add('main', 'themes/Default/css/main.css');
        $this->stylesheets->add('theme', 'resources/assets/css/theme.min.css');
        $this->stylesheets->add('core', 'resources/assets/css/core.min.css', ['weight' => 10]);

        return $this;
    }

    /**
     * Check is a given address is valid and does not contain illegal strings.
     *
     * @param string $address
     * @return bool
     */
    public function isAddressValid($address, bool $strictPHP = true) : bool
    {
        if ($strictPHP && stripos($address, '.php') === false) {
            return false;
        }

        return !(stripos($address, '..') !== false
            || stristr($address, 'installer')
            || stristr($address, 'uploads')
            || stristr($address, 'config.php')
            || in_array(strtolower($address), array('index.php', '/index.php', './index.php'))
            || strtolower(substr($address, -11)) == '// index.php'
            || strtolower(substr($address, -11)) == './index.php');
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
