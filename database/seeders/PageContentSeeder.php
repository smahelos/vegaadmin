<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories first if they don't exist
        $this->createCategories();

        // Create pages with content
        $this->createFeaturesPage();
        $this->createPricingPage();
        $this->createTemplatesPage();
        $this->createApiPage();
        $this->createHelpPage();
        $this->createContactPage();
        $this->createDocumentationPage();
        $this->createServiceStatusPage();
        $this->createPrivacyPolicyPage();
        $this->createTermsPage();
        $this->createCookiesPage();
        $this->createFeatureCategoryPages();
    }

    private function createCategories(): void
    {
        $categories = [
            [
                'name' => [
                    'cs' => 'Hlavní stránky',
                    'en' => 'Main Pages',
                    'de' => 'Hauptseiten',
                    'sk' => 'Hlavné stránky',
                ],
                'slug' => [
                    'cs' => 'hlavni-stranky',
                    'en' => 'main-pages',
                    'de' => 'hauptseiten',
                    'sk' => 'hlavne-stranky',
                ],
                'description' => [
                    'cs' => 'Hlavní informační stránky webu',
                    'en' => 'Main informational pages of the website',
                    'de' => 'Hauptinformationsseiten der Website',
                    'sk' => 'Hlavné informačné stránky webu',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Právní',
                    'en' => 'Legal',
                    'de' => 'Rechtlich',
                    'sk' => 'Právne',
                ],
                'slug' => [
                    'cs' => 'pravni',
                    'en' => 'legal',
                    'de' => 'rechtlich',
                    'sk' => 'pravne',
                ],
                'description' => [
                    'cs' => 'Právní dokumenty a podmínky',
                    'en' => 'Legal documents and terms',
                    'de' => 'Rechtliche Dokumente und Bedingungen',
                    'sk' => 'Právne dokumenty a podmienky',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Podpora',
                    'en' => 'Support',
                    'de' => 'Unterstützung',
                    'sk' => 'Podpora',
                ],
                'slug' => [
                    'cs' => 'podpora',
                    'en' => 'support',
                    'de' => 'unterstützung',
                    'sk' => 'podpora',
                ],
                'description' => [
                    'cs' => 'Nápověda a dokumentace',
                    'en' => 'Help and documentation',
                    'de' => 'Hilfe und Dokumentation',
                    'sk' => 'Pomoc a dokumentácia',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Funkce',
                    'en' => 'Features',
                    'de' => 'Funktionen',
                    'sk' => 'Funkcie',
                ],
                'slug' => [
                    'cs' => 'funkce',
                    'en' => 'features',
                    'de' => 'funktionen',
                    'sk' => 'funkcie',
                ],
                'description' => [
                    'cs' => 'Funkce systému',
                    'en' => 'System features',
                    'de' => 'Systemfunktionen',
                    'sk' => 'Funkcie systému',
                ],
            ],
        ];

        foreach ($categories as $category) {
            PageCategory::firstOrCreate(
                ['slug->cs' => $category['slug']['cs']],
                $category,
            );
        }
    }

    private function categoryId(string $slug): int
    {
        $categoryQuery = PageCategory::where('slug->cs', $slug);
        $category = $categoryQuery->first();

        return $category->id;
    }

    private function createFeaturesPage(): void
    {
        $categoryId = $this->categoryId('hlavni-stranky');

        $content = [
            'cs' => $this->getFeaturesContent('cs'),
            'en' => $this->getFeaturesContent('en'),
            'de' => $this->getFeaturesContent('de'),
            'sk' => $this->getFeaturesContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'funkce'],
            [
                'name' => [
                    'cs' => 'Funkce',
                    'en' => 'Features',
                    'de' => 'Funktionen',
                    'sk' => 'Funkcie',
                ],
                'slug' => [
                    'cs' => 'funkce',
                    'en' => 'features',
                    'de' => 'funktionen',
                    'sk' => 'funkcie',
                ],
                'category_id' => $categoryId,
                'sort_order' => 1,
                'description' => [
                    'cs' => 'Všechny funkce našeho fakturačního systému na jednom místě',
                    'en' => 'All features of our invoicing system in one place',
                    'de' => 'Alle Funktionen unseres Rechnungssystems an einem Ort',
                    'sk' => 'Všetky funkcie nášho fakturačného systému na jednom mieste',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createPricingPage(): void
    {
        $categoryId = $this->categoryId('hlavni-stranky');

        $content = [
            'cs' => $this->getPricingContent('cs'),
            'en' => $this->getPricingContent('en'),
            'de' => $this->getPricingContent('de'),
            'sk' => $this->getPricingContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'cenik'],
            [
                'name' => [
                    'cs' => 'Ceník',
                    'en' => 'Pricing',
                    'de' => 'Preise',
                    'sk' => 'Cenník',
                ],
                'slug' => [
                    'cs' => 'cenik',
                    'en' => 'pricing',
                    'de' => 'preise',
                    'sk' => 'cennik',
                ],
                'category_id' => $categoryId,
                'sort_order' => 2,
                'description' => [
                    'cs' => 'Transparentní ceník našich služeb',
                    'en' => 'Transparent pricing of our services',
                    'de' => 'Transparente Preisgestaltung unserer Dienstleistungen',
                    'sk' => 'Transparentný cenník našich služieb',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createTemplatesPage(): void
    {
        $categoryId = $this->categoryId('hlavni-stranky');

        $content = [
            'cs' => $this->getTemplatesContent('cs'),
            'en' => $this->getTemplatesContent('en'),
            'de' => $this->getTemplatesContent('de'),
            'sk' => $this->getTemplatesContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'sablony'],
            [
                'name' => [
                    'cs' => 'Šablony',
                    'en' => 'Templates',
                    'de' => 'Vorlagen',
                    'sk' => 'Šablóny',
                ],
                'slug' => [
                    'cs' => 'sablony',
                    'en' => 'templates',
                    'de' => 'vorlagen',
                    'sk' => 'sablony',
                ],
                'category_id' => $categoryId,
                'sort_order' => 3,
                'description' => [
                    'cs' => 'Předpřipravené šablony faktur pro různá odvětví',
                    'en' => 'Pre-designed invoice templates for various industries',
                    'de' => 'Vorgefertigte Rechnungsvorlagen für verschiedene Branchen',
                    'sk' => 'Predpripravené šablóny faktúr pre rôzne odvetvia',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createApiPage(): void
    {
        $categoryId = $this->categoryId('hlavni-stranky');

        $content = [
            'cs' => $this->getApiContent('cs'),
            'en' => $this->getApiContent('en'),
            'de' => $this->getApiContent('de'),
            'sk' => $this->getApiContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'api'],
            [
                'name' => [
                    'cs' => 'API',
                    'en' => 'API',
                    'de' => 'API',
                    'sk' => 'API',
                ],
                'slug' => [
                    'cs' => 'api',
                    'en' => 'api',
                    'de' => 'api',
                    'sk' => 'api',
                ],
                'category_id' => $categoryId,
                'sort_order' => 4,
                'description' => [
                    'cs' => 'Informace o API pro vývojáře',
                    'en' => 'API information for developers',
                    'de' => 'API-Informationen für Entwickler',
                    'sk' => 'Informácie o API pre vývojárov',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createHelpPage(): void
    {
        $categoryId = $this->categoryId('podpora');

        $content = [
            'cs' => $this->getHelpContent('cs'),
            'en' => $this->getHelpContent('en'),
            'de' => $this->getHelpContent('de'),
            'sk' => $this->getHelpContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'napoveda'],
            [
                'name' => [
                    'cs' => 'Nápověda',
                    'en' => 'Help',
                    'de' => 'Hilfe',
                    'sk' => 'Pomoc',
                ],
                'slug' => [
                    'cs' => 'napoveda',
                    'en' => 'help',
                    'de' => 'hilfe',
                    'sk' => 'pomoc',
                ],
                'category_id' => $categoryId,
                'sort_order' => 1,
                'description' => [
                    'cs' => 'Nápověda k používání systému',
                    'en' => 'Help for using the system',
                    'de' => 'Hilfe zur Nutzung des Systems',
                    'sk' => 'Pomoc pri používaní systému',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createContactPage(): void
    {
        $categoryId = $this->categoryId('hlavni-stranky');

        $content = [
            'cs' => $this->getContactContent('cs'),
            'en' => $this->getContactContent('en'),
            'de' => $this->getContactContent('de'),
            'sk' => $this->getContactContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'kontakt'],
            [
                'name' => [
                    'cs' => 'Kontakt',
                    'en' => 'Contact',
                    'de' => 'Kontakt',
                    'sk' => 'Kontakt',
                ],
                'slug' => [
                    'cs' => 'kontakt',
                    'en' => 'contact',
                    'de' => 'kontakt',
                    'sk' => 'kontakt',
                ],
                'category_id' => $categoryId,
                'sort_order' => 5,
                'description' => [
                    'cs' => 'Kontaktní informace a formulář',
                    'en' => 'Contact information and form',
                    'de' => 'Kontaktinformationen und Formular',
                    'sk' => 'Kontaktné informácie a formulár',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createDocumentationPage(): void
    {
        $categoryId = $this->categoryId('podpora');

        $content = [
            'cs' => $this->getDocumentationContent('cs'),
            'en' => $this->getDocumentationContent('en'),
            'de' => $this->getDocumentationContent('de'),
            'sk' => $this->getDocumentationContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'dokumentace'],
            [
                'name' => [
                    'cs' => 'Dokumentace',
                    'en' => 'Documentation',
                    'de' => 'Dokumentation',
                    'sk' => 'Dokumentácia',
                ],
                'slug' => [
                    'cs' => 'dokumentace',
                    'en' => 'documentation',
                    'de' => 'dokumentation',
                    'sk' => 'dokumentacia',
                ],
                'category_id' => $categoryId,
                'sort_order' => 2,
                'description' => [
                    'cs' => 'Kompletní průvodce aplikací',
                    'en' => 'Complete application guide',
                    'de' => 'Kompletter Anwendungsguide',
                    'sk' => 'Kompletný sprievodca aplikáciou',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createServiceStatusPage(): void
    {
        $categoryId = $this->categoryId('podpora');

        $content = [
            'cs' => $this->getServiceStatusContent('cs'),
            'en' => $this->getServiceStatusContent('en'),
            'de' => $this->getServiceStatusContent('de'),
            'sk' => $this->getServiceStatusContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'stav-sluzby'],
            [
                'name' => [
                    'cs' => 'Stav služby',
                    'en' => 'Service Status',
                    'de' => 'Dienststatus',
                    'sk' => 'Stav služby',
                ],
                'slug' => [
                    'cs' => 'stav-sluzby',
                    'en' => 'service-status',
                    'de' => 'dienststatus',
                    'sk' => 'stav-sluzby',
                ],
                'category_id' => $categoryId,
                'sort_order' => 3,
                'description' => [
                    'cs' => 'Aktuální stav systému',
                    'en' => 'Current system status',
                    'de' => 'Aktueller Systemstatus',
                    'sk' => 'Aktuálny stav systému',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createPrivacyPolicyPage(): void
    {
        $categoryId = $this->categoryId('pravni');

        $content = [
            'cs' => $this->getPrivacyPolicyContent('cs'),
            'en' => $this->getPrivacyPolicyContent('en'),
            'de' => $this->getPrivacyPolicyContent('de'),
            'sk' => $this->getPrivacyPolicyContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'zasady-ochrany-osobnich-udaju'],
            [
                'name' => [
                    'cs' => 'Zásady ochrany osobních údajů',
                    'en' => 'Privacy Policy',
                    'de' => 'Datenschutzrichtlinie',
                    'sk' => 'Zásady ochrany osobných údajov',
                ],
                'slug' => [
                    'cs' => 'zasady-ochrany-osobnich-udaju',
                    'en' => 'privacy-policy',
                    'de' => 'datenschutzrichtlinie',
                    'sk' => 'zasady-ochrany-osobnych-udajov',
                ],
                'category_id' => $categoryId,
                'sort_order' => 1,
                'description' => [
                    'cs' => 'Informace o zpracování osobních údajů v souladu s GDPR',
                    'en' => 'Information on personal data processing in accordance with GDPR',
                    'de' => 'Informationen zur Verarbeitung personenbezogener Daten gemäß GDPR',
                    'sk' => 'Informácie o spracovaní osobných údajov v súlade s GDPR',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createTermsPage(): void
    {
        $categoryId = $this->categoryId('pravni');

        $content = [
            'cs' => $this->getTermsContent('cs'),
            'en' => $this->getTermsContent('en'),
            'de' => $this->getTermsContent('de'),
            'sk' => $this->getTermsContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'obchodni-podminky'],
            [
                'name' => [
                    'cs' => 'Obchodní podmínky',
                    'en' => 'Terms and Conditions',
                    'de' => 'Geschäftsbedingungen',
                    'sk' => 'Obchodné podmienky',
                ],
                'slug' => [
                    'cs' => 'obchodni-podminky',
                    'en' => 'terms-and-conditions',
                    'de' => 'geschaftsbedingungen',
                    'sk' => 'obchodne-podmienky',
                ],
                'category_id' => $categoryId,
                'sort_order' => 2,
                'description' => [
                    'cs' => 'Podmínky používání naší fakturační aplikace',
                    'en' => 'Terms of use of our invoicing application',
                    'de' => 'Nutzungsbedingungen unserer Rechnungsanwendung',
                    'sk' => 'Podmienky používania našej fakturačnej aplikácie',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createCookiesPage(): void
    {
        $categoryId = $this->categoryId('pravni');

        $content = [
            'cs' => $this->getCookiesContent('cs'),
            'en' => $this->getCookiesContent('en'),
            'de' => $this->getCookiesContent('de'),
            'sk' => $this->getCookiesContent('sk'),
        ];

        Page::updateOrCreate(
            ['slug->cs' => 'zasady-pouzivani-cookies'],
            [
                'name' => [
                    'cs' => 'Zásady používání cookies',
                    'en' => 'Cookies Policy',
                    'de' => 'Cookie-Richtlinie',
                    'sk' => 'Zásady používania cookies',
                ],
                'slug' => [
                    'cs' => 'zasady-pouzivani-cookies',
                    'en' => 'cookies-policy',
                    'de' => 'cookie-richtlinie',
                    'sk' => 'zasady-pouzivania-cookies',
                ],
                'category_id' => $categoryId,
                'sort_order' => 3,
                'description' => [
                    'cs' => 'Informace o tom, jak naše webová stránka používá soubory cookies',
                    'en' => 'Information on how our website uses cookies',
                    'de' => 'Informationen darüber, wie unsere Website Cookies verwendet',
                    'sk' => 'Informácie o tom, ako naša webová stránka používa súbory cookies',
                ],
                'content' => $content,
                'published' => true,
                'published_by' => User::first()?->id,
            ],
        );
    }

    private function createFeatureCategoryPages(): void
    {
        $categoryId = $this->categoryId('funkce');
        $publisher = User::first();

        foreach ($this->featureCategoryPages() as $index => $page) {
            Page::updateOrCreate(
                ['slug->cs' => $page['slug']['cs']],
                $page + [
                    'category_id' => $categoryId,
                    'sort_order' => $index + 1,
                    'published' => true,
                    'published_by' => $publisher?->id,
                ],
            );
        }
    }

    /**
     * The homepage shows these pages as its feature cards.
     *
     * @return list<array{name: array<string, string>, slug: array<string, string>, description: array<string, string>, content: array<string, string>}>
     */
    private function featureCategoryPages(): array
    {
        return [
            [
                'name' => [
                    'cs' => 'Fakturace pro firmy',
                    'en' => 'Invoicing for businesses',
                    'de' => 'Rechnungsstellung für Unternehmen',
                    'sk' => 'Fakturácia pre firmy',
                ],
                'slug' => [
                    'cs' => 'fakturace-pro-firmy',
                    'en' => 'invoicing-for-businesses',
                    'de' => 'rechnungsstellung-fur-unternehmen',
                    'sk' => 'fakturacia-pre-firmy',
                ],
                'description' => [
                    'cs' => 'Profesionální fakturace speciálně navržená pro potřeby firem a podnikatelů',
                    'en' => 'Professional invoicing specially designed for the needs of businesses and entrepreneurs',
                    'de' => 'Professionelle Rechnungsstellung, speziell für die Bedürfnisse von Unternehmen und Unternehmern entwickelt',
                    'sk' => 'Profesionálna fakturácia špeciálne navrhnutá pre potreby firiem a podnikateľov',
                ],
                'content' => [
                    'cs' => '
                        <h2>Fakturace šitá na míru vašemu podnikání</h2>
                        <p>Naše řešení je ideální pro malé i střední firmy, které potřebují jednoduchou a spolehlivou fakturaci.</p>
                        <h3>Výhody pro firmy:</h3>
                        <ul>
                            <li>Adresář kontaktů pro rychlé vyplnění faktur</li>
                            <li>Možnost vytváření šablon produktů</li>
                            <li>Pokročilé nástroje pro správu faktur</li>
                            <li>Označování faktur jako zaplacené</li>
                            <li>Historie všech vystavených faktur</li>
                        </ul>
                        <p>Registrujte se zdarma a vyzkoušejte všechny funkce.</p>
                    ',
                    'en' => '
                        <h2>Invoicing tailored to your business</h2>
                        <p>Our solution is ideal for small and medium-sized businesses that need simple and reliable invoicing.</p>
                        <h3>Benefits for businesses:</h3>
                        <ul>
                            <li>Contact address book for filling in invoices quickly</li>
                            <li>Ability to create product templates</li>
                            <li>Advanced tools for invoice management</li>
                            <li>Marking invoices as paid</li>
                            <li>History of all issued invoices</li>
                        </ul>
                        <p>Register for free and try all the features.</p>
                    ',
                    'de' => '
                        <h2>Rechnungsstellung, die auf Ihr Unternehmen zugeschnitten ist</h2>
                        <p>Unsere Lösung ist ideal für kleine und mittelständische Unternehmen, die eine einfache und zuverlässige Rechnungsstellung benötigen.</p>
                        <h3>Vorteile für Unternehmen:</h3>
                        <ul>
                            <li>Kontaktverzeichnis zum schnellen Ausfüllen von Rechnungen</li>
                            <li>Möglichkeit zur Erstellung von Produktvorlagen</li>
                            <li>Erweiterte Tools für das Rechnungsmanagement</li>
                            <li>Markierung von Rechnungen als bezahlt</li>
                            <li>Historie aller ausgestellten Rechnungen</li>
                        </ul>
                        <p>Registrieren Sie sich kostenlos und testen Sie alle Funktionen.</p>
                    ',
                    'sk' => '
                        <h2>Fakturácia prispôsobená vášmu podnikaniu</h2>
                        <p>Naše riešenie je ideálne pre malé a stredné firmy, ktoré potrebujú jednoduchú a spoľahlivú fakturáciu.</p>
                        <h3>Výhody pre firmy:</h3>
                        <ul>
                            <li>Adresár kontaktov na rýchle vyplnenie faktúr</li>
                            <li>Možnosť vytvárania šablón produktov</li>
                            <li>Pokročilé nástroje na správu faktúr</li>
                            <li>Označovanie faktúr ako zaplatené</li>
                            <li>História všetkých vystavených faktúr</li>
                        </ul>
                        <p>Registrujte sa zadarmo a vyskúšajte všetky funkcie.</p>
                    ',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Rychlé faktury pro hosty',
                    'en' => 'Quick invoices for guests',
                    'de' => 'Schnelle Rechnungen für Gäste',
                    'sk' => 'Rýchle faktúry pre hostí',
                ],
                'slug' => [
                    'cs' => 'rychle-faktury-pro-hosty',
                    'en' => 'quick-invoices-for-guests',
                    'de' => 'schnelle-rechnungen-fur-gaste',
                    'sk' => 'rychle-faktury-pre-hosti',
                ],
                'description' => [
                    'cs' => 'Vytvořte si fakturu za pár minut, bez nutnosti registrace',
                    'en' => 'Create an invoice in a few minutes, no registration required',
                    'de' => 'Erstellen Sie eine Rechnung in wenigen Minuten, ohne Registrierung',
                    'sk' => 'Vytvorte si faktúru za pár minút, bez nutnosti registrácie',
                ],
                'content' => [
                    'cs' => '
                        <h2>Fakturu bez registrace</h2>
                        <p>Potřebujete rychle vystavit fakturu a nechcete se registrovat? Naše řešení pro hosty je přesně pro vás!</p>
                        <h3>Jak to funguje:</h3>
                        <ol>
                            <li>Vyplníte základní údaje o faktuře</li>
                            <li>Zadáte položky a ceny</li>
                            <li>Systém vygeneruje PDF fakturu</li>
                            <li>Dostanete bezpečný odkaz pro stažení</li>
                        </ol>
                        <p>Máte potřebu fakturovat pravidelně? <a href="/cs/register">Registrujte se zdarma</a> a získejte přístup ke všem funkcím.</p>
                    ',
                    'en' => '
                        <h2>Invoice without registration</h2>
                        <p>Need to quickly issue an invoice without registering? Our guest solution is perfect for you!</p>
                        <h3>How it works:</h3>
                        <ol>
                            <li>Fill in basic invoice details</li>
                            <li>Enter items and prices</li>
                            <li>The system generates a PDF invoice</li>
                            <li>You receive a secure download link</li>
                        </ol>
                        <p>Need to invoice regularly? <a href="/en/register">Register for free</a> and gain access to all features.</p>
                    ',
                    'de' => '
                        <h2>Rechnung ohne Registrierung</h2>
                        <p>Müssen Sie schnell eine Rechnung ausstellen, ohne sich zu registrieren? Unsere Gastlösung ist perfekt für Sie!</p>
                        <h3>So funktioniert es:</h3>
                        <ol>
                            <li>Geben Sie die grundlegenden Rechnungsdetails ein</li>
                            <li>Fügen Sie Artikel und Preise hinzu</li>
                            <li>Das System erstellt eine PDF-Rechnung</li>
                            <li>Sie erhalten einen sicheren Download-Link</li>
                        </ol>
                        <p>Müssen Sie regelmäßig Rechnungen ausstellen? <a href="/de/register">Registrieren Sie sich kostenlos</a> und erhalten Sie Zugriff auf alle Funktionen.</p>
                    ',
                    'sk' => '
                        <h2>Faktúra bez registrácie</h2>
                        <p>Potrebujete rýchlo vystaviť faktúru bez registrácie? Naše riešenie pre hostí je presne pre vás!</p>
                        <h3>Ako to funguje:</h3>
                        <ol>
                            <li>Vyplníte základné údaje o faktúre</li>
                            <li>Zadáte položky a ceny</li>
                            <li>Systém vygeneruje PDF faktúru</li>
                            <li>Dostanete bezpečný odkaz na stiahnutie</li>
                        </ol>
                        <p>Potrebujete fakturovať pravidelne? <a href="/sk/register">Zaregistrujte sa zdarma</a> a získajte prístup ku všetkým funkciám.</p>
                    ',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Kontakty',
                    'en' => 'Contacts',
                    'de' => 'Kontakte',
                    'sk' => 'Kontakty',
                ],
                'slug' => [
                    'cs' => 'kontakty',
                    'en' => 'contacts',
                    'de' => 'kontakte',
                    'sk' => 'kontakty',
                ],
                'description' => [
                    'cs' => 'Jeden adresář pro odběratele i dodavatele.',
                    'en' => 'One address book for customers and suppliers.',
                    'de' => 'Ein Adressbuch für Kunden und Lieferanten.',
                    'sk' => 'Jeden adresár pre odberateľov aj dodávateľov.',
                ],
                'content' => [
                    'cs' => '<p>Systém umožňuje vytvářet osobní adresář kontaktů, firem i lidí, se kterými obchodujete. Kontakt snadno načtete do faktury a z vystavených faktur se automaticky vytvářejí dlouhodobé statistiky.</p>',
                    'en' => '<p>The system allows creating a personal address book of contacts, the companies and people you do business with. You load a contact into an invoice in one step, and your issued invoices automatically build long-term statistics.</p>',
                    'de' => '<p>Das System ermöglicht ein persönliches Kontaktverzeichnis der Firmen und Personen, mit denen Sie Geschäfte machen. Einen Kontakt laden Sie mit einem Klick in die Rechnung, und aus Ihren ausgestellten Rechnungen entstehen automatisch langfristige Statistiken.</p>',
                    'sk' => '<p>Systém umožňuje vytvárať osobný adresár kontaktov, firiem aj ľudí, s ktorými obchodujete. Kontakt jednoducho načítate do faktúry a z vystavených faktúr sa automaticky vytvárajú dlhodobé štatistiky.</p>',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Moje firmy',
                    'en' => 'My businesses',
                    'de' => 'Meine Firmen',
                    'sk' => 'Moje firmy',
                ],
                'slug' => [
                    'cs' => 'moje-firmy',
                    'en' => 'my-businesses',
                    'de' => 'meine-firmen',
                    'sk' => 'moje-firmy',
                ],
                'description' => [
                    'cs' => 'Fakturujte za více vlastních firem z jednoho účtu.',
                    'en' => 'Invoice for several businesses of your own from one account.',
                    'de' => 'Stellen Sie Rechnungen für mehrere eigene Firmen aus einem Konto aus.',
                    'sk' => 'Fakturujte za viac vlastných firiem z jedného účtu.',
                ],
                'content' => [
                    'cs' => '<p>Pod jedním účtem můžete vést více vlastních firem. Každá má své údaje, logo a bankovní účty, které se tisknou na fakturu a použijí se pro QR platbu. Při vystavení faktury se předvyplní výchozí firma.</p>',
                    'en' => '<p>One account can hold several businesses of your own. Each has its own details, logo and bank accounts, which are printed on the invoice and used for the QR payment. A new invoice is prefilled with your default business.</p>',
                    'de' => '<p>Unter einem Konto können Sie mehrere eigene Firmen führen. Jede hat ihre eigenen Angaben, ihr Logo und ihre Bankkonten, die auf der Rechnung gedruckt und für die QR-Zahlung verwendet werden. Eine neue Rechnung wird mit Ihrer Standardfirma vorausgefüllt.</p>',
                    'sk' => '<p>Pod jedným účtom môžete viesť viac vlastných firiem. Každá má svoje údaje, logo a bankové účty, ktoré sa tlačia na faktúru a použijú sa pre QR platbu. Pri vystavení faktúry sa predvyplní predvolená firma.</p>',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Soukromé statistiky',
                    'en' => 'Private statistics',
                    'de' => 'Private Statistiken',
                    'sk' => 'Súkromné štatistiky',
                ],
                'slug' => [
                    'cs' => 'soukrome-statistiky',
                    'en' => 'private-statistics',
                    'de' => 'private-statistiken',
                    'sk' => 'sukromne-statistiky',
                ],
                'description' => [
                    'cs' => 'Automatické vytváření statistik na Vašem dashboardu.',
                    'en' => 'Automatic generation of statistics on your dashboard.',
                    'de' => 'Automatische Erstellung von Statistiken auf Ihrem Dashboard.',
                    'sk' => 'Automatické vytváranie štatistík na vašom dashboarde.',
                ],
                'content' => [
                    'cs' => '<p>Každý přihlášený uživatel má svůj osobní Dashboard (nástěnku) s rychlým přehledem a podrobnými statistikami.</p>',
                    'en' => '<p>Each logged-in user has their personal Dashboard with a quick overview and detailed statistics.</p>',
                    'de' => '<p>Jeder angemeldete Benutzer hat sein persönliches Dashboard mit einer schnellen Übersicht und detaillierten Statistiken.</p>',
                    'sk' => '<p>Každý prihlásený používateľ má svoj osobný Dashboard s rýchlym prehľadom a podrobnými štatistikami.</p>',
                ],
            ],
            [
                'name' => [
                    'cs' => 'Soukromé produkty',
                    'en' => 'Private products',
                    'de' => 'Private Produkte',
                    'sk' => 'Súkromné produkty',
                ],
                'slug' => [
                    'cs' => 'soukrome-produkty',
                    'en' => 'private-products',
                    'de' => 'private-produkte',
                    'sk' => 'sukromne-produkty',
                ],
                'description' => [
                    'cs' => 'Každý uživatel má možnost si vytvářet své produkty.',
                    'en' => 'Each user has the ability to create their own products.',
                    'de' => 'Jeder Benutzer hat die Möglichkeit, seine eigenen Produkte zu erstellen.',
                    'sk' => 'Každý používateľ má možnosť vytvárať svoje vlastné produkty.',
                ],
                'content' => [
                    'cs' => '<p>Můžete si vytvářet své produkty na základě Vašich nejčastějších položek na faktuře. Následně si je můžete do faktury pouze přidat v podobě položky faktury. Vše se pak na faktuře automaticky přepočítá, tak aby s tím nebyla žádná starost.</p>',
                    'en' => '<p>You can create your products based on your most frequent invoice items. Subsequently, you can simply add them to the invoice as an invoice item. Everything will then be automatically recalculated on the invoice, so there is no hassle.</p>',
                    'de' => '<p>Sie können Ihre Produkte basierend auf Ihren häufigsten Rechnungspositionen erstellen. Anschließend können Sie diese einfach als Rechnungsposition zur Rechnung hinzufügen. Alles wird dann automatisch auf der Rechnung neu berechnet, sodass es keine Mühe bereitet.</p>',
                    'sk' => '<p>Môžete si vytvárať svoje produkty na základe vašich najčastejších položiek na faktúre. Následne si ich môžete do faktúry jednoducho pridať ako položku faktúry. Všetko sa potom na faktúre automaticky prepočíta, aby ste s tým nemali žiadne starosti.</p>',
                ],
            ],
        ];
    }

    private function getFeaturesContent(string $locale): string
    {
        $lang['cs'] = '
            <div class="bg-white dark:bg-gray-800 py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-base font-semibold leading-7 text-indigo-600">Fakturace snadno</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Vše, co potřebujete pro profesionální fakturaci
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-400">
                            Náš fakturační systém obsahuje všechny nástroje potřebné pro efektivní správu faktur, klientů a plateb.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-3h.75a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.42 2.42 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.5m0-6.375h-4.5m0 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125H9" />
                                        </svg>
                                    </div>
                                    Rychlé vytváření faktur
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Vytvořte profesionální faktury během několika minut. Automatické vyplňování údajů klientů a produktů.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </div>
                                    Správa klientů
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Uchovávejte všechny informace o vašich klientech na jednom místě. Historie faktur a plateb.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    Pokročilé statistiky
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Sledujte výkonnost vašeho podnikání. Detailní reporty a analýzy tržeb.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                        </svg>
                                    </div>
                                    Bezpečnost dat
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Vaše data jsou v bezpečí díky pokročilému šifrování a pravidelným zálohám.
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Proč si vybrat náš systém?
                        </h2>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-16 lg:grid-cols-3">
                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Úspora času</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Automatizace rutinních úkolů vám ušetří hodiny práce týdně.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H4.5m2.25 0v3m0 0v.75A.75.75 0 0 1 6 9h-.75m0 0h-.375c-.621 0-1.125-.504-1.125-1.125v-.75m0 0V4.5m0 0h.75c.621 0 1.125.504 1.125 1.125v.375M6 9v.75c0 .414.336.75.75.75h.75V9h-.75A.75.75 0 0 1 6 8.25V9ZM21.75 9v.75c0 .414-.336.75-.75.75h-.75V9h.75c.414 0 .75.336.75.75ZM18 9v.75A.75.75 0 0 1 17.25 10.5h-.75V9h.75c.414 0 .75.336.75.75ZM18 4.5h-.75c-.621 0-1.125.504-1.125 1.125v.375m1.125-.375V3.75c0-.621.504-1.125 1.125-1.125h.375m-1.5 1.5h.375c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125H15.75c-.621 0-1.125-.504-1.125-1.125V4.5c0-.621.504-1.125 1.125-1.125h1.5Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Profesionální vzhled</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Vaše faktury budou vypadat profesionálně a zaujmou klienty.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Snadné používání</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Intuitivní rozhraní, které zvládne každý bez dlouhého učení.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

        $lang['en'] = '
            <div class="bg-white dark:bg-gray-800 py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-base font-semibold leading-7 text-indigo-600">Easy invoicing</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Everything you need for professional invoicing
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-400">
                            Our invoicing system includes all the tools needed for efficient management of invoices, businesses, contacts, and payments.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-3h.75a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.42 2.42 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.5m0-6.375h-4.5m0 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125H9" />
                                        </svg>
                                    </div>
                                    Quick invoice creation
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Create professional invoices in minutes. Automatic filling of business, contact and product details.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </div>
                                    Contact management
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Keep all your contact information in one place. Invoice and payment history.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    Advanced statistics
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Track your business performance. Detailed revenue reports and analyses.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                        </svg>
                                    </div>
                                    Data security
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Your data is safe thanks to advanced encryption and regular backups.
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Why choose our system?
                        </h2>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-16 lg:grid-cols-3">
                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Time savings</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Automation of routine tasks saves you hours of work weekly.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H4.5m2.25 0v3m0 0v.75A.75.75 0 0 1 6 9h-.75m0 0h-.375c-.621 0-1.125-.504-1.125-1.125v-.75m0 0V4.5m0 0h.75c.621 0 1.125.504 1.125 1.125v.375M6 9v.75c0 .414.336.75.75.75h.75V9h-.75A.75.75 0 0 1 6 8.25V9ZM21.75 9v.75c0 .414-.336.75-.75.75h-.75V9h.75c.414 0 .75.336.75.75ZM18 9v.75A.75.75 0 0 1 17.25 10.5h-.75V9h.75c.414 0 .75.336.75.75ZM18 4.5h-.75c-.621 0-1.125.504-1.125 1.125v.375m1.125-.375V3.75c0-.621.504-1.125 1.125-1.125h.375m-1.5 1.5h.375c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125H15.75c-.621 0-1.125-.504-1.125-1.125V4.5c0-.621.504-1.125 1.125-1.125h1.5Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Professional appearance</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Your invoices will look professional and impress clients.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Ease of use</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Intuitive interface that anyone can handle without long learning.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

        $lang['de'] = '
            <div class="bg-white dark:bg-gray-800 py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-base font-semibold leading-7 text-indigo-600">Einfache Rechnungsstellung</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Alles, was Sie für professionelle Rechnungsstellung benötigen
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-400">
                            Unser Rechnungsstellungssystem enthält alle Werkzeuge, die für eine effiziente Verwaltung von Rechnungen, Kunden und Zahlungen erforderlich sind.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-3h.75a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.42 2.42 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.5m0-6.375h-4.5m0 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125H9" />
                                        </svg>
                                    </div>
                                    Schnelle Rechnungserstellung
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Erstellen Sie professionelle Rechnungen in wenigen Minuten. Automatische Ausfüllung von Kunden- und Produktdaten.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </div>
                                    Kundenverwaltung
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Bewahren Sie alle Informationen über Ihre Kunden an einem Ort auf. Rechnungs- und Zahlungshistorie.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    Erweiterte Statistiken
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Verfolgen Sie die Leistung Ihres Unternehmens. Detaillierte Umsatzberichte und Analysen.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                        </svg>
                                    </div>
                                    Datensicherheit
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Ihre Daten sind dank fortschrittlicher Verschlüsselung und regelmäßiger Backups sicher.
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Warum unser System wählen?
                        </h2>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-16 lg:grid-cols-3">
                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Zeitersparnis</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Automatisierung von Routineaufgaben spart Ihnen wöchentlich Stunden Arbeit.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H4.5m2.25 0v3m0 0v.75A.75.75 0 0 1 6 9h-.75m0 0h-.375c-.621 0-1.125-.504-1.125-1.125v-.75m0 0V4.5m0 0h.75c.621 0 1.125.504 1.125 1.125v.375M6 9v.75c0 .414.336.75.75.75h.75V9h-.75A.75.75 0 0 1 6 8.25V9ZM21.75 9v.75c0 .414-.336.75-.75.75h-.75V9h.75c.414 0 .75.336.75.75ZM18 9v.75A.75.75 0 0 1 17.25 10.5h-.75V9h.75c.414 0 .75.336.75.75ZM18 4.5h-.75c-.621 0-1.125.504-1.125 1.125v.375m1.125-.375V3.75c0-.621.504-1.125 1.125-1.125h.375m-1.5 1.5h.375c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125H15.75c-.621 0-1.125-.504-1.125-1.125V4.5c0-.621.504-1.125 1.125-1.125h1.5Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Professionelles Erscheinungsbild</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Ihre Rechnungen sehen professionell aus und beeindrucken Kunden.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Jednoduché používanie</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Intuitívne rozhranie, ktoré zvládne každý bez dlhého učenia.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

        $lang['sk'] = '
            <div class="bg-white dark:bg-gray-800 py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-base font-semibold leading-7 text-indigo-600">Jednoduché fakturovanie</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Všetko, čo potrebujete pre profesionálne fakturovanie
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-400">
                            Náš fakturačný systém obsahuje všetky nástroje potrebné na efektívnu správu faktúr, klientov a platieb.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-3h.75a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.42 2.42 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.5m0-6.375h-4.5m0 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125H9" />
                                        </svg>
                                    </div>
                                    Rýchle vytváranie faktúr
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Vytvorte profesionálne faktúry behom niekoľkých minút. Automatické vyplňovanie údajov klientov a produktov.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </div>
                                    Správa klientov
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Uchovávajte všetky informácie o vašich klientoch na jednom mieste. História faktúr a platieb.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    Pokročilé štatistiky
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Sledujte výkonnosť vášho podnikania. Detailné reporty a analýzy tržieb.
                                </dd>
                            </div>

                            <div class="relative pl-16">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                        </svg>
                                    </div>
                                    Bezpečnosť dát
                                </dt>
                                <dd class="mt-2 text-base leading-7 text-gray-600">
                                    Vaše dáta sú v bezpečí vďaka pokročilému šifrovaniu a pravidelným zálohám.
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Prečo si vybrať náš systém?
                        </h2>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-16 lg:grid-cols-3">
                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Úspora času</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Automatizácia rutinných úloh vám ušetrí hodiny práce týždenne.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H4.5m2.25 0v3m0 0v.75A.75.75 0 0 1 6 9h-.75m0 0h-.375c-.621 0-1.125-.504-1.125-1.125v-.75m0 0V4.5m0 0h.75c.621 0 1.125.504 1.125 1.125v.375M6 9v.75c0 .414.336.75.75.75h.75V9h-.75A.75.75 0 0 1 6 8.25V9ZM21.75 9v.75c0 .414-.336.75-.75.75h-.75V9h.75c.414 0 .75.336.75.75ZM18 9v.75A.75.75 0 0 1 17.25 10.5h-.75V9h.75c.414 0 .75.336.75.75ZM18 4.5h-.75c-.621 0-1.125.504-1.125 1.125v.375m1.125-.375V3.75c0-.621.504-1.125 1.125-1.125h.375m-1.5 1.5h.375c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125H15.75c-.621 0-1.125-.504-1.125-1.125V4.5c0-.621.504-1.125 1.125-1.125h1.5Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Profesionálny vzhľad</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Vaše faktúry budú vyzerať profesionálne a zaujmú klientov.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-sm bg-indigo-600">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-6 text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Jednoduché používanie</h3>
                                <p class="mt-2 text-base leading-7 text-gray-600">
                                    Intuitívne rozhranie, ktoré zvládne každý bez dlhého učenia.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getPricingContent(string $locale): string
    {
        $lang['cs'] = '
            <div class="sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-4xl text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Ceník</span></h2>
                        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-5xl">
                            Transparentní ceny pro každého
                        </p>
                    </div>
                    <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">
                        Vyberte si balíček, který nejlépe vyhovuje potřebám vašeho podnikání. Všechny ceny jsou uvedeny bez DPH.
                    </p>
                    
                    <div class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
                        <!-- Basic Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Základní</h3>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Ideální pro začínající podnikatele a malé firmy</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">Zdarma</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Až 5 faktur měsíčně
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Základní šablony faktur
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Správa klientů
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        E-mailová podpora
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Začít zdarma
                            </a>
                        </div>

                        <!-- Pro Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-indigo-600">Profesionální</h3>
                                    <p class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs font-semibold leading-5 text-indigo-600">Nejpopulárnější</p>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Pro rostoucí firmy s vyššími nároky</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">490 Kč</span>
                                    <span class="text-sm font-semibold leading-6 text-gray-600">/měsíc</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Neomezené faktury
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Pokročilé šablony
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Automatické připomínky
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Podrobné statistiky
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Prioritní podpora
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Vybrat plán
                            </a>
                        </div>
                    </div>
                </div>
            </div>';

        $lang['en'] = '
            <div class="sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-4xl text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Pricing</span></h2>
                        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-5xl">
                            Transparent prices for everyone
                        </p>
                    </div>
                    <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">
                        Choose a package that best suits your business needs. All prices are listed without VAT.
                    </p>
                    
                    <div class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
                        <!-- Basic Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Basic</h3>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Ideal for starting entrepreneurs and small businesses</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">Free</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Up to 5 invoices per month
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Basic invoice templates
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Contact management
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Email support
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Start for free
                            </a>
                        </div>

                        <!-- Pro Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-indigo-600">Professional</h3>
                                    <p class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs font-semibold leading-5 text-indigo-600">Most popular</p>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">For growing businesses with higher demands</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">490 CZK</span>
                                    <span class="text-sm font-semibold leading-6 text-gray-600">/month</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Unlimited invoices
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Advanced templates
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Automatic reminders
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Detailed statistics
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Priority support
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Choose plan
                            </a>
                        </div>
                    </div>
                </div>
            </div>';

        $lang['de'] = '
            <div class="sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-4xl text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Preisliste</span></h2>
                        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-5xl">
                            Transparente Preise für alle
                        </p>
                    </div>
                    <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">
                        Wählen Sie ein Paket, das am besten zu den Bedürfnissen Ihres Unternehmens passt. Alle Preise sind ohne Mehrwertsteuer angegeben.
                    </p>
                    
                    <div class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
                        <!-- Basic Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Basis</h3>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Ideal für Start-ups und kleine Unternehmen</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">Kostenlos</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Bis zu 5 Rechnungen pro Monat
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Basis-Rechnungsvorlagen
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Kundenverwaltung
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        E-Mail-Support
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Kostenlos starten
                            </a>
                        </div>

                        <!-- Pro Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-indigo-600">Professionell</h3>
                                    <p class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs font-semibold leading-5 text-indigo-600">Am beliebtesten</p>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Für wachsende Unternehmen mit höheren Anforderungen</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">490 CZK</span>
                                    <span class="text-sm font-semibold leading-6 text-gray-600">/Monat</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Unbegrenzte Rechnungen
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Erweiterte Vorlagen
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Automatische Erinnerungen
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Detaillierte Statistiken
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Priorisierter Support
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Plan auswählen
                            </a>
                        </div>
                    </div>
                </div>
            </div>';

        $lang['sk'] = '
            <div class="sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-4xl text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Cenník</span></h2>
                        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-5xl">
                            Transparentné ceny pre každého
                        </p>
                    </div>
                    <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">
                        Vyberte si balíček, ktorý najlepšie vyhovuje potrebám vášho podnikania. Všetky ceny sú uvedené bez DPH.
                    </p>
                    
                    <div class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
                        <!-- Basic Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-gray-900 dark:text-gray-400">Základný</h3>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Ideálny pre začínajúcich podnikateľov a malé firmy</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">Zdarma</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Až 5 faktúr mesačne
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Základné šablóny faktúr
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Správa klientov
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        E-mailová podpora
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Začať zdarma
                            </a>
                        </div>

                        <!-- Pro Plan -->
                        <div class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800 p-8 border border-gray-200 dark:border-gray-700 xl:p-10">
                            <div>
                                <div class="flex items-center justify-between gap-x-4">
                                    <h3 class="text-lg font-semibold leading-8 text-indigo-600">Profesionálny</h3>
                                    <p class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs font-semibold leading-5 text-indigo-600">Najpopulárnejší</p>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-gray-600">Pre rastúce firmy s vyššími nárokmi</p>
                                <p class="mt-6 flex items-baseline gap-x-1">
                                    <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-400">490 Kč</span>
                                    <span class="text-sm font-semibold leading-6 text-gray-600">/mesiac</span>
                                </p>
                                <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Neobmedzené faktúry
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Pokročilé šablóny
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Automatické pripomienky
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Podrobné štatistiky
                                    </li>
                                    <li class="flex gap-x-3">
                                        <svg class="h-6 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                        Prioritná podpora
                                    </li>
                                </ul>
                            </div>
                            <a href="#" class="mt-8 block rounded-sm bg-indigo-600 px-3 py-2 text-center text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600">
                                Vybrať plán
                            </a>
                        </div>
                    </div>
                </div>
            </div>';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getTemplatesContent(string $locale): string
    {
        $lang['cs'] = '
            <div class="py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Šablony faktur</span></h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Profesionální šablony pro každé odvětví
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Vyberte si z našich předpřipravených šablon nebo si vytvořte vlastní. Všechny šablony jsou plně přizpůsobitelné.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-blue-50 to-indigo-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">FAKTURA</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-indigo-200 rounded"></div>
                                        <div class="h-2 bg-indigo-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Klasická šablona
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Minimalistický design pro všechna odvětví</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-green-50 to-emerald-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">INVOICE</h3>
                                            <span class="text-sm text-gray-500">#INV-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-emerald-200 rounded"></div>
                                        <div class="h-2 bg-emerald-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Moderní šablona
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Stylový design pro kreativní obory</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-purple-50 to-violet-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">RAČUN</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-violet-200 rounded"></div>
                                        <div class="h-2 bg-violet-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Vícejazyčná šablona
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Podpora více jazyků pro mezinárodní firmy</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Přizpůsobte si šablony podle svých potřeb
                        </h2>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Každou šablonu můžete plně přizpůsobit svému brandingu a požadavkům.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 0 3 3 0 0 0-1.888-4.07A2.25 2.25 0 0 1 5.436 8.34a3 3 0 0 0 3.789-3.789A2.25 2.25 0 0 1 13.304 2.6a3 3 0 0 0 4.97-1.878.75.75 0 0 1 1.334 0 3 3 0 0 0 4.97 1.878 2.25 2.25 0 0 1 4.069 1.95 3 3 0 0 0 3.789 3.789 2.25 2.25 0 0 1 1.95 4.069 3 3 0 0 0 1.878 4.97.75.75 0 0 1 0 1.334 3 3 0 0 0-1.878 4.97 2.25 2.25 0 0 1-1.95 4.069 3 3 0 0 0-3.789 3.789 2.25 2.25 0 0 1-4.069 1.95 3 3 0 0 0-4.97 1.878.75.75 0 0 1-1.334 0 3 3 0 0 0-4.97-1.878 2.25 2.25 0 0 1-4.069-1.95 3 3 0 0 0-3.789-3.789 2.25 2.25 0 0 1-1.95-4.069 3 3 0 0 0-1.878-4.97Z" />
                                        </svg>
                                    </div>
                                    Vlastní logo a barvy
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Přidejte své logo a přizpůsobte barvy podle firemního stylu.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5" />
                                        </svg>
                                    </div>
                                    Flexibilní layouty
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Upravte rozložení polí a sekcí podle svých preferencí.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                                        </svg>
                                    </div>
                                    Vícejazyčnost
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Podporujeme češtinu, slovenštinu, němčinu a angličtinu.</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>';

        $lang['en'] = '
            <div class="py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Invoice Templates</span></h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Professional templates for every industry
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Choose from our pre-designed templates or create your own. All templates are fully customizable.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-blue-50 to-indigo-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">INVOICE</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-indigo-200 rounded"></div>
                                        <div class="h-2 bg-indigo-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Classic Template
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Minimalist design for all industries</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-green-50 to-emerald-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">INVOICE</h3>
                                            <span class="text-sm text-gray-500">#INV-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-emerald-200 rounded"></div>
                                        <div class="h-2 bg-emerald-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Modern Template
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Stylish design for creative fields</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-purple-50 to-violet-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">INVOICE</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-violet-200 rounded"></div>
                                        <div class="h-2 bg-violet-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Multilingual Template
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Support for multiple languages for international companies</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Customize templates to suit your needs
                        </h2>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Each template can be fully customized to match your branding and requirements.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 0 3 3 0 0 0-1.888-4.07A2.25 2.25 0 0 1 5.436 8.34a3 3 0 0 0 3.789-3.789A2.25 2.25 0 0 1 13.304 2.6a3 3 0 0 0 4.97-1.878.75.75 0 0 1 1.334 0 3 3 0 0 0 4.97 1.878 2.25 2.25 0 0 1 4.069 1.95 3 3 0 0 0 3.789 3.789 2.25 2.25 0 0 1 1.95 4.069 3 3 0 0 0 1.878 4.97.75.75 0 0 1 0 1.334 3 3 0 0 0-1.878 4.97 2.25 2.25 0 0 1-1.95 4.069 3 3 0 0 0-3.789 3.789 2.25 2.25 0 0 1-4.069 1.95 3 3 0 0 0-4.97 1.878.75.75 0 0 1-1.334 0 3 3 0 0 0-4.97-1.878 2.25 2.25 0 0 1-4.069-1.95 3 3 0 0 0-3.789-3.789 2.25 2.25 0 0 1-1.95-4.069 3 3 0 0 0-1.878-4.97Z" />
                                        </svg>
                                    </div>
                                    Custom logo and colors
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Add your logo and customize colors to match your corporate style.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5" />
                                        </svg>
                                    </div>
                                    Flexible layouts
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Adjust the layout of fields and sections according to your preferences.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                                        </svg>
                                    </div>
                                    Multilingual support
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">We support Czech, Slovak, German, and English.</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>';

        $lang['de'] = '
            <div class="py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Rechnungsvorlagen</span></h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Professionelle Vorlagen für jede Branche
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Wählen Sie aus unseren vorgefertigten Vorlagen oder erstellen Sie Ihre eigene. Alle Vorlagen sind vollständig anpassbar.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-blue-50 to-indigo-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">RECHNUNG</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-indigo-200 rounded"></div>
                                        <div class="h-2 bg-indigo-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Klassische Vorlage
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Minimalistisches Design für alle Branchen</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-green-50 to-emerald-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">RECHNUNG</h3>
                                            <span class="text-sm text-gray-500">#INV-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-emerald-200 rounded"></div>
                                        <div class="h-2 bg-emerald-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Moderne Vorlage
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Stilvolles Design für kreative Branchen</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-purple-50 to-violet-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">RECHNUNG</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-violet-200 rounded"></div>
                                        <div class="h-2 bg-violet-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Mehrsprachige Vorlage
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Unterstützung mehrerer Sprachen für internationale Unternehmen</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Passen Sie die Vorlagen an Ihre Bedürfnisse an
                        </h2>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Jede Vorlage kann vollständig an Ihr Branding und Ihre Anforderungen angepasst werden.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 0 3 3 0 0 0-1.888-4.07A2.25 2.25 0 0 1 5.436 8.34a3 3 0 0 0 3.789-3.789A2.25 2.25 0 0 1 13.304 2.6a3 3 0 0 0 4.97-1.878.75.75 0 0 1 1.334 0 3 3 0 0 0 4.97 1.878 2.25 2.25 0 0 1 4.069 1.95 3 3 0 0 0 3.789 3.789 2.25 2.25 0 0 1 1.95 4.069 3 3 0 0 0 1.878 4.97.75.75 0 0 1 0 1.334 3 3 0 0 0-1.878 4.97 2.25 2.25 0 0 1-1.95 4.069 3 3 0 0 0-3.789 3.789 2.25 2.25 0 0 1-4.069 1.95 3 3 0 0 0-4.97 1.878.75.75 0 0 1-1.334 0 3 3 0 0 0-4.97-1.878 2.25 2.25 0 0 1-4.069-1.95 3 3 0 0 0-3.789-3.789 2.25 2.25 0 0 1-1.95-4.069 3 3 0 0 0-1.878-4.97Z" />
                                        </svg>
                                    </div>
                                    Eigenes Logo und Farben
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Fügen Sie Ihr Logo hinzu und passen Sie die Farben an Ihren Unternehmensstil an.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5" />
                                        </svg>
                                    </div>
                                    Flexible Layouts
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Passen Sie die Anordnung von Feldern und Abschnitten nach Ihren Wünschen an.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                                        </svg>
                                    </div>
                                    Mehrsprachigkeit
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Wir unterstützen Tschechisch, Slowakisch, Deutsch und Englisch.</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>';

        $lang['sk'] = '
            <div class="py-24 sm:py-5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center"><span class="border-b-3 border-[#490BF4]">Šablóny faktúr</span></h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Profesionálne šablóny pre každé odvetvie
                        </p>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Vyberte si z našich predpripravených šablón alebo si vytvorte vlastnú. Všetky šablóny sú plne prispôsobiteľné.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-blue-50 to-indigo-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">FAKTÚRA</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-indigo-200 rounded"></div>
                                        <div class="h-2 bg-indigo-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Klasická šablóna
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Minimalistický dizajn pre všetky odvetvia</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-green-50 to-emerald-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">FAKTÚRA</h3>
                                            <span class="text-sm text-gray-500">#INV-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-emerald-200 rounded"></div>
                                        <div class="h-2 bg-emerald-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Moderná šablóna
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Štýlový dizajn pre kreatívne odvetvia</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <div class="aspect-h-1 aspect-w-1 w-full overflow-hidden rounded-sm bg-gray-200 lg:aspect-none group-hover:opacity-75 lg:h-80">
                                <div class="h-full w-full bg-gradient-to-br from-purple-50 to-violet-100 p-6 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-400">FAKTÚRA</h3>
                                            <span class="text-sm text-gray-500">#2025-001</span>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                            <div class="h-2 bg-gray-300 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-violet-200 rounded"></div>
                                        <div class="h-2 bg-violet-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700">
                                        <a href="#">
                                            <span aria-hidden="true" class="absolute inset-0"></span>
                                            Viacjazyčná šablóna
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Podpora viacerých jazykov pre medzinárodné firmy</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 py-24 sm:py-5 rounded-md">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-400 sm:text-4xl">
                            Prispôsobte si šablóny podľa svojich potrieb
                        </h2>
                        <p class="mt-6 text-lg leading-8 text-gray-600">
                            Každú šablónu môžete plne prispôsobiť svojmu brandingu a požiadavkám.
                        </p>
                    </div>
                    
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 0 3 3 0 0 0-1.888-4.07A2.25 2.25 0 0 1 5.436 8.34a3 3 0 0 0 3.789-3.789A2.25 2.25 0 0 1 13.304 2.6a3 3 0 0 0 4.97-1.878.75.75 0 0 1 1.334 0 3 3 0 0 0 4.97 1.878 2.25 2.25 0 0 1 4.069 1.95 3 3 0 0 0 3.789 3.789 2.25 2.25 0 0 1 1.95 4.069 3 3 0 0 0 1.878 4.97.75.75 0 0 1 0 1.334 3 3 0 0 0-1.878 4.97 2.25 2.25 0 0 1-1.95 4.069 3 3 0 0 0-3.789 3.789 2.25 2.25 0 0 1-4.069 1.95 3 3 0 0 0-4.97 1.878.75.75 0 0 1-1.334 0 3 3 0 0 0-4.97-1.878 2.25 2.25 0 0 1-4.069-1.95 3 3 0 0 0-3.789-3.789 2.25 2.25 0 0 1-1.95-4.069 3 3 0 0 0-1.878-4.97Z" />
                                        </svg>
                                    </div>
                                    Vlastné logo a farby
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Pridajte svoje logo a prispôsobte farby podľa firemného štýlu.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5m0 6h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m-3.75 0H7.5" />
                                        </svg>
                                    </div>
                                    Flexibilné rozloženia
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Upravte rozloženie polí a sekcií podľa svojich preferencií.</p>
                                </dd>
                            </div>

                            <div class="flex flex-col">
                                <dt class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-400">
                                    <div class="mb-6 flex h-10 w-10 items-center justify-center rounded-sm bg-indigo-600">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                                        </svg>
                                    </div>
                                    Viacjazyčnosť
                                </dt>
                                <dd class="mt-1 flex flex-auto flex-col text-base leading-7 text-gray-600">
                                    <p class="flex-auto">Podporujeme češtinu, slovenčinu, nemčinu a angličtinu.</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getApiContent(string $locale): string
    {
        $lang['cs'] = 'API obsah v češtině';

        $lang['en'] = 'API content in English';
        $lang['de'] = 'API-Inhalt auf Deutsch';
        $lang['sk'] = 'API obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getHelpContent(string $locale): string
    {
        $lang['cs'] = 'Nápověda obsah v češtině';

        $lang['en'] = 'Help content in English';
        $lang['de'] = 'Hilfe-Inhalt auf Deutsch';
        $lang['sk'] = 'Pomoc obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getContactContent(string $locale): string
    {
        $lang['cs'] = 'Kontakt obsah v češtině';

        $lang['en'] = 'Contact content in English';
        $lang['de'] = 'Kontakt-Inhalt auf Deutsch';
        $lang['sk'] = 'Kontakt obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getDocumentationContent(string $locale): string
    {
        $lang['cs'] = 'Dokumentace obsah v češtině';

        $lang['en'] = 'Documentation content in English';
        $lang['de'] = 'Dokumentationsinhalt auf Deutsch';
        $lang['sk'] = 'Dokumentácia obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getServiceStatusContent(string $locale): string
    {
        $lang['cs'] = 'Stav služby obsah v češtině';

        $lang['en'] = 'Service status content in English';
        $lang['de'] = 'Dienststatus-Inhalt auf Deutsch';
        $lang['sk'] = 'Stav služby obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getPrivacyPolicyContent(string $locale): string
    {
        $lang['cs'] = 'Zásady ochrany osobních údajů obsah v češtině';
        $lang['en'] = 'Privacy policy content in English';
        $lang['de'] = 'Datenschutzrichtlinien-Inhalt auf Deutsch';
        $lang['sk'] = 'Zásady ochrany osobných údajov obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getTermsContent(string $locale): string
    {
        $lang['cs'] = 'Obchodní podmínky obsah v češtině';
        $lang['en'] = 'Terms and conditions content in English';
        $lang['de'] = 'Geschäftsbedingungen-Inhalt auf Deutsch';
        $lang['sk'] = 'Obchodné podmienky obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }

    private function getCookiesContent(string $locale): string
    {
        $lang['cs'] = 'Zásady používání cookies obsah v češtině';
        $lang['en'] = 'Cookies policy content in English';
        $lang['de'] = 'Cookie-Richtlinien-Inhalt auf Deutsch';
        $lang['sk'] = 'Zásady používania cookies obsah v slovenčine';

        return $lang[$locale] ?? $lang['cs'];
    }
}
