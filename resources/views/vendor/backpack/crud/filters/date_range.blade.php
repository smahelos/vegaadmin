{{-- Date Range Backpack CRUD filter --}}

<li filter-name="{{ $filter->name }}"
	filter-type="{{ $filter->type }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
	<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
	<div class="dropdown-menu p-0">
		<div class="form-group backpack-filter mb-0">
			<div class="input-group date">
		          <span class="input-group-text"><i class="la la-calendar"></i></span>
		        
		        <input class="form-control pull-right"
		        		id="daterangepicker-{{ $filter->name }}"
		        		type="text"
                        data-bs-daterangepicker=""
		        		@if ($filter->currentValue)
							@php
								$dates = (array)json_decode($filter->currentValue);
								$start_date = $dates['from'];
								$end_date = $dates['to'];
					        	$date_range = implode(' ~ ', $dates);
					        	$date_range = str_replace('-', '/', $date_range);
					        	$date_range = str_replace('~', '-', $date_range);

					        @endphp
					        placeholder="{{ $date_range }}"
						@endif
		        		>
		        <div class="input-group-append daterangepicker-{{ $filter->name }}-clear-button">
		          <a class="input-group-text" href=""><i class="la la-times"></i></a>
		        </div>
		    </div>
		</div>
	</div>
</li>

{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS  --}}
{{-- push things in the after_styles section --}}

@push('crud_list_styles')
    <!-- include select2 css-->
	<link rel="stylesheet" type="text/css" href="{{ asset('packages/bootstrap-daterangepicker/daterangepicker.css') }}" />
	<style>
		.input-group.date {
			width: 320px;
			max-width: 100%; }
		.daterangepicker.dropdown-menu {
			z-index: 3001!important;
		}
	</style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('crud_list_scripts')
	<script type="text/javascript" src="{{ asset('packages/moment/min/moment.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('packages/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
  <script>

  		function applyDateRangeFilterdateRange(start, end) {

  			if (start && end) {
  				var dates = {
					'from': start.format('YYYY-MM-DD HH:mm:ss'),
					'to': end.format('YYYY-MM-DD HH:mm:ss')
                };

                var value = JSON.stringify(dates);
  			} else {
  				var value = '';
  			}

            var parameter = 'date_range';

			var new_url = updateDatatablesOnFilterChange(parameter, value, true, 0);

			// mark this filter as active in the navbar-filters
			if (URI(new_url).hasQuery('date_range', true)) {
				$('li[filter-key={{ $filter->name }}]').removeClass('active').addClass('active');
			} else {
				$('li[filter-key={{ $filter->name }}]').trigger('filter:clear');
			}
  		}

		jQuery(document).ready(function($) {
			var dateRangeShouldUpdateFilterUrl = false;

            moment.locale('en');

			var dateRangeInput = $('#daterangepicker-{{ $filter->name }}');

            console.log('#daterangepicker-{{ $filter->name }}: ' + '{{ $filter->name }}');
            $config = dateRangeInput.data('bs-daterangepicker');

            $ranges = $config.ranges;
            $config.ranges = {};

            //if developer configured ranges we convert it to moment() dates.
            for (var key in $ranges) {
                if ($ranges.hasOwnProperty(key)) {
                    $config.ranges[key] = $.map($ranges[key], function($val) {
                        return moment($val);
                    });
                }
            }

            $config.startDate = moment($config.startDate);

            $config.endDate = moment($config.endDate);


            dateRangeInput.daterangepicker($config);


            dateRangeInput.on('apply.daterangepicker', function(ev, picker) {
				applyDateRangeFilterdateRange(picker.startDate, picker.endDate);
			});
			$('li[filter-key={{ $filter->name }}]').on('hide.bs.dropdown', function () {
				if($('.daterangepicker').is(':visible'))
			    return false;
			});
			//focus on input when filter open
			$('li[filter-key={{ $filter->name }}] a[data-bs-toggle]').on('click', function(e) {
				setTimeout(() => {
					dateRangeInput.focus();
				}, 50);
			});
			$('li[filter-key={{ $filter->name }}]').on('filter:clear', function(e) {
				//if triggered by remove filters click just remove active class,no need to send ajax
				$('li[filter-key=dateRange]').removeClass('active');
			});
			// datepicker clear button
			$(".daterangepicker-{{ $filter->name }}-clear-button").click(function(e) {
				e.preventDefault();
				applyDateRangeFilterdateRange(null, null);
			});
		});
  </script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
