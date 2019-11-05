function EmbViewObject()
{
	this.isAppView = (info && info.isAppForm && info.isAppForm == "1" ? true : false); //true if view is an app view, false otherwise
	this.embViewID = "";	//the id of the div that will contain the view contents
	this.rowHeight = 18;	//default row height
	this.maxHeight = 150;	//default max height of the embedded view
	this.perspectiveID = "";	//the ID of the obj that will contain the view perspective XML
	this.srcView = "";		//the name of the view to retrieve the XML from
	this.suffix = "";		//for core DOCOVA, the key to pass to the URL for lookup to ludocumentsbyparent
	this.captureEventDiv = ""	//the ID of the div surrounding the embedded view for processing events
	this.objEmbView = null; //view object
	this.category = "";		//the category to return from the srcView
	this.onRowClick = "";	//the custom function to run when a row is clicked
	this.idPrefix = "";		//Each view object must have a unique identifier for categorized columns and totals to work correctly.  If only adding one embedded view object can be left blank, otherwise a short identifier should be set.  Note the following prefixes are used by the system:  RelDoc, AdvComm, RelLink, RelEmail
	this.baseUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/";	//Can be used to override where the srcView is located	
	this.fixedHeight = "100%";	//Only valid if maxHeight is 0.  Default is 100%.
	this.usejQuery = false;		//set to true to allow themes to be applied
	this.nocache = true;		//set to true to append url parameter to prevent caching	
	this.lookupURL = "";		//Views Objects default for emb views: this.baseUrl + this.embViewPage + "?openPage&view=" + this.baseLookupView + "&restricttocategory=" + prefx + this.folderID + this.suffix + "&" + Math.random();
								//use this param to build a custom lookup
	this.imgPath = "";		//assetics path to the images in Symfony
	
	var evObj = this;	//fix for getting correct object during event handling

	// ------------- sets the embedded view object and loads default view perspective---------------
	this.EmbViewInitialize = function()
	{
		this.objEmbView = new ObjView(this.embViewID, this.isAppView);
		this.objEmbView.imgPath = this.imgPath;
		this.objEmbView.iconBaseUrl = this.baseUrl;
		this.objEmbView.baseUrl = this.baseUrl;
		
		if(this.category == "") {
			this.objEmbView.folderID = docInfo.DocKey;	
		} else {
			this.objEmbView.folderID = this.category;
		}
		//this.objEmbView.thingFactory = thingFactory;
		//this.objEmbView.columnPropertiesDialogUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgViewColumnProperties?OpenForm&FolderID=" + docInfo.FolderID+ "&LibraryID=" + docInfo.LibraryKey; 
		this.objEmbView.serverName = ServerName;
		this.objEmbView.nsfName = NsfName;
		this.objEmbView.serviceAgent = this.baseUrl + "ViewServices?OpenAgent";
		this.objEmbView.embView = true;  //disable progress messages
		this.objEmbView.baseLookupView = this.srcView;
		this.objEmbView.suffix = this.suffix;
		this.objEmbView.rowHeight = this.rowHeight;
		this.objEmbView.maxHeight = this.maxHeight;
		this.objEmbView.idPrefix = this.idPrefix;
		this.objEmbView.usejQuery = this.usejQuery
		if(this.lookupURL != "") {
			this.objEmbView.lookupURL = this.lookupURL;
		}
		this.objEmbView.dateFormat = docInfo.SessionDateFormat;

		this.EmbViewLoadPerspective();
		this.EmbViewAttachEvents();
		
		$("#" + this.embViewID).css("overflow", "auto");
	}

	this.EmbViewLoadPerspective = function() 
	{
		
		var perspectiveXMLString = $(document.getElementById(this.perspectiveID)).html()

		this.objEmbView.SetViewParams(perspectiveXMLString);
		this.objEmbView.Refresh(true,true,true);
		this.EmbViewReload(true)

	}

	this.EmbViewReload = function(collapse)
	{
		var noDocs = document.getElementById("no" + this.embViewID);
		var rows = 1;
		if(this.objEmbView.hasData) {
			if(this.maxHeight == 0) {
				//document.all[this.embViewID].style.height = this.fixedHeight
				$("#" + this.embViewID).css("height", this.fixedHeight)
			} else {
				if(this.objEmbView.isCategorized){
					if(collapse) {
						this.objEmbView.CollapseAll();	
					}	
					var countChildren = false;
					for(var k=1; k < this.objEmbView.dataTable.rows.length; k++) {
						if($(this.objEmbView.dataTable.rows[k]).attr("isCategory")) {
							rows += 1;
							if($(this.objEmbView.dataTable.rows[k]).attr("isCollapsed")){
								countChildren = false;
							} else {
								countChildren = true;
							}
						} else if(countChildren) {
							rows += 1;
						}
					}
				} else {
					rows = this.objEmbView.dataTable.rows.length;
				}
				var calcHt = 4 + (parseInt(rows) * this.rowHeight);
				//$("#" + this.embViewID).css("height",(calcHt > this.maxHeight) ? this.maxHeight + "px" : calcHt + "px");
				$("#" + this.embViewID).css("height", this.maxHeight + "px");
			}
			//document.all[this.embViewID].style.display = ""
			$("#" + this.embViewID).css("display", "");
			$(noDocs).css("display","none");
			
		} else {
			//document.all[this.embViewID].style.display = "none"
			$("#" + this.embViewID).css("display", "none");
			//noDocs.style.display="";
			$(noDocs).css("display","");
		}	
	}
	
	this.EmbViewLoadDocument = function(docUrl) {

		if(this.objEmbView.currentEntry) {
			var entryObj = this.objEmbView.GetCurrentEntry();
			if(!entryObj) {return; }
			var recType = entryObj.GetElementValue("rectype");
			var docid = entryObj.GetElementValue("parentdocid");
			var folderid = entryObj.GetElementValue('folderid');
			if(!docid) {docid = entryObj.GetElementValue("docid")}
			docUrl = this.baseUrl + "ReadDocument/" + docid + "?ParentUNID=" + folderid;
			
			//custom API
			if(this.onRowClick != "") {
				try {
					eval(this.onRowClick + "(entryObj,docUrl)");
					return;
				}
				catch(e) { 
					alert(prmptMessages.msgCF019);
					return;
				}
			} else {

				//handler for standard DOCOVA embedded view types
				switch (recType) {
				case "doc": //related document
					OpenRelatedDocument(entryObj)
					break;
				case "URL": //related link
					docUrl = entryObj.GetElementValue("description");
					window.open(docUrl,"ExternalLink");
					break;
				case "DocLink":  //related link
					docUrl = entryObj.GetElementValue("description");
					location.href=docUrl;
					break;
				case "memo":  //related email
					OpenMemo(entryObj)
					break;		
				case "comment":  //advanced comments
					var comm = entryObj.GetElementValue("comment");
					var cDate = entryObj.GetElementValue("commentdate");
					var cType = entryObj.GetElementValue("commentype");
					var cAuth = entryObj.GetElementValue("createdby");
					var msg = comm + "<br><br>" + cAuth + "<br>" + cDate
					Docova.Utils.messageBox({
						prompt: msg,
						title: cType,
						width: 400
					})
					break;					
				}
			}
		}
	}

	// ------------------------------- embedded view event handlers -----------------------------

	this.EmbViewAttachEvents = function()
	{
		var thisEmbView = this;
		var paneObj = document.getElementById(this.captureEventDiv);
		if(!paneObj) {return false;}
		$(paneObj).unbind(); //unbind any events first so reinitializing emb views dont hold duplicate events
		//paneObj.onmousedown = this.EmbViewHandleEvent(e);
		$(paneObj).on("mousedown", function(e){ thisEmbView.EmbViewHandleEvent(e) })
//		paneObj.onmouseup = this.EmbViewHandleEvent(event);
//		paneObj.onmousemove = this.EmbViewHandleEvent(event);
//		paneObj.onmouseover = this.EmbViewHandleEvent(event);
//		paneObj.onclick = this.EmbViewHandleEvent(event);
		//paneObj.ondblclick = this.EmbViewHandleEvent(event);
		$(paneObj).on("dblclick", function(e){ e.stopPropagation(); thisEmbView.EmbViewHandleEvent(e) });
//		paneObj.onselectstart = this.EmbViewHandleEvent(event);
//		paneObj.onkeydown = this.EmbViewHandleEvent(event);
		$(paneObj).on("keydown", function(e){ thisEmbView.EmbViewHandleEvent(e);})
		//$(paneObj).on("keydown", function(e){ thisEmbView.EmbViewHandleEvent(e) })
//		paneObj.oncontextmenu = this.EmbViewHandleEvent(event);
	}

	//-----------------------------------------  event dispatcher --------------------------------------
	this.EmbViewHandleEvent = function(event) {

	var eventSource = event.target
	var eventType = event.type; 
	var sourceClass = $.trim(eventSource.className); 
	var sourceId = $.trim(eventSource.id); 
	var keyCode = event.keyCode;
	var currentRow; //table row containing the eventSource
	var sourceType="";

	if(!eventSource){return}

		currentRow = $(eventSource).closest("TR").get(0)
		//------------------ check if there are any event handlers for the event source --------
		//------------- clickable images
		if(eventSource.tagName == "IMG" ) 
			{
				if(sourceClass=="listsorticon")
					{
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "viewsort", keyCode);
						return;
					}
					
					else if ( sourceClass=="reflection" || sourceClass=="content portray" || sourceClass=="content landscape"){
						//currentRow = eventSource.parentElement;
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "coverflow", keyCode,currentRow);
						return;
					}
				
				else if(sourceClass=="listviewrefresh")
					{
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "viewrefresh", keyCode);
						return;
					}		
				else if(sourceClass=="listexpandericon")
					{
						//currentRow = eventSource.parentElement.parentElement.parentElement;
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "categorytoggle", keyCode, currentRow)
						return;
					}			
			}

		//------------ cells/rows
		//while(eventSource.tagName != "BODY")
		//{	
		if(eventSource.tagName == "TD" || eventSource.tagName == "TH" || eventSource.tagName =="SPAN" )
			{
				if ( sourceClass == "thumbnail" ) {
						currentRow = eventSource;
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "thumbnail", keyCode, currentRow);
						return;
				}
				//currentRow = eventSource.parentElement;
				if(sourceClass=="listheader" || sourceClass=="listheaderfr" || sourceClass=="listheaderfltr" || sourceClass=="listheaderfrfltr") //column heading
					{
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "viewheader", keyCode, currentRow);
						return;
					}
				else if(sourceClass=="listselheader") //selection column heading
					{
						evObj.EmbViewEventDispatch(event, eventType, eventSource, "viewselectheader", keyCode, currentRow);
						return;
					}
				else if(sourceClass=="listitem" || sourceClass=="listitemfr") //data or total cell
					{
						//if(currentRow.isRecord)
						if($(currentRow).attr("isRecord"))
							{
								//alert("is record found")
								evObj.EmbViewEventDispatch(event, eventType, eventSource, "datarow", keyCode, currentRow);
								return;
							}
						//if(currentRow.isCategory) 
						if($(currentRow).attr("isCategory"))
							{
								evObj.EmbViewEventDispatch(event, eventType, eventSource, "categoryrow", keyCode, currentRow);
								return;
							}
						//if(currentRow.isSubtotal)
						if($(currentRow).attr("isSubtotal"))
							{
								evObj.EmbViewEventDispatch(event, eventType, eventSource, "subtotalrow", keyCode, currentRow);
								return;
							}
						//if(currentRow.isTotal)
						if($(currentRow).attr("isTotal"))
							{
								evObj.EmbViewEventDispatch(event, eventType, eventSource, "totalrow", keyCode, currentRow);
								return;
							}
					}
				else if(sourceClass=="listcat" || sourceClass=="listcatfr") //category heading cell
					{
						evObj.EmbViewEventDispatch(event, eventType, eventSource,  "categoryrow", keyCode, currentRow);
						return;
					}
				else if(sourceClass=="listsel") //selection margin cell
					{
						evObj.EmbViewEventDispatch(event, eventType, eventSource,  "selectcell", keyCode, currentRow);
						return;
					}
			}
			if(eventSource.tagName == "INPUT"){
				evObj.EmbViewEventDispatch(event, eventType, eventSource,  "selectcell", keyCode, currentRow);
			}			
			//alert("going to parent")
			//eventSource=eventSource.parentElement;
			//eventSource=$(eventSource).parent().get(0)
			//sourceClass=eventSource.className;
			//sourceClass =  $(eventSource).prop("className")
			//sourceId=eventSource.id;
			//sourceId = $(eventSource).prop("id"); 
		//}

			evObj.EmbViewEventDispatch(event, eventType, eventSource, "", keyCode, currentRow);
	}

	//------------------ dispatch event handler based on event source and type --------
	this.EmbViewEventDispatch = function(event, eventType, eventSource, sourceType, keyCode, currentRow) 
	{
		if(eventType=="mousemove") {
			if(sourceType=="selectcell") {return this.CancelEvent();} 
			if(sourceType=="viewheader" ) {return this.CancelEvent();} 
		}
		//else if(eventType=="mousedown" && event.button == 1) {
		else if(eventType=="mousedown" && event.which == 1) {
			//alert("mousedown and left click")
			if(sourceType=="selectcell") {this.EmbViewDocSelectClick(eventSource);} //doc selection column
			if(sourceType=="viewsort") {this.EmbViewSortColumn(eventSource);}
			if(sourceType=="viewrefresh" || sourceType=="viewselectheader") { //view refresh icon
				this.objEmbView.Refresh(true, false, true);
				this.objEmbView.ResetHighlight();
				this.EmbViewReload(false);  //resize the embedded view div
			} 
			if(sourceType=="categorytoggle") {this.EmbViewToggleCategory(currentRow);}
			if(sourceType=="datarow" || sourceType=="subtotalrow" || sourceType=="categoryrow" || sourceType=="thumbnail") {
				var isiPad = navigator.userAgent.match(/iPad/i) != null;
				if ( isiPad){
					if ( currentRow.id == this.objEmbView.currentEntry )
						this.EmbViewLoadDocument(false);
					else
						this.EmbViewHighlightEntry(currentRow);
				}else{
					this.EmbViewHighlightEntry(currentRow);
				}
			}
			// disable filtering if(sourceType=="viewheader" ) {this.EmbViewColumnSelectFilter(eventSource);}	
		}
		//else if(eventType=="click" && event.button == 1) {
		else if(eventType=="click" && event.which == 1) {
			return this.CancelEvent();
		}
		else if(eventType=="dblclick" ) {
			if(sourceType=="datarow") {this.EmbViewLoadDocument(false);}
			if(sourceType=="categoryrow" ) {this.EmbViewToggleCategory(currentRow);}	
			if(sourceType=="thumbnail"){this.EmbViewLoadDocument(false);}
			if(sourceType=="coverflow"){this.EmbViewHighlightEntry(currentRow);this.EmbViewLoadDocument(false);}
		}
		else if(eventType=="mouseout") {
			return this.EmbViewClearEventState();
		}
		else if(eventType=="keydown") {
			this.EmbViewHandleKeyboardEvent(eventType, eventSource, sourceType, keyCode, currentRow);
		}
		else if(eventType=="selectstart") {
			if(sourceType !="ftquery") {return this.CancelEvent();}
		}
		else if(eventType=="contextmenu") {
			if(sourceType=="datarow" || sourceType=="subtotalrow" || sourceType=="categoryrow" || sourceType =="thumbnail" ) {this.EmbViewHighlightEntry(currentRow);}
			if(sourceType=="viewheader" || sourceType=="viewselectheader" || sourceType=="viewrefresh") {
				this.EmbViewShowContextPopup(sourceType, eventSource);
				return this.CancelEvent();
			}
			return this.CancelEvent();
						
		}
	}

	this.EmbViewDocSelectClick = function(source) 
	{
		if(!source) {return this.CancelEvent();}
		var parentRow = $(source).parentsUntil("TR").parent().get(0)

		if(!$(parentRow).attr("isRecord")) {return this.CancelEvent();}

		var chkbox = source
		if( !chkbox.checked )	{
			parentRow.isChecked=true;
			if(chkbox) {this.objEmbView.ToggleSelectEntryById(parentRow.id, "check");}
		}
		else {
			parentRow.isChecked=false;
			if(chkbox) {this.objEmbView.ToggleSelectEntryById(parentRow.id, "uncheck");}
		}
		return this.CancelEvent(); //handled
	}

	//----------------------------- highlight entry on click -------------------
	this.EmbViewHighlightEntry = function(source) {
		if(!source) {return false;}
		//give focus to container div of embedded views for events to work (cross-browser)
		var capturediv = $(source).parentsUntil(".capturediv").parent().get(0)
		$(capturediv).attr("tabindex", 0)
		$(capturediv).focus();
		
		this.objEmbView.HighlightEntryById($(source).prop("id"));
		this.CancelEvent();
	}

	//----------------------------- un-highlight entry on ESC key -------------------
	this.EmbViewResetEntryHighlight = function() {
		this.objEmbView.ResetHighlight();
	}

	// ------- context menu handler ---------
	this.EmbViewShowContextPopup = function(popupSource, sourceObject) {
			if(popupSource=="viewheader" )
				{
				if(!sourceObject.colIdx){return;}
				var colNo = parseInt(sourceObject.colIdx);
				var offsetX = (event)? event.clientX - sourceObject.offsetLeft : 0;
				curContextObj = this.objEmbView.columns[colNo]; //object handling the action click
				var contextMenu = this.objEmbView.columns[colNo].CreateContextMenu(offsetX);
				}
			else if(popupSource=="viewselectheader" || popupSource=="viewrefresh"  )
				{
				curContextObj = this.objEmbView;
				var offsetX = (event)? event.clientX - sourceObject.offsetLeft : 0;
				var contextMenu = this.objEmbView.CreateContextMenu("selectheader", sourceObject, offsetX);
				}
			
			if(contextMenu)
				{
				var oPopBody = oPopup.document.body;
				oPopBody.innerHTML = contextMenu.innerHTML();
				oPopup.show(contextMenu.offsetRight, contextMenu.offsetTop, contextMenu.width, contextMenu.height, event.srcElement);
				return false;
				}
	}

	//--------------------------------------------- column sorting ----------------------------------------------------

	this.EmbViewSortColumn = function(source)
	{
		var  colIdx = $(source).prop("id").split("-")[1];
		this.objEmbView.ToggleCustomSort(colIdx)
		return this.CancelEvent();
	}

	//--------------------------------------------- expand/collapse category ----------------------------------------------------

	this.EmbViewToggleCategory = function(currentRow)
	{
		this.objEmbView.ToggleCategory($(currentRow).prop("id"));
		this.EmbViewReload(false);  //resize the embedded view div
		return this.CancelEvent();
	}


	//----------------------------- up/down keys on highlighted entry ------------------------
	this.EmbViewMoveDocHighlight = function(dir) //up/down arrow key handler
	{
		this.objEmbView.MoveEntryHighlight(dir) ;
		return this.CancelEvent();
	}

	// clears the view event variables in case mouse cursor had wandered to far
	this.EmbViewClearEventState = function() {
		var dragSourceColumn=null;
		var dragTargetColumn=null;
		selectDragMode=null;
		return true;
	}

	this.CancelEvent = function() {
		if(!window.event) {return;}
		window.event.cancelBubble = true;
		window.event.returnValue=false;
		return false;
	}

	// ------------------------------------ document list key handlers ------------------------------------------------

	this.EmbViewHandleKeyboardEvent = function(eventType, eventSource, sourceType, keyCode, currentRow)
	{
		var ENTER_KEY = 13
		var DOWNARROW_KEY = 40
		var UPARROW_KEY = 38
		var F9_KEY = 120
		var SPACE_KEY= 32;
	
	//The keydown event.target is different in different browsers. Use view object currentEntry to re-set currentRow
	//and sourceType
	currentRow = $("#" + this.captureEventDiv).find('tr[id="' + this.objEmbView.currentEntry +'"]').get(0)
	if($(currentRow).attr("isRecord")){sourceType = "datarow";}
	if($(currentRow).attr("isCategory")){sourceType = "categoryrow";}
	if($(currentRow).attr("isSubtotal")){sourceType = "subtotalrow";}
	if($(currentRow).attr("isTotal")){sourceType = "totalrow";}

	if(keyCode == ENTER_KEY)
		{
			if(sourceType=="datarow" && this.objEmbView.currentEntry != "") //process entry only if it is highlighted
				{
				this.EmbViewLoadDocument(false);
				}
			if(sourceType=="categoryrow" || sourceType== "categorycell") {this.EmbViewToggleCategory(currentRow);}	
		}
	else if(keyCode == DOWNARROW_KEY)
		{
			if(sourceType=="datarow" || sourceType=="categoryrow" || sourceType=="subtotalrow")
			{
				this.EmbViewMoveDocHighlight("down");
			}
		}
	else if(keyCode == UPARROW_KEY)
		{
			if(sourceType=="datarow" || sourceType=="categoryrow" || sourceType=="subtotalrow")
			{
				this.EmbViewMoveDocHighlight("up");
			}
		}
	else if(keyCode == F9_KEY)
		{
			this.objEmbView.Refresh(true, false, true);
		}	
		
	else if(keyCode == SPACE_KEY)
		{
			if(sourceType=="datarow" && this.objEmbView.currentEntry != "") //select the entry only if it is highlighted
				{
					this.EmbViewDocSelectClick(document.getElementById(this.objEmbView.currentEntry));
				}
			if(sourceType=="categoryrow" || sourceType== "categorycell") {this.EmbViewToggleCategory(currentRow);}	
			if(sourceType=="ftquery"){return;} //let it go
			this.EmbViewClearEventState();
			this.CancelEvent(); //handled
		}
	}	
	
	//-------- Delete the selected emb view document(s) ------------------------------------
	//-------- Note 'Related' sections may call their own delete function ------------------
	
	this.DeleteSelEmbViewEntries = function() 
	{
		if (this.objEmbView.currentEntry == "" && this.objEmbView.selectedEntries.length==0 ) {return false;} //nothing selected
		
		var tmpArray = new Array();
		(this.objEmbView.selectedEntries.length==0) ? tmpArray[0] = this.objEmbView.currentEntry : tmpArray = this.objEmbView.selectedEntries;
		var request = "";
		
		//if (!confirm("Are you sure you want to delete the " + tmpArray.length +  " selected document(s)?")) {return false;}

		//-------------------------------------------------------

		request = "<Request><Action>DELETESELECTED</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		
		for(var i=0; i < tmpArray.length ; i++) {
			request += "<Unid>" + tmpArray[i] + "</Unid>";
		}
		
		request += "<FolderName><![CDATA[" + docInfo.FolderName + "]]></FolderName></Request>";
		this.objEmbView.ShowProgressMessage(prmptMessages.msgCF020);
		var flag = this.objEmbView.SendData(request);
		(this.objEmbView.selectedEntries.length!=0) ? this.objEmbView.selectedEntries = new Array() : this.objEmbView.currentEntry=""; //selected/current are deleted now
		this.objEmbView.Refresh(true,false,true); //reload xml data with current xsl
		//-------------------------------------------------------		
		this.objEmbView.HideProgressMessage()
	}	

}  //end embViewObject class