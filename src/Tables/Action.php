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

namespace Gibbon\Tables;

use Gibbon\Forms\Layout\WebLink;
use Gibbon\Http\Url;
use Gibbon\View\Component;

/**
 * Action link representation for HTML listings.
 *
 * Represents quick actions for user to take in listing UIs.
 * Will be rendered into HTML links with or without icon image.
 *
 * @version v16
 * @since   v16
 */
class Action extends WebLink
{
    /**
     * Name of the action.
     *
     * @var string
     */
    protected $name;

    /**
     * Label of the action. Displayed on hover.
     *
     * @var string
     */
    protected $label;

    /**
     * The internal URL for this action.
     *
     * @var string
     */
    protected $url;

    /**
     * URL fragment of the internal URL for this action.
     *
     * @var string
     */
    protected $urlFragment;

    /**
     * The icon name, without any path or filetype
     *
     * @var string
     */
    protected $icon;

    /**
     * The icon css class
     *
     * @var string
     */
    protected $iconClass;

    /**
     * The icon library (basic, solid, outline, etc.)
     *
     * @var string
     */
    protected $iconLibrary;

    /**
     * Boolean flag indicate if the link opens a modal box.
     *
     * @var boolean
     */
    protected $modal = false;

    /**
     * Boolean flag indicate if the link is a direct link.
     *
     * @var boolean
     */
    protected $direct = false;

    /**
     * Boolean flag indicate if the link is an external link.
     *
     * @var boolean
     */
    protected $external = false;

    /**
     * Boolean flag indicate if the action label should be displayed as text next to the icon.
     *
     * @var boolean
     */
    protected $displayLabel = false;

    /**
     * Class constructor of Action.
     *
     * @param string $name   Name of the action. Usually 'add', 'addMultiple',
     *                       'edit', 'delete', 'print', 'export', 'import', 'view',
     *                       or 'accept'.
     * @param string $label  The label for the action. Displayed on hover.
     */
    public function __construct($name, $label = '')
    {
        $this->name = $name;
        $this->setLabel($label);

        // Pre-defined settings for common actions
        switch ($this->name) {
            case 'delete':  $this->modalWindow(650, 250);
                            break;
        }
    }

    /**
     * Sets the internal url for this action.
     *
     * @param string $url
     * @param string $urlFragment
     * @return self
     */
    public function setURL($url, $urlFragment = null)
    {
        $this->url = $url;
        $this->urlFragment = $urlFragment;

        return $this;
    }

    /**
     * Sets the external url for this action.
     *
     * @param string $url
     * @param string $urlFragment
     * @return self
     */
    public function setExternalURL($url, $urlFragment = null, $downloadable = false)
    {
        $this->url = $url;
        $this->external = true;
        $this->urlFragment = $urlFragment;

        $this->setAttribute('target', mb_stripos($url, '://') !== false ? '_blank' : '');
        $this->setAttribute('download', $downloadable);

        return $this;
    }

    /**
     * Sets the action label, displayed on hover.
     *
     * @param string $label
     * @return self
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Gets the action label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Display the action label as text next to the icon.
     *
     * @param bool $value
     * @return self
     */
    public function displayLabel($value = true)
    {
        $this->displayLabel = $value;

        return $this;
    }

    /**
     * Set the icon name, without any path or filetype
     *
     * @param string $icon
     * @return self
     */
    public function setIcon($icon, $class = '', $library = 'solid')
    {
        $this->icon = $icon;
        $this->iconClass = $class;
        $this->iconLibrary = $library;

        return $this;
    }

    /**
     * Gets the action icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @deprecated Remove setters that start with isXXX for code consistency.
     */
    public function isModal($width = 650, $height = 650)
    {
        return $this->modalWindow($width, $height);
    }

    /**
     * Load the action URL in a modal window rather than loading a new page. Commonly used for delete actions.
     *
     * @param bool $value
     * @return self
     */
    public function modalWindow($width = 650, $height = 650)
    {
        $this->modal = !empty($width);

        return $this;
    }

    /**
     * @deprecated Remove setters that start with isXXX for code consistency.
     */
    public function isDirect($value = true)
    {
        return $this->directLink($value);
    }

    /**
     * The action link will not prepend an index.php?q=
     *
     * @param bool $value
     * @return self
     */
    public function directLink($value = true)
    {
        $this->direct = $value;

        return $this;
    }

    /**
     * Renders the action as an icon and url, adding in any nessesary url parameters.
     *
     * @param array $data
     * @param array $params
     * @return string
     */
    public function getOutput(&$data = array(), $params = array())
    {
        global $session; // :((

        if (empty($this->url)) {
            return $this->getLabel();
        }

        $queryParams = !$this->direct ? array('q' => $this->url) : array();

        // Allow ActionColumn level params to auto-fill from the row data, if they're not set
        foreach ($params as $key => $value) {
            $queryParams[$key] = (is_null($value) && !empty($data[$key]))? $data[$key] : $value;
        }

        // Load excplicit params from the Action itself
        foreach ($this->params as $key => $value) {
            $queryParams[$key] = $value;
        }

        if ($this->modal) {
            $this->setAttribute('hx-boost', 'true')
                ->setAttribute('hx-target', '#modalContent')
                ->setAttribute('hx-push-url', 'false')
                ->setAttribute('x-on:htmx:after-on-load', 'modalOpen = true')
                ->setAttribute('x-on:click', "modalType = '{$this->name}'")
                ->setAttribute('hx-swap', 'innerHTML show:no-scroll swap:0s');
        } elseif (!$this->external && !$this->direct && $this->url != '#') {
            $this->setAttribute('hx-boost', 'true')
                ->setAttribute('hx-target', '#content-wrap')
                ->setAttribute('hx-select', '#content-wrap')
                ->setAttribute('hx-swap', 'outerHTML show:window:top swap:0s');
        } elseif (!empty($this->getAttribute('hx-confirm'))) {
            $this->setAttribute('hx-post', Url::fromHandlerRoute(ltrim($this->url, '/')) )
                ->setAttribute('hx-target', '#content-wrap')
                ->setAttribute('hx-select', '#content-wrap')
                ->setAttribute('hx-swap', 'outerHTML show:window:top swap:0s');
        }

        if ($this->url instanceof Url) {
            $this->setAttribute('href', (string)$this->url);
        } elseif ($this->external) {
            $this->setAttribute('href', $this->url.$this->urlFragment)->setAttribute('rel', 'noopener noreferrer');
        } else if ($this->direct) {
            $this->setAttribute('href', Url::fromHandlerRoute(ltrim($this->url, '/'))
                ->withQueryParams($queryParams)
                ->withFragment(ltrim($this->urlFragment ?? '', '#')));
        } else if ($this->modal) {
            $this->setAttribute('href', Url::fromHandlerRoute('fullscreen.php')
                ->withQueryParams($queryParams));
        } else if ($this->url != '#') {
            $this->setAttribute('href', Url::fromRoute()
                ->withQueryParams($queryParams)
                ->withFragment(ltrim($this->urlFragment ?? '', '#')));
        }

        return Component::render(Action::class, $this->getAttributeArray() + [
            'action'       => $this->name,
            'modal'        => $this->modal,
            'icon'         => $this->icon,
            'iconClass'    => $this->iconClass,
            'iconLibrary'  => $this->iconLibrary,
            'label'        => $this->label,
            'displayLabel' => $this->displayLabel,
        ]);
    }
}
