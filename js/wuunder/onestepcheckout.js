function switchShippingMethodValidation(e) {
    var requiredFieldContainer = window.parent.document.getElementById("localParcelShopsContainer");
    if (requiredFieldContainer) {
        var input;
        if (e.target.id !== "s_method_wuunderparcelshop_wuunderparcelshop") {
            input = window.parent.document.getElementById("onestepValidationField");
            if (input !== undefined) {
                input.remove();
            }
        } else {
            input = document.createElement('input');
            input.id = "onestepValidationField";
            input.className += "validate-text required-entry";
            requiredFieldContainer.appendChild(input);
        }
    }
}