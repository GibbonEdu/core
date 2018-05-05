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
 * An interface for INSERT queries.
 *
 * @package Aura.SqlQuery
 *
 */
interface InsertInterface extends QueryInterface, ValuesInterface
{
    /**
     *
     * Sets the table to insert into.
     *
     * @param string $into The table to insert into.
     *
     * @return $this
     *
     */
    public function into($into);
}
