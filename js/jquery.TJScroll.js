/* 
 * plugin: TJScroll
 * author: Alex Prokop
 * version: 0.9.1
 * 
 * description: jQuery plugin for creating custom mobile-compatible scrollbars, port from iScroll with improved desktop compatibility and introduced IE compatibility
 * 		it's not currently a complete port (I've removed any pinch to zoom) and I've yet to implement scrollToElement and scrollToPage methods,
 * 		I've also replaced the existing capabilities testing with Modernizr (it needs at least tests for touch, csstransitions, csstransforms and csstransforms3d)
 * 		
 * dependencies: jquery, jquery.easing, jquery.mousewheel.js, Modernizr, requestAnimationFrame shim
 * 
 * methods: refresh, setPosition, scrollTo, enable, disable, hideScrollbars, showScrollbars
 * 
 * options: see TJScroll.defaultOptions below, everything is overridable
 * 
 * events: set events as callbacks in TJScroll.defaultOptions, the callback will return the TJScroll api as a parameter
 *		onRefresh, onBeforeScrollStart, onAnimationEnd, onScrollStart, onBeforeScrollMove, onScrollMove, onPositionChange, onBeforeScrollEnd, onScrollEnd, onTouchEnd
 *
 * usage: $jQueryElement.TJScroll({options});	// see below for additional options
 *		the api can then be retrieved from the TJScroll data attribute: var api = $jQueryElement.data('TJScroll');
 *
 * changelog:
 *
 * 0.9.1 - added draggable scrollbar 
 *
 */

(function($) {
	$.fn.TJScroll = function (opts) {		
		var options = $.extend({}, TJScroll.defaultOptions, opts);
				
		this.each(function() {
			var $this = $(this);
			var scroll;
									
			$.extend(options, getOptionsFromDataAttributes($this));
						
			scroll = new TJScroll($this, options);
			$(this).data('TJScroll', scroll);
			scroll.init();
		});
		
		function getOptionsFromDataAttributes ($scroller) {
			var options = {};
			var attrs = $scroller[0].attributes;
						
			var key;
			var value;
			
			$.each(attrs, function () {
				key = this.nodeName;
				value = this.nodeValue;
								
				if(key.indexOf('data-') > -1) {
					
					key = toCamelCase(key);
										
					if(key in TJScroll.defaultOptions) {
						value = (value == "true") ? true : value;
						value = (value == "false") ? false : value;
						options[key] = value;
					}
				}
			});
			
			return options;
		}
		
		function toCamelCase(key) {
			var a = key.substring(5).split('-');
			var str = "";
			
			$.each(a, function (i) {
				if(i > 0) {
					str += ucFirst(this);	
				}
				else {
					str += this;
				}
			});
			
			return str;
		}
		
		function ucFirst(string) {
			return string.charAt(0).toUpperCase() + string.slice(1);
		}
				
		return this;
	}
})(jQuery);

// constructor

var TJScroll = function ($element, options) {
	this.$wrapper = $element;
	this.$scroller = this.$wrapper.children().eq(0);
	
	this.options = options;
	
	this.x = 0;
	this.y = 0;
	
	this.steps = [];
	this.animating = false;
	this.moved = false;
	this.aniTime = null;
	
	this.wrapperW = 0;
	this.wrapperH = 0;
	
	this.minScrollY = 0;
	this.scrollerW = 0;
	this.scrollerH = 0;
	this.maxScrollX = 0;
	this.maxScrollY = 0;
	
	this.dirX = 0;
	this.dirY = 0;
	
	this.hScroll = false;
	this.vScroll = false;
	this.hScrollbar = false;
	this.vScrollbar = false;
	
	this.barActiveDir = null;
	
	this.$hScrollbarWrapper = null;
	this.$vScrollbarWrapper = null;
	this.$hScrollbarIndicator = null;
	this.$vScrollbarIndicator = null;
	
	this.wrapperOffsetLeft = 0;
	this.wrapperOffsetTop = 0;
	
	this.pagesX = [];
	this.pagesY = [];
	
	this.enabled = true;
	this.currPageX = 0;
	this.currPageY = 0;
}

// static
TJScroll.mround = function (r) { return r >> 0; };
TJScroll.vendor = (/webkit/i).test(navigator.appVersion) ? 'webkit' :
	(/firefox/i).test(navigator.userAgent) ? 'Moz' :
	(/trident/i).test(navigator.userAgent) ? 'ms' :
	'opera' in window ? 'O' : '';

// Browser capabilities
TJScroll.isAndroid = (/android/gi).test(navigator.appVersion);
TJScroll.isIDevice = (/iphone|ipad/gi).test(navigator.appVersion);
TJScroll.isPlaybook = (/playbook/gi).test(navigator.appVersion);
TJScroll.isTouchPad = (/hp-tablet/gi).test(navigator.appVersion);

TJScroll.has3d = Modernizr.csstransforms3d;
TJScroll.hasTouch = Modernizr.touch;
TJScroll.hasTransform = Modernizr.csstransforms;
TJScroll.hasTransitionEnd = Modernizr.csstransitions;

// Events
TJScroll.RESIZE_EV = 'onorientationchange' in window ? 'orientationchange' : 'resize';
TJScroll.START_EV = TJScroll.hasTouch ? 'touchstart' : 'mousedown';
TJScroll.MOVE_EV = TJScroll.hasTouch ? 'touchmove' : 'mousemove';
TJScroll.END_EV = TJScroll.hasTouch ? 'touchend' : 'mouseup';
TJScroll.CANCEL_EV = TJScroll.hasTouch ? 'touchcancel' : 'mouseup';
TJScroll.WHEEL_EV = $.fn.mwheelIntent ? 'mwheelIntent' : $.fn.mousewheel ? 'mousewheel' : null;

