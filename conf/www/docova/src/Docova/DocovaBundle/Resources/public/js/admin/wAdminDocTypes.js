// content section drop down TEXT
var arrBaseListText = new Array('None', 'Embedded form 1', 'Embedded form 2', 'Embedded form 3', 'Docova text editor', 'Rich text editor',
								'Plain text editor', 'Attachments', 'RSS newsfeed', 'Mail header',  'Web bookmark', 'Related emails',
								'Related documents', 'Related links', 'Keyword', 'Comments');
// content section drop down list items
var arrBaseListValue = new Array("N", "CS1","CS2","CS3", "DRTXT","RTXT", "TXT", "ATT", "RSS","MEMO", "WEB","REMAIL", "RDOC","RLINK", "KEY","COMM");
var arrContentSectionSelected = [];

$(function() {
	$('form').height($(window).height() - 46);
	$( window ).unload(function() {
		return processOnUnload();
	});

	$('#divFormContainer').accordion({
		heightStyle: "content",
		collapsible: true
	});
	$("#appearance_container").tabs();
	$("#advance_container").tabs();

	$('.colorPicker').button({
		icons: {
			primary: 'ui-icon-colorpicker'
		}
	})
	.click(function(e) {
		e.preventDefault();
		Docova.Utils.colorPicker(e, $(this).attr('field'));
	});
	
	$('.customsubforms').on('change', function() {
		var selector = $(this).prop('name').substring(2);
		if ($(this).val() != 'NIL') {
			$('#' + selector).val($(this).val());
		}
		else {
			$('#' + selector).val('Please enter embedded form name');
		}
	});

	$('#EnableVersions').click(function() {
		if ($(this).prop('checked')) {
			$('#RestrictLiveDrafts').prop('disabled', false);
		}
		else {
			$('#RestrictLiveDrafts').prop('checked', false);
			$('#RestrictLiveDrafts').prop('disabled', true);
			$('.restricted').prop('disabled', true);
			$('.EnableVersions input[type=checkbox]').prop('checked', false);
		}
	});

	$('#StrictVersioning').click(function() {
		if ($(this).prop('checked')) {
			$('.restricted').prop('disabled', false);
		}
		else {
			$('.restricted').prop('checked', false);
			$('.restricted').prop('disabled', true);
		}
	});

	$('#ValidateChoice').on('change', function() {
		if ($(this).val() == 1) {
			$('.builtIn').removeClass('hidden');
			$('.customValidation').addClass('hidden');
		}
		else if ($(this).val() == 2) {
			$('.builtIn').addClass('hidden');
			$('.customValidation').removeClass('hidden');
		}
		else {
			$('.builtIn').addClass('hidden');
			$('.customValidation').addClass('hidden');
		}
	});
	
	var sectionTabs = $( "#sectionsContainer" ).tabs({heightStyle: "content"});

	$('.sectionCombos').on('change', function() {
		var visible = false;
		$('.sectionCombos').each(function() {
			if ($(this).val() != 'N' && $(this).val() != 'RSS' && $(this).val() != 'MEMO' && $(this).val() != 'KEY') {
				visible = true;
				return;
			}
		});

		if (visible === true) {
			$('#sectionsContainer').removeClass('hidden');
			if ($(this).val() == 'CS1' || $(this).val() == 'CS2' || $(this).val() == 'CS3') {
				$('#hstab1, #stab1').removeClass('hidden');
				sectionTabs.tabs("option", "active", 0);
				$('#stab1 TR.' + $(this).val()).not('.' + $(this).val().toLowerCase() +'ShowInMore').removeClass('hidden');
			}
			else if ($(this).val() == 'TXT' || $(this).val() == 'RTXT' || $(this).val() == 'DRTXT') {
				$('#hstab2, #stab2').removeClass('hidden');
				$('#stab2 table.elmContainer').removeClass('hidden');
				sectionTabs.tabs("option", "active", 1);
			}
			else if ($(this).val() == 'ATT') {
				$('#hstab3, #stab3').removeClass('hidden');
				$('#stab3 table.elmContainer').removeClass('hidden');
				sectionTabs.tabs("option", "active", 2);
			}
			else if ($(this).val() == 'COMM') {
				$('#hstab4, #stab4').removeClass('hidden');
				$('#stab4 table.elmContainer').removeClass('hidden');
				sectionTabs.tabs("option", "active", 3);
			}
			else if ($(this).val() == 'RDOC') {
				$('#hstab5, #stab5').removeClass('hidden');
				$('#stab5 table.elmContainer').removeClass('hidden');
				sectionTabs.tabs("option", "active", 4);
			}
			else if ($(this).val() == 'REMAIL' || $(this).val() == 'RLINK') {
				$('#hstab6, #stab6').removeClass('hidden');
				$('#stab6 table.elmContainer').removeClass('hidden');
				$('#stab6 .' + $(this).val()).removeClass('hidden');
				sectionTabs.tabs("option", "active", 5);
			}
			else if ($(this).val() == 'WEB') {
				$('#hstab7, #stab7').removeClass('hidden');
				$('#stab7 table.elmContainer').removeClass('hidden');
				sectionTabs.tabs("option", "active", 6);
			}
		}
		else {
			$('#hstab1, #stab1, #hstab2, #stab2, #hstab3, #stab3, #hstab4, #stab4, #hstab5, #stab5, #hstab6, #stab6, #hstab7, #stab7, .CS1, .CS2, .CS3, .REMAIL, .RLINK').addClass('hidden');
			$('#sectionsContainer').addClass('hidden');
		}

		handleContentSectionOptions($(this));
	});

	$('.multiSelector').multiselect({
		position: { my: 'left top', at: 'left bottom'},  //required due to content scroll workaround
		selectedList: 3,
		appendTo: "#divFormContainer" 
	});

	var templateList = $('#TemplateList').multiselect({
		position: { my: 'left top', at: 'left bottom' },
		selectedList: 3,
		appendTo: "#divFormContainer",
		header: false,
		click: function(e, ui) {
			if ($('#TemplateList option:selected').length > 1 || ($('#TemplateList option:selected').length == 0 && ui.checked)) {
				$('TR.autoLoadTemp').removeClass('hidden');
			}
			else if ($('#TemplateList option:selected').length <= 1 && !ui.checked) {
				$('TR.autoLoadTemp').addClass('hidden');
			}
		}
	});

	$('#TemplateType').on('change', function() {
		if ($(this).val() != 'None') {
			$.ajax({
				'url' : docInfo.PortalWebPath + '/luFileTemplatesByType?RestrictToCategory=' + $(this).val(),
				async: false,
				cache: false,
				type: "GET",
				dataType: "xml"
			})
			.done(function(response) {
				if (response && $(response).has('viewentry').length) {
					$(response).find('viewentry').each(function() {
						var txt = $(this).find('entrydata[name="TemplateName"]').text();
						var value = $(this).find('entrydata[name="DocKey"]').text();
						if ($.trim(txt) && $.trim(value)) {
							templateList.append($('<option></option>').attr('value', value).text(' '+txt));
						}
						templateList.multiselect('refresh');
					});
					$('TR.templateList').removeClass('hidden');
				}
			});
		}
		else {
			$('TR.templateList , TR.autoLoadTemp').addClass('hidden');
		}
	});

	if (docInfo.isDocBeingEdited && docInfo.isNewDoc == '') {
		var i = $( "#sectionsContainer li" ).not('.hidden').index();
		$( "#sectionsContainer" ).tabs({active : i});
	}	
});

