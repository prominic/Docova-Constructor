var wfSummaryView = null; //embedded view object

$(document).ready(function(){   
	InitwfSummary();	
});



function InitwfSummary() {

	//get the library/folder selections the user set last session
	//var lib = thingFactory.GetCookieValue( "wfSummaryLibraryName");
	var lib = Docova.Utils.getCookie({ keyname: "wfSummaryLibraryName", httpcookie: true });
	//var fld = thingFactory.GetCookieValue( "wfSummaryFolderName");
	var fld = Docova.Utils.getCookie({ keyname: "wfSummaryFolderName", httpcookie: true });
	
	var category = "";

	if(lib != "") { 
		category = lib;
		if(document.getElementById(lib)) {
			document.getElementById(lib).selected="true";
		}
		setFolderNameList(lib);
	}
	if(lib != "" && fld != "") { 
		category = lib + "~" + fld;
		setFolderNameList(lib, fld);
	}

	//initialize the embedded view
	wfSummaryView = new EmbViewObject;
	wfSummaryView.imgPath = docInfo.ImagesPath;
	wfSummaryView.embViewID = "divwfSummary";	
	wfSummaryView.captureEventDiv = "divwfSummaryCapture";
	wfSummaryView.perspectiveID = "xmlwfSummary";
	wfSummaryView.srcView = "xmlWorkflowItemsSummary";
	wfSummaryView.suffix = "";
	wfSummaryView.category = category;
	wfSummaryView.onRowClick = "wfSummaryOnClick";
	wfSummaryView.maxHeight = 0;
	var tempheight = parseInt(jQuery("#"+wfSummaryView.embViewID).closest("td").height(), 10) - parseInt(jQuery("#"+wfSummaryView.captureEventDiv).closest("td").find("div.portlet-header").height(), 10);
	if(tempheight > 65){tempheight = tempheight - 65;}		
	wfSummaryView.fixedHeight = tempheight.toString() + 'px';		
	wfSummaryView.idPrefix = "wfSummary";
	wfSummaryView.usejQuery = false;
	wfSummaryView.EmbViewInitialize();
	wfSummaryView.objEmbView.CollapseAll();
}

function wfSummaryOnClick(entryObj, docUrl) {
	//open in a tab in the main window
	//OpenDocumentEmbView(entryObj);   //function in wMyDocova form
	OpenDocument(entryObj);
}

function updateSummary(isLib) {

//	var lib = thingFactory.GetHTMLItemValue("LibraryName");
	var lib = $("#LibraryName").val();
	//var fld = thingFactory.GetHTMLItemValue("FolderName");
	var fld = $("#FolderName").val();

	if(isLib) {
		setFolderNameList(lib);
	}

	if(fld == undefined || fld == "") {
		wfSummaryView.category =  lib;
		fld = "";
	} else {
		wfSummaryView.category =  lib + "~" + fld;
	}

	wfSummaryView.EmbViewInitialize();
	wfSummaryView.objEmbView.CollapseAll();
//	thingFactory.SetCookieValue( "wfSummaryLibraryName", lib);
	Docova.Utils.setCookie({ keyname: "wfSummaryLibraryName", keyvalue: lib, httpcookie: true });
//	thingFactory.SetCookieValue( "wfSummaryFolderName", fld);
	Docova.Utils.setCookie({ keyname: "wfSummaryFolderName", keyvalue: fld, httpcookie: true });
}

function setFolderNameList(selLib, selFld) {
	var libList = document.getElementById("LibraryName");
	var fldList = document.getElementById("FolderName");
	while (fldList.options.length) {
		fldList.remove(0);
	}
	var obj = document.getElementById("wfSummaryItems");
	var items = obj.innerHTML;
	var arr = items.split("; ");
	var sArr = "";
	var fld = "";
	
	fld = new Option("");
	fldList.options.add(fld);
				
	for(x=0; x < arr.length; x++) {
		if(arr[x].indexOf("~") > 0) {
			sArr = arr[x].split("~");
			if(sArr[0] == selLib) {
				if(sArr[1] == selFld) {
					fld = new Option(sArr[1], sArr[1], false, true);
				} else {
					fld = new Option(sArr[1], sArr[1], false, false);
				}
				fldList.options.add(fld);
			}
		}	
	}
}

function wfSummaryRefresh(){
	InitwfSummary();
}