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
use Twig\Environment;

class HtmlRenderer implements ReportRendererInterface
{
    protected $template;
    protected $twig;

    protected $absolutePath;
    protected $absoluteURL;
    protected $customAssetPath;

    protected $mode = 0;
    protected $microtime = 0;

    protected $preProcess = [];
    protected $postProcess = [];

    public function __construct(Environment $templateEngine)
    {
        $this->microtime = microtime(true);

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

    public function render(ReportTemplate $template, array $input, string $output = '')
    {
        $this->template = $template;
        $this->absolutePath = $template->getData('absolutePath');
        $this->absoluteURL = $template->getData('absoluteURL');
        $this->customAssetPath = $template->getData('customAssetPath');

        $customTemplatePath = $this->absolutePath.$this->customAssetPath.'/templates';
        if (is_dir($customTemplatePath)) {
            $this->twig->getLoader()->prependPath($customTemplatePath);
        }

        $reports = (is_array($input))? $input : array($input);
        $html = '';
        $pages = [];
        $pageNum = 1;
        $this->template->addData([
            'pageNum' => $pageNum,
            'basePath' => $this->absoluteURL,
            'assetPath' => $this->absoluteURL.$this->customAssetPath,
            'isDraft' => $this->template->getIsDraft(),
        ]);

        foreach ($reports as $reportData) {
            
            if ($header = $this->template->getHeader($pageNum)) {
                $html .= '<header style="height: '.$header->height.'mm">'.$this->renderSectionToHTML($header, $reportData).'</header>';
            }

            $pageBreak = function ($html) use (&$pages, &$pageNum, &$reportData) {
                if ($footer = $this->template->getFooter($pageNum)) {
                    $html .= '<footer style="height: '.$footer->height.'mm">'.$this->renderSectionToHTML($footer, $reportData).'</footer>';
                }

                $pages[$pageNum] = $html;
                $html = '';
                $pageNum++;
                $this->template->addData(['pageNum' => $pageNum]);
                
                if ($header = $this->template->getHeader($pageNum)) {
                    $html .= '<header style="height: '.$header->height.'mm">'.$this->renderSectionToHTML($header, $reportData).'</header>';
                }

                return $html;
            };

            $sections = $this->template->getSections();
            foreach ($sections as $section) {

                if ($section->hasFlag(ReportSection::PAGE_BREAK_BEFORE)) {
                    $html = $pageBreak($html);
                }

                $html .= '<section>'.$this->renderSectionToHTML($section, $reportData).'</section>';

                if ($section->hasFlag(ReportSection::PAGE_BREAK_AFTER)) {
                    $html = $pageBreak($html);
                }
            }

            if ($footer = $this->template->getFooter($pageNum, true)) {
                $html .= '<footer style="height: '.$footer->height.'mm">'.$this->renderSectionToHTML($footer, $reportData).'</footer>';
            }
            
            $pages[$pageNum] = $html;
        }

        return $pages;
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

    protected function renderSectionToHTML(ReportSection &$section, ReportData &$reportData)
    {
        $data = $reportData->getData(array_keys($section->sources));

        // Skip this section if any of the data sources are empty
        if ($section->hasFlag(ReportSection::SKIP_IF_EMPTY)) {
            if (count(array_filter($data)) != count($data)) {
                return;
            }
        }
        
        $data = array_merge($data, $this->template->getData(), $section->getData());
        
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
}
