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

class Google_Service_Texttospeech_AudioConfig extends Google_Collection
{
  protected $collection_key = 'effectsProfileId';
  public $audioEncoding;
  public $effectsProfileId;
  public $pitch;
  public $sampleRateHertz;
  public $speakingRate;
  public $volumeGainDb;

  public function setAudioEncoding($audioEncoding)
  {
    $this->audioEncoding = $audioEncoding;
  }
  public function getAudioEncoding()
  {
    return $this->audioEncoding;
  }
  public function setEffectsProfileId($effectsProfileId)
  {
    $this->effectsProfileId = $effectsProfileId;
  }
  public function getEffectsProfileId()
  {
    return $this->effectsProfileId;
  }
  public function setPitch($pitch)
  {
    $this->pitch = $pitch;
  }
  public function getPitch()
  {
    return $this->pitch;
  }
  public function setSampleRateHertz($sampleRateHertz)
  {
    $this->sampleRateHertz = $sampleRateHertz;
  }
  public function getSampleRateHertz()
  {
    return $this->sampleRateHertz;
  }
  public function setSpeakingRate($speakingRate)
  {
    $this->speakingRate = $speakingRate;
  }
  public function getSpeakingRate()
  {
    return $this->speakingRate;
  }
  public function setVolumeGainDb($volumeGainDb)
  {
    $this->volumeGainDb = $volumeGainDb;
  }
  public function getVolumeGainDb()
  {
    return $this->volumeGainDb;
  }
}
