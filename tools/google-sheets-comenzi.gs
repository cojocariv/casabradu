/**
 * Casa Bradu Caffè — comenzi din site → foaia "de pe site"
 *
 * SETUP (o singură dată):
 * 1. Deschide spreadsheet-ul "Contacte clienti"
 * 2. Extensii → Apps Script
 * 3. ȘTERGE tot codul vechi (inclusiv myFunction) și lipește acest fișier
 * 4. În SPREADSHEET_ID pune ID-ul din URL-ul foii:
 *    https://docs.google.com/spreadsheets/d/<<<ACEST_ID>>>/edit
 * 5. În WEBHOOK_SECRET pune aceeași valoare ca GOOGLE_SHEETS_WEBHOOK_SECRET din config.php
 * 6. Salvează → Rulează setupSheet() o dată (▶) și acceptă permisiunile
 * 7. Implementare → Gestionează implementările → creion → Versiune nouă → Implementează
 *    - Executare ca: Eu
 *    - Cine are acces: Oricine
 * 8. Copiază URL-ul /exec în GOOGLE_SHEETS_WEBHOOK_URL din config.php
 */

var SPREADSHEET_ID = ''; // OBLIGATORIU — ID din URL-ul Google Sheet
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

function getSpreadsheet_() {
  if (SPREADSHEET_ID && String(SPREADSHEET_ID).trim() !== '') {
    return SpreadsheetApp.openById(String(SPREADSHEET_ID).trim());
  }
  var active = SpreadsheetApp.getActiveSpreadsheet();
  if (active) {
    return active;
  }
  throw new Error('Setează SPREADSHEET_ID în script (din URL-ul foii Google).');
}

function setupSheet() {
  var ss = getSpreadsheet_();
  var sheet = ss.getSheetByName(SHEET_NAME);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAME);
  }
  ensureHeaders_(sheet);
  Logger.log('OK sheet="' + SHEET_NAME + '" id=' + ss.getId());
}

function doPost(e) {
  try {
    Logger.log('doPost start');
    if (!e || !e.postData || !e.postData.contents) {
      Logger.log('empty_body');
      return jsonOut_({ ok: false, error: 'empty_body' });
    }

    var data = JSON.parse(e.postData.contents);
    if ((data.secret || '') !== WEBHOOK_SECRET) {
      Logger.log('secret mismatch');
      return jsonOut_({ ok: false, error: 'secret' });
    }

    var ss = getSpreadsheet_();
    var name = data.sheet || SHEET_NAME;
    var sheet = ss.getSheetByName(name);
    if (!sheet) {
      sheet = ss.insertSheet(name);
    }
    ensureHeaders_(sheet);

    var row = data.row;
    if (!row || !row.length) {
      return jsonOut_({ ok: false, error: 'row' });
    }

    // appendRow accepts only primitives
    var clean = [];
    for (var i = 0; i < row.length; i++) {
      var v = row[i];
      if (v === null || v === undefined) {
        clean.push('');
      } else if (typeof v === 'object') {
        clean.push(JSON.stringify(v));
      } else {
        clean.push(v);
      }
    }

    sheet.appendRow(clean);
    Logger.log('appended order=' + (clean[1] || ''));
    return jsonOut_({ ok: true });
  } catch (err) {
    Logger.log('doPost error: ' + err);
    try {
      return jsonOut_({ ok: false, error: String(err) });
    } catch (e2) {
      return ContentService.createTextOutput('{"ok":false,"error":"fatal"}');
    }
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

function jsonOut_(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

function doGet() {
  return ContentService.createTextOutput('Casa Bradu sheets webhook OK');
}

/** Rulează din editor ca să simulezi un POST (fără site). */
function testDoPost() {
  var fake = {
    postData: {
      contents: JSON.stringify({
        secret: WEBHOOK_SECRET,
        sheet: SHEET_NAME,
        row: [
          Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'dd.MM.yyyy HH:mm'),
          'TEST-EDITOR',
          'Test Companie',
          'Test Nume',
          '060000000',
          'test@casabradu.md',
          'Cafea × 1',
          '',
          '1',
          '0',
          '',
          'Chișinău',
          'test editor',
          'test',
          'cont email'
        ]
      })
    }
  };
  var out = doPost(fake);
  Logger.log(out.getContent());
}
