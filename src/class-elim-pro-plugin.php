<?php

class EnlightenedImages_ProLite
{
  protected $_elim;

  public function __construct(EnlightenedImages_Plugin $instance)
  {
    $this->_elim = $instance;

    add_action('init', $this->get_method('init'));

    if (is_admin()) {
      add_action('admin_init', $this->get_method('admin_init'));
      add_action('admin_init', $this->get_method('ajax_init'));
    }

    update_option('elim_pro_plugin', (int) 1);
  }

  public function __call($method, $args)
  {
    return call_user_func_array(array($this->_elim, $method), $args);
  }

  public function __get($key)
  {
    return $this->_elim->$key;
  }

  public function __set($key, $val)
  {
    return $this->_elim->$key = $val;
  }

  protected function get_method($name)
  {
    return array($this, $name);
  }

  public function init()
  {

    add_filter('wp_generate_attachment_metadata', $this->get_method('process_attachment_upload'), 10, 2);
  }

  public function ajax_init()
  {
    add_filter(
      'wp_ajax_elim_async_annotate_upload_new_media',
      $this->get_method('ajax_annotate_on_upload')
    );
  }

  public function process_attachment_upload($metadata, $attachment_id)
  {
    $annotate_upload = get_option('elim_on_media_upload', 'async');
    if ($annotate_upload == 'async') {
      $this->async_annotate($metadata, $attachment_id);
    } elseif ($annotate_upload == 'blocking') {
      $this->blocking_annotate($metadata, $attachment_id);
    }
    // In case of error, set alt text to empty 
    if (empty(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))) {
      update_post_meta($attachment_id, '_wp_attachment_image_alt', '');
    }


    return $metadata;
  }

  //Does an "async" smart annotation by making an ajax request right after image upload
  public function async_annotate($metadata, $attachment_id)
  {
    $context     = 'wp';
    $action      = 'elim_async_annotate_upload_new_media';
    $_ajax_nonce = wp_create_nonce('elim_new_media-' . $attachment_id);
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
    // error_log('annotating in the background');
    if (!is_array($_POST['metadata'])) exit();

    if (current_user_can('upload_files')) {

      $attachment_id = intval($_POST['attachment_id']);
      $this->annotate_an_image_on_upload($attachment_id);
      // error_log(print_r($result, true));
    }

    exit();
  }

  public function blocking_annotate($metadata, $attachment_id)
  {

    if (current_user_can('upload_files') && is_array($metadata)) {
      $this->annotate_an_image_on_upload($attachment_id);
    }
  }

  public function annotate_an_image_on_upload($p)
  {
    $image = null;

    if (has_image_size('medium')) {
      $image = wp_get_attachment_image_url($p, 'medium');
    } else {
      $image = wp_get_original_image_url($p);
    }

    if ($image === false) {
      return;
    }

    $image_response = $this->image_client->get_annotation($image);

    if (is_wp_error($image_response)) {
      return;
    }

    $this->update_image_alt_text_az($image_response, $p);
  }
}

$elim_prolite = new EnlightenedImages_ProLite(EnlightenedImages_Plugin::getInstance());
