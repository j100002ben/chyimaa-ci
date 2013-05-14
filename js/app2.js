;"use strict";
(function(window, undefined){
	var document = window.document
	  , $ = window.jQuery
	  , Raphael = window.Raphael
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
  		  , $body = $('#body')
  		  , $header = $('#header')
  		  , iframeWin = $('.fancybox-iframe').get(0)
  		  , scale
  		  , y
  		  ;
  		if( iframeWin !== undefined && iframeWin ){
  			iframeWin.contentWindow.postMessage('resize-window', window.location.origin);
  		}
  		if( height < 650 ) {
  			scale = height / 650;
  			y = ( 650 - height ) / 2 * scale;
  			$body.css({
  				transform: "translate(0,-" + y + "px) scale(" + scale + "," + scale + ")"
  			});
  			y = y * 0.9 - 29.5;
  			$header.css({
  				transform: "translate(0,-" + y + "px) scale(" + scale + "," + scale + ")"
  			});
  		} else {
  			$body.add($header).css({
  				transform: "translate(0,0) scale(1,1)"
  			});
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
				GLonly.init.draw_line.call(this);
				if(ieflag.ie){
					GLonly.init.ie_page.call(this);
				}
			},
		traditional_page: function(){
		  		console.log('exec init traditional page');
			},
		draw_line: function(){
				var $d = $('#drawings-group-inner')
				  , width = $d.width()
				  , height = $d.height()
				  , half_width = width / 2
				  , canvas = Raphael("drawings-group-inner", width, height)
				  , start_point = ['M', half_width, 265]
				  , left_start_point = ['M', half_width - 250, 265]
				  , left_points = []
				  , left_bottom_points = []
				  , right_start_point = ['M', half_width + 250, 265]
				  , right_points = []
				  , right_bottom_points = []
				  ;	
				left_bottom_points.push({
					point: left_start_point,
					time: 1000,
					delay: 700
				});
				left_bottom_points.push({
					point: ['M', half_width - 250, 400],
					time: 1000
				});
				left_points.push({
					point: start_point,
					time: 1000
					});
				left_points.push({
					point: ['L', half_width - 350, 265],
					time: 1000
					});
				left_points.push({
					point: ['L', half_width - 350, 190],
					time: 1000
					});
				
				right_bottom_points.push({
					point: right_start_point,
					time: 1000,
					delay: 700
				});
				right_bottom_points.push({
					point: ['M', half_width + 250, 400],
					time: 1000
				});
				right_points.push({
					point: start_point,
					time: 1000
					});
				right_points.push({
					point: ['L', half_width + 350, 265],
					time: 1000
					});
				right_points.push({
					point: ['L', half_width + 350, 190],
					time: 1000
					});
				
				var left_paths = []
				  , left_bottom_paths = []
				  , right_paths = []
				  , right_bottom_paths = []
				  ;
				var point_animate_seq = function(paths, points, i){
					if( i >= points.length - 1) return ;
					if( points[i].delay !== undefined && points[i].delay ){
						setTimeout(function(){
							point_animate_seq(paths, points, i);
						}, points[i].delay);
						delete points[i].delay;
						return ;
					}
					var a = points[i].point
					  , b = points[i+1].point
					  ;
					a[0] = 'M';
					b[0] = 'L';
					paths[i] = canvas.path(a);
					paths[i].attr({
						stroke: '#CCC', 
						opacity: 0.5,
						"stroke-width": 8,
						"stroke-linecap": 'round',
						"stroke-linejoin": 'round',
						"stroke-dasharray": '-'
						});
					paths[i].animate( { path: [a, b] } , points[i].time, function(){
						point_animate_seq(paths, points, i+1);
					} );
					return ;
				};
				point_animate_seq(left_paths, left_points, 0);
				point_animate_seq(left_bottom_paths, left_bottom_points, 0);
				point_animate_seq(right_paths, right_points, 0);
				point_animate_seq(right_bottom_paths, right_bottom_points, 0);
			},
		ie_page: function(){
		  		
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