// Helpers
TJScroll.trnOpen = 'translate' + (TJScroll.has3d ? '3d(' : '(');
TJScroll.trnClose = TJScroll.has3d ? ',0)' : ')';

TJScroll.defaultOptions = {
	hScroll: true,
	vScroll: true,
	bounce: true,
	bounceLock: false,
	momentum: true,
	lockDirection: true,
	useTransform: true,
	useTransition: false,
	topOffset: 0,
	checkDOMChanges: false,		// Experimental
	ease:"easeInOutQuad",
	testTouch:false,
	enabled:true,
	
	x:0,
	y:0,

	// Scrollbar
	hScrollbar: true,
	vScrollbar: true,
	fixedScrollbar: TJScroll.isAndroid,
	hideScrollbar: TJScroll.isIDevice,
	fadeScrollbar: TJScroll.isIDevice && TJScroll.has3d,
	scrollbarClass: 'scrollBar',
	mouseWheelSpeed:30,
	mouseWheelEnabled:true,

	// Snap
	snap: false,
	snapThreshold: 1,

	// Events
	onRefresh: null,
	onBeforeScrollStart: function (e) {e.preventDefault();},
	onAnimationEnd: null,
	onScrollStart: null,
	onBeforeScrollMove: null,
	onScrollMove: null,
	onPositionChange: null,
	onBeforeScrollEnd: null,
	onScrollEnd: null,
	onTouchEnd: null,
	onDestroy: null
};



/* init ***********************************************************************/


TJScroll.prototype.init = function () {	
	this.options.useTransform = TJScroll.hasTransform ? this.options.useTransform : false;
	this.options.useTransition = TJScroll.hasTransitionEnd ? this.options.useTransition : false;
	this.options.hScrollbar = this.options.hScroll && this.options.hScrollbar;
	this.options.vScrollbar = this.options.vScroll && this.options.vScrollbar;
	this.enabled = this.options.enabled;
	
	this.x = this.options.x;
	this.y = this.options.y;
	
	var css = {};
	
	css[TJScroll.vendor + 'TransitionProperty'] = this.options.useTransform ? '-' + TJScroll.vendor.toLowerCase() + '-transform' : 'top left';
	
	if(this.options.useTransition) {
		css[TJScroll.vendor + 'TransitionDuration'] = '5';
		css[TJScroll.vendor + 'TransitionTimingFunction'] = 'cubic-bezier(0.33,0.66,0.66,1)';
	}
	
	if(this.options.useTransform) {
		css[TJScroll.vendor + 'TransformOrigin'] = '0 0';
	}
	else {
		css['position'] = 'absolute';
	}
		
	this.getPositionCss(css, this.x, this.y);	
	this.$scroller.css(css);
	
	this.refresh();
}



/* event handlers *************************************************************/


TJScroll.prototype.onTouchStart = function (e) {
	e.touches = e.originalEvent.touches;
	
	var self = this,	
	point = TJScroll.hasTouch ? e.touches[0] : e,
	matrix, x, y,
	c1, c2;

	if(!this.enabled) return;

	if(this.options.onBeforeScrollStart) this.options.onBeforeScrollStart.call(this, e);

	if(this.options.useTransition) this.setTransitionTime(0);

	this.moved = false;
	this.animating = false;
	this.distX = 0;
	this.distY = 0;
	this.absDistX = 0;
	this.absDistY = 0;
	this.dirX = 0;
	this.dirY = 0;

	if(this.options.momentum) {
		if(this.options.useTransform) {
			// Very lame general purpose alternative to CSSMatrix			
			matrix = this.$scroller.css('-' + TJScroll.vendor + '-transform').replace(/[^0-9-.,]/g, '').split(',');
			x = matrix[4] * 1;
			y = matrix[5] * 1;
		}
		else {
			x = this.$scroller.css('left').replace(/[^0-9-]/g, '') * 1;
			y = this.$scroller.css('top').replace(/[^0-9-]/g, '') * 1;
		}
		
		if(x != this.x || y != this.y) {
			if(this.options.useTransition) {
				this.$scroller.unbind('webkitTransitionEnd');
			}
			else {
				cancelAnimationFrame(this.aniTime);
			}
			
			this.steps = [];
			this.setPosition(x, y);
		}
	}

	this.absStartX = this.x;	// Needed by snap threshold
	this.absStartY = this.y;

	this.startX = this.x;
	this.startY = this.y;
	this.pointX = point.pageX;
	this.pointY = point.pageY;

	if(e.timeStamp) {
		this.startTime = e.timeStamp;
	}
	else {
		var date = new Date();
		this.startTime = date.getTime();
	}

	if(this.options.onScrollStart) {
		this.options.onScrollStart.call(this, e);
	}
	
	this.$scroller.bind(TJScroll.MOVE_EV, function (e) {
		self.onTouchMove(e);
	});
	
	this.$scroller.bind(TJScroll.END_EV, function (e) {
		self.onTouchEnd(e);
	});
	
	this.$scroller.bind(TJScroll.CANCEL_EV, function (e) {
		self.onTouchEnd(e);
	})
}

