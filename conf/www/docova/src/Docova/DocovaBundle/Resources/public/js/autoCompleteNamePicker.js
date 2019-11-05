/**
 * jQuery based plugin to generate an auto complete
 * name picker and add/remove the selected users
 * @author: Javad Rahimi
 */
(function( $ ) {
	$.fn.autoComplete = function( options ) {
		return this.each(function() {
			var $this = $(this);
			var	typingTimer = null;
			var	opts = $.extend({
					type: 'multi',
					url: 'getSearchNames',
					ristrictTo: [],
					actualField: "",
					maxResult: 15,
					delimiter: ',',
					droppedListClass: '',
					selectionContainer: "",
					typeContainer: '',
					shortName: false
				}, options);

			$this.on('keydown', function(e) {
				if (e.which == 9) {
					if ($this.attr('valid') == 'valid') {
						closeList();
					}
					else {
						$.ajax({
							url: opts.url,
							type: 'POST',
							async: false,
							data: { 'searchfor': encodeURIComponent($this.val()) }
						})
						.done(function(response) {
							if (response && response.count == 1)
							{
								$this.attr('valid', 'valid');
							}
							else {
								$this.attr('valid', '');
							}
							closeList();
						});
					}
				}
			});

			$this.on('keyup', function(e) {
				if ($.inArray(e.which, [9, 16, 17, 18, 20, 33, 34, 35, 36, 45, 144]) == -1) {
					clearTimeout(typingTimer);
					if ($this.val()) {
						typingTimer = setTimeout(generateList, 500);
					}
				}
				else {
					return false;
				}
			});
	
			function generateList()
			{
				if ($.trim($this.val())) 
				{
					if (opts.ristrictTo.length && $this.attr('restriction') != 'no')
					{
						var listContainer = $('<div tabindex="1" id="autoCompleteContainer"><ul' + (opts.droppedListClass ? ' class="'+ opts.droppedListClass +'"' : '') + '></ul></div>');
						for (var x = 0; x < opts.ristrictTo.length; x++)
						{
							var item = '<li '+ (x == 0 ? 'class="highlight"' : '') +'>';
							item += '<span class="optValue hidden">' + opts.ristrictTo[x] + '</span>';
							item += '<samp class="person">' + opts.ristrictTo[x] + '</samp>';
							item += '</li>';
							listContainer.find('ul').append(item);							
						}
						
						listContainer.on('click', 'li', function(e) {
							$(this).addClass('highlight');
							selectItem($(this));
						});

						listContainer.position({
							my: 'left top',
							at: 'left bottom',
							of: $this
						})
						.appendTo('body');
						$this.attr('valid', '');
					}
					else {
						$this.attr('valid', '');
						$.ajax({
							url: opts.url,
							type: 'POST',
							async: false,
							data: { 'searchfor': encodeURIComponent($this.val()) }
						})
						.done(function(response) {
							if ($('#autoCompleteContainer').length) {
								$('#autoCompleteContainer').remove();
							}
							if (response && response.count)
							{
								var listContainer = $('<div tabindex="1" id="autoCompleteContainer"><ul' + (opts.droppedListClass ? ' class="'+ opts.droppedListClass +'"' : '') + '></ul></div>');
								$.each(response.users, function(index, node) {
									var item = '<li '+ (index == 0 ? 'class="highlight"' : '') +'>',
										itype = 'person';
									if (node.type && node.type == 'group') {
										itype = 'group';
									}
									item += '<span class="optValue hidden">' + node.userNameDnAbbreviated + '</span>';
									item += '<samp class="'+ itype +'">' + (opts.shortName === true ? (node.Display_Name + ' - ['+ node.userNameDnAbbreviated +']') : (node.userNameDnAbbreviated + ' ['+ node.Display_Name +']')) + '</samp>';
									item += '</li>';
									listContainer.find('ul').append(item);
									
									if (index >= opts.maxResult) {
										return false;
									}
								});
								
								listContainer.on('click', 'li' ,function() {
									$(this).addClass('highlight');
									selectItem($(this));
								});
		
								listContainer.position({
									my: 'left top',
									at: 'left bottom',
									of: $this
								})
								.appendTo('body');
							}
						});
					}
					$('#autoCompleteContainer').focus();

					$('#autoCompleteContainer').on('keydown', function(e) {
						if (e.which == 9) {
							if ($this.attr('valid') == 'valid') {
								closeList();
							}
						}
					});
					
					$('#autoCompleteContainer').on('keyup', function(e) {
						switch (e.which) 
						{
							case 13: //enter
								e.preventDefault();
								if ($('#autoCompleteContainer').length) {
									var selectedObj = $('#autoCompleteContainer li.highlight');
									selectItem(selectedObj);
								}
								return false;
								break;
							case 27: //escape
							case 9: //tab
								e.preventDefault();
								closeList();
								break;
							case 38: //up
							case 104:
								moveUp();
								break;
							case 40: //down
							case 98:
								moveDown();
								break;
							default:
								if ($.inArray(e.which, [9, 16, 17, 18, 20, 33, 34, 35, 36, 45, 144]) == -1) {
									clearTimeout(typingTimer);
									if ($this.val()) {
										typingTimer = setTimeout(generateList, 500);
									}
								}
								break;
						}
					});
				}
				else {
					closeList();
				}
			}
			
			function selectItem(selectedObj)
			{
				if (opts.type == 'single')
				{
					$this.val(selectedObj.find('.optValue').text());
					if (opts.typeContainer != '')
					{
						$('#'+opts.typeContainer).val(selectedObj.find('samp.group').length ? 'group' : 'person');
					}
				}
				else {
					var slContainer = null;
					if (opts.selectionContainer) {
						var selectorId = opts.selectionContainer.toLowerCase();
						if ($('#slContainer' + selectorId).length && $('#slContainer' + selectorId).hasClass('slContainer')) {
							slContainer = $('#slContainer' + selectorId);
						}
						else {
							slContainer = $('<em class="slContainer" id="slContainer'+opts.selectionContainer.toLowerCase()+'"></em>');
							$this.nextAll('input').first().after(slContainer);
						}
					}
					else {
						if ($this.parent().find('.slContainer').length) {
							slContainer = $this.parent().find('.slContainer');
						}
						else {
							slContainer = $('<em class="slContainer"></em>');
							$this.nextAll('input').first().after(slContainer);
						}
					}
					
					if (slContainer)
					{
						var selected_names_elem = $('#' + $this.attr('target'));						
						var mvsep = ($(selected_names_elem).attr("mvdisep") || $(selected_names_elem).attr("mvsep") || opts.delimiter);
						mvsep = mvsep.split(" ")[0];
						if(mvsep == "semicolon"){
							mvsep = ";";
						}else if(mvsep == "comma"){
							mvsep = ",";
						}else if(mvsep == "newline" || mvsep == "blankline"){
							mvsep = "\n";
						}else if(mvsep == "space"){
							mvsep = " ";
						}						
						
						
						var currentVal = $(selected_names_elem).val();
						currentVal = multiSplit(currentVal, mvsep);
						if ($.inArray(selectedObj.find('.optValue').text(), currentVal) == -1)
						{
							slContainer.append('<span>'+$.trim(selectedObj.find('.optValue').text())+'<i class="far fa-times removename"></i></span>');
							if (!$.trim(currentVal.join()) || currentVal.length < 1) {
								$('#' + $this.attr('target')).val(selectedObj.find('.optValue').text());
							}
							else {
								$('#' + $this.attr('target')).val(currentVal.join(mvsep) + mvsep + selectedObj.find('.optValue').text());
							}
						}
					}
					$this.val('');
				}
				$this.attr('valid', 'valid');
				closeList();
			}
			
			function closeList()
			{
				if (opts.ristrictTo.length > 0) {
					if ($.inArray($this.val(), opts.ristrictTo) == -1) {
						$this.val('');
					}
				}
				else if ($this.attr('valid') != 'valid') {
					$this.val('');
				}
				$('#autoCompleteContainer').unbind();
				$('#autoCompleteContainer').empty();
				$('#autoCompleteContainer').remove();
			}
			
			function moveUp()
			{
				if ($('#autoCompleteContainer li').length) {
					var currentItem = $('#autoCompleteContainer li.highlight');
					if (currentItem.length) {
						if (currentItem.prev('li').length) {
							currentItem.removeClass('highlight');
							currentItem.prev('li').addClass('highlight');
							currentItem = currentItem.prev('li');
						}
					}
				}
			}
			
			function moveDown()
			{
				if ($('#autoCompleteContainer li').length) {
					var currentItem = $('#autoCompleteContainer li.highlight');
					if (currentItem.length) {
						if (currentItem.next('li').length) {
							currentItem.removeClass('highlight');
							currentItem.next('li').addClass('highlight');
							currentItem = currentItem.next('li');
						}
					}
				}
			}			
		});
	}
	
	$(document).on('click', 'em.slContainer i.removename', function() {
		var to_remove = $.trim($(this).parent().text());
		var selected_names_elem = $(this).closest('em.slContainer').prev('input[type=hidden]');
		var mvsep = ($(selected_names_elem).attr("mvdisep") || $(selected_names_elem).attr("mvsep") || opts.delimiter);
		mvsep = mvsep.split(" ")[0];
		if(mvsep == "semicolon"){
			mvsep = ";";
		}else if(mvsep == "comma"){
			mvsep = ",";
		}else if(mvsep == "newline" || mvsep == "blankline"){
			mvsep = "\n";
		}else if(mvsep == "space"){
			mvsep = " ";
		}
		var selected_names = $(selected_names_elem).val();
		selected_names = selected_names.length ? multiSplit(selected_names, mvsep) : [];
		var index = $.inArray(to_remove, selected_names);
		if (index != -1) {
			selected_names.splice(index, 1);
		}
		
		$(this).closest('em.slContainer').prev('input[type=hidden]').val(selected_names.join(mvsep));
		$(this).parent().remove();
		if ($.trim($(this).closest('em.slContainer').text()) == '') {
			$(this).closest('em.slContainer').remove();
		}
	})
		
}(jQuery));

$(document).on('click', function(e) {
	if (!$(e.target).closest('#autoCompleteContainer').length && !$(e.target).hasClass('singleNamePicker') && !$(e.target).hasClass('multipleNamePicker')) {
		$('#autoCompleteContainer').empty();
		$('#autoCompleteContainer').remove();
	}
});