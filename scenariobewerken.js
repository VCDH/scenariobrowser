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

var linecolordefault = '#999';
var linecolorhover = '#C0C';
var linecolorselected = '#C00';
var linecolorenddefault = '#666';
var linecolorendselected = '#600';
var lineicondefault = [{
	icon: { //end
		path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
		anchor: new google.maps.Point(0,1),
		fillColor: linecolordefault,
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
var lineiconselected = [{
	icon: { //end
		path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
		anchor: new google.maps.Point(0,1),
		fillColor: linecolorselected,
		fillOpacity: 1,
		strokeWeight: 1
	},
	offset: '100%'
},{
	icon: {  //start
		path: google.maps.SymbolPath.CIRCLE,
		fillColor: linecolorendselected,
		fillOpacity: 1,
		strokeColor: linecolorendselected,
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
	
	//set position from cookie, if any
	if (typeof(Cookies.get('scn_map')) !== 'undefined') {
		var cookievalues = Cookies.getJSON('scn_map');
		map.setCenter(cookievalues[0]);
	}
}

$( document ).ready(function() {
	initMap();
	
	//segments onload handler
	var wvk_selected = $('input[name=wegvakken]').val();
	if (wvk_selected.length > 0) {
		wvk_selected = wvk_selected.split(',');
		//set button state
		for (var i = 0; i < wvk_selected.length; i++) {
			$('li#wvk_' + wvk_selected[i]).addClass('selected');
		}
	}
	else {
		wvk_selected = [];
	}
	//set legend
	$('#legend_wvk').html('Gekoppelde stremmingsroutedelen (' + wvk_selected.length + ')');
	
	//add segments
	$.getJSON( "ajax.php", {type: "wvk"} )
	.done(function( json ) {
		$.each( json, function(i, value) {
			//add polyline
			var polyline = new google.maps.Polyline({
				xid: value.id,
				map: map,
				strokeColor: linecolordefault,
				strokeOpacity: 1,
				strokeWeight: 4,
				icons: lineicondefault,
				zIndex: 1
			});
			//set path from coordinates
			var path = [];
			for (var i = 0; i < value.coords.length; i = i+2) {
				var point = new google.maps.LatLng(value.coords[i], value.coords[i+1]);
				path.push(point);
			}
			polyline.setPath(path);
			//populate list
			$('<li class="scn" id="wvk_' + value.id + '">' + value.naam + '</li>').appendTo('#wegvakkenlijst').on('click', function() { toggle_wvk(value.id); } );
			//set selected state
			if (wvk_selected.indexOf(value.id) >= 0) {
				$('#wvk_' + value.id).addClass('selected');
				polyline.setOptions({strokeColor: linecolorselected, icons: lineiconselected, zIndex: 2});
			}
			
			//onclick handler
			google.maps.event.addListener(polyline, 'click', function() {
				toggle_wvk(value.id);
			});
			//bind mouseover color change
			google.maps.event.addListener(polyline, 'mouseover', function() {
				polyline.setOptions({strokeColor: linecolorhover, icons: lineiconhover, zIndex: 3});
			});
			google.maps.event.addListener(polyline, 'mouseout', function() {
				if (wvk_selected.indexOf(value.id) >= 0) {
					polyline.setOptions({strokeColor: linecolorselected, icons: lineiconselected, zIndex: 2});
				}
				else {
					polyline.setOptions({strokeColor: linecolordefault, icons: lineicondefault, zIndex: 1});
				}
			});
			polylines.push(polyline);
		});
	});
	
	//accordion
	$("ul.scenariolijst").accordion({
		collapsible: true,
		active: false,
		heightStyle: "content"
	});
	
	//change segment selected state
	function toggle_wvk(id) {
		//change button state
		$('#wvk_' + id).toggleClass('selected');
		//add or remove from array
		var i = wvk_selected.indexOf(id);
		if (i >= 0) {
			wvk_selected.splice(i, 1);
			//remove line color
			for (l = 0; l < polylines.length; l++) {
				if (polylines[l].xid == id) {
					polylines[l].setOptions({strokeColor: linecolordefault, icons: lineicondefault, zIndex: 1});
					break;
				}
			}
		}
		else {
			wvk_selected.push(id);
			//set line color
			for (l = 0; l < polylines.length; l++) {
				if (polylines[l].xid == id) {
					polylines[l].setOptions({strokeColor: linecolorselected, icons: lineiconselected, zIndex: 2});
					break;
				}
			}
		}
		//update legend count
		$('#legend_wvk').html('Gekoppelde stremmingsroutedelen (' + wvk_selected.length + ')');
	}
	
	//process submit
	$('#submitbutton').click(function(event) {
		$('input[name=wegvakken]').val(wvk_selected.join(','));
	});
});