TJScroll.prototype.onTouchMove = function (e) {
	e.touches = e.originalEvent.touches;
	
	var point = TJScroll.hasTouch ? e.touches[0] : e,
	deltaX = point.pageX - this.pointX,
	deltaY = point.pageY - this.pointY,
	newX = this.x + deltaX,
	newY = this.y + deltaY,
	c1, c2, scale,
	timestamp;
	
	if(e.timeStamp) {
		timestamp = e.timeStamp;
	}
	else {
		var date = new Date();
		timestamp = date.getTime();
	}

	if(this.options.onBeforeScrollMove) {
		this.options.onBeforeScrollMove.call(this, e);
	}

	this.pointX = point.pageX;
	this.pointY = point.pageY;

	// Slow down if outside of the boundaries
	if(newX > 0 || newX < this.maxScrollX) {
		newX = this.options.bounce ? this.x + (deltaX / 2) : newX >= 0 || this.maxScrollX >= 0 ? 0 : this.maxScrollX;
	}
	if(newY > this.minScrollY || newY < this.maxScrollY) { 
		newY = this.options.bounce ? this.y + (deltaY / 2) : newY >= this.minScrollY || this.maxScrollY >= 0 ? this.minScrollY : this.maxScrollY;
	}

	this.distX += deltaX;
	this.distY += deltaY;
	this.absDistX = Math.abs(this.distX);
	this.absDistY = Math.abs(this.distY);

	if(this.absDistX < 6 && this.absDistY < 6) {
		return;
	}

	// Lock direction
	if(this.options.lockDirection) {
		if(this.absDistX > this.absDistY + 5) {
			newY = this.y;
			deltaY = 0;
		}
		else if (this.absDistY > this.absDistX + 5) {
			newX = this.x;
			deltaX = 0;
		}
	}

	this.moved = true;
	
	this.setPosition(newX, newY);
	
	this.dirX = deltaX > 0 ? -1 : deltaX < 0 ? 1 : 0;
	this.dirY = deltaY > 0 ? -1 : deltaY < 0 ? 1 : 0;

	if(timestamp - this.startTime > 300) {
		this.startTime = timestamp;
		this.startX = this.x;
		this.startY = this.y;
	}
	
	if(this.options.onScrollMove) {
		this.options.onScrollMove.call(this, e);
	}
}

TJScroll.prototype.onTouchEnd = function (e) {	
	e.touches = e.originalEvent.touches;
	
	if(TJScroll.hasTouch && e.touches.length != 0) {
		return;
	}
	
	e.changedTouches = e.originalEvent.changedTouches;

	var point = TJScroll.hasTouch ? e.changedTouches[0] : e,
	$target, ev,
	momentumX = {dist:0, time:0},
	momentumY = {dist:0, time:0},
	newPosX = this.x,
	newPosY = this.y,
	distX, distY,
	newDuration,
	snap,
	scale,
	duration;
	
	if(e.timestamp) {
		duration = e.timestamp - this.startTime;
	}
	else {
		var date = new Date();
		duration = date.getTime() - this.startTime;
	}
	
	this.$scroller.unbind(TJScroll.MOVE_EV);
	this.$scroller.unbind(TJScroll.END_EV);
	this.$scroller.unbind(TJScroll.CANCEL_EV);

	if(this.options.onBeforeScrollEnd) {
		this.options.onBeforeScrollEnd.call(this, e);
	}
	
	if(!this.moved) {
		if(TJScroll.hasTouch) {
			// Find the last touched element
			$target = $(point.target);
			
			while($target[0].nodeType != 1) {
				$target = $target.parent();
			}

			if($target[0].tagName != 'SELECT' && $target[0].tagName != 'INPUT' && $target[0].tagName != 'TEXTAREA') {
				ev = document.createEvent('MouseEvents');
				ev.initMouseEvent('click', true, true, e.view, 1,
					point.screenX, point.screenY, point.clientX, point.clientY,
					e.ctrlKey, e.altKey, e.shiftKey, e.metaKey,
					0, null);	
				ev._fake = true;
				
				ev = $.event.fix(ev);
				$target.trigger(ev);
				
				// if clickthrough didn't work, manually change url
				if (!ev.isDefaultPrevented()) {
					var href = $target[0].getAttribute('href');
					var target = $target[0].getAttribute('target');
					
					if (href != null) {
						if (target != null) {
							window.open(href, target);
						} else {
							window.open(href, '_self');	
						}
					}
				}
			}
		}

		this.resetPosition(200);

		if(this.options.onTouchEnd) {
			this.options.onTouchEnd.call(that, e);
		}
		
		return;
	}
	
	if(duration < 300 && this.options.momentum) {
		momentumX = newPosX ? this.calculateMomentum(newPosX - this.startX, duration, -this.x, this.scrollerW - this.wrapperW + this.x, this.options.bounce ? this.wrapperW : 0) : momentumX;
		momentumY = newPosY ? this.calculateMomentum(newPosY - this.startY, duration, -this.y, (this.maxScrollY < 0 ? this.scrollerH - this.wrapperH + this.y - this.minScrollY : 0), this.options.bounce ? this.wrapperH : 0) : momentumY;

		newPosX = this.x + momentumX.dist;
		newPosY = this.y + momentumY.dist;

		if ((this.x > 0 && newPosX > 0) || (this.x < this.maxScrollX && newPosX < this.maxScrollX)) momentumX = { dist:0, time:0 };
		if ((this.y > this.minScrollY && newPosY > this.minScrollY) || (this.y < this.maxScrollY && newPosY < this.maxScrollY)) momentumY = { dist:0, time:0 };
	}

	if(momentumX.dist || momentumY.dist) {
		newDuration = Math.max(Math.max(momentumX.time, momentumY.time), 10);

		// Do we need to snap?
		if(this.options.snap) {
			distX = newPosX - this.absStartX;
			distY = newPosY - this.absStartY;
			
			if(Math.abs(distX) < this.options.snapThreshold && Math.abs(distY) < this.options.snapThreshold) {
				this.scrollTo(this.absStartX, this.absStartY, 200);
			}
			else {
				snap = this.calculateSnap(newPosX, newPosY);
				newPosX = snap.x;
				newPosY = snap.y;
				newDuration = Math.max(snap.time, newDuration);
			}
		}

		this.scrollTo(TJScroll.mround(newPosX), TJScroll.mround(newPosY), newDuration, true);

		if(this.options.onTouchEnd) {
			this.options.onTouchEnd.call(this, e);
		}
		
		return;
	}

	// Do we need to snap?
	if(this.options.snap) {
		distX = newPosX - this.absStartX;
		distY = newPosY - this.absStartY;
		
		if(Math.abs(distX) < this.options.snapThreshold && Math.abs(distY) < this.options.snapThreshold) {
			this.scrollTo(this.absStartX, this.absStartY, 200);
		}
		else {
			snap = this.calculateSnap(this.x, this.y);
			
			if(snap.x != this.x || snap.y != this.y) {
				this.scrollTo(snap.x, snap.y, snap.time);
			}
		}

		if(this.options.onTouchEnd) {
			this.options.onTouchEnd.call(this, e);
		}
		
		return;
	}

	this.resetPosition(200);
	
	if(this.options.onTouchEnd) {
		this.options.onTouchEnd.call(this, e);
	}
}

