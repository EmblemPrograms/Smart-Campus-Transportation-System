/**
 * Auth Pages JavaScript
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Form validation, role-field toggle, password strength, submit spinner.
 */

(function ($) {
    'use strict';

    /**
     * Toggle student / staff field visibility based on role select.
     */
    function toggleRoleFields() {
        var role = $('#role').val();
        if (!role) {
            return;
        }

        $('.role-field').addClass('d-none');
        $('.role-' + role).removeClass('d-none');

        // Required attributes for visible role fields
        if (role === 'student') {
            $('#matric_number, #level, #faculty').attr('required', true);
            $('#staff_id, #position').removeAttr('required').val('');
        } else if (role === 'staff') {
            $('#staff_id, #position').attr('required', true);
            $('#matric_number, #level, #faculty').removeAttr('required').val('');
        }
    }

    /**
     * Evaluate password strength and update meter UI.
     *
     * @param {string} password
     */
    function updatePasswordStrength(password) {
        var $wrap = $('#passwordStrength');
        if (!$wrap.length) {
            return;
        }

        var score = 0;
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        var pct = 0;
        var label = 'Password strength';
        var cls = '';

        if (password.length === 0) {
            pct = 0;
        } else if (score <= 2) {
            pct = 25;
            label = 'Weak';
            cls = 'strength-weak';
        } else if (score <= 3) {
            pct = 50;
            label = 'Fair';
            cls = 'strength-fair';
        } else if (score <= 4) {
            pct = 75;
            label = 'Good';
            cls = 'strength-good';
        } else {
            pct = 100;
            label = 'Strong';
            cls = 'strength-strong';
        }

        var $bar = $wrap.find('.progress-bar');
        $bar.css('width', pct + '%')
            .removeClass('strength-weak strength-fair strength-good strength-strong')
            .addClass(cls);
        $wrap.find('.strength-text').text(label);
    }

    /**
     * Client-side password policy check (mirrors server validatePassword).
     *
     * @param {string} password
     * @returns {boolean}
     */
    function isPasswordValid(password) {
        return password.length >= 8
            && /[A-Z]/.test(password)
            && /[a-z]/.test(password)
            && /[0-9]/.test(password);
    }

    /**
     * Validate a single auth form. Returns true if valid.
     *
     * @param {JQuery} $form
     * @returns {boolean}
     */
    function validateForm($form) {
        var valid = true;
        $form.find('.is-invalid').removeClass('is-invalid');

        $form.find('[required]:visible').each(function () {
            var $el = $(this);
            var value = $.trim($el.val() || '');

            if ($el.attr('type') === 'email') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    $el.addClass('is-invalid');
                    valid = false;
                }
            } else if ($el.is('select')) {
                if (value === '') {
                    $el.addClass('is-invalid');
                    valid = false;
                }
            } else if (value === '') {
                $el.addClass('is-invalid');
                valid = false;
            }
        });

        var $password = $form.find('#password');
        if ($password.length && $password.is(':visible') && $password.val()) {
            if (!isPasswordValid($password.val())) {
                $password.addClass('is-invalid');
                valid = false;
            }
        }

        var $confirm = $form.find('#confirm_password');
        if ($confirm.length && $password.length) {
            if ($confirm.val() !== $password.val()) {
                $confirm.addClass('is-invalid');
                valid = false;
            }
        }

        return valid;
    }

    /**
     * Show loading spinner on submit button.
     *
     * @param {JQuery} $btn
     */
    function showSpinner($btn) {
        $btn.prop('disabled', true);
        $btn.find('.btn-text').addClass('d-none');
        $btn.find('.btn-spinner').removeClass('d-none');
    }

    $(function () {
        // Role toggle on register page
        if ($('#role').length) {
            toggleRoleFields();
            $('#role').on('change', toggleRoleFields);
        }

        // Password strength meter
        $('#password').on('input', function () {
            updatePasswordStrength($(this).val() || '');
        });

        // Show / hide password
        $(document).on('click', '.toggle-password', function () {
            var targetId = $(this).data('target');
            var $input = $('#' + targetId);
            var $icon = $(this).find('i');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });

        // Form submit: validate + spinner
        $('.auth-form').on('submit', function (e) {
            var $form = $(this);
            var $btn = $form.find('#submitBtn');

            if (!validateForm($form)) {
                e.preventDefault();
                e.stopPropagation();
                $form.addClass('was-validated');
                return false;
            }

            showSpinner($btn);
            return true;
        });

        // Clear invalid state on input
        $('.auth-form').on('input change', 'input, select', function () {
            $(this).removeClass('is-invalid');
        });
    });
})(jQuery);
