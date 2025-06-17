// File: js/checkout.js (Versi Final untuk Form Detail)

document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    const addressSelection = document.getElementById('address_selection');
    const newAddressFieldsContainer = document.getElementById('new_address_form_fields');
    const finalShippingAddressInput = document.getElementById('final_shipping_address');

    // Peta antara key di data JSON dengan ID input field di HTML
    const inputMap = {
        recipient_name: document.getElementById('recipient_name'),
        phone_number: document.getElementById('phone_number'),
        street_address: document.getElementById('street_address'),
        house_number: document.getElementById('house_number'),
        neighborhood: document.getElementById('neighborhood'),
        sub_district: document.getElementById('sub_district'),
        city_regency: document.getElementById('city_regency'),
        province: document.getElementById('province'),
        postal_code: document.getElementById('postal_code')
    };

    // Fungsi untuk mengisi semua field di form detail
    function populateForm(addressData) {
        for (const key in inputMap) {
            if (inputMap[key]) {
                inputMap[key].value = addressData[key] || '';
            }
        }
    }

    // Fungsi untuk menangani perubahan pada dropdown alamat
    function handleAddressSelection() {
        const selectedValue = addressSelection.value;
        
        if (selectedValue === 'new_address') {
            populateForm({}); // Kosongkan semua field
        } else {
            try {
                const addressData = JSON.parse(selectedValue);
                populateForm(addressData); // Isi field dengan data dari alamat yang dipilih
            } catch (e) {
                console.error("Data alamat tidak valid:", e);
                populateForm({}); // Jika data error, kosongkan form
            }
        }
    }

    // Fungsi untuk menggabungkan data dari form detail menjadi satu string
    function buildAddressString() {
        const parts = [
            `Penerima: ${inputMap.recipient_name.value}`,
            inputMap.street_address.value,
            inputMap.house_number.value ? `No. ${inputMap.house_number.value}` : '',
            inputMap.neighborhood.value,
            inputMap.sub_district.value ? `Kec. ${inputMap.sub_district.value}` : '',
            inputMap.city_regency.value,
            inputMap.province.value,
            inputMap.postal_code.value ? `Kode Pos: ${inputMap.postal_code.value}` : ''
        ];
        return parts.filter(part => part).join(', ');
    }
    
    // Event listener PENTING: Sebelum form disubmit ke server
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(event) {
            // Update nilai input tersembunyi dengan gabungan data dari form detail
            finalShippingAddressInput.value = buildAddressString();
        });
    }

    // Event listener untuk dropdown
    if (addressSelection) {
        addressSelection.addEventListener('change', handleAddressSelection);
        // Panggil sekali saat halaman dimuat untuk mengisi form sesuai pilihan default
        handleAddressSelection();
    }
});