<?php

class EnlightenedImages_Azure_Client
{

    public function get_annotation($original_file)
    {

        if (!file_get_contents($original_file)) {
            return new WP_Error('bad_image_url', __("Image URL not readable. You'll need to manually add alt text."), $original_file);
        }

        $apikey = get_option('elim_api_key');
        $endpoint_base = get_option('elim_azure_endpoint');
        $body = json_encode(array(
            'url' => $original_file,
        ));
        $content_type = 'application/json';

        $request = wp_remote_post($endpoint_base . '/vision/v3.1/describe', array(
            'headers' => array(
                'Content-Type' => $content_type,
                'Ocp-Apim-Subscription-Key' => $apikey,
            ),
            'body' => $body,
            'method' => 'POST',
            'data_format' => 'body',
        ));

        if (is_wp_error($request)) {
            return $request;
        }

        $data = json_decode(wp_remote_retrieve_body($request));

        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        error_log($original_file);
        error_log(print_r($data, true));
        error_log($response_code);
        error_log($response_message);

        if (200 != $response_code && !empty($data)) {
            return new WP_Error($data->code, $data->message);
        }
        if (200 != $response_code && !empty($response_message)) {
            return new WP_Error($response_code, $response_message . ': ' . __("Uknown error. Make sure your images are publicly accessible and API key valid."));
        }

        return $data->description;
    }
}
