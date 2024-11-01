jQuery(document).ready(function($) {


    // Selectors are cached for better performance
    const $emailInput = $('#wd_smartsearch_license_email');
    const $emailError = $('#wd_smartsearch_license_email_error');
    const $licenseStatus = $('#wd-free-license-status');
    const $updatesCheckbox = $('#wd_license_updates');

    $('#wd-free-license-submit').on('click', function(event) {
        event.preventDefault();

        const email = $emailInput.val().trim();
        const emailRegex = /\S+@\S+\.\S+/;

        // Show error if email is invalid
        if (!emailRegex.test(email) || email.length === 0) {
            $emailError.prop('hidden', false);
            return;
        }

        $emailError.prop('hidden', true);
        $licenseStatus.addClass('loading').removeClass('no-license has-license');
        $licenseStatus.html('<i class="fas fa-spinner fa-spin"></i>');

        const receive_updates = $updatesCheckbox.is(':checked');
        const data = {
            action: 'wdgpt_get_free_license',
            email: email,
            receive_updates: receive_updates,
            security: wdgpt_ajax_object.ajax_free_license_nonce
        };

        $.ajax({
            type: 'POST',
            url: wdgpt_ajax_object.ajax_url,
            data: data,
            success: free_license_handle_success,
            error: free_license_handle_error
        });
    });

    function free_license_handle_success(response) {
        $licenseStatus.removeClass('loading');

        if (response.success) {
            $licenseStatus.addClass('has-license').removeClass('no-license');
            $licenseStatus.html('<i class="fas fa-check"></i> ' + wdAdminTranslations.freeLicenseValid);
        } else {
            $licenseStatus.addClass('no-license').removeClass('has-license');
            $licenseStatus.html('<i class="fas fa-times"></i> ' + wdAdminTranslations.freeLicenseInvalid);
            console.error('Erreur lors de la récupération de la licence gratuite : ' + response.data.message);
        }
    }

    function free_license_handle_error(xhr, status, error) {
        console.error('Erreur lors de la requête AJAX : ' + error);
    }

    const $licenseKey = $('#license_key');
    const $licenseStatusPremium = $('#wd-premium-license-status');
    const $verifyPremiumLicense = $('#wd-premium-license-submit');
    

    $verifyPremiumLicense.on('click', function(event) {
        event.preventDefault();
        const licenseKey = $licenseKey.val().trim();
        $licenseStatusPremium.addClass('loading').removeClass('no-license has-license');
        $licenseStatusPremium.html('<i class="fas fa-spinner fa-spin"></i>');
        const data = {
            action: 'wdgpt_verify_license',
            license_key: licenseKey,
            security: wdgpt_ajax_object.ajax_verify_license_nonce
        };
        $.ajax({
            type: 'POST',
            url: wdgpt_ajax_object.ajax_url,
            data: data,
            success: premium_license_handle_success,
            error: premium_license_handle_error
        }); 
    });

    function premium_license_handle_success(response) {
        $licenseStatusPremium.removeClass('loading');

        if (response.success) {
            let message = '';
            switch (response.data.state) {
                case 'not_found':
                    message = wdAdminTranslations.premiumLicenseNotFound;
                    break;
                case 'verified_with_url':
                    message = wdAdminTranslations.premiumLicenseVerifiedWithUrl;
                    break;
                case 'already_registered_with_another_url':
                    message = wdAdminTranslations.premiumLicenseAlreadyRegisteredWithAnotherUrl;
                    break;
                case 'failed_to_register_url':
                    message = wdAdminTranslations.premiumLicenseFailedToRegisterUrl;
                    break;
                case 'verified':
                    message = wdAdminTranslations.premiumLicenseValid;
                    break;
                case 'failed_to_retrieve_expiry_date':
                    message = wdAdminTranslations.premiumLicenseFailedToRetrieveExpiryDate;
                    break;
                case 'expired':
                    message = wdAdminTranslations.premiumLicenseExpired;
                    break;
                default:
                    message = wdAdminTranslations.premiumLicenseInvalid;
                    break;
            }
            if (response.data.is_valid) {
                $licenseStatusPremium.html('<i class="fas fa-check"></i> ' + message);
                $licenseStatusPremium.addClass('has-license').removeClass('no-license');
            }
            else {
                $licenseStatusPremium.html('<i class="fas fa-times"></i> ' + message);
                $licenseStatusPremium.addClass('no-license').removeClass('has-license');
            }
        }
        else {
            $licenseStatusPremium.addClass('no-license').removeClass('has-license');
            $licenseStatusPremium.html('<i class="fas fa-times"></i> ' + wdAdminTranslations.premiumLicenseInvalid);
            console.error('Erreur lors de la vérification de la licence : ' + response.data.message);
        }
    }

    function premium_license_handle_error(xhr, status, error) {
        console.error('Erreur lors de la requête AJAX : ' + error);
    }
    
    const premium_license_key_input = document.getElementById('license_key');
    const premium_license_key_button = document.getElementById('verify_license');
    if (premium_license_key_input && premium_license_key_button) {
        premium_license_key_input.addEventListener('input', function() {
            const trimmedValue = premium_license_key_input.value.trim();
            if (trimmedValue.length > 0) {
                premium_license_key_button.disabled = false;
            } else {
                premium_license_key_button.disabled = true;
            }
        });
    }

    const addonLinks = document.querySelectorAll('.wdgpt-addons-cell.button-cell a');
    
    addonLinks.forEach(function(link) {
        link.addEventListener('click', function(event) {
            const action = link.getAttribute('data-action');
            if (action !== 'free_license' && action !== 'url') {
                const id = link.getAttribute('data-id');
                link.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                link.setAttribute('disabled', 'disabled');
                const data = {
                    action: 'wdgpt_'+action+'_addon',
                    id : id,
                    security: wdgpt_ajax_object['ajax_'+action+'_addon_nonce']
                };
                $.ajax({
                    type: 'POST',
                    url: wdgpt_ajax_object.ajax_url,
                    data: data,
                    success: function(response) {
                        let result = response.success ? 1 : 0;
                        window.location.href = window.location.href + '&' + action + '=' + result;
                        window.location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.log(error);
                    }
                });
            }
        });
    });
});
