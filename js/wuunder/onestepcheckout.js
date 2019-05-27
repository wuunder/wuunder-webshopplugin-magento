function wuunderOneStepCheckoutInit() {
    var existingInput = window.parent.document.getElementById("onestepValidationField");

    if (!existingInput) {
        existingInput.onchange = removeWuunderOneStepCheckoutValidationWarning;
    }
}

function switchShippingMethodValidation(e) {
    var requiredFieldContainer = window.parent.document.getElementById("localParcelShopsContainer");
    if (requiredFieldContainer) {
        var input;
        if (e.target.id !== "s_method_wuunderparcelshop_wuunderparcelshop") {
            input = window.parent.document.getElementById("onestepValidationField");
            if (input !== undefined) {
                input.remove();
            }
            removeWuunderOneStepCheckoutValidationWarning();
        } else {
            var existingInput = window.parent.document.getElementById("onestepValidationField");

            if (!existingInput) {
                input = document.createElement('input');
                input.id = "onestepValidationField";
                input.className += "validate-text required-entry";
                input.value = selectedParcelshopId;
                input.onchange = removeWuunderOneStepCheckoutValidationWarning;
                requiredFieldContainer.appendChild(input);
            }
        }
    }
}

function removeWuunderOneStepCheckoutValidationWarning(e) {
    console.log("H1");

    var requiredFieldContainer = window.parent.document.getElementById("localParcelShopsContainer");

    if (requiredFieldContainer) {
        var warning = window.parent.document.getElementById("localParcelShopsContainer").getElementsByClassName("validation-advice");
        if (warning[0]) {
            warning[0].remove();
        }
    }
}