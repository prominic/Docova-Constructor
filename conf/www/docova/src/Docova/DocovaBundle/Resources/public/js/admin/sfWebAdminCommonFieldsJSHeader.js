var allowClose = false;
var forceSave = false;

$(function() {
	$('#closeBtn').button({
		icons : {
			primary : 'ui-icon-close'
		}
	})
	.click(function() {
		if (docInfo.isDocBeingEdited == "true") {
			SaveBeforeClosing();
		}
		else {
			CloseDocument();
		}
	});

	$('#saveAndCloseBtn').button({
		icons : {
			primary : 'ui-icon-check'
		}
	})
	.click(function() {
		SaveAndClose(true);
	});
	
	$('#editBtn').button({
		icons : {
			primary : 'ui-icon-pencil'
		}		
	})
	.click(function() {
		$('body').hide();
	});

	$('input.namepicker').each(function() {
		var $this = $(this);
		$this.autoComplete({
			url : docInfo.PortalWebPath + '/getSearchNames',
			shortName : true,
			selectionContainer : $this.attr('target'),
			type: ($this.attr('elmtype') == 'single' ? 'single' : 'multiple')
		});
	});
	
	$('button.show-namepicker').button({
		text: false,
		icons: {
			primary: $(this).prev('input').attr('elmtype') == 'single' ? 'ui-icon-person' : 'ui-icon-group'
		}
	})
	.click(function(e) {
		e.preventDefault();
		var elmtype = $(this).prev('input').attr('elmtype') == 'single' ? 'single' : 'multi';
		window.top.Docova.Utils.showAddressDialog({ fieldname: $(this).prev('input').attr('target'), dlgtype: elmtype, sourcedocument: document});
	})

	$('button.namepicker').button({
		text: false,
		icons: {
			primary: $(this).attr('single') == 'true' ? 'ui-icon-person' : 'ui-icon-group'
		}
	})
	.click(function(e) {
		e.preventDefault();
		var elmtype = $(this).attr('single') == 'true' ? 'single' : 'multi';
		window.top.Docova.Utils.showAddressDialog({ fieldname: $(this).attr('field'), dlgtype: elmtype, sourcedocument: document});
	})

	$('.hideShow').click(function() {
		if ($(this).prop('type').toLowerCase() == 'checkbox')
		{
			if ($(this).prop('checked')) {
				$('.' + $(this).attr('show')).removeClass('hidden');
				if ($(this).attr('hide')) {
					$('.' + $(this).attr('hide')).addClass('hidden');
				}
			}
			else {
				$('.' + $(this).attr('show')).addClass('hidden');
				if ($(this).attr('hide')) {
					$('.' + $(this).attr('hide')).removeClass('hidden');
				}
			}
		}
		else {
			if ($(this).attr('show'))
			{
				var showClasses = $(this).attr('show').split(' ');
				for (var c = 0; c < showClasses.length; c++)
				{
					$('.' + showClasses[c]).each(function() {
						var classes = $(this).attr('class').split(' '),
							visible = true;
						for (var x = 0; x < classes.length; x++) 
						{
							if (classes[x] != 'hidden' && classes[x] != 'elNote' && classes[x] != showClasses[c]) {
								visible = ($('input[name="' + classes[x] + '"]').prop('tagName') != undefined && $('input[name="' + classes[x] + '"]').prop('checked')) ? true : ($('input[name="' + classes[x] + '"]').prop('tagName') ? false : true);
							}
						}
						if (visible === true) 
						{
							$(this).removeClass('hidden');
						}
					});
				}
			}
			
			if ($(this).attr('hide')) {
				var hideClasses = $(this).attr('hide').split(' ');
				for (var c = 0; c < hideClasses.length; c++)
				{
					$('.' + hideClasses[c]).addClass('hidden');
				}
			}
		}
	});
	
	$('.hidetoload').removeClass('hidetoload');
	if ($('form').length) {
		$('form').height($(window).height() - 50);
	}
	else {
		$('#frmEmulator').height($(window).height() - 50);
	}
	$(window).resize(function() {
		if ($('#frmEmulator').length) {
			$('#frmEmulator').height($(window).height() - 50);
		}
		else {
			$('form').height($(window).height() - 50);
		}
	});
});

