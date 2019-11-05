var userFavoritesView = null; //embedded view object

jQuery(document).ready(function(){
	InitUserFavorites();
});

function InitUserFavorites() {	
	userFavoritesView = new EmbViewObject;
	userFavoritesView.embViewID = "divUserFavorites";	
	userFavoritesView.captureEventDiv = "divUserFavoritesCapture";
	userFavoritesView.perspectiveID = "xmlUserFavorites";
	userFavoritesView.imgPath = docInfo.ImagesPath;
	userFavoritesView.lookupURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/xUserWatchlists.xml?ReadForm&listid=6&format=ADOEMBVIEW&ukey=F";
	userFavoritesView.suffix = "";
	userFavoritesView.category = "";
	userFavoritesView.onRowClick = "UserFavoritesOnClick";
	userFavoritesView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+userFavoritesView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+userFavoritesView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}	
	userFavoritesView.fixedHeight = tempheight.toString() + 'px';		
	userFavoritesView.idPrefix = "UserFavorites";
	userFavoritesView.usejQuery = false;
	userFavoritesView.EmbViewInitialize();
}

function UserFavoritesOnClick(entryObj, docUrl) {
	//open in a tab in the main window
	OpenDocument(entryObj);  //function in wMyDocova form
}

function deleteFavorite(){

	var agentName = "UserDataServices";
	var request = "";
	var unidList = "";
	var FavWatchlistUNID = Docova.Utils.dbLookup({ nsfname: docInfo.PortalWebPath, viewname : "(luWatchlistFavByOwner)", key : docInfo.UserNameAB, columnorfield : 2, failsilent : true }); 

	if(!userFavoritesView.objEmbView.hasData){return false;}
	if (userFavoritesView.objEmbView.currentEntry == "" && userFavoritesView.objEmbView.selectedEntries.length==0 ) {
	//nothing selected
		var dlgHTML = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>To remove a Favorite, please select or check the the entry and try again.</p>'
		var dlgSelectFavorite = window.top.Docova.Utils.createDialog({
			id: "dlgDivSelectFavorite",
			title: "No favorite(s) selected!",
			dlghtml: dlgHTML,
			resizable: false,
			width: 500,
			height:200,
			sourcwindow: window,
			sourcedocument: document,
			buttons: {
			"OK": function() {
				dlgSelectFavorite.closeDialog();	
			}
			}
		});	
		return false;
	} 
		
	var tmpArray = new Array();
	(userFavoritesView.objEmbView.selectedEntries.length==0) ? tmpArray[0] = userFavoritesView.objEmbView.currentEntry : tmpArray = userFavoritesView.objEmbView.selectedEntries;
	for(var i=0; i < tmpArray.length ; i++) {
		entry = userFavoritesView.objEmbView.GetEntryById(tmpArray[i]);
		unidList += "<Unid>" + tmpArray[i].slice(0, -3) + "</Unid>";
	}

//-----Dialog to confirm deletion	
		var dlgHTML = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You are about to remove the selected favorites from your list.  Are you sure?</p>'
		var dlgRemoveFavorites = window.top.Docova.Utils.createDialog({
			id: "dlgDivRemoveFavorites",
			title: "Remove selected favorite(s)?",
			dlghtml: dlgHTML,
			resizable: false,
			width: 500,
			height:200,
			sourcewindow: window,
			sourcedocument: document,
			buttons: {
			"OK": function() {
				dlgRemoveFavorites.closeDialog();
				//-----Build request to remove selected favorites
				request += "<Request>";
				request += "<Action>REMOVEWATCHLISTITEMS</Action>";
				request += "<SrcUnid>" + FavWatchlistUNID + "</SrcUnid>";
				request += unidList;
				request += "</Request>";
				
				var result = SubmitRequest(request, agentName);
				//refresh the view
				if (userFavoritesView.objEmbView.selectedEntries.length!=0) {
					userFavoritesView.objEmbView.selectedEntries = new Array();
				} else {
					userFavoritesView.objEmbView.currentEntry=""; //selected/current are deleted now
				}
				userFavoritesView.objEmbView.Refresh(true,false,true); //reload xml data with current xsl
				userFavoritesView.EmbViewReload();			
			},
			Cancel: function() {
				dlgRemoveFavorites.closeDialog();
			}
		}
	});	
}