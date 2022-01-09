<?php

class SmartImageSearch_Image
{
    const ORIGINAL = 0;

    private $settings;
    private $id;
    private $name;
    private $wp_metadata;

    public function __construct(
        $settings,
        $id,
        $wp_metadata = null
    ) {
        $this->settings = $settings;
        $this->id = $id;
        $this->original_filename = null;
        $this->wp_metadata = $wp_metadata;
        $this->parse_wp_metadata();
    }

    private function parse_wp_metadata()
    {
        if (!is_array($this->wp_metadata)) {
            $this->wp_metadata = wp_get_attachment_metadata($this->id);
        }
        if (!is_array($this->wp_metadata)) {
            return;
        }
        if (!isset($this->wp_metadata['file'])) {
            /* No file metadata found, this might be another plugin messing with
            metadata. Simply ignore this! */
            return;
        }

        $upload_dir = wp_upload_dir();
        $path_prefix = $upload_dir['basedir'] . '/';
        $path_info = pathinfo($this->wp_metadata['file']);
        if (isset($path_info['dirname'])) {
            $path_prefix .= $path_info['dirname'] . '/';
        }

        /* Do not use pathinfo for getting the filename.
        It doesn't work when the filename starts with a special character. */
        $path_parts = explode('/', $this->wp_metadata['file']);
        $this->name = end($path_parts);
        $filename = $path_prefix . $this->name;
        $this->original_filename = $filename;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_original_filename()
    {
        return $this->original_filename;
    }

    public function get_wp_metadata()
    {
        return $this->wp_metadata;
    }

    public function file_type_allowed()
    {
        return in_array($this->get_mime_type(), array('image/jpeg', 'image/png'));
    }

    public function get_mime_type()
    {
        return get_post_mime_type($this->id);
    }

    public function get_smartimage_meta()
    {

        $gcv_credit = 0;

        $response = array(
            'gcv_api' => $gcv_credit,
        );


        $gcv_client = new GCV_Client();
        $annotation_data = $gcv_client->get_annotation($this->original_filename);

        if (is_wp_error($annotation_data)) {
            return $annotation_data;
        }

        $response['gcv_api'] = 1;


        $image_markup = $this->update_smartimage_meta($annotation_data);

        if (is_wp_error($image_markup)) {
            return $image_markup;
        }

        $response['image_markup'] = $image_markup;

        return $response;
    }

    public function parse_smartimage_meta($response)
    {
        $wp_meta = array();

        if (SmartImageSearch_Settings::emotions_active() && !empty($response->faceAnnotations)) {
        }
    }

    public function update_smartimage_meta($annotation_data)
    {

        $smartimage_meta = get_post_meta($this->id, 'smartimagesearch', true);
        SmartImageSearch::write_log($smartimage_meta);

        if (!is_array($smartimage_meta)) {

            $smartimage_meta = array(
                'index_date' => date(DateTime::ISO8601), //need to make this timezone adjusted
                // then need to group metadata by type (labels, emotions, etc.)
            );
        } else {

            // $smartimage_meta[] = time();
        }

        $result = update_post_meta($this->id, 'smartcropai', $smartimage_meta);

        /*
        This action is being used by WPML:
        https://gist.github.com/srdjan-jcc/5c47685cda4da471dff5757ba3ce5ab1
         */
        do_action('update_smartcrop_meta', $this->id, 'smartcropai', $this->wp_metadata);
    }

    public function get_image_size($size = self::ORIGINAL, $create = false)
    {
        if (isset($this->sizes[$size])) {
            return $this->sizes[$size];
        } elseif ($create) {
            return new SmartCrop_Image_Size();
        } else {
            return null;
        }
    }

    public static function is_original($size)
    {
        return self::ORIGINAL === $size;
    }
}
