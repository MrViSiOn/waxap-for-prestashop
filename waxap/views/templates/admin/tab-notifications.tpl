{*
 * Waxap for PrestaShop — pestaña Notificaciones (selector de estados).
 *
 * Los editores de plantilla por estado, las variables y el prefijo de país se añaden
 * en DRAPPS-496. Aquí está el selector dinámico de estados de pedido (OrderState).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
<div class="waxap-section-header">
    <h2>{l s='¿En qué momentos avisas a tus clientes?' d='Modules.Waxap.Admin'}</h2>
    <p>{l s='Activa los estados de pedido que enviarán un WhatsApp a tus clientes.' d='Modules.Waxap.Admin'}</p>
</div>

<form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" autocomplete="off">

    <div class="wan-status-list">
        {foreach from=$waxap_states item=state}
            <div class="wan-status-item">
                <div class="wan-status-card-row">
                    <label class="wan-status-card" for="wan-status-{$state.id|escape:'html':'UTF-8'}">
                        <span class="wan-status-dot-indicator" style="background-color: {$state.color|escape:'html':'UTF-8'};"></span>
                        <div class="wan-status-info">
                            <strong>{$state.label|escape:'html':'UTF-8'}</strong>
                        </div>
                        <div class="wan-toggle">
                            <input type="checkbox"
                                   id="wan-status-{$state.id|escape:'html':'UTF-8'}"
                                   name="waxap_notify_statuses[]"
                                   value="{$state.id|escape:'html':'UTF-8'}"
                                   class="wan-toggle-input"
                                   {if $state.enabled}checked{/if}>
                            <span class="wan-toggle-track"></span>
                            <span class="wan-toggle-thumb"></span>
                        </div>
                    </label>
                </div>
            </div>
        {/foreach}
    </div>

    <p class="wan-action-row">
        <button type="submit" name="submitWaxapNotifications" class="btn btn-default waxap-btn-primary">
            {l s='Guardar cambios' d='Modules.Waxap.Admin'}
        </button>
    </p>
</form>
