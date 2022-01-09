<?php

class Azure_Client
{

  public function get_annotation($original_file)
  {

    // $img = file_get_contents($original_file);
    // $data = base64_encode($img);
    $baseurl = get_option('smartimagesearch_azure_url');
    $apikey = get_option('smartimagesearch_api_key');
    $body = array('url' => $original_file);

    $request = wp_remote_post($baseurl, array(
      'headers' => array(
        // Request headers
        'Content-Type' => 'application/json',
        'Ocp-Apim-Subscription-Key' => $apikey,
      ),
      'body' => json_encode($body),
      'method' => 'POST',
      'data_format' => 'body',
    ));

    if (is_wp_error($request)) {
      return $request;
    }

    $data = json_decode(wp_remote_retrieve_body($request));
    if (isset($data->error)) {
      $response_code = $data->error->code;
      $response_message = $data->error->message;
    } else {
      $response_code = wp_remote_retrieve_response_code($request);
      $response_message = wp_remote_retrieve_response_message($request);
    }

    if (200 != $response_code && !empty($response_message)) {
      return new WP_Error($response_code, $response_message, $data);
    } elseif (200 != $response_code) {
      return new WP_Error($response_code, "Uknown error", $data);
    } else {
      return $data->responses[0];
    }
  }
}