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

class Google_Service_DLP_GooglePrivacyDlpV2RecordTransformations extends Google_Collection
{
  protected $collection_key = 'recordSuppressions';
  protected $fieldTransformationsType = 'Google_Service_DLP_GooglePrivacyDlpV2FieldTransformation';
  protected $fieldTransformationsDataType = 'array';
  protected $recordSuppressionsType = 'Google_Service_DLP_GooglePrivacyDlpV2RecordSuppression';
  protected $recordSuppressionsDataType = 'array';

  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2FieldTransformation
   */
  public function setFieldTransformations($fieldTransformations)
  {
    $this->fieldTransformations = $fieldTransformations;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2FieldTransformation
   */
  public function getFieldTransformations()
  {
    return $this->fieldTransformations;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2RecordSuppression
   */
  public function setRecordSuppressions($recordSuppressions)
  {
    $this->recordSuppressions = $recordSuppressions;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2RecordSuppression
   */
  public function getRecordSuppressions()
  {
    return $this->recordSuppressions;
  }
}
