var activeObj = null;
var elementEvents = [];
var radioDefaults = [];


$(document).ready(function() {
	$('#tabs').hide(0, function() {
		$( "#tabs" ).tabs();
		$('#tabs').show();
	});
	$( "#tabs" ).tabs();
	$('#property_tabs').tabs({ heightStyle: "auto"});
	$( "#tool_box" ).draggable({cursor: "move", containment: "#tabs", scroll: false, addClasses: false });
	$( "#tool_box li" ).draggable({ revert: "invalid", containment: "document", helper: "clone", opacity: 0.5 });
	$( "#form_element_container td" ).resizable({ handles: "e" });
	
	$('.color_picker').on('click', function() {
		$(this).colourPicker({
			pickerInput: $(this).attr('for')
		});
	});
	
	$('input.date_picker').removeAttr("readonly").addClass("docovaDatepicker").removeClass("date_picker");
	
	$('#form_element_container input[name^="radio_btn_"]:checked').each(function(index) {
		radioDefaults[$(this).prop('name')] = $(this).val();
	});
	
	$('#form_element_container').on('mouseover', 'td:not(.list)', function(e) {
		$(this).css('background-color', '#DDD');
	});

	$('#form_element_container').on('mouseleave', 'td:not(.list)', function(e) {
		$(this).css('background-color', '');
	});
	
	$('#collapseExpand').on('click', function() {
		if ($(this).prop('class') == 'collapse') {
			$(this).prop({
				'src' : docInfo.imgPath + '\cat-expand.gif',
				'class' : 'expand'
			});
			$('#afcontainer').slideUp('slow');
		}
		else {
			$(this).prop({
				'src' : docInfo.imgPath + '\cat-collapse.gif',
				'class' : 'collapse'
			});
			$('#afcontainer').slideDown('slow');
		}
	});
	
	$('#addRow').on('click', function(e) {
		var cloneRw = $(this).closest('tr').clone();
		cloneRw.find('img').attr({
			'class' : 'removeRow',
			'alt' : 'remove',
			'src' : docInfo.imgPath + '\minus.png',
			'id' : null
		});
		cloneRw.find('img').on('click', function() {
			$(this).closest('tr').remove();
		});
		$('#additional_fields_container tr:last').after(cloneRw);
	});

	$('.removeRow').on('click', function() {
		$(this).closest('tr').remove();
	});

	$.contextMenu({
		selector: '#form_element_container td:not(.list)',
		callback: function(key, options) {
			window[key]($(this));
        },
        items: {
        	"delete_element": {
        		"name": docInfo.delElement,
        		"disabled" : function(key, opt) {
        			if (opt.$trigger.children(':not(div)').length) { return false; }
        			return true;
        		}
        	},
        	"sep1": "---------",
            "Rows": {
	            "name": docInfo.Rows,
	            "className": "right_arrow",
	            "items": {
		            "insert_row": { "name": docInfo.insert },
		            "delete_row": { "name": docInfo.del },
		            "append_row": { "name": docInfo.append }
		        }
		    },
            "Columns": {
	            "name": docInfo.Columns,
	            "className": "right_arrow",
	            "items": {
		            "insert_column": { "name": docInfo.insert },
		            "delete_column": { "name": docInfo.del },
		            "append_column": { "name": docInfo.append }
		        }
		    },
		    "Cell": {
		    	"name": docInfo.Cells,
		    	"className": "right_arrow",
		    	"items": {
		    		"merge_right": { "name": docInfo.mrgRight },
		    		"merge_cells": { "name": docInfo.mrgCells },
		    		"cell_properties" : { "name" : docInfo.cellProperties }
		    	}
		    },
		    "sep2": "---------",
		    "table_properties" : {
		    	"name": docInfo.tblProperties
		    }
		}
	});

	$( "#form_element_container td:not(:has(table))" ).droppable({
		accept: "#tool_box li",
		drop: dropElement
    });

	$('#static_properties').dialog({
		autoOpen: false,
		width: 550,
		modal: true,
		position: { my: "center top+10%", at: "center top+10%" },
		buttons: [{
			"text" : docInfo.applyChanges,
			"click": function() {
				if (activeObj) {
					var stClasses = $(activeObj).attr('class') ? $(activeObj).attr('class').split(/\s+/) : [];
					for (var c = 0; c < stClasses.length; c++) {
						if (stClasses[c] != 'ui-resizable' && stClasses[c] != 'ui-droppable' && stClasses[c] != 'context-menu-active' && stClasses[c] != 'ui-droppable-disabled' && stClasses[c] != 'ui-state-disabled') {
							$(activeObj).removeClass(stClasses[c]);
						}
					}

					if ($.trim($('#element_name').val())) {
						var suffix = $(activeObj).prop('type') && $(activeObj).prop('type').toLowerCase() == 'checkbox' && $('#element_name').val().indexOf('[]') == -1 ? '[]' : '';
						$(activeObj).attr('name', ($('#element_name').val() + suffix));
					}
					if ($.trim($('#static_id').val())) {
						$(activeObj).attr('id', $('#static_id').val());
					}
					if ($.trim($('#static_class').val())) { 
						$(activeObj).addClass($('#static_class').val());
					}
					if ($.trim($('#static_style').val())) {
						$(activeObj).attr('style', $('#static_style').val());
					}
					$( this ).dialog( "close" );
				}
			}
		},
		{
			"text" : docInfo.cancel,
			"click" : function() {
				$( this ).dialog( "close" );
			}
		}],
		close : function() {
			$('#static_properties input').val('');
			activeObj = null;
		}
	});
	
	$('#layout input, #layout select, #layout textarea').each(function() {
		elementEvents[$(this).attr('name')] = {
			'onclick' : $(this).attr('onClick') ? $(this).attr('onclick') : null,
			'onchange' : $(this).attr('onChange') ? $(this).attr('onchange') : null,
			'onfocus' : $(this).attr('onFocus') ? $(this).attr('onfocus') :null,
			'onblur' : $(this).attr('onBlur') ? $(this).attr('onblur') : null
		};
		
		$(this).attr('onClick', null);
		$(this).attr('onChange', null);
		$(this).attr('onFocus', null);
		$(this).attr('onBlur', null);
	});

	$( "#properties_container" ).dialog({
	   autoOpen: false,
	   width: 670,
	   modal: true,
	   height: 350,
	   position: { my: "center top+10%", at: "center top+10%" },
	   resize: function(event, ui) {
		   var newheight = $('#properties_container').css('height');
			
		   $('.option_container').css('max-height', parseInt(newheight) -150 + 'px'); 
	   },
	   buttons: [{
		   "text": docInfo.applyChanges,
		   "click": function() { 
	 		 var sElem_name = $('#selected_element_id').val();
			 var sElement = $('[name="' + sElem_name +'"]'),
			 	 tagName = sElement.prop("tagName").toLowerCase(), 
			 	 class_container = (tagName == 'input' && sElement.parent().prop('tagName').toLowerCase() == 'label') ? sElement.parent() : sElement;
			 	 
			 	 if (tagName == 'input' && sElement.prop('type').toLowerCase() == 'checkbox')
			 	 {
			 		 var nm = $('#element_name').val();
			 		 nm += '[]';
			 		 $('#element_name').val(nm); 	
				 }
				 
				 
			 var date_picker = (class_container.hasClass('docovaDatepicker') ? 'docovaDatepicker' : '');
			 if (sElement.prop('name') != $('#element_name').val()) {
				 delete elementEvents[sElement.prop('name')];
			 }
			 if (typeof elementEvents[$('#element_name').val()] == 'undefined') {
				elementEvents[$('#element_name').val()] = {
					'onclick' : null,
					'onchange' : null,
					'onfocus' : null,
					'onblur' : null
				};
			 }
			 sElement.attr('name', $('#element_name').val());
			 class_container.prop('class', '');
			 class_container.addClass(date_picker);
			 class_container.addClass($('#element_font').val());
			 class_container.addClass('sz_' + $('#element_fontsize').val());

			 if (tagName == 'label') {
				if ($.trim($('#element_id').val())) {
					sElement.prop('id', $('#element_id').val());
				}
			 	sElement.text($('#element_properties input[name="label_value"]').val());
			 	sElement.parent().removeClass('left center right');
			 	sElement.parent().addClass($('#element_properties select[name="lbl_alignment"]').val());
			 	if ($.trim($('#element_properties input[name="lbl_color"]').val())) {
			 		sElement.css('color', $('#element_properties input[name="lbl_color"]').val());
			 	}
			 	if ($('#element_properties input[name="label_italic"]').prop('checked')) {
				 	sElement.addClass('italic');
				}
			 	if ($('#element_properties input[name="label_bold"]').prop('checked')) {
				 	sElement.addClass('bold');
				}
			 }
			 else if (tagName == 'input' && sElement.prop('type').toLowerCase() == 'text') {
				 var isNames = (typeof(sElement.attr('target')) != undefined && sElement.attr('target')) ? true : false;
				 if (isNames === true) {
					 sElement.attr('target', $('#element_name').val());
				 }
				 sElement.prop('id', $('#element_id').val());
				 if (!sElement.hasClass('docovaDatepicker') && isNames === false) {
					 sElement.val($('#element_properties input[name="textbox_value"]').val());
				 }

				 if (!sElement.hasClass('docovaDatepicker')) {
					 if (isNames === false && $.trim($('#element_properties input[name="txt_width"]').val())) {
						 sElement[0].style.width = $('#element_properties input[name="txt_width"]').val() + 'px';
					 }
					 else if (isNames === true && $.trim($('#element_properties input[name="names_width"]').val())) {
						 sElement[0].style.width = $('#element_properties input[name="names_width"]').val() + 'px';
					 }
				 }
				 
				 if ($.trim($('#element_properties input[name="onClickEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onclick'] = $('#element_properties input[name="onClickEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onclick'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onChangeEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onchange'] = $('#element_properties input[name="onChangeEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onchange'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onFocusEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onfocus'] = $('#element_properties input[name="onFocusEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onfocus'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onBlurEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onblur'] = $('#element_properties input[name="onBlurEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onblur'] = null;
				 }
				 
				 if (isNames === true) {
					 var ismulti = ($('#element_properties select[name="namep_type"]').val() == 'multi' ? true : false);
					 sElement.addClass((ismulti == 'multi' ? 'multipleNamePicker' : 'singleNamePicker'));
					 jQuery("img:first", sElement.next()[0]).prop("src", docInfo.imgPath +'/icons/vwicn00' + (ismulti ? '4' : '3') + '.gif');
				 }
			 }
			 else if (tagName == 'input' && sElement.prop('type').toLowerCase() == 'checkbox') {
				 var tbl_container = sElement.parentsUntil('table').parent(),
				 columns = parseInt($('#element_properties input[name="column_countcb"]').val()),
				 default_values = $('#checkbox_defaults').val();
				 tbl_container.html('');
				
				 default_values = $.trim(default_values) ? default_values.split(';') : [];
				 if ($.trim($('#element_properties input[name="onClickEvent"]').val())) {
					 elementEvents[$('#element_name').val()]['onclick'] = $('#element_properties input[name="onClickEvent"]').val();
				 }
				 else { elementEvents[$('#element_name').val()]['onclick'] = null; }
				 if ($.trim($('#element_properties input[name="onChangeEvent"]').val())) {
					 elementEvents[$('#element_name').val()]['onchange'] = $('#element_properties input[name="onChangeEvent"]').val();
				 }
				 else { elementEvents[$('#element_name').val()]['onchange'] = null; }
				 if ($.trim($('#element_properties input[name="onFocusEvent"]').val())) {
					elementEvents[$('#element_name').val()]['onfocus'] = $('#element_properties input[name="onFocusEvent"]').val();
				 }
				 else { elementEvents[$('#element_name').val()]['onfocus'] = null; }
				 if ($.trim($('#element_properties input[name="onBlurEvent"]').val())) {
					 elementEvents[$('#element_name').val()]['onblur'] = $('#element_properties input[name="onBlurEvent"]').val();
				 }
				 else { elementEvents[$('#element_name').val()]['onblur'] = null; }
				 
				 
				 rows_html = '';
				 for (var x = 1; x <= Math.ceil($('#element_properties input[name^="chbox_label"]').length / columns); x++) {
						var remain = 0;
						rows_html += '<tr>';
						$('#element_properties input[name^="chbox_label"]').each(function(index) {
							var checked = $.inArray($(this).parent().next().next().children('input').first().val(), default_values) != -1 ? 'checked' : ''; 
							
							if (Math.ceil((index+1)/columns) == x) {
								remain = (index+1) % columns;
								rows_html += '<td class="list">';
								rows_html += '<label class="'+ $('#element_font').val() + ' sz_' + $('#element_fontsize').val() +'"><input type="checkbox" '+ checked +' value="'+ $(this).parent().next().next().children('input').first().val() +'" name="'+ $('#element_name').val() +'" order="'+ x +'" >';
								rows_html += '<span>'+ $(this).val() +'</span></label>';
								rows_html += '</td>';
							}
						});
						if (remain != 0) {
							for (var i = 1; i < remain; i++) {
								rows_html += '<td>&nbsp;</td>';
							}
						}
						rows_html += '</tr>';
				}
				
				tbl_container.append(rows_html);
				 
			 }
			 else if (tagName == 'input' && sElement.prop('type').toLowerCase() == 'radio') {
				var tbl_container = sElement.parentsUntil('table').parent(),
					columns = parseInt($('#element_properties input[name="column_count"]').val()),
				 	rows_html = '';
				columns = !columns ? 1 : columns;
				tbl_container.html('');
				tbl_container.attr('id', 'tmp_rdo_tbl');
				if ($.trim($('#element_properties input[name="onClickEvent"]').val())) {
					elementEvents[$('#element_name').val()]['onclick'] = $('#element_properties input[name="onClickEvent"]').val();
				}
				else { elementEvents[$('#element_name').val()]['onclick'] = null; }
				if ($.trim($('#element_properties input[name="onChangeEvent"]').val())) {
					elementEvents[$('#element_name').val()]['onchange'] = $('#element_properties input[name="onChangeEvent"]').val();
				}
				else { elementEvents[$('#element_name').val()]['onchange'] = null; }
				if ($.trim($('#element_properties input[name="onFocusEvent"]').val())) {
					elementEvents[$('#element_name').val()]['onfocus'] = $('#element_properties input[name="onFocusEvent"]').val();
				}
				else { elementEvents[$('#element_name').val()]['onfocus'] = null; }
				if ($.trim($('#element_properties input[name="onBlurEvent"]').val())) {
					elementEvents[$('#element_name').val()]['onblur'] = $('#element_properties input[name="onBlurEvent"]').val();
				}
				else { elementEvents[$('#element_name').val()]['onblur'] = null; }
				radioDefaults[$('#element_name').val()] = ($('#element_properties input[name="radiobtn_defaul"]').val());
				for (var x = 1; x <= Math.ceil($('#element_properties input[name^="rdbtn_label"]').length / columns); x++) {
					var remain = 0;
					rows_html += '<tr>';
					$('#element_properties input[name^="rdbtn_label"]').each(function(index) {
						if (Math.ceil((index+1)/columns) == x) {
							remain = (index+1) % columns;
							rows_html += '<td class="list">';
							rows_html += '<label class="'+ $('#element_font').val() + ' sz_' + $('#element_fontsize').val() +'">';
							rows_html += '<input type="radio" '+($(this).parent().next().next().children('input').first().val() == $('#element_properties input[name="radiobtn_defaul"]').val() ? 'checked' : '')+' value="'+ $(this).parent().next().next().children('input').first().val() +'" name="'+ $('#element_name').val() +'" order="'+ x +'" >';
							rows_html += '<span>'+ $(this).val() +'</span></label>';
							rows_html += '</td>';
						}
					});
					if (remain != 0) {
						for (var i = 1; i < remain; i++) {
							rows_html += '<td class="list">&nbsp;</td>';
						}
					}
					rows_html += '</tr>';
				}
				tbl_container.append(rows_html);
				$('#tmp_rdo_tbl td').resizable({ handles: "e", containment: "parent" });
				
				$('#tmp_rdo_tbl').removeAttr('id');
			 }
			 else if (tagName == 'select') {
				 sElement.prop('id', $('#element_id').val());
				 sElement.children('option').remove();
				 if ($.trim($('#element_properties input[name="combobox_width"]').val())) {
					 sElement[0].style.width = $('#element_properties input[name="combobox_width"]').val() + 'px';
				 }
				 $('#element_properties input[name^="combo_label"]').each(function(index) {
					 sElement.append('<option '+ ($(this).parent().next().next().children('input').first().val() == $('#element_properties input[name="combobox_default"]').val() ? 'selected' : '') +' value="' + $(this).parent().next().next().children('input').first().val() + '" order="'+ index +'">'+ $(this).val() +'</option>');
				 });

				 if ($.trim($('#element_properties input[name="onClickEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onclick'] = $('#element_properties input[name="onClickEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onclick'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onChangeEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onchange'] = $('#element_properties input[name="onChangeEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onchange'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onFocusEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onfocus'] = $('#element_properties input[name="onFocusEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onfocus'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onBlurEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onblur'] = $('#element_properties input[name="onBlurEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onblur'] = null;
				 }
			 }
			 else if (tagName == 'textarea') {
				 sElement.prop('id', $('#element_id').val());
				 sElement.prop('cols', $('#element_properties input[name="tarea_cols"]').val());
				 sElement.prop('rows', $('#element_properties input[name="tarea_rows"]').val());
				 sElement.val($('#element_properties input[name="tarea_default"]').val());
				 if ($.trim($('#element_properties input[name="onClickEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onclick'] = $('#element_properties input[name="onClickEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onclick'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onChangeEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onchange'] = $('#element_properties input[name="onChangeEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onchange'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onFocusEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onfocus'] = $('#element_properties input[name="onFocusEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onfocus'] = null;
				 }
				 if ($.trim($('#element_properties input[name="onBlurEvent"]').val())) {
					 elementEvents[sElement.prop('name')]['onblur'] = $('#element_properties input[name="onBlurEvent"]').val();
				 }
				 else {
					 elementEvents[sElement.prop('name')]['onblur'] = null;
				 }
			 }
			 $( this ).dialog( "close" );
	      }
	   },
	   {
		   "text": docInfo.cancel,
		   "click": function() {
		     $( this ).dialog( "close" );
		   }
	   }]
	   ,close : function() {
		   $('#element_properties input[type=text],#element_properties textarea').val('');
		   $('#element_properties input:checkbox').prop('checked', false);
		   $('#element_fontsize').val($('#element_fontsize option:eq(3)').val());
		   $('#element_font').val($('#element_font option:first').val());
		   $('#chbox_options tr:gt(0)').remove();
		   $('#rdbtn_options tr:gt(0)').remove();
		   $('#combo_options tr:gt(0)').remove();
	   }
	});

	$("#form_element_container").on('click', function(e) {
	    var elem = $(e.target).prop("tagName").toLowerCase();
		var isNames = typeof $(e.target).attr('target') != undefined && $(e.target).attr('target') ? true : false;
	    if (elem) 
	    {
    		var font_found = false,
    		    size_found = false,
    		    target_el = (elem == 'input' && $(e.target).parent().prop('tagName').toLowerCase() == 'label') ? $(e.target).parent() : $(e.target);
	    	if (target_el) {
	    		$('#element_font').children('option').each(function() {
	    			if (target_el.hasClass($(this).attr('value'))) {
	    				$('#element_font').val($(this).attr('value'));
	    				font_found = true;
	    				return;
	    			}
	    		});
	    		
	    		$('#element_fontsize').children('option').each(function() {
	    			if (target_el.hasClass('sz_' + $(this).attr('value'))) {
	    				$('#element_fontsize').val($(this).attr('value'));
	    				size_found = true;
	    				return;
	    			}
	    		});
	    	}
    		if (font_found === false) {
    			$('#element_font').val($('#element_font option:first').val());
    		}
    		if (size_found === false) {
    			$('#element_fontsize').val($('#element_fontsize option:eq(3)').val());
    		}
    		$('#selected_element_id').val($(e.target).attr('name'));
	    	$('#element_name').val($(e.target).attr('name'));
	    	$('#element_id').val($(e.target).prop('id'));
	    	$('#field_id').css('visibility', 'visible');
	    	$('.variable').css('display', 'none');
	    }
	    
	    if (elem == 'input' && ($(e.target).prop('type').toLowerCase() == 'checkbox')) {
	    	$('#field_id').text(docInfo.defaultValue + 'Default Value(s): ');
	    	$('#element_id').css('display', 'none');
	    	var elmname = $("#element_name").val();
	    	elmname = elmname.substr(0, elmname.length-2);
	    	$('#element_name').val(elmname);
	    	$('#checkbox_defaults').css('display', '');
	    }
	    else {
	    	$('#field_id').text(docInfo.elementId + ': ');
	    	$('#element_id').css('display', '');
	    	$('#checkbox_defaults').css('display', 'none');
	    }

	    if (elem == 'label' && !$(e.target).prop('for')) {
	    	$('#element_properties input[name="label_italic"]').prop('checked', ($(e.target).hasClass('italic') ? true : false));
	    	$('#element_properties input[name="label_bold"]').prop('checked', ($(e.target).hasClass('bold') ? true : false));
			if ( $(e.target).parent().hasClass('center') )
				$('#element_properties select[name="lbl_alignment"]').val('center');
			else if ($(e.target).parent().hasClass('left') )
				$('#element_properties select[name="lbl_alignment"]').val('left');
			else if ($(e.target).parent().hasClass('right') )
				$('#element_properties select[name="lbl_alignment"]').val('right');
			
			$('#element_properties input[name="label_value"]').val($(e.target).text());
			$('#element_properties input[name="lbl_color"]').val($(e.target).css('color') != '#222' ? $(e.target).css('color').replace('#', '') : '');
			$('.label_rows').css('display', '');
			$( "#properties_container" ).dialog("option", "title", docInfo.labelProperties).dialog("open");
			$('#property_tabs').tabs({ disabled: [1]});
		}
		else if (elem == 'input' && $(e.target).prop('type').toLowerCase() == 'text') {
			if (isNames === true ) {
				$('#element_properties input[name="names_width"]').val($(e.target).css('width').replace('px', ''));
				$('#element_properties select[name="namep_type"]').val($(e.target).hasClass('multipleNamePicker') ? 'multi' : 'single');
			}
			else {
				$('#element_properties input[name="txt_width"]').val($(e.target).css('width').replace('px', ''));
				$('#element_properties input[name="textbox_value"]').val($(e.target).val());
			}
			if (!$(e.target).prop('readonly')) {
				if (isNames === true) {
					$('.names_rows').css('display', '');
				}
				else {
					$('.textbox_rows').css('display', '');
				}
			}
			$('#properties_container input[name="onClickEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onclick'] : '');
			$('#properties_container input[name="onChangeEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onchange'] : '');
			$('#properties_container input[name="onFocusEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onfocus'] : '');
			$('#properties_container input[name="onBlurEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onblur'] : '');
			if ($(e.target).hasClass('docovaDatepicker')) {
				$( "#properties_container" ).dialog("option", "title", docInfo.dateProperties).dialog("open");
			}
			else {
				if (isNames === true) {
					$( "#properties_container" ).dialog("option", "title", docInfo.namesProperties).dialog("open");
				}
				else {
					$( "#properties_container" ).dialog("option", "title", docInfo.inputProperties).dialog("open");
				}
			}
			$('#property_tabs').tabs({ disabled: false});
		}
		else if (elem == 'input' && $(e.target).prop('type').toLowerCase() == 'checkbox') {
			$(e.target).is(':checked') ? $(e.target).prop('checked', false) : $(e.target).prop('checked', true);
			buildOptionsRows('checkbox', $(e.target));
			$('#element_properties input[name="column_countcb"]').val($(e.target).parentsUntil('tr').parent().children('td').length);
			$('#properties_container input[name="onClickEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onclick'] : '');
			$('#properties_container input[name="onChangeEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onchange'] : '');
			$('#properties_container input[name="onFocusEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onfocus'] : '');
			$('#properties_container input[name="onBlurEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onblur'] : '');
			$("#properties_container" ).dialog("option", "title", docInfo.checkboxProperties).dialog("open");
			$('#property_tabs').tabs({ disabled: false});
			$('.checkbox_rows').css('display', '');
			$('#element_id, #field_id').css('visibility', 'hidden');
			return false;
		}
		else if (elem == 'input' && $(e.target).prop('type').toLowerCase() == 'radio') {
			e.stopPropagation();
			buildOptionsRows('radio', $(e.target));
			$('#element_properties input[name="column_count"]').val($(e.target).parentsUntil('tr').parent().children('td').length);
			if ( radioDefaults[$(e.target).prop('name')] )
				$('#element_properties input[name="radiobtn_defaul"]').val(radioDefaults[$(e.target).prop('name')]);
			else
				$('#element_properties input[name="radiobtn_defaul"]').val($('input[name="'+$(e.target).prop('name')+'"]:checked').val());
			$('#properties_container input[name="onClickEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onclick'] : '');
			$('#properties_container input[name="onChangeEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onchange'] : '');
			$('#properties_container input[name="onFocusEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onfocus'] : '');
			$('#properties_container input[name="onBlurEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onblur'] : '');
			$( "#properties_container" ).dialog("option", "title", docInfo.radioProperties).dialog("open");
			$('#property_tabs').tabs({ disabled: false});
			$('.radiobtn_rows').css('display', '');
			$('#element_id, #field_id').css('visibility', 'hidden');
			return false;
		}
		else if (elem == 'select') {
			var options = $('#form_element_container select[name="' + $(e.target).attr('name') + '"]').children('option');
			$('#element_properties input[name="combobox_default"]').val($(e.target).val());
			$('#element_properties input[name="combobox_width"]').val($(e.target).css('width').replace('px', ''));
			$('#combo_options input[name="combo_label1"]').val(options.first().text());
			$('#combo_options input[name="combo_value1"]').val(options.first().val());
			$('#combo_options tr:not(:first)').each(function() {
				$(this).remove();
			});
			options.each(function(index) {
				if (index > 0) {
					var cloned_row = $('#combo_options tr').first().clone(true);
					cloned_row.find('input:first').attr('name', 'combo_label' + (index+1)).val($(this).text());
					cloned_row.find('input:last').attr('name', 'combo_value' + (index+1)).val($(this).val());
					cloned_row.find('img').attr("id", '').prop("src", docInfo.imgPath + '\minus.png').prop("class", 'omit_opt');
					$('#combo_options tr:last').after(cloned_row);
					cloned_row = null;
				}
			});
			$('#properties_container input[name="onClickEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onclick'] : '');
			$('#properties_container input[name="onChangeEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onchange'] : '');
			$('#properties_container input[name="onFocusEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onfocus'] : '');
			$('#properties_container input[name="onBlurEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onblur'] : '');
			$( "#properties_container" ).dialog("option", "title", docInfo.dropdownProperties).dialog("open");
			$('#property_tabs').tabs({ disabled: false});
			$('.combobox_rows').css('display', '');
			return false;
		}
		else if (elem == 'textarea') {
			$('.tarea_rows').css('display', '');
			$('#element_properties input[name="tarea_cols"]').val($(e.target).prop('cols'));
			$('#element_properties input[name="tarea_rows"]').val($(e.target).prop('rows'));
			$('#element_properties input[name="tarea_default"]').val($(e.target).val());
			$('#properties_container input[name="onClickEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onclick'] : '');
			$('#properties_container input[name="onChangeEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onchange'] : '');
			$('#properties_container input[name="onFocusEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onfocus'] : '');
			$('#properties_container input[name="onBlurEvent"]').val(typeof elementEvents[$(e.target).prop('name')] != 'undefined' ? elementEvents[$(e.target).prop('name')]['onblur'] : '');
			$("#properties_container").dialog("option", "title", docInfo.textareaProperties).dialog("open");
			$('#property_tabs').tabs({ disabled: false});
		}
	});
	
	$(document).on('click', '#add_choption', function() {
		addOptionRow($(this), 'chbox');
	});
	
	$(document).on('click', '#add_rdoption', function() {
		addOptionRow($(this), 'rdbtn');
	});
	
	$(document).on('click', '#add_cmboption', function() {
		addOptionRow($(this), 'combo');
	});
	
	$(document).on('click', '.omit_opt', function() {
		$(this).parentsUntil('tr').parent().remove();
	});

	$( "#savd_subform" ).button({ 
	 icons : {
		 primery: "ui-icon-document-b"
	 },
	 text: false })
	 .click(function( event ) {
		 var valid = true,
		 	 extraFields = [],
		 	 fieldTypes = [];
		 if (!$.trim($('input[name="from_name"]').val()) || !$.trim($('input[name="form_alias"]').val())) {
			 alert('Form Name and Form Alias are required, please fill them out.');
			 return false;
		 }

		 $('#properties input[name="addFieldName"]').each(function(index) {
			 if ($.trim($(this).val())) {
				 if ($('#properties select[name="addFieldType"]').eq(index).val() == -1) {
					 alert('Please select a data type for additional inputs.');
					 valid = false;
					 return false;
				 }
				 else {
					 extraFields.push($.trim($(this).val().replace(/,/g, '')));
					 fieldTypes.push($('#properties select[name="addFieldType"]').eq(index).val());
				 }
			 }
		 });
		 
		 if (valid !== true) {
			 return false;
		 }

		 if (elementEvents) {
			 for (index in elementEvents) {
				if ($('#layout input[name="'+ index +'"]').length) {
					$('#layout input[name="'+ index +'"]').each(function() {
						$(this).attr('onClick', elementEvents[index]['onclick']);
						$(this).attr('onChange', elementEvents[index]['onchange']);
						$(this).attr('onFocus', elementEvents[index]['onfocus']);
						$(this).attr('onBlur', elementEvents[index]['onblur']);
					});
				}
				else if ($('#layout select[name="'+ index +'"]').length) {
					$('#layout select[name="'+ index +'"]').each(function() {
						$(this).attr('onClick', elementEvents[index]['onclick']);
						$(this).attr('onChange', elementEvents[index]['onchange']);
						$(this).attr('onFocus', elementEvents[index]['onfocus']);
						$(this).attr('onBlur', elementEvents[index]['onblur']);
					});
				}
				else if ($('#layout textarea[name="'+ index +'"]').length) {
					$('#layout textarea[name="'+ index +'"]').each(function() {
						$(this).attr('onClick', elementEvents[index]['onclick']);
						$(this).attr('onChange', elementEvents[index]['onchange']);
						$(this).attr('onFocus', elementEvents[index]['onfocus']);
						$(this).attr('onBlur', elementEvents[index]['onblur']);
					});
				}
			 };
		 }

		 $.ajax({
			url : docInfo.ServerUrl + docInfo.SubmissionPath,
			type: "POST",
			data : {
				form_name : encodeURIComponent($('#properties input[name="from_name"]').val()),
				form_alias : encodeURIComponent($('#properties input[name="form_alias"]').val()),
				extra_fields : encodeURIComponent(extraFields.join(',')),
				field_types : encodeURIComponent(fieldTypes.join(',')),
				bgColor : encodeURIComponent($('#properties input[name="sbfrom_bgcolor"]').val()),
				subform_html : encodeURIComponent($('#form_element_container').html()),
				subform_js : encodeURIComponent($.trim($('#js_container').val()))
			}
		 })
		 .done(function(output) {
			 if (output[0] == true) {
				 alert(prmptMessages.msgDS001);
				 parent.frames['fraLeftFrame'].document.getElementsByTagName('a')[1].click();
			 }
			 else {
				 var error_msg = '';
				 $.each(output.error, function(i, value){
					 error_msg += (value + "\n");
				 });
				 alert (prmptMessages.msgDS002 + ":\n" + error_msg);
			 }
		 });
	});
	
	$( "#close_subform" ).button({ 
		icons : {
			primery: "ui-icon-document-b"
		},
		text: false })
		.click(function( event ) {
			var ans = confirm(prmptMessages.msgDS003);
			if (ans) {
				parent.frames['fraLeftFrame'].document.getElementsByTagName('a')[1].click();
			}
		});
	
	$('#use_domready').click(function() {
		if ($(this).prop('checked') && $('#js_container').val().indexOf('$(document).ready(function() {') == -1) 
		{
			$('#js_container').val("$(document).ready(function() {\n\n});\n\n" + $('#js_container').val());
		}
		else {
			if (!$(this).prop('checked') && $('#js_container').val().indexOf("$(document).ready(function() {\n\n});") != -1) {
				$('#js_container').val($('#js_container').val().replace("$(document).ready(function() {\n\n});", ''));
			}
			else if (!$(this).prop('checked') && $('#js_container').val().indexOf('$(document).ready(function() {') != -1) {
				return false;
			}
		}
	});
	
	window.onresize = function(event) {
		resizeDiv();
	}
	
});

