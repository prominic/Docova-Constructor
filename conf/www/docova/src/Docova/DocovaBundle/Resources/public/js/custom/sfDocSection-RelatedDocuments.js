var relDocsView = null; //embedded view object

$(document).ready(function(){
	$("#btnCreateRelDoc").button({
		icons: { primary: "ui-icon-document" },
    		text: false
    	}).click(function(event){
    		AddLinks();
    		event.preventDefault();
	});
	$("#btnDeleteRelDoc").button({
		icons: { primary: "ui-icon-circle-close" },
    		text: false
    	}).click(function(event){
    		DeleteLink();
    		event.preventDefault();
	});
});

function InitRelDocs() {
	relDocsView = new EmbViewObject;
	relDocsView.embViewID = "divRelatedDocs";	
	relDocsView.captureEventDiv = "divRelatedDocsCapture";
	relDocsView.perspectiveID = "xmlRelatedDocs";
	relDocsView.imgPath = docInfo.ImagesPath;
	relDocsView.lookupURL = docInfo.ServerUrl + docInfo.PortalWebPath + "/RelDocLinks.xml?OpenView&RestricttoCategory=" + docInfo.DocKey;
	relDocsView.EmbViewInitialize();
}


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: AddLinks
 * Presents dialog for user to choose related documents, and then creates related document links based
 * on selections
 *------------------------------------------------------------------------------------------------------------------------------------------- */
 function AddLinks()
{

	var dlgUrl =docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgRelatedDocSelect?ReadForm";	
	var dlgreldoc = window.top.Docova.Utils.createDialog({
		id: "divDlgRelatedDocSelect", 
		url: dlgUrl,
		title: "Select Related Documents",
		height: 600,
		width: 800, 
		useiframe: true,
		sourcedocument : document,		
		buttons: {
        			"Close": function() {
		   				dlgreldoc.closeDialog();
        			},
        			"Create Links": function() {
	        			var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.GetSelectedIdList();
	        			if(returnValue){
	        				if (docInfo.isNewDoc) {
	        					var currentDocIds = relDocsView.objEmbView.oXml;
	        					jQuery(currentDocIds).find('docid').each(function() {
	        						returnValue = '<Unid>' + jQuery(this).text() + '</Unid>' + returnValue;	        						
	        					});
	        				}
	        				dlgreldoc.closeDialog();
	        			 	ProcessAddLinkRequest(returnValue); 
	        			}else{
	        				window.top.Docova.Utils.messageBox({
							prompt: "Please select one or more documents to link as related documents.",
							icontype: 3,
							msgboxtype: 0, 
							title: "Choose One or More Documents",
							width: 400
						});
					}
				}
      	}
	});	
}//--end AddLinks

function ProcessAddLinkRequest(requestData){

	if(!requestData) {return false;}
	
	var request="";
	request += "<Request>";
	request += "<Action>ADDRELATEDLINKS</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<ParentDocKey>" + docInfo.DocKey + "</ParentDocKey>";
	request += "<ParentDocID>" + docInfo.DocID + "</ParentDocID>";	
	request += "<isXLinkEnabled>" + docInfo.isXLinkEnabled + "</isXLinkEnabled>";
	request += "<isNewDoc>" + docInfo.isNewDoc + "</isNewDoc>";
	request +=	requestData;
	request += "</Request>";	

	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
	ShowProgressMessage(prmptMessages.msgCF020);
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, processUrl) || httpObj.status=="FAILED"){
		HideProgressMessage();
			return false;
		}
	HideProgressMessage();

	if (docInfo.isNewDoc) {
		var xmlnode = httpObj.xmlDocument.selectSingleNode('/Results/Result[@ID="Ret1"]/documents');
		if(xmlnode){
			var xmlText = new XMLSerializer().serializeToString(xmlnode);
			if(trim(xmlText) != ""){
				var parser = new DOMParser();
				var docXml = parser.parseFromString(xmlText, "text/xml");
		relDocsView.objEmbView.oXml = docXml;
		relDocsView.objEmbView.Refresh(false,false,false);
		relDocsView.EmbViewReload();
				
				docXml = null;
				parser = null;
			}
			xmlText = null;
		}
		xmlnode = null;
		
			//--- For new docs, if cross linking is enabled for this document type, track the XML for creating the cross links for processing when doc is saved
			var request="";
			request += "<Request>";
			request += "<Action>ADDRELATEDLINKS</Action>";
			request +=	requestData;
			request += "</Request>";	
		
		var tmplinkxmldata = jQuery.trim(jQuery("#tmpLinkDataXml").val());
		tmplinkxmldata += request;
		jQuery("#tmpLinkDataXml").val(tmplinkxmldata);
			}
	else {
		relDocsView.objEmbView.Refresh(true,true,true);
		relDocsView.EmbViewReload();
	}

}

