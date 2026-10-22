</main>
<footer class="site-footer">
    <?php if (($page ?? '') !== 'acasa'): ?>
        <?= wave_svg() ?>
    <?php endif; ?>
    <div class="wrap footer-grid">
        <div>
            <a class="brand-lockup" href="<?= e(url()) ?>">
                <img src="<?= e(asset('assets/img/logo-alb.png?v=1')) ?>" alt="<?= e(SITE_NAME) ?>">
            </a>
            <p><?= tr('Importăm din Italia cafea cu aromă și un raport ideal între Arabica și Robusta. Livrăm cialde, capsule și boabe în toată țara, în Republica Moldova, și oferim aparate în comodat.') ?></p>
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
<script src="<?= e(asset('assets/js/main.js')) ?>?v=3" defer></script>
</body>
</html>
