// Zuul status page
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

/* eslint max-len: ["warn", { "code": 120 }] */
/* exported zuul_start */

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
		let zuul_graph_update_count = 0;
		let last_rendered_raw;
		let xhr;
		// eslint-disable-next-line prefer-const
		let $jq;

		const format = {
			job: function ( job ) {
				const result = job.result ? job.result.toLowerCase() : ( job.url ? 'in progress' : 'queued' );
				const jobName = job.name + ( job.voting === false ? ' (non\u00a0voting)' : '' );

				let progress_percent = 100 * (
					job.elapsed_time / ( job.elapsed_time + job.remaining_time ) );
				let progress_width = progress_percent;
				const progress_class = [ 'zuul-progressbar' ];
				if ( !progress_percent ) {
					progress_percent = 0;
					progress_width = 100;
					progress_class.push( 'zuul-progressbar--animated' );
				}

				return $( '<li>' )
					.addClass( 'zuul-change-job' )
					.append(
						( job.url !== null )
							? $( '<a>' )
								.addClass( 'zuul-job-name' )
								.attr( 'href', job.url )
								.text( jobName )
							: $( '<span>' )
								.addClass( 'zuul-job-name' )
								.text( jobName )
					)
					.append(
						( result === 'in progress' )
							? $( '<div>' )
								.addClass( 'zuul-job-result zuul-job-result--progress' )
								.append( $( '<div>' )
									.addClass( progress_class )
									.attr( 'role', 'progressbar' )
									.attr( 'aria-valuenow', progress_percent )
									.attr( 'aria-valuemin', '0' )
									.attr( 'aria-valuemax', '100' )
									.css( 'width', progress_width + '%' )
								)
							: $( '<span>' )
								.addClass( 'zuul-job-result zuul-job-result--label' )
								.attr( 'data-result', result )
								.text( result )
					)
					.get( 0 );
			},

			enqueue_time: function ( ms ) {
				// Special format case for enqueue time to add style
				const hours = 60 * 60 * 1000;
				const delta = options.demo ?
					// In demo mode, ignore the far-past timestamps in the sample data,
					// and instead pretend jobs started 0min-5h ago
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
						r += hours + '\u2006hr ';
					}
					r += minutes + '\u2006min';
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
				return $( '<div>' )
					.addClass( 'zuul-job-result--progress zuul-change-total-result' )
					.append( change.jobs.map( ( job ) => {
						const result = job.result ? job.result.toLowerCase() : ( job.url ? 'in progress' : 'queued' );
						if ( result === 'queued' ) {
							return '';
						}
						return $( '<div>' )
							.addClass( 'zuul-progressbar' )
							.attr( 'data-result', result )
							.attr( 'title', job.name )
							.css( 'width', job_percent + '%' )
							.get( 0 );
					} ) );
			},

			change_panel: function ( change ) {
				const panel_id = change.id ?
					change.id.replace( ',', '_' ) :
					change.project.replace( '/', '_' ) + '-' + change.enqueue_time;

				// Zuul events may respond to a commit hash (eg. tag) without a Gerrit change number
				const isLongHash = /^[0-9a-f]{40}$/.test( change.id || '' );
				const change_id_short = isLongHash ? change.id.slice( 0, 7 ) : ( change.id || 'NA' );

				const remaining_time = change.live && this.time( change.remaining_time, true );
				const enqueue_time = change.live && this.enqueue_time( change.enqueue_time );

				return $( '<div>' )
					.addClass( 'zuul-change' )
					.attr( 'id', panel_id )
					.append(
						$( '<div>' )
							.addClass( 'zuul-patchset-header' )
							.on( 'click', this.toggle_patchset )
							.append( $( '<div>' )
								.addClass( 'zuul-patchset-header-left' )
								.append(
									$( '<span>' )
										.addClass( 'change_project' )
										.text( change.project ),
									$( '<div>' )
										.addClass( 'zuul-patchset-sub' )
										.append( $( '<div>' )
											.addClass( 'zuul-patchset-change' )
											.append( ( !change.url )
												? $( '<span>' ).text( change_id_short )
												: $( '<a>' )
													.attr( 'href', change.url )
													.append( ( isLongHash )
														? $( '<abbr>' )
															.attr( 'title', change.id )
															.text( change_id_short )
														: change_id_short
													)
											)
										)
										.append( $( '<div>' )
											.addClass( 'zuul-patchset-progress' )
											.append( this.change_total_progress_bar( change ) )
										)
								)
							)
							.append( ( change.live === true )
								? $( '<div>' )
									.addClass( 'zuul-patchset-eta' )
									.append(
										$( '<span>' )
											.attr( 'title', 'Remaining Time' ).html( 'ETA: ' + remaining_time ),
										$( '<br>' ),
										$( '<span>' )
											.attr( 'title', 'Elapsed Time' ).html( 'Elapsed: ' + enqueue_time )
									)
								: []
							)
					)
					.append( $( '<ul>' )
						.addClass( 'zuul-patchset-body' )
						.append( change.jobs.map( ( job ) => format.job( job ) ) )
					);
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

				return $( '<span>' )
					.addClass( [ 'zuul-queue-icon', icon_class ] )
					.attr( 'title', icon_title );
			},

			change_with_status_tree: function ( change, change_queue ) {
				const change_width = 360 - ( 16 * change_queue._tree_columns );

				const $change_row = $( '<tr>' );
				for ( let i = 0; i < change_queue._tree_columns; i++ ) {
					const $tree_cell = $( '<td>' )
						.addClass( 'zuul-queue-line' );

					if ( i < change._tree.length && change._tree[ i ] !== null ) {
						$tree_cell.addClass( 'zuul-queue-line--solid' );
					}

					if ( i === change._tree_index ) {
						$tree_cell.append(
							this.change_status_icon( change )
						);
					}
					if ( change._tree_branches.indexOf( i ) !== -1 ) {
						if ( change._tree_branches.indexOf( i ) === change._tree_branches.length - 1 ) {
							// Angle line
							$tree_cell.append( $( '<span>' ).addClass( 'zuul-queue-angle' ) );
						} else {
							// T line
							$tree_cell.append( $( '<span>' ).addClass( 'zuul-queue-tee' ) );
						}
					}
					$change_row.append( $tree_cell );
				}

				$change_row.append( $( '<td>' )
					.css( 'width', change_width + 'px' )
					.addClass( 'zuul-change-cell' )
					.append( this.change_panel( change ) )
				);

				return $( '<table>' )
					.addClass( 'zuul-change-box' )
					.append( $change_row );
			},

			pipeline: function ( pipeline, count ) {
				const pipelineDescs = ( typeof pipeline.description === 'string' )
					? pipeline.description.split( /\r?\n\r?\n/ )
					: [];

				const $html = $( '<div>' )
					// Track change_ids so that when filtering, we can easly hide pipelines
					// that contain no visible matches
					.data( 'change_ids', new Set() )
					.addClass( 'zuul-pipeline' )
					.append( $( '<div>' )
						.addClass( 'zuul-pipeline-header' )
						.append(
							$( '<h3>' )
								.text( pipeline.name )
								.append(
									' ',
									$( '<span>' )
										.addClass( 'zuul-badge zuul-pipeline-count' )
										.text( count )
								),
							pipelineDescs.map( ( descr_part ) =>
								$( '<p>' )
									.addClass( 'zuul-pipeline-desc' )
									.text( descr_part )
									.get( 0 )
							)
						)
					);

				pipeline.change_queues.forEach( ( change_queue ) => {
					change_queue.heads.forEach( ( changes, head_i ) => {
						if ( pipeline.change_queues.length > 1 && head_i === 0 ) {
							const name = change_queue.name;
							const short_name = ( name.length > 32 )
								? name.slice( 0, 32 ) + '…'
								: name;

							$html.append( $( '<p>' )
								.addClass( 'zuul-queue-desc' )
								.text( 'Queue: ' )
								.append( $( '<abbr>' )
									.attr( 'title', name )
									.text( short_name )
								)
							);
						}

						changes.forEach( ( change ) => {
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
				// Toggle showing/hiding the patchset when the header is clicked.

				if ( e.target.nodeName.toLowerCase() === 'a' ) {
					// Ignore clicks from gerrit patch set link
					return;
				}

				// Grab the patchset panel
				const $panel = $( e.target ).parents( '.zuul-change' );
				const $body = $panel.children( '.zuul-patchset-body' );
				$body.toggle( 200 );
				const collapsed_index = collapsed_exceptions.indexOf( $panel.attr( 'id' ) );
				if ( collapsed_index === -1 ) {
					// Currently not an exception, add it to list
					collapsed_exceptions.push( $panel.attr( 'id' ) );
				} else {
					// Currently an except, remove from exceptions
					collapsed_exceptions.splice( collapsed_index, 1 );
				}
			},

			display_patchset: function ( $change_box, animate ) {
				// Determine if we should hide the body/results
				const $panel = $change_box.find( '.zuul-change' );
				const panel_change = $panel.attr( 'id' );
				const $body = $panel.children( '.zuul-patchset-body' );
				const expand_by_default = $( '#expand_by_default' ).prop( 'checked' );
				const panel_project = $panel.find( '.change_project' ).text().toLowerCase();
				const $pipeline = $change_box.parents( '.zuul-pipeline' );
				const panel_pipeline = $pipeline
					.find( '.zuul-pipeline-header > h3' )
					.html()
					.toLowerCase();

				const collapsed_index = collapsed_exceptions.indexOf( panel_change );
				// Expand by default, or is an exception
				const show_body = ( expand_by_default && collapsed_index === -1 ||
					!expand_by_default && collapsed_index !== -1
				);
				// Check if we should hide the whole panel
				let show_panel = true;
				if ( current_filter !== '' ) {
					show_panel = false;
					const filter = current_filter.trim().split( /[\s,]+/ );
					filter.forEach( ( f_val ) => {
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

				if ( show_body ) {
					$body.show( animate );
				} else {
					$body.hide( animate );
				}

				if ( show_panel === true ) {
					$change_box.show( animate );
					$pipeline.data( 'change_ids' ).add( panel_change );
				} else {
					$change_box.hide( animate );
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
				xhr = $.ajax( options.source, {
					dataType: 'text',
					// Enable cache buster query string
					// https://phabricator.wikimedia.org/T94796
					cache: false
				} );

				return xhr
					.then( function ( raw ) {
						if ( last_rendered_raw === raw ) {
							// Don't re-render if response identical to last,
							// to make debugging easier (e.g. when using demo during development)
							return;
						}

						const data = JSON.parse( raw );

						if ( 'message' in data ) {
							$msg.removeClass( 'wm-alert-error' )
								.text( data.message )
								.show();
						} else {
							$msg.empty().hide();
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
						data.pipelines.forEach( ( pipeline ) => {
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

						last_rendered_raw = raw;
					} )
					.catch( function ( jqxhrOrError ) {
						// jqXHR: network failure,
						// Error: JSON syntax error.
						const errMsg = jqxhrOrError.statusText || jqxhrOrError;
						if ( jqxhrOrError.statusText === 'abort' ) {
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
				return $( '<form>' ).attr( 'role', 'form' ).append(
					$( '<label>' )
						.attr( 'for', 'filter_string' )
						.text( 'Filter:' ),
					' ',
					$( '<span>' )
						.addClass( 'wm-input-group--aside' )
						.append(
							$( '<input>' )
								.prop( {
									type: 'text',
									id: 'filter_string',
									className: 'wm-input-text zuul-filter-input',
									// eslint-disable-next-line max-len
									title: 'Any partial match for a gerrit change number, repo name, or pipeline. Multiple terms may be comma-separated.',
									placeholder: 'e.g. 1234 or mediawiki… \u00a0 [ / ]',
									value: current_filter
								} )
								// Listen for 'input' instead of 'change'.
								// The input event will fire as-you-type. The 'change' event
								// only fires when clicking or tabbing to elsewhere on the page.
								.on( 'input', app.handle_filter_change ),
							$( '<span>' )
								.addClass( 'wm-input-icon--clear zuul-filter-clear' )
								.attr( 'id', 'filter_form_clear_box' )
								.attr( 'title', 'Clear filter' )
								.prop( 'hidden', ( current_filter === '' ) )
								.on( 'click', function () {
									$( '#filter_string' ).val( '' ).trigger( 'focus' );
									app.handle_filter_change();
								} )
						),
					' ',
					this.expand_form_group()
				);
			},

			expand_form_group: function () {
				const initial_value = (
					read_persistent_store( 'zuul_expand_by_default' ) === 'true'
				);

				return $( '<label>' )
					.text( ' Expand by default' )
					.prepend( $( '<input>' )
						.attr( 'type', 'checkbox' )
						.attr( 'id', 'expand_by_default' )
						.prop( 'checked', initial_value )
						.on( 'change', this.handle_expand_by_default )
					);
			},

			// Called from zuul.app.js to focus input field when pressing "/" keyboard shortcut.
			focus_filter_input: function () {
				$( '#filter_string' ).trigger( 'focus' );
			},

			handle_filter_change: function () {
				// Update the filter and save it to a cookie
				current_filter = $( '#filter_string' ).val();

				$( '#filter_form_clear_box' ).prop( 'hidden', current_filter === '' );
				$( '.zuul-change-box' ).each( function ( i, element ) {
					format.display_patchset( $( element ), 200 );
				} );
				app.handle_pipeline_visibility();

				update_fragment_filter( current_filter );
			},

			handle_pipeline_visibility: function () {
				if ( current_filter !== '' ) {
					// Hide pipelines without matches when filtering.
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
				pipeline.change_queues.forEach( function ( change_queue ) {
					const tree = [];
					let max_tree_columns = 1;
					const changes = [];
					let last_tree_length = 0;
					change_queue.heads.forEach( function ( head ) {
						head.forEach( function ( change, change_i ) {
							changes[ change.id ] = change;
							change._tree_position = change_i;
						} );
					} );
					change_queue.heads.forEach( function ( head ) {
						head.forEach( function ( change ) {
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
							change.items_behind.forEach( function ( id ) {
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
							change._tree = tree.slice(); // make a copy
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

/**
 * @param {HTMLElement|jQuery|string} container Element or element selector
 * @return {$.zuul}
 */
function zuul_start( container ) {
	const defaultLayout = '<div style="display: none;" class="wm-alert" id="zuul_msg"></div>' +
		'<span class="zuul-badge zuul-spinner">Updating…</span>' +
		'<div id="zuul_controls"></div>' +
		'<div id="zuul_pipelines" class="zuul-pipelines"></div>' +
		'<p>Zuul version: <span id="zuul-version-span"></span></p>' +
		'<p>Last reconfigured: <span id="last-reconfigured-span"></span></p>' +
		'<p>Queue lengths: <span id="zuul_queue_events_num">0</span> events, <span id="zuul_queue_results_num">0</span> results.</p>';

	const demo = location.search.match( /[?&]demo=([^?&]*)/ );
	const source_url = location.search.match( /[?&]source_url=([^?&]*)/ );
	let source = demo ?
		'./status-' + ( demo[ 1 ] || 'basic' ) + '-sample.json' :
		'status.json';
	source = source_url ? source_url[ 1 ] : source;

	const zuul = $.zuul( {
		demo: !!demo,
		source: source
	} );

	const $container = $( container ).addClass( 'zuul-container' ).html( defaultLayout );
	$( '#zuul_controls' ).append( zuul.app.control_form() );

	zuul.jq.on( 'update-start', function () {
		$container.addClass( 'zuul-container-loading' );
	} );
	zuul.jq.on( 'update-end', function () {
		$container.removeClass( 'zuul-container-loading' );
	} );
	zuul.jq.one( 'update-end', function () {
		// Do this asynchronous so that if the first update adds a
		// message, the message will not animate as the content fades in.
		// Instead, it fades with the rest of the content.
		setTimeout( function () {
			// Fade in the content
			$container.addClass( 'zuul-container-ready' );
		} );
	} );

	zuul.app.schedule();

	$( document ).on( {
		visibilitychange: function () {
			if ( document.hiden ) {
				zuul.options.enabled = false;
			} else {
				zuul.options.enabled = true;
				zuul.app.update();
			}
		},
		keydown: function ( e ) {
			if ( e.key === '/' && e.target.nodeName !== 'INPUT' ) {
				// Keyboard shortcut
				zuul.app.focus_filter_input();
				// Don't actually render a slash now
				return false;
			}
		}
	} );

	return zuul;
}
