// File: js/profile.js

document.addEventListener('DOMContentLoaded', function() {
    const profilePictureInput = document.getElementById('profile_picture');
    const profilePicPreview = document.getElementById('profile-pic-preview');
    const addAddressBtn = document.getElementById('addAddressBtn');
    const addressFormContainer = document.getElementById('addressFormContainer');
    const addressForm = document.getElementById('addressForm');
    const formTitle = document.getElementById('formTitle');
    const submitAddressBtn = document.getElementById('submitAddressBtn');
    const cancelAddressBtn = document.getElementById('cancelAddressBtn');

    // Form fields for address
    const addressIdInput = document.getElementById('address_id');
    const labelInput = document.getElementById('label');
    const recipientNameFormInput = document.getElementById('recipient_name_form');
    const phoneNumberFormInput = document.getElementById('phone_number_form');
    const streetAddressFormInput = document.getElementById('street_address_form');
    const houseNumberFormInput = document.getElementById('house_number_form');
    const neighborhoodFormInput = document.getElementById('neighborhood_form');
    const subDistrictFormInput = document.getElementById('sub_district_form');
    const cityRegencyFormInput = document.getElementById('city_regency_form');
    const provinceFormInput = document.getElementById('province_form');
    const postalCodeFormInput = document.getElementById('postal_code_form');
    const isDefaultCheckbox = document.getElementById('is_default');

    if (profilePictureInput && profilePicPreview) {
        profilePictureInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePicPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Show add address form
    addAddressBtn.addEventListener('click', function() {
        addressFormContainer.style.display = 'block';
        formTitle.textContent = 'Tambah';
        submitAddressBtn.name = 'add_address';
        submitAddressBtn.textContent = 'Simpan Alamat';
        addressForm.reset(); // Clear previous data
        addressIdInput.value = ''; // Ensure no address_id for new address
        // Scroll to form
        addressFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Hide form
    cancelAddressBtn.addEventListener('click', function() {
        addressFormContainer.style.display = 'none';
        addressForm.reset();
        addressIdInput.value = '';
    });

    // Handle edit buttons
    document.querySelectorAll('.btn-edit-address').forEach(button => {
        button.addEventListener('click', function() {
            const addressData = JSON.parse(this.dataset.address);
            
            addressIdInput.value = addressData.address_id;
            labelInput.value = addressData.label || '';
            recipientNameFormInput.value = addressData.recipient_name || '';
            phoneNumberFormInput.value = addressData.phone_number || '';
            streetAddressFormInput.value = addressData.street_address || '';
            houseNumberFormInput.value = addressData.house_number || '';
            neighborhoodFormInput.value = addressData.neighborhood || '';
            subDistrictFormInput.value = addressData.sub_district || '';
            cityRegencyFormInput.value = addressData.city_regency || '';
            provinceFormInput.value = addressData.province || '';
            postalCodeFormInput.value = addressData.postal_code || '';
            isDefaultCheckbox.checked = addressData.is_default == 1;

            formTitle.textContent = 'Edit';
            submitAddressBtn.name = 'edit_address';
            submitAddressBtn.textContent = 'Perbarui Alamat';
            addressFormContainer.style.display = 'block';
            // Scroll to form
            addressFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});

