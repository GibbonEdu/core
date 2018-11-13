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

class Google_Service_ServiceUsage_GoogleApiService extends Google_Collection
{
  protected $collection_key = 'types';
  protected $apisType = 'Google_Service_ServiceUsage_Api';
  protected $apisDataType = 'array';
  protected $authenticationType = 'Google_Service_ServiceUsage_Authentication';
  protected $authenticationDataType = '';
  protected $backendType = 'Google_Service_ServiceUsage_Backend';
  protected $backendDataType = '';
  protected $billingType = 'Google_Service_ServiceUsage_Billing';
  protected $billingDataType = '';
  public $configVersion;
  protected $contextType = 'Google_Service_ServiceUsage_Context';
  protected $contextDataType = '';
  protected $controlType = 'Google_Service_ServiceUsage_Control';
  protected $controlDataType = '';
  protected $customErrorType = 'Google_Service_ServiceUsage_CustomError';
  protected $customErrorDataType = '';
  protected $documentationType = 'Google_Service_ServiceUsage_Documentation';
  protected $documentationDataType = '';
  protected $endpointsType = 'Google_Service_ServiceUsage_Endpoint';
  protected $endpointsDataType = 'array';
  protected $enumsType = 'Google_Service_ServiceUsage_Enum';
  protected $enumsDataType = 'array';
  protected $experimentalType = 'Google_Service_ServiceUsage_Experimental';
  protected $experimentalDataType = '';
  protected $httpType = 'Google_Service_ServiceUsage_Http';
  protected $httpDataType = '';
  public $id;
  protected $loggingType = 'Google_Service_ServiceUsage_Logging';
  protected $loggingDataType = '';
  protected $logsType = 'Google_Service_ServiceUsage_LogDescriptor';
  protected $logsDataType = 'array';
  protected $metricsType = 'Google_Service_ServiceUsage_MetricDescriptor';
  protected $metricsDataType = 'array';
  protected $monitoredResourcesType = 'Google_Service_ServiceUsage_MonitoredResourceDescriptor';
  protected $monitoredResourcesDataType = 'array';
  protected $monitoringType = 'Google_Service_ServiceUsage_Monitoring';
  protected $monitoringDataType = '';
  public $name;
  public $producerProjectId;
  protected $quotaType = 'Google_Service_ServiceUsage_Quota';
  protected $quotaDataType = '';
  protected $sourceInfoType = 'Google_Service_ServiceUsage_SourceInfo';
  protected $sourceInfoDataType = '';
  protected $systemParametersType = 'Google_Service_ServiceUsage_SystemParameters';
  protected $systemParametersDataType = '';
  protected $systemTypesType = 'Google_Service_ServiceUsage_Type';
  protected $systemTypesDataType = 'array';
  public $title;
  protected $typesType = 'Google_Service_ServiceUsage_Type';
  protected $typesDataType = 'array';
  protected $usageType = 'Google_Service_ServiceUsage_Usage';
  protected $usageDataType = '';

