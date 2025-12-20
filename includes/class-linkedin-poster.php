<?php
if (!defined('ABSPATH')) exit;

class WPLP_Poster
{
    private $token;
    private $org_id;

    public function __construct()
    {
        $this->token  = get_option('wp2linkedin_access_token');
        $this->org_id = get_option('wp2linkedin_default_org');
    }

    public function publish_to_linkedin($post_id, $post)
    {
        if (wp_is_post_revision($post_id)) return false;

        // Evitar duplicados
        if (get_post_meta($post_id, '_linkedin_posted', true)) {
            WPLP_Logger::error('Post ya publicado en LinkedIn', [
                'post_id' => $post_id
            ]);
            return false;
        }
        // Validaciones
        if (!$this->token) {
            WPLP_Logger::error('No hay token configurado.', [
                'post_id' => $post_id
            ]);
            return false;
        }

        if (!$this->org_id) {
            WPLP_Logger::error('No hay organización por defecto configurada.', [
                'post_id' => $post_id
            ]);
            return false;
        }

        $title   = get_the_title($post_id);
        $content_linkedin = function_exists('get_field') ? get_field('content_linkedin', $post_id) : '';
        $clean_content = wp_strip_all_tags($content_linkedin);
        $url     = get_permalink($post_id);
        $featured_image_id = get_post_thumbnail_id($post_id);

        $media_assets = [];

        // Subir imagen si existe
        if ($featured_image_id) {
            $media_asset = $this->upload_image_to_linkedin($featured_image_id, $this->org_id);
            if ($media_asset) {
                $media_assets[] = $media_asset['asset'];
                WPLP_Logger::info('Imagen subida correctamente, asset = ' . $media_asset['asset'], [
                'post_id' => $post_id
            ]);
            }
        }

        // Construir payload
        $body = [
            'author' => "urn:li:organization:" . $this->org_id,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $clean_content  . "\n\n" . $url],
                    'shareMediaCategory' => empty($media_assets) ? 'ARTICLE' : 'IMAGE',
                ]
            ],
            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC']
        ];

        // Agregar media si existe
        if (!empty($media_assets)) {
            $body['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [];
            foreach ($media_assets as $asset) {
                $body['specificContent']['com.linkedin.ugc.ShareContent']['media'][] = [
                    'status' => 'READY',
                    'description' => ['text' => $title],
                    'media' => $asset,
                    'title' => ['text' => $title]
                ];
            }
        }

        $response = wp_remote_post('https://api.linkedin.com/v2/ugcPosts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ],
            'body' => wp_json_encode($body)
        ]);

        // Logs detallados
        if (is_wp_error($response)) {
            WPLP_Logger::error('(WP_Error): ' . $response->get_error_message(), [
                'post_id' => $post_id
            ]);            
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($response);


        if ($http_code === 201) {
            update_post_meta($post_id, '_linkedin_posted', 1);
            update_post_meta($post_id, '_linkedin_posted_date', current_time('mysql'));
            WPLP_Logger::info('Post publicado correctamente', [
                'post_id' => $post_id
            ]);
            return true;
        } else {
            WPLP_Logger::error('Error al publicar el post', [
                'post_id' => $post_id
            ]);
            return false;
        }
    }

    private function upload_image_to_linkedin($image_id, $organization_id)
    {
        $access_token = $this->token;

        $image_path = get_attached_file($image_id);
        if (!$image_path || !file_exists($image_path)) {
            WPLP_Logger::error('Imagen no encontrada', [
                'image_id' => $image_id
            ]);
            return false;
        }

        // Registrar upload
        $register_data = [
            'registerUploadRequest' => [
                'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                'owner' => 'urn:li:organization:' . $organization_id,
                'serviceRelationships' => [
                    ['relationshipType' => 'OWNER', 'identifier' => 'urn:li:userGeneratedContent']
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.linkedin.com/v2/assets?action=registerUpload');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($register_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // ⚡ activado para seguridad
        $register_response = curl_exec($ch);
        $register_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($register_http_code !== 200) {
            WPLP_Logger::error('Error al registrar la imagen', [
                'image_id' => $image_id,
                'http_code' => $register_http_code
            ]);
            return false;
        }

        $register_data = json_decode($register_response, true);
        if (!isset($register_data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'])) {
            WPLP_Logger::error('inkedIn Register Upload: No upload URL received', [
                'image_id' => $image_id
            ]);
            return false;
        }

        $upload_url = $register_data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset_id = $register_data['value']['asset'];

        // Subir imagen
        $image_data = file_get_contents($image_path);
        $mime_type = wp_get_image_mime($image_path);

        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: ' . $mime_type
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $upload_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $image_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // ⚡ activado para seguridad
        $upload_response = curl_exec($ch);
        $upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($upload_http_code === 201 || $upload_http_code === 200) {
            return ['asset' => $asset_id, 'upload_response' => $upload_response];
        } else {
            WPLP_Logger::error('Error al subir la imagen', [
                'image_id' => $image_id,
                'http_code' => $upload_http_code
            ]);
            return false;
        }
    }
}
