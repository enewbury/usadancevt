var messages = require('./messages.js');
module.exports = function(){
    $("#instructors").on('click', '.activate-instructor', function(e){
        e.preventDefault();
        //get id
        var instructorId = $(this).closest('.tile').data('id');
        var isOn = $(this).hasClass('on');
        $.post('/ajax/instructor-activation', {'instructorId': instructorId, 'isOn':isOn }, function(res){
            messages.displayNotificationMessage(res.status, res.data.message);
        }, 'json').fail(function(response){
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error updating activation');
        });
    });
    $("#instructors").on('click',".delete-instructor", function(e){
        e.preventDefault();
        var btn = $(this);
        messages.displayPopup('Are you sure your want to delete this instructor?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                var container = btn.closest('.tile');
                var instructorId = container.data('id');
                $.post('/ajax/delete-instructor', {'instructorId': instructorId} , function(res){
                    messages.displayNotificationMessage(res.status, res.data.message);
                    if(res.status == 'success'){

                        container.closest('a').fadeOut(300,function(){
                            container.remove();
                        })
                    }
                },'json').fail(function(response){
                    console.log(response);
                    messages.displayNotificationMessage('fail', 'There was an error deleting');
                });
            }}
        ]);
    });

    $('#instructors').on('click', '.remove-access', function(){
        var container = $("#instructors");
        var tile = $(this).closest('.tile');
        var name = tile.attr('title');
        var instructorId = tile.data('id');
        messages.displayPopup('Are you sure your want to remove your access to this instructor?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                $.post('/ajax/remove-instructor-access', {'instructorId': instructorId}, function(res){
                    messages.displayNotificationMessage(res.status, res.data.message);

                    //remove and put back in selector
                    if(res.status === 'success') {
                        tile.fadeOut(300, function () {
                            $(this).remove();
                        });
                        container.find('form select').append('<option value="' + instructorId + '">' + name + '</option>');
                    }
                },'json').fail(function(res){
                    console.log(res)
                    messages.displayNotificationMessage('fail', 'There was an error removing access');
                });
            }}
        ]);
    });

    $('#instructor-organizations').on('click', '.remove-instructor-organization-access', function(){
        var btn = $(this);
        var instructorId = $("#instructor-organizations").data('instructorId');
        var organizationId = $(this).closest('[data-id]').data('id');
        messages.displayPopup('Are you sure your want to remove your association with this organization?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                $.post('/ajax/remove-instructor-organization-access', {'instructorId': instructorId, 'organizationId': organizationId}, function(res){
                    messages.displayNotificationMessage(res.status, res.data.message);

                    //remove and put back in selector
                    var tile = btn.closest('.tile');
                    var id = tile.data('organizationId');
                    var name = tile.attr('title');
                    tile.fadeOut(300, function(){$(this).remove();});
                    $('#instructor-organizations').find('form select').append('<option value="'+ id +'">'+ name + '</option>');

                },'json').fail(function(response){
                    console.log(response);
                    messages.displayNotificationMessage('fail', 'There was an error');
                });;
            }}
        ]);
    });
}