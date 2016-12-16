<?php
/*
 * Copyright 2016 Google Inc.
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

class Google_Service_Sheets_ConditionalFormatRule extends Google_Collection
{
  protected $collection_key = 'ranges';
  protected $booleanRuleType = 'Google_Service_Sheets_BooleanRule';
  protected $booleanRuleDataType = '';
  protected $gradientRuleType = 'Google_Service_Sheets_GradientRule';
  protected $gradientRuleDataType = '';
  protected $rangesType = 'Google_Service_Sheets_GridRange';
  protected $rangesDataType = 'array';

  public function setBooleanRule(Google_Service_Sheets_BooleanRule $booleanRule)
  {
    $this->booleanRule = $booleanRule;
  }
  public function getBooleanRule()
  {
    return $this->booleanRule;
  }
  public function setGradientRule(Google_Service_Sheets_GradientRule $gradientRule)
  {
    $this->gradientRule = $gradientRule;
  }
  public function getGradientRule()
  {
    return $this->gradientRule;
  }
  public function setRanges($ranges)
  {
    $this->ranges = $ranges;
  }
  public function getRanges()
  {
    return $this->ranges;
  }
}
