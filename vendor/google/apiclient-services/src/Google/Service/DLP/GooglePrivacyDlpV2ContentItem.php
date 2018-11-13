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

class Google_Service_DLP_GooglePrivacyDlpV2ContentItem extends Google_Model
{
  protected $byteItemType = 'Google_Service_DLP_GooglePrivacyDlpV2ByteContentItem';
  protected $byteItemDataType = '';
  protected $tableType = 'Google_Service_DLP_GooglePrivacyDlpV2Table';
  protected $tableDataType = '';
  public $value;

  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2ByteContentItem
   */
  public function setByteItem(Google_Service_DLP_GooglePrivacyDlpV2ByteContentItem $byteItem)
  {
    $this->byteItem = $byteItem;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2ByteContentItem
   */
  public function getByteItem()
  {
    return $this->byteItem;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2Table
   */
  public function setTable(Google_Service_DLP_GooglePrivacyDlpV2Table $table)
  {
    $this->table = $table;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2Table
   */
  public function getTable()
  {
    return $this->table;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}
