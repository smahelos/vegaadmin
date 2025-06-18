<?php

namespace Tests\Feature\Traits;

use App\Traits\HasPreferredLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasPreferredLocaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function locale_from_country_returns_correct_locales(): void
    {
        // Test known countries
        $this->assertEquals('cs', TestLocaleModel::localeFromCountry('CZ'));
        $this->assertEquals('cs', TestLocaleModel::localeFromCountry('cz'));
        $this->assertEquals('cs', TestLocaleModel::localeFromCountry('CS'));
        $this->assertEquals('sk', TestLocaleModel::localeFromCountry('SK'));
        $this->assertEquals('de', TestLocaleModel::localeFromCountry('DE'));
        $this->assertEquals('at', TestLocaleModel::localeFromCountry('AT'));
        $this->assertEquals('ch', TestLocaleModel::localeFromCountry('CH'));
    }

    #[Test]
    public function locale_from_country_returns_default_for_unknown_country(): void
    {
        // Test unknown countries return fallback locale
        $this->assertEquals('en', TestLocaleModel::localeFromCountry('US'));
        $this->assertEquals('en', TestLocaleModel::localeFromCountry('FR'));
        $this->assertEquals('en', TestLocaleModel::localeFromCountry('XYZ'));
    }

    #[Test]
    public function locale_from_country_handles_null_input(): void
    {
        $this->assertEquals('en', TestLocaleModel::localeFromCountry(null));
        $this->assertEquals('en', TestLocaleModel::localeFromCountry(''));
    }

    #[Test]
    public function get_preferred_locale_uses_country_attribute(): void
    {
        $model = new TestLocaleModel();
        $model->attributes = ['country' => 'CZ'];
        
        $this->assertEquals('cs', $model->getPreferredLocale());
    }

    #[Test]
    public function get_preferred_locale_uses_country_attribute_case_insensitive(): void
    {
        $model = new TestLocaleModel();
        $model->attributes = ['country' => 'cz'];
        
        $this->assertEquals('cs', $model->getPreferredLocale());
    }

    #[Test]
    public function get_preferred_locale_returns_default_for_unknown_country(): void
    {
        $model = new TestLocaleModel();
        $model->attributes = ['country' => 'US'];
        
        $this->assertEquals('en', $model->getPreferredLocale());
    }

    #[Test]
    public function get_preferred_locale_uses_address_country_when_available(): void
    {
        $address = new \stdClass();
        $address->country = 'SK';
        
        $model = new TestLocaleModel();
        $model->setAddressRelation($address);
        
        $this->assertEquals('sk', $model->getPreferredLocale());
    }

    #[Test]
    public function get_preferred_locale_uses_address_country_case_insensitive(): void
    {
        $address = new \stdClass();
        $address->country = 'sk';
        
        $model = new TestLocaleModel();
        $model->setAddressRelation($address);
        
        $this->assertEquals('sk', $model->getPreferredLocale());
    }

    #[Test]
    public function get_preferred_locale_returns_default_when_no_country(): void
    {
        $model = new TestLocaleModel();
        
        $this->assertEquals('en', $model->getPreferredLocale());
    }

    #[Test]
    public function get_preferred_locale_prefers_direct_country_over_address(): void
    {
        $address = new \stdClass();
        $address->country = 'SK';
        
        $model = new TestLocaleModel();
        $model->attributes = ['country' => 'CZ'];
        $model->setAddressRelation($address);
        
        // Should use direct country attribute over address
        $this->assertEquals('cs', $model->getPreferredLocale());
    }

    #[Test]
    public function country_to_locale_mapping_is_comprehensive(): void
    {
        // Test all mapped countries
        $expectedMappings = [
            'CZ' => 'cs',
            'CS' => 'cs',
            'SK' => 'sk',
            'DE' => 'de',
            'AT' => 'at',
            'CH' => 'ch',
        ];

        foreach ($expectedMappings as $country => $expectedLocale) {
            $this->assertEquals($expectedLocale, TestLocaleModel::localeFromCountry($country));
        }
    }
}

/**
 * Test model class that uses HasPreferredLocale trait for testing
 */
class TestLocaleModel
{
    use HasPreferredLocale;

    public array $attributes = [];
    private $address = null;

    public function setAddressRelation($address): void
    {
        $this->address = $address;
    }

    public function address()
    {
        return $this->address;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }
}
