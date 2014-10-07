/*global Wikimedia */
(function ($) {
	var menu = Wikimedia.monitoringMenu,
	select = $('<select>').on('change', function () {
		location.hash = '#h-' + this.value;
	});
	$.each(menu, function (i, item) {
		select.append($('<option>').val(item.id).text(item.label));
	});
	$('#contents').append(
		'<p>Contents: </p>',
		select
	);
}(jQuery));
