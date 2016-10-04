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

class Google_Service_Fusiontables_Column extends Google_Collection
{
  protected $collection_key = 'validValues';
  protected $baseColumnType = 'Google_Service_Fusiontables_ColumnBaseColumn';
  protected $baseColumnDataType = '';
  public $columnId;
  public $columnJsonSchema;
  public $columnPropertiesJson;
  public $description;
  public $formatPattern;
  public $graphPredicate;
  public $kind;
  public $name;
  public $type;
  public $validValues;
  public $validateData;

  public function setBaseColumn(Google_Service_Fusiontables_ColumnBaseColumn $baseColumn)
  {
    $this->baseColumn = $baseColumn;
  }
  public function getBaseColumn()
  {
    return $this->baseColumn;
  }
  public function setColumnId($columnId)
  {
    $this->columnId = $columnId;
  }
  public function getColumnId()
  {
    return $this->columnId;
  }
  public function setColumnJsonSchema($columnJsonSchema)
  {
    $this->columnJsonSchema = $columnJsonSchema;
  }
  public function getColumnJsonSchema()
  {
    return $this->columnJsonSchema;
  }
  public function setColumnPropertiesJson($columnPropertiesJson)
  {
    $this->columnPropertiesJson = $columnPropertiesJson;
  }
  public function getColumnPropertiesJson()
  {
    return $this->columnPropertiesJson;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setFormatPattern($formatPattern)
  {
    $this->formatPattern = $formatPattern;
  }
  public function getFormatPattern()
  {
    return $this->formatPattern;
  }
  public function setGraphPredicate($graphPredicate)
  {
    $this->graphPredicate = $graphPredicate;
  }
  public function getGraphPredicate()
  {
    return $this->graphPredicate;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setValidValues($validValues)
  {
    $this->validValues = $validValues;
  }
  public function getValidValues()
  {
    return $this->validValues;
  }
  public function setValidateData($validateData)
  {
    $this->validateData = $validateData;
  }
  public function getValidateData()
  {
    return $this->validateData;
  }
}
