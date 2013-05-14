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
	  , GLonly = {}
	  ;
	GLonly.resize_window = function(){
  		var height = $(document.body).height()
  		  , iframeWin = $('.fancybox-iframe').get(0)
  		  ;
  		if( iframeWin !== undefined && iframeWin ){
  			iframeWin.contentWindow.postMessage('resize-window', window.location.origin);
  		}
	};
	GLonly.modal_loaded = function(){
		var iframeWin = $('.fancybox-iframe').get(0);
  		if( iframeWin !== undefined && iframeWin ){
  			iframeWin.contentWindow.postMessage('modal-loaded', window.location.origin);
  		}
	};
	GLonly.view = {
		
	};
	GLonly.data = {
		
	};
	GLonly.events = {
		
	};
	GLonly.network = {
		
	};
	GLonly.init = {
		page: function(){
		  		console.log('exec init page');
		  		var menu_tap;
		  		$(document).on('touchmove', function(e) { 
					e.preventDefault();
					e.stopPropagation();
				});
				$('#menu-list > li > div.list').on('touchstart', function(e){
					menu_tap = true;
					e.preventDefault();
					e.stopPropagation();
				}).on('touchend', function(e){
					if( ! menu_tap ) return false;
					menu_tap = false;
					var $this = $(this);
					$this.parent().toggleClass('tap');
					e.preventDefault();
					e.stopPropagation();
				});
				if(ieflag.ie){
					GLonly.init.ie_page.call(this);
				}
			},
		traditional_page: function(){
		  		console.log('exec init traditional page');
			},
		ie_page: function(){
		  		$('#plurk-box').on('mouseenter', function(){
					$(this).addClass('hover');
				}).on('mouseleave', function(){
					$(this).removeClass('hover');
				}).children('iframe').on('hover',function(){
					$(this).parent().addClass('hover');
				});
			}
	};
	$(function(){
		GLonly.resize_window();
	  	$(window).resize(GLonly.resize_window);
	  	$('a[rel="fancybox-iframe"]').click(function(e){
	  		$.fancybox.open({
				href : $(this).attr('href'),
				type : 'iframe',
				padding : 5,
				maxWidth	: 800,
				maxHeight	: 600,
				fitToView	: false,
				width		: '70%',
				height		: '70%',
				autoSize	: false,
				closeClick	: true,
				afterShow	: GLonly.modal_loaded,
				onUpdate	: GLonly.modal_loaded
			});
	  		e.preventDefault();
			e.stopPropagation();
	  		return false;
	  	});
		if( $.fn.jquery == '2.0.0' ){
			GLonly.init.page.call(this);
		}else{
			GLonly.init.traditional_page.call(this);
		}
	});
	
	$.extend({ ieflag: ieflag });
	window.ieflag = ieflag;
	window.GLonly = GLonly;
})(this);