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

class Google_Service_Vision_AnnotateFileResponse extends Google_Collection
{
  protected $collection_key = 'responses';
  protected $inputConfigType = 'Google_Service_Vision_InputConfig';
  protected $inputConfigDataType = '';
  protected $responsesType = 'Google_Service_Vision_AnnotateImageResponse';
  protected $responsesDataType = 'array';

  /**
   * @param Google_Service_Vision_InputConfig
   */
  public function setInputConfig(Google_Service_Vision_InputConfig $inputConfig)
  {
    $this->inputConfig = $inputConfig;
  }
  /**
   * @return Google_Service_Vision_InputConfig
   */
  public function getInputConfig()
  {
    return $this->inputConfig;
  }
  /**
   * @param Google_Service_Vision_AnnotateImageResponse
   */
  public function setResponses($responses)
  {
    $this->responses = $responses;
  }
  /**
   * @return Google_Service_Vision_AnnotateImageResponse
   */
  public function getResponses()
  {
    return $this->responses;
  }
}
