var relEmailsView = null; //embedded view object

$(document).ready(function(){
	$("#btnAcquireMessages").button({
		icons: { primary: "ui-icon-mail-closed" },
		label: "Acquire message(s).",
    		text: false
    	}).click(function(event){
    		ImportMessages();
    		event.preventDefault();
	});
	$("#btnDeleteMessages").button({
		icons: { primary: "ui-icon-circle-close" },
		label: "Remove selected message link(s).",
    		text: false
    	}).click(function(event){
    		DeleteMemo();
    		event.preventDefault();
	})	;
});

function InitRelEmails() {
	relEmailsView = new EmbViewObject;
	relEmailsView.embViewID = "divRelatedEmails";	
	relEmailsView.captureEventDiv = "divRelatedEmailsCapture";
	relEmailsView.perspectiveID = "xmlRelatedEmails";
	relEmailsView.srcView = "ludocumentsbyparent";
	relEmailsView.suffix = "M";
	relEmailsView.idPrefix = "RelEmail";
	relEmailsView.imgPath = docInfo.ImagesPath;
	relEmailsView.EmbViewInitialize();
}



/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: ImportMessages
 * Imports selected email messages as related correspondence
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function ImportMessages()
{
	var dlgUrl =docInfo.MailAcquireMessagesDialogUrl + '?associatemail=true';
	if (dlgUrl == ''){
			window.top.Docova.Utils.messageBox({
				title: "Import Messages Not Available",
				prompt: "Import Messages is not available for your current mail configuration.",
				icontype: 4,
				msgboxtype: 0
			});
			return false;
	}

	if(docInfo.UserMailSystem == "O" && !window.top.Docova.IsPluginAlive){
			window.top.Docova.Utils.messageBox({
				title: "DOCOVA Plugin Not Running",
				prompt: "The DOCOVA Plugin is required for the importing of messages from Outlook.",
				width: 400,
				icontype: 4,
				msgboxtype: 0
			});
			return false;	
	}	



	var dlgmail = window.top.Docova.Utils.createDialog({
		id: "divDlgAcquireMessages", 
		url: dlgUrl,
		title: "Import Mail Messages",
		height: 600,
		width: 800, 
		useiframe: true,
		sourcedocument : document,		
		sourcewindow : window,
		buttons: {
        			"Close": function() {
	        			var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.GetImportCount();      
						if(returnValue){
							relEmailsView.objEmbView.Refresh(true,true,true);
							relEmailsView.EmbViewReload();						
	        			}	  			
		   				dlgmail.closeDialog();
        			},
        			"Import Selected": function() {
        				if(docInfo.UserMailSystem == "O"){
		        			jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.CompleteDialog(function(returnValue){
		        				if(returnValue){
								relEmailsView.objEmbView.Refresh(true,true,true);
								relEmailsView.EmbViewReload();						
		        				}else{
		        					window.top.Docova.Utils.messageBox({
									prompt: "Please select one or more emails to import.",
									icontype: 3,
									msgboxtype: 0, 
									title: "Choose One or More Emails",
									width: 400
								});
							}        
						});				
					}else{ 
		        			var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.CompleteDialog();
		        			if(returnValue){
							relEmailsView.objEmbView.Refresh(true,true,true);
							relEmailsView.EmbViewReload();						
		        			}else{
		        				window.top.Docova.Utils.messageBox({
								prompt: "Please select one or more emails to import.",
								icontype: 3,
								msgboxtype: 0, 
								title: "Choose One or More Emails",
								width: 400
							});
						}
					}	        			
				}
      	}
	});		
}//--end ImportMessages


function OpenMemo(entry){
	var docUnid = entry.GetElementValue("docid");	
	var docMemoUrl =  "/" + NsfName + "/RelatedEmail/" + docUnid + "?OpenDocument";
	var title = entry.GetElementValue("subject");
	//since the folder of the doc being opened may not be open, pass the current doc ID as the 'frame ID' (docInfo.DocID).  When related doc is closed, focus returns to the parent
	if(window.parent.fraTabbedTable) {
		window.parent.fraTabbedTable.objTabBar.CreateTab(title, docUnid, "D", docMemoUrl, docInfo.DocID, false);	
	}  else {
		window.open(docMemoUrl);
	}
}


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: DeleteMemo
 * Deletes selected email message from related correspondence
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function DeleteMemo(){
	if(!relEmailsView.objEmbView.hasData){return false;}
	if (relEmailsView.objEmbView.currentEntry == "" && relEmailsView.objEmbView.selectedEntries.length==0 ) {return false;} //nothing selected
	window.top.Docova.Utils.messageBox({
		msgboxtype: 3, 
		prompt: "Do you wish to delete the highlighted email?", 
		title: "Delete Memo?", 
		icontype: 2, 
		width: 400,
		onYes: function(){
			var unidArray = new Array();
			(relEmailsView.objEmbView.selectedEntries.length==0) ? unidArray[0] = relEmailsView.objEmbView.currentEntry : unidArray = relEmailsView.objEmbView.selectedEntries;
	
			var request = "";

			//----- Build the requests and update the documents -----
			for(var i=0; i<unidArray.length; i++){
				request += "<Unid>" + unidArray[i] + "</Unid>";
				request += "<SrcType>Email</SrcType>";
			}

			DeleteMemoRequest(request);
	
			//---- Refresh the data -----
			relEmailsView.objEmbView.Refresh(true,true,true);
			relEmailsView.EmbViewReload();		
		}
	});

}//--end DeleteMemo

function DeleteMemoRequest(docRequest){
	var request = "<Request><Action>REMOVERELATED</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request +=  "<ParentDocID>" + docInfo.DocID + "</ParentDocID>";
	request +=  "<ParentDocKey>" + docInfo.DocKey + "</ParentDocKey>";
	request += docRequest;
	request += "</Request>";
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent";
	ShowProgressMessage("Processing request. Please wait...");
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, processUrl) || httpObj.status=="FAILED"){
		HideProgressMessage();
			return false;
		}
	HideProgressMessage();
}