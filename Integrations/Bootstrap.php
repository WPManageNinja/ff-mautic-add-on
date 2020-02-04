<?php

namespace FluentFormMautic\Integrations;

use FluentForm\App\Services\Integrations\IntegrationManager;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;

class Bootstrap extends IntegrationManager
{
    public function __construct(Application $app)
    {
        parent::__construct(
            $app,
            'Mautic',
            'mautic',
            '_fluentform_mautic_settings',
            'mautic_feed',
            36
        );


        $this->logo = $this->app->url('public/img/integrations/mautic.png');

        $this->description = 'Mautic is Easy to use all-in-one software for live chat, email marketing automation, forms, knowledge base, and more for a complete 360° view of your contacts.';

        $this->registerAdminHooks();


        add_filter('fluentform_notifying_async_mautic', '__return_false');

        add_action('admin_init', function () {
            if(isset($_REQUEST['ff_mautic_auth'])) {
                $client = $this->getRemoteClient();
                if(isset($_REQUEST['code'])) {
                    // Get the access token now
                    $code = sanitize_text_field($_REQUEST['code']);
                    $settings = $this->getGlobalSettings([]);
                    $settings = $client->generateAccessToken($code, $settings);

                    if(!is_wp_error($settings)) {
                        $settings['status'] = true;
                        update_option($this->optionKey, $settings, 'no');
                    }

                    wp_redirect(admin_url('admin.php?page=fluent_forms_settings#general-mautic-settings'));
                    exit();
                } else {
                    $client->redirectToAuthServer();
                }
                die();
            }

            if(isset($_REQUEST['mm_test'])) {
                $api = $this->getRemoteClient();

                $contacts = $api->make_request('contacts/new', [
                    'firstname' => 'Jewel',
                    'lastname' => 'SHAHJAHAN',
                    'email' => 'jl@gmail.com'
                ], 'POST');

                print_r($contacts);
                die();
            }

        });

    }

    public function getGlobalFields($fields)
    {
        return [
            'logo'             => $this->logo,
            'menu_title'       => __('Mautic Settings', 'fluentformpro'),
            'menu_description' => $this->description,
            'valid_message'    => __('Your Mautic API Key is valid', 'fluentformpro'),
            'invalid_message'  => __('Your Mautic API Key is not valid', 'fluentformpro'),
            'save_button_text' => __('Save Settings', 'fluentformpro'),
            'fields'           => [
                'apiUrl'        => [
                    'type'        => 'text',
                    'placeholder' => 'Your Mautic Installation URL',
                    'label_tips'  => __("Please provide your Mautic Installation URL", 'fluentformpro'),
                    'label'       => __('Your Moutic API URL', 'fluentformpro'),
                ],
                'client_id'     => [
                    'type'        => 'text',
                    'placeholder' => 'Mautic App Client ID',
                    'label_tips'  => __("Enter your Mautic Client ID, if you do not have <br>Please login to your Mautic account and go to<br>Settings -> Integrations -> API key", 'fluentformpro'),
                    'label'       => __('Mautic Client ID', 'fluentformpro'),
                ],
                'client_secret' => [
                    'type'        => 'password',
                    'placeholder' => 'Mautic App Client Secret',
                    'label_tips'  => __("Enter your Mautic API Key, if you do not have <br>Please login to your Mautic account and go to<br>Settings -> Integrations -> API key", 'fluentformpro'),
                    'label'       => __('Mautic Client Secret', 'fluentformpro'),
                ],
            ],
            'hide_on_valid'    => true,
            'discard_settings' => [
                'section_description' => 'Your Mautic API integration is up and running',
                'button_text'         => 'Disconnect Mautic',
                'data'                => [
                    'apiUrl'        => '',
                    'client_id'     => '',
                    'client_secret' => ''
                ],
                'show_verify'         => true
            ]
        ];
    }

    public function getGlobalSettings($settings)
    {
        $globalSettings = get_option($this->optionKey);
        if (!$globalSettings) {
            $globalSettings = [];
        }
        $defaults = [
            'apiUrl'        => '',
            'client_id'     => '',
            'client_secret' => '',
            'status'    => '',
            'access_token' => '',
            'refresh_token' => '',
            'expire_at' => false
        ];

        return wp_parse_args($globalSettings, $defaults);
    }

