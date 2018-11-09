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

class Google_Service_Slides_Image extends Google_Model
{
  public $contentUrl;
  protected $imagePropertiesType = 'Google_Service_Slides_ImageProperties';
  protected $imagePropertiesDataType = '';
  public $sourceUrl;

  public function setContentUrl($contentUrl)
  {
    $this->contentUrl = $contentUrl;
  }
  public function getContentUrl()
  {
    return $this->contentUrl;
  }
  /**
   * @param Google_Service_Slides_ImageProperties
   */
  public function setImageProperties(Google_Service_Slides_ImageProperties $imageProperties)
  {
    $this->imageProperties = $imageProperties;
  }
  /**
   * @return Google_Service_Slides_ImageProperties
   */
  public function getImageProperties()
  {
    return $this->imageProperties;
  }
  public function setSourceUrl($sourceUrl)
  {
    $this->sourceUrl = $sourceUrl;
  }
  public function getSourceUrl()
  {
    return $this->sourceUrl;
  }
}
