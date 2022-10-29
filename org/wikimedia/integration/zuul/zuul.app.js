// Client script for Zuul status page
//
// Copyright 2013 OpenStack Foundation
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

/* exported zuul_build_dom, zuul_start */

/**
 * @param {HTMLElement} container
 */
function zuul_build_dom( container ) {
	const defaultLayout = '<div class="zuul-container" id="zuul-container">' +
		'<div style="display: none;" class="alert" id="zuul_msg"></div>' +
		'<button class="btn btn-default pull-right zuul-spinner">updating <span class="glyphicon glyphicon-refresh"></span></button>' +
		'<p>Queue lengths: <span id="zuul_queue_events_num">0</span> events, <span id="zuul_queue_results_num">0</span> results.</p>' +
		'<div id="zuul_controls"></div>' +
		'<div id="zuul_pipelines" class="row"></div>' +
		'<p>Zuul version: <span id="zuul-version-span"></span></p>' +
		'<p>Last reconfigured: <span id="last-reconfigured-span"></span></p>' +
		'</div>';

	$( function () {
		$( container ).html( defaultLayout );
	} );
}

/**
 * @return {$.zuul}
 */
function zuul_start() {
	let $container;
	const demo = location.search.match( /[?&]demo=([^?&]*)/ );
	const source_url = location.search.match( /[?&]source_url=([^?&]*)/ );
	let source = demo ?
		'./status-' + ( demo[ 1 ] || 'basic' ) + '.json-sample' :
		'status.json';
	source = source_url ? source_url[ 1 ] : source;

	const zuul = $.zuul( {
		source: source
	} );

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

	$( function () {
		$container = $( '#zuul-container' );
		$( '#zuul_controls' ).append( zuul.app.control_form() );

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
	} );

	return zuul;
}
