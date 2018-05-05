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
use Aura\SqlQuery\Exception;

/**
 *
 * An object for INSERT queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Insert extends AbstractDmlQuery implements InsertInterface
{
    /**
     *
     * The table to insert into.
     *
     * @var string
     *
     */
    protected $into;

    /**
     *
     * A map of fully-qualified `table.column` names to last-insert-id names.
     * This is used to look up the right last-insert-id name for a given table
     * and column. Generally useful only for extended tables in Posgres.
     *
     * @var array
     *
     */
    protected $last_insert_id_names;

    /**
     *
     * The current row-number we are adding column values for. This comes into
     * play only with bulk inserts.
     *
     * @var int
     *
     */
    protected $row = 0;

    /**
     *
     * A collection of `$col_values` for previous rows in bulk inserts.
     *
     * @var array
     *
     */
    protected $col_values_bulk = array();

    /**
     *
     * A collection of `$bind_values` for previous rows in bulk inserts.
     *
     * @var array
     *
     */
    protected $bind_values_bulk = array();

    /**
     *
     * The order in which columns will be bulk-inserted; this is taken from the
     * very first inserted row.
     *
     * @var array
     *
     */
    protected $col_order = array();

    /**
     *
     * Sets the map of fully-qualified `table.column` names to last-insert-id
     * names. Generally useful only for extended tables in Posgres.
     *
     * @param array $last_insert_id_names The list of ID names.
     *
     */
    public function setLastInsertIdNames(array $last_insert_id_names)
    {
        $this->last_insert_id_names = $last_insert_id_names;
    }

    /**
     *
     * Sets the table to insert into.
     *
     * @param string $into The table to insert into.
     *
     * @return $this
     *
     */
    public function into($into)
    {
        // don't quote yet, we might need it for getLastInsertIdName()
        $this->into = $into;
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
        return 'INSERT'
            . $this->buildFlags()
            . $this->buildInto()
            . $this->buildValuesForInsert()
            . $this->buildReturning();
    }

    /**
     *
     * Builds the INTO clause.
     *
     * @return string
     *
     */
    protected function buildInto()
    {
        return " INTO " . $this->quoter->quoteName($this->into);
    }

    /**
     *
     * Returns the proper name for passing to `PDO::lastInsertId()`.
     *
     * @param string $col The last insert ID column.
     *
     * @return mixed Normally null, since most drivers do not need a name;
     * alternatively, a string from `$last_insert_id_names`.
     *
     */
    public function getLastInsertIdName($col)
    {
        $key = $this->into . '.' . $col;
        if (isset($this->last_insert_id_names[$key])) {
            return $this->last_insert_id_names[$key];
        }
    }

    /**
     *
     * Sets one column value placeholder; if an optional second parameter is
     * passed, that value is bound to the placeholder.
     *
     * @param string $col The column name.
     *
     * @param mixed,...  $val Optional: a value to bind to the placeholder.
     *
     * @return $this
     *
     */
    public function col($col)
    {
        return call_user_func_array(array($this, 'addCol'), func_get_args());
    }

    /**
     *
     * Sets multiple column value placeholders. If an element is a key-value
     * pair, the key is treated as the column name and the value is bound to
     * that column.
     *
     * @param array $cols A list of column names, optionally as key-value
     * pairs where the key is a column name and the value is a bind value for
     * that column.
     *
     * @return $this
     *
     */
    public function cols(array $cols)
    {
        return $this->addCols($cols);
    }

    /**
     *
     * Sets a column value directly; the value will not be escaped, although
     * fully-qualified identifiers in the value will be quoted.
     *
     * @param string $col   The column name.
     *
     * @param string $value The column value expression.
     *
     * @return $this
     *
     */
    public function set($col, $value)
    {
        return $this->setCol($col, $value);
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
        return array_merge(parent::getBindValues(), $this->bind_values_bulk);
    }

    /**
     *
     * Adds multiple rows for bulk insert.
     *
     * @param array $rows An array of rows, where each element is an array of
     * column key-value pairs. The values are bound to placeholders.
     *
     * @return $this
     *
     */
    public function addRows(array $rows)
    {
        foreach ($rows as $cols) {
            $this->addRow($cols);
        }
        if ($this->row > 1) {
            $this->finishRow();
        }
        return $this;
    }

    /**
     *
     * Add one row for bulk insert; increments the row counter and optionally
     * adds columns to the new row.
     *
     * When adding the first row, the counter is not incremented.
     *
     * After calling `addRow()`, you can further call `col()`, `cols()`, and
     * `set()` to work with the newly-added row. Calling `addRow()` again will
     * finish off the current row and start a new one.
     *
     * @param array $cols An array of column key-value pairs; the values are
     * bound to placeholders.
     *
     * @return $this
     *
     */
    public function addRow(array $cols = array())
    {
        if (! $this->col_values) {
            return $this->cols($cols);
        }

        if (! $this->col_order) {
            $this->col_order = array_keys($this->col_values);
        }

        $this->finishRow();
        $this->row ++;
        $this->cols($cols);
        return $this;
    }

    /**
     *
     * Finishes off the current row in a bulk insert, collecting the bulk
     * values and resetting for the next row.
     *
     * @return null
     *
     */
    protected function finishRow()
    {
        if (! $this->col_values) {
            return;
        }

        foreach ($this->col_order as $col) {
            $this->finishCol($col);
        }

        $this->col_values = array();
        $this->bind_values = array();
    }

    /**
     *
     * Finishes off a single column of the current row in a bulk insert.
     *
     * @param string $col The column to finish off.
     *
     * @return null
     *
     * @throws Exception on named column missing from row.
     *
     */
    protected function finishCol($col)
    {
        if (! array_key_exists($col, $this->col_values)) {
            throw new Exception("Column $col missing from row {$this->row}.");
        }

        // get the current col_value
        $value = $this->col_values[$col];

        // is it *not* a placeholder?
        if (substr($value, 0, 1) != ':') {
            // copy the value as-is
            $this->col_values_bulk[$this->row][$col] = $value;
            return;
        }

        // retain col_values in bulk with the row number appended
        $this->col_values_bulk[$this->row][$col] = "{$value}_{$this->row}";

        // the existing placeholder name without : or row number
        $name = substr($value, 1);

        // retain bind_value in bulk with new placeholder
        if (array_key_exists($name, $this->bind_values)) {
            $this->bind_values_bulk["{$name}_{$this->row}"] = $this->bind_values[$name];
        }
    }

    /**
     *
     * Builds the inserted columns and values of the statement.
     *
     * @return string
     *
     */
    protected function buildValuesForInsert()
    {
        if ($this->row) {
            return $this->buildValuesForBulkInsert();
        }

        return ' ('
            . $this->indentCsv(array_keys($this->col_values))
            . PHP_EOL . ') VALUES ('
            . $this->indentCsv(array_values($this->col_values))
            . PHP_EOL . ')';
    }

    /**
     *
     * Builds the bulk-inserted columns and values of the statement.
     *
     * @return string
     *
     */
    protected function buildValuesForBulkInsert()
    {
        $this->finishRow();
        $cols = "    (" . implode(', ', $this->col_order) . ")";
        $vals = array();
        foreach ($this->col_values_bulk as $row_values) {
            $vals[] = "    (" . implode(', ', $row_values) . ")";
        }
        return PHP_EOL . $cols . PHP_EOL
            . "VALUES" . PHP_EOL
            . implode("," . PHP_EOL, $vals);
    }
}
