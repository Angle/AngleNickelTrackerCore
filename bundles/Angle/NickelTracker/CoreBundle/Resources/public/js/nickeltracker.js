$( document ).ready(function() {
    $('.editable-td').each(function(){
        $(this).append('<i class="fa fa-pencil"></i>');
    });

    /*
    $('.editable-td').on('click', function() {
        var $this = $(this);
        var $input = $('<input>', {
            value: $this.text().trim(),
            type: 'text',
            blur: function() {
                $this.text(this.value);
            },
            keyup: function(e) {
                if (e.which === 13) $input.blur();
            }
        }).appendTo( $this.empty() ).focus();
    });
    */
});