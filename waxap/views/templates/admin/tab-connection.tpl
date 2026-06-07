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

    {* Estado desconectado: conexión manual. El onboarding guiado llega en DRAPPS-499. *}
    <div class="waxap-section-header">
        <h2>{l s='Conecta tu tienda con Waxap' d='Modules.Waxap.Admin'}</h2>
        <p>{l s='Introduce la URL del servidor y tu API Key para conectar la tienda.' d='Modules.Waxap.Admin'}</p>
    </div>

    <form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" autocomplete="off">
        <div class="wan-field-rows" style="max-width:620px;">
            <div class="wan-field-row">
                <label for="wan-wrapper-url" class="wan-field-label">{l s='URL del servidor Waxap' d='Modules.Waxap.Admin'}</label>
                <input type="url" id="wan-wrapper-url" name="wrapper_url"
                       value="{$waxap_wrapper_url|escape:'html':'UTF-8'}"
                       class="wan-field-input" placeholder="https://api.waxap.shop">
            </div>
            <div class="wan-field-row">
                <label for="wan-api-key" class="wan-field-label">{l s='API Key' d='Modules.Waxap.Admin'}</label>
                <input type="text" id="wan-api-key" name="api_key" value="" class="wan-field-input" autocomplete="off">
            </div>
            <div class="wan-field-row">
                <label for="wan-tenant-id" class="wan-field-label">{l s='Tenant ID' d='Modules.Waxap.Admin'}</label>
                <input type="text" id="wan-tenant-id" name="tenant_id" value="" class="wan-field-input" autocomplete="off">
            </div>
        </div>
        <p class="wan-action-row">
            <button type="submit" name="submitWaxapConnection" class="btn btn-default waxap-btn-primary">
                {l s='Conectar' d='Modules.Waxap.Admin'}
            </button>
        </p>
    </form>

{/if}
