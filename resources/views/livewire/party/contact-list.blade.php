<div>
    <x-party.list :parties="$contacts" show-route="frontend.contacts.show" route-key="contact" :search="$search"
        :archived="$archived" :order-by="$orderBy" :order-asc="$orderAsc"
        :empty-title="$archived ? __('contacts.titles.empty_archived') : __('contacts.titles.empty')"
        :empty-message="$archived ? '' : __('contacts.titles.empty_message')" />
</div>
