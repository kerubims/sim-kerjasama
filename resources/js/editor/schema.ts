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
      spaceBefore: { default: 0 },
      spaceAfter: { default: 0 },
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
        style: `text-align: ${node.attrs.alignment}; margin-top: ${node.attrs.spaceBefore || 0}pt; margin-bottom: ${node.attrs.spaceAfter || 0}pt;`,
        class: `text-${node.attrs.alignment}`,
      },
      0,
    ],
  },
  image: {
    group: 'block',
    inline: false,
    attrs: {
      src: {},
      alignment: { default: 'center' },
    },
    parseDOM: [
      {
        tag: 'img[src]',
        getAttrs: (dom) => {
          const el = dom as HTMLImageElement;
          return { src: el.getAttribute('src') };
        },
      },
    ],
    toDOM: (node) => [
      'div',
      { style: `text-align: ${node.attrs.alignment || 'center'}; margin: 1.5rem 0;` },
      ['img', { src: node.attrs.src, style: 'max-width: 180px; height: auto; display: inline-block;' }],
    ],
  },
  page_break: {
    group: 'block',
    inline: false,
    toDOM: () => [
      'div',
      {
        class: 'page-break-divider',
        style: 'border-top: 2px dashed #94a3b8; margin: 3rem 0; text-align: center; color: #64748b; font-size: 11px; font-weight: bold; position: relative;',
      },
      '--- LEMBAR BARU (A4 PAGE BREAK) ---',
    ],
  },
  table: {
    content: 'table_row+',
    group: 'block',
    attrs: {
      hasBorders: { default: false },
    },
    parseDOM: [{ tag: 'table' }],
    toDOM: (node) => [
      'table',
      {
        style: `width: 100%; border-collapse: collapse; margin: 1rem 0; ${node.attrs.hasBorders ? 'border: 1px solid #000;' : 'border: 1px dashed #cbd5e1;'}`,
      },
      ['tbody', 0],
    ],
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
        style: `padding: 8px 12px; vertical-align: top; border: 1px dashed #cbd5e1; background-color: ${node.attrs.shading || 'transparent'};`,
      },
      0,
    ],
  },
  text: {
    group: 'inline',
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
        getAttrs: (value) => ({ family: value }),
      },
    ],
    toDOM: (mark) => ['span', { style: `font-family: "${mark.attrs.family}", serif` }, 0],
  },
  fontSize: {
    attrs: { size: { default: 12 } },
    parseDOM: [
      {
        style: 'font-size',
        getAttrs: (value) => ({ size: parseFloat(value as string) }),
      },
    ],
    toDOM: (mark) => ['span', { style: `font-size: ${mark.attrs.size}pt` }, 0],
  },
  color: {
    attrs: { hex: { default: '#000000' } },
    parseDOM: [
      {
        style: 'color',
        getAttrs: (value) => ({ hex: value }),
      },
    ],
    toDOM: (mark) => ['span', { style: `color: ${mark.attrs.hex}` }, 0],
  },
};

export const docxSchema = new Schema({ nodes, marks });
