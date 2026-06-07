<?php
/**
 * Waxap for PrestaShop — generación del botón wa.me.
 *
 * Port de OrderEmails::build_button_html() (WooCommerce). Construye el botón verde de WhatsApp
 * que se inyecta en los emails transaccionales y en la página de confirmación de pedido.
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

use Tools;
use Waxap\Settings\Config;

/**
 * Construye el HTML del botón wa.me a partir de la configuración de email branding.
 */
final class EmailButton
{
    /**
     * Lista blanca de plantillas de email donde inyectar el botón (emails de pedido).
     *
     * @var string[]
     */
    public const ORDER_EMAIL_TEMPLATES = [
        'order_conf',
        'order_changed',
        'order_canceled',
        'order_return_state',
        'preparation',
        'shipped',
        'in_transit',
        'delivery',
        'payment',
        'cheque',
        'bankwire',
        'refund',
        'credit_slip',
        'validation',
        'outofstock',
    ];

    /**
     * Construye el HTML del botón.
     *
     * @param string|null $orderNumber Referencia/número de pedido para sustituir {pedido};
     *                                 null lo elimina del prefill.
     *
     * @return string HTML listo para imprimir, o cadena vacía si el botón está desactivado
     *                o no hay número vinculado.
     */
    public static function build(?string $orderNumber): string
    {
        if ('1' !== Config::get('EMAIL_BTN_ENABLED')) {
            return '';
        }

        $phone = Config::get('PHONE_NUMBER');
        if ('' === $phone) {
            return '';
        }

        $cleanPhone = preg_replace('/\D/', '', $phone) ?? '';
        if ('' === $cleanPhone) {
            return '';
        }

        $prefillRaw = Config::get('EMAIL_BTN_PREFILL');
        $prefill = null !== $orderNumber
            ? str_replace('{pedido}', $orderNumber, $prefillRaw)
            : str_replace('{pedido}', '', $prefillRaw);
        $prefill = trim($prefill);

        $url = 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($prefill);
        $text = Config::get('EMAIL_BTN_TEXT');

        return sprintf(
            '<p style="text-align:center;margin:24px 0 8px;">'
                . '<a href="%1$s" style="background-color:#25d366;color:#ffffff;padding:12px 28px;'
                . 'border-radius:4px;text-decoration:none;font-size:14px;font-weight:bold;display:inline-block;">'
                . '%2$s</a></p>',
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($text, ENT_QUOTES)
        );
    }
}
