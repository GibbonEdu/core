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

        return$this->db()->select($sql);
    }
}
