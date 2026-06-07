<?php
/**
 * Waxap for PrestaShop — instalación/desinstalación (hooks, tablas, tabs, defaults).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

namespace Waxap\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use Module;
use Tab;
use Language;
use Waxap\Settings\Config;

/**
 * Encapsula toda la lógica de instalación/desinstalación del módulo.
 *
 * Equivale a la combinación de la cabecera del plugin WooCommerce + uninstall.php:
 * registra hooks, crea tablas propias, siembra Configuration por defecto y registra
 * los AdminController ocultos que sirven el AJAX.
 */
final class Installer
{
    /**
     * Hooks de PrestaShop que el módulo necesita registrar.
     *
     * @var string[]
     */
    public const HOOKS = [
        // Cambio de estado de pedido → notificación WhatsApp (DRAPPS-495).
        'actionOrderStatusPostUpdate',
        // Inyección del botón wa.me en los emails transaccionales (DRAPPS-497).
        'actionEmailAddBeforeContent',
        // Opt-in GDPR en checkout (DRAPPS-498).
        'displayPaymentTop',
        'actionValidateOrder',
        // Botón wa.me en la página de confirmación de pedido (paridad shortcode WC).
        'displayOrderConfirmation',
        // Aviso de actualización disponible en el back-office (DRAPPS-505).
        'displayBackOfficeHeader',
    ];

    /**
     * Controladores admin ocultos que sirven las peticiones AJAX.
     *
     * @var string[]
     */
    public const ADMIN_TABS = [
        'AdminWaxapAjax',
    ];

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /** Ejecuta la instalación completa. */
    public function install(): bool
    {
        return $this->installDb()
            && $this->registerHooks()
            && $this->installTabs()
            && $this->seedDefaults();
    }

    /** Ejecuta la desinstalación completa. */
    public function uninstall(): bool
    {
        // El orden no es crítico, pero limpiamos todo aunque algún paso falle.
        $ok = $this->uninstallTabs();
        $ok = $this->deleteConfig() && $ok;
        $ok = $this->uninstallDb() && $ok;

        return $ok;
    }

    /** Registra todos los hooks declarados en self::HOOKS. */
    private function registerHooks(): bool
    {
        foreach (self::HOOKS as $hook) {
            if (!$this->module->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    /** Crea las tablas propias del módulo (idempotencia + opt-in). */
    private function installDb(): bool
    {
        $prefix = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        $sqlNotified = "CREATE TABLE IF NOT EXISTS `{$prefix}waxap_order_notified` (
            `id_notified` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT UNSIGNED NOT NULL,
            `order_status` VARCHAR(64) NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_notified`),
            UNIQUE KEY `order_status` (`id_order`, `order_status`)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;";

        $sqlOptin = "CREATE TABLE IF NOT EXISTS `{$prefix}waxap_order_optin` (
            `id_order` INT UNSIGNED NOT NULL,
            `opt_in` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_order`)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;";

        return Db::getInstance()->execute($sqlNotified)
            && Db::getInstance()->execute($sqlOptin);
    }

    /** Elimina las tablas propias del módulo. */
    private function uninstallDb(): bool
    {
        $prefix = _DB_PREFIX_;

        return Db::getInstance()->execute("DROP TABLE IF EXISTS `{$prefix}waxap_order_notified`")
            && Db::getInstance()->execute("DROP TABLE IF EXISTS `{$prefix}waxap_order_optin`");
    }

    /** Siembra la configuración por defecto en Configuration. */
    private function seedDefaults(): bool
    {
        foreach (Config::defaults() as $key => $value) {
            Config::set($key, $value);
        }

        return true;
    }

    /** Elimina todas las claves de configuración del módulo. */
    private function deleteConfig(): bool
    {
        // Claves base.
        foreach (array_keys(Config::defaults()) as $key) {
            Config::delete($key);
        }

        // Claves dinámicas de plantilla por estado: WAXAP_TEMPLATE_<id>.
        $prefix = pSQL(Config::PREFIX . 'TEMPLATE_');
        $rows = Db::getInstance()->executeS(
            'SELECT `name` FROM `' . _DB_PREFIX_ . "configuration` WHERE `name` LIKE '{$prefix}%'"
        );
        if (is_array($rows)) {
            foreach ($rows as $row) {
                \Configuration::deleteByName((string) $row['name']);
            }
        }

        return true;
    }

    /** Instala los AdminController ocultos (id_parent = -1) que sirven el AJAX. */
    private function installTabs(): bool
    {
        foreach (self::ADMIN_TABS as $className) {
            if (Tab::getIdFromClassName($className)) {
                continue;
            }
            $tab = new Tab();
            $tab->class_name = $className;
            $tab->module = $this->module->name;
            $tab->id_parent = -1; // Oculto: invocable por token pero fuera del menú.
            $tab->active = 1;
            $tab->name = [];
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[(int) $lang['id_lang']] = 'Waxap';
            }
            if (!$tab->add()) {
                return false;
            }
        }

        return true;
    }

    /** Elimina los AdminController ocultos. */
    private function uninstallTabs(): bool
    {
        foreach (self::ADMIN_TABS as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return true;
    }
}
