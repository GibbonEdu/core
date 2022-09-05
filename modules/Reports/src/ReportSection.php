<?php

namespace Gibbon\Module\Reports;

class ReportSection
{
    const NO_PAGE_WRAP = 0b00000001;
    const PAGE_BREAK_BEFORE = 0b00000010;
    const PAGE_BREAK_AFTER = 0b00000100;
    const SKIP_IF_EMPTY = 0b00001000;
    const IS_LAST_PAGE = 0b00010000;

    protected $template = '';
    protected $sources = [];
    protected $data = [];
    
    protected $x = 0;
    protected $y = 0;
    protected $width = 0;
    protected $height = 0;
    protected $flags = 0b00000000;

    protected $lastPage = false;

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function __get($var)
    {
        return isset($this->$var)? $this->$var : '';
    }

    public function __isset($var)
    {
        return isset($this->$var);
    }

    public function addDataSource($alias, $className)
    {
        $this->sources[$alias] = $className;

        return $this;
    }
    
    public function addDataSources($sources)
    {
        $sources = is_string($sources)
            ? json_decode($sources) ?? []
            : $source ?? [];

        foreach ($sources as $alias => $className) {
            $this->addDataSource($alias, $className);
        }

        return $this;
    }

    public function addData($data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function getData($key = null, $default = null)
    {
        if (!is_null($key)) {
            return isset($this->data[$key])? $this->data[$key] : $default;
        }

        return $this->data;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    public function hasFlag($bitmask)
    {
        return ($this->flags & $bitmask) == $bitmask;
    }

    public function allowPageWrap()
    {
        $this->flags ^= self::NO_PAGE_WRAP;

        return $this;
    }

    public function preventPageWrap()
    {
        $this->flags |= self::NO_PAGE_WRAP;

        return $this;
    }

    public function pageBreakBefore()
    {
        $this->flags |= self::PAGE_BREAK_BEFORE;

        return $this;
    }

    public function pageBreakAfter()
    {
        $this->flags |= self::PAGE_BREAK_AFTER;

        return $this;
    }

    public function skipIfEmpty()
    {
        $this->flags |= self::SKIP_IF_EMPTY;

        return $this;
    }

    public function lastPage()
    {
        $this->flags |= self::IS_LAST_PAGE;

        return $this;
    }

    public function setX($x)
    {
        $this->x = $x;

        return $this;
    }

    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }

    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }
}
