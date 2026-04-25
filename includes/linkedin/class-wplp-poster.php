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
        
        
    // --- NUEVO: Chequeo de expiraci¨®n local del token ---
    $token_expires = get_option('wp2linkedin_token_expires');
    if (!$token_expires || $token_expires < time()) {
        WPLP_Logger::error('Token de LinkedIn expirado (local check)', [
            'post_id' => $post_id,
            'token_expires' => $token_expires
        ]);

        // Guardamos flag para el admin
        update_option('wplp_token_expired', 1);

        return ['error' => 'token_expired'];
    }
    // ------------------------------------------------------

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
            return ['error' => 'token_missing'];
        }

        if (!$this->org_id) {
            WPLP_Logger::error('No hay organizaciÃ³n por defecto configurada.', [
                'post_id' => $post_id
            ]);
            return false;
        }

        $title   = get_the_title($post_id);
        $content_linkedin = wplp_get_linkedin_content($post_id);
        $clean_content = wplp_clean_linkedin_content($content_linkedin);

        if ($clean_content === '') {
            WPLP_Logger::error('Contenido de LinkedIn vacio', [
                'post_id' => $post_id
            ]);

            return [
                'error' => 'linkedin_content_missing',
                'message' => 'Completa el campo Contenido para LinkedIn antes de publicar.'
            ];
        }

        $url     = get_permalink($post_id);
        $featured_image_id = get_post_thumbnail_id($post_id);

        $media_assets = [];

        // Subir imagen si existe
        if ($featured_image_id) {
            $media_asset = $this->upload_image_to_linkedin($featured_image_id, $this->org_id);

            if (is_array($media_asset) && isset($media_asset['error']) && $media_asset['error'] === 'token_expired') {
                return ['error' => 'token_expired'];
            }

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
                    'shareCommentary' => ['text' => $clean_content . "\n\n" . $url],
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
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Detectar token expirado
        if (
            $http_code === 401 &&
            isset($data['code']) &&
            $data['code'] === 'EXPIRED_ACCESS_TOKEN'
        ) {

            // Guardamos flag para mostrar alerta en el admin
            update_option('wplp_token_expired', 1);

            WPLP_Logger::error('Token de LinkedIn expirado', [
                'post_id' => $post_id
            ]);

            return ['error' => 'token_expired'];
        }

        if ($http_code === 201) {

            update_post_meta($post_id, '_linkedin_posted', 1);
            update_post_meta($post_id, '_linkedin_posted_date', current_time('mysql'));

            WPLP_Logger::info('Post publicado correctamente', [
                'post_id' => $post_id
            ]);

            return true;
        } else {

            WPLP_Logger::error('Error al publicar el post en LinkedIn', [
                'post_id'   => $post_id,
                'http_code' => $http_code,
                'response'  => $response_body,
            ]);

            return [
                'error' => 'linkedin_error',
                'http_code' => $http_code,
                'message' => $data['message'] ?? 'LinkedIn error desconocido'
            ];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $register_response = curl_exec($ch);
        $register_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($register_http_code === 401) {
            return ['error' => 'token_expired'];
        }

        if ($register_http_code !== 200) {
            WPLP_Logger::error('Error al registrar la imagen', [
                'image_id' => $image_id,
                'http_code' => $register_http_code
            ]);
            return false;
        }

        $register_data = json_decode($register_response, true);

        if (!isset($register_data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'])) {

            WPLP_Logger::error('LinkedIn Register Upload: No upload URL received', [
                'image_id' => $image_id
            ]);

            return false;
        }

        $upload_url = $register_data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset_id = $register_data['value']['asset'];

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $upload_response = curl_exec($ch);
        $upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($upload_http_code === 201 || $upload_http_code === 200) {

            return [
                'asset' => $asset_id,
                'upload_response' => $upload_response
            ];
        } else {

            WPLP_Logger::error('Error al subir la imagen', [
                'image_id' => $image_id,
                'http_code' => $upload_http_code
            ]);

            return false;
        }
    }
}
