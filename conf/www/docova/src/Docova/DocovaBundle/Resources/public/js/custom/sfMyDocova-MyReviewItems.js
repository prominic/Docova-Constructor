var ReviewItemsView = null; //embedded view object

jQuery(document).ready(function(){
	Initreviewitem();
});

function Initreviewitem() {    
	ReviewItemsView = new EmbViewObject;
	ReviewItemsView.embViewID = "divReviewItem";	
	ReviewItemsView.captureEventDiv = "divReviewItemCapture";
	ReviewItemsView.perspectiveID = "xmlReviewItem";
	ReviewItemsView.imgPath = docInfo.ImagesPath;
	ReviewItemsView.srcView = "xmlReviewItems.xml";
	ReviewItemsView.suffix = "";
	ReviewItemsView.category = docInfo.UserNameAB;
	ReviewItemsView.onRowClick = "ReviewItemOnClick";
	ReviewItemsView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+ReviewItemsView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+ReviewItemsView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}	
	ReviewItemsView.fixedHeight = tempheight.toString() + 'px';	
	ReviewItemsView.idPrefix = "pendingReviewItem";
	ReviewItemsView.usejQuery = false;
	ReviewItemsView.EmbViewInitialize();
}

function ReviewItemOnClick(entryObj, docUrl) {
	//open in a tab in the main window
	//OpenDocumentEmbView(entryObj);  //function in wMyDocova form
	OpenDocument(entryObj);
}