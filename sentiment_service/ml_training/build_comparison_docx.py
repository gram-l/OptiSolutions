"""Build the paper report with the bundled document runtime."""
import argparse
import json
from pathlib import Path

from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


def paragraph(doc, text, *, size=None, bold=False, align=None, keep=False):
    item = doc.add_paragraph()
    if align is not None:
        item.alignment = align
    item.paragraph_format.keep_with_next = keep
    run = item.add_run(text)
    run.bold = bold
    if size:
        run.font.size = Pt(size)
    return item


def table(doc, section):
    headers = section['headers']
    result = doc.add_table(rows=1, cols=len(headers))
    result.alignment = WD_TABLE_ALIGNMENT.CENTER
    result.autofit = False
    widths = ([1.8, 1.15, 1.15, 1.15, 1.25] if section['number'] <= 3 else
        [1.75, .95, .95, .95, .95, .95] if section['number'] == 4 else
        [1.85, 2., 1.3, 1.35] if section['number'] == 5 else [2.1, 1.1, 1.1, 1.1, 1.1])
    for col, width in zip(result.columns, widths):
        col.width = Inches(width)
    properties = result._tbl.tblPr
    borders = OxmlElement('w:tblBorders')
    for edge in ['top', 'left', 'bottom', 'right', 'insideH', 'insideV']:
        line = OxmlElement('w:' + edge)
        line.set(qn('w:val'), 'single')
        line.set(qn('w:sz'), '4')
        line.set(qn('w:color'), 'D9D9D9')
        borders.append(line)
    properties.append(borders)
    for values in [headers] + section['rows']:
        row = result.rows[0] if values is headers else result.add_row()
        cant_split = OxmlElement('w:cantSplit')
        row._tr.get_or_add_trPr().append(cant_split)
        for index, (cell, text) in enumerate(zip(row.cells, values)):
            cell.width = Inches(widths[index])
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            tcpr = cell._tc.get_or_add_tcPr()
            margins = OxmlElement('w:tcMar')
            for side, amount in [('top', 55), ('bottom', 55), ('left', 85), ('right', 85)]:
                element = OxmlElement('w:' + side)
                element.set(qn('w:w'), str(amount))
                element.set(qn('w:type'), 'dxa')
                margins.append(element)
            tcpr.append(margins)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER if index or values is headers else WD_ALIGN_PARAGRAPH.LEFT
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.line_spacing = 1.0
            p.paragraph_format.keep_with_next = True
            run = p.add_run(str(text))
            run.font.size = Pt(10.5)
            if values is headers:
                run.bold = True
                shading = OxmlElement('w:shd')
                shading.set(qn('w:fill'), 'F2F2F2')
                tcpr.append(shading)
        if values is headers:
            repeated = OxmlElement('w:tblHeader')
            row._tr.get_or_add_trPr().append(repeated)
    return result


def build(directory):
    content = json.loads((directory / 'report_content.json').read_text(encoding='utf-8'))
    doc = Document()
    section = doc.sections[0]
    section.page_width = Inches(8.5)
    section.page_height = Inches(11)
    section.left_margin = section.right_margin = Inches(1)
    section.top_margin = section.bottom_margin = Inches(.85)
    section.header_distance = section.footer_distance = Inches(.35)
    for name in ['Normal', 'Title', 'Heading 1', 'Heading 2', 'Caption']:
        style = doc.styles[name]
        style.font.name = 'Times New Roman'
        style.font.color.rgb = RGBColor(0, 0, 0)
        fonts = style.element.find('.//' + qn('w:rFonts'))
        if fonts is not None:
            for attribute in ['asciiTheme', 'hAnsiTheme', 'eastAsiaTheme', 'cstheme']:
                fonts.attrib.pop(qn('w:' + attribute), None)
    normal = doc.styles['Normal']
    normal.font.size = Pt(11)
    normal.paragraph_format.line_spacing = 1.05
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.widow_control = True
    title = doc.add_paragraph(content['title'], 'Title')
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    title.paragraph_format.space_after = Pt(10)
    for run in title.runs:
        run.font.size = Pt(16)
        run.font.name = 'Times New Roman'
        run.bold = True
    paragraph(doc, content['intro'])
    paragraph(doc, 'Evaluation Method', bold=True, size=12, keep=True)
    paragraph(doc, content['methodology'])
    for part in content['sections']:
        caption = paragraph(doc, f"Table 4-{part['number']} {part['title']}",
            bold=True, align=WD_ALIGN_PARAGRAPH.CENTER, keep=True)
        caption.paragraph_format.space_before = Pt(5)
        caption.paragraph_format.space_after = Pt(5)
        table(doc, part)
        if 'accuracy' in part:
            paragraph(doc, 'Overall accuracy: ' + part['accuracy'] + '.', bold=True, align=WD_ALIGN_PARAGRAPH.CENTER, keep=True)
        else:
            spacer = doc.add_paragraph()
            spacer.paragraph_format.space_after = Pt(0)
            spacer.paragraph_format.space_before = Pt(0)
            spacer.paragraph_format.line_spacing = Pt(6)
            spacer.paragraph_format.keep_with_next = True
            spacer.add_run().font.size = Pt(3)
        interpretation = paragraph(doc, part['interpretation'])
        interpretation.paragraph_format.keep_together = True
        if part['number'] == 1:
            paragraph(doc, 'Sources and Reproducibility', bold=True, size=12, keep=True)
            for source in content['sources']:
                source_paragraph = paragraph(doc, source, size=9)
                source_paragraph.paragraph_format.space_after = Pt(3)
        if part['number'] == 5:
            paragraph(doc, 'Selected Model Settings', bold=True, keep=True)
            for setting in content['settings']:
                paragraph(doc, setting)
    paragraph(doc, 'Scope and Limitations', bold=True, size=12, keep=True)
    paragraph(doc, content['limitations'])
    footer = section.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    footer.add_run('Page ').font.size = Pt(9)
    field = OxmlElement('w:fldSimple')
    field.set(qn('w:instr'), 'PAGE')
    footer._p.append(field)
    doc.core_properties.title = content['title']
    doc.core_properties.subject = 'Measured sentiment model comparison tables and interpretations'
    doc.core_properties.author = ''
    # Remove the bundled template's inherited title rule and theme font hooks.
    for root in [doc.element, doc.styles.element]:
        for border in root.xpath('.//w:pBdr'):
            border.getparent().remove(border)
    path = directory / 'Sentiment_Model_Comparison.docx'
    doc.save(path)
    print(path)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('run_directory', type=Path)
    build(parser.parse_args().run_directory)