function OpenRelatedDocument(entry){
	var targetDocId ="";
	var hasLaterVersion=false;
	if(entry.GetElementValue("lvparentdocid") != null && entry.GetElementValue("lvparentdocid") != ""){
		hasLaterVersion = true;
	}
	var openCurrent=true;
	var LibraryNsfName = "";
	var linkType = entry.GetElementValue("rectype");
	var docTypeKey = entry.GetElementValue("doctypekey");
	var promptKey = docInfo.OMUserSelectDocTypeKey.split(";");
  	var latestKey = docInfo.OMLatestDocTypeKey.split(";");
  	var linkedKey = docInfo.OMLinkedDocTypeKey.split(";");
  	var openAction = "0";

	//----------------- Docova related docs --------------------------------	
	//check if selected doc key matches any specific option keys
	if(ArrayGetIndex(promptKey, docTypeKey) > -1) { openAction = "1"; }
	if(ArrayGetIndex(latestKey, docTypeKey)  > -1) { openAction = "2"; }
	if(ArrayGetIndex(linkedKey, docTypeKey)  > -1) { openAction = "3"; }
	
	if(openAction == "0"){ openAction = docInfo.RelatedDocOpenMode; }
	
	if(hasLaterVersion && openAction == "1"){
		//openCurrent = !confirm("The selected document has been superseded by a newer version. Would you like to open the newer version.?");
		window.top.Docova.Utils.messageBox({
			prompt: "The selected document has been superseded by a newer version. Would you like to open the newer version.?",
			title: "Newer version available.",
			width: 400,
			icontype : 2,
			msgboxtype : 4,
			onYes: function() {
				openCurrent = false;
				DoOpenRelatedDocument(entry, openCurrent);
			},
			onNo: function() { 
				openCurrent = true;
				DoOpenRelatedDocument(entry, openCurrent);
			}
    		});	
	}
	else if(hasLaterVersion && openAction == "2"){
		//open latest
		openCurrent = false;
		DoOpenRelatedDocument(entry, openCurrent);
	}else{
		DoOpenRelatedDocument(entry, openCurrent);
	}
}

