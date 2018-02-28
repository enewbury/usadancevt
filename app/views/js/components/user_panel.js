var messages = require('./messages.js');
module.exports = function(){
    var accountPanel = $("#account-panel");

    accountMenu(accountPanel);
    openAccountForms(accountPanel);
    accountUpdatesAjax(accountPanel);
    deleteAccount(accountPanel);
};

function accountMenu(accountPanel){
    //slide open side panel
    $('#account-panel-button').click(function(){
        $(this).toggleClass("panel-open");
        accountPanel.toggleClass('panel-open');
    });

    //show hide account nav
    $("#account-nav-button").click(function(){
        $(this).closest('ul').toggleClass('closed');
        var isClosed = Cookies.get('accountNavClosed') || 'true';
        isClosed = (isClosed === 'true')?'false':'true';
        Cookies.set('accountNavClosed', isClosed, { expires: 7 });
    });

    $("#notifications-button, .close-requests").click(function(){
       $("#requests-list").toggleClass("open");
    });

    //respond to requests
    $("body").on('mouseup', "#requests-list .approve, #requests-list .deny", function(){
        var button = $(this);
        var permissionAction = (button.hasClass('approve')) ? 'APPROVE' : 'DENY';
        var requestId = button.closest('tr').data('requestId');

        $.post('/ajax/request-response', {'permissionAction': permissionAction, 'requestId': requestId}, function(res){
            messages.displayNotificationMessage(res.status, res.data.message);
            if(permissionAction === 'APPROVE' && res.status === 'success'){
                button.parent().html('<i class="icon-check center-text bigger"></i>');
            }
            else if(permissionAction === 'DENY' && res.status === 'success'){
                button.parent().html('<i class="icon-cancel center-text bigger"></i>');
            }
        }, 'json').fail(function(res){
            console.log(res);
        });
    });
}



function openAccountForms(accountPanel){
    var buttons = accountPanel.find(".form-dropdown");
    buttons.each(function(){
        $(this).data('val',$(this).text());
    });
    accountPanel.find(".form-dropdown").click(function(){
        var textVal = $(this).data('val');
        if($(this).text() == textVal){
            $(this).text('Close');
        }
        else{
            $(this).text(textVal);
        }
        $(this).parent().siblings('form').slideToggle();
    });
}

function accountUpdatesAjax(accountPanel){
    var validationBox = accountPanel.find('.ajax-validation');
    accountPanel.find('form').submit(function(e){
        e.preventDefault();
        var form = $(this);
        form.find('button').addClass('selected');
        var valueBox = form.parent().find('.value');
        $.post('/ajax/account', form.serialize(), function(res){
            validationBox.show();
            if(res.status && res.status === 'success') {
                //set message box text and fade out
                validationBox.text(res.data.message).addClass('success-box');
                setTimeout(function () {
                    validationBox.fadeOut();
                }, 2000);

                //update panel UI with changes
                var value = "";
                var inputs = form.find('input');
                inputs.each(function (i, el) {
                    if (i != inputs.length - 1) {
                        value += $(this).val() + " ";
                    } else {
                        value += $(this).val();
                    }
                });
                valueBox.text(value);

                //update name
                if (inputs.first().attr('name') == 'first') {
                    $('#account-panel-button').children('span').text(value);
                }

                //clear fields
                form[0].reset();

            } else {
                validationBox.text(res.data.message || res.errorMessage).addClass('error-box');
            }

        },'json').fail(function(res){
            console.log(res);
            messages.displayNotificationMessage('fail', 'Error updating account information');
        }).always(function(){
            form.find('button').removeClass('selected');
        });

    });
}

function deleteAccount(accountPanel){
    var deleteButton = accountPanel.find('.delete-account');
    deleteButton.click(function(e){
        e.preventDefault();
        messages.displayPopup('Are you sure your want to delete your account?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'red', callback: function(){
                messages.closePopup();
                window.location = deleteButton.attr('href');
            }}
        ]);
    });

}