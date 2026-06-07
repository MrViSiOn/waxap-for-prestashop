<?php
/**
 * Waxap for PrestaShop — chasis de la página de configuración (router de pestañas + forms).
 *
 * Equivale a WaNotifier\Admin\AdminMenu del plugin WooCommerce: gestiona la navegación por
 * pestañas, decide cuáles son visibles según el estado de conexión y procesa el guardado de
 * cada formulario antes de renderizar.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

namespace Waxap\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Context;
use Module;
use OrderState;
use Tools;
use Validate;
use Waxap\Api\WrapperClient;
use Waxap\Api\WrapperException;
use Waxap\Settings\Config;

/**
 * Renderiza y procesa la página de configuración del módulo con su sistema de pestañas.
 */
final class Page
{
    /**
     * Definición de pestañas: slug => clave de traducción del label.
     *
     * @var array<string,string>
     */
    private const TABS = [
        'connection' => 'Conexión',
        'phone' => 'Número WhatsApp',
        'notifications' => 'Notificaciones',
        'email' => 'Email',
        'history' => 'Historial',
        'messages' => 'Mensajes',
    ];

    private Module $module;

    private Context $context;

    public function __construct(Module $module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
    }

    /** Punto de entrada llamado desde Waxap::getContent(). */
    public function render(): string
    {
        $notice = $this->processForms();

        $current = (string) Tools::getValue('tab', 'connection');
        if (!array_key_exists($current, self::TABS)) {
            $current = 'connection';
        }

        // Si no está conectado, solo es accesible la pestaña Conexión (onboarding).
        if (!Config::isConnected() && 'connection' !== $current) {
            $current = 'connection';
        }

        $this->enqueueAssets($current);

        return $this->renderChassis($current, $notice);
    }

    /** Encola los scripts/estilos del back-office según la pestaña activa. */
    private function enqueueAssets(string $current): void
    {
        $controller = $this->context->controller;
        if (null === $controller) {
            return;
        }

        $base = $this->module->getPathUri();
        $ver = $this->module->version;
        $controller->addCSS($base . 'views/css/admin.css?v=' . $ver);

        $ajaxUrl = $this->context->link->getAdminLink('AdminWaxapAjax');

        if ('phone' === $current) {
            $controller->addJS($base . 'views/js/admin-session.js?v=' . $ver);
            \Media::addJsDef([
                'waxapSession' => [
                    'ajaxUrl' => $ajaxUrl,
                    'hasSession' => Config::hasSession() ? '1' : '0',
                    'confirmUnlink' => $this->module->trans('¿Seguro que quieres desvincular el número WhatsApp?', [], 'Modules.Waxap.Admin'),
                    'confirmDeleteSession' => $this->module->trans('¿Desvincular esta sesión? Se cerrará la conexión WhatsApp de este número.', [], 'Modules.Waxap.Admin'),
                ],
            ]);
        }
    }

    /* ===================================================================
     *  RENDER
     * =================================================================== */

    /** Renderiza la cabecera, la navegación de pestañas y el contenido activo. */
    private function renderChassis(string $current, string $notice): string
    {
        $isConnected = Config::isConnected();
        $visibleTabs = [];
        foreach (self::TABS as $slug => $label) {
            if ($isConnected || 'connection' === $slug) {
                $visibleTabs[$slug] = [
                    'slug' => $slug,
                    'label' => $this->module->trans($label, [], 'Modules.Waxap.Admin'),
                    'url' => $this->tabUrl($slug),
                    'active' => $slug === $current,
                ];
            }
        }

        $this->context->smarty->assign([
            'waxap_tabs' => $visibleTabs,
            'waxap_notice' => $notice,
            'waxap_tab_content' => $this->renderTab($current),
            'waxap_css' => $this->module->getPathUri() . 'views/css/admin.css?v=' . $this->module->version,
        ]);

        return $this->fetch('config.tpl');
    }

    /** Renderiza el contenido de la pestaña activa. */
    private function renderTab(string $tab): string
    {
        switch ($tab) {
            case 'phone':
                return $this->renderPhone();
            case 'notifications':
                return $this->renderNotifications();
            case 'connection':
            default:
                return $this->renderConnection();
        }
    }

    /** Pestaña Número WhatsApp: vinculación por QR, estado y mensaje de prueba. */
    private function renderPhone(): string
    {
        $storedPhone = Config::get('PHONE_NUMBER');
        $displayPhone = '' !== $storedPhone ? '+' . ltrim($storedPhone, '+') : '';

        // Lista de sesiones (solo se muestra si hay más de una).
        $sessions = [];
        try {
            $all = (new WrapperClient())->getSessions();
            if (is_array($all) && count($all) >= 2) {
                foreach ($all as $session) {
                    $sid = (string) ($session['id'] ?? '');
                    if ('' === $sid) {
                        continue;
                    }
                    $phone = (string) ($session['phoneNumber'] ?? '');
                    $sessions[] = [
                        'id' => $sid,
                        'phone' => '' !== $phone ? '+' . ltrim($phone, '+') : '—',
                        'status' => (string) ($session['status'] ?? 'disconnected'),
                    ];
                }
            }
        } catch (WrapperException $e) {
            $sessions = [];
        }

        $this->context->smarty->assign([
            'waxap_has_session' => Config::hasSession(),
            'waxap_display_phone' => $displayPhone,
            'waxap_sessions' => $sessions,
        ]);

        return $this->fetch('tab-phone.tpl');
    }

