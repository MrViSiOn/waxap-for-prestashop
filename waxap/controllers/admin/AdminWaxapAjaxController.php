<?php
/**
 * Waxap for PrestaShop — AdminController oculto que sirve todas las peticiones AJAX.
 *
 * Equivale a los handlers wp_ajax_* del plugin WooCommerce (SessionAjax, InboxAjax, Onboarding).
 * Se registra como Tab oculta (id_parent = -1) en la instalación, de modo que es invocable por
 * token desde el back-office pero no aparece en el menú. PrestaShop enruta `action=Xxx` al
 * método `ajaxProcessXxx()`.
 *
 * @author    drappsinfo
 * @copyright Copyright (c) drappsinfo. Todos los derechos reservados.
 * @license   Propietaria — All rights reserved. Prohibida la copia o distribución no autorizada.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Waxap\Api\WrapperClient;
use Waxap\Api\WrapperException;
use Waxap\Settings\Config;

/**
 * Controlador AJAX del módulo. Token y permiso de empleado los garantiza PrestaShop.
 */
class AdminWaxapAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = false;
        // Garantiza que el autoloader del módulo (Waxap\…) esté registrado.
        Module::getInstanceByName('waxap');
    }

    /* ===================================================================
     *  SESIÓN / QR (DRAPPS-494)
     * =================================================================== */

    /** Crea una nueva sesión WhatsApp en el wrapper. */
    public function ajaxProcessCreateSession(): void
    {
        try {
            $client = new WrapperClient();
            $name = Tools::str2url((string) Configuration::get('PS_SHOP_NAME')) ?: 'tienda';
            $session = $client->createSession($name, $this->context->link->getBaseLink());
            $sessionId = (string) ($session['id'] ?? '');
            Config::set('SESSION_ID', $sessionId);
            $this->ok(['sessionId' => $sessionId]);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /** Consulta el estado de la sesión activa y devuelve el QR si corresponde. */
    public function ajaxProcessPollSession(): void
    {
        $sessionId = Config::get('SESSION_ID');
        if ('' === $sessionId) {
            $this->err($this->l('No hay sesión activa.'));
        }

        try {
            $client = new WrapperClient();
            $status = $client->getSession($sessionId);
            $currentStatus = (string) ($status['status'] ?? 'unknown');
            $qr = null;

            if (in_array($currentStatus, ['initializing', 'qr_ready'], true)) {
                try {
                    $qrResponse = $client->getQr($sessionId);
                    $qr = $qrResponse['qr'] ?? null;
                } catch (WrapperException $e) {
                    $qr = null;
                }
            }

            $phone = (string) ($status['phoneNumber'] ?? '');
            if ('ready' === $currentStatus && '' !== $phone) {
                Config::set('PHONE_NUMBER', $phone);
            }

            $this->ok([
                'status' => $currentStatus,
                'qr' => $qr,
                'phone' => '' !== $phone ? $phone : null,
            ]);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /** Desconecta la sesión activa y limpia las credenciales de sesión. */
    public function ajaxProcessDisconnect(): void
    {
        $sessionId = Config::get('SESSION_ID');
        if ('' !== $sessionId) {
            try {
                (new WrapperClient())->deleteSession($sessionId);
            } catch (WrapperException $e) {
                // Ignoramos: limpiamos localmente igualmente.
            }
        }

        Config::delete('SESSION_ID');
        Config::delete('PHONE_NUMBER');
        $this->ok();
    }

    /** Envía un mensaje de prueba al número indicado. */
    public function ajaxProcessSendTest(): void
    {
        $to = trim((string) Tools::getValue('to'));
        if ('' === $to) {
            $this->err($this->l('El número de teléfono es obligatorio.'));
        }

        $sessionId = Config::get('SESSION_ID');
        if ('' === $sessionId) {
            $this->err($this->l('No hay sesión activa.'));
        }

        $message = (string) Tools::getValue('message');

        try {
            $result = (new WrapperClient())->sendTest($sessionId, $to, $message);
            $this->ok(['messageId' => $result['messageId'] ?? '']);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /** Elimina una sesión concreta (por ID) del wrapper. */
    public function ajaxProcessDeleteSession(): void
    {
        $sessionId = (string) Tools::getValue('session_id');
        if ('' === $sessionId) {
            $this->err($this->l('ID de sesión requerido.'));
        }

        if (Config::get('SESSION_ID') === $sessionId) {
            Config::delete('SESSION_ID');
            Config::delete('PHONE_NUMBER');
        }

        try {
            (new WrapperClient())->deleteSession($sessionId);
            $this->ok();
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /* ===================================================================
     *  ONBOARDING (DRAPPS-499)
     * =================================================================== */

    /** Registra una nueva cuenta de tienda en el wrapper. */
    public function ajaxProcessRegister(): void
    {
        $email = trim((string) Tools::getValue('email'));
        $password = (string) Tools::getValue('password');

        if ('' === $email || !Validate::isEmail($email) || Tools::strlen($password) < 8) {
            $this->err($this->l('Introduce un email válido y una contraseña de al menos 8 caracteres.'));
        }

        try {
            $result = (new WrapperClient())->register($email, $password);
            $tenantId = (string) ($result['tenantId'] ?? '');
            $claimToken = (string) ($result['claimToken'] ?? '');
            if ('' === $tenantId) {
                $this->err($this->l('El servidor no devolvió un identificador de cuenta.'));
            }
            Config::set('TENANT_ID', $tenantId);
            Config::set('CLAIM_TOKEN', $claimToken);
            $this->ok(['tenantId' => $tenantId]);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /** Devuelve la URL de checkout de Stripe para el plan seleccionado. */
    public function ajaxProcessCheckoutUrl(): void
    {
        $tenantId = Config::get('TENANT_ID');
        if ('' === $tenantId) {
            $this->err($this->l('No hay cuenta registrada. Completa el paso anterior.'));
        }

        $allowed = ['basic', 'pro', 'lifetime'];
        $plan = (string) Tools::getValue('plan');
        if (!in_array($plan, $allowed, true)) {
            $plan = 'basic';
        }

        $base = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'waxap',
            'tab' => 'connection',
        ]);
        $successUrl = $base . '&payment=success';
        $cancelUrl = $base . '&payment=cancelled';

        try {
            $result = (new WrapperClient())->getCheckoutUrl($tenantId, $plan, $successUrl, $cancelUrl);
            $url = (string) ($result['url'] ?? '');
            if ('' === $url) {
                $this->err($this->l('No se pudo obtener el enlace de pago. Contacta con soporte.'));
            }
            $this->ok(['url' => $url]);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /** Consulta el estado de activación tras el pago y canjea las credenciales si procede. */
    public function ajaxProcessPollActivation(): void
    {
        $tenantId = Config::get('TENANT_ID');
        if ('' === $tenantId) {
            $this->err($this->l('No hay cuenta registrada.'));
        }

        try {
            $client = new WrapperClient();
            $result = $client->getAuthStatus($tenantId);
            $status = (string) ($result['status'] ?? 'pending_payment');

            if ('active' === $status) {
                $claimToken = Config::get('CLAIM_TOKEN');
                if ('' !== $claimToken) {
                    $credentials = $client->claimCredentials($tenantId, $claimToken);
                    Config::set('API_KEY', (string) ($credentials['apiKey'] ?? ''));
                    Config::set('HMAC_SECRET', (string) ($credentials['hmacSecret'] ?? ''));
                    Config::set('CLAIM_TOKEN', '');
                }
            }

            $this->ok(['status' => $status]);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /* ===================================================================
     *  BILLING (DRAPPS-500)
     * =================================================================== */

    /** Devuelve la URL del portal de cliente Stripe. */
    public function ajaxProcessBillingPortal(): void
    {
        $returnUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'waxap',
            'tab' => 'connection',
        ]);

        try {
            $result = (new WrapperClient())->getBillingPortalUrl($returnUrl);
            $url = (string) ($result['url'] ?? '');
            if ('' === $url) {
                $this->err($this->l('No se pudo obtener el enlace del portal.'));
            }
            $this->ok(['url' => $url]);
        } catch (WrapperException $e) {
            $this->err($e->getMessage());
        }
    }

    /* ===================================================================
     *  RESPUESTAS JSON
     * =================================================================== */

    /**
     * Envía una respuesta JSON de éxito y termina.
     *
     * @param array<string,mixed> $data
     */
    private function ok(array $data = []): void
    {
        $this->reply(true, $data);
    }

    /** Envía una respuesta JSON de error y termina. */
    private function err(string $message): void
    {
        $this->reply(false, ['message' => $message]);
    }

    /**
     * Emite la respuesta JSON con la forma {success, data} que esperan los scripts.
     *
     * @param array<string,mixed> $data
     */
    private function reply(bool $success, array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'data' => $data]);
        exit;
    }
}
