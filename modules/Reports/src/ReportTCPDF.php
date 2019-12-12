<?php

namespace Gibbon\Module\Reports;

/**
 * Wrapper for TCPDF to handle multi-page reports and dynamic header & footer content
 */
class ReportTCPDF extends \TCPDF
{
    protected $headerCallback;
    protected $footerCallback;

    protected $pageOffset = 0;
    protected $pageNumber = 1;
    protected $lastPage;

    protected $pageStarted = false;
    protected $outputStarted = false;

    public function getPageNumber($absolute = false)
    {
        return ($absolute)? $this->GetPage() : $this->pageNumber;
    }

    public function resetPageNumber()
    {
        $this->pageOffset = $this->GetPage();
        $this->lastPage = false;
        $this->pageStarted = false;
        $this->outputStarted = false;
    }

    public function isFirstPage()
    {
        return $this->getPageNumber() == $this->pageOffset;
    }

    public function isLastPage()
    {
        return $this->lastPage;
    }

    public function setLastPage($value)
    {
        $this->lastPage = boolval($value);
    }

    public function trimOverflow()
    {
        // Remove blank pages started from section-overflow
        if ($this->pageStarted && !$this->outputStarted) {
            $this->deletePage($this->GetPage()+1);
            $this->pageStarted = false;
        }
    }

    public function getPageData()
    {
        return array('pageNum' => $this->getPageNumber());
    }

    public function setHeaderCallback($callback)
    {
        $this->headerCallback = $callback;
    }

    public function setFooterCallback($callback)
    {
        $this->footerCallback = $callback;
    }

    public function Header()
    {
        $this->pageNumber = ($this->GetPage() - $this->pageOffset);
        $this->pageStarted = true;
        $this->outputStarted = false;

        if (!empty($this->headerCallback) && is_callable($this->headerCallback)) {
            call_user_func($this->headerCallback, $this);
        }
    }

    public function Footer()
    {
        $this->pageStarted = false;

        if (!empty($this->footerCallback) && is_callable($this->footerCallback)) {
            call_user_func($this->footerCallback, $this);
        }
    }

    public function writeHTMLTransaction($html, $transaction = false)
    {
        $this->outputStarted = true;

        if ($transaction) {
            $pageNum = $this->getPage();
            $this->startTransaction();
            $this->writeHTML($html);
            if ($this->getPage() != $pageNum) {
                $this->rollbackTransaction(true);
                $this->AddPage();
                $this->writeHTML($html);
            }
            $this->commitTransaction();
        } else {
            $this->writeHTML($html);
        }
    }
}
