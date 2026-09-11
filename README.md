# Form Annotator for WebMCP

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/silvaitamar-form-annotator-for-webmcp.svg)](https://wordpress.org/plugins/silvaitamar-form-annotator-for-webmcp/)

Plugin WordPress que anota formulários já existentes com atributos [WebMCP](https://developer.chrome.com/docs/ai/webmcp) declarativos (`toolname`, `tooldescription`, `toolparamdescription`). Agentes de IA no navegador (Chrome com WebMCP) descobrem o form na página e preenchem lead, contato, suporte, busca ou filtro sem adivinhar o DOM.

**Autor:** [Itamar Silva](https://github.com/silvaitamar) · [Perfil WordPress](https://profiles.wordpress.org/itamarsilvacc/) · [Plugin no WordPress.org](https://wordpress.org/plugins/silvaitamar-form-annotator-for-webmcp/)

**Status:** `1.1.0` — anotação opt-in para Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, SureForms e Jetpack Forms (forms sincronizados); também Site search (tema / `core/search`), Filter Everything e Search & Filter. WordPress 6.4–7.1, PHP 8.0+.

## O que faz

Injeta anotações WebMCP no markup real do `<form>` (opt-in por formulário). Em **Configurações → Form Annotator** a lista filtra por builder/status, permite ativar em massa, e **Annotate** abre um form por vez (nome da tool, descrição e `toolparamdescription` dos campos). Token opcional de Chrome Origin Trial. Soft-deps: cada adapter só carrega se o plugin correspondente estiver ativo (Site search aparece sempre).

Não substitui o plugin de formulário: CF7, Fluent, WPForms, Jetpack e os demais continuam criando e processando o form. Site search e filtros só anotam o markup que o tema ou o plugin de filtro já renderizam — este produto **não** embarca UI de busca ou filtro própria.

O que **não** faz:

- não é um “WebMCP Bridge” REST para posts, menus ou carrinho WooCommerce;
- não gera `llms.txt` nem substitui plugins de SEO / GEO / discovery;
- não é um servidor MCP para IDEs (Cursor / Claude Desktop) e não precisa de API key;
- não implementa formulário de contato próprio — só anota forms dos builders suportados;
- lead, contato e suporte **nunca** usam `toolautosubmit` (confirmação humana);
- busca e filtro (GET idempotente) **podem** usar `toolautosubmit` quando você habilitar no editor.

O laboratório público de demos fica em [`wp-webmcp-forms`](https://github.com/silvaitamar/wp-webmcp-forms) e **não** é submetido ao WordPress.org.

Compatibilidade detalhada (cache, PSI, Jetpack synced, Apply button do Filter Everything, term IDs do Search & Filter): [`docs/COMPATIBILITY.md`](docs/COMPATIBILITY.md). Fixtures Execute Tool: [`docs/EXECUTE-TOOL.md`](docs/EXECUTE-TOOL.md).

## Perguntas frequentes

**WebMCP é o mesmo que MCP do Cursor?** Não. WebMCP é API de browser na página. MCP de IDE é outro protocolo.

**O agente envia o formulário sozinho?** Lead, contato e suporte: não — o visitante confirma. Busca e filtro: só se `toolautosubmit` estiver habilitado nesse form (GET idempotente).

**Isso é plugin de SEO / GEO / `llms.txt`?** Não. Só anota o `<form>` para agentes no navegador. Não escreve `llms.txt`, sitemap nem schema para motores de busca. O audit Lighthouse Agentic Browsing `webmcp-form-coverage` procura esses atributos no markup real; o plugin não garante score.

**Preciso de ChatGPT, servidor MCP ou REST?** Não. A anotação é HTML no form. Para testar hoje: Chrome com flag WebMCP (ou Origin Trial) + extensão inspector.

## Requisitos

- WordPress 6.4+
- PHP 8.0+
- Para tools de lead: um builder suportado com pelo menos um formulário
- Site search / Filter Everything / Search & Filter: o markup correspondente já presente no site

Para testar tools hoje: Chrome com flag WebMCP (`chrome://flags/#enable-webmcp-testing`) ou token de Origin Trial, mais a extensão [Model Context Tool Inspector](https://chromewebstore.google.com/detail/gbpdfapgefenggkahomfgkhfehlcenpd).

## Instalação

### A partir do WordPress.org

Instale **Form Annotator for WebMCP** pelo painel **Plugins → Adicionar novo**, ou baixe o ZIP em [wordpress.org/plugins/silvaitamar-form-annotator-for-webmcp](https://wordpress.org/plugins/silvaitamar-form-annotator-for-webmcp/).

### A partir de uma release GitHub

ZIP em [Releases](https://github.com/silvaitamar/wp-form-annotator-for-webmcp/releases): extraia em `wp-content/plugins/` e ative o plugin.

### A partir do repositório

```bash
git clone https://github.com/silvaitamar/wp-form-annotator-for-webmcp.git
cd wp-form-annotator-for-webmcp
```

Copie a pasta para `wp-content/plugins/silvaitamar-form-annotator-for-webmcp/` (ou use o clone diretamente nesse caminho) e ative em **Plugins**. Abra **Configurações → Form Annotator**.

## Desenvolvimento

```bash
composer install
composer lint   # PHPCS + WPCS, prefixo siwmfa
composer test   # Annotator/Registry sem WordPress
bash scripts/build-release-zip.sh
bash scripts/validate-release-zip.sh
```

Traduções: o domínio é o slug (`silvaitamar-form-annotator-for-webmcp`). O `.pot` fica em `languages/`. Language packs vêm do [GlotPress](https://translate.wordpress.org/) — não versionar `.mo` de locales que já têm pack.

Material versionado em `docs/` e o que sobe ao WordPress.org (`readme.txt`, UI, blueprint/assets) ficam em **inglês**. Checklist de QA manual: `docs/QA.md` (pt-BR, local, fora do git).

Estrutura principal:

```text
src/           Código PHP (PSR-4), adapters em src/Adapters/
assets/        CSS do admin e JS do adapter Ninja
languages/     POT (GlotPress)
tests/         Testes de núcleo sem WordPress
docs/          COMPATIBILITY, EXECUTE-TOOL, fixtures (inglês)
silvaitamar-form-annotator-for-webmcp.php   Bootstrap
readme.txt     Metadados WordPress.org (inglês)
```

## Empacotamento

O ZIP de distribuição exclui `vendor/`, `.github/`, `scripts/`, `tests/`, `.wordpress-org/`, `composer.*`, `phpcs.xml.dist` e docs. Ver [`.distignore`](.distignore) e [`scripts/build-release-zip.sh`](scripts/build-release-zip.sh). Push de tag `v*` dispara o workflow de release no GitHub Actions.

Live Preview (wp.org): [`.wordpress-org/blueprints/blueprint.json`](.wordpress-org/blueprints/blueprint.json) — Fluent Forms + login no admin + form já anotado.

## Licença

GPL-2.0-or-later — veja [LICENSE](LICENSE).

## Changelog

Veja [CHANGELOG.md](CHANGELOG.md).