    public function saveGlobalSettings($settings)
    {
        if (empty($settings['apiUrl'])) {
            $integrationSettings = [
                'apiUrl'        => '',
                'client_id'     => '',
                'client_secret' => '',
                'status'    => false
            ];
            // Update the details with siteKey & secretKey.
            update_option($this->optionKey, $integrationSettings, 'no');
            wp_send_json_success([
                'message' => __('Your settings has been updated', 'fluentformpro'),
                'status'  => false
            ], 200);
        }

        // Verify API key now
        try {
            $oldSettings = $this->getGlobalSettings([]);
            $oldSettings['apiUrl'] = esc_url_raw($settings['apiUrl']);
            $oldSettings['client_id'] = sanitize_text_field($settings['client_id']);
            $oldSettings['client_secret'] = sanitize_text_field($settings['client_secret']);
            $oldSettings['status'] = false;

            update_option($this->optionKey, $oldSettings, 'no');
            wp_send_json_success([
                'message'      => 'You are redirect to athenticate',
                'redirect_url' => admin_url('?ff_mautic_auth=1')
            ], 200);
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations[$this->integrationKey] = [
            'title'                 => $this->title . ' Integration',
            'logo'                  => $this->logo,
            'is_active'             => $this->isConfigured(),
            'configure_title'       => 'Configration required!',
            'global_configure_url'  => admin_url('admin.php?page=fluent_forms_settings#general-getgist-settings'),
            'configure_message'     => 'Mautic is not configured yet! Please configure your Mautic api first',
            'configure_button_text' => 'Set Mautic API'
        ];
        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        return [
            'name'         => '',
            'list_id'      => '',
            'fields'       => (object)[],
            'other_fields_mapping' => [
                [
                    'item_value' => '',
                    'label' => ''
                ]
            ],
            'conditionals' => [
                'conditions' => [],
                'status'     => false,
                'type'       => 'all'
            ],
            'resubscribe'  => false,
            'enabled'      => true
        ];
    }

    public function getSettingsFields($settings, $formId)
    {

        $api = $this->getRemoteClient();
        $fields = $api->getContactFields();

        return [
            'fields'            => [
                [
                    'key'         => 'name',
                    'label'       => 'Feed Name',
                    'required'    => true,
                    'placeholder' => 'Your Feed Name',
                    'component'   => 'text'
                ],
                [
                    'key'                => 'fields',
                    'label'              => 'Map Fields',
                    'tips'               => 'Select which Fluent Form fields pair with their<br /> respective Gist fields.',
                    'component'          => 'map_fields',
                    'field_label_remote' => 'Mautic Fields',
                    'field_label_local'  => 'Form Field',
                    'primary_fileds'     => [
                        [
                            'key'           => 'email',
                            'label'         => 'Email Address',
                            'required'      => true,
                            'input_options' => 'emails'
                        ],
                        [
                            'key'      => 'lead_name',
                            'label'    => 'Name',
                            'required' => false
                        ],
                        [
                            'key'      => 'lead_phone',
                            'label'    => 'Phone',
                            'required' => false
                        ]
                    ]
                ],
                [
                    'key'                => 'other_fields_mapping',
                    'require_list'       => false,
                    'label'              => 'Other Fields',
                    'tips'               => 'Select which Fluent Form fields pair with their<br /> respective Platformly fields.',
                    'component'          => 'dropdown_many_fields',
                    'field_label_remote' => 'Mautic Field',
                    'field_label_local'  => 'Mautic Field',
                    'options'            => $this->otherFields()
                ],
                [
                    'key'         => 'tags',
                    'label'       => 'Lead Tags',
                    'required'    => false,
                    'placeholder' => 'Tags',
                    'component'   => 'value_text',
                    'inline_tip'  => 'Use comma separated value. You can use smart tags here'
                ],              
                [
                    'key'             => 'landing_url',
                    'label'           => 'Landing URL',
                    'tips'            => 'When this option is enabled, FluentForm will pass the form page url to the gist lead',
                    'component'       => 'checkbox-single',
                    'checkobox_label' => 'Enable Landing URL'
                ],
                [
                    'key'             => 'last_seen_ip',
                    'label'           => 'Push IP Address',
                    'tips'            => 'When this option is enabled, FluentForm will pass the last_seen_ip to gist',
                    'component'       => 'checkbox-single',
                    'checkobox_label' => 'Enable last IP address'
                ],
                [
                    'key'       => 'conditionals',
                    'label'     => 'Conditional Logics',
                    'tips'      => 'Allow Gist integration conditionally based on your submission values',
                    'component' => 'conditional_block'
                ],
                [
                    'key'             => 'enabled',
                    'label'           => 'Status',
                    'component'       => 'checkbox-single',
                    'checkobox_label' => 'Enable This feed'
                ]
            ],
            'integration_title' => $this->title
        ];
    }

    protected function getLists()
    {
        return [];
    }

    public function getMergeFields($list = false, $listId = false, $formId = false)
    {
        return [];
    }
    public function otherFields() {
            $attributes = [
                "title" => "Title",
                "firstname" => "FirstName",
                "lastname" => "Last Name",
                "company" => "Company",
                "position" => "Position",
                "phone" => "Phone",
                "mobile" => "Mobile",
                "address1" => "Address1",
                "address2" => "Address2",
                "city" => "City",
                "zipcode" => "Zipcode",
                "country" => "Country",
                "fax" => "Fax",
                "website" => "Website",
                "facebook" => "Facebook",
                "foursquare" => "Foursquare",
                "googleplus" => "Googleplus",
                "instagram" => "Instagram",
                "linkedin" => "Linkedin",
                "skype" => "Skype",
                "twitter" => "Twitter"
            ];
            
            return $attributes;
    }

    /*
     * Form Submission Hooks Here
     */
    public function notify($feed, $formData, $entry, $form)
    {
        $feedData = $feed['processedValues'];


        $subscriber = [
            'name'         => ArrayHelper::get($feedData, 'lead_name'),
            'email'        => ArrayHelper::get($feedData, 'email'),
            'phone'        => ArrayHelper::get($feedData, 'phone'),
            'created_at'   => time(),
            'last_seen_at' => time()
        ];

        $tags = ArrayHelper::get($feedData, 'tags');
        if ($tags) {
            $tags = explode(',', $tags);
            $formtedTags = [];
            foreach ($tags as $tag) {
                $formtedTags[] = wp_strip_all_tags(trim($tag));
            }
            $subscriber['tags'] = $formtedTags;
        }

        if (ArrayHelper::isTrue($feedData, 'landing_url')) {
            $subscriber['landing_url'] = $entry->source_url;
        }

        if (ArrayHelper::isTrue($feedData, 'last_seen_ip')) {
            $subscriber['last_seen_ip'] = $entry->ip;
        }

        $subscriber = array_filter($subscriber);

        if (!empty($subscriber['email']) && !is_email($subscriber['email'])) {
            $subscriber['email'] = ArrayHelper::get($formData, $subscriber['email']);
        }

        foreach (ArrayHelper::get($feedData, 'other_fields_mapping') as $item) {
            $subscriber[$item['label']] = $item['item_value'];
        }


        if (!is_email($subscriber['email'])) {
            return;
        }

        $api = $this->getRemoteClient();
        $response = $api->subscribe($subscriber);

        if (is_wp_error($response)) {
            // it's failed
            do_action('ff_log_data', [
                'parent_source_id' => $form->id,
                'source_type'      => 'submission_item',
                'source_id'        => $entry->id,
                'component'        => $this->integrationKey,
                'status'           => 'failed',
                'title'            => $feed['settings']['name'],
                'description'      => 'Looks like I encountered an error (error #404)'
            ]);
        } else {
            // It's success
            do_action('ff_log_data', [
                'parent_source_id' => $form->id,
                'source_type'      => 'submission_item',
                'source_id'        => $entry->id,
                'component'        => $this->integrationKey,
                'status'           => 'success',
                'title'            => $feed['settings']['name'],
                'description'      => 'Mautic feed has been successfully initialed and pushed data'
            ]);
        }
    }


    public function getRemoteClient()
    {
        $settings = $this->getGlobalSettings([]);
        return new API(
            $settings['apiUrl'],
            $settings
        );
    }

}
