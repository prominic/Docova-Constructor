//----------- onbeforeadd handler for local Uploader

function Uploader2OnBeforeAdd(filePath)
{
	var Uploader = doc.DLIUploader2;
	var max = Uploader.FileCount;
	var validFileCount=0;
	for (var a = 1; a <= max; a++){
		if (!Uploader.IsFileDeleted( a )){
				validFileCount++;
			}
		}
	if(validFileCount>=1){
		thingFactory.Messagebox(prmptMessages.msgWC002, 0 + 16, prmptMessages.msgAUP026);
		return false;
	}
	var fileName = GetFileName(filePath);
	if(!IsValidFileExtension(fileName, doc.officeInfo.AllowedOfficeFileExtensions)){
		thingFactory.Messagebox(prmptMessages.msgATT015.replace('%allowedextension%', doc.officeInfo.AllowedOfficeFileExtensions), 0 + 16, prmptMessages.msgATUP026);
		return false;
	}
	return true;
}

function Uploader2OnAdd(filePath)
{
return;
	if(doc.officeInfo.OfficeApplication=="WORD"){
		ConvertWordFile("")
	}
}

//--------- on file delete handler
function Uploader2OnDelete(filePath)
{
	ClearHTMLContent();
}


//========================================================================
// Office document conversion to html code
//========================================================================

//------ word to html file conversion -----
function ConvertWordFile(outputType)
{
	var upldobj = doc.DLIUploader1
	var wdFormatFilteredHTML = 10
	var wdDoNotSaveChanges = 0
	
	//--- get the file name to be converted
	var localfilename = GetFirstLocalFile("", 2)
	if(!localfilename){
		return;
		}
	var objWord=null	
	var statusMsg = (outputType=="PREVIEW")? prmptMessages.msgWC003 : prmptMessages.msgWC004;
	//convert data
	try
		{
			ShowProgressMessage(statusMsg);
			//----- get instantiate necessary OLE objects ----
			objWord = thingFactory.NewObject("Word.Application");
			objWord.Visible=false;
			var wordDoc = objWord.Documents.Open(localfilename, true);

			//use uploader to get temporary directory path
			upldobj.ResolvePathNames()
			var olddownloadpath = upldobj.DownloadPath
			upldobj.DownloadPath = "0"    //temporary folder
			upldobj.ResolvePathNames()
			var tmpFolder = upldobj.DownloadPath
			upldobj.DownloadPath = olddownloadpath
			
			var filePathParts = localfilename.split("\\");
			var wordFileName = filePathParts[ filePathParts.length - 1 ];
			var htmlPath = tmpFolder + wordFileName.split(".")[0] + ".htm";
			tmpFilePaths[tmpFilePaths.length] = htmlPath;
			wordDoc.AttachedTemplate.Saved = true;  //fix to tell Word not to prompt to save the default .dot template
			wordDoc.SaveAs(htmlPath, wdFormatFilteredHTML);			
			wordDoc.Close(wdDoNotSaveChanges);
			wordDoc=null;
			objWord.Quit();
			objWord=null;
			DeleteFiles(0); //clear all support files from Uploader1
//			upldobj.UploadFile(localfilename)
			ProcessWordHTML(outputType, htmlPath);
			}
	catch(e)
		{
			wordDoc=null;
			if(objWord!=null){
				objWord.Quit();
				objWord=null;
				}
		 	HideProgressMessage();
			alert(prmptMessages.msgWC001 + e.message);
			return false;
		}
		 	HideProgressMessage();
			wordDoc=null;
			if(objWord!=null){
				objWord.Quit();
				objWord=null;
				}
			return true;	
}

