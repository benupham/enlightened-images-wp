<?php

class SisaPro extends SmartImageSearch_WP_Base
{

    public function __construct()
    {
        parent::__construct();
        $this->is_pro = (int) get_option('sisa_pro') === 1 ? true : false;
        $this->has_pro = true;
        update_option('sisa_pro_plugin', 1);
        // error_log($this->is_pro);
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
            dirname(dirname(__FILE__)) . '/smart-image-search-ai-pro.php'
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

    public function add_sisa_api_routes()
    {
        register_rest_route('smartimagesearch/v1', '/proxy', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('pro_api_bulk_sisa'),
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

    public function api_get_sisa_settings($request)
    {
        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'options' => array(
                'apiKey' => get_option('sisa_api_key', ''),
                'proApiKey' => get_option('sisa_pro_api_key') ?: '',
                'isPro' => (int) get_option('sisa_pro', (int) 0),
                'hasPro' => (int) 1,
                'onUpload' => get_option('sisa_on_media_upload', 'async'),
                'altText' => (int) get_option('sisa_alt_text', (int) 1),
                'labels' => (int) get_option('sisa_labels', (int) 0),
                'text' => (int) get_option('sisa_text', (int) 0),
                'logos' => (int) get_option('sisa_logos', (int) 0),
                'landmarks' => (int) get_option('sisa_landmarks', (int) 0),
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

        $sisa_pro = $this->check_pro_api_key(sanitize_text_field(($json['options']['proApiKey'])));

        if (true === $sisa_pro) {
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
                'hasPro' => (int) 1,
                'onUpload' => get_option('sisa_on_media_upload', 'async'),
                'altText' => (int) get_option('sisa_alt_text', (int) 1),
                'labels' => (int) get_option('sisa_labels', (int) 0),
                'text' => (int) get_option('sisa_text', (int) 0),
                'logos' => (int) get_option('sisa_logos', (int) 0),
                'landmarks' => (int) get_option('sisa_landmarks', (int) 0),
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function get_annotation_options()
    {
        return array(
            // 'sisa_alt_text' => (int) get_option('sisa_alt_text', (int) 1),
            'sisa_labels' => (int) get_option('sisa_labels', (int) 0),
            'sisa_text' => (int) get_option('sisa_text', (int) 0),
            'sisa_logos' => (int) get_option('sisa_logos', (int) 0),
            'sisa_landmarks' => (int) get_option('sisa_landmarks', (int) 0),
        );
    }

    public function pro_api_bulk_sisa($request)
    {

        $params = $request->get_query_params();

        $now = time();
        $start = !empty($params['start']) ? $params['start'] : false;

        if (isset($start) && (string)(int)$start == $start && strlen($start) > 9) {
            $now = (int) $start;
        }

        $annotation_options = $this->get_annotation_options();

        $meta_query = '';

        foreach ($annotation_options as $key => $value) {
            if ($value === 1) {
                $meta_query .= "meta_value NOT LIKE '%" . $key . "%' OR ";
            }
        }
        $meta_query = substr($meta_query, 0, -3);

        // error_log($meta_query);

        global $wpdb;
        $datetime = "'" . date('Y-m-d H:i:s', $now) . "'";
        $sisa_query = "
        SELECT DISTINCT post_id 
        FROM $wpdb->postmeta
        INNER JOIN wp_posts ON wp_posts.`ID`=wp_postmeta.`post_id` 
        AND wp_posts.`post_type`= 'attachment' 
        AND wp_posts.`post_date` < $datetime 
        WHERE meta_key = 'sisa_meta'
        AND meta_value IS NULL OR 
        post_id IN 
        (
            SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sisa_meta' AND 
            (
                $meta_query
            )
        ) 
        OR post_id NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sisa_meta') 
        ";

        $annotation = $wpdb->get_results($sisa_query);
        $annotation_cnt = count($annotation);

        $alt_text = array();

        if (1 === (int) get_option('sisa_alt_text', (int) 1)) {

            $alt_text = $wpdb->get_results("
            SELECT DISTINCT post_id 
            FROM $wpdb->postmeta
            INNER JOIN wp_posts ON wp_posts.`ID`=wp_postmeta.`post_id` 
            AND wp_posts.`post_type`= 'attachment' 
            AND wp_posts.`post_date` < $datetime 
            WHERE (meta_key = '_wp_attachment_image_alt' AND meta_value IS NULL OR meta_value = '')
            ");
        }

        $alt_text_cnt = count($alt_text);

        $images = array_unique(array_merge($alt_text, $annotation), SORT_REGULAR);
        $images_cnt = count($images);

        // error_log($images_cnt);
        // error_log(count($alt_text));
        // error_log($sisa_query);

        if (false === $start) {
            return new WP_REST_RESPONSE(array(
                'success' => true,
                'body' => array(
                    'count' => $images_cnt,
                    'count_annotation' => $annotation_cnt,
                    'count_alt_text' => $alt_text_cnt,
                    'errors' => 0,
                    'start' => $now,
                    'estimate' => $this->get_estimate($images_cnt)
                ),
            ), 200);
        }

        if ($images_cnt === 0) {
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
        $posts_per_page = 2;
        $max_posts = $posts_per_page > $images_cnt ? $images_cnt : $posts_per_page;

        for ($i = 0; $i < $max_posts; $i++) {

            $p = $images[$i]->post_id;

            $annotation_data = array();
            $gcv_result = array();

            $annotation_data['thumbnail'] = wp_get_attachment_image_url($p);
            $annotation_data['attachmentURL'] = '/wp-admin/upload.php?item=' . $p;

            $attachment = get_post($p);
            $annotation_data['file'] = $attachment->post_name;

            $image = $this->get_image_url($p);

            if ($image === false) {
                $response[] = new WP_Error('bad_image', 'Image filepath not found');
                continue;
            }

            $features = $this->get_annotation_features($annotation_options);
            $gcv_result = $this->gcv_client->get_annotation($image, implode(',', $features));

            error_log("result from server endpoint");
            error_log(print_r($gcv_result, true));

            if (is_wp_error($gcv_result)) {
                error_log('response was a wp error');
                ++$errors;
                $annotation_data['error'] = $gcv_result;
                $response[] = $annotation_data;
                continue;
            }

            $cleaned_data = $this->clean_up_gcv_data($gcv_result);
            $alt = $this->update_image_alt_text($cleaned_data, $p, true);
            $meta = $this->update_attachment_meta($cleaned_data, $p);

            if (is_wp_error($alt) || is_wp_error($meta)) {
                ++$errors;
            }

            $annotation_data['alt_text'] = $alt;
            $annotation_data['meta_data'] = $meta;

            $response[] = $annotation_data;
        }

        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'body' => array(
                'image_data' => $response,
                'count' => $images_cnt - $max_posts,
                'errors' => $errors,
            ),
        ), 200);

        nocache_headers();

        return $response;
    }

    public function get_annotation_features($annotation_options)
    {
        $features = array();
        $feature_lookup = array(
            'sisa_alt_text' => 'OBJECT_LOCALIZATION,WEB_DETECTION',
            'sisa_labels' => 'LABEL_DETECTION',
            'sisa_text' => 'TEXT_DETECTION',
            'sisa_logos' => 'LOGO_DETECTION',
            'sisa_landmarks' => 'LANDMARK_DETECTION',
        );

        foreach ($annotation_options as $key => $value) {
            if ($value === 1) {
                $features[] = $feature_lookup[$key];
            }
        }
        return $features;
    }

    public function update_attachment_meta($cleaned_data, $p)
    {
        $sisa_search = array();

        foreach ($cleaned_data as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $sisa_search = array_merge($sisa_search, $value);
            }
            if (is_string($value) && !empty($value)) {
                $sisa_search[] = $value;
            }
        }
        $current_search = explode(' ', get_post_meta($p, 'sisa_search', true));
        $sisa_search = array_unique(array_merge($sisa_search, $current_search));
        $new_sisa_search = implode(' ', $sisa_search);
        update_post_meta($p, 'sisa_search', $new_sisa_search);

        $current_meta = get_post_meta($p, 'sisa_meta', true);
        update_post_meta($p, 'sisa_meta', array_merge($current_meta, $cleaned_data));

        return $new_sisa_search;
    }

    public function clean_up_gcv_data($data)
    {
        $cleaned_data = array();
        $annotation_options = $this->get_annotation_options();
        $min_score = 0.6;

        if (1 === $annotation_options['sisa_landmarks']) {
            if (isset($data->landmarkAnnotations) && !empty($data->landmarkAnnotations)) {
                if ($data->landmarkAnnotations[0]->score >= $min_score) {
                    $cleaned_data['sisa_landmarks'] = $data->landmarkAnnotations[0]->description;
                } else {
                    $cleaned_data['sisa_landmarks'] = '';
                }
            }
        }

        if (1 === $annotation_options['sisa_labels']) {
            if (isset($data->labelAnnotations) && !empty($data->labelAnnotations)) {
                $labels = array();
                foreach ($data->labelAnnotations as $label) {
                    if ($label->score >= $min_score) {
                        $labels[] = strtolower($label->description);
                    }
                }
                $cleaned_data['sisa_labels'] = array_values(array_unique($labels));
            } else {
                $cleaned_data['sisa_labels'] = '';
            }
        }

        if (1 === (int) get_option('sisa_alt_text')) {
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
            } else {
                $cleaned_data['sisa_web_entities'] = '';
                $cleaned_data['sisa_web_labels'] = '';
            }

            if (isset($data->localizedObjectAnnotations) && !empty($data->localizedObjectAnnotations)) {
                $objects = array();
                foreach ($data->localizedObjectAnnotations as $object) {
                    if ($object->score >= $min_score) {
                        $objects[] = strtolower($object->name);
                    }
                }
                $cleaned_data['sisa_objects'] =  array_values(array_unique($objects));
            } else {
                $cleaned_data['sisa_objects'] =  '';
            }
        }

        if (1 === $annotation_options['sisa_logos']) {
            if (isset($data->logoAnnotations) && !empty($data->logoAnnotations)) {
                $logos = array();
                foreach ($data->logoAnnotations as $logo) {
                    if ($logo->score >= $min_score) {
                        $logos[] = $logo->description;
                    }
                }
                $cleaned_data['sisa_logos'] = array_values(array_unique($logos));
            } else {
                $cleaned_data['sisa_logos'] = '';
            }
        }

        if (1 === $annotation_options['sisa_text']) {
            if (isset($data->textAnnotations) && !empty($data->textAnnotations)) {
                $text = $data->textAnnotations[0]->description;
                $cleaned_data['sisa_text'] = $text;
            } else {
                $cleaned_data['sisa_text'] = '';
            }
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
            return new WP_Error(500, 'Failed to update alt text.', $alt);
        }

        return array('existing' => '', 'smartimage' => $alt);
    }

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
                'key' => 'sisa_search',
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

    public function ajax_annotate_on_upload()
    {
        if (!is_array($_POST['metadata'])) exit();

        if (current_user_can('upload_files')) {

            $attachment_id = intval($_POST['attachment_id']);
            $image_url = $this->get_image_url($attachment_id);

            $options = $this->get_annotation_options();
            $features = $this->get_annotation_features($options);
            $gcv_result = $this->gcv_client->get_annotation($image_url, implode(',', $features));

            if (!is_wp_error($gcv_result)) {

                $cleaned_data = $this->clean_up_gcv_data($gcv_result);
                $this->update_image_alt_text($cleaned_data, $attachment_id, true);
                $this->update_attachment_meta($cleaned_data, $attachment_id);
            }
        }

        exit();
    }

    public function blocking_annotate($metadata, $attachment_id)
    {

        if (current_user_can('upload_files') && is_array($metadata)) {

            $image_url = $this->get_image_url($attachment_id);
            $options = $this->get_annotation_options();
            $features = $this->get_annotation_features($options);
            $gcv_result = $this->gcv_client->get_annotation($image_url, implode(',', $features));

            if (!is_wp_error($gcv_result)) {

                $cleaned_data = $this->clean_up_gcv_data($gcv_result);
                $this->update_image_alt_text($cleaned_data, $attachment_id, true);
            }
        }

        return $metadata;
    }

    public function get_image_url($attachment_id)
    {
        $url = null;

        if (has_image_size('medium')) {
            $url = wp_get_attachment_image_url($attachment_id, 'medium');
        } else {
            $url = wp_get_original_image_url($attachment_id);
        }

        return $url;
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

    //needs to include all sisa_ metavalues 
    public function delete_sisa_meta($metadata, $attachment_id)
    {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'postmeta',
            array('meta_key' => 'sisa_search', 'post_id' => $attachment_id),
            array('%s', '%d')
        );

        $wpdb->delete(
            $wpdb->prefix . 'postmeta',
            array('meta_key' => 'sisa_meta', 'post_id' => $attachment_id),
            array('%s', '%d')
        );

        return $metadata;
    }

    //needs to include all sisa_ metavalues 
    public function delete_all_sisa_meta()
    {
        global $wpdb;
        $results = $wpdb->delete(
            $wpdb->prefix . 'postmeta',
            array('meta_key' => 'sisa_search'),
            array('%s')
        );

        $results = $wpdb->delete(
            $wpdb->prefix . 'postmeta',
            array('meta_key' => 'sisa_meta'),
            array('%s')
        );

        return $results;
    }


    public function check_pro_api_key($pro_api_key)
    {
        $response = wp_remote_get('https://smart-image-ai.lndo.site/wp-json/smartimageserver/v1/account?api_key=' . $pro_api_key, array(
            'headers' => array('Content-Type' => 'application/json'),
            'method' => 'GET',
        ));

        $data = json_decode(wp_remote_retrieve_body($response));

        if (isset($data) && isset($data->success)) {
            return true;
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
