<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery\Mysql;

use Aura\SqlQuery\Common;

/**
 *
 * An object for MySQL INSERT queries.
 *
 * @package Aura.SqlQuery
 *
 */
class Insert extends Common\Insert
{
    /**
     *
     * Column values for ON DUPLICATE KEY UPDATE section of query; the key is
     * the column name and the value is the column value.
     *
     * @param array
     *
     */
    protected $col_on_update_values;

    /**
     *
     * Adds or removes HIGH_PRIORITY flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function highPriority($enable = true)
    {
        $this->setFlag('HIGH_PRIORITY', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes LOW_PRIORITY flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function lowPriority($enable = true)
    {
        $this->setFlag('LOW_PRIORITY', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes IGNORE flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function ignore($enable = true)
    {
        $this->setFlag('IGNORE', $enable);
        return $this;
    }

    /**
     *
     * Adds or removes DELAYED flag.
     *
     * @param bool $enable Set or unset flag (default true).
     *
     * @return $this
     *
     */
    public function delayed($enable = true)
    {
        $this->setFlag('DELAYED', $enable);
        return $this;
    }

    /**
     *
     * Sets one column value placeholder in ON DUPLICATE KEY UPDATE section;
     * if an optional second parameter is passed, that value is bound to the
     * placeholder.
     *
     * @param string $col The column name.
     *
     * @param mixed,... $val Optional: a value to bind to the placeholder.
     *
     * @return $this
     *
     */
    public function onDuplicateKeyUpdateCol($col)
    {
        $key = $this->quoter->quoteName($col);
        $bind = $col . '__on_duplicate_key';
        $this->col_on_update_values[$key] = ":$bind";
        $args = func_get_args();
        if (count($args) > 1) {
            $this->bindValue($bind, $args[1]);
        }
        return $this;
    }

    /**
     *
     * Sets multiple column value placeholders in ON DUPLICATE KEY UPDATE
     * section. If an element is a key-value pair, the key is treated as the
     * column name and the value is bound to that column.
     *
     * @param array $cols A list of column names, optionally as key-value
     * pairs where the key is a column name and the value is a bind value for
     * that column.
     *
     * @return $this
     *
     */
    public function onDuplicateKeyUpdateCols(array $cols)
    {
        foreach ($cols as $key => $val) {
            if (is_int($key)) {
                // integer key means the value is the column name
                $this->onDuplicateKeyUpdateCol($val);
            } else {
                // the key is the column name and the value is a value to
                // be bound to that column
                $this->onDuplicateKeyUpdateCol($key, $val);
            }
        }
        return $this;
    }

    /**
     *
     * Sets a column value directly in ON DUPLICATE KEY UPDATE section; the
     * value will not be escaped, although fully-qualified identifiers in the
     * value will be quoted.
     *
     * @param string $col The column name.
     *
     * @param string $value The column value expression.
     *
     * @return $this
     *
     */
    public function onDuplicateKeyUpdate($col, $value)
    {
        if ($value === null) {
            $value = 'NULL';
        }

        $key = $this->quoter->quoteName($col);
        $value = $this->quoter->quoteNamesIn($value);
        $this->col_on_update_values[$key] = $value;
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
            . $this->buildValuesForUpdateOnDuplicateKey()
            . $this->buildReturning();
    }

    /**
     *
     * Builds the UPDATE ON DUPLICATE KEY part of the statement.
     *
     * @return string
     *
     */
    protected function buildValuesForUpdateOnDuplicateKey()
    {
        if (! $this->col_on_update_values) {
            return ''; // not applicable
        }

        $values = array();
        foreach ($this->col_on_update_values as $key => $row) {
            $values[] = $this->indent(array($key . ' = ' . $row));
        }

        return ' ON DUPLICATE KEY UPDATE'
            . implode (',', $values);
    }
}
