<?php

if (!defined('ABSPATH')) exit;

class WPLP_Plugin {

    public function init() {

        new WPLP_Admin();
        new WPLP_OAuth();
        new WPLP_Organizations();
        new WPLP_Poster();

        $this->register_columns();
        $this->register_acf();

    }

    private function register_columns() {

        add_filter('manage_posts_columns', function ($columns) {

            $screen = get_current_screen();

            if ($screen && $screen->post_type === 'post') {
                $columns['linkedin_status'] = 'LinkedIn';
            }

            return $columns;

        });

        add_action('manage_posts_custom_column', function ($column, $post_id) {

            if ($column === 'linkedin_status') {

                $posted = get_post_meta($post_id, '_linkedin_posted', true);

                if ($posted) {

                    $date = get_post_meta($post_id, '_linkedin_posted_date', true);

                    echo '<span style="color:green;">✅ Publicado</span>';

                    if ($date) {
                        echo '<br><small>' . date('d/m/Y H:i', strtotime($date)) . '</small>';
                    }

                } else {

                    echo '<span style="color:#ccc;">⏳ Pendiente</span>';

                }

            }

        }, 10, 2);

    }

    private function register_acf() {

        add_action('acf/init', function () {

            if (function_exists('acf_add_local_field_group')) {

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

        });

    }

}