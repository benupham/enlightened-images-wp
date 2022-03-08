<?php

class SmartImageSearch_SisaPro_Client
{

  public function get_annotation($file_path, $features = 'WEB_DETECTION,OBJECT_LOCALIZATION')
  {
    error_log("using pro client");

    $baseurl = 'https://enlightenedimageswp.com/wp-json/smartimageserver/v1/proxy';
    $apikey = get_option('sisa_pro_api_key');

    $request = wp_remote_get($baseurl . '?image=' . $file_path . '&api_key=' . $apikey . '&features=' . $features, array(
      'headers' => array('Content-Type' => 'application/json'),
      'method' => 'GET',
    ));
    /**
     * DO SAFETY CHECK ON DATA COMING FROM THE SISA SERVER
     */
    if (is_wp_error($request)) {
      return $request;
    }

    $data = json_decode(wp_remote_retrieve_body($request));
    // error_log('returned from pro server');
    // error_log(print_r($data, true));

    if (isset($data->code)) {
      $response_code = $data->code;
      $response_message = $data->message;
    } else {
      $response_code = wp_remote_retrieve_response_code($request);
      $response_message = wp_remote_retrieve_response_message($request);
    }

    // error_log($response_code . ' ' . $response_message);

    if (200 != $response_code && !empty($response_message)) {
      return new WP_Error($response_code, $response_message, $data);
    } elseif (200 != $response_code) {
      return new WP_Error($response_code, "Uknown error", $data);
    } else {
      return $data;
    }
  }
}
