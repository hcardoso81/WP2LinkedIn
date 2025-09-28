$.post(wplp.ajaxurl, {
  action: 'wp2linkedin_get_orgs',
  _ajax_nonce: wplp.nonce
}, function(response) {
  var $select = $('#wp2linkedin-org-select');
  $select.empty();

  if (Array.isArray(response) && response.length) {
    response.forEach(function(org) {
      $select.append('<option value="' + org.id + '">' + org.name + '</option>');
    });
  } else {
    $select.append('<option value="">Ninguna organización</option>');
  }

  $btn.prop('disabled', false).text('Cargar organizaciones');
}).fail(function() {
  console.error('Error al cargar organizaciones.');
  $btn.prop('disabled', false).text('Cargar organizaciones');
});


  // Guardar organización seleccionada con botón Confirmar
  $('#wp2linkedin-confirm-org').on('click', function(e) {
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
    }, function(response) {
      if (response.success) {
        alert('✅ Organización guardada correctamente: ' + orgId);
        location.reload(); // refresca la página para mostrar la notificación verde
      } else {
        alert('❌ Error al guardar la organización.');
      }
    }).fail(function() {
      alert('❌ Error de AJAX al guardar la organización.');
    });
  });

});
