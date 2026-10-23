<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage pages
        return backpack_user() && backpack_user()->can('can_create_edit_page');
    }

    /**
     * Transform multilingual field data from name_locale to name.locale format.
     */
    protected function prepareForValidation(): void
    {
        $locales = ['cs', 'en', 'de', 'sk'];
        $multilingualFields = ['name', 'slug', 'description', 'content', 'meta_title', 'meta_description', 'meta_keywords'];
        
        $transformedData = $this->all();
        
        foreach ($multilingualFields as $field) {
            $fieldData = [];
            $hasData = false;
            
            foreach ($locales as $locale) {
                $key = $field . '_' . $locale;
                if ($this->has($key)) {
                    $value = $this->input($key);
                    if (!empty($value)) {
                        $fieldData[$locale] = $value;
                        $hasData = true;
                    }
                    // Remove the original key
                    unset($transformedData[$key]);
                }
            }
            
            // Add the transformed data if we have any data for this field
            if ($hasData) {
                $transformedData[$field] = $fieldData;
            }
        }
        
        $this->replace($transformedData);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? request()->route('id');
        
        $rules = [
            // Multilingual name validation - at least one locale required
            'name' => 'required|array',
            'name.cs' => 'nullable|string|max:255',
            'name.en' => 'nullable|string|max:255',
            'name.de' => 'nullable|string|max:255',
            'name.sk' => 'nullable|string|max:255',
            
            // Multilingual slug validation - at least one locale required
            'slug' => 'required|array',
            'slug.cs' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:pages,slug->cs,' . $id,
            'slug.en' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:pages,slug->en,' . $id,
            'slug.de' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:pages,slug->de,' . $id,
            'slug.sk' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:pages,slug->sk,' . $id,
            
            // Multilingual description - optional
            'description' => 'nullable|array',
            'description.cs' => 'nullable|string',
            'description.en' => 'nullable|string',
            'description.de' => 'nullable|string',
            'description.sk' => 'nullable|string',
            
            // Multilingual content - optional
            'content' => 'nullable|array',
            'content.cs' => 'nullable|string',
            'content.en' => 'nullable|string',
            'content.de' => 'nullable|string',
            'content.sk' => 'nullable|string',
            
            // Multilingual meta fields - optional
            'meta_title' => 'nullable|array',
            'meta_title.cs' => 'nullable|string|max:255',
            'meta_title.en' => 'nullable|string|max:255',
            'meta_title.de' => 'nullable|string|max:255',
            'meta_title.sk' => 'nullable|string|max:255',
            
            'meta_description' => 'nullable|array',
            'meta_description.cs' => 'nullable|string|max:500',
            'meta_description.en' => 'nullable|string|max:500',
            'meta_description.de' => 'nullable|string|max:500',
            'meta_description.sk' => 'nullable|string|max:500',
            
            'meta_keywords' => 'nullable|array',
            'meta_keywords.cs' => 'nullable|string|max:500',
            'meta_keywords.en' => 'nullable|string|max:500',
            'meta_keywords.de' => 'nullable|string|max:500',
            'meta_keywords.sk' => 'nullable|string|max:500',
            
            // Non-multilingual fields
            'category_id' => 'nullable|exists:page_categories,id',
            'parent_id' => 'nullable|exists:pages,id',
            'sort_order' => 'nullable|integer|min:0',
            'published' => 'boolean',
            'publishing_start' => 'nullable|date',
            'publishing_end' => 'nullable|date|after:publishing_start',
            'main_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',

            // Set by the application, never by the form
            'images' => 'prohibited',
            'published_by' => 'prohibited',
            'created_at' => 'prohibited',
            'updated_at' => 'prohibited',
        ];

        // Prevent circular parent reference
        if ($this->parent_id) {
            $rules['parent_id'] .= '|different:id';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        $attributes = [];
        foreach (['name', 'slug', 'category_id', 'parent_id', 'sort_order', 'description', 'content', 'meta_title', 'meta_description', 'meta_keywords', 'main_image', 'published', 'publishing_start', 'publishing_end'] as $field) {
            $attributes[$field] = __("pages.fields.$field");
        }

        foreach (['name', 'slug', 'description', 'content', 'meta_title', 'meta_description', 'meta_keywords'] as $field) {
            foreach (config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $locale) {
                $attributes["$field.$locale"] = __("pages.fields.$field").' ('.strtoupper($locale).')';
            }
        }

        return $attributes;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'slug.*.regex' => __('pages.validation.slug_format'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure at least one name translation is provided
            $names = $this->input('name', []);
            $hasName = false;
            foreach (['cs', 'en', 'de', 'sk'] as $locale) {
                if (!empty($names[$locale])) {
                    $hasName = true;
                    break;
                }
            }
            
            if (!$hasName) {
                $validator->errors()->add('name', __('pages.validation.at_least_one_locale'));
            }
            
            // Ensure at least one slug translation is provided
            $slugs = $this->input('slug', []);
            $hasSlug = false;
            foreach (['cs', 'en', 'de', 'sk'] as $locale) {
                if (!empty($slugs[$locale])) {
                    $hasSlug = true;
                    break;
                }
            }
            
            if (!$hasSlug) {
                $validator->errors()->add('slug', __('pages.validation.at_least_one_locale'));
            }
        });
    }
}
