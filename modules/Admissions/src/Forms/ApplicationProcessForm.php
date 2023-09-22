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

namespace Gibbon\Module\Admissions\Forms;

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

/**
 * ApplicationProcessForm
 *
 * @version v24
 * @since   v24
 */
class ApplicationProcessForm extends Form implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function createForm($urlParams, FormBuilderInterface $formBuilder, $processes, FormDataInterface $formData)
    {
        $action = Url::fromHandlerRoute('modules/Admissions/applications_manage_editProcess.php');

        $form = Form::create('applicationProcess', $action);

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValues($urlParams);
        $form->addHiddenValue('tab', 6);

        foreach ($processes as $index => $process) {
            if (!$process->isEnabled($formBuilder)) continue;

            $form->addHiddenValue('applicationProcess['.$process->getProcessName().'][class]', $process->getProcessName());

            if ($viewClass = $process->getViewClass()) {
                $view = $this->getContainer()->get($viewClass);
                $row = $form->addRow();
                    $row->addLabel('applicationProcess['.$process->getProcessName().'][enabled]', $view->getName())->description($view->getDescription());
                    $row->addCheckbox('applicationProcess['.$process->getProcessName().'][enabled]')->setValue('Y');

                $view->configureEdit($form, $formData, 'applicationProcess['.$process->getProcessName().']');
            }
        }
        $form->addRow()->addSubmit();

        return $form;
    }
}
