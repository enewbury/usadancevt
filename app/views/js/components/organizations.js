var messages = require('./messages.js');
module.exports = function(){
    $("#organizations").on('click', '.activate-organization', function(e){
        e.preventDefault();
        //get id
        var organizationId = $(this).closest('a').data('id');
        var isOn = $(this).hasClass('on');
        $.post('/ajax/organization-activation', {'organizationId': organizationId, 'isOn':isOn }, function(res){
            messages.displayNotificationMessage(res.status, res.data.message);
        }, 'json').fail(function(response){
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error updating activation');
        });;
    });
    $("#organizations").on('click',".delete-organization", function(e){
        e.preventDefault();
        var btn = $(this);
        messages.displayPopup('Are you sure your want to delete this organization?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                var container = btn.closest('a');
                var organizationId = container.data('id');
                $.post('/ajax/delete-organization', {'organizationId': organizationId} , function(res){
                    messages.displayNotificationMessage(res.status, res.data.message);
                    if(res.status == 'success'){

                        container.closest('a').fadeOut(300,function(){
                            container.remove();
                        })
                    }
                },'json').fail(function(response){
                    console.log(response);
                    messages.displayNotificationMessage('fail', 'There was an error deleting');
                });;
            }}
        ]);

    });

    $('#organizations').on('click', '.remove-access', function(){
        var container = $("#organizations");
        var tile = $(this).closest('.tile');
        var name = tile.attr('title');
        var organizationId = tile.data('id');
        messages.displayPopup('Are you sure your want to remove your access to this organization?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                $.post('/ajax/remove-organization-access', {'organizationId': organizationId}, function(res){
                    messages.displayNotificationMessage(res.status, res.data.message);

                    //remove and put back in selector
                    if(res.status === 'success') {
                        tile.fadeOut(300, function () {
                            $(this).remove();
                        });
                        container.find('form select').append('<option value="' + organizationId + '">' + name + '</option>');
                    }
                },'json').fail(function(response){
                    console.log(response);
                    messages.displayNotificationMessage('fail', 'There was an error removing access');
                });
            }}
        ]);
    });

    $('#organization-instructors').on('click', '.remove-instructor-organization-access', function(){
        var btn = $(this);
        var organizationId = $("#organization-instructors").data('organizationId');
        var instructorId = $(this).closest('[data-id]').data('id');
        messages.displayPopup('Are you sure your want to remove your association with this instructor?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                $.post('/ajax/remove-instructor-organization-access', {'instructorId': instructorId, 'organizationId': organizationId}, function(res){
                    messages.displayNotificationMessage(res.status, res.data.message);

                    //remove and put back in selector
                    var tile = btn.closest('.tile');
                    var id = tile.data('instructorId');
                    var name = tile.attr('title');
                    tile.fadeOut(300, function(){$(this).remove();});
                    $('#organization-instructors').find('form select').append('<option value="'+ id +'">'+ name + '</option>');

                },'json').fail(function(response){
                    console.log(response);
                    messages.displayNotificationMessage('fail', 'There was an error removing access');
                });;
            }}
        ]);
    });
}