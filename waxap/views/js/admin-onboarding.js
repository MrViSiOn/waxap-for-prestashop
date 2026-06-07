/* global jQuery, waxapOnboarding */
/**
 * Waxap for PrestaShop — wizard de onboarding (registro + activación Stripe).
 * Port de assets/js/admin-onboarding.js (WooCommerce) adaptado al AdminController AJAX de PrestaShop.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 */
(function ($) {
    'use strict';

    var OB = {
        data: window.waxapOnboarding || {},
        pollTimer: null,
        pollCount: 0,
        MAX_POLLS: 72, // 6 minutos a intervalos de 5s

        planDescs: {
            basic: 'Ideal para tiendas pequeñas. Hasta 100 WhatsApps al mes. Puedes cancelar cuando quieras.',
            pro: 'Para tiendas con más volumen. Hasta 200 WhatsApps al mes. Puedes cancelar cuando quieras.',
            lifetime: 'Un solo pago, sin cuotas mensuales. Mensajes ilimitados de por vida.'
        },

        post: function (action, extra) {
            return $.post(this.data.ajaxUrl, $.extend({ ajax: 1, action: action }, extra || {}));
        },

        init: function () {
            $('#wan-ob-register-form').on('submit', this.onRegister.bind(this));
            $(document).on('click', '#wan-ob-pay-btn', this.onPayClick.bind(this));
            $(document).on('click', '#wan-ob-already-paid', this.onAlreadyPaid.bind(this));
            $(document).on('change', '#wan-plan-select', this.onPlanChange.bind(this));
            this.onPlanChange();

            if (this.data.step === '2') {
                this.showStep(2);
            }

            if (this.data.paymentReturned === '1' && this.data.step === '2') {
                $('#wan-ob-pay-btn').hide();
                $('#wan-ob-pay-error').hide();
                $('#wan-ob-polling-wrap').show();
                this.startPolling();
            }
        },

        /* ---- Paso 1: registro ---- */

        onRegister: function (e) {
            e.preventDefault();
            var $form = $(e.target);
            var $btn = $form.find('[type=submit]');
            var $err = $('#wan-ob-register-error');

            $err.hide().text('');
            $btn.prop('disabled', true).text('Creando cuenta…');

            this.post('Register', {
                email: $form.find('[name=email]').val(),
                password: $form.find('[name=password]').val()
            })
                .done(function (res) {
                    if (!res.success) {
                        $err.text(res.data.message).show();
                        $btn.prop('disabled', false).text('Crear cuenta');
                        return;
                    }
                    OB.showStep(2);
                })
                .fail(function () {
                    $err.text('Error de red. Comprueba tu conexión e inténtalo de nuevo.').show();
                    $btn.prop('disabled', false).text('Crear cuenta');
                });
        },

        /* ---- Paso 2: pago ---- */

        onPlanChange: function () {
            var plan = $('#wan-plan-select').val() || 'basic';
            $('#wan-plan-desc').text(this.planDescs[plan] || '');
        },

        onPayClick: function () {
            var $btn = $('#wan-ob-pay-btn');
            var plan = $('#wan-plan-select').val() || 'basic';
            $btn.prop('disabled', true).text('Preparando enlace de pago…');
            $('#wan-ob-pay-error').hide().text('');

            this.post('CheckoutUrl', { plan: plan })
                .done(function (res) {
                    if (!res.success) {
                        $('#wan-ob-pay-error').text(res.data.message).show();
                        $btn.prop('disabled', false).text('Ir a pagar →');
                        return;
                    }
                    window.open(res.data.url, '_blank');
                    $btn.text('Pago abierto en nueva pestaña ↗');
                    $('#wan-ob-polling-wrap').show();
                    OB.startPolling();
                })
                .fail(function () {
                    $('#wan-ob-pay-error').text('Error de red. Inténtalo de nuevo.').show();
                    $btn.prop('disabled', false).text('Ir a pagar →');
                });
        },

        onAlreadyPaid: function (e) {
            if (e) { e.preventDefault(); }
            $('#wan-ob-polling-wrap').show();
            OB.startPolling();
        },

        /* ---- Polling de activación ---- */

        startPolling: function () {
            if (this.pollTimer) { return; }
            this.pollCount = 0;
            $('#wan-ob-polling-status').text('Esperando confirmación del pago…');
            this.pollTimer = setInterval(function () { OB.pollOnce(); }, 5000);
            this.pollOnce();
        },

        stopPolling: function () {
            if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }
        },

        pollOnce: function () {
            OB.pollCount++;
            if (OB.pollCount > OB.MAX_POLLS) {
                OB.stopPolling();
                $('#wan-ob-polling-status').text('El tiempo de espera expiró. Si completaste el pago, recarga esta página.');
                return;
            }
            OB.post('PollActivation').done(function (res) {
                if (!res.success) { return; }
                if (res.data.status === 'active') {
                    OB.stopPolling();
                    OB.showStep(3);
                }
            });
        },

        showStep: function (n) {
            $('.wan-ob-step').hide();
            $('#wan-ob-step-' + n).show();
        }
    };

    $(function () {
        if ($('#wan-ob-step-1').length) { OB.init(); }
    });

})(jQuery);
