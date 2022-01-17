<?php

use function PHPSTORM_META\type;

class SmartImageSearch extends SmartImageSearch_WP_Base
{

    public function __construct()
    {
        parent::__construct();
        $this->gcv_client = new SmartImageSearch_GCV_Client();
    }

    public function init()
    {
        load_plugin_textdomain(
            self::NAME,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        //PRO
        add_action('rest_api_init', $this->get_method('add_sisa_meta_to_media_api'));

        add_action('rest_api_init', $this->get_method('add_sisa_api_routes'));

        add_filter('wp_generate_attachment_metadata', $this->get_method('process_attachment_upload'), 10, 2);
    }

    public function ajax_init()
    {
        add_filter(
            'wp_ajax_sisa_async_annotate_upload_new_media',
            $this->get_method('ajax_annotate_on_upload')
        );
    }

    public function admin_init()
    {
        add_action('pre_get_posts', $this->get_method('filter_media_search'), 10, 1);

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
                '<a href="tools.php?page=smartimagesearch">%s</a>',
                esc_html__('Get Started', 'smartimagesearch')
            ),
        );
        return array_merge($additional, $current_links);
    }

    //Pro
    public function add_sisa_meta_to_media_api()
    {
        register_rest_field(
            'attachment',
            'smartimagesearch',
            array(
                'get_callback' => $this->get_method('get_sisa_meta_for_api'),
                'update_callback' => null,
                'schema' => null,
            )
        );
    }

    //Pro
    public function get_sisa_meta_for_api($object)
    {

        $the_meta = get_post_meta($object['id'], 'smartimagesearch', true);

        if (is_null($the_meta) || empty($the_meta)) {
            return null;
        }
        return $the_meta;
    }

    public function add_sisa_api_routes()
    {
        register_rest_route('smartimagesearch/v1', '/proxy', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_bulk_sisa'),
            'permission_callback' => $this->get_method('sisa_proxy_permissions_check'),
        ));
        register_rest_route('smartimagesearch/v1', '/settings', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_get_sisa_settings'),
            'permission_callback' => $this->get_method('sisa_settings_permissions_check'),
        ));
        register_rest_route('smartimagesearch/v1', '/settings', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => $this->get_method('api_update_sisa_settings'),
            'permission_callback' => $this->get_method('sisa_settings_permissions_check'),
        ));
    }

    //change
    public function api_get_sisa_settings($request)
    {
        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'value' => array(
                'apiKey' => get_option('sisa_api_key', ''),
                'useSmartsearch' => (int) get_option('sisa_use_smartsearch', 1),
                'altText' => (int) get_option('sisa_alt_text', 1),
                'onUpload' => get_option('sisa_on_media_upload', 'async')
            ),
        ), 200);
        $response->set_headers(array('Cache-Control' => 'no-cache'));
        return $response;
    }

    //change
    public function api_update_sisa_settings($request)
    {
        $json = $request->get_json_params();
        error_log(print_r($json, true));
        update_option('sisa_api_key', sanitize_text_field(($json['options']['apiKey'])));
        update_option('sisa_use_smartsearch', sanitize_text_field(($json['options']['useSmartsearch'])));
        update_option('sisa_alt_text', sanitize_text_field(($json['options']['altText'])));
        update_option('sisa_on_media_upload', sanitize_text_field(($json['options']['onUpload'])));

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'value' => $json,
        ), 200);
        $response->set_headers(array('Cache-Control' => 'no-cache'));
        return $response;
    }

    public function sisa_settings_permissions_check()
    {
        // Restrict endpoint to only users who have the capability to manage options.
        if (current_user_can('manage_options')) {
            return true;
        }

        return new WP_Error('rest_forbidden', esc_html__('You do not have permissions to view this data.', 'smartimagesearch'), array('status' => 401));
    }

    public function sisa_proxy_permissions_check()
    {
        // Restrict endpoint to only users who have the capability to manage options.
        if (current_user_can('manage_options')) {
            return true;
        }

        return new WP_Error('rest_forbidden', esc_html__('You do not have permission to use this.', 'smartimagesearch'), array('status' => 401));
    }

    //change
    public function api_bulk_sisa($request)
    {

        $params = $request->get_query_params();

        $now = time();
        $start = !empty($params['start']) ? $params['start'] : false;
        // error_log($start);
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
            'meta_key' => 'smartimagesearch',
            'meta_compare' => 'NOT EXISTS', //this may not work
            'post_mime_type' => array('image/jpeg', 'image/gif', 'image/png', 'image/bmp'),
            'fields' => 'ids'
        );

        $query = new WP_Query($args);

        if ($start === false) {
            return new WP_REST_RESPONSE(array(
                'success' => true,
                'body' => array(
                    'count' => $query->found_posts,
                    'errors' => 0,
                    'start' => $now,
                ),
            ), 200);
        }

        if (!$query->have_posts()) {
            return new WP_REST_RESPONSE(array(
                'success' => true,
                'body' => array(
                    'image_data' => array(),
                    'status' => 'no images need annotation.'
                ),
            ), 200);
        }

        $response = array();
        $errors = 0;

        foreach ($query->posts as $p) {

            $annotation_data = array();

            $annotation_data['thumbnail'] = wp_get_attachment_image_url($p);

            $image_file_path = $this->get_filepath($p);

            if ($image_file_path === false) {
                $response[] = new WP_Error('bad_image', 'image filepath not found');
                continue;
            }
            // $gcv_client = new SmartImageSearch_GCV_Client();
            $gcv_result = $this->gcv_client->get_annotation($image_file_path);

            if (is_wp_error($gcv_result)) {
                ++$errors;
                $annotation_data['gcv_data'] = $gcv_result;
                $response[] = $annotation_data;
                continue;
            }

            $cleaned_data = $this->clean_up_gcv_data($gcv_result);
            $alt = $this->update_image_alt_text($cleaned_data, $p, true);
            $meta = $this->update_attachment_meta($cleaned_data, $p);

            $annotation_data['gcv_data'] = $cleaned_data;

            if (is_wp_error($alt) || is_wp_error($meta)) {
                ++$errors;
            }

            $annotation_data['alt_text'] = $alt;
            $annotation_data['smartsearch_meta'] = $meta;

            $response[] = $annotation_data;
        }

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'body' => array(
                'image_data' => $response,
                'count' => $query->found_posts - count($query->posts),
                'errors' => $errors,
            ),
        ), 200);

        $response->set_headers(array('Cache-Control' => 'no-cache'));

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
                $cleaned_data['landmark'] = $data->landmarkAnnotations[0]->description;
            }
        }
        if (isset($data->labelAnnotations) && !empty($data->labelAnnotations)) {
            $labels = array();
            foreach ($data->labelAnnotations as $label) {
                if ($label->score >= $min_score) {
                    $labels[] = strtolower($label->description);
                }
            }
            $cleaned_data['labels'] = array_values(array_unique($labels));
        }
        if (isset($data->webDetection) && !empty($data->webDetection)) {
            $web_entities = array();
            foreach ($data->webDetection->webEntities as $entity) {
                if (isset($entity->description) && $entity->score >= $min_score)
                    $web_entities[] = strtolower($entity->description);
            }
            $cleaned_data['webEntities'] = array_values(array_unique($web_entities));
            if (isset($data->webDetection->bestGuessLabels) && !empty($data->webDetection->bestGuessLabels)) {
                $web_labels = array();
                foreach ($data->webDetection->bestGuessLabels as $web_label) {
                    if (isset($web_label->label)) {
                        $web_labels[] = $web_label->label;
                    }
                }
                $cleaned_data['webLabels'] = array_values(array_unique($web_labels));
            }
        }
        if (isset($data->localizedObjectAnnotations) && !empty($data->localizedObjectAnnotations)) {
            $objects = array();
            foreach ($data->localizedObjectAnnotations as $object) {
                if ($object->score >= $min_score) {
                    $objects[] = strtolower($object->name);
                }
            }
            $cleaned_data['objects'] =  array_values(array_unique($objects));
        }
        if (isset($data->logoAnnotations) && !empty($data->logoAnnotations)) {
            $logos = array();
            foreach ($data->logoAnnotations as $logo) {
                if ($logo->score >= $min_score) {
                    $logos[] = $logo->description;
                }
            }
            $cleaned_data['logos'] = array_values(array_unique($logos));
        }
        if (isset($data->textAnnotations) && !empty($data->textAnnotations)) {
            $text = $data->textAnnotations[0]->description;
            $cleaned_data['text'] = $text;
        }
        return $cleaned_data;
    }

    //pro
    public function update_attachment_meta($cleaned_data, $p)
    {
        $sisa_meta = array();

        foreach ($cleaned_data as $value) {
            if (is_array($value) && !empty($value)) {
                $sisa_meta = array_merge($sisa_meta, $value);
            }
            if (is_string($value) && !empty($value)) {
                $sisa_meta[] = $value;
            }
        }

        $sisa_meta = array_unique($sisa_meta);
        $sisa_meta_string = implode(' ', $sisa_meta);
        // error_log($sisa_meta_string);
        $success = update_post_meta($p, 'smartimagesearch', $sisa_meta_string);

        if (false === $success) {
            return new WP_Error(500, 'Failed to update or matching meta already exists.', $sisa_meta_string);
        }
        return $sisa_meta_string;
    }

    //change
    public function update_image_alt_text($cleaned_data, $p, $save_alt)
    {
        $success = true;
        $alt = '';

        if (is_array($cleaned_data['webLabels']) && !empty($cleaned_data['webLabels'][0])) {
            $alt = $cleaned_data['webLabels'][0];
        } elseif (is_array($cleaned_data['webEntities']) && !empty($cleaned_data['webEntities'][0])) {
            $alt = $cleaned_data['webEntities'][0];
        } else {
            $alt = $cleaned_data['objects'][0];
        }

        if (!empty($existing = get_post_meta($p, '_wp_attachment_image_alt', true))) {
            return array('existing' => $existing, 'smartimage' => $alt);
        }

        $success = update_post_meta($p, '_wp_attachment_image_alt', $alt);

        if (false === $success) {
            return new WP_Error(500, 'Failed to update alt text.', $alt);
        }

        return array('existing' => '', 'smartimage' => $alt);
    }

    //pro
    public function filter_media_search($query)
    {
        if (true != get_option('sisa_use_smartsearch', true)) return;

        if (!$query->is_search) return;
        $post_type = $query->get('post_type');

        if (is_array($post_type) || $post_type !== 'attachment') return;

        $search = $query->get('s');
        if (empty($search)) return;

        $query->set('s', null);

        // This is so we search attachments by sisa meta, and filename, and title. 
        // https://wordpress.stackexchange.com/questions/78649/using-meta-query-meta-query-with-a-search-query-s
        add_filter('get_meta_sql', function ($sql) use ($search) {
            global $wpdb;

            // Only run once:
            static $nr = 0;
            if (0 != $nr++) return $sql;

            // Modified WHERE
            $sql['where'] = sprintf(
                " AND ( %s OR %s ) ",
                $wpdb->prepare("{$wpdb->posts}.post_title like '%%%s%%'", $search),
                mb_substr($sql['where'], 5, mb_strlen($sql['where']))
            );

            return $sql;
        });

        $meta_query = array(
            'relation' => 'OR',
            array(
                'key' => 'smartimagesearch',
                'value' => $search,
                'compare' => 'LIKE'
            ),
            array(
                'key' => '_wp_attached_file',
                'value' => $search,
                'compare' => 'LIKE'
            )
        );
        $query->set('meta_query', $meta_query);
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

    public function process_attachment_upload($metadata, $attachment_id)
    {
        $annotate_upload = get_option('sisa_on_media_upload', 'async');
        if ($annotate_upload == 'async') {
            $this->async_annotate($metadata, $attachment_id);
        } elseif ($annotate_upload == 'blocking') {
            $this->blocking_annotate($metadata, $attachment_id);
        }
        return $metadata;
    }

    //Does an "async" smart annotation by making an ajax request right after image upload
    public function async_annotate($metadata, $attachment_id)
    {
        $context     = 'wp';
        $action      = 'sisa_async_annotate_upload_new_media';
        $_ajax_nonce = wp_create_nonce('sisa_new_media-' . $attachment_id);
        $body = compact('action', '_ajax_nonce', 'metadata', 'attachment_id', 'context');

        $args = array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'body'      => $body,
            'cookies'   => isset($_COOKIE) && is_array($_COOKIE) ? $_COOKIE : array(),
            'sslverify' => apply_filters('https_local_ssl_verify', false),
        );

        if (getenv('WORDPRESS_HOST') !== false) {
            wp_remote_post(getenv('WORDPRESS_HOST') . '/wp-admin/admin-ajax.php', $args);
        } else {
            wp_remote_post(admin_url('admin-ajax.php'), $args);
        }
    }

    //change
    public function ajax_annotate_on_upload()
    {
        if (!is_array($_POST['metadata'])) exit();

        if (current_user_can('upload_files')) {

            $attachment_id = intval($_POST['attachment_id']);
            $image_file_path = $this->get_filepath($attachment_id);

            if (get_option('sisa_alt_text') === 1) {

                $gcv_client = new SmartImageSearch_GCV_Client();
                $gcv_result = $gcv_client->get_annotation($image_file_path);

                if (!is_wp_error($gcv_result)) {

                    $cleaned_data = $this->clean_up_gcv_data($gcv_result);
                    $this->update_image_alt_text($cleaned_data, $attachment_id, true);
                    error_log(get_option('sisa_use_smartsearch'));
                    if (get_option('sisa_use_smartsearch') === 1) {
                        $this->update_attachment_meta($cleaned_data, $attachment_id);
                    }
                }
            }
        }
        exit();
    }

    //change
    public function blocking_annotate($metadata, $attachment_id)
    {
        if (!get_option('sisa_alt_text')) return $metadata;

        if (current_user_can('upload_files') && is_array($metadata)) {

            $image_file_path = $this->get_filepath($attachment_id);

            $gcv_client = new SmartImageSearch_GCV_Client();
            $gcv_result = $gcv_client->get_annotation($image_file_path);

            if (!is_wp_error($gcv_result)) {

                $cleaned_data = $this->clean_up_gcv_data($gcv_result);
                $this->update_image_alt_text($cleaned_data, $attachment_id, true);

                if (!!get_option('sisa_use_smartsearch')) {
                    $this->update_attachment_meta($cleaned_data, $attachment_id);
                }
            }
        }

        return $metadata;
    }

    //pro
    public function delete_sisa_meta($metadata, $attachment_id)
    {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'postmeta',
            array('meta_key' => 'smartimagesearch', 'post_id' => $attachment_id),
            array('%s', '%d')
        );

        return $metadata;
    }

    //pro
    public function delete_all_sisa_meta()
    {
        global $wpdb;
        $results = $wpdb->delete(
            $wpdb->prefix . 'postmeta',
            array('meta_key' => 'smartimagesearch'),
            array('%s')
        );
        return $results;
    }

    //change
    public function admin_menu()
    {
        global $sisa_settings_page;
        $sisa_settings_page = add_media_page(
            __('Smart Image Bulk Alt Text and Index'),
            esc_html__('Bulk Alt Text and SmartIndex'),
            'manage_options',
            'smartimagesearch',
            array($this, 'smartimagesearch_settings_do_page')
        );
    }

    public function smartimagesearch_settings_do_page()
    {
?>
        <div id="smartimagesearch_settings"></div>
        <div id="smartimagesearch_dashboard"></div>
<?php
    }
}
