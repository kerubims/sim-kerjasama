import xml.etree.ElementTree as ET
import base64
import zlib
import urllib.parse
import uuid

def create_mxfile():
    mxfile = ET.Element('mxfile', {
        'host': 'Electron',
        'modified': '2026-04-29T09:00:00.000Z',
        'agent': 'Antigravity',
        'version': '21.0.0',
        'type': 'device'
    })
    return mxfile

def create_diagram(name):
    diagram = ET.Element('diagram', {
        'id': str(uuid.uuid4()),
        'name': name
    })
    mxGraphModel = ET.SubElement(diagram, 'mxGraphModel', {
        'dx': '1000',
        'dy': '1000',
        'grid': '1',
        'gridSize': '10',
        'guides': '1',
        'tooltips': '1',
        'connect': '1',
        'arrows': '1',
        'fold': '1',
        'page': '1',
        'pageScale': '1',
        'pageWidth': '827',
        'pageHeight': '1169',
        'math': '0',
        'shadow': '0'
    })
    root = ET.SubElement(mxGraphModel, 'root')
    ET.SubElement(root, 'mxCell', {'id': '0'})
    ET.SubElement(root, 'mxCell', {'id': '1', 'parent': '0'})
    return diagram, root

def add_swimlane(parent, id, name, x, width, height):
    style = f"swimlane;whiteSpace=wrap;html=1;startSize=40;fillColor=#f8f9fa;strokeColor=#343a40;fontStyle=1"
    return ET.SubElement(parent, 'mxCell', {
        'id': id,
        'value': name,
        'style': style,
        'vertex': '1',
        'parent': '1'
    }, x=str(x), y="0", width=str(width), height=str(height))

def add_node(parent, id, parent_id, value, x, y, style="rounded=1;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#000000;"):
    cell = ET.SubElement(parent, 'mxCell', {
        'id': id,
        'value': value,
        'style': style,
        'vertex': '1',
        'parent': parent_id
    })
    ET.SubElement(cell, 'mxGeometry', {
        'x': str(x),
        'y': str(y),
        'width': '120',
        'height': '60',
        'as': 'geometry'
    })
    return cell

def add_edge(parent, id, source, target, value=""):
    style = "edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;"
    cell = ET.SubElement(parent, 'mxCell', {
        'id': id,
        'value': value,
        'style': style,
        'edge': '1',
        'parent': '1',
        'source': source,
        'target': target
    })
    ET.SubElement(cell, 'mxGeometry', {'relative': '1', 'as': 'geometry'})
    return cell

