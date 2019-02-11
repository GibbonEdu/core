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

class Google_Service_Slides_Line extends Google_Model
{
  public $lineCategory;
  protected $linePropertiesType = 'Google_Service_Slides_LineProperties';
  protected $linePropertiesDataType = '';
  public $lineType;

  public function setLineCategory($lineCategory)
  {
    $this->lineCategory = $lineCategory;
  }
  public function getLineCategory()
  {
    return $this->lineCategory;
  }
  /**
   * @param Google_Service_Slides_LineProperties
   */
  public function setLineProperties(Google_Service_Slides_LineProperties $lineProperties)
  {
    $this->lineProperties = $lineProperties;
  }
  /**
   * @return Google_Service_Slides_LineProperties
   */
  public function getLineProperties()
  {
    return $this->lineProperties;
  }
  public function setLineType($lineType)
  {
    $this->lineType = $lineType;
  }
  public function getLineType()
  {
    return $this->lineType;
  }
}
