function removeDiacritics(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}
function myFunction() {
    var input, filter, ul, li, a, i, txtValue;
    
    
    input = document.getElementById("mySearch");
    filter = removeDiacritics(input.value.toLowerCase());
    
    
    ul = document.getElementById("myMenu");
    li = ul.getElementsByTagName("li");

   
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName("a")[0];
        
      
        txtValue = removeDiacritics(a.innerHTML.toLowerCase());
        
       
        if (txtValue.indexOf(filter) > -1) {
            li[i].style.display = "";  // Hiển thị mục
        } else {
            li[i].style.display = "none";  // Ẩn mục
        }
    }
}
