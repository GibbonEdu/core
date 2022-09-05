<?php

namespace Gibbon\Module\Reports;

use Gibbon\Module\Reports\DataSource;

class ReportData
{
    protected $identifiers = array();
    protected $data = array();

    public function __construct($identifiers = [])
    {
        $this->identifiers = $identifiers;
    }

    public function getID($key)
    {
        return isset($this->identifiers[$key])? $this->identifiers[$key] : null;
    }

    public function getField($alias, $key)
    {
        return isset($this->data[$alias][$key])? $this->data[$alias][$key] : null;
    }

    public function setField($alias, $key, $value = null)
    {
        $this->data[$alias][$key] = $value;
    }

    public function addData($alias, $data)
    {
        $this->data[$alias] = $data;
    }

    public function getData($alias)
    {
        $indexes = (is_array($alias))? $alias : array($alias);

        return array_intersect_key($this->data, array_flip($indexes));
    }

    public function getDataSources()
    {
        return array_keys($this->data);
    }
}
