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

namespace Gibbon\Forms\Layout;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\RowDependancyInterface;

/**
 * Content
 *
 * @version v14
 * @since   v14
 */
class Heading extends Element implements OutputableInterface, RowDependancyInterface
{
    protected $row;
    protected $tag = 'h3';
    protected $id;
    protected $headingClass;

    /**
     * Add a generic heading element.
     * @param  string  $content
     */
    public function __construct($id, $content, $tag = null)
    {
        $this->id = $id;
        $this->content = $content;
        $this->tag = !empty($tag) ? $tag : 'h3';
    }

    /**
     * Method for RowDependancyInterface to automatically set a reference to the parent Row object.
     * @param  object  $row
     */
    public function setRow($row)
    {
        $this->row = $row;
        $this->headingClass = $row->getClass();

        $this->row->addClass($this->tag == 'h3' ? 'formHeading break top-0 z-10 text-sm text-gray-600 font-light ' : 'formHeading m-0 p-0 text-sm text-gray-600 font-light');

        $headingID = preg_replace('[/~`!@%#$%^&*()+={}\[\]|\\:;"\'<>,.?\/]', '', substr($this->id, 0, 60)); 
        $this->row->setID($headingID);

        $this->row->setHeading(preg_replace('/[^a-zA-Z0-9 -_]/', '', strip_tags($this->id)));
        $this->setID($headingID);
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function getHeadingClass()
    {
        return $this->headingClass;
    }

    /**
     * Get the content text of the element.
     * @return  string
     */
    protected function getElement()
    {
        return sprintf('<%1$s class="m-0 p-0 pt-2 pb-1 font-semibold text-gray-950 text-2xl/8 sm:text-xl/8"><a id="%3$s"></a>%2$s</%1$s>', $this->tag ?? 'h3', $this->content, $this->id);
    }
}
