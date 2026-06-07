{*
 * Waxap for PrestaShop — pestaña Conexión.
 *
 * El wizard de onboarding completo (registro / login / pago Stripe) se añade en DRAPPS-499.
 * Aquí se cubre el estado conectado y un formulario de conexión manual por API key.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
{if $waxap_is_connected}

    <div class="waxap-section-header">
        <h2>
            {l s='Servidor Waxap' d='Modules.Waxap.Admin'}
            <span class="wan-connection-badge wan-connection-badge--ok">{l s='Conectado' d='Modules.Waxap.Admin'}</span>
        </h2>
        <p>{l s='Tu tienda está conectada. Puedes actualizar tu API Key si es necesario.' d='Modules.Waxap.Admin'}</p>
    </div>

    <form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" autocomplete="off">
        <div class="wan-field-rows" style="max-width:620px;">
            <div class="wan-field-row">
                <label for="wan-api-key" class="wan-field-label">{l s='API Key' d='Modules.Waxap.Admin'}</label>
                <input type="text" id="wan-api-key" name="api_key"
                       value="{$waxap_api_key|escape:'html':'UTF-8'}"
                       class="wan-field-input" autocomplete="off">
            </div>
            {if $waxap_tenant_id}
            <div class="wan-field-row">
                <label class="wan-field-label">{l s='Tenant ID' d='Modules.Waxap.Admin'}</label>
                <input type="text" value="{$waxap_tenant_id|escape:'html':'UTF-8'}" class="wan-field-input" readonly>
                <input type="hidden" name="tenant_id" value="{$waxap_tenant_id|escape:'html':'UTF-8'}">
            </div>
            {/if}
        </div>
        <p class="wan-action-row">
            <button type="submit" name="submitWaxapConnection" class="btn btn-default waxap-btn-primary">
                {l s='Guardar cambios' d='Modules.Waxap.Admin'}
            </button>
        </p>
    </form>

    {* Tarjeta de uso/suscripción: se rellena en DRAPPS-500. *}
    {if isset($waxap_usage) && $waxap_usage}
        {include file="module:waxap/views/templates/admin/partials/usage-card.tpl"}
    {/if}

    <div class="wan-danger-zone">
        <h3>{l s='Desconectar' d='Modules.Waxap.Admin'}</h3>
        <p>{l s='Elimina las credenciales y desvincula la sesión WhatsApp. La tienda dejará de enviar notificaciones.' d='Modules.Waxap.Admin'}</p>
        <form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}"
              onsubmit="return confirm('{l s='¿Seguro? Se eliminarán las credenciales del módulo.' d='Modules.Waxap.Admin' js=1}');">
            <button type="submit" name="submitWaxapDisconnect" class="btn btn-default wan-btn-danger">
                {l s='Desconectar cuenta' d='Modules.Waxap.Admin'}
            </button>
        </form>
    </div>