TJScroll.prototype.onMouseWheel = function (e, delta, deltaX, deltaY) {
	if(!this.enabled) return;
	
	var dx = this.x + (deltaX * this.options.mouseWheelSpeed);
	var dy = this.y + (deltaY * this.options.mouseWheelSpeed);
	
	if(dx > 0 || !this.hScrollbar) {
		dx = 0;
	}
	else if(dx < this.maxScrollX) {
		dx = this.maxScrollX;
	}

	if(dy > this.minScrollY || !this.vScrollbar) {
		dy = this.minScrollY;
	}
	else if(dy < this.maxScrollY) {
		dy = this.maxScrollY;
	}
			
	dx = TJScroll.mround(dx);
	dy = TJScroll.mround(dy);

	this.setPosition(dx, dy);
}

TJScroll.prototype.onMouseOut = function (e) {	
	var t = e.relatedTarget;

	if(!t) {
		this.onTouchEnd(e);
		return;
	}

	while(t = t.parentNode) {
		if(t == this.wrapper) {
			return;
		}
	}
	
	this.onTouchEnd(e);
}

TJScroll.prototype.onWebkitTransitionEnd = function (e) {	
	this.$scroller.unbind('webkitTransitionEnd');
	this.doAnimation();
}



/* api ************************************************************************/


TJScroll.prototype.refresh = function (resetPosition) {
	var offset,
	i, l,
	els,
	pos = 0,
	page = 0;
	
	resetPosition = typeof(resetPosition) != 'undefined' ? resetPosition : true;

	this.wrapperW = this.$wrapper.outerWidth() || 1;
	this.wrapperH = this.$wrapper.outerHeight() || 1;
	
	this.minScrollY = -this.options.topOffset || 0;
	this.scrollerW = this.$scroller.outerWidth(true);
	this.scrollerH = this.$scroller.outerHeight(true) + this.minScrollY;
	this.maxScrollX = this.wrapperW - this.scrollerW;
	this.maxScrollY = this.wrapperH - this.scrollerH + this.minScrollY;
	this.dirX = 0;
	this.dirY = 0;
	
	if(this.options.onRefresh) {
		this.options.onRefresh.call(this);
	}

	this.hScroll = this.options.hScroll && this.maxScrollX < 0;
	this.vScroll = this.options.vScroll && (!this.options.bounceLock && !this.hScroll || this.scrollerH > this.wrapperH);

	this.hScrollbar = this.hScroll && this.options.hScrollbar;
	this.vScrollbar = this.vScroll && this.options.vScrollbar && this.scrollerH > this.wrapperH;
	
	offset = this.$wrapper.offset();
	this.wrapperOffsetLeft = -offset.left;
	this.wrapperOffsetTop = -offset.top;

	// Prepare snap
	if (typeof this.options.snap == 'string') {
		this.pagesX = [];
		this.pagesY = [];
		
		els = this.$scroller.children(this.options.snap);
		
		els.each(function (index, el) {
			pos = el.offset();
			pos.left += this.wrapperOffsetLeft;
			pos.top += this.wrapperOffsetTop;
			this.pagesX[i] = pos.left < this.maxScrollX ? this.maxScrollX : pos.left;
			this.pagesY[i] = pos.top < this.maxScrollY ? this.maxScrollY : pos.top;
		});
	}
	else if (this.options.snap) {
		this.pagesX = [];
		while (pos >= this.maxScrollX) {
			this.pagesX[page] = pos;
			pos = pos - this.wrapperW;
			page++;
		}
		if (this.maxScrollX%this.wrapperW) this.pagesX[this.pagesX.length] = this.maxScrollX - this.pagesX[this.pagesX.length-1] + this.pagesX[this.pagesX.length-1];

		pos = 0;
		page = 0;
		this.pagesY = [];
		while (pos >= this.maxScrollY) {
			this.pagesY[page] = pos;
			pos = pos - this.wrapperH;
			page++;
		}
		if (this.maxScrollY%this.wrapperH) this.pagesY[this.pagesY.length] = this.maxScrollY - this.pagesY[this.pagesY.length-1] + this.pagesY[this.pagesY.length-1];
	}

	// Prepare the scrollbars
	this.createScrollbar('h');
	this.createScrollbar('v');

	this.setTransitionTime(0);
	
	if(resetPosition) {
		this.resetPosition(200);
	}
	
	if(this.enabled) {
		this.enableInteraction();
	}
}

