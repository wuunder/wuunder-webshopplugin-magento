console.log("test");

function showParcelshopPicker(e, url) {
    e.preventDefault();
    var container = window.parent.document.getElementById('parcelShopsContainer');
    var iframe = container.getElementsByTagName("iframe");
    if (iframe.length > 0) {
        iframe = iframe[0];
        iframe.src = url;
        iframe.style.display = "block";

        var innerDoc = iframe.contentDocument || iframe.contentWindow.document;

    }
    return false;
};