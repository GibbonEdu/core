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

namespace Gibbon\Module\SystemAdmin\Forms;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Database\Updater;
use Gibbon\Database\Migrations\EngineUpdate;
use Gibbon\Contracts\Services\Session;

/**
 * @version v30
 * @since   v30
 */
class EngineUpdateForm
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param array<string, array<string, mixed>> $remoteVersions
     * @param array<string, mixed>|null $nextRelease
     */
    public function createForm(EngineUpdate $engineUpdate, Updater $updater): Form
    {
        $form = Form::create('innoDB', $this->session->get('absoluteURL').'/modules/System Admin/updateProcess.php?type=InnoDB');
        $form->addHiddenValue('address', $this->session->get('address'));

        $form->setTitle(__('Database Engine Migration'));
        $form->setDescription(__('Starting from v16, Gibbon is offering installations the option to migrate from MySQL\'s MyISAM engine to InnoDB, as a way to achieve greater reliability and performance.'));
        $output = '';

        if ($updater->isUpdateRequired() > 0) {
            $output .= Format::alert(__('Please run the database update, above, before proceeding with the Database Engine Migration.'), 'warning');
        } else {
            $currentEngine = $engineUpdate->checkEngine();

            if ($currentEngine == 'InnoDB') {
                $output .= Format::alert(sprintf(__('Your current default database engine is: %1$s'), $currentEngine), 'message');
            } else {
                $output .= Format::alert(sprintf(__('Your current default database engine is: %1$s.'), $currentEngine).' '.__('It is advised that you change your server config so that your default storage engine is set to InnoDB.'), 'warning');
            }

            $output .= Format::alert(sprintf(__('%1$s of your tables are not set to InnoDB.'), $engineUpdate->tablesTotal - $engineUpdate->tablesInnoDB).' <b>'.__('Click "Submit" below to continue. This operation cannot be undone: backup your entire database prior to running the update!'), 'warning');

            $form->addRow()->addSubmit();
        }

        $form->setDescription($form->getDescription().$output);

        return $form;
    }
}
