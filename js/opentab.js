function openTab(tabId) {
    var i;
    var x = document.getElementsByClassName("tab-content");
    for (i = 0; i < x.length; i++) {
        x[i].classList.remove('active');
    }
    document.getElementById(tabId).classList.add('active');
}