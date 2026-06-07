{*
 * Waxap for PrestaShop — tarjeta de uso/suscripción (pestaña Conexión).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
<div class="wan-usage-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <strong style="font-size:14px;">{l s='Plan Waxap' d='Modules.Waxap.Admin'}</strong>
        {if $waxap_usage.is_active}
            <span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;">{l s='Activo' d='Modules.Waxap.Admin'}</span>
        {elseif $waxap_usage.is_suspended}
            <span style="background:#fee2e2;color:#991b1b;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;">{l s='Suspendido' d='Modules.Waxap.Admin'}</span>
        {else}
            <span style="background:#e5e7eb;color:#374151;padding:2px 10px;border-radius:10px;font-size:12px;font-weight:600;">{$waxap_usage.status|escape:'html':'UTF-8'}</span>
        {/if}
    </div>

    <div style="margin-bottom:6px;font-size:13px;color:#555;">
        {l s='%used% / %quota% mensajes usados este mes' sprintf=['%used%' => $waxap_usage.used, '%quota%' => $waxap_usage.quota] d='Modules.Waxap.Admin'}
    </div>
    <div class="wan-usage-bar-track">
        <div class="wan-usage-bar-fill" style="background:{if $waxap_usage.warning}#ef4444{else}#25d366{/if};width:{$waxap_usage.pct|string_format:"%d"}%;"></div>
    </div>

    {if $waxap_usage.reset_label}
        <p style="font-size:12px;color:#9ca3af;margin:6px 0 0;">
            {l s='Renovación el %date%' sprintf=['%date%' => $waxap_usage.reset_label] d='Modules.Waxap.Admin'}
        </p>
    {/if}

    {if $waxap_usage.warning && $waxap_usage.is_active}
        <p style="margin:10px 0 0;padding:8px 12px;background:#fef3c7;border-radius:6px;font-size:13px;color:#92400e;">
            ⚠️ {l s='Te quedan %n% mensajes este mes. Considera actualizar tu plan.' sprintf=['%n%' => $waxap_usage.remaining] d='Modules.Waxap.Admin'}
        </p>
    {/if}

    {if $waxap_usage.is_suspended}
        <p style="margin:10px 0 0;padding:8px 12px;background:#fee2e2;border-radius:6px;font-size:13px;color:#991b1b;">
            🔴 {l s='Tu suscripción no está activa. La tienda no enviará notificaciones.' d='Modules.Waxap.Admin'}
        </p>
    {/if}

    <p style="margin:12px 0 0;">
        <a href="#" id="wan-portal-btn" style="font-size:13px;">{l s='Gestionar suscripción →' d='Modules.Waxap.Admin'}</a>
        <span id="wan-portal-spinner" style="display:none;font-size:12px;color:#9ca3af;margin-left:8px;">{l s='Cargando…' d='Modules.Waxap.Admin'}</span>
    </p>
</div>

<script>
(function () {
    var btn = document.getElementById('wan-portal-btn');
    var spinner = document.getElementById('wan-portal-spinner');
    if (!btn) { return; }
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        btn.style.pointerEvents = 'none';
        spinner.style.display = 'inline';
        var body = new URLSearchParams({ ajax: '1', action: 'BillingPortal' });
        fetch('{$waxap_ajax_url|escape:'javascript':'UTF-8'}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && data.data && data.data.url) {
                window.open(data.data.url, '_blank');
            } else {
                window.alert((data.data && data.data.message) ? data.data.message : 'Error al obtener el enlace.');
            }
        })
        .catch(function () { window.alert('Error de conexión.'); })
        .finally(function () {
            btn.style.pointerEvents = '';
            spinner.style.display = 'none';
        });
    });
}());
</script>
