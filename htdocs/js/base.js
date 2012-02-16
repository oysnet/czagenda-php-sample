var map, layer;
function initMap() {
	if (typeof(map) !== 'undefined') {
		return;
	}
	map = new OpenLayers.Map('map');
	layer = new OpenLayers.Layer.OSM("OSM");
	map.addLayer(layer);
	map.updateSize();

	var markers = new OpenLayers.Layer.Markers("Markers");
	map.addLayer(markers);

	var lonlat = new OpenLayers.LonLat($('#localization_longitude').val(),
			$('#localization_latitude').val()).transform(
			new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());

	var size = new OpenLayers.Size(21, 25);
	var offset = new OpenLayers.Pixel(-(size.w / 2), -size.h);
	var icon = new OpenLayers.Icon(
			'http://www.openlayers.org/dev/img/marker.png', size, offset);
	markers.addMarker(new OpenLayers.Marker(lonlat, icon));

	map.setCenter(lonlat, 12);
}
$(function() {

	var tab_index = 0;

	var tabs_html = "<div class='tabs'><ul>";
	$('#maintags .tab>legend').each(function(i) {
		tabs_html += '<li><a href="#tabs-' + tab_index + '-' + i + '">'
				+ $(this).text() + '</a></li>';
		$(this).remove();
	});
	tabs_html += "</ul>"
	$('#maintags .tab').each(function(i) {
		tabs_html += '<div id="tabs-' + tab_index + '-' + i + '" class="'
				+ $(this).attr('class') + '">' + $(this).html() + '</div>';
	});
	$('#maintags').html(tabs_html + '</div>');

	tab_index += 1;
	var contacts_tabs_html = "<div class='tabs'><ul>";
	$('#contacts legend').each(function(i) {
		contacts_tabs_html += '<li><a href="#tabs-' + tab_index + '-' + i
				+ '">' + $(this).text() + '</a></li>';
		$(this).remove();
	});
	contacts_tabs_html += "</ul>"
	$('#contacts fieldset').each(function(i) {
		contacts_tabs_html += '<div id="tabs-' + tab_index + '-' + i
				+ '" class="' + $(this).attr('class') + '">' + $(this).html()
				+ '</div>';
	});
	$('#contacts').html(contacts_tabs_html + '</div>');

	$(".tabs").tabs();
	// $(".tabs-contacts").tabs();

	$("input:submit").button();
	$(".tabs").bind("tabsshow", function(event, ui) {
				if ($(ui.panel).children('#map').length > 0) {
					initMap();
				}
			});

	var dates = $("#start_time, #end_time").datepicker({
		defaultDate : "+1d",
		changeMonth : true,
		numberOfMonths : 1,
		dateFormat : 'yy-mm-dd',
		onSelect : function(selectedDate) {
			var option = this.id == "end_time" ? "maxDate" : "minDate", instance = $(this)
					.data("datepicker"), date = $.datepicker.parseDate(
					instance.settings.dateFormat
							|| $.datepicker._defaults.dateFormat, selectedDate,
					instance.settings);
			dates.not(this).datepicker("option", option, date);
		}
	});

});
