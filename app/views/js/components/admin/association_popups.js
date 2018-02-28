var messages = require('./../messages.js');

module.exports = function(){
    initializeFormAndTooltipster();
    associationBoxUI();
};

function initializeFormAndTooltipster(){

    var box = $('.permission-data');
    box.each(function(){
        var box = $(this);
        var association = box.data('association');
        box.data('request', {'association': association, 'id': box.data('id'), 'instructors':[], 'organizations':[]});
    });

    //check for loading event from profile and autoselect self
    var instructorIdObject = $('[data-current-instructor-id]');
    var organizationIdObject = $('[data-current-organization-id]');
    //on an instructor profile and loading a new event
    if(instructorIdObject.size() && box.data('id') == ''){
        loadSelectBox($('.permission-data.instructor-permissions .new-item'), instructorIdObject.data('current-instructor-id'));
    }
    else if(organizationIdObject.size() && box.data('id') == ''){
        loadSelectBox($('.permission-data.organization-permissions .new-item'), organizationIdObject.data('current-organization-id'));
    }
    
    box.closest('form').submit(function(e){
       box.each(function(){
           $(this).find('.association-input').val(JSON.stringify($(this).data('request')));
       });
    });

    $(".associations-popup").tooltipster({

        theme: 'tooltipster-basic',
        trigger: 'click',
        interactive: true,
        contentCloning: false,
        content: $('<div class="padding-15 loading"></div>'),
        functionBefore: function(origin, continueTooltip) {
            continueTooltip();
            origin.addClass('selected');

            if (origin.data('ajax') !== 'cached') {
                var id = origin.closest('[data-id]').data('id');
                var association = origin.data('association');
                $.post('/ajax/get-associations',{'id': id, 'association': association},function(res) {
                    var box = $(res);
                    box.data('origin', origin);
                    box.data('request', {'association': association, 'id': id, 'instructors':[], 'organizations':[]});
                    origin.tooltipster('content', box).data('ajax', 'cached');
                    $('.permission-data select').select2();
                }).fail(function(res){
                    origin.tooltipster('hide');
                   console.log(res);
                });
            }
        },
        functionAfter: function(origin){
            origin.removeClass('selected');
        }
    });

}

function associationBoxUI(){
    //switch handler
    $("body").on("mouseup",".permission-data .switch", function(){
        var request = $(this).closest('.permission-data').data('request');
        request.active = $(this).hasClass('on');

        console.log($(this).closest('.permission-data').data('request'));
    });

    //approve item handler
    $("body").on("mouseup",".permission-data .approve", function(){
        //update UI
        $(this).toggleClass('selected');
        $(this).closest('tr').find('.pending-overlay').toggleClass('hidden');

        var itemId = $(this).closest("[data-item-id]").data('itemId');
        var request = $(this).closest('.permission-data').data('request');
        var isApproved = $(this).hasClass('selected');

        updateDataStructure(request, null, isApproved, itemId);

        console.log($(this).closest('.permission-data').data('request'));
    });

    //delete item handler
    $("body").on('mouseup', '.permission-data .delete', function(){
        var box = $(this).closest('.permission-data');
        var item = $(this).closest("[data-item-id]");
        var itemId = item.data('itemId');
        var itemName = item.data('name');
        var request = box.data('request');
        var action = 'remove';
        var itemImageLink = item.data('link');
        var admin = box.data('admin');
        updateDataStructure(request, action, null, itemId);

        console.log(box.data('request'));

        var itemData = {'id':itemId, 'name': itemName, 'link': itemImageLink}

        //add option to select list
        if (!admin){
            //decide which optgroup to put it in
            if(item.find('.approve.selected').size()){
                addToSelect({'optionItems': [itemData], 'others': []}, box.find('select'));
            }
            else{
                addToSelect({'optionItems': [],'others':[itemData]}, box.find('select'));
            }
        }
        else{
            addToSelect({'optionItems': [itemData]}, box.find('select'));
        }



        //updateUI
        $(this).closest('tr').remove();
    });

    //new item button handler
    $('body').on('mouseup', '.permission-data .new-item', function(){
        loadSelectBox($(this), null);
    });

    //new item selection handler
    $('body').on('change','.permission-data select', function(){
        generateNewItem($(this));
    });

    //admin option handler
    $("body").on("mouseup",".permission-data .update",function(){
        var submitButton = $(this);
        var dataBox = $(this).closest('.permission-data');
        var association = dataBox.data('association');
        var userId = dataBox.data('userId');
        var origin = dataBox.data('origin');

        //SITE ADMIN PERMISSIONS
        if(association == ADMIN){
            //update selection
            $(this).parent().children().removeClass('selected');
            $(this).addClass('selected');

            var adminStatus = $(this).data('status');
            $.post('/ajax/update-admin-status', {'userId':userId, 'status':adminStatus}, function(res){
                //update colors of button
                origin.removeClass('pending').removeClass('approved').addClass(adminStatus.toLowerCase());
                //display message
                messages.displayNotificationMessage(res.status, res.data.message);
                //hide window
                origin.tooltipster('hide');
            }, 'json').fail(function(response){
                console.log(response);
                messages.displayNotificationMessage('fail', 'There was an error updating permissions');
                origin.tooltipster('hide');
                origin.tooltipster('show');
            });
        }
        //INSTRUCTOR & ORGANIZATION ADMIN PERMISSION
        else{
            submitButton.addClass('loading');
            var request = dataBox.data('request');
            $.post('/ajax/update-associations', JSON.stringify(request), function(res){
                submitButton.removeClass('loading');
                //update colors of button
                if(request.active === false){ origin.removeClass('active'); }
                if(request.active === true){ origin.addClass('active'); }
                //display message
                messages.displayNotificationMessage(res.status, res.data.message);
                //hide window
                origin.tooltipster('hide');
                //clear request data
                delete request.action;
                request.organizations = [];
                request.instructors = [];
            }, 'json').fail(function(response){
                console.log(response);
                submitButton.removeClass('loading');
                messages.displayNotificationMessage('fail', 'There was an error updating permissions');
                origin.tooltipster('hide');
                origin.tooltipster('show');
            });
        }
    });
}

