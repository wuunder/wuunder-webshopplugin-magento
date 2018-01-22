var parcelshopsData;
var baseUrl;
var googleMapsLoaded = false;
var map;

function showParcelshopPicker(e, url) {
    e.preventDefault();
    baseUrl = url;

    bindPopupEvents();
    toggleOverlay();
    window.parent.document.getElementById('parcelShopsPopup').style.display = 'block';
    toggleDataLoader();

    if (parcelshopsData !== undefined) {
        handleParcelshopsData(parcelshopsData);
    } else {
        fetchParcelshops(false)
    }
    return false;
}

function fetchParcelshops(post, data) {
    var fetchUrl = baseUrl + "shops";
    if (post) {
        fetch(fetchUrl, {
            credentials: "include",
            method: 'POST', // or 'PUT'
            body: JSON.stringify(data),
            headers: new Headers({
                'Content-Type': 'application/json'
            })
        }).then(function (response) {
            console.log(response);
            if (response.status === 200) {
                return response.json();
            } else {
                showErrorMessage();
                return null;
            }

        }).then(function (json) {
            if (json !== null)
                handleParcelshopsData(json);
        });
    } else {
        fetch(fetchUrl, {credentials: "include"}).then(function (response) {
            console.log(response);
            if (response.status === 200) {
                return response.json();
            } else {
                showErrorMessage();
                return null;
            }

        }).then(function (json) {
            if (json !== null)
                handleParcelshopsData(json);
        });
    }
}

function handleParcelshopsData(json) {
    initMap(window.parent.document.getElementById('parcelShopsMap'), json);
    toggleDataLoader();

    google.maps.event.trigger(map, 'resize'); // make sure map is updated
    map.setCenter(new google.maps.LatLng(parcelshopsData.lat, parcelshopsData.long)); //go to location of given address
}

function showErrorMessage() {
    alert("Something went wrong!");
    toggleOverlay();
    closePopup();
}

function closePopup() {
    window.parent.document.getElementById('parcelShopsPopup').style.display = 'none';
    clearDataView();
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
        window.parent.document.body.style.overflow = "auto";
    } else {
        overlay.style.display = "block";
        window.parent.document.body.style.overflow = "hidden";
    }
}

function toggleDataLoader() {
    var overlayLoader = window.parent.document.getElementById("parcelShopsMapLoader");

    if (overlayLoader.style.display === "block") {
        overlayLoader.style.display = "none";
        window.parent.document.getElementById('parcelShopsMapContainer').style.display = 'block';
        window.parent.document.getElementById('parcelShopsList').style.display = 'block';
    } else {
        overlayLoader.style.display = "block";
        window.parent.document.getElementById('parcelShopsMapContainer').style.display = 'none';
        window.parent.document.getElementById('parcelShopsList').style.display = 'none';
    }
}

function initMap(mapCanvas, data) {
    googleMapsLoaded = true;
    console.log(data);
    parcelshopsData = data;
    var parcelshops = JSON.parse(data.parcelshops);

    console.log(parcelshops);
    if (data.lat !== undefined && data.long !== undefined) {
        clearDataView();
        var pos = {lat: data.lat, lng: data.long};

        map = new google.maps.Map(mapCanvas, {
            zoom: 15,
            center: pos,
            mapTypeId: google.maps.MapTypeId.ROAsetParcelshopImageDMAP,
            mapTypeControl: false,
            scaleControl: true,
            streetViewControl: false,
            rotateControl: false,
            fullscreenControl: false
        });

        window.parent.document.getElementById('parcelShopsSearchBar').value = data.formatted_address;

        addUserMarker(data);

        //add all markers for nearby parcelshops
        for (var i = 0; i < parcelshops.length; i++) {
            addParcelshopMarker(parcelshops, i, data.image_dir);
        }
    }
}

