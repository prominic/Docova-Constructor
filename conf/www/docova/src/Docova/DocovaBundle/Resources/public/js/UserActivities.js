function RefreshActivityData(){
	ActivityResponseData.reload();
	if (DocActivityData === null) {
		dataIslandInit('activity');
	}
	else {
		DocActivityData.reload();
	}
}

function SetDocActivityRowColor(rowObj, mode){
	if(mode == true){
		$(rowObj).css("background-color", "#dfefff");
		$(rowObj).prop("title", "Click to open the Activity.");
	}else{
		$(rowObj).css("background-color", "");
		$(rowObj).prop("title", "");
	}
}

function ViewDocActivity(recNo) {
	recNo = recNo-1; //need to subtract 1 from recNo if using tbody in dataisland as thead is recNo 0 and rs represents only tbody rows
	var rs = DocActivityData.recordset;
	rs.AbsolutePosition(recNo);
	var activityUnid= rs.Fields("Unid").getValue();
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/openActivity/" + activityUnid + "?OpenDocument";
	var dlgDocActivity = window.top.Docova.Utils.createDialog({
		id: "divDlgActivityResponse", 
		url: dlgUrl,
		title: "Activity Response",
		height: 450,
		width: 440, 
		useiframe: true,
		buttons: {
       		"OK": function() {
				dlgDocActivity.closeDialog();
     		}
     	}
	});
}

function ActivitiesLoaded(){
	var xobj = DocActivityData.XMLDocument;
	var nodes = $(xobj).find("Document");
	if(!nodes || !nodes.length){
		$("#NoDocActivityDataMsg").css("display", "");
		$("#tblDocActivityData").css("display", "none");
	}else{
		$("#NoDocActivityDataMsg").css("display", "none");
		$("#tblDocActivityData").css("display", "");	
	}
}


function checkActivities() {
	var msg = "You have created Activities for this Document.<br>If you exit without saving, the Activities will be discarded.<br><br>Continue?";
	window.top.Docova.Utils.messageBox({
		prompt: msg,
		title: "Discard Activities",
		width: 400,
		icontype : 2,
		msgboxtype : 4,
		onYes: function() {
			$("#tmpDiscardActivities").val("1");
			var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent";
			var request="";
			request += "<Request>";
			request += "<Action>REMOVEORPHANACTIVITY</Action>";
			request += "<DocKey>" + docInfo.DocKey + "</DocKey>";
			request += "</Request>";

			jQuery.ajax({
				type: "POST",
				url: url,
				data: request,
				cache: false,
				asynce: false,
				dataType: "xml",
				success: function(){
					CloseDocument(false);
				}
			});
		},
		onNo: function() { 
			$( this ).dialog( "close" );
		}
    });
}

function updateActivityCount() {
	ActivityResponseData.ondatasetcomplete = function() {
		var xobj = ActivityResponseData.XMLDocument;
		var nodes = xobj.selectSingleNode("documents/Document");
		if (nodes == null) {
			displayActivityPrompt('none');
			displayResponseActivities(true, 'none');		
		}
		else {
			var actCount = xobj.getElementsByTagName('Document').length;
			$("#btnShowActivities").button("option", "label", actCount );
			displayActivityPrompt('');
			displayResponseActivities(true, 'none');
		}		
	};
	ActivityResponseData.process();
}

function displayActivityPrompt(state) {
	var obj = document.getElementById("btnShowActivities");
	$(obj).css("display", state);
}

function displayResponseActivities(forceState, state) {
	obj = document.getElementById("ActivityResponseData");
	
	if(forceState && state != "") { 
		$(obj).css("display", state);
		return;
	}
	
	if($(obj).css("display") == "none") {
		$(obj).css("display", "");
	} else {
		$(obj).css("display", "none");
	}
}

