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

    public function __construct()
    {
        $this->name = 'actions';
        $this->setLabel(__('Actions'));
        $this->setSortable(false);
    }

    public function addAction($name, $label = '')
    {
        $action = new Action($name, $label);
        $this->actions[$name] = $action;

        return $action;
    }

    public function addParam($name, $value = '')
    {
        $this->params[$name] = $value;

        return $this;
    }

    public function getWidth()
    {
        return (count($this->actions) * 36).'px';
    }

    public function getOutput(&$data = array())
    {
        $output = '';
        foreach ($this->actions as $actionName => $action) {
            $output .= $action->getOutput($data, $this->params);
        }

        return $output;
    }
}