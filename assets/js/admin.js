jQuery(document).ready(function ($) {

  // --- Cargar organizaciones ---
  $('#wp2linkedin-load-orgs').on('click', function (e) {

    e.preventDefault();

    var $btn = $(this);
    $btn.prop('disabled', true).text('Cargando...');

    $.post(wplp.ajaxurl, {
      action: 'wp2linkedin_get_orgs',
      _ajax_nonce: wplp.nonce
    }, function (response) {

      var $select = $('#wp2linkedin-org-select');
      $select.empty();

      if (Array.isArray(response) && response.length) {

        response.forEach(function (org) {
          $select.append('<option value="' + org.id + '">' + org.name + '</option>');
        });

      } else {

        $select.append('<option value="">Ninguna organización</option>');

      }

      $btn.prop('disabled', false).text('Cargar organizaciones');

    }).fail(function () {

      console.error('Error al cargar organizaciones.');
      $btn.prop('disabled', false).text('Cargar organizaciones');

    });

  });


  // --- Guardar organización ---
  $('#wp2linkedin-confirm-org').on('click', function (e) {

    e.preventDefault();

    var orgId = $('#wp2linkedin-org-select').val();

    if (!orgId) {
      alert('Selecciona una organización primero.');
      return;
    }

    $.post(wplp.ajaxurl, {

      action: 'wplp_save_org',
      org_id: orgId,
      _ajax_nonce: wplp.nonce

    }, function (response) {

      if (response.success) {

        alert('✅ Organización guardada correctamente: ' + orgId);

      } else {

        alert('❌ Error al guardar la organización.');

      }

    }).fail(function () {

      alert('❌ Error de AJAX al guardar la organización.');

    });

  });


  // --- Publicar post en LinkedIn ---
jQuery(document).ready(function ($) {

    $('#linkedin-publish-btn').on('click', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var postId = $btn.data('post-id');

        if (!postId) {
            $('#linkedin-status').html('❌ Post ID no definido.');
            return;
        }

        var originalText = $btn.text();
        $btn.prop('disabled', true).text('Publicando...');
        $('#linkedin-status').html(''); // limpiar mensajes previos

        $.post(wplp.ajaxurl, {
            action: 'linkedin_publish_post',
            post_id: postId,
            security: wplp.nonce
        }, function (response) {

            if (response.success) {
                $('#linkedin-status').html('<span style="color:green;">' + response.data.message + '</span>');
                location.reload();
            } else {
                var message = response?.data?.message || '❌ Error al publicar.';

                // Insertar mensaje HTML directamente
                $('#linkedin-status').html('<span style="color:red;">' + message + '</span>');

                // Detectar token expirado para navegación
                if (message.includes('Reconectar ahora')) {
                    $('#linkedin-status a').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = $(this).attr('href');
                    });
                }

                $btn.prop('disabled', false).text(originalText);
            }

        }).fail(function () {
            $('#linkedin-status').html('❌ Error de AJAX al publicar el post.');
            $btn.prop('disabled', false).text(originalText);
        });
    });

});

});