function SetActivityResponseRowColor(rowObj, mode){
	if (mode == true){
		$(rowObj).css("background-color", "#dfefff");
		$(rowObj).prop("title", "Click to open the Activity.");
	}else{
		$(rowObj).css("background-color", "");
		$(rowObj).prop("title", "");
	}	
}

//-------------- API Hook:  onCreateActivity ---------------------------------------------------------
var customFields = "";
function onCreateActivity(){
	try{
			//customCreateActivity() is a custom function created by a developer on a custom subform
			//if additional data is to be stored on the Activity document, the function must set the customFields variable to an XML string in the following format
			// customFields = "<customFieldList><customField><fieldName></fieldName><fieldValue></fieldValue></customField><customField><fieldName>...etc ... </customFieldList>"
			//eg: customFields = "<customFieldList><customField><fieldName>field1</fieldName><fieldValue>A</fieldValue></customField><customField><fieldName>field2 ...etc ... </customFieldList>"
			//if fieldValue is multivalue, use ';' as a separator.  Be sure to wrap the field value in CDATA tags as appropriate
			//alternatively, customCreateActivity can call it's own custom server processing agent and return false to stop CreateActivity from running.
			if (typeof(customCreateActivity) === "function") { 
	   			if(! customCreateActivity()){ return false;}
			}
		} catch(e) {
			alert('Activity on Create function could not be executed due to the following error: \r' + e); 
			return false;
		}
		return true;
}

// ---------- Activity dialog --------
function CreateActivity() {

	if (docInfo.isNewDoc) {
		window.top.Docova.Utils.messageBox({
			title: prmptMessages.msgUA002,
			prompt: prmptMessages.msgUA001,
			width: 300,
			icontype : 1,
			msgboxtype : 0,
		});
		return false;
	}
	//API hook
	if(!onCreateActivity()) {
		return false;
	}
	
	var dlgUrl ="/" + NsfName + "/" + "dlgActivity?ParentUNID=" + docInfo.DocID;
	dlgParams.length = 0;
	dlgParams[0] = docInfo.UserNameAB;
	var dlgActivity = window.top.Docova.Utils.createDialog({
		id: "divDlgActivity", 
		url: dlgUrl,
		title: "Create Activity",
		height: 500,
		width: 420, 
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
      		"OK": function() {
				//var dlgDoc = window.top.$("#divDlgActivityIFrame")[0].contentWindow.document;
				var dlgWin = window.top.$("#divDlgActivityIFrame")[0].contentWindow;
				//var curAction = $("input[name=CurVersionAction]:checked", dlgDoc).val();
				var parentdockey = docInfo.DocKey;
				var sendto = dlgWin.Docova.Utils.getField("SendTo");
				var subject = dlgWin.Docova.Utils.getField("Subject");
				var body = dlgWin.Docova.Utils.getField("Body");
				var activitytype = dlgWin.Docova.Utils.getField("ActivityType");
				var activityobligation = dlgWin.Docova.Utils.getField("ActivityObligation");
				var activitysendmessage = dlgWin.Docova.Utils.getField("ActivitySendMessage");
				var activitydocumentowner = dlgWin.Docova.Utils.getField("ActivityDocumentOwner");
				//--- If activity type is not selected 
				if(activitytype == "-Select-"){
					window.top.Docova.Utils.messageBox({
							title: "No Action selected.",
							prompt: "You must choose an Action.",
							width: 300,
							icontype : 1,
							msgboxtype : 0,
					});
					return false;
				}
				//--- If recipient is not document owner and/or sendto list
				if(activitydocumentowner == "" && sendto == ""){
					window.top.Docova.Utils.messageBox({
							title: "No Recipients selected.",
							prompt: "You must assign this Action to the Document Owner or select one or more people for the Recipient List.",
							width: 400,
							icontype : 1,
							msgboxtype : 0,
					});
					return false;
				}
				//--- If subject for activity email is blank.
				if(subject == ""){
					window.top.Docova.Utils.messageBox({
							title: "No Activity Subject provided.",
							prompt: "Please fill in the Activity Subject.",
							width: 300,
							icontype : 1,
							msgboxtype : 0,
					});
					return false;
				}
				//--- If body of activity email is blank.
				if(body == ""){
					window.top.Docova.Utils.messageBox({
							title: "Missing Activity Message/Instruction.",
							prompt: "Please provide an Activity Message/Instruction.",
							width: 300,
							icontype : 1,
							msgboxtype : 0,
					});
					return false;
				}
				//--- If all ok, generate request
				var request="";
				request += "<Request>";
				request += "<Action>CREATEACTIVITY</Action>";
				request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
				request += "<Unid>" + docInfo.DocID + "</Unid>";
				request += "<LibraryKey>" + docInfo.LibraryKey + "</LibraryKey>";
				request += "<FolderID>" + docInfo.FolderID + "</FolderID>";
				request += "<parentdockey>" + parentdockey + "</parentdockey>";
				request += "<sendto><![CDATA[" + sendto + "]]></sendto>";
				request += "<subject><![CDATA[" + subject + "]]></subject>";
				request += "<body><![CDATA[" + body + "]]></body>";
				request += "<activitytype>" + activitytype + "</activitytype>";
				request += "<activityobligation>" + activityobligation + "</activityobligation>";
				request += "<activitysendmessage>" + activitysendmessage + "</activitysendmessage>";
				request += "<activitydocumentowner><![CDATA[" + activitydocumentowner + "]]></activitydocumentowner>";
				request += customFields;
				request += "</Request>";
				
				DoCreateActivity(request);
				dlgActivity.closeDialog();
       		},
       		"Cancel": function() {
				dlgActivity.closeDialog();
     		}
     	}
	})
}

