<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery;

use Aura\SqlQuery\Common\LimitInterface;
use Aura\SqlQuery\Common\LimitOffsetInterface;
use Aura\SqlQuery\Common\SubselectInterface;

/**
 *
 * Abstract query object.
 *
 * @package Aura.SqlQuery
 *
 */
abstract class AbstractQuery
{
    /**
     *
     * Data to be bound to the query.
     *
     * @var array
     *
     */
    protected $bind_values = array();

    /**
     *
     * The list of WHERE conditions.
     *
     * @var array
     *
     */
    protected $where = array();

    /**
     *
     * ORDER BY these columns.
     *
     * @var array
     *
     */
    protected $order_by = array();

    /**
     *
     * The number of rows to select
     *
     * @var int
     *
     */
    protected $limit = 0;

    /**
     *
     * Return rows after this offset.
     *
     * @var int
     *
     */
    protected $offset = 0;

    /**
     *
     * The list of flags.
     *
     * @var array
     *
     */
    protected $flags = array();

    /**
     *
     * A helper for quoting identifier names.
     *
     * @var Quoter
     *
     */
    protected $quoter;

    /**
     *
     * Prefix to use on placeholders for "sequential" bound values; used for
     * deconfliction when merging bound values from sub-selects, etc.
     *
     * @var mixed
     *
     */
    protected $seq_bind_prefix = '';

    /**
     *
     * Constructor.
     *
     * @param Quoter $quoter A helper for quoting identifier names.
     *
     * @param string $seq_bind_prefix A prefix for rewritten sequential-binding
     * placeholders (@see getSeqPlaceholder()).
     *
     */
    public function __construct(Quoter $quoter, $seq_bind_prefix = '')
    {
        $this->quoter = $quoter;
        $this->seq_bind_prefix = $seq_bind_prefix;
    }

    /**
     *
     * Returns the prefix for rewritten sequential-binding placeholders
     * (@see getSeqPlaceholder()).
     *
     * @return string
     *
     */
    public function getSeqBindPrefix()
    {
        return $this->seq_bind_prefix;
    }

    /**
     *
     * Returns this query object as an SQL statement string.
     *
     * @return string
     *
     */
    public function __toString()
    {
        return $this->getStatement();
    }

    /**
     *
     * Returns this query object as an SQL statement string.
     *
     * @return string
     *
     */
    public function getStatement()
    {
        return $this->build();
    }

    /**
     *
     * Builds this query object into a string.
     *
     * @return string
     *
     */
    abstract protected function build();

    /**
     *
     * Returns the prefix to use when quoting identifier names.
     *
     * @return string
     *
     */
    public function getQuoteNamePrefix()
    {
        return $this->quoter->getQuoteNamePrefix();
    }

    /**
     *
     * Returns the suffix to use when quoting identifier names.
     *
     * @return string
     *
     */
    public function getQuoteNameSuffix()
    {
        return $this->quoter->getQuoteNameSuffix();
    }

    /**
     *
     * Returns an array as an indented comma-separated values string.
     *
     * @param array $list The values to convert.
     *
     * @return string
     *
     */
    protected function indentCsv(array $list)
    {
        return PHP_EOL . '    '
             . implode(',' . PHP_EOL . '    ', $list);
    }

    /**
     *
     * Returns an array as an indented string.
     *
     * @param array $list The values to convert.
     *
     * @return string
     *
     */
    protected function indent(array $list)
    {
        return PHP_EOL . '    '
             . implode(PHP_EOL . '    ', $list);
    }

    /**
     *
     * Binds multiple values to placeholders; merges with existing values.
     *
     * @param array $bind_values Values to bind to placeholders.
     *
     * @return $this
     *
     */
    public function bindValues(array $bind_values)
    {
        // array_merge() renumbers integer keys, which is bad for
        // question-mark placeholders
        foreach ($bind_values as $key => $val) {
            $this->bindValue($key, $val);
        }
        return $this;
    }

    /**
     *
     * Binds a single value to the query.
     *
     * @param string $name The placeholder name or number.
     *
     * @param mixed $value The value to bind to the placeholder.
     *
     * @return $this
     *
     */
    public function bindValue($name, $value)
    {
        $this->bind_values[$name] = $value;
        return $this;
    }

    /**
     *
     * Gets the values to bind to placeholders.
     *
     * @return array
     *
     */
    public function getBindValues()
    {
        return $this->bind_values;
    }

    /**
     *
     * Reset all values bound to named placeholders.
     *
     * @return $this
     *
     */
    public function resetBindValues()
    {
        $this->bind_values = array();
        return $this;
    }

    /**
     *
     * Builds the flags as a space-separated string.
     *
     * @return string
     *
     */
    protected function buildFlags()
    {
        if (! $this->flags) {
            return ''; // not applicable
        }

        return ' ' . implode(' ', array_keys($this->flags));
    }

