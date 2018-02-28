var messages = require('./../messages.js');

module.exports = function() {

    //OPEN ADD OPTION MENU
    $('.email-content').on('click', '.new-below:not(.tooltipstered)', function() {
        $(this).tooltipster({
            theme: 'tooltipster-basic',
            trigger: 'click',
            interactive: true,
            contentCloning: false,
            content: $('<div class="section-type-picklist">' +
                '<a data-section-type="html"><i class="icon-doc-text-inv"></i> Content Area</a>' +
                '<a data-section-type="instructorProfiles"><i class="icon-user"></i> Instructor Profiles</a>' +
                '<a data-section-type="organizationProfiles"><i class="icon-commerical-building"></i> Organization Profiles</a>' +
                '<a data-section-type="eventList"><i class="icon-calendar"></i> Event List</a></div>'),
            functionBefore: function (origin, continueTooltip) {
                origin.tooltipster('content').data('origin', origin);
                continueTooltip();
            }
        });
        $(this).tooltipster('show');
    });

    //ADD A SECTION
    $('body').on('mouseup', '.section-type-picklist a', function(){
        var type = $(this).data('section-type');
        var origin = $(this).closest('.section-type-picklist').data('origin');
        var currentSection = origin.closest('.email-section');
        var newSection = $('<div class="email-section"></div>');

        origin.tooltipster('hide');
        //set the type
        newSection.data('type', type);
        //add to dom
        currentSection.after(newSection);

        if(type == 'html'){
            newSection.append($('#contentAreaHtml').html());
            newSection.find('textarea').addClass('editor').attr('id', Date.now());
            tinyMCE.execCommand('mceAddEditor', false, newSection.find('.editor').attr('id'));
        }
        else if(type == 'instructorProfiles'){
            newSection.append($('#instructorProfileHtml').html());
            var select = newSection.find('select');
            var instructors = JSON.parse($('#instructors').html());
            $.each(instructors, function(i, instructor){
                select.append('<option value="'+ instructor.id +'">'+ instructor.name +'</option>');
            });
            //set select2
            select.select2();
        }
        else if (type == 'organizationProfiles'){
            newSection.append($('#organizationProfileHtml').html());
            var select = newSection.find('select');
            var organizations = JSON.parse($('#organizations').html());
            $.each(organizations, function(i, organization){
                select.append('<option value="'+ organization.id +'">'+ organization.name +'</option>');
            });
            //set select2
            select.select2();
        }
        else{
            //add to dom
            newSection.append($('#eventListHtml').html());
            
            //load instructor dropdown
            var instructors = JSON.parse($('#instructors').html());
            $.each(instructors, function(i, instructor){
                newSection.find('select[name="instructor"]').append('<option value="'+ instructor.id +'">'+ instructor.name +'</option>');
            });
            //load organization dropdown
            var organizations = JSON.parse($('#organizations').html());
            $.each(organizations, function(i, organization){
                newSection.find('select[name="organization"]').append('<option value="'+ organization.id +'">'+ organization.name +'</option>');
            });
            
            //load category dropdown
            var categories = JSON.parse($('#categories').html());
            $.each(categories, function(i, category){
                newSection.find('select[name="category"]').append('<option value="'+ category.id +'">'+ category.value +'</option>');
            });

            //load location dropdown
            var counties = JSON.parse($('#counties').html());
            $.each(counties, function(i, county){
                newSection.find('select[name="county"]').append('<option value="'+ county +'">'+ county +'</option>');
            });

            //initialize select2
            newSection.find('select').select2();
            //initialize pickaday
            newSection.find('.date-input').each(function(){
                new Pikaday({ field: $(this)[0], format: 'MM/DD/YYYY', theme: 'date-picker-custom' });
            });

        }

        //scroll to new item
        $('html,body').animate({scrollTop: newSection.offset().top - 30}, 500);
    });

    //DELETE A SECTION
    $('.email-content').on('click', '.delete-email-section', function(){
        var thisSection = $(this).closest('.email-section');
        if(thisSection.data('type') == 'html'){
            tinyMCE.execCommand('mceRemoveEditor', false, thisSection.find('.editor').attr('id'));
        }
        thisSection.remove();
    });

    //ADD A PROFILE
    $('.email-content').on('change', '.profile-select', function(e){
        var section = $(this).closest('.email-section');
        var profileArea = section.find('.profile-area');
        var ids = [];
        $(this).find(':selected').each(function(i, selected){
            ids[i]=$(selected).val();
        });
        var type = $(this).closest('.email-section').data('type');

        profileArea.children().css('opacity',0);
        profileArea.animate({'min-height': 100}, 'fast', 'swing');
        $.post('/ajax/get-email-profiles', {type: type, ids: ids}, function(res){
            section.find('.profile-area').html(res);

        }).fail(function(response){
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error loading the template.');
        });
    });
    
    //LOAD EVENTS
    $('.email-content').on('click', '.load-events', function(e){
        e.preventDefault();
        var section = $(this).closest('.email-section');
        var listArea = section.find('.list-area');
        listArea.children().css('opacity', 0);
        listArea.animate({'min-height': 100}, 'fast', 'swing');


        $.post('/ajax/load-event-list', section.find('.filter-controls-horizontal :input').serialize(), function(res){
            listArea.html(res);
        }).fail(function(response){
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error loading the template.');
        });
    });

    
    //SUBMIT
    $('#email-tool').submit(function(e){
        var sectionObjectList = [];
        //save instances to text areas
        tinyMCE.triggerSave();
        
        $(this).find('.email-section').each(function(){
            var section = $(this);
            var type = section.data('type');
            var sectionObject = {type: type, content: null};

            if (type == 'html'){
                sectionObject.content = section.find('.editor').val();
            }
            else if (type == 'instructorProfiles' || type == 'organizationProfiles'){
                sectionObject.content = section.find('.profile-area').html();
            }
            else {
                sectionObject.content = section.find('.list-area').html();
            }
            
            //add to list of sections
            sectionObjectList.push(sectionObject);
        });
        
        $(this).find('input[name="sections"]').val(JSON.stringify(sectionObjectList));
    });
};