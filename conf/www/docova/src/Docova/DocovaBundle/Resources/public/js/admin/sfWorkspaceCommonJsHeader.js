var allowClose = false;
var forceSave = false;
//var DLIUploader1 = null;
$(function() {
	$('#tdActionBar a').each(function(index,element) {
		$(element).button({
			text: $.trim($(this).text()) != '' ? true : false,
			label: $.trim($(this).text()) != '' ? $.trim($(this).text()) : null,
			icons: {
				primary: ($.trim($(this).attr('primary'))) ? $(this).attr('Primary') : null,
				secondary: ($.trim($(this).attr('secondary'))) ? $(this).attr('secondary') : null
			}
		});
	
		$( "button" ).button().click(function( event ) {
			event.preventDefault();
		});
	})
	.promise().done(function() {
		$('#FormHeader').show();
	});
});

// set the field focus
function setFieldFocus(fldObj){
	if (docInfo.isDocBeingEdited=="true")
		fldObj.focus();
}

// for user profile
function userClose()
{
	window.parent.fraToolbar.OpenUserProfile(docInfo.userProfileRetUrl); //call from homeTop
}

function CloseDocument(refreshView)
{
	resizeUL = false; //Don't resize any Uploaders on the form
	allowClose = true; //to let the onUnload event fall through	

	//-----If this is the User Profile opened in its own dialog from home top button----
	if(window.top.Docova.GlobalStorage["divDlgUserProfile"] != null){
		var parentWin = window.top.Docova.GlobalStorage["divDlgUserProfile"].sourcewindow;
		if(parentWin){
			parentWin.dlgUserProfile.closeDialog();
			return false;
		}
	}

	//-----If mode querystring is window-----
    if(docInfo.Mode=="window"){
		window.close();
		return false;
	}
	//----------------------------------------------------------------
	if(IsValidParent()){
		srcWindow.ViewUnloadDocument(refreshView, docInfo.DocID);
		}
	else{
		window.close();
		}
}

function SaveAndClose(refreshView)
{
	// only save upton validation
	if (validateDocumentFields() )
	{
		// check if its app settings save
		if (docInfo.isAppSettings)
		{
			document.forms[0].submit(); //regular form submit
		}
		else
		{
 			if(!docInfo.isDocBeingEdited) {return CloseDocument(refreshView);}
			allowClose = true; //to let the onUnload event fall through
			//if (DLIUploader1 != null)
			if(typeof DLIUploader1 != "undefined")
			{
				DLIUploader1.Submit()
			}else{
				if (docInfo.formName =="Library" && docInfo.isNewDoc=="true") {
					var msg = "You are about to create a new Library.  This process will take a few moments.<br><br>Would you like to continue?"
					Docova.Utils.messageBox({
						prompt: msg,
						title: "Create a new library.",
						width: 400,
						icontype : 2,
						msgboxtype : 4,
						onYes: function() {
							Docova.Utils.showProgressMessage("Creating new library.  One moment please...");
							document.forms[0].submit(); //regular form submit	
						},
						onNo: function() { 
							return false;
						}
    					});							
				}else{
					document.forms[0].submit(); //regular form submit	
				}
			}
		}
	 }
}

/*function SaveBeforeClosing(noCancel)
{

	if(!docInfo.isDocBeingEdited) {return 2;}
	var boxType = (noCancel)? 36 : 35;
	//return thingFactory.MessageBox( "Would you like to save the changes to this document?" ,boxType, "Closing Document" );
	return confirm("Would you like to save the changes to this document?");
}*/

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

function processOnUnload(){
	return false;
}

//---------------------------- create new document ----------------------------
function ViewCreateDocument(formname, parentlink)
{
	docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + formname + "?OpenForm";
	if(parentlink){
		docUrl = docUrl + "&ParentUNID=" + docInfo.DocID
	}
	ViewLoadDocument(docUrl);
}

function ViewLoadDocument(docUrl)
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
		docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + objView.currentEntry + "?OpenDocument&ParentUNID=" + docInfo.DocID;
	}
	if(!docUrl) {return alert("Document Url cannot be located.");}
	
	window.parent.fraContentBottom.location.href=docUrl ;
	window.parent.fsContentFrameset.rows = "25,*";
}