// preventing backspace on forms except for inputs and textareas
$(document).on("keydown", function (e) {
    if ((e.which == 8 || e.which == 13) && !$(e.target).is("input, textarea")) {
        e.preventDefault();
    }
});

// set the field focus
function setFieldFocus(fldObj){
	if (docInfo.isDocBeingEdited=="true"){
		if ( fldObj )
			fldObj.focus();
	}
}

// for user profile
function userClose()
{
	window.parent.fraUserProfile.location.href = docInfo.userProfileRetUrl; //call from homeTop
}

function CloseDocument(refreshView)
{
	resizeUL = false; //Don't resize any Uploaders on the form
	allowClose = true; //to let the onUnload event fall through	

	//----------------------------------------------------------------	
    if(docInfo.Mode=="window"){
		window.close();
		return false;
	}
    
    if (docInfo.userProfileRetUrl.indexOf("uType=user") != -1) {
    	if(window.top.Docova.GlobalStorage["divDlgUserProfile"] != null){
    		var parentWin = window.top.Docova.GlobalStorage["divDlgUserProfile"].sourcewindow;
    		if(parentWin){
    			parentWin.dlgUserProfile.closeDialog();
    			return false;
    		}
    	}
    	//window.parent.fraUserProfile.location.href = docInfo.userProfileRetUrl; //call from homeTop
    }
    else {
		var top_frame = parent.frames['fraAdminContentTop'];
		if (!top_frame) { return false; }
		var index = top_frame.tabs.tabs("option", "active");
		top_frame.removeTabByIndex(index);
    }
}

function SaveAndClose(refreshView)
{
	// only save upton validation
	if (validateDocumentFields() )
	{
		$('body').hide();
		// check if its app settings save
		if (docInfo.isAppSettings)
		{
			$('form').submit(); //regular form submit
		}
		else
		{
 			if(! docInfo.isDocBeingEdited) {return CloseDocument(refreshView);}
			allowClose = true; //to let the onUnload event fall through
			if (document.all.DLIUploader1)
			{
				document.all.DLIUploader1.submit();
			}
			else
			{
				if (docInfo.formName =="Library" && docInfo.isNewDoc=="true") {
					window.top.Docova.Utils.messageBox({
						prompt : prmptMessages.msgACF001,
						icontype : 2,
						msgboxtype : 4,
						width: 450,
						title : prmptMessages.msgACF002,
						onYes : function() {
							toggleActionButton(prmptMessages.msgACF003);
						},
						onNo : function() {
							if ($('input[type=submit]').length) {
								$('input[type=submit]').click();
							}
							else {
								document.forms[0].submit();
							}
						}
					});
				}
				else {
					if ($('input[type=submit]').length) {
						$('input[type=submit]').click();
					}
					else {
						document.forms[0].submit();
					}
				}
			}
		}
	}
}

function SaveBeforeClosing(noCancel)
{
	if(!docInfo.isDocBeingEdited) { return false; }
	window.top.Docova.Utils.messageBox({
		prompt : prmptMessages.msgACF004,
		icontype : 2,
		msgboxtype : (noCancel) ? 4 : 3, 
		title : prmptMessages.msgACF005,
		width: 450,
		onYes : function() {
			SaveAndClose(true);
		},
		onNo : function() {
			CloseDocument();
		}
	});
}

function IsValidParent()
{
	try	{
		srcWindow = window.parent.fraContentTop;
		if(srcWindow.ViewLoadDocument){return true;}
	}
	catch (e) {
		return false;
	}
	return false;
}

