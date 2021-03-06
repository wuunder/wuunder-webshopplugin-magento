var baseUrl = "";
var baseUrlApi = "";
var parcelshopInfoDiv = 'parcelShopsSelectedContainer';
var parcelshopMethodId = 's_method_wuunderparcelshop_wuunderparcelshop';
var carrierAvailableList = '';

var selectedParcelshopId = null;

function initParcelshopMethod(url, apiUrl, carriers, parcelshopId) {
    baseUrl = url;
    baseUrlApi = apiUrl;
    carrierAvailableList = carriers;

    if (parcelshopId !== undefined) {
        selectedParcelshopId = parcelshopId;
    }
    
    if (window.parent.document.getElementById(parcelshopMethodId).checked) {
        window.parent.document.getElementById(parcelshopInfoDiv).style.display = 'block';

        //Trigger onClick function, to trigger switchShippingMethodValidation function in onestepcheckout
        window.parent.document.getElementById(parcelshopMethodId).click();
    }

    //OneStepCheckout
    wuunderOneStepCheckoutInit();
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
            selectedParcelshopId = parcelshopId;

            // OneStepCheckout support
            if (window.parent.document.getElementById("onestepValidationField")) {
                window.parent.document.getElementById("onestepValidationField").value = parcelshopId;
                window.parent.document.getElementById("onestepValidationField").onchange();
            }

            // Typical action to be performed when the document is ready:
            var data = JSON.parse(xhttp.response);
            window.parent.document.getElementById("parcelShopsSelected").outerHTML = data

        }
    };
    xhttp.open("GET", fetchUrl, true);
    xhttp.send();
}
