var _DOCOVAEdition = "SE";  //-- set this variable to either SE or Domino depending upon where this code is installed

/* Global variables */
var rtime = new Date();
var timeout = false;
var delta = 200;
var DEBUG = false;
var DLITFolderView = null;
var Docova = window.top.Docova;
var dlgParams = new Array(); //params array that gets used by dialogs

/* Return copy of folder control object */
function getFolderControl() {
	return DLITFolderView;
}

/* Initializes folder control */
function Initialize() {
	document.oncontextmenu = function () {
		return (false)
	};
	InitVars(info);

	//-- create new folder control
	DLITFolderView = new FolderControl("#divTreeView", foldercontrolconfig);
	if (!DLITFolderView) {
		return false;
	}

	//-- update tab control and buttons for folder area --
	jQuery("#divFolderControlTabs").tabs({
		beforeActivate: function (event, ui) {},
		activate: function (event, ui) {
			if (ui.newTab.index() == 0) {
				//-- focus on current folder
				var curnode = DLITFolderView.jstree.get_node(DLITFolderView.jstree.get_selected(false)[0]);
				if (curnode) {
					DLITFolderView.jstree.get_node(curnode, true).children('.jstree-anchor')[0].focus();
				}
			}
		}
	});
	jQuery("#divFolderControlTabs").find(".ui-tabs-nav").css("height", "30px");
	jQuery("#btnSubscribe").button({
		text: false,
		icons: {
			primary: 'ui-icon-home'
		}
	}).css({
		height: '25px',
		width: '25px',
		'padding-top': '0px',
		'padding-bottom': '0px'
	});
	jQuery("#btnRefreshLibs").button({
		text: false,
		icons: {
			primary: 'ui-icon-refresh'
		}
	}).css({
		height: '25px',
		width: '25px',
		'padding-top': '0px',
		'padding-bottom': '0px'
	});
	jQuery("#divFolderControlTabContainer").show();
	jQuery("#divFolderControl").css("border", DLITFolderView.Border + "px" + " " + DLITFolderView.BorderStyle + " " + DLITFolderView.BorderColor);
	jQuery("ul").css("border-bottom-right-radius", "0px");
	jQuery("ul").css("border-top-right-radius", "0px");

	//-- resize folder pane
	adjustFolderPaneSize();
	jQuery(window).resize(function () {
		resizeStart(adjustFolderPaneSize);
	});

	//-- load favorites
	DLITFolderView.LoadFavorites();

} //-- end Initialize


/*********************************************************************************************
 *********************************************************************************************
 * object: FolderControl
 * Description: Instantiates a new folder control object. contains jstree instance
 * Inputs: targetid - string - id of target div element where folder control will be inserted
 *             params - json  - list of configuration parameter data value pairs
 * Output: object - folder control object, or false if control is not instantiated
 *********************************************************************************************
 *********************************************************************************************/