function DoOpenRelatedDocument(entry, openCurrent){
	//var targetLibraryKey = entry.GetElementValue("librarykey");
	if(openCurrent){
		targetDocId=entry.GetElementValue("parentdocid");	
	}
	else{
		targetDocId=entry.GetElementValue("lvparentdocid");	
	}

	/*
	 * OBSOLETE FOR NEW WORLD
	 *
	//--build the request and get the library NsfName using the LibraryKey that we have from the Related Documents xml.
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>GETNSFNAMEBYLIBRARYKEY</Action>";
	request += "<LibraryKey>" + targetLibraryKey + "</LibraryKey>";
	request += "</Request>";

	var httpObj = new objHTTP()
	if(httpObj.PostData(request, url))
	{
	 if(httpObj.status=="OK") //all OK
		{
			if(httpObj.results.length > 0)
			{
				LibraryNsfName = httpObj.results[0];
			}
		}
	}
	*/
	if(openCurrent && entry.GetElementValue("active") != "1"){
		//document has been deleted or archived 
	
		var docKey = entry.GetElementValue("archivedparentdockey");
		var docTypeKey = entry.GetElementValue("doctypekey"); 
		
		var urlPath = leftBack(docInfo.NsfName ,"/");
		//var dlgUrl = docInfo.ServerUrl + "/" + urlPath +  "/" + LibraryNsfName + "/wDocument?ReadForm&mode=preview";
		var dlgUrl = docInfo.ServerUrl + "/" + urlPath +  "/wDocument?ReadForm&mode=preview";
		dlgUrl += "&typekey=" + docTypeKey + "&datadocsrc=A&datadoc=" + docKey;
		
		var title = entry.GetElementValue("title");
		//since the folder of the doc being opened may not be open, pass the current doc ID as the 'frame ID'.  When related doc is closed, focus returns to the parent
		if ( window.parent.fraTabbedTable )
			window.parent.fraTabbedTable.objTabBar.CreateTab(title, docKey, "D", dlgUrl, docInfo.DocID, false);
		else if ( window.parent.fraTabbedTableSearch ) {
			window.parent.fraTabbedTableSearch.objTabBarSearch.CreateTab(title, docKey, "D", dlgUrl, docInfo.DocID, false);
		}		
		return;
	}
	
	//var urlPath = leftBack(docInfo.NsfName ,"/")
	var urlPath = docInfo.NsfName;
	var docUrl = docInfo.ServerUrl + "/" + urlPath +  "/ReadDocument/" + targetDocId + "?OpenDocument&ParentUNID=" + entry.GetElementValue('folderid');
	
	//check if the user has access to the document.
	var url = docInfo.ServerUrl + "/" + urlPath + "/DocumentServices?OpenAgent";
	var request="";
	request += "<Request>";
	request += "<Action>CHECKACCESS</Action>";
	request += "<Unid>" + targetDocId + "</Unid>";
	request += "</Request>";

	var hasAccess = false;
	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(httpObj.PostData(request, url)) {
		if(httpObj.status=="OK") { hasAccess = true;}
	}

	if(hasAccess) {
		var title = entry.GetElementValue("title");
		//since the folder of the doc being opened may not be open, pass the current doc ID as the 'frame ID' (docInfo.DocID).  When related doc is closed, focus returns to the parent
		if ( window.parent.fraTabbedTable ) {
			window.parent.fraTabbedTable.objTabBar.CreateTab(title, targetDocId, "D", docUrl, docInfo.DocID, false);
		} else if ( window.parent.fraTabbedTableSearch ) {
			window.parent.fraTabbedTableSearch.objTabBarSearch.CreateTab(title, targetDocId, "D", docUrl, docInfo.DocID, false);
		} else {
			window.open(docUrl);
		}
	} else {
		alert(prmptMessages.msgRD013);
	}	
}

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: DeleteLink
 * Removes a highlighted related document entry
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function DeleteLink(){

	if(!relDocsView.objEmbView.hasData){return false;}
	if (relDocsView.objEmbView.currentEntry == "" && relDocsView.objEmbView.selectedEntries.length==0 ) {return false;} //nothing selected
		
	var tmpArray = new Array();
	(relDocsView.objEmbView.selectedEntries.length==0) ? tmpArray[0] = relDocsView.objEmbView.currentEntry : tmpArray = relDocsView.objEmbView.selectedEntries;

	var request = "";
	var unidList = "";
	var parentDocIDList = "";
	var unidArray;
	var entry = "";
		
	for(var i=0; i < tmpArray.length ; i++) {
		entry = relDocsView.objEmbView.GetEntryById(tmpArray[i]);
		if(unidList == ""){
			unidList += tmpArray[i];
			parentDocIDList += (entry) ? entry.GetElementValue("parentdocid") : "";
		}else{
			unidList += "," + tmpArray[i];
			parentDocIDList += ";" + (entry) ? entry.GetElementValue("parentdocid") : "";
		}
	}

	//---- If there is nothing selected, return, otherwise process the tasks
	if (unidList == ""){return;}


	window.top.Docova.Utils.messageBox({
		msgboxtype: 3, 
		prompt: "Do you wish to delete the highlighted relationship?", 
		title: "Delete Relationship?", 
		icontype: 2, 
		width: 400,
		onYes: function(){
			if (docInfo.isNewDoc) {
				var oXml = relDocsView.objEmbView.oXml;
				oXml = jQuery(oXml);
				unidArray = unidList.split(",");
				//----- Build the requests and update the documents -----
				for(var i=0; i<unidArray.length; i++){
					var node = oXml.find('docid:contains("'+ unidArray[i] +'")');
					node.parent().remove();
				}
				relDocsView.objEmbView.Refresh(false,false,false);
				relDocsView.EmbViewReload();
			}
			else {
				unidArray = unidList.split(",");
	
				//----- Build the requests and update the documents -----
				for(var i=0; i<unidArray.length; i++){
					request += "<Unid>" + unidArray[i] + "</Unid>";
					request += "<SrcType>document</SrcType>";
				}
	
				DeleteLinkRequest(request);
	
				//Refresh the related documents xml
				if (relDocsView.objEmbView.selectedEntries.length!=0) {
					relDocsView.objEmbView.selectedEntries = new Array();
				} else {
					relDocsView.objEmbView.currentEntry=""; //selected/current are deleted now
				}
				relDocsView.objEmbView.Refresh(true,false,true); //reload xml data with current xsl
				relDocsView.EmbViewReload();
			
				//Track any deleted links for the linked documents for cleanup if the user chooses to not save
				//TrackTmpOrphans("DEL", unidList);
	
				//if parent doc has not been saved yet, also need to update tmpLinkDataXML
				//eg: create new doc, add link, remove link, then save parent
				UpdateLinkDataXML(parentDocIDList);
			}
		}
	});
}//--end DeleteLink

function UpdateLinkDataXML(parentDocIDList){
	if(docInfo.isNewDoc) {
		relDocTmpXML = thingFactory.GetHTMLItemValue("tmpLinkDataXML");
		//tmpLinkData may be single xml string with multi UNID nodes, and/or multi xml strings with ~ delimiter
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
		thingFactory.SetHTMLItemValue("tmpLinkDataXML", arr.join("~"));
	}
}

