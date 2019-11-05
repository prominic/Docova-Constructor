var editedView = null; //embedded view object

$(document).ready(function(){
	Initedited();
})

function Initedited() {
	editedView = new EmbViewObject;
	editedView.embViewID = "divedited";	
	editedView.captureEventDiv = "diveditedCapture";
	editedView.perspectiveID = "xmledited";
	editedView.imgPath = docInfo.ImagesPath;
	editedView.lookupURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/xUserWatchlists.xml?ReadForm&listid=2&format=ADOEMBVIEW&ukey=RE";
	editedView.suffix = "RecEdited";
	editedView.category = "";
	editedView.onRowClick = "OpenRecentlyEditedDoc";
	editedView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+editedView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+editedView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}
	editedView.fixedHeight = tempheight.toString() + 'px';	
	editedView.idPrefix = "recentlyEdited";
	editedView.usejQuery = false;
	editedView.EmbViewInitialize();
}

function OpenRecentlyEditedDoc(entryObj, docUrl) {
	OpenDocument(entryObj); // function on wMyDocova form
}