function resizeDiv()
{

	vph = $(window).height();
	vptoolbar = $("#toolbar").height();
	vptabbed = $("#prop").height();
	vph = vph-(vptoolbar+vptabbed+40);
	$('#layout').css({'height': vph + 'px'});
	
}

function dropElement( event, ui ) {
	if ($(this).children(':not(div)').length > 0 && $(this).children(':not(table)').length > 0) {
		var res = confirm("Are you sure to replace with current element?");
		if (!res) { return; }
		else {
			$(this).empty();
			$(this).html('&nbsp;');
		}
	}

	var x = '1';
	switch (ui.draggable.attr('elem')) {
		case 'label':
			x = findValidElemIndex('label', 'label_');
			$(this).append($('<label></label>')
					.attr({id: "label_" + x, name: "label_" + x})
					.text('Label: ')
				);
			break;
		case 'text':
			x = findValidElemIndex(':input', 'txt_input_');
			$(this).append($('<input>')
					.attr({
						name: "txt_input_" + x,
						id: "txt_input_" + x, 
						type: "text"
					})
				);
			break;
		case 'chbox':
			x = findValidElemIndex(':input', 'chbox_input_');
			$(this).html('');
			$(this).append($('<table></table>')
					.append('<tr><td class="list"><label>\
							<input type="checkbox" value="option1" name="chbox_input_'+ x +'[]">\
							<span>Option1</span>\
							</label></td></tr>')
					.append('<tr><td class="list"><label>\
							<input type="checkbox" value="option2" name="chbox_input_'+ x +'[]">\
							<span>Option2</span>\
							</label></td></tr>')
					.append('<tr><td class="list"><label>\
							<input type="checkbox" value="option3" name="chbox_input_'+ x +'[]">\
							<span>Option3</span>\
							</label></td></tr>')
				);
			break;
		case 'rdbutton':
			x = findValidElemIndex(':input', 'radio_btn_');
			$(this).html('');
			$(this).append($('<table></table>')
					.append('<tr><td class="list"><label>\
							<input type="radio"  value="option1" name="radio_btn_'+ x +'">\
							<span>Option1</span>\
							</label></td></tr>')
					.append('<tr><td class="list"><label>\
							<input type="radio"  value="option2" name="radio_btn_'+ x +'">\
							<span>Option2</span>\
							</label></td></tr>')
					.append('<tr><td class="list"><label>\
							<input type="radio"  value="option3" name="radio_btn_'+ x +'">\
							<span>Option3</span>\
							</label></td></tr>')
				);
			break;
		case 'select':
			x = findValidElemIndex(':input', 'select_box_');
			$(this).append($('<select></select>')
					.attr({
						name: ('select_box_' + x),
						id: 'select_box_' + x
					})
					.append('<option value="option1">Option 1</option><option value="option2">Option 2</option><option value="option3">Option 3</option>'));
			break;
		case 'tarea':
			x = findValidElemIndex(':input', 'text_area_');
			$(this).append($('<textarea>')
					.attr({
						name: 'text_area_' + x,
						id: 'text_area_' + x,
						cols: 50,
						rows: 7
					}));
			break;
		case 'date':
			x = findValidElemIndex(':input', 'calendar_box_');
			$(this).append($('<input>')
					.attr({
						name: "calendar_box_" + x,
						id: "calendar_box_" + x, 
						type: "text",
						textrole: "t",
						elem: "date",
						changemonth: "true",
						changeyear: "true"
					})
					.css('width', '110px')
					.addClass('docovaDatepicker')
				);
			break;
		case 'names':
			x = findValidElemIndex(':input', 'name_picker_');
			$(this).append($('<input>')
					.attr({
						name: "name_picker_" + x,
						id: "name_picker_" + x, 
						"class" : "multipleNamePicker",
						"target" : "name_picker_" + x,
						"restriction": 'no',
						type: "text"
					})
				);
			$(this).append('<BUTTON onclick="" style="HEIGHT: 18px" class="multi" type="button">\
					<IMG border=0 align=top src="'+ docInfo.imgPath +'/icons/vwicn004.gif">\
					</BUTTON>');
			break;
		case 'table':
			x = findValidElemIndex('table', 'sub_table_');
			$(this).html('');
			$(this).append($('<table></table>')
					.attr('id', 'sub_table_' + x)
					.append('<tr><td>&nbsp;</td><td>&nbsp;</td></tr>')
				);
			$('#sub_table_' + x + ' td').resizable({ handles: "e" });
			$(this).droppable({"disabled" : true , "addClasses" : false});
			$('#sub_table_' + x + ' td').droppable({ drop: dropElement });
			break;
		case 'htmlcode':
			x = findValidElemIndex('span', 'html_code_');
			$(this).append('<span id="html_code_' + x + '" class="htmlcode" contenteditable="true"></span>');
			break;
		case 'twigcode':
			x = findValidElemIndex('span', 'twig_code_');
			$(this).append('<span id="twig_code_' + x + '" class="twigcode" contenteditable="true"></span>');
			break;
			
	} 
}

