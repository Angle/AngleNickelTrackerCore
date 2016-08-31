/** SOURCE: http://jsfiddle.net/umutc1/eyf9q87c/ **/
$(function () {
    $('.tree li:has(ul)').addClass('parent_li').find(' > span').attr('title', 'Collapse this branch');
    $('.tree li.parent_li > span').on('click', function (e) {
        var children = $(this).parent('li.parent_li').find(' > ul > li');
        if (children.is(":visible")) {
            children.hide('fast');
            $(this).attr('title', 'Expand this branch').find(' > i:first-child').addClass('fa-plus-square-o').removeClass('fa-minus-square-o');
        } else {
            children.show('fast');
            $(this).attr('title', 'Collapse this branch').find(' > i:first-child').addClass('fa-minus-square-o').removeClass('fa-plus-square-o');
        }
        e.stopPropagation();
    });
});