{else}

    {* Estado desconectado: wizard de onboarding (registro → pago → activación) + login. *}
    <div class="wan-ob-steps-nav">
        <span class="wan-ob-step-dot {if $waxap_ob_step == '1'}active{else}done{/if}">1</span>
        <span class="wan-ob-step-line"></span>
        <span class="wan-ob-step-dot {if $waxap_ob_step == '2'}active{/if}">2</span>
        <span class="wan-ob-step-line"></span>
        <span class="wan-ob-step-dot">3</span>
    </div>

    {* PASO 1: crear cuenta / iniciar sesión *}
    <div id="wan-ob-step-1" class="wan-ob-step" {if $waxap_ob_step != '1'}style="display:none"{/if}>
        <div class="waxap-section-header">
            <h2>{l s='Crea tu cuenta Waxap' d='Modules.Waxap.Admin'}</h2>
            <p>{l s='Introduce tu email y una contraseña para registrarte. Después elegirás tu plan.' d='Modules.Waxap.Admin'}</p>
        </div>

        <form id="wan-ob-register-form" autocomplete="off">
            <div class="wan-field-rows" style="max-width:480px;">
                <div class="wan-field-row">
                    <label for="wan-ob-email" class="wan-field-label">{l s='Email' d='Modules.Waxap.Admin'}</label>
                    <input type="email" id="wan-ob-email" name="email" class="wan-field-input" required
                           placeholder="tienda@ejemplo.com" autocomplete="off">
                </div>
                <div class="wan-field-row">
                    <label for="wan-ob-password" class="wan-field-label">
                        {l s='Contraseña' d='Modules.Waxap.Admin'}
                        <span class="wan-field-hint">{l s='Mínimo 8 caracteres' d='Modules.Waxap.Admin'}</span>
                    </label>
                    <input type="password" id="wan-ob-password" name="password" class="wan-field-input" required
                           minlength="8" autocomplete="new-password">
                </div>
            </div>
            <p id="wan-ob-register-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin:10px 0 0;"></p>
            <p class="wan-action-row">
                <button type="submit" class="btn btn-default waxap-btn-primary">{l s='Crear cuenta' d='Modules.Waxap.Admin'}</button>
            </p>
        </form>

        <p style="margin-top:20px;border-top:1px solid #e5e7eb;padding-top:16px;">
            <a href="#" id="wan-toggle-login" style="font-size:13px;color:#666;">{l s='¿Ya tienes cuenta? Inicia sesión →' d='Modules.Waxap.Admin'}</a>
        </p>

        <div id="wan-login-form-wrap" style="display:none;">
            <form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" autocomplete="off">
                <div class="wan-field-rows" style="max-width:480px;">
                    <div class="wan-field-row">
                        <label for="wan-login-email" class="wan-field-label">{l s='Email' d='Modules.Waxap.Admin'}</label>
                        <input type="email" id="wan-login-email" name="email" class="wan-field-input" required autocomplete="off">
                    </div>
                    <div class="wan-field-row">
                        <label for="wan-login-password" class="wan-field-label">{l s='Contraseña' d='Modules.Waxap.Admin'}</label>
                        <input type="password" id="wan-login-password" name="password" class="wan-field-input" required minlength="8">
                    </div>
                </div>
                <p class="wan-action-row">
                    <button type="submit" name="submitWaxapLogin" class="btn btn-default waxap-btn-primary">{l s='Iniciar sesión' d='Modules.Waxap.Admin'}</button>
                </p>
            </form>
        </div>
    </div>

    {* PASO 2: elegir plan y pagar *}
    <div id="wan-ob-step-2" class="wan-ob-step" {if $waxap_ob_step != '2'}style="display:none"{/if}>
        <div class="waxap-section-header">
            <h2>{l s='Elige tu plan' d='Modules.Waxap.Admin'}</h2>
            <p>{l s='Selecciona el plan que mejor se adapta a tu tienda.' d='Modules.Waxap.Admin'}</p>
        </div>

        <div class="wan-plan-select-wrap">
            <select id="wan-plan-select" name="wan_plan" class="wan-plan-select">
                <option value="basic">⚡ {l s='Básico — 6 €/mes · 100 mensajes al mes' d='Modules.Waxap.Admin'}</option>
                <option value="pro">🚀 {l s='Pro — 12 €/mes · 200 mensajes al mes' d='Modules.Waxap.Admin'}</option>
                <option value="lifetime">✨ {l s='Vitalicio — 200 € pago único · Mensajes ilimitados' d='Modules.Waxap.Admin'}</option>
            </select>
            <p id="wan-plan-desc" class="wan-plan-desc-text"></p>
        </div>

        {if $waxap_payment_cancelled}
            <p class="wan-inline-notice wan-inline-notice--warning" style="margin-bottom:12px;">
                {l s='Pago cancelado. Puedes intentarlo de nuevo cuando quieras.' d='Modules.Waxap.Admin'}
            </p>
        {/if}

        <p id="wan-ob-pay-error" class="wan-inline-notice wan-inline-notice--error" style="display:none;margin:0 0 12px;"></p>

        <button id="wan-ob-pay-btn" class="btn btn-default waxap-btn-primary">{l s='Ir a pagar →' d='Modules.Waxap.Admin'}</button>

        <div id="wan-ob-polling-wrap" style="display:none;margin-top:20px;">
            <p id="wan-ob-polling-status" style="color:#666;font-style:italic;">{l s='Esperando confirmación del pago…' d='Modules.Waxap.Admin'}</p>
            <p style="font-size:12px;color:#999;margin-top:4px;">
                {l s='¿Ya pagaste pero no se activa?' d='Modules.Waxap.Admin'}
                <a href="#" id="wan-ob-already-paid">{l s='Verificar ahora' d='Modules.Waxap.Admin'}</a>
            </p>
        </div>

        <p style="margin-top:24px;border-top:1px solid #e5e7eb;padding-top:16px;">
            <form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" style="display:inline;">
                <button type="submit" name="submitWaxapCancelRegistration" class="btn btn-link" style="font-size:13px;color:#666;">
                    {l s='← Conectar con otra cuenta' d='Modules.Waxap.Admin'}
                </button>
            </form>
        </p>
    </div>

    {* PASO 3: activado *}
    <div id="wan-ob-step-3" class="wan-ob-step" style="display:none">
        <div class="waxap-section-header">
            <h2>{l s='¡Cuenta activada! 🎉' d='Modules.Waxap.Admin'}</h2>
            <p>{l s='Tu suscripción está activa. Ahora vincula tu número de WhatsApp para empezar a enviar notificaciones.' d='Modules.Waxap.Admin'}</p>
        </div>
        <a href="{$waxap_phone_tab_url|escape:'html':'UTF-8'}" class="btn btn-default waxap-btn-primary">
            {l s='Vincular número WhatsApp →' d='Modules.Waxap.Admin'}
        </a>
    </div>

    <script>
    (function () {
        var toggle = document.getElementById('wan-toggle-login');
        var wrap = document.getElementById('wan-login-form-wrap');
        var reg = document.getElementById('wan-ob-register-form');
        if (toggle && wrap) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                var show = wrap.style.display === 'none';
                wrap.style.display = show ? 'block' : 'none';
                if (reg) { reg.style.display = show ? 'none' : 'block'; }
            });
        }
    }());
    </script>

{/if}
