/**
 * TG Course Bot PRO - Admin JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // Copy webhook URL
        $('#tgcb-copy-webhook').on('click', function () {
            const webhookInput = $(this).closest('td').find('input[readonly]');
            webhookInput.select();
            document.execCommand('copy');

            const btn = $(this);
            const originalText = btn.text();
            btn.text('✓ Copied!');

            setTimeout(function () {
                btn.text(originalText);
            }, 2000);
        });

        // Setup webhook
        $('#tgcb-setup-webhook').on('click', function () {
            const btn = $(this);
            const originalText = btn.html();

            btn.prop('disabled', true).html('<span class="tgcb-loading"></span> Setting up...');

            $.ajax({
                url: tgcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tgcb_setup_webhook',
                    nonce: tgcbAdmin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        location.reload();
                    } else {
                        alert('❌ ' + response.data);
                    }
                },
                error: function () {
                    alert('❌ Connection error');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Send test message
        $('#tgcb-send-test').on('click', function () {
            const message = $('#tgcb-test-message').val();

            if (!message) {
                alert('Please enter a message');
                return;
            }

            const btn = $(this);
            const originalText = btn.html();
            const resultDiv = $('#tgcb-test-result');

            btn.prop('disabled', true).html('<span class="tgcb-loading"></span> Sending...');
            resultDiv.removeClass('success error').html('');

            $.ajax({
                url: tgcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tgcb_send_test',
                    nonce: tgcbAdmin.nonce,
                    message: message
                },
                success: function (response) {
                    if (response.success) {
                        resultDiv.addClass('success').html('✅ ' + response.data);
                    } else {
                        resultDiv.addClass('error').html('❌ ' + response.data);
                    }
                },
                error: function () {
                    resultDiv.addClass('error').html('❌ Connection error');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Approve payment
        $(document).on('click', '.tgcb-approve-payment', function () {
            if (!confirm('Are you sure you want to approve this payment?')) {
                return;
            }

            const paymentId = $(this).data('payment-id');
            const btn = $(this);
            const originalText = btn.html();

            btn.prop('disabled', true).html('<span class="tgcb-loading"></span> Processing...');

            $.ajax({
                url: tgcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tgcb_approve_payment',
                    nonce: tgcbAdmin.nonce,
                    payment_id: paymentId
                },
                success: function (response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        location.reload();
                    } else {
                        alert('❌ ' + response.data);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function () {
                    alert('❌ Connection error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Reject payment
        $(document).on('click', '.tgcb-reject-payment', function () {
            if (!confirm('Are you sure you want to reject this payment?')) {
                return;
            }

            const paymentId = $(this).data('payment-id');
            const btn = $(this);
            const originalText = btn.html();

            btn.prop('disabled', true).html('<span class="tgcb-loading"></span> Processing...');

            $.ajax({
                url: tgcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tgcb_reject_payment',
                    nonce: tgcbAdmin.nonce,
                    payment_id: paymentId
                },
                success: function (response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        location.reload();
                    } else {
                        alert('❌ ' + response.data);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function () {
                    alert('❌ Connection error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Resend Invite Link
        $(document).on('click', '.tgcb-resend-invite', function (e) {
            e.preventDefault();

            if (!confirm('Send a new invite link to this user?')) {
                return;
            }

            const tgId = $(this).data('tg-id');
            const courseId = $(this).data('course-id');
            const btn = $(this);
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="tgcb-loading"></span>');

            $.ajax({
                url: tgcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tgcb_resend_invite',
                    nonce: tgcbAdmin.nonce,
                    tg_id: tgId,
                    course_id: courseId
                },
                success: function (response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                    } else {
                        alert('❌ ' + response.data);
                    }
                },
                error: function () {
                    alert('❌ Connection error');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

    });

})(jQuery);
