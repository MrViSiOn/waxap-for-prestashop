/* global jQuery, waxapSession */
/**
 * Waxap for PrestaShop — vinculación de sesión WhatsApp por QR.
 * Port de assets/js/admin-session.js (WooCommerce) adaptado al AdminController AJAX de PrestaShop.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 */
(function ($) {
    'use strict';

    var DATA = window.waxapSession || {};

    var WAN = {
        pollTimer: null,
        POLL_INTERVAL_MS: 2500,

        init: function () {
            $(document).on('click', '#wa-notifier-link-btn', this.onLinkClick.bind(this));
            $(document).on('click', '#wa-notifier-unlink-btn', this.onUnlink.bind(this));
            $(document).on('click', '#wa-notifier-modal-close, #wa-notifier-modal-overlay', this.onModalClose.bind(this));
            $(document).on('click', '#wa-notifier-modal-content', function (e) { e.stopPropagation(); });
            $(document).on('submit', '#wa-notifier-test-form', this.onTestSubmit.bind(this));
            $(document).on('click', '.wan-delete-session-btn', this.onDeleteSession.bind(this));

            $(document).on('keyup', function (e) {
                if (e.key === 'Escape') { WAN.onModalClose(); }
            });

            if (DATA.hasSession === '1' && $('#wa-notifier-modal').length) {
                this.refreshStatus();
            }
        },

        post: function (action, extra) {
            var payload = $.extend({ ajax: 1, action: action }, extra || {});
            return $.post(DATA.ajaxUrl, payload);
        },

        /* ---- Vinculación ---- */

        onLinkClick: function () {
            this.clearError('#wa-notifier-link-error');
            this.setLoading('#wa-notifier-link-btn', true);

            this.post('CreateSession')
                .done(function (res) {
                    WAN.setLoading('#wa-notifier-link-btn', false);
                    if (res.success) {
                        WAN.openModal();
                        WAN.startPolling();
                    } else {
                        WAN.showError('#wa-notifier-link-error', res.data.message);
                    }
                })
                .fail(function () {
                    WAN.setLoading('#wa-notifier-link-btn', false);
                    WAN.showError('#wa-notifier-link-error', 'Error de red.');
                });
        },

        /* ---- Polling ---- */

        refreshStatus: function () {
            this.post('PollSession').done(function (res) {
                if (!res.success) { return; }
                var data = res.data;
                WAN.updateStatusDisplay(data.status, data.phone);
                if (data.status === 'qr_ready' || data.status === 'initializing' || data.status === 'authenticating') {
                    WAN.openModal();
                    WAN.startPolling();
                }
            });
        },

        startPolling: function () {
            this.stopPolling();
            this.pollOnce();
            this.pollTimer = setInterval(function () { WAN.pollOnce(); }, WAN.POLL_INTERVAL_MS);
        },

        stopPolling: function () {
            if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }
        },

        pollOnce: function () {
            this.post('PollSession').done(function (res) {
                if (!res.success) { return; }
                var data = res.data;

                if (data.status === 'authenticating') {
                    $('#wa-notifier-qr-img').hide();
                    $('#wa-notifier-qr-loading').html('⏳ Verificando… un momento').show();
                }

                if (data.qr) {
                    $('#wa-notifier-qr-img').attr('src', data.qr).show();
                    $('#wa-notifier-qr-loading').hide();
                }

                if (data.status === 'ready') {
                    WAN.stopPolling();
                    WAN.onAuthenticated(data.phone);
                } else if (data.status === 'failed' || data.status === 'disconnected') {
                    WAN.stopPolling();
                    WAN.showError('#wa-notifier-modal-error', 'La sesión falló. Inténtalo de nuevo.');
                }

                if ($('#wa-notifier-modal').is(':hidden')) {
                    WAN.updateStatusDisplay(data.status, data.phone);
                }
            });
        },

        onAuthenticated: function () {
            this.closeModal();
            location.reload();
        },

        /* ---- Estado ---- */

        updateStatusDisplay: function (status, phone) {
            var labels = {
                created: '⏳ Sesión creada, arrancando…',
                initializing: '⏳ Iniciando…',
                qr_ready: '📱 Esperando escaneo del QR…',
                authenticating: '🔐 Autenticando…',
                ready: '✅ Conectado',
                disconnected: '❌ Desconectado',
                failed: '❌ Error en la sesión'
            };
            $('#wa-notifier-status-text').text(labels[status] || ('⏳ ' + status));

            if (status === 'ready' && phone) {
                var formatted = phone.charAt(0) === '+' ? phone : '+' + phone;
                $('#wan-phone-display-number').text(formatted);
                $('#wan-phone-display').show();
            } else {
                $('#wan-phone-display').hide();
            }

            $('#wa-notifier-status-dot')
                .removeClass('wan-green wan-red wan-yellow')
                .addClass(status === 'ready' ? 'wan-green' : (status === 'disconnected' || status === 'failed') ? 'wan-red' : 'wan-yellow');

            if (status === 'disconnected' || status === 'failed') {
                $('#wa-notifier-link-btn').show();
                $('#wa-notifier-unlink-btn').hide();
            } else if (status === 'ready') {
                $('#wa-notifier-link-btn').hide();
                $('#wa-notifier-unlink-btn').show();
            }

            $('#wa-notifier-test-wrap').toggle(status === 'ready');
        },

        /* ---- Mensaje de prueba ---- */

        onTestSubmit: function (e) {
            e.preventDefault();
            var $form = $(e.target);
            var to = $.trim($form.find('[name=to]').val());
            if (!to) {
                WAN.showTestResult('error', 'Introduce un número de teléfono.');
                return;
            }
            this.setLoading($form, true);
            this.clearTestResult();

            this.post('SendTest', { to: to })
                .done(function (res) {
                    WAN.setLoading($form, false);
                    if (res.success) {
                        WAN.showTestResult('success', '✅ Mensaje enviado correctamente. Comprueba el teléfono.');
                    } else {
                        WAN.showTestResult('error', res.data && res.data.message ? res.data.message : 'Error al enviar el mensaje.');
                    }
                })
                .fail(function () {
                    WAN.setLoading($form, false);
                    WAN.showTestResult('error', 'Error de red. Comprueba la conexión con el servidor.');
                });
        },

        showTestResult: function (type, message) {
            $('#wa-notifier-test-result')
                .removeClass('wan-inline-notice--error wan-inline-notice--warning')
                .addClass('wan-inline-notice')
                .addClass(type === 'success' ? '' : 'wan-inline-notice--error')
                .text(message).show();
        },

        clearTestResult: function () { $('#wa-notifier-test-result').text('').hide(); },

        /* ---- Desvincular ---- */

        onUnlink: function () {
            if (!window.confirm(DATA.confirmUnlink)) { return; }
            this.post('Disconnect').always(function () { location.reload(); });
        },

        onDeleteSession: function (e) {
            var btn = e.currentTarget;
            if (!window.confirm(DATA.confirmDeleteSession)) { return; }
            var sid = btn.getAttribute('data-session-id');
            btn.disabled = true;
            this.post('DeleteSession', { session_id: sid }).done(function (res) {
                if (res.success) {
                    $('[data-session-row="' + sid + '"]').remove();
                } else {
                    window.alert(res.data && res.data.message ? res.data.message : 'Error al desvincular.');
                    btn.disabled = false;
                }
            }).fail(function () {
                window.alert('Error de conexión.');
                btn.disabled = false;
            });
        },

        /* ---- Modal ---- */

        openModal: function () {
            var $modal = $('#wa-notifier-modal');
            if (!$modal.length) { return; }
            $('#wa-notifier-qr-img').attr('src', '').hide();
            $('#wa-notifier-qr-loading').html('Generando QR…').show();
            this.clearError('#wa-notifier-modal-error');
            $modal.attr('aria-hidden', 'false').show();
            $('body').css('overflow', 'hidden');
        },

        closeModal: function () {
            this.stopPolling();
            $('#wa-notifier-modal').attr('aria-hidden', 'true').hide();
            $('body').css('overflow', '');
        },

        onModalClose: function () { this.closeModal(); },

        /* ---- Helpers ---- */

        showError: function (selector, message) { $(selector).text(message).show(); },
        clearError: function (selector) { $(selector).text('').hide(); },
        setLoading: function (target, loading) {
            var $target = typeof target === 'string' ? $(target) : target;
            $target.prop('disabled', loading);
            $target.closest('form, p').find('.wa-notifier-spinner').toggleClass('is-active', loading);
        }
    };

    $(function () {
        if (typeof waxapSession !== 'undefined') { WAN.init(); }
    });

})(jQuery);