function FolderControl(targetid, params) {
	var parentobject = this;

	if (!targetid) {
		return false;
	}
	targetid = (targetid.charAt(0) == "#") ? targetid : "#" + targetid;

	//-- set defaults for properties
	this.ignoreevent = false;
	this.librarynode = null;
	this.selectednode = null;
	this.AllowLabelEdit = false; //todo add support
	this.Border = 0;
	this.BorderStyle = "";
	this.BorderColor = "";
	this.CurrentFolderAccess = {};
	this.CurrentFolderID = "";
	this.CurrentFolderKey = "";
	this.CurrentFolderName = "";
	this.CurrentLibraryID = "";
	this.CurrentLibraryNsf = "";
	this.CurrentLibraryUrl = "";
	this.CurrentPosition = "";
	this.CurrentUNID = "";
	this.DocumentService = "";
	this.FavoritesData = null;
	this.FavoritesUrl = "";
	this.FavoritesService = "";
	this.FolderAccessUrl = "";
	this.FolderDataOverride = null;
	this.FolderPropertiesUrl = "";
	this.FolderOpenUrl = "";
	this.FolderService = "";
	this.ImagePath = "";
	this.IsDocumentSelected = false;
	this.IsFolderSelected = false;
	this.IsInitialized = false;
	this.IsLibrarySelected = false;
	this.IsLoading = false;
	this.isSymfony = 0;
	this.LibraryCount = 0;
	this.LibraryUrl = "";
	this.LoadAllAtStartup = false;
	this.LoadUrl = "";
	this.LoadUrlPartial = "";
	this.LoadDocsUrl = "";
	this.MaxHeight = 0;
	this.NewFolderName = "";
	this.NewNodeID = "";
	this.NodeType = "";
	this.ReadLimit = 0;
	this.ShowCheckBoxes = false;
	this.SourceFolderUnid = "";
	this.SourceFolderKey = "";
	this.SourceLibraryID = "";
	this.TargetFolderUnid = "";
	this.TargetFolderKey = "";
	this.TargetLibraryID = "";
	this.UrlType = 0;
	this.WasFolderCut = false;

	this.onFoldersReady = "";

	this.onBeforeAddFavorite = "";
	this.onAddFavorite = "";
	this.onAfterAddFavorite = "";

	this.onBeforeAddFiles = "";
	this.onAddFiles = "";
	this.onAfterAddFiles = "";

	this.onBeforeDeleteFavorite = "";
	this.onDeleteFavorite = "";
	this.onAfterDeleteFavorite = "";

	this.onBeforeFolderCopy = "";
	this.onFolderCopy = "";
	this.onAfterFolderCopy = "";

	this.onBeforeDocumentCreate = "";
	this.onDocumentCreate = "";

	this.onBeforeFolderCreate = "";
	this.onFolderCreate = "";
	this.onAfterFolderCreate = "";

	this.onBeforeFolderCut = "";
	this.onFolderCut = "";
	this.onAfterFolderCut = "";

	this.onBeforeFolderDelete = "";
	this.onFolderDelete = "";
	this.onAfterFolderDelete = "";

	this.onBeforeDocumentDelete = "";
	this.onDocumentDelete = "";
	this.onAfterDocumentDelete = "";

	this.onBeforeFolderPaste = "";
	this.onFolderPaste = "";
	this.onAfterFolderPaste = "";

	this.onBeforeFolderRename = "";
	this.onFolderRename = "";
	this.onAfterFolderRename = "";

	this.onBeforeOpenInNewTab = "";
	this.onOpenInNewTab = "";
	this.onAfterOpenInNewTab = "";

	this.onBeforeCopyFolderLink = "";
	this.onCopyFolderLink = "";
	this.onAfterCopyFolderLink = "";

	this.onBeforeExpandAll = "";
	this.onAfterExpandAll = "";

	this.onClick = "";
	this.onDoubleClick = "";

	this.onPropertiesMenu = "";
	this.onSubscriptionClick = "";

	//-- update any properties based on supplied params
	if (params) {
		for (var param in params) {
			this[param] = params[param];
		}
	}

	/*********************************************************************************************
	 * method: guid
	 * Description: generates a unique id
	 *********************************************************************************************/
	this.guid = function () {
		this.s4 = function () {
			return Math.floor((1 + Math.random()) * 0x10000)
			.toString(16)
			.substring(1);
		};

		return this.s4() + this.s4() + '-' + this.s4() + '-' + this.s4() + '-' + this.s4() + '-' + this.s4() + this.s4() + this.s4();
	} //--end guid

	/*********************************************************************************************
	 * method: hashCode
	 * Description: generates a hash code based on a string value
	 *********************************************************************************************/
	this.hashCode = function (stringtohash) {
		var hash = 0;
		var i;
		var chr;
		var len;
		if (stringtohash == undefined || stringtohash == null || stringtohash.length == 0){
			return hash;
		}
		for (i = 0, len = stringtohash.length; i < len; i++) {
			chr = stringtohash.charCodeAt(i);
			hash = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return hash;
	} //--end hashCode


	/*********************************************************************************************
	 * method: callCustomFunctions
	 * Description: triggers custom functions for selected node based on event name
	 * Inputs: eventname - string - name of configuration event name that lists function
	 *              callback - function - optional - function to be called at end of custom function
	 *              data - object - optional - data variable or array of variables to be passed to
	 *                          the custom function
	 * Output: various - false if errors - otherwise returns result of custom function
	 **********************************************************************************************/
	this.callCustomFunctions = function (eventname, cb, data) {
		var result = true;

		if (parentobject[eventname] && parentobject[eventname] != "") {
			var fn = window[parentobject[eventname]];
			try {
				result = fn(cb, data);
			} catch (e) {
				result = false;
				alert("Error processing custom " + eventname + " function: " + parentobject[eventname]);
			}
		}
		return result;
	} //--end callCustomFunctions


	/*********************************************************************************************
	 * method: storeSelectedNode
	 * Description: stores information about a selected entry in the tree
	 * Inputs: node - object (optional) - selected node, if not specified will be retrieved
	 *********************************************************************************************/
	this.storeSelectedNode = function (node) {
		if (DEBUG && typeof console == "object") {
			console.log("storeSelectedNode->start")
		};

		parentobject.selectednode = (!node) ? parentobject.jstree.get_node(parentobject.jstree.get_selected(false)[0]) : node;
		parentobject.NodeType = (parentobject.selectednode ? ((parentobject.selectednode.data && parentobject.selectednode.data.NodeType && parentobject.selectednode.data.NodeType != "") ? parentobject.selectednode.data.NodeType : "folder") : "");
		parentobject.CurrentUNID = ((!parentobject.selectednode || parentobject.NodeType == "realm" || parentobject.NodeType == "community") ? "" : ((parentobject.selectednode.data && parentobject.selectednode.data.Unid) ? parentobject.selectednode.data.Unid : parentobject.selectednode.id.slice(2)));

		parentobject.IsFolderSelected = (parentobject.NodeType === "folder");
		parentobject.IsDocumentSelected = (parentobject.NodeType === "document");
		parentobject.IsLibrarySelected = (parentobject.NodeType === "library");

		parentobject.CurrentFolderID = (parentobject.IsFolderSelected && parentobject.selectednode.id) ? parentobject.selectednode.id : "";
		parentobject.CurrentFolderKey = (parentobject.IsFolderSelected && parentobject.selectednode.data && parentobject.selectednode.id) ? parentobject.selectednode.id : "";

		parentobject.CurrentFolderName = (parentobject.selectednode && parentobject.selectednode.text) ? parentobject.selectednode.text : "";

		librarynode = parentobject.getParentNodeByType(parentobject.selectednode, "library", true);
		parentobject.librarynode = librarynode;
		parentobject.CurrentLibraryID = (librarynode && librarynode.data && librarynode.data.DocKey) ? librarynode.data.DocKey : "";
		parentobject.CurrentLibraryNsf = (librarynode && librarynode.data && librarynode.data.NsfName) ? librarynode.data.NsfName : "";
		parentobject.CurrentLibraryUrl = (librarynode && librarynode.data && librarynode.data.NsfName) ? librarynode.data.NsfName : "";

		parentobject.CurrentFolderAccess = ((parentobject.IsFolderSelected) ? parentobject.getFolderAccessData(parentobject.selectednode) : parentobject.getFolderAccessData(null));

		parentobject.FolderOpenUrl = (parentobject.selectednode && parentobject.selectednode.data && parentobject.selectednode.data.FolderOpenUrl) ? parentobject.selectednode.data.FolderOpenUrl : "";

		return true;
	} //--end storeSelectedNode

	/*********************************************************************************************
	 * method: getFolderAccessData
	 * Description: retrieves folder access data for a specified folder
	 * Inputs: obj - node object - folder node being queried
	 *********************************************************************************************/
	this.getFolderAccessData = function (obj) {
		folderaccessdata = {
			"DbAccessLevel": -1,
			"DocAccessLevel": -1,
			"IsRecycleBin": false,
			"IsDeleted": false,
			"CanCreateDocuments": false,
			"CanCreateRevisions": false,
			"CanDeleteDocuments": false,
			"CanSoftDeleteDocuments": false,
			"AuthorsCanNotCreateFolders": true,
			"DocAccessRole": ""
		};

		if ((parentobject.FolderAccessUrl == "") || !(obj && obj.id && obj.data.Unid)) {
			return folderaccessdata;
		}

		var libnode = parentobject.getParentNodeByType(obj, "library", false);
		var liburl = ((libnode && libnode.data && libnode.data.NsfName) ? libnode.data.NsfName : parentboject.CurrentLibraryUrl);

		var accessurl = liburl + parentobject.FolderAccessUrl + obj.data.Unid;

		jQuery.ajax({
			type: "GET",
			url: accessurl,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				var xmlobj = jQuery(xml);

				var dbaccesslevel = parseInt(xmlobj.find("DbAccessLevel").text());
				folderaccessdata.DbAccessLevel = isNaN(dbaccesslevel) ? -1 : dbaccesslevel;

				var docaccesslevel = parseInt(xmlobj.find("DocAccessLevel").text());
				folderaccessdata.DocAccessLevel = isNaN(docaccesslevel) ? -1 : docaccesslevel;

				folderaccessdata.IsRecycleBin = (xmlobj.find("IsRecycleBin").text() == "1");
				folderaccessdata.IsDeleted = (xmlobj.find("IsDeleted").text() == "1");
				folderaccessdata.CanCreateDocuments = (xmlobj.find("CanCreateDocuments").text() == "1");
				folderaccessdata.CanCreateRevisions = (xmlobj.find("CanCreateRevisions").text() == "1");
				folderaccessdata.CanDeleteDocuments = (xmlobj.find("CanDeleteDocuments").text() == "1");
				folderaccessdata.CanSoftDeleteDocuments = (xmlobj.find("CanSoftDeleteDocuments").text() == "1");
				folderaccessdata.AuthorsCanNotCreateFolders = (xmlobj.find("AuthorsCanNotCreateFolders").text() == "1");

				folderaccessdata.DocAccessRole = xmlobj.find("DocAccessRole").text();
			}
		});
		return folderaccessdata;
	} //--end getFolderAccessData


	/*********************************************************************************************
	 * method: expandNodeBranch
	 * Description: expands the parent branches of a node
	 *********************************************************************************************/
	this.expandNodeBranch = function (obj) {
		obj = obj.parents ? obj : parentobject.jstree.get_node(obj);

		if (!obj || obj.id === '#') {
			if (DEBUG && typeof console == "object") {
				console.log("expandNodeBranch->no target node specified or root tree node specified.")
			};
			return false;
		}

		if (!obj.parents) {
			if (DEBUG && typeof console == "object") {
				console.log("expandNodeBranch->no parent nodes for specified node.")
			};
			return false;
		}

		for (var i = 0; i < obj.parents.length; i++) {
			var parentnode = parentobject.jstree.get_node(obj.parents[i]);
			if (!parentnode) {
				if (DEBUG && typeof console == "object") {
					console.log("expandNodeBranch->parent node could not be found.")
				};
				return false;
			}

			if (DEBUG && typeof console == "object") {
				console.log("expandNodeBranch->calling open_node for node id=" + parentnode.id);
			};
			parentobject.jstree.open_node(parentnode);
		}

		return true;
	} //--end expandNodeBranch


	/*********************************************************************************************
	 * method: loadFolderPath
	 * Inputs: libnodeorid - object or string - library node or id
	 *		   targetnodeid - string - id of target node we are loading
	 *		   ancestors - array (optional) - string array of ancestor node ids
	 *		   cb - callback function (optional) - function to call on completion
	 *		        receives a true/false parameter to indicate if node was found
	 * Description: loads the tree path to a specified library folder
	 *********************************************************************************************/
	this.loadFolderPath = function (libnodeorid, targetnodeid, ancestors, cb) {
		var targetnode = parentobject.jstree.get_node(targetnodeid);
		if (targetnode) {
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->target node has been loaded.")
			};
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->calling expandNodeBranch to expand tree to targetnode.")
			};
			if (parentobject.expandNodeBranch(targetnode)) {
				parentobject.jstree.deselect_all(false);
				parentobject.jstree.select_node(targetnode, false, true);
				if (cb && (typeof(cb) === "function")) {
					cb(true);
				}
			}
			return; //we have a targetnode
		}

		//-- use library node object if passed otherwise look up library by id
		var libnode = (libnodeorid && libnodeorid.id) ? libnodeorid : parentobject.jstree.get_node(libnodeorid);
		if (!libnode) {
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->get_node(" + libnodeorid + ") did not find the library node")
			};
			if (cb && (typeof(cb) === "function")) {
				cb(false);
			}
			return; //-- quit since library cannot be located
		}

		//-- check if library root folders are loaded
		if (!parentobject.jstree.is_loaded(libnode)) {
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->library " + libnode.id + " not loaded.  Call load_node() to load root folders.")
			};
			//-- load the library root folders
			parentobject.jstree.load_node(libnode, function (loadednode, loadstatus) {
				if (DEBUG && typeof console == "object") {
					console.log("loadFolderPath->loadstatus=" + loadstatus)
				};
				if (loadstatus) {
					if (DEBUG && typeof console == "object") {
						console.log("loadFolderPath->calling loadFolderpath with targetnodeid:" + targetnodeid + " and ancestors: " + (ancestors ? ancestors.toString() : ""))
					};
					result = parentobject.loadFolderPath(libnode, targetnodeid, ancestors, cb);
				}
			});
			return; //-- quit early since we dont want to process anything else until library root folders loaded
		}

		//-- not a root node so lets look at the nodes ancestors
		if (ancestors == undefined || ancestors == null) {
			ancestors = [];
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->folder ancestors undefined - attempting to retrieve values.")
			};
			var parentids = null;
			var libPath = libnode.data.NsfName;
			//--------- get folder ancestors from folder properties -----------
			var folderpropUrl = libPath + parentobject.FolderPropertiesUrl + targetnodeid;
			jQuery.ajax({
				type: "GET",
				url: folderpropUrl,
				cache: false,
				async: false,
				dataType: "xml",
				success: function (xml) {
					var xmlobj = jQuery(xml);
					parentids = xmlobj.find("FolderAncestors").text();
					if (parentids && parentids != "") {
						var delims = [":", ";", ","];
						for (var d = 0; d < delims.length; d++) {
							if (parentids.indexOf(delims[d]) > -1) {
								parentids = parentids.split(delims[d]);
								break;
							}
						}
						if (!jQuery.isArray(parentids)) {
							parentids = parentids.split(":");
						}
					}
				}
			}); //-- end ajax
			if (parentids && parentids.length > 0) {
				for (var i = parentids.length - 1; i > -1; i--) {
					parentids[i] = parentids[i].trim();
				}
			}
			ancestors = parentids;
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->folder ancestors=" + ((ancestors && ancestors.length > 0) ? ancestors.toString() : "null"))
			};
		}

		//-- check to see if any ancestor nodes defined that need checking
		if (ancestors != undefined && ancestors != null && ancestors.length > 0) {
			var ancestortocheck = ancestors.shift();
			var ancestornode = parentobject.jstree.get_node(ancestortocheck);
			if (!ancestornode) {
				if (DEBUG && typeof console == "object") {
					console.log("loadFolderPath->folder ancestor node not found.")
				};
				return;
			}
			parentobject.jstree.load_node(ancestornode, function (loadednode, loadstatus) {
				if (DEBUG && typeof console == "object") {
					console.log("loadFolderPath->loadstatus=" + loadstatus)
				};
				if (loadstatus) {
					if (DEBUG && typeof console == "object") {
						console.log("loadFolderPath->calling loadFolderPath")
					};
					parentobject.loadFolderPath(libnode, targetnodeid, ancestors, cb);
				}
			});
			return;
		} else {
			if (DEBUG && typeof console == "object") {
				console.log("loadFolderPath->no folder ancestors left.")
			};
			//-- following code is a duplicate of logic at start of function
			//-- but repeated here so that the function can fall out if no node found
			//-- rather than making a recursive call which may loop indefinitely
			var targetnode = parentobject.jstree.get_node(targetnodeid);
			if (targetnode) {
				if (DEBUG && typeof console == "object") {
					console.log("loadFolderPath->target node has been loaded.")
				};
				if (DEBUG && typeof console == "object") {
					console.log("loadFolderPath->calling expandNodeBranch to expand tree to targetnode.")
				};
				if (parentobject.expandNodeBranch(targetnode)) {
					parentobject.jstree.deselect_all(false);
					parentobject.jstree.select_node(targetnode, false, true);
					if (cb && (typeof(cb) === "function")) {
						cb(true);
					}
				}
				return; //we have a targetnode
			} else {
				if (cb && (typeof(cb) === "function")) {
					cb(false);
				}
			}
		}
		return;
	} //--end loadFolderPath


	/*********************************************************************************************
	 * method: getParentNodeByType
	 * Description: retrieves a particular node in the parent tree by type
	 *********************************************************************************************/
	this.getParentNodeByType = function (obj, nodetype, includecurrentnode) {
		obj = obj.parents ? obj : parentobject.jstree.get_node(obj);

		if (!obj || obj.id === '#') {
			return false;
		}

		if (includecurrentnode && obj.data && obj.data.NodeType && obj.data.NodeType == nodetype) {
			return obj;
		}

		if (!obj.parents) {
			return false;
		}

		//-- if searching for a top level node type we should search from top down for speed
		if (nodetype == "library" || nodetype == "community" || nodetype == "realm") {
			for (var i = obj.parents.length - 1; i >= 0; i--) {
				var parentnode = parentobject.jstree.get_node(obj.parents[i]);
				if (!parentnode) {
					return false;
				}
				if (parentnode.data && parentnode.data.NodeType && parentnode.data.NodeType == nodetype) {
					return parentnode;
				}
			}
		} else {
			for (var i = 0; i < obj.parents.length; i++) {
				var parentnode = parentobject.jstree.get_node(obj.parents[i]);
				if (!parentnode) {
					return false;
				}
				if (parentnode.data && parentnode.data.NodeType && parentnode.data.NodeType == nodetype) {
					return parentnode;
				}
			}
		}

		return false;
	} //--end getParentNodeByType


	/********************************************************************************************
	 * method: loadChildNodes
	 * Description: triggers the retrieval of child nodes of specified type for parent node
	 * Inputs: obj - node object or id string - folder node or id to load children for
	 *             nodetype - string - type of child nodes to trigger data loading for
	 *********************************************************************************************/
	this.loadChildNodes = function (obj, nodetype) {
		if (DEBUG && typeof console == "object") {
			console.log("loadChildNodes->parent node id=" + obj.id + " text=" + ((obj.text) ? obj.text : ""))
		};
		var result = false;
		obj = obj.id ? obj : parentobject.jstree.get_node(obj);

		if (obj.children && obj.children.length > 0) {
			for (var x = 0, y = obj.children.length; x < y; x++) {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildNodes->parent node id=" + obj.id + " text=" + ((obj.text) ? obj.text : "") + "-> x=" + x.toString() + " child node id=" + obj.children[x])
				};
				var childobj = parentobject.jstree.get_node(obj.children[x]);
				if (childobj) {
					if (DEBUG && typeof console == "object") {
						console.log("loadChildNodes->parent node id=" + obj.id + " text=" + ((obj.text) ? obj.text : "") + "-> x=" + x.toString() + " childobj.id=" + childobj.id + " childobj.text=" + ((childobj.text) ? childobj.text : ""))
					};
					if (nodetype && childobj.data && childobj.data.NodeType && childobj.data.NodeType == nodetype) {
						if (DEBUG && typeof console == "object") {
							console.log("loadChildNodes->parent node id=" + obj.id + " text=" + ((obj.text) ? obj.text : "") + "-> x=" + x.toString() + " childobj.id=" + childobj.id + " childobj.text=" + ((childobj.text) ? childobj.text : "") + "-> calling load_node")
						};
						parentobject.jstree.load_node(childobj);
						result = true;
					}
					result = parentobject.loadChildNodes(childobj, nodetype) || result;
				}
			}
		}
		return result;
	} //-- end loadChildNodes

	/*********************************************************************************************
	 * method: LoadFoldersFromXML
	 * Description: loads folder data from an xml object or xml string
	 * Inputs: xmldata - xml data object or xml string containing folder data
	 *         libraryname - string (optional) - name to use for root/parent folder
	 *         libraryicon - string (optional) - icon to use for root/parent folder
	 * Output: none
	 *********************************************************************************************/
	this.LoadFoldersFromXML = function (xmldata, libraryname, libraryicon) {
		if (xmldata == undefined || xmldata == null || xmldata === "") {
			alert("Error loading folder data. No folder data passed to function.");
			return false;
		}
		if (libraryname == undefined || libraryname == null) {
			var libraryname = "";
		}
		if (libraryicon == undefined || libraryicon == null || libraryicon === "") {
			var libraryicon = "FIcon_Mail.png";
		}

		var $folderData = null;
		if (typeof xmldata == "string") {
			$folderData = jQuery(jQuery.parseXML(xmldata));
		} else {
			$folderData = jQuery(xmldata);
		}

		if ($folderData == null || $folderData.length == 0) {
			alert("Error loading folder data. No folder data returned.");
			return false;
		}

		var $viewEntries = $folderData.find("viewentry");
		if ($viewEntries == null || $viewEntries.length == 0) {
			alert("Error loading folder data. No folder data returned.");
			return false;
		}

		var jsonFolders = [];

		var jsonLib = null;
		if (libraryname != "") {
			jsonLib = {
				"id": "",
				"parent": "",
				"text": "",
				"icon": "",
				"state": {
					"opened": true,
					"disabled": false,
					"selected": false
				},
				"li_attr": {},
				"a_attr": {},
				"data": {}
			};
			jsonLib.id = parentobject.guid();
			jsonLib.parent = "#";
			jsonLib.text = libraryname;
			jsonLib.data.NodeType = "library";
			jsonLib.a_attr["class"] = "docova-" + jsonLib.data.NodeType + "-label";
			jsonLib.icon = libraryicon;
			jsonLib.data.NsfName = "";
			jsonLib.data.ArchiveNsfName = "";
			jsonLib.data.Title = jsonLib.text;
			jsonLib.data.Description = "";
			jsonLib.data.DocKey = jsonLib.id;
			jsonLib.data.FolderID = jsonLib.data.DocKey;
			jsonLib.data.Unid = "";
			jsonLib.data.Community = "";
			jsonLib.data.Realm = "";
			if (parentobject.UrlType == 1) {
				jsonLib.data.noselect = true; // disable selection for root node in mail folder type urls
			}

			jsonFolders.push(jsonLib);
		}

		$viewEntries.each(function (idx, viewentry) {
			var sFolderName = "";
			var sParentPath = "";
			var sFolderIcon = "";
			var sSortOrder = "";
			var sFolderType = "";

			if (jQuery(viewentry).find("entrydata[name=FolderIcon]").length > 0) {
				sFolderIcon = jQuery(viewentry).find("entrydata[name=FolderIcon]").text().trim();
			}

			var sFolderPath = jQuery(viewentry).find("entrydata[name=FolderName]").text().trim();

			if (jQuery(viewentry).find("entrydata[name=SortOrder]").length > 0) {
				sSortOrder = jQuery(viewentry).find("entrydata[name=SortOrder]").text().trim();
			}

			if (jQuery(viewentry).find("entrydata[name=FolderType]").length > 0) {
				sFolderType = jQuery(viewentry).find("entrydata[name=FolderType]").text().trim();
			}

			var sFolderUnid = "";
			if (jQuery(viewentry).find("entrydata[name=FolderID]").length > 0) {
				sFolderUnid = jQuery(viewentry).find("entrydata[name=FolderID]").text().trim();
			} else {
				if (jQuery(viewentry).attr("unid").length > 0) {
					sFolderUnid = jQuery(viewentry).attr("unid").trim();
				}
			}

			if (sFolderPath.slice(-1) == "\\") {
				sFolderPath = sFolderPath.slice(sFolderPath.length - 1);
			}
			if (sFolderPath.slice(1) == "\\") {
				sFolderPath = sFolderPath.slice(1);
			}
			var pos = sFolderPath.lastIndexOf("\\");
			if (pos > -1) {
				sFolderName = sFolderPath.slice(pos + 1);
				sParentPath = sFolderPath.slice(0, pos);
			} else {
				sFolderName = sFolderPath;
				sParentPath = "";
			}
			var parentid = "#";
			if (jsonLib && jsonLib.id) {
				parentid = jsonLib.id;
			}
			if (sParentPath != "") {
				parentid = parentobject.hashCode(sParentPath).toString();
			}

			var folderid = parentobject.hashCode(sFolderPath).toString();

			var jsonFolder = {
				"id": folderid,
				"parent": parentid,
				"text": sFolderName,
				"icon": sFolderIcon,
				"state": {
					"opened": true,
					"disabled": false,
					"selected": false
				},
				"li_attr": {},
				"a_attr": {},
				"data": {
					"FolderID": folderid,
					"Unid": sFolderUnid,
					"FolderOpenUrl": "",
					"sortorder": sSortOrder,
					"noselect": (parentobject.UrlType == 1 && sFolderType == "library" ? true : false), // disable selection for root node in mail folder type urls
					"NodeType": sFolderType
				}
			};

			jsonFolders.push(jsonFolder);
		});

		//-- set an override so that when refresh is called the following data will be used instead
		//-- of a typical library folder data load
		parentobject.FolderDataOverride = function () {
			return jsonFolders;
		}
		//-- refresh the tree - code in the refresh will use the override data set above
		parentobject.jstree.refresh();

	} //--end LoadFoldersFromXML

	/*********************************************************************************************
	 * method: LoadLibraries
	 * Description: retrieves a list of subscribed libraries and any parent categories
	 * Inputs: cb - function - callback function to return the data to
	 * Output: none
	 *********************************************************************************************/
	this.LoadLibraries = function (cb) {
		var librarycount = 0;

		jQuery.ajax({
			type: "GET",
			url: parentobject.LibraryUrl,
			cache: false,
			dataType: "xml",
			success: function (xml) {
				var jsonData = [];
				var Categories = [];
				var jsonLibraries = [];
				var jsonCategory = [];
				jQuery(xml).find("Library").each(function () {
					var $lib = jQuery(this);
					var jsonLib = {
						"id": "",
						"parent": "",
						"text": "",
						"icon": "",
						"state": {
							"opened": false,
							"disabled": false,
							"selected": false
						},
						"li_attr": {},
						"a_attr": {},
						"data": {}
					};
					jsonLib.id = $lib.find('DocKey').text();
					jsonLib.parent = "#";
					jsonLib.text = $lib.find('Title').text();
					jsonLib.data.NodeType = "library";
					jsonLib.children = true; //-- library node will always have children even if only the trash bin
					jsonLib.a_attr["class"] = "docova-" + jsonLib.data.NodeType + "-label";
					jsonLib.icon = "docova-default docova-" + jsonLib.data.NodeType;
					jsonLib.data.NsfName = $lib.find('NsfName').text();
					jsonLib.data.ArchiveNsfName = $lib.find('ArchiveNsfName').text();
					jsonLib.data.Title = jsonLib.text;
					jsonLib.data.Description = $lib.find('Description').text();
					jsonLib.data.DocKey = jsonLib.id;
					jsonLib.data.FolderID = jsonLib.data.DocKey;
					jsonLib.data.Unid = $lib.find('Unid').text();
					jsonLib.data.Community = $lib.find('Community').text();
					jsonLib.data.Realm = $lib.find('Realm').text();
					jsonLib.data.LoadDocsAsFolders = ($lib.find('LoadDocsAsFolders').text() === "1" ? true : false);

					var communitytext = jsonLib.data.Community;
					var realmtext = jsonLib.data.Realm;
					if (communitytext != "") {
						if (!Categories[communitytext]) {
							Categories[communitytext] = {
								"id": parentobject.guid(),
								"text": communitytext,
								"data": {
									"NodeType": "community"
								},
								"children": new Array()
							};
						}
						//-- update library to fall beneath the community
						jsonLib.parent = Categories[communitytext].id;
					}
					if (realmtext != "") {
						if (communitytext == "") {
							if (!Categories[realmtext]) {
								Categories[realmtext] = {
									"id": parentobject.guid(),
									"text": realmtext,
									"data": {
										"NodeType": "realm"
									},
									"children": new Array()
								};
							}
							//-- update library to fall beneath the realm
							jsonLib.parent = Categories[realmtext].id;
						} else {
							if (!Categories[communitytext].children[realmtext]) {
								Categories[communitytext].children[realmtext] = {
									"id": parentobject.guid(),
									"text": realmtext,
									"data": {
										"NodeType": "realm"
									},
									"children": new Array()
								};
							}
							//-- update library to fall beneath the community/realm
							jsonLib.parent = Categories[communitytext].children[realmtext].id;
						}
					}

					//-- add the library node to the library array
					jsonLibraries.push(jsonLib);
					librarycount++;
				});

				for (var key in Categories) {
					if (Categories.hasOwnProperty(key)) {
						var category = Categories[key];
						var jsonCategory = {
							"id": "",
							"parent": "",
							"text": "",
							"icon": "",
							"state": {
								"opened": false,
								"disabled": false,
								"selected": false
							},
							"li_attr": {},
							"a_attr": {},
							"data": {}
						};
						jsonCategory.id = category.id;
						jsonCategory.parent = "#";
						jsonCategory.text = category.text;
						jsonCategory.a_attr["class"] = "docova-" + category.data.NodeType + "-label";
						jsonCategory.icon = "docova-default docova-" + category.data.NodeType;
						jsonCategory.data.NodeType = category.data.NodeType;
						jsonCategory.data.FolderID = "";
						jsonCategory.data.Unid = "";

						jsonData.push(jsonCategory);

						var Realms = category.children;
						for (var key2 in Realms) {
							if (Realms.hasOwnProperty(key2)) {
								var realm = Realms[key2];
								var jsonRealm = {
									"id": "",
									"parent": "",
									"text": "",
									"icon": "",
									"state": {
										"opened": false,
										"disabled": false,
										"selected": false
									},
									"li_attr": {},
									"a_attr": {},
									"data": {}
								};
								jsonRealm.id = realm.id;
								jsonRealm.parent = jsonCategory.id;
								jsonRealm.text = realm.text;
								jsonRealm.a_attr["class"] = "docova-" + realm.data.NodeType + "-label";
								jsonRealm.icon = "docova-default docova-" + realm.data.NodeType;
								jsonRealm.data.NodeType = realm.data.NodeType;
								jsonCategory.data.FolderID = "";
								jsonCategory.data.Unid = "";

								jsonData.push(jsonRealm);
							}
						}
					}
				}
				parentobject.LibraryCount = librarycount;
				jsonData = jsonData.concat(jsonLibraries);
				cb.call(this, jsonData); //return data
			}
		}); //close ajax
	} //--end LoadLibraries

	/*********************************************************************************************
	 * method: LoadLibrary
	 * Description: triggers the loading/reloading of folders for a library already in the tree
	 * Inputs: libnode - node object or string - library node object or id of library node
	 *             cb - function - a call back function to be called once loading completes
	 *                     accepts two arguments - node and boolean status
	 * Output: none
	 *********************************************************************************************/
	this.LoadLibrary = function (libnode, cb) {
		parentobject.jstree.load_node(libnode, cb);
	} //--end LoadLibrary


	/*********************************************************************************************
	 * method: loadLibraryFolders
	 * Description: retrieves a list of folders for a specified library
	 *                      recursive function
	 * Inputs: jsonLib - object - library to load folders for
	 *             cb - function - call back function that will process the folders
	 *             partialdata - json array - (optional) existing folders already retrieved by
	 *                                 parent function. not required on initial call to function
	 *             start - long - (optional) starting number in index to begin retrieving results
	 *                                  used when data is too large to load in one call.
	 *                                  defaults to 1 if not specified
	 *             limit - long - (optional) maximum count of entries to return
	 *                                 defaults to 1000 if not specified
	 *             iteration - integer - (optional) count of how many recursive calls
	 *                                 defaults to 1 if not specified
	 * Output: none
	 *********************************************************************************************/
	this.loadLibraryFolders = function (jsonLib, cb, partialdata, start, limit, iteration) {
		start = (start ? start : 1);
		limit = (limit ? limit : 1000);
		iteration = (iteration ? iteration : 1);

		var jsonData;

		jQuery.ajax({
			type: "GET",
			url: jsonLib.data.NsfName + parentobject.LoadUrl + (_DOCOVAEdition == "SE" ? "LibraryId=" + jsonLib.data.DocKey : "") + "&start=" + start.toString() + "&count=" + limit.toString(),
			cache: false,
			dataType: "json",
			success: function (data) {
				var jsonData = data;
				var nodecount = 0;
				for (var key in jsonData) {
					if (jsonData.hasOwnProperty(key)) {
						if (!jsonData[key].id) {
							jsonData.splice(key, 1); //remove empty node
						} else {
							nodecount++;
							if (jsonData[key].parent == "") {
								jsonData[key].parent = jsonLib.id;
							}
							jsonData[key].data.NodeType = "folder";
						}
					}
				}
				jsonData = (partialdata ? partialdata.concat(jsonData) : jsonData);
				if (nodecount >= limit) {
					if (DEBUG && typeof console == "object") {
						console.log("loadLibraryFolders->getting more data")
					};
					//-- get more data
					parentobject.loadLibraryFolders(jsonLib, cb, jsonData, start + nodecount, limit, iteration + 1);
				} else {
					if (DEBUG && typeof console == "object") {
						console.log("loadLibraryFolders->returning jsonData")
					};
					cb.call(this, jsonData); //-- return what we have
				}
			}, //close success
			error: function () {
				if (DEBUG && typeof console == "object") {
					console.log("loadLibraryFolders->ajax error triggered")
				};
				cb.call(this, (partialdata) ? partialdata : []); //-- return what we have, probably invalid json returned
			}, //close error
			failure: function () {
				if (DEBUG && typeof console == "object") {
					console.log("loadLibraryFolders->returning partial data")
				};
				cb.call(this, (partialdata) ? partialdata : []);
			} //close failure
		}); //close $.ajax(
	} //-- end loadLibraryFolders

	/*********************************************************************************************
	 * method: loadChildFolders
	 * Description: retrieves a list of direct child folders for a specified folder
	 *                      recursive function
	 * Inputs: parentnode - object - library or folder node to load direct child folders for
	 *         cb - function - call back function that will process the folders
	 *             partialdata - json array - (optional) existing folders already retrieved by
	 *                                 parent function. not required on initial call to function
	 *             start - long - (optional) starting number in index to begin retrieving results
	 *                                  used when data is too large to load in one call.
	 *                                  defaults to 1 if not specified
	 *             limit - long - (optional) maximum count of entries to return
	 *                                 defaults to 1000 if not specified
	 *             iteration - integer - (optional) count of how many recursive calls
	 *                                 defaults to 1 if not specified
	 * Output: none
	 *********************************************************************************************/
	this.loadChildFolders = function (parentnode, cb, partialdata, start, limit, iteration) {
		start = (start ? start : 1);
		limit = (limit ? limit : 1000);
		iteration = (iteration ? iteration : 1);
		var jsonData;

		if (DEBUG && typeof console == "object") {
			console.log("loadChildFolders->parent node id=" + parentnode.id + " text=" + ((parentnode.text) ? parentnode.text : ""))
		};

		var keyval = "";
		if (parentnode.data.NodeType == "folder") {
			keyval = (_DOCOVAEdition == "SE" ? "RestrictToCategory=" : "") + parentnode.id;
			if (DEBUG && typeof console == "object") {
				console.log("loadChildFolders->keyval=" + keyval);
			};
		} else if (parentnode.data.NodeType == "library") {
			keyval = (_DOCOVAEdition == "SE" ? "RootLib=" + parentnode.id : "Root");
			if (DEBUG && typeof console == "object") {
				console.log("loadChildFolders->keyval=" + keyval);
			};
		} else {
			if (DEBUG && typeof console == "object") {
				console.log("loadChildFolders->parentnode.NodeType=" + parentnode.NodeType);
			};
			return false;
		}

		var librarynode = parentobject.getParentNodeByType(parentnode, "library", true);
		var folderurl = librarynode.data.NsfName;
		folderurl += parentobject.LoadUrlPartial + keyval;
		folderurl += "&start=" + start.toString();
		folderurl += "&count=" + limit.toString();

		jQuery.ajax({
			type: "GET",
			url: folderurl,
			cache: false,
			dataType: "json",
			success: function (data) {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildFolders->ajax returned data")
				};

				var jsonData = data;
				var nodecount = 0;
				for (var key in jsonData) {
					if (jsonData.hasOwnProperty(key)) {
						if (!jsonData[key].id) {
							jsonData.splice(key, 1); //remove empty node
						} else {
							nodecount++;
							if (jsonData[key].parent == "") {
								jsonData[key].parent = parentnode.id;
							}
							jsonData[key].data.NodeType = "folder";
							if (jsonData[key].data.FolderID.substring(0, 5) == "RCBIN") {
								jsonData[key].children = []; //-- recycle bin will never have child nodes
							} else {
								jsonData[key].children = true; //-- assume all other nodes may have sub nodes
							}
						}
					}
				}
				jsonData = (partialdata ? partialdata.concat(jsonData) : jsonData);
				if (nodecount >= limit) {
					if (DEBUG && typeof console == "object") {
						console.log("loadChildFolders->getting more data")
					};
					//-- get more data
					parentobject.loadChildFolders(parentnode, cb, jsonData, start + nodecount, limit, iteration + 1);
				} else {
					if (librarynode.data.LoadDocsAsFolders && parentnode.data.NodeType == "folder") {
						//-- get document data for folder
						parentobject.loadChildDocuments(parentnode, cb, jsonData);
					} else {
						if (DEBUG && typeof console == "object") {
							console.log("loadChildFolders->returning jsonData")
						};
						cb.call(this, jsonData); //-- return what we have
					}
				}
			}, //close success
			error: function () {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildFolders->ajax call returned error")
				};
				//-- following is a hack to resolve an issue in IE browser in which the leaf node display is not updating properly
				var domNode = parentobject.jstree.get_node(parentnode.id, true);
				domNode.addClass('jstree-leaf').removeClass('jstree-closed');
				//-- the above is a hack to resolve an issue in IE browser in which the leaf node display is not updating properly
				var jsonData = (partialdata) ? partialdata : [];
				if (librarynode.data.LoadDocsAsFolders && parentnode.data.NodeType == "folder") {
					//-- get document data for folder
					parentobject.loadChildDocuments(parentnode, cb, jsonData);
				} else {
					cb.call(this, jsonData); //-- return what we have, probably invalid json returned
				}
			}, //close error
			failure: function () {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildFolders->returning partial data")
				};

				var jsonData = (partialdata) ? partialdata : [];
				if (librarynode.data.LoadDocsAsFolders && parentnode.data.NodeType == "folder") {
					//-- get document data for folder
					parentobject.loadChildDocuments(parentnode, cb, jsonData);
				} else {
					cb.call(this, jsonData); //-- return what we have
				}
			} //close failure
		}); //close $.ajax(
	} //-- end loadChildFolders


	/*********************************************************************************************
	 * method: loadChildDocuments
	 * Description: retrieves a list of direct child documents for a specified folder
	 * Inputs: parentnode - object - folder node to load direct child documents for
	 *         cb - function - call back function that will process the documents
	 *             partialdata - json array - (optional) existing folders already retrieved by
	 *                                 parent function. not required on initial call to function
	 *             start - long - (optional) starting number in index to begin retrieving results
	 *                                  used when data is too large to load in one call.
	 *                                  defaults to 1 if not specified
	 *             limit - long - (optional) maximum count of entries to return
	 *                                 defaults to 1000 if not specified
	 *             iteration - integer - (optional) count of how many recursive calls
	 *                                 defaults to 1 if not specified
	 * Output: none
	 *********************************************************************************************/
	this.loadChildDocuments = function (parentnode, cb, partialdata, start, limit, iteration) {
		start = (start ? start : 1);
		limit = (limit ? limit : 1000);
		iteration = (iteration ? iteration : 1);
		var jsonData;

		if (DEBUG && typeof console == "object") {
			console.log("loadChildDocuments->parent node id=" + parentnode.id + " text=" + ((parentnode.text) ? parentnode.text : ""))
		};

		var keyval = "";
		if (parentnode.data.NodeType == "folder") {
			keyval = parentnode.id;
			if (DEBUG && typeof console == "object") {
				console.log("loadChildDocuments->keyval=" + keyval)
			};
		} else {
			if (DEBUG && typeof console == "object") {
				console.log("loadChildDocuments->parentnode.NodeType=" + parentnode.NodeType)
			};
			return false;
		}

		var librarynode = parentobject.getParentNodeByType(parentnode, "library", true);
		var docsurl = librarynode.data.NsfName + parentobject.LoadDocsUrl + keyval + "&start=" + start.toString() + "&count=" + limit.toString();

		jQuery.ajax({
			type: "GET",
			url: docsurl,
			cache: false,
			dataType: "json",
			success: function (data) {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildDocuments->ajax returned data")
				};

				var jsonData = data;
				var nodecount = 0;
				for (var key in jsonData) {
					if (jsonData.hasOwnProperty(key)) {
						if (!jsonData[key].id) {
							jsonData.splice(key, 1); //remove empty node
						} else {
							nodecount++;
							if (jsonData[key].parent == "") {
								jsonData[key].parent = parentnode.id;
							}
							jsonData[key].data.NodeType = "document";
							jsonData[key].children = []; //-- documents will never have child nodes
						}
					}
				}
				jsonData = (partialdata ? partialdata.concat(jsonData) : jsonData);
				if (nodecount >= limit) {
					if (DEBUG && typeof console == "object") {
						console.log("loadChildDocuments->getting more data")
					};
					//-- get more data
					parentobject.loadChildDocuments(parentnode, cb, jsonData, start + nodecount, limit, iteration + 1);
				} else {
					if (DEBUG && typeof console == "object") {
						console.log("loadChildDocuments->returning jsonData")
					};
					cb.call(this, jsonData); //-- return what we have
				}
			}, //close success
			error: function () {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildDocuments->ajax call returned error")
				};
				//-- following is a hack to resolve an issue in IE browser in which the leaf node display is not updating properly
				var domNode = parentobject.jstree.get_node(parentnode.id, true);
				domNode.addClass('jstree-leaf').removeClass('jstree-closed');
				//-- the above is a hack to resolve an issue in IE browser in which the leaf node display is not updating properly
				var jsonData = (partialdata) ? partialdata : [];
				cb.call(this, jsonData); //-- return what we have, probably invalid json returned
			}, //close error
			failure: function () {
				if (DEBUG && typeof console == "object") {
					console.log("loadChildDocuments->returning partial data")
				};

				var jsonData = (partialdata) ? partialdata : [];
				cb.call(this, jsonData); //-- return what we have
			} //close failure
		}); //close $.ajax(
	} //-- end loadChildDocuments


	/*********************************************************************************************
	 * method: RefreshAllLibraries
	 * Description: refreshes the folder control. Typically called after subscription
	 *********************************************************************************************/
	this.RefreshAllLibraries = function () {
		parentobject.jstree.refresh();
	} //--end RefreshAllLibraries

	/*********************************************************************************************
	 * method: LoadFavorites
	 * Description: initialize and load the favorites list
	 *********************************************************************************************/
	this.LoadFavorites = function (datasrcid, template, url) {
		var tablerowdataid = (template ? template : "otblFavorites");
		var datasourceid = (datasrcid ? datasrcid : "Favorites");
		var dataurl = (url ? url : DLITFolderView.FavoritesUrl);

		if (dataurl == ""){
			return (false);
		}

		parentobject.FavoritesData = new xmlDataIsland();
		parentobject.FavoritesData.setSrc(dataurl);
		parentobject.FavoritesData.id = datasourceid;
		parentobject.FavoritesData.setTemplateName(tablerowdataid);
		parentobject.FavoritesData.ondatasetcomplete = function () {
			jQuery("div.favorite-img").addClass(function (index) {
				return ("favorite-" + jQuery(this).text());
			}).text("").show();
		};
		parentobject.FavoritesData.process();

		//-- event handling for clicking within favorites screen
		jQuery("#" + tablerowdataid)
		.on("mouseover", "tr", function (event) {
			SetRowColor(this, true);
		})
		.on("mouseout", "tr", function (event) {
			SetRowColor(this, false);
		})
		.on("mousedown", "tr", function (event) {
			//-- left click button
			if (event.button == 0) {
				var rs = parentobject.FavoritesData.recordset;
				var row = this.rowIndex;
				OpenFavorite(rs, row);
			} //--end left click
			//-- right click button
			else if (event.button == 2) {
				var rs = parentobject.FavoritesData.recordset;
				var row = this.rowIndex;
				var menuitems = {};
				menuitems["deletefavorite"] = {
					"label": "Delete Favorite",
					"icon": "iconDeleteFolder.png",
					"action": function () {
						parentobject.DeleteFavorite(rs, row)
					},
					"separator_before": false,
					"separator_after": true,
					"shortcut": "",
					"shortcut_label": "",
					"_disabled": false
				};

				//				alert("x:" + jQuery(e.target).position().left + " y:" + jQuery(e.target).position().top);
				var jqTarget = jQuery(this);
				var xpos = jqTarget.offset().left;
				var ypos = jqTarget.offset().top + jqTarget.height() + 5;
				jQuery.vakata.context.show(event.target, {
					'x': xpos,
					'y': ypos
				}, menuitems);

				event.preventDefault();
				return false;
			} //--end right click
			return true;
		}); //-- end table mousdown trigger

		return (true);
	} //--end LoadFavorites

	/*********************************************************************************************
	 * method: ReloadFolderProperties
	 * Description: reloads folder properties
	 * Inputs: foldernodeorid (optional)- node object or string - folder node or id of folder node
	 *         libnodeorid (optional) - node object or string - library node or id of library node
	 *********************************************************************************************/
	this.ReloadFolderProperties = function (foldernodeorid, libnodeorid) {

		//-- locate folder node object
		var foldernode = ((foldernodeorid && foldernodeorid.id) ? foldernodeorid : (foldernodeorid ? parentobject.jstree.get_node(foldernodeorid) : (parentobject.IsFolderSelected ? parentobject.selectednode : null)));
		if (!foldernode) {
			if (DEBUG && typeof console == "object") {
				console.log("ReloadFolderProperties could not find the folder node")
			};
			return; //-- quit since folder cannot be located
		}

		//-- use library node object if passed otherwise look up library by id or use active library
		var libnode = (libnodeorid && libnodeorid.id) ? libnodeorid : ((libnodeorid) ? parentobject.jstree.get_node(libnodeorid) : parentobject.librarynode);
		if (!libnode) {
			if (DEBUG && typeof console == "object") {
				console.log("ReloadFolderProperties could not find the library node")
			};
			return; //-- quit since library cannot be located
		}

		var libPath = libnode.data.NsfName;
		//--------- get current folder properties -----------
		var folderpropUrl = libPath + parentobject.FolderPropertiesUrl + foldernode.data.Unid;
		jQuery.ajax({
			type: "GET",
			url: folderpropUrl,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				var xmlobj = jQuery(xml);
				var foldername = xmlobj.find("FolderName").text();
				var foldericon = xmlobj.find("IconNormal").text();
				var curicon = parentobject.jstree.get_icon(foldernode);
				if (curicon != foldericon && foldericon != '0') {
					if (_DOCOVAEdition == "SE") {
						foldericon = '/Symfony/web/bundles/docova/images/' + foldericon;
					}
					parentobject.jstree.set_icon(foldernode, foldericon);
				}
				if (foldernode.text != foldername) {
					parentobject.jstree.rename_node(foldernode, foldername);
				}
			}
		}); //-- end ajax
	} //-- end ReloadFolderProperties


	/*********************************************************************************************
	 * method: RefreshFolder
	 * Description: refreshes folder sub folders
	 * Inputs: foldernodeorid - node object or string - folder node or id of folder node
	 *********************************************************************************************/
	this.RefreshFolder = function (foldernodeorid) {

		//-- locate folder node object
		var foldernode = ((foldernodeorid && foldernodeorid.id) ? foldernodeorid : (foldernodeorid ? parentobject.jstree.get_node(foldernodeorid) : (parentobject.IsFolderSelected ? parentobject.selectednode : null)));
		if (!foldernode) {
			if (DEBUG && typeof console == "object") {
				console.log("RefreshFolder could not find the folder node")
			};
			return; //-- quit since folder cannot be located
		}

		if (foldernode.data && foldernode.data.NodeType === "folder") {
			var oktorefresh = false;
			if (!parentobject.LoadAllAtStartup) {
				oktorefresh = true;
			} else {
				var libnode = parentobject.getParentNodeByType(foldernode, "library", false);
				if (libnode && libnode.data && libnode.data.LoadDocsAsFolders) {
					oktorefresh = true;
				}
			}
			if (oktorefresh) {
				parentobject.jstree.load_node(foldernode);
			}
		}
	} //-- end RefreshFolder


	/*********************************************************************************************
	 * method: ExpandAll
	 * Description: expands all nodes beneath a given node
	 * Inputs: foldernodeorid - node object or string - node or id of folder to expand children
	 *                          if blank currently selected
	 *********************************************************************************************/
	this.ExpandAll = function (foldernodeorid) {
		var result = false;

		//-- locate folder node object
		var foldernode = ((foldernodeorid && foldernodeorid.id) ? foldernodeorid : (foldernodeorid ? parentobject.jstree.get_node(foldernodeorid) : (parentobject.IsFolderSelected ? parentobject.selectednode : null)));
		if (!foldernode) {
			if (DEBUG && typeof console == "object") {
				console.log("ExpandAll could not find the folder node")
			};
			return; //-- quit since folder cannot be located
		}

		if (parentobject.callCustomFunctions("onBeforeExpandAll")) {
			DLITFolderView.jstree.open_all(foldernode, -1);
			result = parentobject.callCustomFunctions("onAfterExpandAll");
		}
		return (result);
	} //-- end ExpandAll


	/*********************************************************************************************
	 * method: OpenFolder
	 * Description: selects a folder specified by a folder id
	 *********************************************************************************************/
	this.OpenFolder = function (folderid, douserevents, libid, cb) {
		if (douserevents == undefined) {
			var douserevents = false;
		}

		parentobject.loadFolderPath(libid, folderid, null, cb);
	} //-- end OpenFolder

	/*********************************************************************************************
	 * method: ExpandOnDrag
	 * Description: expands the tree when a file is dragged over the folder
	 *********************************************************************************************/
	this.ExpandOnDrag = function (obj) {
		var id = $(obj).attr("id");
		window.setTimeout(
			function () {
			var targetnode = parentobject.jstree.get_node(id);
			parentobject.jstree.open_node(targetnode);

		}, 500);

	} //-- end ExpandOnDrag

	/*********************************************************************************************
	 * method: getCurrentFolderPath
	 * Description: returns a string of the folderpath of the current folder
	 *********************************************************************************************/
	this.getCurrentFolderPath = function () {
		var node = parentobject.jstree.get_node(DLITFolderView.CurrentFolderID);
		if (!node){
			return "";
		}
		var fpath = parentobject.jstree.get_path(node, "\\");
		fpath = fpath.substring(fpath.indexOf("\\") + 1);
		return fpath;
	}

	/*********************************************************************************************
	 * method: getFolderPathByID
	 * Description: returns a string of the folderpath of the folder with the given id
	 *********************************************************************************************/
	this.getFolderPathByID = function (id) {
		var node = parentobject.jstree.get_node(id);
		if (!node){
			return "";
		}
		var fpath = parentobject.jstree.get_path(node, "\\");
		fpath = fpath.substring(fpath.indexOf("\\") + 1);
		return fpath;
	}

	/*********************************************************************************************
	 * method: doDrop
	 * Description: expands the tree when a file is dragged over the folder
	 *********************************************************************************************/
	this.doDrop = function (e, obj) {
		var id = $(obj).attr("id");
		var evt = e;
		var jqxhrarr = [];
		var targetnode = parentobject.jstree.get_node(id);
		var node = targetnode;
		parentobject.jstree.deselect_all(false);
		var nodetype = (node.data && node.data.NodeType && node.data.NodeType != "") ? node.data.NodeType : "folder";
		var librarynode = parentobject.getParentNodeByType(targetnode, "library", true);
		var loaddocsasfolders = (librarynode && librarynode.data && librarynode.data.LoadDocsAsFolders);
		var cancreatedocs = false;

		if (nodetype == "folder") {
			var security = null;
			security = DLITFolderView.getFolderAccessData(node);
			cancreatedocs = security.CanCreateDocuments;

		} else {
			alert("Invalid drop target.  Please drop the files onto a folder!")
			return;
		}

		if (!cancreatedocs) {
			alert("You don't have sufficient rights to create documents in this folder!");
			return;
		}

		id = ((targetnode.data.NodeType == "realm" || targetnode.data.NodeType == "community") ? "" : ((targetnode.data && targetnode.data.Unid) ? targetnode.data.Unid : targetnode.id.slice(2)));

		if (id == "") {
			alert("Not a drop target!")
			return;
		}

		var fpath = parentobject.jstree.get_path(node, "\\");
		fpath = fpath.substring(fpath.indexOf("\\") + 1);
		var files = evt.originalEvent.dataTransfer.files;
		var flArr = [];
		for (var x = 0; x < files.length; x++) {
			flArr.push(files[x].name);
		}
		if (!ValidatePathLength(docInfo.LimitPathLength, fpath, flArr)){
			return;
		}

		var defDocType = ""
			//get the default doc type of this folder

			var liburl = (librarynode && librarynode.data && librarynode.data.NsfName) ? librarynode.data.NsfName : "";
		var container = this;
		var nodoctypesfound = false;
		//--------- get doc types from folder properties -----------
		var folderpropUrl = liburl + parentobject.FolderPropertiesUrl + id;
		jQuery.ajax({
			type: "GET",
			url: folderpropUrl,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				var xmlobj = jQuery(xml);
				var doctypenode = xmlobj.find("DocumentType");
				if (doctypenode.children().length == 0) {
					nodoctypesfound = true;
					return
				};
				var DefaultDocType = xmlobj.find("DefaultDocumentType").text();

				if (DefaultDocType && DefaultDocType != "" && DefaultDocType != "None") {
					defDocType = DefaultDocType;
					return;
				}
				if (doctypenode.children().length == 1) {
					defDocType = $(doctypenode[0]).find("key").text();
					return;
				}

				var dlgUrl = librarynode.data.NsfName + "/" + "dlgSelectDocType?OpenForm&ParentUNID=" + id;
				var tmpDocova = (window.top.Docova ? window.top.Docova : Docova);
				var doctypedlg = tmpDocova.Utils.createDialog({
						id: "divDlgSelectDocType",
						url: dlgUrl,
						title: "New Document",
						height: 425,
						width: 400,
						useiframe: true,
						buttons: {
							"Continue": function () {
								var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
								if (result && result.DocumentType) {
									var defkey = result.DocumentType;
									var pfm = result.PromptForMetadata;

									doctypedlg.closeDialog();
									container.CreateDocumentsDragDrop(defkey, librarynode.data.NsfName, id, files, targetnode);
								}
							},
							"Cancel": function () {
								doctypedlg.closeDialog();
							}
						}
					});
			}

		}); //-- end ajax


		if (nodoctypesfound) {
			alert("No document type has been specified for this folder.  Unable to create documents.")
			return;
		}

		if (defDocType != "")
			this.CreateDocumentsDragDrop(defDocType, liburl, id, files, targetnode);

	} //-- end doDrop

	this.CreateDocumentsDragDrop = function (defDocType, liburl, id, files, targetnode) {
		if (defDocType == ""){
			return;
		}

		if (liburl == "") {
			alert("Error!  Could not find the library node for the selected folder.")
			return;
		}

		//get the file upload id
		var elementURL = liburl + "/Document?OpenForm&ParentUNID=" + id + "&Seq=1&typekey=" + defDocType
			var inputid = ""
			jQuery.ajax({
				url: elementURL,
				async: false,
				success: function (results) {
					//Get/Set formname
					var res = $(results).find('#fileAttach');
					inputid = res.attr("name");
				}
			});

		if (inputid == "") {
			alert("Could not find uploader on the document type " + defDocType + ".  Exiting")
			return;
		}

		if (files.length > 0) {
			var um = new uploadMultiple({

					"files": files,
					"inputid": inputid,
					"liburl": liburl,
					"defDocType": defDocType,
					"id": id,
					"onComplete": function () {
						parentobject.jstree.deselect_all(false);
						parentobject.jstree.select_node(targetnode);
						try {
							var o = window.parent.frames[id]
								var objview = o.contentWindow ? o.contentWindow.objView : o.objView;
							if (objview)
								objview.Refresh(true, false, true);

						} catch (e) {}

					}
				});
			um.start();
		}

	}

	/*********************************************************************************************
	 * method: ClickFolder
	 * Description: calls functions when a folder is clicked
	 *********************************************************************************************/
	this.ClickFolder = function () {
		var result = false;

		parentobject.storeSelectedNode();
		result = parentobject.callCustomFunctions("onClick"); //-- trigger click function to process folder opening

		return (result);
	} //-- end ClickFolder

	/*********************************************************************************************
	 * method: DoubleClickFolder
	 * Description: calls functions when a folder is double clicked
	 *********************************************************************************************/
	this.DoubleClickFolder = function () {
		var result = false;

		result = parentobject.callCustomFunctions("onDoubleClick"); //-- trigger click function to process double click action

		return (result);
	} //-- end DoubleClickFolder


	/*********************************************************************************************
	 * method: SyncFolderContent
	 * calls the SyncFolderContent function to update folder
	 *********************************************************************************************/
	this.SyncFolderContent = function () {
		SyncFolderContent();
	} //-- end SyncFolderContent


	/*********************************************************************************************
	 * method: CopyFolder
	 * Description: calls functions to copy a folder
	 * Inputs: libraryid - string - id of source library or blank to use currently selected lib
	 *             folderid - string - id of source folder or blank to use currently selected folder
	 *********************************************************************************************/
	this.CopyFolder = function (libraryid, folderid) {
		var result = false;

		parentobject.SourceLibraryID = (libraryid && libraryid != "") ? libraryid : parentobject.CurrentLibraryID;
		var nodeid = (folderid && folderid != "") ? folderid : parentobject.CurrentFolderID;
		var foldernode = parentobject.jstree.get_node(nodeid);
		parentobject.SourceFolderUnid = (foldernode && foldernode.data && foldernode.data.Unid) ? foldernode.data.Unid : "";
		parentobject.SourceFolderKey = (foldernode) ? foldernode.id : "";

		parentobject.WasFolderCut = false;

		//-- call custom functions to perform checks and perform action
		if (parentobject.callCustomFunctions("onBeforeFolderCopy")) {
			if (parentobject.callCustomFunctions("onFolderCopy")) {
				result = parentobject.callCustomFunctions("onAfterFolderCopy");
			}
		}

		return (result);
	} //-- end CopyFolder


	/*********************************************************************************************
	 * method: CutFolder
	 * Description: calls functions to cut a folder
	 * Inputs: libraryid - string - id of source library or blank to use currently selected lib
	 *             folderid - string - id of source folder or blank to use currently selected folder
	 *********************************************************************************************/
	this.CutFolder = function (libraryid, folderid) {
		var result = false;

		parentobject.SourceLibraryID = (libraryid && libraryid != "") ? libraryid : parentobject.CurrentLibraryID;
		var nodeid = (folderid && folderid != "") ? folderid : parentobject.CurrentFolderID;
		var foldernode = parentobject.jstree.get_node(nodeid);
		parentobject.SourceFolderUnid = (foldernode && foldernode.data && foldernode.data.Unid) ? foldernode.data.Unid : "";
		parentobject.SourceFolderKey = (foldernode) ? foldernode.id : "";

		parentobject.WasFolderCut = true;

		//-- call custom functions to perform checks and perform action
		if (parentobject.callCustomFunctions("onBeforeFolderCut")) {
			if (parentobject.callCustomFunctions("onFolderCut")) {
				result = parentobject.callCustomFunctions("onAfterFolderCut");
			}
		}

		return (result);
	} //-- end CutFolder


	/*********************************************************************************************
	 * method: PasteFolder
	 * Description: calls functions to paste a copied or cut folder
	 * Inputs: libraryid - string - id of target library or blank to use currently selected lib
	 *             folderid - string - id of target folder or blank to use currently selected folder
	 *********************************************************************************************/
	this.PasteFolder = function (libraryid, folderid) {
		var result = false;

		parentobject.TargetLibraryID = (libraryid && libraryid != "") ? libraryid : parentobject.CurrentLibraryID;
		var targetid = (folderid && folderid != "") ? folderid : parentobject.CurrentFolderID;
		var foldernode = parentobject.jstree.get_node(targetid);
		parentobject.TargetFolderUnid = (foldernode && foldernode.data && foldernode.data.Unid) ? foldernode.data.Unid : "";
		parentobject.TargetFolderKey = (foldernode) ? foldernode.id : (parentobject.IsLibrarySelected ? parentobject.CurrentLibraryID : "");

		if (parentobject.WasFolderCut && (parentobject.SourceFolderUnid == parentobject.TargetFolderUnid)) {
			alert("A folder cannot be moved onto itself.  Please choose a different destination.");
			return (false);
		}

		//-- call custom functions to perform checks and
		if (parentobject.callCustomFunctions("onBeforeFolderPaste")) {
			result = parentobject.callCustomFunctions("onFolderPaste", function () {
					parentobject.callCustomFunctions("onAfterFolderPaste");
				});
		}

		return (result);
	} //-- end PasteFolder


	/*********************************************************************************************
	 * method: DeleteFolder
	 * Description: calls functions to delete a folder
	 * Inputs: folderid - string - id of folder to delete, if blank uses currently selected folder
	 *********************************************************************************************/
	this.DeleteFolder = function (folderid) {
		var result = false;

		var node = (folderid && folderid != "") ? parentobject.jstree.get_node(folderid) : parentobject.CurrentFolderID;

		//-- call custom functions to perform checks and perform action
		if (parentobject.callCustomFunctions("onBeforeFolderDelete")) {
			if (parentobject.callCustomFunctions("onFolderDelete")) {
				result = parentobject.jstree.delete_node(node); //--remove the node from the tree
				parentobject.callCustomFunctions("onAfterFolderDelete");
			}
		}

		return (result);
	} //-- end DeleteFolder


	/*********************************************************************************************
	 * method: DeleteDocument
	 * Description: calls functions to delete a document
	 * Inputs: docnode - node object or string - node object or id of document node to delete
	 *********************************************************************************************/
	this.DeleteDocument = function (docnodeorid) {
		var result = false;

		if (docnodeorid) {
			var node = (docnodeorid && docnodeorid.id) ? docnodeorid : parentobject.jstree.get_node(docnodeorid);
			if (node) {
				//-- call custom functions to perform checks and perform action
				if (parentobject.callCustomFunctions("onBeforeDocumentDelete", null, node)) {
					if (parentobject.callCustomFunctions("onDocumentDelete", null, node)) {
						parentobject.callCustomFunctions("onAfterDocumentDelete", null, node);
					}
				}
			}
		}

		return (result);
	} //-- end DeleteDocument


	/*********************************************************************************************
	 * method: RenameFolder
	 * Description: calls functions to trigger renaming of a folder
	 * Inputs: folderid - string - id of folder to delete, if blank uses currently selected folder
	 *********************************************************************************************/
	this.RenameFolder = function (folderid, newname, douserevents) {
		var result = false;

		var node = (folderid && folderid != "") ? parentobject.jstree.get_node(folderid) : parentobject.CurrentFolderID;
		newname = (newname && newname != "") ? newname : node.text;

		//-- call custom functions to perform checks and perform action
		if (douserevents) {
			var res = parentobject.callCustomFunctions("onBeforeFolderRename");
			if (res) {
				parentobject.jstree.edit(node, newname); //--back end rename will occur on the completion of rename event
			}
		} else {
			parentobject.jstree.rename_node(node, newname);
		}

		return (result);
	} //-- end RenameFolder

	/*********************************************************************************************
	 * method: FinishRenameFolder
	 * Description: calls functions to rename a folder
	 * Inputs: dataobj - object - jstree data object containing info on renamed node
	 *********************************************************************************************/
	this.FinishRenameFolder = function (dataobj) {
		var result = false;

		if (dataobj.old != dataobj.text) { //-- folder name was changed
			if (parentobject.callCustomFunctions("onFolderRename")) {
				result = parentobject.callCustomFunctions("onAfterFolderRename");
			} else {
				//-- undo UI update if an error occurred in the rename process
				parentobject.jstree.set_text(dataobj.node, dataobj.old)
			}
		}

		return (result);
	} //-- end FinishRenameFolder

	/*********************************************************************************************
	 * method: CreateFolder
	 * Description: calls functions to trigger creation of a folder
	 * Inputs: foldername - string - name of new folder
	 *             parentfolderid - string - id of parent folder, if blank uses currently selected
	 *********************************************************************************************/
	this.CreateFolder = function (foldername, parentfolderid) {
		if (DEBUG && typeof console == "object") {
			console.log("CreateFolder->foldername=" + foldername + " and parentfolderid=" + parentfolderid)
		};

		var result = false;

		var parentnode = (parentfolderid && parentfolderid != "") ? parentobject.jstree.get_node(parentfolderid) : (parentobject.IsFolderSelected ? parentobject.selectednode : (parentobject.IsLibrarySelected ? parentobject.librarynode : null));
		foldername = (foldername && foldername != "") ? foldername : "";

		if (parentnode && parentobject.callCustomFunctions("onBeforeFolderCreate")) {
			if (DEBUG && typeof console == "object") {
				console.log("CreateFolder->about to call create_node");
			};

			if (!parentobject.jstree.is_open(parentnode)) {
				//--expand the node so that our new entry will be visible
				parentobject.jstree.open_node(parentnode, function () {
					var newfolder = DLITFolderView.jstree.create_node(parentnode, " ");
					if (newfolder) {
						DLITFolderView.jstree.edit(newfolder, foldername); //--back end save will occur on the completion of rename event
						result = true;
					}
				});
			} else {
				var newfolder = DLITFolderView.jstree.create_node(parentnode, " ");
				if (newfolder) {
					DLITFolderView.jstree.edit(newfolder, foldername); //--back end save will occur on the completion of rename event
					result = true;
				}
			}
		}

		return (result);
	} //-- end CreateFolder


	/*********************************************************************************************
	 * method: FinishCreateFolder
	 * Description: calls functions to create a folder
	 * Inputs: dataobj - object - jstree data object containing info on new node
	 *********************************************************************************************/
	this.FinishCreateFolder = function (dataobj) {
		var result = false;
		if (DEBUG && typeof console == "object") {
			console.log("FinishCreateFolder->start");
		};

		if (parentobject.callCustomFunctions("onFolderCreate")) {
			var unid = parentobject.NewNodeID;
			parentobject.jstree.set_id(dataobj.node, (_DOCOVAEdition == "SE" ? "" : "DK") + unid); //-- reset the id on the node with the one returned from back end
			dataobj.node.data = {
				"FolderID": (_DOCOVAEdition == "SE" ? "" : "DK") + unid,
				"Unid": unid,
				"FolderOpenUrl": "",
				"NodeType": ""
			};
			result = parentobject.callCustomFunctions("onAfterFolderCreate");
		} else {
			parentobject.jstree.delete_node(dataobj.node); //error occurred saving the new folder so remove the node
			alert("Error: An error occurred while creating the new folder");
		}

		return (result);
	} //-- end FinishCreateFolder


	/*********************************************************************************************
	 * method: CreateDocument
	 * Description: calls functions to create a new document
	 * Inputs: foldernodeorid - node object or string - parent folder object, or id of parent folder
	 *                          if blank uses currently selected folder
	 *********************************************************************************************/
	this.CreateDocument = function (parentfolderid) {
		if (DEBUG && typeof console == "object") {
			console.log("CreateDocument->start");
		};

		var result = false;

		if (parentobject.callCustomFunctions("onBeforeDocumentCreate", null, parentfolderid)) {
			result = parentobject.callCustomFunctions("onDocumentCreate", null, parentfolderid);
		}

		return (result);
	} //-- end CreateDocument

	/*********************************************************************************************
	 * method: ImportFiles
	 * Description: calls functions to import files to a folder
	 *********************************************************************************************/
	this.ImportFiles = function () {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeAddFiles")) {
			if (parentobject.callCustomFunctions("onAddFiles")) {
				result = parentobject.callCustomFunctions("onAfterAddFiles");
			}
		}

		return (result);
	} //-- end ImportFiles

	/*********************************************************************************************
	 * method: ImportFolder
	 * Description: calls functions to import folder and subfolders to a folder
	 *********************************************************************************************/
	this.ImportFolder = function () {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeImportFolder")) {
			if (parentobject.callCustomFunctions("onImportFolder")) {
				result = parentobject.callCustomFunctions("onAfterImportFolder");
			}
		}

		return (result);
	} //-- end ImportFiles


	/*********************************************************************************************
	 * method: ExportFiles
	 * Description: calls functions to export files from a folder
	 *********************************************************************************************/
	this.ExportFiles = function () {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeFileExport")) {
			if (parentobject.callCustomFunctions("onFileExport")) {
				result = parentobject.callCustomFunctions("onAfterFileExport");
			}
		}
		return (result);
	} //-- end ExportFiles


	/*********************************************************************************************
	 * method: OpenInNewTab
	 * Description: calls functions to open a folder in a new tab pane
	 *********************************************************************************************/
	this.OpenInNewTab = function () {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeOpenInNewTab")) {
			if (parentobject.callCustomFunctions("onOpenInNewTab")) {
				result = parentobject.callCustomFunctions("onAfterOpenInNewTab");
			}
		}

		return (result);
	} //--end OpenInNewTab


	/*********************************************************************************************
	 * method: CopyFolderLink
	 * Description: calls functions to copy a folder url link to clipboard
	 *********************************************************************************************/
	this.CopyFolderLink = function () {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeCopyFolderLink")) {
			if (parentobject.callCustomFunctions("onCopyFolderLink")) {
				result = parentobject.callCustomFunctions("onAfterCopyFolderLink");
			}
		}

		return (result);
	} //-- end CopyFolderLink


	/*********************************************************************************************
	 * method: AddFavorite
	 * Description: calls functions to add a folder to favorites
	 *********************************************************************************************/
	this.AddFavorite = function () {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeAddFavorite")) {
			if (parentobject.callCustomFunctions("onAddFavorite")) {
				result = parentobject.callCustomFunctions("onAfterAddFavorite");
			}
		}

		return (result);
	} //-- end AddFavorite

	/*********************************************************************************************
	 * method: DeleteFavorite
	 * Description: calls functions to delete a favorite
	 *********************************************************************************************/
	this.DeleteFavorite = function (rs, row) {
		var result = false;

		if (parentobject.callCustomFunctions("onBeforeDeleteFavorite", null, {
				"recordset": rs,
				"row": row
			})) {
			if (parentobject.callCustomFunctions("onDeleteFavorite", null, {
					"recordset": rs,
					"row": row
				})) {
				result = parentobject.callCustomFunctions("onAfterDeleteFavorite", null, {
						"recordset": rs,
						"row": row
					});
			}
		}

		return (result);
	} //-- end DeleteFavorite

	/*********************************************************************************************
	 * method: ShowProperties
	 * Description: calls functions to show library/folder properties
	 * Inputs: options - (optional) - data value pair array of optional parameters to pass to
	 *                                custom function
	 *********************************************************************************************/
	this.ShowProperties = function (options) {
		var result = false;
		result = parentobject.callCustomFunctions("onPropertiesMenu", null, options);

		return (result);
	} //-- end ShowProperties

	/*********************************************************************************************
	 * method: ShowSubscriptions
	 * Description: calls functions to show library subscription dialog
	 *********************************************************************************************/
	this.ShowSubscriptions = function () {
		var result = false;

		result = parentobject.callCustomFunctions("onSubscriptionClick");
		return (result);
	} //-- end ShowSubscriptions

	/*********************************************************************************************
	 * method: BuildContextMenu
	 * Description: calls functions to generate contents of right click context menu
	 *********************************************************************************************/
	this.BuildContextMenu = function (nodeobj) {
		var menu = parentobject.callCustomFunctions("onContextMenu", nodeobj);

		return (menu);
	} //-- end BuildContextMenu


	/*********************************************************************************************
	 * method: InitializeFolderControl
	 * Description: Instantiates the folder control and triggers the initial load of data
	 * Inputs: targetid - string - id of target div element where folder control will be inserted
	 * Output: object - folder control object, or false if control is not instantiated
	 *********************************************************************************************/
	this.InitializeFolderControl = function (targetid) {
		if (!targetid) {
			return false;
		}

		targetid = (targetid.charAt(0) == "#") ? targetid : "#" + targetid;

		jQuery(targetid).jstree({
			"core": {
				"animation": false,
				"check_callback": function (operation, node, node_parent, node_position) {
					return true;
				},
				"multiple": false,
				"themes": {
					"dots": true,
					"responsive": false
				},
				"data": function (obj, cb) { //-- performs the retrieval of tree data
					if (typeof parentobject.FolderDataOverride == "function") {
						//-- special case folder data load
						if (obj.id == "#") {
							parentobject.IsLoading = true;
							cb.call(this, parentobject.FolderDataOverride());
						}
					} else { //-- normal library data load
						if (obj.id == "#") {
							if (DEBUG && typeof console == "object") {
								console.log("jstree.core.data->loading libraries for node.id=#")
							};
							parentobject.IsLoading = true;
							//-- initially loads the libraries for the tree
							parentobject.LoadLibraries(cb);
						} else if (obj.data && obj.data.NodeType && obj.data.NodeType == "library") {
							if (DEBUG && typeof console == "object") {
								console.log("jstree.core.data->loading library folders for node.id=" + obj.id + " node.name=" + obj.text)
							};
							if (parentobject.LoadAllAtStartup == true && obj.data.LoadDocsAsFolders !== true) {
								//-- load the folders for the current library
								parentobject.loadLibraryFolders(obj, cb, null, 1, parentobject.ReadLimit); //-- loads all library folders in one pass
							} else {
								parentobject.loadChildFolders(obj, cb, null, 1, parentobject.ReadLimit); //-- loads just the library root folders
							}
						} else if (obj.data && obj.data.NodeType && obj.data.NodeType == "folder") {
							if (DEBUG && typeof console == "object") {
								console.log("jstree.core.data->loading sub folders for node.id=" + obj.id + " node.name=" + obj.text)
							};
							var getchildren = false;
							if (parentobject.LoadAllAtStartup != true) {
								getchildren = true;
							} else {
								var libnode = parentobject.getParentNodeByType(obj, "library", false);
								if (libnode && libnode.data && libnode.data.LoadDocsAsFolders === true) {
									getchildren = true;
								}
							}
							if (getchildren) {
								parentobject.loadChildFolders(obj, cb, null, 1, parentobject.ReadLimit); //-- loads just the selected node sub folders
							}
						} else {
							if (DEBUG && typeof console == "object") {
								console.log("jstree.core.data->called for node.id=" + obj.id + " node.name=" + obj.text)
							};
						}
					}
				}
			},
			"sort": function (a, b) { //-- sort nodes alphabetically or by custom sort except for recycle bin which goes to the end
				if (DEBUG && typeof console == "object") {
					console.log("jstree.sort->triggered");
				};
				var sortorder = 0;
				var anode = this.get_node(a);
				var bnode = this.get_node(b);
				var aisrc = (anode.data && anode.data.FolderID && anode.data.FolderID.indexOf("RCBIN") == 0);
				var bisrc = (bnode.data && bnode.data.FolderID && bnode.data.FolderID.indexOf("RCBIN") == 0);
				var atypesort = 0;
				if (anode.data && anode.data.NodeType) {
					if (anode.data.NodeType == "folder") {
						atypesort = 2;
					} else if (anode.data.NodeType == "document") {
						atypesort = 1
					}
				}
				var btypesort = 0;
				if (bnode.data && bnode.data.NodeType) {
					if (bnode.data.NodeType == "folder") {
						btypesort = 2;
					} else if (bnode.data.NodeType == "document") {
						btypesort = 1
					}
				}
				var acust = (anode.data && anode.data.sortorder && anode.data.sortorder != "") ? anode.data.sortorder : false;
				var bcust = (bnode.data && bnode.data.sortorder && bnode.data.sortorder != "") ? bnode.data.sortorder : false;
				var valA = "";
				var valB = "";
				if (aisrc) {
					sortorder = 1; //-- move a to end since it is recycle bin
				} else if (bisrc) {
					sortorder = -1; //-- move b to end since it is recycle bin
				} else if (atypesort > btypesort) {
					sortorder = 1; //-- move a to end since it is of a node type that should come after b
				} else if (atypesort < btypesort) {
					sortorder = -1; //-- move b to end since it is of a node type that should come after a
				} else if (acust && !bcust) {
					sortorder = -1; //-- move b to end since a has custom sort and b doesnt
				} else if (bcust && !acust) {
					sortorder = 1; //-- move a to end since b has custom sort and a doesnt
				} else {
					valA = anode.text.toLowerCase();
					valB = bnode.text.toLowerCase();
					if (acust) {
						var tempval = parseFloat(acust);
						if (!isNaN(tempval)) {
							valA = tempval;
						}
					}
					if (bcust) {
						var tempval = parseFloat(bcust);
						if (!isNaN(tempval)) {
							valB = tempval;
						}
					}
					//-- if both values have custom sorts and they are equal use the original label as a tie breaker
					if (acust && bcust && valA === valB) {
						sortorder = (anode.text.toLowerCase() > bnode.text.toLowerCase() ? 1 : -1);
					} else {
						sortorder = (valA > valB) ? 1 : -1;
					}
				}

				return sortorder;
			},
			"conditionalselect": function (node) { //-- to stop selection of a node put code here
				var selectable = true;
				if (node && node.data && node.data.noselect && node.data.noselect == true) {
					selectable = false;
					//-- need to reset the highlight as the previously selected node is now no longer highlighted
					if (parentobject.selectednode) {
						parentobject.jstree.select_node(parentobject.selectednode);
					}
				}
				return selectable;
			},
			"contextmenu": {
				"select_node": false,
				"items": function (node) {
					return parentobject.BuildContextMenu(node);
				}
			},
			"plugins": ["contextmenu", "sort", "conditionalselect", (parentobject.ShowCheckBoxes) ? "checkbox" : ""]
		})
		.on('ready.jstree', function (e, data) { //-- respond to tree completed loading
			if (DEBUG && typeof console == "object") {
				console.log("jstree -> event -> ready");
			};
			parentobject.IsLoading = false;
			if (parentobject.LibraryCount == 0) {
				parentobject.ShowSubscriptions();
			} //--display the subscription dialog
			parentobject.callCustomFunctions("onFoldersReady");
			Docova.events.triggerHandler('FolderControlLoaded', parentobject, true);
		})
		.on('load_node.jstree', function (e, data) { //-- respond to node load data event
			if (data.status) {
				if (DEBUG && typeof console == "object") {
					console.log("jstree -> event -> load_node-> for node.id=" + data.node.id + " node.text=" + (data.node.text ? data.node.text : ""))
				};
				if (data.node.id == "#") {
					//					parentobject.IsLoading = true;
					if (parentobject.LoadAllAtStartup == true) {
						parentobject.loadChildNodes(data.node, "library"); //-- triggers the loading of the library folders
					}
				}
				if (DEBUG && typeof console == "object") {
					console.log("jstree -> event -> load_node-> for node.id=" + data.node.id + " is_leaf=" + parentobject.jstree.is_leaf(data.node))
				};
			}
		})
		//		.on('activate_node.jstree', function (e, data) {
		//		})
		.on('select_node.jstree', function (e, data) {
			if (DEBUG && typeof console == "object") {
				console.log("select_node.jstree event triggered")
			};
			if (parentobject.ignoreevent == true) {
				if (DEBUG && typeof console == "object") {
					console.log("select_node.jstree event overridden/ignored")
				};
				parentobjectignoreevent = false;
			} else {
				var processclick = true;
				//-- optional logic to stop folder from being clicked
				//if(data.node && data.node && data.node.data && data.node.data.NodeType && data.node.data.NodeType == "folder"){
				//	var libnode = parentobject.getParentNodeByType(data.node, "library", false);
				//	if(libnode && libnode.data && libnode.data.LoadDocsAsFolders){
				//		if(data.node.data.FolderID && data.node.data.FolderID.substring(0,5) != "RCBIN"){
				//			processclick = false;
				//		}
				//	}
				//}
				if (processclick) {
					parentobject.ClickFolder(); //-- respond to select node event
				}
			}
		})
		.on('rename_node.jstree', function (e, data) {
			parentobject.NewFolderName = data.text;
			if (!data.node.data) { //-- newly created node, need to post to server
				if (data.old != data.text) { //-- newly created folder name was changed
					parentobject.FinishCreateFolder(data);
				} else { //-- newly created folder was not renamed so remove the node
					parentobject.jstree.delete_node(data.node);
				}
			} else { //-- a rename of an existing folder
				if (data.old != data.text) { //-- folder name was changed
					parentobject.FinishRenameFolder(data);
				}
			};
			parentobject.NewFolderName = "";
			parentobject.NewNodeID = "";
			parentobject.storeSelectedNode();
		});
		//		.on('changed.jstree', function (e, data){
		//		});

		parentobject.IsInitialized = true;
		return jQuery(targetid).jstree(true);
	} //--end InitializeFolderControl


	this.jstree = this.InitializeFolderControl(targetid);
	jQuery(targetid).on('dblclick', '.jstree-anchor', function () {
		parentobject.DoubleClickFolder();
	});
	jQuery.vakata.context.settings.hide_onmouseleave = 1; //-- configure context menu to close on mouse leave
	jQuery(targetid).on('dragover', '.jstree-anchor', function (e) {
		//call the hover node method in jstree
		$(targetid).jstree("hover_node", e.currentTarget);

		e.preventDefault();
		parentobject.ExpandOnDrag(this)
	});
	jQuery(targetid).on('dragleave', '.jstree-anchor', function (e) {
		$(targetid).jstree("dehover_node", e.currentTarget);

	});
	jQuery(targetid).on('drop', '.jstree-anchor', function (e) {
		e.preventDefault();
		parentobject.doDrop(e, this);
	});
	window.addEventListener("dragover", function (e) {
		e = e || event;
		e.preventDefault();
	}, false);
	window.addEventListener("drop", function (e) {
		e = e || event;
		e.preventDefault();
	}, false);

} //--end FolderControl
/*******************************************************************************
 *******************************************************************************/

