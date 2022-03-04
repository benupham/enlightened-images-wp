<?php

class Sisa extends Sisa_WP_Base
{

    public function __construct()
    {
        parent::__construct();
        $this->is_pro = (int) get_option('sisa_pro') === 1 ? true : false;
        $this->has_pro = false;
        update_option('sisa_pro_plugin', (int) 0);
        $this->set_client();
    }

    public function set_client()
    {
        if ($this->is_pro) {
            $this->gcv_client = new SmartImageSearch_SisaPro_Client();
        } else {
            $this->gcv_client = new SmartImageSearch_GCV_Client();
        }
    }

    public function init()
    {

        add_action('rest_api_init', $this->get_method('add_sisa_api_routes'));

        add_filter('wp_generate_attachment_metadata', $this->get_method('process_attachment_upload'), 10, 2);
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
            $this->get_method('add_sisa_plugin_links')
        );
    }

    public function add_sisa_plugin_links($current_links)
    {
        $additional = array(
            'smartimagesearch' => sprintf(
                '<a href="upload.php?page=smartimagesearch">%s</a>',
                esc_html__('Get Started', 'smartimagesearch')
            ),
        );
        return array_merge($additional, $current_links);
    }

    public function add_sisa_api_routes()
    {
        register_rest_route('smartimagesearch/v1', '/proxy', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_bulk_sisa'),
            'permission_callback' => $this->get_method('sisa_permissions_check'),
        ));
        register_rest_route('smartimagesearch/v1', '/settings', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_get_sisa_settings'),
            'permission_callback' => $this->get_method('sisa_permissions_check'),
        ));
        register_rest_route('smartimagesearch/v1', '/settings', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => $this->get_method('api_update_sisa_settings'),
            'permission_callback' => $this->get_method('sisa_permissions_check'),
        ));
    }

    public $credits = null;

    public function get_credits()
    {
        if ($this->is_pro && !isset($this->credits)) {
            $account = $this->get_account_status(get_option('sisa_pro_api_key'));

            if (isset($account->success)) {
                $this->credits = (int) $account->data->credits;
            }
        }

        return $this->credits;
    }

    public function api_get_sisa_settings($request)
    {

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'options' => array(
                'apiKey' => get_option('sisa_api_key', ''),
                'proApiKey' => get_option('sisa_pro_api_key') ?: '',
                'isPro' => (int) get_option('sisa_pro', (int) 0),
                'hasPro' => (int) get_option('sisa_pro_plugin', (int) 0),
                'onUpload' => get_option('sisa_on_media_upload', 'async'),
                'altText' => (int) get_option('sisa_alt_text', (int) 1),
                'labels' => (int) get_option('sisa_labels', (int) 0),
                'text' => (int) get_option('sisa_text', (int) 0),
                'logos' => (int) get_option('sisa_logos', (int) 0),
                'landmarks' => (int) get_option('sisa_landmarks', (int) 0),
                'credits' => $this->get_credits(),
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function api_update_sisa_settings($request)
    {
        $json = $request->get_json_params();
        update_option('sisa_api_key', sanitize_text_field(($json['options']['apiKey'])));
        update_option('sisa_pro_api_key', sanitize_text_field(($json['options']['proApiKey'])));
        update_option('sisa_on_media_upload', sanitize_text_field(($json['options']['onUpload'])));
        update_option('sisa_alt_text', (int) sanitize_text_field(($json['options']['altText'])));
        update_option('sisa_labels', (int) sanitize_text_field(($json['options']['labels'])));
        update_option('sisa_text', (int) sanitize_text_field(($json['options']['text'])));
        update_option('sisa_logos', (int) sanitize_text_field(($json['options']['logos'])));
        update_option('sisa_landmarks', (int) sanitize_text_field(($json['options']['landmarks'])));

        $sisa_pro = $this->get_account_status(sanitize_text_field(($json['options']['proApiKey'])));
        error_log(print_r($sisa_pro, true));
        if (isset($sisa_pro->data)) {
            update_option('sisa_pro', (int) 1);
            $this->is_pro = true;
            $this->set_client();
        } else {
            update_option('sisa_pro', (int) 0);
            $this->is_pro = false;
            $this->set_client();
        }

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'options' => array(
                'apiKey' => $json['options']['apiKey'],
                'proApiKey' => $json['options']['proApiKey'],
                'isPro' => (int) get_option('sisa_pro'),
                'hasPro' => (int) get_option('sisa_pro_plugin', (int) 1),
                'onUpload' => get_option('sisa_on_media_upload', 'async'),
                'altText' => (int) get_option('sisa_alt_text', (int) 1),
                'labels' => (int) get_option('sisa_labels', (int) 0),
                'text' => (int) get_option('sisa_text', (int) 0),
                'logos' => (int) get_option('sisa_logos', (int) 0),
                'landmarks' => (int) get_option('sisa_landmarks', (int) 0),
                'credits' => $this->get_credits(),
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function api_bulk_sisa($request)
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
                array(
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '='
                ),
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ),
                'relation' => 'OR'
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
                    'estimate' => $this->get_estimate($query->found_posts)
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

            $attachment = get_post($p);
            $annotation_data['file'] = $attachment->post_name;

            $image = null;

            if ($this->is_pro) {
                if (has_image_size('medium')) {
                    $image = wp_get_attachment_image_url($p, 'medium');
                } else {
                    $image = wp_get_original_image_url($p);
                }
            } else {
                $image = $this->get_filepath($p);
            }

            if ($image === false) {
                $response[] = new WP_Error('bad_image', 'Image filepath not found');
                continue;
            }

            $gcv_result = $this->gcv_client->get_annotation($image);

            if (is_wp_error($gcv_result)) {
                ++$errors;
                $annotation_data['error'] = $gcv_result;
                $response[] = $annotation_data;
                continue;
            }

            $cleaned_data = $this->clean_up_gcv_data($gcv_result);
            $alt = $this->update_image_alt_text($cleaned_data, $p, true);

            if (is_wp_error($alt)) {
                ++$errors;
            }

            $annotation_data['alt_text'] = $alt;

            if ($this->is_pro) {
                $this->credits = $gcv_result->credits;
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

    public function get_filepath($p)
    {
        $wp_metadata = wp_get_attachment_metadata($p);
        if (!is_array($wp_metadata) || !isset($wp_metadata['file'])) {
            return false;
        }
        $upload_dir = wp_upload_dir();
        $path_prefix = $upload_dir['basedir'] . '/';
        $path_info = pathinfo($wp_metadata['file']);
        if (isset($path_info['dirname'])) {
            $path_prefix .= $path_info['dirname'] . '/';
        }

        /* Do not use pathinfo for getting the filename.
        It doesn't work when the filename starts with a special character. */
        $path_parts = explode('/', $wp_metadata['file']);
        $name = end($path_parts);
        $filename = $path_prefix . $name;
        return $filename;
    }

    public function clean_up_gcv_data($data)
    {
        $cleaned_data = array();
        $min_score = 0.6;

        if (isset($data->landmarkAnnotations) && !empty($data->landmarkAnnotations)) {
            if ($data->landmarkAnnotations[0]->score >= $min_score) {
                $cleaned_data['sisa_landmarks'] = $data->landmarkAnnotations[0]->description;
            }
        }
        if (isset($data->labelAnnotations) && !empty($data->labelAnnotations)) {
            $labels = array();
            foreach ($data->labelAnnotations as $label) {
                if ($label->score >= $min_score) {
                    $labels[] = strtolower($label->description);
                }
            }
            $cleaned_data['sisa_labels'] = array_values(array_unique($labels));
        }
        if (isset($data->webDetection) && !empty($data->webDetection)) {
            $web_entities = array();
            foreach ($data->webDetection->webEntities as $entity) {
                if (isset($entity->description) && $entity->score >= $min_score)
                    $web_entities[] = strtolower($entity->description);
            }
            $cleaned_data['sisa_web_entities'] = array_values(array_unique($web_entities));
            if (isset($data->webDetection->bestGuessLabels) && !empty($data->webDetection->bestGuessLabels)) {
                $web_labels = array();
                foreach ($data->webDetection->bestGuessLabels as $web_label) {
                    if (isset($web_label->label)) {
                        $web_labels[] = $web_label->label;
                    }
                }
                $cleaned_data['sisa_web_labels'] = array_values(array_unique($web_labels));
            }
        }
        if (isset($data->localizedObjectAnnotations) && !empty($data->localizedObjectAnnotations)) {
            $objects = array();
            foreach ($data->localizedObjectAnnotations as $object) {
                if ($object->score >= $min_score) {
                    $objects[] = strtolower($object->name);
                }
            }
            $cleaned_data['sisa_objects'] =  array_values(array_unique($objects));
        }
        if (isset($data->logoAnnotations) && !empty($data->logoAnnotations)) {
            $logos = array();
            foreach ($data->logoAnnotations as $logo) {
                if ($logo->score >= $min_score) {
                    $logos[] = $logo->description;
                }
            }
            $cleaned_data['sisa_logos'] = array_values(array_unique($logos));
        }
        if (isset($data->textAnnotations) && !empty($data->textAnnotations)) {
            $text = $data->textAnnotations[0]->description;
            $cleaned_data['sisa_text'] = $text;
        }
        return $cleaned_data;
    }

    public function update_image_alt_text($cleaned_data, $p, $save_alt)
    {
        $success = true;
        $alt = '';

        if (is_array($cleaned_data['sisa_web_labels']) && !empty($cleaned_data['sisa_web_labels'][0])) {
            $alt = $cleaned_data['sisa_web_labels'][0];
        } elseif (is_array($cleaned_data['sisa_web_entities']) && !empty($cleaned_data['sisa_web_entities'][0])) {
            $alt = $cleaned_data['sisa_web_entities'][0];
        } else {
            $alt = $cleaned_data['sisa_objects'][0];
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
        global $sisa_settings_page;
        if ($hook != 'index.php' && $hook != $sisa_settings_page) {
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
                wp_enqueue_style('smartimagesearch_styles', $css_to_load);
            }
        }

        wp_enqueue_script('smartimagesearch_react', $js_to_load, '', mt_rand(10, 1000), true);
        wp_localize_script('smartimagesearch_react', 'smartimagesearch_ajax', array(
            'urls' => array(
                'proxy' => rest_url('smartimagesearch/v1/proxy'),
                'settings' => rest_url('smartimagesearch/v1/settings'),
                'media' => rest_url('wp/v2/media'),
            ),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function admin_menu()
    {
        global $sisa_settings_page;
        $sisa_settings_page = add_media_page(
            __('Bulk Image Alt Text'),
            esc_html__('Bulk Alt Text'),
            'manage_options',
            'smartimagesearch',
            array($this, 'smartimagesearch_settings_do_page')
        );
    }

    public function smartimagesearch_settings_do_page()
    {
?>
        <div id="sisa-dashboard"></div>
<?php
    }


    public function get_account_status($pro_api_key)
    {
        $response = wp_remote_get('https://smart-image-ai.lndo.site/wp-json/smartimageserver/v1/account?api_key=' . $pro_api_key, array(
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
        $response = wp_remote_get('https://smart-image-ai.lndo.site/wp-json/smartimageserver/v1/estimate?imageCount=' . $image_count, array(
            'headers' => array('Content-Type' => 'application/json'),
            'method' => 'GET',
        ));

        $data = json_decode(wp_remote_retrieve_body($response));

        if (isset($data) && isset($data->success)) {
            return $data->cost;
        }
        return new WP_Error('estimate_unavailable', 'Could not generate Pro estimate');
    }

    public function sisa_permissions_check()
    {
        // Restrict endpoint to only users who have the capability to manage options.
        if (current_user_can('manage_options')) {
            return true;
        }

        return new WP_Error('rest_forbidden', esc_html__('You do not have permissions to do that.', 'smartimagesearch'), array('status' => 401));
    }
}
