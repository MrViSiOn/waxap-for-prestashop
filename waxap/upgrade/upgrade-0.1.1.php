<?php
/**
 * Waxap for PrestaShop — script de actualización a 0.1.1 (ejemplo de la convención upgrade/).
 *
 * PrestaShop ejecuta automáticamente las funciones upgrade_module_X_Y_Z() al detectar que la
 * versión de los archivos del módulo es mayor que la registrada en base de datos. Este script
 * de ejemplo se limita a re-registrar los hooks por si alguno se añadió entre versiones.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Waxap\Install\Installer;

/**
 * @param Module $module
 */
function upgrade_module_0_1_1($module): bool
{
    // Re-registra cualquier hook que no estuviera presente en instalaciones anteriores.
    foreach (Installer::HOOKS as $hook) {
        if (!$module->isRegisteredInHook($hook)) {
            $module->registerHook($hook);
        }
    }

    return true;
}