    /** Pestaña Conexión: estado conectado o formulario de conexión manual. */
    private function renderConnection(): string
    {
        $isConnected = Config::isConnected();

        $this->context->smarty->assign([
            'waxap_is_connected' => $isConnected,
            'waxap_wrapper_url' => Config::get('WRAPPER_URL'),
            'waxap_api_key' => Config::get('API_KEY'),
            'waxap_tenant_id' => Config::get('TENANT_ID'),
            'waxap_phone_tab_url' => $this->tabUrl('phone'),
            'waxap_config_url' => $this->configUrl(),
        ]);

        return $this->fetch('tab-connection.tpl');
    }

    /** Pestaña Notificaciones: selector de estados de pedido (lectura dinámica de OrderState). */
    private function renderNotifications(): string
    {
        $enabled = array_filter(explode(',', Config::get('NOTIFY_STATUSES')));
        $states = [];
        foreach (OrderState::getOrderStates((int) $this->context->language->id) as $state) {
            $id = (string) $state['id_order_state'];
            $template = Config::get('TEMPLATE_' . $id);
            $states[] = [
                'id' => $id,
                'label' => (string) $state['name'],
                'color' => (string) ($state['color'] ?? '#6b7280'),
                'enabled' => in_array($id, $enabled, true),
                'template' => $template,
                'has_content' => '' !== $template,
            ];
        }

        $this->context->smarty->assign([
            'waxap_states' => $states,
            'waxap_country_code' => Config::get('PHONE_COUNTRY_CODE'),
            'waxap_vars' => ['{nombre}', '{pedido}', '{estado}', '{total}', '{enlace}'],
            'waxap_config_url' => $this->configUrl(),
        ]);

        return $this->fetch('tab-notifications.tpl');
    }

    /* ===================================================================
     *  PROCESADO DE FORMULARIOS
     *
     *  Los formularios hacen POST a la propia URL de configuración del módulo,
     *  que ya incluye el token de AdminModules (protección CSRF de PrestaShop).
     * =================================================================== */

    /** Despacha el guardado del formulario enviado, si lo hay. Devuelve HTML de aviso. */
    private function processForms(): string
    {
        if (Tools::isSubmit('submitWaxapConnection')) {
            return $this->saveConnection();
        }
        if (Tools::isSubmit('submitWaxapDisconnect')) {
            return $this->disconnect();
        }
        if (Tools::isSubmit('submitWaxapNotifications')) {
            return $this->saveNotifications();
        }

        return '';
    }

    /** Guarda las credenciales de conexión introducidas manualmente. */
    private function saveConnection(): string
    {
        $wrapperUrl = trim((string) Tools::getValue('wrapper_url'));
        $apiKey = trim((string) Tools::getValue('api_key'));
        $tenantId = trim((string) Tools::getValue('tenant_id'));

        if ('' !== $wrapperUrl && Validate::isUrl($wrapperUrl)) {
            Config::set('WRAPPER_URL', $wrapperUrl);
        }
        Config::set('API_KEY', $apiKey);
        Config::set('TENANT_ID', $tenantId);

        return $this->ok($this->module->trans('Configuración guardada.', [], 'Modules.Waxap.Admin'));
    }

    /** Desconecta la cuenta borrando las credenciales. */
    private function disconnect(): string
    {
        Config::disconnect();

        return $this->warn($this->module->trans('Cuenta desconectada.', [], 'Modules.Waxap.Admin'));
    }

    /** Guarda los estados de pedido a notificar, sus plantillas y el prefijo de país. */
    private function saveNotifications(): string
    {
        $validIds = array_map(
            static fn ($s): string => (string) $s['id_order_state'],
            OrderState::getOrderStates((int) $this->context->language->id)
        );

        $posted = Tools::getValue('waxap_notify_statuses');
        $selected = [];
        if (is_array($posted)) {
            foreach ($posted as $id) {
                $id = (string) (int) $id;
                if (in_array($id, $validIds, true)) {
                    $selected[] = $id;
                }
            }
        }
        Config::set('NOTIFY_STATUSES', implode(',', $selected));

        // Prefijo de país (solo dígitos). Por defecto 34 (España).
        $countryCode = preg_replace('/\D/', '', (string) Tools::getValue('phone_country_code'));
        Config::set('PHONE_COUNTRY_CODE', '' !== (string) $countryCode ? (string) $countryCode : '34');

        // Plantilla de mensaje por cada estado.
        $templates = Tools::getValue('waxap_templates');
        if (is_array($templates)) {
            foreach ($validIds as $id) {
                $tpl = isset($templates[$id]) ? (string) $templates[$id] : '';
                // Limpiamos etiquetas pero conservamos saltos de línea y variables {…}.
                $tpl = strip_tags($tpl);
                Config::set('TEMPLATE_' . $id, $tpl);
            }
        }

        return $this->ok($this->module->trans('Configuración guardada.', [], 'Modules.Waxap.Admin'));
    }

    /* ===================================================================
     *  HELPERS
     * =================================================================== */

    /** URL base de configuración del módulo (con token). */
    private function configUrl(): string
    {
        return $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->module->name,
            'module_name' => $this->module->name,
        ]);
    }

    /** URL de una pestaña concreta. */
    private function tabUrl(string $slug): string
    {
        return $this->configUrl() . '&tab=' . $slug;
    }

    /** Hace fetch de una plantilla admin del módulo. */
    private function fetch(string $template): string
    {
        return $this->module->fetch(
            'module:waxap/views/templates/admin/' . $template
        );
    }

    /** Aviso de éxito (verde). */
    private function ok(string $message): string
    {
        return '<div class="waxap-updated">' . htmlspecialchars($message, ENT_QUOTES) . '</div>';
    }

    /** Aviso de advertencia (amarillo). */
    private function warn(string $message): string
    {
        return '<div class="waxap-notice-warning">' . htmlspecialchars($message, ENT_QUOTES) . '</div>';
    }
}
