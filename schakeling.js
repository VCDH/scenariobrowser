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
var clear_upload_timeout;

$(function () {
	'use strict';
	// Change this to the location of your server-side upload handler:
	$('#fileupload').fileupload({
		url: 'schakelinguploadhandler.php?id=' + $('input[name=id]').val(),
		dataType: 'json',
		submit: function() {
			$('#fileupload').fileupload(
				'option',
				'url',
				'schakelinguploadhandler.php?id=' + $('input[name=id]').val() + '&ow=' + $('#overwrite:checked').val()
			);
		},
		start: function() {
			clearTimeout(clear_upload_timeout);
		},
		done: function (e, data) {
			$.each(data.result.files, function (index, file) {
				if (file.error) {
					$('<p/>').text('Kan ' + file.name + ' niet uploaden: ' + file.error).appendTo('#files');
				}
				else {
					$('<p/>').text(file.name + ' toegevoegd').appendTo('#files');
				}
			});
			clear_upload_timeout = setTimeout(clear_upload_result, 5000);
			load_drips();
		},
		fail: function (e, data) {
			$('<p/>').text('Kan bestand niet uploaden: ' + data.errorThrown).appendTo('#files');
			clear_upload_timeout = setTimeout(clear_upload_result, 5000);
		}
	}).prop('disabled', !$.support.fileInput)
		.parent().addClass($.support.fileInput ? undefined : 'disabled');
});

function clear_upload_result() {
	$('#files').empty();
}

var infowindow =  new google.maps.InfoWindow({
	content: ''
});

function seeifdripisdrawn(id) {
	for (var i = 0; i < drips.length; i++) {
		if (id == drips[i].drip_id) {
			return true;
		}
	}
	return false;
}
function unsetdripsfrommap() {
	for (var i = 0; i < drips.length; i++) {
		if (drip_ids.indexOf(drips[i].drip_id) == -1) {
			drips[i].setMap(null);
			drips.splice(i, 1);
		}
	}
}

