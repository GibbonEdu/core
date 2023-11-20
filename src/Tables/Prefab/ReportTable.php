<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Tables\Prefab;

use Gibbon\Tables\DataTable;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Services\Format;
use Gibbon\Tables\Renderer\PaginatedRenderer;
use Gibbon\Tables\Renderer\PrintableRenderer;
use Gibbon\Tables\Renderer\SpreadsheetRenderer;

/**
 * ReportTable
 *
 * @version v17
 * @since   v17
 */
class ReportTable extends DataTable
{
    /**
     * Helper method to create a report data table, which can display as a table, printable page or export.
     *
     * @param string $id
     * @param QueryCriteria $criteria
     * @param string $viewMode
     * @param string $guid
     * @return self
     */
    public static function createPaginated($id, QueryCriteria $criteria)
    {
        $table = parent::createPaginated($id, $criteria);

        $table->addHeaderAction('print', __('Print'))
            ->setURL('/report.php')
            ->addParams($_GET)
            ->addParam('format', 'print')
            ->addParam('search', $criteria->getSearchText(true))
            ->setTarget('_blank')
            ->displayLabel()
            ->directLink()
            ->addClass('mr-2 underline')
            ->append(' | ');

        $table->addHeaderAction('export', __('Export'))
            ->setURL('/export.php')
            ->addParams($_GET)
            ->addParam('format', 'export')
            ->addParam('search', $criteria->getSearchText(true))
            ->displayLabel()
            ->directLink()
            ->addClass('mr-2 underline');

        return $table;
    }

    public function setViewMode($viewMode, $session)
    {
        switch ($viewMode) {
            case 'print':   $this->setRenderer(new PrintableRenderer());
                $this->setHeader([]);
                $this->addHeaderAction('print', __('Print'))
                    ->onClick('javascript:window.print(); return false;')
                    ->displayLabel();
                $this->addMetaData('hidePagination', true);
                break;
            
            case 'export':  $this->setRenderer(new SpreadsheetRenderer($session->get('absolutePath'))); break;
        }

        $this->addMetaData('filename', 'gibbonExport_'.$this->getID());
        $this->addMetaData('creator', Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff'));

        return $this;
    }

    /**
     * Add an incremental row count. For paginated tables, the starting count from DataSet::getPageFrom should be passed in.
     *
     * @param int $count
     * @return Column
     */
    public function addRowCountColumn($count = 1)
    {
        return $this->addColumn('count', '')
            ->notSortable()
            ->width('35px')
            ->format(function ($row) use (&$count) {
                return '<span class="subdued">'.$count++.'</span>';
            });
    }
}
