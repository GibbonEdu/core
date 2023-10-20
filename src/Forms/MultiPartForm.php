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

namespace Gibbon\Forms;

use Gibbon\Forms\Form;

/**
 * class MultiPartForm extends Form

 *
 * @version v23
 * @since   v23
 */
class MultiPartForm extends Form
{
    protected $pages = [];
    protected $currentPage = 0;
    protected $maxPage = 0;

    /**
     * Create a form with the default factory and renderer.
     * @param    string  $id
     * @param    string  $action
     * @param    string  $method
     * @param    string  $class
     * @return   object  Form object
     */
    public static function create($id, $action, $method = 'post', $class = 'smallIntBorder fullWidth standardForm')
    {
        global $container;

        $form = $container->get(MultiPartForm::class)
            ->setID($id)
            ->setClass($class)
            ->setAction($action)
            ->setMethod($method);

        return $form;
    }

    public function addPage(int $pageNumber, string $pageName, string $pageUrl = null)
    {
        $this->pages[$pageNumber] = [
            'name'   => $pageName,
            'number' => $pageNumber,
            'url'    => $pageUrl,
        ];

        return $this;
    }

    public function addPages(array $pages)
    {
        foreach ($pages as $pageNumber => $pageName) {
            $this->addPage($pageNumber, $pageName);
        }
    }

    public function hasPages()
    {
        return count($this->pages) > 0;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function setCurrentPage(int $pageNumber)
    {
        $this->currentPage = $pageNumber;

        return $this;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function setMaxPage(int $pageNumber)
    {
        $this->maxPage = $pageNumber;

        return $this;
    }

    public function getMaxPage()
    {
        return $this->maxPage;
    }
}
