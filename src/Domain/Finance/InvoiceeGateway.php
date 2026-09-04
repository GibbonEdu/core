<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\DataSet;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

class InvoiceeGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $primaryKey = 'gibbonFinanceInvoiceeID';
    private static $tableName = 'gibbonFinanceInvoicee';
    private static $searchableColumns = ['preferredName', 'surname', 'username'];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['companyName' => null,'companyContact' => null,'companyAddress' => null,'companyEmail' => null,'companyCCFamily' => null,'companyPhone' => null];

    public function queryInvoicees(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonFinanceInvoicee')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID = gibbonFinanceInvoicee.gibbonPersonID')
            ->where("NOT surname = ''")
            ->cols([
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.title',
                'gibbonPerson.dateStart',
                'gibbonPerson.dateEnd',
                'gibbonPerson.status',
                'gibbonFinanceInvoicee.invoiceTo',
                'gibbonFinanceInvoicee.gibbonFinanceInvoiceeID',
                'gibbonFinanceInvoicee.companyAll',
                "IF(
            gibbonPerson.dateStart <= CURRENT_TIMESTAMP OR
            gibbonPerson.dateStart IS NULL,'Y','N'
          ) AS started",
                "IF(
            gibbonPerson.dateEnd >= CURRENT_TIMESTAMP OR
            gibbonPerson.dateEnd IS NULL,'N','Y'
          ) AS ended"
            ]);

        if (!$criteria->hasFilter('allUsers')) {
            $query->where("gibbonPerson.status = 'Full'")
                    ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
                    ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
                    ->bindValue('today', date('Y-m-d'));
        }


        return $this->runQuery($query, $criteria);
    }

    public function selectStudentsWithNoInvoicee()
    {
        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFinanceInvoiceeID 
                FROM gibbonPerson 
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) 
                LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonFinanceInvoiceeID IS NULL";

        return $this->db()->select($sql);
    }

    public function selectInvoiceesDetails() {
        $sql = "SELECT username, surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";

        return $this->db()->select($sql);
    }

    public function selectInvoiceeByAdultID($gibbonPersonID) {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamily.name as familyName, child.surname, child.preferredName, child.gibbonPersonID, gibbonFinanceInvoicee.gibbonFinanceInvoiceeID FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson as child ON (gibbonFamilyChild.gibbonPersonID=child.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=child.gibbonPersonID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.childDataAccess='Y' AND child.status='Full' ORDER BY gibbonFamily.name, child.surname, child.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectInvoiceeByID($gibbonFinanceInvoiceeID) {
        $data = ['gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID];
        $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFinanceInvoicee.* FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

     public function selectInvoiceeByFamilyID($gibbonFamilyID) {
        $data = ['gibbonFamilyID' => $gibbonFamilyID];
        $sql = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID, gibbonFinanceInvoicee.* FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID";

        return $this->db()->select($sql, $data);
    }
}
