// File: js/index-main.js

document.addEventListener('DOMContentLoaded', function() {
    // 1. Navigasi "Sticky" dan Sorot Menu Aktif
    const navbar = document.querySelector('.navbar');
    const navLinks = document.querySelectorAll('header nav ul li a');

    if (navbar) {
        function handleScroll() {
            // Logika untuk highlight menu aktif saat scroll
            let currentSectionId = '';
            const sections = document.querySelectorAll('section[id]');
            const navbarHeight = navbar.offsetHeight || 0;

            sections.forEach(section => {
                if (section.id) {
                    const sectionTop = section.offsetTop - navbarHeight - 50;
                    if (window.scrollY >= sectionTop) {
                        currentSectionId = section.getAttribute('id');
                    }
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                const href = link.getAttribute('href');
                if (href && href.includes('#')) {
                    const anchor = href.substring(href.lastIndexOf('#') + 1);
                    if (anchor === currentSectionId) {
                        link.classList.add('active');
                    }
                }
            });
        }
        window.addEventListener('scroll', handleScroll);
        handleScroll(); // Panggil saat awal untuk set state
    }

    // 2. Pengiriman Form Kontak (AJAX)
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        const formMessageContainer = document.getElementById('formMessage');
        
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(contactForm);
            const actionUrl = contactForm.getAttribute('action');
            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;

            submitButton.disabled = true;
            submitButton.textContent = 'Mengirim...';
            formMessageContainer.innerHTML = '';
            formMessageContainer.className = 'form-message-container';

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                formMessageContainer.textContent = data.message;
                if (data.success) {
                    formMessageContainer.classList.add('success');
                    contactForm.reset();
                } else {
                    formMessageContainer.classList.add('error');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                formMessageContainer.textContent = 'Terjadi kesalahan. Pesan tidak terkirim.';
                formMessageContainer.classList.add('error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
});