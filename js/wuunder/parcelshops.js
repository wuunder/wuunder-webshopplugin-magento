function showParcelshopPicker(e, url) {
    e.preventDefault();
    fetch(url, {credentials: "include"}).then(function (response) {
        return response.json();
    }).then(function (json) {
        initMap(window.parent.document.getElementById('parcelShopsMap'), json);
        toggleOverlay();
        window.parent.document.getElementById("closeParcelshopPopup").onclick = function (e) {
            e.preventDefault();
            toggleOverlay();
            window.parent.document.getElementById('parcelShopsPopup').style.display = 'none';
            return false;
        };
        window.parent.document.getElementById('parcelShopsPopup').style.display = 'block';
    });
    return false;
}

function toggleOverlay() {
    var overlay;
    if (window.parent.document.getElementById("parcelShopOverlay") === undefined || window.parent.document.getElementById("parcelShopOverlay") === null) {
        var node = document.createElement("div");
        node.id += "parcelShopOverlay";
        window.parent.document.getElementsByTagName("body")[0].appendChild(node);
        overlay = node;
    } else {
        overlay = window.parent.document.getElementById("parcelShopOverlay");
    }
    if (overlay.style.display === "block") {
        overlay.style.display = "none";
    } else {
        overlay.style.display = "block";
    }
}

function initMap(mapCanvas, data) {
    console.log(data);
    var parcelshops = JSON.parse(data.parcelshops);

    console.log(parcelshops);
    if (data.lat !== undefined && data.long !== undefined) {
        var pos = {lat: data.lat, lng: data.long};

        var map = new google.maps.Map(mapCanvas, {
            zoom: 15,
            center: pos,
            mapTypeId: google.maps.MapTypeId.ROAsetParcelshopImageDMAP,
            mapTypeControl: false,
            scaleControl: true,
            streetViewControl: false,
            rotateControl: false,
            fullscreenControl: false
        });

        for (var i = 0; i < parcelshops.length; i++) {
            var markerImage = {
                url: data.image_dir + "frontend/base/default/images/wuunder/green_marker.png",
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(25, 25)
            };

            var marker = new google.maps.Marker({
                position: {
                    lat: parseFloat(parcelshops[i].latitude),
                    lng: parseFloat(parcelshops[i].longitude)
                },
                icon: markerImage,
                map: map
            });

            var contentString = '<div id="content">' +
                '<span class="parcelshop_window_title">' + parcelshops[i].company_name + '</span>' +
                '<p>' + parcelshops[i].address.street_name + ' ' + parcelshops[i].address.house_number + ',<br>' +
                parcelshops[i].address.city + '</p><br>' +
                '<span class="parcelshop_window_title">Openingstijden</span><table>';

            for (var j = 0; j < parcelshops[i].opening_hours.length; j++) {
                contentString += '<tr><td>' + parcelshops[i].opening_hours[j].weekday + '</td><td>' + parcelshops[i].opening_hours[j].open_morning + ' - ' + parcelshops[i].opening_hours[j].close_morning + ' ' + parcelshops[i].opening_hours[j].open_afternoon + ' - ' + parcelshops[i].opening_hours[j].close_afternoon + '</td></tr>'
            }

            contentString += '</table><button>Kies deze parcelshop</button></div>';

            var infowindow = new google.maps.InfoWindow({
                content: contentString,
                borderRadius: 8,
                borderWidth: 2,
                borderColor: '#aec941'
            });

            marker.addListener('click', function() {
                infowindow.open(map, marker);
            });

            var node = document.createElement("div");
            node.className += "parcelshopItem";
            node.onclick = parcelshopItemCallbackClosure(parcelshops[i].latitude, parcelshops[i].longitude, map);
            node.innerHTML = "<table><tr><td><span class='parcelshop-marker-icon'></span></td>" +
                "<td><span class='parcelshop-item-title'>" + parcelshops[i].company_name + "</span>" +
                "<span class='parcelshop-item-address'>" + parcelshops[i].address.street_name + " " + parcelshops[i].address.house_number + ", " + parcelshops[i].address.city + "</span>" +
                "<span class='parcelshop-item-distance'>" + Math.round(parseFloat(parcelshops[i].distance) * 1000) + " meter</span></td></tr></table>";
            window.parent.document.getElementById('parcelShopsList').appendChild(node);
        }
    }
}

function parcelshopItemCallbackClosure(lat, long, map) {
    return function () {
        map.setCenter(new google.maps.LatLng(lat, long));
    }
}