/**
 * Logotip za HTML maile (naloga 30) — NI del paketa.
 *
 * Iz orodja/ikone/logo.svg izriše PNG širine 600 px z zapečeno belo podlago (ne prozorno:
 * temni način v Outlooku/Gmailu bi temni logotip na prozorni podlagi skril).
 *
 * Zagon (Node + Playwright z Chromium):
 *   node orodja/ikone/logo-mail.js
 * Rezultat: paket/com_belauncerunde/media/images/logo-mail.png
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SIRINA = 600;
const vir = path.join(__dirname, 'logo.svg');
const cilj = path.join(__dirname, '..', '..', 'paket', 'com_belauncerunde', 'media', 'images', 'logo-mail.png');

(async () => {
    const svg = fs.readFileSync(vir, 'utf8');
    // Razmerje iz viewBox, da je višina celoštevilska in brez praznega roba.
    const viewBox = svg.match(/viewBox="([^"]+)"/)[1].trim().split(/\s+/).map(Number);
    const visina = Math.round(SIRINA * viewBox[3] / viewBox[2]);

    const brskalnik = await chromium.launch();
    const stran = await brskalnik.newPage({ viewport: { width: SIRINA, height: visina }, deviceScaleFactor: 1 });
    await stran.setContent(
        '<!doctype html><html><body style="margin:0;background:#ffffff">'
        + svg.replace('<svg ', `<svg width="${SIRINA}" height="${visina}" style="display:block" `)
        + '</body></html>'
    );
    // omitBackground: false → bela podlaga strani je zapečena v PNG (brez alfa prosojnosti).
    await stran.screenshot({ path: cilj, omitBackground: false, clip: { x: 0, y: 0, width: SIRINA, height: visina } });
    await brskalnik.close();
    console.log(`${path.relative(process.cwd(), cilj)} (${SIRINA} × ${visina})`);
})();
