var relLinksView = null; //embedded view object

$(document).ready(function(){
	$("#btnCreateRelLink").button({
		icons: { primary: "ui-icon-link" },
		text: false
		}).click(function(event){
    		AddRelLinks();
    		event.preventDefault();
	});
	
	$("#btnDeleteRelLink").button({
		icons: { primary: "ui-icon-circle-close" },
   		text: false
    	}).click(function(event){
    		DeleteRelLink();
    		event.preventDefault();
	});
});

function InitRelLinks() {
	relLinksView = new EmbViewObject;
	relLinksView.embViewID = "divRelatedLinks";	
	relLinksView.captureEventDiv = "divRelatedLinksCapture";
	relLinksView.perspectiveID = "xmlRelatedLinks";
	relLinksView.srcView = "ludocumentsbyparent";
	relLinksView.suffix = "L";
	relLinksView.idPrefix = "RelLink";
	relLinksView.imgPath = docInfo.ImagesPath + '/bundles/docova/images/';
	relLinksView.EmbViewInitialize();
}

function AddRelLinks(){
	var dlgUrl=docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgGetRelatedLink?OpenForm"
	var dlgRelLinks = window.top.Docova.Utils.createDialog({
		id: "divDlgRelatedLink", 
		url: dlgUrl,
		title: "Related Link",
		height: 400,
		width: 400, 
		useiframe: true,
		sourcedocument : document,		
		sourcewindow : window,
		buttons: {
			"OK": function(){
				var dlgDoc = window.top.$("#divDlgRelatedLinkIFrame")[0].contentWindow.document;
				var linktext = $("#linkURL", dlgDoc).val();
				var linkdesc = $("#linkDescription", dlgDoc).val();
				processData(linktext, linkdesc);
				dlgRelLinks.closeDialog();
			},
        		"Close": function() {
				//$( this ).dialog( "close" );
				dlgRelLinks.closeDialog();
			}	
      		}
	});
}

function processData(data, linkdesc){
	if(!data){return false;}

	if(data.indexOf("<NDL>") > -1){
		//pasted Notes Doclink
		var arr = data.split("\n")
		var index = 0;
		var nsf = "";
		var nDoc = "";
		var nDomain = "";
		var nTitle = "";
		
		index = getArrayIndex(arr, '<REPLICA');
		if(index > -1) { nsf = "__" + replaceSubstring(leftBack(rightBack(arr[index], "REPLICA "), ">"), ":", "");}

		index = getArrayIndex(arr, '<NOTE');
		if(index > -1) { nDoc = replaceSubstring(replaceSubstring(leftBack(rightBack(arr[index], "NOTE OF"), ">"), "-ON", ""), ":", "");}
		
		index = getArrayIndex(arr, '<HINT>');
		if(index > -1) { 
			var server = leftBack(rightBack(arr[index],"<HINT>CN="), "</HINT>");
			var tmp = server.split("/")
			server = tmp[0] + tmp[tmp.length-1]
			nDomain = replaceSubstring(server, "O=", "@");
		}
		
		index = getArrayIndex(arr, '<REM>');
		if(index > -1) { nTitle = leftBack(rightBack(arr[index], "<REM>"), "</REM>");}
		if(linkdesc != ""){ nTitle = linkdesc; }  //use description as title if one was entered.
		var nParams = ["DocLink", "notes://" + nDomain + "/" + nsf + "/0/" + nDoc + "?opendocument", nTitle];
	}
	else if((data.indexOf("http://") > -1) || (data.indexOf("https://") > -1)) {
		if(linkdesc != ""){ 
			nTitle = linkdesc;   //use description as title if one was entered.
		}else{
			nTitle = data.match(/^(?:https?:)?(?:\/\/)?([^\/\?]+)/)[1];
		}
		var nParams = ["URL", data, nTitle];
	}
	else if(data.toLowerCase().indexOf("notes://") > -1) { 
		//dropped Notes doc
		var tmp = data.split('\r');
		data = tmp[0];
		var nTitle = "Notes Document Link";
		if(tmp.length > 1) { 
			var alt = replaceSubstring(tmp[1], "\n", "")
			alt = replaceSubstring(alt, "\r", "")
			if(alt != ""){nTitle = alt}
		}
		var nParams = ["DocLink", data, nTitle];
	}
	else {
		alert(prmptMessages.msgRL002);
	}

	linkParams = nParams;
	if(linkParams) {
		var requestData = "<LinkType>" + linkParams[0] + "</LinkType><LinkURL><![CDATA[" + linkParams[1] + "]]></LinkURL><LinkDesc><![CDATA[" + linkParams[2] + "]]></LinkDesc>"	
		ProcessAddURLLinkRequest(requestData);
	}
}

