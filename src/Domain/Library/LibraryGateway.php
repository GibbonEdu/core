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

    public function queryLendingDetail(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonLibraryItemEvent')
            ->cols([
                'gibbonPersonResponsible.gibbonPersonID as responsiblePersonID',
                'gibbonPersonResponsible.title as responsiblePersonTitle',
                'gibbonPersonResponsible.preferredName as responsiblePersonPreferredName',
                'gibbonPersonResponsible.surname as responsiblePersonSurname',
                'gibbonPersonResponsible.image_240 as responsiblePersonImage',
                'gibbonPersonOut.gibbonPersonId as outPersonID',
                'gibbonPersonOut.title as outPersonTitle',
                'gibbonPersonOut.preferredName as outPersonPreferredName',
                'gibbonPersonOut.surname as outPersonSurname',
                'gibbonPersonOut.image_240 as outPersonImage',
                'gibbonPersonIn.gibbonPersonID as inPersonID',
                'gibbonPersonIn.title as inPersonTitle',
                'gibbonPersonIn.preferredName as inPersonPreferredName',
                'gibbonPersonIn.surname as inPersonSurname',
                'gibbonPersonIn.image_240 as inPersonImage',
                'gibbonLibraryItemEvent.gibbonPersonIDStatusResponsible',
                'gibbonLibraryItemEvent.gibbonLibraryItemID',
                'gibbonLibraryItemEvent.gibbonLibraryItemEventID',
                'CONVERT(gibbonLibraryItemEvent.timestampOut,DATE) AS timestampOut',
                'CONVERT(gibbonLibraryItemEvent.timestampReturn,DATE) AS timestampReturn',
                'gibbonLibraryItemEvent.status',
                'gibbonLibraryItemEvent.returnExpected',
                'gibbonLibraryItemEvent.returnAction',
                'gibbonLibraryItemEvent.gibbonPersonIDOut',
                "IF(gibbonLibraryItemEvent.returnExpected <= CURRENT_TIMESTAMP,'Y','N') as pastDue"
            ])
            ->leftJoin('gibbonPerson as gibbonPersonResponsible', 'gibbonLibraryItemEvent.gibbonPersonIDStatusResponsible = gibbonPersonResponsible.gibbonPersonID')
            ->leftJoin('gibbonPerson as gibbonPersonOut', 'gibbonLibraryItemEvent.gibbonPersonIDOut = gibbonPersonOut.gibbonPersonID')
            ->leftJoin('gibbonPerson as gibbonPersonIn', 'gibbonLibraryItemEvent.gibbonPersonIDIn = gibbonPersonIn.gibbonPersonID');


        $criteria->addFilterRules([
            'gibbonLibraryItemID' => function ($query, $itemid) {
                return $query
                    ->where('gibbonLibraryItemEvent.gibbonLibraryItemID = :itemid')
                    ->bindValue('itemid', $itemid);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryLending(QueryCriteria $criteria)
    {
      
        $query = $this
        ->newQuery()
        ->from($this->getTableName())
        ->join('left', 'gibbonLibraryType', 'gibbonLibraryType.gibbonLibraryTypeID = gibbonLibraryItem.gibbonLibraryTypeID')
        ->join('left', 'gibbonSpace', 'gibbonSpace.gibbonSpaceId = gibbonLibraryItem.gibbonSpaceID')
        ->join('left', 'gibbonPerson', 'gibbonLibraryItem.gibbonPersonIDStatusResponsible = gibbonPerson.gibbonPersonID')
        ->cols([
          "gibbonLibraryItem.id",
          "gibbonLibraryItem.name",
          "gibbonLibraryItem.producer",
          "gibbonLibraryType.name as 'typeName'",
          "gibbonLibraryItem.gibbonLibraryItemID",
          "gibbonLibraryItem.gibbonLibraryTypeID",
          "gibbonLibraryItem.gibbonSpaceID",
          "gibbonLibraryItem.status",
          "gibbonLibraryItem.returnExpected",
          "gibbonLibraryItem.gibbonPersonIDStatusResponsible",
          "gibbonLibraryItem.timestampStatus",
          "gibbonPerson.title",
          "gibbonPerson.preferredName",
          "gibbonPerson.surname",
          "gibbonPerson.firstName",
          "gibbonSpace.name as 'spaceName'",
          "gibbonLibraryItem.locationDetail",
          "IF(gibbonLibraryItem.status = 'On Loan' AND gibbonLibraryItem.returnExpected < CURRENT_TIMESTAMP(),'Y','N') as 'pastDue'"
        ])
        ->where("gibbonLibraryItem.status IN ('Available','Repair','Reserved','On Loan')")
        ->where("ownershipType != 'Individual'")
        ->where("gibbonLibraryItem.borrowable = 'Y'");

        $criteria->addFilterRules([
        'name' => function ($query, $name) {
            return $query
            ->where('(gibbonLibraryItem.name like :name OR gibbonLibraryItem.producer like :name OR gibbonLibraryItem.id like :name)')
            ->bindValue('name', '%'.$name.'%');
        },
        'gibbonLibraryTypeID' => function ($query, $typeid) {
            return $query
            ->where('gibbonLibraryItem.gibbonLibraryTypeID = :typeid')
            ->bindValue('typeid', $typeid);
        },
        'gibbonSpaceID' => function ($query, $spaceid) {
            return $query
            ->where('gibbonLibraryItem.gibbonSpaceID = :spaceid')
            ->bindValue('spaceid', $spaceid);
        },
        'status' => function ($query, $status) {
            return $query
            ->where('gibbonLibraryItem.status = :status')
            ->bindValue('status', $status);
        }
        ]);
        return $this->runQuery($query, $criteria);
    }

    public function queryBrowseItems(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonLibraryItem.gibbonLibraryItemID',
                'gibbonLibraryItem.gibbonLibraryTypeID',
                'gibbonLibraryItem.id',
                'gibbonLibraryItem.name',
                'gibbonLibraryItem.producer',
                'gibbonLibraryItem.fields',
                'gibbonLibraryItem.vendor',
                'gibbonLibraryItem.purchaseDate',
                'gibbonLibraryItem.invoiceNumber',
                'gibbonLibraryItem.imageType',
                'gibbonLibraryItem.imageLocation',
                'gibbonLibraryItem.comment',
                'gibbonLibraryItem.gibbonSpaceID',
                'gibbonSpace.name as spaceName',
                'gibbonLibraryItem.locationDetail',
                'gibbonLibraryItem.ownershipType',
                'gibbonLibraryItem.gibbonPersonIDOwnership',
                'gibbonLibraryItem.physicalCondition',
                'gibbonLibraryItem.bookable',
                'gibbonLibraryItem.borrowable',
                'gibbonLibraryItem.status',
                'gibbonLibraryItem.gibbonPersonIDStatusResponsible',
                'gibbonLibraryItem.gibbonPersonIDStatusRecorder',
                'gibbonLibraryItem.timestampStatus',
                'gibbonLibraryItem.returnExpected',
                'gibbonLibraryItem.returnAction',
                'gibbonLibraryItem.gibbonPersonIDReturnAction',
                'gibbonLibraryItem.gibbonPersonIDCreator',
                'gibbonLibraryItem.timestampCreator',
                'gibbonLibraryItem.gibbonPersonIDUpdate',
                'gibbonLibraryItem.timestampUpdate'
            ])
            ->innerJoin('gibbonLibraryType', 'gibbonLibraryItem.gibbonLibraryTypeID = gibbonLibraryType.gibbonLibraryTypeID')
            ->join('left', 'gibbonSpace', 'gibbonLibraryItem.gibbonSpaceID = gibbonSpace.gibbonSpaceID')
            ->where("gibbonLibraryItem.status IN ('Available','On Loan','Repair')")
            ->where("gibbonLibraryItem.ownershipType <> 'Individual'")
            ->where("gibbonLibraryItem.borrowable = 'Y'");

        $criteria->addFilterRules([
            'name' => function ($query, $name) {
                return $query
                    ->where('gibbonLibraryItem.name LIKE :name')
                    ->bindValue('name', '%' . $name . '%');
            },
            'producer' => function ($query, $producer) {
                return $query
                    ->where('gibbonLibraryItem.producer LIKE :producer')
                    ->bindValue('producer', '%' . $producer . '%');
            },
            'category' => function ($query, $category) {
                return $query
                    ->where('gibbonLibraryItem.gibbonLibraryTypeID = :category')
                    ->bindValue('category', $category);
            },
            'collection' => function ($query, $collection) {
                return $query
                    ->where("gibbonLibraryItem.fields LIKE CONCAT('%s:10:\"Collection\";s:', :collectionlen, ':\"', :collection, '\";%')")
                    ->bindValue('collection', $collection)
                    ->bindValue('collectionlen', strlen($collection));
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

    public function queryCatalog(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonLibraryItem.gibbonLibraryItemID',
                'gibbonLibraryItem.id',
                'gibbonLibraryItem.name',
                'gibbonLibraryItem.producer',
                'gibbonLibraryItem.vendor',
                'gibbonSpace.name as spaceName',
                'gibbonLibraryItem.locationDetail',
                'gibbonLibraryItem.ownershipType',
                'gibbonLibraryItem.gibbonPersonIDOwnership',
                'gibbonLibraryItem.borrowable',
                'gibbonLibraryItem.status',
                'gibbonPerson.title as title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonLibraryType.name as itemType',
                'responsible.title as titleResponsible',
                'responsible.surname as surnameResponsible',
                'responsible.preferredName as preferredNameResponsible',
                'gibbonRollGroup.nameShort as rollGroup',
              ])
            ->innerJoin('gibbonLibraryType', 'gibbonLibraryItem.gibbonLibraryTypeID = gibbonLibraryType.gibbonLibraryTypeID')
            ->leftJoin('gibbonSpace', 'gibbonLibraryItem.gibbonSpaceID = gibbonSpace.gibbonSpaceID')
            ->leftJoin('gibbonPerson', 'gibbonLibraryItem.gibbonPersonIDOwnership = gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPerson as responsible', 'responsible.gibbonPersonID=gibbonLibraryItem.gibbonPersonIDStatusResponsible')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=responsible.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonRollGroup', 'gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'name' => function ($query, $name) {
                return $query
                    ->where('(gibbonLibraryItem.name LIKE :name OR gibbonLibraryItem.producer LIKE :name OR gibbonLibraryItem.id LIKE :name)')
                    ->bindValue('name', '%' . $name . '%');
            },
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonLibraryItem.gibbonLibraryTypeID = :type')
                    ->bindValue('type', $type);
            },
            'location' => function ($query, $location) {
                return $query
                    ->where('gibbonLibraryItem.gibbonSpaceID = :location')
                    ->bindValue('location', $location);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonLibraryItem.status = :status')
                    ->bindValue('status', $status);
            },
            'owner' => function ($query, $owner) {
                return $query
                    ->where('gibbonLibraryItem.gibbonPersonIDOwnership = :owner')
                    ->bindValue('owner', $owner);
            },
            'typeSpecificFields' => function ($query, $typeSpecificFields) {
                return $query
                    ->where('gibbonLibraryItem.fields LIKE :typeSpecificFields')
                    ->bindValue('typeSpecificFields', '%'.$typeSpecificFields.'%');
            }
        ]);
        return $this->runQuery($query, $criteria);
    }
}
