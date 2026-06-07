/* global jQuery, waxapInbox */
/**
 * Waxap for PrestaShop — bandeja de entrada WhatsApp.
 * Port de assets/js/admin-inbox.js (WooCommerce) adaptado al AdminController AJAX de PrestaShop.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 */
(function ($) {
    'use strict';

    var DATA = window.waxapInbox || {};

    var App = {
        currentPhone: null,
        pollTimer: null,
        POLL_INTERVAL: 5000,

        post: function (action, extra) {
            return $.post(DATA.ajaxUrl, $.extend({ ajax: 1, action: action }, extra || {}));
        },

        init: function () {
            this.bindEvents();
            this.startPolling();
        },

        bindEvents: function () {
            var self = this;
            $(document).on('click', '.waxap-conv-item', function () {
                self.openThread($(this).data('phone'));
            });
            $('#waxap-send-btn').on('click', function () { self.sendMessage(); });
            $('#waxap-send-text').on('keydown', function (e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { self.sendMessage(); }
            });
        },

        startPolling: function () {
            var self = this;
            self.pollTimer = setInterval(function () {
                if (document.visibilityState === 'hidden') { return; }
                self.pollConversations();
                if (self.currentPhone) { self.pollThread(self.currentPhone); }
            }, self.POLL_INTERVAL);
        },

        pollConversations: function () {
            var self = this;
            this.post('InboxConversations').done(function (res) {
                if (res.success) { self.renderConversations(res.data); }
            });
        },

        pollThread: function (phone) {
            var self = this;
            this.post('InboxThread', { phone: phone }).done(function (res) {
                if (res.success && self.currentPhone === phone) { self.renderMessages(res.data); }
            });
        },

        openThread: function (phone) {
            var self = this;
            self.currentPhone = phone;
            $('.waxap-conv-item').removeClass('active');
            $('.waxap-conv-item[data-phone="' + phone + '"]').addClass('active');
            $('#waxap-thread-header-phone').text('+' + String(phone).replace(/@[a-z.]+$/i, ''));
            $('#waxap-inbox-empty').hide();
            $('#waxap-thread').css('display', 'flex');
            $('#waxap-thread-messages').html('<div class="waxap-thread-loading">Cargando...</div>');

            this.post('InboxThread', { phone: phone }).done(function (res) {
                if (res.success) {
                    self.renderMessages(res.data);
                    self.scrollToBottom($('#waxap-thread-messages'));
                    self.markRead(phone);
                } else {
                    $('#waxap-thread-messages').html('<div class="waxap-thread-error">Error al cargar mensajes.</div>');
                }
            });
        },

        markRead: function (phone) {
            $('.waxap-conv-item[data-phone="' + phone + '"] .waxap-unread-badge').remove();
            this.post('InboxRead', { phone: phone });
        },

        sendMessage: function () {
            var self = this;
            var $textarea = $('#waxap-send-text');
            var text = $.trim($textarea.val());
            if (!text || !self.currentPhone) { return; }
            var $btn = $('#waxap-send-btn');
            $btn.prop('disabled', true).text('...');

            this.post('InboxSend', { phone: self.currentPhone, text: text }).done(function (res) {
                if (res.success) {
                    $textarea.val('');
                    self.appendMessage({ direction: 'outbound', body: text, createdAt: new Date().toISOString() });
                } else {
                    window.alert(res.data && res.data.message ? res.data.message : 'Error al enviar el mensaje.');
                }
            }).always(function () {
                $btn.prop('disabled', false).text('Enviar');
                $textarea.focus();
            });
        },

        renderConversations: function (conversations) {
            var self = this;
            var $list = $('#waxap-conv-list');
            if (!conversations || !conversations.length) {
                $list.html('<div class="waxap-conv-empty">Sin conversaciones todavía.</div>');
                return;
            }
            var html = '';
            $.each(conversations, function (i, conv) {
                var isActive = self.currentPhone === conv.phone ? ' active' : '';
                var preview = conv.lastMessage && conv.lastMessage.body
                    ? conv.lastMessage.body.substring(0, 45) + (conv.lastMessage.body.length > 45 ? '…' : '')
                    : '—';
                var badge = conv.unreadCount > 0 && self.currentPhone !== conv.phone
                    ? '<span class="waxap-unread-badge">' + conv.unreadCount + '</span>' : '';
                var initial = (conv.phone || '?').charAt(0);
                var label = '+' + String(conv.displayPhone || conv.phone).replace(/@[a-z.]+$/i, '');
                html += '<div class="waxap-conv-item' + isActive + '" data-phone="' + self.escAttr(conv.phone) + '">';
                html += '<div class="waxap-conv-avatar">' + self.escHtml(initial) + '</div>';
                html += '<div class="waxap-conv-info">';
                html += '<div class="waxap-conv-phone">' + self.escHtml(label) + '</div>';
                html += '<div class="waxap-conv-preview">' + self.escHtml(preview) + '</div>';
                html += '</div>';
                html += '<div class="waxap-conv-meta">' + badge + '</div>';
                html += '</div>';
            });
            $list.html(html);
        },

        isScrolledToBottom: function ($el) {
            var el = $el[0];
            return el.scrollHeight - el.scrollTop - el.clientHeight < 60;
        },

        scrollToBottom: function ($el) {
            $el.scrollTop($el[0].scrollHeight);
        },

        renderMessages: function (messages) {
            var self = this;
            var $c = $('#waxap-thread-messages');
            if (!messages || !messages.length) {
                $c.html('<div class="waxap-thread-empty-msgs">Sin mensajes aún.</div>');
                return;
            }
            var wasAtBottom = self.isScrolledToBottom($c);
            var html = '';
            $.each(messages, function (i, msg) {
                var dir = msg.direction === 'outbound' ? 'outbound' : 'inbound';
                html += '<div class="waxap-msg waxap-msg-' + dir + '">';
                html += self.escHtml(msg.body || '');
                html += '<div class="waxap-msg-time">' + self.escHtml(self.formatTime(msg.createdAt)) + '</div>';
                html += '</div>';
            });
            $c.html(html);
            if (wasAtBottom) { self.scrollToBottom($c); }
        },

        appendMessage: function (msg) {
            var self = this;
            var $c = $('#waxap-thread-messages');
            var dir = msg.direction === 'outbound' ? 'outbound' : 'inbound';
            var $msg = $('<div class="waxap-msg waxap-msg-' + dir + '">');
            $msg.text(msg.body || '');
            $msg.append('<div class="waxap-msg-time">' + self.escHtml(self.formatTime(msg.createdAt)) + '</div>');
            $c.append($msg);
            self.scrollToBottom($c);
        },

        formatTime: function (iso) {
            if (!iso) { return ''; }
            try {
                var d = new Date(iso);
                var now = new Date();
                if (d.toDateString() === now.toDateString()) {
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                return d.toLocaleDateString([], { day: '2-digit', month: '2-digit' });
            } catch (e) { return ''; }
        },

        escHtml: function (str) { return $('<div>').text(String(str)).html(); },
        escAttr: function (str) { return String(str).replace(/"/g, '&quot;'); }
    };

    $(function () {
        if ($('#waxap-inbox').length) { App.init(); }
    });

})(jQuery);
