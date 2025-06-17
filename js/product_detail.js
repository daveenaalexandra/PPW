// File: js/product_detail.js

document.addEventListener('DOMContentLoaded', function() {
    const addToCartButton = document.querySelector('.btn-add-to-cart');
    const messageDiv = document.getElementById('add-to-cart-message');

    if (addToCartButton) {
        addToCartButton.addEventListener('click', function() {
            // Ambil data produk dari atribut data-* tombol
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            const quantity = 1; // Untuk saat ini, selalu tambahkan 1 item

            // Tampilkan status loading
            messageDiv.className = 'message info';
            messageDiv.textContent = 'Menambahkan...';
            messageDiv.style.display = 'block';

            // Kirim data ke server menggunakan fetch API
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&product_name=${encodeURIComponent(productName)}&product_price=${productPrice}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }
                return response.json();
            })
            .then(data => {
                // Tampilkan pesan sukses atau error dari server
                messageDiv.className = data.status === 'success' ? 'message success' : 'message error';
                messageDiv.textContent = data.message;
                
                // Sembunyikan pesan setelah beberapa detik
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Terjadi kesalahan saat menambahkan produk.';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            });
        });
    }
});