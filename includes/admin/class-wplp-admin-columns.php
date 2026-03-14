<?php

if (!defined('ABSPATH')) exit;

class WPLP_Admin_Columns {

    public function __construct() {

        add_filter('manage_posts_columns', [$this, 'add_column']);
        add_action('manage_posts_custom_column', [$this, 'render_column'], 10, 2);

    }

    public function add_column($columns) {

        $screen = get_current_screen();

        if ($screen && $screen->post_type === 'post') {
            $columns['linkedin_status'] = 'LinkedIn';
        }

        return $columns;

    }

    public function render_column($column, $post_id) {

        if ($column !== 'linkedin_status') {
            return;
        }

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

}

