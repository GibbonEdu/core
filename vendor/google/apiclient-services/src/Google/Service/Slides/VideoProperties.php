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

class Google_Service_Slides_VideoProperties extends Google_Model
{
  public $autoPlay;
  public $end;
  public $mute;
  protected $outlineType = 'Google_Service_Slides_Outline';
  protected $outlineDataType = '';
  public $start;

  public function setAutoPlay($autoPlay)
  {
    $this->autoPlay = $autoPlay;
  }
  public function getAutoPlay()
  {
    return $this->autoPlay;
  }
  public function setEnd($end)
  {
    $this->end = $end;
  }
  public function getEnd()
  {
    return $this->end;
  }
  public function setMute($mute)
  {
    $this->mute = $mute;
  }
  public function getMute()
  {
    return $this->mute;
  }
  /**
   * @param Google_Service_Slides_Outline
   */
  public function setOutline(Google_Service_Slides_Outline $outline)
  {
    $this->outline = $outline;
  }
  /**
   * @return Google_Service_Slides_Outline
   */
  public function getOutline()
  {
    return $this->outline;
  }
  public function setStart($start)
  {
    $this->start = $start;
  }
  public function getStart()
  {
    return $this->start;
  }
}
