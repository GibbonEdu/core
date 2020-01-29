<?php

namespace Gibbon\Module\Reports;

use Gibbon\Module\Reports\ReportSection;

class ReportTemplate
{
    const ALL_PAGES = 0;
    const FIRST_PAGE_ONLY = 1;
    const LAST_PAGE_ONLY = -1;

    protected $headers = array();
    protected $footers = array();
    protected $sections = array();
    protected $data = array();
    protected $draft = false;

    public function __construct()
    {
        
    }

    public function setIsDraft($draft)
    {
        $this->draft = $draft;

        return $this;
    }

    public function getIsDraft()
    {
        return $this->draft;
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

    public function addSection($section)
    {
        if ($section = $this->getOrCreateSection($section)) {
            $this->sections[] = $section;
        }

        return $section;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function getSourcesRequired()
    {
        return array_unique(array_reduce(array_merge($this->headers, $this->sections, $this->footers), function($carry, $item) {
            return array_merge($carry, $item->sources);
        }, array()));
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function addHeader($section, $pageNum = self::ALL_PAGES)
    {
        if ($section = $this->getOrCreateSection($section)) {
            $section->allowPageWrap();
            $this->headers[$pageNum] = $section; 
        }

        return $section;
    }

    public function getHeader($pageNum = 0, $lastPage = false)
    {
        if ($lastPage && isset($this->headers[self::LAST_PAGE_ONLY])) {
            return $this->headers[self::LAST_PAGE_ONLY];
        }

        if ($pageNum > 0 && isset($this->headers[$pageNum])) {
            return $this->headers[$pageNum];
        }

        if (isset($this->headers[self::ALL_PAGES])) {
            return $this->headers[self::ALL_PAGES];
        }

        return false;
    }

    public function getFooters()
    {
        return $this->footers;
    }

    public function addFooter($section, $pageNum = self::ALL_PAGES)
    {
        if ($section = $this->getOrCreateSection($section)) {
            $section->allowPageWrap();
            $this->footers[$pageNum] = $section; 
        }

        return $section;
    }

    public function getFooter($pageNum = 0, $lastPage = false)
    {
        if ($lastPage && isset($this->footers[self::LAST_PAGE_ONLY])) {
            return $this->footers[self::LAST_PAGE_ONLY];
        } 

        if ($pageNum > 0 && isset($this->footers[$pageNum])) {
            return $this->footers[$pageNum];
        }

        if (isset($this->footers[self::ALL_PAGES])) {
            return $this->footers[self::ALL_PAGES];
        }

        return false;
    }

    protected function getOrCreateSection($template)
    {
        if (is_string($template)) {
            return new ReportSection($template);
        }

        if ($template instanceof ReportSection) {
            return $template;
        }

        return false;
    }
}