function addUserMarker(data) {

    //add current user location (by address)
    var markerImage = {
        url: data.image_dir + "frontend/base/default/images/wuunder/position-sender.png",
        size: new google.maps.Size(81, 101),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(17, 34),
        scaledSize: new google.maps.Size(25, 35)
    };

    var marker = new google.maps.Marker({
        position: {
            lat: parseFloat(data.lat),
            lng: parseFloat(data.long)
        },
        icon: markerImage,
        map: map
    });

    var node = document.createElement("div");
    node.className += "parcelshopItem";
    node.onclick = parcelshopItemCallbackClosure(data.lat, data.long);
    node.innerHTML = "<table><tr><td rowspan='2'><span class='parcelshop-marker-icon-u'></span></td>" +
        "<td><span class='parcelshop-item-title'>Jouw adres</span></td></tr><tr><td>" +
        "<span class='parcelshop-item-address'>" + data.formatted_address + "</span>" +
        "</td></tr></table>";
    window.parent.document.getElementById('parcelShopsList').appendChild(node);
}

function addParcelshopMarker(parcelshops, i, image_dir) {
    var image_file_name = "";
    var image_class = "";

    switch (parcelshops[i].carrier_name) {
        case "DPD":
            image_file_name = "DPD-locator.png";
            image_class = "dpd";
            break;
        case "DHL":
            image_file_name = "DHL-locator.png";
            image_class = "dhl";
            break;
        case "GLS":
            image_file_name = "GLS-locator.png";
            image_class = "gls";
            break;
        case "POSTNL":
            image_file_name = "POSTNL-locator.png";
            image_class = "postnl";
            break;
        default:
            image_file_name = "green_marker.png";
            break;
    }
    var markerImage = {
        url: image_dir + "frontend/base/default/images/wuunder/" + image_file_name,
        size: new google.maps.Size(81, 101),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(17, 34),
        scaledSize: new google.maps.Size(50, 50)
    };

    var marker = new google.maps.Marker({
        position: {
            lat: parseFloat(parcelshops[i].latitude),
            lng: parseFloat(parcelshops[i].longitude)
        },
        icon: markerImage,
        map: map
    });

    marker.addListener('click', function() {
        map.setZoom(15);
        map.setCenter(marker.getPosition());
        openParcelshopItemDetails(window.parent.document.getElementsByClassName("parcelshopItem-" + i)[0]);
    });

    var node = document.createElement("div");
    node.className += "parcelshopItem parcelshopItem-" + i;
    node.onclick = parcelshopItemCallbackClosure(parcelshops[i].latitude, parcelshops[i].longitude);
    var nodeInnerHTML = "<table><tr><td><span class='parcelshop-marker-icon parcelshop-marker-icon-" + image_class + "'></span></td>" +
        "<td><span class='parcelshop-item-title parcelshop-item-line'>" + parcelshops[i].company_name + "</span>" +
        "<span class='parcelshop-item-line'>" + parcelshops[i].address.street_name + " " + parcelshops[i].address.house_number +
        "<span class='parcelshop-item-line'>" + parcelshops[i].address.zip_code + " " + parcelshops[i].address.city + "</span>" +
        "<span class='parcelshop-item-line'>" + Math.round(parseFloat(parcelshops[i].distance) * 1000) + " meter</span></td></tr></table>" +
        "<table class='parcelshop-item-details'><tr><td colspan='2'><span class='parcelshop_window_title'>Openingstijden</span></td></tr>";

    for (var j = 0; j < parcelshops[i].opening_hours.length; j++) {
        nodeInnerHTML += '<tr><td>' + parcelshops[i].opening_hours[j].weekday + '</td><td>' + parcelshops[i].opening_hours[j].open_morning + ' - ' + parcelshops[i].opening_hours[j].close_morning + ' ' + parcelshops[i].opening_hours[j].open_afternoon + ' - ' + parcelshops[i].opening_hours[j].close_afternoon + '</td></tr>'
    }
    node.innerHTML = nodeInnerHTML + "<tr><td colspan='2'><button onClick='chooseParcelshop(event, `" + parcelshops[i].wuunder_parcelshop_id + "`)'>Kies deze parcelshop</button></td></tr></table>";

    window.parent.document.getElementById('parcelShopsList').appendChild(node);
}

