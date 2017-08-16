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

class Google_Service_Speech_LongRunningRecognizeRequest extends Google_Model
{
  protected $audioType = 'Google_Service_Speech_RecognitionAudio';
  protected $audioDataType = '';
  protected $configType = 'Google_Service_Speech_RecognitionConfig';
  protected $configDataType = '';

  /**
   * @param Google_Service_Speech_RecognitionAudio
   */
  public function setAudio(Google_Service_Speech_RecognitionAudio $audio)
  {
    $this->audio = $audio;
  }
  /**
   * @return Google_Service_Speech_RecognitionAudio
   */
  public function getAudio()
  {
    return $this->audio;
  }
  /**
   * @param Google_Service_Speech_RecognitionConfig
   */
  public function setConfig(Google_Service_Speech_RecognitionConfig $config)
  {
    $this->config = $config;
  }
  /**
   * @return Google_Service_Speech_RecognitionConfig
   */
  public function getConfig()
  {
    return $this->config;
  }
}
