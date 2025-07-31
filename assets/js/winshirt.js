jQuery(function($) {
    var modal = $('#winshirt-modal');
    var btn = $('#winshirt-customize');
    var span = $('<span class="winshirt-close">&times;</span>');

    btn.on('click', function(e) {
        e.preventDefault();
        modal.show();
    });

    modal.on('click', '.winshirt-close', function() {
        modal.hide();
    });
});