function DoCreateActivity(request){
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
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
				if(docInfo.isNewDoc){
					$("#tmpActivity").val("1");
					$("#tmpDiscardActivities").val("0");
				}
				//refresh the activity data island
				updateActivityCount();
				RefreshActivityData();
				alert("Your Activity was successfully created.");
			}
		},
		error: function(){
			window.top.Docova.Utils.messageBox({
					title: "Error Creating Activity",
					prompt: "There was an error creating the Activity.  Notifications have not been sent. An error has been logged.",
					width: 400,
					icontype : 1,
					msgboxtype : 0
			});
		}	
	});
}

function OpenActivityResponse(recNo){
	var rs = ActivityResponseData.recordset;
	rs.AbsolutePosition(recNo);
	var activityUnid = rs.Fields("Unid").getValue();
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/EditActivity/" + activityUnid;
	
	var dlgActivityResponse = window.top.Docova.Utils.createDialog({
		id: "divDlgActivityResponse", 
		url: dlgUrl,
		title: "Activity Response",
		height: 450,
		width: 440, 
		useiframe: true,
		buttons: {
      		"Done": function() {
				var dlgDoc = window.top.$("#divDlgActivityResponseIFrame")[0].contentWindow.document;
				var dlgWin = window.top.$("#divDlgActivityResponseIFrame")[0].contentWindow;
				var activityDocID = dlgWin.docInfo.DocID;
				if($("#ActivityAcknowledged", dlgDoc).is(":checked")){
					var activityacknowledged = 1;
				}else{
					var activityacknowledged = 0;
				}
				var activityresponse = $.trim(dlgWin.Docova.Utils.getField("ActivityResponse"));
				var activityobligation = dlgWin.Docova.Utils.getField("ActivityObligation");
				
				//-----If ack is needed and resp is not needed, user must provide acknowledgement-----
				if(activityobligation == "1") {
					if(activityacknowledged == 0){
						window.top.Docova.Utils.messageBox({
								title: "Acknowledgement Required",
								prompt: "This Activity requires your Acknowledgement.  Please check the Acknowledgement checkbox.",
								width: 400,
								icontype : 1,
								msgboxtype : 0
						});					
					return false;
					}
				}

				//-----If resp is needed but ack is not needed, user must provide response text ----
				if(activityobligation == "2") {
					if(activityresponse == ""){
						window.top.Docova.Utils.messageBox({
								title: "Response/Answer Required",
								prompt: "This Activity requires your Response.  Please provide a Response/Answer for this Activity.",
								width: 400,
								icontype : 1,
								msgboxtype : 0
						});					
						return false;
					}
				}
				
				//--- Update the activity---
				var action = "UPDATEACTIVITY";
				var request="";

				request += "<Request>";
				request += "<Action>" + action + "</Action>";
				request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
				request += "<Unid>" + docInfo.DocID + "</Unid>";
				request += "<SystemRecordsNsfName>" + docInfo.SystemRecordsNsfName + "</SystemRecordsNsfName>";
				request += "<activityDocID>" + activityDocID + "</activityDocID>";
				request += "<activityacknowledged>" + activityacknowledged + "</activityacknowledged>";
				request += "<activityresponse><![CDATA[" + activityresponse + "]]></activityresponse>";
				request += "</Request>";
				
				DoUpdateActivity(request);
				dlgActivityResponse.closeDialog();
       		},
       		"Cancel": function() {
				dlgActivityResponse.closeDialog();
     		}
     	}
	})
return	
}

