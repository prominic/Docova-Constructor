function SetVersionRowColor(obj, mode){

	var rs = doc.xmlDataVersionLog.recordset;
	var recNo=obj.recordNumber;
	rs.AbsolutePosition = recNo;
	//-------------------------------------------------------
	var docKey= rs.Fields("ParentDocKey").Value;
	if(docKey==docInfo.DocKey){return false;} //current document
	
	if (mode == true){
		obj.runtimeStyle.backgroundColor='#dfefff';
		obj.title = "Click to open the document";
	}else{
		obj.runtimeStyle.backgroundColor='';
		obj.title = "";
	}	
}

function OpenVersionDocument(clickObj)
{
	var rs = doc.xmlDataVersionLog.recordset;
	var recNo=clickObj.recordNumber;
	rs.AbsolutePosition = recNo;
	//-------------------------------------------------------
	var docTypeKey = docInfo.DocumentTypeKey;
	var docKey= rs.Fields("ParentDocKey").Value;
	var docLocation = rs.Fields("Location").Value;
	if(docKey==docInfo.DocKey){return false;} //current document
	
	//-----------------------------------------------------------
	//check current user access
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/AccessServices?OpenAgent"
	var request="";
	
	request += "<Request>";
	request += "<Action>QUERYACCESS</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + rs.Fields("ParentDocID").Value + "</Unid>";
	request += "<Location>" + docLocation + "</Location>";
	request += "<DocKey>" + docKey + "</DocKey>";
	request += "</Request>";
	var httpObj = new objHTTP();
	
	var accessLevel = 0;
	
	if(httpObj.PostData(request, url)){
		if(httpObj.status=="OK" && httpObj.resultCount > 0){
			accessLevel = httpObj.results[0];
		}
	}
	
	if(accessLevel == 0) {
		thingFactory.MessageBox(prmptMessages.msgV001, 16, prmptMessages.msgV002);
		return false;
	}
	//---------------------------------------------------------
	
	if(! rs.Fields("IsAvailable").Value){
		alert(prmptMessages.msgV003);
	}
	//-------------------------------------------------------
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + rs.Fields("ParentDocID").Value + "?ParentUNID=" + docInfo.FolderID + "&mode=preview";
	dlgUrl += "&typekey=" + docTypeKey + "&datadocsrc=" + docLocation + "&datadoc=" + docKey;

	//-------------------------------------------------------
	var leftPosition = (screen.width) ? (screen.width-700)/2 : 20;
	var topPosition = (screen.height) ? (screen.height-500)/2 : 20;
	dlgSize = "height=500,width=700,top=" + topPosition+ ",left=" + leftPosition;
	var dlgSettings = dlgSize + ",status=no,toolbar=no,menubar=no,location=no,scrollbars=yes,resizable=yes";
	return window.open(dlgUrl,'',dlgSettings); //Display the document window
}