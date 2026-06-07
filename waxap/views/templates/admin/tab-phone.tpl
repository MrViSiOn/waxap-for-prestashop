{*
 * Waxap for PrestaShop — pestaña Número WhatsApp (vinculación QR + estado + prueba).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
{if !$waxap_has_session}

    <div class="waxap-section-header">
        <h2>{l s='Vincula tu número WhatsApp' d='Modules.Waxap.Admin'}</h2>
        <p>{l s='Tu tienda está conectada al servidor. Escanea el código QR con WhatsApp para vincular tu número.' d='Modules.Waxap.Admin'}</p>
    </div>

    <p class="wan-action-row">
        <button type="button" class="btn btn-default waxap-btn-primary" id="wa-notifier-link-btn">
            {l s='Vincular WhatsApp' d='Modules.Waxap.Admin'}
        </button>
        <span class="wa-notifier-spinner"></span>
    </p>
    <p id="wa-notifier-link-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin-top:8px;"></p>

{else}

    <div class="waxap-section-header">
        <h2>{l s='Número WhatsApp' d='Modules.Waxap.Admin'}</h2>
    </div>

    <div class="wan-phone-display" id="wan-phone-display" {if !$waxap_display_phone}style="display:none;"{/if}>
        <span class="wan-phone-display-icon">📱</span>
        <div>
            <span class="wan-phone-display-label">{l s='Número vinculado' d='Modules.Waxap.Admin'}</span>
            <strong class="wan-phone-display-number" id="wan-phone-display-number">{$waxap_display_phone|escape:'html':'UTF-8'}</strong>
        </div>
    </div>

    <div id="wa-notifier-status-wrap" class="wan-session-status-card">
        <span class="wa-notifier-status-dot" id="wa-notifier-status-dot"></span>
        <span id="wa-notifier-status-text">{l s='Comprobando…' d='Modules.Waxap.Admin'}</span>
    </div>

    <p class="wan-action-row">
        <button type="button" class="btn btn-default waxap-btn-primary" id="wa-notifier-link-btn" style="display:none;">
            {l s='Volver a vincular' d='Modules.Waxap.Admin'}
        </button>
        <span class="wa-notifier-spinner"></span>
    </p>
    <p style="margin-top:8px;">
        <button type="button" class="btn btn-default wan-btn-outline-danger" id="wa-notifier-unlink-btn">
            {l s='Desvincular número' d='Modules.Waxap.Admin'}
        </button>
    </p>
    <p id="wa-notifier-link-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin-top:8px;"></p>

    {if $waxap_sessions}
        <div style="margin-top:28px;">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:12px;">{l s='Sesiones WhatsApp' d='Modules.Waxap.Admin'}</h3>
            <div style="display:flex;flex-direction:column;gap:10px;max-width:620px;">
                {foreach from=$waxap_sessions item=session}
                    <div data-session-row="{$session.id|escape:'html':'UTF-8'}"
                         style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid #e5e7eb;border-radius:8px;">
                        <span style="font-size:13px;font-weight:600;">{$session.phone|escape:'html':'UTF-8'}</span>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:11px;color:#6b7280;">{$session.status|escape:'html':'UTF-8'}</span>
                            <button type="button" class="btn btn-default wan-delete-session-btn"
                                    data-session-id="{$session.id|escape:'html':'UTF-8'}"
                                    style="padding:2px 10px;font-size:12px;color:#dc2626;border-color:#fca5a5;">
                                {l s='Desvincular' d='Modules.Waxap.Admin'}
                            </button>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    {/if}

    <div id="wa-notifier-test-wrap" style="display:none;margin-top:28px;">
        <div class="waxap-section-header">
            <h2>{l s='Mensaje de prueba' d='Modules.Waxap.Admin'}</h2>
            <p>{l s='Verifica que la conexión funciona enviando un mensaje a cualquier número WhatsApp.' d='Modules.Waxap.Admin'}</p>
        </div>

        <form id="wa-notifier-test-form" autocomplete="off">
            <div class="wan-field-rows" style="max-width:480px;">
                <div class="wan-field-row">
                    <label for="wan-test-phone" class="wan-field-label">
                        {l s='Número de teléfono' d='Modules.Waxap.Admin'}
                        <span class="wan-field-hint">{l s='Formato internacional: +34612345678' d='Modules.Waxap.Admin'}</span>
                    </label>
                    <input type="tel" id="wan-test-phone" name="to" placeholder="+34612345678"
                           class="wan-field-input" autocomplete="off" required>
                </div>
            </div>
            <p class="wan-action-row">
                <button type="submit" class="btn btn-default waxap-btn-primary" id="wa-notifier-test-btn">
                    {l s='Enviar mensaje de prueba' d='Modules.Waxap.Admin'}
                </button>
                <span class="wa-notifier-spinner"></span>
            </p>
        </form>
        <p id="wa-notifier-test-result" class="wan-inline-notice" style="display:none;margin-top:8px;"></p>
    </div>

{/if}

{* Modal de escaneo QR *}
<div id="wa-notifier-modal" aria-hidden="true" role="dialog" aria-modal="true" style="display:none;">
    <div id="wa-notifier-modal-overlay">
        <div id="wa-notifier-modal-content" class="wa-notifier-modal-box">
            <button type="button" id="wa-notifier-modal-close" aria-label="{l s='Cerrar' d='Modules.Waxap.Admin' js=1}">&times;</button>
            <h2>{l s='Escanea el QR con WhatsApp' d='Modules.Waxap.Admin'}</h2>
            <div id="wa-notifier-qr-wrap">
                <img id="wa-notifier-qr-img" src="" alt="QR WhatsApp" />
                <p id="wa-notifier-qr-loading">{l s='Generando QR…' d='Modules.Waxap.Admin'}</p>
            </div>
            <p class="wan-field-hint" style="margin-top:12px;">
                {l s='WhatsApp → Dispositivos vinculados → Vincular un dispositivo' d='Modules.Waxap.Admin'}
            </p>
            <p id="wa-notifier-modal-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin-top:8px;"></p>
        </div>
    </div>
</div>
