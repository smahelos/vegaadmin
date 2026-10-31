<div>
    <x-party.list :parties="$businesses" show-route="frontend.businesses.show" route-key="business" :search="$search"
        :archived="$archived" :order-by="$orderBy" :order-asc="$orderAsc" :default-id="$defaultId"
        :empty-title="$archived ? __('businesses.titles.empty_archived') : __('businesses.titles.empty')"
        :empty-message="$archived ? '' : __('businesses.titles.empty_message')" />
</div>
