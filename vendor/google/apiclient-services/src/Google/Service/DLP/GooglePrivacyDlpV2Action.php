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

class Google_Service_DLP_GooglePrivacyDlpV2Action extends Google_Model
{
  protected $pubSubType = 'Google_Service_DLP_GooglePrivacyDlpV2PublishToPubSub';
  protected $pubSubDataType = '';
  protected $publishSummaryToCsccType = 'Google_Service_DLP_GooglePrivacyDlpV2PublishSummaryToCscc';
  protected $publishSummaryToCsccDataType = '';
  protected $saveFindingsType = 'Google_Service_DLP_GooglePrivacyDlpV2SaveFindings';
  protected $saveFindingsDataType = '';

  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2PublishToPubSub
   */
  public function setPubSub(Google_Service_DLP_GooglePrivacyDlpV2PublishToPubSub $pubSub)
  {
    $this->pubSub = $pubSub;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2PublishToPubSub
   */
  public function getPubSub()
  {
    return $this->pubSub;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2PublishSummaryToCscc
   */
  public function setPublishSummaryToCscc(Google_Service_DLP_GooglePrivacyDlpV2PublishSummaryToCscc $publishSummaryToCscc)
  {
    $this->publishSummaryToCscc = $publishSummaryToCscc;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2PublishSummaryToCscc
   */
  public function getPublishSummaryToCscc()
  {
    return $this->publishSummaryToCscc;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2SaveFindings
   */
  public function setSaveFindings(Google_Service_DLP_GooglePrivacyDlpV2SaveFindings $saveFindings)
  {
    $this->saveFindings = $saveFindings;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2SaveFindings
   */
  public function getSaveFindings()
  {
    return $this->saveFindings;
  }
}
