{*
 * Waxap for PrestaShop — pestaña Mensajes (bandeja de entrada estilo WhatsApp Web).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
<div class="waxap-section-header">
    <h2>{l s='Mensajes' d='Modules.Waxap.Admin'}</h2>
    <p>{l s='Bandeja de entrada de WhatsApp. Responde a tus clientes directamente desde aquí.' d='Modules.Waxap.Admin'}</p>
</div>

{if $waxap_inbox_error}
    <div class="wan-inline-notice wan-inline-notice--error">{$waxap_inbox_error|escape:'html':'UTF-8'}</div>
{else}

<div id="waxap-inbox">
    <div class="waxap-inbox-sidebar">
        <div id="waxap-conv-list">
            {if !$waxap_conversations}
                <div class="waxap-conv-empty">{l s='Sin conversaciones todavía.' d='Modules.Waxap.Admin'}</div>
            {else}
                {foreach from=$waxap_conversations item=conv}
                    <div class="waxap-conv-item" data-phone="{$conv.phone|escape:'html':'UTF-8'}">
                        <div class="waxap-conv-avatar">{$conv.initial|escape:'html':'UTF-8'}</div>
                        <div class="waxap-conv-info">
                            <div class="waxap-conv-phone">{$conv.display|escape:'html':'UTF-8'}</div>
                            <div class="waxap-conv-preview">{$conv.preview|escape:'html':'UTF-8'}</div>
                        </div>
                        <div class="waxap-conv-meta">
                            {if $conv.unread > 0}<span class="waxap-unread-badge">{$conv.unread|string_format:"%d"}</span>{/if}
                        </div>
                    </div>
                {/foreach}
            {/if}
        </div>
    </div>

    <div class="waxap-inbox-main">
        <div id="waxap-inbox-empty">
            <span>💬</span>
            <span>{l s='Selecciona una conversación para verla' d='Modules.Waxap.Admin'}</span>
        </div>
        <div id="waxap-thread" style="display:none">
            <div class="waxap-thread-header">
                <span>🟢</span>
                <span id="waxap-thread-header-phone"></span>
            </div>
            <div id="waxap-thread-messages"></div>
            <div class="waxap-thread-form">
                <textarea id="waxap-send-text" placeholder="{l s='Escribe un mensaje… (Ctrl+Enter para enviar)' d='Modules.Waxap.Admin' js=1}"></textarea>
                <button id="waxap-send-btn" type="button">{l s='Enviar' d='Modules.Waxap.Admin'}</button>
            </div>
        </div>
    </div>
</div>

{/if}

<style>
#waxap-inbox { display:flex; height:620px; border:1px solid #dcdcde; border-radius:4px; overflow:hidden; background:#fff; margin-top:1rem; }
.waxap-inbox-sidebar { width:280px; border-right:1px solid #dcdcde; overflow-y:auto; flex-shrink:0; background:#fafafa; }
.waxap-inbox-main { flex:1; display:flex; flex-direction:column; min-width:0; }
.waxap-conv-item { padding:12px 14px; border-bottom:1px solid #f0f0f0; cursor:pointer; display:flex; align-items:flex-start; gap:10px; }
.waxap-conv-item:hover { background:#f0f0f1; }
.waxap-conv-item.active { background:#e8f5e9; }
.waxap-conv-avatar { width:38px; height:38px; border-radius:50%; background:#25d366; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; flex-shrink:0; font-size:15px; }
.waxap-conv-info { flex:1; min-width:0; }
.waxap-conv-phone { font-weight:600; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.waxap-conv-preview { font-size:12px; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.waxap-conv-meta { display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0; }
.waxap-conv-time { font-size:11px; color:#aaa; }
.waxap-unread-badge { background:#25d366; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; font-weight:700; }
.waxap-conv-empty { padding:20px 14px; color:#888; font-size:13px; }
#waxap-inbox-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#aaa; font-size:14px; gap:10px; }
#waxap-inbox-empty span:first-child { font-size:42px; }
#waxap-thread { display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; }
.waxap-thread-header { padding:12px 16px; border-bottom:1px solid #dcdcde; background:#f9fafb; font-weight:600; font-size:14px; display:flex; align-items:center; gap:8px; flex-shrink:0; }
#waxap-thread-messages { flex:1; min-height:0; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:6px; background:#ece5dd; }
.waxap-msg { max-width:65%; padding:7px 12px; border-radius:8px; font-size:13px; line-height:1.5; word-break:break-word; }
.waxap-msg-inbound { background:#fff; border-radius:0 8px 8px 8px; align-self:flex-start; box-shadow:0 1px 1px rgba(0,0,0,.06); }
.waxap-msg-outbound { background:#dcf8c6; border-radius:8px 0 8px 8px; align-self:flex-end; box-shadow:0 1px 1px rgba(0,0,0,.06); }
.waxap-msg-time { font-size:10px; color:#999; margin-top:3px; text-align:right; }
.waxap-thread-form { padding:10px 14px; border-top:1px solid #dcdcde; display:flex; gap:8px; background:#fff; flex-shrink:0; align-items:flex-end; }
.waxap-thread-form textarea { flex:1; resize:none; padding:8px 10px; border:1px solid #dcdcde; border-radius:4px; font-size:13px; height:56px; font-family:inherit; line-height:1.4; }
.waxap-thread-form textarea:focus { border-color:#25d366; outline:none; box-shadow:0 0 0 1px #25d366; }
#waxap-send-btn { padding:8px 18px; background:#25d366; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:13px; height:56px; white-space:nowrap; }
#waxap-send-btn:hover { background:#20bc5a; }
#waxap-send-btn:disabled { background:#a5d6b7; cursor:default; }
.waxap-thread-loading, .waxap-thread-error, .waxap-thread-empty-msgs { text-align:center; padding:20px; color:#888; font-size:13px; }
</style>
