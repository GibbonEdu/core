<?php
/*
    Custom Gateway: StudentVanHoaGateway.php
    Purpose: Handles custom full name searches ("surname preferredName") separately from default logic.
*/

namespace Gibbon\Domain\Students;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;

class StudentVanHoaGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPerson';
    private static $primaryKey = 'gibbonPersonID';

    public function searchByFullName($searchVH, $gibbonSchoolYearID)
{
    $query = $this
        ->newQuery()
        ->distinct()
        ->from('gibbonPerson')
        ->cols([
            'gibbonPerson.gibbonPersonID', 
            'gibbonStudentEnrolmentID', 
            'gibbonPerson.title', 
            'gibbonPerson.preferredName', 
            'gibbonPerson.surname',
            'gibbonPerson.image_240',  
            'gibbonYearGroup.gibbonYearGroupID', 
            'gibbonYearGroup.nameShort AS yearGroup', 
            'gibbonFormGroup.gibbonFormGroupID', 
            'gibbonFormGroup.nameShort AS formGroup', 
            'gibbonStudentEnrolment.rollOrder', 
            'gibbonPerson.dateStart', 
            'gibbonPerson.dateEnd', 
            'gibbonPerson.status', 
            "'Student' as roleCategory"
        ])
        ->leftJoin(
            'gibbonStudentEnrolment', 
            'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
        ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
        ->leftJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
        ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $fullName = trim($searchVH);

    if (strpos($fullName, ',') !== false) {
        [$surname, $preferredName] = array_map('trim', explode(',', $fullName, 2));

        $query->where('gibbonPerson.surname LIKE :surname')
              ->where('gibbonPerson.preferredName LIKE :preferredName')
              ->bindValue('surname', "%$surname%")
              ->bindValue('preferredName', "%$preferredName%");
    } else {
        $parts = preg_split('/\s+/', $fullName);
        if (count($parts) >= 2) {
            $surname = implode(' ', array_slice($parts, 0, -1));
            $preferredName = end($parts);

            $query->where('gibbonPerson.surname LIKE :surname')
                  ->where('gibbonPerson.preferredName LIKE :preferredName')
                  ->bindValue('surname', "%$surname%")
                  ->bindValue('preferredName', "%$preferredName%");
        } else {
            $query->where('(gibbonPerson.surname LIKE :fullName OR gibbonPerson.preferredName LIKE :fullName)')
                  ->bindValue('fullName', "%$fullName%");
        }
    }
    return $this->runQuery($query, new QueryCriteria());
  }
}
