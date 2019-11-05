var checkedOutView = null; //embedded view object

jQuery(document).ready(function(){
	InitCheckedOut();
});

function InitCheckedOut() {   
	checkedOutView = new EmbViewObject;
	checkedOutView.embViewID = "divCheckedOut";	
	checkedOutView.captureEventDiv = "divCheckedOutCapture";
	checkedOutView.perspectiveID = "xmlCheckedOut";
	checkedOutView.srcView = "luCIAOLogsByUser";
	checkedOutView.suffix = "";
	checkedOutView.imgPath = docInfo.ImagesPath;
	checkedOutView.category = docInfo.UserNameAB
	checkedOutView.onRowClick = "CheckedOutOnClick";
	checkedOutView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+checkedOutView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+checkedOutView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}	
	checkedOutView.fixedHeight = tempheight.toString() + 'px';	
	checkedOutView.idPrefix = "CheckedOut";
	checkedOutView.usejQuery = false;
	checkedOutView.EmbViewInitialize();
}

function CheckedOutOnClick(entryObj, docUrl) {
	//open in a tab in the main window
	OpenCIAODocument(entryObj);
}

function OpenCIAODocument(entryObj){
	var libID = entryObj.GetElementValue("librarykey");
	var libPath = GetLibraryPath(libID);
	var folderID = entryObj.GetElementValue("folderid");
	var docKey = entryObj.GetElementValue("parentdocid");
	var docTitle = entryObj.GetElementValue("Subject");

	var ws = Docova.getUIWorkspace(document);
	var fraToolbarWin = ws.getDocovaFrame("fraToolbar", "window");
	fraToolbarWin.OpenLibraries();
	var fraFolderWin = ws.getDocovaFrame("foldercontrol", "window");
	var folderControl = fraFolderWin.DLITFolderView;

	folderControl.OpenFolder(folderID, false, libID, function(result){
		if(result){
			var newDocUrl = docInfo.ServerUrl + libPath + "ReadDocument/" + docKey + "?OpenDocument&ParentUNID="  + folderID;
			var fraTabbedTableWin = ws.getDocovaFrame("fraTabbedTable", "window");
			fraTabbedTableWin.objTabBar.CreateTab(docTitle, docKey, "D", newDocUrl);
		}else{
			alert("The selected document folder could not be found.");
		}
	});
}