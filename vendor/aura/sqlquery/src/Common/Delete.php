<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Common;

use Aura\SqlQuery\AbstractDmlQuery;

/**
 *
 * An object for DELETE queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Delete extends AbstractDmlQuery implements DeleteInterface
{
    /**
     *
     * The table to delete from.
     *
     * @var string
     *
     */
    protected $from;

    /**
     *
     * Sets the table to delete from.
     *
     * @param string $table The table to delete from.
     *
     * @return $this
     *
     */
    public function from($table)
    {
        $this->from = $this->quoter->quoteName($table);
        return $this;
    }

    /**
     *
     * Builds this query object into a string.
     *
     * @return string
     *
     */
    protected function build()
    {
        return 'DELETE'
            . $this->buildFlags()
            . $this->buildFrom()
            . $this->buildWhere()
            . $this->buildOrderBy()
            . $this->buildLimit()
            . $this->buildReturning();
    }

    /**
     *
     * Builds the FROM clause.
     *
     * @return string
     *
     */
    protected function buildFrom()
    {
        return " FROM {$this->from}";
    }

    /**
     *
     * Adds a WHERE condition to the query by AND. If the condition has
     * ?-placeholders, additional arguments to the method will be bound to
     * those placeholders sequentially.
     *
     * @param string $cond The WHERE condition.
     * @param mixed ...$bind arguments to bind to placeholders
     *
     * @return $this
     *
     */
    public function where($cond)
    {
        $this->addWhere('AND', func_get_args());
        return $this;
    }

    /**
     *
     * Adds a WHERE condition to the query by OR. If the condition has
     * ?-placeholders, additional arguments to the method will be bound to
     * those placeholders sequentially.
     *
     * @param string $cond The WHERE condition.
     * @param mixed ...$bind arguments to bind to placeholders
     *
     * @return $this
     *
     * @see where()
     *
     */
    public function orWhere($cond)
    {
        $this->addWhere('OR', func_get_args());
        return $this;
    }
}
