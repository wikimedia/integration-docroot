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

/*jshint eqnull:true */
(function ($) {
    var updateCount = 0,
        enableStatusUpdates = true;

    function formatPipeline(data) {
        var html = '<div class="pipeline"><h3 class="subhead">' +
            data.name + '</h3>';
        if (data.description != null) {
            html += '<p>' + data.description + '</p>';
        }

        $.each(data.change_queues, function (queueNum, changeQueue) {
            $.each(changeQueue.heads, function (headNum, head) {
                if (data.change_queues.length > 1 && headNum === 0) {
                    html += '<div> Change queue: ';

                    var name = changeQueue.name;
                    html += '<a title="' + name + '">';
                    if (name.length > 32) {
                        name = name.substr(0, 32) + '...';
                    }
                    html += name + '</a></div>';
                }
                $.each(head, function (changeNum, change) {
                    if (changeNum > 0) {
                        html += '<div class="arrow">&uarr;</div>';
                    }
                    html += formatChange(change);
                });
            });
        });

        html += '</div>';
        return html;
    }

    function formatChange(change) {
        var html = '<div class="change"><div class="header">',
            id = change.id,
            url = change.url;

        html += '<span class="project">' + change.project + '</span>';
        if (id.length === 40) {
            id = id.substr(0, 7);
        }
        html += '<span class="changeid">';
        if (url != null) {
            html += '<a href="' + url + '">';
        }
        html += id;
        if (url != null) {
            html += '</a>';
        }
        html += '</span></div><div class="jobs">';

        $.each(change.jobs, function (i, job) {
            var result = job.result,
                resultClass = 'result';
            if (result == null) {
                if (job.url != null) {
                    result = 'in progress';
                } else {
                    result = 'queued';
                }
            } else if (result === 'SUCCESS') {
                resultClass += ' result_success';
            } else if (result === 'FAILURE') {
                resultClass += ' result_failure';
            } else if (result === 'LOST') {
                resultClass += ' result_unstable';
            } else if (result === 'UNSTABLE') {
                resultClass += ' result_unstable';
            }
            html += '<span class="job">';
            if (job.url != null) {
                html += '<a href="' + job.url + '">';
            }
            html += job.name;
            if (job.url != null) {
                html += '</a>';
            }
            html += ': <span class="' + resultClass + '">' + result + '</span>';
            if (job.voting === false) {
                html += ' (non-voting)';
            }
            html += '</span>';
        });

        html += '</div></div>';
        return html;
    }

    function updateTimeout() {
        if (!enableStatusUpdates) {
            setTimeout(updateTimeout, 5000);
            return;
        }

        updateCount += 1;

        update();
        /* Only update graphs every minute */
        if (updateCount > 11) {
            updateCount = 0;
            updateGraphs();
        }

        setTimeout(updateTimeout, 5000);
    }

    function update() {

        $.getJSON('/zuul/status.json', function (data) {
            data = data || {};
            var html = '';
            if ('message' in data) {
                $('#message')
                    .attr('class', 'alertbox')
                    .html(data.message);
            } else {
                $('#message')
                    .removeClass('alertbox')
                    .empty();
            }

            html += '<br style="clear:both"/>';

            $.each(data.pipelines, function (i, pipeline) {
                html = html + formatPipeline(pipeline);
            });

            html += '<br style="clear:both"/>';
            $('#pipeline-container').html(html);

            $('#trigger_event_queue_length').text(
                 (data.trigger_event_queue || []).length
            );
            $('#result_event_queue_length').text(
                 (data.result_event_queue || []).length
            );

        });
    }

    function updateGraphs() {
        $('.graph').each(function (i, img) {
            var newimg = new Image(),
                parts = img.src.split('#');
            newimg.src = parts[0] + '#' + new Date().getTime();
            $(newimg).load(function () {
                img.src = newimg.src;
            });
        });
    }

    $(document).ready(function () {
        updateTimeout();

        $(document).on({
            'show.visibility': function () {
                enableStatusUpdates = true;
                update();
                updateGraphs();
            },
            'hide.visibility': function () {
                enableStatusUpdates = false;
            }
        });

    });

}(jQuery));