function DelUnidNode(srcXML, Unid){
	var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
	xmlDoc.async = false;
	xmlDoc.loadXML(srcXML); 
	
	var LnkData = xmlDoc.selectSingleNode('//Unid[.="' + Unid + '"]' );
	
	if (LnkData != null){
		LnkData.parentNode.removeChild(LnkData);
	}
	return xmlDoc.xml;
}

function DeleteLinkRequest(docRequest){
	var DocID;
	if(docInfo.isNewDoc){ //If the current document hasn't been saved yet, then the DocID sent needs to be blank
		DocID = "";
	}else{
		DocID = docInfo.DocID;
	}
	var request = "<Request><Action>REMOVERELATED</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request +=  "<ParentDocID>" + DocID + "</ParentDocID>";	
	request += docRequest;
	request += "</Request>";
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent";
	ShowProgressMessage(prmptMessages.msgCF020);
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, processUrl) || httpObj.status=="FAILED"){
		HideProgressMessage();
			return false;
		}
	HideProgressMessage();
	
}

function TrackTmpOrphans(action, request) {
//-----Action parameter can be ADD or DEL-----

	//---- Add to the list -----
	if(action == "ADD"){
		var xmlDoc = relDocsView.objEmbView.oXml;
		var nodeList = xmlDoc.selectNodes( 'documents/document' ); 

		if(nodeList.length ==0){ return false;} 
	
		var txtNode; 
		var txtLibraryKey;
		var txtUnid;
		var txtLibraryKeyUnidList = "";
		var ctr = 0; 

		while( objNode = nodeList.nextNode()){ 
			var txtNode = objNode.firstChild; 
			txtLibraryKey = objNode.selectSingleNode( 'librarykey').text;
			txtUnid = objNode.selectSingleNode( 'Unid' ).text;
			AddTmpOrphan(txtLibraryKey, txtUnid)
			ctr++; 
		}
	}

	//---- Delete from the list -----
	if(action == "DEL"){
		var unidList = request;
		var unidArray;
		var i;
		
		if (unidList == ""){
			return;
		}else{
			unidArray = unidList.split(",");
		}

		for(var i=0; i<unidArray.length; i++){
			DelTmpOrphan(unidArray[i]);
		}		
	}
}


//------------------------------------------------------------------------------------------------
// OverrideUploader
// Description: Override default Uploader settings and sets events
//-------------------------------------------------------------------------------------------------
function OverrideUploader(){
	if(docInfo.Mode=="dle" || docInfo.isBookmark) {return true;} //dle mode or bookmark so dont remap any functionality

	//OverrideUploader is run with setinterval (inline) because the Uploader might not be loaded before this function executes
	if(typeof(DLIUploader1)==typeof(undefined) || DLIUploader1 == null){  //if null return
		return;
	}else{
		clearInterval(myVar); //if Uploader is loaded and ready clear the setinterval
	}
	
	var Uploader = DLIUploader1;	
	if(Uploader == null) {
		alert(prmptMessages.msgRD001); 
		return false;
	}
	Uploader.OnBeforeFileEditEvent = "LinkedFilesOnBeforeFileEdit";
	Uploader.OnBeforeDownloadEvent = "LinkedFilesOnBeforeDownload";
	Uploader.OnDownloadEvent = "LinkedFilesOnDownload";
	Uploader.OnBeforeLaunchEvent = "LaunchOverride";
	
}


//------------------------------------------------------------------------------------------------
// LinkedFilesOnUnLoad
// Description: Clean up temporary directory
//-------------------------------------------------------------------------------------------------
function LinkedFilesOnUnLoad(){
	if(docInfo.Mode=="dle") {return true;} //dle mode so dont do any special on unload
	
	DeleteTempDirectory(true);	
}

//------------------------------------------------------------------------------------------------
// CreateTempDirectory
// Description: creates a tempoary directory if it doesn't exist alread, and
//                        remaps the download and launch directory for uploader
//------------------------------------------------------------------------------------------------
function CreateTempDirectory(){
	var Uploader = doc.DLIUploader1;
	
	//-- remap download and edit file paths 
	Uploader.DownloadPath = "0";
	Uploader.EditFilePath = "0";
	Uploader.ResolvePathNames();

	var dockey = docInfo.DocKey;
		
	var temppath = Uploader.DownloadPath + "~" + dockey + "\\";
	var folderexists = CreateLocalFolder(temppath, false);
	if (folderexists){
		Uploader.DownloadPath = temppath;
		Uploader.EditFilePath = temppath;
	}
}