//validate user profile fields before submitting
function validateDocumentFields()
{
	return true;
}

function handleContentSectionOptions(obj)
{
	var newList = arrBaseListValue.slice(),
	flagAddOption = false;
	if (obj.val() == 'N') {
		flagAddOption = true;
	}
	else if (obj.val() == 'TXT' || obj.val() == 'RTXT') {
		$('.noneDRT').removeClass('hidden');
	}
	else if (obj.val() == 'DRTXT'){
		$('.noneDRT').addClass('hidden');
	}
	$('.sectionCombos').each(function() {
		if ($(this).val() != 'N') {
			var index = newList.indexOf($(this).val());
			if (index > -1) {
				newList.splice(index, 1);
			}
		}

		if ($(this).val() == 'TXT' || $(this).val() == 'RTXT' || $(this).val() == 'DRTXT')
		{
			var index = newList.indexOf('TXT');
			if (index > -1) { newList.splice(index, 1); }
			index = newList.indexOf('RTXT');
			if (index > -1) { newList.splice(index, 1); }
			index = newList.indexOf('DRTXT');
			if (index > -1) { newList.splice(index, 1); }
		}

		if ($(this).val() == 'ATT')
		{
			var index = newList.indexOf('ATT');
			if (index > -1) { newList.splice(index, 1); }
		}
	});

	$('.sectionCombos').each(function() {
		if ($(this).prop('id') != obj.prop('id')) {
			reCreateContentSectionList($(this), newList, flagAddOption);
		}
		else if (flagAddOption) {
			reCreateContentSectionList($(this), newList, flagAddOption);
		}
		hideNoneSelectedTabs(newList);
	});
}

