var params= window.dialogArguments;

function updateWordAndPDF(){
	
	if(docInfo.isDocBeingEdited) {
	
		if(!DLExtensions.isPDFCreatorInstalled()) {
			alert(prmptMessages.msgW2PDF001);
			return
		}
		/*called whenever the document is saved.  
		checks for an existing PDF and if found, deletes it, then creates a new pdf from the updated Word document.*/
		
		//first check that all files have been saved.  FileOpenInApp() function is in sfDocSection-Attachments subform
		if(FileOpenInApp() == true) { return false };
		
		//comma separated (no spaces) string list of fields to push to the Word doc bookmarks
//		var fieldList = "Title,SubjectArea,EnforcementDate,EnforcementEndDate";
		
		var Uploader = doc.DLIUploader1;
		var pdfFileName = "";
		var localFileName = "";
		
		//get the filename from a field on the document. Remove spaces and non word characters (&, /, etc)
		var fileName = thingFactory.GetHTMLItemValue("Subject").replace(/\W/g, '');
		
		//get the Uploader index of the word doc (and pdf if one exists)
		var wordIndex = -1;
		var pdfIndex = -1;
		var files = Uploader.GetAllFileNames(";", false).split(";");
		for(x=0; x<files.length; x++) {
			if(!Uploader.IsFileDeleted(x+1)) {
				e = files[x].lastIndexOf(".");
				ext = files[x].substr(e+1);
				if(ext.slice(0,3).toLowerCase() == "doc") { wordIndex = x +1}
				if(ext.slice(0,3).toLowerCase() == "pdf") { pdfIndex = x +1}
			}
		}
		
		if(wordIndex > 0) {	
//			ShowProgressMessage("Updating Word document..." )
			
			//if a new doc then localfilename already available, otherwise need to download
			if(Uploader.IsFileNew(wordIndex) || Uploader.IsFileEdited(wordIndex)) {	
				localFileName = Uploader.FileName(wordIndex);
				
			}
			else {
				//download the existing Word doc to the system temp folder. 
				//If this is changed from "0", need to adjust Wscript.Shell code as well.
				Uploader.DownloadPath ="0"; 			
				//first check for existing doc on local system and delete if found. 
				var wshShell = thingFactory.NewObject("WScript.Shell"); 
	        		var temp = wshShell.ExpandEnvironmentStrings("%TEMP%"); 
	        		wshShell = null;
	        		
				fso = thingFactory.NewObject("Scripting.FileSystemObject");
				if(fso.FileExists(temp + "\\" + Uploader.FileName(wordIndex))) {
					f = fso.GetFile(temp + "\\" + Uploader.FileName(wordIndex));
					f.Delete(true);
				}
				fso = null;
				
				//download the Word file
				Uploader.DownloadFile( wordIndex, false );		
				localFileName = Uploader.localFileName(wordIndex);	
			}
	
			//update the bookmarks in the word doc from the current document.  Function is in sfDocSection-Attachments subform		
//			if (!PopulateBookmarks(localFileName, fieldList , true)) { return false;	}
	
			//copy to a temp location. PDF process will delete copy after creating pdf, but we need to leave original doc to upload			
			pdfFileName = copyFileToTempLocation(localFileName);
			
			 //remove the any old pdf files
			if(pdfIndex > 0) {	Uploader.RemoveFile(pdfIndex)}
			
			//convert file in temp location to PDF
			ShowProgressMessage(prmptMessages.msgW2PDF004);
			var allowPrint = parseInt(thingFactory.GetHTMLItemValue("AllowPrint")); //field on doc that sets whether printing is allowed
			var pdfPath = DLExtensions.ConvertToPDF ( pdfFileName, allowPrint, "aPassword");
			HideProgressMessage();
			if ( pdfPath == "" ) {
				alert ( prmptMessages.msgW2PDF002 );
				return; 
			}
			
			
			//upload the pdf
			pdfFileName = fileName + ".pdf"
			Uploader.UploadFile ( pdfPath, pdfFileName);
		}
	}
	
	//if created from a parent doc, close the window
	if(params){
		SaveAndClose(false);
		window.close();
	} else {
		SaveAndClose();
	}
}


function deleteLocalFile(localFileName){
	fso = thingFactory.NewObject("Scripting.FileSystemObject");
	if(fso.FileExists(localFileName)) {
		f = fso.GetFile(localFileName);
		f.Delete(true);
	}
	fso = null;
}

function copyFileToTempLocation(currentFilePath){
	//copies file to filename + temp + .ext within the same location it's currently in
	fso = thingFactory.NewObject("Scripting.FileSystemObject");
	
	e = currentFilePath.lastIndexOf(".");
	fileExt = currentFilePath.substr(e);	
	var targetFileName = currentFilePath.replace(fileExt, "temp" + fileExt)
	try {
		f = fso.GetFile(currentFilePath);
		f.Copy(targetFileName, true);
		fso = null;
		return targetFileName;
	}
	catch (e) {
		alert(prmptMessages.msgW2PDF003);
		fso = null;
		return false;
	}
}

function handleOnload() {

	if (!docInfo.isDocBeingEdited) {
		var Uploader = doc.DLIUploader1;
		var pdfIndex = -1;
		var files = Uploader.GetAllFileNames(";", false).split(";");
		for(x=0; x<files.length; x++) {
			e = files[x].lastIndexOf(".");
			ext = files[x].substr(e+1);
			if(ext.slice(0,3) == "pdf") { pdfIndex = x +1}
		}	
		if(pdfIndex > 0) {
			//show PDF in iframe
			//open the pdf: view=FitH (fill horizontally); toolbar=0 (don't show toolbar).  
			//new Date() function makes the doc look like a new doc and therefore reload from server, not cache
			url = docInfo.LogNsfName + "/0/" + docInfo.DocID + "/$file/" + Uploader.ServerFileName(pdfIndex) + "?OpenElement&" + (new Date()).valueOf() + "#view=FitH&toolbar=0";
			obj = document.getElementById("ReadPDF");
			obj.src = url;
		}
	}
}
