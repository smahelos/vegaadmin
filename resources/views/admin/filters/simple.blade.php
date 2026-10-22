{{--
    Simple filter (Billex implementation): an on/off toggle. When on, the GET parameter named after
    the filter is set to 1 and the filter logic in the CRUD controller applies it.
--}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    class="nav-item {{ $filter->isActive() ? 'active' : '' }}"
    filter-init-function="billexInitSimpleFilter">
    <a class="nav-link" href="#" data-simple-filter="{{ $filter->name }}">{{ $filter->label }}</a>
</li>

@push('crud_list_scripts')
    @once
        <script>
            // Called by the filters navbar on load and again after "Remove filters"
            function billexInitSimpleFilter(filter, navbar) {
                if (filter.dataset.billexInitialized) {
                    return;
                }
                filter.dataset.billexInitialized = '1';

                var item = $(filter);

                item.find('[data-simple-filter]').on('click', function (e) {
                    e.preventDefault();

                    var activate = !item.hasClass('active');

                    item.toggleClass('active', activate);
                    document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
                        detail: {
                            filterName: item.attr('filter-name'),
                            filterValue: activate ? 1 : '',
                            shouldUpdateUrl: true,
                            debounce: 0,
                            componentId: navbar.getAttribute('data-component-id') || '',
                        },
                    }));
                });

                // "Remove filters" button of the filters navbar (it updates the URL itself)
                filter.addEventListener('backpack:filter:clear', function () {
                    item.removeClass('active');
                });
            }
        </script>
    @endonce
@endpush
