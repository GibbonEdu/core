<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_Sheets_ChartSpec extends Google_Model
{
  protected $backgroundColorType = 'Google_Service_Sheets_Color';
  protected $backgroundColorDataType = '';
  protected $basicChartType = 'Google_Service_Sheets_BasicChartSpec';
  protected $basicChartDataType = '';
  protected $bubbleChartType = 'Google_Service_Sheets_BubbleChartSpec';
  protected $bubbleChartDataType = '';
  protected $candlestickChartType = 'Google_Service_Sheets_CandlestickChartSpec';
  protected $candlestickChartDataType = '';
  public $fontName;
  public $hiddenDimensionStrategy;
  protected $histogramChartType = 'Google_Service_Sheets_HistogramChartSpec';
  protected $histogramChartDataType = '';
  public $maximized;
  protected $orgChartType = 'Google_Service_Sheets_OrgChartSpec';
  protected $orgChartDataType = '';
  protected $pieChartType = 'Google_Service_Sheets_PieChartSpec';
  protected $pieChartDataType = '';
  public $title;
  protected $titleTextFormatType = 'Google_Service_Sheets_TextFormat';
  protected $titleTextFormatDataType = '';

  /**
   * @param Google_Service_Sheets_Color
   */
  public function setBackgroundColor(Google_Service_Sheets_Color $backgroundColor)
  {
    $this->backgroundColor = $backgroundColor;
  }
  /**
   * @return Google_Service_Sheets_Color
   */
  public function getBackgroundColor()
  {
    return $this->backgroundColor;
  }
  /**
   * @param Google_Service_Sheets_BasicChartSpec
   */
  public function setBasicChart(Google_Service_Sheets_BasicChartSpec $basicChart)
  {
    $this->basicChart = $basicChart;
  }
  /**
   * @return Google_Service_Sheets_BasicChartSpec
   */
  public function getBasicChart()
  {
    return $this->basicChart;
  }
  /**
   * @param Google_Service_Sheets_BubbleChartSpec
   */
  public function setBubbleChart(Google_Service_Sheets_BubbleChartSpec $bubbleChart)
  {
    $this->bubbleChart = $bubbleChart;
  }
  /**
   * @return Google_Service_Sheets_BubbleChartSpec
   */
  public function getBubbleChart()
  {
    return $this->bubbleChart;
  }
  /**
   * @param Google_Service_Sheets_CandlestickChartSpec
   */
  public function setCandlestickChart(Google_Service_Sheets_CandlestickChartSpec $candlestickChart)
  {
    $this->candlestickChart = $candlestickChart;
  }
  /**
   * @return Google_Service_Sheets_CandlestickChartSpec
   */
  public function getCandlestickChart()
  {
    return $this->candlestickChart;
  }
  public function setFontName($fontName)
  {
    $this->fontName = $fontName;
  }
  public function getFontName()
  {
    return $this->fontName;
  }
  public function setHiddenDimensionStrategy($hiddenDimensionStrategy)
  {
    $this->hiddenDimensionStrategy = $hiddenDimensionStrategy;
  }
  public function getHiddenDimensionStrategy()
  {
    return $this->hiddenDimensionStrategy;
  }
  /**
   * @param Google_Service_Sheets_HistogramChartSpec
   */
  public function setHistogramChart(Google_Service_Sheets_HistogramChartSpec $histogramChart)
  {
    $this->histogramChart = $histogramChart;
  }
  /**
   * @return Google_Service_Sheets_HistogramChartSpec
   */
  public function getHistogramChart()
  {
    return $this->histogramChart;
  }
  public function setMaximized($maximized)
  {
    $this->maximized = $maximized;
  }
  public function getMaximized()
  {
    return $this->maximized;
  }
  /**
   * @param Google_Service_Sheets_OrgChartSpec
   */
  public function setOrgChart(Google_Service_Sheets_OrgChartSpec $orgChart)
  {
    $this->orgChart = $orgChart;
  }
  /**
   * @return Google_Service_Sheets_OrgChartSpec
   */
  public function getOrgChart()
  {
    return $this->orgChart;
  }
  /**
   * @param Google_Service_Sheets_PieChartSpec
   */
  public function setPieChart(Google_Service_Sheets_PieChartSpec $pieChart)
  {
    $this->pieChart = $pieChart;
  }
  /**
   * @return Google_Service_Sheets_PieChartSpec
   */
  public function getPieChart()
  {
    return $this->pieChart;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  /**
   * @param Google_Service_Sheets_TextFormat
   */
  public function setTitleTextFormat(Google_Service_Sheets_TextFormat $titleTextFormat)
  {
    $this->titleTextFormat = $titleTextFormat;
  }
  /**
   * @return Google_Service_Sheets_TextFormat
   */
  public function getTitleTextFormat()
  {
    return $this->titleTextFormat;
  }
}
