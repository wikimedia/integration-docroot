// Client script for Zuul status page (wmf version)
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

/*jshint camelcase:false */
(function ($) {
	var $container, $msg, $msgWrap, $indicator, $jq, $graphs,
		prevHtml, xhr, zuul,
		demo = location.search.match(/[?&]demo=([^?&]*)/),
		source = demo ?
			'./sample-status-' + (demo[1] || 'basic') + '.json' :
			'/zuul/status.json',
		nonce = $.now();

	/**
	 * Escape a string for HTML. Converts special characters to HTML entities.
	 *
	 * Based on mediawiki-core's mw.html.escape utility.
	 *
	 * @param {string} s The string to escape
	 * @return {string}
	 */
	function htmlEscape(s) {
		return s.replace(/['"<>&]/g, function (s) {
			switch (s) {
			case '\'':
				return '&#039;';
			case '"':
				return '&quot;';
			case '<':
				return '&lt;';
			case '>':
				return '&gt;';
			case '&':
				return '&amp;';
			}
		});
	}

	zuul = {
		enabled: true,

		schedule: function () {
			if (!zuul.enabled) {
				setTimeout(zuul.schedule, 5000);
				return;
			}
			zuul.update().complete(function () {
				setTimeout(zuul.schedule, 5000);
			});
		},

		/** @return {jQuery.Promise} */
		update: function () {
			// Cancel the previous update if it hasn't completed yet.
			if (xhr) {
				xhr.abort();
			}

			zuul.emit('update-start');

			xhr = $.ajax({
				url: source,
				dataType: 'json',
				cache: false
			})
			.done(function (data) {
				var html = '', last_reconfigured,
					total_queued = 0, total_completed = 0,  total_running = 0;
				data = data || {};

				if ('message' in data) {
					$msg.html(data.message);
					$msgWrap.removeClass('zuul-msg-wrap-off');
				} else {
					$msg.empty();
					$msgWrap.addClass('zuul-msg-wrap-off');
				}

				$.each(data.pipelines, function (i, pipeline) {
					html += zuul.format.pipeline(pipeline);
				});

				// Only re-parse the DOM if we have to
				if (html !== prevHtml) {
					prevHtml = html;
					$('#zuul-pipelines').html(html);
				}

				$.each(data.pipelines, function (i, pipeline) {
					$.each(pipeline.change_queues, function (queueNum, changeQueue) {
						$.each(changeQueue.heads, function (headNum, changes) {
							$.each(changes, function (changeNum, change) {
								$.each(change.jobs, function (jobNum, job) {
									if (job.url === null) {
										total_queued++;
									} else if ( job.result === null) {
										total_running++;
									} else {
										total_completed++;
									}
								});
							});
						});
					});
				});

				$('#zuul-eventqueue-length').text(
					data.trigger_event_queue ? data.trigger_event_queue.length : '0'
				);
				$('#zuul-resulteventqueue-length').text(
					data.result_event_queue ? data.result_event_queue.length : '0'
				);

				$('#zuul-total-jobs-running').text(total_running);
				$('#zuul-total-jobs-completed').text(total_completed);
				$('#zuul-total-jobs-queued').text(total_queued);

				// Borrowed from OpenStack
				$('#zuul-version').text(
					data.zuul_version ? data.zuul_version : 'unknown'
				);
				if ('last_reconfigured' in data) {
					last_reconfigured = new Date(data.last_reconfigured);
					$('#zuul-last-reconfigured').text(
						last_reconfigured.toString()
					);
				}

			})
			.fail(function (err, jqXHR, errMsg) {
				$msg.text(source + ': ' + errMsg).show();
				$msgWrap.removeClass('zuul-msg-wrap-off');
			})
			.complete(function () {
				xhr = undefined;
				zuul.emit('update-end');
			});

			return xhr;
		},

		format: {
			change: function (change) {
				var id = change.id,
					url = change.url,
					// In CSS .zuul-change:target should be enough, but because
					// browsers only evaluate that on load, we still need to manually
					// check it for future refreshes. Use a class name instead.
					html = '<div class="well well-small zuul-change' +
						(location.hash === '#change-' + id ? ' zuul-change-target' : '') +
						'" id="change-' + htmlEscape(id) + '">' +
						'<ul class="nav nav-list">';

				html += '<li class="nav-header">' + change.project;
				if (id.length === 40) {
					id = id.substr(0, 7);
				}
				html += ' <span class="zuul-change-id">';
				if (url !== null) {
					html += '<a href="' + url + '">';
				}
				html += id;
				if (url !== null) {
					html += '</a>';
				}
				html += '</span></li>';

				html += '<li>ETA: ' + zuul.format.time(change.remaining_time, true);
				html += ', queued ' + zuul.format.time(Date.now() - change.enqueue_time, true) + ' ago</li>';
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

					html += job.url !== null ?
						'<a href="' + job.url + '" class="zuul-change-job-link">' :
						'<span class="zuul-change-job-link">';

					html += job.name;
					if (job.voting === false) {
						html += ' <span class="muted">(non-voting)</span>';
					}

					if (job.result === null && job.url !== null) {
						html += zuul.format.progress(job.elapsed_time, job.remaining_time);
					} else {
						// job completed
						html += ' <span class="' + resultClass + '">' + result + '</span>';
					}

					html += job.url !== null ? '</a>' : '</span>';


					html += '</li>'; // .zuul-change-job
				});

				html += '</ul></div>';
				return html;
			},

			// From Openstack format_time()
			// Passed via jshint
			time: function (ms, words) {
				if (ms === null) {
					return 'unknown';
				}
				var r = '',
					seconds = (+ms) / 1000,
					minutes = Math.floor(seconds / 60),
					hours = Math.floor(minutes / 60);
				seconds = Math.floor(seconds % 60);
				minutes = Math.floor(minutes % 60);
				if (words) {
					if (hours) {
						r += hours;
						r += ' hr ';
					}
					r += minutes + ' min';
				} else {
					if (hours < 10) { r += '0'; }
					r += hours + ':';
					if (minutes < 10) { r += '0'; }
					r += minutes + ':';
					if (seconds < 10) { r += '0'; }
					r += seconds;
				}
				return r;
			},

			// From Openstack format_progress()
			// Passed via jshint
			progress: function (elapsed, remaining) {
				var total,
					r = '';

				if (remaining !== null) {
					total = elapsed + remaining;
				} else {
					total = null;
				}
				r = '<progress class="zuul-change-progress" title="' +
					zuul.format.time(elapsed, false) + ' elapsed, ' +
					zuul.format.time(remaining, false) + ' remaining" ' +
					'value="' + elapsed + '" max="' + total + '">in progress</progress>';
				return r;
			},

			pipeline: function (pipeline) {
				var html = '<div class="zuul-pipeline span4"><h3>' +
					pipeline.name + '</h3>';
				if (typeof pipeline.description === 'string') {
					html += '<p><small>' + pipeline.description + '</small></p>';
				}

				$.each(pipeline.change_queues, function (queueNum, changeQueue) {
					$.each(changeQueue.heads, function (headNum, changes) {
						if (pipeline.change_queues.length > 1 && headNum === 0) {
							var name = changeQueue.name;
							html += '<p>Queue: <abbr title="' + name + '">';
							if (name.length > 32) {
								name = name.substr(0, 32) + '...';
							}
							html += name + '</abbr></p>';
						}
						$.each(changes, function (changeNum, change) {
							// If there are multiple changes in the same head it means they're connected
							if (changeNum > 0) {
								html += '<div class="zuul-change-arrow">&uarr;</div>';
							}
							html += zuul.format.change(change);
						});
					});
				});

				html += '</div>';
				return html;
			}
		},

		emit: function () {
			$jq.trigger.apply($jq, arguments);
			return this;
		},
		on: function () {
			$jq.on.apply($jq, arguments);
			return this;
		},
		one: function () {
			$jq.one.apply($jq, arguments);
			return this;
		}
	};

	$jq = $(zuul);

	$jq.one('update-start', function () {
		// Store original image urls of graphs
		$graphs = $('.graphite-graph').each(function (i, node) {
			$.data(node, 'src', node.src);
		});
	});

	$jq.on('update-start', function () {
		$container.addClass('zuul-container-loading');

		$indicator
			.addClass('zuul-spinner-on')
			.html('updating <i class="icon-refresh"></i>');
	});

	$jq.on('update-end', function () {
		// Refresh graphs
		$graphs.attr('src', function () {
			return $.data(this, 'src') + '&_=' + ( nonce++ );
		} );

		$container.removeClass('zuul-container-loading');
		setTimeout(function () {
			// Delay so that the updating state is visible for at least half a second
			$indicator
				.removeClass('zuul-spinner-on')
				.html('idle <i class="icon-time"></i>');
		}, 500);
	});

	$jq.one('update-end', function () {
		// Do this asynchronous so that if the first update adds a message, it will not animate
		// while we fade in the content. Instead it simply appears with the rest of the content.
		setTimeout(function () {
			// Fade in the content
			$container.addClass('zuul-container-ready');
		});
	});

	$(function ($) {
		$indicator = $('<span class="btn pull-right zuul-spinner">idle <i class="icon-time"></i></span>');
		$msg = $('<div class="zuul-msg alert alert-error"></div>');
		$msgWrap = $msg.wrap('<div class="zuul-msg-wrap zuul-msg-wrap-off"></div>').parent();
		$container = $('#zuul-container').prepend($msgWrap, $indicator);

		zuul.schedule();

		$(document).on({
			'show.visibility': function () {
				zuul.enabled = true;
				zuul.update();
			},
			'hide.visibility': function () {
				zuul.enabled = false;
			}
		});
	});
}(jQuery));
