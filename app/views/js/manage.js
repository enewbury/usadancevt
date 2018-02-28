//switch on offs
$('body').on('mouseup','.switch',function(){
    $(this).toggleClass("on");
    var checkBoxes = $(this).children('input');
    checkBoxes.prop("checked", !checkBoxes.prop("checked"));
});

window.ADMIN = "ADMIN";
window.USER_MANAGES_INSTRUCTOR = "USER_MANAGES_INSTRUCTOR";
window.USER_MANAGES_ORGANIZATION = "USER_MANAGES_ORGANIZATION";
window.INSTRUCTOR_TEACHES_FOR_ORGANIZATION = "INSTRUCTOR_TEACHES_FOR_ORGANIZATION";
window.ORGANIZATION_HAS_INSTRUCTOR = "ORGANIZATION_HAS_INSTRUCTOR";
window.INSTRUCTOR_TEACHES_EVENT = "INSTRUCTOR_TEACHES_EVENT";
window.ORGANIZATION_HOSTS_EVENT = "ORGANIZATION_HOSTS_EVENT";

var base = require('./components/base.js');
var userPanel = require('./components/user_panel.js');
var associationPopups = require('./components/admin/association_popups.js');
var accountsPage = require('./components/admin/accounts_page.js');
var pages = require('./components/admin/pages.js');
var emailTool = require('./components/admin/email_tool.js');
var organizationsPage = require('./components/organizations.js');
var instructorsPage = require('./components/instructors.js');
var eventsPage = require('./components/events.js');

base();
userPanel();
associationPopups();
accountsPage();
pages();
emailTool();
organizationsPage();
instructorsPage();
eventsPage();
initTinyMCE();

var ajax = require('./components/ajax.js');

$(".change-image").click(function(){
    var imgContainer = $(this).closest(".photo-picker-box").find('.img-container');
    ajax.uploadImage(
        function(){ imgContainer.addClass('loading')},
        null,
        function(){
            imgContainer.removeClass('loading');
            var res = JSON.parse(this.responseText);
            var responseBox = $(".response-box");

            if(res.status == 'success'){
                imgContainer.css('background-image',"url('"+res.data.imageUrl+"')");
                imgContainer.children('input[type="hidden"]:not(.thumb)').val(res.data.imageUrl);
                imgContainer.children('.thumb').val(res.data.thumbUrl);
            }
            else{
                responseBox.append(res.data.message);
                if(res.data.messages){
                    responseBox.append("<ul></ul>");
                    var ul = responseBox.children('ul');
                    $.each(res.data.messages, function(i, message){
                        ul.append('<li>'+ message +'</li>');
                    });
                }
                responseBox.addClass('error-box');
            }
        }
    );
});


$('.phone-input').formatter({
    'pattern': '{{999}}.{{999}}.{{9999}}'
}).resetPattern();

$(".time-input").timeEntry({
    spinnerImage: '',
    noSeparatorEntry: true
});

$('.top-slider').click(function(e){
    e.preventDefault();
});

$('.event-entity, .tile').on("touchend", function(e){
   var slider = $(this).find(".top-slider");
    if(!slider.hasClass("open")){
        e.preventDefault();
        slider.addClass("open");
    }
});


function initTinyMCE(){
    window.tinymce.init({
        height: 300,
        relative_urls: false,
        convert_urls: false,
        content_css: '/editor_style.css',
        selector: "textarea.editor",
        plugins: 'autolink link code textcolor fullscreen table image preview wordcount hr',
        toolbar: 'formatselect | bold italic underline forecolor | alignleft aligncenter alignright | link | bullist numlist | image fullscreen',
        image_caption: true,
        file_browser_callback: function(fieldId, initialValue, browserType, win){
            if(browserType == 'image') {
                tinyMCEUploadImage($(win.document.getElementById(fieldId)));
            }
        }
    });
}

function tinyMCEUploadImage(mceInput){
    //handle to mce dialog box
    var dialog = mceInput.closest('.mce-window-body').children('.mce-container');

    //add box where things will go
    if($(".hack-box")){$(".hack-box").remove();}
    var hackBox = $('<div class="hack-box"></div>');
    dialog.append(hackBox);

    function mceSuccessCallback(){
        //remove loading bar
        dialog.find('.loading-bar').remove();
        var errorBox;
        try{
            var response=JSON.parse(this.responseText);
            console.log(response);
        }
        catch(error){
            errorBox = $('<div class="error-box">Upload Failed</div>');
            hackBox.append(errorBox);
            updateHeight();
            return;
        }

        if(response.status != 'success'){
            errorBox = $('<div class="error-box"><ul></ul></div>');
            if(response.data.message){
                errorBox.children().first().append("<li>"+response.data.message+"</li>");
            }
            if(response.data.messages && response.data.messages.length > 0) {
                $.each(response.data.messages, function (i, val) {
                    errorBox.children().first().append("<li>" + val + "</li>");
                });
            }
            hackBox.append(errorBox);
            updateHeight();
        }
        else{
            //success
            var successBox = $('<div class="success-box">Uploaded Successfully</div>');
            hackBox.append(successBox);
            updateHeight();

            var absoluteUrls = $("#"+tinymce.activeEditor.id).data("absoluteUrls");
            var host = (absoluteUrls) ? window.location.protocol + "//" + window.location.hostname : '';
            mceInput.val(host + response.data.imageUrl);
        }

    }

    function mceProgressCallback(upload){
        if(dialog.find('.loading-bar').length == 0) {
            var loadingBarOuter = $('<div class="loading-bar"><div class="loading-bar-done"></div></div>');
            var loadingBarInner = loadingBarOuter.children().first();
            hackBox.append(loadingBarOuter);
            updateHeight(dialog, loadingBarOuter);
        }

        if (upload.lengthComputable) {
            loadingBarInner.css('width', Math.round(upload.loaded / upload.total * 100) + "%");
            if(upload.loaded==upload.total){
                loadingBarInner.text("Processing...");
            }
        }
    }

    function updateHeight(){
        var height;
        if(!dialog.data('height')){
            height = dialog.height();
            dialog.data('height', height);
        }else{
            height = dialog.data('height');
        }

        dialog.height(height+hackBox.outerHeight());
        dialog.parent().height(height+hackBox.outerHeight());
    }

    ajax.uploadImage(null, mceProgressCallback, mceSuccessCallback);
}
