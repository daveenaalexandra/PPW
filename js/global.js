// File: js/global.js

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Fungsi generik untuk mengelola semua menu dropdown di situs.
     * @param {string} containerId - ID dari elemen <li> pembungkus dropdown.
     */
    function setupDropdown(containerId) {
        const dropdownContainer = document.getElementById(containerId);
        if (dropdownContainer) {
            const dropbtn = dropdownContainer.querySelector('.dropbtn');

            if (dropbtn) {
                dropbtn.addEventListener('click', function(event) {
                    // Tutup dropdown lain yang mungkin sedang terbuka
                    document.querySelectorAll('.dropdown.show').forEach(openDropdown => {
                        if (openDropdown.id !== containerId) {
                            openDropdown.classList.remove('show');
                        }
                    });

                    // Buka atau tutup dropdown yang diklik
                    dropdownContainer.classList.toggle('show');
                    event.stopPropagation(); // Mencegah event 'click' menyebar ke window
                });
            }
        }
    }

    // Inisialisasi semua dropdown yang ada di header
    setupDropdown('ourDropdown');
    setupDropdown('userDropdown');

    // Menambahkan event listener untuk menutup dropdown jika pengguna mengklik di luar area menu
    window.addEventListener('click', function(event) {
        // Periksa apakah yang diklik bukan tombol dropdown
        if (!event.target.matches('.dropbtn')) {
            // Ambil semua dropdown yang sedang terbuka dan tutup
            document.querySelectorAll('.dropdown.show').forEach(openDropdown => {
                openDropdown.classList.remove('show');
            });
        }
    });
});