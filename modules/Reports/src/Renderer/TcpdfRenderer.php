<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\Reports\Renderer;

use Gibbon\Module\Reports\Renderer\ReportRendererInterface;
use Gibbon\Module\Reports\ReportData;
use Gibbon\Module\Reports\ReportTemplate;
use Gibbon\Module\Reports\ReportSection;
use Gibbon\Module\Reports\ReportTCPDF;
use Twig\Environment;

class TcpdfRenderer implements ReportRendererInterface
{
    protected $template;
    protected $pdf;
    protected $twig;

    protected $absolutePath;
    protected $filename;

    protected $mode = 0;

    protected $preProcess = array();
    protected $postProcess = array();

    protected $profile;
    protected $microtime;

    public function __construct(Environment $templateEngine)
    {
        $this->twig = $templateEngine;
    }

    public function setMode(int $bitmask)
    {
        if ($bitmask == 0) $this->mode = 0;
        else $this->mode |= $bitmask;
    }

    public function hasMode(int $bitmask)
    {
        return ($this->mode & $bitmask) == $bitmask;
    }

    public function addPreProcess(string $name, callable $callable)
    {
        if (is_callable($callable)) {
            $this->preProcess[$name] = $callable;
        }
    }

    public function addPostProcess(string $name, callable $callable)
    {
        if (is_callable($callable)) {
            $this->postProcess[$name] = $callable;
        }
    }

    public function render(ReportTemplate $template, array $input, string $output = '')
    {
        $this->template = $template;
        $this->absolutePath = $template->getData('absolutePath');
        $this->absoluteURL = $template->getData('absoluteURL');
        $this->customAssetPath = $template->getData('customAssetPath');

        $customTemplatePath = $this->absolutePath . $this->customAssetPath . '/templates';
        if (is_dir($customTemplatePath)) {
            $this->twig->getLoader()->prependPath($customTemplatePath);
        }

        $reports = (is_array($input)) ? $input : array($input);
        $this->filename = $output;

        $this->setupDocument();

        foreach ($reports as $reportData) {
            if ($reportData instanceof ReportData) {
                $this->setupReport($reportData);
                $this->renderReport($reportData);
                $this->finishReport($reportData);
            }
        }

        if ($this->hasMode(self::OUTPUT_CONTINUOUS)) {
            $finalReport = end($reports);
            $outputPath = $this->getFilePath($finalReport);
            $this->finishDocument($outputPath);
        }
    }

    protected function renderReport(ReportData &$reportData)
    {
        $sections = $this->template->getSections();

        if (!empty($sections)) {
            foreach ($sections as $section) {
                $this->renderSection($section, $reportData);
            }
        }
    }

    protected function renderSection(ReportSection &$section, ReportData &$reportData)
    {
        $data = $reportData->getData(array_keys($section->sources));

        // Skip this section if any of the data sources are empty
        if ($section->hasFlag(ReportSection::SKIP_IF_EMPTY)) {
            if (count(array_filter($data)) != count($data)) {
                return;
            }
        }

        $this->pdf->setLastPage($section->lastPage);

        if ($section->hasFlag(ReportSection::PAGE_BREAK_BEFORE)) {
            $this->pdf->AddPage();
        }

        // Determine the footer before writing the section? For last pages ...
        if ($footer = $this->template->getFooter($this->pdf->getPageNumber(), $this->pdf->isLastPage())) {
            $this->pdf->SetAutoPageBreak(1, $footer->height + $footer->y);
        }

        if ($section->y != null) {
            $signed = substr($section->y, 0, 1);
            if ($signed === "+" || $signed === "-") {
                $this->pdf->setY($this->pdf->getY() + floatval($section->y), false);
            } else {
                $this->pdf->setY(floatval($section->y), false);
            }
        }

        if ($section->x != null) {
            $signed = substr($section->x, 0, 1);
            if ($signed === "+" || $signed === "-") {
                $this->pdf->setX($this->pdf->getX() + floatval($section->x));
            } else {
                $this->pdf->setX(floatval($section->x));
            }
        }

        $html = $this->renderSectionToHTML($section, $reportData);
        $this->pdf->writeHTMLTransaction($html, $section->hasFlag(ReportSection::NO_PAGE_WRAP));

        if ($section->hasFlag(ReportSection::PAGE_BREAK_AFTER)) {
            $this->pdf->AddPage();
        }

        $this->pdf->trimOverflow();
    }

    protected function renderSectionToHTML(ReportSection &$section, ReportData &$reportData)
    {
        $data = $reportData->getData(array_keys($section->sources));
        $data = array_merge($data, $this->template->getData(), $section->getData());

        // Render .twig templates using Twig
        if (stripos($section->template, '.twig') !== false) {
            return $this->twig->render($section->template, $data);
        }

        // Render .php templates by including the file, data is shared by scope
        if (stripos($section->template, '.php') !== false) {
            $pdf = $this->pdf;
            return include 'templates/' . $section->template;
        }

        return '';
    }

    protected function setupDocument()
    {
        $this->pdf = new ReportTCPDF($this->template->getData('orientation', 'P'), 'mm', strtoupper($this->template->getData('pageSize', 'A4')), true, 'UTF-8', false);
        $this->pdf->SetLeftMargin($this->template->getData('marginX', '10'));
        $this->pdf->SetRightMargin($this->template->getData('marginX', '10'));

        $this->template->addData([
            'basePath' => $this->absolutePath,
            'assetPath' => $this->absolutePath . $this->customAssetPath,
            'isDraft' => $this->template->getIsDraft(),
        ]);
    }

