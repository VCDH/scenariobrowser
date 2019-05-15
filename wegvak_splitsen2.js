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
var polyline_1;
var polyline_2;
var hectopunten_1 = [];
var hectopunten_2 = [];
var hectoactive = true;

function initMap(map_canvas) {
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
	var map = new google.maps.Map(document.getElementById(map_canvas),
	mapOptions);
	
	return map;
}

//onload set polyline if there is one given
function addPolyline(map, polyline, coordsfield) {
	var coords = $('input[name=' + coordsfield + ']').val();
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
		map.fitBounds(bounds);
	}
	//draw polyline
	polyline = new google.maps.Polyline({
		strokeColor: '#F00',
		strokeOpacity: 1,
		strokeWeight: 4,
		editable: true,
		path: path,
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
	
	return polyline;
}

function draw_hecto(map, hectopunten) {
	for (var i = hectopunten.length - 1; i >= 0; i--) {
		hectopunten[i].setMap(null);
		hectopunten.pop();
	}
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
	map_1 = initMap('map-canvas_1');
	map_2 = initMap('map-canvas_2');
	//add hecto
	map_1.addListener('idle', function() { draw_hecto(map_1, hectopunten_1); });
	map_2.addListener('idle', function() { draw_hecto(map_2, hectopunten_2); });
	//add polyline
	polyline_1 = addPolyline(map_1, polyline_1, 'coords_1');
	polyline_2 = addPolyline(map_2, polyline_2, 'coords_2');
	
	//process submit
	$('#submitbutton').click(function(event) {
		/*
		* line
		*/
		var path = polyline_1.getPath();
		//check if there is a line
		if (path.getLength() <= 1) {
			event.preventDefault();
			alert('Teken eerst een lijn voor routedeel 1.');
		}
		//check if there is a name
		else if ($('input[name=naam_1]').val().length <= 1) {
			event.preventDefault();
			alert('Het veld \'naam\' van routedeel 1 kan niet leeg zijn.');
		}
		else {
			$('input[name=coords_1]').val(path.getArray().toString());
		}
		/*
		* line
		*/
		var path = polyline_2.getPath();
		//check if there is a line
		if (path.getLength() <= 1) {
			event.preventDefault();
			alert('Teken eerst een lijn voor routedeel 2.');
		}
		//check if there is a name
		else if ($('input[name=naam_2]').val().length <= 1) {
			event.preventDefault();
			alert('Het veld \'naam\' van routedeel 2 kan niet leeg zijn.');
		}
		else {
			$('input[name=coords_2]').val(path.getArray().toString());
		}
	});
});
