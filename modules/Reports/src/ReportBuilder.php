<?php

namespace Gibbon\Module\Reports;

use Faker\Factory as FakerFactory;
use Faker\Provider\en_HK\Address;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Module\Reports\ReportTemplate;
use Gibbon\Module\Reports\DataFactory;
use Gibbon\Module\Reports\Contexts\ContextFactory;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\ReportSection;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;

class ReportBuilder
{
    protected $db;
    protected $dataFactory;
    protected $reportGateway;
    protected $reportTemplateGateway;
    protected $templateSectionGateway;
    protected $templateFontGateway;
    protected $templateGateway;
    protected $settingGateway;

    protected $absoluteURL;
    protected $absolutePath;
    protected $customAssetPath;

    public function __construct(Connection $db, DataFactory $dataFactory, ReportGateway $reportGateway, ReportTemplateGateway $templateGateway, ReportTemplateSectionGateway $templateSectionGateway, ReportTemplateFontGateway $templateFontGateway, SettingGateway $settingGateway)
    {
        $this->db = $db;
        $this->dataFactory = $dataFactory;
        $this->reportGateway = $reportGateway;
        $this->templateGateway = $templateGateway;
        $this->templateSectionGateway = $templateSectionGateway;
        $this->templateFontGateway = $templateFontGateway;
        $this->settingGateway = $settingGateway;

        $this->absolutePath = $this->settingGateway->getSettingByScope('System', 'absolutePath');
        $this->absoluteURL = $this->settingGateway->getSettingByScope('System', 'absoluteURL');
        $this->customAssetPath = $this->settingGateway->getSettingByScope('Reports', 'customAssetPath');
        
        $this->dataFactory->setAssetPath($this->absolutePath.$this->customAssetPath);
    }

    public function createTemplate() : ReportTemplate
    {
        $template = new ReportTemplate();

        $template->addData([
            'absolutePath'    => $this->absolutePath,
            'absoluteURL'     => $this->absoluteURL,
            'customAssetPath' => $this->customAssetPath,
        ]);

        return $template;
    }

    public function buildTemplate($gibbonReportTemplateID, $draft = false) : ReportTemplate
    {
        $template = $this->createTemplate();
        $template->setIsDraft($draft);

        $templateData = $this->templateGateway->getByID($gibbonReportTemplateID ?? '');
        $template->addData([
            'orientation' => $templateData['orientation'],
            'pageSize'    => $templateData['pageSize'],
            'marginX'     => $templateData['marginX'],
            'marginY'     => $templateData['marginY'],
            'stylesheet'  => $templateData['stylesheet'] ?? '',
            'flags'       => $templateData['flags'] ?? '',
        ]);

        $config = json_decode($templateData['config'] ?? '', true);
        if (!empty($config['fonts'])) {
            $fonts = $this->templateFontGateway->selectFontListByFamily($config['fonts'])->fetchAll();
            $fonts = array_reduce($fonts, function ($group, $font) {
                $fontPath = $this->absolutePath.$this->customAssetPath.'/fonts/'.basename($font['fontPath']);
                if (!is_file($fontPath)) return $group;

                $group[$font['fontFamily']][$font['fontType']] = basename($font['fontPath']);
                return $group;
            }, []);

            $template->addData(['fonts' => $fonts]);
        }

        $criteria = $this->templateSectionGateway->newQueryCriteria()
            ->sortBy('sequenceNumber', 'ASC')
            ->fromPOST();

        $sections = $this->templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID);
        foreach ($sections as $sectionData) {
            switch ($sectionData['type']) {
                case 'Header':
                    $section = $template->addHeader($sectionData['templateFile'], $sectionData['page']);
                    break;
                case 'Body':
                    $section = $template->addSection($sectionData['templateFile']);
                    break;

                case 'Footer':
                    $section = $template->addFooter($sectionData['templateFile'], $sectionData['page']);
                    break;
            }

            $section->addDataSources($sectionData['dataSources'])
                    ->setFlags($sectionData['flags']);

            if ($params = json_decode($sectionData['templateParams'] ?? '', true)) {
                $section->setHeight(!empty($params['height']) ? $params['height'] : $section->height)
                        ->setWidth(!empty($params['width']) ? $params['width'] : $section->width)
                        ->setX(!empty($params['x']) ? $params['x'] : $section->x)
                        ->setY(!empty($params['y']) ? $params['y'] : $section->y);
            }

            if ($config = json_decode($sectionData['config'] ?? '', true)) {
                $section->addData(['config' => $config]);
            }
        }
        
        return $template;
    }

    public function buildReportBatch(ReportTemplate $template, $report, $contextData = '') : array
    {
        $templateData = $this->templateGateway->getByID($report['gibbonReportTemplateID'] ?? '');

        $context = ContextFactory::create($templateData['context']);
        $contextData = !empty($contextData)? $contextData : $report['gibbonYearGroupIDList'];
        $ids = $context->getIdentifiers($this->db, $report['gibbonReportID'], $contextData);
        $ids = array_map(function ($id)  use (&$report) {
            $id['gibbonReportID'] = $report['gibbonReportID'];
            return $id;
        }, $ids);

        return $this->dataFactory->buildReportData($ids, $template->getSourcesRequired());
    }

    public function buildReportSingle(ReportTemplate $template, $report, $ids) : array
    {
        $ids['gibbonReportID'] = $report['gibbonReportID'];

        return $this->dataFactory->buildReportData([$ids], $template->getSourcesRequired());
    }

    public function buildReportMock(ReportTemplate $template) : array
    {
        $faker = FakerFactory::create();
        $faker->addProvider(new Address($faker));
        
        return $this->dataFactory->buildMockData($faker, $template->getSourcesRequired());
    }
}
