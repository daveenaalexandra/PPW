// File: js/script.js
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM Loaded, script.js is running.");

    const navbar = document.getElementById('navbar');
    // Dapatkan navLinks dari dalam elemen nav yang benar jika ada beberapa nav
    const mainNavElement = document.querySelector('header.main-navbar nav');
    const navLinks = mainNavElement ? mainNavElement.querySelectorAll('ul li a') : [];

    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = mainNavElement; // navMenu adalah elemen <nav> itu sendiri
    const currentYearSpan = document.getElementById('currentYear'); // Jika Anda mengaktifkan kembali footer
    const contactForm = document.getElementById('contactForm');
    const formMessageContainer = document.getElementById('formMessage');

    // Sticky navbar on scroll
    if (navbar) {
        function handleScroll() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            let currentSectionId = '';
            const sections = document.querySelectorAll('main section[id]'); // Pastikan section punya ID
            const navbarHeight = navbar.offsetHeight || 0;

            sections.forEach(section => {
                if (section.id) { // Hanya proses jika section punya ID
                    const sectionTop = section.offsetTop - navbarHeight - 20;
                    if (window.scrollY >= sectionTop) {
                        currentSectionId = section.getAttribute('id');
                    }
                }
            });

            if (navLinks.length > 0) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    const href = link.getAttribute('href');
                    // Cek apakah link adalah anchor link ke section di halaman saat ini
                    if (href && href.includes('#')) {
                        const anchor = href.substring(href.lastIndexOf('#') + 1);
                        if (anchor === currentSectionId) {
                            link.classList.add('active');
                        }
                    }
                });
            }
        }
        window.addEventListener('scroll', handleScroll);
        handleScroll(); // Initial check
    } else {
        // Hanya log jika navbar diharapkan ada di halaman (misalnya, bukan halaman login/register standalone)
        // console.warn("Navbar element with ID 'navbar' not found on this page.");
    }

    // Mobile menu toggle
    if (menuToggle && navMenu) {
        if (navLinks.length > 0) {
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (navMenu.classList.contains('active')) {
                        navMenu.classList.remove('active');
                        menuToggle.setAttribute('aria-expanded', 'false');
                        menuToggle.classList.remove('active');
                    }
                });
            });
        }

        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
            const isExpanded = navMenu.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
        });
    } else {
        // Hanya log jika elemen ini diharapkan ada
        // if (!menuToggle) console.warn("Menu toggle button with class '.menu-toggle' not found on this page.");
        // if (!navMenu) console.warn("Nav menu element ('header.main-navbar nav') not found on this page.");
    }

    // Set current year in footer (jika footer diaktifkan kembali dan memiliki span#currentYear)
    if (currentYearSpan) {
        currentYearSpan.textContent = new Date().getFullYear();
    }

    // Product View Toggle Logic (DIHAPUS/DIKOMENTARI KARENA TIDAK LAGI DIGUNAKAN DI INDEX.PHP)
    /*
    const toggleProductViewButton = document.getElementById('toggleProductViewButton');
    const initialProductGrid = document.getElementById('initialProductGrid');
    const detailedProductGrid = document.getElementById('detailedProductGrid');
    let productViewIsDetailed = false;

    if (toggleProductViewButton && initialProductGrid && detailedProductGrid) {
        console.log("Product toggle elements found. Attaching event listener.");
        toggleProductViewButton.addEventListener('click', function() {
            console.log("More button clicked! Current state (isDetailed):", productViewIsDetailed);
            if (!productViewIsDetailed) {
                initialProductGrid.style.display = 'none';
                detailedProductGrid.style.display = 'grid';
                toggleProductViewButton.innerHTML = 'Less <span class="arrow-icon">←</span>';
                productViewIsDetailed = true;
            } else {
                detailedProductGrid.style.display = 'none';
                initialProductGrid.style.display = 'grid';
                toggleProductViewButton.innerHTML = 'More <span class="arrow-icon">→</span>';
                productViewIsDetailed = false;
            }
        });
    } else {
        const currentPage = window.location.pathname.split("/").pop();
        if (currentPage === 'index.php' || currentPage === '') {
            // Pesan ini mungkin tidak lagi relevan jika tombol sudah diubah jadi link
            // if (!toggleProductViewButton) console.warn("Tombol 'toggleProductViewButton' tidak ditemukan.");
            // if (!initialProductGrid) console.warn("Elemen 'initialProductGrid' tidak ditemukan.");
            // if (!detailedProductGrid) console.warn("Elemen 'detailedProductGrid' tidak ditemukan.");
        }
    }
    */

    // Contact Form Submission (AJAX)
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Contact form submitted.");

            const formData = new FormData(contactForm);
            const actionUrl = contactForm.getAttribute('action');
            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton ? submitButton.textContent : 'Send'; // Simpan teks asli tombol

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Mengirim...';
            }

            if (formMessageContainer) {
                formMessageContainer.innerHTML = '';
                formMessageContainer.className = 'form-message-container'; // Reset class
            }

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { // Coba dapatkan detail error dari server
                        throw new Error('Network response error: ' + response.statusText + ' (status: ' + response.status + ') - Server says: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (formMessageContainer) {
                    formMessageContainer.textContent = data.message;
                    if (data.success) {
                        formMessageContainer.classList.add('success');
                        contactForm.reset();
                    } else {
                        formMessageContainer.classList.add('error');
                    }
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                if (formMessageContainer) {
                    formMessageContainer.textContent = 'Terjadi kesalahan. Isi form tidak terkirim. (' + error.message + ')';
                    formMessageContainer.classList.add('error');
                }
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText; // Kembalikan ke teks awal
                }
            });
        });
    } else {
        // console.warn("Contact form with ID 'contactForm' not found on this page.");
    }

});