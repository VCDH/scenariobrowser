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
	//add hecto
	map.addListener('idle', function() { draw_hecto(); });
	
	//set position from cookie, if any
	if (typeof(Cookies.get('scn_map')) !== 'undefined') {
		var cookievalues = Cookies.getJSON('scn_map');
		map.setCenter(cookievalues[0]);
		map.setZoom(cookievalues[1]);
	}
	drawPolylines();
}

//teken routedeel als individuele lijnstukken, om zo index van te splitsen lijnstuk te kunnen achterhalen
function drawPolylines() {
	var coords = $('input[name=coords]').val();
	if (coords.length > 0) {
		coords = coords.substr(1, coords.length - 2).split('),(');
		var bounds = new google.maps.LatLngBounds();
		
		var starticon = [{
			icon: { 
				path: google.maps.SymbolPath.CIRCLE,
				fillColor: '#F00',
				fillOpacity: 1,
				strokeWeight: 4,
			},
			offset: '0%'
		}];
		var endicon = [{
			icon: { 
				path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
				anchor: new google.maps.Point(0,1),
				fillColor: '#F00',
				fillOpacity: 1,
				strokeWeight: 1,
			},
			offset: '100%'
		}];
		
		for (var i = 0; i < coords.length - 1; i++) {
			var path = [];
			for (var a = 0; a <= 1; a++) {
				var coords_this = coords[i+a].split(', ');
				var point = new google.maps.LatLng(coords_this[0], coords_this[1]);
				path.push(point);
				bounds.extend(point);
			}
			
			//add polyline
			var polyline = new google.maps.Polyline({
				x_index: i,
				strokeColor: '#F00',
				strokeOpacity: 1,
				strokeWeight: 4,
				editable: false,
				path: path
			});
			polyline.setMap(map);
			if (i == 0) {
				polyline.setOptions({icons: starticon});
			}
			else if (i == (coords.length - 2)) {
				polyline.setOptions({icons: endicon});
			}
			
			//event listener to set split point and submit form
			polyline.addListener('click', function(evt){
				$('input[name=splitatcoords]').val(evt.latLng.toString());
				$('input[name=splitatindex]').val(this.x_index);
				$('input[name=coords]').remove();
				$('form').submit();
			});
		}
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
});
