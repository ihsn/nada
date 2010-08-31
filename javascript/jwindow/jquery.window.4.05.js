/**
 * jQuery Window Plugin - To Popup A Beautiful Window-like Dialog
 * http://fstoke.net/jquery/window/
 * Copyright(c) 2009 David Hung
 * Dual licensed under the MIT and GPL licenses
 * Version: 4.05
 * Last Revision: 2009-12-17
 *
 * The window status is defined as: cascade(default status), minimized, maxmized
 * The code style is reference from javascript Singleton design pattern. Please see:
 * http://fstoke.net/blog/?p=1219
 *
 * This jQuery plugin has been tested in the following browsers:
 * - IE 7, 8
 * - Firefox 3.5
 * - Opera 9, 10
 * - Safari 4.0
 * - Chrome 2.0+
 *
 * Required jQuery Libraries:
 * jquery.js         (v1.3.2+)
 * jquery-ui.js      (v1.7.2+)
 *
 * Customized Button JSON Array Sample:
	var myButtons = [
		// facebook button
		{
		id: "btn_facebook",           // required, it must be unique in this array data
		title: "share to facebook",   // optional, it will popup a tooltip by browser while mouse cursor over it
		clazz: "my_button",           // optional, don't set border, padding, margin or any style which will change element position or size
		style: "",                    // optional, don't set border, padding, margin or any style which will change element position or size
		image: "img/facebook.gif",    // required, the url of button icon(16x16 pixels)
		callback:                     // required, the callback function while click it
			function(btn, wnd) {
				wnd.getContainer().find("#demo_text").text("Share to facebook!");
				wnd.getContainer().find("#demo_logo").attr("src", "img/facebook_300x100.png");
			}
		},
		// twitter button
		{
		id: "btn_twitter",
		title: "share to twitter",
		clazz: "my_button",
		style: "background:#eee;",
		image: "img/twitter.png",
		callback:
			function(btn, wnd) {
				wnd.getContainer().find("#demo_text").text("Share to twitter!");
				wnd.getContainer().find("#demo_logo").attr("src", "img/twitter_300x100.jpg");
			}
		}
	];
 *
 *
 * Known Issues:
 * ISU_002 - The window could be resized overlay its parent even it's set boundary within its parent.
 * ISU_005 - When window maximized, even the browser scroll bar is already disabled, it still can be scrolled in Opera.
 * ISU_006 - When window maximized, the browser scroll bar still exists until browser REPAINT in IE7. IE8 is fine.
 *           (This case seems only happen at the window content is not a iframe)
 *           (What time the browser will repaint? -> popup a alert dialog, switch browser page tab, alt + tab to switch application...etc.)
 * ISU_007 - It will delay display new title while window is minimized, until browser REPAINT. IE7/8 both happens.
 *           This case root cause seems is IE browser can't handle a element whose position is "fixed" correctly.
 *
 * Fixed Issues:
 * ISU_001 - If drag & resize a window first, then do maximize, the window will disappear while mouse down the header panel. It will appear again when mouse up.
 * ISU_003 - When resizing over an IFRAME, it lost the mouse event. - Thank for crabo!
 * ISU_004 - When the window is maximized, mouse scroll and text input delay about 3 seconds in IE browser.
 *           It's caused by IE browser can't support css position attribute "fixed" correctly.
 *
 **************************************************************************************************************************************
 * Version Update Change Log
 *
 * Version 4.05 change log:
 * - Add round corner dispaly control options - showRoundCorner
 * - Change mouse cursor display on header panel by three different status: normal, minimized, maximized
 * - Fix change title text dynamically display error while window is minimized
 *
 * Version 4.04 change log:
 * - Change all callback functions parameter to window instance instead of container
 * - Add new options attribute - showFooter, to control show footer panel
 * 
 * Version 4.03 change log:
 * - Modify header and footer border css style to make display correctly
 * - Modify window icon button css style to fix customized buttons display bug
 *
 * Version 4.02 change log:
 * - Add functions for change window display dynamically - setTitle, setUrl, setContent, setFooterContent
 *
 * Version 4.01 change log:
 * - Add backgroundColor value check in private hideContent function to avoid display bug in Firefox2. Thanks for crabo.
 * - Add a instance public function - close, instead of destroy function. It does the same routine, but have a boolean parameter to decide doing callback. 
 * - Add parameter: quiet in closeAll function, it decide doing close callback or not.
 *
 * Version 4.00 change log:
 * - Change code to another javascript OOP style, more structural, readable, easy manageable and extending
 * - Modify maximized status css position attribute as "absolute" to fix ISU_004 issue.
 *   And also disable browser(except Opera) scrollbar concurrently to avoid user have chance to change maxmized window's view position.
 *   But it got side effect, see issue ISU_005, ISU_006. Even so, at least IE user can read/operate content normally while window maxmized. 
 * - Support jquery ui theme now
 * - Move animationSpeed from options to global window attribute
 * - Add options new attribute: createRandomOffset, to make window have a random offset position while creating
 * - Add options new attribute: footerContent, to let put something on window footer
 * - Add options new attribute: containerClass, headerClass, frameClass, footerClass to make customize window look-like possible
 * - Add handle dblclick event on header panel, it will do maximize or restore by window status
 * - options content attribute also accept a jQuery object or html element now
 * - Customized buttons callback function parameter change from a element to a jquery object, and add an extra container parameter
 * - Modify public function name setFocus, releaseFocus to select and unselect
 * - Merge icon buttons as one image, and change bookmark icon
 * - Fix issues: ISU_001, ISU_003, ISU_004
 * - Fix serveral UI bug
 * 
 * Version 3.03 change log:
 * - Modify window icon button margin-right to "4px"
 *
 * Version 3.02 change log:
 * - Fix serveral UI display bugs
 *
 * Version 3.01 change log:
 * - Availalbe to create customized icon buttons with passing a element JSON Array
 *
 * Version 3.00 change log:
 * - Add maximize feature
 * - Add adjust the width of header title text panel while window is resized. And add css style text-overflow: ellipsis; to let browser
 *   auto modify title text with "..." at the end while text width exceed panel width.
 * - Add create window from a html element, and could be limit boundary within it.
 * - Add iframeRedirectCheckMsg attribute to make iframe redirect checking mechanism more simple
 * - Add custBtns attribute to implement customized icon button feature.
 * - Modify bookmark not support message for Chrome, Safari and Opera.
 * - Modify option attribute "zIndex" to simpler "z"
 * - Add option attribute "animationSpeed" to control the animation speed
 *
 * Version 2.03 change log:
 * - Add three options attribute to control show function button(close, minimize, bookmark) or not
 *
 * Version 2.02 change log:
 * - Modify css class name to avoid conflict with others
 *
 * Version 2.01 change log:
 * - Fix checkBoundary work incorrectly bug.
 * - Add show loading animation while opening a iframe window.
 *
 * Version 2.00 change log:
 * - Modify call function method. It doesn't need caller panel now, plugin will add a div element in html body automatically.
 * - Add minimized & restore feature.
 *
 * Version 1.02 change log:
 * - Fix second call, return wrong value bug
 * - Add set focus feature
 *
 * Version 1.01 change log:
 * - Change window panel css attribute: position to "fixed" to let window keep position on screen.
 * - Fix auto align center incorrectly bug
 *
 */
	
