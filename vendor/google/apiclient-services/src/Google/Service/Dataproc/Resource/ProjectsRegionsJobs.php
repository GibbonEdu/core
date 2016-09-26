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
 * The "jobs" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dataprocService = new Google_Service_Dataproc(...);
 *   $jobs = $dataprocService->jobs;
 *  </code>
 */
class Google_Service_Dataproc_Resource_ProjectsRegionsJobs extends Google_Service_Resource
{
  /**
   * Starts a job cancellation request. To access the job resource after
   * cancellation, call [regions/{region}/jobs.list](/dataproc/reference/rest/v1/p
   * rojects.regions.jobs/list) or [regions/{region}/jobs.get](/dataproc/reference
   * /rest/v1/projects.regions.jobs/get). (jobs.cancel)
   *
   * @param string $projectId [Required] The ID of the Google Cloud Platform
   * project that the job belongs to.
   * @param string $region [Required] The Cloud Dataproc region in which to handle
   * the request.
   * @param string $jobId [Required] The job ID.
   * @param Google_Service_Dataproc_CancelJobRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dataproc_Job
   */
  public function cancel($projectId, $region, $jobId, Google_Service_Dataproc_CancelJobRequest $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'jobId' => $jobId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('cancel', array($params), "Google_Service_Dataproc_Job");
  }
  /**
   * Deletes the job from the project. If the job is active, the delete fails, and
   * the response returns `FAILED_PRECONDITION`. (jobs.delete)
   *
   * @param string $projectId [Required] The ID of the Google Cloud Platform
   * project that the job belongs to.
   * @param string $region [Required] The Cloud Dataproc region in which to handle
   * the request.
   * @param string $jobId [Required] The job ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dataproc_DataprocEmpty
   */
  public function delete($projectId, $region, $jobId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'jobId' => $jobId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Dataproc_DataprocEmpty");
  }
  /**
   * Gets the resource representation for a job in a project. (jobs.get)
   *
   * @param string $projectId [Required] The ID of the Google Cloud Platform
   * project that the job belongs to.
   * @param string $region [Required] The Cloud Dataproc region in which to handle
   * the request.
   * @param string $jobId [Required] The job ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dataproc_Job
   */
  public function get($projectId, $region, $jobId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'jobId' => $jobId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dataproc_Job");
  }
  /**
   * Lists regions/{region}/jobs in a project. (jobs.listProjectsRegionsJobs)
   *
   * @param string $projectId [Required] The ID of the Google Cloud Platform
   * project that the job belongs to.
   * @param string $region [Required] The Cloud Dataproc region in which to handle
   * the request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize [Optional] The number of results to return in each
   * response.
   * @opt_param string pageToken [Optional] The page token, returned by a previous
   * call, to request the next page of results.
   * @opt_param string clusterName [Optional] If set, the returned jobs list
   * includes only jobs that were submitted to the named cluster.
   * @opt_param string jobStateMatcher [Optional] Specifies enumerated categories
   * of jobs to list.
   * @return Google_Service_Dataproc_ListJobsResponse
   */
  public function listProjectsRegionsJobs($projectId, $region, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dataproc_ListJobsResponse");
  }
  /**
   * Submits a job to a cluster. (jobs.submit)
   *
   * @param string $projectId [Required] The ID of the Google Cloud Platform
   * project that the job belongs to.
   * @param string $region [Required] The Cloud Dataproc region in which to handle
   * the request.
   * @param Google_Service_Dataproc_SubmitJobRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dataproc_Job
   */
  public function submit($projectId, $region, Google_Service_Dataproc_SubmitJobRequest $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'region' => $region, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('submit', array($params), "Google_Service_Dataproc_Job");
  }
}