function parcelshopItemCallbackClosure(lat, long) {
    return function (e) {
        var clickedElement = e.target;
        if (!clickedElement.classList.contains('parcelshopItem')) {
            clickedElement = closest(clickedElement, '.parcelshopItem');
        }
        openParcelshopItemDetails(clickedElement);
        map.setCenter(new google.maps.LatLng(lat, long));
    }
}

function openParcelshopItemDetails(parcelshopItem) {
    var detailsElement = parcelshopItem.getElementsByClassName('parcelshop-item-details')[0];
    if (detailsElement !== undefined) {
        closeAllParcelshopItemDetails();
        if (detailsElement.style.display === "none" || detailsElement.style.display === "") {
            detailsElement.style.display = "block";
            // parcelshopItem.scrollIntoView(true);
            parcelshopItem.parentNode.scrollTop = parcelshopItem.offsetTop - parcelshopItem.parentNode.offsetTop;
        } else {
            detailsElement.style.display = "none";
        }
    }
}

function chooseParcelshop(e, parcelshopId) {
    e.preventDefault();
    var parcelshops = JSON.parse(parcelshopsData.parcelshops);
    for (var i = 0; i < parcelshops.length; i++) {
        if (parcelshops[i].wuunder_parcelshop_id === parcelshopId) {
            window.parent.document.getElementById("s_method_wuunderparcelshop_wuunderparcelshop").checked = true;
            var fetchUrl = baseUrl + "setshop/id/" + parcelshopId;
            fetch(fetchUrl, {credentials: "include"}).then(function (response) {
                console.log(response);

            });
            var parcelshop = parcelshops[i];
            window.parent.document.getElementById("parcelShopsSelected").innerHTML = "<table><tr><td><span class='wuunder-logo-small'></span></td><td></td><td></td></tr><tr><td></td><td><b>Parcelshop adres:</b></td><td></td></tr><tr><td></td><td>" +
                "<table><tbody><tr><td>" + parcelshop.company_name + "</td></tr>" +
                "<tr><td>" + parcelshop.address.street_name + " " + parcelshop.address.house_number + ",</td></tr>" +
                "<tr><td>" + parcelshop.address.city + "</td><td></td></tr></tbody></table></td></tr></table>";


        }
    }
    window.parent.document.getElementById('selectParceshop').innerHTML = 'Selecteer andere parcelshop';
    window.parent.document.getElementById('parcelShopsSelected').style.display = "block";
    closePopup();
    toggleOverlay();
    return false;
}

function bindPopupEvents() {
    var searchBar = window.parent.document.getElementById('submitParcelShopsSearchBar');
    searchBar.addEventListener('click', function (e) {
        clearDataView();
        toggleDataLoader();
        fetchParcelshops(true, {"address": window.parent.document.getElementById('parcelShopsSearchBar').value});
    });
    window.parent.document.getElementById('parcelShopsSearchBar').onkeydown = function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            searchBar.click();
            return false;
        }
    };
    window.parent.document.getElementById("closeParcelshopPopup").onclick = function (e) {
        e.preventDefault();
        toggleOverlay();
        closePopup();
        return false;
    };
}

function clearDataView() {
    window.parent.document.getElementById('parcelShopsMap').innerHTML = "";
    window.parent.document.getElementById('parcelShopsList').innerHTML = "";
}

function closeAllParcelshopItemDetails() {
    var elements = window.parent.document.getElementsByClassName('parcelshop-item-details');

    for (var i = 0; i < elements.length; i++) {
        elements[i].style.display = "none";
    }
}

function closest(el, selector) {
    var matchesFn;

    // find vendor prefix
    ['matches', 'webkitMatchesSelector', 'mozMatchesSelector', 'msMatchesSelector', 'oMatchesSelector'].some(function (fn) {
        if (typeof document.body[fn] == 'function') {
            matchesFn = fn;
            return true;
        }
        return false;
    })

    var parent;

    // traverse parents
    while (el) {
        parent = el.parentElement;
        if (parent && parent[matchesFn](selector)) {
            return parent;
        }
        el = parent;
    }

    return null;
}
