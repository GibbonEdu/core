<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Sqlsrv;

use Aura\SqlQuery\Common;

/**
 *
 * An object for Sqlsrv SELECT queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Select extends Common\Select
{
    /**
     *
     * Builds this query object into a string.
     *
     * @return string
     *
     */
    protected function build()
    {
        return $this->applyLimit(parent::build());
    }

    /**
     *
     * Override so that LIMIT equivalent will be applied by applyLimit().
     *
     * @see build()
     *
     * @see applyLimit()
     *
     */
    protected function buildLimit()
    {
        return '';
    }

    /**
     *
     * Modify the statement applying limit/offset equivalent portions to it.
     *
     * @param string $stm SQL statement
     * @return string SQL statement with limit/offset applied
     *
     */
    protected function applyLimit($stm)
    {
        if (! $this->limit && ! $this->offset) {
            return $stm; // no limit or offset
        }

        // limit but no offset?
        if ($this->limit && ! $this->offset) {
            // use TOP in place
            return preg_replace(
                '/^(SELECT( DISTINCT)?)/',
                "$1 TOP {$this->limit}",
                $stm
            );
        }

        // both limit and offset. must have an ORDER clause to work; OFFSET is
        // a sub-clause of the ORDER clause. cannot use FETCH without OFFSET.
        return $stm . PHP_EOL . "OFFSET {$this->offset} ROWS "
                    . "FETCH NEXT {$this->limit} ROWS ONLY";
    }
}
