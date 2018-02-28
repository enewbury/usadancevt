module.exports = function(){
    menuButton();
    loadingOnSubmit();
    showCreateNewPanel();
    currentlySelectedMenu();
    clickAway();

    $(document).ready(function(){
        $('.big-ol ol li').each(function(i){
           $(this).prepend('<span class="item">'+(i+1)+'</span>')
        });
        $('body').addClass('dom-loaded');
    });

    $(':not(.sortable-select-container) select').select2();
    // init sortable
    $(".sortable-select-container select").select2_sortable();

    $('.date-input').each(function() {
        new Pikaday({ field: $(this)[0], format: 'MM/DD/YYYY', theme: 'date-picker-custom' });
    });
};

function menuButton(){
    var menu = $('nav');
    var menuButton = $('#slab-button');
    menuButton.click(function(){
        menu.slideToggle();
    });
    $(document).on('click', function(event){
       if(!$(event.target).closest(menu).length && !$(event.target).closest(menuButton).length && $(window).width() < 767){
           menu.slideUp();
       }
    });
}

function loadingOnSubmit(){
    $("form").submit(function(e){
        var button = $(this).find('button[type="submit"]:not(.no-loading)').first();
        button.addClass("loading");
    });
}

function showCreateNewPanel(){
    $("form select").change(function(){
       if($(this).val() == "new"){
           $(this).siblings(".create-new").show();
       }
       else{
           $(this).siblings(".create-new").hide();
       }
    });
}

function currentlySelectedMenu(){
    $('a').each(function(){
        if (window.location.pathname.indexOf($(this).attr('href')) == 0){
            $(this).addClass("selected");
        }
    });
}

function clickAway(){
    $(document).click(function(e){
        if(!$(e.target).closest("#requests-list").length && !$(e.target).closest("#notifications-button").length){
            $("#requests-list").removeClass("open");
        }
    });
}








