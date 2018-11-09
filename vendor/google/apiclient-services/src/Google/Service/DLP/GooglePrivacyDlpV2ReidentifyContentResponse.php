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

class Google_Service_DLP_GooglePrivacyDlpV2ReidentifyContentResponse extends Google_Model
{
  protected $itemType = 'Google_Service_DLP_GooglePrivacyDlpV2ContentItem';
  protected $itemDataType = '';
  protected $overviewType = 'Google_Service_DLP_GooglePrivacyDlpV2TransformationOverview';
  protected $overviewDataType = '';

  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2ContentItem
   */
  public function setItem(Google_Service_DLP_GooglePrivacyDlpV2ContentItem $item)
  {
    $this->item = $item;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2ContentItem
   */
  public function getItem()
  {
    return $this->item;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2TransformationOverview
   */
  public function setOverview(Google_Service_DLP_GooglePrivacyDlpV2TransformationOverview $overview)
  {
    $this->overview = $overview;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2TransformationOverview
   */
  public function getOverview()
  {
    return $this->overview;
  }
}