def generate():
    mxfile = create_mxfile()
    
    scenarios = [
        ("1. Login Workflow", [
            ("User", ["Pilih Role", "Klik Login"]),
            ("System", ["Validasi User", "Set Session", "Redirect ke Dashboard"])
        ]),
        ("2. Logout Workflow", [
            ("User", ["Klik Logout"]),
            ("System", ["Hapus Session", "Redirect ke Login"])
        ]),
        ("3. Dashboard View", [
            ("System", ["Query Dokumen & Mitra", "Hitung Statistik", "Render Charts", "Tampilkan Dashboard"])
        ]),
        ("4. Create Document", [
            ("Admin", ["Isi Form Dokumen", "Pilih Pihak Terlibat", "Simpan"]),
            ("System", ["Validasi Input", "Simpan ke DB (Status: Draft)", "Catat History"])
        ]),
        ("5. Parent-Child Link", [
            ("Admin", ["Pilih Dokumen Rujukan (MoU)", "Buat MoA/IA Baru"]),
            ("System", ["Set parent_doc_id", "Simpan Relasi Hierarki"])
        ]),
        ("6. TinyMCE Editing", [
            ("Admin", ["Edit Konten di Editor", "Klik Simpan"]),
            ("System", ["Update Kolom 'content'", "Simpan Perubahan"])
        ]),
        ("7. Client Review", [
            ("Client", ["Terima Notifikasi", "Buka Dokumen", "Pilih Edit atau Upload"]),
            ("System", ["Update Status: Review Client"])
        ]),
        ("8. Client Editor Edit", [
            ("Client", ["Modifikasi Teks di Editor", "Simpan"]),
            ("System", ["Simpan Versi Client", "Catat History"])
        ]),
        ("9. Stamp Insertion", [
            ("Client", ["Klik 'Sisipkan Stempel'", "Upload Gambar"]),
            ("System", ["Proses Image", "Render di Editor TinyMCE"])
        ]),
        ("10. Client File Upload", [
            ("Client", ["Pilih File (Word/PDF)", "Upload"]),
            ("System", ["Simpan File ke Storage", "Update Status: Waiting Admin Approval"])
        ]),
        ("11. Admin Approval", [
            ("Admin", ["Review File Client", "Klik 'Approve'"]),
            ("System", ["Update Status: Review Unit", "Kirim Notifikasi"])
        ]),
        ("12. Unit Review", [
            ("Unit Pengusul", ["Buka Dokumen", "Review Akhir", "Berikan Tanda Tangan"]),
            ("System", ["Simpan Tanda Tangan Unit"])
        ]),
        ("13. Signature Process", [
            ("Party", ["Upload Tanda Tangan Digital"]),
            ("System", ["Simpan ke storage", "Update 'has_signed' di Pivot Table"])
        ]),
        ("14. Auto-Compression", [
            ("System", ["Terima Upload (10MB)", "Resize & Kompres (Intervention Image)", "Simpan File (<200KB)"])
        ]),
        ("15. Final Status Update", [
            ("System", ["Cek Semua Pihak Signed", "Update Status Dokumen: SIGNED", "Kirim Notifikasi Final"])
        ]),
        ("16. PDF Export", [
            ("User", ["Klik Export PDF"]),
            ("System", ["Gather Konten & Signature", "Render via DomPDF", "Download File"])
        ]),
        ("17. Hierarchy Tracking", [
            ("User", ["Buka Menu Tracking", "Cari Dokumen"]),
            ("System", ["Query Tree MoU->MoA->IA", "Render Visual Tree UI"])
        ]),
        ("18. Reporting", [
            ("User", ["Filter (Tanggal/Jenis/Unit)", "Klik Export Excel"]),
            ("System", ["Query Data Terfilter", "Generate via Maatwebsite Excel"])
        ]),
        ("19. Cron Notification", [
            ("System (Cron)", ["Cek Dokumen Expiring <30 Hari", "Kirim Email Notifikasi"])
        ])
    ]

    for name, lanes in scenarios:
        diagram, root = create_diagram(name)
        mxfile.append(diagram)
        
        lane_width = 250
        total_height = 800
        
        for i, (lane_name, steps) in enumerate(lanes):
            lane_id = f"lane_{i}_{name.replace(' ', '_')}"
            add_swimlane(root, lane_id, lane_name, i * lane_width, lane_width, total_height)
            
            for j, step in enumerate(steps):
                node_id = f"node_{i}_{j}_{name.replace(' ', '_')}"
                add_node(root, node_id, lane_id, step, 65, 80 + (j * 100))
                
                # Simple edge logic: connect to next node in same lane
                if j > 0:
                    prev_id = f"node_{i}_{j-1}_{name.replace(' ', '_')}"
                    add_edge(root, f"edge_{i}_{j}_{name.replace(' ', '_')}", prev_id, node_id)
                
                # Cross-lane connection if previous lane finished
                if i > 0 and j == 0:
                    prev_lane_last_node = f"node_{i-1}_{len(lanes[i-1][1])-1}_{name.replace(' ', '_')}"
                    add_edge(root, f"edge_cross_{i}_{name.replace(' ', '_')}", prev_lane_last_node, node_id)

    tree = ET.ElementTree(mxfile)
    with open("system_architecture.drawio", "wb") as f:
        tree.write(f, encoding="utf-8", xml_declaration=True)
    
    print("Generated system_architecture.drawio successfully.")

if __name__ == "__main__":
    generate()
