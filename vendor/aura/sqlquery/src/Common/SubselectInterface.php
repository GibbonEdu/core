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
 * Interface to get a statement string so we can bind sub-select values.
 *
 * @package Aura.SqlQuery
 *
 * @see AbstractQuery::rebuildCondAndBindValues()
 *
 */
interface SubselectInterface
{
    /**
     *
     * Returns this query object as an SQL statement string.
     *
     * @return string
     *
     */
    public function getStatement();

    /**
     *
     * Gets the values to bind to placeholders.
     *
     * @return array
     *
     */
    public function getBindValues();
}
