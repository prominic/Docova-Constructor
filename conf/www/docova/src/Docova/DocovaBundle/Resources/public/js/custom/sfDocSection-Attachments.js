var fileCountError = false;
var oldWidth = "";
var UploaderDirOverride_Launch = ""; 
var UploaderDirOverride_Edit = "";
var UploaderDirOverride_Download = "";

var dialogArguments =  [];

var returnValue;

function AddFileFromDOE(filepath, filename, filedate, filesize, filetype, targetUploader)
{
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	Uploader.UploadFile(filepath, filename, filedate, filesize, filetype);
	return true;
}


//====================== uploader burtton handlers =====================
function UmenuClick(menuitem, from)
{
	if(!menuitem){return false;}
	var controlNo=menuitem.id.split("-")[1];
	var action=menuitem.id.split("-")[2];
	if(!action || !controlNo){return false;}
	var uploader = window["DLIUploader" + controlNo];
	if(!uploader) {return false;}
	uploader.SwitchView(action);
	return;
}

//============ Attachment Recovery ===============================

function attachmentEditLogManager(incompEdits, targetUploader) {
	//attachments on this doc were edited but not saved.  First check if local files available
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	if ( ! Uploader ) return;
	
	//check for edit logs from Home db
	var tmpArr = incompEdits.split("; ");

	if(! Uploader.pluginEnabled){
		var tmpfileList = "";		
		for(x=0; x<tmpArr.length; x++) {
			tmpfileList +=  '"' + tmpArr[x] + '"' + '<br>';
		}
		window.top.Docova.Utils.messageBox({
				prompt:"A log file was found, indicating you have recoverable attachments, however the corresponding files could not be retrieved automatically without the use of the DOCOVA Plugin (which is either not installed or not currently running).<br><br>" +
							"Please examine the files in the following locations and manually recover any content you wish to keep.<br><br>" +
							"File(s) to recover:<br>" + tmpfileList + "<br><br>The recovery logs for these files will be cleared once you select Ok.", 
				title:"Edited Attachment Recovery", 
				icontype: 3,
				width: 600
		});		
			
		removeEditInPlaceLogs(); 			

		return;
	}

	var fileList = "";
	for(x=0; x<tmpArr.length; x++) {
		fileList += tmpArr[x] + "\r";
		if(Uploader.LocalFileExists(tmpArr[x])) {		
			dialogArguments.push(tmpArr[x])
		}
	}
	if(dialogArguments.length == 0) { //files not found on local PC
	
			window.top.Docova.Utils.messageBox({
				prompt:"A log file was found, indicating you have recoverable attachments, <br> however the corresponding files could not be found on your local PC." +
							"<br>The log file will be removed.  File(s) not found:<br>" + fileList, 
				title:"Edited Attachment Recovery", 
				icontype: 3,
				width: 400
			});
			removeEditInPlaceLogs(); 
	
		return;  
	}

	//prompt user for desired action
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/" + "dlgEditedAttachments?OpenForm";
	var arr = [window, dialogArguments]


	var dlgEditedAttachments = window.top.Docova.Utils.createDialog({
		 url: dlgUrl, 
		 id: "dlgEditedAttachments" ,
		 useiframe: true,
		 autoopen: true,
		 title: "Recover Unsaved, Edited Attachments",
		 width: "650",
		 height:"360",
		 sourcedocument: this,
		 buttons: [
    					{
    						  text: "Done",
      					icons: {
        						primary: "ui-icon-check"
      					},
      					click: function() {
      						var iwin = $(this).find("iframe")[0].contentWindow;
      						iwin.completeWizard();
      					     var result = returnValue;
        						dlgEditedAttachments.closeDialog();
        						var index = "";
							var replace = false;
						
							var resave = false;
	
							//process actions
							for(x=0; x<result.length; x++) {
								switch(result[x]) {
								case "C":  //add file as a copy
									var d = new Date();
									var fd = d.toDateString().replace(/ /g,"_");
									var fileNameArr =  GetFileName(dialogArguments[x]).split(".");	
									var filename = (fileNameArr[0] + " - Copy." + fileNameArr[1]).replace(/^.*[\\\/]/, '');
									Uploader.UploadFile(dialogArguments[x], filename);
									var dd = d.getDate();
									var mm = d.getMonth() + 1;
									if(dd<10) {
									    dd='0'+dd;
									} 
						
									if(mm<10) {
									    mm='0'+mm;
									}
									$('#OFileNames').val(filename);
									$('#tmpRenamedFiles').val(GetFileName(dialogArguments[x]) + ',' + filename);
									$('#OFileDates').val(mm + '/' + dd + '/' + d.getFullYear());
									resave = true;
									break;
								case "R":  //replace existing file
									index = Uploader.GetFileIndex(GetFileName(dialogArguments[x]));
									Uploader.RemoveFile(index);
									Uploader.UploadFile(dialogArguments[x], "", true);
									var filename = dialogArguments[x].replace(/^.*[\\\/]/, '');
									$('#tmpEditedFiles').val(filename);
									resave = true;
									break;
								case "D":  //delete local file
									DocovaExtensions.deleteFile(dialogArguments[x], true)			
								break;
							}
						}
	
						//submit to finalize changes		
						if(resave) {
							if(docInfo.HasMultiAttachmentSections == "1"){
								StoreMultiCtrlFileNames();
							}						
							Uploader.Submit({
										Navigate: false, 
										GetResults: false, 
										onOk: function(){
												Uploader.RefreshAfterSubmit();
												return false;
										}
							});
							
							
							
						
						}
	
						//remove EDIT logs
						removeEditInPlaceLogs(); 

     				 	}
    				}
  				]
		});

	}

function removeEditInPlaceLogs() {

	//--- remove any log files created to track the edit in place event ----------------------
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServicesExt?OpenAgent"
	var request="";
	
	request += "<Request>";
	request += "<Action>LOGFILEEDITEDDELETE</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Library>" + docInfo.LibraryKey + "</Library>";
	request += "<DocKey>" + docInfo.DocKey + "</DocKey>";
	request += "</Request>";
	
	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	httpObj.PostData(request, url)
}

