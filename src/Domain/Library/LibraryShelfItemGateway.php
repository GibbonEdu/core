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

    public function selectItemsByShelf($gibbonLibraryShelfID)
    {
        $data = array('gibbonLibraryShelfID' => $gibbonLibraryShelfID);
        $sql = "SELECT gibbonLibraryShelfItem.gibbonLibraryShelfItemID, gibbonLibraryShelfItem.gibbonLibraryItemID, gibbonLibraryItem.name, gibbonLibraryItem.producer
                FROM gibbonLibraryItem
                JOIN gibbonLibraryShelfItem ON (gibbonLibraryItem.gibbonLibraryItemID=gibbonLibraryShelfItem.gibbonLibraryItemID)
                WHERE gibbonLibraryShelfItem.gibbonLibraryShelfID=:gibbonLibraryShelfID";
                //ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }


}
