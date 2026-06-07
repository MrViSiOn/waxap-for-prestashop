<?php
/**
 * Waxap for PrestaShop — gestión de configuración (equivalente a Settings de WooCommerce).
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

namespace Waxap\Settings;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Configuration;

/**
 * Envuelve la API Configuration de PrestaShop con prefijo y valores por defecto.
 *
 * Mantiene paridad con WaNotifier\Settings del plugin WooCommerce, pero apoyándose
 * en Configuration::get()/updateValue()/deleteByName() en lugar de wp_options.
 */
final class Config
{
    /** Prefijo de todas las claves de configuración del módulo. */
    public const PREFIX = 'WAXAP_';

    /**
     * Valores por defecto de las opciones del módulo.
     *
     * Las claves de plantilla por estado (WAXAP_TEMPLATE_<idEstado>) no se listan aquí
     * porque dependen de los OrderState del shop; se resuelven con TPL_DEFAULT como fallback.
     *
     * @var array<string,string>
     */
    private const DEFAULTS = [
        'WRAPPER_URL'        => 'https://api.waxap.shop',
        'API_KEY'            => '',
        'TENANT_ID'          => '',
        'CLAIM_TOKEN'        => '',
        'SESSION_ID'         => '',
        'HMAC_SECRET'        => '',
        'PHONE_NUMBER'       => '',
        'PHONE_COUNTRY_CODE' => '34',
        // IDs de OrderState por defecto en una instalación PS estándar:
        // 2=Pago aceptado, 3=Preparación en curso, 4=Enviado, 5=Entregado.
        'NOTIFY_STATUSES'    => '2,3,4,5',
        'EMAIL_BTN_ENABLED'  => '1',
        'EMAIL_BTN_TEXT'     => '¿Tienes dudas? Escríbenos por WhatsApp',
        'EMAIL_BTN_PREFILL'  => 'Hola, tengo una consulta sobre mi pedido #{pedido}',
        // Plantilla genérica usada cuando un estado no tiene plantilla propia.
        'TPL_DEFAULT'        => '¡Hola {nombre}! Tu pedido #{pedido} ahora está: {estado}. 📦',
        // Repositorio de releases para el auto-updater (DRAPPS-505). Parametrizable.
        'GITHUB_REPO'        => 'MrViSiOn/waxap-for-prestashop',
    ];

    /**
     * Obtiene el valor de una opción del módulo.
     *
     * @param string $key Clave sin prefijo (p. ej. 'API_KEY').
     */
    public static function get(string $key): string
    {
        $default = self::DEFAULTS[$key] ?? '';
        $value = Configuration::get(self::PREFIX . $key);

        // Configuration::get() devuelve false si la clave no existe.
        if (false === $value || null === $value) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Guarda el valor de una opción del módulo.
     *
     * @param string $key   Clave sin prefijo.
     * @param string $value Valor a almacenar.
     */
    public static function set(string $key, string $value): void
    {
        Configuration::updateValue(self::PREFIX . $key, $value);
    }

    /**
     * Elimina una opción del módulo.
     *
     * @param string $key Clave sin prefijo.
     */
    public static function delete(string $key): void
    {
        Configuration::deleteByName(self::PREFIX . $key);
    }

    /** Indica si el módulo está conectado a un tenant (tiene API key). */
    public static function isConnected(): bool
    {
        return '' !== self::get('API_KEY');
    }

    /** Indica si existe una sesión WhatsApp activa guardada. */
    public static function hasSession(): bool
    {
        return '' !== self::get('SESSION_ID');
    }

    /** Elimina todas las credenciales del módulo (desconexión completa). */
    public static function disconnect(): void
    {
        foreach (['API_KEY', 'TENANT_ID', 'CLAIM_TOKEN', 'SESSION_ID', 'HMAC_SECRET', 'PHONE_NUMBER'] as $key) {
            self::set($key, '');
        }
    }

    /**
     * Devuelve el conjunto de claves base con sus valores por defecto.
     *
     * Usado por el instalador para sembrar la configuración inicial.
     *
     * @return array<string,string>
     */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }
}