function UploaderOnFileEdit(fileName, targetUploader) { 
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	Uploader.ResolvePathNames();
	var path = Uploader.EditFilePath;

	//--- create a log file to track the edit in place event ----------------------
	
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServicesExt?OpenAgent"
	var request="";
	
	request += "<Request>";
	request += "<Action>LOGFILEEDITED</Action>";
	request += "<FileName><![CDATA[" + fileName + "]]></FileName>";
	request += "<Path><![CDATA[" + path + "]]></Path>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<DocKey>" + docInfo.DocKey + "</DocKey>";
	request += "<NsfName>" + docInfo.NsfName + "</NsfName>";
	request += "<UNID>" + docInfo.DocID + "</UNID>";	
	request += "</Request>";
	
	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	httpObj.PostData(request, url)
}






//=====================================================================================

//----------- onbeforeadd handler for check in operation

function UploaderOnBeforeAdd(filePath, targetUploader){

	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	//----------------- check-in/out code----------------------
	var fileName = GetFileName(filePath);
	//----- Restricting file names that have semi-colons because in maintaining original dates of files, files must be tracked----
	//----- separately, with dates and fields in Notes/Domino can contain separators of commas, newlines, semi-colons and so on---
	//----- so least obstructive is semi-colons so we are using that. This allows us to run our @Replace formulas easier when----
	//----- putting together the original and modified dates of files together.
	if(fileName.indexOf(";") != -1){
		window.top.Docova.Utils.messageBox({prompt:"Filenames can not contain semi-colons.", title:"Adding Files", icontype: 3, width: 400})
		return false;
	}
	
	if(!IsValidFileExtension(fileName, Uploader)){
	
		window.top.Docova.Utils.messageBox({prompt:"Only files with " + Uploader.AllowedFileExtensions + " extensions are allowed.", icontype:3, title: "Adding Files", width:400})
		return false;
	}

	if(!docInfo.isFileCIAOEnabled) { 
		var res =  IsValidFileCount();
		if (res) {
			$('#MCSF_DLIUploader1').val(fileName);
		}
		return res;
	} // nothing to process
	
	
	var isCheckedOut = Uploader.IsFileCheckedOut(filePath)
	var fileExists= IsFileAttached(filePath, Uploader);

	if(!fileExists && !isCheckedOut) {
		var res = IsValidFileCount();
		if (res) {
			$('#MCSF_DLIUploader1').val(fileName);
		}
		return res;
	}

	if(fileExists)
	{
		var addOption ="";
		if(isCheckedOut && !checkin)
			{
			var fileInfo = Uploader.GetCheckedOutFileInfo(fileName);
			var msg = "The file " + fileName + " has been checked out by " + fileInfo[0] + " on " + fileInfo[1] + ".\r";
					msg += "You can replace this file once it is checked in.";
					var choice = window.top.Docova.Utils.messageBox({prompt:msg, icontype:3, title:"Add new file", width:400})
					checkin=false;
					return false;
			}
	}

return IsValidFileCount(Uploader);
}

//---------- before edit handler
function UploaderOnBeforeFileEdit(filePath, targetUploader){
  
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);	
	
	//----------- check-in/out code --------------------	
	if(docInfo.isFileCIAOEnabled) 
	{
		var fileName = GetFileName(filePath);		
		var isCheckedOut = Uploader.IsFileCheckedOut(filePath)
	
		if(isCheckedOut) //checked out by current user
		{
			var fileInfo = Uploader.GetCheckedOutFileInfo(fileName);
			if(docInfo.UserNameAB == fileInfo[0])
			{
				var msg = "The file " + fileName + " has been checked out by you on " + fileInfo[1] + " into " + fileInfo[2] + ".\r\r";
				msg += "You should be editing  your checked out copy then check it in when done.";
				
				window.top.Docova.Utils.messageBox({
					prompt: msg, 
					title:"Checkout Files", 
					icontype: 3,
					width:400,
					msgboxtype: 0
				});
				
				return false;
			}
			else //checked out by someone else			
			{
				var msg = "The file " + fileName + " has been checked out by " + fileInfo[0] + " on " + fileInfo[1] + ".\r";
				msg += "You can only download this file and edit it locally. Once the file is checked in you will be able to edit it in-place.";
				window.top.Docova.Utils.messageBox({
					prompt: msg, 
					title:"Checkout Files", 
					icontype: 3,
					width: 400,
					msgboxtype:0
				});
				return false;
			}
		}
	}

	//-- no check in out issues so we are about to edit the file
	if(Uploader.pluginEnabled){
		var temppath = "";
		if(UploaderDirOverride_Edit == ""){
			//-- set the download path to a temporary folder so that we don't mistakenly overwrite a file from another document
			temppath = window.top.DocovaExtensions.CreateTempFolder();
			if(temppath != "" && temppath.slice(-1) != "\\"){
				temppath += "\\";
			}
			UploaderDirOverride_Edit =  temppath;	
			if(temppath != ""){
				//-- store folder name so that it can be purged on successful close
				tmpSupportFolders.push(temppath);
			}				
		}else{
			temppath = UploaderDirOverride_Edit;
		}
 		if(temppath != ""){
  			Uploader.EditFilePath = temppath;
 		}  
	}	
	
	return true;
}

//---------- before launch handler
function UploaderOnBeforeLaunch(filePath, targetUploader){  
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	if(Uploader.pluginEnabled){
		var temppath = "";
		if(UploaderDirOverride_Launch == ""){
			//-- set the download path to a temporary folder so that we don't mistakenly overwrite a file from another document
			temppath = window.top.DocovaExtensions.CreateTempFolder();
			if(temppath != "" && temppath.slice(-1) != "\\"){
				temppath += "\\";
			}			
			UploaderDirOverride_Launch =  temppath;		
			if(temppath != ""){
				//-- store folder name so that it can be purged on successful close
				tmpSupportFolders.push(temppath);
			}					
		}else{
			temppath = UploaderDirOverride_Launch;
		}
 		if(temppath != ""){
  			Uploader.EditFilePath = temppath;
 		}  
	}	
	
	return true;
}


//--------- on file delete handler
function UploaderOnDelete(filePath, targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);

	//----------- check-in/out code --------------------	
	var fileName = GetFileName(filePath);
	
	if(!docInfo.isFileCIAOEnabled) {return true;} // nothing to process
	var isCheckedOut = Uploader.IsFileCheckedOut(filePath)
	
	if(isCheckedOut && !checkin)
	{
		var fileInfo = GetCheckedOutFileInfo(fileName);
		var msg = "The file " + fileName + " has been checked out by " + fileInfo[0] + " on " + fileInfo[1] + ".\r";
				msg += "You can not delete this file while it is checked out.";
				window.top.Docova.Utils.messageBox({
					prompt: msg, 
					title:"Checkout Files", 
					icontype: 3,
					width: 400,
					msgboxtype:0
				});
				return false;
			
	}
	return true;
}