function load_drips() {
	$.getJSON('schakelinghelper.php', { get: 'drips', id: $('input[name=id]').val() })
	.done (function(json) {
		$('#drips').empty();
		drip_ids = [];
		$.each( json, function(i, value) {
			//toon afbeelding
			$('#drips').append('<li id="drip_' + value.id + '"><div class="drip_name org org-' + value.org + '"><span class="name" title="Beeldstand aan DRIP koppelen">' + value.name + '</span><span class="delete ui-icon ui-icon-trash" title="DRIP verwijderen"></span></div><div class="drip_image" title="Klik om beeldstand te bewerken">' + value.img + '</div></li>');
			if (value.drip_id != null) {
				drip_ids.push(value.drip_id);
			}
			//teken op kaart
			if ((value.lat != null) && !seeifdripisdrawn(value.drip_id)) {
				var icon_path = 'M 0,-4 2,-1 4,-1 4,4 -4,4 -4,-1 -2,-1 z';
				var position = new google.maps.LatLng(value.lat, value.lng);
				//label position
				var drip = new google.maps.Marker({
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
					drip_id: value.drip_id
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
				drips.push(drip);
			}
		});
		unsetdripsfrommap();
		$('span.delete').click(function() { 
			var id = $(this).parent().parent().attr('id').substr(5);
			delete_drip(id);
		});
		$('span.name').click(function() { 
			var id = $(this).parent().parent().attr('id').substr(5);
			rename_drip(id);
		});
		//bestaande DRIP bewerken
		$('#drips img').click( function() {
			var id = $(this).parent().parent().attr('id').substr(5);
			var drip_name = $(this).parent().parent().children('div.drip_name').children('span.name').html();
			var md5 = $(this).attr('src').substr($(this).attr('src').lastIndexOf('/')+1,32);
			var return_url = window.location.href;
			return_url = return_url.substr(0, return_url.lastIndexOf('/') + 1) + 'wddhandler.php?do=edit&id=' + id;
			open_wdd(drip_name, return_url, md5);
		});
	});
}

function delete_drip(id) {
	if (window.confirm('DRIP verwijderen?')) {
        $.getJSON('schakelinghelper.php', { get: 'dripdelete', id: id })
        .done (function(json) {
            load_drips();
        });
    }
}

function rename_drip(id) {
	if ($('#dialog').length == 0) {
		$('html').append('<div id="dialog"></div>');
	}
	$('#dialog').html('');
	$('#dialog').dialog({
		autoOpen: false,
		title: 'laden...',
		height: 'auto',
		width: 800,
		minWidth: 800,
		height: $(window).height(),
		position: { my: "top", at: "top", of: window }
	});
	$("#dialog").parent().css({position : "fixed"}).end().dialog('open');
	//get DRIP select gui
	$.getJSON('schakelinghelper.php', { get: 'dripselect' })
	.done (function(json) {
		$('#dialog').html(json.html);
		var driplist = new List('dripselect', {
			valueNames: [ 'name', 'org' ]
		});
		$('#dialog').dialog('option', 'title', 'Kies DRIP voor afbeelding');
		$('input.search').focus();
		$('#dripselect tr').click(function() { 
			var drip_id =  $(this).attr('id').substr(7);
			$.getJSON('schakelinghelper.php', { get: 'driprename', id: id, drip_id: drip_id }) //id is id van scenariodrips
			.done (function(json) {
				$('#dialog').dialog('close');
				load_drips();
			});
		});
	});
}

var map;
var polylines = [];
var drips = [];
var drip_ids = [];

var linecolordefault = '#999';
var linecolorhover = '#C0C';
var linecolorselected = '#0C0';
var linecolorstremming = '#C00';
var linecolorenddefault = '#666';
var linecolorendselected = '#060';
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
var lineiconstremming = [{
	icon: { //end
		path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
		anchor: new google.maps.Point(0,1),
		fillColor: linecolorstremming,
		fillOpacity: 1,
		strokeWeight: 1
	},
	offset: '100%'
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


/*
* WebDRIP Designer koppeling
*/
function open_wdd(drip_name, return_url, md5) {
	if ($('#dialog').length == 0) {
		$('html').append('<div id="dialog"></div>');
	}
	$('#dialog').html('');
	$('#dialog').dialog({
		autoOpen: false,
		title: 'Maak afbeelding voor DRIP ' + drip_name,
		width: $(window).width() - 20,
		height: $(window).height() - 20,
		modal: true,
		position: { my: "center", at: "center", of: window }
	});
	$("#dialog").parent().css({position : "fixed"}).end().dialog('open');
	$('#dialog').html('<iframe src="http://wdd.s200.nl/?return_url=' + encodeURIComponent(return_url) + (md5 != null ? '&amp;md5=' + md5 : '') + '" height="100%" width="100%" style="border:none;"></iframe>');
}
function close_wdd() {
	$('#dialog').dialog('close');
	load_drips();
}

$( document ).ready(function() {
	load_drips();
	//reload on request
	$('#button_refresh_drips').click( function() {
		load_drips();
		$('<p/>').text('Vernieuwen voltooid.').appendTo('#files');
		clear_upload_timeout = setTimeout(clear_upload_result, 5000);
	});
	
	//WebDRIP Designer handler
	//nieuwe DRIP toevoegen
	$('#wdd_button').click( function() {
		//toon eerst een dialoog met alle DRIPs
		if ($('#dialog').length == 0) {
			$('html').append('<div id="dialog"></div>');
		}
		$('#dialog').html('');
		$('#dialog').dialog({
			autoOpen: false,
			title: 'laden...',
			height: 'auto',
			width: 800,
			minWidth: 800,
			height: $(window).height(),
			position: { my: "top", at: "top", of: window }
		});
		$("#dialog").parent().css({position : "fixed"}).end().dialog('open');
		//get DRIP select gui
		$.getJSON('schakelinghelper.php', { get: 'dripselect' })
		.done (function(json) {
			$('#dialog').html(json.html);
			var driplist = new List('dripselect', {
				valueNames: [ 'name', 'org' ]
			});
			$('#dialog').dialog('option', 'title', 'Kies DRIP voor afbeelding');
			$('input.search').focus();
			$('#dripselect tr').click(function() { 
				var drip_id =  $(this).attr('id').substr(7);
				var drip_name = $(this).children('td.name').html();
				var return_url = window.location.href;
				return_url = return_url.substr(0, return_url.lastIndexOf('/') + 1) + 'wddhandler.php?do=new&id=' + $('input[name=id]').val() + '&drip_id=' + drip_id + '&ow=' + $('#overwrite:checked').val();
				open_wdd(drip_name, return_url, null);
			});
		});
	});
	
	initMap();
	
	//stremmingsvakken onload handler
	var wvk_stremming = $('input[name=stremmingsvakken]').val();
	if (wvk_stremming.length > 0) {
		wvk_stremming = wvk_stremming.split(',');
	}
	else {
		wvk_stremming = [];
	}
	//segments onload handler
	var wvk_selected = $('input[name=wegvakken]').val();
	if (wvk_selected.length > 0) {
		wvk_selected = wvk_selected.split(',');
		//set button state
		for (var i = wvk_selected.length - 1; i >= 0 ; i--) {
			if (wvk_stremming.indexOf(wvk_selected[i]) >= 0) {
				wvk_selected.splice(i, 1);
			}
			else {
				$('li#wvk_' + wvk_selected[i]).addClass('selected');
			}
		}
	}
	else {
		wvk_selected = [];
	}
	
	//set legend
	$('#legend_wvk').html('Gekoppelde omleidingsroutedelen (' + wvk_selected.length + ')');
	
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
			if (wvk_stremming.indexOf(value.id) >= 0) {
				polyline.setOptions({strokeColor: linecolorstremming, icons: lineiconstremming, zIndex: 3});
			}
			else if (wvk_selected.indexOf(value.id) >= 0) {
				$('#wvk_' + value.id).addClass('selected');
				polyline.setOptions({strokeColor: linecolorselected, icons: lineiconselected, zIndex: 2});
			}
			
			//onclick handler
			google.maps.event.addListener(polyline, 'click', function() {
				if (!(wvk_stremming.indexOf(value.id) >= 0)) {
					toggle_wvk(value.id);
				}
			});
			//bind mouseover color change
			google.maps.event.addListener(polyline, 'mouseover', function() {
				if (!(wvk_stremming.indexOf(value.id) >= 0)) {
					polyline.setOptions({strokeColor: linecolorhover, icons: lineiconhover, zIndex: 3});
				}
			});
			google.maps.event.addListener(polyline, 'mouseout', function() {
				if (wvk_stremming.indexOf(value.id) >= 0) {
					polyline.setOptions({strokeColor: linecolorstremming, icons: lineiconstremming, zIndex: 3});
				}
				else if (wvk_selected.indexOf(value.id) >= 0) {
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
		$('#legend_wvk').html('Gekoppelde omleidingsroutedelen (' + wvk_selected.length + ')');
	}
	
	//process submit
	$('#submitbutton').click(function(event) {
		$('input[name=wegvakken]').val(wvk_selected.join(','));
	});
});