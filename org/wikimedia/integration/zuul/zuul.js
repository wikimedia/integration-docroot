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

/* eslint max-len: ["warn", { "code": 120, "ignoreTemplateLiterals": true }] */
/* global Mustache:false */
/* exported zuul_start */

( function () {
	'use strict';

	const fragment_filter_prefix = '#q=';

	// read filter from fragment
	function read_fragment_filter() {
		const hash = location.hash;
		if ( !hash.includes( fragment_filter_prefix ) ) {
			return '';
		}
		return hash.slice( fragment_filter_prefix.length );
	}

	function update_fragment_filter( value ) {
		if ( value !== '' ) {
			history.replaceState( null, '', fragment_filter_prefix + value );
		} else {
			// Prefer not to leave an empty "#" or "#q=".
			// If the browser doesn't have the URL API yet, don't bother with workarounds
			if ( window.URL ) {
				const obj = new URL( location.href );
				obj.hash = '';
				history.replaceState( null, '', obj.toString() );
			} else {
				history.replaceState( null, '', '#' );
			}
		}
	}

	// remember for this domain, across browser tabs and restarts.
	function set_persistent_store( name, value ) {
		try {
			localStorage.setItem( name, value );
		} catch ( e ) {
			// Disallowed (disabled, or out of quota).
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

	/**
	 * @param {HTMLElement} element
	 * @param {Object} options
	 * @param {Object[]} options.keyframes Required
	 */
	function animate( element, options ) {
		options = Object.assign( {
			duration: options.duration || 0,
			easing: 'ease-in-out',
			fill: 'forwards',
			after() {}
		}, options );
		const anim = element.animate( options.keyframes, options );
		anim.finished.then( () => {
			options.after();
			anim.cancel();
		} );
	}

	function showAnimate( element ) {
		element.hidden = false;
		animate( element, {
			duration: 200,
			keyframes: [ { transform: 'scaleY(0)' }, { transform: 'scaleY(1)' } ]
		} );
	}

	function hideAnimate( element ) {
		animate( element, {
			duration: 200,
			keyframes: [ { transform: 'scaleY(0)' } ],
			after() { element.hidden = true; }
		} );
	}

	function parseHTML( htmlString ) {
		const node = document.createElement( 'div' );
		node.innerHTML = htmlString;
		const ret = node.firstElementChild;
		// Detach
		ret.remove();
		return ret;
	}

	const container_template = `
		<div hidden class="wm-alert zuul-msg"></div>
		<span class="zuul-badge zuul-spinner">Updating…</span>
		<form role="form" class="zuul-controls">
			<label class="wm-input-group--aside">Filter: <input type="text" class="wm-input-text zuul-filter-input" title="Any partial match for a gerrit change number, repo name, or pipeline. Multiple terms may be comma-separated." placeholder="e.g. 1234 or mediawiki… &nbsp; [ / ]" value="{{filter_value}}"><span class="wm-input-icon--clear zuul-filter-clear" title="Clear filter" {{^filter_value}}hidden{{/filter_value}}></span></label>
			<label><input type="checkbox" class="zuul-control-expand" {{#expandByDefault}}checked{{/expandByDefault}}> Expand by default</label>
		</form>
		<div class="zuul-pipelines"></div>
		<p>Zuul version: <span class="zuul-info--version"></span></p>
		<p>Last reconfigured: <span class="zuul-info--reconfigured"></span></p>
		<p>Queue lengths: <span class="zuul-info--queue-events">0</span> events, <span class="zuul-info--queue-results">0</span> results.</p>`;

	const pipeline_template = `<div class="zuul-pipeline">
		<div class="zuul-pipeline-header">
			<h3>{{pipeline.name}} <span class="zuul-badge zuul-pipeline-count">{{count}}</span></h3>
			{{#pipeline_descriptions}}
			<p class="zuul-pipeline-desc">{{.}}</p>
			{{/pipeline_descriptions}}
		</div>
		{{#queues_and_changes}}
			{{#queue}}
			<p class="zuul-queue-desc">Queue: <abbr title="{{name}}">{{short_name}}</abbr></p>
			{{/queue}}
			{{#change_box_data}}
			{{> change_box_template}}
			{{/change_box_data}}
		{{/queues_and_changes}}
	</div>`;

	const change_box_template = `<table class="zuul-change-box" data-changeid="{{change.id}}" {{^visibility.showPanel}}hidden{{/visibility.showPanel}}>
		<tr>
			{{#change_tree_cells}}
			<td class="zuul-queue-line {{solid_class}}">
				{{#icon}}
				<span class="zuul-queue-icon {{icon_class}}" title="{{icon_title}}"></span>
				{{/icon}}
				{{#branch_class}}
				<span class="{{branch_class}}"></span>
				{{/branch_class}}
			</td>
			{{/change_tree_cells}}
			<td class="zuul-change-cell" style="width: {{change_width}}px;">
				{{#change_panel_data}}
				{{> change_template}}
				{{/change_panel_data}}
			</td>
		</tr>
	</table>`;

	const change_template = `<div class="zuul-change" id="{{panel_id}}">
		<div class="zuul-patchset-header">
			<div class="zuul-patchset-header-left">
				<span class="change_project">{{change.project}}</span>
				<div class="zuul-patchset-sub">
					<div class="zuul-patchset-change">
						{{#change.url}}
						<a href="{{change.url}}">{{change_id_short}}</a>
						{{/change.url}}
						{{^change.url}}
						<span>{{change_id_short}}</span>
						{{/change.url}}
					</div>
					<div class="zuul-patchset-progress">
						<div class="zuul-job-result--progress zuul-change-total-result">
							{{#change.jobs}}{{#_progressbar_total}}<span class="zuul-progressbar" data-result="{{_progressbar_total}}" title="{{name}}" style="width: {{job_percent}}%;"></span>{{/_progressbar_total}}{{/change.jobs}}
						</div>
					</div>
				</div>
			</div>
			{{#change.live}}
			<div class="zuul-patchset-eta">
				<span title="Remaining Time">ETA: {{remaining_time}}</span><br>
				<span title="Elapsed Time">Elapsed: <span class="{{ellapsed_time.text_class}}">{{ellapsed_time.text}}</span></span>
			</div>
			{{/change.live}}
		</div>
		<ul class="zuul-patchset-body" {{^visibility.showBody}}hidden{{/visibility.showBody}}>
			{{#change.jobs}}
			{{> job_template}}
			{{/change.jobs}}
		</ul>
	</div>`;

	const job_template = `<li class="zuul-change-job">
		{{#url}}
		<a class="zuul-job-name" href="{{url}}">{{_display_name}}</a>
		{{/url}}
		{{^url}}
		<span class="zuul-job-name">{{_display_name}}</span>
		{{/url}}

		{{#_progressbar}}
		<span class="zuul-job-result zuul-job-result--progress"><span class="{{progress_class}}" role="progressbar" aria-valuenow="{{progress_percent}}" aria-valuemin="0" aria-valuemax="100" style="width: {{progress_width}}%;"></span></span>
		{{/_progressbar}}
		{{^_progressbar}}
		<span class="zuul-job-result zuul-job-result--label" data-result="{{_result_normalized}}">{{_result_normalized}}</span>
		{{/_progressbar}}
	</li>`;

	function Zuul( options ) {
		options = Object.assign( {
			demo: false,
			enabled: true,
			source: 'status.json',
			container: '#zuul_container',
			onUpdateStart() {
			},
			onUpdateEnd() {
			}
		}, options );

		const collapsedExceptions = new Set();
		const pipelineStates = [];
		let domOut = null;
		let current_filter = read_fragment_filter();
		let expandByDefault = read_persistent_store( 'zuul_expand_by_default' ) === 'true';
		let last_rendered_raw;

		const format = {
			enqueue_time: function ( ms ) {
				const hours = 60 * 60 * 1000;
				const delta = options.demo ?
					// In demo mode, ignore the far-past timestamps in the sample data,
					// and instead pretend jobs started 0min-5h ago
					( Math.floor( Math.random() * 5 * 60 ) * 60 * 1000 ) :
					Date.now() - ms;
				const text = format.time( delta, true );
				let text_class = '';
				if ( delta > ( 4 * hours ) ) {
					text_class = 'wm-text-error';
				} else if ( delta > ( 2 * hours ) ) {
					text_class = 'wm-text-warning';
				}
				return { text, text_class };
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

			change_panel_data: function ( change ) {
				const panel_id = ( change.id || 'unknown' ).replace( ',', '_' );

				// Zuul events may respond to a commit hash (eg. tag) without a Gerrit change number
				const isLongHash = /^[0-9a-f]{40}$/.test( change.id || '' );
				const change_id_short = isLongHash ? change.id.slice( 0, 7 ) : ( change.id || 'NA' );

				const remaining_time = change.live && format.time( change.remaining_time, true );
				const ellapsed_time = change.live && format.enqueue_time( change.enqueue_time );

				// Each job gets an equal proportion in the combined "total" progress bar
				const job_percent = Math.floor( 100 / change.jobs.length );
				change.jobs.forEach( ( job ) => {
					const result = job.result ? job.result.toLowerCase() : ( job.url ? 'in progress' : 'queued' );

					// In the combined progressbar, let the unfilled (right) side of the progress
					// bar represent jobs that are still waiting in the queue. That is, draw no
					// progress bar segmen for them (null).
					job._progressbar_total = ( result === 'queued' ? null : result );
					job._display_name = job.name + ( job.voting === false ? ' (non\u00a0voting)' : '' );
					job._result_normalized = result;

					if ( result === 'in progress' ) {
						let progress_percent = 100 * (
							job.elapsed_time / ( job.elapsed_time + job.remaining_time ) );
						let progress_width = progress_percent;
						let progress_class = 'zuul-progressbar';

						if ( !progress_percent ) {
							progress_percent = 0;
							progress_width = 100;
							progress_class += ' zuul-progressbar--animated';
						}

						job._progressbar = {
							progress_percent,
							progress_width,
							progress_class
						};
					}
				} );

				return {
					panel_id,
					change_id_short,
					job_percent,
					remaining_time,
					ellapsed_time
				};
			},

			change_box_data: function ( change, change_queue ) {
				const change_width = 360 - ( 16 * change_queue._tree_columns );

				let icon_class = 'zuul-queue-icon--success';
				let icon_title = 'Succeeding';
				if ( !change.active ) {
					icon_class = 'zuul-queue-icon--waiting';
					icon_title = 'Waiting until closer to head of queue to start jobs';
				} else if ( !change.live ) {
					icon_class = 'zuul-queue-icon--waiting';
					icon_title = 'Dependent change required for testing';
				} else if ( change.failing_reasons && change.failing_reasons.length ) {
					icon_class = 'zuul-queue-icon--error';
					icon_title = 'Failing because ' + change.failing_reasons.join( ', ' );
				}

				const change_tree_cells = [];
				for ( let i = 0; i < change_queue._tree_columns; i++ ) {

					// Start or continue drawing a line down toward the current change box
					const draw_line = ( i < change._tree.length && change._tree[ i ] !== null );
					const is_self = ( i === change._tree_index );
					const is_branch_point = change._tree_branches.includes( i );
					const branch_class = is_branch_point && (
						( change._tree_branches.indexOf( i ) === change._tree_branches.length - 1 )
							// Angle line
							? 'zuul-queue-angle'
							// T line
							: 'zuul-queue-tee'
					);

					change_tree_cells.push( {
						solid_class: draw_line ? 'zuul-queue-line--solid' : null,
						icon: is_self ? { icon_class, icon_title } : null,
						branch_class
					} );
				}

				return {
					change,
					change_width,
					change_tree_cells,
					change_panel_data: format.change_panel_data( change )
				};
			},

			pipeline: function ( pipeline, count ) {
				const pipeline_descriptions = ( typeof pipeline.description === 'string' )
					? pipeline.description.split( /\r?\n\r?\n/ )
					: [];

				// Track which change boxes are visible so that that when filtering,
				// we can easily hide pipelines that contain no visible matches
				const state = {
					filterable: {},
					changeBoxes: new Set(),
					visible: new Set(),
					element: null
				};

				const queues_and_changes = [];
				pipeline.change_queues.forEach( ( change_queue ) => {
					change_queue.heads.forEach( ( changes, head_i ) => {
						if ( pipeline.change_queues.length > 1 && head_i === 0 ) {
							const name = change_queue.name;
							const short_name = ( name.length > 32 )
								? name.slice( 0, 32 ) + '…'
								: name;
							queues_and_changes.push( { queue: { name, short_name } } );
						}

						changes.forEach( ( change ) => {
							state.filterable[ change.id ] = [ pipeline.name, change.project, change.id ]
								.join( ' ' ).toLowerCase();

							const visibility = format.getChangeVisibility( change.id, state );
							if ( visibility.showPanel ) {
								state.visible.add( change.id );
							}
							queues_and_changes.push( {
								visibility,
								change_box_data: format.change_box_data( change, change_queue )
							} );
						} );
					} );
				} );

				const pipeline_html = Mustache.render( pipeline_template,
					{
						pipeline,
						count,
						pipeline_descriptions,
						queues_and_changes
					},
					{
						change_box_template,
						change_template,
						job_template
					}
				);

				const pipelinesElement = parseHTML( pipeline_html );
				pipelinesElement.addEventListener( 'click', ( e ) => {
					if ( e.target.closest( '.zuul-patchset-header' ) ) {
						format.toggle_patchset( e );
					}
				} );

				pipelinesElement.querySelectorAll( '.zuul-change-box' ).forEach( ( changeBox ) => {
					state.changeBoxes.add( changeBox );
				} );

				state.element = pipelinesElement;
				pipelineStates.push( state );

				return pipelinesElement;
			},

			// Toggle showing/hiding the patchset when the header is clicked.
			toggle_patchset: function ( e ) {
				if ( e.target.nodeName === 'A' ) {
					// Ignore clicks from gerrit patch set link
					return;
				}

				// Find the outer change box
				const changeBox = e.target.closest( '.zuul-change-box' );
				const changeID = changeBox.dataset.changeid;
				const changeBody = changeBox.querySelector( '.zuul-patchset-body' );

				if ( changeBody.hidden ) {
					showAnimate( changeBody );
				} else {
					hideAnimate( changeBody );
				}

				if ( !collapsedExceptions.has( changeID ) ) {
					// Currently not an exception, add it to list
					collapsedExceptions.add( changeID );
				} else {
					// Currently an except, remove from exceptions
					collapsedExceptions.delete( changeID );
				}
			},

			getChangeVisibility: function ( changeID, pipelineState ) {
				// Determine if we should hide the body/results
				const isCollapsedExempt = collapsedExceptions.has( changeID );
				// Expand by default, or is an exception
				const showBody = ( expandByDefault && !isCollapsedExempt ||
					!expandByDefault && isCollapsedExempt
				);

				// Show panel if no filters, or at least one filter matches one field
				const filterable = pipelineState.filterable[ changeID ];
				const showPanel = ( current_filter === '' ||
					current_filter.toLowerCase().split( /[\s,]+/ ).some( ( f_val ) => {
						return f_val !== '' && filterable.includes( f_val );
					} )
				);

				return { showBody, showPanel };
			},

			display_patchset: function ( changeBox, pipelineState ) {
				const changeID = changeBox.dataset.changeid;
				const { showBody, showPanel } = format.getChangeVisibility( changeID, pipelineState );

				const changeBody = changeBox.querySelector( '.zuul-patchset-body' );
				changeBody.hidden = !showBody;
				changeBox.hidden = !showPanel;
				if ( showPanel ) {
					pipelineState.visible.add( changeID );
				} else {
					pipelineState.visible.delete( changeID );
				}
			}
		};

		const app = {
			render: function () {
				const container = typeof options.container === 'string'
					? document.querySelector( options.container )
					: options.container;

				// Fill the container with the status page layout,
				// and render the form based on current URL/cookies
				container.classList.add( 'zuul-container' );
				container.innerHTML = Mustache.render( container_template, {
					filter_value: current_filter,
					expandByDefault
				} );
				domOut = {
					msg: container.querySelector( '.zuul-msg' ),
					filterInput: container.querySelector( '.zuul-filter-input' ),
					filterClear: container.querySelector( '.zuul-filter-clear' ),
					controlExpand: container.querySelector( '.zuul-control-expand' ),
					pipelines: container.querySelector( '.zuul-pipelines' ),
					infoVersion: container.querySelector( '.zuul-info--version' ),
					infoReconfigured: container.querySelector( '.zuul-info--reconfigured' ),
					infoQueueEvents: container.querySelector( '.zuul-info--queue-events' ),
					infoQueueResults: container.querySelector( '.zuul-info--queue-results' )
				};

				// Listen for 'input' instead of 'change'.
				// The input event will fire as-you-type. The 'change' event
				// only fires when clicking or tabbing to elsewhere on the page.
				domOut.filterInput.addEventListener( 'input', app.handle_filter_change );
				domOut.filterClear.addEventListener( 'click', () => {
					domOut.filterInput.value = '';
					domOut.filterInput.focus();
					app.handle_filter_change();
				} );
				domOut.controlExpand.addEventListener( 'change', app.handle_expand_by_default );
			},

			schedule: function () {
				if ( !options.enabled ) {
					return;
				}
				app.update().finally( function () {
					setTimeout( function () {
						app.schedule();
					}, 5000 );
				} );
			},

			/** @return {jQuery.Promise} */
			update: function () {
				options.onUpdateStart();

				// Bypass cache
				// https://phabricator.wikimedia.org/T94796
				return fetch( options.source, { cache: 'no-store' } )
					.then( function ( resp ) {
						if ( !resp.ok ) {
							throw new Error( 'HTTP ' + resp.status );
						}
						return resp.text();
					} )
					.then( function ( raw ) {
						if ( last_rendered_raw === raw ) {
							// Don't re-render if response identical to last,
							// to make debugging easier (e.g. when using demo during development)
							return;
						}

						const data = JSON.parse( raw );

						if ( 'message' in data ) {
							domOut.msg.classList.remove( 'wm-alert-error' );
							domOut.msg.textContent = data.message;
							domOut.msg.hidden = false;
						} else {
							domOut.msg.hidden = true;
						}

						if ( 'zuul_version' in data ) {
							domOut.infoVersion.textContent = data.zuul_version;
						}
						if ( 'last_reconfigured' in data ) {
							const last_reconfigured = new Date( data.last_reconfigured );
							domOut.infoReconfigured.textContent = last_reconfigured.toString();
						}

						domOut.pipelines.innerHTML = '';
						pipelineStates.length = 0;

						data.pipelines.forEach( ( pipeline ) => {
							const count = app.create_tree( pipeline );
							domOut.pipelines.append( format.pipeline( pipeline, count ) );
						} );
						app.handle_pipeline_visibility();

						domOut.infoQueueEvents.textContent =
							( data.trigger_event_queue ? data.trigger_event_queue.length : '0' );
						domOut.infoQueueResults.textContent =
							( data.result_event_queue ? data.result_event_queue.length : '0' );

						last_rendered_raw = raw;
					} )
					.catch( function ( jqxhrOrError ) {
						// jqXHR: network failure. Error: JSON syntax error.
						const errMsg = jqxhrOrError.statusText || jqxhrOrError;
						domOut.msg.classList.add( 'wm-alert-error' );
						domOut.msg.textContent = options.source + ': ' + errMsg;
						domOut.msg.hidden = false;
					} )
					.finally( function () {
						options.onUpdateEnd();
					} );
			},

			// Called from zuul.app.js to focus input field when pressing "/" keyboard shortcut.
			focus_filter_input: function () {
				domOut.filterInput.focus();
			},

			// Read and apply the filter, and update the URL fragment
			handle_filter_change: function () {
				current_filter = domOut.filterInput.value;

				for ( const pipelineState of pipelineStates ) {
					for ( const changeBox of pipelineState.changeBoxes ) {
						format.display_patchset( changeBox, pipelineState );
					}
				}

				app.handle_pipeline_visibility();
				domOut.filterClear.hidden = ( current_filter === '' );

				update_fragment_filter( current_filter );
			},

			// When filtering, hide pipelines that contain zero matches
			handle_pipeline_visibility: function () {
				for ( const pipelineState of pipelineStates ) {
					pipelineState.element.hidden =
						( current_filter !== '' && !pipelineState.visible.size );
				}
			},

			// Handle toggling "Expand by default"
			handle_expand_by_default: function ( e ) {
				expandByDefault = e.target.checked;
				set_persistent_store( 'zuul_expand_by_default', String( expandByDefault ) );

				// Expand or collapse all change boxes
				collapsedExceptions.clear();
				app.handle_filter_change();
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
							if ( change.live ) {
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

		this.options = options;
		this.app = app;
	}

	window.Zuul = Zuul;
}() );

/**
 * @param {string} containerSelector CSS selector
 * @return {Zuul}
 */
function zuul_start( containerSelector ) {
	/* global Zuul */

	const demo = location.search.match( /[?&]demo=([^?&]*)/ );
	const source = demo ?
		'./status-' + ( demo[ 1 ] || 'basic' ) + '-sample.json' :
		'status.json';
	const container = document.querySelector( containerSelector );

	const zuul = new Zuul( {
		demo: !!demo,
		source: source,
		container: container,
		onUpdateStart() {
			container.classList.add( 'zuul-container-loading' );
		},
		onUpdateEnd() {
			container.classList.remove( 'zuul-container-loading' );
		}
	} );

	zuul.app.render();
	zuul.app.schedule();

	document.addEventListener( 'visibilitychange', () => {
		if ( document.hidden ) {
			zuul.options.enabled = false;
		} else {
			zuul.options.enabled = true;
			zuul.app.schedule();
		}
	} );
	document.addEventListener( 'keydown', ( e ) => {
		if ( e.key === '/' && e.target.nodeName !== 'INPUT' ) {
			// Keyboard shortcut
			zuul.app.focus_filter_input();
			// Don't actually insert a slash now
			return false;
		}
	} );

	return zuul;
}
