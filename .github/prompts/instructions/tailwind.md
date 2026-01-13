---
mode: 'agent'
description: 'Tailwind CSS 4.1.3 styling instructions and conventions'
---


# Tailwind CSS 4.1.3 Styling Instructions (Project-specific)

## Version
- Vždy používej Tailwind CSS 4.1.3 a jeho aktuální utility a komponenty.
- Dodržuj konvence a best practices Tailwindu 4.1.3.
- Všechny nové komponenty, třídy a komentáře musí být v angličtině.

## Utility Classes
- Vždy používej utility-first přístup.
- Preferuj Tailwind utility před vlastním CSS (custom CSS pouze výjimečně).
- Používej responsive prefixy pro různé velikosti obrazovek.
- Využívej Tailwind color palette a spacing scale.

## Configuration
- Konfigurace Tailwindu je v `tailwind.config.js`.
- Vlastní utility a komponenty v `resources/css/`.
- Build proces je řízen přes Vite (`vite.config.js`) a PostCSS (`postcss.config.js`).

## Best Practices
- Při seskupování utilit používej sémantické názvy tříd.
- Využívej Tailwind component layer pro znovupoužitelné styly.
- Používej `@apply` pouze výjimečně, preferuj utility classes.
- Dodržuj mobile-first přístup.
- Všechny úpravy a komponenty testuj ve všech podporovaných prohlížečích a na různých zařízeních.

## Integration with Backpack
- Pro admin rozhraní (Backpack) vždy přepisuj styly pomocí Tailwind utilit, ne custom CSS.
- Zachovej konzistenci admin rozhraní.
- Pro vlastní frontend komponenty používej výhradně Tailwind.
- Dbej na přístupnost (accessibility) pomocí Tailwind utilit.

## File Organization
- Hlavní CSS soubor: `resources/css/app.css`
- Komponentové styly v samostatných souborech.
- Pro zpracování používej PostCSS (`postcss.config.js`).

---
*Tento soubor je závazný pro práci se styly v projektu. Při nejasnostech ověř aktuální stav v kódu, konfiguraci a dokumentaci.*
