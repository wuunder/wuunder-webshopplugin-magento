function showParcelshopPicker(e, fetchUrl) {
    if (self.fetch) {
        fetch(fetchUrl + 'address', {
            credentials: "include",
            method: 'GET', // or 'PUT'
            headers: new Headers({
                'Content-Type': 'application/json'
            })
        }).then(function (response) {
            if (response.status === 200) {
                return response.json();
            } else {
                alert("Something went wrong!");
                return null;
            }
        }).then(function (json) {
            if (json !== null)
                showModal(json);
        });
    } else {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                showModal(JSON.parse(xhttp.response));
            }
        };
        xhttp.open("GET", fetchUrl + 'address', true);
        xhttp.send();
    }
}

function showModal(data) {
    var url = 'http://128.199.52.98/parcelshoppicker/?lang=nl&address=' + encodeURI(data.address);
    var iframeDiv = document.createElement('div');
    iframeDiv.innerHTML = '<iframe src="' + url + '" width="100%" height="100%">';
    iframeDiv.style.cssText = 'position: fixed; top: 0; left: 0; bottom: 0; right: 0; z-index: 2147483647';
    var loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483646; background-color: black; opacity: 0.7';
    document.getElementById("parcelShopsContainer").appendChild(iframeDiv);

    function removeServicePointPicker() {
        removeElement(iframeDiv);
        removeElement(loadingDiv);
    }

    function onServicePointSelected(event) {
        console.log(event);
        removeServicePointPicker();
    }

    function onServicePointClose() {
        removeServicePointPicker();
    }

    function onWindowMessage(event) {
        var origin = event.origin,
            messageData = event.data;
        // if (origin !== baseDomain) {
        //     alert('Invalid domain');
        //     return;
        // }
        var messageHandlers = {
            'servicePointSelected': onServicePointSelected,
            'servicePointClose': onServicePointClose
        };
        if (!(messageData in messageHandlers)) {
            alert('Invalid event type');
            return;
        }
        var messageFn = messageHandlers[messageData];
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
