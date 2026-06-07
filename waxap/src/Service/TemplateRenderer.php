<?php
/**
 * Waxap for PrestaShop — render de plantillas de mensaje.
 *
 * Port de OrderEvents::resolve_template() (WooCommerce). Resuelve la plantilla del estado
 * (con fallback a la plantilla genérica) y sustituye las variables
 * {nombre} {pedido} {estado} {total} {enlace}.
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

use Context;
use Currency;
use Customer;
use Order;
use Tools;
use Waxap\Settings\Config;

/**
 * Construye el texto del mensaje a partir de la plantilla del estado y los datos del pedido.
 */
final class TemplateRenderer
{
    /**
     * Resuelve y renderiza el mensaje para un pedido y estado dados.
     *
     * @param Order  $order      Pedido.
     * @param string $stateId    ID del estado de pedido (clave de la plantilla).
     * @param string $stateLabel Nombre legible del estado (para {estado}).
     *
     * @return string Mensaje listo para enviar, o cadena vacía si no hay plantilla.
     */
    public static function render(Order $order, string $stateId, string $stateLabel): string
    {
        $template = Config::get('TEMPLATE_' . $stateId);
        if ('' === $template) {
            // Fallback a la plantilla genérica configurable.
            $template = Config::get('TPL_DEFAULT');
        }
        if ('' === $template) {
            return '';
        }

        $customer = new Customer((int) $order->id_customer);
        $name = trim((string) $customer->firstname . ' ' . (string) $customer->lastname);
        if ('' === $name) {
            $name = 'Cliente';
        }

        $currency = new Currency((int) $order->id_currency);
        $total = Tools::displayPrice((float) $order->total_paid, $currency);
        // Tools::displayPrice puede incluir entidades HTML; las decodificamos para WhatsApp.
        $total = html_entity_decode(strip_tags($total), ENT_QUOTES, 'UTF-8');

        $link = Context::getContext()->link->getPageLink(
            'order-detail',
            true,
            (int) $order->id_lang,
            ['id_order' => (int) $order->id]
        );

        return str_replace(
            ['{nombre}', '{pedido}', '{estado}', '{total}', '{enlace}'],
            [$name, (string) $order->reference, $stateLabel, $total, $link],
            $template
        );
    }
}
