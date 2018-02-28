var messages = require('./messages.js');
module.exports = function(){
    
    //submit form checks
    $('#edit-event-form').submit(function(e){
        e.preventDefault();
        var form = $(this);
        form.find('button[type="submit"]').removeClass('loading');

        //run all the check functions
        submissionCheckFunctions[0](form, 1)

    });

    var submissionCheckFunctions = [
        //CHECK NOT DELETING SELF
        function(form, nextIndex){
            var instructorIdObject = $('[data-current-instructor-id]');
            var organizationIdObject = $('[data-current-organization-id]');
            if(instructorIdObject.size()){
                var instructors = $('.permission-data.instructor-permissions').data('request').instructors;
                for(var i = 0; i < instructors.length; i++){
                    //if user was trying to delete self, warn
                    if(instructors[i].id == instructorIdObject.data('current-instructor-id') && instructors[i].action == 'remove'){
                        messages.displayPopup('Do you really want to remove yourself as an instructor for this event? You will not be able to edit it anymore.',[
                            {text:'No', value:true, color:'grey', callback: function(){
                                messages.closePopup();
                            }},
                            {text:'Yes', value:true, color:'blue', callback: function(){
                                messages.closePopup();
                                submissionCheckFunctions[nextIndex](form, nextIndex+1);
                            }}
                        ]);
                        //return if found so function doesn't automatically go to next function
                        return;
                    }
                }
            }
            else if(organizationIdObject.size()){
                var organizations = $('.permission-data.organization-permissions').data('request').organizations;
                for(var i = 0; i < organizations.length; i++){
                    //if user was trying to delete self, warn
                    if(organizations[i].id == organizationIdObject.data('current-instructor-id') && organizations[i].action == 'remove'){
                        messages.displayPopup('Do you really want to remove yourself as a host for this event? You will not be able to edit it anymore.',[
                            {text:'No', value:true, color:'grey', callback: function(){
                                messages.closePopup();
                            }},
                            {text:'Yes', value:true, color:'blue', callback: function(){
                                messages.closePopup();
                                submissionCheckFunctions[nextIndex](form, nextIndex+1);
                            }}
                        ]);
                        //return if found so function doesn't automatically go to next function
                        return;
                    }
                }
            }
            submissionCheckFunctions[nextIndex](form, nextIndex+1);
        },
        //CHECK REPEATING EVENT OPTIONS
        function(form, nextIndex){
            var eventTimestamp = getPathVariableFromWindow('date');
            //is repeating
            if(eventTimestamp != false){
                //ask about repeating event
                messages.displayPopup('Do you want to change only this occurrence of the event, or this and all future occurrences?',[
                    {text:'Cancel', value:true, color:'grey', callback: function(){
                        messages.closePopup();
                    }},
                    {text:'Every Occurrence', value:true, color:'blue', callback: function(){
                        messages.closePopup();
                        form.find('#repeat-selection-input').val('past');
                        submissionCheckFunctions[nextIndex](form, nextIndex+1);
                    }},
                    {text:'Just This Event', value:true, color:'blue', callback: function(){
                        messages.closePopup();
                        form.find('#repeat-selection-input').val('this');
                        submissionCheckFunctions[nextIndex](form, nextIndex+1);
                    }},
                    {text:'All Future Events', value:true, color:'blue', callback: function(){
                        messages.closePopup();
                        form.find('#repeat-selection-input').val('all');
                        submissionCheckFunctions[nextIndex](form, nextIndex+1);
                    }}
                ]);
            }
            //not repeating
            else{
                submissionCheckFunctions[nextIndex](form, nextIndex+1);
            }
        },
        //SUBMIT FORM
        function(form, nextIndex){
            //submit if we've gotten this far
            form.find('button[type="submit"]').addClass('loading');
            form[0].submit();
        }
    ];

    function submitActivationRequest(data){
        $.post('/ajax/event-activation', data, function(res){
            messages.displayNotificationMessage(res.status, res.data.message);
        }, 'json').fail(function(response){
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error updating activation');
        });
    }

    $("#events").on('click', '.activate-event', function(e){
        e.preventDefault();
        //get id
        var entity = $(this).closest('.entity');
        var eventId = entity.data('id');
        var isOn = $(this).hasClass('on');
        var eventTimestamp = getPathVariableFromLink('date', $(this).closest('.entity'));
        var data = {'eventId': eventId, 'isOn':isOn };
        if(eventTimestamp != false){
            data['instanceDate'] = eventTimestamp;
            //ask about repeating event
            messages.displayPopup('Do you want to change only this occurrence of the event, or this and all future occurrences?',[
                {text:'Cancel', value:true, color:'grey', callback: function(){
                    messages.closePopup();
                    $(this).toggleClass('on');
                }},
                {text:'Every Occurrence', value:true, color:'blue', callback: function(){
                    messages.closePopup();
                    data['repeatSelection'] = 'past';
                    submitActivationRequest(data);
                    entity.siblings('[data-id*="'+eventId+'"]').find('.activate-event').toggleClass('on');
                }},
                {text:'Just This Event', value:true, color:'blue', callback: function(){
                    messages.closePopup();
                    data['repeatSelection'] = 'this';
                    submitActivationRequest(data);
                }},
                {text:'All Future Events', value:true, color:'blue', callback: function(){
                    messages.closePopup();
                    data['repeatSelection'] = 'all';
                    submitActivationRequest(data);
                    entity.nextAll('[data-id*="'+eventId+'"]').find('.activate-event').toggleClass('on');
                }}
            ]);
        }
        else{
            submitActivationRequest(data);
        }
    });
    
    function doDeleteEventRequest(data, btn){
        var container = btn.closest('.entity');
        var eventId = container.data('id');
        $.post('/ajax/delete-event', data, function(res){
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
    }
    
    function checkRepeatingBeforeDelete(btn){
        var entity = btn.closest('.entity');
        var eventId = entity.data('id');
        var isOn = btn.hasClass('on');
        var eventTimestamp = getPathVariableFromLink('date', btn.closest('.entity'));
        var data = {'eventId': eventId, 'isOn':isOn };
        if(eventTimestamp != false){
            data['instanceDate'] = eventTimestamp;
            //ask about repeating event
            messages.displayPopup('Do you want to delete only this occurrence of the event, or this and all future occurrences?',[
                {text:'Cancel', value:true, color:'grey', callback: function(){
                    messages.closePopup();
                }},
                {text:'Just This Event', value:true, color:'blue', callback: function(){
                    messages.closePopup();
                    data['repeatSelection'] = 'this';
                    doDeleteEventRequest(data, btn);
                }},
                {text:'All Future Events', value:true, color:'blue', callback: function(){
                    messages.closePopup();
                    data['repeatSelection'] = 'all';
                    doDeleteEventRequest(data, btn);
                    entity.nextAll('[data-id*="'+eventId+'"]').fadeOut(300,function(){
                        container.remove();
                    });
                }}
            ]);
        }
        else{
            doDeleteEventRequest(data, btn);
        }
    }
    
    $("#events").on('click',".delete-event", function(e){
        e.preventDefault();
        var btn = $(this);
        var entity = btn.closest('.entity');
        messages.displayPopup('Are you sure your want to delete this event?', [
            {text:'No', value:true, color:'grey', callback: function(){
                messages.closePopup();
            }},
            {text: 'Yes', value: false, color: 'blue', callback: function(){
                messages.closePopup();
                checkRepeatingBeforeDelete(btn)
            }}
        ]);
    });
    
    $('input[name="allDay"]').change(function(){
        $("#date-time-box").find(".time-input-section").toggle();
    });
    $('input[name="repeating"]').change(function(){
        $("#repeating-box").slideToggle('fast');
    });

    $("#repeating-box").find("[data-day]").click(function(){
       $(this).toggleClass('selected');
        var days = "";
        var buttons = $(this).parent().children('[data-day]');
        buttons.each(function(){
            if($(this).hasClass('selected')){
                days+=$(this).data('day')+",";
            }
        });
        days = days.slice(0,-1);
        
        $(this).siblings('input[name="repeatDays"]').val(days);
    });

    function getPathVariableFromWindow(variable){
        return getPathVariable(variable, window.location.pathname);
    }
    function getPathVariableFromLink(variable, link){
        return getPathVariable(variable, link.attr('href'));
    }
    function getPathVariable(variable, path)
    {
        var pathParts = path.split("/");
        var index = pathParts.indexOf(variable);
        if(index !== -1 && index+1 <= pathParts.length -1 && pathParts[index+1] !== '') return pathParts[index+1];
        return false;
    }
};