function myFunction() {
    var x = document.getElementById("fluent_support_app_pass");
    console.log(x);
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}