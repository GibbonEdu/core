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
use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;

/**
 * ApplicationMilestonesForm
 *
 * @version v24
 * @since   v24
 */
class ApplicationMilestonesForm extends Form
{
    protected $view;
    protected $session;
    protected $settingGateway;
    protected $userGateway;

    public function __construct(Session $session, View $view, SettingGateway $settingGateway, UserGateway $userGateway)
    {
        $this->view = $view;
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->userGateway = $userGateway;
    }

    public function createForm($urlParams, $milestones)
    {
        $action = Url::fromHandlerRoute('modules/Admissions/applications_manage_editMilestoneProcess.php');

        // Get the milestones
        $milestonesList = $this->settingGateway->getSettingByScope('Application Form', 'milestones');
        $milestonesList = array_map('trim', explode(',', $milestonesList));

        $milestonesData = json_decode($milestones ?? '', true);

        // Build the form
        $form = Form::create('applicationMilestones', $action);

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValues($urlParams);
        $form->addHiddenValue('tab', 1);
        $form->setClass('w-full blank');

        $col = $form->addRow()->addColumn();

        $checkIcon = $this->view->fetchFromTemplate('ui/icons.twig.html', ['icon' => 'check', 'iconClass' => 'w-6 h-6 fill-current mr-3 -my-2']);
        $crossIcon = $this->view->fetchFromTemplate('ui/icons.twig.html', ['icon' => 'cross', 'iconClass' => 'w-6 h-6 fill-current mr-3 -my-2']);

        foreach ($milestonesList as $index => $milestone) {
            $data = $milestonesData[$milestone] ?? [];
            $checked = !empty($data);
            $dateInfo = '';
            if ($checked) {
                $user = $this->userGateway->getByID($milestonesData[$milestone]['user'], ['preferredName', 'surname']);
                $dateInfo = Format::dateReadable($milestonesData[$milestone]['date']).' '.__('By').' '.Format::name('', $user['preferredName'], $user['surname'], 'Staff', false, true);
            }

            $description = '<div class="milestone flex-1 text-left"><span class="milestoneCheck '.($checked ? '' : 'hidden').'">'.$checkIcon.'</span><span class="milestoneCross '.($checked ? 'hidden' : '').'">'.$crossIcon.'</span><span class="text-base leading-normal">'.__($milestone).'</span></div><div class="flex-1 text-left">'.$dateInfo.'</div>';
            $col->addCheckbox("milestones[{$milestone}]")
                ->setValue('Y')
                ->checked($checked ? 'Y' : 'N')
                ->description($description)
                ->alignRight()
                ->setLabelClass('w-full flex items-center')
                ->addClass('milestoneInput border rounded p-4 my-2 '. ($checked ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'));
        }

        $form->addRow()->addSubmit();

        return $form;
    }
}
