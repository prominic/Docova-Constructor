var advCommentsView = null; //embedded view object

function InitAdvComments() {
	advCommentsView = new EmbViewObject;
	advCommentsView.embViewID = "divAdvComments";	
	advCommentsView.captureEventDiv = "divAdvCommentsCapture";
	advCommentsView.perspectiveID = "xmlAdvComments";
	advCommentsView.srcView = "ludocumentsbyparent";
	advCommentsView.suffix = "C";
	advCommentsView.idPrefix = "AdvComm";
	advCommentsView.imgPath = docInfo.ImagesPath;
	advCommentsView.EmbViewInitialize();
}

//===================================================================================
function DeleteComment(){

	if(!advCommentsView.objEmbView.hasData){return false;}
	if (advCommentsView.objEmbView.currentEntry == "" && advCommentsView.objEmbView.selectedEntries.length==0 ) {
		window.top.Docova.Utils.messageBox({
			prompt: "Please select a comment to delete and try again.",
			icontype: 1,
			msgboxtype: 0, 
			title: "Nothing selected.",
			width: 300
		})
		return false;
	} //nothing selected
		
	var unidArray = new Array();
	(advCommentsView.objEmbView.selectedEntries.length==0) ? unidArray[0] = advCommentsView.objEmbView.currentEntry : unidArray = advCommentsView.objEmbView.selectedEntries;

	var request = "";
	
	//----- Build the requests and update the documents -----
	for(var i=0; i<unidArray.length; i++){
		request += "<Unid>" + unidArray[i] + "</Unid>"
		request += "<SrcType>Related</SrcType>"
	}
	window.top.Docova.Utils.messageBox({
		prompt: "You are about to delete a comment.  Are you sure?",
		title: "Delete Comment?",
		width: 400,
		icontype : 2,
		msgboxtype : 4,
		onYes: function() {
			DeleteCommentRequest(request)
		},
		onNo: function() { 
			return false;
		}
	});	
}

function DeleteCommentRequest(docRequest){
	var request = "<Request><Action>REMOVECOMMENT</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	request += docRequest
	request += "</Request>"
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
	jQuery.ajax({
		type: "POST",
		url: processUrl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = jQuery(xml);
			var statustext = jQuery(xmlobj).find("Result").first().text()
			if(statustext == "OK"){
				if (advCommentsView.objEmbView.selectedEntries.length!=0) {
					advCommentsView.objEmbView.selectedEntries = new Array();
				} else {
					advCommentsView.objEmbView.currentEntry=""; //selected/current are deleted now
				}
				advCommentsView.objEmbView.Refresh(true,false,true); //reload xml data with current xsl
				advCommentsView.EmbViewReload();
				alert("Comment successfully deleted.");
			}
		},
		error: function(){
			alert("Error.  Comment could not be deleted. Please check error logs.")
		}
	})
}

// ----------- log comments and workflow/lifecycle comments (if option is selected) -----------
function LogAdvancedComment()
{
	dlgParams.length = 0;
	dlgParams[0] = document.getElementById("AutoNotify").value
	dlgParams[1] = document.getElementById("AutoNotifyRecip").value
	
	var dlgUrl ="/" + NsfName + "/" + "dlgAdvComment?OpenForm";	
	var dlgGetAdvComment = window.top.Docova.Utils.createDialog({
			id: "divDlgGetAdvComment", 
			url: dlgUrl,
			title: "Comment",
			height: 490,
			width: 440, 
			useiframe: true,
			sourcewindow: window,
			sourcedocument: document,
			buttons: {
       			"Save": function() {
					var dlgDoc = window.top.jQuery("#divDlgGetAdvCommentIFrame")[0].contentWindow.document
					var sendto = jQuery.trim(jQuery("#SendTo", dlgDoc).val());
					var subject = jQuery.trim(jQuery("#Subject", dlgDoc).val());
					var body = jQuery.trim(jQuery("#Body", dlgDoc).val());
					var comment = jQuery.trim(jQuery("#Comment", dlgDoc).val());
					var $an = jQuery("#AutoNotify", dlgDoc);
					var notifyauth = ($an.is(":checked") ? $an.val() : "");
										
					//---Set global retValues array to pass to process function
					retValues.length = 0 //reset array for this dialog
					retValues[0] = sendto
					retValues[1] = subject
					retValues[2] = body
					retValues[3] = comment
					retValues[4] = notifyauth
				//--- Ensure comment is not empty.
				if(comment == ""){
					window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please provide a comment.",
							width: 300,
							icontype : 1,
							msgboxtype : 0,
					})
					return false;
				}					
				//--- If sendto is empty 
				if(sendto == "" && subject != ""){
					window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please provide the Recipient names for notification.",
							width: 300,
							icontype : 1,
							msgboxtype : 0,
					})
					return false;
				}
				//--- If subject is emptry
				if(subject == "" && sendto != ""){
					window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter the subject",
							width: 300,
							icontype : 1,
							msgboxtype : 0,
					})
					return false;
				}
				//if validation passed process the comment and notification
					var action = "LOGCOMMENT";
					var request = "<UserComment><![CDATA[" + encodeURI(comment) +  "]]></UserComment>";
					request += "<CommentType></CommentType>";
					ProcessComment(action, request);
					dlgGetAdvComment.closeDialog();
        			}, //OK
        			"Cancel": function() {
					dlgGetAdvComment.closeDialog();
        			} //Cancel
        		}
		})	
}

function ProcessComment(action, additionalHeader){
	var request="";

	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += (additionalHeader)? additionalHeader : "";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "<DocKey>" + docInfo.DocKey + "</DocKey>"
	request += "</Request>";

	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = jQuery(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				advCommentsView.objEmbView.Refresh(true,true,true);
				advCommentsView.EmbViewReload();		
				if( $.trim(retValues[0]) != "" || $.trim(retValues[4]) != "" ){
					SendCommentNotification();
				}else{
					// alert("Your comment was posted.")
				}
			}
		},
		error: function(){
			alert("Error.  Comment could not be posted.  Please see error logs.")
		}
	})
}

function SendCommentNotification(){
	var folderPath = docInfo.ServerUrl + docInfo.PortalWebPath + "/HomeFrame?ReadForm&goto=" + docInfo.LibraryKey + "," + docInfo.FolderID;
	var request="<Request>";
	request += "<Action>SENDLINKMSG</Action>";
	request += "<SendTo><![CDATA[" + retValues[0] +  "]]></SendTo>";
	request += "<Subject><![CDATA[" + retValues[1] +  "]]></Subject>";
	request += "<Body><![CDATA[" + retValues[2] +  "]]></Body>";
	request += "<Comment><![CDATA[" + retValues[3] +  "]]></Comment>";
	request += "<UserName><![CDATA[" + docInfo.UserName +  "]]></UserName>";	
	request += "<FolderName><![CDATA[" + docInfo.FolderName +  "]]></FolderName>";
	request += "<FolderPath><![CDATA["  + folderPath +  "]]></FolderPath>";
	request += "<Unid>" + docInfo.DocID +  "</Unid>";
	request += "<AutoNotify>" + retValues[4]  +  "</AutoNotify>";
	request += "<DocumentTypeKey>" + docInfo.DocumentTypeKey +  "</DocumentTypeKey>";
	request += "</Request>"

	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/MessagingServices?OpenAgent"
	jQuery.ajax({
		type: "POST",
		url: url,
		data: encodeURI(request),
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			// alert("Comment posted and Notification message sent.")
		},
		error: function(){
			alert("Error.  Comment could not be posted.  Please see error logs.")
		}
	})
}