/*******************************************************************************
 * plugin: conditionalselect
 * Description: jstree plugin to allow conditional selection of a node
 *******************************************************************************/
(function ($, notused) {
	"use strict";
	$.jstree.defaults.conditionalselect = function () {
		return true;
	};

	$.jstree.plugins.conditionalselect = function (options, parent) {
		// own function
		this.select_node = function (obj, supress_event, prevent_open) {
			if (this.settings.conditionalselect.call(this, this.get_node(obj))) {
				parent.select_node.call(this, obj, supress_event, prevent_open);
			}
		};
	};
})(jQuery);

/*********************************************************************************************
 * External Folder Functions
 * Description: The following functions are called by the internal folder methods
 * These functions can be overridden and replaced by custom functions by modifying the
 * the configuration parameters used to initalize the folder control
 *********************************************************************************************/

/*********************************************************************************************
 * function: FolderClicked
 * Description: Called when user clicks on a library or folder node
 * Inputs:
 * Output: boolean - true or false
 *********************************************************************************************/
function FolderClicked() {

	//check if document is open and is being edited
	var contentBottom = window.parent.fraContentBottom;
	var curContentUrl = contentBottom.location.href;
	if (curContentUrl && curContentUrl != 'about:blank' && curContentUrl.indexOf("/BlankContent?") == -1) {
		try {
			if (!contentBottom.allowClose && contentBottom.docInfo.isDocBeingEdited) {
				var ans = contentBottom.SaveBeforeClosing(true);
				if (ans == 6) {
					contentBottom.HandleSaveClick();
					return false;
				} else if (ans != 7) {
					return false;
				}
				contentBottom.allowClose = true;
				if (contentBottom.docInfo.isLocked) {
					contentBottom.Unlock();
				}
			}
		} catch (e) {}
	}

	//-------- open current folder ---------------
	return (LoadCurrentFolder());
} //-- end FolderClicked