function IsValidFileCount(targetUploader)
{
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	var availCount = GetAvailableFileCount(Uploader);
	if(availCount != 0) {return true;} // either unlimited or some space still available
	if(availCount == 0)
	{
		//-- check if a prior error was generated, if so don't prompt again
		if (! fileCountError){
			window.top.Docova.Utils.messageBox({prompt:"This document type can contain only " + Uploader.MaxFiles + " attachments(s).", title: "Error Adding Files", icontype: 3, width:400})
		}
		fileCountError = true; 
		return false;
	}
	fileCountError = false;  //-- clear any prior error that may have been logged
	return true;
}

//---- returns the number of files that can still be attached to the current document
function GetAvailableFileCount(targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);

	if(parseInt(Uploader.MaxFiles) == 0) {return -1;} //no limit
	
	var deletedCount = (Uploader.GetDeletedFileNames()=="")? 0 : Uploader.GetDeletedFileNames().split(",").length;
	var fileCount = Uploader.FileCount();
	var reminingCount = parseInt(Uploader.MaxFiles) + deletedCount - fileCount ;
	reminingCount = (reminingCount <0 )? 0 : reminingCount;
	return reminingCount;
}

//----------- predicate to check if the file is checked out
function IsFileCheckedOut(fileName)
{
	//--- make sure that we get just the file name part
	fileName = GetFileName(fileName);
	//--- get checked out files xml object
	var objCoFiles = DLIUploader1.xmlFileLog;
	//--- check if any of the selected files is already out, if so, complain and terminate
	var fileNode;
	fileNode = objCoFiles.selectSingleNode('cofiles/file[fnamelc="' + fileName.toLowerCase() + '"]');
	 return (fileNode != null)
}

//----------- predicate to check if the file is in the Uploader UI
function IsFileAttached(fileName, targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	return Uploader.IsFileAttached(fileName);
}

//----- checks if the attachment extension matches the document type settings---

function IsValidFileExtension(fileName, extensionListOrTargetUploader){	
	var allowedext = "";
	if(typeof extensionListOrTargetUploader == "string"){
		allowedext = extensionListOrTargetUploader;
	}else if(typeof extensionListOrTargetUploader == "object"){
		 allowedext = extensionListOrTargetUploader.AllowedFileExtensions;
	}
	
	if(allowedext == ""){
		return true;
	}
	
	var fileExtension=(fileName.indexOf(".") != -1)? fileName.split(".") : "";
	fileExtension = (fileExtension[fileExtension.length-1])
	var extensionArray=(extensionList)? extensionList.split(",") : docInfo.AllowedFileExtensions.split(",");
	for(var i=0; i<extensionArray.length; i++){
		if(fileExtension==extensionArray[i]) {return true;}
	}
	return false;
}

//----------- extracts thr file name from the file path
function GetFileName(filePath)
{
	var fileNameArray = filePath.split("\\");
	return fileNameArray[ fileNameArray.length - 1 ];
}

//--- returns the path to first local file with the specific extension
function GetFirstLocalFile(extension, controlNo)
{
	try{
			//----------------get uploader----------------------------------------------------------
			var Uploader = (controlNo)? window["DLIUploader" + controlNo] : DLIUploader1;
			var max = Uploader.FileCount();
			var fileName="";
			
			//------ locate first word doc file available locally-------------
			for (var a = 1; a <= max; a++){
				fileName = Uploader.LocalFileName(a);
				if (!Uploader.IsFileDeleted( a ) && fileName && ((fileName.toLowerCase().indexOf("." + extension.toLowerCase()) != -1) || (extension==""))){
					return fileName;
				}
			}
			return "";
		}
	catch (e){ 
			return "";
		}
}

// checks if the specific file type exists in the Uploader
function HasFileType(extension, controlNo)
{
	try{
			//----------------get uploader----------------------------------------------------------
			var Uploader = (controlNo)? window["DLIUploader" + controlNo] : DLIUploader1;
			var max = Uploader.FileCount();
			var fileName="";
			
			//------ check if the file with specified extension is in the uploader -------------
			for (var a = 1; a <= max; a++){
				fileName = Uploader.FileName(a);
				if (fileName.toLowerCase().indexOf("." + extension.toLowerCase()) != -1){
					return true;
				}
			}
			return false;
		}
	catch (e){ 
			return false;
		}
}

//------ deletes all files in the Uploader controls
function DeleteFiles(extension, controlNo)
{
	try{
			//----------------get  uploader----------------------------------------------------------
			var Uploader = (controlNo)? window["DLIUploader" + controlNo] : DLIUploader1;
			var max = Uploader.FileCount();
			var fileName="";
			
			//------ delete all files except the one matching extension -------------
			for (var a = 1; a <= max; a++){
				fileName = Uploader.FileName(a);
				if (!extension || (fileName.toLowerCase().indexOf("." + extension.toLowerCase()) != -1)){
					Uploader.RemoveFile(a)
				}
			}
			return true;
		}
	catch (e){ 
			return false;
		}
}
//---------- extracts the file info from the checked out xml
function GetCheckedOutFileInfo(fileName, targetUploader){

	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);

	var objCoFiles = Uploader.xmlFileLog;
	var fileNode;
	fileNode = objCoFiles.selectSingleNode('cofiles/file[fnamelc="' + fileName.toLowerCase() + '"]');
	if(fileNode == null) {return false;}
	var fileInfo = new Array();
	var infoNode=null;
	
	infoNode = fileNode.selectSingleNode("editor");
		fileInfo[0] = (infoNode == null)? "" : infoNode.text || infoNode.textContent;
		infoNode = fileNode.selectSingleNode("date");
		fileInfo[1] = (infoNode == null)? "" : infoNode.text || infoNode.textContent;	
		infoNode = fileNode.selectSingleNode("path");
		fileInfo[2] = (infoNode == null)? "" : infoNode.text || infoNode.textContent;
	

	
	return fileInfo;
}


