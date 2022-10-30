// jquery plugin for Zuul status page
//
// Copyright 2012 OpenStack Foundation
// Copyright 2013 Timo Tijhof
// Copyright 2013 Wikimedia Foundation
// Copyright 2014 Rackspace Australia
//
// Licensed under the Apache License, Version 2.0 (the "License"); you may
// not use this file except in compliance with the License. You may obtain
// a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
// WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
// License for the specific language governing permissions and limitations
// under the License.

( function () {
	'use strict';

	const fragment_filter_prefix = '#q=';

	// read filter from fragment
	function read_fragment_filter() {
		const hash = location.hash;

		if ( hash.indexOf( fragment_filter_prefix ) === -1 ) {
			return '';
		}

		return hash.slice( fragment_filter_prefix.length );
	}

	function update_fragment_filter( value ) {
		if ( value !== '' ) {
			history.replaceState( null, '', fragment_filter_prefix + value );
		} else {
			// Prefer not to leave an empty "#" or "#q=".
			// But if the browser doesn't have the URL API yet,
			// then don't bother with workarounds
			if ( window.URL ) {
				const obj = new URL( location.href );
				obj.hash = '';
				history.replaceState( null, '', obj.toString() );
			} else {
				history.replaceState( null, '', '#' );
			}
		}
	}

	// remember for this origin, across browser tabs and restarts.
	function set_persistent_store( name, value ) {
		try {
			localStorage.setItem( name, value );
		} catch ( e ) {
			// Disabled, or out of quota.
		}
	}

	function read_persistent_store( name ) {
		try {
			return localStorage.getItem( name );
		} catch ( e ) {
			// Disallowed.
			return null;
		}
	}

	$.zuul = function ( options ) {
		options = $.extend( {
			demo: false,
			enabled: true,
			source: 'status.json',
			msg_id: '#zuul_msg',
			pipelines_id: '#zuul_pipelines',
			queue_events_num: '#zuul_queue_events_num',
			queue_results_num: '#zuul_queue_results_num'
		}, options );

		let collapsed_exceptions = [];
		let current_filter = read_fragment_filter();
		// eslint-disable-next-line prefer-const
		let $jq;

		let xhr,
			zuul_graph_update_count = 0;

		const format = {
			job: function ( job ) {
				const $job_line = $( '<span>' );

				if ( job.url !== null ) {
					$job_line.append(
						$( '<a>' )
							.addClass( 'zuul-job-name' )
							.attr( 'href', job.url )
							.text( job.name )
					);
				} else {
					$job_line.append(
						$( '<span>' )
							.addClass( 'zuul-job-name' )
							.text( job.name )
					);
				}

				const result = job.result ? job.result.toLowerCase() : ( job.url ? 'in progress' : 'queued' );
				if ( result === 'in progress' ) {
					$job_line.append(
						this.job_progress_bar( job.elapsed_time, job.remaining_time )
					);
				} else {
					$job_line.append( this.status_label( result ) );
				}

				if ( job.voting === false ) {
					$job_line.append(
						$( ' <small>' )
							.addClass( 'zuul-non-voting-desc' )
							.text( ' (non-voting)' )
					);
				}

				$job_line.append( $( '<div style="clear: both"></div>' ) );
				return $job_line;
			},

			status_label: function ( result ) {
				const $status = $( '<span>' )
					.addClass( 'zuul-job-result zuul-job-result--label' )
					.text( result );

				switch ( result ) {
					case 'success':
						$status.addClass( 'zuul-progressbar--success' );
						break;
					case 'failure':
					case 'unstable':
						$status.addClass( 'zuul-progressbar--error' );
						break;
					case 'skipped':
					case 'lost':
					case 'aborted':
					case 'canceled':
						$status.addClass( 'zuul-progressbar--warning' );
						break;
				}
				return $status;
			},

			job_progress_bar: function ( elapsed_time, remaining_time ) {
				let progress_percent = 100 * ( elapsed_time / ( elapsed_time +
                  remaining_time ) );
				let progress_width = progress_percent;

				const progress_class = [ 'zuul-progressbar' ];
				if ( !progress_percent ) {
					progress_percent = 0;
					progress_width = 100;
					progress_class.push( 'zuul-progressbar--animated' );
				}

				const $bar_inner = $( '<div>' )
					.addClass( progress_class )
					.attr( 'role', 'progressbar' )
					.attr( 'aria-valuenow', progress_percent )
					.attr( 'aria-valuemin', '0' )
					.attr( 'aria-valuemax', '100' )
					.css( 'width', progress_width + '%' );

				const $bar_outer = $( '<div>' )
					.addClass( 'zuul-job-result zuul-job-result--progress' )
					.append( $bar_inner );

				return $bar_outer;
			},

			enqueue_time: function ( ms ) {
				// Special format case for enqueue time to add style
				const hours = 60 * 60 * 1000;
				const delta = options.demo ?
					// In demo mode, assume jobs started 0min-5h ago
					( Math.floor( Math.random() * 5 * 60 ) * 60 * 1000 ) :
					Date.now() - ms;
				let status = '';
				const text = this.time( delta, true );
				if ( delta > ( 4 * hours ) ) {
					status = 'wm-text-error';
				} else if ( delta > ( 2 * hours ) ) {
					status = 'wm-text-warning';
				}
				return '<span class="' + status + '">' + text + '</span>';
			},

			time: function ( ms, words ) {
				if ( typeof words === 'undefined' ) {
					words = false;
				}
				let seconds = ( +ms ) / 1000;
				let minutes = Math.floor( seconds / 60 );
				const hours = Math.floor( minutes / 60 );
				seconds = Math.floor( seconds % 60 );
				minutes = Math.floor( minutes % 60 );
				let r = '';
				if ( words ) {
					if ( hours ) {
						r += hours;
						r += ' hr ';
					}
					r += minutes + ' min';
				} else {
					if ( hours < 10 ) {
						r += '0';
					}
					r += hours + ':';
					if ( minutes < 10 ) {
						r += '0';
					}
					r += minutes + ':';
					if ( seconds < 10 ) {
						r += '0';
					}
					r += seconds;
				}
				return r;
			},

			change_total_progress_bar: function ( change ) {
				const job_percent = Math.floor( 100 / change.jobs.length );
				const $bar_outer = $( '<div>' )
					.addClass( 'zuul-job-result--progress zuul-change-total-result' );

				// eslint-disable-next-line no-jquery/no-each-util
				$.each( change.jobs, function ( i, job ) {
					let result = job.result ? job.result.toLowerCase() : null;
					if ( result === null ) {
						result = job.url ? 'in progress' : 'queued';
					}

					if ( result !== 'queued' ) {
						const $bar_inner = $( '<div>' )
							.addClass( 'zuul-progressbar' );

						switch ( result ) {
							case 'success':
								$bar_inner.addClass( 'zuul-progressbar--success' );
								break;
							case 'lost':
							case 'failure':
								$bar_inner.addClass( 'zuul-progressbar--error' );
								break;
							case 'unstable':
								$bar_inner.addClass( 'zuul-progressbar--warning' );
								break;
							case 'in progress':
							case 'queued':
								break;
						}
						$bar_inner
							.attr( 'title', job.name )
							.css( 'width', job_percent + '%' );
						$bar_outer.append( $bar_inner );
					}
				} );
				return $bar_outer;
			},

			change_header: function ( change ) {
				let change_id = change.id || 'NA';
				if ( change_id.length === 40 ) {
					change_id = change_id.substr( 0, 7 );
				}

				let $change_link;
				if ( change.url !== null ) {
					if ( /^[0-9a-f]{40}$/.test( change.id ) ) {
						const change_id_short = change.id.slice( 0, 7 );
						$change_link = $( '<a>' ).attr( 'href', change.url ).append(
							$( '<abbr>' )
								.attr( 'title', change.id )
								.text( change_id_short )
						);
					} else {
						$change_link = $( '<a>' ).attr( 'href', change.url ).text( change.id );
					}
				} else {
					$change_link = $( '<span>' ).text( change_id );
				}

				const $change_progress_row_left = $( '<div>' )
					.addClass( 'zuul-patchset-change' )
					.append( $change_link );
				const $change_progress_row_right = $( '<div>' )
					.addClass( 'zuul-patchset-progress' )
					.append( this.change_total_progress_bar( change ) );

				const $change_progress_row = $( '<div>' )
					.addClass( 'zuul-patchset-sub' )
					.append( $change_progress_row_left )
					.append( $change_progress_row_right );

				const $project_span = $( '<span>' )
					.addClass( 'change_project' )
					.text( change.project );

				const $left = $( '<div>' )
					.addClass( 'zuul-patchset-header-left' )
					.append( $project_span, $change_progress_row );

				const $right = $( '<div>' );
				if ( change.live === true ) {
					const remaining_time = this.time( change.remaining_time, true );
					const enqueue_time = this.enqueue_time( change.enqueue_time );
					const $remaining_time = $( '<span>' )
						.attr( 'title', 'Remaining Time' ).html( 'ETA: ' + remaining_time );
					const $enqueue_time = $( '<span>' )
						.attr( 'title', 'Elapsed Time' ).html( 'Elapsed: ' + enqueue_time );
					$right.addClass( 'zuul-patchset-eta' )
						.append( $remaining_time, $( '<br>' ), $enqueue_time );
				}

				return $left.add( $right );
			},

			change_list: function ( jobs ) {
				const $list = $( '<ul>' )
					.addClass( 'zuul-patchset-body' );

				// eslint-disable-next-line no-jquery/no-each-util
				$.each( jobs, function ( i, job ) {
					const $item = $( '<li>' )
						.addClass( 'zuul-change-job' )
						.append( format.job( job ) );
					$list.append( $item );
				} );

				return $list;
			},

			change_panel: function ( change ) {
				const $header = $( '<div>' )
					.addClass( 'zuul-patchset-header' )
					.append( this.change_header( change ) );

				const panel_id = change.id ? change.id.replace( ',', '_' ) :
					change.project.replace( '/', '_' ) +
                                           '-' + change.enqueue_time;
				const $panel = $( '<div>' )
					.attr( 'id', panel_id )
					.addClass( 'zuul-change' )
					.append( $header )
					.append( this.change_list( change.jobs ) );

				$header.on( 'click', this.toggle_patchset );
				return $panel;
			},

			change_status_icon: function ( change ) {
				let icon_class = 'zuul-queue-icon--success';
				let icon_title = 'Succeeding';

				if ( change.active !== true ) {
					icon_class = 'zuul-queue-icon--waiting';
					icon_title = 'Waiting until closer to head of queue to' +
                        ' start jobs';
				} else if ( change.live !== true ) {
					icon_class = 'zuul-queue-icon--waiting';
					icon_title = 'Dependent change required for testing';
				} else if ( change.failing_reasons &&
                         change.failing_reasons.length > 0 ) {
					const reason = change.failing_reasons.join( ', ' );
					icon_title = 'Failing because ' + reason;
					icon_class = 'zuul-queue-icon--error';
				}

				const $icon = $( '<span>' )
					.addClass( [ 'zuul-queue-icon', icon_class ] )
					.attr( 'title', icon_title );

				return $icon;
			},

			change_with_status_tree: function ( change, change_queue ) {
				const $change_row = $( '<tr>' );

				for ( let i = 0; i < change_queue._tree_columns; i++ ) {
					const $tree_cell = $( '<td>' )
						.addClass( 'zuul-queue-line' );

					if ( i < change._tree.length && change._tree[ i ] !== null ) {
						$tree_cell.addClass( 'zuul-queue-line--solid' );
					}

					if ( i === change._tree_index ) {
						$tree_cell.append(
							this.change_status_icon( change ) );
					}
					if ( change._tree_branches.indexOf( i ) !== -1 ) {
						const $image = $( '<span>' );
						if ( change._tree_branches.indexOf( i ) ===
                            change._tree_branches.length - 1 ) {
							// Angle line
							$image.addClass( 'zuul-queue-angle' );
						} else {
							// T line
							$image.addClass( 'zuul-queue-tee' );
						}
						$tree_cell.append( $image );
					}
					$change_row.append( $tree_cell );
				}

				const change_width = 360 - 16 * change_queue._tree_columns;
				const $change_column = $( '<td>' )
					.css( 'width', change_width + 'px' )
					.addClass( 'zuul-change-cell' )
					.append( this.change_panel( change ) );

				$change_row.append( $change_column );

				const $change_table = $( '<table>' )
					.addClass( 'zuul-change-box' )
					.append( $change_row );

				return $change_table;
			},

			pipeline_header: function ( pipeline, count ) {
				// Format the pipeline name, and description
				const $header_div = $( '<div>' )
					.addClass( 'zuul-pipeline-header' );

				const $heading = $( '<h3>' )
					.text( pipeline.name )
					.append(
						' ',
						$( '<span>' )
							.addClass( 'zuul-badge zuul-pipeline-count' )
							.text( count )
					);

				$header_div.append( $heading );

				if ( typeof pipeline.description === 'string' ) {
					// eslint-disable-next-line no-jquery/no-each-util
					$.each( pipeline.description.split( /\r?\n\r?\n/ ), function ( index, descr_part ) {
						$header_div.append(
							$( '<p class="zuul-pipeline-desc">' ).text( descr_part )
						);
					} );
				}
				return $header_div;
			},

			pipeline: function ( pipeline, count ) {
				const $html = $( '<div>' )
					// WMF(Aug 2019): Hide pipelines without matches when filtering.
					.data( 'change_ids', new Set() )
					.addClass( 'zuul-pipeline' )
					.append( this.pipeline_header( pipeline, count ) );

				// eslint-disable-next-line no-jquery/no-each-util
				$.each( pipeline.change_queues,
					function ( queue_i, change_queue ) {
						// eslint-disable-next-line no-jquery/no-each-util
						$.each( change_queue.heads, function ( head_i, changes ) {
							if ( pipeline.change_queues.length > 1 &&
                            head_i === 0 ) {
								const name = change_queue.name;
								let short_name = name;
								if ( short_name.length > 32 ) {
									short_name = short_name.substr( 0, 32 ) + '...';
								}
								$html.append(
									$( '<p>' )
										.addClass( 'zuul-queue-desc' )
										.text( 'Queue: ' )
										.append(
											$( '<abbr>' )
												.attr( 'title', name )
												.text( short_name )
										)
								);
							}

							// eslint-disable-next-line no-jquery/no-each-util
							$.each( changes, function ( change_i, change ) {
								const $change_box = format.change_with_status_tree(
									change,
									change_queue
								);
								$html.append( $change_box );
								format.display_patchset( $change_box );
							} );
						} );
					} );
				return $html;
			},

			toggle_patchset: function ( e ) {
				// Toggle showing/hiding the patchset when the header is
				// clicked.

				if ( e.target.nodeName.toLowerCase() === 'a' ) {
					// Ignore clicks from gerrit patch set link
					return;
				}

				// Grab the patchset panel
				const $panel = $( e.target ).parents( '.zuul-change' );
				const $body = $panel.children( '.zuul-patchset-body' );
				$body.toggle( 200 );
				const collapsed_index = collapsed_exceptions.indexOf(
					$panel.attr( 'id' ) );
				if ( collapsed_index === -1 ) {
					// Currently not an exception, add it to list
					collapsed_exceptions.push( $panel.attr( 'id' ) );
				} else {
					// Currently an except, remove from exceptions
					collapsed_exceptions.splice( collapsed_index, 1 );
				}
			},

			display_patchset: function ( $change_box, animate ) {
				// Determine if to show or hide the patchset and/or the results
				// when loaded

				// See if we should hide the body/results
				const $panel = $change_box.find( '.zuul-change' );
				const panel_change = $panel.attr( 'id' );
				const $body = $panel.children( '.zuul-patchset-body' );
				const expand_by_default = $( '#expand_by_default' )
					.prop( 'checked' );

				const collapsed_index = collapsed_exceptions
					.indexOf( panel_change );

				if ( expand_by_default && collapsed_index === -1 ||
                    !expand_by_default && collapsed_index !== -1 ) {
					// Expand by default, or is an exception
					$body.show( animate );
				} else {
					$body.hide( animate );
				}

				// Check if we should hide the whole panel
				const panel_project = $panel.find( '.change_project' ).text()
					.toLowerCase();

				const $pipeline = $change_box
					.parents( '.zuul-pipeline' );

				const panel_pipeline = $pipeline
					.find( '.zuul-pipeline-header > h3' )
					.html()
					.toLowerCase();

				let show_panel = true;
				if ( current_filter !== '' ) {
					const filter = current_filter.trim().split( /[\s,]+/ );
					show_panel = false;
					// eslint-disable-next-line no-jquery/no-each-util
					$.each( filter, function ( index, f_val ) {
						if ( f_val !== '' ) {
							f_val = f_val.toLowerCase();
							if ( panel_project.indexOf( f_val ) !== -1 ||
                                panel_pipeline.indexOf( f_val ) !== -1 ||
                                panel_change.indexOf( f_val ) !== -1 ) {
								show_panel = true;
							}
						}
					} );
				}
				if ( show_panel === true ) {
					$change_box.show( animate );
					$pipeline.data( 'change_ids' ).add( panel_change );
				} else {
					$change_box.hide( animate );
					// WMF(Aug 2019): Hide pipelines without matches when filtering.
					$pipeline.data( 'change_ids' ).delete( panel_change );
				}
			}
		};

		const app = {
			schedule: function () {
				if ( !options.enabled ) {
					setTimeout( function () {
						app.schedule( app );
					}, 5000 );
					return;
				}
				app.update().always( function () {
					setTimeout( function () {
						app.schedule( app );
					}, 5000 );
				} );

				/* Only update graphs every minute */
				if ( zuul_graph_update_count > 11 ) {
					zuul_graph_update_count = 0;
				}
			},

			/** @return {jQuery.Promise} */
			update: function () {
				// Cancel the previous update if it hasn't completed yet.
				if ( xhr ) {
					xhr.abort();
				}

				app.emit( 'update-start' );

				const $msg = $( options.msg_id );
				xhr = $.getJSON( options.source )
					.done( function ( data ) {
						if ( 'message' in data ) {
							$msg.removeClass( 'wm-alert-error' )
								.text( data.message )
								.show();
						} else {
							$msg.empty()
								.hide();
						}

						if ( 'zuul_version' in data ) {
							$( '#zuul-version-span' ).text( data.zuul_version );
						}
						if ( 'last_reconfigured' in data ) {
							const last_reconfigured =
                                new Date( data.last_reconfigured );
							$( '#last-reconfigured-span' ).text(
								last_reconfigured.toString() );
						}

						const $pipelines = $( options.pipelines_id );
						$pipelines.html( '' );
						// eslint-disable-next-line no-jquery/no-each-util
						$.each( data.pipelines, function ( i, pipeline ) {
							const count = app.create_tree( pipeline );
							$pipelines.append(
								format.pipeline( pipeline, count ) );
						} );
						app.handle_pipeline_visibility();

						$( options.queue_events_num ).text(
							data.trigger_event_queue ?
								data.trigger_event_queue.length : '0'
						);
						$( options.queue_results_num ).text(
							data.result_event_queue ?
								data.result_event_queue.length : '0'
						);
					} )
					.fail( function ( jqXHR, statusText, errMsg ) {
						if ( statusText === 'abort' ) {
							return;
						}
						$msg.text( options.source + ': ' + errMsg )
							.addClass( 'wm-alert-error' )
							.removeClass( 'zuul-msg-wrap-off' )
							.show();
					} )
					.always( function () {
						xhr = undefined;
						app.emit( 'update-end' );
					} );

				return xhr;
			},

			emit: function () {
				$jq.trigger.apply( $jq, arguments );
				return this;
			},
			on: function () {
				$jq.on.apply( $jq, arguments );
				return this;
			},
			one: function () {
				$jq.one.apply( $jq, arguments );
				return this;
			},

			// Build the filter form filling anything from cookies
			control_form: function () {
				const $control_form = $( '<form>' )
					.attr( 'role', 'form' );

				const $label = $( '<label>' )
					.attr( 'for', 'filter_string' )
					.text( 'Filter:' );

				const $input_group = $( '<span>' )
					.addClass( 'wm-input-group--aside' );

				// WMF(April 2019): Add 'placeholder' to Filter input.
				// WMF(April 2019): Improve 'title' text for Filter input.
				const $input = $( '<input>' ).prop( {
					type: 'text',
					id: 'filter_string',
					className: 'wm-input-text',
					title: 'Any partial match for a gerrit change number, repo name, or pipeline. Multiple terms may be comma-separated.',
					placeholder: 'e.g. 1234 or mediawiki…   [ / ]',
					value: current_filter
				} ).css( 'min-width', '20em' );

				// WMF(April 2019): Listen on 'input' instead of 'change'.
				// The input event will fire as-you-type. The 'change' event
				// only fired when clicking or tabbing to elsewhere on the page.
				$input.on( 'input', app.handle_filter_change );

				// Update the filter form with a clear button if required
				const $clear_icon = $( '<span>' )
					.addClass( 'wm-input-icon--clear' )
					.attr( 'id', 'filter_form_clear_box' )
					.attr( 'title', 'Clear filter' )
					.css( 'cursor', 'pointer' );

				$clear_icon.on( 'click', function () {
					$input.val( '' ).trigger( 'focus' );
					app.handle_filter_change();
				} );

				if ( current_filter === '' ) {
					$clear_icon.hide();
				}

				$control_form.append(
					$label,
					' ',
					$input_group.append( $input, $clear_icon ),
					' ',
					this.expand_form_group()
				);
				return $control_form;
			},

			expand_form_group: function () {
				const initial_value = (
					read_persistent_store( 'zuul_expand_by_default' ) === 'true'
				);

				const $checkbox = $( '<input>' )
					.attr( 'type', 'checkbox' )
					.attr( 'id', 'expand_by_default' )
					.prop( 'checked', initial_value )
					.on( 'change', this.handle_expand_by_default );

				const $label = $( '<label>' )
					.text( ' Expand by default' )
					.prepend( $checkbox );

				return $label;
			},

			// WMF(April 2019): Expose this for zuul.app.js to focus
			// input field when pressing "/" keyboard shortcut.
			focus_filter_input: function () {
				$( '#filter_string' ).trigger( 'focus' );
			},

			handle_filter_change: function () {
				// Update the filter and save it to a cookie
				current_filter = $( '#filter_string' ).val();
				if ( current_filter === '' ) {
					$( '#filter_form_clear_box' ).hide();
				} else {
					$( '#filter_form_clear_box' ).show();
				}

				$( '.zuul-change-box' ).each( function ( index, obj ) {
					const $change_box = $( obj );
					format.display_patchset( $change_box, 200 );
				} );
				app.handle_pipeline_visibility();

				update_fragment_filter( current_filter );
			},

			handle_pipeline_visibility: function () {
				if ( current_filter !== '' ) {
					// WMF(Aug 2019): Hide pipelines without matches when filtering.
					$( '.zuul-pipeline' ).each( function () {
						if ( $( this ).data( 'change_ids' ).size ) {
							$( this ).show();
						} else {
							$( this ).hide();
						}
					} );
				} else {
					$( '.zuul-pipeline' ).show();
				}
			},

			handle_expand_by_default: function ( e ) {
				// Handle toggling expand by default
				set_persistent_store( 'zuul_expand_by_default', String( e.target.checked ) );
				collapsed_exceptions = [];
				$( '.zuul-change-box' ).each( function ( index, obj ) {
					const $change_box = $( obj );
					format.display_patchset( $change_box, 200 );
				} );
			},

			create_tree: function ( pipeline ) {
				let count = 0;
				let pipeline_max_tree_columns = 1;
				// eslint-disable-next-line no-jquery/no-each-util
				$.each( pipeline.change_queues, function ( change_queue_i,
					change_queue ) {
					const tree = [];
					let max_tree_columns = 1;
					const changes = [];
					let last_tree_length = 0;
					// eslint-disable-next-line no-jquery/no-each-util
					$.each( change_queue.heads, function ( head_i, head ) {
						// eslint-disable-next-line no-jquery/no-each-util
						$.each( head, function ( change_i, change ) {
							changes[ change.id ] = change;
							change._tree_position = change_i;
						} );
					} );
					// eslint-disable-next-line no-jquery/no-each-util
					$.each( change_queue.heads, function ( head_i, head ) {
						// eslint-disable-next-line no-jquery/no-each-util
						$.each( head, function ( change_i, change ) {
							if ( change.live === true ) {
								count += 1;
							}
							const idx = tree.indexOf( change.id );
							if ( idx > -1 ) {
								change._tree_index = idx;
								// remove...
								tree[ idx ] = null;
								while ( tree[ tree.length - 1 ] === null ) {
									tree.pop();
								}
							} else {
								change._tree_index = 0;
							}
							change._tree_branches = [];
							change._tree = [];
							if ( typeof change.items_behind === 'undefined' ) {
								change.items_behind = [];
							}
							change.items_behind.sort( function ( a, b ) {
								return ( changes[ b ]._tree_position -
                                        changes[ a ]._tree_position );
							} );
							// eslint-disable-next-line no-jquery/no-each-util
							$.each( change.items_behind, function ( i, id ) {
								tree.push( id );
								if ( tree.length > last_tree_length &&
                                    last_tree_length > 0 ) {
									change._tree_branches.push(
										tree.length - 1 );
								}
							} );
							if ( tree.length > max_tree_columns ) {
								max_tree_columns = tree.length;
							}
							if ( tree.length > pipeline_max_tree_columns ) {
								pipeline_max_tree_columns = tree.length;
							}
							change._tree = tree.slice( 0 ); // make a copy
							last_tree_length = tree.length;
						} );
					} );
					change_queue._tree_columns = max_tree_columns;
				} );
				pipeline._tree_columns = pipeline_max_tree_columns;
				return count;
			}
		};

		$jq = $( app );
		return {
			options: options,
			format: format,
			app: app,
			jq: $jq
		};
	};
}() );