//------------------------------------------------------------------------------------------------
// DeleteTempDirectory
// Description: deletes a tempoary directory if it exists, and
//                        remaps the download and launch directory for uploader
// Inputs: promptiflocked - boolean - true if process should prompt user
//                                            if the directory cannot be deleted (presumably
//                                            because it is locked by another process) and
//                                            give user option to try again
//------------------------------------------------------------------------------------------------
function DeleteTempDirectory(promptiflocked){
	var result = true;
	var Uploader = doc.DLIUploader1;
	if (!Uploader){ return false; }
	
	//-- remap download and edit file paths 
	Uploader.DownloadPath = "0";
	Uploader.EditFilePath = "0";
	Uploader.ResolvePathNames();

	var dockey = docInfo.DocKey;
		
	var temppath = Uploader.DownloadPath + "~" + dockey;

	if (LocalFolderExists(temppath, true)){
		do{
			var tryagain = false;
			result = DeleteLocalFolder(temppath, true);
			if(promptiflocked && !result){
				var answer = thingFactory.MessageBox(prmptMessages.msgRD015, 5+32, prmptMessages.msgRD016);
				if(answer == 4){ tryagain = true;}
			}
		} while (tryagain); 
	}	
	return result;
}

//-----------------------------------------------------------------------------------------------
//
//-----------------------------------------------------------------------------------------------
function LinkedFilesOnBeforeFileEdit(filename){
	var Uploader = doc.DLIUploader1;
	//-- check to see if chosen file is a new file if so ask user to save document first
	var index = Uploader.GetFileIndex(filename);
	if (Uploader.IsFileNew( index )){
		alert(prmptMessages.msgRD002.replace('%filename%', filename));
		return false;
	}
	return true;
}

//------------------------------------------------------------------------------------------------
// LaunchOverride
// Description: Override launch behavior for documents opened in read
//                        mode instead of for editing so that files are opened locally
//                        instead of from server url. Also download any attachments 
//                        in related documents
// Inputs: filename - string - filename selected in uploader for launch
//-------------------------------------------------------------------------------------------------
function LaunchOverride(filename){
	var Uploader = doc.DLIUploader1;

	//------ download the selected file instead of launching ---------
	var index = Uploader.GetFileIndex(filename);
	CreateTempDirectory();
	var basepath = Uploader.DownloadPath;
	var localfilename = basepath + filename;

	//------if file exists, delete it-----------
	if (Uploader.LocalFileExists( localfilename )){
		if(!DeleteLocalFile(localfilename, true)){
			alert(prmptMessages.msgRD003.replace('%filename%', filename)); 	
			return false;
		}
	}
	
	//-- disable custom on download event temporarily --
	var tmpOnDownloadEvent = Uploader.OnDownloadEvent;
	Uploader.OnDownloadEvent = "";

	//-- download a local copy of the file ---
	Uploader.DownloadFile(	index, false);
	
	//-- set file to read only --
	FileReadOnly(localfilename, true);
	
	//-- reset custom on download event --
	Uploader.OnDownloadEvent = tmpOnDownloadEvent;
	
	//-- download any related files	--
	LinkedFilesOnDownload(filename); 

	//-- open the downloaded file --
	OpenLocalFile(localfilename);

	//-- don't launch the file via uploader --	
	return false;
}

//------------------------------------------------------------------------------------------------
// LinkedFilesOnDownload
// Description: When downloading an attachment also retrieve
//                        any attachments in related documents
// Inputs: filename - string - filename selected in uploader for download
//-------------------------------------------------------------------------------------------------
function LinkedFilesOnDownload(filename){

	var Uploader = doc.DLIUploader1;
	
	var index = Uploader.CurrentFileIndex;
	var localfilepath = Uploader.LocalFileName( index );
	localfilepath = localfilepath.substr(0, localfilepath.lastIndexOf("\\"));

	//-- disable custom on download event temporarily --
	var tmpOnDownloadEvent = Uploader.OnDownloadEvent;
	Uploader.OnDownloadEvent = "";
	
	//-- download any related documents --
	DownloadRelatedDocuments(localfilepath);

	//-- reset custom on download event --
	Uploader.OnDownloadEvent = tmpOnDownloadEvent;
}

//------------------------------------------------------------------------------------------------
// LinkedFilesOnBeforeDownload
// Description: Before downloading an attachment create a temporary 
//                        download folder if needed and check to see if the file 
//                        already exists and is read only and if so remove old copy
// Inputs: filename - string - filename selected in uploader for download
//-------------------------------------------------------------------------------------------------
function LinkedFilesOnBeforeDownload(filename){
	var Uploader = doc.DLIUploader1;
	
	//-- create and remap temporary download folder --
	CreateTempDirectory();
	
	var onlyFileName = GetFileName(filename);
	var localFilePath = Uploader.DownloadPath + onlyFileName;
	
	//-- check to see if file already exists locally --
	//------if file exists, delete it-----------
	if (Uploader.LocalFileExists( localFilePath)){
		if(!DeleteLocalFile(localFilePath, true)){
			alert(prmptMessages.msgRD003.replace('%filename%', filename)); 	
			return false;
		}
	}
	return true;
}

