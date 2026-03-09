<?php

if (!defined('ABSPATH')) exit;

class WPLP_Content_Fields {

    public function __construct() {

        add_action('acf/init', [$this, 'register_fields']);

    }

    public function register_fields() {

        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_wplp_linkedin',
            'title' => 'Contenido para LinkedIn',
            'fields' => [
                [
                    'key' => 'field_wplp_content_linkedin',
                    'label' => 'Contenido para LinkedIn',
                    'name' => 'content_linkedin',
                    'type' => 'wysiwyg',
                    'instructions' => 'Este contenido se usará para publicar en LinkedIn si está completo.',
                    'required' => 0,
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
            'position' => 'acf_after_title',
            'style' => 'default',
            'label_placement' => 'top',
            'active' => true,
        ]);

    }

}