function buildOptionsRows(type, targetEl)
{
	var first_option = $('#form_element_container input[name="' + targetEl.attr('name') + '"]').first(),
		selector = type == 'radio' ? 'rdbtn' : 'chbox',
		checkeds = '';
	$('#'+ selector +'_options input[name="'+ selector +'_label1"]').val($.trim(first_option.parent().text()));
	$('#'+ selector +'_options input[name="'+ selector +'_value1"]').val(first_option.val());
	$('#'+ selector +'_options tr:not(:first)').each(function() {
		$(this).remove();
	});
	$('#form_element_container input[name="' + targetEl.attr('name') + '"]').each(function(index) {
		if (type == 'checkbox') {
			if ($(this).is(':checked')) {
				checkeds += $(this).val() + ';';
			}
		}
		if (index > 0) {
			var cloned_row = $('#'+ selector +'_options tr').first().clone(true);
			var lbl = $.trim($(this).parent().text());
			var vl = $.trim($(this).val());
			cloned_row.find('input:first').attr("name", selector +'_label');
			cloned_row.find('input:first').val(lbl);
			cloned_row.find('input:last').attr("name", selector +'_value' + (index+1));
			cloned_row.find('input:last').val(vl);
			cloned_row.find('img').attr("id", '').prop("src", docInfo.imgPath + '\minus.png').attr("class", 'omit_opt');
			$('#'+ selector +'_options tr:last').after(cloned_row);
			cloned_row = lbl = vl = null;
		}
	});
	if (type == 'checkbox' && checkeds) {
		$('#checkbox_defaults').val(checkeds.slice(0, -1));
	}
}