    /**
     *
     * Sets or unsets specified flag.
     *
     * @param string $flag Flag to set or unset
     *
     * @param bool $enable Flag status - enabled or not (default true)
     *
     * @return null
     *
     */
    protected function setFlag($flag, $enable = true)
    {
        if ($enable) {
            $this->flags[$flag] = true;
        } else {
            unset($this->flags[$flag]);
        }
    }

    /**
     *
     * Reset all query flags.
     *
     * @return $this
     *
     */
    public function resetFlags()
    {
        $this->flags = array();
        return $this;
    }

    /**
     *
     * Adds a WHERE condition to the query by AND or OR. If the condition has
     * ?-placeholders, additional arguments to the method will be bound to
     * those placeholders sequentially.
     *
     * @param string $andor Add the condition using this operator, typically
     * 'AND' or 'OR'.
     *
     * @param array $args Arguments for adding the condition.
     *
     * @return $this
     *
     */
    protected function addWhere($andor, $args)
    {
        $this->addClauseCondWithBind('where', $andor, $args);
        return $this;
    }

    /**
     *
     * Adds conditions and binds values to a clause.
     *
     * @param string $clause The clause to work with, typically 'where' or
     * 'having'.
     *
     * @param string $andor Add the condition using this operator, typically
     * 'AND' or 'OR'.
     *
     * @param array $args Arguments for adding the condition.
     *
     * @return null
     *
     */
    protected function addClauseCondWithBind($clause, $andor, $args)
    {
        // remove the condition from the args and quote names in it
        $cond = array_shift($args);
        $cond = $this->rebuildCondAndBindValues($cond, $args);

        // add condition to clause; $this->where
        $clause =& $this->$clause;
        if ($clause) {
            $clause[] = "$andor $cond";
        } else {
            $clause[] = $cond;
        }
    }

    /**
     *
     * Rebuilds a condition string, replacing sequential placeholders with
     * named placeholders, and binding the sequential values to the named
     * placeholders.
     *
     * @param string $cond The condition with sequential placeholders.
     *
     * @param array $bind_values The values to bind to the sequential
     * placeholders under their named versions.
     *
     * @return string The rebuilt condition string.
     *
     */
    protected function rebuildCondAndBindValues($cond, array $bind_values)
    {
        $cond = $this->quoter->quoteNamesIn($cond);

        // bind values against ?-mark placeholders, but because PDO is finicky
        // about the numbering of sequential placeholders, convert each ?-mark
        // to a named placeholder
        $parts = preg_split('/(\?)/', $cond, null, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $key => $val) {
            if ($val != '?') {
                continue;
            }

            $bind_value = array_shift($bind_values);
            if ($bind_value instanceof SubselectInterface) {
                $parts[$key] = $bind_value->getStatement();
                $this->bind_values = array_merge(
                    $this->bind_values,
                    $bind_value->getBindValues()
                );
                continue;
            }

            $placeholder = $this->getSeqPlaceholder();
            $parts[$key] = ':' . $placeholder;
            $this->bind_values[$placeholder] = $bind_value;
        }

        $cond = implode('', $parts);
        return $cond;
    }

    /**
     *
     * Gets the current sequential placeholder name.
     *
     * @return string
     *
     */
    protected function getSeqPlaceholder()
    {
        $i = count($this->bind_values) + 1;
        return $this->seq_bind_prefix . "_{$i}_";
    }

    /**
     *
     * Builds the `WHERE` clause of the statement.
     *
     * @return string
     *
     */
    protected function buildWhere()
    {
        if (! $this->where) {
            return ''; // not applicable
        }

        return PHP_EOL . 'WHERE' . $this->indent($this->where);
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
    protected function addOrderBy(array $spec)
    {
        foreach ($spec as $col) {
            $this->order_by[] = $this->quoter->quoteNamesIn($col);
        }
        return $this;
    }

    /**
     *
     * Builds the `ORDER BY ...` clause of the statement.
     *
     * @return string
     *
     */
    protected function buildOrderBy()
    {
        if (! $this->order_by) {
            return ''; // not applicable
        }

        return PHP_EOL . 'ORDER BY' . $this->indentCsv($this->order_by);
    }

    /**
     *
     * Builds the `LIMIT ... OFFSET` clause of the statement.
     *
     * Note that this will allow OFFSET values with a LIMIT.
     *
     * @return string
     *
     */
    protected function buildLimit()
    {
        $clause = '';
        $limit = $this instanceof LimitInterface && $this->limit;
        $offset = $this instanceof LimitOffsetInterface && $this->offset;

        if ($limit) {
            $clause .= "LIMIT {$this->limit}";
        }

        if ($offset) {
            $clause .= " OFFSET {$this->offset}";
        }

        if ($clause) {
            $clause = PHP_EOL . trim($clause);
        }

        return $clause;
    }
}
