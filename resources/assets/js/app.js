
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

$(document).ready(function() {
    $('tr').click(function() {
        var translationCell = $(this).find('td#translation');
        var id = $(this).attr('id');
        var lang = $(this).parents('table').attr('id');
        var form = '<textarea>' + translationCell.html() + '</textarea><input type="button" class="send" value="Save">';
        translationCell.html(form);
        $(this).unbind('click');
        $(translationCell).find('input').click(function() { alert('send'); });
        $(translationCell).find('textarea').prop('disabled', true);
    });
});
