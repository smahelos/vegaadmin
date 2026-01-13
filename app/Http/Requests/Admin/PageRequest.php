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
            'template' => 'nullable|string|max:100',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048',
            'tags' => 'nullable|string',
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
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $attributes = [
            'name' => __('admin.pages.name'),
            'slug' => __('admin.pages.slug'),
            'category_id' => __('admin.pages.category'),
            'description' => __('admin.pages.description'),
            'content' => __('admin.pages.content'),
            'meta_title' => __('admin.pages.meta_title'),
            'meta_description' => __('admin.pages.meta_description'),
            'meta_keywords' => __('admin.pages.meta_keywords'),
            'parent_id' => __('admin.pages.parent'),
            'sort_order' => __('admin.pages.sort_order'),
            'published' => __('admin.pages.published'),
            'publishing_start' => __('admin.pages.publishing_start'),
            'publishing_end' => __('admin.pages.publishing_end'),
            'template' => __('admin.pages.template'),
            'image' => __('admin.pages.image'),
            'tags' => __('admin.pages.tags'),
        ];
        
        // Add attributes for each locale
        foreach ($locales as $locale) {
            $localeUpper = strtoupper($locale);
            $attributes["name.$locale"] = __('admin.pages.name') . " ($localeUpper)";
            $attributes["slug.$locale"] = __('admin.pages.slug') . " ($localeUpper)";
            $attributes["description.$locale"] = __('admin.pages.description') . " ($localeUpper)";
            $attributes["content.$locale"] = __('admin.pages.content') . " ($localeUpper)";
            $attributes["meta_title.$locale"] = __('admin.pages.meta_title') . " ($localeUpper)";
            $attributes["meta_description.$locale"] = __('admin.pages.meta_description') . " ($localeUpper)";
            $attributes["meta_keywords.$locale"] = __('admin.pages.meta_keywords') . " ($localeUpper)";
        }
        
        return $attributes;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $locales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
        $messages = [
            'name.required' => __('admin.validation.pages.name.required'),
            'name.array' => __('admin.validation.pages.name.array'),
            'slug.required' => __('admin.validation.pages.slug.required'),
            'slug.array' => __('admin.validation.pages.slug.array'),
            'description.array' => __('admin.validation.pages.description.array'),
            'content.array' => __('admin.validation.pages.content.array'),
            'category_id.exists' => __('admin.validation.pages.category_id.exists'),
            'meta_title.array' => __('admin.validation.pages.meta_title.array'),
            'meta_description.array' => __('admin.validation.pages.meta_description.array'),
            'meta_keywords.array' => __('admin.validation.pages.meta_keywords.array'),
            'parent_id.exists' => __('admin.validation.pages.parent_id.exists'),
            'sort_order.integer' => __('admin.validation.pages.sort_order.integer'),
            'sort_order.min' => __('admin.validation.pages.sort_order.min'),
            'published.boolean' => __('admin.validation.pages.published.boolean'),
            'publishing_start.date' => __('admin.validation.pages.publishing_start.date'),
            'publishing_end.date' => __('admin.validation.pages.publishing_end.date'),
            'publishing_end.after' => __('admin.validation.pages.publishing_end.after'),
            'template.max' => __('admin.validation.pages.template.max'),
            'image.file' => __('admin.validation.pages.image.file'),
            'image.mimes' => __('admin.validation.pages.image.mimes'),
            'image.max' => __('admin.validation.pages.image.max'),
            'tags.string' => __('admin.validation.pages.tags.string'),
        ];
        
        // Add messages for each locale
        foreach ($locales as $locale) {
            $localeUpper = strtoupper($locale);
            $messages["name.$locale.required"] = __('admin.validation.pages.name.required') . " ($localeUpper)";
            $messages["name.$locale.string"] = __('admin.validation.pages.name.string') . " ($localeUpper)";
            $messages["name.$locale.max"] = __('admin.validation.pages.name.max') . " ($localeUpper)";
            $messages["slug.$locale.required"] = __('admin.validation.pages.slug.required') . " ($localeUpper)";
            $messages["slug.$locale.string"] = __('admin.validation.pages.slug.string') . " ($localeUpper)";
            $messages["slug.$locale.max"] = __('admin.validation.pages.slug.max') . " ($localeUpper)";
            $messages["slug.$locale.unique"] = __('admin.validation.pages.slug.unique') . " ($localeUpper)";
            $messages["slug.$locale.regex"] = __('admin.validation.pages.slug.regex') . " ($localeUpper)";
            $messages["description.$locale.string"] = __('admin.validation.pages.description.string') . " ($localeUpper)";
            $messages["content.$locale.string"] = __('admin.validation.pages.content.string') . " ($localeUpper)";
            $messages["meta_title.$locale.string"] = __('admin.validation.pages.meta_title.string') . " ($localeUpper)";
            $messages["meta_title.$locale.max"] = __('admin.validation.pages.meta_title.max') . " ($localeUpper)";
            $messages["meta_description.$locale.string"] = __('admin.validation.pages.meta_description.string') . " ($localeUpper)";
            $messages["meta_description.$locale.max"] = __('admin.validation.pages.meta_description.max') . " ($localeUpper)";
            $messages["meta_keywords.$locale.string"] = __('admin.validation.pages.meta_keywords.string') . " ($localeUpper)";
            $messages["meta_keywords.$locale.max"] = __('admin.validation.pages.meta_keywords.max') . " ($localeUpper)";
        }
        
        return $messages;
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
                $validator->errors()->add('name', __('admin.validation.pages.at_least_one_locale_required'));
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
                $validator->errors()->add('slug', __('admin.validation.pages.at_least_one_locale_required'));
            }
            
            // Validate publish/unpublish date logic
            $publishingStart = $this->input('publishing_start');
            $publishingEnd = $this->input('publishing_end');
            
            if ($publishingStart && $publishingEnd && strtotime($publishingEnd) <= strtotime($publishingStart)) {
                $validator->errors()->add('publishing_end', 'Publishing end date must be after publishing start date');
            }
        });
    }
}
