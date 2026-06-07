{*
 * Waxap for PrestaShop — pestaña Historial (mensajes WhatsApp enviados, paginado).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
<div class="waxap-section-header">
    <h2>{l s='Historial de mensajes WhatsApp' d='Modules.Waxap.Admin'}</h2>
    <p>{l s='Registro de notificaciones enviadas desde esta tienda.' d='Modules.Waxap.Admin'}</p>
</div>

{if $waxap_history_error}
    <div class="wan-inline-notice wan-inline-notice--error">{$waxap_history_error|escape:'html':'UTF-8'}</div>
{elseif !$waxap_rows}
    <div class="wan-inline-notice wan-inline-notice--warning">{l s='No hay mensajes registrados todavía.' d='Modules.Waxap.Admin'}</div>
{else}
    <table class="table">
        <thead>
            <tr>
                <th>{l s='Fecha' d='Modules.Waxap.Admin'}</th>
                <th>{l s='Teléfono' d='Modules.Waxap.Admin'}</th>
                <th>{l s='Pedido' d='Modules.Waxap.Admin'}</th>
                <th>{l s='Estado pedido' d='Modules.Waxap.Admin'}</th>
                <th>{l s='Resultado' d='Modules.Waxap.Admin'}</th>
                <th>{l s='Mensaje / Error' d='Modules.Waxap.Admin'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$waxap_rows item=row}
                {assign var=rstat value=$row.status|default:'queued'}
                <tr>
                    <td>{if $row.createdAt}{$row.createdAt|escape:'html':'UTF-8'}{else}—{/if}</td>
                    <td>{$row.customerPhone|default:''|escape:'html':'UTF-8'}</td>
                    <td>{if $row.orderId}#{$row.orderId|escape:'html':'UTF-8'}{else}—{/if}</td>
                    <td>{$row.orderStatus|default:''|escape:'html':'UTF-8'}</td>
                    <td>
                        {if $rstat == 'sent'}
                            <span class="waxap-badge waxap-badge--sent">{l s='Enviado' d='Modules.Waxap.Admin'}</span>
                        {elseif $rstat == 'failed'}
                            <span class="waxap-badge waxap-badge--failed">{l s='Error' d='Modules.Waxap.Admin'}</span>
                        {elseif $rstat == 'skipped'}
                            <span class="waxap-badge waxap-badge--skipped">{l s='Omitido' d='Modules.Waxap.Admin'}</span>
                        {else}
                            <span class="waxap-badge waxap-badge--queued">{l s='En cola' d='Modules.Waxap.Admin'}</span>
                        {/if}
                    </td>
                    <td class="waxap-history-msg">
                        {if $rstat == 'failed' || $rstat == 'skipped'}
                            {$row.skipReason|default:$row.messageSent|default:''|escape:'html':'UTF-8'}
                        {else}
                            {$row.messageSent|default:''|escape:'html':'UTF-8'}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if $waxap_total_pages > 1}
        <div style="display:flex;align-items:center;gap:10px;margin-top:12px;">
            <span style="font-size:12px;color:#6b7280;">{l s='%n% mensajes' sprintf=['%n%' => $waxap_total] d='Modules.Waxap.Admin'}</span>
            <span style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                {if $waxap_page > 1}
                    <a class="btn btn-default" href="{$waxap_history_base_url|escape:'html':'UTF-8'}&paged=1">«</a>
                    <a class="btn btn-default" href="{$waxap_prev_url|escape:'html':'UTF-8'}">‹</a>
                {/if}
                <span style="font-size:13px;">{$waxap_page|intval} / {$waxap_total_pages|intval}</span>
                {if $waxap_page < $waxap_total_pages}
                    <a class="btn btn-default" href="{$waxap_next_url|escape:'html':'UTF-8'}">›</a>
                    <a class="btn btn-default" href="{$waxap_last_url|escape:'html':'UTF-8'}">»</a>
                {/if}
            </span>
        </div>
    {/if}
{/if}

<style>
.waxap-history-msg { max-width:320px; white-space:normal; word-break:break-word; font-size:12px; color:#555; }
.waxap-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.waxap-badge--sent { background:#d1fae5; color:#065f46; }
.waxap-badge--failed { background:#fee2e2; color:#991b1b; }
.waxap-badge--skipped { background:#fef3c7; color:#92400e; }
.waxap-badge--queued { background:#e5e7eb; color:#374151; }
</style>
