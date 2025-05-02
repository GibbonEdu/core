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

use ZipArchive;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitBlockGateway;
use Gibbon\Module\FreeLearning\Domain\UnitAuthorGateway;
use Gibbon\Module\FreeLearning\Domain\UnitPrerequisiteGateway;

class UnitImporter
{
    protected $gibbonDepartmentIDList;
    protected $course;

    protected $override = false;
    protected $delete = false;
    protected $files;

    protected $unitGateway;
    protected $unitBlockGateway;
    protected $unitAuthorGateway;
    protected $unitPrerequisiteGateway;
    protected $yearGroupGateway;
    protected $roleGateway;
    protected $session;

    public function __construct(UnitGateway $unitGateway, UnitBlockGateway $unitBlockGateway, UnitAuthorGateway $unitAuthorGateway, UnitPrerequisiteGateway $unitPrerequisiteGateway, YearGroupGateway $yearGroupGateway, Session $session, RoleGateway $roleGateway)
    {
        $this->unitGateway = $unitGateway;
        $this->unitBlockGateway = $unitBlockGateway;
        $this->unitAuthorGateway = $unitAuthorGateway;
        $this->unitPrerequisiteGateway = $unitPrerequisiteGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->roleGateway = $roleGateway;
        $this->session = $session;
    }

    public function setOverride($override)
    {
        $this->override = $override;
    }

    public function setDelete($delete)
    {
        $this->delete = $delete;
    }

    public function setDefaults($gibbonDepartmentIDList = null, $course = null)
    {
        $this->gibbonDepartmentIDList = $gibbonDepartmentIDList;
        $this->course = $course;
    }

    public function importFromFile($zipFilePath) : bool
    {
        $zip = new ZipArchive();
        $zip->open($zipFilePath);

        $json = $zip->getFromName('data.json');
        $data = json_decode($json, true);

        if (empty($data['units'])) return false;

        // Upload all necessary files first
        $this->files = $this->uploadImportedFiles($data, $zipFilePath);

        // Import Units
        foreach ($data['units'] as $index => $unit) {
            $existingUnit = $this->unitGateway->selectBy(['name' => $unit['name']])->fetch();

            // Skip existing units if override is not enabled
            if (!$this->override && !empty($existingUnit)) {
                unset($data['units'][$index]);
                continue;
            }

            // Update certain values before importing
            $unit = $this->updateUnitDetails($unit);

            // Add or update the unit in the database
            if (!empty($existingUnit)) {
                $freeLearningUnitID = $existingUnit['freeLearningUnitID'];
                $this->unitGateway->update($freeLearningUnitID, $unit['unit']);
            } else {
                $freeLearningUnitID = $this->unitGateway->insert($unit['unit']);
            }

            // Add blocks and authors
            $this->addUnitBlocks($unit['blocks'], $freeLearningUnitID, $existingUnit);
            $this->addUnitAuthors($unit['authors'], $freeLearningUnitID, $existingUnit);
        }

        // Connect prerequisites after all units have been imported
        $this->connectUnitPrerequisites($data);

        $zip->close();

        unlink($zipFilePath);

        return true;
    }

    protected function uploadImportedFiles($data, $zipFilePath) {
        $this->files = [];

        foreach ($data['files'] as $filename) {
            $uploadsFolder = 'uploads/'.date('Y').'/'.date('m');
            $destinationPath = $this->session->get('absolutePath').'/'.$uploadsFolder.'/'.$filename;

            if (@copy('zip://'.$zipFilePath.'#files/'.$filename, $destinationPath)) {
                $this->files[$filename] = $this->session->get('absoluteURL').'/'.$uploadsFolder.'/'.$filename;
            }
        }

        return $this->files;
    }

