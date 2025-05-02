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
use DOMDocument;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitBlockGateway;
use Gibbon\Module\FreeLearning\Domain\UnitAuthorGateway;

class UnitExporter
{
    protected $filename = 'FreeLearningUnits';
    protected $data = [];
    protected $files = [];

    protected $unitGateway;
    protected $unitBlockGateway;
    protected $unitAuthorGateway;
    protected $yearGroupGateway;
    protected $roleGateway;
    protected $session;

    public function __construct(UnitGateway $unitGateway, UnitBlockGateway $unitBlockGateway, UnitAuthorGateway $unitAuthorGateway, YearGroupGateway $yearGroupGateway, Session $session, RoleGateway $roleGateway)
    {
        $this->unitGateway = $unitGateway;
        $this->unitBlockGateway = $unitBlockGateway;
        $this->unitAuthorGateway = $unitAuthorGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->roleGateway = $roleGateway;
        $this->session = $session;
    }

    public function setFilename($filename)
    {
        $this->filename = preg_replace('/[^a-zA-Z0-9]/', '', $filename);
    }

    public function addUnitToExport($freeLearningUnitID)
    {
        $unit = $this->unitGateway->getByID($freeLearningUnitID);

        if (empty($unit)) return;

        // Extract logo image
        if (!empty($unit['logo'])) {
            $logoPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($unit['logo'], PHP_URL_PATH);
            $this->files[] = file_exists($logoPath)
                ? ['type' => 'path', 'location' => $logoPath]
                : ['type' => 'url', 'location' => $unit['logo']];

            $unit['logo'] = basename($logoPath);
        }

        //Deal with schoolMentorCustomRole
        if (!empty($unit['schoolMentorCustomRole']) && $unit['schoolMentorCustomRole'] > 0) {
            $role = $this->roleGateway->getRoleByID($unit['schoolMentorCustomRole']);
            $unit['schoolMentorCustomRole'] = $role["name"];
        }

        // Extract images from unit outline
        $dom = new DOMDocument();
        @$dom->loadHTML($unit['outline']);
        foreach ($dom->getElementsByTagName('img') as $node) {
            $src = $node->getAttribute('src');
            $srcPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($src, PHP_URL_PATH);
            $this->files[] = file_exists($srcPath)
                ? ['type' => 'path', 'location' => $srcPath]
                : ['type' => 'url', 'location' => $src];

            $unit['outline'] = str_replace($src, basename($src), $unit['outline']);
        }

        // Extract images from blocks
        $blocks = $this->unitBlockGateway->selectBlocksByUnit($freeLearningUnitID)->fetchAll();
        foreach ($blocks as $index => $block) {
            $dom = new DOMDocument();
            if (empty($blocks[$index]['contents'])) continue;
            
            @$dom->loadHTML($blocks[$index]['contents']);
            foreach ($dom->getElementsByTagName('img') as $node) {
                $src = $node->getAttribute('src');
                $srcPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($src, PHP_URL_PATH);
                $this->files[] = file_exists($srcPath)
                    ? ['type' => 'path', 'location' => $srcPath]
                    : ['type' => 'url', 'location' => $src];

                $blocks[$index]['contents'] = str_replace($src, basename($src), $blocks[$index]['contents']);
            }
        }

        // Convert gibbonYearGroupIDMinimum to year group nameShort
        if (!empty($unit['gibbonYearGroupIDMinimum'])) {
            $yearGroup = $this->yearGroupGateway->getByID($unit['gibbonYearGroupIDMinimum']);
            $unit['gibbonYearGroupIDMinimum'] = (!empty($yearGroup['nameShort'])) ? $yearGroup['nameShort'] : null;
        }

        // Add unit details to data array
        $this->data['units'][] = [
            'name' => $unit['name'],
            'unit' => $unit,
            'prerequisites' => $this->unitGateway->selectPrerequisiteNamesByUnitID($unit['freeLearningUnitID'])->fetchAll(\PDO::FETCH_COLUMN),
            'blocks' => $blocks,
            'authors' => $this->unitGateway->selectUnitAuthorsByID($unit['freeLearningUnitID'])->fetchAll()
        ]; 
    }

    public function output()
    {
        // Create the zip archive and add contents
        $filepath = tempnam(sys_get_temp_dir(), 'freelearning');
        $zip = new ZipArchive();
        $zip->open($filepath, ZipArchive::CREATE);

        // Add Files
        foreach ($this->files as $file) {
            if ($file['type'] == 'url') {
                // Handle images from url by downloading them first
                $context  = stream_context_create(['ssl' => ['verify_peer' => false]]);
                $fileContents = @file_get_contents($file['location'], false, $context);

                if (empty($fileContents)) continue;

                $zip->addFromString('files/'.basename($file['location']), $fileContents);
                $this->data['files'][] = basename($file['location']);

            } elseif ($file['type'] == 'path') {
                // Handle local images by adding them directly
                if (!file_exists($file['location'])) continue;

                $zip->addFile($file['location'], 'files/'.basename($file['location']));
                $this->data['files'][] = basename($file['location']);
            }
        }

        // Add Data
        $zip->addFromString('data.json', json_encode($this->data, JSON_PRETTY_PRINT));

        $zip->close();

        // Stream the zip archive for downloading
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.htmlentities($this->filename).'.zip"');
        header('Content-Transfer-Encoding: base64');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        echo file_get_contents($filepath);

        unlink($filepath);
    }
}