TJScroll.prototype.setPosition = function (x, y) {		
	this.$scroller.css(this.getPositionCss({}, x, y));
	
	this.x = x;
	this.y = y;
	
	this.setScrollbarPosition('h');
	this.setScrollbarPosition('v');
	
	if(this.options.onPositionChange) {
		this.options.onPositionChange.call(this);
	}
}

TJScroll.prototype.scrollTo = function (x, y, time, isMomemtum) {
	var step = x, i, l;

	this.stop();

	if(!step.length) {
		step = [{x:x, y:y, time:time}];
	}
		
	for(i=0, l=step.length; i<l; ++i) {
		this.steps.push({x:step[i].x, y:step[i].y, time:step[i].time || 0 });
	}

	this.doAnimation(isMomemtum);
}

TJScroll.prototype.disable = function () {
	this.stop();
	this.resetPosition(0);
	this.enabled = false;

	// If disabled after touchstart we make sure that there are no left over events
	this.$scroller.unbind(TJScroll.MOVE_EV);
	this.$scroller.unbind(TJScroll.END_EV);
	this.$scroller.unbind(TJScroll.CANCEL_EV);
	this.disableInteraction();
}
	
TJScroll.prototype.enable = function () {
	this.enabled = true;
	this.enableInteraction();
}

TJScroll.prototype.stop = function () {
	var self = this;
	
	if(this.options.useTransition) {
		this.$scroller.unbind('webkitTransitionEnd');
	}
	else {
		cancelAnimationFrame(self.aniTime);
	}
	
	this.steps = [];
	this.moved = false;
	this.animating = false;
}

TJScroll.prototype.isReady = function () {
	return !this.moved && !this.animating;
}

TJScroll.prototype.hideScrollbars = function () {
	this.options.hideScrollbar = true;
	
	if(this.hScrollbar) {
		this.hideScrollbar('h');
	}
	if(this.vScrollbar) {
		this.hideScrollbar('v');	
	}
}

// replace with TJTween?
TJScroll.prototype.hideScrollbar = function (dir) {
	if(TJScroll.hasTransitionEnd) {
		this['$' + dir + 'ScrollbarWrapper'].css({opacity:0});	
	}
	else {
		this['$' + dir + 'ScrollbarWrapper'].animate({opacity:0}, 350);
	}
}

TJScroll.prototype.showScrollbars = function () {
	this.options.hideScrollbar = false;
	
	if(this.hScrollbar) {
		this.showScrollbar('h');
	}
	if(this.vScrollbar) {
		this.showScrollbar('v');	
	}
}

TJScroll.prototype.showScrollbar = function (dir) {
	if(TJScroll.hasTransitionEnd) {
		this['$' + dir + 'ScrollbarWrapper'].css({opacity:1});	
	}
	else {
		this['$' + dir + 'ScrollbarWrapper'].animate({opacity:1}, 350);
	}
}

TJScroll.prototype.enableInteraction = function () {
	this.enableMouseWheel();
	this.enableTouchEvents();
	this.enableScrollDrag();
}

TJScroll.prototype.disableInteraction = function () {
	this.disableMouseWheel();
	this.disableTouchEvents();
	this.disableScrollDrag();
}

TJScroll.prototype.enableMouseWheel = function () {
	var self = this;
	
	if(!TJScroll.hasTouch && this.options.mouseWheelEnabled && TJScroll.WHEEL_EV) {
		this.$wrapper.unbind('mousewheel').bind('mousewheel', function (e, delta, deltaX, deltaY) {
			self.onMouseWheel(e, delta, deltaX, deltaY);
		});
	}
}

TJScroll.prototype.disableMouseWheel = function () {
	if(!TJScroll.hasTouch && this.options.mouseWheelEnabled && TJScroll.WHEEL_EV) {
		this.$wrapper.unbind('mousewheel');
	}
}

TJScroll.prototype.enableTouchEvents = function () {
	var self = this;
		
	if(TJScroll.hasTouch || this.options.testTouch) {
		this.$wrapper.unbind(TJScroll.START_EV).bind(TJScroll.START_EV, function (e) {
			if(!TJScroll.hasTouch && e.button !== 0) {
				return;
			}
			self.onTouchStart(e);
		});
	}
	
	if(!TJScroll.hasTouch && this.options.testTouch) {
		this.$wrapper.unbind('mouseout').bind('mouseout', function (e) {
			self.onMouseOut(e);
		});
	}
}

TJScroll.prototype.disableTouchEvents = function () {
	if(TJScroll.hasTouch || this.options.testTouch) {
		this.$wrapper.unbind(TJScroll.START_EV);	
	}
	
	if(!TJScroll.hasTouch && this.options.testTouch) {
		this.$wrapper.unbind('mouseout');
	}
}

TJScroll.prototype.enableScrollDrag = function () {
	var self = this;
				
	if(!TJScroll.hasTouch && !this.options.hideScrollbar) {
		if(this.hScrollbar) {
			this.$hScrollbarIndicator.bind('mousedown', function (e) {
				self.onScrollbarMouseDown(e);
			}).bind('mouseover', function (e) {
				self.onScrollbarMouseOver(e);
			}).bind('mouseout', function (e) {
				self.onScrollbarMouseOut(e);
			});
		}
		if(this.vScrollbar) {			
			this.$vScrollbarIndicator.bind('mousedown',  function (e) {
				self.onScrollbarMouseDown(e);
			}).bind('mouseover', function (e) {
				self.onScrollbarMouseOver(e);
			}).bind('mouseout', function (e) {
				self.onScrollbarMouseOut(e);
			});
		}
	}
}