/*********************************************************************************************
 * function: FolderDblClicked
 * Description: Called when user double clicks on a library or folder node
 * Inputs:
 * Output: boolean - true or false
 *********************************************************************************************/
function FolderDblClicked() {
	return false;
} //-- end FolderDblClicked


/*********************************************************************************************
 * function: LoadCurrentFolder
 * Description: Opens the currently selected folder
 * Inputs:
 * Output: boolean - true or false
 *********************************************************************************************/
function LoadCurrentFolder() {

	if (DLITFolderView.NodeType == "community" || DLITFolderView.NodeType == "realm") {
		return false;
	}

	//-------- get redirect url if specified for the folder ------------
	var redirectURL = DLITFolderView.FolderOpenUrl;
	if (redirectURL == undefined) {
		redirectURL = "";
	}

	//-------- redirect if specified ---------------
	if (redirectURL != "") {
		redirectURL = redirectURL.replace(new RegExp("\{\{folderid\}\}", "gi"), DLITFolderView.CurrentUNID);
		redirectURL = redirectURL.replace(new RegExp("\{\{folderkey\}\}", "gi"), DLITFolderView.CurrentFolderID);
		redirectURL = redirectURL.replace(new RegExp("\{\{foldername\}\}", "gi"), DLITFolderView.CurrentFolderName);
		redirectURL = redirectURL.replace(new RegExp("\{\{libraryid\}\}", "gi"), DLITFolderView.CurrentLibraryID);
		redirectURL = redirectURL.replace(new RegExp("\{\{libraryurl\}\}", "gi"), DLITFolderView.CurrentLibraryUrl);

		LoadFrame(redirectURL, true);
		return false;
	}

	//--------get library url ----------------
	var LibraryUrl = DLITFolderView.CurrentLibraryUrl;
	if (LibraryUrl == "") {
		try {
			LoadFrame(docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "BlankFolderSelected?OpenPage");
		} catch (e) {}
		return (false);
	}

	//--------get url to load-----------------
	var FolderUrl = LibraryUrl;
	if (DLITFolderView.NodeType == "library") {
		if (_DOCOVAEdition == "SE") {
			FolderUrl += "/wLibraryWelcome?lib=" + DLITFolderView.CurrentLibraryID;
		} else {
			FolderUrl += "/wLibraryWelcome?ReadForm";
		}
	} else if (DLITFolderView.NodeType == 'document') {
		var FolderUnid = DLITFolderView.getParentNodeByType(DLITFolderView.jstree.get_node(DLITFolderView.jstree.get_selected(false)[0]), "folder", false);
		var docKey = DLITFolderView.CurrentUNID;
		if (FolderUnid == "" || docKey == '') {
			return (false);
		}
		FolderUrl += ("/ReadDocument/" + docKey + "?OpenDocument&ParentUNID=" + FolderUnid);
	} else {
		//--------get folder id-------------------
		var FolderUnid = DLITFolderView.CurrentUNID;
		if (FolderUnid == "") {
			try {
				LoadFrame(docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "BlankFolderSelected?OpenPage");
			} catch (e) {}
			return (false);
		}
		FolderUrl += ("/luAllByDocKey/" + FolderUnid + "?OpenDocument");
		if (FolderUnid.substring(0, 5) == "RCBIN") {
			FolderUrl += '&lib=' + DLITFolderView.CurrentLibraryID;
		}
	}
	try {
		LoadFrame(FolderUrl);
	} catch (e) {}

	//------ change navigation tab -----------
	try {
		parent.frames['fraToolbar'].btnClick(parent.frames['fraToolbar'].document.all.btnContent);
	} catch (e) {}

	return (true);
} //-- end LoadCurrentFolder


