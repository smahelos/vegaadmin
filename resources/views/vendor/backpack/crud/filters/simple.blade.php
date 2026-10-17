{{--
    Simple filter (Billex implementation): an on/off toggle. When on, the GET parameter named after
    the filter is set to 1 and the filter logic in the CRUD controller applies it.
--}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    class="nav-item {{ $filter->isActive() ? 'active' : '' }}">
    <a class="nav-link" href="#" data-simple-filter="{{ $filter->name }}">{{ $filter->label }}</a>
</li>

@push('crud_list_scripts')
    @once
        <script>
            jQuery(function ($) {
                $('[data-simple-filter]').on('click', function (e) {
                    e.preventDefault();

                    var name = $(this).data('simple-filter');
                    var item = $('li[filter-name="' + name + '"]');
                    var activate = !item.hasClass('active');

                    updateDatatablesOnFilterChange(name, activate ? 1 : '', true, 0);

                    if (activate) {
                        item.addClass('active');
                    } else {
                        item.removeClass('active').trigger('filter:clear');
                    }
                });

                // "Remove filters" button of the filters navbar (it updates the URL itself)
                $('li[filter-type="simple"]').on('filter:clear', function () {
                    $(this).removeClass('active');
                });
            });
        </script>
    @endonce
@endpush
