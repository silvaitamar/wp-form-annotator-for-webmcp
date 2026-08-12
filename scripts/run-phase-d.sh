#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT}"

echo "== assets =="
python3 scripts/generate-wporg-assets.py
ls -l .wordpress-org/*.png
python3 - <<'PY'
from pathlib import Path
import struct
for p in sorted(Path('.wordpress-org').glob('*.png')):
    data = p.read_bytes()
    w, h = struct.unpack('>II', data[16:24])
    print(f'{p.name}: {w}x{h} ({p.stat().st_size} bytes)')
PY

echo "== zip =="
bash scripts/build-release-zip.sh
bash scripts/validate-release-zip.sh
echo "== zip listing (first 80) =="
unzip -Z1 silvaitamar-webmcp-form-annotator.zip | head -n 80
