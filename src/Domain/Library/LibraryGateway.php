<?php

namespace Gibbon\Domain\Library;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class LibraryGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'gibbonLibraryItem';
    private static $primaryKey = 'gibbonLibraryItemID';
    private static $searchableColumns = [];

    public function queryBrowseItems(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName() . ' as gli')
            ->cols([
                'gli.gibbonLibraryItemID',
                'gli.gibbonLibraryTypeID',
                'gli.id',
                'gli.name',
                'gli.producer',
                'gli.fields',
                'gli.vendor',
                'gli.purchaseDate',
                'gli.invoiceNumber',
                'gli.imageType',
                'gli.imageLocation',
                'gli.comment',
                'gli.gibbonSpaceID',
                'gs.name as spaceName',
                'gli.locationDetail',
                'gli.ownershipType',
                'gli.gibbonPersonIDOwnership',
                'gli.physicalCondition',
                'gli.bookable',
                'gli.borrowable',
                'gli.status',
                'gli.gibbonPersonIDStatusResponsible',
                'gli.gibbonPersonIDStatusRecorder',
                'gli.timestampStatus',
                'gli.returnExpected',
                'gli.returnAction',
                'gli.gibbonPersonIDReturnAction',
                'gli.gibbonPersonIDCreator',
                'gli.timestampCreator',
                'gli.gibbonPersonIDUpdate',
                'gli.timestampUpdate'
            ])
            ->innerJoin('gibbonLibraryType as glt', 'gli.gibbonLibraryTypeID = glt.gibbonLibraryTypeID')
            ->join('left', 'gibbonSpace as gs', 'gli.gibbonSpaceID = gs.gibbonSpaceID')
            ->where("gli.status IN ('Available','On Loan','Repair','Renewal')")
            ->where("gli.ownershipType <> 'Individual'")
            ->where("gli.borrowable = 'Y'");

        $criteria->addFilterRules([
            'name' => function ($query, $name) {
                return $query
                    ->where('gli.name LIKE :name')
                    ->bindValue('name', '%' . $name . '%');
            },
            'producer' => function ($query, $producer) {
                return $query
                    ->where('gli.producer LIKE :producer')
                    ->bindValue('producer', '%' . $producer . '%');
            },
            'category' => function ($query, $category) {
                return $query
                    ->where('gli.gibbonLibraryTypeID = :category')
                    ->bindValue('category', $category);
            },
            'collection' => function ($query, $collection) {
                return $query
                    ->where("gli.fields LIKE '%s:10:\"Collection\";s::collectionlen:\":collection\";%")
                    ->bindValue('collection', $collection)
                    ->bindvalue('collectionlen', strlen($collection));
            },
            'everything' => function ($query, $needle) {
                $globalSearch = "(";
                foreach ($query->getCols() as $col) {
                    $globalSearch .= $col . " LIKE :needle OR ";
                }
                $globalSearch = preg_replace("/ OR $/", ")", $globalSearch);
                return $query
                    ->where($globalSearch)
                    ->bindValue('needle', '%' . $needle . '%');
            }
        ]);
        return $this->runQuery($query, $criteria);
    }

    public function queryCatalog(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName() . ' as gli')
            ->cols([
                'gli.gibbonLibraryItemID',
                'gli.id',
                'gli.name',
                'gli.producer',
                'gli.vendor',
                'gs.name as spaceName',
                'gli.locationDetail',
                'gli.ownershipType',
                'gli.gibbonPersonIDOwnership',
                'gli.borrowable',
                'gli.status',
                'gp.title as title',
                'gp.preferredName',
                'gp.surname',
                'glt.name as itemType'
              ])
              ->innerJoin('gibbonLibraryType as glt','gli.gibbonLibraryTypeID = glt.gibbonLibraryTypeID')
            ->join('left', 'gibbonSpace as gs', 'gli.gibbonSpaceID = gs.gibbonSpaceID')
            ->join('left', 'gibbonPerson as gp', 'gli.gibbonPersonIDOwnership = gp.gibbonPersonID'); 

        $criteria->addFilterRules([
            'name' => function ($query, $name) {
                return $query
                    ->where('(gli.name LIKE :name OR gli.producer LIKE :name OR gli.id LIKE :name)')
                    ->bindValue('name', '%' . $name . '%');
            },
            'type' => function ($query, $type) {
              return $query
                ->where('gli.gibbonLibraryTypeID = :type')
                ->bindValue('type',$type);
            },
            'location' => function ($query,$location) {
              return $query
                ->where('gli.gibbonSpaceID = :location')
                ->bindValue('location',$location);
            },
            'status' => function($query,$status) {
              return $query
                ->where('gli.status = :status')
                ->bindValue('status',$status);
            },
            'owner' => function ($query,$owner) {
              return $query
                ->where('gli.gibbonPersonIDOwnership = :owner')
                ->bindValue('owner',$owner);
            },
            'typeSpecificFields' => function ($query,$typeSpecificFields) {
              return $query
                ->where('gli.fields LIKE :typeSpecificFields')
                ->bindValue('typeSpecificFields','%'.$typeSpecificFields.'%');
            }
        ]);
        return $this->runQuery($query, $criteria);
    }
}
