<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Common;

/**
 *
 * An interface for WHERE clauses.
 *
 * @package Aura.SqlQuery
 *
 */
interface WhereInterface
{
    /**
     *
     * Adds a WHERE condition to the query by AND. If the condition has
     * ?-placeholders, additional arguments to the method will be bound to
     * those placeholders sequentially.
     *
     * @param string $cond The WHERE condition.
     * @param mixed ...$params arguments to be bound to placeholders
     *
     * @return $this
     *
     */
    public function where($cond);

    /**
     *
     * Adds a WHERE condition to the query by OR. If the condition has
     * ?-placeholders, additional arguments to the method will be bound to
     * those placeholders sequentially.
     *
     * @param string $cond The WHERE condition.
     * @param mixed ...$params arguments to be bound to placeholders
     *
     * @return $this
     *
     * @see where()
     *
     */
    public function orWhere($cond);
}
