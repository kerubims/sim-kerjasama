#!/usr/bin/env python3
import sys
import os
import zipfile
import re
from bs4 import BeautifulSoup
from xml.etree import ElementTree as ET

NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
}
ET.register_namespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main')
ET.register_namespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships')

def patch_docx(original_docx_path, html_content, output_docx_path):
    soup = BeautifulSoup(html_content, 'html.parser')
    
    html_paragraphs = []
    for elem in soup.find_all(['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'td', 'th']):
        txt = elem.get_text().strip()
        if txt:
            html_paragraphs.append(txt)

    temp_output_path = output_docx_path + '.tmp'

    with zipfile.ZipFile(original_docx_path, 'r') as zin:
        with zipfile.ZipFile(temp_output_path, 'w') as zout:
            for item in zin.infolist():
                data = zin.read(item.filename)
                if item.filename == 'word/document.xml':
                    tree = ET.fromstring(data)
                    p_nodes = tree.findall('.//w:p', NS)
                    
                    p_idx = 0
                    for p in p_nodes:
                        runs = p.findall('.//w:r', NS)
                        if not runs:
                            continue
                        
                        full_p_text = "".join(p.itertext()).strip()
                        if not full_p_text:
                            continue
                            
                        if p_idx < len(html_paragraphs):
                            new_text = html_paragraphs[p_idx]
                            
                            first_t = None
                            for r in runs:
                                t = r.find('w:t', NS)
                                if t is not None:
                                    if first_t is None:
                                        first_t = t
                                        first_t.text = new_text
                                    else:
                                        t.text = ""
                            p_idx += 1

                    data = ET.tostring(tree, encoding='utf-8', xml_declaration=True)
                zout.writestr(item, data)

    os.replace(temp_output_path, output_docx_path)
    print("Docx patcher successfully updated original docx into", output_docx_path)

if __name__ == '__main__':
    if len(sys.argv) < 4:
        print("Usage: DocxPatcher.py <orig_docx> <html_file> <output_docx>")
        sys.exit(1)
        
    orig = sys.argv[1]
    html_file = sys.argv[2]
    out = sys.argv[3]
    
    with open(html_file, 'r', encoding='utf-8') as f:
        html_str = f.read()
        
    patch_docx(orig, html_str, out)
