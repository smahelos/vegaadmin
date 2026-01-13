<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First create/update subscription plan features
        $this->createSubscriptionPlanFeatures();
        
        // Then create subscription plans based on production data
        $plans = [
            [
                'name' => 'Basic Plan',
                'description' => 'Základní plán pro registrované uživatele',
                'price' => 50.00,
                'currency' => 'CZK',
                'billing_period' => 'monthly',
                'billing_interval' => 1,
                'trial_days' => 14,
                'is_active' => true,
                'feature_slugs' => [
                    'neomezeny-pocet-faktur',
                    'az-10-ulozenych-klientu',
                    'az-10-ulozenych-dodavatelu',
                    'komplexni-statistiky-a-grafy',
                    'export-faktur-do-pdf',
                    'logo-pro-fakturu',
                    'logo-dodavatele',
                    'automaticke-pripominy',
                    'ares-vyhledavani'
                ]
            ],
            [
                'name' => 'Proffesional Plan',
                'description' => 'Proffesional Plan',
                'price' => 100.00,
                'currency' => 'CZK',
                'billing_period' => 'monthly',
                'billing_interval' => 1,
                'trial_days' => 14,
                'is_active' => true,
                'feature_slugs' => [
                    'neomezeny-pocet-faktur',
                    'az-10-ulozenych-klientu',
                    'az-10-ulozenych-dodavatelu',
                    'komplexni-statistiky-a-grafy',
                    'az-30-ulozenych-klientu',
                    'az-10-produktusluzeb',
                    'export-faktur-do-pdf',
                    'vyber-z-vice-pdf-sablon',
                    'logo-pro-fakturu',
                    'logo-dodavatele',
                    'snadne-vkladani-produktu',
                    'az-30-ulozenych-dodavatelu',
                    'az-20-ulozenych-produktu',
                    'automaticke-pripominy',
                    'ares-vyhledavani'
                ]
            ]
        ];

        foreach ($plans as $planData) {
            $featureSlugs = $planData['feature_slugs'];
            unset($planData['feature_slugs']);
            
            $plan = SubscriptionPlan::firstOrCreate(
                ['name' => $planData['name']],
                $planData
            );
            
            // Sync features for this plan
            $featureIds = SubscriptionPlanFeature::whereIn('slug', $featureSlugs)->pluck('id');
            $plan->features()->sync($featureIds);
        }
    }
    
    /**
     * Create subscription plan features based on production data
     */
    private function createSubscriptionPlanFeatures(): void
    {
        $features = [
            [
                'name' => 'Neomezený počet faktur',
                'slug' => 'neomezeny-pocet-faktur',
                'description' => null,
                'is_active' => true,
                'sort_order' => 0
            ],
            [
                'name' => 'Až 10 uložených klientů',
                'slug' => 'az-10-ulozenych-klientu',
                'description' => 'Možnost uložit si a spravovat až 10 uložených klientů',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Až 10 uložených dodavatelů',
                'slug' => 'az-10-ulozenych-dodavatelu',
                'description' => 'Možnost uložit si a spravovat až 10 uložených dodavatelů',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Komplexní statistiky a grafy',
                'slug' => 'komplexni-statistiky-a-grafy',
                'description' => 'Statistiky a grafy o Vašich transakcích',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Až 30 uložených klientů',
                'slug' => 'az-30-ulozenych-klientu',
                'description' => 'Možnost uložit si a spravovat až 30 uložených klientů',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Až 10 produktů/služeb',
                'slug' => 'az-10-produktusluzeb',
                'description' => 'Možnost uložit si a spravovat až 10 produktů/služeb',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Export faktur do PDF',
                'slug' => 'export-faktur-do-pdf',
                'description' => 'Faktury lze exportovat do PDF formátu',
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Výběr z více PDF šablon',
                'slug' => 'vyber-z-vice-pdf-sablon',
                'description' => 'Možost vybrat s z více šablon pro Vaší PDF fakturu',
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Logo pro fakturu',
                'slug' => 'logo-pro-fakturu',
                'description' => 'K faktuře je možné nahrát lgo Vaší firmy',
                'is_active' => true,
                'sort_order' => 8
            ],
            [
                'name' => 'Logo dodavatele',
                'slug' => 'logo-dodavatele',
                'description' => 'K dodavateli je možné nahrát logo firmy, tak aby se načítalo do faktury automaticky.',
                'is_active' => true,
                'sort_order' => 9
            ],
            [
                'name' => 'Snadné vkládání produktů',
                'slug' => 'snadne-vkladani-produktu',
                'description' => 'Při vytváření faktury snadno vyberete z uložených produktů/služeb ty, které chcete k faktuře přiřadit.',
                'is_active' => true,
                'sort_order' => 10
            ],
            [
                'name' => 'Až 30 uložených dodavatelů',
                'slug' => 'az-30-ulozenych-dodavatelu',
                'description' => 'Možnost uložit si a spravovat až 30 uložených dodavatelů',
                'is_active' => true,
                'sort_order' => 11
            ],
            [
                'name' => 'Až 20 uložených produktů',
                'slug' => 'az-20-ulozenych-produktu',
                'description' => 'Možnost uložit si a spravovat až 30 produktů/služeb',
                'is_active' => true,
                'sort_order' => 13
            ],
            [
                'name' => 'Automatické připomíny',
                'slug' => 'automaticke-pripominy',
                'description' => 'Systém automaticky upozorní jak dodavatele tak klienta o blížící se splatnosti faktury, nebo když je faktura po splatnosti.',
                'is_active' => true,
                'sort_order' => 14
            ],
            [
                'name' => 'ARES vyhledávání',
                'slug' => 'ares-vyhledavani',
                'description' => 'Snadné vyhledávání informací o obchodních subjektech podle IČO',
                'is_active' => true,
                'sort_order' => 15
            ]
        ];

        foreach ($features as $featureData) {
            SubscriptionPlanFeature::firstOrCreate(
                ['slug' => $featureData['slug']],
                $featureData
            );
        }
    }
}
