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

namespace Gibbon\Forms\Builder\View;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class CreateINRecordView extends AbstractFormView
{
    public function getHeading() : string
    {
        return 'Student Records';
    }

    public function getName() : string
    {
        return __('Create Individual Needs Record');
    }

    public function getDescription() : string
    {
        return __('Create an individual needs record for the student.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('createINRecord', $this->getName())->description($this->getDescription());
            $row->addYesNo('createINRecord')->selected('N')->required();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading(__('Individual Needs Details'));

        if ($data->hasResult('gibbonINID')) {
            $list = [
                'gibbonINID' => $data->getResult('gibbonINID'),
                __('Special Educational Needs') => Format::yesNo($data->get('sen')),
            ];

            $col->addContent(Format::listDetails($list));
        } else {
            $col->addContent(Format::alert(__('{type} details could not be saved. Please check and create these records manually.', ['type' => __('Individual Needs')]), 'warning'));
        }
    }
}