    protected function setupReport(ReportData &$reportData)
    {
        if (empty($this->pdf)) {
            $this->setupDocument();
        }

        $this->runPreProcess($reportData);

        $template = &$this->template;
        $twig = &$this->twig;

        // $this->pdf->writeHTML('<span></span>');
        $this->pdf->resetPageNumber();

        $this->pdf->SetHeaderMargin(0);
        $this->pdf->SetFooterMargin(0);

        if ($header = $template->getHeader($this->pdf->getPageNumber(), $this->pdf->isLastPage())) {
            $this->pdf->SetTopMargin($this->template->getData('marginY', '10') + $header->y + $header->height);
        }

        if ($footer = $template->getFooter($this->pdf->getPageNumber(), $this->pdf->isLastPage())) {
            $this->pdf->SetAutoPageBreak(1, $this->template->getData('marginY', '10') + $footer->height + $footer->y);
        }

        // Setting a dynamic callback allows runtime specific details (page numbers) to be inserted
        $this->pdf->setHeaderCallback(function ($pdf) use (&$reportData) {
            if ($this->template->getIsDraft()) {
                $pdf->writeHTMLCell(140, 20, 10, 4, $this->twig->render('draft.twig.html'));
            }

            if ($header = $this->template->getHeader($pdf->getPageNumber(), $pdf->isLastPage())) {
                $pdf->setX($header->x);
                $pdf->setY($this->template->getData('marginY', '10') + $header->y);

                $data = $reportData->getData(array_keys($header->sources));
                $data = array_merge($data, $this->template->getData(), $header->getData(), $pdf->getPageData());

                $html = $this->twig->render($header->template, $data);
                $pdf->writeHTML($html);

                $pdf->SetTopMargin($this->template->getData('marginY', '10') + $header->y + $header->height);
                $pdf->setY($this->template->getData('marginY', '10') + $header->y + $header->height);
            }

            if ($footer = $this->template->getFooter($pdf->getPageNumber(), $pdf->isLastPage())) {
                $pdf->SetAutoPageBreak(1, $this->template->getData('marginY', '10') + $footer->height + $footer->y);
            }
        });

        $this->pdf->setFooterCallback(function ($pdf) use (&$reportData) {
            if ($footer = $this->template->getFooter($pdf->getPageNumber(), $pdf->isLastPage())) {
                $pdf->setX($footer->x);
                $pdf->setY($footer->height * -1);
                $pdf->SetAutoPageBreak(1, $this->template->getData('marginY', '10') + $footer->height + $footer->y);

                $data = $reportData->getData(array_keys($footer->sources));
                $data = array_merge($data, $this->template->getData(), $footer->getData(), $pdf->getPageData());

                $html = $this->twig->render($footer->template, $data);
                $pdf->writeHTML($html);
            }

            if ($header = $this->template->getHeader($pdf->getPageNumber() + 1, $pdf->isLastPage())) {
                $pdf->SetTopMargin($this->template->getData('marginY', '10') + $header->y + $header->height);
            }
        });

        $this->pdf->addPage();
    }

    protected function finishDocument($outputPath)
    {
        if (!empty($this->pdf)) {
            if (!file_exists(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $this->pdf->Output($outputPath, 'F');
            unset($this->pdf);
        }
    }

    protected function finishReport(ReportData &$reportData)
    {
        $this->pdf->trimOverflow();
        $this->pdf->setLastPage(true);

        // Determine the footer before writing the section? For last pages ...
        if ($footer = $this->template->getFooter($this->pdf->getPageNumber(), $this->pdf->isLastPage())) {
            $this->pdf->SetAutoPageBreak(1, $footer->height + $footer->y);
        }

        $this->pdf->endPage();

        $this->runPostProcess($reportData);

        // Add a page with odd-numbered reports for two-sided printing
        if ($this->hasMode(self::OUTPUT_CONTINUOUS) && $this->hasMode(self::OUTPUT_TWO_SIDED)) {
            if ($this->pdf->getPageNumber(true) % 2 != 0) {
                $this->pdf->addPage();
                $this->pdf->setLastPage(false);
            }
        }

        // Finish the current page after a report for non-continuous output
        if ($this->hasMode(self::OUTPUT_CONTINUOUS) == false) {
            $outputPath = $this->getFilePath($reportData);
            $this->finishDocument($outputPath);
        }
    }

    protected function runPreProcess(ReportData &$reportData)
    {
        if (empty($reportData)) return;

        foreach ($this->preProcess as $name => $callable) {
            try {
                call_user_func($callable, $reportData);
            } catch (\Exception $e) {
                echo 'Error calling pre-process ' . $name;
                return;
            }
        }
    }

    protected function runPostProcess(ReportData &$reportData)
    {
        if (empty($reportData)) return;

        foreach ($this->postProcess as $name => $callable) {
            try {
                $outputPath = $this->getFilePath($reportData);
                call_user_func($callable, $reportData, $outputPath);
            } catch (\Exception $e) {
                echo 'Error calling post-process ' . $name;
                return;
            }
        }
    }

    protected function getFilePath(ReportData &$reportData)
    {
        $filename = 'output.pdf';

        if (is_callable($this->filename)) {
            $filename = call_user_func($this->filename, $this, $reportData);
        }

        if (is_string($this->filename)) {
            $filename = $this->filename;
        }

        return $filename;
    }
}
