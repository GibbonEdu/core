<?php

namespace Gibbon\Module\Reports;

use Gibbon\Module\Reports\ReportData;
use Gibbon\Module\Reports\ReportTemplate;

class ReportValidator
{
    protected $template;
    protected $missingSources = array();

    public function __construct(ReportTemplate $template)
    {
        $this->template = $template;
    }

    public static function create(ReportTemplate $template)
    {
        return new ReportValidator($template);
    }

    public function validate($input)
    {
        $reports = (is_array($input))? $input : array($input);

        $templateSources = $this->template->getSourcesRequired();
        $dataSources = array_reduce($reports, function ($sources, $reportData) {
            return array_merge($sources, $reportData->getDataSources());
        }, array());

        $this->missingSources = array_diff(array_keys($templateSources), $dataSources);

        return $this->isValid();
    }

    public function isValid()
    {
        return empty($this->missingSources);
    }

    public function getMissingSources()
    {
        return $this->missingSources;
    }
}
