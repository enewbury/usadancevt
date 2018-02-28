var base = require('./components/base.js');
var userPanel = require('./components/user_panel.js');
base();
userPanel();

function formatAMPM(date) {
    var hours = date.getHours();
    var minutes = date.getMinutes();
    var ampm = hours >= 12 ? 'pm' : 'am';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    minutes = minutes < 10 ? '0'+minutes : minutes;
    return hours + ':' + minutes + ' ' + ampm;
}

function loadEventsClusterMap(){
    var events = JSON.parse(document.getElementById("eventsJson").innerHTML);
    var map = new google.maps.Map(document.getElementById('event-map'), {
        center: {lat: 44.260, lng: -72.575},
        zoom: 8,
        scrollwheel: false
    });


    // Reposition
    if(Cookies.get('locationFound')){
        map.setCenter(JSON.parse(Cookies.get('locationFound')));
        map.setZoom(11);
    }
    else if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var pos = {lat: position.coords.latitude, lng: position.coords.longitude};
                Cookies.set('locationFound', JSON.stringify(pos), {expires: 7});
                map.setCenter(pos);
                map.setZoom(11);
            }
        );
    }

    //place markers and set popups
    var infowindow = new google.maps.InfoWindow();
    var marker, i;
    var groups = groupEventsByLocation(events);
    for (var coordinates in groups) {
        if(groups.hasOwnProperty(coordinates) && coordinates != null && coordinates != "") {
            marker = new google.maps.Marker({
                map: map,
                position: JSON.parse(coordinates)
            });

            google.maps.event.addListener(marker, 'click', (function (marker, groupedEvents) {
                return function () {
                    infowindow.setContent(generateEventsHtml(groupedEvents));
                    infowindow.open(map, marker);
                }
            })(marker, groups[coordinates]));
        }
    }
}

function groupEventsByLocation(events){
    var groups={};
    for(var i=0; i< events.length; i++){
        (groups[events[i].coordinates] = groups[events[i].coordinates] || []).push(events[i]);
    }
    return groups;
}

function generateEventsHtml(groupedEvents){
    var html ='<div class="maps-mini-container">';
    for(var i=0;i < groupedEvents.length; i++) {
        var thisEvent = groupedEvents[i];
        var eventTemplate = $($("#mapEventHtml").text());
        if(groupedEvents[i].thumbLink != null) {
            eventTemplate.find(".event-img-container").css("background-image", "url('" + thisEvent.thumbLink + "')");
        }
        eventTemplate.find(".title").text(thisEvent.name);
        var date = new Date(parseInt(thisEvent.startDatetime) * 1000);
        eventTemplate.find(".date").text(date.getMonth() + 1 + "/" + date.getDate() + "/" + date.getYear() + " at " + formatAMPM(date));
        var url = eventTemplate.find("a").attr("href");
        url+="/"+thisEvent.id;
        if(thisEvent.repeating == 1){
            url+="/date/"+thisEvent.startDatetime;
        }
        eventTemplate.find("a").attr("href", url);
        html+= eventTemplate[0].outerHTML;
    }
    html+="</div>";
    return html;
}

window.initEventsClusterMap = function(){
    var page = "";
    var pageIndex = window.location.pathname.indexOf('/page');
    if(pageIndex != -1){
        page = window.location.pathname.substring(pageIndex);
    }

    loadEventsClusterMap();
};

window.initLocationMap = function(){
    var coordinates = $('#location-map').data('coordinates');
    var location = $('#location-map').data('location-name');
    if (coordinates) {
        var map = new google.maps.Map(document.getElementById('location-map'), {
            center: coordinates,
            zoom: 15,
            scrollwheel: false,
            mapTypeControl: false,
            streetViewControl: false
        });

        var marker = new google.maps.Marker({
            map: map,
            position: coordinates,
        });

        google.maps.event.addListener(marker, 'click', (function (coordinates) {
            return function () {
                window.open('http://www.google.com/maps/place/'+location+'/@'+coordinates.lat+','+coordinates.lng, '_blank');
            }
        })(coordinates));
    }
};