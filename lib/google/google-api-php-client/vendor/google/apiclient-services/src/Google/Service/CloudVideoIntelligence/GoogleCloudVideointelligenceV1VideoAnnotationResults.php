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

class Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1VideoAnnotationResults extends Google_Collection
{
  protected $collection_key = 'shotAnnotations';
  protected $errorType = 'Google_Service_CloudVideoIntelligence_GoogleRpcStatus';
  protected $errorDataType = '';
  public $inputUri;
  protected $labelAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1LabelAnnotation';
  protected $labelAnnotationsDataType = 'array';
  protected $safeSearchAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1SafeSearchAnnotation';
  protected $safeSearchAnnotationsDataType = 'array';
  protected $shotAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1VideoSegment';
  protected $shotAnnotationsDataType = 'array';

  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleRpcStatus
   */
  public function setError(Google_Service_CloudVideoIntelligence_GoogleRpcStatus $error)
  {
    $this->error = $error;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleRpcStatus
   */
  public function getError()
  {
    return $this->error;
  }
  public function setInputUri($inputUri)
  {
    $this->inputUri = $inputUri;
  }
  public function getInputUri()
  {
    return $this->inputUri;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1LabelAnnotation
   */
  public function setLabelAnnotations($labelAnnotations)
  {
    $this->labelAnnotations = $labelAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1LabelAnnotation
   */
  public function getLabelAnnotations()
  {
    return $this->labelAnnotations;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1SafeSearchAnnotation
   */
  public function setSafeSearchAnnotations($safeSearchAnnotations)
  {
    $this->safeSearchAnnotations = $safeSearchAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1SafeSearchAnnotation
   */
  public function getSafeSearchAnnotations()
  {
    return $this->safeSearchAnnotations;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1VideoSegment
   */
  public function setShotAnnotations($shotAnnotations)
  {
    $this->shotAnnotations = $shotAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1VideoSegment
   */
  public function getShotAnnotations()
  {
    return $this->shotAnnotations;
  }
}
