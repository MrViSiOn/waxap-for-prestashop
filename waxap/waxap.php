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
use Waxap\Install\Installer;
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
        // Implementado en DRAPPS-495.
    }

    /**
     * Inyecta el botón wa.me en los emails transaccionales (DRAPPS-497).
     *
     * @param array<string,mixed> $params
     */
    public function hookActionEmailAddBeforeContent(array $params): void
    {
        // Implementado en DRAPPS-497.
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
        // Implementado en DRAPPS-497.
        return '';
    }

    /** Acceso de conveniencia a la configuración del módulo. */
    public function isConnected(): bool
    {
        return Config::isConnected();
    }
}
