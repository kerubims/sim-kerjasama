#!/usr/bin/env python3
import sys
import os
import json
import zipfile
import base64
import re
from xml.etree import ElementTree as ET
import docx
from docx import Document
from docx.shared import Pt, RGBColor, Inches, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT

NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
    'wp': 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
    'pic': 'http://schemas.openxmlformats.org/drawingml/2006/picture'
}

def parse_docx(docx_path):
    if not os.path.exists(docx_path):
        return {"version": "2.0", "body": []}

    with zipfile.ZipFile(docx_path, 'r') as z:
        try:
            doc_xml = z.read('word/document.xml')
        except KeyError:
            return {"version": "2.0", "body": []}

        image_map = {}
        try:
            rels_xml = z.read('word/_rels/document.xml.rels')
            rels_tree = ET.fromstring(rels_xml)
            for child in rels_tree:
                r_id = child.attrib.get('Id')
                target = child.attrib.get('Target')
                if r_id and target and 'image' in target:
                    image_path = 'word/' + target if not target.startswith('word/') else target
                    try:
                        img_data = z.read(image_path)
                        ext = image_path.split('.')[-1].lower()
                        mime = 'image/png' if ext == 'png' else 'image/jpeg'
                        b64 = base64.b64encode(img_data).decode('utf-8')
                        image_map[r_id] = f"data:{mime};base64,{b64}"
                    except Exception:
                        pass
        except Exception:
            pass

    tree = ET.fromstring(doc_xml)
    body_elem = tree.find('w:body', NS)
    if body_elem is None:
        return {"version": "2.0", "body": []}

    body = []

    for elem in body_elem:
        tag = elem.tag.split('}')[-1]

        if tag == 'p':
            # Check for page break inside paragraph
            is_page_break = False
            for br in elem.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}br'):
                if br.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}type') == 'page':
                    is_page_break = True

            if is_page_break:
                body.append({"type": "page_break"})

            # Alignment
            pPr = elem.find('w:pPr', NS)
            jc_val = 'left'
            space_before = 0
            space_after = 0
            line_height = 1.15

            if pPr is not None:
                jc = pPr.find('w:jc', NS)
                if jc is not None:
                    v = jc.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}val')
                    if v in ['center', 'right', 'both']:
                        jc_val = 'justify' if v == 'both' else v
                spacing = pPr.find('w:spacing', NS)
                if spacing is not None:
                    before_str = spacing.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}before')
                    after_str = spacing.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}after')
                    if before_str: space_before = int(before_str) // 20
                    if after_str: space_after = int(after_str) // 20

            runs = []
            for child in elem:
                ctag = child.tag.split('}')[-1]
                if ctag == 'r':
                    rPr = child.find('w:rPr', NS)
                    bold = False
                    italic = False
                    underline = False
                    font_family = "Times New Roman"
                    font_size = 12.0
                    color = None

                    if rPr is not None:
                        if rPr.find('w:b', NS) is not None: bold = True
                        if rPr.find('w:i', NS) is not None: italic = True
                        if rPr.find('w:u', NS) is not None: underline = True
                        rFonts = rPr.find('w:rFonts', NS)
                        if rFonts is not None:
                            font_family = rFonts.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}ascii') or font_family
                        sz = rPr.find('w:sz', NS)
                        if sz is not None:
                            font_size = float(sz.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}val', 24)) / 2.0
                        col = rPr.find('w:color', NS)
                        if col is not None:
                            cval = col.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}val')
                            if cval and cval != 'auto': color = f"#{cval}"

                    # Text or Drawing
                    for item in child:
                        itag = item.tag.split('}')[-1]
                        if itag == 't':
                            runs.append({
                                "type": "run",
                                "text": item.text or "",
                                "bold": bold,
                                "italic": italic,
                                "underline": underline,
                                "fontFamily": font_family,
                                "fontSize": font_size,
                                "color": color
                            })
                        elif itag == 'drawing':
                            blip = item.find('.//a:blip', NS)
                            if blip is not None:
                                embed_id = blip.attrib.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed')
                                if embed_id in image_map:
                                    body.append({
                                        "type": "image",
                                        "src": image_map[embed_id],
                                        "alignment": jc_val
                                    })

            if len(runs) > 0 or not is_page_break:
                body.append({
                    "type": "paragraph",
                    "alignment": jc_val,
                    "spaceBefore": space_before,
                    "spaceAfter": space_after,
                    "runs": runs
                })

        elif tag == 'tbl':
            tblPr = elem.find('w:tblPr', NS)
            has_borders = False
            if tblPr is not None:
                tblBorders = tblPr.find('w:tblBorders', NS)
                if tblBorders is not None:
                    for b in tblBorders:
                        val = b.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}val')
                        if val and val != 'none':
                            has_borders = True
                            break

            rows = []
            for tr in elem.findall('w:tr', NS):
                cells = []
                for tc in tr.findall('w:tc', NS):
                    tcPr = tc.find('w:tcPr', NS)
                    shd_color = None
                    if tcPr is not None:
                        shd = tcPr.find('w:shd', NS)
                        if shd is not None:
                            val = shd.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}fill')
                            if val and val != 'auto':
                                shd_color = f"#{val}"

                    cell_paras = []
                    for p in tc.findall('w:p', NS):
                        pPr = p.find('w:pPr', NS)
                        jc_val = 'left'
                        if pPr is not None:
                            jc = pPr.find('w:jc', NS)
                            if jc is not None:
                                v = jc.attrib.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}val')
                                if v in ['center', 'right', 'both']:
                                    jc_val = 'justify' if v == 'both' else v
                        c_runs = []
                        for r in p.findall('w:r', NS):
                            rPr = r.find('w:rPr', NS)
                            b_flag = False
                            i_flag = False
                            if rPr is not None:
                                if rPr.find('w:b', NS) is not None: b_flag = True
                                if rPr.find('w:i', NS) is not None: i_flag = True
                            for t in r.findall('w:t', NS):
                                c_runs.append({
                                    "type": "run",
                                    "text": t.text or "",
                                    "bold": b_flag,
                                    "italic": i_flag
                                })
                        cell_paras.append({
                            "type": "paragraph",
                            "alignment": jc_val,
                            "runs": c_runs
                        })

                    cells.append({
                        "shading": shd_color,
                        "paragraphs": cell_paras if len(cell_paras) > 0 else [{"type": "paragraph", "alignment": "left", "runs": []}]
                    })
                if len(cells) > 0:
                    rows.append({"cells": cells})

            if len(rows) > 0:
                body.append({
                    "type": "table",
                    "hasBorders": has_borders,
                    "rows": rows
                })

    return {"version": "2.0", "body": body}

