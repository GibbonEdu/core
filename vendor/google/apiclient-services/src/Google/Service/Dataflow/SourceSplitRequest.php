<?php
/*
 * Copyright 2016 Google Inc.
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

class Google_Service_Dataflow_SourceSplitRequest extends Google_Model
{
  protected $optionsType = 'Google_Service_Dataflow_SourceSplitOptions';
  protected $optionsDataType = '';
  protected $sourceType = 'Google_Service_Dataflow_Source';
  protected $sourceDataType = '';

  public function setOptions(Google_Service_Dataflow_SourceSplitOptions $options)
  {
    $this->options = $options;
  }
  public function getOptions()
  {
    return $this->options;
  }
  public function setSource(Google_Service_Dataflow_Source $source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
}
