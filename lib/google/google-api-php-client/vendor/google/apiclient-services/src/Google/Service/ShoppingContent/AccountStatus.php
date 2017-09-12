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

class Google_Service_ShoppingContent_AccountStatus extends Google_Collection
{
  protected $collection_key = 'dataQualityIssues';
  public $accountId;
  protected $dataQualityIssuesType = 'Google_Service_ShoppingContent_AccountStatusDataQualityIssue';
  protected $dataQualityIssuesDataType = 'array';
  public $kind;
  public $websiteClaimed;

  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  /**
   * @param Google_Service_ShoppingContent_AccountStatusDataQualityIssue
   */
  public function setDataQualityIssues($dataQualityIssues)
  {
    $this->dataQualityIssues = $dataQualityIssues;
  }
  /**
   * @return Google_Service_ShoppingContent_AccountStatusDataQualityIssue
   */
  public function getDataQualityIssues()
  {
    return $this->dataQualityIssues;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setWebsiteClaimed($websiteClaimed)
  {
    $this->websiteClaimed = $websiteClaimed;
  }
  public function getWebsiteClaimed()
  {
    return $this->websiteClaimed;
  }
}
