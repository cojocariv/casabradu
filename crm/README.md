# CRM Casa Bradu · crm.casabradu.md

Evidență internă: clienți, aparate în comodat, vânzări cialde/capsule/boabe/pahare.

## Pe Plesk

1. Creează subdomeniul **crm.casabradu.md**.
2. Document root: folderul `crm` din site  
   (ex: `httpdocs/crm` sau `httpdocs/SITE/crm`, după cum e structura ta).
3. Folosește același `includes/config.php` al site-ului (parola admin e aceeași).
4. La primul acces, tabelele `crm_clienti`, `crm_aparate`, `crm_vanzari` se creează automat în aceeași bază MySQL.

## Local / fără subdomeniu

Deschide: `https://casabradu.md/crm/` (sau localhost + `/crm/`).

## Autentificare

Utilizatorul și parola din `ADMIN_USER` / `ADMIN_PASS` (config site).
