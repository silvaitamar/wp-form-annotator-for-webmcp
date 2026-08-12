#!/usr/bin/env bash
# Validate a WordPress.org distribution ZIP.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="silvaitamar-webmcp-form-annotator"
ZIP="${1:-${ROOT}/${SLUG}.zip}"
FAIL=0

if [[ ! -f "${ZIP}" ]]; then
  echo "Missing ZIP: ${ZIP}" >&2
  exit 1
fi

LIST="$(unzip -Z1 "${ZIP}")"

first="$(printf '%s\n' "${LIST}" | head -n1)"
if [[ "${first}" != "${SLUG}/"* && "${first}" != "${SLUG}" ]]; then
  echo "FAIL: ZIP root is not ${SLUG}/ (got ${first})"
  FAIL=1
fi

while IFS= read -r f; do
  rel="${f#${SLUG}/}"
  case "${rel}" in
    vendor/*|.github/*|.git/*|node_modules/*|scripts/*|tests/*|docs/*|.wordpress-org/*|composer.json|composer.lock|phpcs.xml.dist|*.md)
      echo "FAIL: forbidden path ${f}"
      FAIL=1
      ;;
  esac
done <<< "${LIST}"

if ! printf '%s\n' "${LIST}" | grep -qx "${SLUG}/readme.txt"; then
  echo "FAIL: missing readme.txt"
  FAIL=1
fi
if ! printf '%s\n' "${LIST}" | grep -qx "${SLUG}/silvaitamar-webmcp-form-annotator.php"; then
  echo "FAIL: missing main plugin file"
  FAIL=1
fi
if ! printf '%s\n' "${LIST}" | grep -qx "${SLUG}/LICENSE"; then
  echo "FAIL: missing LICENSE"
  FAIL=1
fi

STAGE="$(mktemp -d)"
trap 'rm -rf "${STAGE}"' EXIT
unzip -q "${ZIP}" -d "${STAGE}"
MAIN="${STAGE}/${SLUG}/silvaitamar-webmcp-form-annotator.php"
README="${STAGE}/${SLUG}/readme.txt"
ver="$(grep -E '^ \* Version:' "${MAIN}" | awk '{print $3}')"
stable="$(grep -E '^Stable tag:' "${README}" | awk '{print $3}')"
const="$(grep -E "define\\( 'SIWMFA_VERSION'" "${MAIN}" | sed -n "s/.*'\\(.*\\)'.*/\\1/p" | head -n1)"

echo "Version header=${ver} constant=${const} stable_tag=${stable}"
if [[ "${ver}" != "${stable}" || "${ver}" != "${const}" ]]; then
  echo "FAIL: version mismatch"
  FAIL=1
fi

echo "Files: $(printf '%s\n' "${LIST}" | wc -l)"
if [[ "${FAIL}" -ne 0 ]]; then
  echo "ZIP_FAIL"
  exit 1
fi
echo "ZIP_OK ${ZIP}"
