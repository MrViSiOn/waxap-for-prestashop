<?php
/**
 * Waxap for PrestaShop — notificaciones WhatsApp para tu tienda PrestaShop.
 *
 * Trae tu propio número de WhatsApp, vincúlalo por QR y envía notificaciones
 * transaccionales (pedido en preparación, enviado, entregado…) a tus clientes.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

// Autoloader PSR-4 propio para el namespace Waxap\ → src/.
// No dependemos de `composer install` en el servidor de destino: cargamos a mano.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Waxap\\';
    if (0 !== strpos($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Si existe un vendor/ generado por composer (deps futuras), lo cargamos también.
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Waxap\Admin\Page;
use Waxap\Api\WrapperClient;
use Waxap\Api\WrapperException;
use Waxap\Install\Installer;
use Waxap\Service\EmailButton;
use Waxap\Service\Idempotency;
use Waxap\Service\OptIn;
use Waxap\Service\PhoneNormalizer;
use Waxap\Service\TemplateRenderer;
use Waxap\Settings\Config;

/**
 * Clase principal del módulo Waxap.
 */
class Waxap extends Module
{
    public function __construct()
    {
        $this->name = 'waxap';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = 'drappsinfo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.99.99'];

        parent::__construct();

        $this->displayName = $this->trans('Waxap', [], 'Modules.Waxap.Admin');
        $this->description = $this->trans(
            'Notificaciones WhatsApp para tu tienda PrestaShop. Trae tu propio número, vincúlalo por QR y avisa a tus clientes del estado de sus pedidos.',
            [],
            'Modules.Waxap.Admin'
        );
        $this->confirmUninstall = $this->trans(
            '¿Seguro que quieres desinstalar Waxap? Se eliminarán las credenciales y la configuración del módulo.',
            [],
            'Modules.Waxap.Admin'
        );
    }

    /** Instala el módulo: hooks, tablas, tabs ocultas y configuración por defecto. */
    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        return (new Installer($this))->install();
    }

    /** Desinstala el módulo: elimina tabs, configuración y tablas propias. */
    public function uninstall(): bool
    {
        (new Installer($this))->uninstall();

        return parent::uninstall();
    }

    /** Renderiza la página de configuración del módulo (chasis de pestañas). */
    public function getContent(): string
    {
        return (new Page($this))->render();
    }

    /* ===================================================================
     *  HOOKS — los cuerpos se completan en sus issues respectivas.
     * =================================================================== */

    /**
     * Cambio de estado de pedido → envío de evento al wrapper (DRAPPS-495).
     *
     * @param array<string,mixed> $params
     */
    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        if (!Config::isConnected() || !Config::hasSession()) {
            return;
        }

        $newState = $params['newOrderStatus'] ?? null;
        $orderId = (int) ($params['id_order'] ?? 0);
        if (!$newState instanceof OrderState || $orderId <= 0) {
            return;
        }

        $stateId = (string) $newState->id;

        // Filtro de estados a notificar. Lista vacía = notificar todos.
        $enabled = array_filter(explode(',', Config::get('NOTIFY_STATUSES')));
        if (!empty($enabled) && !in_array($stateId, $enabled, true)) {
            return;
        }

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // Teléfono del cliente (dirección de facturación, móvil preferente).
        $address = new Address((int) $order->id_address_invoice);
        $phone = PhoneNormalizer::fromAddress(Validate::isLoadedObject($address) ? $address : null);
        if ('' === $phone) {
            return;
        }

        // Consentimiento del cliente (default true para pedidos sin registro).
        $optIn = OptIn::isOptedIn($orderId);

        // Nombre del cliente.
        $customer = new Customer((int) $order->id_customer);
        $name = trim((string) $customer->firstname . ' ' . (string) $customer->lastname);
        if ('' === $name) {
            $name = 'Cliente';
        }

        // Nombre legible del estado en el idioma del pedido.
        $localizedState = new OrderState($stateId, (int) $order->id_lang);
        $stateLabel = is_array($localizedState->name)
            ? (string) reset($localizedState->name)
            : (string) $localizedState->name;

        $payload = [
            'orderId' => (string) $orderId,
            'orderStatus' => $stateId,
            'customerPhone' => $phone,
            'customerName' => $name,
            'whatsappOptIn' => $optIn,
            'siteUrl' => $this->context->link->getBaseLink(),
        ];

        $message = TemplateRenderer::render($order, $stateId, $stateLabel);
        if ('' !== $message) {
            $payload['message'] = $message;
        }

        // Idempotencia: no reenviar si ya notificamos este estado para este pedido.
        if (Idempotency::alreadyNotified($orderId, $stateId)) {
            return;
        }

        try {
            (new WrapperClient())->sendEvent($payload);
            // Solo marcamos tras un envío correcto; un fallo permite reintento.
            Idempotency::markNotified($orderId, $stateId);
        } catch (WrapperException $e) {
            // No silenciamos el fallo: dejamos traza en el log de PrestaShop.
            \PrestaShopLogger::addLog(
                sprintf(
                    'Waxap: fallo al notificar el pedido #%1$d (estado "%2$s"): %3$s',
                    $orderId,
                    $stateId,
                    $e->getMessage()
                ),
                3,
                null,
                'Order',
                $orderId,
                true
            );
        }
    }

    /**
     * Inyecta el botón wa.me en los emails transaccionales (DRAPPS-497).
     *
     * @param array<string,mixed> $params
     */
    public function hookActionEmailAddBeforeContent(array $params): void
    {
        // Solo emails de pedido (lista blanca) y solo el cuerpo HTML.
        $template = (string) ($params['template'] ?? '');
        if (!in_array($template, EmailButton::ORDER_EMAIL_TEMPLATES, true)) {
            return;
        }
        if (!isset($params['template_html'])) {
            return;
        }

        // Intentamos extraer la referencia del pedido de las variables de la plantilla.
        $orderNumber = null;
        $vars = $params['template_vars'] ?? [];
        if (is_array($vars)) {
            if (!empty($vars['{order_name}'])) {
                $orderNumber = (string) $vars['{order_name}'];
            } elseif (!empty($vars['{id_order}'])) {
                $orderNumber = (string) $vars['{id_order}'];
            }
        }

        $button = EmailButton::build($orderNumber);
        if ('' === $button) {
            return;
        }

        // El elemento template_html del array es una referencia al cuerpo real del email.
        $params['template_html'] .= $button;
    }

    /**
     * Renderiza el checkbox de opt-in WhatsApp en el checkout (DRAPPS-498).
     *
     * @param array<string,mixed> $params
     */
    public function hookDisplayPaymentTop(array $params): string
    {
        // Implementado en DRAPPS-498.
        return '';
    }

    /**
     * Persiste la preferencia de opt-in al validar el pedido (DRAPPS-498).
     *
     * @param array<string,mixed> $params
     */
    public function hookActionValidateOrder(array $params): void
    {
        // Implementado en DRAPPS-498.
    }

    /**
     * Botón wa.me en la página de confirmación de pedido (DRAPPS-497).
     *
     * @param array<string,mixed> $params
     */
    public function hookDisplayOrderConfirmation(array $params): string
    {
        $order = $params['order'] ?? ($params['objOrder'] ?? null);
        $orderNumber = ($order instanceof Order) ? (string) $order->reference : null;

        return EmailButton::build($orderNumber);
    }

    /** Acceso de conveniencia a la configuración del módulo. */
    public function isConnected(): bool
    {
        return Config::isConnected();
    }
}
