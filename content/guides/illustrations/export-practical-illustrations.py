"""Rasterize the actual PDF or export browser captures; never redraw its contents."""
import argparse
import json
from pathlib import Path

from PIL import Image

parser = argparse.ArgumentParser()
parser.add_argument("mode", choices=["pdf", "captures"])
parser.add_argument("directory", type=Path)
args = parser.parse_args()
root = args.directory.resolve()

if args.mode == "pdf":
    import pypdfium2

    with pypdfium2.PdfDocument(root / "application-confirmation.pdf") as pdf:
        if len(pdf) != 1:
            raise ValueError("Review the new PDF layout before updating crop coordinates")
        image = pdf[0].render(scale=3).to_pil()
        for name, box in {
            "pdf-crop": (40, 45, 555, 270),
            "pdf-values": (182, 94, 405, 178),
            "pdf-subject": (45, 188, 285, 270),
        }.items():
            image.crop(tuple(int(x * 3) for x in box)).save(root / f"{name}.png")
else:
    public = Path(__file__).resolve().parents[3] / "public/images/guides"
    captures = json.loads((root / "captures.json").read_text(encoding="utf-8"))
    for item in captures:
        output = public / item["slug"]
        output.mkdir(parents=True, exist_ok=True)
        filename = item["name"] + ("-mobile" if item["mobile"] else "") + ".jpg"
        image = Image.open(root / item["capture"])
        if image.width != item["width"] or image.height < item["height"] - 1:
            raise ValueError(f"Unexpected screenshot dimensions: {item['capture']}")
        item["height"] = min(item["height"], image.height)
        image.crop((0, 0, item["width"], item["height"])).save(
            output / filename, quality=90, optimize=True, subsampling=0
        )
        print(filename, item["width"], item["height"], (output / filename).stat().st_size)
    (root / "exported.json").write_text(json.dumps(captures, ensure_ascii=False, indent=2), encoding="utf-8")
