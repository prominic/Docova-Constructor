

function RefreshHelper(){
	
	this.RefreshList = new Array();
	
	this.AddFolderToRefreshList = function ( folderUnid, docID ){
	
		if ( this.RefreshList[folderUnid]){
			this.RefreshList[folderUnid]["folderUnid"] = folderUnid;
			this.RefreshList[folderUnid]["docID"] = docID;
		}else{
		
			this.RefreshList[folderUnid] = { "folderUnid": folderUnid, "docID": docID};
		}
		
	}
	
	this.AddDocumentToHighlightList = function ( folderUnid, docID) {
		if ( this.RefreshList[folderUnid]){
			this.RefreshList[folderUnid]["docID"] = docID;
		}else{
			this.RefreshList[folderUnid] = { "folderUnid": folderUnid, "docID": docID};
		}
	}
	
	this.RemoveFolderFromRefreshList = function ( folderUnid ){
		
		for ( var j in this.RefreshList){
			if (this.RefreshList[j]["folderUnid"] ==  folderUnid){
				delete this.RefreshList[j];
			}
		}
	}
	
	this.IsFolderInRefreshList = function (folderID){
		if ( this.RefreshList[folderID]) return true;
		else return false;
	}
}


function ObjTabBar() {
this.currentFrameID = ""; //the FrameID of the currently active tab
this.lastFolderIndex = 0; //The index of the last Folder tab opened

this.closeTabHTMLPre = "<span id='closeTab' class='closeTab' title='Close'><span id='";  //close button on the tab
this.closeTabHTMLSuf = "'border=0>&#86;</span>"; //close button on the tab

this.RefreshHelper = new RefreshHelper();
this.refreshID = "";
this.highlightDoc = "";
this.jqtab = null;
this.isSearch = false;
this.handlingClick = false;
this.openFolderID = "";
this.openFolderInNewTab = false;
this.lastTabID = "";
this.disableFocusOnClose = false;  //this allows us to turn off the page that is shown when no taba are open
this.tabs = new Array();
/*JSON container for tabs.  FrameID used as unique value
	eg: tabs[frameID] = {"folderIndex":"","docIndex":"","lastDocIndex":"","state":"","title":"","frameID":"","contentType":"","url":""}
	Elements:
	folderIndex		A sequential integer identifying the Folder position in the tab bar 
	docIndex		A sequential integer identifying the Document position, relative to it's parent folder, in the tab bar (documents only)
	lastDocIndex	Applies to Folder Tabs only.  The index of the last document tab opened
	state			Active, Inactive  ---> maybe not needed?
	title			FolderName if Folder; Subject if a Document
	frameID			FolderID if Folder; DocKey if existing Document; result of GetNewDocID function if a new doc (dateTime stamp)
	contentType		Folder ("F") or Document ("D") or SearchResults ("S")
	url				http://etc
	*/
    
//get a unique id to use for a new document
this.GetNewDocID = function(){
	var tmpDate = new Date()
	id = tmpDate.getUTCMinutes() * 100000;
	id += tmpDate.getUTCSeconds() * 1000;
	id += tmpDate.getUTCMilliseconds();
	return id;
}



this.InitSearch = function()
{
	this.frameSet = window.parent.fsContentFramesetSearch; //the parent frameset container	
	this.tabFrame = window.parent.fraTabbedTableSearch.tabs; //the div in the frame that holds the tabs
	this.tabBarHeight = "34"; //height of the tab bar in pixels. Does not change the actual tab - mod css in wTabbedTable
	this.maxWidth = this.tabFrame.offsetWidth; //Maximum width of the tab bar for the current browser session
	this.maxTabs = parseInt((this.maxWidth - 50) / 80); //(maxWidth - reserved areas) divided by the fixed tab width. 80 set in wTabbedTable css (#topnav A width)
	this.isSearch = true;
	
	this.jqtab = $('#tabs')
	.tabs()
	 this.tabs["mainsearchtab"] = {
	            "title": "Search",
	            "frameID": "mainsearchtab",
	            "contentType": "F",
	            "url": "http://www.google.ca"
	 }    
	
}

this.Init = function()
{
	this.frameSet = window.parent.fsContentFrameset; //the parent frameset container	
	this.tabFrame = window.parent.fraTabbedTable.tabs; //the div in the frame that holds the tabs
	this.tabBarHeight = "36"; //height of the tab bar in pixels. Does not change the actual tab - mod css in wTabbedTable
	this.maxWidth = this.tabFrame.offsetWidth; //Maximum width of the tab bar for the current browser session
	this.maxTabs = parseInt((this.maxWidth - 50) / 80); //(maxWidth - reserved areas) divided by the fixed tab width. 80 set in wTabbedTable css (#topnav A width)
	
	this.jqtab = $("#tabs").tabs();

}


this.IsFolderOpen = function ( frameID ){
	if ( ! this.tabs ) return false;
	if ( this.tabs[frameID] ) return true;
	return false;
}

this.RenameFolder = function ( frameID, newname){
	if ( ! this.tabs[frameID]) return;
	this.tabs[frameID].title = newname;
	this.ChangeState(frameID);
	$("#" + frameID + "titletext").text(newname);
}

//this updates the title text of the tab
this.UpdateTabText = function ( frameID, newtext)
{
	if ( ! this.tabs[frameID]) return;
	this.tabs[frameID].title = newtext;
	$("#" + frameID + "titletext").text(newtext);
	
}

//this updates the ALT text for the tab
this.UpdateTitle = function ( frameID, newtitle){
	var topNavUL = jQuery(this.tabFrame).find("ul:first");
	topNavUL.find("li").each(function(){
       if (this.id == frameID) {
			this.setAttribute("title", newtitle);
			return;
       }		
	});	
}


this.UpdateTab = function (title, frameID, newurl)
{
	 if (!frameID) {
	        return
    }
    
    if ( title == "undefined"){
    	var title = "Untitled ";
    }
    if ( title == null){
    	var title = "Untitled ";
    }
    if ( title == ""){
    	title = "Untitled "
    }
    	
    var path = title;
    var patharr = title.split("~!~");
    if ( patharr.length > 1){
    	title = patharr[0];
    	path = patharr[1];
    }
    if (this.tabs[frameID] ) {
        //re-opening a doc or folder that has already been opened
    	if ( this.tabs[frameID].url != newurl ){
    		var frmset = jQuery(this.frameSet)
    		var frm = frmset.find("frame[id='" + frameID + "']");
    		if ( frm[0]) {
    			frm[0].src = newurl;
    			this.UpdateTabText(frameID, title);
    		}
    		this.tabs[frameID].url = newurl;
    		this.tabs[frameID].title = title;
    	}
        this.ChangeState(frameID);
    }
	   
	
}

//------ create a new tab and set as active or open tab if folder/doc already open ----------------------------
this.CreateTab = function (title, frameID, contentType, url, folderID, isNewDoc) {
    if (!frameID) {
        return
    }
    
    if ( title == "undefined"){
    	var title = "Untitled ";
    }
    if ( title == null){
    	var title = "Untitled ";
    }
    if ( title == ""){
    	title = "Untitled "
    }
    	
    var path = title;
    var patharr = title.split("~!~");
    if ( patharr.length > 1){
    	title = patharr[0];
    	path = patharr[1];
    }
   
    if (this.tabs[frameID] ) {
        //re-opening a doc or folder that has already been opened
    	if ( this.tabs[frameID].url != url ){
    		var frmset = jQuery(this.frameSet)
    		var frm = frmset.find("frame[id='" + frameID + "']");
    		if ( frm[0]) {
    			frm[0].src = url;
    			this.UpdateTitle(frameID, title);
    		}
    	}
    		
        this.ChangeState(frameID);
        return;
    } else {
    	
    	if ( contentType=="F" && this.openFolderID != "" && this.openFolderInNewTab==false ) {
    		//if this is a folder opening, then close the existing folder tab first
    		this.disableFocusOnClose = true;
    		//disable the no frames open page from showing up since that de-selects any selected
    		//folders and doesn't allow the new folder to be opened in the first tab.
    		
    		this.CloseTab(this.openFolderID);
    		
    		//re-enable it now.
    		this.disableFocusOnClose = false;
    	}
    	
    	if ( contentType=="F" && this.openFolderInNewTab==false )
    		this.openFolderID = frameID;
    	
    	//create the frame
        this.tabs[frameID] = {
            "title": title,
            "frameID": frameID,
            "contentType": contentType,
            "url": url
        }    
        var folderIndex = 0;
        var frameExists = false;
        
        
        for ( var x = 3; x < this.frameSet.childNodes.length; x++ ){
    		currFrameID = this.frameSet.childNodes[x].id
    		if ( currFrameID == frameID ){
    			frameExists = true;
    		}
    	}
       
        if ( frameExists == false){
        	jQuery("<frame></frame>").appendTo(this.frameSet).attr({"id": frameID, "src" : url});	      
    	}
        
        if (this.tabs[frameID]["contentType"] == "D" && this.tabs[folderID] ) {
        	//assumes folder is already open, so 'folderID' is valid
        	this.tabs[frameID]["docIndex"] = this.tabs[folderID]["lastDocIndex"] = this.tabs[folderID]["lastDocIndex"] + 1;
            this.tabs[frameID]["folderIndex"] = this.tabs[folderID]["folderIndex"];
            folderIndex = this.tabs[folderID]["folderIndex"];
        } else {
        	//folder or search results
            this.tabs[frameID]["docIndex"] = 0;
            if ( this.openFolderInNewTab )
            	folderIndex = this.lastFolderIndex = this.tabs[frameID]["folderIndex"] = this.lastFolderIndex + 1;
            else
            	folderIndex = this.tabs[frameID]["folderIndex"] = 0;
            
            this.tabs[frameID]["lastDocIndex"] = 0;
        }
        		
        
        var iconname = "ui-icon-document";
        if ( contentType == "F") iconname = "ui-icon-folder-open";
        var newLi = document.createElement("li");
        newLi.id = frameID; 
        tabTemplate = "<a href='#nodiv" + frameID + "' title='" + path + "' id='" + frameID + "'>"
        tabTemplate += "<span id='ficon' class='ui-icon " + iconname + " ficon'></span><span id='" + frameID + "titletext' style='padding-left:4px'>" + this.tabs[frameID]["title"] + "</span></a>";
        
        if ( this.openFolderID != frameID )
        	tabTemplate = tabTemplate+ "<span style='float:right; margin-top:2px;margin-right:2px;' class='ui-icon ui-icon-close' id ='" + frameID + "' role='presentation'>Remove Tab</span>";
        	
       // tabTemplate =  "<a href='#nodiv" + frameID + "' id='" + frameID + "'>" + this.tabs[frameID]["title"] + "</a> <span></span>"
        newLi.innerHTML = tabTemplate;
        newLi.onclick = this.HandleEvent;
       // var span;
       // if ( newLi.firstChild.nextSibling.nextSibling )
       // 	span = newLi.firstChild.nextSibling.nextSibling;
        var dummydiv = document.createElement("div");
        dummydiv.id = "nodiv" + frameID;
        document.all.tabs.appendChild(dummydiv);
       // if ( span != null){
       // 	span.onclick = this.HandleEvent;
        	
       // }
        //insert or append the tab so that document tabs from the same folder appear together
        //topnav is a div on the TabbedTable form
        //var topNavUL = this.tabFrame.firstChild;
        var topNavUL = $(this.tabFrame).find("ul:first");
        var insertBeforeID = "";
        var tmpIndex = "";
        var lastIndex = -1;
        var cnt = 0;
        
        var firstli = topNavUL.find("li:first");
        if( this.openFolderInNewTab ==false && firstli.length > 0 && contentType == "F" ){
        	insertBeforeID =firstli[0].id;
    	}else{
    		for (var index in this.tabs) {
                if (this.tabs[index]["contentType"] != "D") {
                    tmpIndex = this.tabs[index]["folderIndex"];
                    if (tmpIndex > folderIndex) {
                        if (lastIndex == -1) {
                            insertBeforeID = index;
                            lastIndex = tmpIndex;
                        } else if (tmpIndex < lastIndex) {
                            insertBeforeID = index;
                            lastIndex = tmpIndex;
                        }
                    }
                }
            }
        }
       
        
        if (insertBeforeID) {
        	
        	var tli = $(topNavUL).find("li[id=" + this.tabs[insertBeforeID]["frameID"]+ "]" )
        	$(newLi).insertBefore(tli);
            
        } else {
        	jQuery(topNavUL).append(newLi);
            //topNavUL.appendChild(newLi);
        }
        
		//When X is hovered over
		$(".ui-icon-close").hover(
        	function () {
        		$(this).removeClass("ui-icon-close").addClass("ui-icon-circle-close");
        	},
		function () {
			$(this).removeClass("ui-icon-circle-close").addClass("ui-icon-close");
        	}        
	);
	
    }
    

   this.ChangeState(frameID, true);
   
    var $li = $();
	$li = $li.add(newLi);
	this.jqtab.trigger('tabscreate',[$li,$li]);
	
	//need to do this again as without it, the new tab title's width is not calculated properly
   this.ChangeState(frameID, false);
    
}

//----- Show the selected tab/frame, reset all others -------------------------------------------
this.ChangeState = function (frameID, savelasttab) {

	
    var topNavUL = $(this.tabFrame).find("ul:first");
    if (topNavUL[0] == null) return;
    var recordLastTab;
    
    if ( typeof savelasttab == 'undefined'  || savelasttab==true) recordLastTab = true;
    else recordLastTab = false;
  
    if ( ! this.tabs[frameID] ){
    	
    	parent.frames['fraLeftFrame'].LoadCurrentFolder();
    	return;
    }
   
    var lis = topNavUL.find("li");
	
    if ( recordLastTab ){
    	
    	for (var j = 0; j < lis.length; j++){
    		if (this.tabs[lis[j].id]["state"] == "Active" ){
	    		this.lastTabID = lis[j].id;
	    		break;
	        }	
    		
    	}
    	
    }

    	
    //update the applicable tab
    var currChild = null;
    var selIndex = 0;
    for (var x = 0; x < lis.length; x++) {
        currChild = lis[x];
     
        if (currChild.id == frameID) {
            selIndex = x;
            this.tabs[frameID]["state"] = "Active";
        } else {
            this.tabs[currChild.id]["state"] = "Inactive"
        }
    }
    
    
   
   
   this.jqtab.tabs( "refresh" );
   this.jqtab.tabs( 'option', 'active', selIndex );
  
   
    //show the applicable frame
    var currFrameID = "";
    var rowStr = this.tabBarHeight + "";
    if ( this.isSearch ) rowStr = this.tabBarHeight;
    var currentFrame;
    var startind = 3;
    if ( this.isSearch ) startind = 1;
    startind = 0;
    var that = this;
    jQuery(this.frameSet).find("frame").each(function(index, element){
    	
    	//if(index >= startind){
    	if ( element.id != "fraTabbedTable" && element.id != "fraTabbedTableSearch"){
    		currFrameID = element.id;
    		if ( currFrameID == frameID ){
    			rowStr += ",*";
    			if ( that.RefreshHelper.IsFolderInRefreshList(frameID) ){
    				currentFrame = element;			
    			}
    		} else {
    			rowStr += ",0";		
    		}
    	}   	
    });
  	that = null;
    this.frameSet.rows = rowStr;
        
    if ( typeof currentFrame != 'undefined'){
    		if ( this.RefreshHelper.RefreshList[frameID] ){
    			var objView = currentFrame.contentWindow.objView;
    			var docInfo = currentFrame.contentWindow.getinfovar();
    			//refresh to reapply current filter if one is there
    			if ( docInfo.EnableFolderFiltering == "1" )
    				objView.Refresh(true, false, true, false, false, true);
    			else
    				objView.Refresh(true, false, true );
    			
    			if (this.RefreshHelper.RefreshList[frameID]["docID"] != "" ){
    				objView.HighlightEntryById(this.RefreshHelper.RefreshList[frameID]["docID"]);
    			}
    			this.RefreshHelper.RemoveFolderFromRefreshList(frameID);
    		}	
    }
  
    this.currentFrameID = frameID;
}

//------ close the selected tab/frame ----------------------------------------------------
this.CloseTab = function(frameID, closeFrame, refreshFolderUNID) {

	//frameID the frameID to be close
	
	//closeFrame - if set to true, then closes the frame immediately.
	//in some cases we don't want to close the frame right away.  This is due to the fact that cases
	//such as document save, the web query save agent needs the frame and the doc within it to stay open so that
	//it can do a re-direct on successful save or show the error message.
	//In these cases, on a successful save of the document, the webquerysave agent redirects the frame to
	//blankcontent?openage.  This page has the responsibility of closing the tab.
	
	//refreshFolderUNID : has the UNID of the folder we want refreshed after the tab is closed.
	//such as when a doc has been edited and saved...in that case, we want to refresh the underlying folder tab.
	
    var topNavUL = $(this.tabFrame).find("ul:first");
    if (topNavUL == null) return;
    var doCloseFrame;
    if ( typeof closeFrame == 'undefined'  ) doCloseFrame = true;
    else doCloseFrame = closeFrame;
    
    var folderRefreshID = "";
    if ( typeof refreshFolderUNID != 'undefined') {
    	folderRefreshID = refreshFolderUNID;
    }
   
   
    
    if ( frameID == "fraContentBottom") {
    	//this is when the doc is being closed from the search tab
    	window.parent.fsContentFrameset.rows ="0,*";
    	return;
    }
    var hasDocs = false;
    
    //disableFocusonclose will be set to true if we are closing a folder tab to re-open another one in its
    //place...in this case, we don't want the following code to run.
    if ( this.disableFocusOnClose != true){
	    //handling for tabs created on the fly and id is not in tabs array
		if ( ! this.tabs[frameID]){
	   		for (var index in this.tabs) {
	   			if(this.tabs[index]["state"] == "Active"){
	   				if (this.tabs[index]["contentType"] == "D") {
	   					frameID = index;
	   					break;
	   				}
	   			}
	   		}
		}    
	}

    if ( ! this.tabs[frameID]) return;
    
    //locate and remove the frame, closing any child docs too
    var fs = window.parent.fsContentFrameset;  //global reference n/a, set new variable
    if ( this.isSearch) fs = window.parent.fsContentFramesetSearch;
    
    var removeFrameIDs = new Array();
    var removeFrCount = 0;
    var currFrameID = "";
    var removeNodes = new Array();
    var z = 0;
  
    var objView;
    var editInfo;
    var docInfo;
    
   
    var startind = 3;
    if ( this.isSearch ) startind = 1;
    startind = 0;
    var frames = $(fs).find("frame");
	for ( var x = startind; x < frames.length; x++ ){
		currFrameID = frames[x].id
		if ( currFrameID == frameID ){
			removeNodes[z] = x;
			z++;
			removeFrameIDs[removeFrCount] = currFrameID;
			removeFrCount++;
		}
		
		if ( currFrameID ==folderRefreshID){
			//need to refresh the folder in this frame
			objView = frames[x].contentWindow.objView;						
		}
	}

	//remove all identified frames
	this.hightlightDoc = "";
	removeNodes.sort();
	removeNodes.reverse();
	for ( var x = 0; x < removeNodes.length; x++){
		var cd = frames[removeNodes[x]];
		
		try{
			var cddoc = cd.contentWindow.document;
			
			docInfo = cd.contentWindow.getinfovar();
		}catch(err)
		  {
			delete docinfo;
		  
		  }
		
		try{
			editInfo = cd.contentWindow.getupdateinfovar();
		}catch(err){
			delete editInfo;
		}
		if ( typeof editInfo != 'undefined'){
			
			if ( typeof objView != 'undefined'){
				 if ( folderRefreshID != "" ) {
					    //the folder that the doc is in needs to be refreshed
					 	// store the id into the refreshHelper object
					 	// on activation of each tab, we check this list to see if we need to do a refresh using the RefreshHelper
				    	this.RefreshHelper.AddFolderToRefreshList( folderRefreshID, editInfo.docid);
				    }
				
			}
		}
		
		if ( doCloseFrame || (doCloseFrame == false &&  typeof docInfo === 'undefined')){
			//called to ensure that form unload event is triggered
			cd.contentWindow.location.replace("about:blank");
			//close the window that is in the frame
			cd.contentWindow.close();
			jQuery(frames[removeNodes[x]]).remove();
		}
		
	}

	//locate and remove the tab
	var currTabID = "";
    var removeTabs = new Array();
    var z = 0;
    var lis = topNavUL.find("li");
    for ( var x = 0; x < lis.length; x++ ){
    	currTabID = lis[x].id
    	if ( currTabID == frameID ){
    		removeTabs[z] = x;
			z++;
		}
	}
    
   
    
    removeTabs.sort();
    removeTabs.reverse();
	for ( var x = 0; x < removeTabs.length; x++){
		//this.tabFrame.childNodes[0].childNodes[removeTabs[x]].removeNode(true);
		$(lis[removeTabs[x]]).remove();
	}
	
    //remove the tab reference after determining which tab/frame should get focus
	var indexArr = new Array();
	var arrCount = 0;
	var tabsLeft = false;
	var folderTab = "";
    
    //if the closed document is from a folder that needs to be refreshed
	//then thats the folder we need to get into focus on the 
	//doc close..
	
	
	if (this.lastTabID != "")
		folderTab = this.lastTabID;
	else{
	    for (var index in this.tabs) {
				if(this.tabs[index]["contentType"] == "F"){
					folderTab = index;
					break;
				}
		}
	}
   
	if ( folderTab==frameID) folderTab="";
	
	//check if currently closing tab is an active tab.
	//if not, we don't want the "lastasavedtab" to be focued
	
	if ( this.tabs[frameID]["state"] != "Active")
		folderTab = this.currentFrameID;
	
    //update the tabs object
    for(var x=0; x < removeFrameIDs.length; x++){
    	delete this.tabs[removeFrameIDs[x]];	
    }
    
    this.jqtab.trigger('tabsremove',[null,null]);
    
    
    var obj;
    
    if (this.isSearch ){
		obj = objTabBarSearch;
		obj.ChangeState("mainsearchtab", true);
		return;
	}else{
		obj = objTabBar;
	}
    
    if (this.disableFocusOnClose  )
    	return;
    
    frames = $(fs).find("frame");
    if (frames.length > 1) {
    		var lastid;
    		if ( !folderTab || folderTab == "" )
    			lastid = frames[frames.length-1].id;
    		else
    			lastid = folderTab;
    		
    		objTabBar.ChangeState(lastid, true)
    }else{
    		this.NoFrameOpen("");
    	}
}


this.NoFrameOpen = function(url){
	var leftNav = window.parent.fraLeftFrame;
	if ( url == ""){
		url = leftNav.docInfo.ServerUrl + "/" + leftNav.docInfo.NsfName + "/" +  "BlankFolderSelected?OpenPage";
		
	}
	var obj = parent.frames["fraContentTop"];
	window.parent.fsContentFrameset.rows = "0,*,0";	//minimize the preview frame
	obj.location.href = url;
	var folderObj = leftNav.document.all.DLITFolderView;
	if ( folderObj){
		folderObj.OpenFolder("null", false);
	}
}

this.DoClose = function(sourceID){
	  var fs = window.parent.fsContentFrameset;  //global reference n/a, set new variable
	  
	  if ( this.isSearch )
		  fs = window.parent.fsContentFramesetSearch;
	  
	  var currFrameID = "";
	  var docInfo = null;
	  var startind = 3;
	  if ( this.isSearch ) startind = 1;
	  startind = 0;
	  frames = $(fs).find("frame");
	  for ( var x = startind; x < frames.length; x++ ){
			currFrameID = frames[x].id
			if ( currFrameID == sourceID ){
				var cd = frames[x];
				
				try{
					var cddoc = cd.contentWindow.document;
					docInfo = cd.contentWindow.getinfovar();
				
					var cdwin = cd.contentWindow;
					if ( docInfo && docInfo.isDocBeingEdited){
						this.ChangeState(sourceID, false);
						var ans= cdwin.SaveBeforeClosing();
						if(ans==6)
							{
								cdwin.HandleSaveClick();
							}
						else if(ans==7)
						{
							cdwin.CloseDocument();
						}
						else
						{
							return false;
						}
						return true;
					}
				}catch(error){
					
				}
				
			}
		}
	  	if ( this.isSearch )
	  		objTabBarSearch.CloseTab(sourceID);
	  	else
	  		objTabBar.CloseTab(sourceID);
	  	return true;
}
   
//------ capture and process the captured event -------------------------------------------
this.HandleEvent = function(e) {

	var tabEvent;
	var obj;
	if (typeof objTabBarSearch != 'undefined' ){
		obj = objTabBarSearch;
		tabEvent = window.parent.fraTabbedTableSearch.event;
	}else{
		obj = objTabBar;
		tabEvent = window.parent.fraTabbedTable.event || e;
	}	
	
	var eventSource = tabEvent.srcElement || tabEvent.target;
	var sourceId = eventSource.id;

	if(eventSource.tagName == "SPAN" && $(eventSource).hasClass("ui-icon-circle-close")){
		obj.DoClose(sourceId);
	} else {
		var tmpparent = eventSource.parentElement;
		while (tmpparent.nodeName.toLowerCase() != "li")
			tmpparent = tmpparent.parentElement
		sourceId = tmpparent.id;
	
		obj.ChangeState(sourceId);
	}
}

this.CloseAll = function () {
	var p =confirm("Close All Tabs");
	if ( p == false) return;
	var endIndex = 2;
	 if ( this.isSearch ) endIndex = 1;
	
	var toRemove = new Array();
	var j = 0;
	var frams = $(this.frameSet).find("frame");
	for ( var x = frams.length-1; x > endIndex; x-- ){
		var frameId = frams[x].id;
		toRemove[j] = frameId;
		j++;
		//this.CloseTab(frameId);
	}
	
	for ( var y = 0; y<j; y++){
		if (! this.DoClose(toRemove[y])) return;
	}
	
}


} //======== End of the Tab Bar Object ================================================