function ProcessWordHTML(outputType, htmlPath)
{	
		var objFSO=null;
	
		var convertedDoc = ReadFile(htmlPath);
	
		var bodyContent=""
		// --- get Word style sheet
//		if(convertedDoc.styleSheets.length>0){
//			document.styleSheets[document.styleSheets.length-1].cssText=convertedDoc.styleSheets[0].cssText;
//			doc.OfficeStyle.value=convertedDoc.styleSheets[0].cssText;
//		}
//		else{
//			document.styleSheets[document.styleSheets.length-1].cssText="";
//			doc.OfficeStyle.value="";
//		}
	
		//----- adjust the image sources to use absolute local path
		var imagePath = htmlPath.split(".")[0] + "_files" ;
		objFSO = thingFactory.NewObject("Scripting.FileSystemObject");
	
		if(objFSO.FolderExists(imagePath)){
			var imageFolder = objFSO.GetFolder(imagePath).Name;
			
			if(outputType=="PREVIEW"){
					convertedDoc = SetImagePaths(convertedDoc, imageFolder, imagePath + "\\");
				}
			else{
					var docFileUrl="/" + docInfo.NsfName + "/luAllByDocKey/" + docInfo.DocKey + "/$File/";
					convertedDoc = SetImagePaths(convertedDoc, imageFolder, docFileUrl);
					doc.DLIUploader1.UploadFolder( imagePath, "*.*" );
					//AtachFilesFromFolder(imagePath)
				}
			tmpSupportFolders[tmpSupportFolders.length] = imagePath;
		}
	
		if(outputType=="PREVIEW"){
				doc.divOfficeContent.innerHTML=convertedDoc;
			}
		else{
				doc.Body.value=convertedDoc;
			}

	objFSO=null;
}


function SetImagePaths(htmldoctext, imageFolder, basePath)
{
	var tempBasePath = basePath.findandreplace("\\", "/")
	var tempImageFolder = imageFolder + "/"
	
	htmldoctext = htmldoctext.findandreplace(encodeURI(tempImageFolder), encodeURI(tempBasePath))
	return htmldoctext
}



function ClearHTMLContent()
{
	doc.divOfficeContent.innerHTML="";
	doc.OfficeStyle.value=""
	doc.Body.value="";
}

function CustomSave(refreshView)
{
	ConvertWordFile();
	return SaveAndClose(refreshView);
}

function CustomUnload()
{
	ClearTempFiles();
	return true;
}


function CustomOnLoad()
{
	 //---- auto attach a template
	 if (docInfo.isNewDoc){
	   try{
		var templateInfo = GetTemplateInfo();
		var templateUrl = GetTemplateUrl(templateInfo);
		var localfilename = DownloadAndAttach2(templateUrl);
	    }
	   catch(e) {}
	 } 
}

function DownloadAndAttach2(fileUrl, newFileName)
{
	if(!fileUrl) {return false;}
	var Uploader = doc.DLIUploader2;
	Uploader.DownloadPath = "1"; 	
	Uploader.ResolvePathNames();	
	
	var fileNameElements = fileUrl.split("/");
	var localfilename = Uploader.DownloadPath + fileNameElements[fileNameElements.length-1];

	var newFileURL=fileUrl.replace(fileNameElements[fileNameElements.length-1],escape(fileNameElements[fileNameElements.length-1]));
	if(Uploader.DownloadFileFromURL(newFileURL, localfilename, false, false))
	{
		 if(Uploader.UploadFile(localfilename))
		 {
			if(newFileName) {Uploader.RenameFile( Uploader.GetFileIndex( localfilename ), newFileName, true )}
		 	return localfilename;
		 }
	}
	return false;
}


function ReadFile(filename)
{
   var fso, ts, s;
   var ForReading = 1;
   fso = thingFactory.NewObject("Scripting.FileSystemObject");
   // Read the contents of the file.
   ts = fso.OpenTextFile(filename, ForReading);
   s = ts.ReadAll();
   ts.Close();
   fso = null;
   return s;
}


// Find and replace a string inside another string
String.prototype.findandreplace = function (find, replace)
{
        var myString = this;
        var counter = 0;

        while (counter < myString.length)
        {
                var start = myString.indexOf(find, counter);
                if (start == -1)
                {
                        break;
                } else {
                        var before = myString.substr(0, start);
                        var after = myString.substr(start + find.length, myString.length);
                        myString = before + replace + after;
                        var counter = before.length + replace.length;
                }
        }

        return myString;
};