function processOnUnload()
{
	if(!allowClose && docInfo.isDocBeingEdited )
	{
		SaveBeforeClosing(true);
	}
}

//---------------------------- create new document ----------------------------
function ViewCreateDocument(formname, parentlink, label)
{
	docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + formname + "?OpenForm";
	if(parentlink){
		docUrl = docUrl + "&ParentUNID=" + docInfo.DocID;
	}
	ViewLoadDocument(docUrl, label);
}

function ViewLoadDocument(docUrl, label)
{
	if(docInfo.isRecycleBin) // documents in recycle bin cannot be opened, just the properties dialog is displayed
	{
		var entryObj = objView.GetCurrentEntry();
		if(!entryObj) {return; }
		var recType = entryObj.GetElementValue("rectype");
		if(recType == "fld") //deleted folder
		{
			ShowFolderProperties(entryObj.entryId);
		}
		else if(recType == "doc") //deleted doc
		{
			ShowDocumentProperties(entryObj.entryId);
		}
		return;
	}
	// regular folder
	if(!docUrl && objView.currentEntry)
	{
		docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/readAdminDocument/" + objView.folderViewName + '/' + objView.currentEntry + "?OpenDocument&ParentUNID=" + docInfo.DocID;
	}
	if(!docUrl) {
		window.top.Docova.Utils.messageBox({
			prompt : prmptMessages.msgACF006,
			icontype : 0,
			title : prmptMessages.msgCF019,
			width: 300,
			onOk : function() { return false; }
		});
	}
	
	openNewTab(docUrl, label);
/*
	try{
		window.parent.fraContentBottom.showBar(); //show document loading progress bar
	}
	catch(e) {}
	window.parent.fraContentBottom.location.href=docUrl ;
	window.parent.fsContentFrameset.rows = "25,*";
*/
}

function openNewTab(docUrl, tab_label)
{
	var obj = parent.frames["fraAdminContentTop"];
	var fsContainer = parent.document.getElementById("fsAdminContentFrameset");
	if (obj == null || !fsContainer){
		return;
	}

	var id = Math.round(Math.random() * 10000);
	obj.addTab(tab_label, id);

	var newFrame = parent.document.createElement("frame");
	newFrame.id = 'fraTabs' + id;
	newFrame.name = 'fraTabs' + id;
	newFrame.scrolling = 'no';

	fsContainer.appendChild(newFrame);

	var rows_str = '40,';
	for (var x = 2; x < parent.fsAdminContentFrameset.children.length; x++) {
		rows_str += '0,';
	}
	rows_str += '*';
	parent.fsAdminContentFrameset.rows = rows_str;
	var tmp = parent.document.getElementById('fraTabs' + id);
	tmp.src = docUrl;
}


//------------------------------------------------------------------------------------------------------------------------------------
// to handle toggle of  save and close button to avoid more than one clicks while doc is being saved
//------------------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
// getActionButton - returns jQuery object for DOCOVA action button
// DOM element.  Used to hide/modify standard form action buttons 
// dependencies: jQuery
//------------------------------------------------------------------------------------
function getActionButton(buttonlabel){
     var result = false;
   	jQuery('#FormHeader td[id|="CDA"]').each(function(index){
	   var jQbutton = jQuery(this).children("span:first-child");
        if(jQbutton.text() == buttonlabel){
            result = jQbutton.parent();
            return result;
        }
   	});
	return result;
}

//-----------------------------------------------------------------------------------------------------------
// enable disable DOCOVA action button
//-----------------------------------------------------------------------------------------------------------
function toggleActionButton(buttonlabel){
	var jQbutton = getActionButton(buttonlabel);
	if (jQbutton){
		//var chjDisabled=jQbutton.attr("disabled");
		var toggle=jQbutton.prop("disabled");
		if( toggle){
			jQbutton.prop("disabled", false);
		}else{
			jQbutton.prop("disabled", true);
		}
		return true;
	}
	return false;
}
