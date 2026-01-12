/**
 * International Telephone Input Initialization
 * 
 * Initializes intl-tel-input on phone fields with country flags,
 * dial codes, and validation.
 */

import intlTelInput from 'intl-tel-input';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initPhoneInputs();
});

// Also handle Livewire updates
document.addEventListener('livewire:navigated', function() {
    initPhoneInputs();
});

function initPhoneInputs() {
    const phoneInputs = document.querySelectorAll('input[data-intl-tel-input]:not([data-iti-initialized])');
    
    phoneInputs.forEach(function(input) {
        // Mark as initialized to prevent double initialization
        input.setAttribute('data-iti-initialized', 'true');
        
        // Get configuration from data attributes
        const initialCountry = input.dataset.initialCountry || 'es';
        const preferredCountries = (input.dataset.preferredCountries || 'es,pt,fr,de,gb,it,us').split(',');
        const initialValue = input.value || input.dataset.initialValue || '';
        
        // Clear input before init to avoid double parsing issues
        input.value = '';
        
        // Initialize intl-tel-input (utils loaded via import)
        const iti = intlTelInput(input, {
            initialCountry: initialCountry,
            preferredCountries: preferredCountries,
            separateDialCode: true,
            nationalMode: true,
            autoPlaceholder: 'aggressive',
            formatOnDisplay: true,
            dropdownContainer: document.body,
        });
        
        // Store instance on element
        input.iti = iti;
        
        // If there's an initial value in E.164 format, use setNumber to parse it
        if (initialValue && initialValue.startsWith('+')) {
            // Wait for utils to load, then set number
            iti.promise.then(() => {
                iti.setNumber(initialValue);
            });
        } else if (initialValue) {
            // If it's a national number, just set it
            input.value = initialValue;
        }
        
        // Find or create hidden field for full international number
        const form = input.closest('form');
        const fieldName = input.name;
        let hiddenInput = form?.querySelector(`input[name="${fieldName}_full"]`);
        
        if (!hiddenInput && form) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `${fieldName}_full`;
            input.parentNode.insertBefore(hiddenInput, input.nextSibling);
        }
        
        // Create hidden field for country code
        let countryInput = form?.querySelector(`input[name="${fieldName}_country"]`);
        if (!countryInput && form) {
            countryInput = document.createElement('input');
            countryInput.type = 'hidden';
            countryInput.name = `${fieldName}_country`;
            input.parentNode.insertBefore(countryInput, input.nextSibling);
        }
        
        // Update hidden fields on change
        const updateHiddenFields = () => {
            if (hiddenInput) {
                hiddenInput.value = iti.getNumber();
            }
            if (countryInput) {
                const countryData = iti.getSelectedCountryData();
                countryInput.value = countryData.iso2?.toUpperCase() || '';
            }
        };
        
        // Bind events
        input.addEventListener('countrychange', updateHiddenFields);
        input.addEventListener('change', updateHiddenFields);
        input.addEventListener('blur', updateHiddenFields);
        input.addEventListener('input', updateHiddenFields);
        
        // Initial update
        updateHiddenFields();
        
        // Form submission - ensure full number is submitted
        if (form) {
            form.addEventListener('submit', function() {
                updateHiddenFields();
            });
        }
    });
}

// Export for programmatic use
window.initPhoneInputs = initPhoneInputs;
