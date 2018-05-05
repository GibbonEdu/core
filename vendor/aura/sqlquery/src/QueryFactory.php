<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\SqlQuery;

/**
 *
 * Creates query statement objects.
 *
 * @package Aura.SqlQuery
 *
 */
class QueryFactory
{
    const COMMON = 'common';

    /**
     *
     * What database are we building for?
     *
     * @param string
     *
     */
    protected $db;

    /**
     *
     * Build "common" query objects regardless of database type?
     *
     * @param bool
     *
     */
    protected $common = false;

    /**
     *
     * The quote prefix/suffix to use for each type.
     *
     * @param array
     *
     */
    protected $quotes = array(
        'Common' => array('"', '"'),
        'Mysql'  => array('`', '`'),
        'Pgsql'  => array('"', '"'),
        'Sqlite' => array('"', '"'),
        'Sqlsrv' => array('[', ']'),
    );

    /**
     *
     * The quote name prefix extracted from `$quotes`.
     *
     * @var string
     *
     */
    protected $quote_name_prefix;

    /**
     *
     * The quote name suffix extracted from `$quotes`.
     *
     * @var string
     *
     */
    protected $quote_name_suffix;

    /**
     *
     * A map of `table.col` names to last-insert-id names.
     *
     * @var array
     *
     */
    protected $last_insert_id_names = array();

    /**
     *
     * A Quoter for identifiers.
     *
     * @param Quoter
     *
     */
    protected $quoter;

    /**
     *
     * A count of Query instances, used for determining $seq_bind_prefix.
     *
     * @var int
     *
     */
    protected $instance_count = 0;

    /**
     *
     * Constructor.
     *
     * @param string $db The database type.
     *
     * @param string $common Pass the constant self::COMMON to force common
     * query objects instead of db-specific ones.
     *
     */
    public function __construct(
        $db,
        $common = null
    ) {
        $this->db = ucfirst(strtolower($db));
        $this->common = ($common === self::COMMON);
        $this->quote_name_prefix = $this->quotes[$this->db][0];
        $this->quote_name_suffix = $this->quotes[$this->db][1];
    }

    /**
     *
     * Sets the last-insert-id names to be used for Insert queries..
     *
     * @param array $last_insert_id_names A map of `table.col` names to
     * last-insert-id names.
     *
     * @return null
     *
     */
    public function setLastInsertIdNames(array $last_insert_id_names)
    {
        $this->last_insert_id_names = $last_insert_id_names;
    }

    /**
     *
     * Returns a new SELECT object.
     *
     * @return Common\SelectInterface
     *
     */
    public function newSelect()
    {
        return $this->newInstance('Select');
    }

    /**
     *
     * Returns a new INSERT object.
     *
     * @return Common\InsertInterface
     *
     */
    public function newInsert()
    {
        $insert = $this->newInstance('Insert');
        $insert->setLastInsertIdNames($this->last_insert_id_names);
        return $insert;
    }

    /**
     *
     * Returns a new UPDATE object.
     *
     * @return Common\UpdateInterface
     *
     */
    public function newUpdate()
    {
        return $this->newInstance('Update');
    }

    /**
     *
     * Returns a new DELETE object.
     *
     * @return Common\DeleteInterface
     *
     */
    public function newDelete()
    {
        return $this->newInstance('Delete');
    }

    /**
     *
     * Returns a new query object.
     *
     * @param string $query The query object type.
     *
     * @return AbstractQuery
     *
     */
    protected function newInstance($query)
    {
        if ($this->common) {
            $class = "Aura\SqlQuery\Common";
        } else {
            $class = "Aura\SqlQuery\\{$this->db}";
        }

        $class .= "\\{$query}";

        return new $class(
            $this->getQuoter(),
            $this->newSeqBindPrefix()
        );
    }

    /**
     *
     * Returns the Quoter object for queries; creates one if needed.
     *
     * @return Quoter
     *
     */
    protected function getQuoter()
    {
        if (! $this->quoter) {
            $this->quoter = new Quoter(
                $this->quote_name_prefix,
                $this->quote_name_suffix
            );
        }

        return $this->quoter;
    }

    /**
     *
     * Returns a new sequential-placeholder prefix for a query object.
     *
     * We need these to deconflict between bound values in subselect queries.
     *
     * @return string
     *
     */
    protected function newSeqBindPrefix()
    {
        $seq_bind_prefix = '';
        if ($this->instance_count) {
            $seq_bind_prefix = '_' . $this->instance_count;
        }

        $this->instance_count ++;
        return $seq_bind_prefix;
    }
}