/*********************************************************************************************
 * function: LoadFrame
 * Description: loads content in the right-hand content frame - used by other frames
 * Inputs: url - string - url path to content to be loaded into frame
 *             openFromFolder - boolean - true if document is opened from active folder
 *             foldername - string - name of folder document is being opened from
 *                                               ignored if openFromFolder is true
 * Output:
 *********************************************************************************************/
function LoadFrame(url, openFromFolder, foldername) {
	var contentBottom = window.parent.fraContentBottom;
	var curContentUrl = contentBottom.location.href;
	if (curContentUrl && curContentUrl != 'about:blank' && curContentUrl.indexOf("/BlankContent?") == -1) {
		//		try{
		//check if the document being edited can be closed. if not, terminate
		if (!contentBottom.allowClose && contentBottom.docInfo.isDocBeingEdited) {
			var ans = contentBottom.SaveBeforeClosing(true);
			if (ans == 6) {
				contentBottom.HandleSaveClick();
				return false;
			} else if (ans != 7) {
				return false;
			}
			contentBottom.allowClose = true;
		}
		//		}
		//		catch(e) {}
		var contentUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "BlankContent?OpenPage";
		contentBottom.location.href = contentUrl;
	}

	//------------- tabbed interface ----------------------------------
	//	try {
	if (DLITFolderView.CurrentUNID == "" && url.indexOf("BlankFolderSelected") != -1) {
		window.parent.fraTabbedTable.objTabBar.NoFrameOpen(url);
	} else if (DLITFolderView.CurrentUNID == "" && !openFromFolder) {
		var tabtitle = "Document";
		var icontype = "D";
		if (foldername && foldername != "") {
			tabtitle = foldername;
			icontype = "F";
		}
		var docid = getUnidFromUrl(url);
		window.parent.fraTabbedTable.objTabBar.CreateTab(tabtitle, docid, icontype, url);
	} else {
		var docid = getUnidFromUrl(url);
		currentUnid = DLITFolderView.CurrentUNID;
		var icontype = "F";
		var name = DLITFolderView.CurrentFolderName;
		var path = "";
		try {
			path = DLITFolderView.CurrentFolderPath;
		} catch (er) {
			alert(er)
		}
		if (path == "" || path == "undefined" || typeof path == "undefined"){
			path = name;
		}else{
			path = name + "~!~" + path;
		}

		if (openFromFolder && !docid == "") {
			window.parent.fraTabbedTable.objTabBar.CreateTab(path, docid, icontype, url);
		} else {
			window.parent.fraTabbedTable.objTabBar.CreateTab(path, currentUnid, icontype, url)
		}
	}
	//	}
	//	catch(err) {
	//		alert(err)
	//	}
} //--end LoadFrame


/*********************************************************************************************
 * function: getUnidFromUrl
 * Description: given a url extracts the document unique id from it
 * Inputs: url - string - url to extract document unid from
 * Output: string - unid of document
 *********************************************************************************************/
function getUnidFromUrl(url) {
	if (_DOCOVAEdition == "SE") {
		var pos1 = url.toLowerCase().indexOf(".php/Docova/luallbydockey/");
		if (pos1 > -1) {
			pos1 = pos1 + 26;
		}
	} else {
		var pos1 = url.toLowerCase().indexOf(".nsf/0/");
		if (pos1 == -1) {
			pos1 = url.toLowerCase().indexOf(".nsf/luallbydockey/");
			if (pos1 > -1) {
				pos1 = pos1 + 19;
			}
		} else {
			pos1 = pos1 + 7
		}
	}

	var pos2 = url.toLowerCase().indexOf("?opendocument");
	if (pos2 == -1) {
		pos2 = url.toLowerCase().indexOf("?editdocument");
	}
	if ((pos1 != -1) && (pos2 != -1) && pos2 > pos1) {
		var docid = url.slice(pos1, pos2);
		if (docid.indexOf("DK") == 0) {
			docid = docid.slice(2);
		}
		return docid;
	} else {
		return "";
	}
} //--end getUnidFromUrl


/*********************************************************************************************
 * function: showSubscriptionDialog
 * Description: displays library subscription dialog
 * Inputs:
 * Output:
 *********************************************************************************************/
function showSubscriptionDialog() {
	var dlgUrl = "/" + docInfo.NsfName + "/" + "dlgSubscriptions" + (docInfo.SubscriptionsUseSearch == "1" ? "Search" : "") + "?ReadForm";

	var subdlg = Docova.Utils.createDialog({
			id: "divDlgSubscriptions",
			url: dlgUrl,
			title: "Library & Application Subscriptions",
			height: (docInfo.SubscriptionsUseSearch == "1" ? 420 : 520),
			width: 600,
			useiframe: true,
			buttons: {
				"Done": function () {
					subdlg.closeDialog();
					DLITFolderView.RefreshAllLibraries();
				}
			}
		});
} //--end showSubscriptionDialog


/*********************************************************************************************
 * function: generateContextMenu
 * Description: generates the right click folder context menu
 * Inputs: node - object - folder node right clicked on
 * Output: json object - containing menu parameters
 *********************************************************************************************/
