<?php
/**
 * Waxap for PrestaShop — idempotencia de notificaciones (anti-baneo, CRÍTICO).
 *
 * Equivale a la defensa por meta `_waxap_notified_<estado>` del plugin WooCommerce: garantiza
 * que un mismo (pedido, estado) NO se notifique dos veces. La marca se escribe SOLO después de
 * un envío correcto; si el envío falla, no se marca, de modo que un reintento del estado
 * volverá a intentar el envío.
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
 * Registra qué (pedido, estado) ya han sido notificados con éxito.
 */
final class Idempotency
{
    /**
     * Indica si ya se notificó este pedido para este estado.
     *
     * @param int    $orderId     ID del pedido.
     * @param string $orderStatus Clave/ID del estado de pedido.
     */
    public static function alreadyNotified(int $orderId, string $orderStatus): bool
    {
        $query = new DbQuery();
        $query->select('1')
            ->from('waxap_order_notified')
            ->where('id_order = ' . $orderId)
            ->where('order_status = "' . pSQL($orderStatus) . '"');

        return (bool) Db::getInstance()->getValue($query);
    }

    /**
     * Marca el (pedido, estado) como notificado con éxito.
     *
     * Usa INSERT ... ON DUPLICATE KEY para ser idempotente frente a condiciones de carrera
     * (la tabla tiene UNIQUE(id_order, order_status)).
     *
     * @param int    $orderId     ID del pedido.
     * @param string $orderStatus Clave/ID del estado de pedido.
     */
    public static function markNotified(int $orderId, string $orderStatus): void
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'waxap_order_notified` (`id_order`, `order_status`, `date_add`)'
            . ' VALUES (' . $orderId . ', "' . pSQL($orderStatus) . '", NOW())'
            . ' ON DUPLICATE KEY UPDATE `date_add` = NOW()';

        Db::getInstance()->execute($sql);
    }
}
