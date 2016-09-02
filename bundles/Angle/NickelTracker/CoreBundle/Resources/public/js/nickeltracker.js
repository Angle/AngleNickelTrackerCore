$( document ).ready(function() {
    $('.editable-td').each(function(){
        $(this).append('<i class="fa fa-pencil"></i>');
        $(this).append('<i class="fa fa-spinner fa-spin"></i>');
    });

    $('.editable-field')
        .on('keyup', function(e) {
            if (e.which === 13) $(this).blur();
        })
        .on('blur', function(){
            var $this = $(this);
            if ($this.val() != $this.data('original-value')) {
                // Something changed! Trigger the AJAX request..
                console.log('Updating Object ID ' + $this.data('object-id') + ' property "' + $this.data('update-property') + '" with value: ' + $this.val());
                $this.parent().addClass("loading");
                $this.prop('disabled', true);

                // Fire off the request to /form.php
                var request = $.ajax({
                    url: $this.data('update-path'),
                    type: "POST",
                    dataType: "json",
                    contentType: 'application/json; charset=UTF-8',
                    data: JSON.stringify({
                        "id": $this.data('object-id'),
                        "property": $this.data('update-property'),
                        "value": $this.val()
                    })
                });

                // Callback handler that will be called on success
                request.done(function (data, textStatus, jqXHR){
                    if (data.error == 0) {
                        // Log a message to the console
                        console.log("Successfully updated object!");
                        // Update data-original-value to the new value
                        $this.data('original-value', $this.val());
                    } else {
                        // An error has ocurred
                        console.log("Object update failed");
                        // Revert field value to original value
                        $this.val( $this.data('original-value') );
                    }
                });

                // Callback handler that will be called on failure
                request.fail(function (jqXHR, textStatus, errorThrown){
                    // Log the error to the console
                    console.error(
                        "The following error occurred: "+
                        textStatus, errorThrown
                    );
                    // Revert field value to original value
                    $this.val( $this.data('original-value') );
                });

                // Callback handler that will be called regardless if the request failed or succeeded
                request.always(function () {
                    // Re-enable the input
                    $this.parent().removeClass("loading");
                    $this.prop('disabled', false);
                });
            } else {
                // No changes in the input.. do nothing.
            }
        });

    $('.editable-field.money')
        .on('focus', function(){
            var $this = $(this);
            $this.val($this.data('original-value'));
        });
});