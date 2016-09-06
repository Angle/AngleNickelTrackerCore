Number.prototype.formatNumber = function(c, d, t){
    var n = this,
        c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

Number.prototype.formatMoney = function(c, d, t){
    var n = this,
        c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;
    return s + '$' + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

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
                    // If the field was formatted as money, fix its formatting
                    if ($this.hasClass('money')) {
                        fixMoneyFormat($this)
                    }
                });
            } else {
                // No changes in the input.. do nothing ajaxy.
                // If the field was formatted as money, fix its formatting
                if ($this.hasClass('money')) {
                    fixMoneyFormat($this)
                }
            }
        });

    $('.editable-field.money')
        .on('focus', function(){
            var $this = $(this);
            $this.val($this.data('original-value'));
        });

    $('#logout').click(function(evt) {
        evt.preventDefault();
        evt.stopPropagation();

        var r = confirm("Are you sure you want to logout?");
        if (r == true) {
            window.location = '/logout';
        }
    });
});

function fixMoneyFormat(editableElement) {
    editableElement.val(parseFloat(editableElement.val()).formatMoney(2));
}