// Get window instance via jQuery call
// create window on html body
$.window = function(options) {
	return $.Window.getInstance(null, options);
};

// create window on caller element
$.fn.window = function(options) {
	return $.Window.getInstance($(this), options);
}

// Creating Window Dialog Module
$.Window = (function()  {
	// static private methods
	// static constants
	var VERSION = "4.05";           // the version of current plugin
	var ICON_WH = 16;               // window icon button width/height, in pixels. check "window_icon_button" style in css
	var ICON_MARGIN = 4;            // window icon button margin, in pixels. check "window_icon_button" style in css
	var ICON_OFFSET = ICON_WH + ICON_MARGIN; // window icon button offset for decide function bar width in header panel
	var OPACITY_MINIMIZED = 0.7;    // css opacity while window minimized or doing animation
	var ua = navigator.userAgent.toLowerCase(); // browser useragent
	
	// static variables
	var animationSpeed = 400;       // the speed of various animations: maximize, minimize, restore, shift, in milliseconds
	var windowIndex = 0;            // index to create window instance id
	var lastSelectedWindow = null;  // to remember last selected window instance
	var windowStorage = [];         // a array to store created window instance
	var minWindowStorage = [];      // a array to store minimized window instance
	
	// select the current clicked window instance, concurrently, unselect last selected window instance
	function selectWindow(wnd) {
		if( lastSelectedWindow != null ) {
			lastSelectedWindow.unselect();
		}
		if( typeof wnd == 'object' ) { // it's window instance
			lastSelectedWindow = wnd;
		} else if( typeof wnd == 'string' ) { // it's window id
			lastSelectedWindow = $("#"+wnd).get(0).windowInstance;
		}
		lastSelectedWindow.select();
	}
	
	// get the window instance
	function getWindow(id) {
		for( var i=0; i<windowStorage.length; i++ ) {
			var wnd = windowStorage[i];
			if( wnd.getWindowId() == windowId ) {
				return wnd;
			}
		}
	}
	
	// push the window instance into storage
	function pushWindow(wnd) {
		if( typeof wnd == 'object' ) { // it's window instance
			windowStorage.push(wnd);
		} else if( typeof wnd == 'string' ) { // it's window id
			windowStorage($("#"+wnd).get(0).windowInstance);
		}
	}
	
	// pop the window instance from storage out
	function popWindow(windowId) {
		for( var i=0; i<windowStorage.length; i++ ) {
			var wnd = windowStorage[i];
			if( wnd.getWindowId() == windowId ) {
				windowStorage.splice(i--,1); // remove array element
				break;
			}
		}
	}
	
	// push the window instance into minimized storage
	function pushMinWindow(windowId) {
		minWindowStorage.push($("#"+windowId).get(0).windowInstance);
	}
	
	// pop the window instance from minimized storage out
	function popMinWindow(windowId) {
		var doAdjust = false;
		for( var i=0; i<minWindowStorage.length; i++ ) {
			var wnd = minWindowStorage[i];
			if( wnd.getWindowId() == windowId ) {
				minWindowStorage.splice(i--,1); // remove array element
				doAdjust = true;
				continue;
			}
			if( doAdjust ) {
				var position = wnd.getContainer().position();
				//var top = position.top - 120;
				var top = i * 120;
				wnd.getContainer().animate({
					top: top
				}, animationSpeed);

				// modify target css style
				wnd.getTargetCssStyle().top = top;
			}
		}
	}
	
	// hide browser scroll bar
	function hideBrowserScrollbar() {
		if( ua.indexOf("msie 7") >= 0 ) { // fix IE7
			$("body").attr("scroll", "no");
		} else {
			document.body.style.overflow = "hidden";
		}
	}
	
	// show browser scroll bar
	function showBrowserScrollbar() {
		if( ua.indexOf("msie 7") >= 0 ) { // fix IE7
			$("body").removeAttr("scroll");
		} else {
			document.body.style.overflow = "auto";
		}
	}
	
	function getBrowserScreenWH() {
		var width = document.documentElement.clientWidth;
		var height = document.documentElement.clientHeight;
		return {width:width, height:height};
	}

	function getBrowserScrollXY() {
		var scrOfX = 0, scrOfY = 0;
		if( typeof( window.pageYOffset ) == 'number' ) {
			//Netscape compliant
			scrOfY = window.pageYOffset;
			scrOfX = window.pageXOffset;
		} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
			//DOM compliant
			scrOfY = document.body.scrollTop;
			scrOfX = document.body.scrollLeft;
		} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
			//IE6 standards compliant mode
			scrOfY = document.documentElement.scrollTop;
			scrOfX = document.documentElement.scrollLeft;
		}
		return {left:scrOfX, top:scrOfY};
	}
	
	function constructor(caller, options) {
		// instance private methods
		// flag & variables
		var me = null;                  // to remember current window instance
		var windowId = "window_" + (windowIndex++); // the window's id
		var absolute = true;            // a boolean flag to decide window css position attribute is "absolute", it should be true always
		var minimized = false;          // a boolean flag to tell the window is minimized
		var maximized = false;          // a boolean flag to tell the window is maximized
		var redirectCheck = false;      // a boolean flag to control popup message or not while browser is going to leave this page
		var pos = new Object();         // to save cascade mode current position
		var wh = new Object();          // to save cascade mode current width & height
		var orgPos = new Object();      // to save position before minimize
		var orgWh = new Object();       // to save width & height before minimize
		var targetCssStyle = {};        // to save target css style json object
		var funcBarWidth = 0;           // the width of header function bar
		
		// element
		var container = null;           // whole window container element
		var header = null;              // the header panel of window. it includes title text and buttons
		var frame = null;               // the content panel of window. it could be a iframe or a div element, depending on which way you create it
		var footer = null;              // the footer panel of window. currently, it got nothing, but maybe a status bar or something will be added in the future 
		
		// the instance options
		var options = $.extend({
			title: "",                    // [string:""] the title text of window
			url: "",                      // [string:""] the target url of iframe ready to load.
			content: "",                  // [html string, jquery object, element:""] this attribute only works when url is null. when passing a jquery object or a element, it will clone the original one to append.
			footerContent: "",            // [html string, jquery object, element:""] same as content attribute, but it's put on footer panel.
			containerClass: "",           // [string:""] container extra class
			headerClass: "",              // [string:""] header extra class
			frameClass: "",               // [string:""] frame extra class
			footerClass: "",              // [string:""] footer extra class
			x: -1,                        // [number:-1] the x-axis value on screen(or caller element), if -1 means put on screen(or caller element) center
			y: -1,                        // [number:-1] the y-axis value on screen(or caller element), if -1 means put on screen(or caller element) center
			z: 2000,                      // [number:2000] the css z-index value
			width: 400,                   // [number:400] window width
			height: 300,                  // [number:300] window height
			minWidth: 200,                // [number:200] the minimum width, if -1 means no checking
			minHeight: 150,               // [number:150] the minimum height, if -1 means no checking
			maxWidth: 800,                // [number:800] the maximum width, if -1 means no checking
			maxHeight: 600,               // [number:600] the maximum height, if -1 means no checking
			showFooter: true,             // [boolean:true] to control show footer panel
			showRoundCorner: false,       // [boolean:true] to control display window as round corner
			closable: true,               // [boolean:true] to control window closable
			minimizable: true,            // [boolean:true] to control window minimizable
			maximizable: true,            // [boolean:true] to control window maximizable
			bookmarkable: true,           // [boolean:true] to control window with remote url could be bookmarked
			draggable: true,              // [boolean:true] to control window draggable
			resizable: true,              // [boolean:true] to control window resizable
			scrollable: true,             // [boolean:true] to show scroll bar or not
			checkBoundary: false,         // [boolean:false] to check window dialog overflow html body or caller element
			custBtns: null,               // [json array:null] to describe the customized button display & callback function
			onOpen: null,                 // [function:null] a callback function while container is added into body
			onShow: null,                 // [function:null] a callback function while whole window display routine is finished
			onClose: null,                // [function:null] a callback function while user click close button
			onIframeStart: null,          // [function:null] a callback function while iframe ready to connect remoting url. this attribute only works while url attribute is given
			onIframeEnd: null,            // [function:null] a callback function while iframe load finished. this attribute only works while url attribute is given
			iframeRedirectCheckMsg: null, // [string:null] if null means no check, or pass a string to show warning message while iframe is going to redirect
			createRandomOffset: {x:200, y:150}, // [json object:{x:200, y:150}] random the new created window position, it only works when options x,y value both are -1
			showLog: false                // [boolean:false] to show log in firebug, IE8, chrome console
		}, options);
		
		function initialize(instance) {
			me = instance;
			// build html
			var realCaller = caller != null? caller:$("body");
			var cornerClass = options.showRoundCorner? "ui-corner-all ":"";
			realCaller.append("<div id='"+windowId+"' class='window_panel "+cornerClass+options.containerClass+"'></div>");
			container = realCaller.children("div#"+windowId);
	
			// onOpen call back
			if( $.isFunction(options.onOpen) ) {
				options.onOpen(me);
			}
	
			wh.w = options.width;
			wh.h = options.height;
			container.width(options.width);
			container.height(options.height);
			container.css("position", absolute ? 'absolute' : 'fixed'); // must do this, or it will occurs display error
			container.css("z-index", options.z);
	
			// set position x
			if( options.x >= 0 || options.y >= 0 ) {
				if( options.x >= 0 ) {
					var pLeft = 0;
					if( caller != null ) {
						pLeft = options.x + caller.offset().left;
					} else {
						pLeft = options.x + jQuery(window).scrollLeft();
					}
					container.css("left", pLeft);
				} else { // put on center
					alignHorizontalCenter();
				}
	
				// set position y
				if( options.y >= 0 ) {
					var pTop = 0;
					if( caller != null ) {
						pTop = options.y + caller.offset().top;
					} else {
						pTop = options.y + jQuery(window).scrollTop();
					}
					container.css("top", pTop);
				} else { // put on middle
					alignVerticalCenter();
				}
			} else {
				alignCenter();
			}
			// feed x,y with real pixel value(not a percentage), to avoid "JUMPING" while window restore from minized status
			var currPos = container.position();
			container.css({
				left: currPos.left,
				top: currPos.top
			});
	
			// build header html
			cornerClass = options.showRoundCorner? "ui-corner-top ":"";
			var headerHtml = "<div class='window_header window_header_normal ui-widget-header "+cornerClass+"no-resizable "+options.headerClass+"'>"+
				"<div class='window_title_text'>"+options.title+"</div>"+
				"<div class='window_function_bar'></div>"+
				"</div>";
			container.append(headerHtml);
			header = container.children("div.window_header");
			
			// bind double click event with doing maximize action
			if( options.maximizable ) {
				header.dblclick(function() {
					if( maximized ) {
						restore();
					} else {
						maximize();
					}
				});
			}
			
			var headerFuncPanel = header.children("div.window_function_bar");
			// add close button
			if( options.closable ) {
				headerFuncPanel.append( "<div title='close window' class='closeImg window_icon_button no-draggable'></div>" );
				headerFuncPanel.children(".closeImg").click(function() {
					close();
				});
				funcBarWidth += ICON_OFFSET;
			}
	
			// add maxmize button
			if( options.maximizable ) {
				headerFuncPanel.append( "<div title='maximize window' class='maximizeImg window_icon_button no-draggable'></div>" );
				headerFuncPanel.append( "<div title='cascade window' class='cascadeImg window_icon_button no-draggable' style='display:none;'></div>" );
				headerFuncPanel.children(".maximizeImg").click(function() {
					maximize();
				});
				headerFuncPanel.children(".cascadeImg").click(function() {
					restore();
				});
				funcBarWidth += ICON_OFFSET;
			}
	
			// add minimize button
			if( options.minimizable ) {
				headerFuncPanel.append( "<div title='minimize window' class='minimizeImg window_icon_button no-draggable'></div>" );
				headerFuncPanel.children(".minimizeImg").click(function() {
					minimize();
				});
				funcBarWidth += ICON_OFFSET;
			}
	
			// add bookmark button
			if( options.bookmarkable && options.url != null && $.trim(options.url) != "" ) {
				headerFuncPanel.append( "<div title='bookmark this' class='bookmarkImg window_icon_button no-draggable'></div>" );
				headerFuncPanel.children(".bookmarkImg").click(function() {
					doBookmark(options.title, options.url);
				});
				funcBarWidth += ICON_OFFSET;
			}
	
			// add customized buttons
			addCustomizedButtns(headerFuncPanel);
			
			// make buttons don't pass dblclick event to header panel 
			$(".window_icon_button").dblclick(function() {
				return false;
			});
	
			// set text & function bar width
			adjustHeaderTextPanelWidth();
			headerFuncPanel.width( funcBarWidth );
	
			// build iframe html
			var frameHeight = getFrameHeight(wh.h);
			if( options.url != null && $.trim(options.url) != "" ) {
				// iframe starting call back
				if( $.isFunction(options.onIframeStart) ) {
					log("start connecting iframe: "+options.url);
					options.onIframeStart(me, options.url);
				}
	
				// add iframe redirect checking
				if( options.iframeRedirectCheckMsg ) {
					redirectCheck = true;
					window.onbeforeunload = function() {
						if( redirectCheck ) {
							var msg = options.iframeRedirectCheckMsg.replace("{url}", options.url);
							return msg;
						}
					}
				}
	
				// show loading image
				container.append("<div class='frame_loading'>Loading...</div>");
				var loading = container.children(".frame_loading");
				loading.css("marginLeft",	'-' + (loading.outerWidth() / 2) - 20 + 'px');
				loading.click(function() {
					loading.remove();
				});
	
				// append iframe html
				var scrollingHtml = options.scrollable? "yes":"no";
				container.append("<iframe style='display:none;' class='window_frame ui-widget-content no-draggable no-resizable "+options.frameClass+"' scrolling='"+scrollingHtml+"' src='"+options.url+"' width='100%' height='"+frameHeight+"px' frameborder='0'></iframe>");
				frame = container.children(".window_frame");
	
				// iframe load finished call back
				frame.ready(function() {
					frame.show();
				});
	
				frame.load(function() {
					redirectCheck = false;
					loading.remove();
					log("load iframe finished: "+options.url);
					if( $.isFunction(options.onIframeEnd) ) {
						options.onIframeEnd(me, options.url);
					}
				});
			} else {
				container.append("<div class='window_frame ui-widget-content no-draggable no-resizable "+options.frameClass+"' style='width:100%; height:"+frameHeight+"px;'></div>");
				frame = container.children(".window_frame");
				if( options.content != null ) {
					setContent(options.content);
					frame.children().show();
				}
				frame.css({
					overflow: options.scrollable? "auto":"hidden"
				});
			}
	
			// build footer html
			if( options.showFooter ) {
				cornerClass = options.showRoundCorner? "ui-corner-bottom ":"";
				container.append("<div class='window_footer ui-widget-content "+cornerClass+"no-draggable "+options.footerClass+"'><div></div></div>");
				footer = container.children("div.window_footer");
				if( options.footerContent != null ) {
					setFooterContent(options.footerContent);
					footer.children("div").children().show();
				}
			} else {
				cornerClass = options.showRoundCorner? "ui-corner-bottom ":"";
				frame.addClass(cornerClass);
			}
	
			// bind container handle mousedown event
			container.mousedown(function() {
				selectWindow(windowId);
			});
	
			// make window draggable
			if( options.draggable ) {
				container.draggable({
					cancel: ".no-draggable",
					start: function() {
						log( "drag start" );
						if( minimized || maximized ) { // if window is minimized or maximized, reset the css style
							container.css("position", "fixed");
							container.css(targetCssStyle);
						}
						showOverlay();
						hideContent();
					},
					stop: function() {
						log( "drag stop" );
						if( minimized || maximized ) { // if window is minimized or maximized, reset the css style
							container.css("position", "fixed");
							container.css(targetCssStyle);
						}
						hideOverlay();
						showContent();
					}
				});
				// set boundary if got opotions
				if( options.checkBoundary ) {
					container.draggable('option', 'containment', 'parent');
				}
			}
	
			// make window resizable
			if( options.resizable ) {
				container.resizable({
					cancel: ".no-resizable",
					alsoResize: frame,
					start: function() { // this will be triggered when window is going to drag in minimized or maximized mode
						log( "resize start" );
						if( minimized || maximized ) { // if window is minimized or maximized, reset the css style
							container.css("position", "fixed");
							container.css(targetCssStyle);
						}
						showOverlay();
						hideContent();
					},
					stop: function() {
						log( "resize stop" );
						if( minimized || maximized ) { // if window is minimized or maximized, reset the css style
							container.css("position", "fixed");
							container.css(targetCssStyle);
						}
						hideOverlay();
						adjustHeaderTextPanelWidth();
						showContent();
					}
				});
				// set boundary if got opotions
				if( options.checkBoundary ) {
					// this got bug, so mark it temporarily
					//container.resizable('option', 'containment', "parent");
				}
	
				// set resize min, max width & height
				if( options.maxWidth >= 0 ) {
					container.resizable('option', 'maxWidth', options.maxWidth);
				}
				if( options.maxHeight >= 0 ) {
					container.resizable('option', 'maxHeight', options.maxHeight);
				}
				if( options.minWidth >= 0 ) {
					container.resizable('option', 'minWidth', options.minWidth);
				}
				if( options.minHeight >= 0 ) {
					container.resizable('option', 'minHeight', options.minHeight);
				}
			}
	
			// handle window resize event
			$(window).resize(function() {
				if( maximized ) {
					if( minimized ) {
						// reset the restore width/height
						var screenWH = getBrowserScreenWH();
						orgWh.w = screenWH.width;
						orgWh.h = screenWH.height;
					} else {
						maximize(true, true);
					}
				}
			});
	
			// onShow call back
			if( $.isFunction(options.onShow) ) {
				options.onShow(me);
			}
		}
		
		function log(msg) {
			if(options != null && options.showLog && window.console != null) {
				console.log(msg);
			}
		}
		
		function setTitle(title) {
			options.title = title;
			header.children(".window_title_text").text(title);
			if( minimized ) {
				transformTitleText();
			}
		}
		
		function setUrl(url) {
			options.url = url;
			frame.attr("src", url);
		}
		
		function setContent(content) {
			options.content = content;
			if( typeof content == 'object' ) {
				content = $(content).clone(true);
			} else if( typeof content == 'string' ) {
				// using original content
			}
			frame.empty();
			frame.append(content);
		}
		
		function setFooterContent(content) {
			if( options.showFooter ) {
				options.footerContent = content;
				if( typeof content == 'object' ) {
					content = $(content).clone(true);
				} else if( typeof content == 'string' ) {
					// using original content
				}
				footer.children("div").empty();
				footer.children("div").append(content);
			}
		}
		
		// popup a overlay panel block whole screen while window dragging or resizing
		// to avoid lost event while mouse cursor over iframe region. [ISU_003]
		function showOverlay() {
			var overlay = $("#window_overlay");
			if( overlay.get(0) == null ) {
				$("body").append("<div id='window_overlay'>&nbsp;</div>");
				overlay = $("#window_overlay");
				overlay.css({
					zIndex: options.z + 1
				});
			}
			overlay.show();
		}
		
		function hideOverlay() {
			$("#window_overlay").hide();
		}
		
		function transferToFixed() {
			var currPos = container.offset();
			var scrollPos = getBrowserScrollXY();
			container.css({
				position: "fixed", // this will cause IE brwoser UI error, See ISU_004
				left: currPos.left - scrollPos.left,
				top: currPos.top - scrollPos.top,
				marginLeft: 0,
				marginTop: 0
			});
		}
	
		function transferToAbsolute() {
			var currPos = container.offset();
			container.css({
				position: "absolute",
				left: currPos.left,
				top: currPos.top
			});
		}
	
		function addCustomizedButtns(headerFuncPanel) {
			if( options.custBtns != null && typeof options.custBtns == 'object' ) {
				for( var i=0; i<options.custBtns.length; i++ ) {
					var btnData = options.custBtns[i];
					if( btnData != null && typeof btnData == 'object' ) {
						if( btnData.id != null && btnData.image != null && btnData.callback != null ) { // it's a JSON object
							var id = btnData.id != null? btnData.id:"";
							var clazz = btnData.clazz != null? btnData.clazz:"";
							var title = btnData.title != null? btnData.title:"";
							var style = btnData.style != null? btnData.style:"";
							var image = btnData.image != null? btnData.image:"";
							var callback = btnData.callback != null? btnData.callback:"";
							headerFuncPanel.append( "<img id='"+id+"' src='"+image+"' title='"+title+"' class='"+clazz+" window_icon_button no-draggable' style='"+style+"'/>" );
							var btn = headerFuncPanel.children("img[id="+id+"]");
							btn.get(0).clickCb = callback;
							if( $.isFunction(callback)) {
								btn.click(function() {
									this.clickCb($(this), me);
								});
							}
						} else { // it's a html element(or wrapped with jQuery)
							var btn = $(btnData).clone(true);
							btn.addClass("window_icon_button no-draggable cust_button");
							headerFuncPanel.append( btn );
							btn.show();
						}
					}
					funcBarWidth += ICON_OFFSET;
				}
			}
		}
	
		function adjustHeaderTextPanelWidth() {
			header.children("div.window_title_text").width( header.width() - funcBarWidth - 10 );
		}
	
		function adjustFrameWH() {
			var width = container.width();
			var height = container.height();
			var frameHeight = getFrameHeight(height);
			frame.width( width );
			frame.height( frameHeight );
		}
	
		function doBookmark(title, url) {
			if ( $.browser.mozilla && window.sidebar ) { // Mozilla Firefox Bookmark
				window.sidebar.addPanel(title, url, "");
			} else if( $.browser.msie && window.external ) { // IE Favorite
				window.external.AddFavorite( url, title);
			} else if( ua.indexOf("chrome") >= 0 ) { // Chrome
				alert("Sorry! Chrome doesn't support bookmark function currently.");
				//alert("Press [Ctrl + D] to bookmark in Chrome");
			} else if($.browser.safari || ua.indexOf("safari") >= 0 ) { // Safari
				alert("Sorry! Safari doesn't support bookmark function currently.");
				//alert("Press [Ctrl + D] to bookmark in Safari");
			} else if($.browser.opera || ua.indexOf("opera") >= 0 ) { // Opera Hotlist
				alert("Sorry! Opera doesn't support bookmark function currently.");
				//alert("Press [Ctrl + D] to bookmark in Opera");
			}
		}
	
		function hideContent() {
			//log("hideContent");
			var bgColor = frame.css("backgroundColor");
			if( bgColor != null && bgColor != "transparent" ) {
				container.css("backgroundColor", bgColor);
			}
			frame.hide();
			if( options.showFooter ) {
				footer.hide();
			}
			container.css("opacity", OPACITY_MINIMIZED);
		}
	
		function showContent() {
			//log("showContent");
			frame.show();
			if( options.showFooter ) {
				footer.show();
			}
			container.css("opacity", 1);
		}
	
		function getFrameHeight(windowHeight) {
			var footerHeight = options.showFooter? 16:0;
			return windowHeight - 20 - footerHeight - 4; // minus header & footer & iframe's padding height
		}
	
		// modify title text as vertical presentation
		function transformTitleText() {
			var textBlock = header.children("div.window_title_text");
			var text = textBlock.text();
			var buf = "";
			var limitHeight = 120 - 7 - 13; // total height - padding height - one font height
			for( var i=0; i<text.length; i++ ) {
				var c = text.charAt(i);
				if( c == "-" || c == "_" ) {
					c = "|";
				}
				if( c == " " ) {
					c = "<div style='height:5px; line-height:5px;'>&nbsp;</div>";
					buf += c;
				} else {
					buf += c+"<br>";
				}
				textBlock.html(buf);
				//log( textBlock.html() +': ' +textBlock.height() +','+textBlock.outerHeight());
				if( textBlock.outerHeight() + 13 > limitHeight ) {
					buf += ":";
					textBlock.html(buf);
					break;
				}
			}
		}
	
		function restoreTitleText() {
			var textBlock = header.children("div.window_title_text");
			textBlock.text(options.title);
		}
		
		// public
		function select() {
			container.css("z-index", options.z + 2);
		}
	
		function unselect() {
			container.css("z-index", options.z);
		}
	
		function getContainer() {
			return container;
		}
	
		function getHeader() {
			return header;
		}
	
		function getFrame() {
			return frame;
		}
	
		function getFooter() {
			return footer;
		}
		
		function getTargetCssStyle() {
			return targetCssStyle;
		}
		
		function alignCenter() {
			if( caller != null ) {
				var pLeft = (caller.width() - container.width())/2 + caller.offset().left;
				var pTop = (caller.height() - container.height())/2 + caller.offset().top;
				// random new created window position
				if( options.createRandomOffset.x > 0 ) {
					pLeft += ((Math.random() - 0.5) * options.createRandomOffset.x); 
				}
				if( options.createRandomOffset.y > 0 ) {
					pTop += ((Math.random() - 0.5) * options.createRandomOffset.y);					
				}
				container.css({
					left:		pLeft,
					top:		pTop
				});
			} else {
				container.css({
					left:		'50%',
					top:		'50%'
				}).css({
					marginLeft:	'-' + (container.outerWidth() / 2) + 'px',
					marginTop:	'-' + (container.outerHeight() / 2) + 'px'
				});
	
				if (absolute) {
					var marginLeft = parseInt(container.css('marginLeft'), 10) + jQuery(window).scrollLeft();
					var marginTop = parseInt(container.css('marginTop'), 10) + jQuery(window).scrollTop();
					// random new created window position
					if( options.createRandomOffset.x > 0 ) {
						marginLeft += ((Math.random() - 0.5) * options.createRandomOffset.x); 
					}
					if( options.createRandomOffset.y > 0 ) {
						marginTop += ((Math.random() - 0.5) * options.createRandomOffset.y);					
					}
					container.css({
						marginLeft:	marginLeft,
						marginTop:	marginTop
					});
				}
			};
		}
	
		function alignHorizontalCenter() {
			if( caller != null ) {
				var pLeft = (caller.width() - container.width())/2 + caller.offset().left;
				container.css({
					left:		pLeft
				});
			} else {
				container.css({
					left:		'50%'
				}).css({
					marginLeft:	'-' + (container.outerWidth() / 2) + 'px'
				});
	
				if (absolute) {
					container.css({
						marginLeft:	parseInt(container.css('marginLeft'), 10) + jQuery(window).scrollLeft()
					});
				}
			}
		}
	
		function alignVerticalCenter() {
			if( caller != null ) {
				var pTop = (caller.height() - container.height())/2 + caller.offset().top;
				container.css({
					top:		pTop
				});
			} else {
				container.css({
					top:		'50%'
				}).css({
					marginTop:	'-' + (container.outerHeight() / 2) + 'px'
				});
	
				if (absolute) {
					container.css({
						marginTop:	parseInt(container.css('marginTop'), 10) + jQuery(window).scrollTop()
					});
				}
			}
		}
	
		function maximize(bImmediately, bNoSaveDisplay) {
			if( !$.browser.msie ) { // in IE, must do hide scrollbar routine after animation finished
				hideBrowserScrollbar();
			}
			maximized = true;
			container.draggable( 'disable' );
			container.resizable( 'disable' );
	
			// save current display
			if( bNoSaveDisplay != true ) {
				pos.left = container.css("left");
				pos.top = container.css("top");
				pos.marginLeft = container.css("marginLeft");
				pos.marginTop = container.css("marginTop");
				wh.w = container.width();
				wh.h = container.height();
			}
			var scrollPos = getBrowserScrollXY();
			var screenWH = getBrowserScreenWH();
			targetCssStyle = {
				left: 0,
				top: 0,
				marginLeft: scrollPos.left,
				marginTop: scrollPos.top,
				width: screenWH.width,
				height: screenWH.height,
				opacity: 1
			};
	
			if( bImmediately ) {
				container.css(targetCssStyle);
				adjustHeaderTextPanelWidth();
				adjustFrameWH();
				header.removeClass('window_header_normal');
				header.addClass('window_header_maximize');
				// switch maximize, cascade button
				var headerFuncPanel = header.children("div.window_function_bar");
				headerFuncPanel.children(".maximizeImg").hide();
				headerFuncPanel.children(".cascadeImg").show();
			} else {
				hideContent();
				container.animate(targetCssStyle, animationSpeed, 'swing', function() {
					if( $.browser.msie ) { // in IE, must do hide scrollbar routine after animation finished
						hideBrowserScrollbar();
					}
					showContent();
					adjustHeaderTextPanelWidth();
					adjustFrameWH();
					header.removeClass('window_header_normal');
					header.addClass('window_header_maximize');
					// switch maximize, cascade button
					var headerFuncPanel = header.children("div.window_function_bar");
					headerFuncPanel.children(".maximizeImg").hide();
					headerFuncPanel.children(".cascadeImg").show();
				});
			}
		}
	
		function minimize() {
			showBrowserScrollbar();
			minimized = true;
			container.draggable( 'disable' );
			container.resizable( 'disable' );
			
			// save current display
			orgPos.left = container.css("left");
			orgPos.top = container.css("top");
			orgPos.marginLeft = container.css("marginLeft");
			orgPos.marginTop = container.css("marginTop");
			orgWh.w = container.width();
			orgWh.h = container.height();
	
			var top = ( minWindowStorage.length * 120) + "px";
			targetCssStyle = {
				left: 0,
				top: top,
				marginLeft: 0,
				marginTop: 0,
				width: 24,
				height: 120,
				opacity: OPACITY_MINIMIZED
			};
	
			transferToFixed(); // transfer position to fixed first
			hideContent();
			container.animate(targetCssStyle, animationSpeed, 'swing', function() {
				header.children("div.window_title_text").width( "100%" );
				header.attr("title", options.title);
				header.removeClass('window_header_normal');
				header.removeClass('window_header_maximize');
				header.addClass('window_header_minimize');
				if( options.showRoundCorner ) {
					header.removeClass('ui-corner-top');
					header.addClass('ui-corner-all');
				}
				header.children(".window_function_bar").hide();
				transformTitleText();
	
				// bind header click event
				header.click(function() {
					restore();
				});
			});
			container.mouseover(function() {
				$(this).css("opacity", 1);
			});
			container.mouseout(function() {
				$(this).css("opacity", OPACITY_MINIMIZED);
			});
	
			// push into minimized window storage
			pushMinWindow(windowId);
		}
	
		function restore() {
			var rpos = null;
			var rwh = null;
			if( minimized ) { // from minimized status
				minimized = false;
				rpos = orgPos;
				rwh = orgWh;
				transferToAbsolute(); // transfer position to fixed first
				header.removeClass('window_header_minimize');
				if( maximized ) { // to maximized status	
					var scrollPos = getBrowserScrollXY();
					rpos = {left:0, top:0, marginLeft:scrollPos.left, marginTop:scrollPos.top};
					header.addClass('window_header_maximize');
				} else {
					header.addClass('window_header_normal');
				}
			} else if( maximized ) { // from maximized status
				maximized = false;
				rpos = pos;
				rwh = wh;
				header.removeClass('window_header_maximize');
				header.addClass('window_header_normal');
			}
			restoreTitleText();
			header.removeAttr("title");
			header.removeClass('window_header_minimize');
			if( options.showRoundCorner ) {
				header.removeClass('ui-corner-all');
				header.addClass('ui-corner-top');
			}
			header.children(".window_function_bar").show();
			
			// unbind event
			container.unbind("mouseover");
			container.unbind("mouseout");

			targetCssStyle = {
				left: rpos.left,
				top: rpos.top,
				marginLeft: rpos.marginLeft,
				marginTop: rpos.marginTop,
				width: rwh.w,
				height: rwh.h,
				opacity: 1
			};
			
			hideContent();
			container.animate(targetCssStyle, animationSpeed, 'swing', function() {
				showContent();
				header.unbind('click');
				adjustHeaderTextPanelWidth();
				adjustFrameWH();
	
				// switch maximize, cacade icon
				var headerFuncPanel = header.children("div.window_function_bar");
				if( maximized ) {
					hideBrowserScrollbar();
					headerFuncPanel.children(".maximizeImg").hide();
					headerFuncPanel.children(".cascadeImg").show();
				} else {
					showBrowserScrollbar();
					container.draggable( 'enable' );
					container.resizable( 'enable' );
					headerFuncPanel.children(".maximizeImg").show();
					headerFuncPanel.children(".cascadeImg").hide();
				}
			});
	
			// pop from minimized window storage
			popMinWindow(windowId);
		}
		
		function close(quiet) {
			// do callback
			if( !quiet && $.isFunction(options.onClose) ) {
				options.onClose(me);
			}
			destroy();
		}
	
		function destroy() {
			redirectCheck = false;
			if( maximized ) {
				showBrowserScrollbar();
			}
			popWindow(windowId);
			container.remove();
		}
	
		return { // instance public methods
			initialize: initialize,
			getTargetCssStyle: getTargetCssStyle,         // get the css ready to change
			getWindowId: function() {                     // get window id
				return windowId;
			},
			select: select,                               // select current window, it will increase the original z-index value with 2
			unselect: unselect,                           // unselect current window, it will set the z-index as original options.z
			getContainer: getContainer,                   // get window container panel, a jQuery object
			getHeader: getHeader,                         // get window header panel, a jQuery object
			getFrame: getFrame,                           // get window frame panel, a jQuery object
			getFooter: getFooter,                         // get window footer panel, a jQuery object
			alignCenter: alignCenter,                     // set current window as screen center
			alignHorizontalCenter: alignHorizontalCenter, // set current window as horizontal center
			alignVerticalCenter: alignVerticalCenter,     // set current window as vertical center
			maximize: maximize,                           // maximize current window
			minimize: minimize,                           // minimize current window
			restore: restore,                             // restore current window, it could be maximized or cascade status
			close: close,                                 // close current window. parameter: quiet - [boolean] to decide doing callback or not
			setTitle: setTitle,                           // change window title. parameter: title - [string] window title text
			setUrl: setUrl,                               // change iframe url. parameter: url - [string] iframe url
			setContent: setContent,                       // change frame content. parameter: content - [html string, jquery object, element] the content of frame
			setFooterContent: setFooterContent            // change footer content. parameter: content - [html string, jquery object, element] the content of footer
		};
	} // constructor end
	
	return { // static public methods
		getInstance: function(caller, options)	{ // create new window instance
			var instance = constructor(caller, options);
			instance.initialize(instance);
			selectWindow(instance); // set new created window instance as selected
			instance.getContainer().get(0).windowInstance = instance;
			pushWindow(instance);
			return instance;
		},
		getVersion: function() { // get current version of plugin
			return VERSION;
		},
		setAnimationSpeed: function(speed) { // set animation speed of all windows
			animationSpeed = speed;
		},
		closeAll: function(quiet) { // close all created windows. it got a parameter - quiet, a boolean flag to decide doing callback or not
			var count = windowStorage.length;
			for( var i=0; i<count; i++ ) {
				var wnd = windowStorage[0];
				wnd.close(quiet);
			}
			windowStorage = [];
			minWindowStorage = [];
		},
		hideAll: function() { // hide all created windows
			for( var i=0; i<windowStorage.length; i++ ) {
				windowStorage[i].getContainer().hide();
			}
		},
		showAll: function() { // show all created windows
			for( var i=0; i<windowStorage.length; i++ ) {
				windowStorage[i].getContainer().show();
			}
		}
	}
})();

// alias methods
$.window.getVersion = $.Window.getVersion;
$.window.setAnimationSpeed = $.Window.setAnimationSpeed;
$.window.closeAll = $.Window.closeAll;
$.window.hideAll = $.Window.hideAll;
$.window.showAll = $.Window.showAll;