//------------------------------------------------------------------------------------------------
// DownloadRelatedDocuments
// Description: Downloads attachment in related documents
// Inputs:  dataObj - related documents data object containing recordset  
//                localpath - local directory path to store files to
//-------------------------------------------------------------------------------------------------
function DownloadRelatedDocuments(localpath){

	if(!relDocsView.objEmbView.hasData){return false;}
	
	var Uploader = doc.DLIUploader1;
     var tempcount = 0;

	var promptKey = docInfo.OMUserSelectDocTypeKey.split(";");
  	var latestKey = docInfo.OMLatestDocTypeKey.split(";");
  	var linkedKey = docInfo.OMLinkedDocTypeKey.split(";");

	var reldoclistbylib = [];

	var request = "";
	var unidList = "";
	var parentDocIDList = "";
	var unidArray;
	var entry = "";

	//-- loop through any related documents
	relDocsView.objEmbView.GetAllEntryIds(true);
	var tmpArray = relDocsView.objEmbView.allEntries;
	
	for(var i=0; i < tmpArray.length ; i++) {

		entry = relDocsView.objEmbView.GetEntryById(tmpArray[i]);

		var libKey = entry.GetElementValue("librarykey");
		var docTitle =  entry.GetElementValue("title");
		var docTypeKey =  entry.GetElementValue("doctypekey");

		var openAction = "0";			
		//-- check if selected doc key matches any specific option keys
		if(ArrayGetIndex(promptKey, docTypeKey) > -1) { openAction = "1"; }
		if(ArrayGetIndex(latestKey, docTypeKey)  > -1) { openAction = "2"; }	
		if(ArrayGetIndex(linkedKey, docTypeKey)  > -1) { openAction = "3"; }		
		if(openAction == "0"){ openAction = docInfo.RelatedDocOpenMode; }

		var hasLaterVersion = (entry.GetElementValue("lvparentdocid") !="");
		
		var openOrig = true;  //open original linked document even if newer version exists
		if(hasLaterVersion && openAction == "1"){
			openOrig = !confirm("The linked document titled '" + docTitle +"' has been superseded by a newer version. Would you like to use the newer version?");
		}else if(hasLaterVersion && openAction == "2"){
			openOrig = false;  //open latest version
		}
	
		if(! reldoclistbylib.hasOwnProperty(libKey)) { reldoclistbylib[libKey] = []; }
		//-- store document info in an object for later reference
		reldoclistbylib[libKey].push({
			LibraryKey: libKey,					
			DocID: entry.GetElementValue("parentdocid"),
			LVDocID: entry.GetElementValue("lvparentdocid"),
			TargetDocID: (openOrig) ? entry.GetElementValue("parentdocid") : targetDocId=entry.GetElementValue("lvparentdocid"),
			HasLaterVersion: hasLaterVersion,
			DocTypeKey: docTypeKey,
			DocTitle: docTitle,
			OpenAction: openAction,	
			OpenOrig: openOrig,
			IsActive:  (openOrig && entry.GetElementValue("active") != "1") ? false : true
		});
	}

	//-- loop through the source libraries
	for (var libKey in reldoclistbylib) {
   		var libObj = reldoclistbylib[libKey];
   		var docids = "";
   		//-- loop through the document ids for the specified library
  		for (var i = 0; i<libObj.length; i++){
  			var docObj = libObj[i];
  			if(docids != ""){docids +=",";}
			docids += docObj.TargetDocID;
  		}
  		//-- get attachments from selected documents
		var attInfo = GetAttachmentURLs(docids, '*', 'ALL', libKey, 'NOCOMPARE');

		if(attInfo){
			//-- loop through results for each document
			for(var i = 0; i<attInfo.length; i++){			
				var fileURLs = attInfo[i].selectSingleNode("URL").text.split("*");
				var fileNames = attInfo[i].selectSingleNode("FILENAME").text.split("*");
				//-- loop through attachments within the document
				for(var f = 0; f<fileURLs.length; f++){
					var localFilePath = localpath + "\\" +  fileNames[f];
					//-- check to see if file already exists locally --
					//------if file exists, delete it-----------
					if (Uploader.LocalFileExists( localFilePath)){
						if(!DeleteLocalFile(localFilePath, true)){
							alert(prmptMessages.msgRD010.replace('%filename%', fileNames[f])); 	
							return false;
						}
					}
					//-- download the file
					DLExtensions.DownloadFileFromURL(fileURLs[f], localFilePath);
					//-- set file as read only --
					FileReadOnly(localFilePath, true);
					
					//-- launch the file
					if(docInfo.LaunchLinkedFiles){
						if(IsFileLaunchable(fileNames[f])){
							OpenLocalFile(localFilePath);
						}
					}
				}
			}
		}
	}
	return true;
}

