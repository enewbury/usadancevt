var messages = require('./../messages.js');

module.exports = function(){
    autoFillName();
    activateDeactivateUser();
    deleteUser();
    newUser();
};


function autoFillName(){
    $('input[name="first"]').blur(function(){
        if(!$(this).data("dirty")) {
            $(this).data("dirty", true);
            var messageTextArea = $(this).siblings(".editor");
            var message = messageTextArea.text().replace("{name}", $(this).val());
            messageTextArea.html(message);
            tinymce.activeEditor.setContent(message);
        }
    });
}

function activateDeactivateUser(){

    $("#accounts").on("click",".activate-switch", function(){
        var active = ($(this).hasClass('on'));
        var id = $(this).closest('[data-id]').data('id');
        $.post('/ajax/user-activation',{'active': active, 'id': id}, function(res){
            messages.displayNotificationMessage(res.status, res.data.message);
        },'json');
    });

}

function deleteUser(){
    $("#manage-body").on("click",".delete-user", function() {
        var btn = $(this).addClass('clicked');;
        var row = $(this).closest('[data-id]');
        var id = row.data('id');
        messages.displayPopup('Are you sure your want to delete this user?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
                btn.removeClass('clicked');
            }},
            {
                text: 'Yes',
                value: false,
                color: 'blue',
                callback: function () {
                    $.post('/ajax/delete-user', {'id': id}, function (res) {
                        messages.displayNotificationMessage(res.status, res.data.message);
                        messages.fadeOutSlideUpRemove(row);
                        messages.closePopup();
                    }, 'json').fail(function(res){
                        console.log(res);
                        messages.displayNotificationMessage('fail', 'There was an error deleting user');
                    });
                }
            }
        ]);
    });
}

function newUser(){
    $('#new-user-button').click(function(){
       $("#new-user-form").slideToggle();
        if($(this).data('text') == null){
            $(this).data('text', $(this).find('b').text());
        }

        if($(this).find('b').text() == $(this).data('text')){
            $(this).find("b").text('Close');
        }
        else{
            $(this).find("b").text($(this).data('text'));
        }
    });
}
