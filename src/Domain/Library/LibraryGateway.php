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
    private static $searchableColumns = ['gibbonLibraryItem.name', 'gibbonLibraryItem.producer', 'gibbonLibraryItem.id'];

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
                "IF(gibbonLibraryItemEvent.returnExpected < CURRENT_DATE,'Y','N') as pastDue"
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
          "IF(gibbonLibraryItem.status = 'On Loan' AND gibbonLibraryItem.returnExpected < CURRENT_DATE,'Y','N') as 'pastDue'"
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
                'JSON_EXTRACT(gibbonLibraryItem.fields , "$.Description") as description',
                'JSON_EXTRACT(gibbonLibraryItem.fields , "$.Subjects") as subjects',
                'JSON_EXTRACT(gibbonLibraryItem.fields , \'$."Search Terms"\') as searchTerms',
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
                    ->where("gibbonLibraryItem.fields LIKE CONCAT('%\"Collection\":\"', :collection, '\"%')")
                    ->bindValue('collection', $collection);
            },
            'location' => function ($query, $location) {
                return $query
                    ->where('gibbonSpace.name LIKE :location')
                    ->bindValue('location', $location);
            },
            'agecheck' => function ($query, $readerAge) {
                return $query
                    ->where('gibbonLibraryItem.fields->\'$."Reader Age (Youngest)"\' != "" 
                            AND gibbonLibraryItem.fields->\'$."Reader Age (Oldest)"\' != "" 
                            AND gibbonLibraryItem.fields->\'$."Reader Age (Youngest)"\'+0 <= :readerAge 
                            AND gibbonLibraryItem.fields->\'$."Reader Age (Oldest)"\'+0 >= :readerAge')
                    ->bindValue('readerAge', $readerAge);
            },
            'everything' => function ($query, $needle) {
                $globalSearch = "(";
                foreach ($query->getCols() as $col) {
                    if(preg_match('/.* as .*/', $col)) {
                        $col = preg_replace('/ as .*/', '', $col);
                    }
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
                'gibbonLibraryItem.gibbonLibraryItemIDParent',
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
                'gibbonLibraryItem.timestampUpdate',
                'gibbonPerson.title as title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonLibraryType.name as itemType',
                'responsible.title as titleResponsible',
                'responsible.surname as surnameResponsible',
                'responsible.preferredName as preferredNameResponsible',
                'gibbonFormGroup.nameShort as formGroup',
              ])
            ->innerJoin('gibbonLibraryType', 'gibbonLibraryItem.gibbonLibraryTypeID = gibbonLibraryType.gibbonLibraryTypeID')
            ->leftJoin('gibbonSpace', 'gibbonLibraryItem.gibbonSpaceID = gibbonSpace.gibbonSpaceID')
            ->leftJoin('gibbonPerson', 'gibbonLibraryItem.gibbonPersonIDOwnership = gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPerson as responsible', 'responsible.gibbonPersonID=gibbonLibraryItem.gibbonPersonIDStatusResponsible')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=responsible.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'name' => function ($query, $name) {
                return $query
                    ->where('(gibbonLibraryItem.name LIKE :name OR gibbonLibraryItem.producer LIKE :name OR gibbonLibraryItem.id LIKE :name)')
                    ->bindValue('name', '%' . $name . '%');
            },
            'parent' => function ($query, $parentID) {
                if($parentID == 'NULL') {
                    $query = $query
                    ->leftJoin('gibbonLibraryItem AS parent', '(parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)')
                    ->where('(gibbonLibraryItem.id IS NULL OR parent.id IS NULL)');
                } else {
                    $query = $query
                    ->leftJoin('gibbonLibraryItem AS parent', '(parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)')
                    ->where('(gibbonLibraryItem.id = :parentID OR parent.id = :parentID)')
                    ->bindValue('parentID', $parentID);
                }
                return $query;
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
            'locationDetail' => function ($query, $locationDetail) {
                return $query
                    ->where('gibbonLibraryItem.locationDetail LIKE :locationDetail')
                    ->bindValue('locationDetail', '%'.$locationDetail.'%');
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

    public function getLibraryItemDetails($gibbonLibraryItemID)
    {
        $data = ['gibbonLibraryItemID' => $gibbonLibraryItemID];
        $sql = "SELECT gibbonLibraryItem.*, gibbonLibraryType.name AS type, parent.id as parentID
            FROM gibbonLibraryItem 
            JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) 
            LEFT JOIN gibbonLibraryItem AS parent ON (parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)
            WHERE gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getByRecordID($id)
    {
        $data = ['id' => $id];
        $sql = "SELECT * FROM gibbonLibraryItem WHERE id=:id";

        return $this->db()->selectOne($sql, $data);
    }

    public function getChildRecordCount($gibbonLibraryItemID)
    {
        $data = ['gibbonLibraryItemID' => $gibbonLibraryItemID];
        $sql = "SELECT COUNT(*) FROM gibbonLibraryItem WHERE gibbonLibraryItemIDParent=:gibbonLibraryItemID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectChildRecordIDs($gibbonLibraryItemID)
    {
        $data = ['gibbonLibraryItemID' => $gibbonLibraryItemID];
        $sql = "SELECT gibbonLibraryItemID FROM gibbonLibraryItem WHERE gibbonLibraryItemIDParent=:gibbonLibraryItemID";

        return $this->db()->select($sql, $data);
    }

    public function updateChildRecords($gibbonLibraryItemIDParent)
    {
        $data = ['gibbonLibraryItemIDParent' => $gibbonLibraryItemIDParent];

        $sql = "UPDATE gibbonLibraryItem 
            JOIN gibbonLibraryItem AS parent ON (parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)
            SET gibbonLibraryItem.fields=parent.fields, 
                gibbonLibraryItem.name=parent.name,
                gibbonLibraryItem.producer=parent.producer,
                gibbonLibraryItem.vendor=parent.vendor,
                gibbonLibraryItem.imageType=parent.imageType,
                gibbonLibraryItem.imageLocation=parent.imageLocation,
                gibbonLibraryItem.gibbonSpaceID=parent.gibbonSpaceID,
                gibbonLibraryItem.locationDetail=parent.locationDetail,
                gibbonLibraryItem.gibbonDepartmentID=parent.gibbonDepartmentID,
                gibbonLibraryItem.gibbonPersonIDUpdate=parent.gibbonPersonIDUpdate,
                gibbonLibraryItem.timestampUpdate=parent.timestampUpdate
            WHERE gibbonLibraryItem.gibbonLibraryItemIDParent=:gibbonLibraryItemIDParent";

        return $this->db()->update($sql, $data);
    }

    public function updateFromParentRecord($gibbonLibraryItemID)
    {
        $data = ['gibbonLibraryItemID' => $gibbonLibraryItemID];

        $sql = "UPDATE gibbonLibraryItem 
            JOIN gibbonLibraryItem AS parent ON (parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)
            SET gibbonLibraryItem.fields=parent.fields, 
                gibbonLibraryItem.name=parent.name,
                gibbonLibraryItem.producer=parent.producer,                
                gibbonLibraryItem.vendor=parent.vendor,                
                gibbonLibraryItem.imageType=parent.imageType,
                gibbonLibraryItem.imageLocation=parent.imageLocation,
                gibbonLibraryItem.gibbonSpaceID=parent.gibbonSpaceID,
                gibbonLibraryItem.locationDetail=parent.locationDetail,
                gibbonLibraryItem.gibbonDepartmentID=parent.gibbonDepartmentID
            WHERE gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID";

        return $this->db()->update($sql, $data);
    }

    public function selectItemsByTypeFields($libraryType, $field, $fieldValue)
    {
        if ($field == 'Search Terms') {
            $fieldValue = '"%'.$fieldValue.'%"';
        }
        $field = '$."'.$field.'"';
        $data = array('libraryType' => $libraryType, 'field' => $field, 'fieldValue' => $fieldValue);
        $sql = "SELECT gibbonLibraryItemID FROM gibbonLibraryItem
                JOIN gibbonLibraryType ON (gibbonLibraryType.gibbonLibraryTypeID = gibbonLibraryItem.gibbonLibraryTypeID)
                WHERE gibbonLibraryItem.gibbonLibraryTypeID = :libraryType
                AND gibbonLibraryItem.gibbonLibraryItemIDParent IS NULL";
        if($field == '$."Search Terms"') {
            $sql .= " AND JSON_EXTRACT(gibbonLibraryItem.fields , :field) LIKE :fieldValue;";
        } else {
            $sql .= " AND JSON_EXTRACT(gibbonLibraryItem.fields , :field) = :fieldValue;";
        }

        return $this->db()->select($sql, $data);
    }

    public function queryItemsForShelves(QueryCriteria $criteria)
    {
      
        $query = $this
        ->newQuery()
        ->from($this->getTableName())
        ->cols([
          "gibbonLibraryItem.id",
          "gibbonLibraryItem.name",
          "gibbonLibraryItem.producer",
          "gibbonLibraryItem.gibbonLibraryItemID",
          "gibbonLibraryItem.gibbonLibraryTypeID",
          "gibbonLibraryItem.imageLocation",
          "gibbonLibraryItem.status",
        ])
        ->where("gibbonLibraryItem.imageLocation IS NOT NULL AND gibbonLibraryItem.imageLocation != ''")
        ->where("gibbonLibraryItem.status IN ('Available','On Loan','Repair')");

        $criteria->addFilterRules([
        'name' => function ($query, $name) {
            return $query
            ->where('(gibbonLibraryItem.name like :name OR gibbonLibraryItem.producer like :name OR gibbonLibraryItem.id like :name)')
            ->bindValue('name', '%'.$name.'%');
        },
        'parent' => function ($query, $parentID) {
            if($parentID == 'NULL') {
                $query = $query
                ->leftJoin('gibbonLibraryItem AS parent', '(parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)')
                ->where('(gibbonLibraryItem.id IS NULL OR parent.id IS NULL)');
            } else {
                $query = $query
                ->leftJoin('gibbonLibraryItem AS parent', '(parent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemIDParent)')
                ->where('(gibbonLibraryItem.id = :parentID OR parent.id = :parentID)')
                ->bindValue('parentID', $parentID);
            }
            return $query;
        },
        ]);
        return $this->runQuery($query, $criteria);
    }

    public function selectDistinctVendorList()
    {
        $data = [];
        $sql = "SELECT DISTINCT vendor FROM gibbonLibraryItem ORDER BY vendor";

        return $this->db()->select($sql, $data);
    }

    public function selectDistinctLocationDetails()
    {
        $data = [];
        $sql = "SELECT DISTINCT locationDetail FROM gibbonLibraryItem ORDER BY locationDetail";

        return $this->db()->select($sql, $data);
    }
}