function getArrayIndex(arr, searchString){
	for(x=0; x < arr.length; x++) {
		if(arr[x].indexOf(searchString) > -1){
			return x;
		}
	}
	return -1;
}

function ProcessAddURLLinkRequest(requestData){
	if(!requestData) {return false;}

	var request="";
	request += "<Request>";
	request += "<Action>ADDURLLINK</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<ParentDocKey>" + docInfo.DocKey + "</ParentDocKey>";
	request += "<ParentDocID>" + docInfo.DocID + "</ParentDocID>";	
	request += "<isNewDoc>" + docInfo.isNewDoc + "</isNewDoc>";
	request +=	requestData
	request += "</Request>";	
	
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
	Docova.Utils.showProgressMessage(prmptMessages.msgCF020);
	jQuery.ajax({
		type: "POST",
		url: processUrl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				Docova.Utils.hideProgressMessage();
				relLinksView.objEmbView.Refresh(true,true,true);
				relLinksView.EmbViewReload();
				if (docInfo.isNewDoc){
					//Track any newly created links for cleanup if the user chooses to not save
					//TrackTmpOrphans("ADD", requestData); //Fix.
				}
			}
		},
		error: function(){
			Docova.Utils.hideProgressMessage();
			alert("Error:  Could not create the link.  Please try again or contact your Adminstrator.")
			return false;
		}
	})
}

function ConfirmLinkWithUser(params){
	var dlgParams = params;
	var dlgUrl =docInfo.ServerUrl + '/' + docInfo.LogNsfName + "/dlgAddNotesOrHttpLink?OpenForm";
	var dlgSettings = "dialogHeight:160px;dialogWidth:670px;center:yes; help:no; resizable:yes; status:no;";
	return window.showModalDialog(dlgUrl,dlgParams,dlgSettings);
}

function DeleteRelLink(){

	if(!relLinksView.objEmbView.hasData){return false;}
	if (relLinksView.objEmbView.currentEntry == "" && relLinksView.objEmbView.selectedEntries.length==0 ) {
		alert("Nothing selected.  You must select a link document to delete.")
		return false;
	} //nothing selected
		
	var msg = "You are about to delete a Related document link.<br>Are you sure?"
	Docova.Utils.messageBox({
		prompt: msg,
		title: "Delete related link?",
		width: 400,
		icontype : 2,
		msgboxtype : 4,
		onYes: function() {
			DoDeleteRelLink();
		},
		onNo: function() { 
			$( this ).dialog( "close" );
		}
    	});
}  
function DoDeleteRelLink(){
	var tmpArray = new Array();
	(relLinksView.objEmbView.selectedEntries.length==0) ? tmpArray[0] = relLinksView.objEmbView.currentEntry : tmpArray = relLinksView.objEmbView.selectedEntries;

	var request = "";
	var unidList = "";
	var parentDocIDList = "";
	var unidArray;
	var entry = "";
		
	for(var i=0; i < tmpArray.length ; i++) {
		entry = relLinksView.objEmbView.GetEntryById(tmpArray[i])
		if(unidList == ""){
			unidList += tmpArray[i];
			parentDocIDList += (entry) ? entry.GetElementValue("parentdocid") : "";
		}else{
			unidList += "," + tmpArray[i];
			parentDocIDList += ";" + (entry) ? entry.GetElementValue("parentdocid") : "";
		}
	}

	//---- If there is nothing selected, return, otherwise process the tasks
	if (unidList == ""){
		return;
	}else{
		unidArray = unidList.split(",")
	}

	//----- Build the requests and update the documents -----
	for(var i=0; i<unidArray.length; i++){
		request += "<Unid>" + unidArray[i] + "</Unid>"
		request += "<SrcType>Related</SrcType>"
	}

	if(DeleteRelLinkRequest(request)){

	//Refresh the related documents xml
	if (relLinksView.objEmbView.selectedEntries.length!=0) {
		relLinksView.objEmbView.selectedEntries = new Array();
	} else {
		relLinksView.objEmbView.currentEntry=""; //selected/current are deleted now
	}
	relLinksView.objEmbView.Refresh(true,false,true); //reload xml data with current xsl
	relLinksView.EmbViewReload();
		
	//Track any deleted links for the linked documents for cleanup if the user chooses to not save
	TrackTmpOrphans("DEL", unidList);

	//if parent doc has not been saved yet, also need to update tmpLinkDataXML
	//eg: create new doc, add link, remove link, then save parent
	UpdateRelLinkDataXML(parentDocIDList);
	}
}