//------------------------------------------------------------------------------------------------
// FileReadOnly
// Description: Sets or removes the read only attribute on a file
// Inputs:  filename - string - local file path and filename of file to modify 
//                enablereadonly - boolean - true if file should be read only
//                                                false if file should be writable
//-------------------------------------------------------------------------------------------------
function FileReadOnly(filename, enablereadonly){
	var READ_ONLY = 1;
	
	var fso = thingFactory.NewObject("Scripting.FileSystemObject");
	if (fso == null){
		alert(prmptMessages.msgAUP002);
		return false;
	}
	
	//-- set the file to read only
	var f = fso.GetFile(filename);
	if(f == null){
		alert(prmptMessages.msgRD004.replace('%filename%', filename));
		fso = null;
		return false;
	}
	
	//-- file should be read only --
	if (enablereadonly){
		//-- check to see if file is already read only --
		if (!(f.Attributes & READ_ONLY)){
	  		f.Attributes = f.Attributes ^ READ_ONLY;
		}	
	//-- file should be writable --
	}else{
		//-- check to see if file is already writable --
		if (f.Attributes & READ_ONLY){
	  		f.Attributes = f.Attributes ^ READ_ONLY;
		}		
	}
	
	f = null;
	fso = null;
	return true;
}


//------------------------------------------------------------------------------------------------
// OpenLocalFile
// Description: Opens a specified file
// Inputs:  filename - string - local file path and filename of file 
//               hidemessages - boolean - true to hide any alert messages 
//-------------------------------------------------------------------------------------------------
function OpenLocalFile(filename, hidemessages){
	DLExtensions.LaunchFile(filename);
	
	return true;
}

//------------------------------------------------------------------------------------------------
// DeleteLocalFile
// Description: Deletes a specified file
// Inputs:  filename - string - local file path and filename of file
//               hidemessages - boolean - true to hide any alert messages 
//-------------------------------------------------------------------------------------------------
function DeleteLocalFile(filename, hidemessages){
	var fso = thingFactory.NewObject("Scripting.FileSystemObject");
	
	if (fso == null){
		if(!hidemessages){alert(prmptMessages.msgAUP002);}
		return false;
	}
	
	//------if file exists, delete it-----------
	var f = fso.GetFile(filename);
	if(f != null){
			try {
				f.Delete(true);
			}catch(err){
				if(!hidemessages){ alert(prmptMessages.msgRD005.replace('%filename%', filename));} 	
				f = null;
				fso = null;
				return false;
			}
	}
	
	f = null;
	fso = null;
	return true;
}

//------------------------------------------------------------------------------------------------
// LocalFolderExists
// Description: Determines if a specified folder exists locally
// Inputs:  foldername - string - local path of folder
//               hidemessages - boolean - true to hide any alert messages 
//-------------------------------------------------------------------------------------------------
function LocalFolderExists(foldername, hidemessages){
	if(foldername == ""){return false;}
	
	var fso = thingFactory.NewObject("Scripting.FileSystemObject");	
	if (fso == null){
		if(!hidemessages){alert(prmptMessages.msgAUP002);}
		return false;
	}
	
	folderfound = fso.FolderExists(foldername);
	fso = null;
	return folderfound;
}

//------------------------------------------------------------------------------------------------
// CreateLocalFolder
// Description: Creates a specified folder locally if not already present
// Inputs:  foldername - string - local path of folder
//               hidemessages - boolean - true to hide any alert messages 
//-------------------------------------------------------------------------------------------------
function CreateLocalFolder(foldername, hidemessages){
	var foldercreated = false;
	if(foldername == ''){return false;}

	var fso = thingFactory.NewObject("Scripting.FileSystemObject");	
	if (fso == null){
		if(!hidemessages){alert(prmptMessages.msgAUP002);}
		return false;
	}
	
	if (fso.FolderExists(foldername)){
		foldercreated = true;
	} else {
		try{
			fso.CreateFolder(foldername);
			foldercreated = true;
		}catch(err){
			if(!hidemessages){alert(prmptMessages.msgRD006 + ": '" + foldername + "'");}
		} 
	}	

	fso = null;
	return foldercreated;
}

