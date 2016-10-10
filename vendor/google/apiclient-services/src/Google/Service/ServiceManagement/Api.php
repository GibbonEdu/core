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

class Google_Service_ServiceManagement_Api extends Google_Collection
{
  protected $collection_key = 'options';
  protected $methodsType = 'Google_Service_ServiceManagement_Method';
  protected $methodsDataType = 'array';
  protected $mixinsType = 'Google_Service_ServiceManagement_Mixin';
  protected $mixinsDataType = 'array';
  public $name;
  protected $optionsType = 'Google_Service_ServiceManagement_Option';
  protected $optionsDataType = 'array';
  protected $sourceContextType = 'Google_Service_ServiceManagement_SourceContext';
  protected $sourceContextDataType = '';
  public $syntax;
  public $version;

  public function setMethods($methods)
  {
    $this->methods = $methods;
  }
  public function getMethods()
  {
    return $this->methods;
  }
  public function setMixins($mixins)
  {
    $this->mixins = $mixins;
  }
  public function getMixins()
  {
    return $this->mixins;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOptions($options)
  {
    $this->options = $options;
  }
  public function getOptions()
  {
    return $this->options;
  }
  public function setSourceContext(Google_Service_ServiceManagement_SourceContext $sourceContext)
  {
    $this->sourceContext = $sourceContext;
  }
  public function getSourceContext()
  {
    return $this->sourceContext;
  }
  public function setSyntax($syntax)
  {
    $this->syntax = $syntax;
  }
  public function getSyntax()
  {
    return $this->syntax;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}
