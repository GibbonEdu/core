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

class Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1VideoAnnotationResults extends Google_Collection
{
  protected $collection_key = 'textAnnotations';
  protected $errorType = 'Google_Service_CloudVideoIntelligence_GoogleRpcStatus';
  protected $errorDataType = '';
  protected $explicitAnnotationType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ExplicitContentAnnotation';
  protected $explicitAnnotationDataType = '';
  protected $frameLabelAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation';
  protected $frameLabelAnnotationsDataType = 'array';
  public $inputUri;
  protected $objectAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ObjectTrackingAnnotation';
  protected $objectAnnotationsDataType = 'array';
  protected $segmentLabelAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation';
  protected $segmentLabelAnnotationsDataType = 'array';
  protected $shotAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1VideoSegment';
  protected $shotAnnotationsDataType = 'array';
  protected $shotLabelAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation';
  protected $shotLabelAnnotationsDataType = 'array';
  protected $textAnnotationsType = 'Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1TextAnnotation';
  protected $textAnnotationsDataType = 'array';

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
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ExplicitContentAnnotation
   */
  public function setExplicitAnnotation(Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ExplicitContentAnnotation $explicitAnnotation)
  {
    $this->explicitAnnotation = $explicitAnnotation;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ExplicitContentAnnotation
   */
  public function getExplicitAnnotation()
  {
    return $this->explicitAnnotation;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation
   */
  public function setFrameLabelAnnotations($frameLabelAnnotations)
  {
    $this->frameLabelAnnotations = $frameLabelAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation
   */
  public function getFrameLabelAnnotations()
  {
    return $this->frameLabelAnnotations;
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
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ObjectTrackingAnnotation
   */
  public function setObjectAnnotations($objectAnnotations)
  {
    $this->objectAnnotations = $objectAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1ObjectTrackingAnnotation
   */
  public function getObjectAnnotations()
  {
    return $this->objectAnnotations;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation
   */
  public function setSegmentLabelAnnotations($segmentLabelAnnotations)
  {
    $this->segmentLabelAnnotations = $segmentLabelAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation
   */
  public function getSegmentLabelAnnotations()
  {
    return $this->segmentLabelAnnotations;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1VideoSegment
   */
  public function setShotAnnotations($shotAnnotations)
  {
    $this->shotAnnotations = $shotAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1VideoSegment
   */
  public function getShotAnnotations()
  {
    return $this->shotAnnotations;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation
   */
  public function setShotLabelAnnotations($shotLabelAnnotations)
  {
    $this->shotLabelAnnotations = $shotLabelAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1LabelAnnotation
   */
  public function getShotLabelAnnotations()
  {
    return $this->shotLabelAnnotations;
  }
  /**
   * @param Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1TextAnnotation
   */
  public function setTextAnnotations($textAnnotations)
  {
    $this->textAnnotations = $textAnnotations;
  }
  /**
   * @return Google_Service_CloudVideoIntelligence_GoogleCloudVideointelligenceV1p2beta1TextAnnotation
   */
  public function getTextAnnotations()
  {
    return $this->textAnnotations;
  }
}