function DeleteRelLinkRequest(docRequest){
	var result = false;
	var request = "<Request><Action>REMOVERELATED</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	request +=  "<ParentDocID>" + docInfo.DocID + "</ParentDocID>";	
	request +=  "<ParentDocKey>" + docInfo.DocKey + "</ParentDocKey>";	
	request += docRequest
	request += "</Request>"
	//send the request to server
	Docova.Utils.showProgressMessage(prmptMessages.msgCF020);
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent"
	jQuery.ajax({
		type: "POST",
		url: processUrl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var objxml = $(xml);
			var statustext = objxml.find("Result").first().text();
			if(statustext == "OK"){
				Docova.Utils.hideProgressMessage();
				result = true;
		}
		},
		error: function(){
			Docova.Utils.hideProgressMessage();
			alert("Error.  Could not delete related link.");
			result = false;
		}
	})
return result;	
}

function UpdateRelLinkDataXML(parentDocIDList){
	if(docInfo.isNewDoc) {
		relDocTmpXML = $("#tmpLinkDataXML").val();
		if(relDocTmpXML.indexOf("~") == -1) {relDocTmpXML + "~";}
		var arr = relDocTmpXML.split("~");

		if(parentDocIDList.indexOf("~") == -1) {parentDocIDList + "~";}
		var parArr = parentDocIDList.split(";");
		
		var newTmpXML = "";
		for(x=0; x < parArr.length; x++) {
			for(y=0; y < arr.length; y++) {
				if(arr[y].indexOf(parArr[x]) > 0) {
					arr[y] = DelUnidNode(arr[y], parArr[x]);
				}
			}
		}	
		$("#tmpLinkDataXML").val(arr.join("~"))
	}
}

function TrackTmpOrphans(action, request){
//-----Action parameter can be ADD or DEL-----

	//---- Add to the list -----
	if(action == "ADD"){
		var rdData = document.getElementById("RelatedLinks");
		var xmlDoc = rdData.XMLDocument;
		var nodeList = xmlDoc.selectNodes( 'documents/Document' ); 
	
		if(nodeList.length ==0){ return false;} 
	
		var txtNode; 
		var txtLibraryKey;
		var txtUnid;
		var txtLibraryKeyUnidList = "";
		var ctr = 0; 

		while( objNode = nodeList.nextNode()){ 
			var txtNode = objNode.firstChild; 
			txtLibraryKey = objNode.selectSingleNode( 'LibraryKey').text;
			txtUnid = objNode.selectSingleNode( 'Unid' ).text;
			AddTmpOrphan(txtLibraryKey, txtUnid)
			ctr++; 
		}
	}

	//---- Delete from the list -----
	if(action == "DEL"){
		var unidList = request
		var unidArray;
		var i;
		
		if (unidList == ""){
			return;
		}else{
			unidArray = unidList.split(",")
		}

		for(var i=0; i<unidArray.length; i++){
			DelTmpOrphan(unidArray[i])
		}		
	}
	
}