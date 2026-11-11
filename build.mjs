import { Buffer } from 'node:buffer';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { build } from 'esbuild';

const root = dirname(fileURLToPath(import.meta.url));
const outfile = resolve(root, 'resources/dist/intelliada.min.js');

await build({
    entryPoints: [resolve(root, 'resources/js/script.ts')],
    outfile,
    bundle: true,
    minify: true,
    format: 'iife',
    target: ['es2020'],
    platform: 'browser',
});

const sizeKb = (
    Buffer.byteLength(readFileSync(outfile, 'utf8'), 'utf8') / 1024
).toFixed(2);

console.log(`Built resources/dist/intelliada.min.js (${sizeKb} KB minified)`);
