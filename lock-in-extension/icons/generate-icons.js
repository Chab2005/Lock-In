/**
 * Generate PNG icons from icon.svg using Node + Canvas (no native deps).
 * Run: node generate-icons.js
 *
 * Requires: npm install canvas
 * Or use the shell script with librsvg if available.
 */
const { createCanvas } = require('canvas');
const fs   = require('fs');
const path = require('path');

const SIZES = [16, 32, 48, 128];

// Minimal lock icon as canvas drawing (matches icon.svg)
function drawIcon(ctx, size) {
    const s = size / 128;
    ctx.fillStyle = '#0f0f0f';
    roundRect(ctx, 0, 0, size, size, 22 * s);
    ctx.fill();

    ctx.fillStyle = '#f0f0f0';
    // Shackle
    ctx.beginPath();
    ctx.arc(64 * s, 42 * s, 12 * s, Math.PI, 0);
    ctx.lineTo(76 * s, 58 * s);
    ctx.lineTo(52 * s, 58 * s);
    ctx.closePath();
    ctx.fill();

    // Body
    roundRect(ctx, 26 * s, 58 * s, 76 * s, 52 * s, 4 * s);
    ctx.fill();

    // Keyhole (cutout)
    ctx.fillStyle = '#0f0f0f';
    ctx.beginPath();
    ctx.arc(64 * s, 78 * s, 8 * s, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillRect((62 - 4) * s, 84 * s, 8 * s, 14 * s);
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

for (const size of SIZES) {
    const canvas = createCanvas(size, size);
    const ctx = canvas.getContext('2d');
    drawIcon(ctx, size);
    const buf = canvas.toBuffer('image/png');
    const out = path.join(__dirname, `icon-${size}.png`);
    fs.writeFileSync(out, buf);
    console.log(`Generated icon-${size}.png`);
}

console.log('Done.');
