<?php

namespace App\Application\Shared\Form\DTO;

use App\Application\Shared\Form\Enum\FieldType;

/**
 * FieldDTO represents a single form field definition with enum-backed type.
 */
class FieldDTO
{
    public readonly FieldType $fieldType;

    /** @param array<string,mixed> $options */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        string|FieldType $type,
        public readonly array $options = [],
        public readonly bool $required = false,
        public readonly string $hint = '',
        public readonly string $placeholder = '',
        public readonly mixed $default = null,
        public readonly ?string $entity = null,
        public readonly ?string $attribute = null,
        public readonly ?string $model = null,
        public readonly ?string $accept = null,
    ) {
        $this->fieldType = $type instanceof FieldType ? $type : FieldType::fromLegacy($type);
    }

    /**
     * Export to array preserving legacy structure for blade compatibility.
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        // Always keep common keys (hint, placeholder, required) to prevent undefined index notices in Blade.
        // Only drop keys that are truly null (entity/attribute/model/accept etc.)
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->fieldType->value,
            'options' => $this->options,
            'required' => $this->required, // keep boolean explicitly
            'hint' => $this->hint, // keep even if empty string
            'placeholder' => $this->placeholder, // keep even if empty string
            'default' => $this->default,
            'entity' => $this->entity,
            'attribute' => $this->attribute,
            'model' => $this->model,
            'accept' => $this->accept,
        ];

        // Remove only null-valued optional keys to keep payload lean.
        foreach (['entity','attribute','model','accept','default'] as $maybeNull) {
            if (!array_key_exists($maybeNull, $data)) { continue; }
            if ($data[$maybeNull] === null) {
                unset($data[$maybeNull]);
            }
        }

        return $data;
    }
}
