<?php

class EnlightenedImages_Plugin
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new EnlightenedImages_Plugin();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', $this->get_method('init'));

        if (is_admin()) {
            add_action('admin_init', $this->get_method('admin_init'));
            add_action('admin_menu', $this->get_method('admin_menu'));
            add_action('admin_init', $this->get_method('ajax_init'));
        }

        $this->is_pro = (int) get_option('elim_pro') === 1 ? true : false;
        $this->has_pro = false;
        update_option('elim_pro_plugin', (int) 0);
        $this->set_client();
    }

    protected function get_method($name)
    {
        return array($this, $name);
    }

    public function set_client()
    {
        if ($this->is_pro) {
            $this->image_client = new EnlightenedImages_Pro_Client();
        } else {
            $this->image_client = new EnlightenedImages_Azure_Client();
        }
    }

    public function init()
    {

        add_action('rest_api_init', $this->get_method('add_elim_api_routes'));
    }

    public function ajax_init()
    {
    }

    public function admin_init()
    {

        add_action(
            'admin_enqueue_scripts',
            $this->get_method('enqueue_scripts')
        );

        $plugin = plugin_basename(
            dirname(dirname(__FILE__)) . '/smart-image-search-ai.php'
        );

        add_filter(
            "plugin_action_links_$plugin",
            $this->get_method('add_elim_plugin_links')
        );
    }

    public function add_elim_plugin_links($current_links)
    {
        $additional = array(
            'enlightenedimages' => sprintf(
                '<a href="upload.php?page=enlightenedimages">%s</a>',
                esc_html__('Get Started', 'enlightenedimages')
            ),
        );
        return array_merge($additional, $current_links);
    }

    public function add_elim_api_routes()
    {
        register_rest_route('enlightenedimages/v1', '/proxy', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_bulk_elim'),
            'permission_callback' => $this->get_method('elim_permissions_check'),
        ));
        register_rest_route('enlightenedimages/v1', '/settings', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_get_elim_settings'),
            'permission_callback' => $this->get_method('elim_permissions_check'),
        ));
        register_rest_route('enlightenedimages/v1', '/settings', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => $this->get_method('api_update_elim_settings'),
            'permission_callback' => $this->get_method('elim_permissions_check'),
        ));
    }

    public $credits = null;

    public function get_credits()
    {
        if ($this->is_pro && !isset($this->credits)) {
            $account = $this->get_account_status(get_option('elim_pro_api_key'));

            if (isset($account->success)) {
                $this->credits = (int) $account->data->credits;
            }
        }

        return $this->credits;
    }

    public function api_get_elim_settings($request)
    {

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'options' => array(
                'apiKey' => get_option('elim_api_key', ''),
                'apiEndpoint' => get_option('elim_azure_endpoint', ''),
                'proApiKey' => get_option('elim_pro_api_key') ?: '',
                'isPro' => (int) get_option('elim_pro', (int) 0),
                'hasPro' => (int) get_option('elim_pro_plugin', (int) 0),
                'onUpload' => get_option('elim_on_media_upload', 'async'),
                'altText' => (int) get_option('elim_alt_text', (int) 1),
                'labels' => (int) get_option('elim_labels', (int) 0),
                'text' => (int) get_option('elim_text', (int) 0),
                'logos' => (int) get_option('elim_logos', (int) 0),
                'landmarks' => (int) get_option('elim_landmarks', (int) 0),
                'credits' => $this->get_credits(),
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function api_update_elim_settings($request)
    {
        $json = $request->get_json_params();
        update_option('elim_api_key', sanitize_text_field(($json['options']['apiKey'])));
        update_option('elim_azure_endpoint', sanitize_text_field(($json['options']['apiEndpoint'])));
        update_option('elim_pro_api_key', sanitize_text_field(($json['options']['proApiKey'])));
        update_option('elim_on_media_upload', sanitize_text_field(($json['options']['onUpload'])));
        update_option('elim_alt_text', (int) sanitize_text_field(($json['options']['altText'])));
        update_option('elim_labels', (int) sanitize_text_field(($json['options']['labels'])));
        update_option('elim_text', (int) sanitize_text_field(($json['options']['text'])));
        update_option('elim_logos', (int) sanitize_text_field(($json['options']['logos'])));
        update_option('elim_landmarks', (int) sanitize_text_field(($json['options']['landmarks'])));

        $elim_pro = $this->get_account_status(sanitize_text_field(($json['options']['proApiKey'])));
        // error_log(print_r($elim_pro, true));
        if (isset($elim_pro->data)) {
            update_option('elim_pro', (int) 1);
            $this->is_pro = true;
            $this->set_client();
        } else {
            update_option('elim_pro', (int) 0);
            $this->is_pro = false;
            $this->set_client();
        }

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'options' => array(
                'apiKey' => $json['options']['apiKey'],
                'apiEndpoint' => $json['options']['apiEndpoint'],
                'proApiKey' => $json['options']['proApiKey'],
                'isPro' => (int) get_option('elim_pro'),
                'hasPro' => (int) get_option('elim_pro_plugin', (int) 1),
                'onUpload' => get_option('elim_on_media_upload', 'async'),
                'altText' => (int) get_option('elim_alt_text', (int) 1),
                'labels' => (int) get_option('elim_labels', (int) 0),
                'text' => (int) get_option('elim_text', (int) 0),
                'logos' => (int) get_option('elim_logos', (int) 0),
                'landmarks' => (int) get_option('elim_landmarks', (int) 0),
                'credits' => $this->get_credits(),
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function api_bulk_elim($request)
    {

        $params = $request->get_query_params();

        $now = time();
        $start = !empty($params['start']) ? $params['start'] : false;

        if (isset($start) && (string)(int)$start == $start && strlen($start) > 9) {
            $now = (int) $start;
        }

        $posts_per_page = 2;

        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'paged' => 1,
            'posts_per_page' => $posts_per_page,
            'date_query' => array(
                'before' => date('Y-m-d H:i:s', $now),
            ),
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_wp_attachment_image_alt',
                        'value' => '',
                        'compare' => '='
                    ),
                    array(
                        'key' => '_wp_attachment_image_alt',
                        'compare' => 'NOT EXISTS'
                    )
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'elim_date',
                        'value' => date('Y-m-d H:i:s', $now),
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'elim_date',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ),
            'post_mime_type' => array('image/jpeg', 'image/gif', 'image/png', 'image/bmp'),
            'fields' => 'ids',
            'update_post_meta_cache' => false,
        );

        $query = new WP_Query($args);

        if (false === $start) {
            return new WP_REST_RESPONSE(array(
                'success' => true,
                'body' => array(
                    'count' => $query->found_posts,
                    'errors' => 0,
                    'start' => $now,
                    'estimate' => $this->get_estimate($query->found_posts),
                    'credits' => $this->get_credits(),
                ),
            ), 200);

            nocache_headers();
        }

        if (!$query->have_posts()) {
            return new WP_REST_RESPONSE(array(
                'success' => true,
                'body' => array(
                    'image_data' => array(),
                    'status' => 'no images need annotation.'
                ),
            ), 200);

            nocache_headers();
        }

        $response = array();
        $errors = 0;

        foreach ($query->posts as $p) {

            $annotation_data = array();

            $annotation_data['thumbnail'] = wp_get_attachment_image_url($p);
            $annotation_data['attachmentURL'] = '/wp-admin/upload.php?item=' . $p;
            $annotation_data['id'] = (int) $p;

            $attachment = get_post($p);
            $annotation_data['file'] = $attachment->post_name;

            $image_metadata = wp_get_attachment_metadata($p);

            $image = null;

            if (isset($image_metadata['sizes']['medium']) && $image_metadata['sizes']['medium']['width'] >= 50 && $image_metadata['sizes']['medium']['height'] >= 50) {
                $image = wp_get_attachment_image_url($p, 'medium');
            } else {
                $image = wp_get_attachment_image_url($p, 'full');
            }

            update_post_meta($p, 'elim_date', date('Y-m-d H:i:s', $now));

            if ($image === false) {
                $response[] = new WP_Error('bad_image', __('Image filepath not found'));
                continue;
            }

            $image_response = $this->image_client->get_annotation($image);

            if (is_wp_error($image_response)) {
                ++$errors;
                $annotation_data['error'] = $image_response;
                $response[] = $annotation_data;
                // error_log('this was an error');
                continue;
            }

            $alt_text = $this->update_image_alt_text_az($image_response, $p);

            if (is_wp_error($alt_text)) {
                ++$errors;
            }

            $annotation_data['alt_text'] = $alt_text;

            if ($this->is_pro) {
                $this->credits = $image_response->credits;
            }

            $response[] = $annotation_data;
        }

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'body' => array(
                'image_data' => $response,
                'count' => $query->found_posts - count($query->posts),
                'errors' => $errors,
                'credits' => $this->get_credits(),
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function update_image_alt_text_az($image_response, $p)
    {
        $caption = $image_response->captions[0]->text;

        $success = update_post_meta($p, '_wp_attachment_image_alt', $caption);

        if (false === $success) {
            return new WP_Error(500, 'Failed to update alt text for unknown reason.', array('existing' => '', 'smartimage' => $caption));
        }

        return array('existing' => '', 'smartimage' => $caption);
    }

    public function clean_up_gcv_data($data)
    {
        $cleaned_data = array();
        $min_score = 0.6;
        $labels_min_score = 0.7;

        if (isset($data->landmarkAnnotations) && !empty($data->landmarkAnnotations)) {
            if ($data->landmarkAnnotations[0]->score >= $min_score) {
                $cleaned_data['elim_landmarks'] = $data->landmarkAnnotations[0]->description;
            }
        }

        if (isset($data->webDetection) && !empty($data->webDetection)) {
            $web_entities = array();
            foreach ($data->webDetection->webEntities as $entity) {
                if (isset($entity->description) && $entity->score >= $min_score)
                    $web_entities[] = strtolower($entity->description);
            }
            $cleaned_data['elim_web_entities'] = array_values(array_unique($web_entities));
            if (isset($data->webDetection->bestGuessLabels) && !empty($data->webDetection->bestGuessLabels)) {
                $web_labels = array();
                foreach ($data->webDetection->bestGuessLabels as $web_label) {
                    if (isset($web_label->label)) {
                        $web_labels[] = $web_label->label;
                    }
                }
                $cleaned_data['elim_web_labels'] = array_values(array_unique($web_labels));
            }
        }

        if (isset($data->localizedObjectAnnotations) && !empty($data->localizedObjectAnnotations)) {
            $objects = array();
            foreach ($data->localizedObjectAnnotations as $object) {
                if ($object->score >= $min_score) {
                    $objects[] = strtolower($object->name);
                }
            }

            if (in_array('person', $objects)) {
                $counts = array_count_values($objects);
                if (2 <= $counts['person']) {
                    array_unshift($objects, 'people');
                }
                $objects = array_diff($objects, $this->unnecessary_words);
            }

            $cleaned_data['elim_objects'] =  array_values(array_unique($objects));
        }

        if (isset($data->labelAnnotations) && !empty($data->labelAnnotations)) {
            $labels = array();
            foreach ($data->labelAnnotations as $label) {
                if ($label->score >= $labels_min_score) {
                    $labels[] = strtolower($label->description);
                }
            }

            if (in_array('person', $labels) || ($cleaned_data['elim_objects'] && in_array('person', $cleaned_data['elim_objects']))) {
                $counts = array_count_values($labels);
                if (2 <= $counts['person']) {
                    array_unshift($labels, 'people');
                }
                $labels = array_diff($labels, $this->unnecessary_words);
            }

            $cleaned_data['elim_labels'] = array_values(array_unique($labels));
        }

        if (isset($data->logoAnnotations) && !empty($data->logoAnnotations)) {
            $logos = array();
            foreach ($data->logoAnnotations as $logo) {
                if ($logo->score >= $min_score) {
                    $logos[] = $logo->description;
                }
            }
            $cleaned_data['elim_logos'] = array_values(array_unique($logos));
        }
        if (isset($data->textAnnotations) && !empty($data->textAnnotations)) {
            $text = $data->textAnnotations[0]->description;
            $cleaned_data['elim_text'] = $text;
        }
        return $cleaned_data;
    }

    public $unnecessary_words = [
        'head',
        'cheek',
        'forehead',
        'top',
        'jaw',
        'outerwear',
        'dress shirt',
        'beard',
        'nose',
        'sleeve',
        'hair',
        'glasses',
        'tie',
        'shirt',
        'face ',
        'eye',
        'eyelash ',
        'hat',
        'shoulder',
        'neck',
        'eyebrow',
        'clothing',
        'temple',
        'long hair',
        'nfl divisional round',
    ];

    public function update_image_alt_text($cleaned_data, $p, $save_alt)
    {
        $success = true;
        $alt = '';
        $site_name = strtolower(get_bloginfo('name'));

        if (
            is_array($cleaned_data['elim_web_labels'])
            && !empty($cleaned_data['elim_web_labels'][0])
            && 2 <= str_word_count(
                $cleaned_data['elim_web_labels'][0]
                    && ($cleaned_data['elim_web_labels'][0] != $site_name)
            )
        ) {
            $alt = $cleaned_data['elim_web_labels'][0];
        } elseif (
            is_array($cleaned_data['elim_web_entities'])
            && !empty($cleaned_data['elim_web_entities'][0])
            && 2 <= str_word_count($cleaned_data['elim_web_entities'][0])
            && ($cleaned_data['elim_web_entities'][0] != $site_name)
        ) {
            $alt = $cleaned_data['elim_web_entities'][0];
        } else {
            $labels = $cleaned_data['elim_labels'] ? array_slice($cleaned_data['elim_labels'], 0, 3) : array();
            $objects = $cleaned_data['elim_objects'] ? array_slice($cleaned_data['elim_objects'], 0, 3) : array();
            $alt = implode(', ', array_merge($objects, $labels));
        }

        if (!empty($existing = get_post_meta($p, '_wp_attachment_image_alt', true))) {
            return array('existing' => $existing, 'smartimage' => $alt);
        }

        $success = update_post_meta($p, '_wp_attachment_image_alt', $alt);

        if (false === $success) {
            return new WP_Error(500, 'Failed to update alt text for unknown reason.', array('existing' => '', 'smartimage' => $alt));
        }
        // error_log('image ' . $p . ' alt text: ' . $alt);

        return array('existing' => '', 'smartimage' => $alt);
    }

    public function enqueue_scripts($hook)
    {

        // only load scripts on dashboard and settings page
        global $elim_settings_page;
        if ($hook != 'index.php' && $hook != $elim_settings_page) {
            return;
        }

        if (in_array($_SERVER['REMOTE_ADDR'], array('172.23.0.8', '::1'))) {
            // DEV React dynamic loading
            $js_to_load = 'http://localhost:3000/static/js/bundle.js';
        } else {
            $react_app_manifest = file_get_contents(__DIR__ . '/react-frontend/build/asset-manifest.json');
            if ($react_app_manifest !== false) {
                $manifest_json = json_decode($react_app_manifest, true);
                $main_css = $manifest_json['files']['main.css'];
                $main_js = $manifest_json['files']['main.js'];
                $js_to_load = plugin_dir_url(__FILE__) . '/react-frontend/build' . $main_js;

                $css_to_load = plugin_dir_url(__FILE__) . '/react-frontend/build' . $main_css;
                wp_enqueue_style('enlightenedimages_styles', $css_to_load);
            }
        }

        wp_enqueue_script('enlightenedimages_react', $js_to_load, '', mt_rand(10, 1000), true);
        wp_localize_script('enlightenedimages_react', 'enlightenedimages_ajax', array(
            'urls' => array(
                'proxy' => rest_url('enlightenedimages/v1/proxy'),
                'settings' => rest_url('enlightenedimages/v1/settings'),
                'media' => rest_url('wp/v2/media'),
            ),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function admin_menu()
    {
        global $elim_settings_page;
        $elim_settings_page = add_media_page(
            __('Bulk Image Alt Text'),
            esc_html__('Bulk Alt Text'),
            'manage_options',
            'enlightenedimages',
            array($this, 'enlightenedimages_settings_do_page')
        );
    }

    public function enlightenedimages_settings_do_page()
    {
?>
        <div id="elim-dashboard"></div>
<?php
    }


    public function get_account_status($pro_api_key)
    {
        $response = wp_remote_get('https://enlightenedimageswp.com/wp-json/smartimageserver/v1/account?api_key=' . $pro_api_key, array(
            'headers' => array('Content-Type' => 'application/json'),
            'method' => 'GET',
        ));

        $data = json_decode(wp_remote_retrieve_body($response));

        if (isset($data) && isset($data->success)) {
            return $data;
        }
        return false;
    }

    public function get_estimate($image_count)
    {
        $response = wp_remote_get('https://enlightenedimageswp.com/wp-json/smartimageserver/v1/estimate?imageCount=' . $image_count, array(
            'headers' => array('Content-Type' => 'application/json'),
            'method' => 'GET',
        ));

        $data = json_decode(wp_remote_retrieve_body($response));

        if (isset($data) && isset($data->success)) {
            return $data->cost;
        }
        return new WP_Error('estimate_unavailable', 'Could not generate Pro estimate');
    }

    public function elim_permissions_check()
    {
        // Restrict endpoint to only users who have the capability to manage options.
        if (current_user_can('manage_options')) {
            return true;
        }

        return new WP_Error('rest_forbidden', esc_html__('You do not have permissions to do that.', 'enlightenedimages'), array('status' => 401));
    }
}
