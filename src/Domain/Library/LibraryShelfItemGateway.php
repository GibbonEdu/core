<?php

namespace Gibbon\Domain\Library;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class LibraryShelfItemGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'gibbonLibraryShelfItem';
    private static $primaryKey = 'gibbonLibraryShelfItemID';
    private static $searchableColumns = [];

    public function insertShelfItem($gibbonLibraryItemID, $gibbonLibraryShelfID) {
        return $this->insert([
            'gibbonLibraryItemID' 	    => $gibbonLibraryItemID,
            'gibbonLibraryShelfID'  	=> $gibbonLibraryShelfID
        ]);
    }


    public function queryItemsByShelf($gibbonLibraryShelfID, QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonLibraryItem')
            ->cols([
                'gibbonLibraryShelfItem.gibbonLibraryShelfItemID', 
                'gibbonLibraryShelfItem.gibbonLibraryItemID', 
                'gibbonLibraryItem.name', 
                'gibbonLibraryItem.producer',
            ])
            ->innerJoin('gibbonLibraryShelfItem', 'gibbonLibraryItem.gibbonLibraryItemID=gibbonLibraryShelfItem.gibbonLibraryItemID')
            ->where('gibbonLibraryShelfItem.gibbonLibraryShelfID=:gibbonLibraryShelfID')
            ->bindValue('gibbonLibraryShelfID', $gibbonLibraryShelfID);

        return $this->runQuery($query, $criteria);
    }


}
