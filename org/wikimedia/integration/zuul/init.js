// Initialise Wikimedia's Zuul status dashbaord
//
// Copyright 2015 Timo Tijhof
// Copyright 2015 Wikimedia Foundation
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

/*global zuul_start */
(function ($) {

    /**
     * Override zuul_build_dom() to adapt to our embedded page layout.
     * - Strip h1 as our Page system has one already.
     * - Strip div.container as our Page system has one already. Adding
     *   another would make the page narrower due to double padding.
     */
    function zuul_build_dom_wmf(container) {
        var default_layout = '<div class="zuul-container" id="zuul-container">' +
            '<div style="display: none;" class="alert" id="zuul_msg"></div>' +
            '<button class="btn pull-right zuul-spinner">updating <span class="glyphicon glyphicon-refresh"></span></button>' +
            '<p>Queue lengths: <span id="zuul_queue_events_num">0</span> events, <span id="zuul_queue_results_num">0</span> results.</p>' +
            '<div id="zuul_controls"></div>' +
            '<div id="zuul_pipelines" class="row"></div>' +
            '<p>Zuul version: <span id="zuul-version-span"></span></p>' +
            '<p>Last reconfigured: <span id="last-reconfigured-span"></span></p>' +
            '</div>';

        $(function ($) {
            $(container).html(default_layout);
        });
    }

    // Enable cache buster query string
    // https://phabricator.wikimedia.org/T94796
    $.ajaxSetup({ cache: false });

    zuul_build_dom_wmf('#zuul_wrapper');
    zuul_start($);

}(jQuery));
