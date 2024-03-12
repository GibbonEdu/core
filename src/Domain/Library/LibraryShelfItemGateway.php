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
                'gibbonLibraryItem.imageLocation',
                'gibbonLibraryItem.status',
                'gibbonLibraryItem.locationDetail',
                'JSON_EXTRACT(gibbonLibraryItem.fields , "$.Description") as description',
                'gibbonSpace.name as spaceName',
            ])
            ->innerJoin('gibbonLibraryShelfItem', 'gibbonLibraryItem.gibbonLibraryItemID=gibbonLibraryShelfItem.gibbonLibraryItemID')
            ->leftJoin('gibbonSpace', 'gibbonLibraryItem.gibbonSpaceID = gibbonSpace.gibbonSpaceID')
            ->where('gibbonLibraryShelfItem.gibbonLibraryShelfID=:gibbonLibraryShelfID')
            ->bindValue('gibbonLibraryShelfID', $gibbonLibraryShelfID);

        return $this->runQuery($query, $criteria);
    }

    public function selectDefaultShelfTopBorrowed()
    {
        $data = ['timestampOut' => date('Y-m-d H:i:s', (time() - (60 * 60 * 24 * 30)))];
        $sql = "SELECT gibbonLibraryItem.name, gibbonLibraryItem.producer, gibbonLibraryItem.imageLocation, gibbonLibraryItem.status, gibbonLibraryItem.locationDetail, JSON_EXTRACT(gibbonLibraryItem.fields , \"$.Description\") as description, gibbonSpace.name as spaceName, COUNT( * ) AS count 
        FROM gibbonLibraryItem 
        JOIN gibbonLibraryItemEvent ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) 
        JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID)
        JOIN gibbonSpace ON (gibbonLibraryItem.gibbonSpaceID=gibbonSpace.gibbonSpaceID) 
        WHERE timestampOut>=:timestampOut 
        AND gibbonLibraryItem.borrowable='Y' 
        AND gibbonLibraryItemEvent.type='Loan' 
        AND gibbonLibraryType.name='Print Publication' 
        AND gibbonSpace.type='Library'
        AND gibbonLibraryItem.imageLocation IS NOT NULL
        AND gibbonLibraryItem.imageLocation <> ''
        GROUP BY producer, name 
        ORDER BY count DESC LIMIT 0, 20";

        return $this->db()->select($sql, $data);
    }

    public function selectDefaultShelfNewItems()
    {
        $sql = "SELECT gibbonLibraryItem.name, gibbonLibraryItem.producer, gibbonLibraryItem.imageLocation, gibbonLibraryItem.status, gibbonLibraryItem.locationDetail, JSON_EXTRACT(gibbonLibraryItem.fields , \"$.Description\") as description, gibbonSpace.name as spaceName
            FROM gibbonLibraryItem 
            JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) 
            JOIN gibbonSpace ON (gibbonLibraryItem.gibbonSpaceID=gibbonSpace.gibbonSpaceID) 
            WHERE gibbonLibraryItem.borrowable='Y' 
            AND gibbonLibraryType.name='Print Publication' 
            AND gibbonSpace.type='Library'
            AND gibbonLibraryItem.imageLocation IS NOT NULL
            AND gibbonLibraryItem.imageLocation <> ''
            ORDER BY timestampCreator DESC LIMIT 0, 20";

        return $this->db()->select($sql);
    }

    public function updateShelfContents($libraryShelfID, $field, $fieldValue)
    {
        $field = '$."'.$field.'"';
        $data = array('libraryShelfID' => $libraryShelfID, 'field' => $field, 'fieldValue' => $fieldValue);
        $sql = "SELECT gibbonLibraryItemID
        FROM gibbonLibraryItem
        WHERE gibbonLibraryItemID NOT IN
            (SELECT gibbonLibraryItemID 
             FROM gibbonLibraryShelfItem
            WHERE gibbonLibraryShelfID = :libraryShelfID)
        AND JSON_EXTRACT(gibbonLibraryItem.fields , :field) = :fieldValue
        AND gibbonLibraryItem.status IN ('Available','On Loan','Repair')
        AND gibbonLibraryItem.ownershipType <> 'Individual'
        AND gibbonLibraryItem.borrowable = 'Y'
        AND gibbonLibraryItem.gibbonLibraryItemIDParent IS NULL;";

        $newItems = $this->db()->select($sql, $data)->fetchAll();

        if(!empty($newItems)) {foreach($newItems as $item) {
            $this->insert([
                'gibbonLibraryItemID' 	    => $item['gibbonLibraryItemID'],
                'gibbonLibraryShelfID'  	=> $libraryShelfID
            ]);
        }}
        return $newItems;
    }
}
