{*
 * Waxap for PrestaShop — chasis de la página de configuración.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved.
 *}
<link rel="stylesheet" href="{$waxap_css|escape:'html':'UTF-8'}">

<div class="waxap-admin-wrap">

    <header class="waxap-header">
        <span class="waxap-logo-mark">W</span>
        <div class="waxap-header-text">
            <h1>Waxap</h1>
            <p>{l s='Notificaciones WhatsApp para PrestaShop' d='Modules.Waxap.Admin'}</p>
        </div>
    </header>

    <ul class="waxap-nav-tabs">
        {foreach from=$waxap_tabs item=tab}
            <li>
                <a href="{$tab.url|escape:'html':'UTF-8'}" class="{if $tab.active}active{/if}">
                    {$tab.label|escape:'html':'UTF-8'}
                </a>
            </li>
        {/foreach}
    </ul>

    <div class="waxap-tab-content">
        {if $waxap_notice}{$waxap_notice nofilter}{/if}
        {$waxap_tab_content nofilter}
    </div>

</div>
