<?php

namespace Gibbon\Domain\Finance;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class InvoiceeGateway extends QueryableGateway
{
    use TableAware;
    private static $primaryKey = 'gibbonFinanceInvoiceeID';
    private static $tableName = 'gibbonFinanceInvoicee';
    private static $searchableColumns = [];

    public function queryInvoicees(QueryCriteria $criteria)
    {
      $query = $this
        ->newQuery()
        ->from('gibbonFinanceInvoicee')
        ->innerJoin('gibbonPerson','gibbonPerson.gibbonPersonID = gibbonFinanceInvoicee.gibbonPErsonID')
        ->where("NOT surname = ''")
        ->orderBy([
          'surname',
          'preferredName'
        ])
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

      $criteria->addFilterRules([
        'search' => function($query,$search)
        {
          return $query
            ->where(
              '(gibbonPerson.preferredName LIKE :search OR
              gibbonPerson.surname LIKE :search OR
              gibbonPerson.username LIKE :search')
            ->bindValue('search','%'.$search.'%');
        },
        'allUsers' => function($query,$allUsers)
        {
          if($allUsers == true)
          {
            return $query
              ->where("status = 'Full'");
          }
          return $query;
        }
      ]);

      return $this->runQuery($query,$criteria);
    }

}
