import { Schema, NodeSpec, MarkSpec } from 'prosemirror-model';

const nodes: Record<string, NodeSpec> = {
  doc: {
    content: 'block+',
  },
  paragraph: {
    content: 'inline*',
    group: 'block',
    attrs: {
      alignment: { default: 'left' },
      style: { default: 'Normal' },
    },
    parseDOM: [
      {
        tag: 'p',
        getAttrs: (dom) => {
          const el = dom as HTMLElement;
          return {
            alignment: el.style.textAlign || 'left',
          };
        },
      },
    ],
    toDOM: (node) => [
      'p',
      {
        style: `text-align: ${node.attrs.alignment}; margin-top: 4px; margin-bottom: 8px;`,
      },
      0,
    ],
  },
  text: {
    group: 'inline',
  },
  table: {
    content: 'table_row+',
    group: 'block',
    parseDOM: [{ tag: 'table' }],
    toDOM: () => ['table', { style: 'width: 100%; border-collapse: collapse; margin: 12px 0;' }, ['tbody', 0]],
  },
  table_row: {
    content: 'table_cell+',
    parseDOM: [{ tag: 'tr' }],
    toDOM: () => ['tr', 0],
  },
  table_cell: {
    content: 'block+',
    attrs: {
      shading: { default: null },
    },
    parseDOM: [{ tag: 'td' }],
    toDOM: (node) => [
      'td',
      {
        style: `border: 1px solid #cbd5e1; padding: 6px 10px; background-color: ${node.attrs.shading || 'transparent'};`,
      },
      0,
    ],
  },
};

const marks: Record<string, MarkSpec> = {
  bold: {
    parseDOM: [{ tag: 'strong' }, { tag: 'b' }, { style: 'font-weight=bold' }],
    toDOM: () => ['strong', 0],
  },
  italic: {
    parseDOM: [{ tag: 'em' }, { tag: 'i' }, { style: 'font-style=italic' }],
    toDOM: () => ['em', 0],
  },
  underline: {
    parseDOM: [{ tag: 'u' }, { style: 'text-decoration=underline' }],
    toDOM: () => ['u', 0],
  },
  fontFamily: {
    attrs: { family: { default: 'Times New Roman' } },
    parseDOM: [
      {
        style: 'font-family',
        getAttrs: (value) => ({ family: typeof value === 'string' ? value.replace(/["']/g, '') : 'Times New Roman' }),
      },
    ],
    toDOM: (mark) => ['span', { style: `font-family: '${mark.attrs.family}', serif;` }, 0],
  },
  fontSize: {
    attrs: { size: { default: 12 } },
    parseDOM: [
      {
        style: 'font-size',
        getAttrs: (value) => {
          if (typeof value === 'string') {
            const num = parseFloat(value);
            return { size: isNaN(num) ? 12 : num };
          }
          return { size: 12 };
        },
      },
    ],
    toDOM: (mark) => ['span', { style: `font-size: ${mark.attrs.size}pt;` }, 0],
  },
  color: {
    attrs: { hex: { default: '#000000' } },
    parseDOM: [
      {
        style: 'color',
        getAttrs: (value) => ({ hex: typeof value === 'string' ? value : '#000000' }),
      },
    ],
    toDOM: (mark) => ['span', { style: `color: ${mark.attrs.hex};` }, 0],
  },
};

export const docxSchema = new Schema({ nodes, marks });
