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

class Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1WebhookResponse extends Google_Collection
{
  protected $collection_key = 'outputContexts';
  public $endInteraction;
  protected $followupEventInputType = 'Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1EventInput';
  protected $followupEventInputDataType = '';
  protected $fulfillmentMessagesType = 'Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1IntentMessage';
  protected $fulfillmentMessagesDataType = 'array';
  public $fulfillmentText;
  protected $outputContextsType = 'Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1Context';
  protected $outputContextsDataType = 'array';
  public $payload;
  public $source;

  public function setEndInteraction($endInteraction)
  {
    $this->endInteraction = $endInteraction;
  }
  public function getEndInteraction()
  {
    return $this->endInteraction;
  }
  /**
   * @param Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1EventInput
   */
  public function setFollowupEventInput(Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1EventInput $followupEventInput)
  {
    $this->followupEventInput = $followupEventInput;
  }
  /**
   * @return Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1EventInput
   */
  public function getFollowupEventInput()
  {
    return $this->followupEventInput;
  }
  /**
   * @param Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1IntentMessage
   */
  public function setFulfillmentMessages($fulfillmentMessages)
  {
    $this->fulfillmentMessages = $fulfillmentMessages;
  }
  /**
   * @return Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1IntentMessage
   */
  public function getFulfillmentMessages()
  {
    return $this->fulfillmentMessages;
  }
  public function setFulfillmentText($fulfillmentText)
  {
    $this->fulfillmentText = $fulfillmentText;
  }
  public function getFulfillmentText()
  {
    return $this->fulfillmentText;
  }
  /**
   * @param Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1Context
   */
  public function setOutputContexts($outputContexts)
  {
    $this->outputContexts = $outputContexts;
  }
  /**
   * @return Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1Context
   */
  public function getOutputContexts()
  {
    return $this->outputContexts;
  }
  public function setPayload($payload)
  {
    $this->payload = $payload;
  }
  public function getPayload()
  {
    return $this->payload;
  }
  public function setSource($source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
}
