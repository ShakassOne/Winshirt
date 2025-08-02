jQuery( function( $ ) {
  $('#winshirt-send-participants').on('click', function() {
    var postId = $(this).data('post-id');
    var nonce  = $('input[name="winshirt_send_lottery_nonce"]').val();
    var status = $('#winshirt-send-status').text( 'Envoi…' );

    $.post( WinShirtLottery.ajaxUrl, {
      action: WinShirtLottery.action,
      post_id: postId,
      nonce: nonce
    })
    .done(function( res ) {
      if ( res.success ) {
        status.text( res.data );
      } else {
        status.text( res.data || 'Erreur' );
      }
    })
    .fail(function() {
      status.text( 'Erreur AJAX' );
    });
  });
});
