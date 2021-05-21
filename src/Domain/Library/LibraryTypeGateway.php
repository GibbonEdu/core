<?php

namespace Gibbon\Domain\Library;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\DataSet;

class LibraryTypeGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'gibbonLibraryType';
    private static $primaryKey = 'gibbonLibraryTypeID';
    private static $searchableColumns = [];
}
