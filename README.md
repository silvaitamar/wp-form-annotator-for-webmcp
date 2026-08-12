# SilvaItamar WebMCP Form Annotator

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Anota formulários WordPress com atributos [WebMCP](https://developer.chrome.com/docs/ai/webmcp) declarativos (`toolname`, `tooldescription`, `toolparamdescription`) para que agentes de IA no navegador preencham formulários de lead e suporte com confiabilidade.

**Autor:** [Itamar Silva](https://github.com/silvaitamar) · [Perfil WordPress](https://profiles.wordpress.org/itamarsilvacc/)

**Status:** `1.0.0` — anotação opt-in para Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms e SureForms. WordPress 6.4+, PHP 8.0+.

A ficha do diretório WordPress.org é o [`readme.txt`](readme.txt) (inglês). Este README é o guia do repositório GitHub (português).

## O que faz

Injeta anotações WebMCP no markup real do `<form>` (opt-in por formulário). Em **Configurações → WebMCP Forms** a lista filtra por builder/status, permite ativar em massa, e **Annotate** abre um form por vez (nome da tool, descrição e `toolparamdescription` dos campos). Token opcional de Chrome Origin Trial.

Formulários de conversão e suporte **não** usam `toolautosubmit`: o humano confirma o envio.

O que **não** faz:

- não é um “WebMCP Bridge” REST para posts, menus ou carrinho WooCommerce;
- não gera `llms.txt` nem substitui plugins de SEO/GEO;
- não é um servidor MCP para IDEs (Cursor / Claude Desktop);
- não implementa formulário de contato próprio — só anota forms dos builders suportados.

O laboratório público de demos fica em [`wp-webmcp-forms`](https://github.com/silvaitamar/wp-webmcp-forms) e **não** é submetido ao WordPress.org.

## Requisitos

- WordPress 6.4+
- PHP 8.0+
- Um builder suportado com pelo menos um formulário

Para testar tools hoje: Chrome com flag WebMCP (`chrome://flags/#enable-webmcp-testing`) ou token de Origin Trial, mais a extensão [Model Context Tool Inspector](https://chromewebstore.google.com/detail/gbpdfapgefenggkahomfgkhfehlcenpd).

## Instalação

### A partir do repositório

```bash
git clone https://github.com/silvaitamar/wp-webmcp-form-annotator.git
cd wp-webmcp-form-annotator
```

Copie a pasta para `wp-content/plugins/silvaitamar-webmcp-form-annotator/` (ou use o clone diretamente nesse caminho) e ative em **Plugins**. Instale um dos builders suportados, crie um form e abra **Configurações → WebMCP Forms**.

### A partir de uma release

Quando houver ZIP em [Releases](https://github.com/silvaitamar/wp-webmcp-form-annotator/releases), extraia em `wp-content/plugins/` e ative o plugin.

## Desenvolvimento

```bash
composer install
composer lint   # PHPCS + WPCS, prefixo siwmfa
composer test   # Annotator/Registry sem WordPress
bash scripts/build-release-zip.sh
bash scripts/validate-release-zip.sh
```

Traduções: o domínio é o slug (`silvaitamar-webmcp-form-annotator`). O `.pot` fica em `languages/`. Depois da listagem no WordPress.org, o GlotPress gera language packs — não versionar `.mo` de locales que já têm pack.

O que vai ao WordPress.org (`readme.txt`, UI do plugin, blueprint/assets) e as notas versionadas em `docs/` ficam em **inglês**. Checklist de QA manual: `docs/QA.md` (pt-BR, arquivo local, fora do git).

Estrutura principal:

```text
src/           Código PHP (PSR-4)
assets/        CSS do admin e JS do adapter Ninja
languages/     POT (GlotPress após a listagem)
tests/         Testes de núcleo sem WordPress
silvaitamar-webmcp-form-annotator.php   Bootstrap
readme.txt     Metadados para o WordPress.org (inglês)
```

## Empacotamento

O ZIP de distribuição exclui `vendor/`, `.github/`, `scripts/`, `tests/`, `.wordpress-org/`, `composer.*`, `phpcs.xml.dist` e docs. Ver [`.distignore`](.distignore) e [`scripts/build-release-zip.sh`](scripts/build-release-zip.sh).

Live Preview (após aprovação wp.org): [`.wordpress-org/blueprints/blueprint.json`](.wordpress-org/blueprints/blueprint.json) — Fluent Forms + login no admin + form já anotado. SVN: `assets/blueprints/blueprint.json`. O diretório injeta este plugin; o blueprint não instala o próprio slug.

## Licença

GPL-2.0-or-later — veja [LICENSE](LICENSE).

## Changelog

Veja [CHANGELOG.md](CHANGELOG.md).
