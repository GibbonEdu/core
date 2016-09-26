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
 * Service definition for AdExchangeBuyerII (v2beta1).
 *
 * <p>
 * Accesses the latest features for managing Ad Exchange accounts and Real-Time
 * Bidding configurations and auction metrics.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/ad-exchange/buyer-rest/guides/client-access/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_AdExchangeBuyerII extends Google_Service
{
  /** Manage your Ad Exchange buyer account configuration. */
  const ADEXCHANGE_BUYER =
      "https://www.googleapis.com/auth/adexchange.buyer";

  public $accounts_clients;
  public $accounts_clients_invitations;
  public $accounts_clients_users;
  
  /**
   * Constructs the internal representation of the AdExchangeBuyerII service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://adexchangebuyer.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v2beta1';
    $this->serviceName = 'adexchangebuyer2';

    $this->accounts_clients = new Google_Service_AdExchangeBuyerII_Resource_AccountsClients(
        $this,
        $this->serviceName,
        'clients',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients',
              'httpMethod' => 'POST',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_clients_invitations = new Google_Service_AdExchangeBuyerII_Resource_AccountsClientsInvitations(
        $this,
        $this->serviceName,
        'invitations',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}/invitations',
              'httpMethod' => 'POST',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}/invitations/{invitationId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'invitationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}/invitations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_clients_users = new Google_Service_AdExchangeBuyerII_Resource_AccountsClientsUsers(
        $this,
        $this->serviceName,
        'users',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}/users/{userId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}/users',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'v2beta1/accounts/{accountId}/clients/{clientAccountId}/users/{userId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
  }
}
