<?php

namespace Gibbon\Domain\Library;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class LibraryShelfGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'gibbonLibraryShelf';
    private static $primaryKey = 'gibbonLibraryShelfID';
    private static $searchableColumns = [];

    public function queryLibraryShelves(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonLibraryShelf.gibbonLibraryShelfID',
                'gibbonLibraryShelf.name',
                'gibbonLibraryShelf.active',
                'gibbonLibraryShelf.shuffle',
                'gibbonLibraryShelf.field',
                'gibbonLibraryShelf.fieldValue',
                'gibbonLibraryShelf.type',
                'gibbonLibraryShelf.sequenceNumber',
            ]);

        $criteria->addFilterRules([
            'name' => function ($query, $name) {
                return $query
                    ->where('gibbonLibraryShelf.name LIKE :name')
                    ->bindValue('name', '%' . $name . '%');
            },
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonLibraryShelf.active = :active')
                    ->bindValue('active', $active);
            }
        ]);
        return $this->runQuery($query, $criteria);
    }

    public function getShelfByID($id) {
        $data = ['id' => $id];
        $sql = "SELECT * FROM gibbonLibraryShelf WHERE gibbonLibraryShelfID=:id";
        return $this->db()->selectOne($sql, $data);
    }

    public function selectDisplayableCategories() {
        // Build the type/collection arrays
        $sql = "SELECT gibbonLibraryTypeID as value, name, fields FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
        $result = $this->db()->select($sql);
        
        $typeList = ($result->rowCount() > 0) ? $result->fetchAll() : array();
        $category = $categoryChained = $subCategory = $subCategoryChained = array();
        $types = array_reduce($typeList, function ($group, $item) use (&$category, &$categoryChained, &$subCategory, &$subCategoryChained) {
            $group[$item['value']] = __($item['name']);

            foreach (json_decode($item['fields'], true) as $field) {
                if ($field['type'] == 'Select') {
                    $category[$field['name']] = __($field['name']);
                    $categoryChained[$field['name']] = $item['value'];
                    if($item['name'] == 'Print Publication'){
                        $category['Search Terms'] = __('Search Terms');
                        $categoryChained['Search Terms'] = $item['value'];
                    }
                    $categoryChained[$field['name']] = $item['value'];
                    foreach (explode(',', $field['options']) as $fieldItem) {
                        $fieldItem = trim($fieldItem);
                        $subCategory[$fieldItem] = __($fieldItem);
                        $subCategoryChained[$fieldItem] = $field['name'];
                    }
                }
            }
            return $group;
        }, array());

        return ['category' => $category, 
                'categoryChained' => $categoryChained, 
                'subCategory' => $subCategory, 
                'subCategoryChained' => $subCategoryChained, 
                'types' => $types];
    }

}
