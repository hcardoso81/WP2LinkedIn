/**
 * WP2LinkedIn – Admin Scripts
 */
jQuery(document).ready(function($) {

  // Botón para cargar organizaciones
  $('#wp2linkedin-load-orgs').on('click', function(e) {
    e.preventDefault();

    var $btn = $(this);
    $btn.prop('disabled', true).text('Cargando...');

    $.post(wp2linkedin.ajaxurl, {
      action: 'wp2linkedin_get_orgs',
      _ajax_nonce: wp2linkedin.nonce
    }, function(response) {
      var $select = $('#wp2linkedin-org-select');
      $select.empty();

      if (Array.isArray(response) && response.length) {
        response.forEach(function(orgId) {
          $select.append('<option value="' + orgId + '">' + orgId + '</option>');
        });
      } else {
        console.log('No se encontraron organizaciones.');
        $select.append('<option value="">Ninguna organización</option>');
      }

      $btn.prop('disabled', false).text('Cargar organizaciones');
    }).fail(function() {
      console.error('Error al cargar organizaciones.');
      $btn.prop('disabled', false).text('Cargar organizaciones');
    });
  });

  // Guardar organización seleccionada
  $('#wp2linkedin-org-select').on('change', function() {
    var orgId = $(this).val();

    $.post(wp2linkedin.ajaxurl, {
      action: 'wplp_save_org',
      org_id: orgId,
      _ajax_nonce: wp2linkedin.nonce
    }, function(response) {
      if (response.success) {
        console.log('Organización guardada correctamente.');
      } else {
        console.error('Error al guardar la organización.');
      }
    }).fail(function() {
      console.error('Error de AJAX al guardar la organización.');
    });
  });

});