TJScroll.prototype.disableScrollDrag = function () {
	if(!TJScroll.hasTouch && !this.options.hideScrollbar) {
		if(this.hScrollbar) {
			this.$hScrollbarIndicator.unbind('mousedown').unbind('mouseover').unbind('mouseout').removeClass('hover');
		}
		if(this.vScrollbar) {
			this.$vScrollbarIndicator.unbind('mousedown').unbind('mouseover').unbind('mouseout').removeClass('hover');
		}
	}
}

TJScroll.prototype.onScrollbarMouseDown = function (e) {
	var $target = $(e.currentTarget);
	var self = this;
	
	e.preventDefault();
		
	this.barStartX = this.x;
	this.barStartY = this.y;
	this.barDownX = e.pageX;
	this.barDownY = e.pageY;
	this.barActiveDir;
		
	if(this.hScrollbar && $target[0] == this.$hScrollbarIndicator[0]) {
		this.barActiveDir = 'h';
	}
	else if(this.vScrollbar && $target[0] == this.$vScrollbarIndicator[0]) {
		this.barActiveDir = 'v';
	}
	else {
		return;
	}
	
	$(document).bind('mouseup', function (e) {
		self.onScrollbarMouseUp(e);
	}).bind('mousemove', function (e) {
		self.onScrollbarMouseMove(e);
	});
}

TJScroll.prototype.onScrollbarMouseMove = function (e) {
	var dx = e.pageX - this.barDownX;
	var dy = e.pageY - this.barDownY;
	var factor = this[this.barActiveDir + 'ScrollbarProp'];
	var x, y;
		
	if(this.barActiveDir == 'h') {
		x = TJScroll.mround(this.barStartX + (dx / factor));
		
		if(x > 0) {
			x = 0;
		}
		else if(x < this.maxScrollX) {
			x = this.maxScrollX;
		}
		
		this.setPosition(x, 0);
	}
	else {
		y = TJScroll.mround(this.barStartY + (dy / factor));
		
		if(y > this.minScrollY) {
			y = this.minScrollY;
		}
		else if(y < this.maxScrollY) {
			y = this.maxScrollY;
		}
		
		this.setPosition(0, y);
	}
}

TJScroll.prototype.onScrollbarMouseUp = function (e) {
	this['$' + this.barActiveDir + 'ScrollbarIndicator'].removeClass('hover');
	
	this.barActiveDir = null;
	
	$(document).unbind('mousemove').unbind('mouseup');
}

TJScroll.prototype.onScrollbarMouseOver = function (e) {
	$(e.currentTarget).addClass('hover');
}

TJScroll.prototype.onScrollbarMouseOut = function (e) {
	var $target = $(e.currentTarget);
	
	if(this.barActiveDir == null || (this.barActiveDir == 'h' && $target[0] != this.$hScrollbarIndicator[0]) || (this.barActiveDir == 'v' &&  $target[0] != this.$vScrollbarIndicator[0])) {
		$(e.currentTarget).removeClass('hover');	
	}
}


/* utilities ******************************************************************/


TJScroll.prototype.createScrollbar = function (dir) {
	
	var hasBar = this[dir + 'Scrollbar'];
	var css;
	
	var $wrapper = this['$' + dir + 'ScrollbarWrapper'];
	var $indicator = this['$' + dir + 'ScrollbarIndicator'];
		
	if(!hasBar) {
		if($wrapper) {
			if(this.options.useTransform) {
				$indicator.css(TJScroll.vendor + 'Transform', '');
			}
			
			$wrapper.remove();
			this['$' + dir + 'ScrollbarWrapper'] = null;
			this['$' + dir + 'ScrollbarIndicator'] = null;
		}
		
		return;
	}
		
	if(!$wrapper) {
		$wrapper = $('<div></div>');
		
		if(this.options.scrollbarClass) {
			$wrapper.attr('class', this.options.scrollbarClass + dir.toUpperCase());
		}
		css = {
			'position': 'absolute',
			'z-index': 100,
			'overflow': 'hidden'
		};
		
		if(dir == 'h') {
			css['height'] = 7;
			css['bottom'] = 1;
			css['left'] = 2;
			css['right'] = this.$vScrollbar ? 7 : 2;
		}
		else {
			css['width'] = 7;
			css['bottom'] = this.$hScrollbar ? 7 : 2;
			css['top'] = 2;
			css['right'] = 1;
		}
		
		if(TJScroll.hasTransitionEnd) {
			css['-' + TJScroll.vendor + '-transition-property'] = 'opacity';
			css['-' + TJScroll.vendor + '-transition-duration'] = this.options.fadeScrollbar ? '350ms' : '0';
		}
				
		css['opacity'] = this.options.hideScrollbar ? '0' : '1';
	
		$wrapper.css(css);
				
		this.$wrapper.append($wrapper);
		this['$' + dir + 'ScrollbarWrapper'] = $wrapper;
		
		$indicator = $('<div></div>').attr('class', this.options.scrollbarClass + 'Indicator' + dir.toUpperCase());
		
		css = {
			'position':'absolute',
			'z-index':100,
			'background':'#000',
			'opacity':'0.5',
			'border':'1px solid #fff',
			'border-radius':'3px',
			'box-sizing':'border-box'
		}
		
		if(dir == 'h') {
			css['height'] = '100%';
		}
		else {
			css['width'] = '100%';
		}
		
		css['-' + TJScroll.vendor.toLowerCase() + '-background-clip'] = 'padding-box';
		css['-' + TJScroll.vendor.toLowerCase() + '-box-sizing'] = 'border-box';
		css['-' + TJScroll.vendor.toLowerCase() + '-border-radius'] = '3px';
		
		if(this.options.useTransform) {
			css['-' + TJScroll.vendor.toLowerCase() + '-transform'] = TJScroll.trnOpen + '0,0' + TJScroll.trnClose;
		}
		else {
			css['left'] = 0,
			css['top'] = 0
		}
		
		if(this.options.useTransition) {
			css['-' + TJScroll.vendor.toLowerCase() + '-transition-property'] = '-' + TJScroll.vendor + '-transform;';
			css['-' + TJScroll.vendor.toLowerCase() + '-transition-timing-function'] = 'cubic-bezier(0.33,0.66,0.66,1)';
			css['-' + TJScroll.vendor.toLowerCase() + '-transition-duration'] = 0;
		}
				
		$indicator.css(css);
		
		$wrapper.append($indicator);
		this['$' + dir + 'ScrollbarIndicator'] = $indicator;
	}
	
	if(dir == 'h') {
		this.hScrollbarSize = this.$hScrollbarWrapper.width();
		this.hScrollbarIndicatorSize = Math.max(TJScroll.mround(this.hScrollbarSize * this.hScrollbarSize / this.scrollerW), 8);
		this.hScrollbarMaxScroll = this.hScrollbarSize - this.hScrollbarIndicatorSize;
		this.hScrollbarProp = this.hScrollbarMaxScroll / this.maxScrollX;
		
		this.$hScrollbarIndicator.width(this.hScrollbarIndicatorSize);
	}
	else {
		this.vScrollbarSize = this.$vScrollbarWrapper.height();
		this.vScrollbarIndicatorSize = Math.max(TJScroll.mround(this.vScrollbarSize * this.vScrollbarSize / this.scrollerH), 8);
		this.vScrollbarMaxScroll = this.vScrollbarSize - this.vScrollbarIndicatorSize;
		this.vScrollbarProp = this.vScrollbarMaxScroll / this.maxScrollY;
		
		this.$vScrollbarIndicator.height(this.vScrollbarIndicatorSize);
	}

	// Reset position
	this.setScrollbarPosition(dir, true);
}