def build_docx(ast_data, output_docx_path):
    doc = Document()

    # Set page margin A4
    sections = doc.sections
    for section in sections:
        section.top_margin = Cm(2.5)
        section.bottom_margin = Cm(2.5)
        section.left_margin = Cm(2.0)
        section.right_margin = Cm(2.0)
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)

    body = ast_data.get('body', [])

    for item in body:
        itype = item.get('type')
        if itype == 'page_break':
            doc.add_page_break()
        elif itype == 'image':
            src = item.get('src', '')
            if src.startswith('data:image'):
                header, encoded = src.split(",", 1)
                img_data = base64.b64decode(encoded)
                temp_img = output_docx_path + '_img.png'
                with open(temp_img, 'wb') as f:
                    f.write(img_data)
                p = doc.add_paragraph()
                align = item.get('alignment', 'center')
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER if align == 'center' else WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run()
                run.add_picture(temp_img, width=Inches(2.5))
                if os.path.exists(temp_img):
                    os.remove(temp_img)
        elif itype == 'paragraph':
            p = doc.add_paragraph()
            align = item.get('alignment', 'left')
            if align == 'center': p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            elif align == 'right': p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
            elif align == 'justify': p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            else: p.alignment = WD_ALIGN_PARAGRAPH.LEFT

            for r_data in item.get('runs', []):
                text = r_data.get('text', '')
                run = p.add_run(text)
                if r_data.get('bold'): run.bold = True
                if r_data.get('italic'): run.italic = True
                if r_data.get('underline'): run.underline = True
                font_fam = r_data.get('fontFamily')
                if font_fam: run.font.name = font_fam
                font_sz = r_data.get('fontSize')
                if font_sz: run.font.size = Pt(font_sz)
                color = r_data.get('color')
                if color and color.startswith('#') and len(color) == 7:
                    try:
                        r_hex = int(color[1:3], 16)
                        g_hex = int(color[3:5], 16)
                        b_hex = int(color[5:7], 16)
                        run.font.color.rgb = RGBColor(r_hex, g_hex, b_hex)
                    except Exception:
                        pass
        elif itype == 'table':
            rows_data = item.get('rows', [])
            if not rows_data: continue
            num_rows = len(rows_data)
            num_cols = max(len(r.get('cells', [])) for r in rows_data)
            if num_cols == 0: continue

            tbl = doc.add_table(rows=num_rows, cols=num_cols)
            tbl.alignment = WD_TABLE_ALIGNMENT.CENTER

            for r_idx, r_data in enumerate(rows_data):
                for c_idx, c_data in enumerate(r_data.get('cells', [])):
                    if c_idx >= num_cols: continue
                    cell = tbl.cell(r_idx, c_idx)
                    cell.text = ''
                    for p_data in c_data.get('paragraphs', []):
                        cp = cell.add_paragraph()
                        align = p_data.get('alignment', 'left')
                        if align == 'center': cp.alignment = WD_ALIGN_PARAGRAPH.CENTER
                        elif align == 'right': cp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
                        elif align == 'justify': cp.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

                        for r_item in p_data.get('runs', []):
                            c_run = cp.add_run(r_item.get('text', ''))
                            if r_item.get('bold'): c_run.bold = True
                            if r_item.get('italic'): c_run.italic = True

    doc.save(output_docx_path)

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: DocxAST.py parse <docx_file> | build <ast_json> <output_docx>")
        sys.exit(1)

    cmd = sys.argv[1]
    if cmd == 'parse':
        res = parse_docx(sys.argv[2])
        print(json.dumps(res, indent=2))
    elif cmd == 'build':
        with open(sys.argv[2], 'r') as f:
            data = json.load(f)
        build_docx(data, sys.argv[3])