function generateContextMenu(node) {
	var menuvar = {};

	//-- initial calculations used later in the show enable options
	var nodetype = (node.data && node.data.NodeType && node.data.NodeType != "") ? node.data.NodeType : "folder";
	var isrecyclebin = (node.data && node.data.FolderID && node.data.FolderID.indexOf("RCBIN") == 0);
	var libnode = DLITFolderView.getParentNodeByType(node, "library", true);
	var loaddocsasfolders = (libnode && libnode.data && libnode.data.LoadDocsAsFolders);
	var cancreatefolders = false;
	var cancreatedocs = false;
	var canrenamefolder = false;
	var cancutfolder = false;
	var candeletefolder = false;
	var canimportfiles = false;
	if (nodetype == "folder") {
		var security = null;
		if (DLITFolderView.currentnode && node.id == DLITFolderView.currentnode.id) {
			security = DLITFolderView.CurrentFolderAccess;
		} else {
			security = DLITFolderView.getFolderAccessData(node);
		}
		cancreatedocs = ((loaddocsasfolders && security) ? security.CanCreateDocuments : false);
		cancreatefolders = (security && (security.DocAccessLevel >= 6 || (security.CanCreateDocuments && !security.AuthorsCanNotCreateFolders)));
		canrenamefolder = (security && security.DocAccessLevel >= 6);
		cancutfolder = (security && security.DocAccessLevel >= 6);
		candeletefolder = (security && security.DocAccessLevel >= 6);
		canimportfiles = (security && security.DocAccessLevel > 2);
	}
	if (nodetype == "library") {
		cancreatefolders = CanCreateRootFolders(true, libnode);
	}
	var candeletedocs = false;
	if (nodetype == "document") {
		var foldernode = DLITFolderView.getParentNodeByType(node, "folder", false);
		var foldersecurity = DLITFolderView.getFolderAccessData(foldernode);
		candeletedocs = foldersecurity.CanDeleteDocuments;
	}

	//---- show/enable options for menu sections ---
	var showExpandAll = (nodetype == "folder" || (DLITFolderView.LoadAllAtStartup && nodetype != "document"));
	var enableExpandAll = showExpandAll;

	var showRefresh = (nodetype == "folder" && loaddocsasfolders);
	var enableRefresh = showRefresh;

	var showNewDocument = (nodetype == "folder" && loaddocsasfolders && !isrecyclebin);
	var enableNewDocument = showNewDocument && cancreatedocs;

	var showNewFolder = ((nodetype == "folder" || nodetype == "library") && !isrecyclebin);
	var enableNewFolder = cancreatefolders;

	var showRenameFolder = (nodetype == "folder" && !isrecyclebin);
	var enableRenameFolder = canrenamefolder;

	var showOpenInNewTab = (nodetype == "folder" || nodetype == "document");
	var enableOpenInNewTab = showOpenInNewTab;

	var showCutFolder = (nodetype == "folder" && !isrecyclebin);
	var enableCutFolder = cancutfolder;

	var showCopyFolder = (nodetype == "folder" && !isrecyclebin);
	var enableCopyFolder = showCopyFolder;

	var showPasteFolder = ((nodetype == "folder" || nodetype == "library") && (DLITFolderView.SourceFolderUnid != "") && !isrecyclebin);
	var enablePasteFolder = showPasteFolder;

	var showAddToFavorites = ((nodetype == "folder" && !isrecyclebin) || nodetype == "document");
	var enableAddToFavorites = showAddToFavorites;

	var showCopyFolderLink = (nodetype == "folder" && !isrecyclebin);
	var enableCopyFolderLink = showCopyFolderLink;

	var showDeleteFolder = (nodetype == "folder" && !isrecyclebin);
	var enableDeleteFolder = candeletefolder;

	var showDeleteDocument = (nodetype == "document")
	var enableDeleteDocument = candeletedocs;

	var showImportFiles = (nodetype == "folder" && !isrecyclebin);
	var enableImportFiles = canimportfiles;

	var showImportFolder = ((nodetype == "folder" || nodetype == "library") && !isrecyclebin);
	var enableImportFolder = cancreatefolders;

	var showExportFiles = ((nodetype == "folder" || nodetype == "library") && !isrecyclebin);
	var enableExportFiles = showExportFiles;

	var showProperties = (nodetype == "folder" && !isrecyclebin);
	var enableProperties = showProperties;

	if (showExpandAll) {
		menuvar["expandall"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC001 : "Expand All"),
			"icon": DLITFolderView.ImagePath + "iconExpandAll.png",
			"action": function () {
				processFolderContextMenu(node, "expandall");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableExpandAll
		};
	}

	if (showNewDocument) {
		menuvar["newdocument"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC002 : "New Document"),
			"icon": DLITFolderView.ImagePath + "iconNewDocument.png",
			"action": function () {
				processFolderContextMenu(node, "newdocument");
			},
			"separator_before": true,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableNewDocument
		};
	}

	if (showNewFolder) {
		menuvar["newfolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC003 : "New Folder"),
			"icon": DLITFolderView.ImagePath + "iconNewFolder.png",
			"action": function () {
				processFolderContextMenu(node, "newfolder");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableNewFolder
		};
	}

	if (showRenameFolder) {
		menuvar["renamefolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC004 : "Rename"),
			"icon": DLITFolderView.ImagePath + "iconRename.png",
			"action": function () {
				processFolderContextMenu(node, "renamefolder");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableRenameFolder
		};
	}

	if (showOpenInNewTab) {
		menuvar["openinnewtab"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC005 : "Open in New Tab"),
			"icon": DLITFolderView.ImagePath + "iconOpenInNewTab.png",
			"action": function () {
				processFolderContextMenu(node, "openinnewtab");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableOpenInNewTab
		};
	}

	if (showCutFolder) {
		menuvar["cutfolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC006 : "Cut Folder"),
			"icon": DLITFolderView.ImagePath + "iconCut.png",
			"action": function () {
				processFolderContextMenu(node, "cutfolder");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableCutFolder
		};
	}

	if (showCopyFolder) {
		menuvar["copyfolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC007 : "Copy Folder"),
			"icon": DLITFolderView.ImagePath + "iconCopy.png",
			"action": function () {
				processFolderContextMenu(node, "copyfolder");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableCopyFolder
		};
	}

	if (showPasteFolder) {
		menuvar["pastefolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC008 : "Paste Folder"),
			"icon": DLITFolderView.ImagePath + "iconPaste.png",
			"action": function () {
				processFolderContextMenu(node, "pastefolder");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enablePasteFolder
		};
	}

	if (showCopyFolderLink) {
		menuvar["copyfolderlink"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC009 : "Copy Folder Link"),
			"icon": DLITFolderView.ImagePath + "iconCopyFolderLink.png",
			"action": function () {
				processFolderContextMenu(node, "copyfolderlink");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableCopyFolderLink
		};
	}

	if (showDeleteFolder) {
		menuvar["deletefolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC010 : "Delete"),
			"icon": DLITFolderView.ImagePath + "iconDeleteFolder.png",
			"action": function () {
				processFolderContextMenu(node, "deletefolder");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableDeleteFolder
		};
	}

	if (showRefresh) {
		menuvar["refresh"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC011 : "Refresh Folder"),
			"icon": DLITFolderView.ImagePath + "iconRefresh.png",
			"action": function () {
				processFolderContextMenu(node, "refresh");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableRefresh
		};
	}

	if (showDeleteDocument) {
		menuvar["deletedocument"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC012 : "Delete"),
			"icon": DLITFolderView.ImagePath + "iconDeleteFolder.png",
			"action": function () {
				processFolderContextMenu(node, "deletedocument");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableDeleteDocument
		};
	}

	if (showAddToFavorites) {
		menuvar["addfavorite"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC013 : "Add to Favorites"),
			"icon": DLITFolderView.ImagePath + "iconAddFavorite.png",
			"action": function () {
				processFolderContextMenu(node, "addfavorite");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableAddToFavorites
		};
	}

	if (showImportFiles) {
		menuvar["importfiles"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC014 : "Import Files"),
			"icon": DLITFolderView.ImagePath + "iconImportFiles.png",
			"action": function () {
				processFolderContextMenu(node, "importfiles");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableImportFiles
		};
	}

	if (showImportFolder) {
		menuvar["importfolder"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC015 : "Import Folder"),
			"icon": DLITFolderView.ImagePath + "iconFolderImport.png",
			"action": function () {
				processFolderContextMenu(node, "importfolder");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableImportFolder
		};
	}

	if (showExportFiles) {
		menuvar["exportfiles"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC016 : "Export Files"),
			"icon": DLITFolderView.ImagePath + "iconExportFiles.png",
			"action": function () {
				processFolderContextMenu(node, "exportfiles");
			},
			"separator_before": false,
			"separator_after": true,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableExportFiles
		};
	}

	if (showProperties) {
		menuvar["propertiesdialog"] = {
			"label": (typeof prmptMessages !== "undefined" ? prmptMessages.msgFC017 : "Properties"),
			"icon": DLITFolderView.ImagePath + "iconProperties.png",
			"action": function () {
				processFolderContextMenu(node, "propertiesdialog");
			},
			"separator_before": false,
			"separator_after": false,
			"shortcut": "",
			"shortcut_label": "",
			"_disabled": !enableProperties
		};
	}

	return menuvar;
} //--end generateContextMenu

/*********************************************************************************************
 * function: processFolderContextMenu
 * Description: responds to actions initiated from right click folder context menu
 * Inputs: node - object - folder node right clicked on
 *             actionname - string - name of action selected from menu
 * Output:
 *********************************************************************************************/
function processFolderContextMenu(node, actionname) {
	//-- update stored node information if user has right clicked on a different node
	if (node.id != DLITFolderView.selectednode) {
		DLITFolderView.storeSelectedNode(node);
	}

	//-- hide the context menu
	jQuery.vakata.context.hide();

	//-- perform the selected action
	switch (actionname) {
		//-- Expand All Child Nodes
	case "expandall":
		DLITFolderView.ExpandAll(node);
		break;
		//-- Refresh Folder
	case "refresh":
		DLITFolderView.RefreshFolder(node);
		break;
		//-- Create Documentf
	case "newdocument":
		DLITFolderView.CreateDocument(node);
		break;
		//-- Create New Folder
	case "newfolder":
		DLITFolderView.CreateFolder("");
		break;
		//-- Rename Folder
	case "renamefolder":
		DLITFolderView.RenameFolder("", "", true);
		break;
		//-- Open in New Tab
	case "openinnewtab":
		DLITFolderView.OpenInNewTab();
		break;
		//-- Delete Document
	case "deletedocument":
		DLITFolderView.DeleteDocument(node);
		break;
		//-- Delete Folder
	case "deletefolder":
		DLITFolderView.DeleteFolder("");
		break;
		//-- Copy Folder
	case "copyfolder":
		DLITFolderView.CopyFolder("", "");
		break;
		//-- Cut Folder
	case "cutfolder":
		DLITFolderView.CutFolder("", "");
		break;
		//-- Paste Folder
	case "pastefolder":
		DLITFolderView.PasteFolder("", "");
		break;
		//-- Copy Folder Link
	case "copyfolderlink":
		DLITFolderView.CopyFolderLink();
		break;
		//-- Import Files
	case "importfiles":
		DLITFolderView.ImportFiles();
		break;
		//-- Export Files
	case "exportfiles":
		DLITFolderView.ExportFiles();
		break;
		//-- Properties Dialog
	case "propertiesdialog":
		DLITFolderView.ShowProperties();
		break;
		//-- Add Favorite
	case "addfavorite":
		DLITFolderView.AddFavorite();
		break;
	case "importfolder":
		DLITFolderView.ImportFolder();
		break;
	default:
		alert(actionname + " not implemented");
	}
} //-end processFolderContextMenu


/*********************************************************************************************
 * function: BeforeFolderCreate
 * Description: validates user access before folder create action
 * Output: boolean - true if authorized and no errors, false otherwise
 *********************************************************************************************/
function BeforeFolderCreate() {
	if (DEBUG && typeof console == "object") {
		console.log("BeforeFolderCreate->start.");
	};

	//----- If a library is selected then check to see if user can create sub-folders off of that library-----
	if (DLITFolderView.IsLibrarySelected === true) { //Library is selected
		if (!CanCreateRootFolders()) {
			if (DEBUG && typeof console == "object") {
				console.log("BeforeFolderCreate->returning false.");
			};
			return (false);
		}
	}

	//----- If folder is selected, check to see if the user, if an author, can create folders or not
	if (DLITFolderView.IsFolderSelected == true && !DLITFolderView.CurrentFolderAccess.isRecycleBin) { //Folder is selected
		if (!CanCreateFolders()) {
			if (DEBUG && typeof console == "object") {
				console.log("BeforeFolderCreate->returning false.");
			};
			return false;
		}
	}

	if (DEBUG && typeof console == "object") {
		console.log("BeforeFolderCreate->returning true.");
	};
	return true;
} //-end BeforeFolderCreate


/*********************************************************************************************
 * function: FolderCreate
 * Description: performs folder creation
 * Output: string - unid of folder created, boolean - false otherwise
 *********************************************************************************************/
function FolderCreate() {
	var result = false;

	var errortext = "";

	var isroot = (DLITFolderView.IsLibrarySelected);

	var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.FolderService;

	var request = "<Request>";
	request += "<Action>NEW</Action>";
	request += "<LibraryId>" + DLITFolderView.CurrentLibraryID + "</LibraryId>";
	request += "<DocKey>" + (isroot ? "" : DLITFolderView.CurrentUNID) + "</DocKey>";
	request += "<FolderID>" + (isroot ? (_DOCOVAEdition == "SE" ? DLITFolderView.CurrentLibraryID : "") : DLITFolderView.CurrentFolderID) + "</FolderID>";
	request += "<FolderUNID>" + (isroot ? (_DOCOVAEdition == "SE" ? DLITFolderView.CurrentLibraryID : "") : DLITFolderView.CurrentUNID) + "</FolderUNID>";
	request += "<Name><![CDATA[" + encodeURIComponent(DLITFolderView.NewFolderName) + "]]></Name>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				var unid = xmlobj.find("Result[ID=Unid]:first").text();
				if (unid && unid != undefined && unid != "") {
					DLITFolderView.NewNodeID = unid;
					result = true;
				}
			} else {
				errortext = xmlobj.find("Result[ID=ErrMsg]:first").text()
			}
		}
	}); //ajax close

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end FolderCreate


/*********************************************************************************************
 * function: BeforeFolderDelete
 * Description: validates user access and intetions before folder delete action
 * Output: boolean - true if authorized and no errors, false otherwise
 *********************************************************************************************/
function BeforeFolderDelete() {
	var result = false;

	var errortext = "";
	var rmerecordsfound = false;

	//--make sure this is a folder
	if (DLITFolderView.NodeType != "folder") {
		return (false);
	}

	//--------check access level--------------
	var AccessLevel = DLITFolderView.CurrentFolderAccess.DocAccessLevel;
	if (AccessLevel < 6) {
		alert("You have insufficient access to delete this folder.");
		return (false);
	}

	//----------------------------------------
	var answer = confirm("Are you sure you want to delete the \"" + DLITFolderView.CurrentFolderName + "\" folder? \n");
	if (answer) {
		result = true;
	} else {
		return (false);
	}

	//---------get records management status, if any document is being records managed, then user can not delete folder-----------
	if (docInfo.RMEEnabled == "1") {
		ShowProgressMessage("Detecting records managed documents. Please wait...")

		var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.FolderService;
		var request = "<Request>";
		request += "<Action>QUERYRECMANAGEMENT</Action>";
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<currentfolderUNID>" + DlitFolders.CurrentUNID + "</currentfolderUNID>";
		request += "</Request>";

		request = encodeURIComponent(request)

			jQuery.ajax({
				type: "POST",
				url: agentUrl,
				cache: false,
				async: false,
				contentType: "text/xml",
				data: request,
				dataType: "xml",
				success: function (xml) {
					var xmlobj = jQuery(xml);
					if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
						if (xmlobj.find("Result[ID=Result1]:first").text() == "1") {
							rmerecordsfound = true;
							errortext = "One or more 'Records Managed' documents were found under this folder \n or one of its subfolders.  It can NOT be deleted.";
							result = false;
						}
					} else {
						errortext = xmlobj.find("Result[ID=ErrMsg]:first").text();
						result = false;
					}
				}
			}); //-- end ajax

		HideProgressMessage();
	}

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end BeforeFolderDelete

/*********************************************************************************************
 * function: FolderDelete
 * Description: performs folder deletion
 * Output: boolean - true if no errors, false otherwise
 *********************************************************************************************/
function FolderDelete() {
	var result = false;

	var errortext = "";

	var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.FolderService;

	var request = "<Request>";
	request += "<Action>DELETE</Action>";
	request += "<DocKey>" + DLITFolderView.CurrentUNID + "</DocKey>";
	request += "<FolderID>" + DLITFolderView.CurrentFolderID + "</FolderID>";
	request += "<FolderUNID>" + DLITFolderView.CurrentUNID + "</FolderUNID>";
	request += "<Name></Name>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				result = true;
			} else {
				errortext = xmlobj.find("Result[ID=ErrMsg]:first").text()
			}
		}
	}); //-- end ajax

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end FolderDelete


/*********************************************************************************************
 * function: AfterFolderDelete
 * Description: action to perform after folder is deleted
 * Output: boolean - true if no errors, false otherwise
 *********************************************************************************************/
function AfterFolderDelete() {

	window.parent.fraTabbedTable.objTabBar.CloseTab(DLITFolderView.selectednode.id, true, "");

	return true;
} //--end AfterFolderDelete


/*********************************************************************************************
 * function: BeforeDocumentDelete
 * Description: validates user access and intention before document delete action
 * Inputs: callback - not implemented
 *         node - object - node object of document to delete
 * Output: boolean - true if authorized and no errors, false otherwise
 *********************************************************************************************/
function BeforeDocumentDelete(callback, node) {
	var result = false;

	var errortext = "";
	var rmerecordsfound = false;

	//--make sure this is a document
	if (node && node.data && node.data.NodeType && node.data.NodeType == "document") {
		//ok to continue
	} else {
		return (false);
	}

	//--------check access level--------------
	var foldernode = DLITFolderView.getParentNodeByType(node, "folder", false);
	var foldersecurity = DLITFolderView.getFolderAccessData(foldernode);
	if (!foldersecurity.CanDeleteDocuments) {
		alert("You have insufficient access to delete this document.");
		return (false);
	}

	//----------------------------------------
	var answer = confirm("Are you sure you want to delete document \"" + DLITFolderView.CurrentFolderName + "\"? \n");
	if (answer) {
		result = true;
	} else {
		return (false);
	}

	//---------get records management status, if the document is being records managed, then user can not delete it-----------
	if (docInfo.RMEEnabled == "1") {}

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end BeforeDocumentDelete


/*********************************************************************************************
 * function: DocumentDelete
 * Description: performs document deletion
 * Inputs: callback - not implemented
 *         node - object - document node to be deleted
 * Output: boolean - true if no errors, false otherwise
 *********************************************************************************************/
function DocumentDelete(callback, node) {
	var result = false;

	if (node && node.data && node.data.NodeType == "document" && node.data.Unid && node.data.Unid != "") {
		//--ok to continue
	} else {
		return false;
	}

	var errortext = "";

	var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.DocumentService;

	var request = "<Request>";
	request += "<Action>DELETE</Action>";
	request += "<Unid>" + node.data.Unid + "</Unid>";
	request += "<Name></Name>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				result = true;
			} else {
				errortext = xmlobj.find("Result[ID=ErrMsg]:first").text()
			}
		}
	}); //-- end ajax

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end DocumentDelete


/*********************************************************************************************
 * function: AfterDocumentDelete
 * Description: action to perform after document is deleted
 * Inputs: callback - not implemented
 *         node - object - node object for selected document
 * Output: boolean - true if no errors, false otherwise
 *********************************************************************************************/
function AfterDocumentDelete(callback, node) {

	if (node && node.id) {
		if (node.data && node.data.Unid) {
			window.parent.fraTabbedTable.objTabBar.CloseTab(node.data.Unid, true, "");
		}

		var foldernode = DLITFolderView.getParentNodeByType(node, "folder", false);
		if (foldernode && foldernode.id) {
			DLITFolderView.RefreshFolder(foldernode);
		}
	}

	return true;
} //--end AfterDocumentDelete


/*********************************************************************************************
 * function: GetFolderAccessCode
 * Description: gets descriptive name of access level based on numeric code
 * Inputs: accessLevel - integer - number representing users access level to folder
 * Output: string - Manager, Author, Reader or blank
 *********************************************************************************************/
function GetFolderAccessCode(accessLevel) {
	if (accessLevel >= 6) {
		return "Manager";
	} else if (accessLevel >= 3) {
		return "Author";
	} else if (accessLevel >= 2) {
		return "Reader";
	}
	return "";
} //-end GetFolderAccess

/*********************************************************************************************
 * function: CanCreateFolders
 * Description: determines if the current user is allowed to create sub folders
 * Inputs: silent - boolean (optional) - true to disable prompts
 * Output: boolean - true if user is able to create sub folders to the current folder
 *********************************************************************************************/
function CanCreateFolders(silent) {
	if (DLITFolderView.CurrentFolderAccess.isRecycleBin) {
		return false;
	}

	var AccessLevel = DLITFolderView.CurrentFolderAccess.DocAccessLevel;
	var folderaccessname = GetFolderAccessCode(AccessLevel);

	if (folderaccessname == "Manager") {
		return true;
	} else if (folderaccessname == "Author") {
		if (DLITFolderView.CurrentFolderAccess.AuthorsCanNotCreateFolders) {
			if (!silent) {
				alert("Authors have been restricted from creating sub-folders in this folder.")
			}
			return (false);
		} else {
			return (true);
		}
	}

	return false;
} //-end CanCreateFolders

/*********************************************************************************************
 * function: CanCreateRootFolders
 * Description: determines if the current user is allowed to create root folders in lib
 * Inputs: silent - boolean (optional) - true to disable prompts
 *         libnode - node object (optional) - optional library node
 * Output: boolean - true if user is able to create root folders to the current library
 *********************************************************************************************/
function CanCreateRootFolders(silent, libnode) {
	if (DEBUG && typeof console == "object") {
		console.log("CanCreateRootFolders->start.")
	};

	var result = false;
	var retval = false;
	if (_DOCOVAEdition == "SE") {
		var libid = ((libnode && libnode.data && libnode.data.DocKey) ? libnode.data.DocKey : DLITFolderView.CurrentLibraryID);
		var url = '/' + docInfo.NsfName + '/cancreaterootfolder/' + libid;

		jQuery.ajax({
			type: "GET",
			url: url,
			cache: false,
			async: false
		})
		.done(function (htmldata) {
			var jqhtmldata = $(htmldata);
			var values = $("#CanCreateRootFolders", jqhtmldata);
			if (values.length == 0) {
				values = $("[name=CanCreateRootFolders]", jqhtmldata);
			}
			if (values.length > 0) {
				retval = values.text();

			}
		})
		.fail(function () {
			if (!opts.failsilent) {
				alert("An error occurred retrieving data from the server.");
			}
		});
	} else {
		var ServerName = "";
		var NsfName = "";
		var view = "(LibraryDocKey)";
		var key = ((libnode && libnode.data && libnode.data.DocKey) ? libnode.data.DocKey : DLITFolderView.CurrentLibraryID);
		retval = Docova.Utils.dbLookup({
				"servername": ServerName,
				"nsfname": NsfName,
				"viewname": view,
				"key": key,
				"columnorfield": "CanCreateRootFolders",
				"delimiter": ";",
				"alloweditmode": false,
				"secure": docInfo.SecureAccess,
				"failsilent": true
			});
	}

	if (retval === false) {
		result = false;
		return result;
	}

	if (retval === "") {
		result = true;
		return result;
	}

	var srcArray = retval.split(",");
	var comArray = docInfo.UserNameList.split(",");
	var found = false;
	for (x in srcArray) {
		for (y in comArray) {
			srcArray[x] = srcArray[x].replace(/^\s+|\s+$/g, '');
			comArray[y] = comArray[y].replace(/^\s+|\s+$/g, '');
			if (srcArray[x] == comArray[y]) {
				found = true;
				break;
			}
		}
		if (found){
			break;
		}
	}

	if (!found) {
		if (!silent) {
			alert("You have insufficient access to create a root folder.");
		}
		result = false;
	} else {
		result = true;
	}

	return result;
} //-end CanCreateRootFolders


/*********************************************************************************************
 * function: BeforeFolderRename
 * Description: determines if the current user is allowed to rename a folder
 * Output: boolean - true if user is able to rename folder current folder
 *********************************************************************************************/
function BeforeFolderRename() {
	var result = false;

	var errortext = "";

	//--make sure this is a folder
	if (DLITFolderView.NodeType != "folder") {
		return (result);
	}

	//-----folder rename checked against folder properties to see if disabled ---
	//---------get rename allowed setting from folder properties -----------
	var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.FolderService;
	var request = "<Request>";
	request += "<Action>GETFOLDERINFO</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + DLITFolderView.CurrentUNID + "</Unid>";
	request += "<InfoType>FolderRenameAllowed</InfoType>";
	request += "</Request>";

	request = encodeURIComponent(request)

		jQuery.ajax({
			type: "POST",
			url: agentUrl,
			cache: false,
			async: false,
			contentType: "text/xml",
			data: request,
			dataType: "xml"
		})
		.done(function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				if (xmlobj.find("Result[ID=Ret1]:first").text() == "Yes") {
					result = true;
				} else {
					errortext = "Renaming of this folder has been disabled in the folder properties.";
				}
			} else {
				errortext = xmlobj.find("Result[ID=ErrMsg]:first").text()
			}
		}); //-- end ajax

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}
	return (result);
} //--end BeforeFolderRename


