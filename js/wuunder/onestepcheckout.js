console.log("HELLO");

function switchShippingMethod(e) {
    var requiredFieldContainer = window.parent.document.getElementById("parcelShopsContainer");
    if (requiredFieldContainer) {
        var input;
        if (e.target.id !== "s_method_wuunderparcelshop_wuunderparcelshop") {
            console.log("wuunder");
            input = window.parent.document.getElementById("onestepValidationField");
            if (input !== undefined) {
                input.remove();
            }
        } else {
            console.log("else");
            input = document.createElement('input');
            input.id = "onestepValidationField";
            input.className += "validate-text required-entry";
            requiredFieldContainer.appendChild(input);
        }
    }
}

// window.onload = function () {
// //     var parcelshopOption = window.parent.document.getElementById("s_method_wuunderparcelshop_wuunderparcelshop");
// //
// //
// //     parcelshopOption.onclick = function (e) {
// //         console.log("HERE1");
// //         if (parcelshopOption.checked) {
// //             console.log("HERE2");
// //             selectParcelshopBtn.click();
// //         }
// //     };
//
//     document.querySelector('body').addEventListener('click', function(evt) {
//         // Do some check on target
//         if ( evt.target.getAttribute('name') === 'some-class') {
//             console.log("HERE1");
//             if (evt.target.checked) {
//                 console.log("HERE2");
//                 window.parent.document.getElementById("selectParceshop").click();
//             }
//         }
//     }, true);
// }