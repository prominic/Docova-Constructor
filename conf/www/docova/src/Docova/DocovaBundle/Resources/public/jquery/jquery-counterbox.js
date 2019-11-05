/**
 * jQuery counter box plugin 1.0
 * Compatible with FontAwesome icons, with ability to count up and down
 * @author Javad Rahimi
 */
(function( $ ) {
	$.fn.counterBox = function( options ) {
		return this.each(function() {
			var completed = false;
			var $this = $(this),
				opts = $.extend({
					dir: 'up',
					value: 0,
					color: '',
					size: '',
					title: '',
					titlesize: '',
					titlecolor: '',
					icon: null,
					iconpos: 'l',
					iconsize: '',
					starton: 'topview',
					unit: '',
					unitpos: 'prefix',
					enablebox: false,
					speed: 2000
				}, options);
			
			var start = opts.value;
			
			if (!$this.hasClass('docova-counterbox'))
			{
				$this.addClass('docova-counterbox');
			}

			if (opts.dir == 'up') {
				$this.append('<span class="counterbox-value-container">0</span>');
			}
			else {
				$this.append('<span class="counterbox-value-container">'+ opts.value + '</span>');
			}
			
			if (opts.enablebox === true) {
				$this.addClass('counterbox-borders-on');
			}
			else {
				$this.removeClass('counterbox-borders-on');
			}
			
			if (opts.size) {
				$this.css('font-size', opts.size+'px');
			}
			
			if (opts.color) {
				$this.css('color', opts.color);
			}
			
			if (opts.icon) {
				var icon_html = '<i class="far '+ opts.icon+ '" '+ (opts.iconsize ? ('style="font-size:'+ opts.iconsize +'px"') : '') +'></i>&nbsp;';
				if (opts.iconpos == 'r') {
					$this.append(icon_html);
				}
				else {
					$this.prepend(icon_html);
					if (opts.iconpos == 't') {
						$this.addClass('counterbox-icon-top');
					}
					else {
						$this.removeClass('counterbox-icon-top');
					}
				}
			}
			
			if (opts.unit) {
				if (opts.unitpos == 'prefix') {
					$this.find('span').first().before('<span class="counterbox-unit-container">'+ opts.unit +'</span>&nbsp;');
				}
				else {
					$this.find('span').first().after('<span class="counterbox-unit-container">'+ opts.unit +'</span>&nbsp;');
				}
			}
			
			if (opts.title) {
				var substyle = '';
				if (opts.titlesize) {
					substyle = 'font-size:' + opts.titlesize + 'px; ';
				}
				if (opts.titlecolor) {
					substyle += 'color:' + opts.titlecolor +';';
				}
				$this.append('<div class="counterbox-title" '+ (substyle ? 'style="'+substyle+'"' : '') +'>'+ opts.title +'</div>');
			}
			
			var counter_obj = $this.children('.counterbox-value-container').first();
			$(window).on('resize scroll load', function(){
				var viewportTop = $(window).scrollTop();
				var viewportBottom = viewportTop + $(window).height();
				if (completed === false && opts.starton == 'topview') {
					var elementTop = $this.offset().top;
					if (elementTop < viewportBottom && elementTop > viewportTop) {
						counter_obj.text(start);
						counter_obj.prop('Counter', 0).animate({ Counter: counter_obj.text() }, {
							duration: opts.speed,
							easing: 'swing',
							step: function (now) {
								if (opts.dir == 'up') {
									counter_obj.text(Math.ceil(now));
								}
								else {
							        var interval = Math.ceil(opts.speed / (1000 / 60));
							        var diff_value = Math.round((0 - start) / interval);
									counter_obj.text((parseInt(opts.value) + diff_value < 0) ? 0 : (opts.value = parseInt(opts.value) + diff_value));
								}
							},
							complete: function() {
								if (opts.dir == 'up') {
									counter_obj.text(start);									
								}
								else {
									counter_obj.text(0);
								}
							}							
						});
						completed = true;
					}
				}
				else if (completed === false) {
					var elementTop = $this.offset().top;
					var elementBottom = elementTop + $this.outerHeight();
					if (elementBottom < viewportBottom && elementBottom > viewportTop) {
						counter_obj.text(start);
						counter_obj.prop('Counter', 0).animate({ Counter: counter_obj.text() }, {
							duration: opts.speed,
							easing: 'swing',
							step: function (now) {
								if (opts.dir == 'up') {
									counter_obj.text(Math.ceil(now));
								}
								else {
							        var interval = Math.ceil(opts.speed / (1000 / 60));
							        var diff_value = Math.round((0 - start) / interval);
									counter_obj.text((parseInt(opts.value) + diff_value < 0) ? 0 : (opts.value = parseInt(opts.value) + diff_value));
								}
							},
							complete: function() {
								if (opts.dir == 'up') {
									counter_obj.text(start);									
								}
								else {
									counter_obj.text(0);
								}
							}							
						});
						completed = true;
					}
				}
			});
		});
	}
}(jQuery));