<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <x-form.input class="md:col-span-4" model="party.name" :label="__('parties.fields.name')" required />
        <x-form.input class="md:col-span-2" model="party.shortName" :label="__('parties.fields.short_name')"
            :hint="__('parties.hints.short_name')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <x-form.input class="md:col-span-3" type="email" model="party.email" :label="__('parties.fields.email')" />
        <x-form.input class="md:col-span-3" model="party.phone" :label="__('parties.fields.phone')" />
    </div>

    {{ $slot }}

    <x-form.textarea model="party.note" :label="__('parties.fields.note')" :hint="__('parties.hints.note')" />
</div>