TJScroll.prototype.setScrollbarPosition = function (dir, hidden) {
	var pos = dir == 'h' ? this.x : this.y,
	$indicator, $wrapper,
	size;
	
	if(!this[dir + 'Scrollbar']) return;

	pos = this[dir + 'ScrollbarProp'] * pos;
	
	$indicator = this['$' + dir + 'ScrollbarIndicator'];
	$wrapper = this['$' + dir + 'ScrollbarWrapper'];

	if(pos < 0) {
		if(!this.options.fixedScrollbar) {
			size = this[dir + 'ScrollbarIndicatorSize'] + TJScroll.mround(pos * 3);
			if(size < 8) {
				size = 8;
			}
			
			if(dir == 'h') {
				$indicator.width(size);
			}
			else {
				$indicator.height(size);
			}
		}
		pos = 0;
	}
	else if(pos > this[dir + 'ScrollbarMaxScroll']) {
		if(!this.options.fixedScrollbar) {
			size = this[dir + 'ScrollbarIndicatorSize'] - TJScroll.mround((pos - this[dir + 'ScrollbarMaxScroll']) * 3);
			if(size < 8) {
				size = 8;
			}
			
			if(dir == 'h') {
				$indicator.width(size);
			}
			else {
				$indicator.height(size);
			}
			pos = this[dir + 'ScrollbarMaxScroll'] + (this[dir + 'ScrollbarIndicatorSize'] - size);
		}
		else {
			pos = this[dir + 'ScrollbarMaxScroll'];
		}
	}
	
	var css = {};
	css['-' + TJScroll.vendor + '-transition-delay'] = '0';
	css['opacity'] = hidden && this.options.hideScrollbar ? '0' : '1';
	
	$wrapper.css(css);
	
	css = {};
	
	if(this.options.useTransform) {
		css['-' + TJScroll.vendor + '-transform'] = TJScroll.trnOpen + (dir == 'h' ? pos + 'px,0' : '0,' + pos + 'px') + TJScroll.trnClose; 	
	}
	else {
		if(dir == 'h') {
			css['left'] = pos;
		}
		else {
			css['top'] = pos;
		}
	}
	
	$indicator.css(css);
}

TJScroll.prototype.doAnimation = function (isMomentum) {
	isMomentum = typeof(isMomentum) == 'undefined' ? false : true;
	
	var self = this,
	date = new Date(),
	startX = this.x,
	startY = this.y,
	startTime = date.getTime(),
	step, easeOut, animate;

	if(this.animating) return;
		
	if(!this.steps.length) {
		this.resetPosition(400);
		return;
	}
		
	step = this.steps.shift();
		
	if(step.x == startX && step.y == startY) step.time = 0;

	this.animating = true;
	this.moved = true;
		
	if(this.options.useTransition) {
		this.setTransitionTime(step.time);
		this.setPosition(step.x, step.y);
		this.animating = false;
		
		if(step.time) {
			this.$scroller.bind('webkitTransitionEnd', function (e) {
				self.onWebkitTransitionEnd(e);
			});
		}
		else {
			this.resetPosition(0);
		}
		
		return;
	}
		
	animate = function () {
		var date = new Date();
		var now = date.getTime(),
		dx, dy, easeOut,
		newX, newY;

		if(now >= startTime + step.time) {			
			self.setPosition(step.x, step.y);
			self.animating = false;
			
			if(self.options.onAnimationEnd) {
				self.options.onAnimationEnd.call(self);
			}
			
			self.doAnimation();
			return;
		}
		
		if(isMomentum) {
			now = (now - startTime) / step.time - 1;
			easeOut = Math.sqrt(1 - now * now);
			newX = (step.x - startX) * easeOut + startX;
			newY = (step.y - startY) * easeOut + startY;
		}
		else {
			newX = startX;
			newY = startY;
			dx = step.x - startX;
			dy = step.y - startY;
			
			easeOut = $.easing[self.options.ease](null, now - startTime, 0, 1, step.time);
					
			// current time, start, change, duration
			if(dx > 0 || dx < 0) {
				newX = startX + (dx * easeOut);
			}
			if(dy > 0 || dy < 0) {
				newY = startY + (dy * easeOut);	
			}
		}
				
		self.setPosition(newX, newY);
		
		if(self.animating) {
			self.aniTime = requestAnimationFrame(animate);
		}
	};

	animate();
}

