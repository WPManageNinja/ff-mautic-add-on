<?php

namespace FluentFormMautic\Integrations;

class API
{
    protected $apiUrl = '';

    protected $clientId = null;

    protected $clientSecret = null;

    protected $callBackUrl = null;

    protected $settings = [];

    public function __construct($apiUrl, $settings)
    {
        if (substr($apiUrl, -1) == '/') {
            $apiUrl = substr($apiUrl, 0, -1);
        }

        $this->apiUrl = $apiUrl;
        $this->clientId = $settings['client_id'];
        $this->clientSecret = $settings['client_secret'];
        $this->settings = $settings;
        $this->callBackUrl = admin_url('?ff_mautic_auth=1');
    }

    public function redirectToAuthServer()
    {
        $url = add_query_arg([
            'client_id'     => $this->clientId,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->callBackUrl,
            'response_type' => 'code',
            'state'         => md5($this->clientId)
        ], $this->apiUrl . '/oauth/v2/authorize');

        wp_redirect($url);
        exit();
    }

    public function generateAccessToken($code, $settings)
    {
        $response = wp_remote_post($this->apiUrl . '/oauth/v2/token', [
            'body' => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $this->callBackUrl,
                'code'          => $code
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $body = \json_decode($body, true);

        if (isset($body['error_description'])) {
            return new \WP_Error(423, $body['error_description']);
        }

        $settings['access_token'] = $body['access_token'];
        $settings['refresh_token'] = $body['refresh_token'];
        $settings['expire_at'] = time() + intval($body['expires_in']);
        return $settings;
    }

    public function makeRequest($action, $data = array(), $method = 'GET')
    {
        $settings = $this->getApiSettings();
        if (is_wp_error($settings)) {
            return $settings;
        }

        $url = $this->apiUrl . '/api/' . $action;

        $headers = [
            'Authorization'  => " Bearer ". $settings['access_token'],
        ];

        $response = false;
        if ($method == 'GET') {
            $response = wp_remote_get($url, [
                'headers' => $headers
            ]);
        } elseif ($method == 'POST') {
            $response = wp_remote_post($url, [
                'headers' => $headers,
                'body' => $data
            ]);
        }

        if (!$response) {
            return new \WP_Error(423, __('Request could not be performed', 'ffmauticaddon'));
        }

        if (is_wp_error($response)) {
            return new \WP_Error(423, $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $body = \json_decode($body, true);
        $code = wp_remote_retrieve_response_code($response);

        if (isset($body['errors'])) {
            if (!empty($body['errors'][0]['message'])) {
                $message = $body['errors'][0]['message'];
            } elseif (!empty($body['error_description'])) {
                $message = $body['error_description'];
            } else {
                $message = __('Error when requesting to API Server', 'ffmauticaddon');
            }

            return new \WP_Error($code, $message);
        }

        return $body;
    }

    protected function getApiSettings()
    {
        $response = $this->maybeRefreshToken();

        if (is_wp_error($response)) {
            return $response;
        }

        $apiSettings = $this->settings;

        if (!$apiSettings['status'] || !$apiSettings['expire_at']) {
            return new \WP_Error(423, __('API key is invalid', 'ffmauticaddon'));
        }

        return array(
            'baseUrl'       => $this->apiUrl,       // Base URL of the Mautic instance
            'version'       => 'OAuth2', // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
            'clientKey'     => $this->clientId,       // Client/Consumer key from Mautic
            'clientSecret'  => $this->clientSecret,       // Client/Consumer secret key from Mautic
            'callback'      => $this->callBackUrl,        // Redirect URI/Callback URI for this script
            'access_token'  => $apiSettings['access_token'],
            'refresh_token' => $apiSettings['refresh_token'],
            'expire_at'     => $apiSettings['expire_at']
        );
    }

    protected function maybeRefreshToken()
    {
        $settings = $this->settings;
        $expireAt = $settings['expire_at'];

        if ($expireAt && $expireAt <= (time() - 10)) {
            // we have to regenerate the tokens
            $response = wp_remote_post($this->apiUrl . '/oauth/v2/token', [
                'body' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $settings['refresh_token'],
                    'redirect_uri'  => $this->callBackUrl
                ]
            ]);

            $body = wp_remote_retrieve_body($response);
            $body = \json_decode($body, true);

            if (is_wp_error($response)) {
                return $response;
            }

            if (isset($body['errors'])) {
                if (!empty($body['errors'][0]['message'])) {
                    $message = $body['errors'][0]['message'];
                } elseif (!empty($body['error_description'])) {
                    $message = $body['error_description'];
                } else {
                    $message = 'Error when requesting OAuth token';
                }

                return new \WP_Error(423, $message);
            }

            $settings['access_token'] = $body['access_token'];
            $settings['refresh_token'] = $body['refresh_token'];
            $settings['expire_at'] = time() + intval($body['expires_in']);
            $this->settings = $settings;
            update_option('_fluentform_mautic_settings', $settings, 'no');
            return true;
        }
    }

    public function listAvailableFields()
    {
        $response = $this->makeRequest('contacts/list/fields', [], 'GET');

        if (!is_wp_error($response)) {
            return $response;
        };

        return false;
    }

    public function subscribe($subscriber)
    {
        $response = $this->makeRequest('contacts/new', $subscriber, 'POST');

        if (is_wp_error($response)) {
            return new \WP_Error($response->get_error_code(), $response->get_error_message());
        }

        if ($response['contact']["id"]) {
            return $response;
        }

        return new \WP_Error(423, __('Could not create subscriber', 'ffmauticaddon'));
    }
}
