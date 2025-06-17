document.addEventListener('DOMContentLoaded', function() {
    const addActivityButton = document.getElementById('btn-add-activity-to-cart');
    const messageDiv = document.getElementById('add-to-cart-message');

    if (addActivityButton) {
        addActivityButton.addEventListener('click', function() {
            const activityId = this.dataset.activityId;

            // Tampilkan status loading
            messageDiv.className = 'message info';
            messageDiv.textContent = 'Menambahkan ke keranjang...';
            messageDiv.style.display = 'block';

            // Kirim data ke server menggunakan fetch
            fetch('add_activity_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `activity_id=${activityId}`
            })
            .then(response => response.json())
            .then(data => {
                // Tampilkan pesan dari server
                messageDiv.className = data.status === 'success' ? 'message success' : 'message ' + data.status;
                messageDiv.textContent = data.message;
                
                // Sembunyikan pesan setelah beberapa detik
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3500);
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Terjadi kesalahan saat menambahkan kegiatan.';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            });
        });
    }
});