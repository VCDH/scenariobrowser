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
var polylines = [];
var hectopunten = [];
var drips = [];
var hectoactive = false;
var dripsactive = false;
var dripsvisible = false;
var scnactive = false;

var linecolordefault = '#C00';
var linecolornoscn = '#999';
var linecolorhover = '#C0C';
var linecolorenddefault = '#00C';
var lineicondefault = [{
	icon: { //end
		path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
		anchor: new google.maps.Point(0,1),
		fillOpacity: 1,
		strokeWeight: 1
	},
	offset: '100%'
},{
	icon: {  //start
		path: google.maps.SymbolPath.CIRCLE,
		fillColor: linecolorenddefault,
		fillOpacity: 1,
		strokeColor: linecolorenddefault,
		strokeWeight: 4
	},
	offset: '0%'
}];
var lineiconhover = [{
	icon: { //end
		path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
		anchor: new google.maps.Point(0,1),
		fillColor: linecolorhover,
		fillOpacity: 1,
		strokeWeight: 1
	},
	offset: '100%',
	repeat: '50px'
},{
	icon: {  //start
		path: google.maps.SymbolPath.CIRCLE,
		fillColor: linecolorhover,
		fillOpacity: 1,
		strokeColor: linecolorhover,
		strokeWeight: 4
	},
	offset: '0%'
}];	

var infowindow =  new google.maps.InfoWindow({
	content: ''
});

function initMap() {
	var mapOptions = {
		center: new google.maps.LatLng(52.071, 4.3),
		zoom: 13,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDefaultUI: false,
		scrollwheel: true,
		draggable: true,
		clickableIcons: false,
		disableDoubleClickZoom: true
	};
	map = new google.maps.Map(document.getElementById("map-canvas"),
	mapOptions);
	
}

