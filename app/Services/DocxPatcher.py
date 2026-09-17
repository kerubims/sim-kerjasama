#!/usr/bin/env python3
import sys
import os
import zipfile
import re
import warnings
from difflib import SequenceMatcher
from bs4 import BeautifulSoup, MarkupResemblesLocatorWarning
from xml.etree import ElementTree as ET

warnings.filterwarnings("ignore", category=MarkupResemblesLocatorWarning)

NS = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'xml': 'http://www.w3.org/XML/1998/namespace'
}
ET.register_namespace('w', NS['w'])
ET.register_namespace('r', NS['r'])

def patch_docx(original_docx_path, html_content, output_docx_path):
    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Extract HTML paragraphs
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
                    
                    # Collect XML paragraphs with original index
                    xml_paragraphs = []
                    for idx, p in enumerate(tree.findall('.//w:p', NS)):
                        txt = "".join(p.itertext()).strip()
                        xml_paragraphs.append({
                            'elem': p,
                            'index': idx,
                            'text': txt,
                            'matched': False
                        })
                    
                    # Match HTML paragraphs to XML paragraphs using SequenceMatcher fuzzy alignment
                    for h_text in html_paragraphs:
                        best_sim = 0.0
                        best_xml = None
                        
                        for xml_p in xml_paragraphs:
                            if xml_p['matched'] or not xml_p['text']:
                                continue
                            sim = SequenceMatcher(None, h_text, xml_p['text']).ratio()
                            if sim > best_sim:
                                best_sim = sim
                                best_xml = xml_p
                                
                        # Match anchor if similarity >= 20%
                        if best_sim >= 0.20 and best_xml is not None:
                            best_xml['matched'] = True
                            p_elem = best_xml['elem']
                            runs = p_elem.findall('.//w:r', NS)
                            
                            if runs:
                                first_t = None
                                for r in runs:
                                    t = r.find('w:t', NS)
                                    if t is not None:
                                        if first_t is None:
                                            first_t = t
                                            first_t.text = h_text
                                            first_t.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
                                        else:
                                            t.text = ""

                    data = ET.tostring(tree, encoding='utf-8', xml_declaration=True)
                zout.writestr(item, data)

    os.replace(temp_output_path, output_docx_path)
    print("Docx patcher fuzzy matching successfully updated original docx into", output_docx_path)

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
