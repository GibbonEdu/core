<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Module\Reports\ReportData;
use Gibbon\Module\Reports\ReportTemplate;
use Gibbon\Module\Reports\ReportSection;
use Gibbon\Module\Reports\Renderer\ReportRendererInterface;
use Mpdf\Mpdf as Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Twig\Environment;

class MpdfRenderer implements ReportRendererInterface
{
    protected $template;
    protected $pdf;
    protected $twig;

    protected $absolutePath;
    protected $filename;

    protected $mode = 0;
    protected $firstPage = true;
    protected $lastPage = false;

    protected $preProcess = array();
    protected $postProcess = array();

    public function __construct(Environment $templateEngine)
    {
        $this->twig = $templateEngine;
    }

    public function setMode(int $bitmask)
    {
        $this->mode |= $bitmask;
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
        $this->customAssetPath = $template->getData('customAssetPath');
        $this->firstPage = true;
        $this->lastPage = false;

        $customTemplatePath = $this->absolutePath.$this->customAssetPath.'/templates';
        if (is_dir($customTemplatePath)) {
            $this->twig->getLoader()->prependPath($customTemplatePath);
        }

        $reports = (is_array($input))? $input : array($input);
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

        $this->lastPage = $section->lastPage || $this->lastPage;

        $this->setHeader();

        if ($section->hasFlag(ReportSection::PAGE_BREAK_BEFORE) || $this->firstPage) {
            $this->pdf->AddPageByArray(['suppress' => 'off']);
        }
        
        $this->setFooter();

        $html = $this->renderSectionToHTML($section, $reportData);

        if ($section->x != null && $section->y != null) {
            $this->pdf->WriteFixedPosHTML($html, floatval($section->x), floatval($section->y), !empty($section->width) ? $section->width : '100%', !empty($section->height) ? $section->height : '100%', 'visible');
        } else {
            $this->pdf->writeHTML($html);
        }

        $this->firstPage = false;

        if ($section->hasFlag(ReportSection::PAGE_BREAK_AFTER)) {
            $this->pdf->AddPageByArray([]);
        }
    }

    protected function renderSectionToHTML(ReportSection &$section, ReportData &$reportData)
    {
        $data = $reportData->getData(array_keys($section->sources));
        $data = array_merge($data, $this->template->getData(), $section->getData(), ['stylesheet' => '']);
        
        // Render .twig templates using Twig
        if (stripos($section->template, '.twig') !== false) {
            return $this->twig->render($section->template, $data);
        }
        
        // Render .php templates by including the file, data is shared by scope
        if (stripos($section->template, '.php') !== false) {
            $pdf = $this->pdf;
            return include 'templates/'.$section->template;
        }

        return '';
    }

