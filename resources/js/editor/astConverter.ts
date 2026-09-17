import { Node as PMNode, Schema } from 'prosemirror-model';

export interface AstRun {
  type: string;
  text: string;
  bold?: boolean;
  italic?: boolean;
  underline?: boolean;
  fontFamily?: string | null;
  fontSize?: number | null;
  color?: string | null;
}

export interface AstParagraph {
  type: 'paragraph';
  alignment?: string;
  style?: string;
  runs: AstRun[];
}

export interface AstTableCell {
  shading?: string | null;
  paragraphs: AstParagraph[];
}

export interface AstTableRow {
  cells: AstTableCell[];
}

export interface AstTable {
  type: 'table';
  rows: AstTableRow[];
}

export type AstItem = AstParagraph | AstTable;

export interface AstData {
  version: string;
  body: AstItem[];
}

export function astToProseMirrorDoc(ast: AstData, schema: Schema): PMNode {
  const pmBlocks: PMNode[] = [];

  for (const item of ast.body || []) {
    if (item.type === 'paragraph') {
      const inlineNodes = (item.runs || []).map((r) => {
        const marks = [];
        if (r.bold) marks.push(schema.marks.bold.create());
        if (r.italic) marks.push(schema.marks.italic.create());
        if (r.underline) marks.push(schema.marks.underline.create());
        if (r.fontFamily) marks.push(schema.marks.fontFamily.create({ family: r.fontFamily }));
        if (r.fontSize) marks.push(schema.marks.fontSize.create({ size: r.fontSize }));
        if (r.color) marks.push(schema.marks.color.create({ hex: r.color }));

        return schema.text(r.text || ' ', marks);
      });

      const pNode = schema.nodes.paragraph.create(
        { alignment: item.alignment || 'left', style: item.style || 'Normal' },
        inlineNodes.length > 0 ? inlineNodes : undefined
      );
      pmBlocks.push(pNode);
    } else if (item.type === 'table') {
      const pmRows: PMNode[] = [];
      for (const row of item.rows || []) {
        const pmCells: PMNode[] = [];
        for (const cell of row.cells || []) {
          const cellBlocks: PMNode[] = (cell.paragraphs || []).map((p) => {
            const inlineNodes = (p.runs || []).map((r) => {
              const marks = [];
              if (r.bold) marks.push(schema.marks.bold.create());
              if (r.italic) marks.push(schema.marks.italic.create());
              if (r.fontFamily) marks.push(schema.marks.fontFamily.create({ family: r.fontFamily }));
              return schema.text(r.text || ' ', marks);
            });
            return schema.nodes.paragraph.create(
              { alignment: p.alignment || 'left' },
              inlineNodes.length > 0 ? inlineNodes : undefined
            );
          });

          const cNode = schema.nodes.table_cell.create(
            { shading: cell.shading || null },
            cellBlocks.length > 0 ? cellBlocks : [schema.nodes.paragraph.create()]
          );
          pmCells.push(cNode);
        }
        if (pmCells.length > 0) {
          pmRows.push(schema.nodes.table_row.create(null, pmCells));
        }
      }
      if (pmRows.length > 0) {
        pmBlocks.push(schema.nodes.table.create(null, pmRows));
      }
    }
  }

  return schema.nodes.doc.create(null, pmBlocks.length > 0 ? pmBlocks : [schema.nodes.paragraph.create()]);
}

export function proseMirrorDocToAst(docNode: PMNode): AstData {
  const body: AstItem[] = [];

  docNode.forEach((node) => {
    if (node.type.name === 'paragraph') {
      const runs: AstRun[] = [];
      node.forEach((child) => {
        if (child.isText) {
          const r: AstRun = {
            type: 'run',
            text: child.text || '',
            bold: false,
            italic: false,
            underline: false,
          };
          for (const m of child.marks) {
            if (m.type.name === 'bold') r.bold = true;
            if (m.type.name === 'italic') r.italic = true;
            if (m.type.name === 'underline') r.underline = true;
            if (m.type.name === 'fontFamily') r.fontFamily = m.attrs.family;
            if (m.type.name === 'fontSize') r.fontSize = m.attrs.size;
            if (m.type.name === 'color') r.color = m.attrs.hex;
          }
          runs.push(r);
        }
      });
      body.push({
        type: 'paragraph',
        alignment: node.attrs.alignment || 'left',
        style: node.attrs.style || 'Normal',
        runs,
      });
    } else if (node.type.name === 'table') {
      const rows: AstTableRow[] = [];
      node.forEach((rowNode) => {
        const cells: AstTableCell[] = [];
        rowNode.forEach((cellNode) => {
          const paragraphs: AstParagraph[] = [];
          cellNode.forEach((pNode) => {
            const runs: AstRun[] = [];
            pNode.forEach((c) => {
              if (c.isText) {
                runs.push({
                  type: 'run',
                  text: c.text || '',
                  bold: c.marks.some((m) => m.type.name === 'bold'),
                  italic: c.marks.some((m) => m.type.name === 'italic'),
                });
              }
            });
            paragraphs.push({
              type: 'paragraph',
              alignment: pNode.attrs.alignment || 'left',
              runs,
            });
          });
          cells.push({
            shading: cellNode.attrs.shading || null,
            paragraphs,
          });
        });
        rows.push({ cells });
      });
      body.push({ type: 'table', rows });
    }
  });

  return { version: '1.0', body };
}
