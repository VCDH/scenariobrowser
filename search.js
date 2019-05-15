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
var named_categories = {
	scn: 'Scenario\'s',
	wvk: 'Wegvakken',
	hmp: 'Hectometerposities'
};

$.widget( "custom.catcomplete", $.ui.autocomplete, {
	_create: function() {
		this._super();
		this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
	},
	_renderMenu: function( ul, items ) {
		var that = this,
		currentCategory = "";
		$.each( items, function( index, item ) {
			var li;
			if ( item.category != currentCategory ) {
				ul.append( "<li class='ui-autocomplete-category'>" + named_categories[item.category] + "</li>" );
				currentCategory = item.category;
			}
			li = that._renderItemData( ul, item );
			if ( item.category ) {
				li.attr( "aria-label", item.category + " : " + item.label );
			}
			if ((item.category == 'hmp') && (typeof item.id == 'undefined')) {
				li.addClass('notfound');
			}
		});
	}
});

$(document).ready( function() {
	$( "#searchbox" ).catcomplete({
		source: 'searchresults.php',
		minLength: 2,
		select: function( event, ui ) {
			if (ui.item.category == 'scn') {
				var href = 'scenario.php?id=' + ui.item.id;
			}
			else if (ui.item.category == 'wvk') {
				var href = 'index.php?lookat=' + ui.item.id;
			}
			else if (ui.item.category == 'hmp') {
				if (typeof ui.item.id !== 'undefined') {
					var href = 'index.php?lookat=' + ui.item.id + '&latlng=' + ui.item.latlng;
				}
				else {
					var href = 'index.php?latlng=' + ui.item.latlng;
				}
			}
			else {
				$('#searchbox').catcomplete('close');
				return false;
			}
			history.pushState({}, document.title, window.location.href);
			window.location.replace(href);
		}
	})
	.catcomplete( 'instance' )._renderItem = function( ul, item ) {
		if (item.category == 'scn') {
			var scenario_types = {w : 'Werkzaamheden', e : 'Evenement', i : 'Ongeval', f : 'File', t : 'Tunnel', b : 'Brug', a : 'Algemeen'};
			var scenario_ernsten = {d : 'Dicht (incl. omleiden)', h : 'Hinder (incl. omleiden)', i : 'Informeren'};
			var	scenario_types_afbeeldingen = {w : 'werk.png', e : 'evenement.png', i : 'ongeval.png', f : 'file.png', t : 'tunnel.png', b : 'brug.png', a : 'algemeen.png'};
			var scenario_ernsten_afbeeldingen = {d : 'dicht.png', h : 'omleiden.png', i : 'informeren.png'};
			return $( '<li>' )
				.append( '<img src="images/' + scenario_types_afbeeldingen[item.type] + '" class="te" width="16" height="16" alt="' + scenario_types[item.type] + '" title="' + scenario_types[item.type] + '"><img src="images/' + scenario_ernsten_afbeeldingen[item.ernst] + '" class="te" width="16" height="16" alt="' + scenario_ernsten[item.type] + '" title="' + scenario_ernsten[item.type] + '"> ' + item.label )
				.appendTo( ul );
		}
		else {
			return $( '<li>' )
				.append( item.label )
				.appendTo( ul );
		}
    };
	//help function
	$('#help').click( function(event) {
		event.preventDefault();
		if ($('#dialog').length == 0) {
			$('html').append('<div id="dialog"></div>');
		}
		$('#dialog').html('');
		$('#dialog').dialog({
			autoOpen: false,
			title: 'laden...',
			height: 'auto',
			width: Math.max(($(window).width() - 980), 400),
			height: $(window).height(),
			position: { my: "left top", at: "left top", of: window }
		});
		$("#dialog").parent().css({position : "fixed"}).end().dialog('open');
		$.get($(this).attr('href'), {h: $(this).attr('rel')})
		.done (function(data) {
			$('#dialog').html(data);
			$('#dialog').dialog('option', 'title', 'Help');
			$('div#content').attr('style', 'margin-right: 16px');
		});
		$('#dialog').dialog({
			close: function( event, ui ) {
				$('div#content').removeAttr('style');
			}
		});
	});
});

/*! js-cookie v2.1.2 | MIT */
!function(a){if("function"==typeof define&&define.amd)define(a);else if("object"==typeof exports)module.exports=a();else{var b=window.Cookies,c=window.Cookies=a();c.noConflict=function(){return window.Cookies=b,c}}}(function(){function a(){for(var a=0,b={};a<arguments.length;a++){var c=arguments[a];for(var d in c)b[d]=c[d]}return b}function b(c){function d(b,e,f){var g;if("undefined"!=typeof document){if(arguments.length>1){if(f=a({path:"/"},d.defaults,f),"number"==typeof f.expires){var h=new Date;h.setMilliseconds(h.getMilliseconds()+864e5*f.expires),f.expires=h}try{g=JSON.stringify(e),/^[\{\[]/.test(g)&&(e=g)}catch(i){}return e=c.write?c.write(e,b):encodeURIComponent(String(e)).replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g,decodeURIComponent),b=encodeURIComponent(String(b)),b=b.replace(/%(23|24|26|2B|5E|60|7C)/g,decodeURIComponent),b=b.replace(/[\(\)]/g,escape),document.cookie=[b,"=",e,f.expires&&"; expires="+f.expires.toUTCString(),f.path&&"; path="+f.path,f.domain&&"; domain="+f.domain,f.secure?"; secure":""].join("")}b||(g={});for(var j=document.cookie?document.cookie.split("; "):[],k=/(%[0-9A-Z]{2})+/g,l=0;l<j.length;l++){var m=j[l].split("="),n=m.slice(1).join("=");'"'===n.charAt(0)&&(n=n.slice(1,-1));try{var o=m[0].replace(k,decodeURIComponent);if(n=c.read?c.read(n,o):c(n,o)||n.replace(k,decodeURIComponent),this.json)try{n=JSON.parse(n)}catch(i){}if(b===o){g=n;break}b||(g[o]=n)}catch(i){}}return g}}return d.set=d,d.get=function(a){return d(a)},d.getJSON=function(){return d.apply({json:!0},[].slice.call(arguments))},d.defaults={},d.remove=function(b,c){d(b,"",a(c,{expires:-1}))},d.withConverter=b,d}return b(function(){})});