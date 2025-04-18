
function searchProducts() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const productItems = document.querySelectorAll('.product-item');

    productItems.forEach(item => {
        const productName = item.querySelector('.product-name').textContent.toLowerCase();
        if (productName.includes(input)) {
            item.style.display = 'block'; // Hiện sản phẩm nếu tìm thấy
        } else {
            item.style.display = 'none'; 
        }
    });
}
