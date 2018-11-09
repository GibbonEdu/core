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

class Google_Service_DLP_GooglePrivacyDlpV2KAnonymityConfig extends Google_Collection
{
  protected $collection_key = 'quasiIds';
  protected $entityIdType = 'Google_Service_DLP_GooglePrivacyDlpV2EntityId';
  protected $entityIdDataType = '';
  protected $quasiIdsType = 'Google_Service_DLP_GooglePrivacyDlpV2FieldId';
  protected $quasiIdsDataType = 'array';

  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2EntityId
   */
  public function setEntityId(Google_Service_DLP_GooglePrivacyDlpV2EntityId $entityId)
  {
    $this->entityId = $entityId;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2EntityId
   */
  public function getEntityId()
  {
    return $this->entityId;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2FieldId
   */
  public function setQuasiIds($quasiIds)
  {
    $this->quasiIds = $quasiIds;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2FieldId
   */
  public function getQuasiIds()
  {
    return $this->quasiIds;
  }
}
