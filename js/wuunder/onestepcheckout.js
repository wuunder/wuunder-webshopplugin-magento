console.log("HELLO");


window.onload = function () {
    var parcelshopOption = window.parent.document.getElementById("s_method_wuunderparcelshop_wuunderparcelshop");
    var selectParcelshopBtn = window.parent.document.getElementById("selectParceshop");

    parcelshopOption.onclick = function (e) {
        console.log("HERE1");
        if (parcelshopOption.checked) {
            console.log("HERE2");
            selectParcelshopBtn.click();
        }
    };
}