module.exports = {
    displayNotificationMessage:function (status, message)
    {
        var messageBox = $(this.notificationHtml(status));
        messageBox.text(message).hide();
        this.getMessageBoxContainer().append(messageBox);
        messageBox.fadeIn();
        var exports = this;
        setTimeout(function () {
            exports.fadeOutSlideUpRemove(messageBox)
        }, 3000);
    },

    displayClosableMessage: function (status, message) {

    },

    displayPopup: function(message, buttons){
        var dialog = $(this.popupHtml(message));
        $('body').append(dialog);
        var messages = this;
        $.each(buttons, function(i, buttonObj){
            var btn = $(messages.buttonHtml(buttonObj, (i == 0)));
            dialog.find('.btn-container').append(btn);
            btn.click(function(){
                buttonObj.callback();
            });
        });
    },
    closePopup: function(){
        $('.popup-dialog').fadeOut().remove();
    },

    notificationHtml: function (status) {
        return '<div class="message-box ' + status + '"></div>';
    },

    popupHtml: function(message){
        return '<div class="popup-dialog"><p>'+ message +'</p><div class="btn-container"></div></div>';
    },

    buttonHtml: function(button, focus){
        var autofocus = (focus)?'autofocus':'';
        return '<button value="'+button.value+'" class="btn-third dialog-btn '+button.color+'" '+autofocus+'>'+button.text+'</button>';
    },

    getMessageBoxContainer: function () {
        return $("#message-box-container");
    },

    fadeOutSlideUpRemove: function (object) {
        object.animate({opacity: 0}, 500, function () {
            $(this).slideUp(500, function () {
                $(this).remove();
            })
        })
    }
};