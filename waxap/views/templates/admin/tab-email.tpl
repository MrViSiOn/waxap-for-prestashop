{*
 * Waxap for PrestaShop — pestaña Email (botón wa.me en emails transaccionales).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
{if !$waxap_has_phone}
    <div class="waxap-notice-warning">
        {l s='El botón no aparecerá en los emails hasta que vincules tu número WhatsApp.' d='Modules.Waxap.Admin'}
        <a href="{$waxap_phone_tab_url|escape:'html':'UTF-8'}">{l s='Vincular número' d='Modules.Waxap.Admin'}</a>
    </div>
{/if}

<div class="waxap-section-header">
    <h2>{l s='Botón WhatsApp en emails' d='Modules.Waxap.Admin'}</h2>
    <p>{l s='Añade un botón wa.me en los emails transaccionales de PrestaShop para que el cliente pueda escribirte directamente.' d='Modules.Waxap.Admin'}</p>
</div>

<form method="post" action="{$waxap_config_url|escape:'html':'UTF-8'}" autocomplete="off">

    <div class="wan-status-item" style="max-width:620px;">
        <label class="wan-status-card" for="wan-email-enabled">
            <div class="wan-status-info">
                <strong>{l s='Activar botón WhatsApp' d='Modules.Waxap.Admin'}</strong>
                <span>{l s='Muestra el botón en los emails de pedido enviados al cliente.' d='Modules.Waxap.Admin'}</span>
            </div>
            <div class="wan-toggle">
                <input type="checkbox" id="wan-email-enabled" name="email_button_enabled" value="1"
                       class="wan-toggle-input" {if $waxap_email_enabled}checked{/if}>
                <span class="wan-toggle-track"></span>
                <span class="wan-toggle-thumb"></span>
            </div>
        </label>
    </div>

    <div class="wan-field-rows" style="max-width:620px;margin-top:24px;">
        <div class="wan-field-row">
            <label for="wan-email-text" class="wan-field-label">{l s='Texto del botón' d='Modules.Waxap.Admin'}</label>
            <input type="text" id="wan-email-text" name="email_button_text"
                   value="{$waxap_email_text|escape:'html':'UTF-8'}" class="wan-field-input" maxlength="100" autocomplete="off">
        </div>

        <div class="wan-field-row">
            <label for="wan-email-prefill" class="wan-field-label">
                {l s='Mensaje prefabricado' d='Modules.Waxap.Admin'}
                <span class="wan-field-hint">{l s='El cliente verá este texto en WhatsApp al hacer clic.' d='Modules.Waxap.Admin'}</span>
            </label>
            <textarea id="wan-email-prefill" name="email_button_prefill" class="wan-template-textarea" rows="2">{$waxap_email_prefill|escape:'html':'UTF-8'}</textarea>
            <div class="wan-template-footer">
                <span class="wan-var-chip" data-target="wan-email-prefill">{ldelim}pedido{rdelim}</span>
                <span class="wan-char-count" id="wan-count-prefill">0</span>
            </div>
        </div>
    </div>

    <p class="wan-action-row">
        <button type="submit" name="submitWaxapEmail" class="btn btn-default waxap-btn-primary">
            {l s='Guardar cambios' d='Modules.Waxap.Admin'}
        </button>
    </p>
</form>

<script>
(function () {
    var prefillTa = document.getElementById('wan-email-prefill');
    var counter = document.getElementById('wan-count-prefill');
    if (prefillTa && counter) {
        function update() { counter.textContent = prefillTa.value.length; }
        update();
        prefillTa.addEventListener('input', update);
    }
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
