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

namespace Gibbon\Forms\Input;

use Gibbon\View\Component;
use Gibbon\Forms\Layout\Element;
use Gibbon\Forms\Traits\InputAttributesTrait;
use Gibbon\Forms\Traits\ButtonGroupTrait;

/**
 * Button
 *
 * @version v28
 * @since   v14
 */
class Button extends Element
{
    use ButtonGroupTrait;
    use InputAttributesTrait;
    
    private $type;
    private $icon;
    private $iconClass;
    private $iconLibrary;
    private $size;
    private $color;

    public function __construct($name, $type = 'button', $onClick = null, $id = null)
    {
        $this->setName($name);
        $this->setValue($name);
        $this->setID($id ?? $name);
        $this->onClick($onClick);
        $this->type = $type;
    }

    /**
     * Sets an onClick behaviour for the button.
     *
     * Deprecated. Use Alpine @click behaviours instead.
     * 
     * @deprecated v28
     * @param string $value
     * @return self
     */
    public function onClick($value)
    {
        $this->setAttribute('onClick', $value);
        return $this;
    }

    /**
     * Determines the button type and how it will render.
     *
     * @param string $value     One of: button, input, submit.
     * @return self
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * Sets an icon to display inside the button.
     *
     * @param string $value  
     * @param string $class
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
     * Sets a display size for the button.
     *
     * @param string $value     One of: sm, md, ld
     * @return self
     */
    public function setSize($value)
    {
        $this->size = $value;
        return $this;
    }

    /**
     * Sets a display color for the button.
     *
     * @param string $value     One of: red, gray, purple
     * @return self
     */
    public function setColor($value)
    {
        $this->color = $value;
        return $this;
    }

    public function setAction($url)
    {
        if (empty($url)) {
            $this->setDisabled(true);
            return;
        }

        $this->setAttribute('hx-get', $url)
            ->setAttribute('hx-target', '#content-wrap')
            ->setAttribute('hx-select', '#content-wrap')
            ->setAttribute('hx-push-url', 'true')
            ->setAttribute('hx-swap', 'outerHTML show:none swap:0s');

        return $this;
    }
 
    protected function getElement()
    {
        return Component::render(Button::class, $this->getAttributeArray() + [
            'groupAlign'  => $this->getGroupAlign(),
            'groupClass'  => $this->getGroupClass(),
            'type'        => $this->type,
            'icon'        => $this->icon,
            'iconClass'   => $this->iconClass,
            'iconLibrary' => $this->iconLibrary,
            'size'        => $this->size,
            'color'       => $this->color,
        ]);
    }
}
