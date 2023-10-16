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

namespace Gibbon\Module\Messenger;

use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\View\View;

/**
 * Signature
 *
 * @version v26
 * @since   v26
 */
class Signature
{
    protected $session;
    protected $db;
    protected $view;
    protected $settingGateway;
    protected $signatureTemplate;

    public function __construct(Session $session, Connection $db, View $view, SettingGateway $settingGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->view = $view;
        $this->settingGateway = $settingGateway;

        $this->signatureTemplate = $this->settingGateway->getSettingByScope('Messenger', 'signatureTemplate');
    }

    /**
     * Build an email signature for the specified user using the signature template.
     *
     * @param string $gibbonPersonID
     * @return string
     */
    public function getSignature($gibbonPersonID)
    {
        $signature = '';

        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = 'SELECT gibbonStaff.*, surname, firstName, preferredName, email FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
        $result = $this->db->select($sql, $data);

        if ($result->rowCount() == 1) {
            $values = $result->fetch();

            $signatureData = $values + [
                'organisationName' => $this->session->get('organisationName'),
            ];
            $signature = '<p></p>'.$this->view->fetchFromString($this->signatureTemplate, $signatureData);
        }

        return $signature;
    }
}
