#!/usr/bin/env python3
import sys
import os
import re
import base64
import zipfile
import xml.etree.ElementTree as ET

NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
    'pic': 'http://schemas.openxmlformats.org/drawingml/2006/picture',
}

def dxa_to_px(val):
    try:
        return f"{round(float(val) / 20 * 1.33)}px"
    except:
        return "0px"

def half_pt_to_px(val):
    try:
        return f"{round(float(val) / 2 * 1.33)}px"
    except:
        return "12px"

def parse_docx_to_html(docx_path):
    if not os.path.exists(docx_path):
        return f"<p>Error: File {docx_path} tidak ditemukan.</p>"

    if not zipfile.is_zipfile(docx_path):
        return "<p style='color: red; font-weight: bold;'>Format file .doc (Word 97-2003) tidak didukung secara langsung. Silakan buka file tersebut di Microsoft Word lalu simpan ulang sebagai <u>.docx</u> (Word Document) sebelum meng-import ke sistem.</p>"

    try:
        with zipfile.ZipFile(docx_path, 'r') as z:
            images = {}
            for name in z.namelist():
                if name.startswith('word/media/'):
                    img_data = z.read(name)
                    ext = name.split('.')[-1].lower()
                    mime = 'image/png' if ext == 'png' else ('image/jpeg' if ext in ['jpg', 'jpeg'] else f'image/{ext}')
                    b64 = base64.b64encode(img_data).decode('utf-8')
                    images[name] = f"data:{mime};base64,{b64}"

            rels = {}
            if 'word/_rels/document.xml.rels' in z.namelist():
                rels_tree = ET.fromstring(z.read('word/_rels/document.xml.rels'))
                for rel in rels_tree.findall('{http://schemas.openxmlformats.org/package/2006/relationships}Relationship'):
                    r_id = rel.get('Id')
                    target = rel.get('Target')
                    if target and not target.startswith('word/'):
                        target = 'word/' + target
                    rels[r_id] = target

            doc_xml = z.read('word/document.xml')
            root = ET.fromstring(doc_xml)
    except Exception as e:
        return f"<p>Error reading DOCX file: {str(e)}</p>"

    body = root.find('w:body', NS)
    if body is None:
        return "<p>Dokumen kosong.</p>"

    html_parts = []
    html_parts.append('<div class="docx-document-canvas" style="font-family: \'Times New Roman\', Times, serif; font-size: 12pt; color: #1e293b; line-height: 1.5; padding: 24px; background: #ffffff; width: 100%; box-sizing: border-box;">')

    def parse_run(r_elem):
        rPr = r_elem.find('w:rPr', NS)
        text_elem = r_elem.find('w:t', NS)
        text = text_elem.text if text_elem is not None and text_elem.text else ''

        if not text:
            drawing = r_elem.find('.//w:drawing', NS)
            if drawing is not None:
                blip = drawing.find('.//a:blip', NS)
                if blip is not None:
                    embed_id = blip.get(f"{{{NS['r']}}}embed")
                    if embed_id in rels and rels[embed_id] in images:
                        src = images[rels[embed_id]]
                        return f'<img src="{src}" style="max-width: 100%; height: auto; display: block; margin: 10px auto;" />'
            return ''

        styles = []
        if rPr is not None:
            if rPr.find('w:b', NS) is not None:
                styles.append('font-weight: bold;')
            if rPr.find('w:i', NS) is not None:
                styles.append('font-style: italic;')
            if rPr.find('w:u', NS) is not None:
                styles.append('text-decoration: underline;')
            
            color_el = rPr.find('w:color', NS)
            if color_el is not None:
                c_val = color_el.get(f"{{{NS['w']}}}val")
                if c_val and c_val != 'auto':
                    styles.append(f'color: #{c_val};')

            sz_el = rPr.find('w:sz', NS)
            if sz_el is not None:
                s_val = sz_el.get(f"{{{NS['w']}}}val")
                if s_val:
                    styles.append(f'font-size: {half_pt_to_px(s_val)};')

            rFonts_el = rPr.find('w:rFonts', NS)
            if rFonts_el is not None:
                font_name = rFonts_el.get(f"{{{NS['w']}}}ascii") or rFonts_el.get(f"{{{NS['w']}}}hAnsi")
                if font_name:
                    clean_font = font_name.replace('"', '').replace("'", "")
                    styles.append(f"font-family: '{clean_font}', sans-serif;")

        style_attr = f' style="{" ".join(styles)}"' if styles else ''
        escaped_text = text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').replace('\n', '<br/>')
        return f'<span{style_attr}>{escaped_text}</span>'

    def parse_paragraph(p_elem):
        pPr = p_elem.find('w:pPr', NS)
        p_styles = ['margin-top: 4px;', 'margin-bottom: 8px;']

        if pPr is not None:
            jc_el = pPr.find('w:jc', NS)
            if jc_el is not None:
                val = jc_el.get(f"{{{NS['w']}}}val")
                if val in ['center', 'right', 'justify']:
                    p_styles.append(f'text-align: {val};')
                elif val == 'both':
                    p_styles.append('text-align: justify;')

            sp_el = pPr.find('w:spacing', NS)
            if sp_el is not None:
                before = sp_el.get(f"{{{NS['w']}}}before")
                after = sp_el.get(f"{{{NS['w']}}}after")
                line = sp_el.get(f"{{{NS['w']}}}line")
                if before:
                    p_styles.append(f'margin-top: {dxa_to_px(before)};')
                if after:
                    p_styles.append(f'margin-bottom: {dxa_to_px(after)};')
                if line:
                    try:
                        line_val = round(float(line) / 240, 2)
                        p_styles.append(f'line-height: {line_val};')
                    except:
                        pass

        runs_html = []
        for child in p_elem:
            if child.tag == f"{{{NS['w']}}}r":
                runs_html.append(parse_run(child))

        p_style_attr = f' style="{" ".join(p_styles)}"' if p_styles else ''
        content = ''.join(runs_html)
        if not content.strip():
            content = '&nbsp;'
        return f'<p{p_style_attr}>{content}</p>'

    def parse_table(tbl_elem):
        tbl_styles = [
            'border-collapse: collapse;',
            'width: 100%;',
            'margin: 12px 0;',
            'border: 1px solid #cbd5e1;'
        ]

        rows_html = []
        for tr_elem in tbl_elem.findall('w:tr', NS):
            cells_html = []
            for tc_elem in tr_elem.findall('w:tc', NS):
                tcPr = tc_elem.find('w:tcPr', NS)
                td_styles = ['border: 1px solid #94a3b8;', 'padding: 8px 12px;', 'vertical-align: top;']

                if tcPr is not None:
                    shd_el = tcPr.find('w:shd', NS)
                    if shd_el is not None:
                        fill = shd_el.get(f"{{{NS['w']}}}fill")
                        if fill and fill != 'auto':
                            td_styles.append(f'background-color: #{fill};')

                tc_paragraphs = []
                for p in tc_elem.findall('w:p', NS):
                    tc_paragraphs.append(parse_paragraph(p))

                cell_content = ''.join(tc_paragraphs) if tc_paragraphs else '&nbsp;'
                td_style_attr = f' style="{" ".join(td_styles)}"' if td_styles else ''
                cells_html.append(f'<td{td_style_attr}>{cell_content}</td>')

            rows_html.append(f'<tr>{"".join(cells_html)}</tr>')

        tbl_style_attr = f' style="{" ".join(tbl_styles)}"' if tbl_styles else ''
        return f'<table{tbl_style_attr}><tbody>{"".join(rows_html)}</tbody></table>'

    for elem in body:
        tag = elem.tag
        if tag == f"{{{NS['w']}}}p":
            html_parts.append(parse_paragraph(elem))
        elif tag == f"{{{NS['w']}}}tbl":
            html_parts.append(parse_table(elem))

    html_parts.append('</div>')
    return ''.join(html_parts)

if __name__ == '__main__':
    if len(sys.argv) > 1:
        file_path = sys.argv[1]
        out_path = sys.argv[2] if len(sys.argv) > 2 else None
        res = parse_docx_to_html(file_path)
        if out_path:
            with open(out_path, 'w', encoding='utf-8') as f:
                f.write(res)
        else:
            print(res)
