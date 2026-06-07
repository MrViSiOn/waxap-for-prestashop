# Waxap for PrestaShop 8

Módulo nativo de PrestaShop 8 que envía **notificaciones WhatsApp transaccionales** a tus
clientes (pedido en preparación, enviado, entregado…) usando tu propio número de WhatsApp,
vinculado por código QR. Es el equivalente para PrestaShop del plugin
[`waxap-for-woocommerce`](https://github.com/MrViSiOn/waxap-for-woocommerce) y consume el
mismo backend (wrapper Waxap), que es agnóstico de plataforma.

> Producto comercial. Licencia **propietaria** — ver `LICENSE` en la raíz del repositorio.

## Cómo funciona

- Tú aportas tu número de WhatsApp y lo vinculas escaneando un QR desde el back-office.
- Los emails transaccionales de PrestaShop incluyen un botón `wa.me` → es el cliente final
  quien inicia la conversación, lo que minimiza el riesgo de baneo.
- Una tienda = una sesión = un número de WhatsApp.

## Requisitos

- PrestaShop **8.0+**
- PHP **8.1+**
- Acceso saliente HTTPS al wrapper Waxap (`https://api.waxap.shop` por defecto, configurable).

## Instalación

Este repositorio (`waxap-for-prestashop`) contiene el módulo dentro de la subcarpeta
[`waxap/`](./). **La carpeta del módulo se llama `waxap`** porque PrestaShop exige que el
directorio coincida con el nombre de la clase principal (`Waxap` → `waxap.php`).

Para instalar:

1. Comprime **el contenido de la carpeta `waxap/`** en un ZIP cuyo directorio raíz sea `waxap/`.
2. Back-office → **Módulos → Subir un módulo** → selecciona el ZIP.
3. Pulsa **Instalar** y luego **Configurar**.

> El auto-updater (ver más abajo) descarga exactamente ese ZIP desde GitHub Releases.

## Estructura

```
waxap/
├── waxap.php                 Clase principal Module (install/uninstall/hooks/getContent)
├── config.xml               Metadatos del módulo
├── composer.json            PSR-4 Waxap\ → src/ (autoload propio, no requiere composer en prod)
├── logo.png
├── src/
│   ├── Api/WrapperClient.php     Cliente HTTP del wrapper (auth, sesiones, eventos HMAC, inbox…)
│   ├── Settings/Config.php       Wrapper de Configuration con prefijo WAXAP_ y defaults
│   ├── Service/                  Normalización de teléfono, render de plantillas, idempotencia, updater
│   └── Install/Installer.php     Hooks, tablas, tabs ocultas, defaults
├── controllers/admin/       AdminController(s) ocultos que sirven el AJAX (QR, inbox, onboarding)
├── views/templates/admin/   Plantillas Smarty de las pestañas de configuración
├── views/js/  views/css/    Assets del back-office
├── translations/            Catálogos es_ES (principal), en, pt_BR
└── upgrade/                 Scripts upgrade-X.Y.Z.php
```

## Decisión de estructura (repo ↔ módulo)

A diferencia de WordPress, **PrestaShop obliga a que el nombre de la carpeta del módulo sea
idéntico al de la clase principal** (`waxap`). Como el repositorio se llama
`waxap-for-prestashop`, no podemos usar la raíz del repo como carpeta del módulo (PrestaShop
no lo reconocería). Por eso el módulo vive en la subcarpeta `waxap/` y el ZIP de distribución
contiene esa carpeta como raíz. Así la instalación y el auto-updater funcionan sin renombrados
manuales.

## Configuración

La página de configuración tiene 6 pestañas:

1. **Conexión** — registro/login, estado de la suscripción y desconexión.
2. **Número WhatsApp** — vinculación por QR y mensaje de prueba.
3. **Notificaciones** — qué estados de pedido notifican y la plantilla de cada uno.
4. **Email** — botón wa.me en los emails transaccionales.
5. **Historial** — registro paginado de mensajes enviados.
6. **Mensajes** — bandeja de entrada estilo WhatsApp Web.

Mientras la cuenta no esté conectada solo se muestra la pestaña **Conexión** (onboarding).

## Variables de plantilla

En las plantillas de notificación y en el mensaje prefabricado del botón de email puedes usar:

`{nombre}` · `{pedido}` · `{estado}` · `{total}` · `{enlace}`

## Backend

El módulo **no** habla con WhatsApp directamente: consume la API REST del wrapper Waxap
(`OPENWA` por debajo). La URL base es configurable en la pestaña Conexión. Los eventos de
pedido se firman con **HMAC-SHA256** (`x-tenant-id`, `x-timestamp`, `x-signature`).
