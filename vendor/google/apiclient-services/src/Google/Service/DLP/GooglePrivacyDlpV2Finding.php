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

class Google_Service_DLP_GooglePrivacyDlpV2Finding extends Google_Model
{
  public $createTime;
  protected $infoTypeType = 'Google_Service_DLP_GooglePrivacyDlpV2InfoType';
  protected $infoTypeDataType = '';
  public $likelihood;
  protected $locationType = 'Google_Service_DLP_GooglePrivacyDlpV2Location';
  protected $locationDataType = '';
  public $quote;
  protected $quoteInfoType = 'Google_Service_DLP_GooglePrivacyDlpV2QuoteInfo';
  protected $quoteInfoDataType = '';

  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2InfoType
   */
  public function setInfoType(Google_Service_DLP_GooglePrivacyDlpV2InfoType $infoType)
  {
    $this->infoType = $infoType;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2InfoType
   */
  public function getInfoType()
  {
    return $this->infoType;
  }
  public function setLikelihood($likelihood)
  {
    $this->likelihood = $likelihood;
  }
  public function getLikelihood()
  {
    return $this->likelihood;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2Location
   */
  public function setLocation(Google_Service_DLP_GooglePrivacyDlpV2Location $location)
  {
    $this->location = $location;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2Location
   */
  public function getLocation()
  {
    return $this->location;
  }
  public function setQuote($quote)
  {
    $this->quote = $quote;
  }
  public function getQuote()
  {
    return $this->quote;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2QuoteInfo
   */
  public function setQuoteInfo(Google_Service_DLP_GooglePrivacyDlpV2QuoteInfo $quoteInfo)
  {
    $this->quoteInfo = $quoteInfo;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2QuoteInfo
   */
  public function getQuoteInfo()
  {
    return $this->quoteInfo;
  }
}
