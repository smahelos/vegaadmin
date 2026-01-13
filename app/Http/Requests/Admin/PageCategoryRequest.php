<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PageCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage page categories
        return backpack_user() && backpack_user()->can('can_create_edit_page');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        
        $rules = [];
        
        // Add validation rules for each locale using underscore notation
        foreach ($locales as $locale) {
            $rules["name_{$locale}"] = 'nullable|string|max:255';
            $rules["slug_{$locale}"] = 'nullable|string|max:255';
            $rules["description_{$locale}"] = 'nullable|string|max:1000';
            
            // Add unique validation for slugs per locale, but only if the slug is filled
            if ($this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH') {
                $id = $this->route('id') ?: $this->id;
                $rules["slug_{$locale}"] .= "|unique:page_categories,slug->$locale,$id";
            } else {
                $rules["slug_{$locale}"] .= "|unique:page_categories,slug->$locale";
            }
        }
        
        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateAtLeastOneLocale($validator);
        });
    }

    /**
     * Validate that at least one locale has both name and slug filled
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function validateAtLeastOneLocale($validator): void
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $hasValidLocale = false;
        
        foreach ($locales as $locale) {
            // Multilingual fields come as name_cs, slug_cs etc.
            $nameValue = $this->input("name_{$locale}", '');
            $slugValue = $this->input("slug_{$locale}", '');
            
            Log::debug("Checking locale {$locale}", [
                'name_value' => $nameValue,
                'slug_value' => $slugValue,
                'name_empty' => empty(trim($nameValue)),
                'slug_empty' => empty(trim($slugValue)),
            ]);
            
            // If both name and slug are filled for this locale, we have a valid locale
            if (!empty(trim($nameValue)) && !empty(trim($slugValue))) {
                $hasValidLocale = true;
                Log::debug("Found valid locale: {$locale}");
                break;
            }
        }
        
        if (!$hasValidLocale) {
            Log::debug('No valid locale found, adding validation errors');
            $validator->errors()->add(
                'name', 
                __('admin.validation.page_categories.at_least_one_locale_required')
            );
            $validator->errors()->add(
                'slug', 
                __('admin.validation.page_categories.at_least_one_locale_required')
            );
        }
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $attributes = [
            'name' => __('admin.page_categories.name'),
            'slug' => __('admin.page_categories.slug'),
            'description' => __('admin.page_categories.description'),
        ];
        
        // Add attributes for each locale
        foreach ($locales as $locale) {
            $localeUpper = strtoupper($locale);
            $attributes["name.$locale"] = __('admin.page_categories.name') . " ($localeUpper)";
            $attributes["slug.$locale"] = __('admin.page_categories.slug') . " ($localeUpper)";
            $attributes["description.$locale"] = __('admin.page_categories.description') . " ($localeUpper)";
        }
        
        return $attributes;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $messages = [
            'name.required' => __('admin.validation.page_categories.name.required'),
            'name.array' => __('admin.validation.page_categories.name.array'),
            'slug.required' => __('admin.validation.page_categories.slug.required'),
            'slug.array' => __('admin.validation.page_categories.slug.array'),
            'description.array' => __('admin.validation.page_categories.description.array'),
        ];
        
        // Add messages for each locale
        foreach ($locales as $locale) {
            $localeUpper = strtoupper($locale);
            $messages["name.$locale.required"] = __('admin.validation.page_categories.name.required') . " ($localeUpper)";
            $messages["name.$locale.string"] = __('admin.validation.page_categories.name.string') . " ($localeUpper)";
            $messages["name.$locale.max"] = __('admin.validation.page_categories.name.max') . " ($localeUpper)";
            $messages["slug.$locale.required"] = __('admin.validation.page_categories.slug.required') . " ($localeUpper)";
            $messages["slug.$locale.string"] = __('admin.validation.page_categories.slug.string') . " ($localeUpper)";
            $messages["slug.$locale.max"] = __('admin.validation.page_categories.slug.max') . " ($localeUpper)";
            $messages["slug.$locale.unique"] = __('admin.validation.page_categories.slug.unique') . " ($localeUpper)";
            $messages["description.$locale.string"] = __('admin.validation.page_categories.description.string') . " ($localeUpper)";
            $messages["description.$locale.max"] = __('admin.validation.page_categories.description.max') . " ($localeUpper)";
        }
        
        return $messages;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $mergeData = [];
        
        // Convert underscore notation to array notation for model
        foreach ($locales as $locale) {
            $nameValue = $this->input("name_{$locale}");
            $slugValue = $this->input("slug_{$locale}");
            $descriptionValue = $this->input("description_{$locale}");
            
            if ($nameValue !== null) {
                $mergeData["name"][$locale] = $nameValue;
            }
            
            if ($slugValue !== null) {
                $mergeData["slug"][$locale] = $slugValue;
            } elseif (!empty($nameValue)) {
                // Generate slug if name is provided but slug is empty
                $mergeData["slug"][$locale] = Str::slug($nameValue);
            }
            
            if ($descriptionValue !== null) {
                $mergeData["description"][$locale] = $descriptionValue;
            }
        }
        
        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }
}
