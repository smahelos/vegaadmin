{{-- Select2 Multiple Backpack CRUD filter --}}

<li filter-name="{{ $filter->name }}"
	filter-type="{{ $filter->type }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-0">
      <div class="form-group backpack-filter mb-0">
			<select id="filter_{{ $filter->name }}" name="filter_{{ $filter->name }}" data-filter-name="{{ $filter->name }}" class="form-control input-sm select2" data-filter-type="select2_multiple" placeholder="{{ $filter->placeholder }}" multiple>
				@if (is_array($filter->values) && count($filter->values))
					@foreach($filter->values as $key => $value)
						<option value="{{ $key }}"
							@if($filter->isActive() && json_decode($filter->currentValue) && array_search($key, json_decode($filter->currentValue)) !== false)
								selected
							@endif
							>
							{{ $value }}
						</option>
					@endforeach
				@endif
			</select>
		</div>
    </div>
  </li>

{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS  --}}
{{-- push things in the after_styles section --}}

@push('crud_list_styles')
    <!-- include select2 css-->
    <link href="{{ asset('packages/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
	  .form-inline .select2-container {
	    display: inline-block;
	  }
	  .select2-drop-active {
	  	border:none;
	  }
	  .select2-container .select2-choices .select2-search-field input, .select2-container .select2-choice, .select2-container .select2-choices {
	  	border: none;
	  }
	  .select2-container-active .select2-choice {
	  	border: none;
	  	box-shadow: none;
	  }
	  .select2-container--bootstrap .select2-dropdown {
	  	margin-top: -2px;
	  	margin-left: -1px;
	  }
	  .select2-container--bootstrap {
	  	position: relative!important;
	  	top: 0px!important;
	  }
    </style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
	<!-- include select2 js-->
    <script src="{{ asset('packages/select2/dist/js/select2.full.min.js') }}"></script>
    @if (app()->getLocale() !== 'en')
    <script src="{{ asset('packages/select2/dist/js/i18n/' . app()->getLocale() . '.js') }}"></script>
    @endif

    <script>
        jQuery(document).ready(function($) {
            // trigger select2 for each untriggered select2 box
            $('select[name=filter_{{ $filter->name }}]').not('[data-filter-enabled]').each(function () {
            	var filterName = $(this).attr('data-filter-name');
                var filterKey = $(this).attr('data-filter-key');
				let closeOnSelect = $(this).attr('data-close-on-select') === 'true';

                $(this).select2({
                	allowClear: true,
					closeOnSelect: false,
					theme: "bootstrap",
					dropdownParent: $(this).parent('.form-group'),
	        	    placeholder: $(this).attr('placeholder'),
					closeOnSelect: closeOnSelect,
                    debug: true
                }).on('change', function() {
                    var value = '';
                    if (Array.isArray($(this).val())) {
                        // clean array from undefined, null, "".
                        var values = $(this).val().filter(function(e){ return e === 0 || e });
                        // stringify only if values is not empty. otherwise it will be '[]'.
                        value = values.length ? JSON.stringify(values) : '';
                    }

                    if (!value) {
                        return;
                    }

                        console.log('will updateDatatablesOnFilterChange ' + filterName + ' with value: ' + value);
                    var new_url = updateDatatablesOnFilterChange(filterName, value, true, 0);

                    if (URI(new_url).hasQuery(filterName, true)) {
                        $("li[filter-key="+filterKey+"]").addClass('active');
                    }

				}).on('select2:unselecting', function(e) {

                    var unselectingValue = e.params.args.data.id;
                    let currentElementValue = $(this).val();

                    if(currentElementValue.length) {

                        currentElementValue = currentElementValue.filter(function(v) {
                            return v !== unselectingValue
                        });

                        if (!currentElementValue.length) {
                            updateDatatablesOnFilterChange(filterName, null, true, 0);

                            $("li[filter-key="+filterKey+"]").removeClass("active");
                            $("li[filter-key="+filterKey+"]").find('.dropdown-menu').removeClass("show");
                        }
                    }

                }).on('select2:clear', function(e) {
                    // when the "x" clear all button is pressed, we update the table
                    updateDatatablesOnFilterChange(filterName, null, true, 0);

                    $("li[filter-key="+filterKey+"]").removeClass("active");
					$("li[filter-key="+filterKey+"]").find('.dropdown-menu').removeClass("show");
                });

				// when the dropdown is opened, autofocus on the select2
				$("li[filter-key="+filterKey+"]").on('shown.bs.dropdown', function () {
					$('#filter_'+filterKey+'').select2('open');
				});

				// clear filter event (used here and by the Remove all filters button)
				$("li[filter-key="+filterKey+"]").on('filter:clear', function(e) {
					$("li[filter-key="+filterKey+"]").removeClass('active');
	                $('#filter_'+filterKey).val(null).trigger('change');
				});
            });
		});
	</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
