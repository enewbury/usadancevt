var messages = require('./../messages.js');

module.exports = function() {
    $("#pages").on('click', '.activate-page', function (e) {
        e.preventDefault();
        //get id
        var pageId = $(this).closest('.tile').data('id');
        var isOn = $(this).hasClass('on');
        $.post('/ajax/page-activation', {'pageId': pageId, 'isOn': isOn}, function (res) {
            messages.displayNotificationMessage(res.status, res.data.message);
        }, 'json').fail(function (response) {
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error updating activation');
        });
    });
    $("#pages").on('click', ".delete-page", function (e) {
        e.preventDefault();
        var btn = $(this);
        messages.displayPopup('Are you sure your want to delete this page?', [
            {
                text: 'No', value: true, color: 'grey', callback: function () {
                messages.closePopup();
            }
            },
            {
                text: 'Yes', value: false, color: 'blue', callback: function () {
                messages.closePopup();
                var container = btn.closest('.tile');
                var pageId = container.data('id');
                $.post('/ajax/delete-page', {'pageId': pageId}, function (res) {
                    messages.displayNotificationMessage(res.status, res.data.message);
                    if (res.status == 'success') {

                        container.closest('a').fadeOut(300, function () {
                            container.remove();
                        })
                    }
                }, 'json').fail(function (response) {
                    console.log(response);
                    messages.displayNotificationMessage('fail', 'There was an error deleting');
                });
            }
            }
        ]);
    });
}