/*********************************************************************************************
 * function: FolderRename
 * Description: sends back end request to rename  folder
 * Output: boolean - true if folder rename processed successfully
 *********************************************************************************************/
function FolderRename() {
	var result = false;

	var errortext = "";

	var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.FolderService;

	var request = "<Request>";
	request += "<Action>RENAME</Action>";
	request += "<DocKey>" + DLITFolderView.CurrentUNID + "</DocKey>";
	request += "<FolderID>" + DLITFolderView.CurrentFolderID + "</FolderID>";
	request += "<FolderUNID>" + DLITFolderView.CurrentUNID + "</FolderUNID>";
	request += "<Name><![CDATA[" + encodeURIComponent(DLITFolderView.NewFolderName) + "]]></Name>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				result = true;
			} else {
				errortext = xmlobj.find("Result[ID=ErrMsg]:first").text()
			}
		}
	}); //-- end ajax

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end FolderRename


/*********************************************************************************************
 * function: AfterFolderRename
 * Description: refresh tab headings after folder rename
 * Output: boolean - true if no errors
 *********************************************************************************************/
function AfterFolderRename() {
	if (DEBUG && typeof console == "object") {
		console.log("AfterFolderRename->start");
	};

	//-------if renaming open folder, update right-frame --
	try {
		var currentFolder = DLITFolderView.CurrentUNID;
		if (DEBUG && typeof console == "object") {
			console.log("AfterFolderRename->DLITFolderView.CurrentUNID=" + currentFolder)
		};

		if (window.parent.fraTabbedTable.objTabBar) {
			if (window.parent.fraTabbedTable.objTabBar.IsFolderOpen(currentFolder)) {
				window.parent.fraTabbedTable.objTabBar.RenameFolder(currentFolder, DLITFolderView.NewFolderName);
			}
		}

		DLITFolderView.jstree.deselect_all(false);
		DLITFolderView.jstree.select_node(DLITFolderView.CurrentFolderID, true, true);

	} catch (e) {}
	//---------------------------------------
	return (true);
} //--end AfterFolderRename


/*********************************************************************************************
 * function: FolderCopy
 * Description: performs folder copy action
 * Output: boolean - true if no errors
 *********************************************************************************************/
function FolderCopy() {
	var result = false;

	DLITFolderView.WasFolderCut = false;
	DLITFolderView.SourceLibraryID = "";
	DLITFolderView.SourceFolderUnid = "";
	DLITFolderView.SourceFolderKey = "";

	if (!DLITFolderView.IsFolderSelected) {
		result = false;
	} else {
		DLITFolderView.SourceLibraryID = DLITFolderView.CurrentLibraryID;
		DLITFolderView.SourceFolderUnid = DLITFolderView.CurrentUNID;
		DLITFolderView.SourceFolderKey = DLITFolderView.CurrentFolderKey;
		result = true;
	}

	return (result);
} //--end FolderCopy


/*********************************************************************************************
 * function: BeforeFolderCut
 * Description: checks user access to a folder to see if they can cut it
 * Output: boolean - true if user access allows cut and no errors
 *********************************************************************************************/
function BeforeFolderCut() {
	var result = false;

	//----- Check for user access level for allowing cutting -----
	var AccessLevel = DLITFolderView.CurrentFolderAccess.DocAccessLevel;
	if (AccessLevel < 6) {
		alert("You have insufficient access to cut this folder.");
	} else {
		result = true;
	}
	return (result);
} //--end BeforeFolderCut


/*********************************************************************************************
 * function: FolderCut
 * Description: stores information for a cut folder for later use in paste
 * Output: boolean - true if no errors
 *********************************************************************************************/
function FolderCut() {
	DLITFolderView.TargetFolderUnid = "";
	DLITFolderView.TargetFolderKey = "";
	DLITFolderView.TargetLibraryID = "";

	DLITFolderView.SourceFolderUnid = DLITFolderView.CurrentUNID;
	DLITFolderView.SourceFolderKey = DLITFolderView.CurrentFolderKey;
	DLITFolderView.SourceLibraryID = DLITFolderView.CurrentLibraryID;

	DLITFolderView.WasFolderCut = true;

	return (true);
} //--end FolderCut


/*********************************************************************************************
 * function: BeforePasteFolder
 * Description: checks user access to a folder to see if they can paste a folder
 * Output: boolean - true if user access allows paste and no errors
 *********************************************************************************************/
function BeforePasteFolder() {
	var result = false;

	//------- check ability to paste root folder -------------
	if (DLITFolderView.CurrentLibraryID == DLITFolderView.CurrentFolderID) {
		result = CanCreateRootFolders();
	} else if (DLITFolderView.WasFolderCut && (DLITFolderView.SourceFolderUnid == DLITFolderView.CurrentUNID)) {
		alert("A folder cannot be moved onto itself.  Please choose a different destination.");
	} else {
		result = true;
	}

	return (result);
} //--end BeforePasteFolder


/*********************************************************************************************
 * function: PasteFolder
 * Description: performs paste of cut or copied folder
 * Output: boolean - true if no errors
 *********************************************************************************************/
function PasteFolder(cb) {
	var result = false;

	if (DLITFolderView.WasFolderCut && (DLITFolderView.SourceFolderUnid == DLITFolderView.CurrentUNID)) {
		alert("A folder cannot be moved onto itself.  Please choose a different destination.");
		return (false);
	}

	if (DLITFolderView.SourceLibraryID == "" || DLITFolderView.SourceFolderUnid == "" || DLITFolderView.SourceFolderKey == "") {
		alert("No source folder for paste. Please choose a valid paste source.")
		return (false);
	}

	if (DLITFolderView.TargetLibraryID == "" || DLITFolderView.TargetFolderUnid == "" || DLITFolderView.TargetFolderKey == "") {
		alert("No target folder for paste. Please choose a valid paste destination.")
		return (false);
	}

	var errortext = "";

	var agentUrl = DLITFolderView.CurrentLibraryUrl + "/LibraryServices?OpenAgent";

	var request = "<Request>";
	request += "<Action>PASTEFOLDER</Action>";
	request += "<clipaction>" + (DLITFolderView.WasFolderCut ? "CUTFOLDER" : "COPYFOLDER") + "</clipaction>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<srclibkey>" + DLITFolderView.SourceLibraryID + "</srclibkey>";
	request += "<srcfolder>" + DLITFolderView.SourceFolderKey + "</srcfolder>";
	request += "<srcfolderunid>" + DLITFolderView.SourceFolderUnid + "</srcfolderunid>";
	request += "<targetlibkey>" + DLITFolderView.TargetLibraryID + "</targetlibkey>";
	request += "<targetfolder>" + DLITFolderView.TargetFolderKey + "</targetfolder>";
	request += "<targetfolderunid>" + DLITFolderView.TargetFolderUnid + "</targetfolderunid>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				result = true;
				var newid = xmlobj.find("Result[ID=Ret1]:first").text();
				var newkey = xmlobj.find("Result[ID=Ret2]:first").text();
				//--a cut folder was pasted (ie. move) so update the tree display
				if (DLITFolderView.WasFolderCut) {
					DLITFolderView.jstree.set_id(DLITFolderView.SourceFolderKey, newkey); //-- reset the id on the node with the one returned from back end
					var newnode = DLITFolderView.jstree.get_node(newkey);
					newnode.data.FolderID = newkey;
					newnode.data.Unid = newid;
					DLITFolderView.jstree.move_node(newnode, DLITFolderView.TargetFolderKey);
					DLITFolderView.NewNodeID = newkey;
					cb.call();
					//-- a copied folder was pasted so refresh the tree display
				} else {
					var libnode = DLITFolderView.librarynode;
					DLITFolderView.LoadLibrary(libnode, function (libnode, status) {
						if (status) {
							var newnode = DLITFolderView.jstree.get_node(newid);
							DLITFolderView.NewNodeID = newid;
							cb.call();
						}
					});
				}
			} else {
				errortext = xmlobj.find("Result[ID=ErrMsg]:first").text()
			}
		}
	}); //-- end ajax

	if (!result) {
		if (errortext == "") {
			errortext = "Operating could not be completed due to a system error. Please contact your system administrator for help with this issue.";
		}
		alert(errortext);
	}

	return (result);
} //--end PasteFolder


/*********************************************************************************************
 * function: AfterPasteFolder
 * Description: refreshes list after paste of cut or copied folder
 * Output: boolean - true if no errors
 *********************************************************************************************/
function AfterPasteFolder() {

	DLITFolderView.jstree.select_node(DLITFolderView.NewNodeID, false, false);
	var FolderUrl = DLITFolderView.CurrentLibraryUrl + "/luAllByDocKey/" + DLITFolderView.NewNodeID + "?OpenDocument&syncnav=1";

	DLITFolderView.SourceLibraryID = "";
	DLITFolderView.SourceFolderUnid = "";
	DLITFolderView.SourceFolderKey = "";
	DLITFolderView.TargetLibraryID = "";
	DLITFolderView.TargetFolderUnid = "";
	DLITFolderView.TargetFolderKey = "";
	DLITFolderView.NewNodeID = "";
	DLITFolderView.WasFolderCut = false;

	LoadFrame(FolderUrl);

	return (true);
} //--end AfterPasteFolder


/*********************************************************************************************
 * function: OpenInNewTab
 * Description: opens a folder in a new independent tab
 * Output: boolean - true if no errors
 *********************************************************************************************/
function OpenInNewTab() {
	var result = false;

	var tabobj = window.parent.fraTabbedTable.objTabBar;
	tabobj.openFolderInNewTab = true;
	result = LoadCurrentFolder();
	DLITFolderView.jstree.deselect_all(false);
	DLITFolderView.jstree.select_node(DLITFolderView.CurrentFolderID, true, true);

	//	DLITFolderView.SyncFolderContent();
	tabobj.openFolderInNewTab = false;

	return (result);
} //--end OpenInNewTab


/*********************************************************************************************
 * function: CopyFolderLink
 * Description: copies folder url link
 * Output: boolean - true if no errors
 *********************************************************************************************/
function CopyFolderLink() {

	if (DLITFolderView.CurrentLibraryUrl == "") {
		return (false);
	}

	if (DLITFolderView.IsFolderSelected == false) {
		return (false);
	}
	var result = "";
	var errortext = "";

	if (_DOCOVAEdition == "SE") {
		var agentUrl = DLITFolderView.CurrentLibraryUrl + DLITFolderView.FolderService;
		var request = "<Request>";
		request += "<Action>GETFOLDERINFO</Action>";
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<Unid>" + DLITFolderView.CurrentUNID + "</Unid>";
		request += "<InfoType>FolderUrl</InfoType>";
		request += "</Request>";

		request = encodeURIComponent(request)

			jQuery.ajax({
				type: "POST",
				url: agentUrl,
				cache: false,
				async: false,
				contentType: "text/xml",
				data: request,
				dataType: "xml"
			})
			.done(function (xml) {
				var xmlobj = jQuery(xml);
				if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
					if (xmlobj.find("Result[ID=Ret1]:first").text()) {
						result = docInfo.ServerUrl + xmlobj.find("Result[ID=Ret1]:first").text();
					} else {
						errortext = "Could not get selected folder URL; contact admin for details.";
					}
				} else {
					errortext = xmlobj.find("Result[ID=ErrMsg]:first").text();
				}
			}); //-- end ajax

	} else {
		result = docInfo.ServerUrl + docInfo.PortalWebPath + "/wHomeFrame?ReadForm&goto=" + DLITFolderView.CurrentLibraryID + "," + DLITFolderView.CurrentUNID;
	}

	var html = '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr valign="top"><td width="100%">';
	html += '<div id="dlgContentNh" class="ui-widget" style="width:100%;">';
	html += '<span id="FieldLabel"class="frmLabel">Use CTRL+A to select and CTRL+C to copy:</span>';
	html += '<textarea style="font: 11px Verdana;width:350px;" class="txFld" id="docURL" name="docURL" rows=6>' + ($.trim(result) ? result : errortext) + '</textarea></div></td></tr></table>';

	var copyFolderLinkDlg = window.top.Docova.Utils.createDialog({
			id: "divCopyFolderUrl",
			title: "Folder Link URL",
			dlghtml: html,
			height: 250,
			width: 400,
			sourcewindow: window,
			buttons: {
				"Close": function () {
					copyFolderLinkDlg.closeDialog();
				}
			}
		});
	return (true);
} //--end CopyFolderLink


/*********************************************************************************************
 * function: SyncFolderContent
 * Description: either opens the folder highlighted in tree or highlights the open folder
 * Output: boolean - true if no errors
 *********************************************************************************************/
function SyncFolderContent() {
	var result = false;
	if (DEBUG && typeof console == "object") {
		console.log("SyncFolderContent->start");
	};
	//    try{
	var currentUNID = DLITFolderView.CurrentUNID;
	if (DEBUG && typeof console == "object") {
		console.log("SyncFolderContent->1");
	};
	var activeFolder = parent.frames[currentUNID];
	if (DEBUG && typeof console == "object") {
		console.log("SyncFolderContent->2");
	};
	if (activeFolder) {
		if (DEBUG && typeof console == "object") {
			console.log("SyncFolderContent->3");
		};
		//	     	LoadCurrentFolder();
		result = true;
	} else {
		if (DEBUG && typeof console == "object") {
			console.log("SyncFolderContent->4");
		};
		var activeFolderID = activeFolder.docInfo.FolderID;
		if (DEBUG && typeof console == "object") {
			console.log("SyncFolderContent->5");
		};
		if (activeFolderID) {
			if (DEBUG && typeof console == "object") {
				console.log("SyncFolderContent->6");
			};
			DLITFolderView.OpenFolder(activeFolderID, false);
			result = true;
		}
	}
	//	}catch(e){
	//	}
	if (DEBUG && typeof console == "object") {
		console.log("SyncFolderContent->end");
	};
	return (result);
} //--end SyncFolderContent


/*********************************************************************************************
 * function: ShowPropertiesMenu
 * Description: displays the library/folder properties dialog
 * Inputs:  cb - callback function (not used) - not implemented
 * 			options - (optional) - data value pair of optional parameters
 * Output: boolean - true if no errors
 *********************************************************************************************/
function ShowPropertiesMenu(cb, options) {
	var result = false;

	if (options && options.folderid) {
		result = OpenFolderProperties(options.folderid, (options.mode ? options.mode : null));
	} else {
		if (DLITFolderView.IsFolderSelected) {
			result = OpenFolderProperties();
		} else if (DLITFolderView.IsLibrarySelected) {
			result = OpenLibraryProperties();
		}
	}

	return (result);
} //--end ShowPropertiesMenu


/*********************************************************************************************
 * function: OpenFolderProperties
 * Description: displays the folder properties dialog
 * Inputs: folderID - string (optional) - id of folder to load properties dialog for
 *                    if not supplied the currently selected folder in the tree is used
 *         forceMode - string (optional) - R to force Read or E to force Edit mode
 *                    for the dialog. if not supplied folder security determines method
 * Output: boolean - true if no errors
 *********************************************************************************************/
function OpenFolderProperties(folderID, forceMode) {
	var result = false;
	//--------get lib url---------------------
	var LibraryUrl = DLITFolderView.CurrentLibraryUrl;
	if (LibraryUrl == "") {
		return (false);
	}

	var nodeid = (folderID && folderID != "") ? folderID : DLITFolderView.CurrentFolderID;
	var foldernode = DLITFolderView.jstree.get_node(nodeid);
	if (!foldernode && forceMode != 'R' && DLITFolderView.CurrentFolderID && DLITFolderView.CurrentFolderID.substr(0, 5) != "RCBIN") {
		return (false);
	}
	var targetKey = foldernode.id;
	if (foldernode) {
		var targetUnid = (foldernode.data && foldernode.data.Unid) ? foldernode.data.Unid : "";
	} else if (forceMode == 'R' && DLITFolderView.CurrentFolderID && DLITFolderView.CurrentFolderID.substr(0, 5) == "RCBIN") {
		targetUnid = folderID;
	} else {
		return false;
	}

	var AccessLevel = 0;
	//---------get access level--------------
	if (foldernode.id == DLITFolderView.CurrentFolderID) {
		AccessLevel = DLITFolderView.CurrentFolderAccess.DocAccessLevel;
	} else {
		var accessinfo = DLITFolderView.getFolderAccessData(foldernode);
		AccessLevel = accessinfo.DocAccessLevel;
	}
	var access = GetFolderAccessCode(AccessLevel);

	//--------- generate url for dialog ------------
	var isManager = (access == "Manager");
	var mode = (isManager) ? "&mode=E" : "&mode=R";
	if (forceMode && forceMode != "") {
		mode = "&mode=" + forceMode;
	}
	var dlgUrl = LibraryUrl + "/dlgFolderProperties?OpenForm&ParentUNID=" + targetUnid + mode + "&" + (new Date()).valueOf();
	//-- generate buttons for dialog ----
	var dialogbuttons = new Array();
	if (isManager) {
		//-- buttons for editable dialog
		dialogbuttons.push({
			text: "Apply Changes",
			"class": "split-button-left",
			click: function () {
				var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
				if (result) {
					dbox.closeDialog();
					if (DLITFolderView.FolderOpenUrl == "") {
						DLITFolderView.ReloadFolderProperties(targetKey);
						DLITFolderView.jstree.deselect_all(false);
						DLITFolderView.jstree.select_node(DLITFolderView.CurrentFolderID, true, true);

					}
				}
			}
		});
		dialogbuttons.push({
			icons: {
				primary: "ui-icon-gear"
			},
			"showText": false,
			"class": "split-button-right",
			click: function () {
				var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.setAdvancedSaveOptions();
			}
		});
		dialogbuttons.push({
			text: "Cancel",
			click: function () {
				dbox.closeDialog();
			}
		});
	} else {
		//-- buttons for read only dialog
		dialogbuttons.push({
			text: "Close",
			click: function () {
				dbox.closeDialog();
			}
		});
	}

	//-----open properties dialog-------------
	var dbox = Docova.Utils.createDialog({
			id: "divDlgFolderProperties",
			url: dlgUrl,
			title: "Folder Properties",
			height: 600,
			width: 600,
			useiframe: true,
			sourcedocument: document,
			buttons: dialogbuttons
		});

	jQuery(".split-button-left", window.top.document).removeClass("ui-corner-all").addClass("ui-corner-left").css({
		"margin-right": 0,
		"padding-right": 0
	});
	jQuery(".split-button-right", window.top.document).removeClass("ui-corner-all").addClass("ui-corner-right").css({
		"margin-left": 0,
		"padding-left": 0,
		'padding-bottom': '7px',
		'padding-top': '8px'
	});

	return (result);
} //--end OpenFolderProperties


/*********************************************************************************************
 * function: OpenLibraryProperties
 * Description: displays the library properties dialog
 * Output: boolean - true if no errors
 *********************************************************************************************/
function OpenLibraryProperties() {

	var LibraryUrl = DLITFolderView.CurrentLibraryUrl;
	if (LibraryUrl == "") {
		return (false);
	}
	//----------------------------------------
	var dlgParamaters = "dialogHeight: 390px; dialogWidth: 540px; dialogTop: px; dialogLeft: px; edge: raised; "
		dlgParamaters += "center: Yes; help: No; resizable: No; status: No;"
		//----------------------------------------
		var mode = "&mode=R"; //read only for now
	//----------------------------------------
	var dlgUrl = "/" + docInfo.NsfName + "/dlgLibraryProperties?OpenForm&ParentUNID=" + DLITFolderView.CurrentUNID + mode;
	var dlgParent = window;
	window.showModalDialog(dlgUrl, dlgParent, dlgParamaters);

	return (true);
} //--end OpenLibraryProperties


/*********************************************************************************************
 * function: AddFiles
 * Description: displays the import files dialog
 * Output: boolean - true if no errors
 *********************************************************************************************/
