<?php
/**
 * Waxap for PrestaShop — cliente HTTP para la API REST del wrapper Waxap.
 *
 * Port 1:1 de WaNotifier\Api\WrapperClient (WooCommerce). Mismos endpoints, misma firma
 * HMAC-SHA256 de eventos y mismas cabeceras de autenticación. La diferencia es el transporte:
 * aquí usamos cURL en lugar de wp_remote_request, y devolvemos arrays lanzando
 * WrapperException en caso de error (en vez de WP_Error).
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

use Waxap\Settings\Config;

/**
 * Cliente HTTP para comunicarse con la API REST del wrapper Waxap.
 */
final class WrapperClient
{
    /** URL base del wrapper (sin barra final). */
    private string $baseUrl;

    /** API key del tenant autenticado. */
    private string $apiKey;

    /** ID del tenant en el wrapper. */
    private string $tenantId;

    /** Inicializa el cliente con las credenciales almacenadas en Config. */
    public function __construct()
    {
        $this->baseUrl = rtrim(Config::get('WRAPPER_URL'), '/');
        $this->apiKey = Config::get('API_KEY');
        $this->tenantId = Config::get('TENANT_ID');
    }

    /* ===================================================================
     *  AUTH
     * =================================================================== */

    /**
     * Registra la tienda y devuelve tenantId + claimToken de un solo uso.
     *
     * @return array{tenantId:string,claimToken:string}
     *
     * @throws WrapperException
     */
    public function register(string $email, string $password): array
    {
        return $this->request('POST', '/v1/auth/register', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * Canjea el claim token de un solo uso por las credenciales del tenant.
     *
     * Solo funciona una vez: el wrapper marca el token como consumido en la primera llamada
     * exitosa; reintentos posteriores reciben 401.
     *
     * @return array{apiKey:string,hmacSecret:string}
     *
     * @throws WrapperException
     */
    public function claimCredentials(string $tenantId, string $claimToken): array
    {
        return $this->request('POST', '/v1/auth/claim', [
            'tenantId' => $tenantId,
            'claimToken' => $claimToken,
        ]);
    }

    /**
     * Consulta el estado de activación de un tenant (polling post-pago Stripe).
     *
     * @return array{status:string}
     *
     * @throws WrapperException
     */
    public function getAuthStatus(string $tenantId): array
    {
        return $this->request('GET', '/v1/auth/status/' . rawurlencode($tenantId));
    }

    /**
     * Inicia sesión con email y contraseña y devuelve las credenciales del tenant.
     *
     * @return array{tenantId:string,apiKey:string,hmacSecret:string}
     *
     * @throws WrapperException
     */
    public function login(string $email, string $password): array
    {
        return $this->request('POST', '/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /* ===================================================================
     *  SESIONES (requieren API key)
     * =================================================================== */

    /**
     * Crea una sesión en el wrapper y la arranca.
     *
     * @return array{id:string,status:string}
     *
     * @throws WrapperException
     */
    public function createSession(string $name, string $storeUrl = ''): array
    {
        $body = ['name' => $name];
        if ('' !== $storeUrl) {
            $body['storeUrl'] = $storeUrl;
        }

        return $this->request('POST', '/v1/sessions', $body, true);
    }

    /**
     * Lista todas las sesiones del tenant.
     *
     * @return array<int,array<string,mixed>>
     *
     * @throws WrapperException
     */
    public function getSessions(): array
    {
        return $this->request('GET', '/v1/sessions', [], true);
    }

    /**
     * Obtiene el estado actual de la sesión.
     *
     * @return array{id:string,status:string}
     *
     * @throws WrapperException
     */
    public function getSession(string $sessionId): array
    {
        return $this->request('GET', '/v1/sessions/' . rawurlencode($sessionId), [], true);
    }

    /**
     * Obtiene el QR actual (para polling).
     *
     * @return array{sessionId:string,qr:string}
     *
     * @throws WrapperException
     */
    public function getQr(string $sessionId): array
    {
        return $this->request('GET', '/v1/sessions/' . rawurlencode($sessionId) . '/qr', [], true);
    }

    /**
     * Envía un mensaje de prueba a un número de teléfono.
     *
     * @return array{messageId:string}
     *
     * @throws WrapperException
     */
    public function sendTest(string $sessionId, string $to, string $message = ''): array
    {
        $body = ['to' => $to];
        if ('' !== $message) {
            $body['message'] = $message;
        }

        return $this->request('POST', '/v1/sessions/' . rawurlencode($sessionId) . '/send-test', $body, true);
    }

    /**
     * Elimina la sesión del wrapper.
     *
     * @throws WrapperException
     */
    public function deleteSession(string $sessionId): bool
    {
        $this->request('DELETE', '/v1/sessions/' . rawurlencode($sessionId), [], true);

        return true;
    }

    /* ===================================================================
     *  BILLING
     * =================================================================== */

    /**
     * Crea una Stripe Checkout Session y devuelve la URL de pago.
     *
     * @return array{url:string}
     *
     * @throws WrapperException
     */
    public function getCheckoutUrl(string $tenantId, string $plan = 'basic', string $successUrl = '', string $cancelUrl = ''): array
    {
        return $this->request('POST', '/v1/billing/checkout', [
            'tenantId' => $tenantId,
            'plan' => $plan,
            'successUrl' => $successUrl,
            'cancelUrl' => $cancelUrl,
        ]);
    }

    /**
     * Devuelve el uso mensual y estado de suscripción del tenant.
     *
     * @return array{status:string,used:int,quota:int,quotaResetAt:string|null}
     *
     * @throws WrapperException
     */
    public function getUsage(): array
    {
        return $this->request('GET', '/v1/billing/usage', [], true);
    }

    /**
     * Obtiene la URL del portal de cliente Stripe para gestionar la suscripción.
     *
     * @return array{url:string}
     *
     * @throws WrapperException
     */
    public function getBillingPortalUrl(string $returnUrl = ''): array
    {
        $body = '' !== $returnUrl ? ['returnUrl' => $returnUrl] : [];

        return $this->request('POST', '/v1/billing/portal', $body, true);
    }

    /* ===================================================================
     *  MENSAJES / INBOX
     * =================================================================== */

    /**
     * Devuelve el historial de mensajes WhatsApp enviados por este tenant.
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}
     *
     * @throws WrapperException
     */
    public function getMessageLog(int $limit = 20, int $offset = 0): array
    {
        return $this->request('GET', '/v1/messages', [], true, [], [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);
    }

    /**
     * Lista las conversaciones WhatsApp de la bandeja de entrada del tenant.
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}
     *
     * @throws WrapperException
     */
    public function getInboxConversations(int $limit = 20, int $offset = 0): array
    {
        return $this->request('GET', '/v1/inbox/conversations', [], true, [], [
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        ]);
    }

    /**
     * Devuelve el hilo de mensajes con un número de teléfono.
     *
     * @return array{data: array<int,array<string,mixed>>, total: int, limit: int, offset: int}
     *
     * @throws WrapperException
     */
    public function getInboxThread(string $phone, int $limit = 50, int $offset = 0): array
    {
        return $this->request(
            'GET',
            '/v1/inbox/conversations/' . rawurlencode($phone) . '/messages',
            [],
            true,
            [],
            [
                'limit' => (string) $limit,
                'offset' => (string) $offset,
            ]
        );
    }

    /**
     * Envía un mensaje WhatsApp desde la bandeja de entrada.
     *
     * @return array<string,mixed>
     *
     * @throws WrapperException
     */
    public function sendInboxMessage(string $phone, string $text): array
    {
        return $this->request(
            'POST',
            '/v1/inbox/conversations/' . rawurlencode($phone) . '/send',
            ['text' => $text],
            true
        );
    }

    /**
     * Marca una conversación como leída (resetea unreadCount).
     *
     * @throws WrapperException
     */
    public function markInboxRead(string $phone): bool
    {
        $this->request('POST', '/v1/inbox/conversations/' . rawurlencode($phone) . '/read', [], true);

        return true;
    }

    /* ===================================================================
     *  EVENTOS (firmados con HMAC)
     * =================================================================== */

    /**
     * Envía un evento de cambio de estado de pedido al wrapper, firmado con HMAC.
     *
     * Firma: 'sha256=' . hash_hmac('sha256', timestamp . '.' . body, secret)
     * Cabeceras: x-tenant-id, x-timestamp, x-signature.
     *
     * @param array<string,mixed> $payload
     *
     * @throws WrapperException
     */
    public function sendEvent(array $payload): bool
    {
        $secret = Config::get('HMAC_SECRET');
        $timestamp = (string) time();
        $body = (string) json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $this->request('POST', '/v1/events', $payload, false, [
            'x-tenant-id' => $this->tenantId,
            'x-timestamp' => $timestamp,
            'x-signature' => $signature,
        ]);

        return true;
    }

    /* ===================================================================
     *  TRANSPORTE
     * =================================================================== */

    /**
     * Realiza una petición HTTP a la API del wrapper mediante cURL.
     *
     * @param array<string,mixed>  $body         Cuerpo (se serializa como JSON).
     * @param bool                 $auth         Si es true, adjunta x-tenant-id + x-api-key.
     * @param array<string,string> $extraHeaders Cabeceras adicionales (p. ej. firma HMAC).
     * @param array<string,string> $query        Parámetros de query string.
     *
     * @return array<string,mixed>
     *
     * @throws WrapperException
     */
    private function request(
        string $method,
        string $path,
        array $body = [],
        bool $auth = false,
        array $extraHeaders = [],
        array $query = []
    ): array {
        $url = $this->baseUrl . $path;
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $headers = ['Content-Type: application/json', 'Accept: application/json'];

        if ($auth) {
            $headers[] = 'x-tenant-id: ' . $this->tenantId;
            $headers[] = 'x-api-key: ' . $this->apiKey;
        }

        foreach ($extraHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) json_encode($body));
        }

        $rawResponse = curl_exec($ch);

        if (false === $rawResponse) {
            $error = curl_error($ch) ?: 'Error de conexión con el servidor.';
            curl_close($ch);

            throw new WrapperException($error, 0);
        }

        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $rawResponse, true);

        if ($code >= 400) {
            $message = is_array($data) && isset($data['message'])
                ? (string) $data['message']
                : 'Error desconocido';

            // Si la API rechaza las credenciales en una request autenticada, el tenant fue
            // cancelado/desactivado en el servidor: limpiamos las credenciales locales.
            if ($auth && 401 === $code && Config::isConnected()) {
                Config::disconnect();
            }

            throw new WrapperException($message, $code);
        }

        return is_array($data) ? $data : [];
    }
}