//===================================================================
// scan control
//  Inputs: 
//        options - value data pairs consisting of the following;
//                  feedtype : integer :  -1=Don't Care, 0=Don't Use, 1=Use Feeder.
//                  filetype : string : pdf, tif (defaults to pdf)
//                  filename : string : name to use for scanned file (excluding extension) (defaults to document)
//                  showui : boolean : true to display scanning ui, false to hide (defaults to false)
//                  dpi : double : dpi count to use in scanning (defaults to 300)
//                  duplex: integer :  1 = Don't Care, 0 = Simplex (no duplex), 1 = Duplex (defaults to 0)
//                  colormode : integer : 0 = Black & White images, 1 = Grayscale images, 2 = RGB Color images, 24 bits/pixel, 
//                                                        3 = Indexed color images, 8 bits/pixel. 256 colors stored in a color table in the image,
//                                                        4 = CMY=Cyan-Magenta-Yellow, an exotic 24-bit/pixel format
//                                                        5 = CMYK=Cyan-Magenta-Yellow-Black. Exotic 32 bit/pixel format
//                  targetuploader : uploader object : uploader target to store scanned file to
//===================================================================
function EZTwainScanImagesEx(options){
    var defaultOptns = {
    	'feedtype' : 0,
    	'filetype' : "pdf",
    	'filename' : "document",
    	'showui' : false,
    	'dpi' : 300,
    	'duplex' : 0,
    	'colormode' : 2,
    	'targetuploader' : null
    }    
	var opts = $.extend({}, defaultOptns, options);
    
    var Uploader = (opts.targetUploader ? opts.targetUploader : (typeof DLIUploader1 != "undefined" ? DLIUploader1 : null));
    
	var outputFileName = opts.filename + "." + opts.filetype;

	//-- set the download path to a temporary folder so that we don't mistakenly overwrite a file from another document
	var temppath = "";
	temppath = window.top.DocovaExtensions.CreateTempFolder();
	if(temppath != "" && temppath.slice(-1) != "\\"){
		temppath += "\\";
	}
	
	var outputFilePath = temppath + opts.filename + "." + opts.filetype;
	var counter = 0;
	while(window.top.DocovaExtensions.LocalFileExists(outputFilePath)){
		counter = counter + 1;		     
		outputFilePath = temppath + opts.filename + counter.toString() + "." + opts.filetype;
	}   
   
	var codestr = "";
  	codestr += 'string result = @"{""runstatus"": ""FAILED""}";\n';
  	codestr += '/*-- start parameters passed in from calling routine --*/\n';
	codestr += 'string opts_outputfile = @"' + outputFilePath + '";\n';
	codestr += 'long opts_feedtype = ' + opts.feedtype.toString() + ';\n';		
	codestr += 'bool opts_showui = ' + opts.showui.toString() + ';\n';		
	codestr += 'double opts_dpi = ' + opts.dpi.toString() + ';\n';			
	codestr += 'long opts_scantype = ' + opts.colormode.toString() + ';\n';					
	codestr += 'long opts_duplex = ' + opts.duplex.toString() + ';\n';						
	codestr += 'string outputFile =  opts_outputfile;\n';	  		
	codestr += 'Type objTwainType = null;\n';
 	codestr += 'objTwainType = Type.GetTypeFromProgID("Dosadi.EZTwainX.1");\n';
 	codestr += 'if(objTwainType == null){ goto Cleanup;}\n';
 	codestr += 'Object EZTwain = null;\n';
	codestr += 'EZTwain = Activator.CreateInstance(objTwainType);\n';
	codestr += 'if (EZTwain == null){\n';
	codestr += '    result = @"{""runstatus"": ""FAILURE"", ""data"": ""Error creating EZTwain object.""}";\n';			
	codestr += '     goto Cleanup;\n';
	codestr += '}\n';
	codestr += 'EZTwain.GetType().InvokeMember("AppTitle", BindingFlags.SetProperty, null, EZTwain, new object[]{@"DocLogic"});\n';	
	codestr += 'EZTwain.GetType().InvokeMember("LicenseKey", BindingFlags.SetProperty, null, EZTwain, new object[]{@"1790080187"});\n';	
	codestr += 'EZTwain.GetType().InvokeMember("ScanFeeder", BindingFlags.SetProperty, null, EZTwain, new object[]{opts_feedtype});\n';	
	codestr += 'EZTwain.GetType().InvokeMember("ScanWithUI", BindingFlags.SetProperty, null, EZTwain, new object[]{opts_showui});\n';	
	codestr += 'EZTwain.GetType().InvokeMember("ScanDPI", BindingFlags.SetProperty, null, EZTwain, new object[]{opts_dpi});\n';	
	codestr += 'EZTwain.GetType().InvokeMember("ScanType", BindingFlags.SetProperty, null, EZTwain, new object[]{opts_scantype});\n';	
	codestr += 'EZTwain.GetType().InvokeMember("ScanDuplex", BindingFlags.SetProperty, null, EZTwain, new object[]{opts_duplex});\n';		
	codestr += 'try{\n';
	codestr += '   EZTwain.GetType().InvokeMember("AcquireMultipageFile", BindingFlags.InvokeMethod, null, EZTwain, new object[]{outputFile});\n';		
	codestr += '   if ( File.Exists(outputFile)){\n';
	codestr += '          FileInfo finfo = new FileInfo(outputFile);\n';
    codestr += '          result = @"{""runstatus"": ""SUCCESS"", ""data"": """ + System.Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes(outputFile + @"*" + finfo.LastWriteTime.ToShortDateString() + @"*" + finfo.Length.ToString())) + @"""}";\n';		
    codestr += '          finfo = null;\n';
	codestr += '    }\n';
	codestr += '}catch(Exception e ){\n';
	codestr += '    result = @"{""runstatus"": ""FAILURE"", ""data"": """ + e.ToString() + @"""}";\n';		
	codestr += '};\n';
	codestr += 'Cleanup:\n';
	codestr += '    EZTwain = null;\n';
	codestr += '    objTwainType = null;\n';		
	codestr += 'return result;\n';

	var retval = window.top.DocovaExtensions.executeCode(codestr, false, true);

	if(retval.status == "SUCCESS"){
		try{
			//-- try to parse the json data being returned into an object
			var tempjson = JSON.parse(retval.results);
			if(tempjson.runstatus && tempjson.runstatus == "SUCCESS"){
				tempjson.data = atob(tempjson.data);		
				var fileinfo = tempjson.data;
        		 	var tmpArr = fileinfo.split("*");
        		 	Uploader.UploadFile(tmpArr[0], "", tmpArr[1], tmpArr[2], "");			
        		 }else{
				window.top.Docova.Utils.messageBox({prompt: "An error has occurred Acquiring Image from Scanner.", title: "Scanner Acquire", width: 400});			        		 
        		 }
		}catch(e){
			window.top.Docova.Utils.messageBox({prompt: "An error has occurred Acquiring Image from Scanner.", title: "Scanner Acquire", width: 400});			
		}
	}else{
		window.top.Docova.Utils.messageBox({prompt: "An error has occurred Acquiring Image from Scanner. Error [" + retval.error + "]", title: "Scanner Acquire", width: 400});
	}	
}

