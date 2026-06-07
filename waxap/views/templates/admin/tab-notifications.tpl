{*
 * Waxap for PrestaShop — pestaña Notificaciones.
 *
 * Selector dinámico de estados (OrderState) + plantilla de mensaje por estado con variables
 * {nombre} {pedido} {estado} {total} {enlace} + prefijo de país por defecto.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
<div class="waxap-section-header">
    <h2>{l s='¿En qué momentos avisas a tus clientes?' d='Modules.Waxap.Admin'}</h2>
    <p>{l s='Activa los estados de pedido que enviarán un WhatsApp. Usa el lápiz para personalizar el mensaje.' d='Modules.Waxap.Admin'}</p>
</div>

<form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" autocomplete="off">

    <div class="wan-field-rows" style="max-width:480px;margin-bottom:28px;">
        <div class="wan-field-row">
            <label for="wan-country-code" class="wan-field-label">
                {l s='Prefijo de país (teléfonos de clientes)' d='Modules.Waxap.Admin'}
                <span class="wan-field-hint">{l s='Sin el +. Se añade si el número del cliente no lo lleva. España: 34' d='Modules.Waxap.Admin'}</span>
            </label>
            <input type="text" id="wan-country-code" name="phone_country_code"
                   value="{$waxap_country_code|escape:'html':'UTF-8'}"
                   class="wan-field-input" style="max-width:120px;" maxlength="5" pattern="[0-9]{ldelim}1,5{rdelim}" placeholder="34">
        </div>
    </div>

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

                    <button type="button" class="wan-edit-tpl-btn"
                            data-panel="wan-tpl-panel-{$state.id|escape:'html':'UTF-8'}"
                            data-has-content="{if $state.has_content}1{else}0{/if}"
                            aria-expanded="false"
                            title="{l s='Editar mensaje' d='Modules.Waxap.Admin' js=1}">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7"
                             stroke-linecap="round" stroke-linejoin="round" width="15" height="15" aria-hidden="true">
                            <path d="M11.5 1.5l3 3-9 9H2.5v-3l9-9z"/>
                        </svg>
                    </button>
                </div>

                <div class="wan-tpl-panel" id="wan-tpl-panel-{$state.id|escape:'html':'UTF-8'}">
                    <div class="wan-tpl-panel-inner">
                        <textarea name="waxap_templates[{$state.id|escape:'html':'UTF-8'}]"
                                  id="wan-tpl-{$state.id|escape:'html':'UTF-8'}"
                                  class="wan-template-textarea" rows="3">{$state.template|escape:'html':'UTF-8'}</textarea>
                        <div class="wan-template-footer">
                            {foreach from=$waxap_vars item=var}
                                <span class="wan-var-chip" data-target="wan-tpl-{$state.id|escape:'html':'UTF-8'}">{$var|escape:'html':'UTF-8'}</span>
                            {/foreach}
                            <span class="wan-char-count" id="wan-count-{$state.id|escape:'html':'UTF-8'}">0</span>
                        </div>
                    </div>
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

<script>
(function () {
    // Acordeón: el lápiz abre/cierra el panel de plantilla.
    document.querySelectorAll('.wan-edit-tpl-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = document.getElementById(btn.dataset.panel);
            if (!panel) return;
            var open = btn.classList.toggle('is-active');
            btn.setAttribute('aria-expanded', String(open));
            panel.classList.toggle('is-open', open);
        });
    });

    // Abrir automáticamente los paneles con contenido.
    document.querySelectorAll('.wan-edit-tpl-btn[data-has-content="1"]').forEach(function (btn) {
        var panel = document.getElementById(btn.dataset.panel);
        if (panel) { btn.classList.add('is-active'); panel.classList.add('is-open'); }
    });

    // Contadores de caracteres.
    document.querySelectorAll('.wan-template-textarea').forEach(function (ta) {
        var key = ta.id.replace('wan-tpl-', '');
        var counter = document.getElementById('wan-count-' + key);
        if (!counter) return;
        function update() { counter.textContent = ta.value.length; }
        update();
        ta.addEventListener('input', update);
    });

    // Inserción de variables al hacer clic en un chip.
    document.querySelectorAll('.wan-var-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var ta = document.getElementById(chip.dataset.target);
            if (!ta) return;
            var s = ta.selectionStart, e = ta.selectionEnd;
            ta.value = ta.value.slice(0, s) + chip.textContent + ta.value.slice(e);
            ta.selectionStart = ta.selectionEnd = s + chip.textContent.length;
            ta.focus();
            ta.dispatchEvent(new Event('input'));
        });
    });
}());
</script>
