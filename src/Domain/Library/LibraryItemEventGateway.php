<?php

namespace Gibbon\Domain\Library;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class LibraryItemEventGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'gibbonLibraryItemEvent';
    private static $primaryKey = 'gibbonLibraryItemEventID';
    private static $searchableColumns = [];

    public function getActiveEventByBorrower($gibbonLibraryItemID, $gibbonPersonIDStatusResponsible)
    {
        $data = ['gibbonLibraryItemID' => $gibbonLibraryItemID, 'gibbonPersonIDStatusResponsible' => $gibbonPersonIDStatusResponsible];
        $sql = "SELECT * FROM gibbonLibraryItemEvent 
            WHERE gibbonLibraryItemID=:gibbonLibraryItemID 
            AND gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible
            AND (gibbonLibraryItemEvent.status='On Loan' OR gibbonLibraryItemEvent.status='Reserved')";

        return $this->db()->selectOne($sql, $data);
    }
}
