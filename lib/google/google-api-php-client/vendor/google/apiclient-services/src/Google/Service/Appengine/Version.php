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

class Google_Service_Appengine_Version extends Google_Collection
{
  protected $collection_key = 'libraries';
  protected $apiConfigType = 'Google_Service_Appengine_ApiConfigHandler';
  protected $apiConfigDataType = '';
  protected $automaticScalingType = 'Google_Service_Appengine_AutomaticScaling';
  protected $automaticScalingDataType = '';
  protected $basicScalingType = 'Google_Service_Appengine_BasicScaling';
  protected $basicScalingDataType = '';
  public $betaSettings;
  public $createTime;
  public $createdBy;
  public $defaultExpiration;
  protected $deploymentType = 'Google_Service_Appengine_Deployment';
  protected $deploymentDataType = '';
  public $diskUsageBytes;
  public $env;
  public $envVariables;
  protected $errorHandlersType = 'Google_Service_Appengine_ErrorHandler';
  protected $errorHandlersDataType = 'array';
  protected $handlersType = 'Google_Service_Appengine_UrlMap';
  protected $handlersDataType = 'array';
  protected $healthCheckType = 'Google_Service_Appengine_HealthCheck';
  protected $healthCheckDataType = '';
  public $id;
  public $inboundServices;
  public $instanceClass;
  protected $librariesType = 'Google_Service_Appengine_Library';
  protected $librariesDataType = 'array';
  protected $manualScalingType = 'Google_Service_Appengine_ManualScaling';
  protected $manualScalingDataType = '';
  public $name;
  protected $networkType = 'Google_Service_Appengine_Network';
  protected $networkDataType = '';
  public $nobuildFilesRegex;
  protected $resourcesType = 'Google_Service_Appengine_Resources';
  protected $resourcesDataType = '';
  public $runtime;
  public $servingStatus;
  public $threadsafe;
  public $versionUrl;
  public $vm;

  public function setApiConfig(Google_Service_Appengine_ApiConfigHandler $apiConfig)
  {
    $this->apiConfig = $apiConfig;
  }
  public function getApiConfig()
  {
    return $this->apiConfig;
  }
  public function setAutomaticScaling(Google_Service_Appengine_AutomaticScaling $automaticScaling)
  {
    $this->automaticScaling = $automaticScaling;
  }
  public function getAutomaticScaling()
  {
    return $this->automaticScaling;
  }
  public function setBasicScaling(Google_Service_Appengine_BasicScaling $basicScaling)
  {
    $this->basicScaling = $basicScaling;
  }
  public function getBasicScaling()
  {
    return $this->basicScaling;
  }
  public function setBetaSettings($betaSettings)
  {
    $this->betaSettings = $betaSettings;
  }
  public function getBetaSettings()
  {
    return $this->betaSettings;
  }
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setCreatedBy($createdBy)
  {
    $this->createdBy = $createdBy;
  }
  public function getCreatedBy()
  {
    return $this->createdBy;
  }
  public function setDefaultExpiration($defaultExpiration)
  {
    $this->defaultExpiration = $defaultExpiration;
  }
  public function getDefaultExpiration()
  {
    return $this->defaultExpiration;
  }
  public function setDeployment(Google_Service_Appengine_Deployment $deployment)
  {
    $this->deployment = $deployment;
  }
  public function getDeployment()
  {
    return $this->deployment;
  }
  public function setDiskUsageBytes($diskUsageBytes)
  {
    $this->diskUsageBytes = $diskUsageBytes;
  }
  public function getDiskUsageBytes()
  {
    return $this->diskUsageBytes;
  }
  public function setEnv($env)
  {
    $this->env = $env;
  }
  public function getEnv()
  {
    return $this->env;
  }
  public function setEnvVariables($envVariables)
  {
    $this->envVariables = $envVariables;
  }
  public function getEnvVariables()
  {
    return $this->envVariables;
  }
  public function setErrorHandlers($errorHandlers)
  {
    $this->errorHandlers = $errorHandlers;
  }
  public function getErrorHandlers()
  {
    return $this->errorHandlers;
  }
  public function setHandlers($handlers)
  {
    $this->handlers = $handlers;
  }
  public function getHandlers()
  {
    return $this->handlers;
  }
  public function setHealthCheck(Google_Service_Appengine_HealthCheck $healthCheck)
  {
    $this->healthCheck = $healthCheck;
  }
  public function getHealthCheck()
  {
    return $this->healthCheck;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInboundServices($inboundServices)
  {
    $this->inboundServices = $inboundServices;
  }
  public function getInboundServices()
  {
    return $this->inboundServices;
  }
  public function setInstanceClass($instanceClass)
  {
    $this->instanceClass = $instanceClass;
  }
  public function getInstanceClass()
  {
    return $this->instanceClass;
  }
  public function setLibraries($libraries)
  {
    $this->libraries = $libraries;
  }
  public function getLibraries()
  {
    return $this->libraries;
  }
  public function setManualScaling(Google_Service_Appengine_ManualScaling $manualScaling)
  {
    $this->manualScaling = $manualScaling;
  }
  public function getManualScaling()
  {
    return $this->manualScaling;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNetwork(Google_Service_Appengine_Network $network)
  {
    $this->network = $network;
  }
  public function getNetwork()
  {
    return $this->network;
  }
  public function setNobuildFilesRegex($nobuildFilesRegex)
  {
    $this->nobuildFilesRegex = $nobuildFilesRegex;
  }
  public function getNobuildFilesRegex()
  {
    return $this->nobuildFilesRegex;
  }
  public function setResources(Google_Service_Appengine_Resources $resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
  public function setRuntime($runtime)
  {
    $this->runtime = $runtime;
  }
  public function getRuntime()
  {
    return $this->runtime;
  }
  public function setServingStatus($servingStatus)
  {
    $this->servingStatus = $servingStatus;
  }
  public function getServingStatus()
  {
    return $this->servingStatus;
  }
  public function setThreadsafe($threadsafe)
  {
    $this->threadsafe = $threadsafe;
  }
  public function getThreadsafe()
  {
    return $this->threadsafe;
  }
  public function setVersionUrl($versionUrl)
  {
    $this->versionUrl = $versionUrl;
  }
  public function getVersionUrl()
  {
    return $this->versionUrl;
  }
  public function setVm($vm)
  {
    $this->vm = $vm;
  }
  public function getVm()
  {
    return $this->vm;
  }
}