function addOptionRow(targEl, selector)
{
	var cloned_row = targEl.parentsUntil('tr').parent().clone(true),
		index = $('#'+ selector +'_options input[name^="'+ selector +'_label"]').length;
	cloned_row.find('input:first').attr({
		name: selector +'_label' + (index+1),
		value: ''
	});
	cloned_row.find('input:last').attr({
		name: selector +'_value' + (index+1),
		value: ''
	});
	cloned_row.find('img').attr({
		"id": '',
		"src": docInfo.imgPath + '\minus.png',
		"class": 'omit_opt'
	});
	$('#'+ selector +'_options tr:last').after(cloned_row);
	cloned_row = null;
}

function findValidElemIndex(type, name_prefix) 
{
	var x = 1;
	while (1) {
		if (type == 'table') {
			if (!$('#' + name_prefix + x).prop('tagName')) {
				break;
			}
		}
		else {
			if (!$("#form_element_container " + type + '[name^="'+ name_prefix + x +'"]').first().prop('tagName')) 
			{
				break;
			}
		}
		x++;
	}
	return x;
}

function getMaxColumns(cell)
{
	var colCount = 0;
	cell.parent().children().each(function () {
        if ($(this).prop('colspan') > 1) {
            colCount += $(this).prop('colspan');
        } else {
            colCount++;
        }
    });
    return colCount;
}

