var _DOCOVAEdition = "SE";  //-- set this variable to either SE or Domino depending upon where this code is installed

Docova.Uploader = function (options) {

	/*----------------------------------------------------------------------------------------------
	 * Function: Uploader
	 * Displays the attachment manager interface
	 *
	 * Parameters:
	 * options - value pair object containing the following
	 *		AttachmentNames - string - List of filenames that exist on that document separated by a separator (*) by default.
	 *		AttachmentSeparator - string - separator for attachment names.
	 *		AttachmentDates - string - text list of attachment dates separated by a ", "
	 *		CheckoutXmlUrl - string - url that points to the xml data for checked out files
	 *		onBeforeAdd(filename) - function - called before a file is added to uploader.
	 *				parameters : filename - string - name of file being added.
	 *				function must return true to allow addition of the file or false to skip adding this file.
	 *		onAdd(filename) - function - function to run after a file has been added to uploader.
	 *				parameters : filename - string - name of newly added file.
	 *		onBeforeDownload(filename) function - function to run before a file is downloaded.
	 *				parameters : filename - string - name of file being downloaded.
	 *				function must return true to allow download of the file or false to skip downloading this file.
	 *		onBeforeFileEdit(filename) - function - function to run before a file is edited.
	 *				parameters : filename - string - name of file being edited.
	 *				function must return true to allow editing of the file or false to skip editing this file.
	 *				this function requires the desktop plugin
	 *		onBeforeLaunch(filename) - function - function to run before a file is 'viewed'.
	 *				parameters : filename - string - name of file being viewed.
	 *				function must return true to allow viewing of the file or false to skip viewing this file.
	 *		onBeforeDelete(filename) - function - function to run before a file is 'deleted'.
	 *				parameters : filename - string - name of file being deleted.
	 *				function must return true to allow deletion of the file or false to skip deleting this file.
	 *		onDownload(filename) - function - function to run after a file has been added to downloaded.
	 *				parameters : filename - string - name of downloaded file.
	 *		onDelete(filename) - function - function to run after a file has been deleted.
	 *				parameters : filename - string - name of deleted file.
	 *		onLaunch(filename) - function - function to run after a file has been launched.
	 *				parameters : filename - string - name of launched file.
	 *		onFileEdit(filename) - function - function to run after a file has been edited.
	 *				parameters : filename - string - name of edited file.
	 *				NOTE: this event requires the desktop plugin
	 *		onScan() - function - function to run on scan trigger
	 *				parameters: none
	 *				NOTE: this event requires the desktop plugin
	 *		IsChildUploader:  - boolean - in a document with multiple uploaders, specifies if the uploader is a child.
	 *				 NOTE: For the first uploader on a form, leave this paramater as false...true for all others.
	 *		EnableFileCleanup: integer - set to 1 to allow for files to be removed once they have been submitted successfully
	 *				after a file has been edited.
	 *		CleanupToRecycleBin: boolean - set to true to allow the files that have been cleaned up from the "enablefilecleanup"
	 *				parameter to be sent to the re-cycle bin instead of being deleted.
	 *		GenerateThumbnails: string - set to "1" to create thumbnails for files where possible and post those along with the file
	 *				thumbnail files are posted with the document with the following naming convention.  Filename.ext~dthmb.bmp
	 *		ThumbnailWith: string - width of the thumbnails if enabled.
	 *		ThumbnailHeight: string - height of the thumbnails if enabled.
	 *		LocalDelete: string - specify "1" to allow files to be deleted to the re-cycle bin once a new file has been successfully
	 *						uploaded.
	 *		LocalDeleteExcludes: string - list of directories separated by a ";"
	 *				if LocalDelete is set to "1" this parameter specifies the exception list of directories from where files will NOT be
	 *				removed after a successful upload.
	 *		DownloadPath: string - path where files are to be downloaded
	 *					"0" - system temp folder
	 *					"1" - My Documents folder
	 *					"2" - Desktop folder
	 *					a full path can also be specified e.g. DownloadPath: "c:\temp"
	 *		EditPath: string - path where files are downloaded for file edits
	 *					"0" - system temp folder
	 *					"1" - My Documents folder
	 *					"2" - Desktop folder
	 *					a full path can also be specified e.g. EditPath: "c:\temp"
	 *		LaunchLocally: string - comma delimited list of extensions that should be downloaded and launched locally on the machine
	 *					instead of being opened in the browser
	 *					i.e.  doc,docx,ppt
	 *		MaxFiles: integer - maximum number of files to allow in this uploader
	 *					0 to allow any number (default)
	 *		AllowedFileExtensions: string - comma separated list of file extensions allowed in this uploader
	 *					leave empty to allow all file types.
	 *		AllowAdd:  - string - "1" to allow addition of files to uploader.
	 *		AllowDelete: - string - "1" to allow deletion of files from uploader.
	 *		AllowLaunch: - string - "1" to allow launching of file from uploader.
	 *		AllowDownload: string - "1" to allow downloading of file from uploader.
	 *		AllowFileEdit: string - "1" to allow editing of file from uploader.
	 *		AllowScan: string - "1" to enable scanning of file to uploader
	 *
	 *		divId: string - name of the div that the uploader will draw itself under
	 *
	 *		refId: string - user defined reference for the uploader
	 *
	 *		FieldName: string - name to use as field identifier to store file names to
	 *
	 *		ListType: string - "I" for icon view...anything else for details view
	 *
	 *		FileCtrlId: string - Id of the file upload control
	 *
	 *		Height: string - Height of uploader.
	 *
	 *	Example:
	 *		DLIUploader1= new Docova.Uploader({
	 *				divId : "attachDisplay",
	 *				AttachmentNames : "file1.txt*file2.txt",
	 *				AttachmentLengths:  "44, 55",
	 *				AttachmentDates:  "01/03/2015, 05/03/2015",
	 *				ListType: "I",
	 *				onBeforeAdd: function(filename) {alert ("you are adding " + filename ); return true;}
	 *		});
	 *--------------------------------------------------------------------------------------------*/

	var defaultOptns = {
		AttachmentNames: "",
		AttachmentSeparator: "*",
		AttachmentLengths: "",
		AttachmentDates: "",
		CheckoutXmlUrl: "",
		onBeforeAdd: function () {},
		onAdd: function () {},
		onBeforeDownload: function () {},
		onBeforeFileEdit: function () {},
		onBeforeLaunch: function () {},
		onBeforeDelete: function () {},
		onDownload: function () {},
		onDelete: function () {},
		onDownload: function () {},
		onLaunch: function () {},
		onFileEdit: function () {},
		onScan: function () {},
		IsChildUploader: false,
		EnableFileCleanup: 1,
		CleanupToRecycleBin: true,
		GenerateThumbnails: "0",
		LocalDelete: "",
		LocalDeleteExcludes: "",
		DownloadPath: "",
		EditFilePath: "0",
		ThumbnailWidth: "100",
		ThumbnailHeight: "100",
		LaunchLocally: "",
		MaxFiles: 0,
		AllowedFileExtensions: "",
		AllowAdd: "1",
		AllowDelete: "1",
		AllowLaunch: "1",
		AllowDownload: "1",
		AllowFileEdit: "1",
		AllowScan: "1",
		refId: "",
		divId: "attachDisplay",
		FieldName: "",
		ListType: "R",
		FileCtrlId: "fileAttach",
		Height: "200"
	};

	//**************Begin init of uploader **************//
	var opts = $.extend({}, defaultOptns, options);

	var filesList = [];
	this.addedFiles = [];
	this.deletedFiles = [];
	this.editedFiles = [];
	this.renamedFiles = [];

	this.onBeforeAdd = function (which) {
		return opts.onBeforeAdd.call(this, which)
	};
	this.onBeforeFileEdit = function (which) {
		return opts.onBeforeFileEdit.call(this, which)
	};
	this.onFileEdit = function (which) {
		return opts.onFileEdit.call(this, which)
	};
	this.onBeforeDelete = function (which) {
		return opts.onBeforeDelete.call(this, which)
	};
	this.onBeforeDownload = function (which) {
		return opts.onBeforeDownload.call(this, which)
	};
	this.onDownload = function (which) {
		return opts.onDownload.call(this, which)
	};
	this.onBeforeLaunch = function (which) {
		return opts.onBeforeLaunch.call(this, which)
	};
	this.onLaunch = function (which) {
		return opts.onLaunch.call(this, which)
	};
	this.onDelete = function (which) {
		return opts.onDelete.call(this, which)
	};
	this.OnAddEvent = "";
	this.onAdd = function (which, flobj) {
		if (this.OnAddEvent != "") {
			var fn = window[this.OnAddEvent];
			if (typeof fn === 'function') {
				return fn.call(this, which);
			}
		} else
			return opts.onAdd.call(this, which, flobj)

	};
	this.onScan = function (which) {
		return opts.onScan.call(this, which)
	};

	//local variables and properties
	var jqXHR;
	var updiv = $("#" + opts.divId);
	var fileCtrl = $("#" + opts.FileCtrlId);
	var container = this;
	this.xmlFileLog = null;
	var FileBeingCheckedIn = "";
	this.pluginEnabled = false;
	this.forceNative = false;
	this.SubmitResultPage = "";
	this.refId = opts.refId;
	this.GenerateThumbnails = opts.GenerateThumbnails;
	this.ThumbnailWidth = opts.ThumbnailWidth;
	this.ThumbnailHeight = opts.ThumbnailHeight;
	this.DownloadPath = opts.DownloadPath;
	this.EditFilePath = opts.EditFilePath;
	this.EnableFileCleanup = opts.EnableFileCleanup;
	this.CleanupToRecycleBin = opts.CleanupToRecycleBin;
	this.MaxFiles = opts.MaxFiles;
	this.AllowedFileExtensions = opts.AllowedFileExtensions;
	this.LocalDelete = opts.LocalDelete;
	this.LocalDeleteExcludes = opts.LocalDeleteExcludes;
	this.AllowAdd = opts.AllowAdd;
	this.AllowDelete = opts.AllowDelete;
	this.AllowLaunch = opts.AllowLaunch;
	this.AllowDownload = opts.AllowDownload;
	this.AllowFileEdit = opts.AllowFileEdit;
	this.AllowScan = opts.AllowScan;
	this.FileCtrlId = opts.FileCtrlId;
	this.FieldName = opts.FieldName;
	this.Height = opts.Height;
	this.ParentUploader = ((opts.IsChildUploader && DLIUploader1) ? DLIUploader1 : null);
	this.ChildUploaders = null;
	this.UploaderCallingAdd = null;

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: ShowCheckoutDiv
	 * Displays the checkout info dialog when hovering over the "lock" icon of a checked out file
	 * Inputs: the font awesome lock icon object
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.ShowCheckoutDiv = function (obj) {

		var contDiv = $(obj).parents(".item");
		var infoDiv = contDiv.find(".editinfo");
		$("#dialog-checkoutinfo").remove();
		var pdiv = $("<div id='dialog-checkoutinfo'><div style= 'padding:4px;' id='dialog-checkoutinfo-content'></div></div>");
		pdiv.appendTo(document.body);

		$("#dialog-checkoutinfo").dialog({
			modal: false,
			position: {
				my: "left top",
				at: "left bottom",
				of: obj
			},
			draggable: false,
			minHeight: "45px",
			resizable: false,
			width: 450
		});

		$("#dialog-checkoutinfo").prev("div").hide();
		$("#dialog-checkoutinfo-content").html(infoDiv.html());
		$("#dialog-checkoutinfo-content").find(".editinfo").show();
		$("#dialog-checkoutinfo").dialog("open");
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: HideCheckoutDiv
	 * Hides the checkout info dialog when the mouse leaves the lock icon of a checked out file
	 * Inputs: the font awesome lock icon object
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.HideCheckoutDiv = function (event) {
		$("#dialog-checkoutinfo").on("mouseleave", function (e) {
			$("#dialog-checkoutinfo").dialog("close");
			$("#dialog-checkoutinfo").remove();
		});

	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: getDivId
	 * Returns - string - the id of the div under which uploader interface is rendered
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.getDivId = function () {
		return opts.divId;
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: attachEvents
	 * Function that attaches the uploader to various events.
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.attachEvents = function () {

		var elem;
		if (opts.ListType == "I") {
			elem = "div";
		} else{
			elem = "tr";
		}
		//In icon view show the checkout info when the user hovers over the lock icon
		if (opts.ListType == "I") {
			$("#tbodyUploader" + opts.divId).on("mouseover", ".fa-lock", function () {
				container.ShowCheckoutDiv(this);
			}).on("mouseleave", ".fa-lock", function (event) {
				container.HideCheckoutDiv(event);
			});
		}

		//closes the checkout info div on mouse leave.
		$("#tbodyUploader" + opts.divId).on("mouseleave", ".item", function (event) {
			if ($("#dialog-checkoutinfo")[0]) {

				//dont' close if we are still inside the checkout div
				var toelem = event.relatedTarget;
				if ($(toelem).hasClass("ui-dialog")){
					return;
				}
				$("#dialog-checkoutinfo").dialog("close");
				$("#dialog-checkoutinfo").remove();
			}
		});

		//show highlight on row when the mouse hovers over a row
		$("#tbodyUploader" + opts.divId).on("mouseover", elem, function () {
			if ($(this).hasClass("editinfo")){
				return;
			}
	

			//reset the highlight once the mouse leaves
		}).on("mouseout", elem, function () {
			if ($(this).hasClass("editinfo")){
				return;
			}
			if ($(this).attr("sel") != "yes") {
			

			}

		}).on("click", elem, function (e) { //selects the clicked row
			if ($(this).hasClass("editinfo")){
				return;
			}
			//if already selected then single click opens the context menu
			if ($(this).attr("sel") == "yes") {
				container.ShowContextMenu(atobEx($(this).attr("filename")), e)

			} else {

				var prev = $(this).parent().find(elem + "[sel=yes]");
				if (prev[0]) {

					
					prev.removeClass("selected")
					prev.attr("sel", "");
				}

			
				$(this).addClass('selected');
				$(this).attr("sel", "yes");

			}
		}).on("contextmenu", elem, function (e) {
			//display the context menu
			e.preventDefault();
			if ($("#dialog-checkoutinfo")[0]) {
				$("#dialog-checkoutinfo").dialog("close");
				$("#dialog-checkoutinfo").remove();
			}
			//re-set previously selected
			var prev = $(this).parent().find(elem + "[sel=yes]");
			if (prev[0]) {

				prev.removeClass('selected');
				prev.attr("sel", "");
			}
			$(this).attr("sel", "yes");
			$(this).addClass('selected');
			container.ShowContextMenu(atobEx($(this).attr("filename")), e)
			e.stopPropagation();
		})

		//-- add column sorting
		$("#tblUploader" + opts.divId).find("span[name=hdrFileName]").on("click", function () {
			var sord = jQuery(this).attr("sortord");
			if (sord === "asc") {
				sord = "dec";
			} else {
				sord = "asc";
			}
			jQuery(this).attr("sortord", sord);
			container.sortFileList("filename", sord);
		});
		$("#tblUploader" + opts.divId).find("span[name=hdrFileDate]").on("click", function () {
			var sord = jQuery(this).attr("sortord");
			if (sord === "asc") {
				sord = "dec";
			} else {
				sord = "asc";
			}
			jQuery(this).attr("sortord", sord);
			container.sortFileList("filedate", sord);
		});
		$("#tblUploader" + opts.divId).find("span[name=hdrFileSize]").on("click", function () {
			var sord = jQuery(this).attr("sortord");
			if (sord === "asc") {
				sord = "dec";
			} else {
				sord = "asc";
			}
			jQuery(this).attr("sortord", sord);
			container.sortFileList("filesize", sord);
		});
		$("#tblUploader" + opts.divId).find("span[name=hdrFileType]").on("click", function () {
			var sord = jQuery(this).attr("sortord");
			if (sord === "asc") {
				sord = "dec";
			} else {
				sord = "asc";
			}
			jQuery(this).attr("sortord", sord);
			container.sortFileList("filetype", sord);
		});

		var obj = $("#" + opts.divId);

		var parentobj = obj.parent().parent();
		if ( parentobj.attr("onefilemode") && parentobj.attr("onefilemode") === "true"){
			obj = parentobj.parent().find(".singleuploaderedit");
		}

		obj.unbind();
		obj.on('contextmenu', function (e) {
			//context menu on the div with the uploader to show the "paste" option
			e.preventDefault();

			container.ShowContextMenu("", e)
			e.stopPropagation();
		});

		obj.on('dragenter', function (e) {
			e.stopPropagation();
			e.preventDefault();
			if (!docInfo.isDocBeingEdited){
				return;
			}
			$(this).toggleClass("dragEnter")
		});
		obj.on('dragover', function (e) {

			e.stopPropagation();
			e.preventDefault();
			if (!docInfo.isDocBeingEdited){
				return;
			}
		});
		obj.on('dragleave', function (e) {
			e.stopPropagation();
			e.preventDefault();
			if (!docInfo.isDocBeingEdited){
				return;
			}
			$(this).toggleClass("dragEnter")

		});
		obj.on('drop', function (e) {
			//drag drop code
			if (container.AllowAdd != "1"){
				return;
			}
			if (!docInfo.isDocBeingEdited){
				return;
			}
			$(this).removeClass("dragEnter");
			e.preventDefault();
			e.stopPropagation();
			var files = e.originalEvent.dataTransfer.files;

			for (var p = 0; p < files.length; p++) {

				if (container.onBeforeAdd.call(container, files[p].name) == false){
					return;
				}

				//if plugin in running then the drop code posts the dropped file to the plugin
				//the plugin saves this file in a temp folder and then "adds" the file to uploader.

				if (container.pluginEnabled) {
					var fd = new FormData();

					fd.append('file', files[p]);

					Docova.Utils.showProgressMessage("Preparing file for uploading. Please wait...");

					var surl = getPluginURL();
					surl += "action=droppedFile";
					surl += "&fileurl=" + encodeURIComponent(location.href);
					surl += "&localdelete=" + container.LocalDelete;
					surl += "&localdeleteexcludes=" + container.LocalDeleteExcludes;
					$.ajax({
						url: surl,
						type: "POST",
						data: fd,
						processData: false,
						contentType: false,
						async: false,
						dataType: "json"

					}).done(function (data) {

						if (data.status == "SUCCESS") {
							//file successfully posted to the local plugin.data.filepath is where the file has been saved
							var flpath = atobEx(data.filepath);
							var dt = files[p].lastModifiedDate ? files[p].lastModifiedDate : files[p].lastModified;
							var d = new Date(dt);
							container.addedFiles.push(flpath);
							container.DisplayAddedFile(files[p].name, flpath, Docova.Utils.convertDateFormat(d, "", true), files[p].size, files[p].type);
							container.onAdd.call(container, files[p].name);

						}

					})
					.fail(function (xhr, status, error) {
						window.top.Docova.Utils.messageBox({
							icontype: 1,
							msgboxtype: 0,
							prompt: "Error! Unable to communicate with Docova plugin.",
							title: "Error"
						});
					})
					.always(function () {
						Docova.Utils.hideProgressMessage();
					})
				} else {
					//non plugin...just record the files object in the addedFiles array
					var dt = files[p].lastModifiedDate ? files[p].lastModifiedDate : files[p].lastModified;
					var d = new Date(dt);
					container.addedFiles.push(files[p]);
					container.DisplayAddedFile(files[p].name, "", Docova.Utils.convertDateFormat(d, "", true), files[p].size, files[p].type);
					container.onAdd.call(container, files[p].name, files[p]);

				}
			}

		});
		window.addEventListener("dragover", function (e) {
			e = e || event;
			e.preventDefault();
		}, false);
		window.addEventListener("drop", function (e) {
			e = e || event;
			e.preventDefault();
		}, false);

	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: init
	 * Function that initializes the uploader
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.init = function (isrefresh) {

		if (opts.ListType == "I"){
			tbl = $("<div id='tbodyUploader" + opts.divId + "'></div>");
		}else{
			tbl = $("<table id='tblUploader" + opts.divId + "' class='upTable'><thead class='attachHeader dWidgetHeader'><tr><td style='width:40%; padding:8px'><span class='upheader' name='hdrFileName'>Name</span></td><td style='width:20%;'><span class='upheader' name='hdrFileDate'>Date</span></td><td style='width:20%;'><span class='upheader' name='hdrFileSize'>Size</span></td><td><span class='upheader' name='hdrFileType'>Type</span></td></tr></thead><tbody id='tbodyUploader" + opts.divId + "'></tbody></table>");
		}
		//tbl.insertAfter(updiv);
		updiv.html(tbl);

		//load existing attachments
		this.LoadAttachments();

		this.attachEvents();

		//if this a child uploader then no need to get the checkout xml as the parent will do this
		//also no need to initialize the file upload object again
		if (!opts.IsChildUploader) {
			this.RefreshCheckoutXml();

			//fileupload control being used in a non plugin scenerio.  fileuploa is a jquery plugin
			file_upload = fileCtrl.fileupload({
					autoUpload: false,
					dropZone: null
				}).on("fileuploadadd", function (e, data) {
					//function to call when a file is added without the plugin running
					if (container.UploaderCallingAdd) {
						//need to call add file from the child uploader
						container.UploaderCallingAdd.AddFileNoPlugin(data);
					} else {
						//can call add file from the parent (this) uploader
						container.AddFileNoPlugin(data);
					}
				})
				.bind('fileuploadprogressall', function (e, data) {
					//this method is called by the fileupload plugin to display the progress of the upload

					var progress = parseInt(data.loaded / data.total * 100, 10);
					var context = window.top.document;

					$(".progTotal", context).text(Math.round(data.total / 1024) + ' KB');
					$(".progSent", context).text(Math.round(data.loaded / 1024) + ' KB');
					$(".progBitrate", context).text(Math.round(data.bitrate / (1024 * 1000)) + ' Mbps');
					$(".progress-label", context).text(progress + "%");
					$("#progressbar", context).progressbar({
						value: progress
					});
				});
		} else {
			if (!isrefresh) {
				//this is a child uploader so add it to the parent uploader's child uploader list
				if (this.ParentUploader) {
					if (this.ParentUploader.ChildUploaders === null) {
						this.ParentUploader.ChildUploaders = [];
					}
					this.ParentUploader.ChildUploaders.push(this);
				}
			}
		}

		if (opts.ListType == "I"){
			$("#tbodyUploader" + opts.divId).css({
				'min-height': this.Height + "px"
			});
		}else{
			$("#tbodyUploader" + opts.divId).css({
				'min-height': this.Height + "px",
				'height': this.Height + "px"
			});
		}
		
		if (window.top.Docova.IsPluginAlive == false) {
			window.top.Docova.checkPluginAlive({
				onSuccess: function () {
					window.top.Docova.IsPluginAlive = true;

					$("[id=upmode]").text("Advanced");
					$("[id=uploaderMode]").prop('title', 'Uploader is running in advanced mode.  All features are available.');
					if ( container.forceNative ){
						container.pluginEnabled = false;
					}
					else{
						container.pluginEnabled = true;
					}
				},
				onFailure: function () {
					window.top.Docova.IsPluginAlive = false;
					$("[id=upmode]").text("Basic");
					$("[id=uploaderMode]").prop('title', 'Uploader is running in basic mode.  Not all features are available.');

					container.pluginEnabled = false;
				}
			});

		} else {
			if ( container.forceNative ){
				container.pluginEnabled = false;
			}else{
				this.pluginEnabled = window.top.Docova.IsPluginAlive;
			}
						
			if (this.pluginEnabled) {
				$("[id=upmode]").text("Advanced");
				$("[id=uploaderMode]").prop('title', 'Uploader is running in advanced mode.  All features are available.');

			} else {
				$("[id=upmode]").text("Basic");
				$("[id=uploaderMode]").prop('title', 'Uploader is running in basic mode.  Not all features are available.');

			}
		}

	} //endof Init
	//****************End of Uploader INIT **************//

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: reloadSettings
	 * Function that re-initializes some uploader settings
	 * Inputs: newoptions - name value pair object containing new configuration settings to apply
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.reloadSettings = function (newoptions) {
		opts = $.extend({}, opts, newoptions);
		updiv = $("#" + opts.divId);
		fileCtrl = $("#" + opts.FileCtrlId);
		this.ParentUploader = ((opts.IsChildUploader && DLIUploader1) ? DLIUploader1 : null);
		this.init(true);
		if (this.deletedFiles && Array.isArray(this.deletedFiles) && this.deletedFiles.length > 0) {
			if (console) {
				console.log("DEBUG - need to delete removed files");
			}
			for (var i = 0; i < this.deletedFiles.length; i++) {
				var fnameinfo = this.deletedFiles[i].split("|");
				this.RemoveFile(this.GetFileIndex(fnameinfo[fnameinfo.length - 1]), true);
			}
		}
		if (this.addedFiles && Array.isArray(this.addedFiles) && this.addedFiles.length > 0) {
			if (console) {
				console.log("DEBUG - need to add extra files");
			}
			for (var i = 0; i < this.addedFiles.length; i++) {
				var filepath = "";
				var filename = "";
				var filedate = Docova.Utils.convertDateFormat(new Date(), "", true);
				var filelength = 0;
				if (typeof this.addedFiles[i] == "string") {
					//TODO - need to store attachment length and size somehow
					var fnameinfo = this.addedFiles[i].split("|");
					if (fnameinfo.length == 1) {
						var shortname = fnameinfo[0];
						var nameparts = shortname.split("\\");
						if (nameparts.length == 1) {
							nameparts = shortname.split("\/");
						}
						if (nameparts.length > 1) {
							shortname = nameparts[nameparts.length - 1];
						}
						fnameinfo.push(shortname);
					}
					filepath = fnameinfo[0];
					filename = fnameinfo[fnameinfo.length - 1];
				} else if (typeof this.addedFiles[i] == "object") {
					filedate = Docova.Utils.convertDateFormat(new Date(this.addedFiles[0].lastModifiedDate), "", true);
					filelength = this.addedFiles[0].size;
					filename = this.addedFiles[0].name;
					filepath = "";
				}
				this.DisplayAddedFile(filename, filepath, filedate, filelength, "");
			}
		}
		if (this.editedFiles && Array.isArray(this.editedFiles) && this.editedFiles.length > 0) {
			if (console) {
				console.log("DEBUG - need to mark files as edited");
			}
			for (var i = 0; i < this.editedFiles.length; i++) {
				var fnameinfo = this.editedFiles[i].split("|");
				if (fnameinfo.length == 1) {
					var shortname = fnameinfo[0];
					var nameparts = shortname.split("\\");
					if (nameparts.length == 1) {
						nameparts = shortname.split("\/");
					}
					if (nameparts.length > 1) {
						shortname = nameparts[nameparts.length - 1];
					}
					fnameinfo.push(shortname);
				}
				this.ShowEditedFileInfo(fnameinfo[fnameinfo.length - 1], fnameinfo[0]);
			}
		}
	} //end of reloadSettings


	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: ShowContextMenu
	 * Displays the context menu
	 * Inputs : filename - name of file under the context menu
	 *          obj - jquery object under the context menu
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.ShowContextMenu = function (filename, obj) {
		var menuArr = [];
		var isEditMode = (docInfo.isDocBeingEdited == "true");
		var disable_all = false;
		if (updiv.closest('div[elemtype=section][elem=fields]').attr('enablecasection') == '1' && updiv.closest('div[elemtype=section][elem=fields]').attr('sectionaccess') == '0') {
			disable_all = true;
		}

		//if no filename then this is a right click on the uploader div...show the paste option
		if (filename == "") {
			menuArr.push({
				title: "Paste",
				itemicon: "ui-icon-copy",
				action: "ProcessEntrySubmenuAction('paste', '" + opts.divId + "')",
				disabled: (!isEditMode || !this.pluginEnabled || disable_all)
			});

			var menu = Docova.Utils.menu({
					delegate: obj, //will link the menu to the clicked button
					width: 200,
					menus: menuArr
				});
			return;
		}

		var isItemActive = false;
		var actionHandler = "ProcessEntrySubmenuAction(this)";
		var fileEdited = this.IsFileEdited(filename);
		var checkedOut = this.IsFileCheckedOut(filename);
		var fileNew = this.IsFileNew(filename);

		//hide the checkout popup if exists

		var viewDisable = (this.AllowLaunch != "1" || fileEdited || fileNew);
		menuArr.push({
			title: "View File",
			itemicon: "ui-icon-newwin",
			action: "ProcessEntrySubmenuAction('view','" + opts.divId + "')",
			disabled: viewDisable
		});

		var downloadDisable = (this.AllowDownload != "1" || fileNew);
		menuArr.push({
			title: "Download File",
			itemicon: "ui-icon-extlink",
			action: "ProcessEntrySubmenuAction('download', '" + opts.divId + "')",
			disabled: downloadDisable
		});

		var isDel = this.IsFileDeleted(filename);
		var delDisable = (disable_all || !isEditMode || isDel || checkedOut || this.AllowDelete != "1")
		menuArr.push({
			title: "Delete File",
			itemicon: "ui-icon-scissors",
			action: "ProcessEntrySubmenuAction('delete', '" + opts.divId + "')",
			disabled: delDisable
		});
		var unDelDisable = (disable_all || !isEditMode || !isDel)
		menuArr.push({
			title: "Undelete File",
			itemicon: "ui-icon-arrowrefresh-1-n",
			action: "ProcessEntrySubmenuAction('undelete', '" + opts.divId + "')",
			disabled: unDelDisable
		});

		menuArr.push({
			title: "Edit File",
			itemicon: "ui-icon-pencil",
			action: "ProcessEntrySubmenuAction('edit', '" + opts.divId + "')",
			disabled: (disable_all || !isEditMode || checkedOut || this.AllowFileEdit != "1" || !this.pluginEnabled)
		});
		menuArr.push({
			title: "Rename File",
			itemicon: "ui-icon-carat",
			action: "ProcessEntrySubmenuAction('rename', '" + opts.divId + "')",
			disabled: (disable_all || !isEditMode || checkedOut)
		});
		menuArr.push({
			title: "Paste",
			itemicon: "ui-icon-copy",
			action: "ProcessEntrySubmenuAction('paste', '" + opts.divId + "')",
			disabled: (disable_all || !isEditMode || !this.pluginEnabled)
		});

		if (docInfo.isFileCIAOEnabled) {
			menuArr.push({
				separator: true
			});

			var disableCheckout = (disable_all || !isEditMode || checkedOut || !this.pluginEnabled)
			menuArr.push({
				title: "Check-Out File",
				itemicon: "ui-icon-arrowreturn-1-s",
				action: "ProcessEntrySubmenuAction('checkout', '" + opts.divId + "')",
				disabled: disableCheckout
			});
			menuArr.push({
				title: "Check-In File",
				itemcon: "ui-icon-arrowreturn-1-n",
				action: "ProcessEntrySubmenuAction('checkin', '" + opts.divId + "')",
				disabled: (disable_all || !isEditMode || !checkedOut)
			});
			menuArr.push({
				title: "Cancel Checkout",
				itemcon: "ui-icon-arrowrefresh-1-n",
				action: "ProcessEntrySubmenuAction('cancelcheckout', '" + opts.divId + "')",
				disabled: (disable_all || !isEditMode || !checkedOut)
			});
		}

		//show the menu
		var menu = Docova.Utils.menu({
			delegate: obj, //will link the menu to the clicked button
			width: 200,
			menus: menuArr
		});
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: RefreshCheckoutXml
	 * Function that re-fetches the checkout xml data from the server and refreshes the interface
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.RefreshCheckoutXml = function () {

		if (opts.CheckoutXmlUrl == ""){
			return;
		}
		$.ajax({
			type: "GET",
			url: opts.CheckoutXmlUrl,
			dataType: "xml",
			success: function (data) {
				container.SetCheckoutXmlData(data);
			}
		}).error(function (jqXHR, textStatus, errorThrown) {
			if (errorThrown) {
				window.top.Docova.Utils.messageBox({
					icontype: 1,
					msgboxtype: 0,
					prompt: "Error reading checkout xml data!",
					title: "Error"
				});
			}
		});
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: SetCheckoutXmlData
	 * Function that refreshes the checkout info in uploader
	 * Inputs : data - checkout data in xml format
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.SetCheckoutXmlData = function (data) {
		this.xmlFileLog = data;
		var fileRow;
		var filename;
		//Remove old checkout info rows first
		$(".editinfo").each(function (index, elem) {

			if (opts.ListType == "I") {
				fileRow = $(this).parents(".item");
				filename = atobEx($(fileRow).attr("filename"));
				htm = getFileIcon(opts.ListType, filename);
				htm += "<span class='caption'>" + filename + "</span>";
				fileRow.html(htm);
			} else {
				fileRow = $(this).prev();
				filename = atobEx($(fileRow).attr("filename"));
				var td = fileRow.children('td:first');
				td.html(getFileIcon(opts.ListType,filename) + "<span class='lcaption'" + filename + "</span>");

			}
			$(this).remove();
		});

		var oXMLNodeList = this.xmlFileLog.selectNodes("cofiles/file");
		for (var idx = 0; idx < oXMLNodeList.length; idx++) {
			var filenameNode = oXMLNodeList.item(idx).selectSingleNode('filename');
			var filename = (filenameNode) ? (filenameNode.textContent || filenameNode.text) : "";
			var byNode = oXMLNodeList.item(idx).selectSingleNode('editor');
			var by = (byNode) ? byNode.textContent || byNode.text : "";
			var onNode = oXMLNodeList.item(idx).selectSingleNode('date');
			var on = (onNode) ? onNode.textContent || onNode.text : "";

			if (filename != "") {
				//show the checkout info based on the list type ..Icons, details
				this.ShowCheckoutInfo(filename, by, on);
			}
		}

		if (opts.ListType == "I") {
			$(".editinfotext").css("padding-left", "0px");
		}
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: GetCheckedOutFileInfo
	 * Function to return the checkout details for a particular file
	 * Inputs : fileName  - string - name of file
	 * Outputs : array of checkout info
	 * 			array[0] - editor
	 * 			array[1] - checkout date
	 * 			array[2] - local path where file is checked out
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.GetCheckedOutFileInfo = function (fileName) {
		var objCoFiles = this.xmlFileLog;
		var fileNode;
		fileNode = objCoFiles.selectSingleNode('cofiles/file[fnamelc="' + fileName.toLowerCase() + '"]');
		if (fileNode == null) {
			return false;
		}
		var fileInfo = new Array();
		var infoNode = null;

		infoNode = fileNode.selectSingleNode("editor");
		fileInfo[0] = (infoNode == null) ? "" : infoNode.text || infoNode.textContent;
		infoNode = fileNode.selectSingleNode("date");
		fileInfo[1] = (infoNode == null) ? "" : infoNode.text || infoNode.textContent;
		infoNode = fileNode.selectSingleNode("path");
		fileInfo[2] = (infoNode == null) ? "" : infoNode.text || infoNode.textContent;

		return fileInfo;
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: _getFileRow
	 * Returns the data row containing the specified file entry
	 * Inputs: fileNameOrIndex - string or integer - name of file or index of file
	 * Returns: jquery object of file data row element, or null if not found
	 * Example:  this._getFileRow(1) or this._getFileRow("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this._getFileRow = function (fileNameOrIndex) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var flname = "";
		if (typeof fileNameOrIndex === "string") {
			flname = btoaEx(fileNameOrIndex);
		} else {
			flname = btoaEx(this.FileName(fileNameOrIndex, true));
		}

		var row = $("#tbodyUploader" + opts.divId).find(cont + "[filename='" + flname + "']");
		if (row.length == 0) {
			return null;
		} else {
			return row;
		}
	} //--end _getFileRow


	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: IsFileNew
	 * Returns true if the specified file is newly added, otherwise false
	 * Inputs: fileNameOrIndex - integer or string - index of file, or name of file
	 * Returns: true if file is newly added, false otherwise
	 * Example: DLIUploader1.IsFileNew(1) or DLIUploader1.IsFileNew("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.IsFileNew = function (fileNameOrIndex) {
		var row = this._getFileRow(fileNameOrIndex);
		if (row && row.attr("isnew") == "yes") {
			return true;
		} else {
			return false;
		}
	} //--end IsFileNew

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: IsFileDeleted
	 * Returns true if the specified file has been marked as deleted, otherwise false
	 * Inputs: fileNameOrIndex - integer or string - index of file, or name of file
	 * Returns: true if file is marked for deletion, false otherwise
	 * Example: DLIUploader1.IsFileDeleted(1) or DLIUploader1.IsFileDeleted("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.IsFileDeleted = function (fileNameOrIndex) {
		var row = this._getFileRow(fileNameOrIndex);

		if (row && row.attr("isdeleted") == "yes") {
			return true;
		} else {
			return false;
		}
	} //--end IsFileDeleted

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: IsFileEdited
	 * Returns true if the specified file is edited, otherwise false
	 * Inputs: fileNameOrIndex - integer or string - index of file, or name of file
	 * Returns: true if file is edited, false otherwise
	 * Example: DLIUploader1.IsFileEdited(1) or DLIUploader1.IsFileEdited("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.IsFileEdited = function (fileNameOrIndex) {
		var row = this._getFileRow(fileNameOrIndex);

		if (row && row.attr("isedited") == "yes") {
			return true;
		} else {
			return false;
		}
	} //--end IsFileEdited


	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: IsFileCheckedOut
	 * Returns true if the specified file is checked out, otherwise false
	 * Inputs: fileName - string - name of file
	 * Returns: true if file is checked out, false otherwise
	 * Example: DLIUploader1.IsFileCheckedOut("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.IsFileCheckedOut = function (fileName) {
		var fileNode = this.GetFileCheckoutNode(fileName);
		return (fileNode != null)
	}; //--end IsFileCheckedOut

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: GetFileCheckoutNode
	 * Returns xml node for specified checked out file
	 * Inputs: fileName - string - name of file
	 * Returns: xml node of checked out file entry, or null
	 * Example: DLIUploader1.GetFileCheckoutNode("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.GetFileCheckoutNode = function (fileName) {
		if (this.xmlFileLog == null){
			return null;
		}

		//--- make sure that we get just the file name part
		fileName = GetFileName(fileName);
		//--- get checked out files xml object
		var objCoFiles = this.xmlFileLog;
		//--- check if any of the selected files is already out, if so, complain and terminate
		var fileNode;
		fileNode = objCoFiles.selectSingleNode('cofiles/file[fnamelc="' + fileName.toLowerCase() + '"]');
		return (fileNode)
	}; //--end GetFileCheckoutNode


	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: ShowEditedFileInfo
	 * Updates display of file to indicate that it is being edited
	 * Inputs: fileNameOrIndex - string or number - name of file or index of file
	 *         encfullname - string - full path to file in encoded format
	 * Returns:
	 * Example:
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.ShowEditedFileInfo = function (fileNameOrIndex, encfullname) {
		var filename = "";
		if (typeof fileNameOrIndex === "string") {
			filename = fileNameOrIndex;
		} else {
			filename = this.FileName(fileNameOrIndex, true);
		}

		var row = this._getFileRow(fileNameOrIndex);
		if (row && row[0]) {
			if (opts.ListType == "I") {
				var attachHtml = getFileIcon(opts.ListType, filename, "edit");
				attachHtml += "<span class='caption'>" + filename + "</span>";
				$(row).html(attachHtml);

			} else {
				var td = $(row).children('td:first');
				td.html(getFileIcon(opts.ListType, filename, "edit") + "<span class='lcaption'>" + filename + "</span>");
			}
			$(row).attr("isedited", "yes");
			$(row).attr("fullname", encfullname);

		}
	}; //--end ShowEditedFileInfo

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: ShowCheckoutInfo
	 * Updates display of file to indicate that it has been checked out
	 * Inputs: fileNameOrIndex - string or number - name of file or index of file
	 *         by - string - who has checked out this file
	 *         on - string - date the file was checked out
	 * Returns:
	 * Example:
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.ShowCheckoutInfo = function (fileNameOrIndex, by, on) {
		var cont;
		var filename = "";
		if (typeof fileNameOrIndex === "string") {
			filename = fileNameOrIndex;
		} else {
			filename = this.FileName(fileNameOrIndex, true);
		}

		var htm = "";
		var filenameenc = btoaEx(filename);
		if (opts.ListType == "I") {
			cont = "div";
			htm += getFileIcon( opts.ListType, filename, "checkout");
			htm += "<span class='caption'>" + filename + "</span>";

			htm += '<div class="editinfo" style="display:none" filename="' + filename + '"><span class="editinfotext">File checked out by <b>' + by + '</b> on ' + on + '.';

			if (docInfo.UserNameAB == by && docInfo.isDocBeingEdited == "true") {

				var cancelFunc = "CancelCheckout('" + filename + "')";
				var checkinFunc = "CheckInCurrentFile('" + filename + "')";
				htm += '<br id="einfobr"> [ <a href="javascript:' + cancelFunc + '"> Cancel Checkout</a>&nbsp;|&nbsp; <a href="javascript:' + checkinFunc + '">Check In</a> ]';
			}
			htm += '</span><div>';

			row = $("#tbodyUploader" + opts.divId).find(cont + "[filename='" + filenameenc + "']");

			row.html(htm);

		} else {
			cont = "tr";
			row = $("#tbodyUploader" + opts.divId).find(cont + "[filename='" + filenameenc + "']");

			var td = row.children('td:first');
			td.html(getFileIcon(opts.ListType, filename, "checkout") + "<span class='lcaption'>" + filename + "</span>");

			htm = '<tr class="editinfo" filename="' + filename + '"><td colspan=4><span class="editinfotext">File checked out by <b>' + by + '</b> on ' + on + '. ';
			if (docInfo.UserNameAB == by && docInfo.isDocBeingEdited == "true") {
				var cancelFunc = "CancelCheckout('" + filename + "')";
				var checkinFunc = "CheckInCurrentFile('" + filename + "')";
				htm += ' <br id="einfobr" style="display:none"> [ <a href="javascript:' + cancelFunc + '"> Cancel Checkout</a>&nbsp;|&nbsp; <a href="javascript:' + checkinFunc + '">Check In</a> ]';
			}
			htm += '</span></td></tr>';

			$(htm).insertAfter(row);
		}

	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: LoadAttachments
	 * Called from uploader init to show the list of files currently attached to this document.
	 * List of file, dates and sizes is supplied to uploader as a parameter
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.LoadAttachments = function () {
		if (opts.AttachmentNames == "") {
			return;
		}
		var anames = opts.AttachmentNames.split(opts.AttachmentSeparator);
		var adates = opts.AttachmentDates.split(", ");
		var asizes = opts.AttachmentLengths.split(", ");
		var attachHtml = "";

		for (var j = 0; j < anames.length; j++) {
			//dont show thumbnails
			if (anames[j].indexOf("~dthmb") == -1) {
				var sz = asizes[j];
				sz = fileSize(sz);

				var fname = btoaEx(anames[j]);
				var ext = this.GetFileExtension(anames[j]);
				if (opts.ListType == "I") {
					var iconclass = " class" +  this.GetFileExtension(anames[j]);
					attachHtml += "<div title = '" + anames[j] + "' class='" + iconclass + " item' filename='" + fname + "' filedate='" + adates[j] + "' filesize='" + asizes[j] + "' filetype='" + ext + "' >";
					attachHtml += getFileIcon(opts.ListType, anames[j]);
					attachHtml += "<span class='caption'>" + anames[j] + "</span>";
					attachHtml += "</div>";

				} else {
					attachHtml += "<tr filename='" + fname + "' filedate='" + adates[j] + "' filesize='" + asizes[j] + "' filetype='" + ext + "'><td width='40%'>" + getFileIcon(opts.ListType, anames[j]) + "<span class='lcaption'>" +  anames[j] + "</span></td><td width='20%'>" + adates[j] + "</td><td width='20%'>" + sz + "</td><td>" + ext + "</td></tr>";
				}
			}
		}
		$("#tbodyUploader" + opts.divId).html(attachHtml);
		//sort the files by default
		this.sortFileList("filename", "asc");
	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: CheckInFile
	 * Check in a file
	 * Inputs: fileName - string - name of file
	 * Returns:
	 * Example: DLIUploader1.CheckInFile("somefile.txt")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.CheckInFile = function (filename) {

		var objCoFiles = this.xmlFileLog;
		//--- check if any of the selected files is already out, if so, complain and terminate
		var fileNode = objCoFiles.selectSingleNode('cofiles/file[fnamelc="' + filename.toLowerCase() + '"]');
		if (fileNode == null) {
			return false;
		}
		var fileNodetxt = fileNode.selectSingleNode("path");
		var filePath = fileNodetxt.text || fileNodetxt.textContent;

		//first tell DOE to check-in the file in its interface..when successful send the request to the server
		this.SendCIAORequestToDOE({
			action: "checkinFile",
			fullpath: filePath,
			onSuccess: function () {
				container.SendCheckinRequest(filename)
			}
		})

	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: SendCheckinRequest
	 * Sends a request to DocumentServices to process the checkin request on the server
	 * Inputs: fileName - string - name of file to checkin
	 * 		   checkinAction - string - if "CANCEL" then the performs a CancelCheckout else does a CheckIn
	 * Returns:
	 * Example: DLIUploader1.CheckInFile("somefile.txt") for check-in
	 * 	or 		DLIUploader1.CheckInFile("somefile.txt", "CANCEL") for cancel-checkout
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.SendCheckinRequest = function (fileName, checkinAction) {

		forceSave = false;

		if (docInfo.isDocBeingEdited) {
			//update fields and set "forcesave" flag
			forceSave = true;
			//  var result = ManageChangedFiles(fileName);
		}

		var altAction = "";
		if (!forceSave) {
			altAction = "MODIFYOFILES"
		}

		//--- send the request to update backend document ---
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
			var request = ""

			var fileNode = this.GetFileCheckoutNode(fileName);

		if (!fileNode){
			return;
		}

		request += "<Request>"
		request += "<Action>CHECKIN</Action>"
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
		request += "<Unid>" + docInfo.DocID + "</Unid>"
		var tmpxml; //= fileNode.xml || fileNode.outerHTML;

		if (!tmpxml) {
			tmpxml = "<file>";
			tmpxml += "<filename><![CDATA[" + fileName + "]]></filename>";
			tmpxml += "<fnamelc><![CDATA[" + fileName + "]]></fnamelc>";
			tmpxml += "</file>";
		}

		request += "<filelist>" + tmpxml + "</filelist>"
		request += (checkinAction) ? "<ActionType>" + checkinAction + "</ActionType>" : "";
		request += "<altAction>" + altAction + "</altAction>"
		request += "</Request>"

		Docova.Utils.showProgressMessage("Updating checked out file list on the server. Please wait...");
		//var httpObj = new objHTTP();
		//var retval = httpObj.PostData(request, url);
		var tmpaction = checkinAction;
		$.ajax({
			'type': "POST",
			'url': url,
			'processData': false,
			'data': encodeURI(request)

		})
		.done(function (data) {
			if (tmpaction) {
				//if action is CANCEL then tell DOE that the checkout has been cancelled
				container.SendCIAORequestToDOE({
					action: "cancelCheckout",
					fullpath: fileName,
					onSuccess: function () {
						container.RefreshCheckoutXml();
					}
				});
			} else {
				//refresh after a check-in
				container.RefreshCheckoutXml();
			}

			Docova.Utils.hideProgressMessage();

			return true;

		})
	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: RemoveFileFromDisplay
	 * Updates the UI to remove a file from the display
	 * Inputs: fileName - string - name of file that should be removed
	 *
	 * Returns:
	 * Example:
	 *
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.RemoveFileFromDisplay = function (filename) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var encfilename = btoaEx(filename);
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[filename='" + encfilename + "']");
		if (selected.length > 0) {
			selected.remove();
		}
	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: DisplayAddedFile
	 * Updates the UI to show that a new file has been added
	 * Inputs: fileName - string - name of file that has been added
	 * 		   fullName - string - local path of the file.  Note..for non-plugin this will be blank.
	 * 		   filedate - string - date/time of file
	 *         filesize - string - size of file
	 *         filetype - string - type of file
	 *         displayas - string (optional) - specify "new" to display added file as a new file
	 * Returns:
	 * Example:
	 *
	 *------------------------------------------------------------------------------------------------------------------------------------------- */

	this.DisplayAddedFile = function (filename, fullname, filedate, filesize, filetype, displayas) {
		var t = $('#tbodyUploader' + opts.divId);

		var newrowHtml

		var filenameattr = btoaEx(filename);
		var fullnameattr = btoaEx(fullname);

		filetype = this.GetFileExtension(filename);

		var sz = fileSize(filesize);
		var dt = new Date(filedate);

		var mode = typeof displayas != "undefined" ? displayas : "new";

		if (opts.ListType == "I") {
			var iconclass = " class" +  this.GetFileExtension(filename);
			newrowHtml = "<div class='" + iconclass + " item '" +  (mode == "new" ? "isnew='yes'" : "") + " filename='" + filenameattr + "' fullname='" + fullnameattr + "' filedate='" + filedate + "' filesize='" + filesize + "' filetype= '" + filetype + "' >";
			newrowHtml += getFileIcon(opts.ListType, filename, mode);
			newrowHtml += "<span class='caption'>" + filename + "</span>";
			newrowHtml += "</div>";
		} else {
			newrowHtml = "<tr " + (mode == "new" ? "isnew='yes'" : "") + " filename='" + filenameattr + "' fullname='" + fullnameattr + "' filedate='" + filedate + "' filesize='" + filesize + "' filetype= '" + filetype + "' >";
			newrowHtml += "<td width='40%'>" + getFileIcon(opts.ListType, filename, mode) + filename + "</td>";
			newrowHtml += "<td width='20%'>" + filedate + "</td>";
			newrowHtml += "<td width='20%'>" + sz + "</td>";
			newrowHtml += "<td>" + filetype + "</td>";
		}

		var rowNode = t.append(newrowHtml);
	};


	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: UploadFolder
	 * Adds files (filtered by pattern) from a folder to Uploader
	 * Inputs: folderPath - string - path to the folder containing the files
	 * 		   pattern - string - file pattern to use to filter files eg. *.* or *.pdf
	 * 		   cb - function (optional) - callback function on completion
	 * Returns:
	 * Example:
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.UploadFolder = function (folderPath, pattern, cb) {
		var surl = getPluginURL();
		surl += "action=uploadFolder";
		surl += "&folderPath=" + encodeURIComponent(folderPath);
		surl += "&filePattern=" + encodeURIComponent(pattern);

		Docova.Utils.showProgressMessage("Adding a new file.  Please wait...");
		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			Docova.Utils.hideProgressMessage();
			if (data.status != "FAILED") {

				var myarr = data.files;
				for (var p = 0; p < myarr.length; p++) {
					var fl = myarr[p];
					if (container.onBeforeAdd.call(container, fl.filename) == false) {
						return;
					}

					var replacefile = false;
					//check if file exists..if it does then get a new name
					var tmpfilename = fl.filename;
					while (container.IsFileAttached(tmpfilename)) {
						//check if file is marked for deletion if so replace it with this one
						if (container.IsFileDeleted(tmpfilename)) {
							//look for the file in the deleted file index and clear it
							for (var r = 0; r < container.deletedFiles.length; r++) {
								var tmpfl = container.deletedFiles[r];
								if (typeof tmpfl == "string") {
									tmpfl = tmpfl.split("|")[0];
								}
								var seekname = tmpfl.name ? tmpfl.name : tmpfl;
								var row = container._getFileRow(tmpfilename);
								var flname = atobEx($(row).attr("filename"));
								var fullname = "";
								if ($(row).attr("fullname"))
									fullname = atobEx($(row).attr("fullname"));
								var toremove = fullname && fullname != "" ? fullname : flname;
								if (seekname == toremove) {
									replacefile = true;
									container.deletedFiles.splice(r, 1);
									$(row).remove();
									break;
								}
							}
						} else {
							var tmpfl = tmpfilename.substring(0, tmpfilename.lastIndexOf("."));
							var ext = tmpfilename.substring(tmpfilename.lastIndexOf("."));
							tmpfl += " - Copy";
							tmpfilename = tmpfl + ext;
						}
					}
					if (replacefile) {
						container.editedFiles.push(fl.fullname + "|" + tmpfilename);
					} else {
						container.addedFiles.push(fl.fullname + "|" + tmpfilename);
					}

					container.DisplayAddedFile(tmpfilename, fl.fullname, Docova.Utils.convertDateFormat(fl.filedate, "", true), fl.filesize, fl.filetype);
					container.onAdd.call(container, fl.fullname);
				}
				if (cb && typeof cb == "function") {
					cb(true);
				}
			} else {
				if (cb && typeof cb == "function") {
					cb(false);
				}
			}
		})
		.fail(function () {
			Docova.Utils.hideProgressMessage();
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error! Unable to communicate with Docova plugin.",
				title: "Error Uploading Folder"
			});
			if (cb && typeof cb == "function") {
				cb(false);
			}
		});
	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: AddFileNoPlugin
	 * Adds a new file to uploader when the desktop plugin is not running.  The 'file' object is added to the addFiles Array
	 * Inputs: data - object returned by the file upload control
	 * Returns:
	 * Example:
	 *
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.AddFileNoPlugin = function (data) {

		if (this.AllowAdd != "1"){
			return;
		}
		if (this.onBeforeAdd.call(this, data.files[0].name) == false){
			return;
		}

		if (this.IsFileAttached(data.files[0].name)) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Unable to attach '" + data.files[0].name + "'.  File already exists!",
				title: "Error Adding File"
			});
			return false;
		}

		if (!this.isValidFileExtension(data.files[0].name)) {
			window.top.Docova.Utils.messageBox({
				prompt: "Only files with " + this.AllowedFileExtensions + " extensions are allowed.",
				icontype: 3,
				title: "Adding Files",
				width: 400
			});
			return false;
		}

		var dt = data.files[0].lastModifiedDate ? data.files[0].lastModifiedDate : data.files[0].lastModified;
		var d = new Date(dt);
		
		this.addedFiles.push(data.files[0]);
		this.DisplayAddedFile(data.files[0].name, "", Docova.Utils.convertDateFormat(d, "", true), data.files[0].size, data.files[0].type);
		this.onAdd.call(this, data.files[0].name, data.files[0]);
	};

	this.AddFilePlugin = function () {
		var surl = getPluginURL();
		surl += "action=addFile";

		Docova.Utils.showProgressMessage("Adding a new file.  Please wait...");
		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			Docova.Utils.hideProgressMessage();
			if (data.status != "FAILED") {

				var myarr = data.files;
				for (var p = 0; p < myarr.length; p++) {
					var fl = myarr[p];
					if (container.onBeforeAdd.call(container, fl.filename) == false){
						return;
					}

					//check if file exits..if it does then get a new name
					var tmpfilename = fl.filename;
					while (container.IsFileAttached(tmpfilename)) {
						var tmpfl = tmpfilename.substring(0, tmpfilename.lastIndexOf("."));
						var ext = tmpfilename.substring(tmpfilename.lastIndexOf("."));
						tmpfl += " - Copy";
						tmpfilename = tmpfl + ext;
					}

					if (!container.isValidFileExtension(tmpfilename)) {
						window.top.Docova.Utils.messageBox({
							prompt: "Only files with " + container.AllowedFileExtensions + " extensions are allowed.",
							icontype: 3,
							title: "Adding Files",
							width: 400
						});
						continue;
					}

					container.addedFiles.push(fl.fullname + "|" + tmpfilename);

					container.DisplayAddedFile(tmpfilename, fl.fullname, Docova.Utils.convertDateFormat(fl.filedate, "", true), fl.filesize, fl.filetype);
					container.onAdd.call(container, fl.fullname);

				}
			}
		})
		.fail(function () {
			Docova.Utils.hideProgressMessage();
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error! Unable to communicate with Docova plugin.",
				title: "Error Adding File"
			});
		});
	};

	this.RenameSelectedFiles = function () {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[sel=yes]");

		for (p = 0; p < selected.length; p++) {
			var row = selected[p];

			var flname = atobEx($(row).attr("filename"));
			var fullname = "";
			if ($(row).attr("fullname")) {
				fullname = atobEx($(row).attr("fullname"));
			}

			var newname = prompt("Please enter a new filename", flname);
			if (newname == null) {
				return;
			}

			var edt = "";
			if ($(row).attr("isnew") == "yes") {
				edt = "new";
				if ( this.pluginEnabled ) { $(row).attr("filename", btoaEx(newname)); }
			} else {
				edt = "edit"
			}
			if (opts.ListType == "I") {
				var attachHtml = getFileIcon(opts.ListType, newname, edt);
				attachHtml += "<span class='caption'>" + newname + "</span>";
				$(row).html(attachHtml);
			} else {
				var td = $(row).children('td:first');
				td.html(getFileIcon(opts.ListType, newname, edt) + " " + newname);

			}
			$(row).attr("newname", btoaEx(newname));
			


			if ($(row).attr("isnew") != "yes" || !(this.pluginEnabled )) {

				this.renamedFiles[flname] = newname;

				
			}
			if ( fullname && fullname != ""){ //this will happen only when plugin is enabled
				this.RenameFile(fullname, newname);
			}
			Docova.Utils.hideProgressMessage();
		}

	};

	this.GetRenamedFileName = function (index) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var row;
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				row = $(this);
				return false;
			}

			indx++;
		});
		if (row) {
			if (row.attr("newname"))
				return atobEx(row.attr("newname"));
		}
		return "";
	}

	this.RenameFile = function (index, newfilename) {
		var flname = "";
		if (typeof index == "string") {
			flname = index;
		} else {
			flname = this.FileName(index);
		}

		var found = false;
		if (flname && flname != "") {
			flname = flname.replace(/\\\\/g, '\\')
				for (var p = 0; p < this.addedFiles.length; p++) {
					if (typeof this.addedFiles[p] == "string") {
						var tmparr = this.addedFiles[p].split("|");
						var fl = tmparr[0].replace(/\\\\/g, '\\')
							if (fl == flname) {
								this.addedFiles[p] = flname + "|" + newfilename;
								found = true;
								break;
							}
					}
				}

				//handle edited files

				for (var p = 0; p < this.editedFiles.length; p++) {
					if (typeof this.editedFiles[p] == "string") {
						var tmparr = this.editedFiles[p].split("|");
						var fl = tmparr[0].replace(/\\\\/g, '\\')
							if (fl == flname) {
								this.editedFiles[p] = flname + "|" + newfilename;
								found = true;
								break;
							}
					}
				}

		}
		return found;
	};

	this.UploadFile = function (filepath, filename, filedate, filesize, filetype) {
		if (this.AllowAdd != "1") {
			return;
		}
		var tmpfilename;
		if (filename && filename != "") {
			tmpfilename = filename;
		} else {
			tmpfilename = GetFileName(filepath);
		}

		var replacefile = false;
		while (container.IsFileAttached(tmpfilename)) {
			//check if file is marked for deletion if so replace it with this one
			if (container.IsFileDeleted(tmpfilename)) {
				//look for the file in the deleted file index and clear it
				for (var r = 0; r < container.deletedFiles.length; r++) {
					var tmpfl = container.deletedFiles[r];
					if (typeof tmpfl == "string") {
						tmpfl = tmpfl.split("|")[0];
					}
					var seekname = tmpfl.name ? tmpfl.name : tmpfl;

					var row = container._getFileRow(tmpfilename);
					var flname = atobEx($(row).attr("filename"));
					var fullname = "";
					if ($(row).attr("fullname"))
						fullname = atobEx($(row).attr("fullname"));

					var toremove = fullname && fullname != "" ? fullname : flname;
					if (seekname == toremove) {
						replacefile = true;
						container.deletedFiles.splice(r, 1);
						$(row).remove();
						break;
					}
				}
			} else {
				var tmpfl = tmpfilename.substring(0, tmpfilename.lastIndexOf("."));
				var ext = tmpfilename.substring(tmpfilename.lastIndexOf("."))
					tmpfl += " - Copy";
				tmpfilename = tmpfl + ext;
			}
		}

		if (replacefile) {
			container.editedFiles.push(filepath + "|" + tmpfilename);
		} else {
			container.addedFiles.push(filepath + "|" + tmpfilename);
		}
		filename = tmpfilename;

		//this.addedFiles.push(filepath + "|" + filename);

		if (filedate == undefined || filedate == null) {
			var filedate = Docova.Utils.convertDateFormat(new Date(), "", true);
			if (docInfo && docInfo.TodayDate) {
				var filedate = docInfo.TodayDate;
			}
		}
		if (filesize == undefined || filesize == null) {
			var filesize = 0;
		}
		this.DisplayAddedFile(filename, filepath, Docova.Utils.convertDateFormat(filedate, "", true), filesize, filetype);
		return true;

	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: ScanFile
	 * Triggers action for scanning a file.
	 * Inputs:
	 * Returns:
	 * Example:
	 *      DLIUploader1.ScanFile();
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.ScanFile = function () {
		if (this.AllowScan != "1") {
			return;
		}
		container.onScan.call(container)
	}; //--end ScanFile

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: AddFile
	 * Displays the select file dialog box to allow a user to add new files.
	 * Inputs:
	 * Returns:
	 * Example:
	 *      DLIUploader1.AddFile();
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.AddFile = function () {
		if (this.ParentUploader) {
			this.ParentUploader.UploaderCallingAdd = this;
		} else {
			this.UploaderCallingAdd = null;
		}

		if (this.AllowAdd != "1") {
			return;
		}

		//removed this from here as this check will be done in onBeforeAdd function 
		/*if (this.getAvailableFileCount() == 0) {
			window.top.Docova.Utils.messageBox({
				prompt: "This document type can contain only " + this.MaxFiles + " attachments(s).",
				title: "Error Adding Files",
				icontype: 3,
				width: 400
			});
			return;
		}*/

		if (this.pluginEnabled) {
			this.AddFilePlugin();
		} else {
			$("#" + opts.FileCtrlId).click();
		}
	}; //--end AddFile

	this.HandleFileChanges = function () {
		var ctrlno = 1;
		var uploader = window["DLIUploader" + ctrlno];
		var sAddedList = "";
		var sAddedListUP = "";
		var sEditedList = "";
		var sEditedListUP = "";
		var sDeletedList = "";
		var sRenamedList = "";

		while (uploader) {

			//handle newly added files
			for (var p = 0; p < uploader.addedFiles.length; p++) {
				if (sAddedList != "")
					sAddedList += "*";

				if (sAddedListUP != "")
					sAddedListUP += "*";

				if (uploader.addedFiles[p].split) {
					var tmpArr = uploader.addedFiles[p].split("|")

						sAddedList += tmpArr[0];
					sAddedListUP += uploader.addedFiles[p];
				}
			}

			//handle edited files

			for (var p = 0; p < uploader.editedFiles.length; p++) {

				if (sEditedList != "")
					sEditedList += "*";

				if (sEditedListUP != "")
					sEditedListUP += "*";
				var tmpArr = uploader.editedFiles[p].split("|")
					sEditedList += tmpArr[0];
				sEditedListUP += uploader.editedFiles[p];
			}

			//handle deleted files
			for (var p = 0; p < uploader.deletedFiles.length; p++) {

				var inp = $('<input>').attr({
						type: 'hidden',
						filename: uploader.deletedFiles[p],
						id: 'delFile',
						name: '%%Detach'
					}).appendTo('#divAttachmentSection');
				inp.val(uploader.deletedFiles[p]);
				if (sDeletedList != "")
					sDeletedList += "*"
					sDeletedList += uploader.deletedFiles[p];

			}

			//handle renamed files


			for (var p = 0; p < uploader.renamedFiles.length; p ++){

			}

			for (var property in uploader.renamedFiles) {
    			if (!uploader.renamedFiles.hasOwnProperty(property)) continue;
    			if ( sRenamedList === ""){
    				sRenamedList = "O=" + property + ",N=" + uploader.renamedFiles[property];
    			}else{
    				sRenamedList += ";O=" + property + ",N=" + uploader.renamedFiles[property];
    			}
			}



			ctrlno++;
			uploader = window["DLIUploader" + ctrlno];
		}

		var upAdd = $("#tmpAddedFilesUP");
		if (!upAdd[0])
			upAdd = $('<input>').attr({
					type: 'hidden',
					id: 'tmpAddedFilesUP',
					name: 'tmpAddedFilesUP'
				}).appendTo("form:first");

		upAdd.val(sAddedListUP);

		var upEdit = $("#tmpEditedFilesUP");
		if (!upEdit[0])
			upEdit = $('<input>').attr({
					type: 'hidden',
					id: 'tmpEditedFilesUP',
					name: 'tmpEditedFilesUP'
				}).appendTo("form:first");

		upEdit.val(sEditedListUP);
		$("#tmpRenamedFiles").val(sRenamedList);
		$("#tmpAddedFiles").val(sAddedList);
		$("#tmpEditedFiles").val(sEditedList);
		$("#tmpDeletedFiles").val(sDeletedList);
	};

	this.SubmitWithPlugin = function (options) {

		var defaultOptns = {
			Navigate: true,
			GetResults: false,
			async: true,
			onOk: function () {},
			onOtherwise: function () {}
		};

		//**************Begin init of uploader **************//
		var opts = $.extend({}, defaultOptns, options);

		var surl = getPluginURL();
		surl += "action=submit";
		if (_DOCOVAEdition == "SE") {
			surl += "&fileurl=" + encodeURIComponent(location.href);
		} else {
			var actionUrl = docInfo.ServerUrl + "/" + $("form:first").attr("action");
			surl += "&fileurl=" + encodeURIComponent(actionUrl);
		}

		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to submit with plugin.",
				title: "Error Saving"
			});
			return 0;
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&sessioncookie=" + cookieObj.cookieval;
		if (container.LocalDelete)
			surl += "&localdelete=" + container.LocalDelete;
		if (container.LocalDeleteExcludes)
			surl += "&localdeleteexcludes=" + container.LocalDeleteExcludes;
		var efc = container.EnableFileCleanup == 1 ? "true" : "false";
		var efcr = container.CleanupToRecycleBin ? "true" : "false";
		surl += "&lfc=" + efc;
		surl += "&lfcr=" + efcr;

		this.HandleFileChanges();

		var retcode = 0;
		var formData = $("form:first").serializeArray();
		for (var i = 0; i < formData.length; i++) {
			var key = formData[i].name.toLowerCase();
			//in commonfunctions.js
			if (isMultiValue(key, document)) {
				formData[i].value = processMultiValue(document, key, formData[i].value);
			}

		}
		//in commonfunctions
		formData.push({
			name: "__dtmfields",
			value: getAllDateTimeFields(document)
		});
		formData.push({
			name: "__numfields",
			value: getAllNumericFields(document)
		});
		formData.push({
			name: "tmpgenthumbs",
			value: container.GenerateThumbnails
		});
		formData.push({
			name: "tmpthumbwidth",
			value: container.ThumbnailWidth
		});
		formData.push({
			name: "tmpthumbheight",
			value: container.ThumbnailHeight
		});
		formData.push({
			name: "allcookiesstr",
			value: encodeURIComponent(document.cookie)
		});
    
    formData.push({
			name: "__datatablenames",
			value: getAllDataTablesNames(document)
		});
		
		$('form:first input:checkbox:not(:checked)').each(function(){
			var exists = false;
			for (var i = 0; i < formData.length; i++) {
				if (formData[i].name.toLowerCase() == $(this).prop('name').toLowerCase()) {
					exists = true;
					break;
				}
			}
			
			if (exists === false) {
				formData.push({
					name: $(this).prop('name'),
					value: ''
				});
			}
		});

		var fileCtrl = $("#" + container.FileCtrlId);
		var fileCtrlId = fileCtrl.attr("name");
		formData.push({
			name: "FileCtrlId",
			value: fileCtrlId
		});

		$("#dlgFolderImportError", window.top.document).remove();
		var progressDiv = "<div id='dlgFolderImportError' title='Import Folder'>"
			progressDiv += "<span id='dlgFolderImportErrorText'>Please Wait..Importing Folder</span>"
			progressDiv += "</div>"
			$("body", window.top.document).append(progressDiv);
		$("#dlgFolderImportError", window.top.document).dialog({
			autoOpen: true,
			width: 500,
			height: 200,
			position: {
				my: "top",
				at: "top+100",
				of: window.top
			},
			resizable: false,
			modal: true,
			buttons: {
				"Close": function () {
					$("#dlgFolderImportError", window.top.document).dialog("close");
					$("#dlgFolderImportError", window.top.document).remove();
					opts.onDone.call();
				}
			}
		});
		$("#dlgFolderImportError", window.top.document).parent().hide()

		$.ajax({
			url: surl,
			type: "POST",
			data: formData,
			async: opts.async,
			dataType: "json"
		}).done(function (data) {
			$("#dlgFolderImportError", window.top.document).dialog("close");
			if (data.status == "SUCCESS") {

				var org = atob(data.data)
				if (opts.Navigate) {
					if (data.redirect == "true"){
						document.location.href = org;
					}else{
						document.write(org);
					}
				}
				if (opts.GetResults){
					container.SubmitResultPage = org;
				}
				opts.onOk();
				retcode = 1;
			} else {
				if (data.status == "CANCELLED"){
					retcode = 2;
				}
				opts.onOtherwise();
			}
		})
		.fail(function () {
			$("#dlgFolderImportError", window.top.document).dialog("close");
			opts.onOtherwise();
		});
		return retcode;

	};

	this.Submit = function (options) {
		//if plugin enabled, use that to submit the file
		var defaultOptns = {
			Navigate: true,
			GetResults: false,
			async: true,
			onOk: function () {},
			onOtherwise: function () {}
		};
		var opts = $.extend({}, defaultOptns, options);

		if (this.pluginEnabled) {
			return this.SubmitWithPlugin(opts);
		}

		this.HandleFileChanges();

		var upMode = $("#tmpMode");
		if (upMode[0]){
			upMode.val("nodoe");
		}
		
		var formDatav = $("form:first").serializeArray();
		for (var i = 0; i < formDatav.length; i++) {
			var key = formDatav[i].name.toLowerCase();
			//in commonfunctions.js
			if (isMultiValue(key, document)) {
				formDatav[i].value = processMultiValue(document, key, formDatav[i].value);
			}
		}
		formDatav.push({
			name: "__dtmfields",
			value: getAllDateTimeFields(document)
		});
		formDatav.push({
			name: "__numfields",
			value: getAllNumericFields(document)
		});
		
    formDatav.push({
			name: "__datatablenames",
			value: getAllDataTablesNames(document)
		});
    
		$('form:first input:checkbox:not(:checked)').each(function(){
			var exists = false;
			for (var i = 0; i < formDatav.length; i++) {
				if (formDatav[i].name.toLowerCase() == $(this).prop('name').toLowerCase()) {
					exists = true;
					break;
				}
			}
			
			if (exists === false) {
				formDatav.push({
					name: $(this).prop('name'),
					value: ''
				});
			}
		});

		var alladdedfiles = [];
		alladdedfiles = alladdedfiles.concat(this.addedFiles);
		if (this.ChildUploaders && this.ChildUploaders.length > 0) {
			for (var cu = 0; cu < this.ChildUploaders.length; cu++) {
				if (this.ChildUploaders[cu].addedFiles.length > 0) {
					alladdedfiles = alladdedfiles.concat(this.ChildUploaders[cu].addedFiles);
				}
			}
		}

		if (opts.async == false) {
			var jqXHR;
			if (alladdedfiles.length == 0) {
				alladdedfiles = " ";
				$(".dlgProgressDetails").hide();
			}

			$("#" + container.FileCtrlId).fileupload('option', 'async', false)

			jqXHR = $("#" + container.FileCtrlId).fileupload('send', {
					files: alladdedfiles,
					formData: formDatav
				});
			$("#dialog-checkoutinfo").dialog("close");
			var result = jqXHR;
			if (result.responseText && result.responseText.indexOf("error;")!== 0) {
				if (opts.Navigate) {
					if (result.responseText.indexOf("url;") == 0) {
						var tmpurlarray = result.responseText.split(";")
							if (tmpurlarray.length == 2) {
								Docova.Utils.replaceURL(tmpurlarray[1], document);
								opts.onOk();
								return;
							}
					}
					document.write(result.responseText);
				}
				if (opts.GetResults){
					container.SubmitResultPage = result.responseText;
				}
				opts.onOk();
			} else{
				opts.onOtherwise();
			}
		} else {
			var fileurl = docInfo.ServerUrl + "/" + docInfo.PortalNsfName + "/dlgSubmitProgress?readForm";

			var dlgSubmitProgress = window.top.Docova.Utils.createDialog({
					url: fileurl,
					id: "dlgProgress",
					useiframe: false,
					autoopen: true,
					title: "Saving Document",
					width: "450",
					height: "200",
					buttons: {
						"Cancel": function () {
							jqXHR.abort();
							dlgSubmitProgress.closeDialog();
						}
					},
					onOpen: function () {
						$(this).parent().find(".ui-dialog-titlebar-close").hide();
						$("#progressbar", window.top.document).progressbar();

					}

				});
			if (alladdedfiles.length == 0) {
				alladdedfiles = " ";
				$(".dlgProgressDetails").hide();
			}

			jqXHR = $("#" + container.FileCtrlId).fileupload('send', {
					files: alladdedfiles,
					formData: formDatav
				})
				.complete(function (result, textStatus, jqXHR) {
					setTimeout(function () {
						dlgSubmitProgress.closeDialog();

						if (result.responseText && result.responseText.indexOf("error;")!== 0) {
							if (opts.Navigate) {
								if (result.responseText.indexOf("url;") == 0) {
									var tmpurlarray = result.responseText.split(";")
										if (tmpurlarray.length == 2) {
											Docova.Utils.replaceURL(tmpurlarray[1], document);
											opts.onOk();
											return;
										}
								}
								document.write(result.responseText);
							}
							if (opts.GetResults){
								container.SubmitResultPage = result.responseText;
							}
							opts.onOk();
						} else{
							opts.onOtherwise();
						}
					}, 500);

				})
				.error(function (jqXHR, textStatus, errorThrown) {
					if (errorThrown === 'abort') {
						window.top.Docova.Utils.messageBox({
							icontype: 1,
							msgboxtype: 0,
							prompt: "Form save has been cancelled.",
							title: "Form Save Cancelled"
						});
					}
					opts.onOtherwise();
				});
		}

	};

	this.SelectAll = function () {
		var cont;
		if (opts.ListType != "I"){
			cont = "tr";
		} else {
			cont = "div";
		}

		var selected = $("#tbodyUploader" + opts.divId + " > " + cont);

		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			if (!$(row).hasClass("editinfo")) {
				$(row).attr("sel", "yes");
			}

		}
	};

	this.RefreshAfterSubmit = function () {
		var cont;
		if (opts.ListType != "I"){
			cont = "tr";
		} else {
			cont = "div";
		}

		var selected = $("#tbodyUploader" + opts.divId + " > " + cont);

		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			if (!$(row).hasClass("editinfo")) {
				if ($(row).attr("isnew") == "yes" || $(row).attr("isedited") == "yes") {
					var filename = atobEx($(row).attr("filename"));
					if (opts.ListType == "I") {
						var attachHtml = getFileIcon(opts.ListType, filename);
						attachHtml += "<span class='caption'>" + filename + "</span>";
						$(row).html(attachHtml);
					} else {
						var td = $(row).children('td:first');
						td.html(getFileIcon(opts.ListType, filename) + "<span class='lcaption'>" + filename + "</span>");
					}
					$(row).attr("isedited", "no");
					$(row).attr("isnew", "no");
				} else if ($(row).attr("isdeleted") == "yes") {
					$(row).remove();
				}
			}

		}

	};

	this.UndeleteSelectedFiles = function () {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[sel=yes]");

		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			var td = $(row).children('td:first');
			var flname = atobEx($(row).attr("filename"));
			var encfullname = $(row).attr("fullname");
			if (encfullname)
				var fullname = atobEx(encfullname);
			if (opts.ListType == "I") {
				var attachHtml = getFileIcon(opts.ListType, flname);
				attachHtml += "<span class='caption'>" + flname + "</span>";
				$(row).html(attachHtml);
				$(row).attr("isdeleted", "no");
			} else {
				td.html(getFileIcon(opts.ListType, flname) + "<span class='lcaption'>" + flname + "</span>");
				$(row).attr("isdeleted", "no");
			}
			for (var p = 0; p < this.deletedFiles.length; p++) {
				var fl = this.deletedFiles[p];
				var seekname = fl.name ? fl.name : fl;
				var toremove = fullname && fullname != "" ? fullname : flname;
				if (seekname == toremove) {
					findex = p;
					break;
				}
			}
			if (findex != -1) {

				this.deletedFiles.splice(findex, 1);
			}
		}
	};

	this.RemoveSelectedFiles = function () {
		this.DeleteSelectedFiles();
	};

	this.RemoveFile = function (index, displayonly) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var row;
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				row = $(this);
				return false;
			}

			indx++;
		});
		if (row)
			this.DeleteFile(row, displayonly);
	};

	this.DeleteFile = function (row, displayonly) {
		var isnew = $(row).attr("isnew");
		var flname = atobEx($(row).attr("filename"));
		var fullname = "";
		if ($(row).attr("fullname"))
			fullname = atobEx($(row).attr("fullname"));

		if (typeof displayonly === "undefined" || displayonly !== true) {
			if (container.onBeforeDelete.call(container, flname) == false) {
				return;
			}
		}
		var findex = -1;
		if (isnew == "yes") {

			for (var p = 0; p < this.addedFiles.length; p++) {
				var fl = this.addedFiles[p];
				if (typeof fl == "string") {
					fl = fl.split("|")[0];
				}
				var seekname = fl.name ? fl.name : fl;
				var toremove = fullname && fullname != "" ? fullname : flname;
				if (seekname == toremove) {
					findex = p;
					break;
				}
			}
			if (findex != -1) {

				this.addedFiles.splice(findex, 1);
			}
			$(row).remove();

		} else {
			if (opts.ListType == "I") {
				var attachHtml = getFileIcon(opts.ListType, flname, "delete");
				attachHtml += "<span class='caption'>" + flname + "</span>";
				$(row).html(attachHtml);
				$(row).attr("isdeleted", "yes");
			} else {
				var td = $(row).children('td:first');
				td.html(getFileIcon(opts.ListType, flname, "delete") + "<span class='lcaption'>" + flname + "</caption>");
				$(row).attr("isdeleted", "yes");

			}
			this.PushUnique(this.deletedFiles, flname);
		}
		if (typeof displayonly === "undefined" || displayonly !== true) {
			container.onDelete.call(container, flname)
		}
	};

	this.DeleteSelectedFiles = function () {

		if (this.AllowDelete != "1") {
			return;
		}
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[sel=yes]");
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			this.DeleteFile(row);
		}
		return false;
	};

	this.PushUnique = function (arr, value) {
		if (arr.indexOf(value) == -1)
			arr.push(value);
	};

	this.SwitchView = function (type) {
		if (opts.ListType == type) {
			return;
		}
		var html = "";
		var contdiv;
		var container = this;
		if (opts.ListType != "I") {
			var tbl = $("<div id='tbodyUploader" + opts.divId + "'></div>")

				$('#tbodyUploader' + opts.divId + '  > tr').each(function () {
					if (!$(this).hasClass("editinfo")) {
						var flname = $(this).attr("filename");

						var flname = $(this).attr("filename");
						var fullname = $(this).attr("fullname");
						var flsize = $(this).attr("filesize");
						var fldate = $(this).attr("filedate");
						var fltype = $(this).attr("filetype");
						var isnew = $(this).attr("isnew");
						var newname = $(this).attr("newname");
						var sel = $(this).attr("sel");
						var isdeleted = $(this).attr("isdeleted");
						var isedited = $(this).attr("isedited")
						var iconclass = " class" +  container.GetFileExtension(atobEx((newname && newname !== "" ? newname : flname)));
						contdiv = $("<div title = '" +atobEx((newname && newname !== "" ? newname : flname)) + "' class='" + iconclass + " item'></div>");
						contdiv.attr("filename", flname);

						if (isnew){
							contdiv.attr("isnew", isnew);
						}
						if (sel){
							contdiv.attr("sel", sel);
						}
						if (isdeleted){
							contdiv.attr("isdeleted", isdeleted);
						}
						if (flsize){
							contdiv.attr("filesize", flsize);
						}
						if (fldate){
							contdiv.attr("filedate", fldate);
						}
						if (fltype){
							contdiv.attr("filetype", fltype);
						}
						if (fullname){
							contdiv.attr("fullname", fullname);
						}
						if (isedited){
							contdiv.attr("isedited", isedited);
						}
						if (newname){
							contdiv.attr("newname", newname);
						}
						
						var icn = $(this).find("td:first").children(":first");
						
						icn.removeClass("smallitem");
						
						if ( icn.get(0).tagName.toUpperCase() == "I")
							icn.removeClass(iconclass);
						else{
							icn.children(":first").removeClass(iconclass);
							icn.find(".itop-left").removeClass("itop-left").addClass("top-left");
						}

						contdiv.append(icn)
						var captn = $("<span class='caption'>" + atobEx((newname && newname !== "" ? newname : flname)) + "</span>");
						contdiv.append(captn);
					} else {
						var edtinfo = $("<div class='editinfo' style='display:none'></div>");
						edtinfo.attr("filename", $(this).attr("filename"));
						var innrhtml = $(this).find("td:first").first().html();
						edtinfo.html(innrhtml);
						edtinfo.find(".editinfotext").css("padding-left", "0px");
						edtinfo.find("#einfobr").show();
						contdiv.append(edtinfo);
					}
					tbl.append(contdiv);
				});
		} else {
			tbl = $("<table id='tblUploader" + opts.divId + "' class='upTable'><thead class='attachHeader dWidgetHeader'><tr><td width='40%' style='padding:8px'><span class='upheader' name='hdrFileName'>Name <i class='far fa-chevron-up'></i></span></td><td style='width:20%'><span name='hdrFileDate' class='upheader'>Date <i class='far fa-chevron-up'></i></span></td><td width='20%'><span class='upheader' name='hdrFileSize'>Size <i class='far fa-chevron-up'></i></span></td><td><span name='hdrFileType' class='upheader'>Type <i class='far fa-chevron-up'></i></span></td></tr></thead><tbody id='tbodyUploader" + opts.divId + "'></tbody></table>");
			$('#tbodyUploader' + opts.divId + ' > div').each(function () {
				var flname = $(this).attr("filename");
				var flsize = $(this).attr("filesize");
				var fldate = $(this).attr("filedate");
				var fullname = $(this).attr("fullname");
				var fltype = $(this).attr("filetype");
				var isnew = $(this).attr("isnew");
				var newname = $(this).attr("newname");
				var isedited = $(this).attr("isedited");
				var sel = $(this).attr("sel");
				var isdeleted = $(this).attr("isdeleted");
				var newtr = $("<tr></tr>");
				newtr.attr("filename", flname);

				if (isnew){
					newtr.attr("isnew", isnew);
				}
				if (sel){
					newtr.attr("sel", sel);
				}
				if (isdeleted){
					newtr.attr("isdeleted", isdeleted);
				}
				if (isedited){
					newtr.attr("isedited", isedited);
				}
				if (flsize){
					newtr.attr("filesize", flsize);
				}
				if (fldate){
					newtr.attr("filedate", fldate);
				}
				if (fltype){
					newtr.attr("filetype", fltype);
				}
				if (fullname){
					newtr.attr("fullname", fullname);
				}
				if (newname){
					newtr.attr("newname", newname)
				}
				
				//icon
				var td = $("<td width='40%'></td>");
				var icn = $(this).children(":first");

				var iconclass = "class" +  container.GetFileExtension(atobEx((newname && newname !== "" ? newname : flname)));

				icn.addClass("smallitem");
				if ( icn.get(0).tagName.toUpperCase() == "I")
					icn.addClass(iconclass);
				else{
					icn.children(":first").addClass(iconclass);
					
					icn.find(".top-left").removeClass("top-left").addClass("itop-left");
				}

				//$(" " + flname).insertAfter(icn);
				td.append(icn);
				td.append("<span class='lcaption'>" + atobEx((newname && newname !== "" ? newname : flname)) + "</span>");
				newtr.append(td);

				//date
				td = $("<td width='20%'></td>");
				td.append(fldate);
				newtr.append(td);

				var sz = fileSize(flsize);

				//size
				td = $("<td width='20%'></td>");
				td.append(sz);
				newtr.append(td);

				//type
				td = $("<td></td>");
				td.append(fltype);
				newtr.append(td);

				tbl.find("#tbodyUploader" + opts.divId).append(newtr);

				var edtinfo = $(this).find(".editinfo");
				if (edtinfo[0]) {
					var edttr = $("<tr class='editinfo' filename='" + edtinfo.attr("filename") + "'></tr>");
					var edttd = $("<td colspan='4'></td>");
					edttd.html(edtinfo.html());
					edttd.find(".editinfotext").removeAttr("style");
					edttd.find("#einfobr").hide();
					edttr.append(edttd);
					tbl.find("#tbodyUploader" + opts.divId).append(edttr);

				}
			});
		}
		updiv.empty();
		updiv.append(tbl);
		opts.ListType = type;

		if (opts.ListType == "I")
			$("#tbodyUploader" + opts.divId).css({
				'min-height': this.Height + "px"
			});
		else
			$("#tbodyUploader" + opts.divId).css({
				'min-height': this.Height + "px",
				'height': this.Height + "px"
			});

		this.attachEvents();

		return;

	};

	this.IsFileAttached = function (inname) {
		var cont;
		if (opts.ListType != "I"){
			cont = "tr";
		} else {
			cont = "div";
		}
		var isthere = false;
		var selected = $("#tbodyUploader" + opts.divId + " > " + cont);

		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			if (!$(row).hasClass("editinfo")) {
				var flname = atobEx($(row).attr("filename"));
				if (flname.toLowerCase() == inname.toLowerCase()) {
					isthere = true;
					break;
				}
			}

		}
		return isthere;

	};

	this.FileName = function (index, format) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var bformat = false;
		if (format)
			bformat = format;

		index--;

		var selected = $("#tbodyUploader" + opts.divId + " > " + cont);

		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			if (!$(row).hasClass("editinfo")) {
				if (cnt == index) {
					var flname = atobEx($(row).attr("filename"));
					if ($(row).attr("fullname"))
						var fullname = atobEx($(row).attr("fullname"));

					if (!bformat && fullname)
						return fullname;
					else
						return flname;
				}
			}
			if (!$(row).hasClass("editinfo")) {
				cnt++;
			}
		}
		return "";
	};

	this.ServerFileName = function (index) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var indx = 1;
		var row = null;
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				row = $(this);
				return "";
			}

			indx++;
		});

		if (row != null) {
			if (row.attr("isnew") == "yes") {
				return "";
			}
			var flname = atobEx($(row).attr("filename"));
			return flname;
		}

		return "";
	};

	this.FileCount = function () {
		var cont;
		if (opts.ListType != "I"){
			cont = "tr";
		} else {
			cont = "div";
		}

		var selected = $("#tbodyUploader" + opts.divId + " > " + cont);
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			if (!$(row).hasClass("editinfo")) {
				cnt++;
			}
		}
		return cnt;
	};

	this.GetAllFileNames = function (sep) {
		var sepr = sep || ",";
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		//var table = $("#tbodyUploader tr[sel=yes]");
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "");
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			if (!$(row).hasClass("editinfo") && $(row).attr("isdeleted") != "yes") {
				var flname = atobEx($(row).attr("filename"));
				retArray[cnt] = flname;
				cnt++;
			}
		}

		if (retArray.length)
			return retArray.join(sepr);
		else
			return "";
	};

	this.GetNewFileNames = function (sep, fullpath) {
		var getattr = fullpath ? "fullname" : "filename";
		var sepr = sep || ",";
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[isnew=yes]");
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			var flname = $(row).attr(getattr);
			retArray[cnt] = atobEx(flname);
			cnt++;
		}

		if (retArray.length)
			return retArray.join(sepr);
		else
			return "";
	};

	this.GetEditedFileNames = function (sep) {
		var sepr = sep || ",";
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[isedited=yes]");
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			var flname = atobEx($(row).attr("filename"));
			retArray[cnt] = flname;
			cnt++;
		}

		if (retArray.length)
			return retArray.join(sepr);
		else
			return "";
	};

	this.GetDeletedFileNames = function (sep) {
		var sepr = sep || ",";
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		//var table = $("#tbodyUploader tr[sel=yes]");
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[isdeleted=yes]");
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			var flname = atobEx($(row).attr("filename"));
			retArray[cnt] = flname;
			cnt++;
		}

		if (retArray.length)
			return retArray.join(sepr);
		else
			return "";
	};

	this.GetSelectedFileObj = function () {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		//var table = $("#tbodyUploader tr[sel=yes]");
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[sel=yes]");
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];

			retArray[cnt] = row;
			cnt++;
		}

		return retArray;

	};

	this.GetSelectedFileNames = function (sep) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		//var table = $("#tbodyUploader tr[sel=yes]");
		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[sel=yes]");
		var retArray = new Array();
		var cnt = 0;
		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			var flname = atobEx($(row).attr("filename"));
			retArray[cnt] = flname;
			cnt++;
		}

		if (retArray.length)
			return retArray.join(sep);
		else
			return "";
	};

	this.GetFileIndex = function (filename) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var found = false;
		var fullname = "";
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			var flname = atobEx($(this).attr("filename"));
			if ($(this).attr("fullname"))
				fullname = atobEx($(this).attr("fullname"));

			if (fullname == filename || flname == filename) {
				found = true;
				return false;
			}
			indx++;
		});
		if (found) {
			return indx;
		} else {
			return 0;
		}
	};

	this.LocalFileName = function (index) {

		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var flname = "";
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {

				var fName = $(this).attr("filename");
				var fullName = $(this).attr("fullname");
				if (fName != fullName && fullName != undefined) {
					flname = atobEx($(this).attr("fullname"))
				} else {
					flname = "";
				}

				return false;
			}

			indx++;
		});
		return flname;

	};

	this.GetFileLength = function (index) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var flsize = "";
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				flsize = $(this).attr("filesize");
				return false;
			}

			indx++;
		});
		return flsize;
	};

	this.GetFileType = function (index) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var fltype = "";
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				fltype = $(this).attr("filetype");
				return false;
			}

			indx++;
		});
		return fltype;
	};

	this.GetFileDate = function (index) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var fldate = "";
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				fldate = $(this).attr("filedate");
				return false;
			}

			indx++;
		});
		return fldate;
	};

	this.FileLocation = function (index) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var indx = 1;
		var row = null;
		$('#tbodyUploader' + opts.divId + '  > ' + cont).each(function () {
			if (indx == index) {
				row = $(this);
				return false;
			}

			indx++;
		});

		if (row != null) {
			if (row.attr("isnew") == "yes" || row.attr("isedited") == "yes") {
				return "local";
			} else {
				return "server";
			}
		}

		return "";
	};

	this.LocalFileExists = function (filepath) {
		var surl = getPluginURL();
		surl += "action=localFileExists";
		surl += "&filepath=" + encodeURIComponent(filepath);
		var retval = false;
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS")
				retval = true;
		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error! Unable to communicate with Docova plugin.",
				title: "Error Checking File Existence"
			});
		});

		return retval;
	};

	this.DownloadFileFromURL = function (url, localfilename) {

		var surl = getPluginURL();
		surl += "action=downloadFile";

		var fileurl = url;
		surl += "&fileurl=" + encodeURIComponent(fileurl);
		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to download file.",
				title: "Error Downloading File"
			});
			return "";
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&sessioncookie=" + cookieObj.cookieval;
		surl += "&localfilename=" + localfilename;
		var downpath = "";
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				downpath = atobEx(data.filename);
			}

		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to download file.",
				title: "Error Downloading File"
			});
		});

		return downpath

	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: DownloadFile
	 * Downloads a specified file.  Download path will depend upon the parameters provided.
	 * Inputs: options - integer - string - or json value pair object
	 *                 if integer, represents index of file to download. Download to default path.
	 *                 if string, represents file name of file to download. Download to default path.
	 *                 if object, value data pair consisting of;
	 *                      filename: string (required) name of file to download
	 *                      localfilename: string (optional) target location of file
	 *                      async: boolean (optional) whether to download syncronously or asynchronously. Cannot be false if no localfilename specified.
	 *                      onSuccess: function (optional) callback function on download success
	 *                      onFailure: function (optional) callback function on download failure
	 *                      onCancel: function (optional) callback function on download cancel
	 * Returns:
	 * Example:
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.DownloadFile = function (options) {
		var result = false;

		var defaultOptns = {
			filename: "",
			localfilename: "",
			async: true,
			onSuccess: function () {},
			onFailure: function () {},
			onCancel: function () {}
		};

		if (typeof options === "number") {
			var tmpFileName = this.FileName(options, true);
			this.ResolvePathNames();
			var tmpDownloadTo = this.DownloadPath;
			if (tmpDownloadTo.slice(-1) != "\\") {
				tmpDownloadTo += "\\";
			}
			tmpDownloadTo += tmpFileName;
			var opts = $.extend({}, defaultOptns, {
					"filename": tmpFileName,
					"localfilename": tmpDownloadTo,
					"async": false
				});
		} else if (typeof options === "string") {
			var tmpFileName = options;
			this.ResolvePathNames();
			var tmpDownloadTo = this.DownloadPath;
			if (tmpDownloadTo.slice(-1) != "\\") {
				tmpDownloadTo += "\\";
			}
			tmpDownloadTo += tmpFileName;
			var opts = $.extend({}, defaultOptns, {
					"filename": tmpFileName,
					"localfilename": tmpDownloadTo,
					"async": false
				});
		} else if (typeof options === "object") {
			var opts = $.extend({}, defaultOptns, options);
			if (opts.localfilename == "") {
				opts.async = true;
			}
		}

		var surl = getPluginURL();
		surl += "action=downloadFile";
		var flname = opts.filename;
		var fileurl;
		//check if file is archived
		if (docInfo.DocStatusNo == "6") {
			fileurl = docInfo.DataDocUrl + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
		} else {
			if (_DOCOVAEdition == "SE") {
				fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(flname) + "?OpenElement&doc_id=" + docInfo.DocID;
			} else {
				fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
			}
		}

		surl += "&fileurl=" + encodeURIComponent(fileurl);
		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to download file.",
				title: "Error Downloading File"
			});
			return false;
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&sessioncookie=" + cookieObj.cookieval;
		surl += "&filename=" + flname;
		if (opts.localfilename != "") {
			surl += "&localfilename=" + opts.localfilename;
		}
		var downpath = "";
		$.ajax({
			url: surl,
			type: "POST",
			async: opts.async,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				downpath = atobEx(data.filename);
				result = true;
				//-- update the full download path of the file
				var row = container._getFileRow(opts.filename);
				$(row).attr("fullname", btoaEx(downpath));
				//-- now call user defined function if available
				opts.onSuccess.call(this, downpath);
			} else {
				opts.onCancel();
			}
		})
		.fail(function (xhr, status, error) {
			opts.onFailure.call(this, error);
		});

		if (!opts.async) {
			return result;
		}
	}; //--end DownloadFile


	this.DownloadSelectedFiles = function () {

		if (this.AllowDownload != "1") {
			return;
		}
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var selected = $("#tbodyUploader" + opts.divId + " " + cont + "[sel=yes]");

		var downloadUrl = "";
		if (docInfo.DocStatusNo == "6") {
			//this is an archived document
			var docId = window.location.search.match(new RegExp('(?:[\?\&]datadoc=)([^&]+)'));
			docId = docId ? docId[1] : docInfo.DocID;
			downloadUrl = docInfo.ServerUrl + docInfo.ArchiveNsfName + "/file.xsp?" + docId + "/";

		} else {
			if (_DOCOVAEdition == "SE") {
				downloadUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/";
			} else {
				downloadUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/file.xsp?" + docInfo.DocID + "/";
			}
		}

		for (p = 0; p < selected.length; p++) {
			var row = selected[p];
			var flname = atobEx($(row).attr("filename"));
			downloadUrl += encodeURIComponent(flname);
			if (_DOCOVAEdition == "SE") {
				downloadUrl += '?OpenElement&download=true&doc_id=' + docInfo.DocID;
			}

			if (container.onBeforeDownload.call(container, flname) == false)
				return;
			if (this.pluginEnabled) {
				var surl = getPluginURL();
				surl += "action=downloadFile";
				var fileurl;
				if (docInfo.DocStatusNo == "6") {
					fileurl = docInfo.DataDocUrl + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
				} else {
					if (_DOCOVAEdition == "SE") {
						fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(flname) + "?OpenElement&doc_id=" + docInfo.DocID;
					} else {
						fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
					}
				}

				surl += "&fileurl=" + encodeURIComponent(fileurl);
				var cookieObj = window.top.Docova.Utils.getSessionCookie();
				if (!cookieObj) {
					window.top.Docova.Utils.messageBox({
						icontype: 1,
						msgboxtype: 0,
						prompt: "Error: Unable to read current session cookie value. Unable to download files.",
						title: "Error Downloading Files"
					});
					return false;
				}
				surl += "&sessioncookiename=" + cookieObj.cookiename;
				surl += "&sessioncookie=" + cookieObj.cookieval;
				surl += "&filename=" + encodeURIComponent(flname);
				surl += "&promptoverwrite=yes";
				$.ajax({
					url: surl,
					type: "POST",
					dataType: "json"
				}).done(function (data) {
					if (data.status == "SUCCESS") {
						downpath = atobEx(data.filename);
						window.top.Docova.Utils.messageBox({
							icontype: 4,
							msgboxtype: 0,
							prompt: "File successfully downloaded to \r\n" + downpath,
							title: "File Downloaded"
						});
					}

				})
				.fail(function (xhr, status, error) {
					window.top.Docova.Utils.messageBox({
						icontype: 1,
						msgboxtype: 0,
						prompt: "Error! Unable to communicate with Docova plugin.",
						title: "Error Downloading File"
					});
				});

			} else
				$.fileDownload(downloadUrl);

			if (container.onDownload.call(container, flname) == false)
				return;
		}

	};

	this.GetFileExtension = function (flname) {
		var fileExtension = (flname.indexOf(".") != -1) ? flname.split(".") : "";
		fileExtension = (fileExtension[fileExtension.length - 1]);
		return fileExtension
	};

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: ViewSelectedFiles
	 * Launches selected file for viewing
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.ViewSelectedFiles = function () {
		if (this.AllowLaunch != "1") {
			return;
		}

		var selectedRow = this.GetSelectedFileObj("*");

		var flname = atobEx($(selectedRow).attr("filename"));

		var isnew = ($(selectedRow).attr("isnew") == "yes");

		var fileurl = "";
		var localpath = "";
		//get the file extension
		var fileExtension = (flname.indexOf(".") != -1) ? flname.split(".") : "";
		fileExtension = (fileExtension[fileExtension.length - 1])
		var launchLocal = false;
		if (opts.LaunchLocally != "") {
			var extArray = opts.LaunchLocally.split(",");
			for (var p = 0; p < extArray.length; p++) {
				if (fileExtension.toUpperCase() == extArray[p].toUpperCase()) {
					launchLocal = true;
					break;
				}
			}
		}

		if (!isnew) {
			if (docInfo.DocStatusNo == "6") {
				//this is an archived document
				fileurl = docInfo.DataDocUrl + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
			} else {
				if (_DOCOVAEdition == "SE") {
					fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(flname) + "?OpenElement&doc_id=" + docInfo.DocID;
				} else {
					fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
				}
			}

		} else {
			localpath = atobEx($(selectedRow).attr('fullname'));

		}
		if (container.onBeforeLaunch.call(container, flname) == false) {
			return;
		}

		if (!this.pluginEnabled || (!launchLocal && !isnew)) {
			window.open(fileurl);

		} else {
			var surl = getPluginURL();
			surl += "action=editFile";
			surl += "&fileurl=" + encodeURIComponent(fileurl);
			var cookieObj = window.top.Docova.Utils.getSessionCookie();
			if (!cookieObj) {
				window.top.Docova.Utils.messageBox({
					icontype: 1,
					msgboxtype: 0,
					prompt: "Error: Unable to read current session cookie value. Unable to view file.",
					title: "Error Viewing File"
				});
				return false;
			}
			surl += "&sessioncookiename=" + cookieObj.cookiename;
			surl += "&sessioncookie=" + cookieObj.cookieval;
			surl += "&localpath=" + encodeURIComponent(localpath)
			surl += "&filename=" + encodeURIComponent(flname);
			surl += "&editpath=" + this.EditFilePath;
			surl += "&readmode=1";
			$.ajax({
				url: surl,
				type: "POST",
				dataType: "json"
			}).done(function (data) {
				if (data.status != "FAILED") {
					container.onLaunch.call(container, data.filename);
				}

			})
			.fail(function (xhr, status, error) {
				window.top.Docova.Utils.messageBox({
					icontype: 1,
					msgboxtype: 0,
					prompt: "Error: Unable to read current session cookie value. Unable to view file.",
					title: "Error Viewing File"
				});
			});

		}
	}; //--end ViewSelectedFiles

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: Launch
	 * Launch the file at the specified index, or the file with the specified name
	 * Inputs: fileNameOrIndex - integer - index position of the file (first item in the index is 1)
	 *                         - String - name of the file
	 *         readonly - boolean (optional) - true to launch file in read only mode. Defaults to false
	 * Returns: boolean - true if file launched, false otherwise
	 * Example: DLIUploader1.Launch(1) or DLIUploader1.Launch("Somefile.doc")
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.Launch = function (fileNameOrIndex, readonly) {
		var result = false;

		if (this.AllowLaunch != "1") {
			return result;
		}

		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}

		var flname = "";
		if (typeof fileNameOrIndex === "string") {
			flname = fileNameOrIndex;
		} else {
			flname = this.FileName(fileNameOrIndex, true);
		}

		var selectedRow = null;
		selectedRow = $("#tbodyUploader" + opts.divId).find(cont + "[filename='" + btoaEx(flname) + "']");
		if (!selectedRow || selectedRow.length == 0) {
			return result;
		}
		var isnew = ($(selectedRow).attr("isnew") == "yes");

		var fileurl = "";
		var localpath = "";
		//get the file extension
		var fileExtension = (flname.indexOf(".") != -1) ? flname.split(".") : "";
		fileExtension = (fileExtension[fileExtension.length - 1])
		var launchLocal = false;
		if (opts.LaunchLocally != "") {
			var extArray = opts.LaunchLocally.split(",");
			for (var p = 0; p < extArray.length; p++) {
				if (fileExtension.toUpperCase() == extArray[p].toUpperCase()) {
					launchLocal = true;
					break;
				}
			}
		}

		if (!isnew) {
			if (docInfo.DocStatusNo == "6") {
				//this is an archived document
				fileurl = docInfo.DataDocUrl + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
			} else {
				if (_DOCOVAEdition == "SE") {
					fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(flname) + "?OpenElement&doc_id=" + docInfo.DocID;
				} else {
					fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
				}
			}
		} else {
			localpath = atobEx($(selectedRow).attr('fullname'));
		}

		if (!this.pluginEnabled || (!launchLocal && !isnew))
			window.open(fileurl);
		else {
			var surl = getPluginURL();
			surl += "action=editFile";
			surl += "&fileurl=" + encodeURIComponent(fileurl);
			var cookieObj = window.top.Docova.Utils.getSessionCookie();
			if (!cookieObj) {
				window.top.Docova.Utils.messageBox({
					icontype: 1,
					msgboxtype: 0,
					prompt: "Error: Unable to read current session cookie value. Unable to launch file.",
					title: "Error Launching File"
				});
				return false;
			}
			surl += "&sessioncookiename=" + cookieObj.cookiename;
			surl += "&sessioncookie=" + cookieObj.cookieval;
			surl += "&localpath=" + encodeURIComponent(localpath)
			surl += "&filename=" + encodeURIComponent(flname);
			surl += "&editpath=" + this.EditFilePath;
			if (readonly === true) {
				surl += "&readmode=1"
			}
			$.ajax({
				url: surl,
				type: "POST",
				async: false,
				dataType: "json"
			}).done(function (data) {
				if (data.status == "SUCCESS") {
					result = true;
				}
			})
			.fail(function (xhr, status, error) {});
		}
		if (container.onLaunch.call(container, flname) == false) {
			result = false;
		}
		return result;
	}; //--end Launch

	this.ResolvePathNames = function () {

		if (!this.pluginEnabled) {
			return;
		}
		var surl = getPluginURL();
		surl += "action=resolvePathNames";
		surl += "&downloadpath=" + this.DownloadPath;
		surl += "&editpath=" + this.EditFilePath;

		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				if (data.downloadpath)
					container.DownloadPath = atobEx(data.downloadpath);
				if (data.editpath)
					container.EditFilePath = atobEx(data.editpath);
			}

		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to resolve path names.",
				title: "Error Resolving Path Names"
			});
		});

	};

	this.PasteFromClipboard = function () {
		if (!this.pluginEnabled){
			return;
		}
		var surl = getPluginURL();
		surl += "action=pasteClipboard";
		surl += "&mode=" + docInfo.Mode;
		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				if (data.filepath) {
					var addedfilepath = atobEx(data.filepath);
					var addedfiledate = data.filedate;
					var addedfilesize = data.filesize;
					container.UploadFile(addedfilepath, "", addedfiledate, addedfilesize, "bmp");
				}
			} else {

				if (data.reason && data.reason == "clipempty") {
					window.top.Docova.Utils.messageBox({
						icontype: 4,
						msgboxtype: 0,
						prompt: "Unable to paste content.  Either clipboard is empty or has an unsupported format!",
						title: "Error Pasting Content"
					});
				}

			}

		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error! Unable to communicate with Docova plugin.",
				title: "Error Pasting Content"
			});
		});

	};

	this.EditFile = function (fileNameOrIndex) {
		var cont;
		if (opts.ListType != "I") {
			cont = "tr";
		} else {
			cont = "div";
		}
		var flname = "";
		if (typeof fileNameOrIndex === "string") {
			flname = fileNameOrIndex;
		} else {
			flname = this.FileName(fileNameOrIndex, true);
		}
		var selectedRow = null;
		selectedRow = $("#tbodyUploader" + opts.divId).find(cont + "[filename='" + btoaEx(flname) + "']");
		if (!selectedRow || selectedRow.length == 0) {
			return false;
		}
		var surl = getPluginURL();
		surl += "action=editFile";
		var flname = atobEx($(selectedRow).attr("filename"));
		var isnew = ($(selectedRow).attr("isnew") == "yes" || $(selectedRow).attr("isedited") == "yes");
		if (container.onBeforeFileEdit.call(container, flname) == false){
			return;
		}
		var newfilename = "";
		if ($(selectedRow).attr("newname")) {
			newfilename = atobEx($(selectedRow).attr("newname"));
		}
		var fileurl = "";
		var localpath = "";
		if (!isnew) {
			if (_DOCOVAEdition == "SE") {
				fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(flname) + "?OpenElement&doc_id=" + docInfo.DocID;
			} else {
				fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
			}
		} else {
			localpath = atobEx($(selectedRow).attr('fullname'));
		}
		surl += "&localpath=" + encodeURIComponent(localpath)
		surl += "&fileurl=" + encodeURIComponent(fileurl);
		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to edit file.",
				title: "Error Editing File"
			});
			return false;
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&sessioncookie=" + cookieObj.cookieval;
		surl += "&filename=" + encodeURIComponent(flname);
		surl += "&editpath=" + this.EditFilePath;
		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			if (data.status != "FAILED") {
				var org = atobEx(data.fullname)
					var delindex = -1;
				if (!isnew)
					container.PushUnique(container.editedFiles, org)
					container.ShowEditedFileInfo((newfilename !== "" ? newfilename : data.filename), data.fullname)
					container.onFileEdit.call(container, data.filename)
			}
		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error! Unable to communicate with Docova plugin.",
				title: "Error Editing File"
			});
		});
	};

	this.EditSelectedFiles = function () {
		var surl = getPluginURL();
		surl += "action=editFile";

		var selectedRow = this.GetSelectedFileObj("*");

		var flname = atobEx($(selectedRow).attr("filename"));
		var newfilename = "";
		if ($(selectedRow).attr("newname")) {
			newfilename = atobEx($(selectedRow).attr("newname"));
		}

		var isnew = ($(selectedRow).attr("isnew") == "yes" || $(selectedRow).attr("isedited") == "yes");

		if (container.onBeforeFileEdit.call(container, flname) == false){
			return;
		}

		var fileurl = "";
		var localpath = "";

		if (!isnew) {
			if (docInfo.DocStatusNo == "6") {
				//this is an archived document
				fileurl = docInfo.DataDocUrl + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
			} else {
				if (_DOCOVAEdition == "SE") {
					fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(flname) + "?OpenElement&doc_id=" + docInfo.DocID;
				} else {
					fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "/$file/" + encodeURIComponent(flname) + "?OpenElement";
				}
			}
		} else {
			localpath = atobEx($(selectedRow).attr('fullname'));
		}

		surl += "&localpath=" + encodeURIComponent(localpath)
		surl += "&fileurl=" + encodeURIComponent(fileurl);
		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to submit with plugin.",
				title: "Error Editing File"
			});
			return false;
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&sessioncookie=" + cookieObj.cookieval;
		surl += "&filename=" + encodeURIComponent(flname);
		surl += "&editpath=" + this.EditFilePath;

		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			if (data.status != "FAILED") {
				var org = atobEx(data.fullname)
					var delindex = -1;

				if (!isnew)
					container.PushUnique(container.editedFiles, org)

					container.ShowEditedFileInfo((newfilename !== "" ? newfilename : data.filename), data.fullname)
					container.onFileEdit.call(container, data.filename)
			}

		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to edit file.",
				title: "Error Editing File"
			});
		});
	};

	this.SendCIAORequestToDOE = function (options) {
		var defaultOptns = {
			action: "",
			fullpath: "",
			onSuccess: function () {},
			onOtherwise: function () {}
		};

		//**************Begin init of uploader **************//
		var opts = $.extend({}, defaultOptns, options);

		var surl = getPluginURL();
		surl += "action=" + opts.action;
		surl += "&instanceID=" + docInfo.SystemKey;
		surl += "&libraryID=" + docInfo.LibraryKey;
		surl += "&docUNID=" + docInfo.DocID;
		surl += "&host=" + encodeURIComponent(docInfo.ServerName);
		surl += "&localFileName=" + encodeURIComponent(opts.fullpath);
		surl += "&othercookies=" + encodeURIComponent(document.cookie);
		surl += "&db=" + encodeURIComponent("/" + docInfo.NsfName);
		if(_DOCOVAEdition == "SE"){
			var fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + docInfo.DocID + '?editdocument';
		}else{
			var fileurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID ;		
		}	
		surl += "&fileurl=" + encodeURIComponent(fileurl);
		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error: Unable to read current session cookie value. Unable to process check in/out.",
				title: "Error Check In/Out"
			});
			return false;
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&sessioncookie=" + cookieObj.cookieval;
		var fileCtrl = $("#" + container.FileCtrlId);
		var fileCtrlId = fileCtrl.attr("name");

		surl += "&filectrlid=" + encodeURIComponent(fileCtrlId);

		var done = false;

		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				opts.onSuccess();
			} else
				opts.onOtherwise();

		})
		.fail(function (xhr, status, error) {
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				prompt: "Error! Unable to communicate with Docova plugin.",
				title: "Error Checking In/Out"
			});
		});
		return done;
	};

	this.sortFileList = function (sortattr, sortorder) {
		var asc = (sortorder === 'asc');
		var tbody = jQuery("#tbodyUploader" + opts.divId);
		tbody.find('tr').sort(function (a, b) {
			var result = 0;
			if (sortattr == "filesize") {
				var tmpa = Number(jQuery(a).attr("filesize"));
				var tmpb = Number(jQuery(b).attr("filesize"));
				result = (tmpa > tmpb ? 1 : (tmpa < tmpb ? -1 : 0));
			}
			if (sortattr == "filedate") {
				var dt1 = Docova.Utils.convertStringToDate(jQuery(a).attr("filedate"));
				var dt2 = Docova.Utils.convertStringToDate(jQuery(b).attr("filedate"));
				result = (dt1 > dt2 ? 1 : (dt1 < dt2 ? -1 : 0));
			}
			if (sortattr == "filetype") {
				result = jQuery(a).attr("filetype").localeCompare(jQuery(b).attr('filetype'));
			}
			if (result === 0 || sortattr == "filename") {
				result = atobEx(jQuery(a).attr("filename")).localeCompare(atobEx(jQuery(b).attr('filename')));
			}
			if (!asc) {
				result = (0 - result);
			}
			return result;
		}).appendTo(tbody);

	};

	this.isValidFileExtension = function (fileName) {
		var extensionList = this.AllowedFileExtensions;

		if (extensionList == "") {
			return true;
		}

		var fileExtension = (fileName.indexOf(".") != -1) ? fileName.split(".") : "";
		fileExtension = (fileExtension[fileExtension.length - 1]).toLowerCase();

		var extensionArray = extensionList.toLowerCase().split(",");
		for (var i = 0; i < extensionArray.length; i++) {
			if (fileExtension == extensionArray[i]) {
				return true;
			}
		}
		return false;
	};

	this.getAvailableFileCount = function () {
		if (parseInt(this.MaxFiles) == 0) {
			return -1;
		} //no limit

		var deletedCount = (this.GetDeletedFileNames() == "") ? 0 : this.GetDeletedFileNames().split(",").length;
		var fileCount = this.FileCount();
		var remainingCount = parseInt(this.MaxFiles) + deletedCount - fileCount;
		remainingCount = (remainingCount < 0) ? 0 : remainingCount;
		return remainingCount;
	};

}