function EZTwainScanImages(targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
 	if(! window.top.Docova.IsPluginAlive){
		window.top.Docova.Utils.messageBox({prompt: "DOCOVA Plugin is not running.  This functionality requires the use of the DOCOVA Plugin.", title: "Acquire Image from Scanner", width: 400});
		return result;
	}


	var dlgUrl ="/" + NsfName + "/" + "dlgSelectScanType?OpenForm";
	
	var dlgSelectScanType = window.top.Docova.Utils.createDialog({
		 url: dlgUrl, 
		 id: "dlgSelectScanType" ,
		 useiframe: true,
		 autoopen: true,
		 title: "Scan Type",
		 width: "450",
		 height:"250",
		 sourcedocument: this,
		 buttons: [
			 		{
			 			text: "OK",
			 			icons: {
			 				primary: "ui-icon-check"
			 			},
			 			click: function() {
			 				var iwin = $(this).find("iframe")[0].contentWindow;
			 				var scantype = Docova.Utils.getField("ScanMethod", iwin.document );
							var filetype = Docova.Utils.getField("FileType", iwin.document );      						
      						if(scantype == ""){
								window.top.Docova.Utils.messageBox({prompt: "Please select the type of scanner.", title: "Acquire Image from Scanner", width: 400});      						
      							return;
      						}
      						var scanopts = {'feedtype' : scantype, 'filetype' : filetype, 'targetuploader' : Uploader};
    						window.top.Docova.Utils.showProgressMessage("Acquiring image from scanner. Please wait...");							
							setTimeout(function(){
	      						EZTwainScanImagesEx(scanopts);
	      						window.top.Docova.Utils.hideProgressMessage();
	      						dlgSelectScanType.closeDialog();      					       								
							}, 2000);				
			 			}
     				},
     				{
     					text: "Cancel",
     					icons: {
        						primary: "ui-icon-cancel"
      					},
      					click: function() {
      						dlgSelectScanType.closeDialog();
      					}
      				}
  				]
		});
	
}



//===================================================================
// template handling functions

function GetTemplateInfo()
{
	if(docInfo.TemplateList == "") {return false;}
	var templateInfo = new Array();
	
	if(docInfo.TemplateList.split(";").length==1)
		{
			templateInfo[0] = docInfo.TemplateList; //template id
			templateInfo[1] = docInfo.TemplateFileList; //template file name
			templateInfo[2] = docInfo.TemplateNameList; //template name
			templateInfo[3] = docInfo.TemplateVersionList; //template version
		}
	else
		{
			var dispTemplateList=""
			var selValue = thingFactory.SelectKeyword(docInfo.TemplateNameList, ";", "Select template", "", false, false, false, 450 );
			if(selValue == "") {return false;}

			var nArray= docInfo.TemplateNameList.split(";");
			var iArray= docInfo.TemplateList.split(";");
			var fArray= docInfo.TemplateFileList.split(";");
			var vArray= docInfo.TemplateVersionList.split(";");
			
			for(var i=0; i<nArray.length; i++)
				{
   					if (nArray[i] == selValue){ break; }
				}
			templateInfo[0] = iArray[i]; //template id
			templateInfo[1] = fArray[i]; //template file name
			templateInfo[2] = nArray[i]; //template name
			templateInfo[3] = vArray[i]; //template version			
		}
	return templateInfo;
}

function GetTemplateUrl(templateInfo)
{
	if(!templateInfo) {return false;}
	var url =  docInfo.ServerUrl + "/" + docInfo.PortalNsfName ;
	url += "/luFileTemplatesByKey/" + templateInfo[0] + '/'+ templateInfo[1];
	return url;
}

function DownloadAndAttach(fileUrl, newFileName, targetUploader)
{
	if(!fileUrl) {return false;}
	
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);

	if(!Uploader.pluginEnabled){return false;}
	
	//-- set the download path to a temporary folder so that we don't mistakenly overwrite a file from another document
	var temppath = "";
	if(UploaderDirOverride_Download == ""){
		//-- set the download path to a temporary folder so that we don't mistakenly overwrite a file from another document
		temppath = window.top.DocovaExtensions.CreateTempFolder();
		if(temppath != "" && temppath.slice(-1) != "\\"){
			temppath += "\\";
		}
		UploaderDirOverride_Download =  temppath;					
		if(temppath != ""){
			//-- store folder name so that it can be purged on successful close
			tmpSupportFolders.push(temppath);
		}				
	}else{
		temppath = UploaderDirOverride_Download;
	}
	
 	if(temppath != ""){
  		Uploader.DownloadPath = temppath;
 	}else{	
		Uploader.DownloadPath = "0"; 	
 		Uploader.ResolvePathNames();				
	}
	
	var fileNameElements = fileUrl.split("/");
	var localfilename = Uploader.DownloadPath;
	if(localfilename.slice(-1) != "\\"){
	    localfilename += "\\";
	}
	if(newFileName != undefined && newFileName != null){
		localfilename += newFileName;
	}else{
	 	localfilename += fileNameElements[fileNameElements.length-1];
	}
	
	var newFileURL=fileUrl.replace(fileNameElements[fileNameElements.length-1],escape(fileNameElements[fileNameElements.length-1]));
	if(Uploader.DownloadFileFromURL(newFileURL, localfilename, false, false))
	{
		 if(Uploader.UploadFile(localfilename))
		 {
		 	return localfilename;
		 }
	}
	return false;
}


function PopulateBookmarks(localfilename, fieldList, keepbookmarks, valueList, macrotorun){		
	return window.top.DocovaExtensions.PopulateBookmarks(localfilename, fieldList, keepbookmarks, valueList, macrotorun, document);
}

function ReadExcelData(excelFilePath, xSheet, fieldList, cellList, macroName)
{
	return window.top.DocovaExtensions.ReadExcelData(excelFilePath, xSheet, fieldList, cellList, macroName, document);
}//--end ReadExcelData


