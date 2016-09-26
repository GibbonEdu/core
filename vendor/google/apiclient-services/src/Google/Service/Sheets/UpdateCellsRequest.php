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

class Google_Service_Sheets_UpdateCellsRequest extends Google_Collection
{
  protected $collection_key = 'rows';
  public $fields;
  protected $rangeType = 'Google_Service_Sheets_GridRange';
  protected $rangeDataType = '';
  protected $rowsType = 'Google_Service_Sheets_RowData';
  protected $rowsDataType = 'array';
  protected $startType = 'Google_Service_Sheets_GridCoordinate';
  protected $startDataType = '';

  public function setFields($fields)
  {
    $this->fields = $fields;
  }
  public function getFields()
  {
    return $this->fields;
  }
  public function setRange(Google_Service_Sheets_GridRange $range)
  {
    $this->range = $range;
  }
  public function getRange()
  {
    return $this->range;
  }
  public function setRows($rows)
  {
    $this->rows = $rows;
  }
  public function getRows()
  {
    return $this->rows;
  }
  public function setStart(Google_Service_Sheets_GridCoordinate $start)
  {
    $this->start = $start;
  }
  public function getStart()
  {
    return $this->start;
  }
}
