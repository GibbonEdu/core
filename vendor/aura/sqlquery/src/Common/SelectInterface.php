<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Common;

use Aura\SqlQuery\QueryInterface;

/**
 *
 * An interface for SELECT queries.
 *
 * @package Aura.SqlQuery
 *
 */
interface SelectInterface extends QueryInterface, WhereInterface, OrderByInterface, LimitOffsetInterface
{
    /**
     *
     * Sets the number of rows per page.
     *
     * @param int $paging The number of rows to page at.
     *
     * @return $this
     *
     */
    public function setPaging($paging);

    /**
     *
     * Gets the number of rows per page.
     *
     * @return int The number of rows per page.
     *
     */
    public function getPaging();

    /**
     *
     * Makes the select FOR UPDATE (or not).
     *
     * @param bool $enable Whether or not the SELECT is FOR UPDATE (default
     * true).
     *
     * @return $this
     *
     */
    public function forUpdate($enable = true);

    /**
     *
     * Makes the select DISTINCT (or not).
     *
     * @param bool $enable Whether or not the SELECT is DISTINCT (default
     * true).
     *
     * @return $this
     *
     */
    public function distinct($enable = true);

    /**
     *
     * Adds columns to the query.
     *
     * Multiple calls to cols() will append to the list of columns, not
     * overwrite the previous columns.
     *
     * @param array $cols The column(s) to add to the query.
     *
     * @return $this
     *
     */
    public function cols(array $cols);

    /**
     *
     * Adds a FROM element to the query; quotes the table name automatically.
     *
     * @param string $spec The table specification; "foo" or "foo AS bar".
     *
     * @return $this
     *
     */
    public function from($spec);

    /**
     *
     * Adds a raw unquoted FROM element to the query; useful for adding FROM
     * elements that are functions.
     *
     * @param string $spec The table specification, e.g. "function_name()".
     *
     * @return $this
     *
     */
    public function fromRaw($spec);

    /**
     *
     * Adds an aliased sub-select to the query.
     *
     * @param string|Select $spec If a Select object, use as the sub-select;
     * if a string, the sub-select string.
     *
     * @param string $name The alias name for the sub-select.
     *
     * @return $this
     *
     */
    public function fromSubSelect($spec, $name);

    /**
     *
     * Adds a JOIN table and columns to the query.
     *
     * @param string $join The join type: inner, left, natural, etc.
     *
     * @param string $spec The table specification; "foo" or "foo AS bar".
     *
     * @param string $cond Join on this condition.
     *
     * @return $this
     *
     */
    public function join($join, $spec, $cond = null);

    /**
     *
     * Adds a JOIN to an aliased subselect and columns to the query.
     *
     * @param string $join The join type: inner, left, natural, etc.
     *
     * @param string|Select $spec If a Select
     * object, use as the sub-select; if a string, the sub-select
     * command string.
     *
     * @param string $name The alias name for the sub-select.
     *
     * @param string $cond Join on this condition.
     *
     * @return $this
     *
     */
    public function joinSubSelect($join, $spec, $name, $cond = null);

    /**
     *
     * Adds grouping to the query.
     *
     * @param array $spec The column(s) to group by.
     *
     * @return $this
     *
     */
    public function groupBy(array $spec);

    /**
     *
     * Adds a HAVING condition to the query by AND; if a value is passed as
     * the second param, it will be quoted and replaced into the condition
     * wherever a question-mark appears.
     *
     * Array values are quoted and comma-separated.
     *
     * {{code: php
     *     // simplest but non-secure
     *     $select->having("COUNT(id) = $count");
     *
     *     // secure
     *     $select->having('COUNT(id) = ?', $count);
     *
     *     // equivalent security with named binding
     *     $select->having('COUNT(id) = :count');
     *     $select->bind('count', $count);
     * }}
     *
     * @param string $cond The HAVING condition.
     *
     * @return $this
     *
     */
    public function having($cond);

    /**
     *
     * Adds a HAVING condition to the query by AND; otherwise identical to
     * `having()`.
     *
     * @param string $cond The HAVING condition.
     *
     * @return $this
     *
     * @see having()
     *
     */
    public function orHaving($cond);

    /**
     *
     * Sets the limit and count by page number.
     *
     * @param int $page Limit results to this page number.
     *
     * @return $this
     *
     */
    public function page($page);

    /**
     *
     * Takes the current select properties and retains them, then sets
     * UNION for the next set of properties.
     *
     * @return $this
     *
     */
    public function union();

    /**
     *
     * Takes the current select properties and retains them, then sets
     * UNION ALL for the next set of properties.
     *
     * @return $this
     *
     */
    public function unionAll();
}