function WriteExcelData(excelFilePath, xSheet, fieldList, cellList, macroName)
{
	return window.top.DocovaExtensions.WriteExcelData(excelFilePath, xSheet, fieldList, cellList, macroName, document);
}//--end WriteExcelData


function AttachTemplate(){
		var templateInfo = GetTemplateInfo();
		var templateUrl = GetTemplateUrl(templateInfo);
		var localfilename = DownloadAndAttach(templateUrl);
}


//------------------------------------------------------------------------------
// used to get files from archive or external attachment storage
//------------------------------------------------------------------------------

function UploaderRedirectDownload(fileName, targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
//------switch url to alternate -------------
//	if(docInfo.Mode=="preview"){
//		document.all.DLIUploader1.LaunchUrl=docInfo.DataDocUrl + "/$file/" + escape(fileName) + "?OpenElement&Login";
//	}
	var fileNameElements = fileName.split("\\");
	var newFileName = fileNameElements[fileNameElements.length-1];
	if (docInfo.DocID) {
		Uploader.LaunchUrl=docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + escape(newFileName) + "?OpenElement&doc_id=" + docInfo.DocID;
	}
	return(true);
}

//-----------------------------------------------------------------------------------------
// sets the display of checked out file list
//------------------------------------------------------------------------------------------

function SetCheckedOutListDisplay()
{

	var xmlObject = xmlFileLog.XMLDocument;
	if(xmlObject.selectNodes("cofiles/file").length>0){
	
		//there are checked out fiiles..regardless of the document types ability to allow checkout/in we enable
		//it explicitly here.  This can happen when a user with nonactivex uploader edits a file
		//in which case the file is infact "checked out"
		if ( ! docInfo.isFileCIAOEnabled ){
			docInfo.isFileCIAOEnabled = 1;
			$("#btn-1-checkout").show();
			$("#btn-1-checkin").show();
		}
		doc.divCheckedOutFileList.style.display="";
		}
	else{
		doc.divCheckedOutFileList.style.display="none";
		}
}

//------ instantiates ole objects-----

function InitializeOLEObject(objType)
{
	var currentObject="";
	var oleObj=null;
	try{
		if(objType=="WORD"){
			currentObject="MS Word";
			oleObj = thingFactory.NewObject("Word.Application");
			if (oleObj == null){
				alert("Unable to launch Microsoft Word object.");
			}
		}
		else if(objType=="FSO"){
			currentObject="File System";
			oleObj = thingFactory.NewObject("Scripting.FileSystemObject");
			if (oleObj == null){
				alert("Unable to access file system object.");
			}
		}
		else if(objType=="IE"){
			currentObject="Internet Explorer";
			oleObj = thingFactory.NewObject("InternetExplorer.Application");
			if (oleObj == null){
				alert("Unable to access Internet Explorer object.");
			}
		}
		else if(objType=="PROJECT"){
			currentObject="Microsoft Project";
			oleObj = thingFactory.NewObject("MSProject.Application");
			if (oleObj == null){
				alert("Unable to access Microsoft Project object.");
			}
		}
		else if(objType=="PPT"){
			currentObject="MS Power Point";
			oleObj = thingFactory.NewObject("PowerPoint.Application");
			if (oleObj == null){
				alert("Unable to access Power Point object.");
			}
		}	
		else if(objType=="EXCEL"){
			currentObject="MS Excel";
			oleObj = thingFactory.NewObject("Excel.Application");
			if (oleObj == null){
				alert("Unable to access Excel object.");
			}
		}
	}
	catch(e) {
		alert("Could not initialize " + currentObject + " object.\rError: " + e.message);
		return false;
	}
	return oleObj;
}

//------ logs that a user Viewed a file -----
function LogFileViewed(fileName){
	if ( docInfo.isNewDoc ) return ( false);
	if(!docInfo.isFileViewLoggingOn) return(false);
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServicesExt?OpenAgent"
	var request = ""
	request += "<Request>"
	request += "<Action>LOGVIEWED</Action>"
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	request += "<Unid>" + docInfo.DocID + "</Unid>"
	request += "<FileName>" + "<![CDATA[" + fileName + "]]>" + "</FileName>"
	request += "</Request>"
	
	var httpObj = new objHTTP()
	if(httpObj.PostData(request, url)){
		if(httpObj.status!="OK"){
			alert("Could not log that file was viewed. Exiting.")
			return(false)
		}
		return(true)
	}
}

//------ logs that a user Downloaded a file -----
function LogFileDownloaded(fileName){
	if ( docInfo.isNewDoc ) return ( false);
	if(!docInfo.isFileDownloadLoggingOn) return(false);
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServicesExt?OpenAgent"
	var request = ""
	request += "<Request>"
	request += "<Action>LOGDOWNLOADED</Action>"
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	request += "<Unid>" + docInfo.DocID + "</Unid>"
	request += "<FileName>" + "<![CDATA[" + fileName + "]]>" + "</FileName>"
	request += "</Request>"
	var httpObj = new objHTTP()
	if(httpObj.PostData(request, url)){
		if(httpObj.status!="OK"){
			alert("Could not log that file was downloaded. Exiting.")
			return(false)
		}
		return(true)
	}
}

