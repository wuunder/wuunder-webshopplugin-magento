
function showParcelshopPicker() {
    var url = "http://128.199.52.98/parcelshoppicker/";
    var iframeDiv = document.createElement('div');
    iframeDiv.innerHTML = '<iframe src="' + url + '" width="100%" height="100%">';
    iframeDiv.style.cssText = 'position: fixed; top: 0; left: 0; bottom: 0; right: 0; z-index: 2147483647';
    var loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483646; background-color: black; opacity: 0.7';
    document.getElementById("parcelShopsContainer").appendChild(iframeDiv);
    // document.body.appendChild(loadingDiv);
}