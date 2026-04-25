<?php

if (!defined('ABSPATH')) exit;

class WPLP_Admin_Columns {

    public function __construct() {

        add_filter('manage_posts_columns', [$this, 'add_column'], 10, 2);
        add_action('manage_posts_custom_column', [$this, 'render_column'], 10, 2);

    }

    public function add_column($columns, $post_type = null) {

        if ($post_type === null) {
            $screen = get_current_screen();
            $post_type = $screen ? $screen->post_type : null;
        }

        if ($post_type === 'post') {
            $columns['linkedin_content'] = 'Contenido LinkedIn';
            $columns['linkedin_status'] = 'LinkedIn';
        }

        return $columns;

    }

    public function render_column($column, $post_id) {

        if ($column === 'linkedin_content') {
            $has_content = wplp_has_linkedin_content($post_id);
            $label = $has_content ? 'Con contenido para LinkedIn' : 'Contenido LinkedIn vacio';
            $class = $has_content ? 'wplp-column-icon--ok' : 'wplp-column-icon--empty';
            $icon = $has_content ? 'dashicons-yes-alt' : 'dashicons-dismiss';

            echo '<span class="wplp-column-icon ' . esc_attr($class) . '" title="' . esc_attr($label) . '">';
            echo '<span class="dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span>';
            echo '<span class="screen-reader-text">' . esc_html($label) . '</span>';
            echo '</span>';

            return;
        }

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