//----- Checks to see if a file is in use by another application to avoid error msg in Save and Close -----
function FileOpenInApp(targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	var filecount = Uploader.FileCount();
	var localfilenamelist = "";
	var f, fso
	var simpleFilename;
	var assocApp;
	var fileExtension;
	var err_FileInUseMsg = ""

	//fso = thingFactory.NewObject("Scripting.FileSystemObject");
	var surl = "https://localhost.docova.com:50450/docova?";
	surl += "action=execCode";
	surl += "&fileurl=" + encodeURIComponent(location.href);
	surl += "&sessioncookie=" + readCookie("DomAuthSessId");
	
assocApp = ""
for(var i =1; i<=filecount; i++){
	if(DLIUploader.FileLocation(i) == "local"){
		if ( localfilenamelist != "" )
			localfilenamelist += "*"
		localfilenamelist += DLIUploader.FileName(i);
	}
}

	var mystr = 'string fullname = @"' + localfilenamelist +'";'
           mystr +='string[] filearr = fullname.Split(\'*\');'
           mystr +='Type objType = Type.GetTypeFromProgID("Scripting.FileSystemObject");'
           mystr += 'dynamic fso = Activator.CreateInstance(objType);'
           mystr += 'string errorstr = "";'
           mystr +=' for (int j = 0; j < filearr.Length; j++)'
           mystr+= '{'
            mystr +=  'try{'
              mystr += 'dynamic f = fso.GetFile(filearr[j]);'
                    mystr+= 'f.Move(filearr[j]);'
				mystr +=  '}'
                	mystr += 'catch (Exception err){'
                    mystr += 'string flname = Path.GetFileName(filearr[j]);'
                    mystr += 'errorstr = "The " + flname + " file is currently open in its associated application.  Please save and close before trying to Save this document!";'
                    mystr += 'break;'
               mystr += '}'
           mystr += '}'
           mystr += 'fso =null; return errorstr;'  
	
	  var sdata =  {
    	    code:     btoa(encodeURIComponent(mystr))
    	};

     $.ajax({
                url: surl,
                type: "POST",
                data: sdata,
                dataType: "json"
            }).done(function(data) {
            	var org = atob(data.results)
        			if ( org == "" )
        				return false;
        			else{
        				alert ( org );
        				return true;
        			}
			})
			 .fail(function() {
				 
			 });
		
		
		
		
}

function StoreMultiCtrlFileNames(){
	var ctrlno = 1;
	var uploader = window["DLIUploader" + ctrlno];

	while ( uploader ){
		var filelist = uploader.GetAllFileNames("*");
		var fieldname = (uploader.FieldName && uploader.FieldName !== "" ? uploader.FieldName : ("MCSF_DLIUploader" + ctrlno.toString()));
		$("#" + fieldname).val(filelist);
	
		ctrlno++;
		uploader = window["DLIUploader" + ctrlno];
	}

//-----------------------------------------
	return;
}




function ProcessEntrySubmenuAction(action, objid)
{
	var ctrlno = 1;
	var uploader = window["DLIUploader" + ctrlno];
	
	while ( uploader ){
		if ( uploader.getDivId() == objid) break;
		ctrlno++;
		uploader = window["DLIUploader" + ctrlno];
	}
	
	
	switch (action )
	{
		case "view":
			uploader.ViewSelectedFiles();
			break;
		case "delete":
			uploader.DeleteSelectedFiles();
			break;
		case "undelete":
			uploader.UndeleteSelectedFiles();
			break;
		case "download":
			uploader.DownloadSelectedFiles();
			break;
		case "checkout":
			 CheckOutSelectedFilesSTD();
			 break;
		case "checkin":
			CheckInSelectedFilesSTD();
			break;
		case "cancelcheckout":
			CancelCheckoutSelectedFilesSTD();
			break;
		case "edit":
			uploader.EditSelectedFiles();
			break;
		case "rename":
			uploader.RenameSelectedFiles();
			break;
		case "paste":
			uploader.PasteFromClipboard();
	}
	
	
}

function CancelCheckout(filename, targetUploader){

	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);

   window.top.Docova.Utils.messageBox({
		 title: "Cancel Checkout",
		 msgboxtype: 1,
		 prompt : "You are about to cancel checkout for the file '" +filename + "'. Are you sure?",
		  width: 500,
		 onOk: function()
		{ 
   				Uploader.SendCheckinRequest (filename, "CANCEL" );
   		}
   	});
}

function CheckInCurrentFile(filename, targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
   window.top.Docova.Utils.messageBox({
		 title: "Check in file",
		 msgboxtype: 1,
		 prompt : "You are about to check in changes to the file '" +filename + "'.  Do you want to continue?",
		  width: 500,
		 onOk: function()
		{ 
	   		Uploader.CheckInFile( filename);
   				
   		}
   	});
}

//--------------------- performs a checkout on the selected files
function CheckOutSelectedFilesSTD(targetUploader){
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	if(!CanModifyDocument()){return false;}
	if(docInfo.EnableVersions == "true" && docInfo.isWorkflowCompleted == "true")
	{
		alert("This document has version control and its workflow has completed. \nYou can not check-out files.  Please create a new version if required.");
		return false;
	}
	
	// ----- Get the selected files from uploader, this is for display purposes
	var selectedFiles = Uploader.GetSelectedFileNames("*");
	if ( selectedFiles != "" )
		selectedFiles = selectedFiles.split("*");

	if(selectedFiles.length== 0 ){
		window.top.Docova.Utils.MessageBox({prompt:"Please select the file(s) you would like to check out.", title: "No File(s) Selected" })
		return(false)
	}
	
	
	//--- check if any of the selected files is already out, if so, complain and terminate
	var flname = selectedFiles[0];
	if(Uploader.IsFileCheckedOut(flname))
	{
				var fileInfo = Uploader.GetCheckedOutFileInfo(flname);
				var userMsg = (fileInfo[0] == docInfo.UserNameAB)? "you" : fileInfo[0];
				window.top.Docova.Utils.MessageBox({prompt: "File " + flname + " is currently checked out by " + userMsg + ". Please see document log for more details.", title:"Check out files"});
				return(false)
	}
			
	if ( Uploader.IsFileNew(flname) ){
				window.top.Docova.Utils.MessageBox({prompt: "File <b>'" + flname + "'</b> is new has not been submitted.  File can not be checked out!", title:"Check out files", icontype: 3});
				return false;
	}

	
	window.top.Docova.Utils.messageBox({
		 title: "Checkout File",
		 msgboxtype: 1,
		 prompt : "You are about to Check-out the following files<br>" + selectedFiles.join(", ") + "<br>Are you sure?",
		  width: 500,
		 onOk: function()
		{ 
			
			var filesXml = "";
			var fileSelc =flname;
			Uploader.DownloadFile({
					filename: flname,
					onSuccess: function(fullpath) 
					{
							filesXml += "<file>";
							filesXml += "<filename>" + "<![CDATA[" + flname + "]]>" + "</filename>";
							filesXml += "<path>" + "<![CDATA[" + fullpath + "]]>" + "</path>";
							filesXml += "</file>";
							//--- send the request to update backend document ---
							var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
							var request = ""
	
							request += "<Request>"
							request += "<Action>CHECKOUT</Action>"
							request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
							request += "<Unid>" + docInfo.DocID + "</Unid>"
							request += "<filelist>" + filesXml + "</filelist>"
							request += "</Request>"
	
		
						  $.ajax({
									'type' : "POST",
									'url' : url,
									'processData' : false,
									'data': request
									
								})
								.done(function(data) {
									Uploader.SendCIAORequestToDOE({
										action:"checkOutFile", 
										fullpath: fullpath, 
										onSuccess: function(){
											Uploader.RefreshCheckoutXml();
										}
									});
									
								})
		
					}  //onSuccess
				
				}); //download file
		}  //on ok
	});  //dialogbox
	
	

	return true;
}


