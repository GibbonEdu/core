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

class Google_Service_ServiceManagement_Service extends Google_Collection
{
  protected $collection_key = 'types';
  protected $apisType = 'Google_Service_ServiceManagement_Api';
  protected $apisDataType = 'array';
  protected $authenticationType = 'Google_Service_ServiceManagement_Authentication';
  protected $authenticationDataType = '';
  protected $backendType = 'Google_Service_ServiceManagement_Backend';
  protected $backendDataType = '';
  public $configVersion;
  protected $contextType = 'Google_Service_ServiceManagement_Context';
  protected $contextDataType = '';
  protected $controlType = 'Google_Service_ServiceManagement_Control';
  protected $controlDataType = '';
  protected $customErrorType = 'Google_Service_ServiceManagement_CustomError';
  protected $customErrorDataType = '';
  protected $documentationType = 'Google_Service_ServiceManagement_Documentation';
  protected $documentationDataType = '';
  protected $endpointsType = 'Google_Service_ServiceManagement_Endpoint';
  protected $endpointsDataType = 'array';
  protected $enumsType = 'Google_Service_ServiceManagement_Enum';
  protected $enumsDataType = 'array';
  protected $httpType = 'Google_Service_ServiceManagement_Http';
  protected $httpDataType = '';
  public $id;
  protected $loggingType = 'Google_Service_ServiceManagement_Logging';
  protected $loggingDataType = '';
  protected $logsType = 'Google_Service_ServiceManagement_LogDescriptor';
  protected $logsDataType = 'array';
  protected $metricsType = 'Google_Service_ServiceManagement_MetricDescriptor';
  protected $metricsDataType = 'array';
  protected $monitoredResourcesType = 'Google_Service_ServiceManagement_MonitoredResourceDescriptor';
  protected $monitoredResourcesDataType = 'array';
  protected $monitoringType = 'Google_Service_ServiceManagement_Monitoring';
  protected $monitoringDataType = '';
  public $name;
  public $producerProjectId;
  protected $systemParametersType = 'Google_Service_ServiceManagement_SystemParameters';
  protected $systemParametersDataType = '';
  protected $systemTypesType = 'Google_Service_ServiceManagement_Type';
  protected $systemTypesDataType = 'array';
  public $title;
  protected $typesType = 'Google_Service_ServiceManagement_Type';
  protected $typesDataType = 'array';
  protected $usageType = 'Google_Service_ServiceManagement_Usage';
  protected $usageDataType = '';
  protected $visibilityType = 'Google_Service_ServiceManagement_Visibility';
  protected $visibilityDataType = '';

  public function setApis($apis)
  {
    $this->apis = $apis;
  }
  public function getApis()
  {
    return $this->apis;
  }
  public function setAuthentication(Google_Service_ServiceManagement_Authentication $authentication)
  {
    $this->authentication = $authentication;
  }
  public function getAuthentication()
  {
    return $this->authentication;
  }
  public function setBackend(Google_Service_ServiceManagement_Backend $backend)
  {
    $this->backend = $backend;
  }
  public function getBackend()
  {
    return $this->backend;
  }
  public function setConfigVersion($configVersion)
  {
    $this->configVersion = $configVersion;
  }
  public function getConfigVersion()
  {
    return $this->configVersion;
  }
  public function setContext(Google_Service_ServiceManagement_Context $context)
  {
    $this->context = $context;
  }
  public function getContext()
  {
    return $this->context;
  }
  public function setControl(Google_Service_ServiceManagement_Control $control)
  {
    $this->control = $control;
  }
  public function getControl()
  {
    return $this->control;
  }
  public function setCustomError(Google_Service_ServiceManagement_CustomError $customError)
  {
    $this->customError = $customError;
  }
  public function getCustomError()
  {
    return $this->customError;
  }
  public function setDocumentation(Google_Service_ServiceManagement_Documentation $documentation)
  {
    $this->documentation = $documentation;
  }
  public function getDocumentation()
  {
    return $this->documentation;
  }
  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  public function setEnums($enums)
  {
    $this->enums = $enums;
  }
  public function getEnums()
  {
    return $this->enums;
  }
  public function setHttp(Google_Service_ServiceManagement_Http $http)
  {
    $this->http = $http;
  }
  public function getHttp()
  {
    return $this->http;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setLogging(Google_Service_ServiceManagement_Logging $logging)
  {
    $this->logging = $logging;
  }
  public function getLogging()
  {
    return $this->logging;
  }
  public function setLogs($logs)
  {
    $this->logs = $logs;
  }
  public function getLogs()
  {
    return $this->logs;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
  public function setMonitoredResources($monitoredResources)
  {
    $this->monitoredResources = $monitoredResources;
  }
  public function getMonitoredResources()
  {
    return $this->monitoredResources;
  }
  public function setMonitoring(Google_Service_ServiceManagement_Monitoring $monitoring)
  {
    $this->monitoring = $monitoring;
  }
  public function getMonitoring()
  {
    return $this->monitoring;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setProducerProjectId($producerProjectId)
  {
    $this->producerProjectId = $producerProjectId;
  }
  public function getProducerProjectId()
  {
    return $this->producerProjectId;
  }
  public function setSystemParameters(Google_Service_ServiceManagement_SystemParameters $systemParameters)
  {
    $this->systemParameters = $systemParameters;
  }
  public function getSystemParameters()
  {
    return $this->systemParameters;
  }
  public function setSystemTypes($systemTypes)
  {
    $this->systemTypes = $systemTypes;
  }
  public function getSystemTypes()
  {
    return $this->systemTypes;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setTypes($types)
  {
    $this->types = $types;
  }
  public function getTypes()
  {
    return $this->types;
  }
  public function setUsage(Google_Service_ServiceManagement_Usage $usage)
  {
    $this->usage = $usage;
  }
  public function getUsage()
  {
    return $this->usage;
  }
  public function setVisibility(Google_Service_ServiceManagement_Visibility $visibility)
  {
    $this->visibility = $visibility;
  }
  public function getVisibility()
  {
    return $this->visibility;
  }
}
