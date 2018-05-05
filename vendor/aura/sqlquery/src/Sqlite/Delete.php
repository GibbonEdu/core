<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Sqlite;

use Aura\SqlQuery\Common;

/**
 *
 * An object for Sqlite DELETE queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Delete extends Common\Delete implements Common\OrderByInterface, Common\LimitOffsetInterface
{
    /**
     *
     * Sets a limit count on the query.
     *
     * @param int $limit The number of rows to select.
     *
     * @return $this
     *
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     *
     * Returns the LIMIT value.
     *
     * @return int
     *
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     *
     * Sets a limit offset on the query.
     *
     * @param int $offset Start returning after this many rows.
     *
     * @return $this
     *
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     *
     * Returns the OFFSET value.
     *
     * @return int
     *
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     *
     * Adds a column order to the query.
     *
     * @param array $spec The columns and direction to order by.
     *
     * @return $this
     *
     */
    public function orderBy(array $spec)
    {
        return $this->addOrderBy($spec);
    }
}
