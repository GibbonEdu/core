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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;

class ParentFields extends AbstractFieldGroup
{
    public function __construct()
    {
        $this->fields = [
            'headingParent1PersonalData' => [
                'label' => __('Parent 1 Personal Data'),
                'type' => 'subheading',
            ],
            'parent1surname' => [
                'label' => __('Parent 1 Surname'),
                'type'  => 'varchar',
                'required' => 'Y',
            ],
            'parent1preferredName' => [
                'label' => __('Parent 1 Preferred Name'),
                'type'  => 'varchar',
                'required' => 'Y',
            ],
            'parent1relationship' => [
                'label' => __('Parent 1 Relationship'),
                'type'  => 'varchar',
                'required' => 'Y',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Parent fields enable the creation of parent users once an application has been accepted.');
    }
}
