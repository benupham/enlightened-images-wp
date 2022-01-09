<?php

class SmartImageSearch_Settings extends SmartImageSearch_WP_Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function admin_menu()
    {
        global $sisa_settings_page;
        $sisa_settings_page = add_management_page(
            __('Smart Image Search AI Settings'),
            esc_html__('Smart Image Search AI'),
            'manage_options',
            'smartcropai',
            array($this, 'smartimagesearch_settings_do_page')
        );
    }

    public function smartimagesearch_settings_do_page()
    {
?>
        <div id="smart_image_crop_settings"></div>
        <div id="smart_image_crop_dashboard"></div>
<?php
    }
}
