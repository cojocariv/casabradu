/**
 * Casa Bradu Caffè — comenzi din site → foaia "de pe site"
 *
 * SETUP (o singură dată):
 * 1. Deschide spreadsheet-ul "Contacte clienti"
 * 2. Extensii → Apps Script
 * 3. Șterge codul implicit și lipește acest fișier
 * 4. În WEBHOOK_SECRET pune aceeași valoare ca GOOGLE_SHEETS_WEBHOOK_SECRET din config.php
 * 5. Salvează → Implementare → Aplicatie web nouă
 *    - Executare ca: Eu
 *    - Cine are acces: Oricine
 * 6. Copiază URL-ul și pune-l în config.php ca GOOGLE_SHEETS_WEBHOOK_URL
 * 7. Rulează o dată funcția setupSheet() din editor (▶) ca să creeze anteturile
 */

var SHEET_NAME = 'de pe site';
var WEBHOOK_SECRET = '1dcc3373279d145d433b1e53dc671607';

var HEADERS = [
  'Data',
  'Nr. comandă',
  'Companie',
  'Nume',
  'Telefon',
  'Email',
  'Produse',
  'Cutii',
  'Cantități',
  'Total',
  'TVA',
  'Adresă',
  'Notă',
  'Status',
  'Sursă'
];

function setupSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(SHEET_NAME);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAME);
  }
  ensureHeaders_(sheet);
}

function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return jsonOut({ ok: false, error: 'empty_body' });
    }

    var data = JSON.parse(e.postData.contents);
    if ((data.secret || '') !== WEBHOOK_SECRET) {
      return jsonOut({ ok: false, error: 'secret' });
    }

    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var name = data.sheet || SHEET_NAME;
    var sheet = ss.getSheetByName(name);
    if (!sheet) {
      sheet = ss.insertSheet(name);
    }
    ensureHeaders_(sheet);

    var row = data.row;
    if (!row || !row.length) {
      return jsonOut({ ok: false, error: 'row' });
    }

    sheet.appendRow(row);
    return jsonOut({ ok: true });
  } catch (err) {
    return jsonOut({ ok: false, error: String(err) });
  }
}

function ensureHeaders_(sheet) {
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(HEADERS);
    sheet.setFrozenRows(1);
    sheet.getRange(1, 1, 1, HEADERS.length).setFontWeight('bold');
    return;
  }
  var first = String(sheet.getRange(1, 1).getValue() || '');
  if (first !== HEADERS[0]) {
    sheet.insertRowBefore(1);
    sheet.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]).setFontWeight('bold');
    sheet.setFrozenRows(1);
  }
}

function jsonOut(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

function doGet() {
  return ContentService.createTextOutput('Casa Bradu sheets webhook OK');
}