//displays dialog allowing user to select one or more files to check in
function CheckInSelectedFilesSTD(targetUploader){
	if(!CanModifyDocument()){return false;}
	
	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	// ----- Get the selected files from uploader, this is for display purposes
	var selectedFiles = Uploader.GetSelectedFileNames("*");
	if ( selectedFiles != "" )
		selectedFiles = selectedFiles.split("*");

	if(selectedFiles.length== 0 ){
		window.top.Docova.Utils.messageBox({prompt:"Please select the file(s) you would like to check out.", title: "No File(s) Selected", width:400 })
		return(false)
	}
	
	
	
	//--- check if any of the selected files is already out, if so, complain and terminate
	for(var i=0; i<selectedFiles.length;i++)
	{
		var flname = selectedFiles[i];
		CheckInCurrentFile(flname);
	}
	
}

function CancelCheckoutSelectedFilesSTD(targetUploader){
	if(!CanModifyDocument()){return false;}

	var Uploader = (targetUploader && targetUploader.FileCtrlId ? targetUploader : DLIUploader1);
	
	// ----- Get the selected files from uploader, this is for display purposes
	var selectedFiles = Uploader.GetSelectedFileNames("*");
	if ( selectedFiles != "" )
		selectedFiles = selectedFiles.split("*");

	if(selectedFiles.length== 0 ){
		window.top.Docova.Utils.messageBox({prompt:"Please select the file(s) you would like to check out.", title: "No File(s) Selected" })
		return(false)
	}
	
	
	
	//--- check if any of the selected files is already out, if so, complain and terminate
	for(var i=0; i<selectedFiles.length;i++)
	{
		var flname = selectedFiles[i];
		CancelCheckout(flname);
	}
}

function initUploaders(uploaderconfigs){
	  var result = false;
	  if(typeof uploaderconfigs === 'undefined' || uploaderconfigs === null){
	  	return result;
	  }
	  
	  var containerdivname = "uploaderSTD";
	  var attachdivname = "attachDisplay";
	  var uploaderobjname = "DLIUploader";
	  
	  var sourcehtml = jQuery("#UPLOADER_TEMPLATE").html(); 
	 
	  var counter = 0; 
	  for(var i=0; i<uploaderconfigs.length; i++){
	  		if(typeof uploaderconfigs == "object"){
	      		var randomid = uploaderconfigs[i].divId.replace(attachdivname +"_", "");
				$targetdiv = jQuery("#" + containerdivname + "_" + randomid);
				
				if($targetdiv.length > 0){
					counter ++;
					var objname = uploaderobjname + counter.toString();		
					var newdivid = containerdivname + counter.toString();
					$targetdiv.attr("id", newdivid);											
					var newhtml = sourcehtml.replace(/_TEMPLATE_/g, counter.toString());
					$targetdiv.html(newhtml);
					uploaderconfigs[i].divId = attachdivname + counter.toString();
					uploaderconfigs[i].IsChildUploader = (counter > 1);
					
		      		if(typeof window[objname] == "undefined" || window[objname] === null){
						window[objname] = new Docova.Uploader(uploaderconfigs[i]);
						window[objname].init();					
					}else{
		      			 window[objname].reloadSettings({
		      			 	"divId" : uploaderconfigs[i].divId,
		      			 	"IsChldUploader" : uploaderconfigs[i].IsChildUploader
						});	
						//window[objname].reloadconfig(uploaderconfigs[i]);
					}				
								
					//---------------------------Header Buttons -------------------------------------
					//-----attach button
					$( "#btn-" + counter.toString() + "-attach-std" )
						.button({
						text:true,
						label: "Attach a file.",
						icons: {primary: "far fa-paperclip"}
					})
					.click(function( event ) {
						event.preventDefault();
						var id = jQuery(this).attr("id");
						var index = id.match(/^btn-([0-9]+)-attach-std$/)[1];
						window["DLIUploader" + index].AddFile();
					});	
					if(uploaderconfigs[i].AllowAdd == "0" || uploaderconfigs[i].HideButtons == "1"){
						$( "#btn-" + counter.toString() +"-attach-std" ).hide();
					}

					//Scan  button
					$( "#btn-" + counter.toString() +"-scan" )
					.button({
						text:true,
						label: "Scan a file.",
						icons: {primary: "far fa-scanner"}
					})
					.click(function( event ) {
						event.preventDefault();
						var id = jQuery(this).attr("id");
						var index = id.match(/^btn-([0-9]+)-scan$/)[1];		
							
						window["DLIUploader" + index].ScanFile();
					});
					if(uploaderconfigs[i].AllowScan != "1" || uploaderconfigs[i].HideButtons == "1"){
						$( "#btn-" + counter.toString() +"-scan" ).hide();
					}

					//-----list button
					$( "#btn-" + counter.toString() + "-liststd" )
						.button({
							text:false,
							label: "Views.",
							icons: {primary: "fas fa-caret-down"}
					})
					.click(function( event ) {
						event.preventDefault();
						var menu = $("#" + $(this).attr("menu"));
						menu.show().position({
							my: "left top",
							at: "left bottom",
							of: this
						});
						$(document).one("click", function(){
							menu.hide();
						});
						
						return false;
					});	
					
					//---- format menu element
					$("#listmenu" + counter.toString()).menu();
		
					//-----list dropdown menu click events
					//-----Icons view
					$("#menu-" + counter.toString() +"-I").click(function(event){
						event.preventDefault();
						UmenuClick(this, "STD")
					});
		
					//-----Report or "Details" view
					$("#menu-" +  counter.toString() + "-R").click(function(event){
						event.preventDefault();
						UmenuClick(this, "STD")
					});				
					
					$targetdiv.show();
				}
			}    	      	
		}	

		//-- update docInfo parameters based on the number of attachment areas encountered	
		info.HasAttachmentsSection = (counter > 0 ? "1" : "0");
	  	info.HasMultiAttachmentSections = (counter > 0 ? "1" : "0");
		
	}