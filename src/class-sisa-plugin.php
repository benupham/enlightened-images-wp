<?php

class SmartImageSearch extends SmartImageSearch_WP_Base
{
    const VERSION = '0.9';
    const DATETIME_FORMAT = 'Y-m-d G:i:s';

    private $settings;

    public static function version()
    {
        /* Avoid using get_plugin_data() because it is not loaded early enough
        in xmlrpc.php. */
        return self::VERSION;
    }

    public function __construct()
    {
        parent::__construct();
        $this->settings = new SmartImageSearch_Settings();
    }

    public function init()
    {

        add_filter('attachment_fields_to_edit', array($this, 'add_sisa_button_to_edit_media_modal_fields_area'), 99, 2);

        load_plugin_textdomain(
            self::NAME,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );

        add_action('rest_api_init', $this->get_method('add_sisa_meta_to_media_api'));

        add_action('rest_api_init', $this->get_method('add_sisa_api_routes'));

        // add_filter('wp_generate_attachment_metadata', $this->get_method('delete_sisa_meta'), 10, 2);
    }

    public function admin_init()
    {

        // Add a smartimagesearch button to the non-modal edit media page.
        add_action('attachment_submitbox_misc_actions', array($this, 'add_sisa_button_to_media_edit_page'), 99);

        // Add a smartimagesearch link to actions list in the media list view.
        // add_filter('media_row_actions', array($this, 'add_sisa_link_to_media_list_view'), 10, 2);

        // add_action(
        //     'admin_enqueue_scripts',
        //     $this->get_method('enqueue_scripts')
        // );

        $plugin = plugin_basename(
            dirname(dirname(__FILE__)) . '/smart-image-search-ai.php'
        );

        add_filter(
            "plugin_action_links_$plugin",
            $this->get_method('add_sisa_plugin_links')
        );
    }

    public function create_page_url($id)
    {
        return add_query_arg('page', 'smartimagesearch', admin_url('tools.php')) . '&attachmentId=' . $id;
    }

    /**
     * Add a smart image search button to the submit box on the non-modal "Edit Media" screen for an image attachment.
     */
    public function add_sisa_button_to_media_edit_page()
    {
        global $post;

        echo '<div class="misc-pub-section">';
        echo '<a href="' . esc_url($this->create_page_url($post->ID)) . '" class="button-secondary button-large" title="' . esc_attr(__('Smart annotate this image', 'smartimagesearch')) . '">' . _x('Smart Annotate', 'action for a single image', 'smartimagesearch') . '</a>';
        echo '</div>';
    }

