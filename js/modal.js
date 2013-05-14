;"use strict";
(function(window, undefined){
	var document = window.document
	  , $ = window.jQuery
	  , html_class = document.getElementsByTagName('html')[0].className
	  , ieflag = {
			normal: ! /lte-ie9/i.test(html_class),
			ie: /*@cc_on!@*/false,
			ie9: /is-ie9/i.test(html_class),
			ie8: /is-ie8/i.test(html_class),
			lteie9: /lte-ie9/i.test(html_class),
			lteie8: /lte-ie8/i.test(html_class),
			lteie7: /lte-ie7/i.test(html_class)
		}
	  ;
	$(function(){
		var tjscroll
		  , $modal = $('#modal-page')
		  , refresh_scroll = function(){
		  		tjscroll.refresh.call(tjscroll);
			}
		  ;
		$(document).on('touchmove', function(e) { 
			e.preventDefault();
			e.stopPropagation();
		});
		$(window).on('message', function(e){
			var event = e.originalEvent;
			if (event.origin !== window.location.origin) return;
			if( event.data == 'modal-loaded' ){
				refresh_scroll.call();
			}
		}).resize(refresh_scroll);
		$modal.TJScroll({
			enabled:true,
			hScroll:false,
			vScroll:true,
			hScrollbar:false,
			vScrollbar:true,
			bounceLock: true
		});
		tjscroll = $modal.data('TJScroll');
	});
	
	$.extend({ ieflag: ieflag });
	window.ieflag = ieflag;
})(this);