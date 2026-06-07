# Traducciones — Waxap for PrestaShop

El módulo usa el **sistema de traducción moderno** de PrestaShop 8. Todas las cadenas están
marcadas con:

- En PHP: `$this->trans('texto', [], 'Modules.Waxap.Admin')` (back-office) y
  `$this->trans('texto', [], 'Modules.Waxap.Shop')` (front, opt-in de checkout).
- En Smarty: `{l s='texto' d='Modules.Waxap.Admin'}`.

## Idioma fuente

El **idioma fuente (source) es español (es_ES)**: las cadenas que ves en el código son ya el
texto en español, por lo que una tienda en español no necesita ningún catálogo — se muestran tal
cual. Esto es coherente con el resto del producto Waxap, cuyo mercado principal es España.

## Dominios

| Dominio | Uso |
|---|---|
| `Modules.Waxap.Admin` | Toda la interfaz del back-office (pestañas, formularios, avisos). |
| `Modules.Waxap.Shop` | Cadenas visibles en el front (checkbox de opt-in en el checkout). |

## Catálogos incluidos

- [`en-US.xlf`](./en-US.xlf) — inglés.
- [`pt-BR.xlf`](./pt-BR.xlf) — portugués (Brasil).

Son ficheros XLIFF 1.2 con las unidades de traducción (source en español → target en el idioma
correspondiente). Cubren las cadenas más visibles de la interfaz.

## Cómo añadir o completar un idioma

1. Back-office → **Internacional → Traducciones**.
2. Tipo de traducción: **Traducciones de módulos instalados** → módulo **Waxap** → idioma.
3. Completa las cadenas y guarda. PrestaShop generará/actualizará el catálogo del idioma.

Alternativamente, edita directamente los `.xlf` de esta carpeta y vuelve a empaquetar el módulo.
