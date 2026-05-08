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
        $status = wplp_get_linkedin_status($post_id);
        $display = wplp_get_linkedin_status_display($status);
        $date = get_post_meta($post_id, '_linkedin_posted_date', true);

        echo '<span style="color:' . esc_attr($display['color']) . ';">';
        echo '<span class="dashicons ' . esc_attr($display['icon']) . '" aria-hidden="true"></span> ';
        echo esc_html($display['label']);
        echo '</span>';

        if ($date && in_array($status, ['published', 'manual_published'], true)) {
            echo '<br><small>' . esc_html(date('d/m/Y H:i', strtotime($date))) . '</small>';
        }

    }

}

