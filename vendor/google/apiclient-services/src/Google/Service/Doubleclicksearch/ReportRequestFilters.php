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

class Google_Service_Doubleclicksearch_ReportRequestFilters extends Google_Collection
{
  protected $collection_key = 'values';
  protected $columnType = 'Google_Service_Doubleclicksearch_ReportApiColumnSpec';
  protected $columnDataType = '';
  public $operator;
  public $values;

  public function setColumn(Google_Service_Doubleclicksearch_ReportApiColumnSpec $column)
  {
    $this->column = $column;
  }
  public function getColumn()
  {
    return $this->column;
  }
  public function setOperator($operator)
  {
    $this->operator = $operator;
  }
  public function getOperator()
  {
    return $this->operator;
  }
  public function setValues($values)
  {
    $this->values = $values;
  }
  public function getValues()
  {
    return $this->values;
  }
}
