<?php
/**
 * Waxap for PrestaShop — normalización de números de teléfono.
 *
 * Port de OrderEvents::normalize_phone() (WooCommerce): deja solo dígitos, elimina ceros a
 * la izquierda y antepone el prefijo de país si el número no lo lleva ya.
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

use Waxap\Settings\Config;

/**
 * Normaliza teléfonos de cliente a formato internacional solo-dígitos (sin '+').
 */
final class PhoneNormalizer
{
    /**
     * Normaliza un número en crudo a formato internacional solo-dígitos.
     *
     * @param string $raw Número tal cual viene del Address (phone / phone_mobile).
     *
     * @return string Número normalizado, o cadena vacía si no contiene dígitos.
     */
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ('' === $digits) {
            return '';
        }

        $digits = ltrim($digits, '0');
        if ('' === $digits) {
            return '';
        }

        $countryCode = Config::get('PHONE_COUNTRY_CODE');
        if ('' === $countryCode) {
            $countryCode = '34';
        }

        if (!str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . $digits;
        }

        return $digits;
    }

    /**
     * Extrae y normaliza el teléfono preferente de una dirección.
     *
     * Prioriza el móvil (phone_mobile) sobre el fijo (phone), igual que tiene sentido para
     * WhatsApp; cae al fijo si no hay móvil.
     *
     * @param \Address|null $address Dirección del pedido (puede ser null).
     *
     * @return string Teléfono normalizado o cadena vacía.
     */
    public static function fromAddress(?\Address $address): string
    {
        if (null === $address) {
            return '';
        }

        $mobile = self::normalize((string) $address->phone_mobile);
        if ('' !== $mobile) {
            return $mobile;
        }

        return self::normalize((string) $address->phone);
    }
}
