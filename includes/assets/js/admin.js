/**
 * WP2LinkedIn – Admin Scripts
 */
jQuery(document).ready(function($) {
  // Botón para cargar organizaciones
  $('#wp2linkedin-load-orgs').on('click', function(e) {
    e.preventDefault();

    var $btn = $(this);
    $btn.prop('disabled', true).text('Cargando...');

    $.post(ajaxurl, {
      action: 'wp2linkedin_get_orgs'
    }, function(response) {
      var $select = $('#wp2linkedin-org-select');
      $select.empty();

      if (response.length) {
        response.forEach(function(orgId) {
          $select.append('<option value="' + orgId + '">' + orgId + '</option>');
        });
      } else {
        alert('No se encontraron organizaciones.');
      }

      $btn.prop('disabled', false).text('Cargar organizaciones');
    });
  });

  // Guardar organización seleccionada
  $('#wp2linkedin-org-select').on('change', function() {
    var orgId = $(this).val();

    $.post(ajaxurl, {
      action: 'wp2linkedin_save_org',
      org_id: orgId,
      _ajax_nonce: wp2linkedin.nonce
    }, function(response) {
      if (response.success) {
        alert('Organización guardada correctamente.');
      } else {
        alert('Error al guardar la organización.');
      }
    });
  });
});
