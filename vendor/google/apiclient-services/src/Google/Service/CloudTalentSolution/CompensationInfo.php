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

class Google_Service_CloudTalentSolution_CompensationInfo extends Google_Collection
{
  protected $collection_key = 'entries';
  protected $annualizedBaseCompensationRangeType = 'Google_Service_CloudTalentSolution_CompensationRange';
  protected $annualizedBaseCompensationRangeDataType = '';
  protected $annualizedTotalCompensationRangeType = 'Google_Service_CloudTalentSolution_CompensationRange';
  protected $annualizedTotalCompensationRangeDataType = '';
  protected $entriesType = 'Google_Service_CloudTalentSolution_CompensationEntry';
  protected $entriesDataType = 'array';

  /**
   * @param Google_Service_CloudTalentSolution_CompensationRange
   */
  public function setAnnualizedBaseCompensationRange(Google_Service_CloudTalentSolution_CompensationRange $annualizedBaseCompensationRange)
  {
    $this->annualizedBaseCompensationRange = $annualizedBaseCompensationRange;
  }
  /**
   * @return Google_Service_CloudTalentSolution_CompensationRange
   */
  public function getAnnualizedBaseCompensationRange()
  {
    return $this->annualizedBaseCompensationRange;
  }
  /**
   * @param Google_Service_CloudTalentSolution_CompensationRange
   */
  public function setAnnualizedTotalCompensationRange(Google_Service_CloudTalentSolution_CompensationRange $annualizedTotalCompensationRange)
  {
    $this->annualizedTotalCompensationRange = $annualizedTotalCompensationRange;
  }
  /**
   * @return Google_Service_CloudTalentSolution_CompensationRange
   */
  public function getAnnualizedTotalCompensationRange()
  {
    return $this->annualizedTotalCompensationRange;
  }
  /**
   * @param Google_Service_CloudTalentSolution_CompensationEntry
   */
  public function setEntries($entries)
  {
    $this->entries = $entries;
  }
  /**
   * @return Google_Service_CloudTalentSolution_CompensationEntry
   */
  public function getEntries()
  {
    return $this->entries;
  }
}
