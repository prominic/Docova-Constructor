/***
@howto:
jQuery('select[name="colour"]').colourPicker({ico: 'my-icon.gif', title: 'Select a colour from the list'}); Would replace the select with 'my-icon.gif' which, when clicked, would open a dialogue with the title 'Select a colour from the list'.

You can close the colour-picker without selecting a colour by clicking anywhere outside the colour-picker box.

@exampleJS:
jQuery('#jquery-colour-picker-example select').colourPicker({
	ico:	WEBROOT + 'aFramework/Modules/Base/gfx/jquery.colourPicker.gif', 
	title:	false
});
***/
jQuery.fn.colourPicker = function (conf) {
	// Config for plug
	var config = jQuery.extend({
		id:			 'jquery-colour-picker',
		inputBG:	 false,					// Whether to change the input's background to the selected colour's
		speed:		 500,					// Speed of dialogue-animation
		pickerInput: null
	}, conf);

	// Inverts a hex-colour
	var hexInvert = function (hex) {
		var r = hex.substr(0, 2);
		var g = hex.substr(2, 2);
		var b = hex.substr(4, 2);

		return 0.212671 * r + 0.715160 * g + 0.072169 * b < 0.5 ? 'ffffff' : '000000';
	};

	// Add the colour-picker dialogue if not added
	var colourPicker = jQuery('#' + config.id);

	if (!colourPicker.length) {
		colourPicker = jQuery('<div id="' + config.id + '"></div>').appendTo(document.body).hide();

		// Remove the colour-picker if you click outside it (on body)
		jQuery(document.body).click(function(event) {
			if (!(jQuery(event.target).is('#' + config.id) || jQuery(event.target).parents('#' + config.id).length)) {
				colourPicker.hide(config.speed);
			}
		});
	}

	// For every select passed to the plug-in
	return this.each(function () {
		// Insert icon and input
		var icon	= jQuery(this);
		var input	= jQuery('#' + conf.pickerInput);
		var loc		= '';

		// If user wants to, change the input's BG to reflect the newly selected colour
		if (config.inputBG) {
			input.change(function () {
				input.css({background: '#' + input.val(), color: '#' + hexInvert(input.val())});
			});

			input.change();
		}

		// Show the colour-picker next to the icon and fill it with the colours in the select that used to be there
		var iconPos	= icon.offset();

		colourPicker.css({
			position: 'absolute', 
			left: iconPos.left + 'px', 
			top: iconPos.top + 'px',
			'z-index': 100000
		}).show(config.speed);

		// When you click a colour in the colour-picker
		jQuery('a', colourPicker).click(function () {
			// The hex is stored in the link's rel-attribute
			var hex = jQuery(this).attr('rel');

			input.val(hex);

			// If user wants to, change the input's BG to reflect the newly selected colour
			if (config.inputBG) {
				input.css({background: '#' + hex, color: '#' + hexInvert(hex)});
			}

			// Trigger change-event on input
			input.change();

			// Hide the colour-picker and return false
			colourPicker.hide(config.speed);

			return false;
		});

		return false;
	});
};