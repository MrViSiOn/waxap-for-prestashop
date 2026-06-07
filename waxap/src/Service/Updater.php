<?php
/**
 * Waxap for PrestaShop — auto-updater desde GitHub Releases.
 *
 * Equivale al Plugin Update Checker que usa el plugin WooCommerce. Comprueba la última release
 * publicada en el repositorio de GitHub configurado, avisa en el back-office y permite descargar
 * el ZIP del asset y aplicarlo. El repositorio es parametrizable (Config WAXAP_GITHUB_REPO);
 * por defecto MrViSiOn/waxap-for-prestashop (aún no existe).
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

use Configuration;
use Waxap\Settings\Config;

/**
 * Comprueba, descarga y aplica actualizaciones desde GitHub Releases.
 */
final class Updater
{
    /** TTL de la caché de la comprobación de versión (12 horas). */
    private const CACHE_TTL = 43200;

    private const CACHE_KEY = 'WAXAP_UPDATE_CACHE';
    private const CACHE_AT_KEY = 'WAXAP_UPDATE_CACHE_AT';

    /**
     * Devuelve la última release (cacheada) o null si no se puede determinar.
     *
     * @return array{version:string,zip:string,url:string}|null
     */
    public static function getLatestRelease(bool $force = false): ?array
    {
        $cachedAt = (int) Configuration::get(self::CACHE_AT_KEY);
        $cached = Configuration::get(self::CACHE_KEY);

        if (!$force && $cached && (time() - $cachedAt) < self::CACHE_TTL) {
            $decoded = json_decode((string) $cached, true);

            return is_array($decoded) ? $decoded : null;
        }

        $release = self::fetchLatestFromGitHub();
        if (null !== $release) {
            Configuration::updateValue(self::CACHE_KEY, json_encode($release));
            Configuration::updateValue(self::CACHE_AT_KEY, (string) time());
        }

        return $release;
    }

    /**
     * Indica si hay una versión más nueva que la instalada.
     *
     * @param string $currentVersion Versión actualmente instalada del módulo.
     */
    public static function isUpdateAvailable(string $currentVersion): bool
    {
        $latest = self::getLatestRelease();
        if (null === $latest || '' === $latest['version']) {
            return false;
        }

        return version_compare($latest['version'], $currentVersion, '>');
    }

    /**
     * Descarga el ZIP de la última release y lo extrae sobre el directorio de módulos.
     *
     * @return bool True si se descargó y extrajo correctamente.
     */
    public static function downloadAndInstall(): bool
    {
        $latest = self::getLatestRelease(true);
        if (null === $latest || '' === $latest['zip']) {
            return false;
        }

        // El usuario del servidor web debe poder escribir en modules/ para extraer la actualización.
        if (!is_writable(_PS_MODULE_DIR_)) {
            return false;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'waxap_') ?: '';
        if ('' === $tmp) {
            return false;
        }

        $data = self::httpGet($latest['zip'], true);
        if (null === $data) {
            @unlink($tmp);

            return false;
        }
        file_put_contents($tmp, $data);

        if (!class_exists('ZipArchive')) {
            @unlink($tmp);

            return false;
        }

        $zip = new \ZipArchive();
        if (true !== $zip->open($tmp)) {
            @unlink($tmp);

            return false;
        }

        // El ZIP de release debe contener la carpeta `waxap/` como raíz.
        $ok = $zip->extractTo(_PS_MODULE_DIR_);
        $zip->close();
        @unlink($tmp);

        if ($ok) {
            self::clearCaches();
        }

        return $ok;
    }

    /**
     * Limpia las cachés de PrestaShop tras aplicar la actualización, para que se carguen
     * los archivos nuevos: Smarty, contenedor de Symfony e índice de clases del autoloader.
     * Sin esto, las clases nuevas añadidas en una versión no se autoloadean hasta limpiar caché.
     */
    private static function clearCaches(): void
    {
        if (method_exists('Tools', 'clearCache')) {
            \Tools::clearCache();
        }
        if (method_exists('Tools', 'clearSf2Cache')) {
            try {
                \Tools::clearSf2Cache();
            } catch (\Throwable $e) {
                // La limpieza de la caché de Symfony no es bloqueante; seguimos.
            }
        }
        // Forzar regeneración del índice de clases para autoloadear ficheros nuevos.
        if (defined('_PS_CACHE_DIR_') && is_file(_PS_CACHE_DIR_ . 'class_index.php')) {
            @unlink(_PS_CACHE_DIR_ . 'class_index.php');
        }
    }

    /**
     * Consulta la API de GitHub para obtener la última release.
     *
     * @return array{version:string,zip:string,url:string}|null
     */
    private static function fetchLatestFromGitHub(): ?array
    {
        $repo = trim(Config::get('GITHUB_REPO'));
        if ('' === $repo) {
            return null;
        }

        $body = self::httpGet('https://api.github.com/repos/' . $repo . '/releases/latest');
        if (null === $body) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['tag_name'])) {
            return null;
        }

        $version = ltrim((string) $data['tag_name'], 'vV');

        // Preferimos el primer asset .zip; si no hay, el zipball de fuentes.
        $zip = '';
        if (!empty($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                $name = (string) ($asset['name'] ?? '');
                if (str_ends_with(strtolower($name), '.zip')) {
                    $zip = (string) ($asset['browser_download_url'] ?? '');
                    break;
                }
            }
        }
        if ('' === $zip) {
            $zip = (string) ($data['zipball_url'] ?? '');
        }

        return [
            'version' => $version,
            'zip' => $zip,
            'url' => (string) ($data['html_url'] ?? ''),
        ];
    }

    /**
     * Realiza un GET HTTP con cURL. GitHub exige cabecera User-Agent.
     *
     * @param bool $followBinary Si true, sigue redirecciones (descarga de assets).
     */
    private static function httpGet(string $url, bool $followBinary = false): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $followBinary ? 60 : 15,
            CURLOPT_USERAGENT => 'Waxap-PrestaShop-Updater',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (false === $response || $code >= 400) {
            return null;
        }

        return (string) $response;
    }
}