    protected function setupDocument()
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'] ?? [];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'] ?? [];

        $config = [
            'mode' => 'utf-8',
            'format' => $this->template->getData('pageSize', 'A4') == 'letter' ? [215.9, 279.4] : [210, 297],
            'orientation' => $this->template->getData('orientation', 'P'),
            // 'useOddEven' => $this->hasMode(self::OUTPUT_TWO_SIDED) ? '1' : '0',
            // 'mirrorMargins' => $this->hasMode(self::OUTPUT_TWO_SIDED) ? '1' : '0',
            'useOddEven' => '0',
            'mirrorMargins' => '0',
            
            'margin_top' => $this->template->getData('marginY', '10'),
            'margin_bottom' => $this->template->getData('marginY', '10'),
            'margin_left' => $this->template->getData('marginX', '10'),
            'margin_right' => $this->template->getData('marginX', '10'),

            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
            'autoMarginPadding' => 1,

            'shrink_tables_to_fit' => 0,
            'defaultPagebreakType' => 'cloneall',
            
            'tempDir' =>  $this->absolutePath.'/uploads/reports/temp',
            'fontDir' => array_merge($fontDirs, [
                $this->absolutePath.$this->customAssetPath.'/fonts',
            ]),

            'fontdata' => $fontData + $this->template->getData('fonts', []),
            'default_font' => 'sans-serif',
        ];

        $stylesheetPath = $this->absolutePath.'/modules/Reports/templates/'.$this->template->getData('stylesheet');
        if (is_file($stylesheetPath)) {
            $config['defaultCssFile'] = $stylesheetPath;
        } else {
            $stylesheetPath = $this->absolutePath.$this->customAssetPath.'/templates/'.$this->template->getData('stylesheet');
            if (is_file($stylesheetPath)) {
                $config['defaultCssFile'] = $stylesheetPath;
            }
        }

        $this->pdf = new Mpdf($config);

        $this->template->addData([
            'basePath' => $this->absolutePath,
            'assetPath' => $this->absolutePath.$this->customAssetPath,
            'isDraft' => $this->template->getIsDraft(),
        ]);
    }

    protected function setupReport(ReportData &$reportData)
    {
        if (empty($this->pdf)) {
            $this->setupDocument();
        }

        $this->runPreProcess($reportData);

        // Define Headers
        $this->headers = $this->template->getHeaders();
        
        foreach ($this->headers as $index => $header) {
            $data = $reportData->getData(array_keys($header->sources));
            $data = array_merge($data, $this->template->getData(), $header->getData(), ['stylesheet' => '']);

            $this->pdf->DefHTMLHeaderByName('header'.$index, $this->twig->render($header->template, $data));
        }

        // Define Footers
        $this->footers = $this->template->getFooters();

        foreach ($this->footers as $index => $footer) {
            $data = $reportData->getData(array_keys($footer->sources));
            $data = array_merge($data, $this->template->getData(), $footer->getData(), ['stylesheet' => '']);

            $this->pdf->DefHTMLFooterByName('footer'.$index, $this->twig->render($footer->template, $data));
        }

        // Watermark
        if ($this->template->getIsDraft()) {
            $this->pdf->SetWatermarkText(__('DRAFT COPY. THIS IS NOT A FINAL REPORT.'), 0.05);
            $this->pdf->showWatermarkText = true;
        }

        $this->setFooter();
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
        $this->template->addData(['lastPage' => true]);
        $this->lastPage = true;
        
        $this->setHeader();
        $this->setFooter();

        $this->runPostProcess($reportData);

        // Add a page with odd-numbered reports for two-sided printing
        if ($this->hasMode(self::OUTPUT_TWO_SIDED & self::OUTPUT_CONTINUOUS)) {
            // $this->pdf->AddPageByArray([
            //     'type' => 'ODD',
            //     'resetpagenum' => 1,
            //     'suppress' => 'on',
            //     'odd-header-name' => '',
            //     'even-header-name' => '',
            //     'odd-footer-name' => '',
            //     'even-footer-name' => '',
            // ]);
        }
        
        $this->pdf->PageNumSubstitutions[] = [
            'from' => 1,
            'reset' => 1,
            'type' => '1',
            'suppress' => 'off'
        ];

        // Continue the current document after a report for continuous output
        if ($this->hasMode(self::OUTPUT_CONTINUOUS)) {
            $this->firstPage = true;
            $this->lastPage = false;
        } else {
            $outputPath = $this->getFilePath($reportData);
            $this->finishDocument($outputPath);
        }
    }

    protected function setHeader()
    {
        if (empty($this->headers)) return;

        $pageNum = $this->lastPage && !$this->firstPage ? -1 : $this->pdf->getPageNumber() + 1;
        $defaultHeader = isset($this->headers[0])? 'header0' : false;
        $headerName = isset($this->headers[$pageNum])? 'header'.$pageNum : $defaultHeader;

        // if ($this->hasMode(self::OUTPUT_TWO_SIDED)) {
        //     $this->pdf->SetHTMLHeaderByName($headerName, $pageNum % 2 == 0 || $this->firstPage ? 'O' : 'E', $this->lastPage);
        // } else {
            $this->pdf->SetHTMLHeaderByName($headerName, 'O', $this->lastPage);
        // }
    }

    protected function setFooter()
    {
        if (empty($this->footers)) return;

        $pageNum = $this->lastPage && !$this->firstPage ? -1 : $this->pdf->getPageNumber();
        $defaultFooter = isset($this->footers[0])? 'footer0' : false;
        $footerName = isset($this->footers[$pageNum])? 'footer'.$pageNum : $defaultFooter;

        // if ($this->hasMode(self::OUTPUT_TWO_SIDED)) {
        //     $this->pdf->SetHTMLFooterByName($footerName, $pageNum % 2 == 0 ? 'E' : 'O');
        // } else {
            $this->pdf->SetHTMLFooterByName($footerName, 'O');
        // }
    }

    protected function runPreProcess(ReportData &$reportData)
    {
        if (empty($reportData)) return;

        foreach ($this->preProcess as $name => $callable) {
            try {
                call_user_func($callable, $reportData);
            } catch (\Exception $e) {
                echo 'Error calling pre-process '.$name;
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
                echo 'Error calling post-process '.$name;
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
