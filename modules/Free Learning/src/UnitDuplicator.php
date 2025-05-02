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

namespace Gibbon\Module\FreeLearning;

use Gibbon\Contracts\Services\Session;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitBlockGateway;
use Gibbon\Module\FreeLearning\Domain\UnitAuthorGateway;

class UnitDuplicator
{
    protected $filename = 'FreeLearningUnits';
    protected $data = [];
    protected $files = [];

    protected $unitGateway;
    protected $unitBlockGateway;
    protected $unitAuthorGateway;
    protected $session;

    public function __construct(UnitGateway $unitGateway, UnitBlockGateway $unitBlockGateway, UnitAuthorGateway $unitAuthorGateway, Session $session)
    {
        $this->unitGateway = $unitGateway;
        $this->unitBlockGateway = $unitBlockGateway;
        $this->unitAuthorGateway = $unitAuthorGateway;
        $this->session = $session;
    }

    public function duplicateUnit($freeLearningUnitID)
    {
        $partialFail = false ;

        $unit = $this->unitGateway->getByID($freeLearningUnitID);
        $unit['freeLearningUnitID'] = null;
        $unit['name'] .= " ".__('Copy');
        $unit['gibbonPersonIDCreator'] = $this->session->get('gibbonPersonID');
        $unit['timestamp'] = date('Y-m-d H:i:s');
        $freeLearningUnitIDNew = str_pad($this->unitGateway->insert($unit), 10, '0', STR_PAD_LEFT);

        if (is_numeric($freeLearningUnitIDNew)) {
            $blocks = $this->unitBlockGateway->selectBlocksByUnit($freeLearningUnitID)->fetchAll();
            foreach ($blocks as $block) {
                $block['freeLearningUnitBlockID'] = null;
                $block['freeLearningUnitID'] = $freeLearningUnitIDNew;
                $this->unitBlockGateway->insert($block);
            }

            $authors = $this->unitAuthorGateway->selectAuthorsByUnit($freeLearningUnitID)->fetchAll();
            foreach ($authors as $author) {
                $author['freeLearningUnitAuthorID'] = null;
                $author['freeLearningUnitID'] = $freeLearningUnitIDNew;
                $this->unitAuthorGateway->insert($author);
            }
        }
        else {
            $partialFail = true;
        }

        return $partialFail;
    }
}