function DoUpdateActivity(request){
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				updateActivityCount();	
				RefreshActivityData();
				alert("Your Activity was successfully updated.");
			}
		},
		error: function(){
			alert("Activity was not completed. An error has been logged.");
		}
	});
	return false;	
}



/*
function SetRowColor(obj, mode)
{
	if (mode == true){
		obj.runtimeStyle.backgroundColor='#dfefff';
		obj.title = "Click to open the document";
	}else{
		obj.runtimeStyle.backgroundColor='';
		obj.title = "";
	}	
}

//-------------- API Hook:  onCreateActivity ---------------------------------------------------------
var customFields = "";
function onCreateActivity(){
	try{
			//customCreateActivity() is a custom function created by a developer on a custom subform
			//if additional data is to be stored on the Activity document, the function must set the customFields variable to an XML string in the following format
			// customFields = "<customFieldList><customField><fieldName></fieldName><fieldValue></fieldValue></customField><customField><fieldName>...etc ... </customFieldList>"
			//eg: customFields = "<customFieldList><customField><fieldName>field1</fieldName><fieldValue>A</fieldValue></customField><customField><fieldName>field2 ...etc ... </customFieldList>"
			//if fieldValue is multivalue, use ';' as a separator.  Be sure to wrap the field value in CDATA tags as appropriate
			//alternatively, customCreateActivity can call it's own custom server processing agent and return false to stop CreateActivity from running.
			if (typeof(customCreateActivity) === "function") { 
	   			if(! customCreateActivity()){ return false;}
			}
		} catch(e) {
			alert('Activity on Create function could not be executed due to the following error: \r' + e); 
			return false;
		}
		return true;
}

// ---------- Activity dialog --------
function CreateActivity()
{
	if (docInfo.isNewDoc) {
		thingFactory.MessageBox(prmptMessages.msgUA001, 0 + 16, prmptMessages.msgUA002);
		return false;
	}
	
	var dlgUrl ="/" + NsfName + "/" + "dlgActivity?OpenForm&ParentUNID=" + docInfo.DocID;
	var dlgSettings = "dialogHeight:400px;dialogWidth:420px;center:yes; help:no; resizable:no; status:no;";
	var params = [window, docInfo.DocKey, docInfo.UserNameAB];
	var retValues = window.showModalDialog(dlgUrl,params,dlgSettings); //Display the address dialog

	if( !retValues ) {return false; }

	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
	var request="";

	request += "<Request>";
	request += "<Action>CREATEACTIVITY</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "<LibraryKey>" + docInfo.LibraryKey + "</LibraryKey>";
	request += "<FolderID>" + docInfo.FolderID + "</FolderID>";
	request += "<parentdockey>" + retValues[0] + "</parentdockey>";
	request += "<sendto><![CDATA[" + retValues[1] + "]]></sendto>";
	request += "<subject><![CDATA[" + retValues[2] + "]]></subject>";
	request += "<body><![CDATA[" + retValues[3] + "]]></body>";
	request += "<activitytype>" + retValues[4] + "</activitytype>";
	request += "<activityobligation>" + retValues[5] + "</activityobligation>";
	request += "<activitysendmessage>" + retValues[6] + "</activitysendmessage>";
	request += "<activitydocumentowner><![CDATA[" + retValues[7] + "]]></activitydocumentowner>";
	request += customFields;
	request += "</Request>";

	var httpObj = new objHTTP();
	
	if(httpObj.PostData(request, url))
	{
		if(httpObj.status=="OK")
		{
			thingFactory.MessageBox(prmptMessages.msgUA003, 0 + 48, prmptMessages.msgUA004);
			if (typeof(httpObj.Ret1) != 'undefined' && httpObj.Ret1 == "1") {
				activityCount++;
				doc.activityCounter.innerText = activityCount;
				doc.xmlActivities.src=doc.xmlActivities.src + "&" + (new Date()).valueOf();
				doc.divxmlActivities.style.display = "block";
				doc.activityCounter.style.display = "block";
				RefreshActivityData();
			}
		}
	}
	return false;
}

function OpenActivity(ObjActivity)
{
	var rs = doc.xmlActivities.recordset;
	var rsRows = rs.RecordCount;
	var recNo = ObjActivity.recordNumber;
	rs.AbsolutePosition = recNo;
	var activityObligation = rs.Fields("Obligation").Value;
	var activityUnid = rs.Fields("Unid").Value;
	var action;
	var dlgHeight = '';

	action = "UPDATEACTIVITY";
	
	if(activityObligation == "0") { dlgHeight = "350px;"; }
	if(activityObligation == "1") { dlgHeight = "380px;"; }
	if(activityObligation == "2") { dlgHeight = "420px;"; }

	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/EditActivity/" + activityUnid;
	var dlgSettings = "dialogHeight:" + dlgHeight + "dialogWidth:420px;center:yes; help:no; resizable:no; status:no;";
	var retValues = window.showModalDialog(dlgUrl,window,dlgSettings); //Display the address dialog

	if( !retValues ) {return false; }
	
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
	var request="";

	//---- If the activity did not require an acknowledgement nor response then it can be deleted, otherwise, it is updated -----

	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "<KeywordsNsfName>" + docInfo.KeywordsNsfName + "</KeywordsNsfName>";
	request += "<activityDocID>" + retValues[0] + "</activityDocID>";
	request += "<activityacknowledged>" + retValues[1] + "</activityacknowledged>";
	request += "<activityresponse><![CDATA[" + retValues[2] + "]]></activityresponse>";
	request += "</Request>";

	var httpObj = new objHTTP();
	
	if(httpObj.PostData(request, url))
	{
		if(httpObj.status=="OK")
		{
			thingFactory.MessageBox(prmptMessages.msgUA005, 16, prmptMessages.msgUA006);
			activityCount--;
			doc.activityCounter.innerText = activityCount;
			doc.xmlActivities.src=doc.xmlActivities.src + "&" + (new Date()).valueOf();
			if(rsRows == "1"){
				doc.divxmlActivities.style.display = "none";
				if (activityCount > 0) {
					doc.activityCounter.style.display = "block";
				}
			}
			RefreshActivityData();
		}
	}
		
	return false;	
}

var topOffset=50;
function slideActivities()
{
	var Dif = parseInt((document.body.scrollTop+topOffset-document.all.divxmlActivities.offsetTop)*.1);
	document.all.divxmlActivities.style.pixelTop+=Dif;
}
*/