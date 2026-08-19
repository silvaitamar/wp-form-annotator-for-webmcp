#!/usr/bin/env bash
# Build a WordPress.org-ready ZIP (Linux path separators).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="silvaitamar-form-annotator-for-webmcp"
OUT="${ROOT}/${SLUG}.zip"
STAGE="$(mktemp -d)"
trap 'rm -rf "${STAGE}"' EXIT

mkdir -p "${STAGE}/${SLUG}"

# Copy tracked files except those listed in .distignore.
cd "${ROOT}"
if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  git ls-files -co --exclude-standard -z | while IFS= read -r -d '' f; do
    skip=0
    while IFS= read -r pattern || [[ -n "${pattern}" ]]; do
      [[ -z "${pattern}" || "${pattern}" =~ ^# ]] && continue
      if [[ "${pattern}" == !* ]]; then
        continue
      fi
      # Simple basename / path prefix match for common patterns.
      case "${f}" in
        ${pattern}|${pattern}/*|*/${pattern}|*/${pattern}/*) skip=1 ;;
      esac
      case "${pattern}" in
        *.md) [[ "${f}" == *.md && "${f}" != readme.txt ]] && skip=1 ;;
        *.zip) [[ "${f}" == *.zip ]] && skip=1 ;;
      esac
    done < .distignore
    # Always keep readme.txt.
    [[ "${f}" == "readme.txt" ]] && skip=0
    # Exclude paths from .distignore list explicitly.
    for deny in .github .git .wordpress-org vendor scripts tests docs node_modules; do
      [[ "${f}" == "${deny}" || "${f}" == "${deny}/"* ]] && skip=1
    done
    for deny_file in .gitignore .gitattributes .distignore composer.json composer.lock phpcs.xml.dist phpunit.xml.dist; do
      [[ "${f}" == "${deny_file}" ]] && skip=1
    done
    [[ "${f}" == *.md && "${f}" != "readme.txt" ]] && skip=1
    [[ "${skip}" -eq 1 ]] && continue
    mkdir -p "${STAGE}/${SLUG}/$(dirname "${f}")"
    cp "${f}" "${STAGE}/${SLUG}/${f}"
  done
else
  # Fallback without git: copy PHP sources + license + readme.
  cp -a silvaitamar-form-annotator-for-webmcp.php uninstall.php readme.txt LICENSE "${STAGE}/${SLUG}/"
  cp -a src "${STAGE}/${SLUG}/"
  mkdir -p "${STAGE}/${SLUG}/languages"
fi

rm -f "${OUT}"
(
  cd "${STAGE}"
  zip -r -q "${OUT}" "${SLUG}"
)

echo "Wrote ${OUT}"