function insert_row(element)
{
    var columns = '';
    var colCount = getMaxColumns(element);
    for (var x = 0; x < colCount; x++) {
	    columns += '<td>&nbsp;</td>';
	}

	element.parent().after($('<tr></tr>').append($(columns).resizable({ handles : "e" })));
	$( "#form_element_container td:not(:has(table))" ).droppable({ drop: dropElement });
}

function append_row(element)
{
    var columns = '';
    var colCount = getMaxColumns(element);
    for (var x = 0; x < colCount; x++) {
	    columns += '<td>&nbsp;</td>';
	}

	element.parentsUntil('table').children('tr').last().after($('<tr></tr>').append($(columns).resizable({ handles : "e" })));
	$( "#form_element_container td:not(:has(table))" ).droppable({ drop: dropElement });
}

function delete_row(element)
{
	if (element.parent().siblings().length > 0) {
		var res = confirm('Do you really want to delete this row?');
		if (res) 
		{
			element.parent().remove();
		}
	}
}

function insert_column(element)
{
	var position = element.parent().children().index(element);
	position = (element.prop('colspan') > 1) ? position + element.prop('colspan') : position;
	element.parent().parent().children('tr').each(function() {
		var index = 0;
		$(this).find('td').each(function() {
			if ($(this).prop('colspan') > 1) {
				index += $(this).prop('colspan');
			}
			else {
				index++;
			}
			if ((index - 1) == position)
			{
				$(this).after($('<td>&nbsp;</td>').resizable({ handles: "e" }));
			}
		});
	});
	$( "#form_element_container td:not(:has(table))" ).droppable({ drop: dropElement });
//	$( "#form_element_container td" ).resizable({ handles: "e" });
}