function loadSelectBox(btn, selectId){
    var dataBox = btn.closest(".permission-data");
    var select = btn.siblings('select');

    //load items
    if(!dataBox.data('list-cached')){
        btn.text('loading...');

        //get item list
        $.post('/ajax/get-selection-list', {'association': dataBox.data('association'), 'id': dataBox.data('id')},function(res){
            btn.html('<i class="icon-plus"></i> add new');

            addToSelect(res.data, select);

            //if an item is being inserted programatically, select an item right away.
            if(selectId){
                select.find('option[value="'+selectId+'"]').prop('selected', true);
                generateNewItem(select);
            }

            select.removeClass('hidden');
            dataBox.data('list-cached', true);

        },'json').fail(function(response){
            console.log(response);
            messages.displayNotificationMessage('fail', 'There was an error getting list');
            btn.html('<i class="icon-plus"></i> add new');
        });

    }
    else{
        //cached
        select.removeClass('hidden');
    }
}

function addToSelect(data, select){
    var items = [];
    $.each(data.optionItems, function(i, obj){
        items.push('<option value="'+ obj.id +'" data-link="'+ obj.link +'">'+ obj.name +'</option>');
    });

    //get non associated items
    if(data.hasOwnProperty('others')){
        //make groups
        var myGroup = select.find('optgroup[data-managed]');
        var otherGroup = select.find('optgroup:not([data-managed])');

        //load groups
        myGroup.append(items);
        $.each(data.others, function(i, obj){
            otherGroup.append('<option value="'+ obj.id +'" data-link="'+ obj.link +'">'+ obj.name +'</option>');
        });
    }
    else{
        //append to select
        select.append(items);

    }
    //sort
    var $options = select.find('option');
    $options.sort(function(a, b) {
        if(a.value == -1 ) return 1;
        if(b.value == -1) return -1;
        return $(b).text().localeCompare($(a).text());
    });
    $options.each(function(){
        $(this).parent().prepend($(this));
    });
    select.trigger('change.select2');
}

function generateNewItem(selectBox){
    var itemId = selectBox.val();
    var itemName = selectBox.find('option:selected').text();
    var link = selectBox.find('option:selected').data('link') || "";
    var request = selectBox.closest('.permission-data').data('request');
    var admin = selectBox.closest('.permission-data').data('admin');

    //define new item in box
    var linkStyle = (link == '') ? '' : "background-image: url('"+ link +"')";
    var newItem =
        $('<tr data-item-id="'+itemId+'" data-link="'+link+'" data-name="'+itemName+'" class="clear border-0">\
                <td><div class="img-container small position-relative" style="'+linkStyle+'"> <div class="pending-overlay small icon-dot-3"></div></div> </td>\
                <td class="item-name">'+ itemName +'</td>\
                <td style="width:66px;" class="right-text"><i title="approve" class="little-circle approve icon-check"></i>\
                <i class="little-circle delete icon-cancel"></i></td>\
            </tr>');

    //for admins and users who already manage item, default to approved
    var approved;
    if(admin || selectBox.find('option:selected').parent().data('managed') === true){
        newItem.data('managed', true);
        newItem.find('.pending-overlay').addClass('hidden');
        newItem.find(".approve").addClass('selected');
        approved = true;
    }
    else{
        newItem.data('managed', false);
        approved = false;
    }

    //add to table
    selectBox.closest('.permission-data').find('table').append(newItem);

    //update data list
    updateDataStructure(request, 'add', approved, itemId);

    //remove this option from list
    selectBox.find('option:selected').remove();

    //hide the select
    selectBox.addClass('hidden').prop('selectedIndex',0);

    console.log(request);
    selectBox.trigger('change.select2');
}

function updateDataStructure(request, action, isApproved, itemId){
    var itemList = null;
    if (request.association === USER_MANAGES_INSTRUCTOR || request.association === ORGANIZATION_HAS_INSTRUCTOR || request.association === INSTRUCTOR_TEACHES_EVENT) itemList = request.instructors;
    else if(request.association === USER_MANAGES_ORGANIZATION || request.association === INSTRUCTOR_TEACHES_FOR_ORGANIZATION || request.association === ORGANIZATION_HOSTS_EVENT) itemList = request.organizations;

    var result = $.grep(itemList, function(e){return e.id == itemId});
    //instructor already in array
    if(result.length === 1){
        if (action !== null) {
            if (result[0].action === 'add' && action === 'remove') {
                //don't need to include item that was just going to be added, but now removed
                var indexes = $.map(itemList, function (obj, index) {
                    if (obj.id == itemId) {
                        return index;
                    }
                });
                itemList.splice(indexes[0], 1);
            }
            else if(result[0].action === 'remove' && action ==='add') {
                //remove action on item that is readded
                delete result[0].action;
            }
            else {
                //otherwise just set the action
                result[0].action = action;
            }
        }

        if(isApproved !== null){
            result[0].approved = isApproved;
        }

    }
    //instructor not in array yet
    else{
        var item = {'id':itemId};
        if(isApproved !== null){ item.approved = isApproved; }
        if(action !==null){ item.action = action; }
        itemList.push(item);
    }
}