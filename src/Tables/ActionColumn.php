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

/**
 * ActionColumn
 *
 * @version v16
 * @since   v16
 */
class ActionColumn extends Column
{
    protected $actions = array();
    protected $params = array();

    /**
     * Creates a pre-defined column for grouped sets of action icons.
     */
    public function __construct()
    {
        $this->name('actions')->label(__('Actions'))->sortable(false);
    }

    /**
     * Adds a named action to the column and returns the new Action object. 
     *
     * @param string $name
     * @param string $label
     * @return Action
     */
    public function addAction($name, $label = '')
    {
        $action = new Action($name, $label);
        $this->actions[$name] = $action;

        return $action;
    }

    /**
     * Adds a URL parameter to the column that is passed to _each_ action.
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addParam($name, $value = '')
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Returns a column width based on the number of actions.
     *
     * @return string
     */
    public function calculateWidth()
    {
        return (count($this->actions) * 36).'px';
    }

    /**
     * Iterates over and renders each action, passing in the row data and URL parameters.
     *
     * @param array $data
     * @return string
     */
    public function getOutput(&$data = array())
    {
        $output = '';
        foreach ($this->actions as $actionName => $action) {
            $output .= $action->getOutput($data, $this->params);
        }

        return $output;
    }
}