function append_column(element)
{
	element.parent().parent().children('tr').each(function() {
		$(this).append($('<td>&nbsp;</td>').resizable({ handles: "e" }));
	});
	$( "#form_element_container td:not(:has(table))" ).droppable({ drop: dropElement });
//	$( "#form_element_container td" ).resizable({ handles: "e" });
}

function delete_column(element)
{
	if (element.siblings().length > 0) 
	{
		var res = confirm('Do you really want to delete this column with all its content?');
		if (res) {
			var position = element.parent().children().index(element);
			position = (element.prop('colspan') > 1) ? position + element.prop('colspan') : position;
			element.parent().parent().find('tr').each(function() {
				var index = 0;
				$(this).find('td').each(function() {
					if ($(this).prop('colspan') > 1) {
						index += $(this).prop('colspan');
					}
					else {
						index++;
					}

					if ((index - 1) == position)
					{
						if ($(this).prop('colspan') > 1) {
							$(this).prop('colspan', $(this).prop('colspan') - 1);
						}
						else {
							$(this).html('');
							$(this).remove();
						}
					}
				});
			});
		}
	}
}

function merge_right(element)
{
	if (element.next() && element.next().prop('tagName').toLowerCase() == 'td') {
		if (element.next().children().length > 0 && element.children().length == 0) {
			element.next().prop('colspan', (element.prop('colspan') + 1));
			element.remove();
		}
		else {
			element.next().remove();
			element.prop('colspan', (element.prop('colspan') + 1));
		}
	}
}

