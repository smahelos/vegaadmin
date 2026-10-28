@props([
    'form',
    'names',
])

<x-form.input :model="$form . '.name'" :name="$names . '_name'" :label="__('parties.fields.name')" required />
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-form.input type="email" :model="$form . '.email'" :name="$names . '_email'" :label="__('parties.fields.email')" />
    <x-form.input :model="$form . '.phone'" :name="$names . '_phone'" :label="__('parties.fields.phone')" />
</div>
