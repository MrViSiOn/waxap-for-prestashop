<?php
/**
 * Waxap for PrestaShop — consentimiento (opt-in) de WhatsApp por pedido.
 *
 * Lectura/escritura de la tabla waxap_order_optin. Equivale al order meta
 * `_wa_notifier_opt_in` de WooCommerce. La escritura ocurre en el checkout (DRAPPS-498);
 * la lectura la usa el hook de cambio de estado (DRAPPS-495) antes de enviar.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

namespace Waxap\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use DbQuery;

/**
 * Gestiona el consentimiento de notificaciones WhatsApp asociado a cada pedido.
 */
final class OptIn
{
    /**
     * Indica si el cliente dio su consentimiento para este pedido.
     *
     * Si no existe registro (pedidos anteriores a la activación del checkbox), se asume
     * opt-in TRUE, igual que hace el plugin WooCommerce con los pedidos sin meta.
     *
     * @param int $orderId ID del pedido.
     */
    public static function isOptedIn(int $orderId): bool
    {
        $query = new DbQuery();
        $query->select('opt_in')
            ->from('waxap_order_optin')
            ->where('id_order = ' . $orderId);

        $value = Db::getInstance()->getValue($query);

        // Sin registro → asumimos opt-in (paridad con WooCommerce).
        if (false === $value || null === $value) {
            return true;
        }

        return '1' === (string) $value || 1 === (int) $value;
    }

    /**
     * Persiste el consentimiento de un pedido.
     *
     * @param int  $orderId ID del pedido.
     * @param bool $optIn   Consentimiento del cliente.
     */
    public static function save(int $orderId, bool $optIn): void
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'waxap_order_optin` (`id_order`, `opt_in`, `date_add`)'
            . ' VALUES (' . $orderId . ', ' . ($optIn ? 1 : 0) . ', NOW())'
            . ' ON DUPLICATE KEY UPDATE `opt_in` = ' . ($optIn ? 1 : 0);

        Db::getInstance()->execute($sql);
    }
}
