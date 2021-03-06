/*
 	scenariobrowser - viewer en editor voor verkeersmanagementscenario's
    Copyright (C) 2016-2019 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
var map;
var polyline;
var hectopunten = [];
var hectoactive = true;

function initMap() {
	var position = new google.maps.LatLng(52.071, 4.3);
	
	var mapOptions = {
		center: position,
		zoom: 12,
		mapTypeId: google.maps.MapTypeId.MAP,
		disableDefaultUI: false,
		scrollwheel: true,
		draggable: true,
		clickableIcons: false,
		disableDoubleClickZoom: true
	};
	map = new google.maps.Map(document.getElementById("map-canvas"),
	mapOptions);
	
	//set position from cookie, if any
	if (typeof(Cookies.get('scn_map')) !== 'undefined') {
		var cookievalues = Cookies.getJSON('scn_map');
		map.setCenter(cookievalues[0]);
		map.setZoom(cookievalues[1]);
	}
	
	//add polyline
	polyline = new google.maps.Polyline({
		strokeColor: '#F00',
		strokeOpacity: 1,
		strokeWeight: 4,
		editable: true,
		icons: [{
			icon: { 
				path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
				anchor: new google.maps.Point(0,1),
				fillColor: '#F00',
				fillOpacity: 1,
				strokeWeight: 1,
			},
			offset: '100%'
		},{
			icon: { 
				path: google.maps.SymbolPath.CIRCLE,
				fillColor: '#F00',
				fillOpacity: 1,
				strokeWeight: 4,
			},
			offset: '0%'
		}]
	});
	polyline.setMap(map);
	setLineCoordinates();
	
	//event listener to add points to line
	map.addListener('click', function(evt){
		var path = polyline.getPath();
		//append coordinates
		path.push(evt.latLng);
	});
	
	//event listener to remove points from line on rightclick
	map.addListener('rightclick', function(evt){
		var path = polyline.getPath();
		//append coordinates
		path.pop();
	});
	
}

//onload set polyline if there is one given
function setLineCoordinates() {
	var coords = $('input[name=coords]').val();
	if (coords.length > 0) {
		coords = coords.substr(1, coords.length - 2).split('),(');
		var path = [];
		var bounds = new google.maps.LatLngBounds();
		for (var i = 0; i < coords.length; i++) {
			var coords_this = coords[i].split(', ');
			var point = new google.maps.LatLng(coords_this[0], coords_this[1]);
			path.push(point);
			bounds.extend(point);
		}
		polyline.setPath(path);
		map.fitBounds(bounds);
	}
}

function draw_hecto() {
	for (var i = 0; i < hectopunten.length; i++) {
		hectopunten[i].setMap(null);
	}
	hectopunten = [];
	if ((map.getZoom() >= 11) && (hectoactive == true)) {
		$.getJSON( "ajax.php", {type: "hecto", bounds: map.getBounds().toString(), zoom: map.getZoom().toString()} )
		.done(function( json ) {
			
			$.each( json, function(i, value) {
				//add marker
				var milemarker = new MarkerWithLabel({
					map: map,
					position: new google.maps.LatLng(value[0], value[1]),
					title: value[2],
					icon: 'milemarker.png',
					labelContent: value[2],
					labelAnchor: new google.maps.Point(-8, 8),
					labelClass: 'markerlabel',
					labelVisible: true
				});
				hectopunten.push(milemarker);
			});
		});
	}
}

$( document ).ready(function() {
	initMap();
	//add hecto
	map.addListener('idle', function() { draw_hecto(); });
	
	//accordion
	$("ul.scenariolijst").accordion({
		collapsible: true,
		active: false,
		heightStyle: "content"
	});
	
	//scenarios onload handler
	var scn_selected = $('input[name=scenarios]').val();
	if (scn_selected.length > 0) {
		scn_selected = scn_selected.split(',');
		//set button state
		for (var i = 0; i < scn_selected.length; i++) {
			$('li#scn_' + scn_selected[i]).addClass('selected');
		}
		
	}
	else {
		scn_selected = [];
	}
	//set legend
	$('#legend_scn').html('Gekoppelde scenario\'s (' + scn_selected.length + ')');
	//button onclick handler
	$('li.scn').click( function() {
		//get scenario id
		var id = $(this).attr('id');
		id = id.substr(4);
		//change button state
		$(this).toggleClass('selected');
		//add or remove from array
		var i = scn_selected.indexOf(id);
		if (i >= 0) {
			scn_selected.splice(i, 1);
		}
		else {
			scn_selected.push(id);
		}
		//update legend count
		$('#legend_scn').html('Gekoppelde scenario\'s (' + scn_selected.length + ')');
	});
	
	//process submit
	$('#submitbutton').click(function(event) {
		/*
		* scenarios
		*/
		$('input[name=scenarios]').val(scn_selected.join(','));
		/*
		* line
		*/
		var path = polyline.getPath();
		//check if there is a line
		if (path.getLength() <= 1) {
			event.preventDefault();
			alert('Teken eerst een lijn.');
		}
		//check if there is a name
		else if ($('input[name=naam]').val().length <= 1) {
			event.preventDefault();
			alert('Het veld \'naam\' kan niet leeg zijn.');
		}
		else {
			$('input[name=coords]').val(path.getArray().toString());
		}
	});
});