    protected function updateUnitDetails($unit) {
        // Reset un-importable values
        $unit['unit']['gibbonPersonIDCreator'] = $this->session->get('gibbonPersonID');

        // Apply default values
        if (!empty($this->gibbonDepartmentIDList)) $unit['unit']['gibbonDepartmentIDList'] = $this->gibbonDepartmentIDList;
        if (!empty($this->course)) $unit['unit']['course'] = $this->course;

        //Deal with schoolMentorCustomRole
        if (!empty($unit['unit']['schoolMentorCustomRole'])) {
            $role = $this->roleGateway->selectBy(['name' => $unit['unit']['schoolMentorCustomRole']])->fetch();
            $unit['unit']['schoolMentorCustomRole'] = (!empty($role) && $role["gibbonRoleID"] > 0) ? $role["gibbonRoleID"] : null;
        }

        // Get the uploaded logo URL
        if (!empty($unit['unit']['logo']) && !empty($this->files[$unit['unit']['logo']])) {
            $unit['unit']['logo'] = $this->files[$unit['unit']['logo']] ?? '';
        }

        // Update unit outline to point to new file locations
        foreach ($this->files as $filename => $url) {
            $unit['unit']['outline'] = str_replace($filename, $url, $unit['unit']['outline']);
        }

        // Convert year group nameShort back to gibbonYearGroupIDMinimum
        if (!empty($unit['unit']['gibbonYearGroupIDMinimum'])) {
            $yearGroup = $this->yearGroupGateway->selectBy(['nameShort' => $unit['unit']['gibbonYearGroupIDMinimum']])->fetch();
            $unit['unit']['gibbonYearGroupIDMinimum'] = (!empty($yearGroup['gibbonYearGroupID'])) ? $yearGroup['gibbonYearGroupID'] : null;
        }

        return $unit;
    }

    protected function addUnitBlocks($blocks, $freeLearningUnitID, $existingUnit)
    {
        if ($this->delete) {
            $this->unitBlockGateway->deleteWhere(['freeLearningUnitID' => $freeLearningUnitID]);
        }

        foreach ($blocks as $block) {
            $block['freeLearningUnitID'] = $freeLearningUnitID;
            // Update uploaded files to point to their new file location
            foreach ($this->files as $filename => $url) {
                $block['contents'] = str_replace($filename, $url, $block['contents']);
            }

            if ($this->delete) {
                $this->unitBlockGateway->insert($block);
            }
            else {
                if (!empty($existingUnit)) {
                    $existingBlock = $this->unitBlockGateway->selectBy([
                        'freeLearningUnitID' => $existingUnit['freeLearningUnitID'],
                        'title' => $block['title'],
                    ])->fetch();
                }

                if (!empty($existingBlock)) {
                    $this->unitBlockGateway->update($existingBlock['freeLearningUnitBlockID'], $block);
                } else {
                    $this->unitBlockGateway->insert($block);
                }
            }
        }
    }

    protected function addUnitAuthors($authors, $freeLearningUnitID, $existingUnit)
    {
        foreach ($authors as $author) {
            $author['freeLearningUnitID'] = $freeLearningUnitID;
            $author['gibbonPersonID'] = null;
            unset($author['title']);

            if (!empty($existingUnit)) {
                $existingAuthor = $this->unitAuthorGateway->selectBy([
                    'freeLearningUnitID' => $existingUnit['freeLearningUnitID'],
                    'surname' => $author['surname'],
                    'preferredName' => $author['preferredName'],
                ])->fetch();
            }

            if (!empty($existingAuthor)) {
                $this->unitAuthorGateway->update($existingAuthor['freeLearningUnitAuthorID'], $author);
            } else {
                $this->unitAuthorGateway->insert($author);
            }
        }
    }

    protected function connectUnitPrerequisites($data)
    {
        foreach ($data['units'] as $unit) {
            if (empty($unit['prerequisites'])) continue;

            $existingUnit = $this->unitGateway->selectBy(['name' => $unit['name']])->fetch();

            // Remove prerequisites for this unit
            if (!empty($existingUnit)) {
                $this->unitPrerequisiteGateway->deleteWhere(['freeLearningUnitID' => $existingUnit['freeLearningUnitID']]);
            }

            foreach ($unit['prerequisites'] as $prerequisite) {
                $freeLearningUnitIDPrerequisite = $this->unitGateway->selectBy(['name' => $prerequisite])->fetch()['freeLearningUnitID'];
                $this->unitPrerequisiteGateway->insert(['freeLearningUnitID' => $existingUnit['freeLearningUnitID'], 'freeLearningUnitIDPrerequisite' => $freeLearningUnitIDPrerequisite]);
            }
        }
    }
}
