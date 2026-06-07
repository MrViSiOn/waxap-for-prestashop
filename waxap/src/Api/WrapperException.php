<?php
/**
 * Waxap for PrestaShop — excepción de la API del wrapper.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

namespace Waxap\Api;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Exception;

/**
 * Excepción lanzada cuando la API del wrapper devuelve un error o falla el transporte.
 *
 * Es el equivalente al WP_Error que devuelve el WrapperClient de WooCommerce: el código de
 * estado HTTP queda accesible vía getStatusCode() para que el llamante pueda diferenciar,
 * por ejemplo, un 401 (credenciales) de un 5xx (servidor caído).
 */
final class WrapperException extends Exception
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode = 0)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
    }

    /** Código de estado HTTP asociado al error (0 si fue un fallo de transporte). */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
