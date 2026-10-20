<?php

declare(strict_types=1);

/**
 * Test Google Sheets webhook from CLI:
 *   php tools/test-sheets.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

echo "URL set: " . (sheets_enabled() ? 'yes' : 'NO — empty in config.php') . PHP_EOL;
echo "Secret set: " . (sheets_webhook_secret() !== '' ? 'yes' : 'NO') . PHP_EOL;

if (!sheets_enabled()) {
    echo "Put the Apps Script web app URL in GOOGLE_SHEETS_WEBHOOK_URL inside includes/config.php" . PHP_EOL;
    exit(1);
}

$ok = sheets_send_test_row();
echo ($ok ? "OK — row should appear on sheet \"de pe site\"" : "FAILED — see storage/sheets.log") . PHP_EOL;
if (is_file(dirname(__DIR__) . '/storage/sheets.log')) {
    echo "--- last log lines ---" . PHP_EOL;
    $lines = file(dirname(__DIR__) . '/storage/sheets.log') ?: [];
    echo implode('', array_slice($lines, -8));
}

exit($ok ? 0 : 1);
