import json
from pathlib import Path

p = Path(__file__).resolve().parents[1] / ".wordpress-org" / "blueprints" / "blueprint.json"
d = json.loads(p.read_text(encoding="utf-8"))
steps = d["steps"]
kinds = [s.get("step") for s in steps]
blob = json.dumps(d)
print("landing", d.get("landingPage"))
print("steps", kinds)
print("login", "login" in kinds)
print("fluentform", "fluentform" in blob)
print("self-install", "silvaitamar-form-annotator-for-webmcp" in blob and '"slug": "silvaitamar-form-annotator-for-webmcp"' in blob)
print("bytes", p.stat().st_size)
assert d["landingPage"] == "/"
assert kinds == ["login", "installPlugin", "runPHP"]
assert "fluentform" in blob
assert '"slug": "silvaitamar-form-annotator-for-webmcp"' not in blob
assert "submit_contact" in blob
assert "toolautosubmit" not in blob.lower() or "never auto-submit" in blob.lower()
print("BLUEPRINT_OK")
