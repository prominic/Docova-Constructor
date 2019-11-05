var wfItemsView = null; //embedded view object

$(document).ready(function(){   
	Initwfitem();
});


function Initwfitem() { 
	wfItemsView = new EmbViewObject;
	wfItemsView.embViewID = "divwfItem";	
	wfItemsView.captureEventDiv = "divwfItemCapture";
	wfItemsView.perspectiveID = "xmlwfItem";
	wfItemsView.imgPath = docInfo.ImagesPath;
	wfItemsView.lookupURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/xViewData.xml?view=workflowTasks.xml&lkey=" + encodeURIComponent(docInfo.UserNameAB) + "wfItem";
	wfItemsView.suffix = "";
	wfItemsView.category = docInfo.UserNameAB;
	wfItemsView.onRowClick = "wfItemOnClick";
	wfItemsView.idPrefix = "pendingwfItem";
	wfItemsView.usejQuery = false;
	wfItemsView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+wfItemsView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+wfItemsView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}		
	wfItemsView.fixedHeight = tempheight.toString() + 'px';		
	wfItemsView.EmbViewInitialize();
}

function wfItemOnClick(entryObj, docUrl) {
	//open in a tab in the main window
	//OpenDocumentEmbView(entryObj);   //function in wMyDocova form
	OpenDocument(entryObj); // function on wMyDocova form
}