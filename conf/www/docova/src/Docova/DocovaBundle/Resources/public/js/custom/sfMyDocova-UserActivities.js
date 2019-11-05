var activitiesView = null; //embedded view object
jQuery(document).ready(function(){
	Initactivity();
});

function Initactivity() {
	activitiesView = new EmbViewObject;
	activitiesView.embViewID = "divactivity";	
	activitiesView.captureEventDiv = "divactivityCapture";
	activitiesView.perspectiveID = "xmlactivity";
	activitiesView.imgPath = docInfo.ImagesPath;
	activitiesView.srcView = "luUserActivityIncomplete";
	activitiesView.suffix = "";
	activitiesView.category = docInfo.UserNameAB;
	activitiesView.onRowClick = "activityOnClick";
	activitiesView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+activitiesView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+activitiesView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}	
	activitiesView.fixedHeight = tempheight.toString() + 'px';		
	activitiesView.idPrefix = "pendingactivity";
	activitiesView.baseUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/";
	activitiesView.usejQuery = false;
	activitiesView.EmbViewInitialize();
}

function activityOnClick(entryObj, docUrl) {
	//open in a tab in the main window
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
			alert("The selected document or folder could not be found.");
		}
	});
}