function merge_cells(element)
{
	var colspan = 0;
	element.parent().children('td').each(function() {
		colspan += $(this).prop('colspan');
		if ($(this)[0] != element[0]) {
			$(this).remove();
		} 
	});
	
	element.prop('colspan', colspan);
}

function cell_properties(element)
{
	activeObj = element;
	$('#static_id').val($(activeObj).attr('id'));
	var stClasses = $(activeObj).attr('class') ? $(activeObj).attr('class').split(/\s+/) : [];
	for (var c = 0; c < stClasses.length; c++) {
		if (stClasses[c] == 'ui-resizable' || stClasses[c] == 'ui-droppable' || stClasses[c] == 'context-menu-active' || stClasses[c] == 'ui-droppable-disabled' || stClasses[c] == 'ui-state-disabled') {
			stClasses[c] = null;
		}
	}
	$('#static_class').val($.trim(stClasses.join(' ')));
	$('#static_style').val($(activeObj).attr('style'));
	$( "#static_properties" ).dialog("option", "title", docInfo.cellProperties).dialog("open");
}

function table_properties(element)
{
	activeObj = element.parentsUntil('table').parent();
	$('#static_id').val($(activeObj).attr('id'));
	var stClasses = ($(activeObj).attr('class')) ? $(activeObj).attr('class').split(/\s+/) : [];
	for (var c = 0; c < stClasses.length; c++) {
		if (stClasses[c] == 'ui-resizable' || stClasses[c] == 'ui-droppable' || stClasses[c] == 'context-menu-active' || stClasses[c] == 'ui-droppable-disabled' || stClasses[c] == 'ui-state-disabled') {
			stClasses[c] = null;
		}
	}
	$('#static_class').val($.trim(stClasses.join(' ')));
	$('#static_style').val($(activeObj).attr('style'));
	$( "#static_properties" ).dialog("option", "title", docInfo.tblProperties).dialog("open");

}



function delete_element(element)
{
	var res = confirm('Do you really want to delete this element?');
	if (res) {
		if (element.find('input:radio').length || element.find('input:checkbox').length) {
			element.html('');
			element.closest('table').find('td').resizable({ handles: "e" });
		}
		else {
			element.children(':not(div)').each(function() {
				$(this).remove();
			});
		}
	}
}