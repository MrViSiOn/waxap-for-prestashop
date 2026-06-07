# waxap-for-prestashop

Módulo de **PrestaShop 8** del producto [Waxap](https://waxap.shop): notificaciones WhatsApp
transaccionales para tu tienda, con tu propio número vinculado por QR. Es el port nativo a
PrestaShop del plugin `waxap-for-woocommerce`, consumiendo el mismo backend (wrapper Waxap),
que es agnóstico de plataforma.

> **Licencia propietaria / comercial.** Todos los derechos reservados. Ver [`LICENSE`](./LICENSE).

## El módulo vive en [`waxap/`](./waxap)

PrestaShop exige que la carpeta del módulo se llame igual que su clase principal (`waxap`).
Como este repositorio se llama `waxap-for-prestashop`, el módulo instalable está en la
subcarpeta [`waxap/`](./waxap). El ZIP de distribución contiene esa carpeta como raíz.

Consulta [`waxap/README.md`](./waxap/README.md) para instalación, estructura y configuración.

## Desarrollo

- PHP 8.1+, `declare(strict_types=1);`, PSR-12.
- PrestaShop 8.0+.
- El código de lógica vive en `waxap/src/` (namespace PSR-4 `Waxap\`).

```bash
# Lint de todos los .php del módulo
find waxap -name '*.php' -print0 | xargs -0 -n1 php -l
```
