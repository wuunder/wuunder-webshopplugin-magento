var baseUrl = "";
var baseUrlApi = "";
var parcelshopInfoDiv = 'parcelShopsSelectedContainer';
var parcelshopMethodId = 's_method_wuunderparcelshop_wuunderparcelshop';
var carrierAvailableList = '';

function initParcelshopMethod(url, apiUrl, carriers) {
    baseUrl = url;
    baseUrlApi = apiUrl;
    carrierAvailableList = carriers;
    console.log(carrierAvailableList);
    
    if (window.parent.document.getElementById(parcelshopMethodId).checked) {
        window.parent.document.getElementById(parcelshopInfoDiv).style.display = 'block';

        //Trigger onClick function, to trigger switchShippingMethodValidation function in onestepcheckout
        window.parent.document.getElementById(parcelshopMethodId).click();
    }
}

function switchShippingMethod(e) {
    if (e.target.id === parcelshopMethodId) {
        window.parent.document.getElementById(parcelshopInfoDiv).style.display = 'block';
    } else {
        window.parent.document.getElementById(parcelshopInfoDiv).style.display = 'none';
    }
}

function showParcelshopPicker() {
    var fetchUrl = baseUrl + 'wuunderconnector/parcelshop/';
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            showModal(JSON.parse(xhttp.response));
        }
    };
    xhttp.open("GET", fetchUrl + 'address', true);
    xhttp.send();
}

function showModal(data) {
    // var url = baseUrlApi + 'parcelshop_locator/iframe/?lang=nl&availableCarriers=dpd,dhl,postnl&address=' + encodeURI(data.address);
    var url = baseUrlApi + 'parcelshop_locator/iframe/?lang=nl&availableCarriers=' + carrierAvailableList + '&address=' + encodeURI(data.address);
    var iframeContainer = document.createElement('div');
    iframeContainer.className = "parcelshopPickerIframeContainer";
    iframeContainer.onclick = function (e) {
        e.preventDefault();
        removeServicePointPicker();
        return false;
    };
    var iframeDiv = document.createElement('div');
    iframeDiv.innerHTML = '<iframe src="' + url + '" width="100%" height="100%">';
    iframeDiv.className = "parcelshopPickerIframe";
    iframeDiv.style.cssText = 'position: fixed; top: 0; left: 0; bottom: 0; right: 0; z-index: 2147483647';
    iframeContainer.appendChild(iframeDiv);
    document.getElementById("localParcelShopsContainer").appendChild(iframeContainer);

    function removeServicePointPicker() {
        removeElement(iframeContainer);
    }

    function onServicePointSelected(messageData) {
        removeServicePointPicker();
        setParcelshop(messageData.parcelshopId);
    }

    function onServicePointClose() {
        removeServicePointPicker();
    }

    function onWindowMessage(event) {
        var origin = event.origin,
            messageData = event.data;
        var messageHandlers = {
            'servicePointPickerSelected': onServicePointSelected,
            'servicePointPickerClose': onServicePointClose
        };
        if (!(messageData.type in messageHandlers)) {
            alert('Invalid event type');
            return;
        }
        var messageFn = messageHandlers[messageData.type];
        messageFn(messageData);
    }

    window.addEventListener('message', onWindowMessage, false);
}

function removeElement(element) {
    if (element.remove !== undefined) {
        element.remove();
    } else {
        element && element.parentNode && element.parentNode.removeChild(element);
    }
}

function setParcelshop(parcelshopId) {
    var fetchUrl = baseUrl + "wuunderconnector/parcelshop/setshop/id/" + parcelshopId;
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            // Typical action to be performed when the document is ready:
            var data = JSON.parse(xhttp.response);
            // console.log(data);
            // console.log(window.parent.document.getElementById("parcelShopsSelected").outerHTML);
            window.parent.document.getElementById("parcelShopsSelected").outerHTML = data
            // var parcelshop = parcelshops[i];
            // window.parent.document.getElementById("parcelShopsSelected").innerHTML = "<table><tr><td><span class='wuunder-logo-small'></span></td><td></td><td></td></tr><tr><td></td><td><b>Parcelshop adres:</b></td><td></td></tr><tr><td></td><td>" +
            //     "<table><tbody><tr><td>" + parcelshop.company_name + "</td></tr>" +
            //     "<tr><td>" + parcelshop.address.street_name + " " + parcelshop.address.house_number + ",</td></tr>" +
            //     "<tr><td>" + parcelshop.address.city + "</td><td></td></tr></tbody></table></td></tr></table>";
        }
    };
    xhttp.open("GET", fetchUrl, true);
    xhttp.send();
}
