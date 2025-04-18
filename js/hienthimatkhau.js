
function togglePasswordVisibility(inputId) {
    var passwordField = document.getElementById(inputId);
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}