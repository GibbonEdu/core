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

namespace Gibbon\Tables;

use Gibbon\Forms\Layout\WebLink;

/**
 * Action
 *
 * @version v16
 * @since   v16
 */
class Action extends WebLink
{
    protected $name;
    protected $label;
    protected $url;
    protected $icon;
    protected $params = array();

    protected $modal = false;
    protected $displayLabel = false;

    public function __construct($name, $label = '')
    {
        $this->name = $name;
        $this->setLabel($label);

        // Pre-defined settings for common actions
        switch ($this->name) {
            case 'add':     $this->setIcon('page_new');
                            break;
            case 'edit':    $this->setIcon('config');
                            break;
            case 'delete':  $this->setIcon('garbage')->isModal(true)->addParam('width', '650')->addParam('height', '135');
                            break;
            default:
            case 'view':    $this->setIcon('zoom');
                            break;
        }
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
    public function setIcon($icon)
    {
        $this->icon = $icon;

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
     * Load the action URL in a modal window rather than loading a new page. Commonly used for delete actions.
     *
     * @param bool $value
     * @return self
     */
    public function isModal($value = true) 
    {
        $this->modal = $value;

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
        global $guid; // :(

        $queryParams = array();

        if (!empty($data)) {
            foreach (array_merge($params, $this->params) as $key => $value) {
                $queryParams[$key] = (empty($value) && !empty($data[$key]))? $data[$key] : $value;
            }
        }

        if ($this->modal) {
            $url = $_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q='.$this->getURL();
            $this->addClass('thickbox');
        } else {
            $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q='.$this->getURL();
        }

        if (!empty($queryParams)) {
            $url .= '&'.http_build_query($queryParams);
        }

        $this->setURL($url);
        $this->setContent(sprintf('%1$s<img title="%2$s" src="'.$_SESSION[$guid]['absoluteURL'].'/themes/Default/img/%3$s.png" style="margin-left: 5px">', 
                ($this->displayLabel? $this->getLabel() : ''),
                $this->getLabel(), 
                $this->getIcon()
            ));

        return parent::getOutput();
    }
}
