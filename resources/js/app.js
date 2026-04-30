import './bootstrap';

import Alpine from 'alpinejs';

import html2pdf from 'html2pdf.js';
window.html2pdf = html2pdf;

import mammoth from 'mammoth';
window.mammoth = mammoth;

window.Alpine = Alpine;

Alpine.start();
