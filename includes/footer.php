</main>
<footer class="site-footer">
    <?php if (($page ?? '') !== 'acasa'): ?>
        <?= wave_svg() ?>
    <?php endif; ?>
    <div class="wrap footer-grid">
        <div>
            <a class="brand-lockup" href="<?= e(url()) ?>">
                <img src="<?= e(asset('assets/img/logo-alb.png?v=6')) ?>" alt="<?= e(SITE_NAME) ?>">
            </a>
            <p><?= tr('Importăm din Italia cafea cu aromă și un raport ideal între Arabica și Robusta. Livrăm cialde, capsule și boabe în toată țara, în Republica Moldova. Oferim aparate în comodat și aparate la vânzare.') ?></p>
        </div>
        <div>
            <h2><?= tr('Navigare') ?></h2>
            <ul>
                <li><a href="<?= e(url()) ?>"><?= tr('Acasă') ?></a></li>
                <li><a href="<?= e(url('produse.php')) ?>"><?= tr('Cafea') ?></a></li>
                <li><a href="<?= e(url('aparate.php')) ?>"><?= tr('Aparate') ?></a></li>
                <li><a href="<?= e(url('despre.php')) ?>"><?= tr('Despre') ?></a></li>
                <li><a href="<?= e(url('contact.php')) ?>"><?= tr('Contact') ?></a></li>
                <li><a href="<?= e(current_user() ? url('cabinet.php') : url('autentificare.php')) ?>"><?= current_user() ? tr('Cabinet') : tr('Cont') ?></a></li>
            </ul>
        </div>
        <div>
            <h2><?= tr('Contact') ?></h2>
            <ul>
                <li><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></li>
                <?php if (SITE_PHONE !== ''): ?>
                    <li><a href="tel:<?= e(preg_replace('/\s+/', '', SITE_PHONE) ?? '') ?>"><?= e(SITE_PHONE) ?></a></li>
                <?php endif; ?>
                <?php if (site_whatsapp_digits() !== ''): ?>
                    <li><a href="<?= e(site_whatsapp_url(t('Bună! Vreau să aflu mai multe despre cafeaua Casa Bradu Caffè.'))) ?>" target="_blank" rel="noopener noreferrer"><?= tr('WhatsApp') ?></a></li>
                <?php endif; ?>
                <li><?= e(SITE_LEGAL) ?></li>
                <li><?= tr('Republica Moldova') ?></li>
            </ul>
        </div>
    </div>
    <div class="wrap legal">
        <p>© <?= date('Y') ?> <?= e(SITE_LEGAL) ?>. <?= tr('Toate drepturile rezervate.') ?></p>
        <p><?= e(SITE_DOMAIN) ?></p>
    </div>
</footer>
<?php if (($page ?? '') !== 'admin' && site_whatsapp_digits() !== ''): ?>
    <?php
    $waDefault = t('Bună! Vreau să aflu mai multe despre cafeaua Casa Bradu Caffè.');
    $waBase = site_whatsapp_url();
    ?>
    <div class="wa-chat" id="wa-chat" data-wa-base="<?= e($waBase) ?>" data-wa-default="<?= e($waDefault) ?>">
        <div class="wa-chat-panel" id="wa-chat-panel" hidden>
            <div class="wa-chat-head">
                <div>
                    <p class="wa-chat-brand"><?= e(SITE_NAME) ?></p>
                    <p class="wa-chat-status"><?= tr('Răspundem pe WhatsApp') ?></p>
                </div>
                <button type="button" class="wa-chat-close" id="wa-chat-close" aria-label="<?= e(t('Închide')) ?>">×</button>
            </div>
            <div class="wa-chat-body">
                <p class="wa-chat-bubble"><?= tr('Salut! Scrie-ne aici și continuăm conversația pe WhatsApp.') ?></p>
                <label class="sr-only" for="wa-chat-message"><?= tr('Mesajul tău') ?></label>
                <textarea id="wa-chat-message" rows="3" maxlength="500" placeholder="<?= e(t('Scrie mesajul tău…')) ?>"><?= e($waDefault) ?></textarea>
                <a class="btn btn-dark wa-chat-send" id="wa-chat-send" href="<?= e(site_whatsapp_url($waDefault)) ?>" target="_blank" rel="noopener noreferrer"><?= tr('Trimite pe WhatsApp') ?></a>
            </div>
        </div>
        <button type="button" class="wa-chat-toggle" id="wa-chat-toggle" aria-expanded="false" aria-controls="wa-chat-panel">
            <span class="wa-chat-toggle-icon" aria-hidden="true">
                <svg viewBox="0 0 32 32" width="28" height="28" focusable="false"><path fill="currentColor" d="M16.02 3C9.4 3 4 8.3 4 14.8c0 2.1.56 4.1 1.62 5.9L4 29l8.5-1.56A12.2 12.2 0 0 0 16.02 27C22.66 27 28 21.7 28 15.2 28 8.7 22.66 3 16.02 3zm0 21.9c-1.4 0-2.76-.34-3.98-.99l-.28-.15-5.04.93.95-4.9-.18-.3A9.6 9.6 0 0 1 6.3 14.8c0-5.2 4.36-9.4 9.72-9.4 5.36 0 9.72 4.2 9.72 9.4 0 5.2-4.36 9.4-9.72 9.4zm5.34-7.05c-.29-.14-1.72-.84-1.98-.94-.27-.1-.46-.14-.66.14-.2.29-.76.94-.93 1.13-.17.2-.34.22-.63.07-.29-.14-1.22-.44-2.32-1.4-.86-.75-1.44-1.68-1.61-1.97-.17-.29-.02-.44.13-.58.13-.13.29-.34.43-.51.14-.17.19-.29.29-.49.1-.2.05-.37-.02-.51-.07-.14-.66-1.57-.9-2.15-.24-.57-.48-.49-.66-.5h-.56c-.2 0-.51.07-.78.37-.27.29-1.02.98-1.02 2.4s1.05 2.78 1.19 2.97c.15.2 2.06 3.27 5.08 4.46.71.3 1.26.48 1.7.61.71.23 1.36.2 1.87.12.57-.09 1.72-.69 1.96-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.2-.55-.34z"/></svg>
            </span>
            <span class="wa-chat-toggle-label"><?= tr('Chat WhatsApp') ?></span>
        </button>
    </div>
<?php endif; ?>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=9" defer></script>
</body>
</html>
