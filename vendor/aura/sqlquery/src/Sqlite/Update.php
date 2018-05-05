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
 * An object for Sqlite UPDATE queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Update extends Common\Update implements Common\OrderByInterface, Common\LimitOffsetInterface
{
    /**
     *
     * Adds or removes OR ABORT flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orAbort($enable = true)
    {
        $this->setFlag('OR ABORT', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR FAIL flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orFail($enable = true)
    {
        $this->setFlag('OR FAIL', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR IGNORE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orIgnore($enable = true)
    {
        $this->setFlag('OR IGNORE', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR REPLACE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orReplace($enable = true)
    {
        $this->setFlag('OR REPLACE', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes OR ROLLBACK flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function orRollback($enable = true)
    {
        $this->setFlag('OR ROLLBACK', $enable);
        return $this;
    }

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