//-----  regenerate the content list
function reCreateContentSectionList(dropList, arrNewList, flagAddOption) {
	var selectedVal = dropList.val();
	if( flagAddOption ) {
		// -------------- to add option back to the lists --------------
		var currentSelectedOpt = { text: dropList.find(":selected").text(), value: dropList.val() };
		dropList.children('option').remove();
		for (var i = 0; i < arrNewList.length; i++) { 
			dropList.append($('<option></option>').attr('value', arrNewList[i]).text(arrBaseListText[arrBaseListValue.indexOf(arrNewList[i])]));
		}
		if (!dropList.has('option[value="'+ currentSelectedOpt.value +'"]').length) {
			dropList.append($('<option></option>').attr({
				value: currentSelectedOpt.value,
				selected: true
			})
			.text(currentSelectedOpt.text));
		}
	}
	else
	{
		// ---------- to remove options from the lists -------------------
		if(dropList.children('option').length > 0) {
			dropList.children('option').each(function() {
				if(arrNewList.indexOf($(this).prop('value')) == -1 && selectedVal != $(this).prop('value')) {
					$(this).remove();
				}
			});
		}
	}
}

function hideNoneSelectedTabs(noneSelectedOpt)
{
	if (noneSelectedOpt.length) {
		if (noneSelectedOpt.indexOf('CS1') != -1 && noneSelectedOpt.indexOf('CS2') != -1 && noneSelectedOpt.indexOf('CS3') != -1) {
			$('#hstab1, #stab1, .CS1, .CS2, .CS3').addClass('hidden');
		}
		else {
			if (noneSelectedOpt.indexOf('CS1') != -1) {
				$('.CS1').addClass('hidden');
			}
			if (noneSelectedOpt.indexOf('CS2') != -1) {
				$('.CS2').addClass('hidden');
			}
			if (noneSelectedOpt.indexOf('CS3') != -1) {
				$('.CS3').addClass('hidden');
			}
		}
		if (noneSelectedOpt.indexOf('TXT') != -1 || noneSelectedOpt.indexOf('RTXT') != -1 || noneSelectedOpt.indexOf('DRTXT') != -1) {
			$('#hstab2, #stab2, .noneDRT').addClass('hidden');
		}
		if (noneSelectedOpt.indexOf('ATT') != -1) {
			$('#hstab3, #stab3').addClass('hidden');
		}
		if (noneSelectedOpt.indexOf('COMM') != -1) {
			$('#hstab4, #stab4').addClass('hidden');
		}
		if (noneSelectedOpt.indexOf('RDOC') != -1) {
			$('#hstab5, #stab5').addClass('hidden');
		}
		if (noneSelectedOpt.indexOf('REMAIL') != -1 && noneSelectedOpt.indexOf('RLINK') != -1) {
			$('#hstab6, #stab6, .REMAIL, .RLINK').addClass('hidden');
		}
		else {
			if (noneSelectedOpt.indexOf('REMAIL') != -1) {
				$('.REMAIL').addClass('hidden');
			}
			if (noneSelectedOpt.indexOf('RLINK') != -1) {
				$('.RLINK').addClass('hidden');
			}
		}
		if (noneSelectedOpt.indexOf('WEB') != -1) {
			$('#hstab7, #stab7').addClass('hidden');
		}
	}
}
