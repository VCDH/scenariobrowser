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
$( document ).ready(function() {
	//get map canvi, set map and load map objects
	$('div.map-canvas').each( function () {
		//initialize map
		//map_divs.push(this.id);
		var map = initmap(this.id);
		//maps.push(map);
		var ids = this.id.split('_');
		//infowindow
		var infowindow =  new google.maps.InfoWindow({
			content: ''
		});
		//load objects for map
		$.getJSON( 'ajax.php', {type: 'mapobjects', scn: ids[1], sck: ids[2]} )
		.done(function( json ) {
			//draw each object on map
			var bounds = new google.maps.LatLngBounds();
			$.each( json, function(i, value) {
				//lijnen
				if (value.type == 'line') {
					var polyline = new google.maps.Polyline({
						map: map,
						strokeColor: value.color,
						strokeOpacity: 1,
						strokeWeight: 4,
						icons: [{
							icon: { //end
								path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
								anchor: new google.maps.Point(0,1),
								fillColor: value.color,
								fillOpacity: 1,
								strokeWeight: 1
							},
							offset: '100%'
						}],
						zIndex: 1
					});
					//set path from coordinates
					var path = [];
					for (var i = 0; i < value.coords.length; i = i+2) {
						var point = new google.maps.LatLng(value.coords[i], value.coords[i+1]);
						path.push(point);
						bounds.extend(point);
					}
					polyline.setPath(path);
				}
				else if (value.type == 'drip') {
					var icon_path = 'M 0,-4 2,-1 4,-1 4,4 -4,4 -4,-1 -2,-1 z';
					var position = new google.maps.LatLng(value.latitude, value.longitude);
					bounds.extend(position);
					//label position
					var rotation = parseInt(value.rotation);
					if ((rotation >= 45) && (rotation < 135)) {
						var labelanchor = new google.maps.Point(114, -10);
						var labelclass = 'dripmaplabelcenter';
					}
					else if ((rotation >= 135) && (rotation < 225)) {
						var labelanchor = new google.maps.Point(214, 8);
						var labelclass = 'dripmaplabelleft';
					}
					else if ((rotation >= 225) && (rotation < 315)) {
						var labelanchor = new google.maps.Point(114, 28);
						var labelclass = 'dripmaplabelcenter';
					}
					else {
						var labelanchor = new google.maps.Point(-14, 8);
						var labelclass = 'dripmaplabel';
					}
					var drip = new MarkerWithLabel({
						map: map,
						position: position,
						title: value.name,
						icon: {
							path: icon_path,
							scale: 2,
							fillColor: value.fillcolour,
							strokeColor: value.strokecolour,
							strokeWeight: 2,
							rotation: parseInt(value.rotation),
							fillOpacity: value.opacity,
							strokeOpacity: value.opacity,
						},
						labelContent: value.name,
						labelAnchor: labelanchor,
						labelClass: labelclass,
						labelVisible: true
					});
					drip.addListener('click', function() {
						//info window
						infowindow.setContent('Laden...');
						infowindow.setOptions({position: position });
						infowindow.open(map);
						$.get( "ajax.php", {type: "schakelingdripafbeelding", id: value.id} )
						.done(function( data ) {
							infowindow.setContent(data);
						})
						.fail(function() {
							infowindow.setContent('Kan data niet ophalen');
						});
					});
					/*drip.addListener('mouseout', function() { */
                    map.addListener('mouseout', function() {
                        //info window
                        infowindow.close(map);
                    });
				}
			});
			map.fitBounds(bounds);
		});
	});
});

function initmap(id) {
	var mapOptions = {
		center: new google.maps.LatLng(52.071, 4.3),
		zoom: 13,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDefaultUI: false,
		scrollwheel: false,
		draggable: true,
		clickableIcons: false,
		disableDoubleClickZoom: true
	};
	var map = new google.maps.Map(document.getElementById(id),
	mapOptions);
	map.addListener('click', function() {
		map.setOptions({scrollwheel: true});
	});
	map.addListener('mouseout', function() {
		map.setOptions({scrollwheel: false});
	});
	return map;
}