function AddFiles() {

	if (!window.top.Docova.IsPluginAlive) {
		window.top.Docova.Utils.messageBox({
			title: "DOCOVA Plugin Not Running",
			prompt: "The DOCOVA Plugin is required for the bulk importing of files.",
			width: 400,
			icontype: 4,
			msgboxtype: 0
		});
		return false;
	}

	var doc = document.all;

	//------- get library url ----------------
	var LibraryUrl = DLITFolderView.CurrentLibraryUrl;
	if (LibraryUrl == "") {
		return (false);
	}
	//--------- check if adding files to currently open folder
	var activeFolderID = "";
	try {
		var currentFolderUNID = DLITFolderView.CurrentUNID;
		var activeFolder = parent.frames[currentFolderUNID];
		if (_DOCOVAEdition == "SE") {
			activeFolderID = activeFolder.docInfo.FolderID;
		} else {
			activeFolderID = activeFolder.contentWindow.docInfo.FolderID;
		}
	} catch (e) {}

	//-------------------------- display file add dialog
	var dlgUrl = LibraryUrl + "/" + "dlgFileImport?OpenForm&ParentUNID=" + DLITFolderView.CurrentUNID;

	Docova.Utils.createDialog({
		url: dlgUrl,
		id: "dialogFileImport",
		useiframe: true,
		autoopen: true,
		title: "Import Files",
		width: "750",
		height: "460",
		buttons: [{
				text: "Finish",
				icons: {
					primary: "ui-icon-check"
				},
				click: function () {
					var iwin = $(this).find("iframe")[0].contentWindow;
					iwin.completeWizard(function () {
						Docova.Utils.closeDialog({
							id: "dialogFileImport",
							useiframe: true
						});
						if (DLITFolderView.CurrentFolderID == activeFolderID) {
							try {
								if (_DOCOVAEdition == "SE") {
									parent.frames[currentFolderUNID].objView.Refresh(true, false, true);
								} else {
									parent.frames[currentFolderUNID].contentWindow.objView.Refresh(true, false, true);
								}
							} catch (e) {}
						} else {
							LoadCurrentFolder();
						}
						return true;
					});
				}
			}, {
				text: "Cancel",
				icons: {
					primary: "ui-icon-cancel"
				},
				click: function () {
					Docova.Utils.closeDialog({
						id: "dialogFileImport",
						useiframe: true
					});
				}
			}
		]
	});
} //--end AddFiles


/*********************************************************************************************
 * function: FileExport
 * Description: displays the export files dialog
 * Output: boolean - true if no errors
 *********************************************************************************************/
function FileExport() {
	var result = false;

	if (!window.top.Docova.IsPluginAlive) {
		window.top.Docova.Utils.messageBox({
			title: "DOCOVA Plugin Not Running",
			prompt: "The DOCOVA Plugin is required for the bulk exporting of files.",
			width: 400,
			icontype: 4,
			msgboxtype: 0
		});
		return false;
	}

	//------- get library url ----------------
	var LibraryUrl = DLITFolderView.CurrentLibraryUrl;
	if (LibraryUrl == "") {
		return (false);
	}

	var doc = document.all;

	//------- get current library id ----------
	var libraryid = DLITFolderView.CurrentLibraryID;
	//------- get highlighted folder id ---------
	var folderid = DLITFolderView.CurrentFolderID;
	var folderUNID = DLITFolderView.CurrentUNID;

	//--------- check if adding files to currently open folder	---
	var activeFolderID = "";
	var tmpObjView = null;

	if (window.parent.fraTabbedTable.objTabBar.IsFolderOpen(folderUNID)) {
		var activeFolder = parent.frames[folderUNID];
		activeFolderID = folderid;
		tmpObjView = activeFolder.contentWindow.objView;
	}

	//--------- check if the library as a whole is selected instead of a folder
	var libraryselected = DLITFolderView.IsLibrarySelected;

	var dlgParamaters = "dialogHeight: 315px; dialogWidth: 440px;edge: raised; "
		dlgParamaters += "center: Yes; help: No; resizable: No; status: No;"
		var dlgInputs = new Array();
	dlgInputs.push(window);
	if (activeFolderID == folderid) {
		dlgInputs.push(tmpObjView);
	};

	window.top.Docova.GlobalStorage["dialogFileExport"] = {
		"dlginp": dlgInputs
	};

	var dlgUrl = LibraryUrl + "/" + "dlgFileExport?OpenForm&" + (libraryselected ? "libraryid" : "folderid") + "=" + folderid + "&currentonly=" + ((DLITFolderView.CurrentFolderAccess.DocAccessLevel > 2) ? "0" : "1")

		var expdialog = window.top.Docova.Utils.createDialog({
			url: dlgUrl,
			id: "dialogFileExport",
			useiframe: true,
			autoopen: true,
			title: "Export Files",
			width: "450",
			height: "415",
			buttons: [{
					text: "Export",
					icons: {
						primary: "ui-icon-check"
					},
					click: function () {
						var iwin = $(this).find("iframe")[0].contentWindow;
						if (iwin.exportFiles()) {

							window.top.Docova.Utils.closeDialog({
								id: "dialogFileImport",
								useiframe: true
							});

							return true;
						}
					}
				}, {
					text: "Cancel",
					icons: {
						primary: "ui-icon-cancel"
					},
					click: function () {
						window.top.Docova.Utils.closeDialog({
							id: "dialogFileExport",
							useiframe: true
						});
					}
				}
			]
		});

} //--end FileExport

/*********************************************************************************************
 * function: ImportFolder
 * Description: allows imort of a folder tree into docova.
 * Output: boolean - true if no errors
 *********************************************************************************************/
function ImportFolder() {
	var result = false;

	if (!window.top.Docova.IsPluginAlive) {
		window.top.Docova.Utils.messageBox({
			title: "DOCOVA Plugin Not Running",
			prompt: "The DOCOVA Plugin is required for the bulk importing of folders.",
			width: 400,
			icontype: 4,
			msgboxtype: 0
		});
		return false;
	}

	//------- get library url ----------------
	var LibraryUrl = DLITFolderView.CurrentLibraryUrl;
	if (LibraryUrl == "") {
		return (false);
	}

	var doc = document.all;

	//------- get current library id ----------
	var libraryid = DLITFolderView.CurrentLibraryID;
	//------- get highlighted folder id ---------
	var folderid = DLITFolderView.CurrentFolderID;
	var folderUNID = DLITFolderView.CurrentUNID;
	var fpath = ""
		fpath = DLITFolderView.getFolderPathByID(folderid);
	console.log(fpath);
	var limit = "";
	if (docInfo.LimitPathLength)
		limit = docInfo.LimitPathLength
			//-------------------------- display file add dialog
			var dlgUrl = LibraryUrl + "/" + "dlgFolderImport?OpenForm&ParentUNID=" + DLITFolderView.CurrentUNID;

	Docova.Utils.createDialog({
		url: dlgUrl,
		id: "dialogFolderImport",
		useiframe: true,
		autoopen: true,
		title: "Import Folder",
		width: "600",
		height: "260",
		buttons: [{
				text: "Continue",
				icons: {
					primary: "ui-icon-check"
				},
				click: function () {
					var iwin = $(this).find("iframe")[0].contentWindow;
					var incSubfolder = iwin.$("input[name='IncludeSubfolders']:checked").val();
					var rootFolder = iwin.$("#OutputFolder").val();
					var doctypekey = iwin.$("#DocumentTypeKey").val();
					if (doctypekey == "" || doctypekey == "N") {
						alert("Please select a document type before continuing!")
						return;
					}

					if (rootFolder == "") {
						alert("Please select source folder before continuing!")
						return;
					}
					Docova.Utils.closeDialog({
						id: "dialogFolderImport",
						useiframe: true
					});

					window.top.DocovaExtensions.ImportFolderTree({
						"RootFolder": rootFolder,
						"LibraryUrl": LibraryUrl,
						"ParentFolderUNID": folderUNID,
						"IncludeSubfolders": incSubfolder,
						"FolderPath": fpath,
						"FolderPathLimit": limit,
						"DocumentType": doctypekey,
						onDone: function () {

							DLITFolderView.RefreshFolder(folderid);
							DLITFolderView.OpenFolder(folderid, false, libraryid)

						}

					});

				}
			}, {
				text: "Cancel",
				icons: {
					primary: "ui-icon-cancel"
				},
				click: function () {
					Docova.Utils.closeDialog({
						id: "dialogFolderImport",
						useiframe: true
					});
				}
			}
		]
	});

}

/*********************************************************************************************
 * function: AddFavorite
 * Description: adds a folder to favorites list
 * Output: boolean - true if no errors
 *********************************************************************************************/
function AddFavorite() {
	var result = false;

	var agentUrl = DLITFolderView.FavoritesService;
	var request = "<Request>";
	request += "<Action>ADDFAVORITES</Action>";
	request += "<LibraryKey>" + DLITFolderView.CurrentLibraryID + "</LibraryKey>";
	request += "<FolderKey>" + DLITFolderView.CurrentUNID + "</FolderKey>";
	request += "<FolderID>" + DLITFolderView.CurrentFolderID + "</FolderID>";
	request += "<FolderUNID>" + DLITFolderView.CurrentUNID + "</FolderUNID>";
	request += "<FolderName><![CDATA[" + DLITFolderView.CurrentFolderName + "]]></FolderName>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				result = true;
			}
		}
	}); //-- end ajax

	if (!result) {
		alert("A problem was encountered adding the favorite. Please try again and if the problem persists contact your system administrator.");
	}

	return (result);
} //--end AddFavorite

/*********************************************************************************************
 * function: RefreshFavorites
 * Description: refreshes favorites list
 * Output: boolean - true if no errors
 *********************************************************************************************/
function RefreshFavorites() {
	DLITFolderView.FavoritesData.setSrc(DLITFolderView.FavoritesData.getSrc());

	return (true);
} //--end RefreshFavorites

/*********************************************************************************************
 * function: DeleteFavorite
 * Description: removes an item from the favorites list
 * Inputs: callback - not implemented
 *         data - value pair array containing
 *                "recordset" : recordset object
 *                "row" : selected row number
 * Output: boolean - true if no errors
 *********************************************************************************************/
function DeleteFavorite(callback, data) {
	var result = false;
	data.recordset.AbsolutePosition(data.row - 1);
	var docUNID = data.recordset.Fields("Unid").getValue();
	if (docUNID == "") {
		alert("Error: Unable to determine id of selected favorite.");
		return (false);
	}

	var agentUrl = DLITFolderView.FavoritesService;
	var request = "<Request>";
	request += "<Action>DELETEFAVORITES</Action>";
	request += "<Unid>" + docUNID + "</Unid>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		cache: false,
		async: false,
		contentType: "text/xml",
		data: request,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			if (xmlobj.find("Result[ID=Status]:first").text() == "OK") {
				result = true;
			}
		}
	}); //-- end ajax

	return (result);
} //--end DeleteFavorite


function SetRowColor(obj, mode) {

	if (mode == true) {
		$(obj).css('background-color', '#dfefff');
		$(obj).prop('title', "Click to open the document");
	} else {
		$(obj).css('background-color', '');
		$(obj).prop('title', '');
	}
}

/*********************************************************************************************
 * function: OpenFavorite
 * Description: opens a selected item from the favorites list
 * Output: boolean - true if no errors
 *********************************************************************************************/
function OpenFavorite(rs, row) {
	if (!rs || !row) {
		return false;
	}
	rs.AbsolutePosition(row - 1);
	var libID = rs.Fields("LibraryKey").getValue();
	var libPath = getLibraryPath(libID);
	if (!libPath) {
		alert("Could not locate the source library for this document.");
		return false;
	}
	var recType = rs.Fields("rectype").getValue();
	var folderName = rs.Fields("foldername").getValue();
	var folderID = rs.Fields("folderid").getValue();
	var docKey = rs.Fields("parentdocid").getValue();
	var docTitle = rs.Fields("Title").getValue();

	if (recType == "doc") {
		if (_DOCOVAEdition == "SE") {
			var url = libPath + "/ReadDocument/" + docKey + "?OpenDocument" + "&ParentUNID=" + folderID; //&syncnav=1
		} else {
			var url = libPath + "/luAllByDocKey/" + folderID + "?OpenDocument" + "&loaddoc=" + docKey; //&syncnav=1
		}
	} else if (recType == "fld") {
		var url = libPath + "/luAllByDocKey/" + folderID + "?OpenDocument"; //&syncnav=1";
	}

	//--select folder in tree without triggering folder content open since loadframe call will open folder content and possibly doc
	DLITFolderView.OpenFolder(folderID, false, libID, function (result) {
		//-- open the document
		if (recType == "doc") {
			OpenDocument(libID, docKey, docTitle);
		}
		//-- make folder tree visible
		jQuery("#divFolderControlTabs").tabs("option", "active", 0);

		if (!result) {
			alert("The selected folder could not be found in the listing.")
		}
	});
} //--end OpenFavorite


/**********************************************************************************************
 * Function: OpenDocument
 * Opens a document in a new tab
 * Inputs: libID - string (optional) - library key of library containing the document to open
 *                 if not specified, the currently highlighted/open library in the folder
 *                 control will be used
 *         docID - string - document key or unid of document to be opened
 *         tabTitle - string (optional) - title of the tab used to display the opened document
 ***********************************************************************************************/
function OpenDocument(libID, docID, tabTitle) {
	if (!docID) {
		return false;
	}

	if (!libID){
		var libID = DLITFolderView.CurrentLibraryID;
	}
	var libPath = getLibraryPath(libID);
	if (!libPath) {
		alert("Could not locate the source library for this document.");
		return;
	}

	if (!folderID)
		var folderID = DLITFolderView.CurrentFolderID;
	var folderUNID = DLITFolderView.CurrentUNID;

	//open the document
	if (_DOCOVAEdition == "SE") {
		var docUrl = docInfo.ServerUrl + libPath + "/ReadDocument/" + docID + "?OpenDocument&ParentUNID=" + folderUNID;
	} else {
		var docUrl = docInfo.ServerUrl + libPath + "/luAllByDocKey/" + docID + "?OpenDocument";
	}
	if (tabTitle == undefined || tabTitle == null || tabTitle == ""){
		tabTitle = "Document";
	}

	if (_DOCOVAEdition == "SE") {
		var foldertabwindow = window.top.Docova.getUIWorkspace(document).getDocovaFrame('tabs', 'window');
	} else {
		var foldertabwindow = window.top.Docova.getUIWorkspace(document).getDocovaFrame('fraTabbedTable', 'window');
	}
	if (foldertabwindow) {
		foldertabwindow.objTabBar.CreateTab(tabTitle, docID, "D", docUrl, "", false);
	}

} //--end OpenDocument

/**********************************************************************************************
 * Function: DocumentCreate
 * Creates a document in a new tab
 * Inputs: callback - not implemented
 *         foldernodeorid - node object or string (optional) - folder node or id of folder
 *                 to create document in. If not specified, the currently highlighted/open
 *                 folder in the folder control will be used
 ***********************************************************************************************/
function DocumentCreate(callback, foldernodeorid) {
	var foldernode = null;
	if (foldernodeorid && foldernodeorid.id) {
		foldernode = foldernodeorid;
	} else if (foldernodeorid) {
		foldernode = DLITFolderView.jstree.get_node(foldernodeorid);
	}
	if (!foldernode) {
		foldernode = DLITFolderView.selectednode;
	}
	if (!foldernode) {
		return false;
	}

	var libPath = "";
	var libnode = DLITFolderView.getParentNodeByType(foldernode, "library", false);
	if (libnode && libnode.data.NsfName) {
		libPath = libnode.data.NsfName;
	}
	if (!libPath) {
		return false;
	}

	var folderunid = foldernode.data.Unid;

	var docUrl = libPath + "/Document?OpenForm&ParentUNID=" + folderunid;

	// 	var docTypeArray=docInfo.DocumentType.split(", ");
	// 	if(docTypeArray.length==1 && docTypeArray[0] !="")
	// 	{
	// 		docUrl += "&typekey=" + docInfo.DocumentType;
	// 		ViewLoadDocument(docUrl, docInfo.DocumentTypeName, true);
	// 	}
	// 	else
	// 	{
	var dlgUrl = libPath + "/" + "dlgSelectDocType?OpenForm&ParentUNID=" + folderunid;
	var tmpDocova = (window.top.Docova ? window.top.Docova : Docova);
	var doctypedlg = tmpDocova.Utils.createDialog({
			id: "divDlgSelectDocType",
			url: dlgUrl,
			title: "New Document",
			height: 425,
			width: 400,
			useiframe: true,
			buttons: {
				"Create Document": function () {
					var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
					if (result && result.DocumentType) {
						docUrl += "&typekey=" + result.DocumentType;
						doctypedlg.closeDialog();

						var frameID = window.parent.fraTabbedTable.objTabBar.GetNewDocID();
						var title = "New " + result.DocumentTypeName;
						window.parent.fraTabbedTable.objTabBar.CreateTab(title, frameID, "D", docUrl, folderunid, true);
					}
				},
				"Cancel": function () {
					doctypedlg.closeDialog();
				}
			}
		});
	// 	}
	return true;
} //--end DocumentCreate


/**********************************************************************************************
 * Function: getLibraryPath
 * Returns library path for a specified library key. Only functions for subscribed libraries.
 * Inputs: libKey - string - library key of library to obtain path of
 * Returns: string - web path of library db
 ***********************************************************************************************/
function getLibraryPath(libKey) {

	var libnode = DLITFolderView.jstree.get_node(libKey);
	if (!libnode) {
		return false;
	}
	var libpath = libnode.data.NsfName;

	return libpath;
} //--end getLibraryPath

/**********************************************************************************************
 * Function: resizeStart
 * Waits for a specified period of time before responding to a resize event
 * Inputs: cb - callback function - function to call (after a wait) when resize event is triggered
 ***********************************************************************************************/
function resizeStart(cb) {
	rtime = new Date();
	if (timeout === false) {
		timeout = true;
		setTimeout(function () {
			resizeEnd(cb)
		}, delta);
	}
} //-- end resizeStart

/**********************************************************************************************
 * Function: resizeEnd
 * Waits for a specified period of time before responding to a resize event
 * Inputs: cb - callback function - function to call (after a wait) when resize event is triggered
 ***********************************************************************************************/
function resizeEnd(cb) {
	if (new Date() - rtime < delta) {
		setTimeout(function () {
			resizeEnd(cb);
		}, delta);
	} else {
		timeout = false;
		cb.call();
	}
} //-- end resizeEnd

/**********************************************************************************************
 * Function: adjustFolderPaneSize
 * Adjusts the width of the folder pane to account for resizing of page/frame
 ***********************************************************************************************/
function adjustFolderPaneSize() {
	try {
		var vpheight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		if (vpheight == 0) {
			return;
		} //-- in case we aren't able to get the height
		var headingheight = jQuery("ul.ui-tabs-nav.ui-widget-header:first").outerHeight();
		var newheight = vpheight - headingheight - (DLITFolderView.Border * 2);
		if (DLITFolderView.MaxHeight > 0 && DLITFolderView.MaxHeight < newheight) {
			newheight = DLITFolderView.MaxHeight;
		}
		//		jQuery("div.ui-tabs-panel.ui-widget-content").height(newheight) ;
		jQuery("#divTreeView").parents("div:first").height(newheight);

		//var windowwidth = jQuery(window.parent.document).width() || window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth
		//if(windowwidth == 0){return;}  //-- in case we aren't able to get the width
		//jQuery("#divFolderControlStopWrap").width(windowwidth);
	} catch (e) {}
} //--end adjustFolderPaneSize

/**********************************************************************************************
 * Function: loadGoTo
 * Responds to a &goto= parameter provided as part of the url opening the folder control
 * used to trigger the opening of a particular folder and document
 ***********************************************************************************************/
function loadGoTo() {
	if (DLITFolderView.IsInitialized && !DLITFolderView.IsLoading) {
		if (docInfo && docInfo.GoToParam && docInfo.GoToParam != "") {
			var gotostring = docInfo.GoToParam;
			var gotostringlc = gotostring.toLowerCase();
			if (gotostringlc.indexOf("dashboard") == 0 ||
				gotostringlc.indexOf("admin") == 0 ||
				gotostringlc.indexOf("designer") == 0 ||
				gotostringlc.indexOf("rme") == 0 ||
				gotostringlc.indexOf("search") == 0) {
				//--do nothing
			} else {
				if (gotostringlc.indexOf("library") == 0) {
					gotostring = docInfo.GoToParam.slice(8);
				}
				var gotoArr = gotostring.split(",");
				if (gotoArr.length > 1) {
					var libID = gotoArr[0];
					var folderID = gotoArr[1];
					if (_DOCOVAEdition == "Domino") {
						if (folderID.substring(0, 2) != "DK"){
							folderID = "DK" + folderID;
						}
					}

					var docID = "";
					if (gotoArr.length > 2) {
						docID = gotoArr[2];
						//if( docID.substring(0,2) == "DK" ) docID = docID.slice(2);
					}
					//--select folder in tree without triggering folder content open since loadframe call will open folder content and possibly doc
					DLITFolderView.OpenFolder(folderID, false, libID, function (result) {
						if (docID != "") {
							//-- open the document
							OpenDocument(libID, docID, "");
						}

						if (!result) {
							alert("The selected folder could not be found in the listing.")
						}
					});
				}
			}

		}
	}
} //--end loadGoTo