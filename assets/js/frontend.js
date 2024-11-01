(function($) {
  $(document).on('click touch', '.wpccb_copy', function(e) {
    if (wpccb_vars.confirm === 'yes') {
      if (confirm(wpccb_vars.confirm_message)) {
        wpccb_copy();
      }
    } else {
      wpccb_copy();
    }

    e.preventDefault();
  });

  function wpccb_copy() {
    $('[name^="billing_"]').each(function() {
      var $this = $(this);
      var name = $this.attr('name');
      var val = $this.val();
      var s_name = name.replace('billing_', 'shipping_');

      $('[name="' + s_name + '"]').val(val).trigger('change');
      $(document).trigger('wpccb_field_copied', [name, val]);
    });

    $(document.body).trigger('update_checkout');
    $(document).trigger('wpccb_copied');
  }
})(jQuery);