//------------------------------------------------------------------------------------------------
// DeleteLocalFolder
// Description: Deletes a specified folder locally if possible
// Inputs:  foldername - string - local path of folder
//               hidemessages - boolean - true to hide any alert messages 
//-------------------------------------------------------------------------------------------------
function DeleteLocalFolder(foldername, hidemessages){
	var folderdeleted = false;
	if(foldername == ''){return false;}	
	var fso = thingFactory.NewObject("Scripting.FileSystemObject");
	
	if (fso == null){
		if(!hidemessages){alert(prmptMessages.msgAUP002);}
		return false;
	}

	if (fso.FolderExists(foldername)){ 
		try{
			fso.DeleteFolder(foldername, true);
			folderdeleted = true;
		}catch(err){
			if(!hidemessages){alert(prmptMessages.msgRD007 + ": '" + foldername + "'");}
		} 
	}else{	
		folderdeleted = true;
	}
	fso = null;
	return folderdeleted;
}

//--------------------------------------------------------------------------------------------
// IsFileLaunchable
// Description: determines based on file extension whether a file is launchable
// Inputs: filename - string - file name of the file
// Output: returns true if file is launchable, false otherwise
//--------------------------------------------------------------------------------------------
function IsFileLaunchable(filename){
	var allowable = ["DOC","DOCM","DOCX","XLS","XLSX","XLSM","PPT","PPTX","CSV","XML","TXT","PDF","BMP","GIF","JPG","PNG","TIF","HTM","HTML"];
	
	var arr = filename.split(".");
	var pos = filename.indexOf(".");
	var fileext = ((pos == -1) ? "" : arr.pop());
	fileext = fileext.toUpperCase();
	
	var result = (ArrayGetIndex(allowable, fileext) > -1) ? true : false;
	
	return result;
}

//--------------------------------------------------------------------------------------------
// OpenTemporaryFolder
// Description: opens the temporary local folder used for editing related 
//                        documents if it exists
// Output: returns true if temporary folder is found and launched
//--------------------------------------------------------------------------------------------
function OpenTemporaryFolder(){
		var wasopened = false;
		var Uploader = doc.DLIUploader1;
		
		var foldername = Uploader.EditFilePath;
		var folderfound = LocalFolderExists(foldername, true);
		if (folderfound){
			var objshell = thingFactory.NewObject("Wscript.Shell");	
			if (objshell == null){
				alert(prmptMessages.msgRD008);
				return false;
			}
			objshell.Run(foldername, 1, false); 
			objshell = null;
			wasopened = true;
		} else {
			alert(prmptMessages.msgRD009);
		}
		
		return wasopened;
}

//--------------------------------------------------------------------------------------------
// CopyTemporaryFolderPath
// Description: copies the path to the temporary folder to clipboard 
// Output: returns true if temporary folder is found and path copied
//--------------------------------------------------------------------------------------------
function CopyTemporaryFolderPath(){
		var pathcopied = false;
		var Uploader = doc.DLIUploader1;
		
		var foldername = Uploader.EditFilePath;

		var folderfound = LocalFolderExists(foldername, true);
		if (folderfound){
			DLExtensions.SetCBData("text", foldername);
			pathcopied = true;
		} else {
			alert(prmptMessages.msgRD009);
		}
		
		return pathcopied;
}

//=========================================================================
// CreateLinkedOfficeFilesSubmenu
// Description: Generates a sub menu for Linked Office Documents
// Inputs: actionButton - action button form element
//=========================================================================
function CreateLinkedOfficeFilesSubmenu(actionButton){
	if(!actionButton) {return;}

	var popup = new objPopupmenu();
     popup.textColumnWidth = 80;
     popup.actionHeight=18;
     popup.hasToggleIcons = false;
     var actionHandler = "parent.ProcessLinkedOfficeFilesSubmenuAction(this)";

	//addAction= function(isActive, isChecked, isBold, actionText, actionName, 
	//                                     actionIconSrc, actionShortcutKeyText, actionHandler)
	popup.addAction(true , false, false, "Open Working Folder" , "OpenWorkingFolder", "" , "", actionHandler);
	popup.addAction(true , false, false, "Copy Working Folder Path" , "CopyWorkingFolderPath", "" , "", actionHandler);	

     popup.height = 50;
     popup.width = 160;
     popup.offsetTop= 15;
     popup.offsetRight = 0;
      
     var oPopBody = oPopup.document.body;
	oPopBody.innerHTML = popup.innerHTML();
	
     oPopup.show(0, 20, popup.width, popup.height, actionButton);
     return false;
}


//=========================================================================
// ProcessLinkedOfficeFilesSubmenuAction
// Description: handle action from context sub menu
// Inputs: actionObj - pop up action object
//=========================================================================
function ProcessLinkedOfficeFilesSubmenuAction(actionObj){
            if(!actionObj ) {return false;}
            if(oPopup) {oPopup.hide();}
            var action = actionObj.actionName.split("-")[0];

            if(action=="OpenWorkingFolder"){
                        OpenTemporaryFolder();
            }
            else if(action=="CopyWorkingFolderPath"){
                        CopyTemporaryFolderPath();
            }
            return;
}