  /**
   * @param Google_Service_ServiceUsage_Api
   */
  public function setApis($apis)
  {
    $this->apis = $apis;
  }
  /**
   * @return Google_Service_ServiceUsage_Api
   */
  public function getApis()
  {
    return $this->apis;
  }
  /**
   * @param Google_Service_ServiceUsage_Authentication
   */
  public function setAuthentication(Google_Service_ServiceUsage_Authentication $authentication)
  {
    $this->authentication = $authentication;
  }
  /**
   * @return Google_Service_ServiceUsage_Authentication
   */
  public function getAuthentication()
  {
    return $this->authentication;
  }
  /**
   * @param Google_Service_ServiceUsage_Backend
   */
  public function setBackend(Google_Service_ServiceUsage_Backend $backend)
  {
    $this->backend = $backend;
  }
  /**
   * @return Google_Service_ServiceUsage_Backend
   */
  public function getBackend()
  {
    return $this->backend;
  }
  /**
   * @param Google_Service_ServiceUsage_Billing
   */
  public function setBilling(Google_Service_ServiceUsage_Billing $billing)
  {
    $this->billing = $billing;
  }
  /**
   * @return Google_Service_ServiceUsage_Billing
   */
  public function getBilling()
  {
    return $this->billing;
  }
  public function setConfigVersion($configVersion)
  {
    $this->configVersion = $configVersion;
  }
  public function getConfigVersion()
  {
    return $this->configVersion;
  }
  /**
   * @param Google_Service_ServiceUsage_Context
   */
  public function setContext(Google_Service_ServiceUsage_Context $context)
  {
    $this->context = $context;
  }
  /**
   * @return Google_Service_ServiceUsage_Context
   */
  public function getContext()
  {
    return $this->context;
  }
  /**
   * @param Google_Service_ServiceUsage_Control
   */
  public function setControl(Google_Service_ServiceUsage_Control $control)
  {
    $this->control = $control;
  }
  /**
   * @return Google_Service_ServiceUsage_Control
   */
  public function getControl()
  {
    return $this->control;
  }
  /**
   * @param Google_Service_ServiceUsage_CustomError
   */
  public function setCustomError(Google_Service_ServiceUsage_CustomError $customError)
  {
    $this->customError = $customError;
  }
  /**
   * @return Google_Service_ServiceUsage_CustomError
   */
  public function getCustomError()
  {
    return $this->customError;
  }
  /**
   * @param Google_Service_ServiceUsage_Documentation
   */
  public function setDocumentation(Google_Service_ServiceUsage_Documentation $documentation)
  {
    $this->documentation = $documentation;
  }
  /**
   * @return Google_Service_ServiceUsage_Documentation
   */
  public function getDocumentation()
  {
    return $this->documentation;
  }
  /**
   * @param Google_Service_ServiceUsage_Endpoint
   */
  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  /**
   * @return Google_Service_ServiceUsage_Endpoint
   */
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  /**
   * @param Google_Service_ServiceUsage_Enum
   */
  public function setEnums($enums)
  {
    $this->enums = $enums;
  }
  /**
   * @return Google_Service_ServiceUsage_Enum
   */
  public function getEnums()
  {
    return $this->enums;
  }
  /**
   * @param Google_Service_ServiceUsage_Experimental
   */
  public function setExperimental(Google_Service_ServiceUsage_Experimental $experimental)
  {
    $this->experimental = $experimental;
  }
  /**
   * @return Google_Service_ServiceUsage_Experimental
   */
  public function getExperimental()
  {
    return $this->experimental;
  }
  /**
   * @param Google_Service_ServiceUsage_Http
   */
  public function setHttp(Google_Service_ServiceUsage_Http $http)
  {
    $this->http = $http;
  }
  /**
   * @return Google_Service_ServiceUsage_Http
   */
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
  /**
   * @param Google_Service_ServiceUsage_Logging
   */
  public function setLogging(Google_Service_ServiceUsage_Logging $logging)
  {
    $this->logging = $logging;
  }
  /**
   * @return Google_Service_ServiceUsage_Logging
   */
  public function getLogging()
  {
    return $this->logging;
  }
  /**
   * @param Google_Service_ServiceUsage_LogDescriptor
   */
  public function setLogs($logs)
  {
    $this->logs = $logs;
  }
  /**
   * @return Google_Service_ServiceUsage_LogDescriptor
   */
  public function getLogs()
  {
    return $this->logs;
  }
  /**
   * @param Google_Service_ServiceUsage_MetricDescriptor
   */
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  /**
   * @return Google_Service_ServiceUsage_MetricDescriptor
   */
  public function getMetrics()
  {
    return $this->metrics;
  }
  /**
   * @param Google_Service_ServiceUsage_MonitoredResourceDescriptor
   */
  public function setMonitoredResources($monitoredResources)
  {
    $this->monitoredResources = $monitoredResources;
  }
  /**
   * @return Google_Service_ServiceUsage_MonitoredResourceDescriptor
   */
  public function getMonitoredResources()
  {
    return $this->monitoredResources;
  }
  /**
   * @param Google_Service_ServiceUsage_Monitoring
   */
  public function setMonitoring(Google_Service_ServiceUsage_Monitoring $monitoring)
  {
    $this->monitoring = $monitoring;
  }
  /**
   * @return Google_Service_ServiceUsage_Monitoring
   */
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
  /**
   * @param Google_Service_ServiceUsage_Quota
   */
  public function setQuota(Google_Service_ServiceUsage_Quota $quota)
  {
    $this->quota = $quota;
  }
  /**
   * @return Google_Service_ServiceUsage_Quota
   */
  public function getQuota()
  {
    return $this->quota;
  }
  /**
   * @param Google_Service_ServiceUsage_SourceInfo
   */
  public function setSourceInfo(Google_Service_ServiceUsage_SourceInfo $sourceInfo)
  {
    $this->sourceInfo = $sourceInfo;
  }
  /**
   * @return Google_Service_ServiceUsage_SourceInfo
   */
  public function getSourceInfo()
  {
    return $this->sourceInfo;
  }
  /**
   * @param Google_Service_ServiceUsage_SystemParameters
   */
  public function setSystemParameters(Google_Service_ServiceUsage_SystemParameters $systemParameters)
  {
    $this->systemParameters = $systemParameters;
  }
  /**
   * @return Google_Service_ServiceUsage_SystemParameters
   */
  public function getSystemParameters()
  {
    return $this->systemParameters;
  }
  /**
   * @param Google_Service_ServiceUsage_Type
   */
  public function setSystemTypes($systemTypes)
  {
    $this->systemTypes = $systemTypes;
  }
  /**
   * @return Google_Service_ServiceUsage_Type
   */
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
  /**
   * @param Google_Service_ServiceUsage_Type
   */
  public function setTypes($types)
  {
    $this->types = $types;
  }
  /**
   * @return Google_Service_ServiceUsage_Type
   */
  public function getTypes()
  {
    return $this->types;
  }
  /**
   * @param Google_Service_ServiceUsage_Usage
   */
  public function setUsage(Google_Service_ServiceUsage_Usage $usage)
  {
    $this->usage = $usage;
  }
  /**
   * @return Google_Service_ServiceUsage_Usage
   */
  public function getUsage()
  {
    return $this->usage;
  }
}
