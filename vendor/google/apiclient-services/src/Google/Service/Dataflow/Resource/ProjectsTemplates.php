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

/**
 * The "templates" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dataflowService = new Google_Service_Dataflow(...);
 *   $templates = $dataflowService->templates;
 *  </code>
 */
class Google_Service_Dataflow_Resource_ProjectsTemplates extends Google_Service_Resource
{
  /**
   * Creates a dataflow job from a template. (templates.create)
   *
   * @param string $projectId The project which owns the job.
   * @param Google_Service_Dataflow_CreateJobFromTemplateRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dataflow_Job
   */
  public function create($projectId, Google_Service_Dataflow_CreateJobFromTemplateRequest $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Dataflow_Job");
  }
}