function getFileIcon(listtype, fname, overlay) {

	var extnlower = fname.split('.').pop();

	extn = extnlower.toUpperCase();
	var icn = "fa-file-o";
	var style = "color : black";
	switch (extn) {
	case "TXT":
		icn = "fas fa-file-alt ";
		break;
	case "DOCX":
	case "RTF":
	case "DOC":
	case "DOTX":
	case "DOCM":
		icn = "fas fa-file-word";
		style = "color: blue"
			break;
	case "XLS":
	case "XLSX":
	case "XLSM":		
		icn = "fas fa-file-excel"
			style = "color: #006600"
			break;
	case "JS":
	case "CSS":
	case "HTML":
		icn = "fas fa-file-code"
			style = "color: green"
			break;
	case "JPG":
	case "GIF":
	case "PNG":
	case "BMP":
	case "JPEG":
		style = "color: blue";
		icn = "fas fa-file-image";
		break;
	case "PPT":
	case "PPTX":
		icn = "fas fa-file-powerpoint";
		style = "color: #FF4000";
		break;
	case "WMV":
	case "FLV":
	case "SWF":
	case "MPEG":
	case "MPG":

		style = "color: #3399FF";
		icn = "fas fa-file-video";
		break;
	case "ZIP":
	case "TAR":
		style = "color: #CCBB33";
		icn = "fas fa-file-archive";
		break;
	case "PDF":
		icn = "fas fa-file-pdf";
		style = "color: #D80000"
			break;
	case "WAV":
	case "MP3":
	case "MIDI":
	case "WMA":
	case "FLAC":
	case "AAC":
	case "OGG":
		icn = "fas fa-file-sound";
		break;

	}

	var tmpclass = "";
	var topleft = "top-left";
	var divclass = "";
	if ( listtype != "I"){
		 tmpclass = "class" + extnlower;
		 topleft = "itop-left";
		 divclass = "smallitem";
	}
		
	style = "";

	if (overlay == "new")
		return '<div class="' + divclass + '"><i class=" fa-2x ' + icn +  " " + tmpclass + ' " ></i><i class="fas fa-2x fa-asterisk ' + topleft + '" ></i></div>';
	else if (overlay == "delete")
		return '<div class="' + divclass + '"><i class=" fa-2x ' + icn +  " " + tmpclass + ' " ></i><i class="fas fa-times fa-2x ' + topleft + ' "></i></div>';
	else if (overlay == "edit")
		return '<div class="' + divclass + '"><i class=" fa-2x ' + icn +  " " + tmpclass + ' " ></i><i class="fas fa-edit fa-2x  ' + topleft + ' "></i></div>';
	else if (overlay == "checkout")
		return '<div class="' + divclass + '"><i class=" fa-2x ' + icn +  " " + tmpclass + ' " ></i><i class="fas fa-lock fa-2x  ' + topleft + ' "></i></div>';
	else
		return '<i class=" ' + icn   +  " " + tmpclass + ' fa-2x"></i>'

}

function fileSize(bytes) {
	var exp = Math.log(bytes) / Math.log(1024) | 0;
	var result = (bytes / Math.pow(1024, exp)).toFixed(2);

	return result + ' ' + (exp == 0 ? 'bytes' : 'KMGTPEZY'[exp - 1] + 'B');
}