TJScroll.prototype.setTransitionTime = function (time) {
	time += 'ms';

	var css = {};
	css['-' + TJScroll.vendor + '-transition-duration'] = time;
	
	this.$scroller.css(css);
	
	if(this.hScrollbar) {
		this.$hScrollbarIndicator.css('-' + TJScroll.vendor + '-transition-duration', time);
	}
	if(this.vScrollbar) {
		this.$vScrollbarIndicator.css('-' + TJScroll.vendor + '-transition-duration', time);
	}
}

TJScroll.prototype.calculateMomentum = function (dist, time, maxDistUpper, maxDistLower, size) {
	var deceleration = 0.0006,
	speed = Math.abs(dist) / time,
	newDist = (speed * speed) / (2 * deceleration),
	newTime = 0, outsideDist = 0;

	// Proportinally reduce speed if we are outside of the boundaries 
	if (dist > 0 && newDist > maxDistUpper) {
		outsideDist = size / (6 / (newDist / speed * deceleration));
		maxDistUpper = maxDistUpper + outsideDist;
		speed = speed * maxDistUpper / newDist;
		newDist = maxDistUpper;
	}
	else if (dist < 0 && newDist > maxDistLower) {
		outsideDist = size / (6 / (newDist / speed * deceleration));
		maxDistLower = maxDistLower + outsideDist;
		speed = speed * maxDistLower / newDist;
		newDist = maxDistLower;
	}

	newDist = newDist * (dist < 0 ? -1 : 1);
	newTime = speed / deceleration;

	return {dist:newDist, time:TJScroll.mround(newTime)};
}

TJScroll.prototype.calculateSnap = function (x, y) {
	var i, l,
	page, time,
	sizeX, sizeY;

	// Check page X
	page = this.pagesX.length - 1;
	
	for(i=0, l=this.pagesX.length; i<l; ++i) {
		if (x >= this.pagesX[i]) {
			page = i;
			break;
		}
	}
	
	if(page == this.currPageX && page > 0 && this.dirX < 0) {
		--page;
	}
	
	x = this.pagesX[page];
	sizeX = m.abs(x - this.pagesX[this.currPageX]);
	sizeX = sizeX ? m.abs(this.x - x) / sizeX * 500 : 0;
	
	this.currPageX = page;

	// Check page Y
	page = this.pagesY.length-1;
	
	for (i=0; i<page; ++i) {
		if (y >= this.pagesY[i]) {
			page = i;
			break;
		}
	}
	
	if (page == this.currPageY && page > 0 && this.dirY < 0) {
		--page;
	}
	
	y = this.pagesY[page];
	sizeY = m.abs(y - this.pagesY[this.currPageY]);
	sizeY = sizeY ? m.abs(this.y - y) / sizeY * 500 : 0;
	
	this.currPageY = page;

	// Snap with constant speed (proportional duration)
	time = TJScroll.mround(Math.max(sizeX, sizeY)) || 200;

	return {x:x, y:y, time:time};
}

TJScroll.prototype.getPositionCss = function (css, x, y) {	
	if(this.options.useTransform) {
		css[TJScroll.vendor + 'Transform'] = TJScroll.trnOpen + x + 'px,' + y + 'px' + TJScroll.trnClose;
	}
	else {
		css['left'] = TJScroll.mround(x) + 'px';
		css['top'] = TJScroll.mround(y) + 'px';
	}
	
	return css;
}

TJScroll.prototype.resetPosition = function (time) {
	var resetX = this.x >= 0 ? 0 : this.x < this.maxScrollX ? this.maxScrollX : this.x,
	resetY = this.y >= this.minScrollY || this.maxScrollY > 0 ? this.minScrollY : this.y < this.maxScrollY ? this.maxScrollY : this.y;
	
	if(resetX == this.x && resetY == this.y) {
		if(this.moved) {
			this.moved = false;
			if (this.options.onScrollEnd) this.options.onScrollEnd.call(this);		// Execute custom code on scroll end
		}
		
		if(this.options.hideScrollbar) {
			if(TJScroll.vendor == 'webkit') {
				var css = {};
				css['-' + TJScroll.vendor + '-transition-delay'] = '300ms';
				
				if(this.hScrollbar) {
					this.$hScrollbarWrapper.css(css);
					this.hideScrollbar('h');
				}
				if(this.vScrollbar) {
					this.$vScrollbarWrapper.css(css);
					this.hideScrollbar('v');
				}
			}
		}

		return;
	}
		
	this.scrollTo(resetX, resetY, time || 0);
}