<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Pgsql;

use Aura\SqlQuery\Common;

/**
 *
 * An object for PgSQL UPDATE queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Delete extends Common\Delete implements Common\ReturningInterface
{
    /**
     *
     * Adds returning columns to the query.
     *
     * Multiple calls to returning() will append to the list of columns, not
     * overwrite the previous columns.
     *
     * @param array $cols The column(s) to add to the query.
     *
     * @return $this
     *
     */
    public function returning(array $cols)
    {
        return $this->addReturning($cols);
    }
}
