<?php

if (!defined('ABSPATH')) exit;

class WPLP_Plugin {

    public function init() {

        new WPLP_Admin();
        new WPLP_Admin_Columns();

        new WPLP_OAuth();
        new WPLP_Organizations();
        new WPLP_Poster();

        new WPLP_Content_Fields();

    }

}