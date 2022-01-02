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

namespace Gibbon\Forms\Builder\Processor;

use Gibbon\Forms\Builder\Processor\PreviewFormProcessor;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;


class FormProcessorFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getFormTypes()
    {
        return [
            'Application'      => __('Application'),
            'Post-application' => __('Post-application'),
            'Student'          => __('Student'),
            'Parent'           => __('Parent'),
            'Family'           => __('Family'),
            'Staff'            => __('Staff'),
        ];
    }

    public function getProcessor(string $formType)
    {
        $processor = null;

        switch ($formType) {
            default:
                $processor = $this->getContainer()->get(PreviewFormProcessor::class);
        }

        return $processor;
    }
}