$( document ).ready(function() {
	initMap();
	
	//set position from cookie, if any
	if (typeof(Cookies.get('scn_map')) !== 'undefined') {
		var cookievalues = Cookies.getJSON('scn_map');
		map.setCenter(cookievalues[0]);
		map.setZoom(cookievalues[1]);
		hectoactive = cookievalues[2];
		dripsactive = cookievalues[3];
		scnactive = cookievalues[4];
		google.maps.event.addListenerOnce(map, 'idle', function() { 
			draw_drips();
		});
	}
	
	//add hecto, drips, set cookie with position and view settings
	map.addListener('idle', function() { 
		draw_hecto(); 
		showhide_drips(); 
		set_map_cookie();
	});
	
	//get and set center location
	var centerlatlng = getUrlVars()['latlng'];
	if (typeof(centerlatlng) !== 'undefined') {
		centerlatlng = centerlatlng.match(/^(\d+\.\d+),(\d+\.\d+)$/);
		if (centerlatlng != null) {
			var mapcenter = new google.maps.LatLng(parseFloat(centerlatlng[1]), parseFloat(centerlatlng[2]));
			map.setCenter(mapcenter);
			map.setZoom(18);
			hectoactive = true;
		}
	}
	//add segments
	function draw_segments() {
		//unset previous segments
		for (var i = polylines.length - 1; i >= 0; i--) {
			polylines[i].setMap(null);
			polylines.splice(i, 1);
		}
		
		$.getJSON( "ajax.php", {type: "wvk", all: scnactive} )
		.done(function( json ) {
			var bounds = new google.maps.LatLngBounds();
			var lookat = false;
			var lookatid = parseInt(getUrlVars()['lookat']);
			
			$.each( json, function(i, value) {
				value.id = parseInt(value.id);
				var linecolor = ((value.scn == '1') ? linecolordefault : linecolornoscn);
				var zindex = ((value.scn == '1') ? 2 : 1);
				//add polyline
				var polyline = new google.maps.Polyline({
					map: map,
					strokeColor: linecolor,
					fillColor: linecolor,
					defaultcolor: linecolor,
					strokeOpacity: 1,
					strokeWeight: 4,
					icons: lineicondefault,
					zIndex: zindex,
					defaultzindex: zindex
				});
				//set path from coordinates
				var path = [];
				for (var i = 0; i < value.coords.length; i = i+2) {
					var point = new google.maps.LatLng(value.coords[i], value.coords[i+1]);
					path.push(point);
					//set bounds for zoom later
					if (value.id == lookatid) {
						bounds.extend(point);
						lookat = true;
					}
				}
				polyline.setPath(path);
				//set color
				if (value.id == lookatid) {
					polyline.setOptions({strokeColor: linecolorhover, icons: lineiconhover, zIndex: 2});
				}
				//infowindow
				function open_polyline_infowindow(map, id, latlng) {
					infowindow.setContent('Laden...');
					infowindow.setOptions({position: latlng });
					infowindow.open(map);
					$.get( "ajax.php", {type: "infowindow", id: id, all: scnactive} )
					.done(function( data ) {
						infowindow.setContent(data);
					})
					.fail(function() {
						infowindow.setContent('Kan data niet ophalen');
					});
				}
				//bind info window or selection mode
				google.maps.event.addListener(polyline, 'click', function(event) {
					open_polyline_infowindow(map, value.id, event.latLng);
				});
				//autoopen infowindow if requested
				if ((lookat == true) && (value.id == lookatid)) {
					if (typeof(centerlatlng) !== 'undefined') {
						open_polyline_infowindow(map, value.id, mapcenter);
					}
					else {
						open_polyline_infowindow(map, value.id, path[Math.floor(path.length/2)]);
					}
				}
				//bind mouseover color change
				google.maps.event.addListener(polyline, 'mouseover', function(event) {
					polyline.setOptions({strokeColor: linecolorhover, icons: lineiconhover, zIndex: 3});
				});
				google.maps.event.addListener(polyline, 'mouseout', function(event) {
					polyline.setOptions({strokeColor: this.defaultcolor, icons: lineicondefault, zIndex: this.defaultzindex});
				});
				polylines.push(polyline);
			});
			if (lookat == true) {
				map.fitBounds(bounds);
				google.maps.event.addListenerOnce(map, 'idle', function() { 
					if (map.getZoom() > 16) {
						map.setZoom(16);
					}
				});
			}
		});
		//set button state
		if (scnactive == true) {
			$('#scnactive span img').attr({src: 'images/scntype_all.png', title: 'verberg wegwerkzaamheden- en evenementenscenario\'s'});
		}
		else {
			$('#scnactive span img').attr({src: 'images/scntype_ltd.png', title: 'toon alle scenario\'s'});
		}
	}
	draw_segments();
	
	function draw_hecto() {
		for (var i = 0; i < hectopunten.length; i++) {
			hectopunten[i].setMap(null);
		}
		hectopunten = [];
		if ((map.getZoom() >= 11) && (hectoactive == true)) {
			//draw hecto
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
		//set button state
		if (hectoactive == true) {
			$('#hectoactive span').addClass('selected');
		}
		else {
			$('#hectoactive span').removeClass('selected');
		}
	}
	
	function draw_drips() {
		for (var i = 0; i < drips.length; i++) {
			drips[i].setMap(null);
		}
		drips = [];
		if (dripsactive == true) {
			//set button state
			$('#dripsactive span').addClass('selected');
			//draw DRIPs
			$.getJSON( "ajax.php", {type: 'drips'} )
			.done(function( json ) {
				$.each( json, function(i, value) {
					value.id = parseInt(value.id);
					//opacity
					if (value.status != 'bestaand') {
						var opacity = 0.3;
					}
					else {
						var opacity = 1;
					}
					//colours
					switch (value.owner) {
						case 'gemeente Den Haag' : strokecolour = '#155429'; fillcolour = '#fff'; break;
						case 'Rijkswaterstaat ZWN' : strokecolour = '#000'; fillcolour = '#c9f'; break;
						case 'provincie Zuid-Holland' : strokecolour = '#000'; fillcolour = '#c00'; break;
						case 'gemeente Rotterdam' : strokecolour = '#000'; fillcolour = '#9AC61E'; break;
						case 'Z\'meer' : strokecolour = '#0072b9'; fillcolour = '#ffe00f'; break;
						default : strokecolour = '#000'; fillcolour = '#666'; 
					}
					//icon variations
					if (value.defaulttext == '1') {
						//icon with two lines
						icon_path = 'M 0,-4 2,-1 4,-1 4,4 -4,4 -4,-1 -2,-1 z M 2.5,2.5 -2.5,2.5 M 2.5,0.5 -2.5,0.5';
					}
					else {
						//regular icon
						icon_path = 'M 0,-4 2,-1 4,-1 4,4 -4,4 -4,-1 -2,-1 z';
					}
					var rotation = parseInt(value.rotation);
					//add marker
					var marker = new google.maps.Marker({
						ref_id: value.id,
						map: map,
						position: new google.maps.LatLng(value.latitude, value.longitude),
						title: value.name,
						icon: {
							path: icon_path,
							scale: 2,
							fillColor: fillcolour,
							strokeColor: strokecolour,
							strokeWeight: 2,
							rotation: rotation,
							fillOpacity: opacity,
							strokeOpacity: opacity,
						}
					});
					
					//bind info window or selection mode
					google.maps.event.addListener(marker, 'click', function() {
						//info window
						infowindow.setContent('Laden...');
						infowindow.open(map, marker);
						$.get( "ajax.php", {type: "dripinfo", id: value.id} )
						.done(function( data ) {
							infowindow.setContent(data);
							
						})
						.fail(function() {
							infowindow.setContent('Kan data niet ophalen');
						});
					});
					drips.push(marker);
				});
			});
		}
		else {
			$('#dripsactive span').removeClass('selected');
		}
	}
	function showhide_drips() {
		if ((map.getZoom() >= 11) && (dripsvisible == false)) {
			for (var i = 0; i < drips.length; i++) {
				drips[i].setMap(map);
			}
			dripsvisible = true;
		}
		else if ((map.getZoom() < 11) && (dripsvisible == true)) {
			for (var i = 0; i < drips.length; i++) {
				drips[i].setMap(null);
			}
			dripsvisible = false;
		}
	}
	
	$('#hectoactive span').click(function() {
		if (hectoactive !== true) {
			hectoactive = true;
			//zoom to visible level
			if (map.getZoom() < 11) {
				map.setZoom(11);
			}
		}
		else {
			hectoactive = false;
		}
		draw_hecto();
		set_map_cookie();
	});
	
	$('#dripsactive span').click(function() {
		if (dripsactive !== true) {
			dripsactive = true;
			//zoom to visible level
			if (map.getZoom() < 11) {
				map.setZoom(11);
			}
		}
		else {
			dripsactive = false;
		}
		draw_drips();
		set_map_cookie();
	});
	
	$('#scnactive span').click(function() {
		if (scnactive !== true) {
			scnactive = true;
			
		}
		else {
			scnactive = false;
		}
		draw_segments();
		set_map_cookie();
	});
	
	function set_map_cookie() {
		Cookies.set('scn_map', [map.getCenter(), map.getZoom(), hectoactive, dripsactive, scnactive], {expires: 1000});
	}
});

// Read a page's GET URL variables and return them as an associative array.
//from http://jquery-howto.blogspot.nl/2009/09/get-url-parameters-values-with-jquery.html
function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
