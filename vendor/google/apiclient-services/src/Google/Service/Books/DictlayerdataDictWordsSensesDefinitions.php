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

class Google_Service_Books_DictlayerdataDictWordsSensesDefinitions extends Google_Collection
{
  protected $collection_key = 'examples';
  public $definition;
  protected $examplesType = 'Google_Service_Books_DictlayerdataDictWordsSensesDefinitionsExamples';
  protected $examplesDataType = 'array';

  public function setDefinition($definition)
  {
    $this->definition = $definition;
  }
  public function getDefinition()
  {
    return $this->definition;
  }
  public function setExamples($examples)
  {
    $this->examples = $examples;
  }
  public function getExamples()
  {
    return $this->examples;
  }
}