    public function add_sisa_button_to_edit_media_modal_fields_area($form_fields, $post)
    {

        $form_fields['smartimagesearch'] = array(
            'label' => '',
            'input' => 'html',
            'html' => '<a href="' . esc_url($this->create_page_url($post->ID)) . '" class="button-secondary button-large" title="' . esc_attr(__('Smart annotate this image', 'smartimagesearch')) . '">' . _x('Smart Annotate', 'action for a single image', 'smartimagesearch') . '</a>',
            'show_in_modal' => true,
            'show_in_edit' => false,
        );

        return $form_fields;
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

    public function add_sisa_meta_to_media_api()
    {
        register_rest_field(
            'attachment',
            'smartimagesearch',
            array(
                'get_callback' => $this->get_method('get_sisa_custom_meta'),
                'update_callback' => null,
                'schema' => null,
            )
        );
    }

    public function get_sisa_custom_meta($object)
    {

        $the_meta = get_post_meta($object['id'], 'smartimagesearch', true);

        if (is_null($the_meta) || empty($the_meta)) {
            return null;
        }
        return $the_meta;
    }

    public function delete_sisa_meta($metadata, $attachment_id)
    {
        if (is_array(get_post_meta($attachment_id, 'smartimagesearch'))) {
            update_post_meta($attachment_id, 'smartimagesearch', null);
        }

        return $metadata;
    }

    public function add_sisa_api_routes()
    {
        register_rest_route('smartimagesearch/v1', '/proxy', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::READABLE,
            'callback' => $this->get_method('api_bulk_sisa'),
            // 'permission_callback' => $this->get_method('sisa_proxy_permissions_check'),
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

    // get saved settings from WP DB
    public function api_get_sisa_settings($request)
    {
        $api_key = get_option('smartimagesearch_api_key');
        $response = new WP_REST_RESPONSE(array(
            'success' => true,
            'value' => array(
                'apiKey' => !$api_key ? '' : $api_key,
            ),
        ), 200);
        $response->set_headers(array('Cache-Control' => 'no-cache'));
        return $response;
    }

    // save settings to WP DB
    public function api_update_sisa_settings($request)
    {
        $json = $request->get_json_params();
        // store the values in wp_options table
        $updated_api_key = update_option('smartimagesearch_api_key', $json['apiKey']);
        $response = new WP_REST_RESPONSE(array(
            'success' => $updated_api_key,
            'value' => $json,
        ), 200);
        $response->set_headers(array('Cache-Control' => 'no-cache'));
        return $response;
    }

    // check permissions
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

    public function api_bulk_sisa($request)
    {
        // $this::write_log($request);

        $params = $request->get_query_params();

        $now = time();
        if (isset($params['start']) && is_int($params['start'])) {
            $now = $params['start'];
        }

        $posts_per_page = 5;

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

        if (!isset($params['start'])) {
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
            error_log('image file path: ' . $image_file_path);
            if ($image_file_path === false) {
                $response[] = new WP_Error('bad_image', 'image filepath not found');
                continue;
            }
            $gcv_client = new SmartImageSearch_GCV_Client();
            $gcv_result = $gcv_client->get_annotation($image_file_path);

            if (is_wp_error($gcv_result)) {
                ++$errors;
                $annotation_data['gcv_data'] = $gcv_result;
                $response[] = $annotation_data;
                continue;
            }

            $cleaned_data = $this->clean_up_gcv_data($gcv_result);
            $this->update_attachment_meta($cleaned_data);

            $annotation_data['gcv_data'] = $cleaned_data;

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

        if (isset($data->landmarkAnnotations)) {

            $landmark['description'] = $data->landmarkAnnotations[0]->description;
            $landmark['score'] = $data->landmarkAnnotations[0]->score;
            $cleaned_data['landmark'] = $landmark;
        }
        if (isset($data->labelAnnotations)) {
            $labels = array();
            foreach ($data->labelAnnotations as $label) {
                $labels[] = array('description' => $label->description, 'score' => $label->score);
            }
            $cleaned_data['labels'] = $labels;
        }
        if (isset($data->webDetection)) {
            $web_entities = array();
            foreach ($data->webDetection->webEntities as $entity) {
                if (isset($entity->description))
                    $web_entities[] = array('description' => $entity->description, 'score' => $entity->score);
            }
            $cleaned_data['webEntities'] = $web_entities;
            if ($data->webDetection->bestGuessLabels) {
                foreach ($data->webDetection->bestGuessLabels as $web_label) {
                    $web_labels[] = $web_label->label;
                }
                $cleaned_data['webLabels'] = $web_labels;
            }
        }
        if (isset($data->localizedObjectAnnotations)) {
            $objects = array();
            foreach ($data->localizedObjectAnnotations as $object) {
                $objects[] = array('description' => $object->name, 'score' => $object->score);
            }
            $cleaned_data['objects'] = $objects;
        }
        if (isset($data->logoAnnotations)) {
            $logos = array();
            foreach ($data->logoAnnotations as $logo) {
                $logos[] = array('description' => $logo->description, 'score' => $logo->score);
            }
            $cleaned_data['logos'] = $logos;
        }
        if (isset($data->textAnnotations)) {
            $text = $data->textAnnotations[0]->description;
            $cleaned_data['text'] = $text;
        }
        return $cleaned_data;
    }

    public function update_attachment_meta($data)
    {
        // check for wp error
        // group different data
        // convert to arrays
        // determine best alt text
        // save alt text (if desired)
        // remove duplicates in arrays
        // remove duplicates between arrays

    }

    public function create_image_alt_text($data)
    {
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

    public static function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}
