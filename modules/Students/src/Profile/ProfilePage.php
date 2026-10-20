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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Students\StudentGateway;

abstract class ProfilePage
{
    protected Session $session;

    protected string $gibbonSchoolYearID;
    protected string $gibbonPersonID;
    protected string $studentImage;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Set the student for the profile page
     * 
     * @param string $gibbonSchoolYearID The school year ID
     * @param string $gibbonPersonID The person ID
     * @param string|null $studentImage The student image URL (optional)
     * @return self
     */
    public function setStudent(string $gibbonSchoolYearID, string $gibbonPersonID, ?string $studentImage = ''): self
    {
        $this->gibbonSchoolYearID = $gibbonSchoolYearID;
        $this->gibbonPersonID = $gibbonPersonID;
        $this->studentImage = $studentImage ?? '';

        return $this;
    }

    /**
     * Fetch student data from database
     * 
     * @return array Student data or empty array if not found
     */
    protected function fetchStudentData(StudentGateway $studentGateway): array
    {
        return $studentGateway->selectActiveStudentByPerson(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID,
            false
        )->fetch();
    }

    public abstract function checkAccess(): bool;

    public abstract function getPageName(): string;

    public abstract function getOutput(): string;
}
