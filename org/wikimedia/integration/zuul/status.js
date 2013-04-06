// Client script for Zuul status page.
//
// Copyright 2012 OpenStack Foundation
// Copyright 2013 Timo Tijhof
// Copyright 2013 Wikimedia Foundation
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

/*jshint eqnull:true, camelcase:false */
(function ($) {
    var $container, $msg, $msgWrap, $indicator, prevHtml,
        updateCount = 0,
        enableStatusUpdates = true,
        demo = location.search.match(/[?&]demo=([^?&]*)/),
        source = demo ? './sample-status-' + (demo[1] || 'basic') + '.json' : '/zuul/status.json';

    /**
     * @param {Object} pipeline
     * @return {string} html
     */
    function formatPipeline(pipeline) {
        var html = '<div class="zuul-pipeline span4"><h3>' +
            pipeline.name + '</h3>';
        if (pipeline.description != null) {
            html += '<p><small>' + pipeline.description + '</small></p>';
        }

        $.each(pipeline.change_queues, function (queueNum, changeQueue) {
            $.each(changeQueue.heads, function (headNum, changes) {
                if (pipeline.change_queues.length > 1 && headNum === 0) {
                    html += '<p>Queue: ';

                    var name = changeQueue.name;
                    html += '<abbr title="' + name + '">';
                    if (name.length > 32) {
                        name = name.substr(0, 32) + '...';
                    }
                    html += name + '</abbr></p>';
                }
                $.each(changes, function (changeNum, change) {
                    // If there are multiple changes in the same head it means
                    // they're connected (dependant?)
                    if (changeNum > 0) {
                        html += '<div class="zuul-change-arrow">&uarr;</div>';
                    }
                    html += formatChange(change);
                });
            });
        });

        html += '</div>';
        return html;
    }

    /**
     * @param {Object} change
     * @return {string} html
     */
    function formatChange(change) {
        var html = '<div class="well well-small zuul-change"><ul class="nav nav-list">',
            id = change.id,
            url = change.url;

        html += '<li class="nav-header">' + change.project;
        if (id.length === 40) {
            id = id.substr(0, 7);
        }
        html += '<span class="zuul-change-id">';
        if (url != null) {
            html += '<a href="' + url + '">';
        }
        html += id;
        if (url != null) {
            html += '</a>';
        }
        html += '</span></li>';

        $.each(change.jobs, function (i, job) {
            var result = job.result ? job.result.toLowerCase() : null,
                resultClass = 'zuul-result label';
            if (result === null) {
                result = job.url ? 'in progress' : 'queued';
            }
            switch (result) {
            case 'success':
                resultClass += ' label-success';
                break;
            case 'failure':
                resultClass += ' label-important';
                break;
            case 'lost':
            case 'unstable':
                resultClass += ' label-warning';
                break;
            }
            html += '<li class="zuul-change-job">';
            if (job.url != null) {
                html += '<a href="' + job.url + '">';
            }
            html += job.name;
            html += ' <span class="' + resultClass + '">' + result + '</span>';
            if (job.voting === false) {
                html += ' <span class="muted">(non-voting)</span>';
            }
            if (job.url != null) {
                html += '</a>';
            }
            html += '</li>';
        });

        html += '</ul></div>';
        return html;
    }

    function scheduleUpdate() {
        if (!enableStatusUpdates) {
            setTimeout(scheduleUpdate, 5000);
            return;
        }

        update().complete(function () {

            updateCount += 1;

            // Only update graphs every minute
            if (updateCount > 12) {
                updateCount = 1;
                purgeGraphs();
            }

            setTimeout(scheduleUpdate, 5000);
        });
    }

    function updateStart() {
        $container.addClass('zuul-container-loading');
        $indicator.addClass('zuul-spinner-on');
    }

    function updateEnd() {
        $container.removeClass('zuul-container-loading');
        setTimeout(function () {
            $indicator.removeClass('zuul-spinner-on');
        }, 550);
        if (updateCount === 0) {
            // Remove this asynchronous so that if the first
            // update adds a message, the message does not animate
            // but appears instantly with the rest of the content.
            setTimeout(function () {
                $container.addClass('zuul-container-ready');
            });
        }
    }

    /**
     * @return {jQuery.Promise}
     */
    function update() {
        updateStart();
        return $.ajax({
            url: source,
            dataType: 'json',
            cache: false
        })
        .done(function (data) {
            var html = '';
            data = data || {};

            if ('message' in data) {
                $msg.html(data.message);
                $msgWrap.removeClass('zuul-msg-wrap-off');
            } else {
                $msg.empty();
                $msgWrap.addClass('zuul-msg-wrap-off');
            }

            $.each(data.pipelines, function (i, pipeline) {
                html += formatPipeline(pipeline);
            });

            // Only re-parse the DOM if we have to
            if (html !== prevHtml) {
                prevHtml = html;
                $('#zuul-pipelines').html(html);
            }

            $('#zuul-eventqueue-length').text(
                 data.trigger_event_queue ? data.trigger_event_queue.length : '?'
            );
            $('#zuul-resulteventqueue-length').text(
                 data.result_event_queue ? data.result_event_queue.length : '?'
            );
            updateEnd();

        })
        .fail(function (err, jqXHR, errMsg) {
            $msg.text(source + ': ' + errMsg).show();
            $msgWrap.removeClass('zuul-msg-wrap-off');
            updateEnd();
        });
    }

    function purgeGraphs() {
        $('.graph').each(function (i, img) {
            var newimg = new Image(),
                parts = img.src.split('#');
            newimg.src = parts[0] + '#' + new Date().getTime();
            $(newimg).load(function () {
                img.src = newimg.src;
            });
        });
    }

    $(function ($) {
        $container = $('#zuul-container');
        $indicator = $('<span class="btn pull-right zuul-spinner">updating <i class="icon-refresh"></i></span>')
            .prependTo($container);
        $msg = $('<div class="zuul-msg alert alert-error"></div>');
        $msgWrap = $msg
            .wrap('<div class="zuul-msg-wrap zuul-msg-wrap-off"></div>')
            .parent()
            .prependTo($container);

        scheduleUpdate();

        $(document).on({
            'show.visibility': function () {
                enableStatusUpdates = true;
                update();
                purgeGraphs();
            },
            'hide.visibility': function () {
                enableStatusUpdates = false;
            }
        });

    });

}(jQuery));
