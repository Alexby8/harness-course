from __future__ import annotations

from html import escape
from pathlib import Path

from docx import Document
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import PageBreak, Paragraph, SimpleDocTemplate


ROOT = Path(__file__).resolve().parents[1]
SOURCES = ROOT / "resources" / "legal-documents"
OUTPUT = ROOT / "public" / "documents"
FONT_DIR = Path(r"C:\Windows\Fonts")

pdfmetrics.registerFont(TTFont("LegalSerif", FONT_DIR / "times.ttf"))
pdfmetrics.registerFont(TTFont("LegalSerif-Bold", FONT_DIR / "timesbd.ttf"))
pdfmetrics.registerFont(TTFont("LegalSerif-Italic", FONT_DIR / "timesi.ttf"))
pdfmetrics.registerFontFamily(
    "LegalSerif",
    normal="LegalSerif",
    bold="LegalSerif-Bold",
    italic="LegalSerif-Italic",
    boldItalic="LegalSerif-Bold",
)


BODY = ParagraphStyle(
    "Body",
    fontName="LegalSerif",
    fontSize=10.5,
    leading=13.2,
    alignment=TA_JUSTIFY,
    spaceAfter=5,
    allowWidows=0,
    allowOrphans=0,
)
TITLE = ParagraphStyle(
    "Title",
    parent=BODY,
    fontName="LegalSerif-Bold",
    fontSize=16,
    leading=19,
    alignment=TA_CENTER,
    spaceAfter=4,
)
META = ParagraphStyle(
    "Meta",
    parent=BODY,
    fontName="LegalSerif-Italic",
    fontSize=9.5,
    leading=12,
    alignment=TA_CENTER,
    textColor="#555555",
    spaceAfter=13,
)
H1 = ParagraphStyle(
    "H1",
    parent=BODY,
    fontName="LegalSerif-Bold",
    fontSize=13,
    leading=16,
    alignment=TA_LEFT,
    spaceBefore=10,
    spaceAfter=5,
    keepWithNext=True,
)
H2 = ParagraphStyle(
    "H2",
    parent=BODY,
    fontName="LegalSerif-Bold",
    fontSize=11.5,
    leading=14,
    alignment=TA_LEFT,
    spaceBefore=8,
    spaceAfter=4,
    keepWithNext=True,
)
BULLET = ParagraphStyle(
    "Bullet",
    parent=BODY,
    leftIndent=16,
    firstLineIndent=-9,
    bulletIndent=0,
    spaceAfter=3,
)


def footer(canvas, document, revision: str) -> None:
    canvas.saveState()
    canvas.setFont("LegalSerif", 8.5)
    canvas.setFillColorRGB(0.38, 0.38, 0.38)
    canvas.drawString(2.2 * cm, 1.1 * cm, f"BotGate | {revision.lower()}")
    canvas.drawRightString(A4[0] - 2 * cm, 1.1 * cm, f"Страница {document.page}")
    canvas.restoreState()


def render(source: Path, output: Path) -> None:
    document = Document(source)
    story = []
    non_empty_index = 0
    compact = source.stem in {
        "BotGate_Consent_Personal_Data",
        "BotGate_User_Agreement",
        "BotGate_Analytics_Consent",
    }
    body_style = BODY.clone("CompactBody") if compact else BODY
    title_style = TITLE.clone("CompactTitle") if compact else TITLE
    meta_style = META.clone("CompactMeta") if compact else META
    h1_style = H1.clone("CompactH1") if compact else H1
    h2_style = H2.clone("CompactH2") if compact else H2
    bullet_style = BULLET.clone("CompactBullet") if compact else BULLET

    if source.stem == "BotGate_Privacy_Policy":
        body_style = BODY.clone("PrivacyBody")
        body_style.leading = 13
        body_style.spaceAfter = 4

    if compact:
        body_style.fontSize = 9.8
        body_style.leading = 11.6
        body_style.spaceAfter = 3
        title_style.fontSize = 15
        title_style.leading = 17
        meta_style.spaceAfter = 9
        h1_style.fontSize = 12.2
        h1_style.leading = 14
        h1_style.spaceBefore = 7
        h1_style.spaceAfter = 3
        h2_style.fontSize = 11
        h2_style.leading = 13
        h2_style.spaceBefore = 6
        h2_style.spaceAfter = 3
        bullet_style.spaceAfter = 2

    for paragraph in document.paragraphs:
        text = paragraph.text.strip()
        if not text:
            continue

        style_name = paragraph.style.name
        safe_text = escape(text)

        if non_empty_index == 0:
            story.append(Paragraph(safe_text, title_style))
        elif non_empty_index == 1 and text.startswith("Редакция от"):
            story.append(Paragraph(safe_text, meta_style))
        elif style_name == "Heading 1":
            if source.stem == "BotGate_Analytics_Consent" and text.startswith("3."):
                story.append(PageBreak())
            story.append(Paragraph(safe_text, h1_style))
        elif style_name in {"Heading 2", "Heading 3"}:
            story.append(Paragraph(safe_text, h2_style))
        elif style_name == "List Bullet":
            story.append(Paragraph(safe_text, bullet_style, bulletText="-"))
        elif style_name == "List Number":
            story.append(Paragraph(safe_text, bullet_style, bulletText="-"))
        else:
            story.append(Paragraph(safe_text, body_style))

        non_empty_index += 1

    output.parent.mkdir(parents=True, exist_ok=True)
    pdf = SimpleDocTemplate(
        str(output),
        pagesize=A4,
        rightMargin=(1.8 if compact else 2) * cm,
        leftMargin=(2 if compact else 2.2) * cm,
        topMargin=(1.6 if compact else 1.8) * cm,
        bottomMargin=(1.6 if compact else 1.8) * cm,
        title=document.paragraphs[0].text,
        author="BotGate",
        subject="Публичный юридический документ сервиса BotGate",
    )
    revision = next(p.text.strip() for p in document.paragraphs if p.text.startswith("Редакция от"))
    def page_footer(canvas, document):
        footer(canvas, document, revision)
    pdf.build(story, onFirstPage=page_footer, onLaterPages=page_footer)


if __name__ == "__main__":
    for source in sorted(SOURCES.glob("BotGate_*.docx")):
        target = OUTPUT / f"{source.stem}.pdf"
        render(source, target)
        print(f"Rendered {target}")
