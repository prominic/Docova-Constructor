var _DOCOVAEdition = "SE";  //-- set this variable to either SE or Domino depending upon where this code is installed

/*---------------------------------
 * Constants
 *-------------------------------- */
var AUTHORS = "AUTHORS";
var READERS = "READERS";
var RTELEM_TYPE_TABLE = "table";
var RTELEM_TYPE_TABLECELL = "tablecell";
var RTELEM_TYPE_TEXTPARAGRAPH = "textparagraph";
var RTELEM_TYPE_DOCLINK = "doclink";

var RULER_ONE_INCH = 75;
var RULER_ONE_CENTIMETER = 38;

var COLOR_BLACK = "black";
var COLOR_BLUE = "blue";
var COLOR_CYAN = "cyan";
var COLOR_DARK_BLUE = "darkblue";
var COLOR_DARK_CYAN = "darkcyan";
var COLOR_DARK_GREEN = "darkgreen";
var COLOR_DARK_MAGENTA = "darkmagenta";
var COLOR_DARK_RED = "darkred";
var COLOR_DARK_YELLOW = "darkyellow";
var COLOR_GRAY = "gray";
var COLOR_GREEN = "green";
var COLOR_LIGHT_GRAY = "lightgray";
var COLOR_MAGENTA = "magenta";
var COLOR_RED = "red";
var COLOR_WHITE = "white";
var COLOR_YELLOW = "yellow";

/*
 * Class: DocovaEvents
 * Object to provide event listener services
 *
 **/
function DocovaEvents() {

	this._triggers = [];
	if (window.top.Docova && window.top.Docova.GlobalStorage && window.top.Docova.GlobalStorage["_triggers"]) {
		this._triggers = window.top.Docova.GlobalStorage["_triggers"];
	}

	this.on = function (event, callback) {
		if (!this._triggers[event]) {
			this._triggers[event] = [];
		}
		this._triggers[event].push(callback);
		window.top.Docova.GlobalStorage["_triggers"] = this._triggers;
	}

	//call the last funciton hookped up to this event async and return a value
	this.triggerHandlerAsync = function (trigger, context) {
		var retarr = [];
		if (this._triggers[trigger]) {
			return this._triggers[trigger][this._triggers[trigger].length - 1].call(context);

		} else if (typeof window[trigger] === 'function') {
			return window[trigger].call(context);
		}
		return true;
	}

	this.removeTrigger = function (trigger) {
		this._triggers[trigger].splice(0, 1);
	}

	this.triggerHandler = function (trigger, context, remove) {
		if (this._triggers[trigger]) {
			var tmpArr = this._triggers[trigger];

			for (i in this._triggers[trigger]) {
				//return _triggers[trigger][i](context);
				setTimeout(this._triggers[trigger][i](context), 0);
				if (remove) {
					this._triggers[trigger].splice(0, 1);
				}
			}
		}
	}

} //--end DocovaEvents class


/*
 * Class: DocovaUtils
 * Utility classes for a variety of common DOCOVA functions
 * Typically accessed as Docova.Utils
 **/
function DocovaUtils() {

	/*----------------------------------------------------------------------------------------------
	 * Function: messageBox
	 * Displays a message box dialog
	 *
	 * Parameters:
	 * options - value pair object containing the following
	 *      icontype - integer - icon to display. defaults to 4
	 *          1 = stop sign
	 *          2 = question mark
	 *          3 = exclamation point
	 *          4 = information
	 *      msgboxtype - integer - buttons to display. defaults to 0
	 *          0 = OK
	 *          1 = OK and Cancel
	 *          2 = Abort, Retry, Ignore
	 *          3 = Yes, No, Cancel
	 *          4 = Yes, No
	 *          5 = Retry, Cancel
	 *      prompt - string - text to display in message box
	 *      title - string - title of the message box
	 *      width - integer - width of the message dialog. defaults to 400
	 *      onOk - function (optional) - function to run when Ok button pressed
	 *      onCancel - function (optional) - function to run when Cancel button pressed
	 *      onYes - function (optional) - function to run when Yes button pressed
	 *      onNo - function (optional) - function to run when No button pressed
	 *      onAbort - function (optional) - function to run when Abort button pressed
	 *      onRetry - function (optional) - function to run when Retry button pressed
	 *      onIgnore - function (optional) - function to run when Ignore button pressed
	 *  Example:
	 *      Docova.Utils.messageBox({icontype: 1, msgboxtype : 0, prompt: "Please do something", title: "A prompt box"})
	 *--------------------------------------------------------------------------------------------*/
	this.messageBox = function (options) {

		var defaultOptns = {
			'msgboxdivid' : "MsgBox_" + (Math.floor(Math.random() * 100)).toString(),
			'icontype': 4,
			'msgicon': 'ui-icon-close',
			'msgboxtype': 0,
			'btns': new Array(),
			'onOk': function () {},
			'onCancel': function () {},
			'onYes': function () {},
			'onNo': function () {},
			'onAbort': function () {},
			'onRetry': function () {},
			'onIgnore': function () {},
			'prompt': "Provide some text here",
			'title': "Title",
			'width': 400
		};

		var btns = {};
		var opts = $.extend({}, defaultOptns, options);

		//Set icon to use
		if (opts.icontype == 1) {
			opts.msgicon = "ui-icon-circle-close";
		}
		if (opts.icontype == 2) {
			opts.msgicon = "ui-icon-help";
		}
		if (opts.icontype == 3) {
			opts.msgicon = "ui-icon-alert";
		}
		if (opts.icontype == 4) {
			opts.msgicon = "ui-icon-info";
		}

		if (opts.msgboxtype == 0) {
			btns["OK"] = function () {
				$(this).dialog("close");
				opts.onOk();
			}
		}
		if (opts.msgboxtype == 1) {
			btns["OK"] = function () {
				$(this).dialog("close");
				opts.onOk();
			}
			btns["Cancel"] = function () {
				$(this).dialog("close");
				opts.onCancel();
			}
		}
		if (opts.msgboxtype == 2) {
			btns["Abort"] = function () {
				$(this).dialog("close");
				opts.onAbort();
			}
			btns["Retry"] = function () {
				$(this).dialog("close");
				opts.onRetry();
			}
			btns["Ignore"] = function () {
				$(this).dialog("close");
				opts.onIgnore();
			}
		}
		if (opts.msgboxtype == 3) {
			btns["Yes"] = function () {
				$(this).dialog("close");
				opts.onYes();
			}
			btns["No"] = function () {
				$(this).dialog("close");
				opts.onNo();
			}
			btns["Cancel"] = function () {
				$(this).dialog("close");
				opts.onCancel();
			}
		}
		if (opts.msgboxtype == 4) {
			btns["Yes"] = function () {
				$(this).dialog("close");
				opts.onYes();
			}
			btns["No"] = function () {
				$(this).dialog("close");
				opts.onNo();
			}
		}
		if (opts.msgboxtype == 5) {
			btns["Retry"] = function () {
				$(this).dialog("close");
				opts.onRetry();
			}
			btns["Cancel"] = function () {
				$(this).dialog("close");
				opts.onCancel();
			}
		}

		var msgboxContainer = document.createElement('div');
		msgboxContainer.id = opts.msgboxdivid;
		msgboxContainer.style.display = "none";
		document.body.appendChild(msgboxContainer);

		msgboxHTML = "<table style='display:block; margin-top:15px;'><tr><td style='vertical-align:top;'><div class='ui-icon " + opts.msgicon + "'</div></td><td>" + opts.prompt + "</td></tr></table>";
		msgboxContainer.innerHTML = msgboxHTML;

		var dlg = $("#"+ opts.msgboxdivid);
		dlg.dialog({
			autoopen: true,
			position: {
				my: "center top+50",
				at: "center top",
				of: window
			},
			title: opts.title,
			resizable: false,
			width: opts.width,
			modal: true,
			buttons: btns,
			close: function () {
				$(this).dialog('destroy');
				$(this).remove();
			}
		});
	} //--end messageBox

	/*-----------------------------------------------------------------------------------------
	 * Function: showAddressDialog
	 * Multi and Single name selection from address books
	 *
	 * Parameters:
	 *  options - value pair array consisting of;
	 *          fieldname: string (optional) - field to store selected names to
	 *          cb: function (optional) - callback function to call in place of returning value to field
	 *                     callback function will be called with the selected names as an array parameter
	 *          defaultvalues: string (optional) - list of default values to pre-select in dialog
	 *          dlgtype: string (optional) - single or multi name selection. defaults to single
	 *          title: string (optional) - title of dialog
	 *          separator: string (optional) - delimiter to use for concatenating names. defaults to comma
	 *          dlgdivname: string - id of div to contain dialog (defaults to dlgAddress)
	 *          returntypes: boolean (optional) - true to return types (eg. Group/Person/etc) along with names
	 *          restricttolibrarymembers: string (optional) - library id to restrict membership lookup to
	 *
	 *  Returns:
	 *      Updates specified field in current document with selected names
	 *
	 *  Example:
	 *      Docova.Utils.showAddressDialog({fieldname: "Users", dlgtype: "single", separator: ","});
	 *-------------------------------------------------------------------------------------------*/
	this.showAddressDialog = function (options) {
		var dlgDivContainerName = "dlgAddress" //default div name for dialog

			var defaultOptns = {
			fieldname: "",
			dlgtype: "single",
			separator: ",",
			dlgdivname: dlgDivContainerName,
			title: "Name Selection",
			sourcedocument: document,
			extendoverlay: null,
			cb: null,
			defaultvalues: null,
			returntypes: false,
			restricttolibrarymembers: ""
		};

		var opts = jQuery.extend({}, defaultOptns, options);

		var dlgHeight = (_DOCOVAEdition == "SE" ? "575" : "500"); //default to multi height
		var dlgWidth = "275"; //default to multi width

		//Remove dialog div if it already exists
		var dlgDiv = document.getElementById(dlgDivContainerName)
			if (dlgDiv) {
				dlgDiv.parentNode.removeChild(dlgDiv);
			}

			if (opts.dlgtype == "multi") {
				dlgWidth = "585";
			}

			var infovar = null;
		if (typeof docInfo == "undefined" || docInfo == null) {
			infovar = window.top.getinfovar();
		} else {
			infovar = docInfo;
		}

		if (opts.defaultvalues != null) {
			if (!Docova.GlobalStorage[dlgDivContainerName]) {
				Docova.GlobalStorage[dlgDivContainerName] = {};
			}
			Docova.GlobalStorage[dlgDivContainerName].defaultvalues = opts.defaultvalues;
		}

		if (_DOCOVAEdition == "SE") {
			var dlgUrl = infovar.PortalWebPath + "/dlgNameLookup?OpenForm&parentfield=" + opts.fieldname + "&type=" + opts.dlgtype + "&separator=" + opts.separator + "&dlgDiv=" + opts.dlgdivname;
		} else {
			var dlgUrl = infovar.PortalWebPath + "/dlgAddressBookLookup?OpenForm&parentfield=" + opts.fieldname + "&type=" + opts.dlgtype + "&separator=" + opts.separator + "&dlgDiv=" + opts.dlgdivname;
		}
		if (opts.returntypes) {
			dlgUrl += "&returntypes=1";
		}
		if (opts.restricttolibrarymembers != "") {
			dlgUrl += "&librarymembers=" + opts.restricttolibrarymembers;
		}

		var adrdlg = this.createDialog({
				id: dlgDivContainerName,
				url: dlgUrl,
				title: opts.title,
				height: dlgHeight,
				width: dlgWidth,
				autoopen: true,
				useiframe: true,
				sourcedocument: opts.sourcedocument,
				extendoverlay: opts.extendoverlay,
				buttons: {
					"OK": function () {
						var result = jQuery("#" + dlgDivContainerName + "IFrame")[0].contentWindow.handleOkClick();
						if (opts.cb && typeof opts.cb == "function") {
							opts.cb(result)
						}
						adrdlg.closeDialog();
					},
					Cancel: function () {
						var result = jQuery("#" + dlgDivContainerName + "IFrame")[0].contentWindow.handleCancelClick();
						if (opts.cb && typeof opts.cb == "function") {
							opts.cb(result);
						}
						adrdlg.closeDialog();
					}
				}
			});
	} //--end showAddressDialog

	/*-----------------------------------------------------------------------------------------
	 * Function: showProgressMessage
	 * Display status indicator dialog
	 *
	 * Parameters:
	 *  text: string - text to display in progress dialog
	 *
	 *  Example:
	 *      Docova.Utils.showProgressMessage("Please wait...");
	 *-------------------------------------------------------------------------------------------*/
	this.showProgressMessage = function (text) {
		if ($("#dialog-progress").length) {
			var isOpen = $("#dialog-progress").dialog("isOpen");
			if (isOpen) {
				$("#dialog-progress-text").html(text);
				return;
			}
		}

		var pdiv = $("<div id='dialog-progress'><div style= 'padding:20px;'><span class='ui-state-default ui-corner-all' style='float: left; margin:0 7px 0 0;'><span class='ui-icon ui-icon-info' style='float:left;'></span></span><div id ='dialog-progress-text' style='margin-left: 23px; font: normal normal 12px/1.5 Arial, Helvetica'></div></div></div>");

		pdiv.appendTo(document.body);
		$("#dialog-progress").dialog({
			modal: false,
			draggable: false,
			resizable: false,
			width: 400
		});

		$("#dialog-progress").prev("div").hide();
		$("#dialog-progress-text").html(text);

		$("#dialog-progress").dialog("open");
	} //--end showProgressMessage

	/*-----------------------------------------------------------------------------------------
	 * Function: hideProgressMessage
	 * Close currently open status indicator dialog
	 *
	 *  Example:
	 *      Docova.Utils.hideProgressMessage();
	 *-------------------------------------------------------------------------------------------*/
	this.hideProgressMessage = function () {
		$("#dialog-progress").dialog("close");
		$("#dialog-progress").remove();
	} //--end hideProgessMessage

	/*-----------------------------------------------------------------------------------------
	 * Function: setField
	 * Stores a value to a field specified by name or id
	 * Works on input, select, checkbox, radio, span fields
	 * Parameters:
	 *  options:  value pair array consisting of;
	 *          field: string - field name or id to store value to
	 *          value: string - value to update field with
	 *          separator: string - delimiter to use for splitting value
	 * currentdom: optional - target document object to update, defaults to current document
	 *
	 *  Returns:
	 *      Updates specified field in document with specified value
	 *
	 *  Example:
	 *      Docova.Utils.setField({field: "Users", value: "Jim,Tom", separator: ","});
	 *-------------------------------------------------------------------------------------------*/
	this.setField = function (options, currentdom) {
		var context = (currentdom ? currentdom : document);

		var defaultOptns = {
			field: "",
			value: "",
			separator: ";"
		};
		var opts = jQuery.extend({}, defaultOptns, options);

		//remap a null value to an empty string
		if (opts.value === null) {
			opts.value = "";
		}

		var byname = false;

		var flds = jQuery('[id="' + opts.field + '"]', context);
		//-- check if this is a wrapper (not the actual field element)
		if (jQuery(flds).is('div, span')) {
			flds = null;
		}
		if (!flds || flds.length == 0) {
			flds = jQuery('[name="' + opts.field + '"]', context);
			byname = (flds && flds.length > 0);
		}

		//look for checkboxes/radio button
		if (!flds || flds.length == 0) {
			flds = jQuery('[name="' + opts.field + '[]"]', context);
			byname = (flds && flds.length > 0);
		}

		//look for computed for display fields
		if (!flds || flds.length == 0) {
			flds = jQuery('[id="SPAN' + opts.field + '"]', context);
		}
		if (!flds || flds.length == 0) {
			flds = jQuery('[name="SPAN' + opts.field + '"]', context);
			byname = (flds && flds.length > 0);
		}
		if ((!flds || flds.length == 0) && !docInfo.isDocBeingEdited) {
			flds = jQuery('[id="' + opts.field + '"]', context);
			if (jQuery(flds).is('div')) {
				flds = null;
			}
		}		


		if (flds.length > 0) {
			for (var f = 0; f < flds.length; f++) {
				var fld = jQuery(flds[f]);

				var tempval = opts.value;

				var tagname = fld.prop('tagName').toLowerCase();
				var fldtype = fld.prop('type');

				var mvfield = (fld.attr('allowmultivalues') == "true" || (fld.attr('mvsep') && fld.attr('mvsep') !== "") || (fld.attr('mvdisep') && fld.attr('mvdisep') !== "") ? true : false);
				var mvsep = "";
				if (mvfield) {
					var seplist = fld.attr('mvdisep') || fld.attr('listinputseparators') || fld.attr('mvsep') || "";
					if (seplist.indexOf("newline") > -1) {
						mvsep = "\n";
					} else if (seplist.indexOf("comma") > -1) {
						mvsep = ",";
					} else if (seplist.indexOf("semicolon") > -1) {
						mvsep = ";";
					} else if (seplist.indexOf("blankline") > -1) {
						mvsep = "\n\n";
					} else if (seplist.indexOf("space") > -1) {
						mvsep = " ";
					}
				}

				var includetime = (fld.attr('displaytime') == "true" ? true : false);
				if(Array.isArray(tempval)){
					for(var vi=0; vi<tempval.length; vi++){
						if (tempval[vi] instanceof Date) {
							if (includetime) {
								tempval[vi] = this.convertDateFormat(tempval[vi]);
							} else {
								tempval[vi] = this.convertDateFormat(tempval[vi], "", true);
							}
						}						
					}
				}else{
					if (tempval instanceof Date) {
						if (includetime) {
							tempval = this.convertDateFormat(tempval);
						} else {
							tempval = this.convertDateFormat(tempval, "", true);
						}
					}
				}

				//if radio button
				if (fldtype == "radio") {
					if (byname) { //if the element was gotten by name instead of id
						jQuery('input[name="' + opts.field + '"][value="' + tempval + '"]', context).prop('checked', true);
					} else {
						jQuery('input[id="' + opts.field + '"][value="' + tempval + '"]', context).prop('checked', true);
					}
					//if checkbox
				} else if (fldtype == "checkbox") {
					if ($.trim(tempval) == "") {
						fld.removeAttr('checked'); //if value is blank, then clear all checked
					}
					if (byname) {
						var valueArray = (Array.isArray(tempval) ? tempval : (typeof tempval === "string" ? tempval.split(opts.separator) : [tempval]));
						fld.removeAttr('checked'); //if getting by name(grouped) then clear first
						fld.each(function (idx, obj) {
							for (var j = 0; j < valueArray.length; j++) {
								if (jQuery(obj).val() == valueArray[j]) {
									jQuery(obj).prop('checked', true)
								}
							}
						})
					} else {
						jQuery('input[id="' + opts.field + '"][value="' + tempval + '"]', context).prop('checked', true);
					}
					//if text
				} else if (fldtype == "text" || fldtype == "color") {
					fld.val((mvfield && Array.isArray(tempval) ? tempval.join(mvsep) : tempval));
					//if select-one
				} else if (fldtype == "select-one") {
					var valuechanged = false;
					var hasoption = false;
					jQuery(fld).children("option").each(function () {
						var tempoptxt = $.trim($(this).text());
						var tempopval = $.trim($(this).val());
						if (tempoptxt === tempval || tempopval === tempval) {
							this.selected = true;
							tempval = $.trim($(this).text());
							hasoption = true;
							valuechanged = true;
							return false;
						}
					});

					if (!hasoption) {
						if (fld.attr("allownewvals") == "1") {
							//remove any previously added temporary options
							$(fld).find("option[temporary=1]").remove();
							$(fld).find("option:selected").removeProp("selected").removeAttr("selected");

							//append this new temporary option
							$(fld).append($('<option>', {
									'value': tempval,
									'text': tempval,
									'temporary': "1",
									'selected': true
								}));

							valuechanged = true;
						}
					}
					if (valuechanged) {
						//-- need to update the combobox display value
						jQuery("span.custom-combobox[sourceelem=" + opts.field + "]>input:first", context).val(tempval);
					}
					//if select-multiple
				} else if (fldtype == "select-multiple") {
					var newvals = [];
					var valuechanged = false;

					//--clear existing selections
					$(fld).find("option:selected").removeProp("selected").removeAttr("selected");
					//--remove any temporary options
					$(fld).find("option[temporary=1]").remove();

					$.each((tempval).split(opts.separator), function (i, e) {
						var hasoption = false;

						jQuery(fld).children("option").each(function () {
							var tempoptxt = $.trim($(this).text());
							var tempopval = $.trim($(this).val());
							if (tempoptxt === e || tempopval === e) {
								this.selected = true;
								newvals.push($.trim($(this).text()));
								hasoption = true;
								valuechanged = true;
								return false;
							}
						});

						if (!hasoption) {
							if (fld.attr("allownewvals") == "1") {
								//append this new temporary option
								$(fld).append($('<option>', {
										'value': $.trim(e),
										'text': $.trim(e),
										'temporary': "1",
										'selected': true
									}));
								newvals.push($.trim(e));
								valuechanged = true;
							}
						}
					});

					if (valuechanged) {
						//-- need to update the combobox display value
						jQuery("span.custom-combobox[sourceelem=" + opts.field + "]>input:first", context).val(newvals.join(opts.separator));
					}
					//if textarea
				} else if (fldtype == "textarea") {
					fld.val((mvfield && Array.isArray(tempval) ? tempval.join(mvsep) : tempval));
					//if span tag
				} else if (tagname == "span") {
					//fld.text(tempval);
					var newval = (mvfield && Array.isArray(tempval) ? tempval.join(mvsep) : tempval);
					var tempspan = $(fld).find("span:first");
					if (tempspan.length == 0) {
						tempspan = fld;
					}
					//remove existing text content
					$(tempspan).contents().filter(function () {
						return this.nodeType === 3;
					}).remove();

					var textnode = context.createTextNode(newval);
					$(tempspan).get(0).appendChild(textnode);

					//if a hidden field (may be generated for a computed or field with hide when set)
				} else if (fldtype == "hidden" && tagname == "input") {
					var newval = (mvfield && Array.isArray(tempval) ? tempval.join(mvsep) : tempval);
					fld.val(newval);
					var displayflds = jQuery('[id="SPAN' + opts.field + '"]', context);
					if (!displayflds || displayflds.length == 0) {
						displayflds = jQuery('[name="SPAN' + opts.field + '"]', context);
					}
					if (displayflds.length > 0) {
						var tempspan = $(displayflds).find("span:first");
						if (tempspan.length == 0) {
							tempspan = displayflds;
						}
						//remove existing text content
						$(tempspan).contents().filter(function () {
							return this.nodeType === 3;
						}).remove();

						var textnode = context.createTextNode(newval);
						$(tempspan).get(0).appendChild(textnode);
					}
				}
				///the rest
			}
		}
	} //--end setField

	/*-----------------------------------------------------------------------------------------
	 * Function: getField
	 * Retrieves values from a field specified by name or id
	 * Works on input, select, checkbox, radio, span fields
	 * Parameters:
	 *  options:  value pair array consisting of;
	 *          field: string - field name or id to retrieve values from
	 *          separator: string - delimiter to use for concatenating values
	 * currentdom: optional - target document object to retrieve values from, defaults to current document
	 *
	 *  Returns:
	 *      string/date - value or concatenated values from specified field, or null if no value found
	 *
	 *  Example:
	 *      Docova.Utils.getField({field: "Users", separator: ","});
	 *-------------------------------------------------------------------------------------------*/
	this.getField = function (options, currentdom) {
		var result = null;
		var fieldfound = false;

		var context = (currentdom ? currentdom : document);
		var tmpoptions = {};

		if (typeof options == "string") {
			tmpoptions = {
				field: options,
				separator: ";"
			};
		} else {
			tmpoptions = options;
		}

		var defaultOptns = {
			field: "",
			separator: ";"
		};

		var opts = jQuery.extend({}, defaultOptns, tmpoptions);

		var vals;
		var valsarray = [];

		var flds = jQuery('[id="' + opts.field + '"]', context);
		//-- check if this is a wrapper (not the actual field element)
		if (jQuery(flds).is('div, span')) {
			flds = null;
		}
		if (!flds || flds.length == 0) {
			flds = jQuery('[name="' + opts.field + '"][type!="hidden"]', context);
		}

		//look for checkboxes/radio button
		if (!flds || flds.length == 0) {
			flds = jQuery('[name="' + opts.field + '[]"][type!="hidden"]', context);
		}

		if (!flds || flds.length == 0) {
			flds = jQuery('[name="' + opts.field + '"][type="hidden"]', context);
		}

		//look for computed for display fields
		if (!flds || flds.length == 0) {
			flds = jQuery('[id="SPAN' + opts.field + '"]', context);
		}
		if (!flds || flds.length == 0) {
			flds = jQuery('[name="SPAN' + opts.field + '"]', context);
		}
		if ((!flds || flds.length == 0) && !docInfo.isDocBeingEdited) {
			flds = jQuery('[id="' + opts.field + '"]', context);
			if (jQuery(flds).is('div')) {
				flds = null;
			}
		}

		if (flds.length > 0) {
			fieldfound = true;
			for (var f = 0; f < flds.length; f++) {
				var fld = jQuery(flds[f]);

				var isnames = false;
				if (typeof fld.attr('nametype') !== 'undefined' && typeof fld.attr('nametype') !== '') {
					isnames = true;
				}

				var mvfield = (fld.attr('allowmultivalues') == "true" || (fld.attr('mvsep') && fld.attr('mvsep') !== "") || (fld.attr('mvdisep') && fld.attr('mvdisep') !== "") ? true : false);
				var mvsep = [];
				mvsep.length = 0;
				if (mvfield) {
					var seplist = fld.attr('listinputseparators') || fld.attr('mvsep') || fld.attr('mvdisep') || "";
					if (seplist.indexOf("newline") > -1) {
						mvsep.push("\n");
					}
					if (seplist.indexOf("comma") > -1) {
						mvsep.push(",");
					}
					if (seplist.indexOf("semicolon") > -1) {
						mvsep.push(";");
					}
					if (seplist.indexOf("blankline") > -1) {
						mvsep.push("\n");
					}
					if (seplist.indexOf("space") > -1) {
						mvsep.push(" ");
					}
				}

				if (fld.prop('type') == "checkbox") {
					if (fld.prop("checked")) {
						valsarray.push(fld.val())
					}
				} else if (fld.prop('type') == "radio") {
					if (fld.prop("checked")) {
						valsarray.push(fld.val())
					}
				} else if (jQuery(fld).hasClass("hasDatepicker") || jQuery(fld).attr("elem") == "date") {
					var tempvals = fld.val();
					if (mvfield) {
						var re = new RegExp(mvsep.join("|"), "g");
						tempvals = tempvals.split(re);
					}else{
						tempvals = [tempvals];
					}
					for(var vi=0; vi<tempvals.length; vi++){
						if(jQuery.trim(tempvals[vi]) != ""){
							tempvals[vi] = this.convertStringToDate(tempvals[vi]);	
						}
						valsarray.push(tempvals[vi]);
					}
				} else {
					if (mvfield) {
						var re = new RegExp(mvsep.join("|"), "g");
						if (fld.prop("tagName").toLowerCase() == "span") {
							var childspan = $(fld).find("span:first");
							if (childspan.length > 0) {
								var tempvals = childspan.text().split(re);
							} else {
								var tempvals = fld.text().split(re);
							}
						} else {
							var tempvals = fld.val();
							if(tempvals === null){
								tempvals = "";
							}
							if(typeof tempvals === "string"){
							   tempvals = tempvals.split(re);	
							}
						}
						if (isnames) {
							for (var t = 0; t < tempvals.length; t++) {
								tempvals[t] = $$Name("[CANONICALIZE]", tempvals[t]);
							}
						}
						valsarray = valsarray.concat(tempvals);
					} else {
						if (fld.prop("tagName").toLowerCase() == "span") {
							var childspan = $(fld).find("span:first");
							if (childspan.length > 0) {
								var tempval = childspan.text();
							} else {
								var tempval = fld.text();
							}
						} else {
							var tempval = fld.val();
						}
						if (isnames) {
							tempval = $$Name("[CANONICALIZE]", tempval);
						}
						valsarray.push(tempval);
					}
				}
			}
		}

		//if no result return empty string
		if (!valsarray || valsarray.length == 0) {
			//check if the field was found
			if (fieldfound) {
				//return a blank string instead of null
				result = "";
			} else {
				//leave return value as null
			}
		} else if (valsarray.length > 1) {
			if (opts.separator !== "") {
				//if array and multi value separator specified concat into single string
				result = valsarray.join(opts.separator);
			} else {
				//if array and multi value separator not specified return an array
				result = valsarray;
			}
		} else {
			//return the single value in the array
			result = valsarray[0];
		}

		return result;
	} //--end getField


	/*--------------------------------------
	 * Function: createComboBoxField
	 * Converts a select field to a combobox - type in autocomplete with select
	 * Parameters:
	 *  fieldelement: DOM select input field to apply the styling to
	 *  custoptions:  value pair array consisting of;
	 *		allowvaluesnotinlist: boolean - defaults to false - whether custom values can be typed in
	 *      themefield: boolean - defaults to true - whether input field is themed
	 *      casesensitive: boolean - defaults to true - whether search is case sensitive
	 *      maxShowItems: integer - defaults to 10 - number of items to show in list before scrolling
	 * Returns:
	 *      Changes the format of the specified select field
	 * Dependencies: 
	 *      jQuery UI, jquery.ui.autocomplete.scroll.js plugin
	 * Example:
	 *     Docova.Utils.createComboBoxField(jQuery("#select_box_1"), {allowvaluesnotinlist: false, themefield: false});
	---------------------------------------*/
	this.createComboBoxField = function (fieldelement, custoptions) {
		if (!fieldelement) {
			return false;
		}

		var defopts = {
			allowvaluesnotinlist: false,
			themefield: true,
			casesensitive: false,
			maxShowItems: 10,
			multivalue: false,
			multivaluesep: ",",
			customwidth: '',
			additionalstyle: '',
			placeholder: ''
		};

		var opts = jQuery.extend({}, defopts, custoptions);

		//check to see if the widget has already been registered
		if (typeof jQuery.fn.combobox != 'function') {
			$.widget("custom.combobox", {
				options: opts,

				_create: function () {
					this.wrapper = $("<span>")
						.addClass("custom-combobox")
						.width(this.options.customwidth ? this.options.customwidth : $(this.element).width())
						.attr("sourceelem", (jQuery(this.element).attr("id") || jQuery(this.element).attr("name") || ""))
						.insertAfter(this.element);

					this.element.hide();
					this._createAutocomplete();
					this._createShowAllButton();
				},

				_createAutocomplete: function () {

					var selectedItems = [];
					var selected = this.element.children(":selected");
					var value = "";

					var inputHeight = $(this.element).height();
					if (this.options.multivalue && selected.length > 0) {
						for (var t = 0; t < selected.length; t++) {
							var obj = $(selected[t]);
							var tmpval = obj.val() ? obj.val() : obj.text();
							value += value == "" ? tmpval.trim() : this.options.multivaluesep + tmpval.trim();
							selectedItems.push(tmpval.trim());
						}
					}else{
						value = selected.val() ? selected.text() : "";
						if (value == "-Select-") {
							value = "";
						}
					}
					if (parseInt(inputHeight, 10) < 19) {
						inputHeight = 19;
					}
					
					if (this.options.customwidth && this.options.customwidth.indexOf('%') != -1) {
						inputWidth = this.options.customwidth;
					}
					else {
						inputWidth = $(this.element).width();
						if (parseInt(inputWidth, 10) >= 50) {
							inputWidth = parseInt(inputWidth, 10) - 25; //subtract width of show all button
						}
					}
					var inputTabIndex = $(this.element).attr("tabindex") || "";

					var sourceelemid = jQuery(this.wrapper).attr("sourceelem");
					var self = this;

					var itemcount = 0;

					this.input = $("<input>")
						.appendTo(this.wrapper)
						.val(value)
						.attr('placeholder', this.options.placeholder ? this.options.placeholder : '')
						.attr("title", "")
						.attr("tabindex", inputTabIndex)
						.addClass("custom-combobox-input ui-widget ui-widget-content " + (this.options.themefield ? " ui-state-default ui-corner-left" : ""))
						.tooltip({
							classes: {
								"ui-tooltip": "ui-state-highlight"
							}
						})
						.attr('style', this.options.additionalstyle)
						.height(inputHeight);
						if (isNaN(inputWidth)) {
							this.input.css('width', inputWidth);
						}
						else {
							this.input.width(inputWidth);
						}
					this.input.autocomplete({
							delay: 0,
							minLength: 0,
							maxShowItems: this.options.maxShowItems,
							source: $.proxy(this, "_source"),
							open: function (event, ui) {								
								itemcount = 0;
								jQuery("ul.ui-autocomplete.ui-front:visible").attr("sourceelem", sourceelemid);
							},
							focus: function () {
								// prevent value inserted on focus
								return false;
							},
							close: function (event, ui) {

								return true;
							},

							create: function () {
								if (self.options.multivalue) {
									$(this).data('ui-autocomplete')._renderItem = function (ul, item) {
										if (typeof item === 'undefined'){
											return;
										}
										var checked = ($.inArray(item.label.trim(), selectedItems) >= 0 || $.inArray(item.value.trim(), selectedItems) >= 0 ? 'checked' : '');
										var selval = item.value ? item.value.trim() : item.label.trim();
										itemcount++;
										return $("<li></li>")
										.data("item.autocomplete", item)
										.append('<input id="i' + itemcount.toString() + '" value="' + selval + '" type="checkbox" ' + checked + '/>' + item.label)
										.appendTo(ul);
									};
								}
								
								$(this).data('ui-autocomplete')._resizeMenu = function () {
									
									//-- check if the menu will be cut off by the end of the window																
									var ul, lis, ulW, barW;
									ul = this.menu.element
										.scrollLeft(0).scrollTop(0) // Reset scroll position
										.css({overflowX: '', overflowY: '', width: '', maxHeight: ''}); // Restore
									lis = ul.children('li').css('whiteSpace', 'nowrap');
									
									var adjustedheight = this.menu.element.outerHeight();
									if(!isNaN(this.options.maxShowItems)){
										if (lis.length > this.options.maxShowItems){
											adjustedheight = (lis.eq(0).outerHeight() * this.options.maxShowItems) + 1; // 1px for Firefox
										}
									}
									
									var fieldpos = this.element.offset().top + this.element.outerHeight();
									var winheight = $(window).height();
									var gap = winheight - fieldpos; 
									if (gap < adjustedheight) {
										adjustedheight = gap;
									}
										
									ulW = ul.prop('clientWidth');
									ul.css({overflowX: 'hidden', overflowY: 'auto',	maxHeight: adjustedheight}); // 1px for Firefox
									barW = ulW - ul.prop('clientWidth');
									ul.width('+=' + barW);
									
									// Original code from jquery.ui.autocomplete.js _resizeMenu()
									ul.outerWidth(Math.max(
										ul.outerWidth() + 1,
										this.element.outerWidth()
									));								
								}
								
							}
						});

					this._on(this.input, {
						autocompleteselect: function (event, ui) {

							if (this.options.multivalue) {

								var curval = ui.item.value ? ui.item.value.trim() : ui.item.label.trim();
								var curind = $.inArray(curval, selectedItems);

								var chkboxelem = null;
								var fieldname = ui.item.option.parentElement.id; //name of source field
								jQuery("ul.ui-autocomplete[sourceelem=" + fieldname + "]").find("li").each(function () {
									var inpelem = jQuery(this).find("input").get(0);
									if (inpelem) {
										if ($.trim(jQuery(inpelem).text()) == ui.item.label.trim() || $.trim(jQuery(inpelem).val()) == ui.item.value.trim()) {
											chkboxelem = inpelem;
											return;
										}
									}
								});

								if (curind == -1) {
									if (chkboxelem) {
										chkboxelem.checked = true;
										jQuery(chkboxelem).attr("checked", "checked");
									}
									selectedItems.push(curval);
									$(ui.item.option).prop("selected", true);
								} else {
									if (chkboxelem) {
										chkboxelem.checked = false;
										$(chkboxelem).removeAttr("checked");
									}
									for (var l = 0; l < selectedItems.length; l++) {
										if (selectedItems[l] == curval) {
											selectedItems.splice(l, 1);
											break;
										}
									}
									$(ui.item.option).prop("selected", false);
								}

								this.input.val(selectedItems.join(this.options.multivaluesep));
								event.stopImmediatePropagation();
								event.preventDefault();
								event.stopPropagation();

								return false;

							} else {
								ui.item.option.selected = true;

								this._trigger("select", event, {
									item: ui.item.option
								});
								// Trigger any custom on change event
								this.changeTriggered = true;
								jQuery(this.element).triggerHandler("change");
							}

						},

						autocompletechange: "_removeIfInvalid",

						focus: function (event, ui) {
							jQuery(this.element).triggerHandler("focus");
						},

						click: function (event, ui) {
							jQuery(this.element).triggerHandler("click");
						},

						blur: function (event, ui) {
							jQuery(this.element).triggerHandler("blur");
						}
					});
				},

				_createShowAllButton: function () {
					var input = this.input;
					var wasOpen = false;
					var inputHeight = $(this.input).outerHeight() - 2;
					if (parseInt(inputHeight, 10) < (_DOCOVAEdition == "SE" ? 19 : 16)) {
						inputHeight = (_DOCOVAEdition == "SE" ? 19 : 16);
					}

					$("<a>")
					.attr("tabIndex", -1)
					.attr("title", "")
					.tooltip()
					.appendTo(this.wrapper)
					.button({
						icons: {
							primary: "fas fa-caret-down"
						},
						text: false
					})
					.removeClass("ui-corner-all")
					.addClass("dtoolbar custom-combobox-toggle ui-corner-right")
					.height(inputHeight)
					.on("mousedown", function () {
						wasOpen = input.autocomplete("widget").is(":visible");
					})
					.on("click", function () {
						input.trigger("focus");

						// Close if already visible
						if (wasOpen) {
							return;
						}

						// Pass empty string as value to search for, displaying all results
						input.autocomplete("search", "");
					});
				},

				_source: function (request, response) {
					var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
					response(this.element.children("option").map(function () {
							var text = $(this).text();
							if (this.value && (!request.term || matcher.test(text)))
								return {
									label: text,
									value: text,
									option: this
								};
						}));
				},

				_removeIfInvalid: function (event, ui) {
					// Selected an item, nothing to do
					if (ui.item) {
						// Trigger any custom on change event
						if ( ! this.changeTriggered)
							jQuery(this.element).triggerHandler("change");
						else
							this.changeTriggered = false;

						return;
					}

					var value = "";

					if (this.options.multivalue) {
						var newarr = this.input.val().split(this.options.multivaluesep);
						var newval = "";
						for (var t = 0; t < newarr.length; t++) {
							var found = false;
							this.element.children("option").each(function () {
								var temptxt = $.trim($(this).text()).toLowerCase();
								var tempval = $.trim($(this).val()).toLowerCase();
								if (newarr[t] == temptxt || newarr[t] == tempval) {
									found = true;
								}
							});
							if (!found) {
								value = newarr[t];
							}
						}

					} else {
						value = $.trim(this.input.val());

					}

					// Search for a match (case-insensitive)

					var valueCompare = value;
					var ignorecase = !this.options.casesensitive;
					if (ignorecase) {
						valueCompare = valueCompare.toLowerCase();
					}
					var valid = false;

					this.element.children("option").each(function () {
						var temptxt = $.trim($(this).text());
						if (ignorecase) {
							temptxt = temptxt.toLowerCase();
						}
						var tempval = $.trim($(this).val());
						if (ignorecase) {
							tempval = tempval.toLowerCase();
						}

						if (temptxt === valueCompare || tempval === valueCompare) {
							this.selected = true;
							value = $.trim($(this).text());
							valid = true;
							return false;
						}
					});

					// Found a match, nothing to do
					if (valid) {
						if (this.options.multivalue){
							return;
						}
						this.input.val(value); //make sure input field is a match for found item
						// Trigger any custom on change event
						jQuery(this.element).triggerHandler("change");
						return;
					}

					//check if we should add a new entry to the list
					if (this.options.allowvaluesnotinlist) {
						//remove any previously added temporary options
						$(this.element).find("option[temporary=1]").remove();

						//append this new temporary option
						$(this.element).append($('<option>', {
								'value': value,
								'text': value,
								'temporary': "1"
							}));

						//select the entered value
						$(this.element).val(value);
						// Trigger any custom on change event
						jQuery(this.element).triggerHandler("change");
					} else {
						// Remove invalid value
						this.input
						.val("")
						.attr("title", value + " didn't match any item")
						.tooltip("open");

						this.element.val("");
						// Trigger any custom on change event
						jQuery(this.element).triggerHandler("change");
						this._delay(function () {
							this.input.tooltip("close").attr("title", "");
						}, 2500);
						this.input.autocomplete("instance").term = "";
					}
				},

				_destroy: function () {
					this.wrapper.remove();
					this.element.show();
				}
			});
		}

		//initialize whatever field element was passed
		jQuery(fieldelement).combobox(custoptions);

	} //--end createComboBoxField


	/*--------------------------------------
	 * Function: setDropdownOptions
	 * Sets the available options of a dropdown(selection field)
	 * Parameters:
	 *  options:  value pair array consisting of;
	 *     field: string - field name or id to set options values of
	 *	   optionlist: - delimited string
	 *     separator: string - delimiter to use for concatenating values
	 *
	 *  Returns:
	 *      Sets the options in the specified field
	 * Returns:
	 * Example:
	---------------------------------------*/
	this.setDropdownOptions = function (options) {

		var defaultOptns = {
			field: "",
			optionlist: "",
			separator: ";"
		};

		var opts = jQuery.extend({}, defaultOptns, options);

		var flds = jQuery('[name="' + opts.field + '"]');
		if (!flds[0]) {
			flds = jQuery('[id="' + opts.field + '"]');
		}

		var curvals = [];
		var newoptions = [];
		$(flds).find("option:selected").each(function () {
			curvals.push($(this).attr("value"));
			newoptions.push($(this).attr("value"));
		});

		$(flds).empty();
		var optionlist = opts.optionlist;
		var optionlistarray = optionlist.split(opts.separator);
		if (optionlistarray.indexOf("-Select-") == -1) {
			$(flds).append($("<option></option>").attr("value", "").text("-Select-"));
		}
		for (x = 0; x < optionlistarray.length; x++) {
			if (optionlistarray[x].indexOf("|") != "-1") { //if alias/synonyms where used then
				var singleoptionarray = optionlistarray[x].split("|")
					$(flds).append($("<option></option>").attr("value", singleoptionarray[1]).text(singleoptionarray[0]));
				var pos = newoptions.indexOf(singleoptionarray[1]);
				if (pos > -1) {
					newoptions = newoptions.splice(pos, 1);
				}
			} else {
				$(flds).append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
				var pos = newoptions.indexOf(optionlistarray[x]);
				if (pos > -1) {
					newoptions = newoptions.splice(pos, 1);
				}
			}
		}

		for (x = 0; x < newoptions.length; x++) {
			$(flds).append($("<option></option>").attr("value", newoptions[x]).text(newoptions[x]));
		}

		$(flds).find("option").each(function () {
			if (curvals.indexOf($(this).attr("value")) > -1) {
				$(this).prop("selected", true);
			}
		});

	} //-- end setDropdownOptions

	/*--------------------------------------
	 * Function: openDialog
	 * Helper function to create a dialog with a form in it
	---------------------------------------*/
	this.openDialog = function (options) {
		var defaultOptns = {
			formname: "",
			docid: "",
			title: "",
			height: 500,
			width: 400,
			buttons: [],
			nsfpath: "",
			resizable: true,
			sourcewindow: null,
			sourcedocument: null,
			inherit: false,
			isresponse: false,
			docurl: null,
			onClose: function () {}
		};
		var opts = $.extend({}, defaultOptns, options);
		var _dlgUrl = "";

		var _nsfPath = "";
		if (opts.nsfpath == "") {
			_nsfPath = docInfo.NsfName
		} else {
			_nsfPath = opts.nsfpath;
		}
		if (_nsfPath.substr(0, 1) == "/")
			_nsfPath = _nsfPath.substring(1)

		if (_DOCOVAEdition == "SE" && opts.docurl) {
			if (opts.isresponse == true & opts.inherit == true) {
				_dlgUrl = opts.docurl + "&ParentDocID=" + docInfo.DocID + "&ParentUNID=" + docInfo.DocID + "&isresponse=true";
			}
			if (opts.isresponse == true & opts.inherit == false) {
				_dlgUrl = opts.docurl + "&ParentDocID=" + docInfo.DocID + "&isresponse=true";
			}
			if (opts.isresponse == false & opts.inherit == true) {
				_dlgUrl = opts.docurl + "&ParentUNID=" + docInfo.DocID;
			}
			if (opts.isresponse == false & opts.inherit == false) {
				_dlgUrl = opts.docurl;
			}
		} else if (opts.docid != "" && _nsfPath) {
			if (_DOCOVAEdition == "SE") {
				_dlgUrl = "/" + _nsfPath;
			} else {
				_dlgUrl = "/" + _nsfPath + "/0/" + opts.docid + "?opendocument";
			}
		} else {

			if (opts.isresponse == true & opts.inherit == true) {
				_dlgUrl = "/" + _nsfPath + "/" + opts.formname + "?openform&ParentDocID=" + docInfo.DocID + "&ParentUNID=" + docInfo.DocID + "&isresponse=true";
			}
			if (opts.isresponse == true & opts.inherit == false) {
				_dlgUrl = "/" + _nsfPath + "/" + opts.formname + "?openform&ParentDocID=" + docInfo.DocID + "&isresponse=true";
			}
			if (opts.isresponse == false & opts.inherit == true) {
				_dlgUrl = "/" + _nsfPath + "/" + opts.formname + "?openform&ParentUNID=" + docInfo.DocID;
			}
			if (opts.isresponse == false & opts.inherit == false) {
				_dlgUrl = "/" + _nsfPath + "/" + opts.formname + "?openform";
			}
		}

		if (opts.formname != "") {
			_dlgId = opts.formname;
		} else {
			_dlgId = opts.docid;
		}
		_dlgUrl += "&mode=dialog&dialogid=" + _dlgId;

		//_dlgUrl = "/" + NsfName + "/" + opts.formname + "?OpenForm"

		var win = opts.sourcewindow ? opts.sourcewindow : window;
		var doc = opts.sourcedocument ? opts.sourcedocument : document;

		var _dlg = window.top.Docova.Utils.createDialog({
			id: _dlgId,
			url: _dlgUrl,
			title: opts.title,
			height: opts.height,
			width: opts.width,
			resizable: opts.resizable,
			useiframe: true,
			sourcewindow: win,
			sourcedocument: doc,
			onResize: function (event, ui) {

				var ifrm = window.top.$("#" + _dlgId + "IFrame")[0];

				if (ifrm) {
					ifrm.width = ui.size.width - 50;
					ifrm.height = ui.size.height - 50;
				}
			},
			onClose: function () {
				opts.onClose();
			},
			buttons: opts.buttons
		});

		return _dlg
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: getSubForm
	 * Returns a section of html from a specified url
	 * Parameters: url - string - url to retrieve data from
	 *             htmltagid - string (optional) - id of html element to return
	 *             outerhtml - boolean (optional) - true to retrieve outer html of specified tag
	 * Returns: returns an html string value or "" if an error occurs
	 *-------------------------------------------------------------------------------------------*/
	this.getSubForm = function (url, htmltagid, outerhtml) {
		var result = "";

		if (!url || url == "") {
			return result;
		}

		jQuery.ajax({
			"url": url,
			"async": false,
			"cache": false,
			"type": "GET",
			"dataType": "html"
		})
		.done(function (htmldata) {
			if (htmltagid && htmltagid != "") {
				var htmlbit = jQuery(htmldata).find("#" + htmltagid);
				if (htmlbit.length == 0) {
					htmlbit = jQuery(htmldata).find("[name=htmltagid]:first");
				}
				if (htmlbit.length > 0) {
					if (outerhtml) {
						result = jQuery(htmlbit)[0].outerHTML;
					} else {
						result = jQuery(htmlbit).html();
					}
				}
			} else {
				result = htmldata;
			}
		})

		return result;
	} //-end getSubform

	/*-----------------------------------------------------------------------------------------
	 * Function: dbLookup
	 * Retrieves values from a view or document in a view
	 * Parameters:
	 *  options:  value pair array consisting of;
	 *          servername: string - server host name - defaults to current host
	 *          nsfname: string - database path - defaults to current path
	 *          viewname: string - look up view
	 *          key: string - key look up value to match against view contents
	 *          columnorfield: integer or string - column number or name of field to retrieve values from
	 *          delimiter: string - delimiter to use to concatenate multiple values. defaults to ;
	 *          alloweditmode: boolean - true to open form in edit mode to retrieve values - defaults to true
	 *          secure: "ON" or "OFF" - specify ON to retrieve data via HTTPS
	 *          failsilent: boolean - true to ignore errors, otherwise display prompt on error. defaults to false
	 *          returndocid: boolean - true to return document id instead of column or field value. defaults to false
	 *
	 *  Returns:
	 *      string - value or concatenated values from specified column or field
	 *
	 *  Example:
	 *      Docova.Utils.dbLookup({"servername" : "www.acme.com", "nsfname" : "/docova/hrlib.nsf", "viewname" : "employeecontracts", "key" : "Jim Smith", "columnorfield" : "2", "delimiter" : ",", "alloweditmode" : false, "secure" : "OFF", "failsilent" : true});
	 *-------------------------------------------------------------------------------------------*/
	this.dbLookup = function (options) {
		var defaultOptns = {
			servername: "",
			nsfname: "",
			viewname: "",
			key: "",
			columnorfield: "",
			delimiter: ";",
			alloweditmode: true,
			secure: "",
			extraquery: {},
			failsilent: false,
			returndocid: false
		};

		var opts = $.extend({}, defaultOptns, options);
		var result = "";
		var resultfound = false;
		if (Array.isArray(opts.key)) 
		{
			key = opts.key.join(String.fromCharCode(31));
		}else{
			key = opts.key;
		}

		key = btoa(key);

		if (_DOCOVAEdition == "SE") {

			if (opts.nsfname == "") {
				opts.nsfname = docInfo.appName;
			}

			var request = "<Request>";
			request += "<Action>DBLOOKUP</Action>";
			request += "<appname>" + opts.nsfname + "</appname>";
			request += "<viewname>" + opts.viewname + "</viewname>";
			request += "<key><![CDATA[" + key + "]]></key>";
			request += "<delimiter>" + opts.delimiter + "</delimiter>";
			request += "<columnorfield>" + opts.columnorfield + "</columnorfield>";
			request += "<returndocid>" + (opts.returndocid === true ? "1" : "0") + "</returndocid>";
			request += "</Request>";

			var processUrl = "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";

			var result;
			var resulttext;
			$.ajax({
				type: "POST",
				url: processUrl,
				data: encodeURI(request),
				cache: false,
				async: false,
				dataType: "xml",
				success: function (xml) {
					result = true;
					var xmlobj = $(xml);
					var statustext = xmlobj.find("Result").first().text();
					if (statustext == "OK") {
						var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
						resulttext = resultxmlobj.first().text();
					}
				},
				error: function () {
					if (!opts.failsilent) {
						alert("An error occurred retrieving data from the server.")
					}
				}
			});

			return resulttext;

		} else {
			var column = parseInt(opts.columnorfield);
			var useform = isNaN(column);
			column = (useform) ? -1 : column - 1; //Root xml index starts at 0, but this function will be used where user/developer thinks first column is 1.
			var docunidlist = new Array();

			if (opts.secure == "") {
				var strUrlBase = location.protocol + "//"
			} else {
				var strUrlBase = (opts.secure == "ON") ? "https://" : "http://";
			}

			if (opts.servername == "") {
				strUrlBase += window.location.host;
			} else {
				if (opts.servername.toLowerCase().indexOf("http") > -1) {
					var n = opts.servername.toLowerCase().indexOf("//");
					if (n > -1) {
						opts.servername = opts.servername.slice(n + 2);
					}
				}
				strUrlBase += opts.servername;
			}
			strUrlBase += (strUrlBase.charAt(strUrlBase.length - 1) == "/") ? "" : "/";
			if (opts.nsfname == "") {
				opts.nsfname = window.location.href;
				var n = opts.nsfname.toLowerCase().indexOf(window.location.host.toLowerCase());
				if (n > -1) {
					opts.nsfname = opts.nsfname.slice(n + window.location.host.length + 1);
				}
				var n = opts.nsfname.toLowerCase().indexOf(".nsf");
				if (n > -1) {
					opts.nsfname = opts.nsfname.slice(0, n + 4);
				}
			}
			strUrlBase += (strUrlBase.charAt(strUrlBase.length - 1) == "/" || opts.nsfname.charAt(0) == "/") ? "" : "/";
			strUrlBase += opts.nsfname;
			strUrlBase += (strUrlBase.charAt(strUrlBase.length - 1) == "/") ? "" : "/";

			var strUrl = strUrlBase + encodeURIComponent(opts.viewname);
			strUrl += "?ReadViewEntries&Count=1&StartKey=";
			strUrl += encodeURIComponent(opts.key);

			var counter = 0;
			var positionwanted = "";
			var keepgoing = true;
			while (keepgoing) {
				keepgoing = false;
				//-- get initial row to find out row number and determine if children exist
				jQuery.ajax({
					url: strUrl,
					async: false,
					cache: false,
					type: "GET",
					dataType: "xml"
				})
				.done(function (data) {
					var viewentry = jQuery(data).find("viewentry:first");
					var jqViewEntry = jQuery(viewentry);
					var position = jqViewEntry.attr("position");
					var unid = jqViewEntry.attr("unid");
					var jqEntry = jqViewEntry.find("entrydata:first");
					var colnum = jqEntry.attr("columnnumber");
					//-- check if key matches first column or if we are within a category that the position returned matches what we asked for
					if ((colnum == "0" && jQuery.trim(jqEntry.text()).toUpperCase() == jQuery.trim(opts.key).toUpperCase()) || (colnum != "0" && positionwanted != "" && positionwanted == position)) {
						if (unid != undefined && unid != "") { //-- document found
							resultfound = true;
							if (!useform) { //-- check if we can get data from the view
								jqViewEntry.find("entrydata[columnnumber='" + column.toString() + "']").find("text,number,datetime").each(function (index) {
									result += (result == "") ? "" : opts.delimiter;
									result += jQuery(this).text();
								});
							} else { //-- using form to get data, so push id to stack for later retrieval
								docunidlist.push(unid);
							} //--end use form or view check
							//-- get the next document
							var posbits = position.split(".");
							var tempposbit = posbits[posbits.length - 1];
							tempposbit = parseInt(tempposbit) + 1;
							posbits[posbits.length - 1] = tempposbit.toString();
							positionwanted = posbits.join(".");
						} else { //-- no document found, must be a category
							//-- get the next document
							positionwanted = position + "." + "1";
						}
						strUrl = strUrlBase + encodeURIComponent(opts.viewname);
						strUrl += "?ReadViewEntries&Count=1&Start=" + positionwanted;
						keepgoing = true;
					} //--end key match check
				})
				.fail(function () {
					keepgoing = false;
					resultfound = false;
				});
				//if(counter > 5){ return false;}
				counter = counter + 1;
			} //--end while

			//-- check if any matching results were found
			if (resultfound) {
				//-- if we are only returning the document id we need go no further
				if (opts.returndocid) {
					result = docunidlist.join(opts.delimiter);
					//-- if so do we need to retrieve details from the form rather than the view
				} else if (useform) {
					//-- loop through the unid list of matching documents
					for (var i = 0; i < docunidlist.length; i++) {
						keepgoing = true;
						strUrl = strUrlBase + "0/" + docunidlist[i] + "/" + ((opts.alloweditmode) ? "?EditDocument" : "?OpenDocument");

						jQuery.ajax({
							url: strUrl,
							async: false,
							cache: false,
							type: "GET",
							dataType: "html"
						})
						.done(function (htmldata) {
							var jqhtmldata = jQuery(htmldata);
							var values = jqhtmldata.find("#" + opts.columnorfield);
							if (values.length == 0) {
								values = jqhtmldata.find("[name=" + opts.columnorfield + "]");
							}
							if (values.length > 0) {
								for (var v = 0; v < values.length; v++) {
									result += (result == "") ? "" : opts.delimiter;
									var tempval = "";
									if ('value' in values[v]) {
										tempval = values[v].value;
									}
									if (tempval === "") {
										tempval = jQuery(values[v]).text();
									}
									result += tempval;
								}
							}
						})
						.fail(function () {
							resultfound = false;
							keepgoing = false;
							if (!opts.failsilent) {
								alert("An error occurred retrieving data from the server.")
							}
						});
						if (!keepgoing) {
							break;
						}
					} //--end for loop
				}
			} //--end results found

			if (!resultfound) {
				if (!opts.failsilent) {
					alert("An error occurred retrieving data from the server.")
					result = false;
				} else {
					result = "";
				}
			}

			return result;
		}

	} //--end dbLookup
	
	this.dbCalculate= function(options) {
		var defaultOptns = {
			servername: "",
			appname: "",
			viewname: "",
			action: '',
			e: true,
			column: "",
			secure: "",
			criteria: []
		};
		var opts = $.extend({}, defaultOptns, options);

		var result = "";
		var column = parseInt(opts.column);
		if (column > 0) {
			column = column - 1; //Root xml index starts at 0, but this function will be used where user/developer thinks first column is 1.
		}

		if (opts.secure == "") {
			var strUrl = location.protocol + "//"
		} else {
			var strUrl = (opts.secure == "ON") ? "https://" : "http://";
		}

		if (opts.servername == "") {
			strUrl += window.location.host;
		} else {
			if (opts.servername.toLowerCase().indexOf("http") > -1) {
				var n = opts.servername.toLowerCase().indexOf("//");
				if (n > -1) {
					opts.servername = opts.servername.slice(n + 2);
				}
			}
			strUrl += opts.servername;
		}
		strUrl += (strUrl.charAt(strUrl.length - 1) == "/") ? "" : "/";
		var nsfpath = '';
		if (typeof(docInfo.NsfName) == undefined || docInfo.NsfName == '') {
			nsfpath = window.location.href;
			var n = nsfpath.toLowerCase().indexOf(window.location.host.toLowerCase());
			if (n > -1) {
				nsfpath = nsfpath.slice(n + window.location.host.length + 1);
			}
			var n = nsfpath.toLowerCase().indexOf(".php/Docova");
			if (n > -1) {
				nsfpath = nsfpath.slice(0, n + 11);
			}
		}
		else {
			nsfpath = docInfo.NsfName;
		}
		strUrl += (strUrl.charAt(strUrl.length - 1) == "/" || nsfpath.charAt(0) == "/") ? "" : "/";
		strUrl += nsfpath;
		strUrl += (strUrl.charAt(strUrl.length - 1) == "/") ? "" : "/";
		strUrl += 'DocumentServices?OpenAgent';
		
		if (opts.appname == '') {
			if (typeof(docInfo.AppID) != undefined && docInfo.AppID) {
				opts.appname = docInfo.AppID;
			}
		}

		var request = "<Request>";
		request += "<Action>DBCALCULATE</Action>";
		request += '<function>' + opts.action + '</function>';
		request += "<appname>" + opts.appname + "</appname>";
		request += "<viewname>" + opts.viewname + "</viewname>";
		request += "<column>" + opts.column + "</column>";
		request += '<criteria><![CDATA['+ ($.isEmptyObject(opts.criteria) ? '' : JSON.stringify(opts.criteria)) +']]></criteria>';
		request += "</Request>";
		
		var result;
		var resulttext;
		$.ajax({
			type: "POST",
			url: strUrl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				result = true;
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
					resulttext = resultxmlobj.first().text();
				}
			},
			error: function () {
				if (!opts.failsilent) {
					alert("An error occurred retrieving data from the server.")
				}
			}
		});

		return resulttext;
	}

	//TODO merge the codes of dbColumnNEW and dbColumn
	//dbColumn is working at the moment in appbuilder because when app builder is looking for
	//a list of agents or views etc...its using luApplication as the view name and there is a corresponding route in our routing table
	//this will not work for any other view as there won't be a route of that view name in our routing

	this.dbColumnNEW = function (options) {
		var defaultOptns = {
			servername: "",
			nsfname: "",
			viewname: "",
			urlsuffix: [],
			key: "",
			e: true,
			column: "",
			delimiter: ";",
			viewiscategorized: true,
			secure: "",
			maxreturn: 500,
			returnarray: false,
			htmllistbox: "",
			additionalvalues: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var result = "";
		var resultfound = false;

		if (opts.nsfname == "") {
			opts.nsfname = docInfo.appName;
		}

		var request = "<Request>";
		request += "<Action>DBCOLUMN</Action>";
		request += "<appname>" + opts.nsfname + "</appname>";
		request += "<viewname>" + opts.viewname + "</viewname>";
		request += "<key>" + opts.key + "</key>";
		request += "<delimiter>" + opts.delimiter + "</delimiter>";
		request += "<column>" + opts.column + "</column>";
		request += "</Request>";

		var processUrl = "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";

		var result;
		var resulttext;
		$.ajax({
			type: "POST",
			url: processUrl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				result = true;
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
					resulttext = resultxmlobj.first().text();
				}
			},
			error: function () {
				if (!opts.failsilent) {
					alert("An error occurred retrieving data from the server.")
				}
			}
		})

		return resulttext;

	} //--end dbColumnNEW

	/*-----------------------------------------------------------------------------------------
	 * Function: dbColumn
	 * Retrieves values from a view
	 * Parameters:
	 *  options:  value pair array consisting of;
	 *          servername: string - server host name - defaults to current host
	 *          nsfname: string - database path - defaults to current path
	 *          viewname: string - look up view
	 *          key: string - optional - key look up value to match against view contents
	 *          column: integer - column number to retrieve values from
	 *          delimiter: string - delimiter to use to concatenate multiple values. defaults to ;
	 *          secure: "ON" or "OFF" - specify ON to retrieve data via HTTPS
	 *          viewiscategorized: boolean - false by default.  Used in conjuction with the key attribute. If view is categorized then 'restricttoCategory' is used.
	 *          returnarray: boolean - false by default.  Used to return an array of values if true. Otherwise a string is returned.
	 *          maxreturn: integer - optional - maximum return value. defaults to 500
	 *          htmllistbox: string - optional - id of select field to update with retrieved values
	 *          addditionalvalues: string - optional - if htmllistbox specified a list of additional options to add to list
	 *  Returns:
	 *      string - value or concatenated values from specified column
	 *
	 *  Example:
	 *      Docova.Utils.dbColumn({"servername" : "www.acme.com", "nsfname" : "/docova/hrlib.nsf", "viewname" : "employeecontracts", "key" : "Jim Smith", "column" : "2", "delimiter" : ",", "alloweditmode" : false, "secure" : "OFF"});
	 *-------------------------------------------------------------------------------------------*/
	this.dbColumn = function (options) {
		var defaultOptns = {
			servername: "",
			nsfname: "",
			viewname: "",
			urlsuffix: [],
			key: "",
			e: true,
			column: "",
			delimiter: ";",
			viewiscategorized: false,
			secure: "",
			maxreturn: 500,
			returnarray: false,
			htmllistbox: "",
			additionalvalues: ""
		};
		var opts = $.extend({}, defaultOptns, options);

		var tempresult = new Array();

		var result = "";
		var column = parseInt(opts.column);
		if (column > 0) {
			column = column - 1; //Root xml index starts at 0, but this function will be used where user/developer thinks first column is 1.
		}

		if (opts.secure == "") {
			var strUrl = location.protocol + "//"
		} else {
			var strUrl = (opts.secure == "ON") ? "https://" : "http://";
		}

		if (opts.servername == "") {
			strUrl += window.location.host;
		} else {
			if (opts.servername.toLowerCase().indexOf("http") > -1) {
				var n = opts.servername.toLowerCase().indexOf("//");
				if (n > -1) {
					opts.servername = opts.servername.slice(n + 2);
				}
			}
			strUrl += opts.servername;
		}
		strUrl += (strUrl.charAt(strUrl.length - 1) == "/") ? "" : "/";
		if (opts.nsfname == "") {
			opts.nsfname = window.location.href;
			var n = opts.nsfname.toLowerCase().indexOf(window.location.host.toLowerCase());
			if (n > -1) {
				opts.nsfname = opts.nsfname.slice(n + window.location.host.length + 1);
			}
			var n = opts.nsfname.toLowerCase().indexOf(".nsf");
			if (n > -1) {
				opts.nsfname = opts.nsfname.slice(0, n + 4);
			}
		}
		strUrl += (strUrl.charAt(strUrl.length - 1) == "/" || opts.nsfname.charAt(0) == "/") ? "" : "/";
		strUrl += opts.nsfname;
		strUrl += (strUrl.charAt(strUrl.length - 1) == "/") ? "" : "/";
		strUrl += encodeURIComponent(opts.viewname);
		if (opts.urlsuffix.length) {
			strUrl += '/' + opts.urlsuffix.join('/');
		}
		strUrl += "?ReadViewEntries";
		strUrl += "&Count=" + opts.maxreturn.toString();
		if (opts.key != "" && opts.viewiscategorized) {
			strUrl += "&RestrictToCategory=" + opts.key;
			//if the view is categorized, then we are looking for the column -1 node since
			//categorized views don't include the category column in the readviewentries resultset
			if (!opts.htmllistbox)
				column--;
		}
		if (column.toString() != "0") {
			strUrl += "&ExpandView";
		}

		jQuery.ajax({
			url: strUrl,
			async: false,
			cache: false,
			type: "GET",
			dataType: "xml"
		})
		.done(function (xmldata) {
			jQuery(xmldata).find("viewentries").children().each(function () {

				jQuery(this).find("entrydata[columnnumber='0']").find("text,number,datetime").each(function (index) {
					if (opts.key == "" || opts.viewiscategorized || $(this).text() == opts.key) {
						//get to the entrydata node...we need to return the value from the appropriate column number
						//since we are starting at column no 0, loop until we get to the desired colno
						var parent = $(this).parent();
						for (var col = 0; col < column; col++) {
							parent = parent.next();
						}
						if (parent) {
							if (jQuery(parent).find("number").length > 0) {
								tempresult.push(Number(jQuery(parent).find("number").text()));
							} else if (jQuery(parent).find("datetime").length > 0) {
								var dateval = null;
								var tempdate = jQuery(parent).find("number").text();
								if (tempdate != "") {
									if (tempdate.length > 9) {
										dateval = new Date(Number(tempdate.slice(0, 4)), Number(tempdate.slice(4, 6)) - 1, Number(tempdate.slice(6, 8)), Number(tempdate.slice(9, 11)), Number(tempdate.slice(11, 13)), Number(tempdate.slice(13, 15)));
									} else {
										dateval = new Date(Number(tempdate.slice(0, 4)), Number(tempdate.slice(4, 6)) - 1, Number(tempdate.slice(6, 8)), 0, 0, 0);
									}
								}
								tempresult.push(dateval);
							} else {
								tempresult.push(jQuery(parent).find("text").text());
							}

						}
					}
				});
			})
		}).fail(function (jqXHR, textStatus, errorThrown) {
			if (jqXHR.status == 404) {
				result = "404"
			}
		});

		if (opts.htmllistbox) {
			var htmlstring = "";
			htmlstring += (opts.additionalvalues ? opts.additionalvalues + opts.delimiter : "");
			htmlstring += tempresult.join(opts.delimiter);

			$('#' + opts.htmllistbox)
			.find('option')
			.remove()
			.end()
			.append(htmlstring)
		} else {
			return (opts.returnarray ? tempresult : tempresult.join(opts.delimiter));
		}
	} //--end dbColumn

	/*----------------------------------------------------------------------------------------------
	 * Function: load
	 * Loads data from a url and puts the results of the 'get' into the element specified by the 'targetid' id
	 * Parameters:
	 *  options: value pair array consisting of;
	 *      url : the url for fetching the data
	 *      sourceid : the id of the element in the source data where we are grabbing content from
	 *      outerhtml: boolean - whether to load outer html of source id - defaults to false
	 *      targetid : the ID of the element you want to put the retrieved data into
	 *      onDone : the function to call when the data has been successfully retrieved
	 * Example:
	 *      Docova.Utils.load({"url": "www.acme.com/somepage.htm", "sourceid" : "divaddress", "targetid" : "address"});
	 *-------------------------------------------------------------------------------------------------*/
	this.load = function (options) {
		var defaultOptns = {
			url: "",
			targetid: "",
			sourceid: "",
			outerhtml: false,
			onDone: function () {}
		};
		var opts = $.extend({}, defaultOptns, options);

		//Determine if targetid is a string or object
		if (typeof opts.targetid == "string") {
			var targetelement = "#" + opts.targetid
		} else {
			var targetelement = opts.targetid //if an object is passed like a table cell
		}

		if (opts.outerhtml) {
			$.get(opts.url, function (response) {
				//$("#" + opts.targetid).html($(response).filter("#" + opts.sourceid)[0].outerHTML);
				$(targetelement).html($(response).filter("#" + opts.sourceid)[0].outerHTML);
				opts.onDone()
			})
		} else {
			$.get(opts.url, function (response) {
				//$("#" + opts.targetid).html($(response).filter('#' + opts.sourceid).html());
				$(targetelement).html($(response).filter('#' + opts.sourceid).html());
				//opts.onDone.call(this)
				opts.onDone()
			})
		}
	} //--end load


	/*----------------------------------------------------------------------------------------------
	 * Function: createDialog
	 * Creates a dialog box to the shown to the user.
	 * Parameters:
	 *  options:  value pair array consisting of;
	 *      id: string - name of the div containing the dialog
	 *      url: string - url of dialog source html
	 *      title: string - title of the dialog box
	 *      width: integer - width of dialog box - default 500
	 *      height: integer - height of dialog box - default 400
	 *      resizable: boolean - whether dialog is resizable or not - default to false
	 *      useiframe: boolean - whether to load dialog html source in iframe - defaults to false
	 *      onBeforeOpen: function - function to call before dialog is opened - defaults to calling
	 *                    eventDialogOpen function if available
	 *      onOpen: function (optional) - function to call on open of dialog
	 *      onClose: function (optional) - function to call on close of dialog - defaults to calling
	 *                  eventDialogClose function if available
	 *      autoopen: boolean - true to launch dialog immediately, false to hide until ShowDialog called
	 *      buttons: value pair array - array of button labels and javascript function call
	 *               eg. {"Do It": function() {someFunction()}, "Close" : function() {Docova.Utils.closeDialog({id:"divWelcomeDlg"})}}
	 *      scrolling: string - "no" - to prevent scrolling of content within dialog
	 *      closeonescape: boolean - true to close the dialog when Esc key pressed - defaults to false
	 *      defaultbutton: integer - index of button to give default focus
	 *      extendoverlay: document array - array of document objects that should have a grey overlay
	 *                     assigned when the dialog is open.  Used to block out other frames. Defaults to null
	 *      sourcedocument: (optional) domdocument - pointer to calling html dom document.  Will get stored in
	 *                       Docova.GlobalStorage[dlgid].sourcedocument
	 *      sourcewindow: (optional) domdocuments' window - pointer to calling html dom document parent window.  Will get stored in
	 *                       Docova.GlobalStorage[dlgid].sourcewindow
	 *      sourceid: (optional) string - if loading a page (not a form in an iframe) the id of the page fragment
	 *          eg: "formdata" for getting the html of a div with an id of "formdata" on the page.
	 * Example:
	 *      Docova.Utils.createDialog({id: "divWelcomeDlg", url: "\dlgWelcome?OpenForm", useiframe: true});
	 *-------------------------------------------------------------------------------------------------*/
	this.createDialog = function (options) {
		var parentobj = this;

		var defaultOptns = {
			id: "",
			url: "",
			dlghtml: "",
			title: "Default dialog title",
			width: 500,
			height: 400,
			resizable: false,
			useiframe: false,
			onBeforeOpen: function () {
				try {
					if (typeof opts.sourcewindow.eventDialogOpen === "function") {
						opts.sourcewindow.eventDialogOpen();
					}
				} catch (e) {}
			},
			onOpen: function () {},
			onClose: function () {
				try {
					if (typeof opts.sourcewindow.eventDialogClose === "function") {
						opts.sourcewindow.eventDialogClose();
					}
				} catch (e) {}
			},
			onResize: function () {},
			autoopen: true,
			buttons: [],
			scrolling: "no",
			closeonescape: false,
			defaultbutton: 0,
			extendoverlay: null,
			sourcedocument: document,
			sourcewindow: window,
			sourceid: ""
		};

		var opts = $.extend({}, defaultOptns, options);

		if (!$("#" + opts.id).length) {
			var dlgDiv = document.createElement('div');
			dlgDiv.id = opts.id;
			dlgDiv.style.display = "none";
			document.body.appendChild(dlgDiv);
		}

		$("#" + opts.id).css("overflow", (opts.useiframe || opts.scrolling == "no") ? "hidden" : "auto");

		if (opts.url != "") {
			if (opts.useiframe) {
				var iframeHeight = (jQuery.isNumeric(opts.height) ? opts.height : 400) - 95; //111/135
				var iframeWidth = (jQuery.isNumeric(opts.width) ? opts.width : 500) - 25;
				var dlgIFrame = document.createElement("iframe");
				var dlgIFrameID = opts.id + "IFrame";
				dlgIFrame.id = dlgIFrameID;
				dlgIFrame.width = iframeWidth;
				dlgIFrame.height = iframeHeight;
				dlgIFrame.style.border = 0;
				dlgIFrame.style.visibility = "hidden";
				dlgIFrame.frameBorder = 0;
				dlgIFrame.style.overflow = opts.scrolling;
				dlgIFrame.scrolling = opts.scrolling;
				dlgDiv.appendChild(dlgIFrame);
				dlgIFrame.src = "about:blank";
			} else {
				//$("#" + opts.id).load(opts.url+ " #formdata");
				if (opts.sourceid != "") {
					$("#" + opts.id).load(opts.url + " #" + opts.sourceid);
				} else {
					$("#" + opts.id).load(opts.url);
				}
			}
		} else {
			if (opts.dlghtml != "") {
				$("#" + opts.id).html(opts.dlghtml)
			}
		}

		if (!Docova.GlobalStorage[opts.id]) {
			Docova.GlobalStorage[opts.id] = {};
		}
		Docova.GlobalStorage[opts.id].sourcedocument = opts.sourcedocument;
		Docova.GlobalStorage[opts.id].sourcewindow = opts.sourcewindow;

		$("#" + opts.id).first().dialog({
			autoOpen: opts.autoopen,
			position: {
				my: "center top+50",
				at: "center top",
				of: window
			},
			title: opts.title,
			resizable: opts.resizable,
			width: opts.width,
			height: opts.height,
			closeOnEscape: opts.closeonescape,
			modal: true,
			buttons: opts.buttons,
			resize: function (event, ui) {
				opts.onResize.call(this, event, ui);
			},
			open: function () {
				if (opts.extendoverlay) {
					for (var o = 0; o < opts.extendoverlay.length; o++) {
						var targetelem = opts.extendoverlay[o];
						if (targetelem != null && targetelem != undefined) {
							var $overlay = jQuery('<div id="customOverlay" class="ui-overlay"><div class="ui-widget-overlay"></div></div>').hide().appendTo(targetelem.body);
							$overlay.fadeIn();
						}
					}
				}

				jQuery(this).siblings('.ui-dialog-buttonpane').find('button:eq(' + opts.defaultbutton + ')').focus();
				if (opts.url != "") {
					if (opts.useiframe) {
						var dlgIFrameID = opts.id + "IFrame";
						jQuery("#" + opts.id).find("#" + dlgIFrameID).attr("onload", "this.style.visibility='visible'").attr("src", opts.url);
					}
				}
				try {
					opts.onBeforeOpen.call(this);
				} catch (e) {};
				opts.onOpen.call(this);
			},
			close: function () {
				Docova.Utils.closeDialog(opts);
			}
		});

		var returnoptions = {
			"parent": parentobj,
			"id": opts.id,
			"options": opts,

			showDialog: function () {
				if (this.id == "") {
					return false;
				}
				if (!this.options) {
					return false;
				}
				this.parent.showDialog(this.id, this.options);
			},

			closeDialog: function () {
				if (this.id == "") {
					return false;
				}
				if (!this.options) {
					return false;
				}
				this.parent.closeDialog(this.options);
			},

			getUIDocument: function () {
				if (this.options.useiframe) {
					var iframwin = window.top.jQuery("#" + this.id + "IFrame")[0].contentWindow;
					if (iframwin)
						return iframwin.Docova.getUIDocument();
				}
			}
		};
		if (!Docova.GlobalStorage["obj" + opts.id])
			Docova.GlobalStorage["obj" + opts.id] = returnoptions;

		return returnoptions;
	} //--end createDialog

	/*----------------------------------------------------------------------------------------------
	 * Function: closeDialog
	 * Close a dialog.
	 * Parameters:
	 *  options - value pair array consisting of;
	 *      id: string - name of the div containing the dialog
	 *      url: string - url of dialog source html
	 *      useiframe: boolean - whether the dialog html source was loaded in iframe - defaults to false
	 *      fromparent: boolean - Use true if closing a dialog from a function in an iframe where the
	 *           dialog was opened by the parent. - defaults to false
	 *      extendoverlay: document array - array of document objects that should have a grey overlay
	 *             assigned when the dialog is open.  Used to block out other frames. Defaults to null
	 * Example:
	 *   Docova.Utils.closeDialog({id: "divWelcomeDlg", url: "\dlgWelcome?OpenForm", useiframe: true})
	 *------------------------------------------------------------------------------------------------*/
	this.closeDialog = function (options) {
		var defaultOptns = {
			id: "",
			url: "",
			useiframe: false,
			fromparent: false,
			extendoverlay: null
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.url != "") {
			if (opts.useiframe) {
				var dlgIFrameID = opts.id + "IFrame";
				jQuery("#" + opts.id).find("#" + dlgIFrameID).attr("src", "about:blank");
			}
		}

		if (opts.extendoverlay) {
			for (var o = 0; o < opts.extendoverlay.length; o++) {
				var targetelem = opts.extendoverlay[o];
				if (targetelem != null && targetelem != undefined) {
					jQuery("#customOverlay", targetelem).remove();
				}
			}
		}

		if (opts.fromparent) {
			parent.jQuery("#" + opts.id).dialog('destroy').remove();
		} else {
			jQuery("#" + opts.id).dialog('destroy').remove();
		}

		try {
			if (opts.onClose) {
				opts.onClose.call(this);
			}
		} catch (e) {

			var extra = !e.lineNumber ? '' : '\nline: ' + e.lineNumber;
			if (e.stack && console && console.log) {
				console.log("Error in dialog onClose event. Error Message: " + e.message + "\n" + extra);
				console.log(e.stack);
			}
		};

		if (Docova.GlobalStorage[opts.id]) {
			delete Docova.GlobalStorage[opts.id];
			delete Docova.GlobalStorage["obj" + opts.id];
		}
	} //--end closeDialog

	/*----------------------------------------------------------------------------------------------
	 * Function: showDialog
	 * Display a closed dialog.
	 * Parameters:
	 *  dlgname: string - name of dialog to display
	 * Example:
	 *   Docova.Utils.showDialog("divWelcomeDlg")
	 *------------------------------------------------------------------------------------------------*/
	this.showDialog = function (dlgname) {
		jQuery("#" + dlgname).dialog('open');
	} //--end showDialog


	/*----------------------------------------------------------------------------------------------
	 * Function: checkDate
	 * Validates a value as a date value
	 * Parameters:
	 *  datestring: value to verify as a valid date
	 * Returns:
	 *      boolean - true if input is a valid date, false otherwise
	 * Example:
	 *   Docova.Utils.checkDate(somedatestring);
	 *------------------------------------------------------------------------------------------------*/
	this.checkDate = function (datestring) {
		var testdate = new Date(datestring)
			if (testdate == "Invalid Date") {
				return false;
			} else {
				return true;
			}
	} //--end checkDate

	/*----------------------------------------------------------------------------------------------
	 * Function: allTrim
	 * Removes leading and trailing spaces from a value
	 * Parameters:
	 *  inval: string - value to have spaces trimmed from
	 * Returns:
	 *      string - input value with leading and trailing spaces removed
	 * Example:
	 *   Docova.Utils.allTrim(somestringvalue);
	 *------------------------------------------------------------------------------------------------*/
	this.allTrim = function (inval) {
		return $.trim(inval);
	} //--end allTrim

	/*-------------------
	 * Function: lTrim
	 * Removes spaces from the left of a string
	 *-------------------*/
	this.lTrim = function (str) {
		return str.replace(/^\s+/, "");
	}

	/*-------------------
	 * Function: rTrim
	 * Removes spaces from the right of a string
	 *-------------------*/
	this.rTrim = function (str) {
		return str.replace(/\s+$/, "");
	}

	/*-------------------
	 * Function: safe_quotes_js
	 * escape quotes in a string
	 *-------------------*/
	this.safe_quotes_js = function (str, escape, undo) {
		if (str === null || str === undefined) {
			return "";
		} else {
			if (!undo) {
				return str.replace(/'/g, (escape ? '&amp;apos;' : '&apos;')).replace(/"/g, (escape ? '&amp;quot;' : '&quot;'));
			} else {
				if (escape) {
					return str.replace(/&amp;apos;/g, '\'').replace(/&amp;quot;/g, '"');
				} else {
					return str.replace(/&apos;/g, '\'').replace(/&quot;/g, '"');
				}
			}
		}
	}

	/*-------------------
	 * Function:
	 * Removes leading and trailing char from a string
	 * eg: remove leading and trailing quotes from a string
	 * inchar = character to remove
	 * instr = string to remove char from
	 *-------------------*/
	this.trimFirstAndLast = function (options) {
		var defaultOptns = {
			inchar: "",
			instr: ""
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.inchar == "") {
			return opts.instr
		}

		var newstring = opts.instr;
		if (newstring.charAt(0) == opts.inchar) {
			newstring = newstring.substring(1, newstring.length - 1);
		}
		if (newstring.charAt(newstring.length - 1) == opts.inchar) {
			newstring = newstring.substring(0, newstring.length);
		}
		return newstring;
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: unique
	 * Returns an array or delimited string with all duplicate entries removed
	 * Parameters:
	 *  options - value pair array consisting of;
	 *      inputarr: array - array of values
	 *      inputstr: string - concatenated string
	 *      delimiterin: string - delimiter used in inputstr, defaults to ;
	 *      returnarray: boolean - true to return output as an array, defaults to false
	 *      delimiterout: string - delimiter used in output string if returnarray is false
	 * Returns:
	 *      delimited string or array
	 * Example:
	 *   Docova.Utils.unique({"inputstr": "A,B,B,C,D,C", "delimiterin" : ",", "delimiterout" : ","});
	 *------------------------------------------------------------------------------------------------*/
	this.unique = function (options) {
		var defaultOptns = {
			inputstr: "",
			inputarr: [],
			delimiterin: ";",
			delimiterout: ";",
			returnarray: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.inputstr == "" && opts.inputarr.length == 0) {
			return
		}

		if (opts.inputstr != "") {
			opts.inputarr = opts.inputstr.split(opts.delimiterin);
		}

		var arr = opts.inputarr.reduce(function (p, c) {
				if (p.indexOf(c) < 0)
					p.push(c);
				return p;
			}, []);

		var retVal = opts.returnarray ? arr : arr.join(opts.delimiterout);
		return retVal;
	} //-end unique

	/*----------------------------------------------------------------------------------------------
	 * Function: formatNumber
	 * Returns formatted number string.
	 * Parameters:
	 *  options - value pair array consisting of;
	 *		numstring: string - string to be formatted
	 *      numdecimals: string - number of decimal places
	 *      decimalsymbol: string - punctuation sym bol for decimal point
	 *      thousandsseparator: string - thousands separator
	 *		unformat: boolean - true or false, unformats numstring
	 * Returns:
	 *      formatted number string
	 * Example:
	 *   Returns 1,000.10
	 *   Docova.Utils.formatNumber({
	 *		"numstring" " "1000.1",
	 *		"numdecimals" : 2,
	 *		"decimalsymbol" : ".",
	 *		"thousandsseparator : ","
	 *		});
	 * Example:
	 *   Returns 123456.2
	 *   Docova.Utils.formatNumber({ "numstring" : "123,456.20", "unformat" : true })
	 *------------------------------------------------------------------------------------------------*/
	this.formatNumber = function (options) {
		var defaultOptns = {
			numstring: "0",
			numdecimals: 0,
			decimalsymbol: ".",
			thousandsseparator: "",
			unformat: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (typeof opts.numdecimals == "string") {
			opts.numdecimals = parseInt(opts.numdecimals, 10);
		}
		if ($.trim(opts.numstring) == "") { //if numstring is empty, default to zero
			opts.numstring = "0";
		}

		if (opts.unformat == true) { //if unformat is true, return unformatted string
			return numbro().unformat(opts.numstring);
		}

		//custom unformat of number to get it to work, not same as numbro().unformat() method
		var numarr = opts.numstring.split(opts.decimalsymbol)
			if (numarr.length > 1) {
				var ufnum = numarr[0] + "." + numarr[1]
			} else {
				var ufnum = numarr[0]
			}

			//set a numbro culture - makes any thousands or decimal symbol work
			numbro.culture("custom", {
				delimiters: {
					thousands: opts.thousandsseparator,
					decimal: opts.decimalsymbol
				}
			});
		numbro.culture("custom");

		var defFormat = "0,0." //add decimal zeros after this
			for (var x = 1; x <= opts.numdecimals; x++) {
				defFormat += "0"
			}

			var numval = Number(ufnum) //convert val string to js number
			return numbro(numval).format(defFormat);
	}

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: convertStringToDate
	 * Converts a date string into a date object
	 * Inputs: datestring - string containing date in "dd mm yyyy" or "mm dd yyyy" or "yyyy mm dd" order
	 *                   and using one of the following separators ( / \ . -)
	 *             sourceformat - (optional) - string containing source date format
	 * Returns: date - date value (no time component)
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.convertStringToDate = function (datestring, sourceformat) {
		var result = null;
		var dateformat = (docInfo && docInfo.SessionDateFormat && docInfo.SessionDateFormat != "") ? docInfo.SessionDateFormat : "";
		var simpledateformat = dateformat.toLowerCase().replace(/\\|\/|\.|\-/gi, ' ');
		var outputdateseparator = (
			dateformat.indexOf("/") > -1 ? "/" :
			(dateformat.indexOf("\\") > -1 ? "\\" :
				(dateformat.indexOf("-") > -1 ? "-" :
					(dateformat.indexOf(".") > -1 ? "." :
						""
					)
				)
			)
		);
		var dateportion = "";
		var timeportion = "";
		var pos = datestring.indexOf(" ");
		if (pos > -1) {
			dateportion = datestring.slice(0, pos);
			timeportion = datestring.slice(pos + 1);
		} else {
			dateportion = datestring;
		}
		//-- look for a date separator in the input
		var dateseparator = (
			dateportion.indexOf("/") > -1 ? "/" :
			(dateportion.indexOf("\\") > -1 ? "\\" :
				(dateportion.indexOf("-") > -1 ? "-" :
					(dateportion.indexOf(".") > -1 ? "." :
						""
					)
				)
			)
		);
		
		//-- was a date separator found, if not probably not a valid date
		if (dateseparator != "") {
			var yval;
			var mval;
			var dval;
			var datearray = dateportion.split(dateseparator);
			//-- we potentially have three date components
			if (datearray.length == 3) {
				//-- look for a four digit year at the start if so should be in yyyy mm dd order
				if (datearray[0].length == 4) {
					yval = parseInt(datearray[0], 10);
					mval = parseInt(datearray[1], 10);
					dval = parseInt(datearray[2], 10);
					//-- look for a four digit year at the end
				} else if (datearray[2].length === 4) {
					yval = parseInt(datearray[2], 10);
					//-- try to identify mm and dd based on values
					if (parseInt(datearray[0], 10) > 12 && parseInt(datearray[1], 10) <= 12) {
						mval = parseInt(datearray[1], 10);
						dval = parseInt(datearray[0], 10);
					} else if (parseInt(datearray[1], 10) > 12 && parseInt(datearray[0], 10) <= 12) {
						mval = parseInt(datearray[0], 10);
						dval = parseInt(datearray[1], 10);
					} else {
						if (typeof sourceformat != "undefined" && sourceformat != "") {
							var tempformat = sourceformat.toLowerCase().replace(/\\|\/|\.|\-/gi, ' ');
							if (tempformat == "dd mm yyyy" || tempformat == "dd mm yy") {
								mval = parseInt(datearray[1], 10);
								dval = parseInt(datearray[0], 10);
							} else if (tempformat == "mm dd yyyy" || tempformat == "mm dd yy") {
								mval = parseInt(datearray[0], 10);
								dval = parseInt(datearray[1], 10);
							}
						} else {
							//-- not clear what order mm and dd are so we are going to need to guess
							if (dateseparator == ".") {
								//-- if dot separator assume european format which is dd mm yyyy
								mval = parseInt(datearray[1], 10);
								dval = parseInt(datearray[0], 10);
							} else {
								//-- still guessing so lets use the target date format as a guess
								if (simpledateformat == "dd mm yyyy" || simpledateformat == "dd mm yy") {
									mval = parseInt(datearray[1], 10);
									dval = parseInt(datearray[0], 10);
								} else if (simpledateformat == "mm dd yyyy" || simpledateformat == "mm dd yy") {
									mval = parseInt(datearray[0], 10);
									dval = parseInt(datearray[1], 10);
								}
							}
						}
					}
				}
				if(mval >= 1 && mval <= 12 && dval >= 1 && dval <= 31){
					if (!isNaN(yval) && !isNaN(mval) && !isNaN(dval)) {
						result = new Date(yval, mval - 1, dval);
					}
				}
			}
		}

		return result;
	} //--end convertStringToDate
	
	
	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: convertStringToDateTime
	 * Converts a date/time string into a datetime object
	 * Inputs: datetimestring - string containing date or datetime value
	 *             sourceformat - (optional) - string containing source date format
	 * Returns: datetime - date time value
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.convertStringToDateTime = function (datetimestring, sourceformat) {
		var result = null;
	
		var timeportion = "";
		datetimestring = $.trim(datetimestring);
		var dateval = this.convertStringToDate(datetimestring, sourceformat);
		var pos = datetimestring.indexOf(" ");
		if (dateval !== null && pos > -1) {
			timeportion = datetimestring.slice(pos + 1);
		} else if(dateval === null){
			timeportion = datetimestring;			
		}
		
		var yval = 0;
		var mval = 0;
		var dval = 0;
		var hval = 0;
		var minval = 0;
		var sval = 0;
		
		if(dateval !== null){
			yval = dateval.getFullYear();
			mval = dateval.getMonth() + 1;
			dval = dateval.getDate();
		}
		
		if(timeportion != ""){
			var period = "";
			//-- look if there is an am/pm attribute
			pos = timeportion.toUpperCase().indexOf("AM");
			if(pos > -1){
				period = "AM";
				timeportion = $.trim(timeportion.slice(0, pos));
			}else{
				pos = timeportion.toUpperCase().indexOf("PM");
				period = "PM";
				timeportion = $.trim(timeportion.slice(0, pos));				
			}


			var timearray = timeportion.split(":");
			if(timearray.length > 0){
				hval = parseInt(timearray[0], 10);
				if(isNaN(hval)){
					hval = 0;
				}
			}
			if(timearray.length > 1){
				minval = parseInt(timearray[1], 10);
				if(isNaN(minval)){
					minval = 0;
				}
			}			
			if(timearray.length > 2){
				sval = parseInt(timearray[2], 10);
				if(isNaN(sval)){
					sval = 0;
				}
			}			
			
			//-- see if we need to adjust from 12 hour to 24 hour
			if(hval > 0 && hval < 12 && period == "PM"){
				hval = hval + 12;
			}else if(hval == 12 && period == "AM"){
				hval = 0;
			}
		}

		if((yval > 0 && mval > 0 && dval > 0) || (hval > 0 || minval > 0 || sval > 0) ){
				result = new Date(yval, mval - 1, dval, hval, minval, sval);
		}

		return result;
	} //--end convertStringToDateTime	
	

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: convertDateFormat
	 * Converts a date string or date object into a string formatted to the users date display preferences
	 * Inputs: dateval - string or date time object (if string date must be in "dd mm yyyy" or "mm dd yyyy" or "yyyy mm dd" order
	 *                   and using one of the following separators ( / \ . -)
	 *         sourceformat - string (optional) - if date supplied as string sourceformat can be optionally supplied to give a
	 *                        hint as to the formatting of the string (eg. dd/mm/yyyy mm/dd/yyyy)
	 *         excludetime - boolean (optional) - if set to true, strips off any time component of the supplied date
	 * Returns: string - date value (no time component) formatted into users date display preference
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.convertDateFormat = function (dateval, sourceformat, excludetime) {
		var result = "";
		var dateformat = (docInfo && docInfo.SessionDateFormat && docInfo.SessionDateFormat != "") ? docInfo.SessionDateFormat : "";
		var simpledateformat = dateformat.toLowerCase().replace(/\\|\/|\.|\-/gi, ' ');
		var outputdateseparator = (
			dateformat.indexOf("/") > -1 ? "/" :
			(dateformat.indexOf("\\") > -1 ? "\\" :
				(dateformat.indexOf("-") > -1 ? "-" :
					(dateformat.indexOf(".") > -1 ? "." :
						""
					)
				)
			)
		);

		if ((typeof dateval == "string") && dateval != "") {
			var dateportion = "";
			var timeportion = "";
			var pos = dateval.indexOf(" ");
			if (pos > -1) {
				dateportion = dateval.slice(0, pos);
				timeportion = dateval.slice(pos + 1);
			} else {
				dateportion = dateval;
			}
			//-- look for a date separator in the input
			var dateseparator = (
				dateportion.indexOf("/") > -1 ? "/" :
				(dateportion.indexOf("\\") > -1 ? "\\" :
					(dateportion.indexOf("-") > -1 ? "-" :
						(dateportion.indexOf(".") > -1 ? "." :
							""
						)
					)
				)
			);
			//-- was a date separator found, if not probably not a valid date
			if (dateseparator != "") {
				var yval;
				var mval;
				var dval;
				var hval;
				var nval;
				var sval;
				var datearray = dateportion.split(dateseparator);
				//-- we potentially have three date components
				if (datearray.length == 3) {
					//-- look for a four digit year at the start if so should be in yyyy mm dd order
					if (datearray[0].length == 4) {
						yval = parseInt(datearray[0], 10);
						mval = parseInt(datearray[1], 10);
						dval = parseInt(datearray[2], 10);
						//-- look for a four digit year at the end
					} else if (datearray[2].length === 4) {
						yval = parseInt(datearray[2], 10);
						//-- try to identify mm and dd based on values
						if (parseInt(datearray[0], 10) > 12 && parseInt(datearray[1], 10) <= 12) {
							mval = parseInt(datearray[1], 10);
							dval = parseInt(datearray[0], 10);
						} else if (parseInt(datearray[1], 10) > 12 && parseInt(datearray[0], 10) <= 12) {
							mval = parseInt(datearray[0], 10);
							dval = parseInt(datearray[1], 10);
						} else {
							if (sourceformat && sourceformat != "") {
								var tempformat = sourceformat.toLowerCase().replace(/\\|\/|\.|\-/gi, ' ');
								if (tempformat == "dd mm yyyy" || tempformat == "dd mm yy") {
									mval = parseInt(datearray[1], 10);
									dval = parseInt(datearray[0], 10);
								} else if (tempformat == "mm dd yyyy" || tempformat == "mm dd yy") {
									mval = parseInt(datearray[0], 10);
									dval = parseInt(datearray[1], 10);
								}
							} else {
								//-- not clear what order mm and dd are so we are going to need to guess
								if (dateseparator == ".") {
									//-- if dot separator assume european format which is dd mm yyyy
									mval = parseInt(datearray[1], 10);
									dval = parseInt(datearray[0], 10);
								} else {
									//-- still guessing so lets use the target date format as a guess
									if (simpledateformat == "dd mm yyyy" || simpledateformat == "dd mm yy") {
										mval = parseInt(datearray[1], 10);
										dval = parseInt(datearray[0], 10);
									} else if (simpledateformat == "mm dd yyyy" || simpledateformat == "mm dd yy") {
										mval = parseInt(datearray[0], 10);
										dval = parseInt(datearray[1], 10);
									}
								}
							}
						}
					}
					if (!isNaN(yval) && !isNaN(mval) && !isNaN(dval)) {
						var ystr = ("000" + yval.toString()).slice(-4);
						var mstr = ("0" + mval.toString()).slice(-2);
						var dstr = ("0" + dval.toString()).slice(-2);
						if (simpledateformat == "yyyy mm dd" || simpledateformat == "yy mm dd") {
							result = ystr + outputdateseparator + mstr + outputdateseparator + dstr;
						} else if (simpledateformat == "mm dd yyyy" || simpledateformat == "mm dd yy") {
							result = mstr + outputdateseparator + dstr + outputdateseparator + ystr;
						} else if (simpledateformat == "dd mm yyyy" || simpledateformat == "dd mm yy") {
							result = dstr + outputdateseparator + mstr + outputdateseparator + ystr;
						}
					}
					if (!excludetime) {
						result += (result === "" ? "" : " ") + timeportion;
					}
				}
			}

		} else if (dateval instanceof Date) {
			yval = dateval.getFullYear();
			mval = dateval.getMonth() + 1;
			dval = dateval.getDate();
			hval = dateval.getHours();
			nval = dateval.getMinutes();
			sval = dateval.getSeconds();

			if (!isNaN(yval) && !isNaN(mval) && !isNaN(dval)) {
				var ystr = ("000" + yval.toString()).slice(-4);
				var mstr = ("0" + mval.toString()).slice(-2);
				var dstr = ("0" + dval.toString()).slice(-2);
				if (simpledateformat == "yyyy mm dd" || simpledateformat == "yy mm dd") {
					result = ystr + outputdateseparator + mstr + outputdateseparator + dstr;
				} else if (simpledateformat == "mm dd yyyy" || simpledateformat == "mm dd yy") {
					result = mstr + outputdateseparator + dstr + outputdateseparator + ystr;
				} else if (simpledateformat == "dd mm yyyy" || simpledateformat == "dd mm yy") {
					result = dstr + outputdateseparator + mstr + outputdateseparator + ystr;
				}
			}
			if (!excludetime && !isNaN(hval) && !isNaN(nval) && !isNaN(sval)) {
				result += (result !== "" ? " " : "") + hval.toString() + ":" + ("0" + nval.toString()).slice(-2) + ":" + ("0" + sval.toString()).slice(-2);
			}
		}

		return result;
	} //--end convertDateFormat

	/*----------------------------------------------------------------------------------------------
	 * Function: formatDate
	 * Formats a date or date/time using the applied format masks.
	 *
	 *------------------------------------------------------------------------------------------------*/
	this.formatDate = function (date, mask, utc) {
		var token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) {
				val = "0" + val;
			}
			return val;
		};
		var masks = {
			"default": "ddd mmm dd yyyy HH:MM:ss",
			shortDate: "m/d/yy",
			mediumDate: "mmm d, yyyy",
			longDate: "mmmm d, yyyy",
			fullDate: "dddd, mmmm d, yyyy",
			shortTime: "h:MM TT",
			mediumTime: "h:MM:ss TT",
			longTime: "h:MM:ss TT Z",
			isoDate: "yyyy-mm-dd",
			isoTime: "HH:MM:ss",
			isoDateTime: "yyyy-mm-dd'T'HH:MM:ss",
			isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
		};
		var i18n = {
			dayNames: [
				"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
				"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
			],
			monthNames: [
				"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
				"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
			]
		};

		// Regexes and supporting functions are cached through closure
		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) {
			throw SyntaxError("invalid date");
		};

		//mask = String(dF.masks[mask] || mask || dF.masks["default"]);
		mask = String(masks[mask] || mask || masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var _ = utc ? "getUTC" : "get",
		d = date[_ + "Date"](),
		D = date[_ + "Day"](),
		m = date[_ + "Month"](),
		y = date[_ + "FullYear"](),
		H = date[_ + "Hours"](),
		M = date[_ + "Minutes"](),
		s = date[_ + "Seconds"](),
		L = date[_ + "Milliseconds"](),
		o = utc ? 0 : date.getTimezoneOffset(),
		flags = {
			d: d,
			dd: pad(d),
			ddd: i18n.dayNames[D],
			dddd: i18n.dayNames[D + 7],
			m: m + 1,
			mm: pad(m + 1),
			mmm: i18n.monthNames[m],
			mmmm: i18n.monthNames[m + 12],
			yy: String(y).slice(2),
			yyyy: y,
			h: H % 12 || 12,
			hh: pad(H % 12 || 12),
			H: H,
			HH: pad(H),
			M: M,
			MM: pad(M),
			s: s,
			ss: pad(s),
			l: pad(L, 3),
			L: pad(L > 99 ? Math.round(L / 10) : L),
			t: H < 12 ? "a" : "p",
			tt: H < 12 ? "am" : "pm",
			T: H < 12 ? "A" : "P",
			TT: H < 12 ? "AM" : "PM",
			Z: utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
			o: (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
			S: ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
		};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: colorPicker
	 * Display a color picker dialog.
	 * Parameters:
	 *  e: calling element
	 *  fieldName : string (optional) - field to update with selected value
	 *  cb : function (optional) - callback function to return value to
	 * Returns:
	 *  Updates specified field with selected color value
	 * Example:
	 *   Docova.Utils.colorPicker(this, "BackgroundColor")
	 *   Docova.Utils.colorPicker(this, "", function(chosencolor){alert("Color:" + chosencolor})
	 *------------------------------------------------------------------------------------------------*/
	this.colorPicker = function (e, fieldName, cb) {

		var dlgDiv = document.createElement('div');
		$(dlgDiv).prop('id', 'ColorBox');
		$(dlgDiv).css('border', '2px solid black');
		$(dlgDiv).css("display", "none");
		$(dlgDiv).css("position", "absolute");
		$(dlgDiv).css("zIndex", "1000");
		document.body.appendChild(dlgDiv);
		if (_DOCOVAEdition == "SE") {
			var url = docInfo.PortalWebPath + "/colorPicker.html";
		} else {
			var url = docInfo.PortalWebPath + "/colorPalette.html";
		}
		$("#ColorBox").load(url + " #formdata", function () {

			//Get position of cursor
			var posx = 0;
			var posy = 0;
			if (e.pageX || e.pageY) {
				posx = e.pageX;
				posy = e.pageY;
			} else if (e.clientX || e.clientY) {
				posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
				posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
			}
			//Determine any need to shift the color palette div left or up.
			var winWidth = $(window).width();
			var winHeight = $(window).height();
			var paletteWidth = 200;
			var paletteHeight = 150;
			if ((e.pageX + paletteWidth) >= winWidth) {
				var shiftleft = (e.pageX + paletteWidth) - winWidth;
				posx = posx - shiftleft;
			}
			if ((e.pageY + paletteHeight) >= winHeight) {
				var shiftup = (e.pageY + paletteHeight) - winHeight;
				posy = posy - shiftup;
			}

			$("#ColorBox tbody tr td").on("mouseover", function () {
				$(this).css("border", "1px solid black");
			});
			$("#ColorBox tbody tr td").on("mouseout", function () {
				$(this).css("border", "1px solid white");
			});
			$("#ColorBox tbody tr td").on("mousedown", function () {
				var cell = this;
				if ($(cell).html() == "X") {
					$("#ColorBox").remove();
					return;
				}
				var colorval = $(cell).css('background-color')
					if (colorval.indexOf("#") == "-1") { //extra code converts rgb backgroundColor to hex value in FF and Chrome
						var parts = colorval.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
						delete (parts[0]);
						for (var i = 1; i <= 3; ++i) {
							parts[i] = parseInt(parts[i]).toString(16);
							if (parts[i].length == 1)
								parts[i] = '0' + parts[i];
						}
						colorval = '#' + parts.join('');
					}
					if (fieldName && fieldName != "") {
						document.getElementById(fieldName).value = colorval;
						if (document.getElementById("display" + fieldName) != null) {
							document.getElementById("display" + fieldName).style.backgroundColor = colorval;
						}
					}
					if (cb && typeof cb == "function") {
						cb(colorval);
					}
					$("#ColorBox").remove();
			});
			$(dlgDiv).css('left', posx);
			$(dlgDiv).css('top', posy);
			$(dlgDiv).css("display", "block");
		}); //---end load function
	} //--end colorPicker


	/**
	 * Popup menu. Functions are grouped under 'Menu'. Example:
	 * Docova.Utils.menu({
	 *  delegate : $(e.target).parent(), //will link the menu to the clicked button
	 *  width : 200,
	 *  menus : [
	 *      { title : 'Select All', action : 'selectAll()' },
	 *      { title : 'Deselect All', action : 'deselectAll()' },
	 *      { separator : true },
	 *      { title : 'Delete', action : 'delete()', disabled : true },
	 *  ]
	 * });
	 * @author javad rahimi
	 */
	this.menu = function (options) {
		var defaultOptns = {
			menuid: "",
			delegate: "",
			addclass: "",
			menus: new Array(),
			position: 'default',
			shiftX: 1,
			shiftY: 2,
			width: 150
		};

		var opts = $.extend({}, defaultOptns, options),
		items = [];
		//opts.menuid = $.trim(opts.menuid) ? opts.menuid : ('docova_' + Math.round(Math.random() * 10000));
		opts.menuid = $.trim(opts.menuid) ? opts.menuid : ('docova_menu');
		$('#' + opts.menuid).remove();

		if (opts.position == 'default' || !opts.position) {
			opts.position = {
				my: "left top",
				at: "left bottom",
				of: opts.delegate
			};
		} else if (opts.position == 'XandY') {
			//opts.position = { my: "left+1 bottom-2", of: opts.delegate, collision: "fit" };
			opts.position = {
				my: "left-" + opts.shiftX + " bottom-" + opts.shiftY,
				of: opts.delegate
			};
		}

		if (opts.menus.length < 1) {
			return;
		}
		for (var x = 0; x < opts.menus.length; x++) {
			items.push('<li');
			if (opts.menus[x].separator) {
				items.push('>-</li>');
				continue;
			}
			if (opts.menus[x].disabled) {
				items.push(' class="ui-state-disabled"');
			} else {
				if ($.trim(opts.menus[x].addClass)) {
					items.push(' class="' + opts.menus[x].addClass + '"');
				}
				if ($.trim(opts.menus[x].action)) {
					var tempaction = opts.menus[x].action;
					tempaction = tempaction.replace(/'/g, '&apos;').replace(/"/g, '&quot;');
					items.push(' onclick="' + tempaction + '"');
				}
			}
			if (opts.menus[x].menus) {
				items.push(' issubmenu="true"');
			}
			items.push('>');
			if ($.trim(opts.menus[x].itemicon)) {
				items.push('<span class="ui-icon ' + opts.menus[x].itemicon + '"></span>');
			}
			if (opts.menus[x].menus) {
				items.push(opts.menus[x].title);
				items.push(this.buildSubmenus(opts.menus[x].menus));
				items.push('</li>');
			} else {
				items.push(($.trim(opts.menus[x].title) ? opts.menus[x].title : '-') + '</li>');
			}
		}

		var menuContainer = $('<ul style="width:' + opts.width + 'px" id="' + opts.menuid + '" ' + (opts.addclass != "" ? 'class="' + opts.addclass + '"' : '') + '></ul>')
			.hide()
			.append(items.join(''));
		$(document.body).append(menuContainer);

		$('#' + opts.menuid).menu({
			select: function (e, ui) {
				if (ui.item.attr('issubmenu') != "true") {
					$('#' + opts.menuid).remove();
				}
			}
		})
		.show({
			"duration": 10,
			"complete": function () {
				$('#' + opts.menuid).on('mouseleave', function (e) {
					$('#' + opts.menuid).remove();
				});

				//for ipad

				$(document).bind("touchstart", function (e) {
					if ($('#' + opts.menuid).has(e.target).length < 1) {

						if (opts.delegate.target) {

							$('#' + opts.menuid).remove();
						} else if ($(opts.delegate).has(e.target).length < 1) {

							$('#' + opts.menuid).remove();
						}
					}
				});
			}
		})

		.position(opts.position);

		$('#' + opts.menuid + ' .ui-menu').css({
			'font-weight': 'normal',
			'width': (opts.width + 'px')
		});
	}

	this.buildSubmenus = function (menus) {
		var output = [];
		if (menus.length < 1) {
			return '';
		}
		output.push('<ul>');
		for (var x = 0; x < menus.length; x++) {
			output.push('<li');
			if (menus[x].separator) {
				output.push('>-</li>');
				continue;
			}
			if (menus[x].disabled) {
				output.push(' class="ui-state-disabled"');
			}
			if ($.trim(menus[x].addClass)) {
				output.push(' class="' + menus[x].addClass + '"');
			}
			if ($.trim(menus[x].action)) {
				output.push(' onclick="' + menus[x].action + '"');
			}
			if (menus[x].menus) {
				output.push(' isSubmenu="true"');
			}
			output.push('>');
			if ($.trim(menus[x].itemicon)) {
				output.push('<span class="ui-icon ' + menus[x].itemicon + '"></span>');
			}
			if (menus[x].menus) {
				output.push(menus[x].title);
				output.push(this.buildSubmenus(menus[x].menus));
				output.push('</li>');
			} else {
				output.push(($.trim(menus[x].title) ? menus[x].title : '-') + '</li>');
			}
		}
		output.push('</ul>');
		return output.join('');
	}

	/**
	 * Helper function to determine the top/left
	 * positions of the menu based on any frame structure
	 */
	this.calculateMenuPosition = function (i_oElem, win) {
		var cTL = {
			nLeft: 0,
			nTop: 0
		}
		var oElem = i_oElem.get(0);
		var oWindow = win;

		do {
			cTL.nLeft += oElem.offsetLeft;
			cTL.nTop += oElem.offsetTop;
			oElem = oElem.offsetParent;

			if (oElem == null) { // If we reach top of the ancestor hierarchy
				oElem = oWindow.frameElement; // Jump to IFRAME Element hosting the document
				if (oElem && oElem.parentElement.tagName.toLowerCase() == "frameset") {

					if (oElem.offsetTop != oElem.parentElement.offsetTop) {
						cTL.nTop += oElem.parentElement.offsetTop;
					}
					if (oElem.offsetLeft != oElem.parentElement.offsetLeft) {
						cTL.nLeft += oElem.parentElement.offsetLeft;
					}

				}
				oWindow = oWindow.parent; // and switching current window to 1 level up
			}
		} while (oElem)

		return cTL;
	} //--end calculateMenuPosition

	/**
	 * Popup different types of menu/outline. Functions are grouped under 'Menu'. Example:
	 * Docova.Utils.generateMenu({
	 *  style : 'cascade', //accept 3 types for now: cascade, pane, dropdown
	 *  parent : $(this), //clicked dom element
	 *  sourcewindow : window,
	 *  items : outlineJson.Items[index] // array of menu items created by app outline builder
	 *  position: {
	 *  	my : 'left top'
	 *  	at : 'left bottom+25'
	 *  	collision : 'flip flip'
	 *  }
	 * });
	 * @author javad rahimi
	 */
	this.generateMenu = function (options) {
		var defaultOptns = {
			menuid: '',
			style: 'cascade',
			themed: true,
			width: 250,
			parent: null,
			addclass: '',
			depth: 1,
			xadjustment: 0,
			yadjustment: 0,
			styleobj: null,
			isembedded: false,
			sourcewindow: window,
			items: [],
			position: {
				my: 'top left',
				at: 'right bottom',
				collision: 'none none'
			}
		}

		var opts = $.extend({}, defaultOptns, options);
		var html = '';
		var itemsclass = opts.themed ? "" : " d-ui-state-default ";
		var headerclass = opts.themed ? "" : " d-ui-widget-header "
		if (opts.isembedded === false) {
			html = '<div ';
			opts.menuid = $.trim(opts.menuid) ? opts.menuid : ('docova_outlinemenu');

			$('.docova_menu_container').remove();
			html += 'id="' + opts.menuid + '" ';
			html += 'class="docova_menu_container docova_cascade" ';
			html += 'style="width:' + (opts.width) + 'px;" ';
			html += '>';
			if ( opts.depth == 1){
				//html += "<div class='cMenuHeading'>" + opts.parent.text() + "</div>";
			}
			
		}

		if (opts.style !== 'pane') {
			
			html += '<ul class="docova_menu ' + itemsclass;
			if (opts.isembedded === true) {
				html += 'hidden ';
			}
			html += 'dm_shadow ';
			if (opts.addclass) {
				html += opts.addclass;
			}
			html += '">';
			for (var item in opts.items) {
				html += '<li class="' + (opts.items[item].type == 'header' || (opts.items[item].Items && opts.items[item].Items.length) ? 'docova_menu_header ' + headerclass : itemsclass) + '" ';
				html += 'emenuitemtype="' + (opts.items[item].type == 'header' ? 'H' : 'M') + '" ';
				html += 'etarget="' + opts.items[item].etarget + '" ';
				html += 'isSpacer="' + opts.items[item].isSpacer + '" ';
				html += 'eelement="' + this.safe_quotes_js(opts.items[item].eelement) + '" ';
				html += 'etype="' + opts.items[item].etype + '" ';
			
				html += 'eviewtype="' + opts.items[item].eviewtype + '">';
				if (opts.items[item].icontitle && opts.items[item].type != 'header') {
					html += '<span class="nicon ';
					html += opts.items[item].icontitle ? 'far fa-1x ' + opts.items[item].icontitle + '" ' : '" ';
					html += 'style="';
					html += opts.items[item].iconfontsize ? 'font-size: ' + opts.items[item].iconfontsize + '; ' : '';
					html += opts.items[item].iconcolor ? 'color:' + opts.items[item].iconcolor + ';"' : '"';

					html += ' ></span>';
				} else if ((opts.items[item].type == 'header' || (opts.items[item].Items && opts.items[item].Items.length))) {
					html += '<span class="far fa-1x fa-chevron-right docova_chevron"></span>';
				}
				html += '<a class = "itemlabel" href="#" ';
				html += 'style="';
				html += opts.items[item].size ? 'font-size:' + opts.items[item].size + '; ' : '';
				html += opts.items[item].isbold ? 'font-weight: bold; ' : '';
				html += opts.items[item].isitalic ? 'font-style: italic; ' : '';
				html += opts.items[item].fontcolor ? 'color: ' + opts.items[item].fontcolor + ';' : '';

				html += '">' + opts.items[item].context + '</a>';
				if (opts.items[item].Items && opts.items[item].Items.length) {
					var embeddedHtml = this.generateMenu({
							isembedded: true,
							style: opts.style,
							themed: opts.themed,
							addclass: opts.addclass,
							depth: (opts.depth + 1),
							items: opts.items[item].Items
						});
					html += embeddedHtml;
				}
				html += '</li>';
			}
			html += '</ul>';
		} else {

			
			for (var item in opts.items) {
				if (opts.depth == 1) {
					html += '<div class="flex"><ul class="' + itemsclass + '">';
				}
				html += '<li class=" ' + (opts.items[item].Items && opts.items[item].Items.length ? headerclass : 'itmlist ' + itemsclass) + '"';
				html += 'emenuitemtype="' + (opts.items[item].type == 'header' ? 'H' : 'M') + '" ';
				html += 'etarget="' + opts.items[item].etarget + '" ';
				html += 'isSpacer="' + opts.items[item].isSpacer + '" ';
				if (opts.depth > 1){
					html += 'style="margin-left:' + 5* opts.depth + 'px" ';
				}
				html += 'eelement="' + opts.items[item].eelement + '" ';
				html += 'etype="' + opts.items[item].etype + '" ';
				html += 'eviewtype="' + opts.items[item].eviewtype + '">';
				if (opts.items[item].icontitle && opts.items[item].type != 'header') {
					html += '<span class="';
					html += opts.items[item].icontitle ? 'far fa-1x ' + opts.items[item].icontitle + '" ' : '" ';
					html += 'style="';
					html += opts.items[item].iconfontsize ? 'font-size: ' + opts.items[item].iconfontsize + '; ' : '';
					html += opts.items[item].iconcolor ? 'color:' + opts.items[item].iconcolor + ';"' : '"';
					html += ' ></span>';
				}
				if (opts.items[item].Items && opts.items[item].Items.length) {
					html += '<h4>' + opts.items[item].context + '</h4>';
				} else {
					html += '<a href="#" ';
					if (opts.items[item].isbold || opts.items[item].isitalic) {
						html += 'style="';
						html += opts.items[item].isbold ? 'font-weight: bold; ' : '';
						html += opts.items[item].isitalic ? 'font-style: italic; "' : '"';
					}
					html += '>' + opts.items[item].context + '</a>';
				}
				if (opts.items[item].Items && opts.items[item].Items.length) {
					var embeddedHtml = this.generateMenu({
							isembedded: true,
							style: opts.style,
							themed: opts.themed,
							addclass: opts.addclass,
							depth: (opts.depth + 1),
							items: opts.items[item].Items
						});
					html += embeddedHtml;
				}
				html += '</li>';

				if (opts.depth == 1) {
					html += '</ul></div>';
				}
			}
		}

		if (opts.isembedded === true) {
			return html;
		} else {
			html += '</div>';
			$(html).appendTo(document.body);
			$('#' + opts.menuid).hide();
			$("div.docova_menu_container").on('mouseenter', 'li', function () {
				if ( $(this).attr("isSpacer") == "1" ) return; 
				
				$(this).addClass('d-ui-state-hover');
				
				if ($(this).has('ul').length) {
					$(this).parent().removeClass('dm_shadow');
					$(this).children('ul:first').addClass('dm_shadow').show();
				}
			});
			$("div.docova_menu_container").find ("[isspacer ='1']").each( function () {
				$(this).css("border-bottom",  "0px")
			})
			$("div.docova_menu_container").on('mouseleave', 'li', function () {
				$(this).removeClass('d-ui-state-hover');
				
				if ($(this).has('ul').length) {
					$(this).parent().addClass('dm_shadow');
					$(this).children('ul:first').removeClass('dm_shadow').hide();
				}
			});

			$("div.docova_menu_container").on('click', 'li,a', function (e) {
				e.preventDefault();
				var trg = e.target.nodeName.toLowerCase() == 'a' ? $(e.target).parent().get(0) : e.target;
				opts.sourcewindow.OpenMenuItem(trg);
				$('.docova_menu_container').remove();
				//$('#' + opts.menuid).remove();
				return false;
			});

			$("div.docova_menu_container").on('mouseleave', function (e) {
				setTimeout(function () {
					$('.docova_menu_container').remove();


					$(opts.parent).removeClass("d-ui-state-active");
					$(opts.parent).find(".expandablesys").hide();
					//$('#' + opts.menuid).remove();
				}, 500);
			});
			$('#' + opts.menuid).position({
				my: opts.position.my,
				at: opts.position.at,
				of: opts.parent,
				collision: opts.position.collision
			}).show();

			var mv = this.calculateMenuPosition(opts.parent, opts.sourcewindow);
			
			if ( (mv.nLeft + $('#' + opts.menuid).outerWidth() ) > window.top.innerWidth ) {
				
				var delta = (mv.nLeft + $('#' + opts.menuid).outerWidth() ) - window.top.innerWidth + 10;
				mv.nLeft = mv.nLeft - delta;
			}

			if ( (mv.nTop + $('#' + opts.menuid).outerHeight() ) > window.top.innerHeight ) {
				
				var delta = (mv.nTop + $('#' + opts.menuid).outerHeight() ) - window.top.innerHeight + 10;
				mv.nTop = mv.nTop - delta;
			}

			$('#' + opts.menuid).css("top", mv.nTop + opts.xadjustment);
			$('#' + opts.menuid).css("left", mv.nLeft + opts.yadjustment);
		}
	}

	/*----------------------------------------------------------------------------------------------
	Function: generateMenuCSSFromJSON
	Helper function to return a css string based on the provided JSON object
	-------------------------------------------------------------------------------------------------*/
	this.generateMenuCSSFromJSON = function (styleobj, perspective) {
		var csstxt = "";

		if (styleobj.MenuBackground != "Transparent") {
			csstxt += 'body, #divOutline { background:' + styleobj.MenuBackground + ' }\r\n';
		} else
			csstxt += 'body, #divOutline { background: transparent }\r\n';

		//header
		csstxt += '.d-ui-widget-header { background: ' + styleobj.HeaderBackground + ' ; \r\n';
		csstxt += 'color: ' + styleobj.HeaderFontColor + '; font-size: ' + styleobj.HeaderFontSize + ' }\r\n';

		//subheader
		csstxt += 'ul.d-ui-state-default > li.d-ui-widget-header{ background: ' + styleobj.SubHeaderBackground + '; \r\n';
		csstxt += 'color: ' + styleobj.SubHeaderFontColor + '; font-size: ' + styleobj.SubHeaderFontSize + ' }\r\n';

		var aligntxt = '';
		var aligntxtsub = '';
		if (styleobj.ExpIconPlacementHeader == "Right") {
			aligntxt = 'float:right';
			csstxt += '.OutlineItems { padding-right:0px }\r\n'
		} else {
			aligntxt = 'float:none';
		}

		if (styleobj.ExpIconPlacementSubHeader == "Right") {
			aligntxtsub = 'float:right';
			csstxt += '.OutlineItems { padding-right:0px }\r\n'
		} else {
			aligntxtsub = 'float:none';
		}

		//icon font size and margin top
		
		csstxt += 'li.d-ui-widget-header > .expandable { font-size : ' + styleobj.HeaderIconSize + '; line-height: ' + styleobj.HeaderPadding + '; ' + aligntxt + '; }\r\n';

		
		csstxt += 'ul.d-ui-state-default > li.d-ui-widget-header > .expandable { font-size : ' + styleobj.SubHeaderIconSize + '; ';
		csstxt += styleobj.SubHeaderPadding != null ? ('line-height: ' + styleobj.SubHeaderPadding + '; ') : '';
		csstxt += aligntxtsub + '; }\r\n';

		//header padding/spacing
		if (styleobj.HeaderPadding != null) {
			csstxt += 'li.d-ui-widget-header > .itemlabel { line-height: ' + styleobj.HeaderPadding + '}\r\n';
		}

		//subheader padding/spacing
		if (styleobj.SubHeaderPadding != null) {
			csstxt += 'ul.d-ui-state-default > li.d-ui-widget-header > .itemlabel { line-height: ' + styleobj.SubHeaderPadding + '}\r\n';
		}

		//items background/fontcolor/padding/underline
		csstxt += '.d-ui-state-default { color : ' + styleobj.ItemsFontColor + ';  background : ' + styleobj.ItemsBackground + '; }\r\n'
		//csstxt += '.OutlineItems { }\r\n'


		
		csstxt += 'ul.d-ui-state-default > [emenuitemtype="M"] { line-height : ' + styleobj.ItemsPadding + '; \r\n';
		csstxt += 'font-size: ' + styleobj.ItemsFontSize + "; \r\n";
		csstxt += 'border-bottom: 1px ' + styleobj.border_style + ' ' + styleobj.bordercolor + '}\r\n';
		
		var hcsstxt = "";
		if ( styleobj.IconMenuAccentColor && (perspective == "IC" || perspective == "IS" || perspective == "VC" || perspective == "VS")) {
			hcsstxt = " color : " + styleobj.IconMenuAccentColor + "; border-left: 4px solid " + styleobj.IconMenuAccentColor;
		}

		csstxt += '.d-ui-state-hover { background: ' + styleobj.MenuHoverColor + ' !important; ' + hcsstxt + ' }\r\n';
		csstxt += '.d-ui-state-active { background: ' + styleobj.MenuHoverColor + ' !important; ' + hcsstxt + '}\r\n';

		var paddarr = styleobj.MenuPadding.split(":");
		if ( paddarr.length >=0 ) csstxt += 'ul.OutlineItems li { padding : ' + paddarr[0]+ ' }\r\n';
		if ( paddarr.length >=1 &&  paddarr[1]  != "") csstxt += 'ul.OutlineItems li { padding-left : ' + paddarr[1] + ' }\r\n';
		if ( paddarr.length >=2 &&  paddarr[2]  != "") csstxt += 'ul.OutlineItems li { padding-right : ' + paddarr[2] + ' }\r\n';
		if ( paddarr.length >=3 &&  paddarr[3]  != "") csstxt += 'ul.OutlineItems li { padding-top : ' + paddarr[3] + ' }\r\n';
		if ( paddarr.length >=4 &&  paddarr[4]  != "") csstxt += 'ul.OutlineItems li { padding-bottom : ' + paddarr[4] + ' }\r\n';
		return csstxt;

	}

	/*----------------------------------------------------------------------------------------------
	Function: SetCookie
	By default stores data temporarily in a docovaCookie JS array variable in the top window.
	Optionally call with httpcookie: true to set a persistent browser cookie. httpcookie cookie size
	must be managed by the developer, whereas the element can hold a large amount of any type of data.
	Options:
	keyname:    String.  The identifier for the value you want to store
	keyvalue:   Any data value except undefined, unless httpcookie = true then must be a string
	cookielocn: Document reference.  Default is window.top.document.  The document where the cookiename
	element will be created.  Applies if httpcookie = false only
	httpcookie: Boolean.  Default is false.  Set to true to use a persistent browser cookie rather
	than a value stored temporarily in a element.
	-------------------------------------------------------------------------------------------------*/
	this.setCookie = function (options) {

		var defaultOptns = {
			keyname: "",
			keyvalue: "",
			cookielocn: window.top,
			httpcookie: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.keyname == "") {
			return false
		}

		if (opts.httpcookie) {

			var sExpires = "; expires=Fri, 31 Dec 9999 23:59:59 GMT";
			var sDomain = "; " + document.domain;
			var sPath = "; path=/";
			document.cookie = encodeURIComponent(opts.keyname) + "=" + encodeURIComponent(opts.keyvalue) + sExpires + sDomain + sPath;
			return true;

		} else {

			opts.cookielocn.docovaCookie[opts.keyname] = opts.keyvalue;
			return true;
		}
	} //--end setCookie

	/**----------------------------------------------------------------------------------------------
	Function: getCookie
	By default gets data from the docovaCookie JS array variable in the top window.
	Optionally call with httpcookie: true to get a persistent browser cookie.
	Options:
	keyname:    String.  The identifier for the value you want to store
	ignorecase: Boolean. Default is false. Set to true to ignore case when locating matching cookie
	httpcookie: Boolean.  Default is false.  Set to true to use a persistent browser cookie rather
	than a value stored temporarily in a element.
	cookielocn: Document reference.  Default is window.top.document.  The document where the cookiename
	element will be created.  Applies if httpcookie = false only
	-------------------------------------------------------------------------------------------------**/
	this.getCookie = function (options) {
		var result = null;
		var defaultOptns = {
			keyname: "",
			cookielocn: window.top,
			ignorecase: false,
			httpcookie: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.keyname != "") {
			if (opts.httpcookie) {

				result = decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(opts.keyname).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$", (opts.ignorecase ? "i" : "")), "$1")) || null;
			} else {
				var cookie = opts.cookielocn.docovaCookie;
				if (cookie) {
					for (var key in cookie) {
						if (key === opts.keyname || (opts.ignorecase && key.toLowerCase() === opts.keyname.toLowerCase())) {
							result = cookie[key];
							break;
						}
					}
				}
			}
		}
		return result;
	} //--end GetCookie

	/*----------------------------------------------------------------------------------------------
	 * Function: getSessionCookie
	 * Retrieves session authentication token from one of a number of possible web page cookies
	 * Returns: value pair object consisting of
	 *          cookiename: string - name of session authentication cookie found
	 *          cookieval: string - value of the cookie
	 *------------------------------------------------------------------------------------------------*/
	this.getSessionCookie = function () {
		var cookieName;
		var cookieVal;
		var cookArray = ["DomAuthSessId", "LtpaToken", "LtpaToken2", "SMSESSION"]
		for (var p = 0; p < cookArray.length; p++) {
			var cval = this.getCookie({
					keyname: cookArray[p],
					httpcookie: true
				});
			if (cval && cval != "") {
				cookieName = cookArray[p];
				cookieVal = cval;
				return {
					"cookiename": cookieName,
					"cookieval": cookieVal
				}
			}
		}
	} //--end getSessionCookie
	
	/**----------------------------------------------------------------------------------------------
	Function: selectKeyword
	Displays a dialog with supplied options for user to select.  Single or multivalue
	Options:
	choicelist:     Delimited string or an array. 'a,b,c' or ['a','b','c'].
	To have a value different from the selection list use |.
	eg 'a|1,b|2,c|3' or ['a|1','b|2','c|3'].
	defaultvalues:  Array of values or options that should be preselected. multiselect must be true.
	disabledvalues: Array of values or options that should show as disabled.
	delimiterin:    String. Default is ";".  The delimiter if the choicelist is a string.
	multiselect:    Boolean. Default is false.  True to allow multiple options to be selected, false for single
	allowothervals: Boolean. Default is false.  True displays a field for the user to enter an option not in the list
	allowothertext: String.  Default is "New Keyword:".  The text to display if allowothervals is true.
	returnarray:    Boolean.  Default is true.  True returns an array, false returns a delimited string
	delimiterout:   String.  Default is ";".  If returnarray is false, the delimiter to use in the string list
	width:          String.  Default is "250".  The width of the dialog
	delegate:       Selector or Element or jQuery or Event to anchor the center of the dialog to.
	windowtitle:    String.  Default is "Keyword".  The title at the top of the dialog
	prompt:         String.  Optional text instructions to display at top of the dialog
	onbeforecomplete: function to be called when Ok is clicked, before the oncomplete function is processed
	perform validation if required, display prompts, return true to process oncomplete
	or false to stop dialog from closing and stop oncomplete process
	oncomplete:     function to be called when OK is clicked on the dialog.
	eg: function(data) { Docova.Utils.setField({ field: "V_" + sufixNo, value: data }); }
	The 'data' variable contains the selected value(s) in the selected data type, based on returnarray
	});
	-------------------------------------------------------------------------------------------------**/
	this.selectKeyword = function (options) {

		var defaultOptns = {
			'choicelist': "",
			'defaultvalues': "",
			'disabledvalues': "",
			'delimiterin': ";",
			'multiselect': false,
			'allowothervals': false,
			'allowothertext': "New Keyword:",
			'returnarray': true,
			'delimiterout': ";",
			'width': "250",
			'delegate': "",
			'windowtitle': "Keyword",
			'prompt': "",
			'onbeforecomplete': function () {
				return true
			},
			'oncomplete': function () {},
			'oncancel': function () {}
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.choicelist.length < 1 & opts.allowothervals != true) {
			return;
		}

		//div to hold all content & be displayed by dialog
		var container = document.createElement('div');
		$(container).prop('id', 'Container');
		$(container).css({
			'padding-top': '5px',
			'padding-left': '2px',
			'padding-right': '2px',
			'overflow': 'hidden'
		});
		document.body.appendChild(container);

		//div to hold the prompt instructions/text, if being used
		if (opts.prompt != "") {
			var promptDiv = document.createElement('div');
			$(promptDiv).prop('id', 'PromptText');
			$(promptDiv).css("display", "none");
			$(promptDiv).css("position", "relative");
			$(promptDiv).css("padding", "10px");
			$(promptDiv).css("font-weight", "bold");
			$("#Container").append(promptDiv);
			$("#PromptText").html('<p style="margin: 1;">' + opts.prompt + '</p>');
		}

		//div to hold the options list/multiselect widget
		var selDiv = document.createElement('div')
			$(selDiv).prop('id', 'SelectKeyword');
		$(selDiv).css("display", "none");
		$(selDiv).css("position", "relative");
		if (_DOCOVAEdition == "SE") {
			$(selDiv).css("height", opts.multiselect ? "235px" : "210px");
		} else {
			$(selDiv).css("height", opts.multiselect ? "300px" : "265px");
		}
		$("#Container").append(selDiv)

		//build the options list
		if (!Array.isArray(opts.choicelist)) {
			opts.choicelist = opts.choicelist.split(opts.delimiterin);
		}
		var items = [];
		items.push('<select ' + ((opts.multiselect) ? 'multiple' : '') + ' id="SelKeyword">');
		for (var x = 0; x < opts.choicelist.length; x++) {
			items.push('<option');

			var arr = opts.choicelist[x].split("|");
			if (opts.defaultvalues.indexOf(arr[0]) > -1 || (arr[1] ? (opts.defaultvalues.indexOf(arr[1]) > -1) : false)) {
				items.push(' selected');
			}
			if (opts.disabledvalues.indexOf(arr[0]) > -1 || (arr[1] ? (opts.disabledvalues.indexOf(arr[1]) > -1) : false)) {
				items.push(' disabled');
			}
			items.push(' value="' + (arr[1] ? arr[1] : arr[0]) + '">' + arr[0]);

			items.push('</option>');
		}
		items.push('</select>');

		$("#SelectKeyword").html(items.join(''));

		//div to hold the 'New Keyword' field, if being used
		if (opts.allowothervals) {
			var otherDiv = document.createElement('div');
			$(otherDiv).prop('id', 'OtherKeyword');
			$(otherDiv).css("display", "none");
			$(otherDiv).css("position", "absolute");
			$(otherDiv).css("height", "4em");
			$(otherDiv).css("padding-top", "10px");
			$(otherDiv).css("padding-bottom", "5px");
			$(otherDiv).css("bottom", "0px");
			$(otherDiv).css("font-weight", "bold");
			$("#Container").append(otherDiv);
			var w = parseInt(opts.width) - 10;
			$("#OtherKeyword").html('<p>' + opts.allowothertext + '<br><input type="text" id="Other" style="width:' + w + 'px">');
		}

		//create the dialog
		$("#Container").dialog({
			modal: true,
			width: parseInt(opts.width) + 4,
			closeOnEscape: false,
			open: function (event, ui) {
				$(".ui-dialog-titlebar-close").hide();
			},
			title: opts.windowtitle,
			position: {
				my: 'center',
				at: 'center',
				of: opts.delegate,
				collision: "fit"
			},
			resize: function (event, ui) {
				$(".ui-multiselect-checkboxes").height(ui.size.height - 234);
			},
			buttons: {
				"OK": function () {
					var selList = $("#SelKeyword").val();
					if (selList == null) {
						selList = "";
					}
					var otherList = "";
					if (opts.allowothervals) {
						var otherData = $("#Other").val();
						otherList = otherData == "" ? "" : otherData.split(/[,;]/g);
					}
					var data = "";
					if (opts.multiselect) {
						data = otherList.length < 1 ? selList : selList.concat(otherList);
					} else {
						data = otherList.length < 1 ? selList : otherList;
					}
					if (!opts.onbeforecomplete(data)) {
						return false;
					}
					$(this).dialog("close");
					opts.oncomplete(data);
					$(this).dialog('destroy').remove();
				},
				Cancel: function () {
					$(this).dialog("close");
					opts.oncancel(false);
					$(this).dialog('destroy').remove();
				}
			}
		});

		//show applicable divs
		$(selDiv).css("display", "block");
		if (opts.allowothervals) {
			$(otherDiv).css("display", "block");
		}
		if (opts.prompt != "") {
			$(promptDiv).css("display", "block");
		}

		//convert the UI of the option list using multiselect
		$("#SelKeyword").multiselect({
			initHidden: true,
			height: "200",
			minWidth: opts.width,
			autoOpen: true,
			header: opts.multiselect ? true : false,
			showSelected: true,
			multiple: opts.multiselect,
			beforeclose: function () {
				return false;
			},
			open: function (event, ui) {
				$(".ui-multiselect-close").hide();
			}
		});

		//adjust multiselect css and link to the SelectKeyword div
		$("#SelKeywordmenu").css('top', '0px');
		$("#SelKeywordmenu").css('width', '98%');
		var menu = document.getElementById("SelKeywordmenu");
		$("#SelectKeyword").append(menu)
	} //--end selectKeyword


	/*----------------------------------------------------------------------------------------------
	 * Function: sort
	 * A lightweight sorting utility - basically a front end to javascript.sort(), however can pass
	 * an array, or a string with delimiters.
	 * Parameters:
	 *      options: value pair array consisting of the following;
	 *          inputstr: String. Delimited string to be sorted.
	 *          delimiterin: String. Default is ";".  The delimiter used in the inputstr.
	 *          delimiterout:   String. Default is ";".  The delimiter used in the output if returnarray is false.
	 *          inputarr:       Array.  Array to be sorted. Also used to hold the split inputstr.
	 *          sortfunction:   Optional function to pass to the javascript.sort method, eg: to return in descending order
	 *          returnarray:    Boolean. Default is false.  Set to true to return an array, false to return a delimited string.
	 *  Returns:
	 *      array or string sorted
	-------------------------------------------------------------------------------------------------*/
	this.sort = function (options) {

		var defaultOptns = {
			inputstr: "",
			delimiterin: ";",
			delimiterout: ";",
			inputarr: [],
			sortfunction: "",
			returnarray: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.inputarr.length > 0) {
			return opts.inputarr.sort(opts.sortfunction);
		} else {
			opts.inputarr = opts.inputstr.split(opts.delimiterin);
			if (opts.returnarray) {
				if (opts.sortfunction) {
					return opts.inputarr.sort(opts.sortfunction);
				} else {
					return opts.inputarr.sort();
				}
			} else {
				if (opts.sortfunction) {
					var outputarr = opts.inputarr.sort(opts.sortfunction);
				} else {
					var outputarr = opts.inputarr.sort();
				}
				return outputarr.join(opts.delimiterout)
			}
		}
	} //--end sort


	/*----------------------------------------------------------------------------------------------
	 * Function: loadXMLString
	 * Cross browser method for parsing an XML string into an XML document
	 * Inputs: xmlString - string - string to convert into xml DOM
	 *------------------------------------------------------------------------------------------------*/
	this.loadXMLString = function (xmlString) {
		if (window.DOMParser) {
			parser = new DOMParser();
			xmlDoc = parser.parseFromString(xmlString, "text/xml");
		} else {
			xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
			xmlDoc.async = "false";
			xmlDoc.loadXML(xmlString);
		}
		return xmlDoc;
	} //--end loadXMLString

	/*----------------------------------------------------------------------------------------------
	 * Function: postInBackground
	 * Used to execute a post http xml request from top frame asyncronously for cases in which
	 * a sub function wants to trigger some server side code/process that will run in the
	 * background and then return a result back to the top parent frame even after the child
	 * page/frame has closed
	 * Inputs: serviceurl - string - path to server agent
	 *         request - string - data to be posted to agent
	 *         successmsg - string (optional) - message on success
	 *         failmsg - string (optional) - message on failure
	 *------------------------------------------------------------------------------------------------*/
	this.postInBackground = function (serviceurl, request, successmsg, failmsg) {
		if (!serviceurl || !request) {
			if (failmsg) {
				alert(failmsg);
				return;
			}
		}
		jQuery.ajax({
			'type': "POST",
			'url': serviceurl,
			'processData': false,
			'data': request,
			'contentType': false,
			'async': true,
			'dataType': 'xml'
		})
		.done(function (data) {
			if (successmsg) {
				alert(successmsg);
			}
		})
		.fail(function (jqXHR, textStatus, errorThrown) {
			if (failmsg) {
				alert(failmsg);
			}
		});
	} //--end postInBackground

	/*----------------------------------------------------------------------------------------------
	 * Function: evaluateFormula
	 * evaluates an $$DocovaScriptFormula/@Formula
	 * Inputs: formula - string/array of strings - formula(s) to be evaluated
	 *         doc - DocovaDocument (optional) - document to evaluate the formula against
	 * Returns: array - containing results of formula(s)
	 *-------------------------------------------------------------------------------------------------*/
	this.evaluateFormula = function (formula, doc) {
		var result = null;

		if (typeof formula == 'undefined') {
			return result;
		}

		var appobj = null;
		if (typeof doc !== 'undefined' && doc.constructor_name == "DocovaDocument") {
			appobj = doc.parentApp;
		} else {
			var uiw = Docova.getUIWorkspace(document);
			appobj = uiw.getCurrentApplication();
		}

		if (_DOCOVAEdition == "SE") {
			if (!appobj || !docInfo.NsfName) {
				return result;
			}
		} else {
			if (!appobj || !appobj.filePath || appobj.filePath == "") {
				return result;
			}
		}

		var formulas = [];
		if (Array.isArray(formula)) {
			formulas = formula.slice();
		} else {
			formulas = [formula];
		}

		if (_DOCOVAEdition == "SE") {
			var serviceurl = "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
		} else {
			var serviceurl = appobj.filePath + "/DocumentServices?OpenAgent";
		}

		var request = "<Request>";
		request += "<Action>EVALUATEFORMULA</Action>";
		request += "<AppID>" + appobj.appID + "</AppID>";
		request += "<Unid>";
		if (typeof doc !== 'undefined' && doc !== null && doc.constructor_name === "DocovaDocument" && !doc.isNew && doc.unid !== "") {
			request += doc.unid;
		}
		request += "</Unid>";

		for (var i = 0; i < formulas.length; i++) {
			var tempformula = formulas[i];
			if (_DOCOVAEdition == "SE") {
				var LexerObj = new Lexer();
				tempformula = LexerObj.convertCode(tempformula, "TWIG");
			}
			request += "<Formula ID='" + (i + 1).toString() + "'><![CDATA[" + tempformula + "]]></Formula>";
		}

		request += "</Request>";

		jQuery.ajax({
			type: "POST",
			url: serviceurl,
			data: encodeURIComponent(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xmldata) {
				var xmlobj = jQuery(xmldata);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					result = [];
					var resultcounter = 0;
					var keepgoing = true;
					do {
						var tempresult = [];
						resultcounter++;
						var resultxmlobj = xmlobj.find("Result[ID=Ret" + resultcounter.toString() + "]");
						if (resultxmlobj.length == 0) {
							keepgoing = false;
						} else {
							jQuery(resultxmlobj).find("Element").each(function () {
								var datatype = jQuery(this).attr("dt");
								var index = Number(jQuery(this).attr("index"));
								var fieldval = jQuery(this).text();
								if (datatype == "date") {
									fieldval = new Date(fieldval);
								} else if (datatype == "number") {
									fieldval = Number(fieldval);
								}
								tempresult.push(fieldval);
							});
						}
						result.push(tempresult.slice());
					} while (keepgoing);

				}
			},
			error: function () {}
		});

		return (formulas.length == 1 && Array.isArray(result) ? result[0] : result);
	} //--end evaluateFormula

	
	/*----------------------------------------------------------------------------------------------
	 * Function: replaceURL
	 * changes the current window url, with support for replacing an iframe's content instead of setting src
	 * in order to stop global history from being updated
	 * Inputs: newurl - string - url to change to
	 *         doc - DOM document (optional) - target document to replace with new url
	 *-------------------------------------------------------------------------------------------------*/
	this.replaceURL = function (newurl, doc) {
		if(typeof newurl !== "string" || newurl == ""){ return false;}
		
		var tempdoc = (typeof doc !== "undefined" ? doc : document);
		var tempwindow = (typeof tempdoc.defaultView == "undefined" ? window : tempdoc.defaultView);
	
		if(tempwindow.frameElement !== null && tempwindow.frameElement.tagName.toUpperCase() == "IFRAME"){
			var iframecode = $(tempwindow.frameElement)[0].outerHTML;
			iframecode = iframecode.replace(/(\ssrc=["'])(.*?)(["'][\s>])/, "$1"+newurl+"$3");
			jQuery(tempwindow.frameElement).replaceWith(iframecode);									
		}else{
			tempdoc.location.href = newurl;
		}		
	} //--end replaceURL
	

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *  doc: html document where this object was instantiated from
	 *  msgboxresult: string -
	 *------------------------------------------------------------------------------------------------*/
	this.doc = document;
	this.msgboxresult = "";

} //--end DocovaUtils class


/*
 * Class: DocovaUIDocument
 * User interface classes and methods for interaction with DOCOVA documents and on-screen DOM elements
 * Typically accessed from DocovaUIWorkspace.CurrentDocument
 * Parameters:
 *      parentobj: DocovaUIWorkspace object
 *      uidocwindow: window object (optional) - target window where ui document resides. if not set assume current window
 **/
function DocovaUIDocument(parentobj, uidocwindow) {
	var _document = null;
	var _targetwin = (typeof uidocwindow == 'undefined' ? window : uidocwindow);
	var _htmldoc = _targetwin.document;
	var _fieldIndex = null;

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	this.parent = parentobj;
	this.htmldoc = _htmldoc;
	this.docID = _targetwin.info.DocID;
	this.docKey = _targetwin.info.DocKey;
	this.docNumber = _targetwin.info.DocumentNumber;
	this.appName = _targetwin.info.LibraryTitle;
	this.appID = _targetwin.info.LibraryKey;
	this.username = _targetwin.info.UserName;
	this.usernameAB = _targetwin.info.UserNameAB;
	this.usernameCN = _targetwin.info.UserNameCN;
	this.isNewDoc = (_targetwin.info.isNewDoc === "true" || _targetwin.info.isNewDoc === true);
	this.isProfile = (_targetwin.info.isProfile ? _targetwin.info.isProfile : false);
	this.isUIDoc = (_targetwin.info.isUIDoc === "true" || _targetwin.info.isUIDoc === true);
	this.isDocBeingEdited = (_targetwin.info.isDocBeingEdited === "true" || _targetwin.info.isDocBeingEdited === true);
	this.isWorkflowCreated = (_targetwin.info.isWorkflowCreated === "true" || _targetwin.info.isWorkflowCreated === true);
	this.createWorkflowInDraft = _targetwin.info.CreateWorkflowInDraft;
	this.hasMultiAttachmentSections = _targetwin.info.HasMultiAttachmentSections;
	this.docAccessLevel = _targetwin.info.DocAccessLevel;
	this.docAccessRole = _targetwin.info.DocAccessRole;
	this.appAccessLevel = _targetwin.info.DbAccessLevel;
	this.docMode = _targetwin.info.Mode;
	this.queryString = _targetwin.info.Query_String;
	this.sslState = _targetwin.info.SSLState;
	this.serverName = _targetwin.info.ServerName;
	this.serverURL = _targetwin.info.ServerUrl;
	this.appURL = _targetwin.info.NsfName;
	this.homeURL = _targetwin.info.PortalWebPath;
	this.createdBy = _targetwin.info.CreatedBy;
	this.createdDate = _targetwin.info.CreatedDate;
	this.lastModifiedBy = _targetwin.info.LastModifiedBy;
	this.lastModifedDate = _targetwin.info.LastModifiedDate;
	this.sessionDateFormat = _targetwin.info.SessionDateFormat;
	this.hasActivities = _targetwin.info.HasActivities;
	this.isPostRefresh = _targetwin.info.isPostRefresh;
	this.formName = _targetwin.info.FormName;
	this.profileKey = (_targetwin.info.ProfileKey ? _targetwin.info.ProfileKey : "");
	this.saved = false;
	this.savepending = false;
	this._triggers = {};

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaUIDocument";
		},
		enumerable: true
	});

	Object.defineProperty(this, 'document', {
		get: function () {
			if (!_document) {
				_document = new DocovaDocument(this, this.docID, this.docKey);
			}
			return _document;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'autoReload', {
		get: function () {
			return true;
		},
		set: function (enableedit) {
			return true;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'inPreviewPane', {
		get: function () {
			return false;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'editMode', {
		get: function () {
			return this.isDocBeingEdited;
		},
		set: function (enableedit) {
			if (enableedit === true && !this.isDocBeingEdited) {
				//-- switch to edit mode
				this.edit();
			} else if (enableedit === false && this.isDocBeingEdited) {
				//-- switch to read mode
				this.save({
					editMode: false
				});
			}
		},
		enumerable: true
	});

	this.on = function (event, callback) {
		if (!this._triggers[event])
			this._triggers[event] = [];
		this._triggers[event].push(callback);

	}

	//call the last funciton hookped up to this event async and return a value
	this.triggerHandlerAsync = function (trigger, context) {
		var retarr = [];
		if (this._triggers[trigger]) {
			return this._triggers[trigger][this._triggers[trigger].length - 1].call(context);

		} else if (typeof window[trigger] === 'function') {
			return window[trigger].call(context);
		}
		return true;
	}

	//call all hooked up callbacks for this event synchrously
	this.triggerHandler = function (trigger, context, remove) {
		if (this._triggers[trigger]) {
			var tmpArr = this._triggers[trigger];

			for ( var i = 0; i < tmpArr.length; i ++) {
			//for (i in this._triggers[trigger]) {
				//return _triggers[trigger][i](context);
				setTimeout(this._triggers[trigger][i](context), 0);
				if (remove) {
					this._triggers[trigger].splice(0, 1);
				}
			}
		}
	}

	/*----------------------------------------------------------------------------------------------
	 * Private Function: backendDocSaved
	 * Helper function changing a new document's action to reflect a backend save
	 * i.e.  changes the ?openForm to /0/unid?edit document
	-------------------------------------------------------------------------------------------------*/
	this.backendDocSaved = function (unid) {
		if (this.isNewDoc && _targetwin !== 'undefined') {
			this.docID = unid;
			_targetwin.info.DocID = unid;
			this.isNewDoc = false;
			_targetwin.info.isNewDoc = false;
			$("Form:first", this.htmldoc).attr("action", "/" + this.appURL + "/0/" + unid + "?editDocument");
		}
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: getAllFields
	 * Helper function for retrieving all identifiable fields from the ui document
	 * Input: userfields - boolean (optional) - true to retrieve just user defined custom fields
	 * Returns:
	 *   value pair array of field names and their values
	-------------------------------------------------------------------------------------------------*/
	this.getAllFields = function (userfields) {
		if (this.parent.currentDOM == undefined || this.parent.currentDOM == null) {
			return null;
		}

		var fields = {};
		var allobj;
		if (userfields) {

			allobj = jQuery("#divDocPage", this.parent.currentDOM).find(":input").serialize().split("&");
			var extraobj = jQuery("[name=divComputedFields]", this.parent.currentDOM).find(":input").serialize().split("&");
			allobj = allobj.concat(extraobj);
			for (var p = 0; p < allobj.length; p++) {
				var valarr = allobj[p].split("=");

				var fieldname = decodeURI(valarr[0]);				
				if (fieldname.indexOf("[]") != -1) {
					fieldname = fieldname.split("[]")[0];
				}				
				if(fieldname.trim() === "" || fieldname.toLowerCase() == "__click" || fieldname.indexOf("%%") > -1 || fieldname.indexOf("%25%25") > -1) {
					continue;
				}
				fields[fieldname] = Docova.Utils.getField({
						'field': fieldname,
						'separator': ""
					}, this.parent.currentDOM);

				if (_fieldIndex === null) {
					_fieldIndex = {};
				}
				if (_fieldIndex[fieldname.toLowerCase()]) {
					if (_fieldIndex[fieldname.toLowerCase()].indexOf(fieldname) === -1) {
						_fieldIndex[fieldname.toLowerCase()].push(fieldname);
					}
				} else {
					_fieldIndex[fieldname.toLowerCase()] = [fieldname];
				}
			}

		} else {
			allobj = jQuery("form:first", this.parent.currentDOM).serializeArray();
			jQuery.each(allobj, function (i, field) {
				var fieldname = decodeURI(field.name);
				if (fieldname.indexOf("[]") != -1) {
					fieldname = fieldname.split("[]")[0];
				}			
				if(fieldname.trim() === "" || fieldname.toLowerCase() == "__click" || fieldname.indexOf("%%") > -1 || fieldname.indexOf("%25%25") > -1) {
					return true;
				}
				fields[fieldname] = Docova.Utils.getField({
						'field': fieldname,
						'separator': ""
					}, this.parent.currentDOM);

				if (_fieldIndex === null) {
					_fieldIndex = {};
				}
				if (_fieldIndex[fieldname.toLowerCase()]) {
					if (_fieldIndex[fieldname.toLowerCase()].indexOf(fieldname) === -1) {
						_fieldIndex[fieldname.toLowerCase()].push(fieldname);
					}
				} else {
					_fieldIndex[fieldname.toLowerCase()] = [fieldname];
				}
			});
			if (fields.length == 0) {
				fields = null;
			}
		}
		return fields;
	} //--end getAllFields

	/*----------------------------------------------------------------------------------------------
	 * Function: reload
	 * Refreshes the current document with any changes made to the back-end document associated with the current editing session.
	-------------------------------------------------------------------------------------------------*/
	this.reload = function () {
		return true;
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: goToField
	 * Navigates to a specified field
	 * Parameters:
	 *     fieldname - string - name or id of field to navigate to
	-------------------------------------------------------------------------------------------------*/
	this.goToField = function (fieldname) {
		if (this.parent.currentDOM == undefined || this.parent.currentDOM == null) {
			return;
		}

		if (typeof fieldname == 'undefined' || fieldname == "") {
			return;
		}

		if (!this.editMode) {
			return;
		}

		//-- check if we can find a match for the field name regardless of case
		var fieldnames = this.getFieldNames();
		if (fieldnames && fieldnames[fieldname.toLowerCase()]) {
			fieldname = fieldnames[fieldname.toLowerCase()][0];
		}

		var searchfieldname = fieldname.replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" );			
		var $fieldobj = jQuery("input[name=" + searchfieldname + "]", _htmldoc).first();
		if (!($fieldobj && $fieldobj.length > 0)) {
			$fieldobj = jQuery("[name=" + searchfieldname + "], #" + searchfieldname, _htmldoc).first();
		}
		
		//-- if we have not found the field check to see if it matches a checkbox multi value field
		if (!($fieldobj && $fieldobj.length > 0) && fieldname.indexOf("[]") == -1) {
			var searchfieldname = (fieldname + "[]").replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" );					
			$fieldobj = jQuery("[name=" + searchfieldname + "], #" + searchfieldname, _htmldoc).first();
		}		

		if ($fieldobj && $fieldobj.length > 0) {

			//-- check if the field is in a collapsed section and if so check that it is expanded
			var $sectionobj = $fieldobj.closest("div[elem=fields][elemtype=section][expandcollapse=1]");
			if ($sectionobj && $sectionobj.length > 0) {
				var $sechead = $sectionobj.closest("div.cacontainer").find("div.ui-widget-header:first");
				if ($sechead && $sechead.length > 0) {
					var $content = $sechead.next();
					if (!$content.is(":visible")) {
						var fai = $sechead.children().first();
						fai.removeClass("fa-caret-right").addClass("fa-caret-down");
						$content.slideToggle(0);
					}
				}
			}

			//-- check if the field is in a tabbed section and if so check that the tab is selected
			var $tab = $fieldobj.closest("div.ui-tabs-panel");
			if ($tab && $tab.length > 0) {
				var $tabcontainer = $tab.closest("div.ui-tabs");
				var activetabid = $tab.attr("aria-labelledby");
				var $tablabel = $tabcontainer.find("a[id=" + activetabid + "]").parent();
				if ($tablabel.is(":visible")) {
					if (!$tablabel.hasClass("ui-tabs-active")) {
						var tabid = (parseInt(activetabid.split("-").pop(), 10) - 1);
						$tabcontainer.tabs({
							active: tabid
						});
					}
				}
			}

			//-- for combobox fields locate the custom input element instead of the original field
			if ($fieldobj.prop("tagName").toLowerCase() == "select") {
				var $combobox = jQuery($fieldobj).next("span.custom-combobox");
				if ($combobox && $combobox.length > 0) {
					$fieldobj = $combobox.children("input.custom-combobox-input:first");
				}
			}

			$fieldobj.focus();
		}

	} //--end goToField


	/*----------------------------------------------------------------------------------------------
	 * Function: getFieldNames
	 * Helper function for retrieving all identifiable field names from the ui document
	 * Returns:
	 *   value pair array of unique field names in lower case and an array of the exact case field names
	-------------------------------------------------------------------------------------------------*/
	this.getFieldNames = function () {
		if (this.parent.currentDOM == undefined || this.parent.currentDOM == null) {
			return null;
		}

		if (_fieldIndex === null) {
			_fieldIndex = {};

			jQuery('form:first', this.parent.currentDOM).find(':input').each(function (i, field) {
				if (field.name !== undefined && field.name !== "" && field.name.toLowerCase() !== "__click" && field.name.indexOf("%%") < 0) {
					//for checkboxes, field names have a [] at the end..we remove it.

					var fieldname = "";
					if (field.name.indexOf("[]") != -1) {

						fieldname = field.name.split("[]")[0];
					} else {
						fieldname = field.name;
					}
					if (_fieldIndex[fieldname.toLowerCase()]) {
						if (_fieldIndex[fieldname.toLowerCase()].indexOf(fieldname) === -1) {
							_fieldIndex[fieldname.toLowerCase()].push(fieldname);
						}
					} else {
						_fieldIndex[fieldname.toLowerCase()] = [fieldname];
					}
				}
			});
			
			jQuery('form:first', this.parent.currentDOM).find("span[cfdfieldname!=''][cfdfieldname]").each(function (i, field) {
				var fieldname = jQuery(field).attr("cfdfieldname");
				if (!_fieldIndex[fieldname.toLowerCase()]) {
					_fieldIndex[fieldname.toLowerCase()] = [fieldname];
				}
			});
		}

		return _fieldIndex;
	} //--end getFieldNames

	/*----------------------------------------------------------------------------------------------
	 * Function: getField
	 * Helper function for calling Docova.Utils.getField from DocovaUIDocument
	 * Parameters:
	 *      options: see DocovaUtils.getField
	 * Returns:
	 *  see DocovaUtils.getField
	-------------------------------------------------------------------------------------------------*/
	this.getField = function (options) {
		var result = null;
		if (this.parent.currentDOM == undefined || this.parent.currentDOM == null) {
			return result;
		}

		//-- check if we can find a match for the field name regardless of case
		var fieldname = "";
		if (typeof options === "string") {
			fieldname = options;
			var fieldnames = this.getFieldNames();
			if (fieldnames && fieldnames[fieldname.toLowerCase()]) {
				fieldname = fieldnames[fieldname.toLowerCase()][0];
			}
			options = {
				'field': fieldname,
				'separator': ""
			};
		} else {
			fieldname = options.field;
			var fieldnames = this.getFieldNames();
			if (fieldnames && fieldnames[fieldname.toLowerCase()]) {
				options.field = fieldnames[fieldname.toLowerCase()][0];
			}
			if (!options.separator) {
				options.separator = "";
			}
		}

		if (this.editMode) {
			result = Docova.Utils.getField(options, this.parent.currentDOM);
		} else {
			//-- if doc is in read mode most fields will be inaccessible so get them from the back end
			if (!this.document._fieldBuffer) {
				this.document.getFields("*");
			}
			result = this.document.getField(fieldname);
			if (result) {
				if (result.length > 1) {
					result = result.join(",");
				} else {
					result = result[0];
				}
			}
		}

		return result;
	} //--end getField

	/*----------------------------------------------------------------------------------------------
	 * Function: setField
	 * Helper function for calling Docova.Utils.setField from DocovaUIDocument
	 * Parameters:
	 *      options: see DocovaUtils.setField
	 *      value: optional - used as an overload in cases where options is a field name string
	 * Returns:
	 *  see DocovaUtils.setField
	-------------------------------------------------------------------------------------------------*/
	this.setField = function (options, value) {
		if (this.parent.currentDOM == undefined || this.parent.currentDOM == null) {
			return;
		}
		var defaultOptns = {
			"field": "",
			"value": null
		};
		var opts = {};
		if (typeof options === "string") {
			if (typeof value == "undefined") {
				return false;
			} else {
				defaultOptns.field = options;
				defaultOptns.value = value;
			}
			opts = $.extend({}, defaultOptns);
		} else {
			opts = $.extend({}, defaultOptns, options);
		}

		var uifieldfound = false;
		//-- check if we can find an existing field on the doc
		var fieldnames = this.getFieldNames();
		if (fieldnames && fieldnames[opts.field.toLowerCase()]) {
			//-- if an exact match cannot be found use an alternate
			if (fieldnames[opts.field.toLowerCase()].indexOf(opts.field) === -1) {
				opts.field = fieldnames[opts.field.toLowerCase()][0];
			}
			uifieldfound = true;
		}

		if (uifieldfound) {
			return Docova.Utils.setField(opts, this.parent.currentDOM);
		} else {
			this.document.setField(opts.field, opts.value, null, true);
			Docova.Utils.setField(opts, this.parent.currentDOM); //--extra call in case a computed for display field needs updating in ui
		}
	} //--end setField

	/*------------------------------------------
	 * Function: getUIEmbeddedView
	 * returns a new DocovaUiView object for the request embedded view.
	 * Paramters:
	 *      viewID: string - the id associated with the embedded view.
	 * Returns:
	 *      DocovaUiView object
	--------------------------------------------*/
	this.getUIEmbeddedView = function (viewID) {

		var iframewin = window.frames[viewID];
		if (iframewin)
			return iframewin.contentWindow.Docova.getUIView();
		else
			return null;
	} //end getuiembeddedView

	/*--------------------------------------
	 * Function: refresh
	 * Triggers refresh of ui document
	 * Return: boolean - true if successful false otherwise
	----------------------------------------*/
	this.refresh = function () {
		var result = false;

		var curfield = (_htmldoc.activeElement.name || _htmldoc.activeElement.id || (typeof event != "undefined" && event !== null && event.target ? event.target.id : "") || "");
		var _tempcuruidoc = this;

		//stop nested refresh calls
		if (_targetwin.info.isRefresh) {
			return result;
		}

		if (_DOCOVAEdition == "SE") {
			var tbar = $("#tdActionBar", _htmldoc);
			tbar.append($("<span class='ui-widget' style='width:30px;height:30px; padding:10px'><i class=\"far fa-sync\" aria-hidden=\"true\"></i></span>"));
		}

		//queryRecalc
		if (!_tempcuruidoc.triggerHandlerAsync("queryRecalc")) {
			return result;
		}

		var curfieldobj = ((typeof event != "undefined" && event !== null && event.target ? event.target : null) || _htmldoc.activeElement);

		//-- special check for combobox fields with a drop down menu
		if (jQuery(curfieldobj).hasClass("ui-autocomplete")) {
			curfieldobj = jQuery(curfieldobj).closest("span.custom-combobox").find("input.custom-combobox-input").get(0);
		} else if (jQuery(curfieldobj).hasClass("ui-menu-item")) {
			if (_DOCOVAEdition == "SE") {
				curfieldobj = jQuery(curfieldobj).closest("span.custom-combobox").find("input.custom-combobox-input").get(0);
			} else {
				var sourceelemid = jQuery(curfieldobj).closest("ul.ui-autocomplete").attr("sourceelem");
				if (sourceelemid) {
					curfieldobj = jQuery("span.custom-combobox[sourceelem=" + sourceelemid + "]").find("input.custom-combobox-input").get(0);
				}
			}
		}
		if (jQuery(curfieldobj).is("input.custom-combobox-input")) {
			var $selectobj = jQuery(curfieldobj).parent().prev("select");
			if ($selectobj && $selectobj.length > 0) {
				curfieldobj = $selectobj.get(0);
			}
		}

		curfield = (curfieldobj ? (curfieldobj.name || curfieldobj.id || "") : "");

		_targetwin.info.isRefresh = true;
		//this.setField("docRefreshed", "1");

		//handle data talbes data..stores the data tables json in a hidden field datatablename_values
		//in commonfunctions.js		
		handleDataTablesData(_htmldoc);


		if (typeof _targetwin.saveDocovaRichTextTinyMCE === 'function') {
			_targetwin.saveDocovaRichTextTinyMCE();
		}
		if (typeof _targetwin.saveDocovaRichText === 'function') {
			_targetwin.saveDocovaRichText();
		}

		if (_DOCOVAEdition == "SE") {
			var actionUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/SubmitForRefresh/" + encodeURIComponent(this.formName);
			if (this.isNewDoc == '') {
				actionUrl += "/" + this.docKey;
			}

			actionUrl += "?AppID=" + this.appID;
			if (this.isProfile == "1") {
				actionUrl += "&isProfile=1"
				actionUrl += "&profilename=" + _targetwin.info.ProfileName;
				actionUrl += "&profilekey=" + this.profileKey;
			}

			actionUrl += "&mode=" + this.docMode;
		} else {
			var actionUrl = _targetwin.location.href;
		}

		var oldactionurl = $("form:first", _htmldoc).attr("action") || "";
		var formData = $("form:first", _htmldoc).serializeArray();

		var backendfieldarr = null;
		if (this.document && this.document.fieldBuffer) {
			backendfieldarr = $.extend({}, this.document.fieldBuffer);
		}
		var refreshset = false;
		for (var i = 0; i < formData.length; i++) {
			var key = formData[i].name.toLowerCase();
			if (backendfieldarr && backendfieldarr[key]) {
				//found a match lets see if it was modified and update it in form data
				if (backendfieldarr[key].modified) {
					formData[i].value = backendfieldarr[key].text;
				}
				//clear the value so we know it has been compared
				backendfieldarr[key] = null;
			}

			//in commonfunctions.js
			if (isMultiValue(key, _htmldoc)) {
				formData[i].value = processMultiValue(_htmldoc, key, formData[i].value);
			}

			if(_DOCOVAEdition == "Domino"){
				if (formData[i].name == "__Click") {
					formData[i].value = "$Refresh";
					refreshset = true;
				}
			}

		}

		if(_DOCOVAEdition == "Domino"){
			if (!refreshset) {
				formData.push({
					"name": "__Click",
					"value": "$Refresh"
				});
			}
		}
		
		formData.push({
			name: "__dtmfields",
			value: getAllDateTimeFields(_htmldoc)
		});
		formData.push({
			name: "__numfields",
			value: getAllNumericFields(_htmldoc)
		});
		formData.push({
			name: "__datatablenames",
			value: getAllDataTablesNames(_htmldoc)
		});


		//loop through any back end field elements to see if we need to add any to form data
		if (backendfieldarr) {
			for (var key in backendfieldarr) {
				if (backendfieldarr.hasOwnProperty(key)) {
					if (backendfieldarr[key] !== null) {
						formData.push({
							"name": key,
							"value": backendfieldarr[key].text
						});
					}
				}
			}
		}
		jQuery.ajax({
			url: actionUrl,
			type: "POST",
			data: formData,
			async: false
		}).done(function (data) {
			if (data) {
				var navigatefield = false;
				var cursection = "";
				if (curfield) {
					var searchfieldname = curfield.replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" );	
					cursection = jQuery("[name=" + searchfieldname + "], #" + searchfieldname, _htmldoc).first().closest("div[elem=fields][elemtype=section][expandcollapse=1]").attr("id");
					navigatefield = true;
				}
				var sectionstate = [];
				jQuery("div[elem=fields][elemtype=section][expandcollapse=1]", _htmldoc).each(function () {
					var sectiondivid = jQuery(this).attr("id");
					if (_DOCOVAEdition == "SE") {
						var sechead = jQuery(this).prev("div.ui-widget-header");
					} else {
						var sechead = jQuery(this).closest("div.cacontainer").find("div.ui-widget-header:first");
					}
					if (sechead && sechead.length > 0) {
						//getting the next element
						$content = sechead.next();
						sectionstate.push({
							"divid": sectiondivid,
							"expanded": $content.is(":visible")
						});
					}
				});

				var tabstate = [];
				jQuery('div.ui-tabs', _htmldoc).each(function () {
					var tabdivid = jQuery(this).attr("id");
					var tabid = (parseInt(jQuery(this).find('li.ui-tabs-active').attr('aria-labelledby').split("-").pop(), 10) - 1);
					tabstate.push({
						"divid": tabdivid,
						"tabid": tabid
					});
				});

				DLIUploaderConfigs = null; //clear global variable as it may be appended to by this next update
				if (_DOCOVAEdition == "SE") {
					var bodyhtml = data.body.replace(/^[\S\s]*<body[^>]*?>/i, "").replace(/<\/body[\S\s]*$/i, "");
				} else {
					var bodyhtml = data.replace(/^[\S\s]*<body[^>]*?>/i, "").replace(/<\/body[\S\s]*$/i, "");
				}
				jQuery("body", _htmldoc).html(bodyhtml);
				if(oldactionurl != ""){
					$("form:first", _htmldoc).attr("action", oldactionurl);
				}
				if (_DOCOVAEdition == "SE") {
					jQuery('#tdActionBar a').each(function (index, element) {
						jQuery(element).button({
							text: $.trim(jQuery(this).text()) && jQuery(this).attr('btntext') == 1 ? true : false,
							label: $.trim(jQuery(this).text()),
							icons: {
								primary: ($.trim(jQuery(this).attr('primary'))) ? jQuery(this).attr('Primary') : null,
								secondary: ($.trim(jQuery(this).attr('secondary'))) ? jQuery(this).attr('secondary') : null
							}
						}).addClass("dtoolbar");
					});
				}
				InitAppBuilderForm();
				//-- reset tab state
				if (tabstate.length > 0) {
					for (var i = 0; i < tabstate.length; i++) {
						jQuery("#" + tabstate[i].divid).tabs({
							active: tabstate[i].tabid
						});
					}
				}
				//-- reset section expand collapse state
				if (sectionstate.length > 0) {
					for (var i = 0; i < sectionstate.length; i++) {
						var cursecobj = jQuery("#" + sectionstate[i].divid, _htmldoc);
						if (cursecobj && cursecobj.length > 0) {
							if (_DOCOVAEdition == "SE") {
								var sechead = jQuery(cursecobj).prev("div.ui-widget-header");
							} else {
								var sechead = jQuery(cursecobj).closest("div.cacontainer").find("div.ui-widget-header:first");
							}
							if (sechead && sechead.length > 0) {
								//getting the next element
								$content = sechead.next();
								var fai = sechead.children().first();
								if (sectionstate[i].expanded) {
									fai.removeClass("fa-caret-right").addClass("fa-caret-down");
								} else {
									fai.removeClass("fa-caret-down").addClass("fa-caret-right");
								}
								if (($content.is(":visible") != sectionstate[i].expanded)) {
									var tempfunc = null;
									if (curfield && navigatefield && sectionstate[i].divid == cursection) {
										navigatefield = false;
										tempfunc = function () {
											_tempcuruidoc.goToField(curfield);
										};
									}
									$content.slideToggle(0, tempfunc);
								}
							}
						}
					}
				}
				jQuery(".btngroup").buttonset();

				if(typeof data.info == "string"){
					var parsedInfo = data.info.split(',');
					for (var x = 0; x < parsedInfo.length; x++) {
						var tmp_val = jQuery.trim(parsedInfo[x]).split(':');
						if (jQuery.trim(tmp_val[0])in _targetwin.docInfo) {
							_targetwin.docInfo[jQuery.trim(tmp_val[0])] = jQuery.trim(tmp_val[1]);
						}
					}
				}
				
				if (curfield) {
					if (cursection) {
						var cursecobj = jQuery("#" + cursection, _htmldoc);
						if (cursecobj && cursecobj.length > 0 && jQuery(cursecobj).css("display") == "none") {
							var sechead = jQuery(cursecobj).prev("div.ui-widget-header");
							if (sechead && sechead.length > 0) {
								//getting the next element
								$content = sechead.next();
								var fai = sechead.children().first();
								if ($content.is(":visible")) {
									fai.removeClass("fa-caret-down").addClass("fa-caret-right");
								} else {
									fai.removeClass("fa-caret-right").addClass("fa-caret-down");
								}
								$content.slideToggle(0, function () {
									_tempcuruidoc.goToField(curfield);
								});
							}
						}
					}
				}
				result = true;
			}
		})
		.fail(function () {
			if (console) {
				console.log("Error: refresh was unable to re-load page contents");
			}
		});
		_targetwin.info.isRefresh = false;

		if (result) {
			//post recalc
			_tempcuruidoc.triggerHandlerAsync("postRecalc");
		}

		return result;
	}

	/*--------------------------------------
	 * Function: close
	 * Helper function to close current ui document
	 * Parameters:
	 *    options:
	 *        savePrompt: boolean - if false do not prompt for save, default is true
	----------------------------------------*/
	this.close = function (options) {
		var defaultOptns = {
			savePrompt: true
		};
		var opts = $.extend({}, defaultOptns, options);

		if (this.isDocBeingEdited && opts.savePrompt) {
			_targetwin.SaveBeforeClosing();
		} else {
			_targetwin.CloseDocument(true);
		}
	}

	/*--------------------------------------
	 * Function: save
	 * Helper function to save current ui document
	 * Parameters:
	 *  options:
	 *      andclose: boolean - true - save and close, false - just save
	 *      async: boolean - true - save asynchronously (default), false save synchronously
	 *             Note: async: false will be disabled if using IE browser and DOE plugin
	 *      editMode: boolean - true (default) to leave in edit mode after save, false to switch to read mode
	 *                has no effect if andclose is true, or if Navigate is false.
	 *      Navigate: boolean - true (default) - change to new url after save, false to leave on current page
	 *		onOk: function - function to call on successful save
	 *      onOtherwise: function - function to call if save fails
	----------------------------------------*/
	this.save = function (options) {
		var result = false;
		var defaultOptns = {
			andclose: false,
			async: true,
			editMode: true,
			Navigate: true,
			onOk: function () {},
			onOtherwise: function () {}
		};
		var opts = $.extend({}, defaultOptns, options);
		if (!opts.async && window.top.Docova.IsPluginAlive && Uploader) {
			var isIE = !!navigator.userAgent.match(/Trident/g) || !!navigator.userAgent.match(/MSIE/g);
			if (isIE) {
				opts.async = true; //--override async for IE when using desktop plugin to stop a crash
			}
		}
		var _self = this;
		
		_self.savepending = true;
		
		//querySave
		if (!this.triggerHandlerAsync("querySave")) {
			_self.savepending = false;
			return result;
		}

		if (opts.andclose) {
			//queryClose
			if (!this.triggerHandlerAsync("queryClose")) {
				_self.savepending = false;
				return result;
			}
		}
		
		if (!this.isValidForm()) {
			_self.savepending = false;
			return false;
		}

		allowClose = true; //to let the onUnload event fall through

		if (this.getField("SaveOptions") == "0") {
			return true;
		}
		
		//---If doc has Uploader loaded
		if (typeof _targetwin.DLIUploader1 == "undefined") {
			var Uploader = false;
		} else {
			var Uploader = _targetwin.DLIUploader1;
			_targetwin.GetFileChanges();
		}		
		
		//-- special case of mobile phone interface put up a loading message
		if(typeof docInfo !== "undefined" && docInfo !== null && docInfo.isMobile){
			try{
				if(window.parent && typeof window.parent.showLoadingMessage == "function"){
					window.parent.showLoadingMessage("saving...");
				}
			}catch(e){}
		}else if(!Uploader){
			//-- display spinner during save
			jQuery("#loading_msg").show();	
		}	
		
		//handle data talbes data..stores the data tables json in a hidden field datatablename_values
		//in commonfunctions.js		
		handleDataTablesData(_htmldoc);
		
		//----- get Docova Editor ----------------
		try {
			_targetwin.saveDocovaRichText();
		} catch (e) {}

		try {
			_targetwin.saveDocovaRichTextTinyMCE();
		} catch (e) {}
		
		if (_DOCOVAEdition == "Domino") {
			//**** special case for editing of a profile document ****
			if (this.isProfile) {
				//-- update the back end doc with any changes in the UI
				var tempuifields = _self.getAllFields(true);
				var tempdoc = _self.document;
				var backendfieldarr = tempdoc.fieldBuffer;
				if (tempuifields) {
					for (var key in tempuifields) {
						if (tempuifields.hasOwnProperty(key)) {
							if (key.toLowerCase() !== "__click" && key.indexOf("%%") < 0 && key.indexOf("%25%25") < 0) {
								var updatefield = true;
								var fieldtype = "";
								if (backendfieldarr && backendfieldarr[key.toLowerCase()]) {
									var docovaField = backendfieldarr[key.toLowerCase()];
									fieldtype = docovaField.type;
									if (docovaField.modified || docovaField.removeField) {
										updatefield = false;
									}
								}
								if (updatefield) {
									var sourceval = tempuifields[key];
									tempdoc.setField(key, sourceval, fieldtype, true);
								}
							}
						}
					}
				}

				if (tempdoc.save()) {
					_self.saved = true;
					jQuery("#loading_msg").hide();	
					_self.savepending = false;
					opts.onOk();
					if (opts.andclose) {
						_self.close({
							"savePrompt": false
						});
					}
					result = true;
					return result;
				} else {
					_self.saved = false;
					jQuery("#loading_msg").hide();	
					_self.savepending = false;
					opts.onOtherwise();
					return result;
				}
			} //*** end special case of profile document being edited
		}

		//sync changes made in backend document...only when there isn't a
		//corresponding field on the UI document
		if (this.document) {
			var syncChanges = false;
			var backendfieldarr = this.document.fieldBuffer;
			var fieldnames = this.getFieldNames();
			var backendXmlStr = "<Request><Fields>";
			for (field in backendfieldarr) {
				var docovaField = backendfieldarr[field];
				if (docovaField.modified || docovaField.removeField) {

					if (!fieldnames || !fieldnames[docovaField.name.toLowerCase()]) {
						//field was modified in the backend but is not on the ui..so we generate the xml
						//that the query save agent can parse and do the appropriate things.
						backendXmlStr += docovaField.toXML;
						syncChanges = true;
					}
				}
			}
			backendXmlStr += "</Fields></Request>";

			if (syncChanges)
				this.setField("tmpBackendChangesXML", backendXmlStr)
		}

		//------ handle workflow if any------------------
		if (!this.isWorkflowCreated && this.createWorkflowInDraft && doc.tmpWorkflowDataXml && doc.tmpWorkflowDataXml.value == "") {
			_targetwin.ProcessWorkflow("CREATE");
		}

		//-----If multiple attachment sections exist, then manage storing file lists for each
		if (this.hasMultiAttachmentSections == "1") {
			try {
				_targetwin.StoreMultiCtrlFileNames();
			} catch (e) {}
		}

		//Content paging support for Applications needs to be modified. Left out here.

		//perform input translation
		$("[ftranslate!=''][ftranslate]").each(function () {
			var ftresult = eval($(this).attr('ftranslate'));

			if (typeof ftresult != 'undefined' && ftresult != "") {
				var fldid = "";
				//for checkboxes/radio we store the field name in ftranslatefieldname
				if (typeof $(this).attr("ftranslatefieldname") !== 'undefined')
					fldid = $(this).attr("ftranslatefieldname");
				else {
					fldid = $(this).attr('id') ? $(this).attr('id') : $(this).attr('name');
				}
				Docova.Utils.setField({
					field: fldid,
					value: ftresult
				});
			}
		})


		//-- has Uploader with save and close
		if (Uploader && opts.andclose) {
			if (this.Mode == "dle") {
				Uploader.Submit({
					Navigate: false,
					GetResults: true,
					onOk: function () {

						var data = Uploader.SubmitResultPage;
						if (_self.isNewDoc) {
							var savedUnid = (_self.document ? _self.document.unid : "");
							if (data.indexOf("url;") == 0) {
								var tmpurlarray = data.split(";")
									if (tmpurlarray.length == 3) {
										savedUnid = tmpurlarray[2];
									}
							}
							if (savedUnid == "") {
								var pos1 = data.toLowerCase().indexOf(".nsf/0/");
								if (pos1 > -1) {
									var pos2 = data.toLowerCase().indexOf("?editdocument", pos1)
										savedUnid = data.slice(pos1 + 7, pos2);
								}
							}
							if (_self.document) {
								_self.document.isNewDocument = false;
								_self.document.unid = savedUnid;
							}
							_self.isNewDoc = false;
							if (_self.docID == "") {
								_self.docID = savedUnid;
							}
						}

						_self.saved = true;
						jQuery("#loading_msg").hide();	
						_self.savepending = false;
						_self.triggerHandlerAsync("postSave");
						doc.tmpDleDataXml.value = Uploader.SubmitResultPage;
						window.external.DocLogic_SubmitOk(Uploader.SubmitResultPage);
						result = true;
						return result;
					},
					onOtherwise: function () {
						_self.saved = false;
						jQuery("#loading_msg").hide();
						_self.savepending = false;
						//_self.triggerHandlerAsync("postSave");
						doc.tmpDleDataXml.value = "<Results>";
						doc.tmpDleDataXml.value += "<Status>FAILED</Status>";
						doc.tmpDleDataXml.value += "<Message>Failed submitting data to server.</Message>";
						doc.tmpDleDataXml.value += "<Code>ERR_" + retValue + "</Code>";
						doc.tmpDleDataXml.value += "</Results>";
						var ResultXml = "<Results><Status>FAILED</Status><Message>Failed submitting data to server.</Message><Code>ERR_1</Code></Results>";
						window.external.DocLogic_SubmitFailed(ResultXml);
						return result;
					}
				});
			} else {
				Uploader.EnableFileCleanup = 1;
				_targetwin.removeEditInPlaceLogs();
				$("#isSave", _htmldoc).val("");
				if(opts.async){
					result = true; //--set result to true since following code will not wait for response
				}
				Uploader.Submit({
					async: opts.async,
					GetResults: true,
					onOk: function () {

						var data = Uploader.SubmitResultPage;
						if (_self.isNewDoc) {
							var savedUnid = (_self.document ? _self.document.unid : "");
							if (data.indexOf("url;") == 0) {
								var tmpurlarray = data.split(";")
									if (tmpurlarray.length == 3) {
										savedUnid = tmpurlarray[2];
									}
							}
							if (savedUnid == "") {
								var pos1 = data.toLowerCase().indexOf(".nsf/0/");
								if (pos1 > -1) {
									var pos2 = data.toLowerCase().indexOf("?editdocument", pos1)
										savedUnid = data.slice(pos1 + 7, pos2);
								}
							}
							if (_self.document) {
								_self.document.isNewDocument = false;
								_self.document.unid = savedUnid;
							}
							_self.isNewDoc = false;
							if (_self.docID == "") {
								_self.docID = savedUnid;
							}
						}

						_self.saved = true;
						jQuery("#loading_msg").hide();	
						_self.triggerHandlerAsync("postSave");
						_self.savepending = false;
						opts.onOk();
						result = true;
						return result;
					},
					onOtherwise: function () {
						//_self.triggerHandlerAsync("postSave");
						_self.saved = false;
						_self.savepending = false;
						jQuery("#loading_msg").hide();
						opts.onOtherwise();
						return result;
					}
				}); //Tabs are closed in the response returned by the wqs agent
			}

		//-- has Uploader but no close on save
		} else if (Uploader && !opts.andclose) {
			Uploader.EnableFileCleanUp = 1;
			_targetwin.removeEditInPlaceLogs();
			$("#isSave", _htmldoc).val("1");
			if(opts.async){
				result = true; //--set result to true since following code will not wait for response
				
				//-- if pending actions are on the queue we need to disable them until after the save
				if(typeof _pendingactionstimer !== "undefined" && typeof _pendingactionstimer !== null){
					window.clearTimeout(_pendingactionstimer);
					_pendingactionstimer = null;
				}
			}
			Uploader.Submit({
				Navigate: opts.Navigate,
				GetResults: true,
				async: opts.async,
				onOk: function () {
					var data = Uploader.SubmitResultPage;
					if (_self.isNewDoc) {
						var savedUnid = (_self.document ? _self.document.unid : "");
						if (data.indexOf("url;") == 0) {
							var tmpurlarray = data.split(";")
								if (tmpurlarray.length == 3) {
									savedUnid = tmpurlarray[2];
								}
						}
						if (savedUnid == "") {
							var pos1 = data.toLowerCase().indexOf(".nsf/0/");
							if (pos1 > -1) {
								var pos2 = data.toLowerCase().indexOf("?editdocument", pos1)
									savedUnid = data.slice(pos1 + 7, pos2);
							}
						}
						if (_self.document) {
							_self.document.isNewDocument = false;
							_self.document.unid = savedUnid;
						}
						_self.isNewDoc = false;
						if (_self.docID == "") {
							_self.docID = savedUnid;
						}
					}

					if (opts.Navigate) {
						if (!opts.editMode) {
							//TODO - may need to add check for opts.Navigate and !opts.editMode to switch from ?editdocument to ?opendocument
						}
					}
					_self.saved = true;
					jQuery("#loading_msg").hide();	
					_self.triggerHandlerAsync("postSave");
					_self.savepending = false;
					opts.onOk();
					result = true;
					//-- if pending actions are on the queue we need to trigger them now that save is completed
					if(typeof _pendingactions !== "undefined" && _pendingactions !== null && _pendingactions.length > 0){
						_ProcessPendingActions();
					}
					return result;
				},
				onOtherwise: function () {
					//_self.triggerHandlerAsync("postSave");
					_self.saved = false;
					jQuery("#loading_msg").hide();	
					opts.onOtherwise();
					return result;
				}
			}); //reloads new url returned by wqs agent

		//-- no Uploader with save and close
		} else if (!Uploader && opts.andclose) {
			$("#isSave", _htmldoc).val("");
			var frm = $("form:first", _htmldoc);
			if (_DOCOVAEdition == "SE") {
				var actionUrl = this.serverURL + (frm.attr("action").slice(0, 1) == "/" ? "" : "/") + frm.attr("action");
			} else {
				var actionUrl = this.serverURL + frm.attr("action");
			}
			var formData = $("form:first", _htmldoc).serializeArray();
			//getAllDateTimeFields in commonfunctions.js
			formData.push({
				name: "__dtmfields",
				value: getAllDateTimeFields(_htmldoc)
			});
			formData.push({
				name: "__numfields",
				value: getAllNumericFields(_htmldoc)
			});
      
      			formData.push({
				name: "__datatablenames",
				value: getAllDataTablesNames(_htmldoc)
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
						value: null
					});
				}
			});
			
			for (var i = 0; i < formData.length; i++) {
				var key = formData[i].name.toLowerCase();
				//in commonfunctions.js
				if (isMultiValue(key, _htmldoc)) {
					formData[i].value = processMultiValue(_htmldoc, key, formData[i].value);
				}
			}

			if(opts.async){
				result = true; //--set result to true since following code will not wait for response
			}
			$.ajax({
				url: actionUrl,
				type: "POST",
				data: formData,
				async: opts.async,
				dataType: "text"
			}).done(function (data) {
				if (data) {
					if (data.indexOf("url;") == 0) {
						var tmpurlarray = data.split(";")
							if (tmpurlarray.length >= 2) {
								if (_self.isNewDoc) {
									if (tmpurlarray.length >= 3) {
										var savedUnid = tmpurlarray[2];
										if (_self.document) {
											_self.document.isNewDocument = false;
											_self.document.unid = savedUnid;
										}
										_self.isNewDoc = false;
										if (_self.docID == "") {
											_self.docID = savedUnid;
										}
									}
								}

								Docova.Utils.replaceURL(tmpurlarray[1], _htmldoc);
								_self.saved = true;
								jQuery("#loading_msg").hide();
								_self.savepending = false;
								_self.triggerHandlerAsync("postSave");
								opts.onOk();
								result = true;
								return result;
							}
					}
				} else {
					_self.saved = false;
					_self.savepending = false;
					jQuery("#loading_msg").hide();
					//_self.triggerHandlerAsync("postSave");
					opts.onOtherwise();
					return result;
				}
			})
			.fail(function () {
				_self.saved = false;
				_self.savepending = false;
				jQuery("#loading_msg").hide();	
				opts.onOtherwise();
				return result;
			});

		//-- no Uploader with no close on save
		} else if (!Uploader && !opts.andclose) {
			var frm = $("form:first", _htmldoc);
			if (_DOCOVAEdition == "SE") {
				var actionUrl = this.serverURL + (frm.attr("action").slice(0, 1) == "/" ? "" : "/") + frm.attr("action");
			} else {
				var actionUrl = this.serverURL + frm.attr("action");
			}

			$("#isSave", _htmldoc).val("1")
			var formData = $("form:first", _htmldoc).serializeArray();
			formData.push({
				name: "__dtmfields",
				value: getAllDateTimeFields(_htmldoc)
			});
			formData.push({
				name: "__numfields",
				value: getAllNumericFields(_htmldoc)
			});
			formData.push({
				name: "__datatablenames",
				value: getAllDataTablesNames(_htmldoc)
			});
			for (var i = 0; i < formData.length; i++) {
				var key = formData[i].name.toLowerCase();
				//in commonfunctions.js
				if (isMultiValue(key, _htmldoc)) {
					formData[i].value = processMultiValue(_htmldoc, key, formData[i].value);
				}

			}
			if(opts.async){
				result = true; //--set result to true since following code will not wait for response
			}
			$.ajax({
				url: actionUrl,
				type: "POST",
				data: formData,
				async: opts.async,
				dataType: "text"
			}).done(function (data) {
				if (data) {
					if (data.indexOf("url;") == 0) {
						var tmpurlarray = data.split(";")
							if (tmpurlarray.length >= 2) {
								if (_self.isNewDoc) {
									if (tmpurlarray.length >= 3) {
										var savedUnid = tmpurlarray[2];
										if (_self.document) {
											_self.document.isNewDocument = false;
											_self.document.unid = savedUnid;
										}
										_self.isNewDoc = false;
										if (_self.docID == "") {
											_self.docID = savedUnid;
										}
									}
								}

								if (opts.Navigate) {
									var tmpnewurl = tmpurlarray[1];
									if (!opts.editMode) {
										if (_DOCOVAEdition == "SE") {
											var pos = tmpnewurl.toLowerCase().indexOf("wviewform");
										} else {
											var pos = tmpnewurl.toLowerCase().indexOf("?editdocument");
										}
										if (pos > -1) {
											if (_DOCOVAEdition == "SE") {
												tmpnewurl = tmpnewurl.replace('wViewForm','wReadDocument');
												var pos = tmpnewurl.indexOf('?');
												tmpnewurl = docInfo.PortalWebPath + '/wReadDocument/' + _self.docID + (tmpnewurl.slice(pos).replace('AppID', 'ParentUNID'));
											} else {
												tmpnewurl = tmpnewurl.slice(0, pos) + "?opendocument" + tmpnewurl.slice(pos + 13);
											}
										}
									}
									
									Docova.Utils.replaceURL(tmpnewurl, _htmldoc);
								}

								_self.saved = true;
								_self.savepending = false;
								jQuery("#loading_msg").hide();
								_self.triggerHandlerAsync("postSave");
								opts.onOk();
								result = true;
								return result;
							}
					}
				} else {
					_self.saved = false;
					_self.savepending = false;
					jQuery("#loading_msg").hide();	
					//_self.triggerHandlerAsync("postSave");
					opts.onOtherwise();
					return result;
				}
			})
			.fail(function () {
				_self.saved = false;
				_self.savepending = false;
				jQuery("#loading_msg").hide();	
				opts.onOtherwise();
				return result;
			});

		}
		return result;
	}

	/*--------------------------------------
	 * Function: edit
	 * Helper function to put current ui document into edit mode
	---------------------------------------*/
	this.edit = function () {
		if (!_targetwin.CanModifyDocument()) {
			return false;
		};
		if(typeof docInfo != "undefined" && docInfo !== null && docInfo.isMobile){
			try{
				if(window.parent && typeof window.parent.showLoadingMessage == "function"){
					window.parent.showLoadingMessage();
				}
			}catch(e){}
		}else{
			jQuery("#loading_msg").show();			
		}
		_targetwin.HandleEditClick();
	}

	/*--------------------------------------
	 * Function: deleteDocument
	 * Helper function to delete current ui document
	 * --------------------------------------*/
	this.deleteDocument = function () {
		if (this.isDocBeingEdited) {
			return false;
		}

		if (!_targetwin.CanModifyDocument()) {
			return false;
		};

		if (this.document.deleteDocument()) {
			_targetwin.CloseDocument();
		}
	}

	/*--------------------------------------
	 * Function: openDialog
	 * Helper function to create a dialog with a form in it
	---------------------------------------*/
	this.openDialog = function (options) {
		var defaultOptns = {
			formname: "",
			docid: "",
			title: "",
			height: 500,
			width: 400,
			nsfpath: "",
			buttons: [],
			resizable: true,
			inherit: false,
			sourcewindow: window,
			sourcedocument: document,
			isresponse: false,
			onClose: function () {}
		};

		var opts = $.extend({}, defaultOptns, options);
		opts.nsfpath = this.appURL;
		opts.sourcedocument = _htmldoc;

		return Docova.Utils.openDialog(opts);
	}

	/*--------------------------------------
	 * Function: getParentWindow
	 * Helper function to get parent window (window dialog was opened from) from a dialog
	---------------------------------------*/
	this.getParentWindow = function (options) {
		var defaultOptns = {
			formname: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var _dlgId = opts.formname;
		var _parentwin = window.top.Docova.GlobalStorage[_dlgId].sourcewindow;
		return _parentwin;
	}

	/*--------------------------------------
	 * Function: getParentUIDocument
	 * Helper function to get parent window UIDocument (window dialog was opened from) from a dialog
	---------------------------------------*/
	this.getParentUIDocument = function (options) {
		var defaultOptns = {
			formname: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var _dlgId = opts.formname;
		var _parentwin = window.top.Docova.GlobalStorage[_dlgId].sourcewindow;
		return _parentwin.Docova.UIDocument;
	}

	/*--------------------------------------
	 * Function: getParentDocument
	 * Helper function to get the parent document (document dialog was opened from) from a dialog form
	---------------------------------------*/
	this.getParentDocument = function (options) {
		var defaultOptns = {
			formname: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var _dlgId = opts.formname;
		var _parentdoc = window.top.Docova.GlobalStorage[_dlgId].sourcedocument;
		return _parentdoc;
	}

	/*--------------------------------------
	 * Function: getDialogDocument
	 * Helper function to get parent window UIDocument (window dialog was opened from) from a dialog
	---------------------------------------*/
	this.getDialogDocument = function (options) {
		var defaultOptns = {
			formname: ""
		}
		var opts = $.extend({}, defaultOptns, options);
		var _dlgDoc = window.top.$("#" + opts.formname + "IFrame")[0].contentWindow.document
			return _dlgDoc;
	}

	/*--------------------------------------
	 * Function: getVar
	 * Helper function to get a variable via UIDocument
	---------------------------------------*/
	this.getVar = function (options) {
		var defaultOptns = {
			varname: ""
		}
		var opts = $.extend({}, defaultOptns, options);
		return _targetwin[opts.varname]
	}

	/*--------------------------------------
	 * Function: call
	 * Helper function to call a function via UIDocument
	 * NOT WORKING AT THIS TIME
	---------------------------------------*/
	this.callFn = function (options) {
		var defaultOptns = {
			fn: null
		}
		var opts = $.extend({}, defaultOptns, options);
		//alert("fn: " + opts.fn)
		console.log("Im in the callFn function")
		//if(opts.fn && typeof opts.fn == "function"){ window[opts.fn] }
		//window[opts.fn]();
	}

	/*--------------------------------------
	 * Function: getWorkflowObj
	 * Creates a new DOCOVA UI Workflow object
	---------------------------------------*/
	this.getWorkflowObj = function () {
		return new DocovaUIWorkflow();
	}

	/*--------------------------------------
	 * Function: getVersionObj
	 * Creates a new DOCOVA UI Version object
	---------------------------------------*/
	this.getVersionObj = function () {
		return new DocovaUIVersion();
	}

	/*--------------------------------------
	 * Function: getAttachmentsObj
	 * Creates a new DOCOVA UI Attachments object
	 * Parameter : optional : name of uploader to retrieve
	---------------------------------------*/
	this.getAttachmentsObj = function (upname) {

		if (upname && upname != "") {
			var ctrlno = 1;
			var uploader = window["DLIUploader" + ctrlno];
			while (uploader) {
				if (uploader.refId == upname)
					break;
				ctrlno++;
				uploader = window["DLIUploader" + ctrlno];
			}
		} else {
			var uploader = window["DLIUploader" + ctrlno];
		}

		return new DocovaUIAttachments(uploader);
	}

	/*--------------------------------------
	 * Function: loadProfileFields
	 * For profile document loads values from back-end record
	---------------------------------------*/
	this.loadProfileFields = function () {
		if (!this.isProfile) {
			return;
		}

		var uiw = Docova.getUIWorkspace(document);
		appobj = uiw.getCurrentApplication();
		if (appobj) {
			var backenddoc = this.document;
			var profilefields = appobj.getProfileFields(this.formName, "*", this.profileKey);
			if (profilefields) {
				for (var key in profilefields) {
					if (profilefields.hasOwnProperty(key)) {
						if (key.toLowerCase() !== "__click" && key.indexOf("%%") < 0 && jQuery.trim(key) != "") {
							var sourceval = profilefields[key];
							backenddoc.setField(key, sourceval);
							if (backenddoc.fieldBuffer[key.toLowerCase()]) {
								backenddoc.fieldBuffer[key.toLowerCase()].modified = false;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Validate all form inputs (text, dates, radio, checkboxes, textarea and attachments)
	 * 
	 * @return boolean
	 */
	this.isValidForm = function(silent)
	{
		
		var $inputs = $(":input[isrequired='Y']").filter ( function ( index){
			return ( $(this).closest(".datatable").length == 0 )	
		});

		if (!$inputs.length) { return true; }
		
		//in commonfunctions.js
		return validateRequiredFields ( $inputs, silent); 
	}

} //end DocovaUIDocument class

/*
 * Class: DocovaUIView
 * User interface classes and methods for interaction with DOCOVA view/view documents and on-screen DOM elements
 * Typically accessed from Docova.currentView()
 * Parameter
 **/
function DocovaUIView() {
	var _view = null;

	this.viewobj = (typeof objView === 'undefined' ? null : objView);
	this.username = docInfo.UserName;
	this.usernameAB = docInfo.UserNameAB;
	this.usernameCN = docInfo.UserNameCN;
	this.queryString = docInfo.Query_String;
	this.serverName = docInfo.ServerName;
	this.serverURL = docInfo.ServerUrl;
	this.appURL = docInfo.NsfName;
	this.appFilename = docInfo.AppFileName;
	if (_DOCOVAEdition == "Domino") {
		this.appFilepath = docInfo.AppFilePath;
	}
	this.appKey = docInfo.LibraryKey;
	this.appID = docInfo.AppID;
	this.viewName = docInfo.ViewName;
	this.viewAlias = docInfo.ViewAlias;
	this.sessionDateFormat = docInfo.SessionDateFormat;
	this.homeURL = docInfo.PortalWebPath;

	this._triggers = {};

	/*
	 * Properties:
	 */
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaUIView";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'documents', {
		get: function () {
			return this.getSelectedDocuments();
		},
		enumerable: true
	});
	Object.defineProperty(this, 'view', {
		get: function () {
			if (!_view) {
				var app = new DocovaApplication({
						"appid": this.appID
					});
				_view = new DocovaView(app, this.viewName);
			}
			return _view;
		},
		enumerable: true
	});

	/*
	 * Methods:
	 */
	this.on = function (event, callback) {
		if (!this._triggers[event])
			this._triggers[event] = [];
		this._triggers[event].push(callback);

	}

	//call the last funciton hookped up to this event async and return a value
	this.triggerHandlerAsync = function (trigger, context) {
		var retarr = [];
		if (this._triggers[trigger]) {
			return this._triggers[trigger][this._triggers[trigger].length - 1].call(context);

		} else if (typeof window[trigger] === 'function') {
			return window[trigger].call(context);
		}
		return true;
	}

	//call all hooked up callbacks for this event synchrously
	this.triggerHandler = function (trigger, context, remove) {
		if (this._triggers[trigger]) {
			var tmpArr = this._triggers[trigger];

			for (i in this._triggers[trigger]) {
				//return _triggers[trigger][i](context);
				setTimeout(this._triggers[trigger][i](context), 0);
				if (remove) {
					this._triggers[trigger].splice(0, 1);
				}
			}
		}
	}

	this.getSelectedDocuments = function () {
		var doccol = new DocovaCollection(this);

		var selectedrows = this.viewobj.selectedEntries;
		if (selectedrows.length > 0) {
			for (var k = 0; k < selectedrows.length; k++) {
				if (selectedrows[k] !== "") {
					doccol.addEntry(new DocovaDocument(this, selectedrows[k], selectedrows[k]));
				}
			}
		}

		return doccol;
	}

	this.selectEntry = function (options) {
		var defaultOptns = {
			entryid: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		this.viewobj.ToggleSelectEntryById(opts.entryid, "check")
	}
	this.deselectEntry = function (options) {
		var defaultOptns = {
			entryid: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		this.viewobj.ToggleSelectEntryById(opts.entryid, "uncheck")
	}
	this.getAllEntryIDs = function () {
		this.viewobj.GetAllEntryIds();
		return this.viewobj.allEntries;
	}
	this.getSelectedEntries = function () {
		return this.viewobj.selectedEntries;
	}
	this.getCurrentEntry = function () {
		return this.viewobj.currentEntry;
	}
	this.selectAllEntries = function () {
		this.viewobj.SelectAllEntries();
	}
	this.deselectAllEntries = function () {
		this.viewobj.DeselectAllEntries()
	}
	this.getCurrentRowIndex = function () {
		var _entryid = this.viewobj.currentEntry;
		var _dataRow = $("#" + _entryid)
			return _dataRow[0].rowIndex;
	}
	this.moveEntryHighlight = function (options) {
		var defaultOptns = {
			direction: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.direction == "") {
			return
		};
		this.viewobj.MoveEntryHighlight(opts.direction);
	}
	this.getFirstEntry = function () {
		return this.viewobj.GetEntryByRow(1);
	}
	this.getLastEntry = function () {
		var _rowcount = this.getRowCount();
		return this.viewobj.GetEntryByRow(_rowcount);
	}
	this.getEntryByRow = function (options) {
		var defaultOptns = {
			row: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.row == "") {
			return null;
		}
		return this.viewobj.GetEntryByRow(opts.row);
	}
	this.getEntryByID = function (options) {
		var defaultOptns = {
			entryid: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.entryid == "") {
			return null;
		}
		var _dataRow = $("#" + opts.entryid);
		if (!_dataRow) {
			return null;
		}
		return this.viewobj.GetEntryByRow(_dataRow[0].rowIndex);
	}
	this.getNextEntry = function (options) {
		var defaultOptns = {
			entry: null
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.entry == null) {
			return null;
		}
		return this.viewobj.GetEntryByRow(opts.entry.rowIdx + 1);
	}
	this.getPreviousEntry = function (options) {
		var defaultOptns = {
			entry: null
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.entry == null) {
			return null;
		}
		return this.viewobj.GetEntryByRow(opts.entry.rowIdx - 1);
	}
	this.getRowCount = function () {
		return this.viewobj.dataTable.rows.length - 1;
	}
	this.expandAll = function () {
		this.viewobj.ExpandAll();
	}
	this.collapseAll = function () {
		this.viewobj.CollapseAll();
	}
	this.highlightEntry = function (options) {
		var defaultOptns = {
			entryid: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.entryid == "") {
			return null;
		}
		this.viewobj.HighlightEntryById(opts.entryid);
	}
	this.exportToExcel = function (options) {
		var defaultOptns = {
			selectedonly: false
		};
		var opts = $.extend({}, defaultOptns, options);
		this.viewobj.ExportToExcel(opts.selectedonly);
	}
	/*-------
	 * Function: copy
	 * Copies the current selected document or all selected documents in a ui view
	 * Inputs: type - string - one of the following
	 *                current - currently selected document
	 *                selected - all selected documents
	 * Returns: sets Docova cookies that the paste function leverages, clipaction and clipdata
	---------*/
	this.copy = function (options) {
		var defaultOptns = {
			type: "current"
		};
		var opts = $.extend({}, defaultOptns, options);
		var clipdata = "";
		var clipaction = "copy";
		var clipview = "<srcView>" + docInfo.ViewName + "</srcView>";
		clipview += "<srcApp>" + docInfo.AppID + "</srcApp>";
		if (_DOCOVAEdition == "Domino") {
			clipview += "<srcAppPath>" + docInfo.AppFilePath + "</srcAppPath>";
		}

		if (opts.type == "current") {
			clipdata += "<unid>" + this.viewobj.currentEntry + "</unid>"
		}

		if (opts.type == "selected") {
			//copy all selected
			if (this.viewobj.selectedEntries.length == 0) {
				alert("Please select one or more documents to copy")
				return;
			}

			for (var k = 0; k < this.viewobj.selectedEntries.length; k++) {
				clipdata += "<unid>";
				clipdata += this.viewobj.selectedEntries[k];
				clipdata += "</unid>";
			}
		}

		window.top.Docova.Utils.setCookie({
			keyname: "clipaction",
			keyvalue: clipaction
		});
		window.top.Docova.Utils.setCookie({
			keyname: "clipdata",
			keyvalue: clipdata
		});
		window.top.Docova.Utils.setCookie({
			keyname: "clipview",
			keyvalue: clipview
		});
	}

	/*----------
	 * Function: paste
	 * Paste clipaction and clipdata set by copy or cut
	 * Returns OK if paste succeeded, returns FAILED if paste FAILED
	 *-----------*/
	this.paste = function () {
		var clipdata = window.top.Docova.Utils.getCookie({
				keyname: "clipdata"
			});
		var clipaction = window.top.Docova.Utils.getCookie({
				keyname: "clipaction"
			});
		var clipview = window.top.Docova.Utils.getCookie({
				keyname: "clipview"
			});

		if (!clipdata || !clipaction || !clipview) {
			return false;
		}
		
		if(typeof window['Querypaste'] == "function"){
			var oktopaste = true;
			try{
				oktopaste = Querypaste();
			}catch(e){}
			if(!oktopaste){
				return false;
			}
		}		

		var request = "<Request>"
			request += "<Action>PASTEDOCUMENTS</Action>"
			request += "<clipaction>" + clipaction + "</clipaction>"
			request += clipview
			request += clipdata
			request += "<targetApp>" + docInfo.AppID + "</targetApp>"
			request += "</Request>"

			//clear cookies
			window.top.Docova.Utils.setCookie({
				keyname: "clipaction",
				keyvalue: ""
			});
		window.top.Docova.Utils.setCookie({
			keyname: "clipdata",
			keyvalue: ""
		});

		var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ViewServices?OpenAgent";
		var result;
		var resulttext;
		$.ajax({
			type: "POST",
			url: processUrl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				result = true;
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					resulttext = xmlobj.find("Result[ID=Ret1]").text();
					
					if(typeof window['Postpaste'] == "function"){
						try{
							Postpaste();
						}catch(e){}
					}							
				}
			},
			error: function () {
				result = false
					resulttext = "FAILED"
					//provide an error message
			}
		})
		this.viewobj.Refresh(true, true);
		this.viewobj.HighlightEntryById(resulttext);
		return resulttext;
	}

	/*----------
	 * Function: refresh
	 * Refreshes the current view and optionally highlights the selected document
	 * Returns
	 *-----------*/
	this.refresh = function (options) {
		var defaultOptns = {
			loadxml: true,
			loadxsl: true,
			restorestate: true,
			highlightentryid: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		this.viewobj.Refresh(opts.loadxml, opts.loadxsl, opts.restorestate)
		if (opts.highlightentryid != "") {
			this.viewobj.HighlightEntryById(opts.highlightentryid)
		}
		return;
	}
	this.deleteSelected = function (options) {
		var defaultOptns = {
			currentonly: false
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.currentonly == true) {
			if (this.viewobj.currentEntry == "") {
				return false;
			}
		}
		this.viewobj.RemoveSelectedEntries(opts.currentonly);
		return true;
	}
}
//End DocovaUIView class

/*
 * Class: DocovaUIWorkflow
 * User interface properties and methods for interaction with DOCOVA workflow in a document.
 * Typically accessed/instantiated from a DocovaUIDocument's getWorkflowObj() method
 * Eg:
 * var uidoc = Docova.getUIDocument();
 * var wf = uidoc.getWorkflowObj();
 * Can also be instantiated with new
 * Eg:
 * var wf = new DocovaUIWorkflow();
 **/
function DocovaUIWorkflow() {
	this.docStatus = docInfo.docStatus;
	this.docStatusNo = docInfo.docStatusNo;
	this.hasWorkflow = docInfo.hasWorkflow;
	this.hasLifeCycle = docInfo.hasLifecycle;
	this.isOriginator = docInfo.isOriginator;
	this.isPendingParticipant = docInfo.isPendingParticipant;
	this.isApprover = docInfo.isApprover;
	this.isReviewer = docInfo.isReviewer;
	this.isPublisher = docInfo.isPublisher;
	this.isStartStep = docInfo.isStartStep;
	this.isEndStep = docInfo.isEndStep;
	this.isCompleteStep = docInfo.isCompleteStep;
	this.isApproveStep = docInfo.isApproveStep;
	this.allowUpdate = docInfo.AllowUpdate;
	this.allowPause = docInfo.AllowPause;
	this.allowCustomize = docInfo.AllowCustomize;
	this.allowBacktrack = docInfo.AllowBacktrack;
	this.allowInfoRequest = docInfo.AllowInfoRequest;
	this.allowCancel = docInfo.AllowCancel;
	this.hasMultiWorkflow = docInfo.HasMultiWorkflow;
	this.wfStepStatus = docInfo.wfStepStatus;
	this.wfAction = docInfo.wfAction;
	this.wfType = docInfo.wfType;

	/*-------------------------------------------------------------------------
	 * Function: startWorkflow
	 * Starts workflow on the document
	 *-------------------------------------------------------------------------*/
	this.startWorkflow = function () {
		return StartWorkflow();
	}
	/*--------------------------------------------------------------------------
	 * Function: declineWorkflowStep
	 * Process the 'Decline' workflow step action
	 * Inputs: cb - callback function (optional) - function to call with result
	 *--------------------------------------------------------------------------*/
	this.declineWorkflow = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		return WorkflowHandler("DENY", opts.cb);
	}
	/*--------------------------------------------------------------------------
	 * Function: approveWorkflowStep
	 * Process the 'Approve' workflow step action
	 * Inputs: cb - callback function (optional) - function to call with result
	 *--------------------------------------------------------------------------*/
	this.approveWorkflowStep = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		return WorkflowHandler("APPROVE", opts.cb);
	}
	/*--------------------------------------------------------------------------
	 * Function: completeWorkflowStep
	 * Process the 'Complete' workflow step action
	 * Inputs: cb - callback function (optional) - function to call with result
	 *---------------------------------------------------------------------------*/
	this.completeWorkflowStep = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		return WorkflowHandler("COMPLETE", opts.cb);
	}

	/*-------------------------------------------------------------------------
	 * Function: deleteWorkflowStep
	 * Displays a dialog of workflow steps and prompts the user to select one or more to be deleted
	 * Calls function to deletes the selected steps from the current workflow.
	 * Inputs: cb - callback function (optional) - function to pass on result of deletion
	 *------------------------------------------------------------------------- */
	this.deleteWorkflowStep = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		return DeleteWorkflowStep(opts.cb);
	}
	/*--------------------------------------------------------------------------
	 * Function: finishWorkflow
	 * Process the 'Finish' workflow step action
	 * Inputs: cb - callback function (optional) - function to call with result
	 *--------------------------------------------------------------------------*/
	this.finishWorkflow = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		if (!CanModifyDocument(true)) {
			if (opts.cb && typeof opts.cb == "function") {
				opts.cb(false);
			}
			return false;
		}
		return WorkflowHandler("FINISH", opts.cb);
	}
	/*---------------------------------------------------------------------------
	 * Function: cancelWorkflow
	 * Cancels the current workflow process.
	 * Inputs: cb - callback function (optional) - function to call with result
	 *---------------------------------------------------------------------------*/
	this.cancelWorkflow = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		if (!CanModifyDocument(true)) {
			if (opts.cb && typeof opts.cb == "function") {
				opts.cb(false);
			}
			return false;
		}
		return WorkflowHandler("CANCEL", opts.cb);
	}
	/*---------------------------------------------------------------------------
	 * Function: pauseWorkflow
	 * Pauses the current workflow process.
	 * Inputs: cb - callback function (optional) - function to call with result
	 *---------------------------------------------------------------------------- */
	this.pauseWorkflow = function (options) {
		var defaultOptns = {
			cb: null
		};
		var opts = $.extend({}, defaultOptns, options);
		return WorkflowHandler("PAUSE", cb);
	}
	/*--------------------------------------------------------------------------------
	 * Function: switchWorkflow
	 * Changes the workflow process currently assigned to a document
	 * Inputs: processId - string - id/key of the workflow process to switch to
	 * Returns: boolean - true if workflow data retreived sucessfully, false otherwise
	 *-------------------------------------------------------------------------------*/
	this.switchWorkflow = function (options) {
		var defaultOptns = {
			processid: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var result = false;
		if (opts.processid == "") {
			jQuery("#DataEdit").hide();
			return result;
		};
		// get new data
		var wfUrl = docInfo.PortalWebPath + "/xViewData.xml?ReadForm&view=xmlWorkflowStepsByProcessId&col=2&lkey=" + opts.processid + "&cache=" + Date.now();
		LoadWorkflowSteps(wfUrl);
		result = true;

		return result;
	}
	/*-----------------------------------------------------------------------------
	 * Function: insertWorkflow
	 * Retrieves a specified set of workflow steps and inserts them into an existing workflow.
	 * Start and end steps for the workflow step being inserted are ignored.
	 * Inputs: processid - string - id of workflow sequence to retrieve
	 *         before - string (optional) - title of existing workflow step to insert new workflow steps
	 *                                   ahead of.  If not specified, or if the existing start step is specfied, then the new workflow
	 *                                   will be inserted before the existing end step
	 *        silent - boolean (optional) - true to not display prompt on error retrieving workflow
	 * Returns: boolean - true if workflow is inserted properly, false otherwise
	 *----------------------------------------------------------------------------- */
	this.insertWorkflow = function (options) {
		var defaultOptns = {
			processid: "",
			before: "",
			silent: true
		};
		var opts = $.extend({}, defaultOptns, options);
		return insertWorkflow(opts.processid, opts.before, opts.silent)
	}

	/*-----------------------------------------------------------------------------
	 * Function: changeWorkflowStep
	 * Customizes a workflow step
	 * Inputs:      keytype - string - wfOrder or wfTitle - type of search to use to locate matching step
	 *              key - string - value to match against wfOrder or wfTitle field to locate matchign step
	 *              field - string - xml field name to update
	 *              value - string - new value to update workflow step with
	 *              replacetoken - string (optional) - existing string to search for and replace with the new value
	 *              force - boolean (optional) - allows changing of current/previous workflow steps
	 * Returns: boolean - true if change successful, false otherwise
	 *--------------------------------------------------------------------------- */
	this.changeWorkflowStep = function (options) {
		var defaultOptns = {
			keytype: "",
			key: "",
			field: "",
			value: "",
			replacetoken: "",
			force: false
		};
		var opts = $.extend({}, defaultOptns, options);
		return ChangeWorkflowStep(opts.keytype, opts.key, opts.field, opts.value, opts.replacetoken, opts.force)
	}

	/*------------------------------------------------------------------------------
	 * Function: changeWorkflowStepGroup
	 * Customizes a group of workflow steps
	 * Inputs:      field - string - xml field name to update
	 *              value - string - new value to update workflow step with
	 *              replacetoken - string (optional) - existing string to search for and replace with the new value
	 *              force - boolean (optional) - allows changing of current/previous workflow steps
	 * Returns: boolean - true if change successful, false otherwise
	 *------------------------------------------------------------------------------- */
	this.changeWorkflowStepGroup = function (options) {
		var defaultOptns = {
			field: "",
			value: "",
			replacetoken: "",
			force: false
		};
		var opts = $.extend({}, defaultOptns, options);
		return ChangeWorkflowStepGroup(opts.field, opts.value, opts.replacetoken, opts.force)
	}

	/*---------------------------------------------------------------------------------
	 * Function: deleteWorkflowStepByKey
	 * Removes a specified workflow step
	 * Inputs: keytype - string - workflow xml field to use as a search parameter
	 *         key - string - value to match against specified workflow xml field
	 * Returns: boolean - true if deletion is successful, false otherwise
	 *--------------------------------------------------------------------------------- */
	this.deleteWorkflowStepByKey = function (options) {
		var defaultOptns = {
			keytype: "",
			key: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		return DeleteWorkflowStepByKey(opts.keytype, opts.key)
	}

	/*--------------------------------------------------------------------------------
	 * Function: backtrackWorkflow
	 * Changes the current workflow step to a previous step
	 * Returns: boolean - true if backtrack proceeded successfully, false otherwise
	 *--------------------------------------------------------------------------------- */
	this.backtrackWorkflow = function () {
		return BacktrackWorkflow();
	}

	/*--------------------------------------------------------------------------------
	 * Function: getCurrentWorkflowStep
	 * Locates the current/active workflow step in the workflow recordset
	 * Returns: recordset - recordset object initialized to current workflow step, or false if no current step found
	 *--------------------------------------------------------------------------------- */
	this.getCurrentWorkflowStep = function () {
		return GetCurrentWorkflowStep();
	}

	/*-------------------------------------------------------------------------------
	 * Function: getWorkflowRecordSet
	 * Returns: recordset for the document workflow steps.  Can be used similarly to an ADO record set
	 *-------------------------------------------------------------------------------*/
	this.getWorkflowRecordSet = function () {
		return WorkflowSteps.recordset
	}

} //end DocovaUIWorkflow class

/* Class: DocovaUIVersion
 * User interface class to get version control properties
 * Can get it via DocovaUIDocument
 * eg: var uidoc = Docova.getUIDocument();
 * var vObj = uidoc.getVersionObj();
 * Can also instantiate via new
 * eg: var vObj = new DocovaUIVersion();
 **/
function DocovaUIVersion() {
	this.majorVersion = docInfo.MajorVersion;
	this.minorVersion = docInfo.MinorVersion;
	this.enableVersions = docInfo.enableVersions;
	this.version = docInfo.version;
	this.fullversion = docInfo.fullversion;
	this.previousFullVersion = docInfo.previousFullVersion;
	this.nextFullVersion = docInfo.nextFullVersion;
	this.availableVersionList = docInfo.availableVersionList;
	this.isInitialVersion = docInfo.isInitialVersion;
	this.isSupersededVersion = docInfo.isSupersededVersion;
	this.isDiscardedVersion = docInfo.isDiscardedVersion;
	this.isCurrentVersion = docInfo.isCurrentVersion;
	this.isNewVersion = docInfo.isNewVersion;
	this.strictVersioning = docInfo.strictVersioning;
	this.allowRetract = docInfo.allowRetract;
	this.restrictDrafts = docInfo.restrictDrafts;
	this.restrictLiveDrafts = docInfo.restrictLiveDrafts;
} //end DocovaUIVersion class

/* Class: DocovaUIAttachments
 * User interface class with properties and methods for manipulating attachments
 **/
function DocovaUIAttachments(uploader) {
	this.isFileCIAOEnabled = docInfo.isFileCIAOEnabled;
	this.isFileViewLoggingOn = docInfo.isFileViewLoggingOn;
	this.isFileDownloadLoggingOn = docInfo.isFileDownloadLoggingOn;
	this.maxFiles = docInfo.maxFiles;
	this.allowedFileExtensions = docInfo.allowedFileExtensions;
	this.attachmentsReadOnly = docInfo.attachmentsReadOnly;
	this.attachmentsHidden = docInfo.attachmentsHidden;
	this.templateList = docInfo.templateList;
	this.templateNameList = docInfo.templateNameList;
	this.templateVersionList = docInfo.templateVersionList;
	this.templateType = docInfo.templateType;
	this.templateAutoAttach = docInfo.templateAutoAttach;
	this.attachmentOptions = docInfo.attachmentOptions;
	this.hasAttachmentsSection = docInfo.hasAttachmentsSection;
	this.hasMultiAttachmentSections = docInfo.hasMultiAttachmentSections;
	this.docAttachmentNames = docInfo.docAttachmentNames;
	this.uploader = null;

	if (uploader) {
		this.uploader = uploader;
	}
} //end DocovaUIAttachments class

/* Class: DocovaAgent
 * Back end class with properties and methods for manipulating attachments
 **/
function DocovaAgent(parentobj, name) {
	var _name = "";
	_parentApp = null;
	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'name', {
		get: function () {
			return _name;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Function: Run
	 * Runs the agent
	 * Inputs: noteID - string - the unid of the document to be passed to the agent
	 *         returndata - boolean (optional) - true to return data output by agent, otherwise return boolean
	 *         cb - function (optional) - callback function to call on completion of agent
	 *              if not specified ajax call will be syncronous 
	 * Returns: boolean/string - returns data from the agent as a string if returndata is true
	 *                           otherwise returns boolean true/false indicating whether agent ran 
	 *-------------------------------------------------------------------------------------------------*/
	this.run = function (noteID, returndata , cb) 
	{

		returndata = returndata || false;
		cb = cb || null;

		result = 1;
		if (_name == ""){
			return false;
		}
		var request = "<Request>";
		request += "<Action>RUNAGENT</Action>";
		request += "<Name>" + _name + "</Name>";
		request += "<AppID>" + docInfo.AppID + "</AppID>";
		request += "<NoteUNID>" + (noteID && noteID != "" ? noteID : '') + "</NoteUNID>";

		var requesturl = null;
		if (_DOCOVAEdition == "SE") {
			var noteID = (noteID && noteID != "") ? noteID : '';
			requesturl = "/" + docInfo.NsfName + "/runagent/" + _name + "?AppID=" + docInfo.AppID + "&parentUNID=" + noteID;
		}

		request += "</Request>";
		var xmldata = _requestHelper(request, requesturl, cb);
		if (xmldata) {
			if(returndata){
				result = xmldata;
			}else{
				var retval = _parserHelper(this, xmldata, "text")
				if (retval != "") {
					result = parseInt(retval);
				}
			}
		}

		return result;
	} //--end Run

	/*----------------------------------------------------------------------------------------------
	 * Function: runOnServer
	 * Runs the agent on the server
	 * Inputs: noteID - string - the unid of the document to be passed to the agent
	 * Returns: boolean - true if download successful, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.runOnServer = function (noteID) {
		result = 1;
		if (_name == ""){
			return false;
		}
		var request = "<Request>";
		request += "<Action>RUNAGENTONSERVER</Action>";
		request += "<AppID>" + docInfo.AppID + "</AppID>";
		request += "<Name>" + _name + "</Name>";
		request += "<NoteUNID>" + (noteID && noteID != "" ? noteID : '') + "</NoteUNID>";

		var requesturl = null;
		if (_DOCOVAEdition == "SE") {
			var requesturl = "/" + docInfo.NsfName + "/runagent/" + _name + "?AppID=" + docInfo.AppID + "?parentUNID=" + noteID;
		}

		request += "</Request>";
		var xmldata = _requestHelper(request, requesturl);
		if (xmldata) {
			var retval = _parserHelper(this, xmldata, "text");
			if (retval != "") {
				result = parseInt(retval);
			}
		}

		return result;
	} //--end RunOnServer

	/*----------------------------------------------------------------------------------------------
	 * Function: _requestHelper
	 * Internal function to help with making ajax requests for document data xml
	 * Inputs: request - string - xml string containing request to services agent
	 *         service - string (optional) - name of service to call - defaults to DocumentServices
	 *         cb - function (optional) - optional callback function to call on completion
	 * Returns: xml string - containing results of request
	 *-------------------------------------------------------------------------------------------------*/
	function _requestHelper(request, service, cb) {
		var xmldata = null;
		if (_DOCOVAEdition == "SE") {
			var serviceurl = service && service != "" ? service : "/" + docInfo.NsfName + "/DocumentServices?OpenAgent&AppID=" + docInfo.AppID;
		} else {
			var serviceurl = _parentApp.filePath + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent";
		}

		jQuery.ajax({
			type: "POST",
			url: serviceurl,
			data: encodeURI(request),
			cache: false,
			async: (typeof cb === "function" ? true : false),
//			dataType: "xml",
			success: function (xml) {
				if(typeof cb === "function"){
					cb(xml);
				}else{
					xmldata = xml;
				}
			},
			error: function () {}
		});

		return xmldata;
	} //--end _requestHelper

	/*----------------------------------------------------------------------------------------------
	 * Function: _parserHelper
	 * Internal function to help with parsing document data out of xml response
	 * Inputs: objself - DocovaView object - current docova view object
	 *         xmldata - xml string - xml string containing document result data
	 *         parsetype - string (optional) - one of the following;
	 *                   text - returns the xml result text
	 *                   xmlnode - returns the xml result node value
	 *                   document - returns a single document object
	 *                   array - returns a document object array
	 *         xmlnodename - string (optional) - if parsetype is xmlnode - this specifies the name of the node
	 * Returns: array of DocovaDocument objects, or a single DocovaDocument object, or null
	 *-------------------------------------------------------------------------------------------------*/
	function _parserHelper(objself, xmldata, parsetype, xmlnodename) {
		var result = null;

		try {
			var xmlobj = $(xmldata);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				if (parsetype == "array" || parsetype == "document") {
					var returnarray = (parsetype == "array");
					if (returnarray) {
						result = new Array();
					}
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
					jQuery(resultxmlobj).find("Document" + (returnarray == true ? "" : ":first")).each(function () {
						var unid = jQuery(this).find("Unid").text();
						var docid = jQuery(this).find("ID").text();
						if (unid && unid != "") {
							docobj = new DocovaDocument(objself, unid, docid);
							if (docobj) {
								if (returnarray == true) {
									result.push(docobj);
								} else {
									result = docobj;
								}
							}
						}
					});
				} else if (parsetype == "xmlnode") {
					if (xmlnodename && xmlnodename !== "") {
						result = jQuery(xmlobj.find("Result[ID=Ret1]")).find(xmlnodename).text();
					}
				} else if (parsetype == "text") {
					result = xmlobj.find("Result[ID=Ret1]").text();
				} else {
					result = true;
				}
			}
		} catch (err) {
			return null;
		}

		return result;
	} //--end _parserHelper

	_name = name;
	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new document
	 *-------------------------------------------------------------------------------------------------*/
	if (parentobj === undefined || parentobj === null) {
		return;
	}
	if (parentobj.constructor_name == "DocovaApplication") {
		_parentApp = parentobj;
	}

} //--end DocovaAgent


/* Class: DocovaAttachment
 * Back end class with properties and methods for manipulating attachments
 **/
function DocovaAttachment(parentdoc, filename, filesize, filedate, fileurl) {
	var _parentDoc = null;
	var _filename = "";
	var _filesize = 0;
	var _filedate = "";
	var _fileurl = "";

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'filename', {
		get: function () {
			return _filename;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'fileext', {
		get: function () {
			var ext = "";
			var pos = _filename.lastIndexOf(".");
			if (pos > -1) {
				ext = _filename.slice(pos + 1);
			}
			return ext;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'filesize', {
		get: function () {
			return _filesize;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'filedate', {
		get: function () {
			return _filedate;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'filepath', {
		get: function () {
			return docInfo.NsfName;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'fileurl', {
		get: function () {
			return _fileurl;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Function: deleteAttachment
	 * Deletes an attachment from a back end docova document
	 * Inputs:
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	this.deleteAttachment = function () {
		var result = false;

		if (_parentDoc && _filename != "") {
			_parentDoc.deleteAttachment(_filename);
		}
		_filename = "";
		_filesize = 0;
		_filedate = "";
		_fileurl = "";

		result = true;
		return result;
	} //--end deleteAttachment


	/*----------------------------------------------------------------------------------------------
	 * Function: downloadAttachment
	 * Downloads an attachment from a back end docova document
	 * Inputs: destination - string - file path and file name to download file to
	 * Returns: boolean - true if download successful, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.downloadAttachment = function (destination) {
		result = false;

		if (!destination || destination == "" || _fileurl == "") {
			return result;
		}

		if (DocovaExtensions.DownloadFileFromURL(_fileurl, destination, true) != "") {
			result = true;
		}

		return result;
	} //--end downloadAttachment


	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new attachment
	 *-------------------------------------------------------------------------------------------------*/
	if (parentdoc == undefined || parentdoc == null || filename == undefined || filename == null || filename == "") {
		return;
	}
	_parentDoc = parentdoc;

	var pathdelim = ((filename.indexOf("\\") > -1) ? "\\" : ((filename.indexOf("/") > -1) ? "/" : ""));
	if (pathdelim != "") {
		_filename = filename.slice(filename.lastIndexOf(pathdelim) + 1);
	} else {
		_filename = filename;
	}
	if (filesize != undefined) {
		_filesize = parseInt(filesize, 10);
	}
	if (filedate != undefined) {
		_filedate = new Date(filedate);
	} else {
		_filedate = new Date();
	}
	if (fileurl != undefined) {
		_fileurl = fileurl;
	}
	//--end Constructor

} //end DocovaAttachment class


/*
 * Class: DocovaDocument
 * Back end class and methods for interaction with DOCOVA documents
 * Typically accessed from DocovaApplication.getDocument() or various DocovaView methods.
 * Parameters:
 * 		parentobj: DocovaView or DocovaDatabase object
 * 		unid: string - document unique id
 * 		id: string - document key
 * Properties:
 * 		children: array of DocovaDocument objects - immediate child records for current doc
 * 		id: string - document key
 * 		parentApp: DocovaApplication - application/library object where doc resides
 * 		parentDoc: DocovaDocument - document object that this document is a child of
 * 		parentView: DocovaView - view object where doc was opened from
 * 		unid: string - database universal id of document
 **/
function DocovaDocument(parentobj, unid, id) {
	var _children = null;
	var _attachmentBuffer = null;
	var _fieldBuffer = null;
	var _id = "";
	var _isModified = false;
	var _isNewDocument = false;
	var _isProfile = false;
	var _profileKey = "";
	var _parentApp = null;
	var _parentDoc = null;
	var _parentFolder = null;
	var _parentUIView = null;
	var _parentView = null;
	var _parentUIDoc = null;
	var _unid = "";
	var _self = this;
	var _doComputeWithForm = false;
	var _noteid = "";
	var _columnValues = null;

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaDocument";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'columnValues', {
		get: function () {
			if (_parentView && !_columnValues) {
				_columnValues = this._getColumnValues();
			}

			return _columnValues;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'id', {
		get: function () {
			return _id;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isModified', {
		get: function () {
			return _isModified;
		},

		set: function (newval) {
			_isModified = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'doComputeWithForm', {
		get: function () {
			return _doComputeWithForm;
		},
		set: function (newval) {
			_doComputeWithForm = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'noteID', {
		get: function () {
			if (_noteid && _noteid != "")
				return _noteid;
			return _getNoteID();
		},

		enumerable: true
	});

	Object.defineProperty(this, 'fieldBuffer', {
		get: function () {
			return _fieldBuffer;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isNewDocument', {
		get: function () {
			return _isNewDocument;
		},
		set: function (newval) {
			_isNewDocument = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isProfile', {
		get: function () {
			return _isProfile;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isResponse', {
		get: function () {
			if (_parentDoc) {
				return true;
			} else {
				return false;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isUIDocOpen', {
		get: function () {
			if (_parentUIDoc) {
				return true;
			} else {
				return false;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isValid', {
		get: function () {
			if (_isNewDocument || _unid) {
				return true;
			} else {
				return false;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'items', {
		get: function () {
			var result = [];
			this.getFields("*");

			for (var key in _fieldBuffer) {
				if (_fieldBuffer[key]) {
					result.push(_fieldBuffer[key]);

				}
			}

			return result;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentApp', {
		get: function () {
			return _parentApp;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentDatabase', {
		get: function () {
			return _parentApp;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentDoc', {
		get: function () {
			return _parentDoc;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentDocumentUNID', {
		get: function () {
			if (_parentDoc && _parentDoc.unid) {
				return _parentDoc.unid;
			} else {
				return "";
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentFolder', {
		get: function () {
			return _parentFolder;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentUIDoc', {
		get: function () {
			return _parentUIDoc;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentUIView', {
		get: function () {
			return _parentUIView;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentView', {
		get: function () {
			return _parentView;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'profileKey', {
		get: function () {
			return _profileKey;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'responses', {
		get: function () {
			if (_children === null) {
				//-- if null then try and retrieve a listing
				_getChildren();
			}

			if (_children === false) {
				return null;
			} else {
				return _children;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'unid', {
		get: function () {
			return _unid;
		},
		set: function (newval) {
			_unid = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'universalID', {
		get: function () {
			return _unid;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Methods:
	 *-------------------------------------------------------------------------------------------------*/

	/*----------------------------------------------------------------------------------------------
	 * Function: copyAllItems
	 * Copies items from the document to another target document
	 * Inputs: targetdoc - DocovaDocument object
	 *         replace - boolean - whether to replace existing values or not
	 * Returns: boolean - true or false depending upon whether copy was successful
	 *-------------------------------------------------------------------------------------------------*/
	this.copyAllItems = function (targetdoc) {
		var result = false;

		if (!targetdoc || targetdoc.constructor_name != "DocovaDocument") {
			return result;
		}

		//-- current doc has a problem not new and no unid
		if (!this.isNewDocument && this.unid == "") {
			return result;
		}

		//-- target doc has a problem not new and no unid
		if (!targetdoc.isNewDocument && targetdoc.unid == "") {
			return result;
		}

		//-- check if source document has been modified if so use the buffer fields
		if (this.isModified) {
			for (var key in _fieldBuffer) {
				if (_fieldBuffer[key]) {
					var dfield = _fieldBuffer[key];
					if (dfield.modified && !dfield.removeField) {

						var sourceval = dfield.values[0];
						targetdoc.setField(key, sourceval, dfield.type);
						result = true;
					}
				}
			}
		}

		var sourcefields = null;
		//--first check to see if any values can come from back end document
		if (!this.isNewDocument) {
			sourcefields = this.getFields("*");
		}

		var sourcefields2 = null;
		//--next check to see if any values can come from the parent ui document in edit mode
		if (this.isUIDocOpen && this.parentUIDoc && this.parentUIDoc.editMode) {
			sourcefields2 = this.parentUIDoc.getAllFields();
		}

		//-- if we have results from a back end doc use them
		if (sourcefields) {
			for (var key in sourcefields) {
				if (_fieldBuffer[key]) {
					targetdoc.setField(key, _fieldBuffer[key].values, _fieldBuffer[key].type);
					if (_fieldBuffer[key].type == "attachment") {
						targetdoc.fieldBuffer[key].sourceDocID = _unid;
						targetdoc.fieldBuffer[key].sourceDbPath = docInfo.NsfName;
					}
				}
			}
		}

		//-- if we have results from the front end ui doc use them
		if (sourcefields2) {
			for (var key in sourcefields2) {
				if (sourcefields2.hasOwnProperty(key)) {
					if (key.toLowerCase() !== "__click" && key.indexOf("%%") < 0) {
						var sourceval = sourcefields2[key];
						targetdoc.setField(key, sourceval);
						result = true;
					}
				}
			}
		}

		return result;
	} //--end copyAllItems

	/*----------------------------------------------------------------------------------------------
	 * Function: copyItem
	 * Copies an item from another document
	 * Inputs: targetdoc - DocovaDocument object
	 *         newname - string (optional) - new name to use for field
	 * Returns: DocovaField - new field object
	 *-------------------------------------------------------------------------------------------------*/
	this.copyItem = function (itemobj, newname) {
		var result = null;

		//--check that item is defined
		if (!itemobj || itemobj.constructor_name !== "DocovaField" || itemobj.name === "") {
			return result;
		}

		//-- current doc has a problem not new and no unid
		if (!this.isNewDocument && this.unid == "") {
			return result;
		}

		//-- check that item has a valid parent
		if (itemobj.parent && itemobj.parent.constructor_name === "DocovaDocument") {
			result = itemobj.copyItemToDocument(this, newname);
		}

		return result;
	} //-- end copyItem

	/*----------------------------------------------------------------------------------------------
	 * Function: createRichTextItem
	 * Creates a new rich text item field object
	 * Inputs: fieldname - string - name of new rich text object field
	 * Returns: DocovaRichTextItem - new rt field object
	 *-------------------------------------------------------------------------------------------------*/
	this.createRichTextItem = function (fieldname) {
		return new DocovaRichTextItem(this, fieldname);
	} //--end createRichTextItem


	//private method
	this._setParentDocument = function (objDocovaDocument) {
		if (objDocovaDocument && objDocovaDocument != null && objDocovaDocument.constructor_name === "DocovaDocument") {
			_parentDoc = objDocovaDocument;
		}
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: makeResponse
	 * Makes one document a response to another document. The two documents must be in the same database.
	 * Inputs: A DocovaDocument. The document to which docovaDocument becomes a response.
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	this.makeResponse = function (objDocovaDocument) {
		var result = null;

		if (!objDocovaDocument || objDocovaDocument === null || objDocovaDocument.constructor_name !== "DocovaDocument") {
			return
		}

		this._setParentDocument(objDocovaDocument);
		_isModified = true;
		return;
	} //-- end copyItem

	/*----------------------------------------------------------------------------------------------
	 * Function: computeWithForm
	 * Computes the document with the associated form
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	this.computeWithForm = function (doDataType, raiseError) {
		if (_DOCOVAEdition == "SE") {
			var result = false;

			var formname = this.getField("form");
			formname = (formname == "" ? (this.parentUIDoc ? this.parentUIDoc.formName : "") : formname);
			if (formname == "") {
				return false;
			}
			var actionUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/SubmitForRefresh/" + encodeURIComponent(formname);
			if (!_isNewDocument) {
				actionUrl += "/" + this.id;
			}
			actionUrl += "?AppID=" + this.parentApp.appID;

			var container = this;

			var allfields = this.items;
			var formDataNew = [];
			for (var j = 0; j < allfields.length; j++) {
				$tempval = allfields[j].values;
				$fieldname = allfields[j].name;
				
				if(Array.isArray($tempval)){
					if($tempval.length == 1){
						$tempval = $tempval[0];
					}else if($tempval.length > 1){
						$tempval = $tempval.join(String.fromCharCode(31));
					}
				}
				
				formDataNew.push({
					name: $fieldname,
					value: $tempval
				});
			}

			jQuery.ajax({
				url: actionUrl,
				type: "POST",
				data: formDataNew,
				async: false
			}).done(function (data) {
				if (data) {
					var bodyhtml = data.body.replace(/^[\S\s]*<body[^>]*?>/i, "").replace(/<\/body[\S\s]*$/i, "");
					var htmlobj = jQuery("<div>" + bodyhtml + "</div>");
					var divcont = htmlobj.find("#divFormContentSection");
					
					var mvfieldlist = {};

					var formData = divcont.find("input,select").serializeArray();
					for (var j = 0; j < formData.length; j++) {
						var fieldname = decodeURI(formData[j].name);				
						if (fieldname.indexOf("[]") != -1) {
							fieldname = fieldname.split("[]")[0];
							//-- checkbox style field name so combine values to an array and save until later
							if(mvfieldlist.hasOwnProperty(fieldname.toLowerCase())){
								mvfieldlist[fieldname.toLowerCase()].push(formData[j].value);
							}else{
								mvfieldlist[fieldname.toLowerCase()] = (Array.isArray(formData[j].value) ? formData[j].value : [formData[j].value]);
							}
						}else{
							container.setField(fieldname, formData[j].value);
						}
					}
					
					//-- update any checkbox fields
					for(var key in mvfieldlist){
						if(mvfieldlist.hasOwnProperty(key)){
							container.setField(key, mvfieldlist[key]);
						}
					}

					result = true;
				}
			})
			.fail(function () {
				if (console) {
					console.log("Error: refresh was unable to re-load page contents");
				}
			});

			return result;
		} else {
			this.doComputeWithForm = true;
		}

	} //-- end computeWithForm


	/*----------------------------------------------------------------------------------------------
	 * Function: deleteAttachment
	 * Deletes a back end docova attachment. Requires a doc save method to be called
	 * Inputs: filename - string - name of file attachment being deleted
	 * Returns: boolean - true or false depending upon whether delete was successful
	 *-------------------------------------------------------------------------------------------------*/
	this.deleteAttachment = function (filename) {
		if (filename == undefined || filename == null || filename == "") {
			return false;
		}

		if (_attachmentBuffer === null) {
			_attachmentBuffer = {};
		}
		if (_isNewDocument) {
			delete _attachmentBuffer[filename];
		} else {
			_attachmentBuffer[filename] = null;
		}
		_isModified = true;

		return true;
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: deleteDocument
	 * Deletes a back end docova document. If a library app flags for trash, otherwise permanently deletes.
	 * Inputs:
	 * Returns: boolean - true or false depending upon whether delete was successful
	 *-------------------------------------------------------------------------------------------------*/
	this.deleteDocument = function () {
		var result = false;

		if (this.unid == "") {
			return result;
		}

		var isappcomment = this.getField("form") == "d_DocComment" ? 1 : 0;

		var request = "<Request>";
		request += "<Action>DELETE</Action>";
		request += "<Unid>" + this.unid + "</Unid>";
		request += "<isDocComment>" + isappcomment + "</isDocComment>";
		request += "<AppID>" + this.parentApp.appID + "</AppID>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			if (_parserHelper(xmldata, "delete")) {
				_children = null;
				_id = "";
				_parentApp = null;
				_parentDoc = null;
				_parentFolder = null;
				_parentView = null;
				_unid = "";
				_isModified = false;
				_isNewDocument = false;
				_fieldBuffer = null;
				result = true;
			}
		}

		return result;
	} //--end deleteDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: deleteField
	 * Deletes a field on a document
	 * Inputs: fieldname - string - name of field to delete
	 *-------------------------------------------------------------------------------------------------*/
	this.deleteField = function (fieldname) {
		if (fieldname == undefined || fieldname == null) {
			return false;
		}

		if (!this.isNewDocument && this.unid == "") {
			return false;
		}

		if (_fieldBuffer === null) {
			_fieldBuffer = {};
		}
		var dfield = new DocovaField(this, fieldname);
		dfield.removeField = true;
		_fieldBuffer[fieldname.toLowerCase()] = dfield;
		_isModified = true;

		return true;
	} //--end deleteField

	/*----------------------------------------------------------------------------------------------
	 * Function: getAttachment
	 * Returns a docova attachment object
	 * Inputs: filename - string - name of attachment to retrieve
	 * Returns: DocovaAttachment
	 *-------------------------------------------------------------------------------------------------*/
	this.getAttachment = function (filename) {
		var result = null;

		if (!filename || filename == "") {
			return result;
		}

		if (this.unid == "") {
			return result;
		}

		var request = "<Request>";
		request += "<Action>GETATTACHMENTS</Action>";
		request += "<SelectionType>1</SelectionType>";
		request += "<SelectedDocs>";
		request += "<DocID>" + this.unid + "</DocID>";
		request += "</SelectedDocs>";
		request += "<FolderID>0</FolderID>";
		request += "<IncludeExtensions></IncludeExtensions>";
		request += "<ExcludeExtensions></ExcludeExtensions>";
		request += "<IncludeThumbnails>1</IncludeThumbnails>";
		request += "<AppendVersionInfo>0</AppendVersionInfo>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			var tempresult = _parserHelper(xmldata, "attachments");
			if (tempresult && jQuery.isArray(tempresult)) {
				for (var x = 0; x < tempresult.length; x++) {
					if (tempresult[x].filename.toLowerCase() == filename.toLowerCase()) {
						result = new DocovaAttachment(this, tempresult[x].filename, tempresult[x].filesize, tempresult[x].filedate, tempresult[x].fileurl);
						break;
					}
				}
			}
		}

		return result;
	} //--end getAttachment

	/*----------------------------------------------------------------------------------------------
	 * Function: getAttachments
	 * Returns an array of docova attachment objects
	 * Inputs: filepattern - string - file pattern filter to use in searching for files. (defaults to all)
	 * Returns: array of DocovaAttachment objects
	 *-------------------------------------------------------------------------------------------------*/
	this.getAttachments = function (filepattern) {
		var result = null;

		if (this.unid == "") {
			return result;
		}

		var matchpattern = "^";
		if (filepattern && filepattern != "") {
			matchpattern += filepattern.replace(/[-\/\\^$+?.()|[\]{}]/g, '\\$&').replace(/\*/g, ".*");
		} else {
			matchpattern += ".*"; //match anything
		}
		matchpattern += "$";
		var regex = new RegExp(matchpattern, "i");

		var request = "<Request>";
		request += "<Action>GETATTACHMENTS</Action>";
		request += "<SelectionType>1</SelectionType>";
		request += "<SelectedDocs>";
		request += "<DocID>" + this.unid + "</DocID>";
		request += "</SelectedDocs>";
		request += "<FolderID>0</FolderID>";
		request += "<IncludeExtensions></IncludeExtensions>";
		request += "<ExcludeExtensions></ExcludeExtensions>";
		request += "<IncludeThumbnails>1</IncludeThumbnails>";
		request += "<AppendVersionInfo>0</AppendVersionInfo>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			var tempresult = _parserHelper(xmldata, "attachments");
			if (tempresult && jQuery.isArray(tempresult)) {
				for (var x = 0; x < tempresult.length; x++) {
					if (tempresult[x].filename.match(regex)) {
						if (!jQuery.isArray(result)) {
							result = new Array();
						}
						result.push(new DocovaAttachment(this, tempresult[x].filename, tempresult[x].filesize, tempresult[x].filedate, docInfo.ServerUrl + tempresult[x].fileurl));
					}
				}
			}
		}

		return result;
	} //--end getAttachments

	/*----------------------------------------------------------------------------------------------
	 * Private Function: _getColumnValues
	 * For documents retrieved from a view retrieves/sets/returns the column values for the doc in the view
	 *-------------------------------------------------------------------------------------------------*/
	this._getColumnValues = function () {
		//-- only retrieve them if not already set and we have a parentView
		if (_parentView && !_columnValues) {
			var request = "<Request>";
			request += "<Action>GETCOLUMNVALUES</Action>";
			request += "<ViewName><![CDATA[" + _parentView.viewName + (_parentView.isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
			request += "<DocUnid>" + this.unid + "</DocUnid>";
			request += "</Request>";

			var xmldata = _requestHelper(request, "(ViewServices)");
			if (xmldata) {
				var xmlobj = jQuery(xmldata);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
					var resultxmlobj = jQuery(resultxmlobj).find("Columns");
					var result = [];
					jQuery(resultxmlobj).find("Column").each(function () {
						var index = Number(jQuery(this).attr("index"));
						var fieldval = jQuery(this).text();
						result[index] = fieldval;
					});
					if (result.length > 0) {
						_columnValues = result;
					}
				}
			}
		}
		return _columnValues;
	} //--end _getColumnValues

	/*----------------------------------------------------------------------------------------------
	 * Private Function: _getFieldFromDoc
	 * Inputs: fieldname - string - name of field
	 *         source - string (optional) - all - look at both front end and back end sources - default
	 *                                    - frontend - look at just front end ui doc
	 *                                    - backend - look at just back end document
	 * Returns the value of a field on a document.  checks UI doc first, and then the backend doc
	 *-------------------------------------------------------------------------------------------------*/
	this._getFieldFromDoc = function (fieldname, source) {
		result = "";

		var contentsource = (typeof source === 'undefined' || source === null ? "all" : source.toLowerCase());

		if (contentsource === "all" || contentsource === "backend") {
			//-- check if document is new back end doc
			if (!result && !this.isNewDocument) {
				if (this.unid !== "") {
					var request = "<Request>";
					request += "<Action>GETDOCFIELDS</Action>";
					request += "<AppID>" + this.parentApp.appID + "</AppID>"
					request += "<Fields><![CDATA[" + fieldname.toLowerCase() + "]]></Fields>";
					request += "<Unid>" + this.unid + "</Unid>";
					request += "</Request>";

					var xmldata = _requestHelper(request);
					if (xmldata) {
						var tempresult = _parserHelper(xmldata, "field");
						if (tempresult && tempresult[fieldname.toLowerCase()] != undefined) {
							var retobj = tempresult[fieldname.toLowerCase()];
							result = retobj.fieldvalue;
							if (!Array.isArray(result)) {
								result = [result];
							}
							if (_fieldBuffer === null) {
								_fieldBuffer = {};
							}
							var df = new DocovaField(this, fieldname, result, "", retobj.fieldtype);
							if (_fieldBuffer[fieldname.toLowerCase()]){
								_fieldBuffer[fieldname.toLowerCase()].modified = false;
							}
						}
					}
				}
			}
		}
		
		if (contentsource === "all" || contentsource === "frontend") {
			if (this.parentUIDoc && this.parentUIDoc.isDocBeingEdited) {
				//-- try to retrieve data from front end ui doc
				result = this.parentUIDoc.getField(fieldname);
				if (result) {
					if (!Array.isArray(result)) {
						result = [result];
					}
					if (_fieldBuffer === null) {
						_fieldBuffer = {};
					}
					var df = new DocovaField(this, fieldname, result, "");
					if (_fieldBuffer[fieldname.toLowerCase()]){
						_fieldBuffer[fieldname.toLowerCase()].modified = false;
					}
				}
			}
		}

		return result;
	} //--end _getFieldFromDoc


	/*----------------------------------------------------------------------------------------------
	 * Function: getField
	 * Returns the value of a field on a document
	 * Inputs: fieldname - string - name of field to retrieve
	 * Returns: string/numeric/date
	 *-------------------------------------------------------------------------------------------------*/
	this.getField = function (fieldname) {
		var result = "";

		if (!fieldname || fieldname == "") {
			return null;
		}

		//-- check if a parent ui doc is open in edit mode
		//-- if so we should try to get the field from there first
		//-- in case it has been edited
		if (this.parentUIDoc && this.parentUIDoc.isDocBeingEdited) {
			//get field from the document ui/backend
			result = this._getFieldFromDoc(fieldname, "frontend");
			if (result !== null) {
				if (!Array.isArray(result)) {
					result = [result];
				}
				return result;
			}
		}

		//-- need to retrieve data from memory if it is present
		if (_fieldBuffer && _fieldBuffer[fieldname.toLowerCase()]) {
			result = _fieldBuffer[fieldname.toLowerCase()].values;
			return result;
		}
		//get field from the document ui/backend
		result = this._getFieldFromDoc(fieldname, "backend");

		if (!Array.isArray(result)) {
			result = [result];
		}
		return result;
	} //--end getField

	/*----------------------------------------------------------------------------------------------
	 * Function: getFieldNames
	 * Returns an array of field names
	 * Returns: array of field names
	 *-------------------------------------------------------------------------------------------------*/
	this.getFieldNames = function () {
		var result = null;
		if (this.unid == "") {
			return result;
		}
		var request = "<Request>";
		request += "<Action>GETDOCFIELDS</Action>";
		request += "<AppID>" + this.parentApp.appID + "</AppID>"
		request += "<SubAction>NAMESONLY</SubAction>";
		request += "<Unid>" + this.unid + "</Unid>";
		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			result = _parserHelper(xmldata, "text");
			if (result) {
				result = result.split(",");
			}
		}
		return result;
	} //--end getFieldNames

	/*----------------------------------------------------------------------------------------------
	 * Function: getFields
	 * Returns the value of one or more fields from a document
	 * Inputs: fields - concatentated comma delimited string of field names or array of field names to retrieve
	 *                  provide string "*" to retrieve all available fields
	 * Returns: value pair object - {fielname1:fieldvalue, fieldname2:fieldvalue, etc}
	 *          value names are in lower case
	 *-------------------------------------------------------------------------------------------------*/
	this.getFields = function (fields) {
		var result = null;

		if (!fields || fields == "") {
			return result;
		}

		if (_fieldBuffer === null) {
			_fieldBuffer = {};
		}

		//-- get fields from back end object
		if (!this.isNewDocument && this.unid !== "") {
			//--get fields from back end doc
			var request = "<Request>";
			request += "<Action>GETDOCFIELDS</Action>";
			request += "<AppID>" + this.parentApp.appID + "</AppID>"
			request += "<Fields><![CDATA[" + (Array.isArray(fields) ? fields.join(",").toLowerCase() : fields.toLowerCase()) + "]]></Fields>";
			request += "<Unid>" + this.unid + "</Unid>";
			request += "</Request>";

			var xmldata = _requestHelper(request);
			if (xmldata) {
				var tempresult = _parserHelper(xmldata, "fields");
				//also cache the values
				for (var res in tempresult) {
					var obj = tempresult[res];

					if ((!_fieldBuffer[res.toLowerCase()]) || (_fieldBuffer[res.toLowerCase()] && _fieldBuffer[res.toLowerCase()].modified == false)) {
						var df = new DocovaField(this, res, obj.fieldvalue, "", obj.fieldtype);
						_fieldBuffer[res.toLowerCase()].modified = false;

					}
				}
			}
		}

		var fieldlist = (Array.isArray(fields) ? fields : fields.split(","));
		
		//-- get fields from ui doc if available
		if(this.parentUIDoc !== null && this.parentUIDoc.editMode){
			if(fieldlist[0] === "*"){
				var uidocfields = this.parentUIDoc.getAllFields(true);
				if(uidocfields){
					for (var key in uidocfields) {
						if (uidocfields.hasOwnProperty(key)) {
							if (key.toLowerCase() !== "__click" && key.indexOf("%%") < 0 && key.indexOf("%25%25") < 0) {
								var fname = key;
								var fval = uidocfields[key];
								if ((!_fieldBuffer[fname.toLowerCase()]) || (_fieldBuffer[fname.toLowerCase()] && _fieldBuffer[fname.toLowerCase()].modified == false)) {
									var df = new DocovaField(this, fname, fval);
									_fieldBuffer[fname.toLowerCase()].modified = false;
								}								
							}
						}
					}
				}
			}else{
				for(var f = 0; f<fieldlist.length; f++){
					//get field from the document ui
					var tempresult = this._getFieldFromDoc(fieldlist[f], "frontend");
					if (tempresult !== null) {
						var fname = fieldlist[f];
						var fval = tempresult;
						if ((!_fieldBuffer[fname.toLowerCase()]) || (_fieldBuffer[fname.toLowerCase()] && _fieldBuffer[fname.toLowerCase()].modified == false)) {
							var df = new DocovaField(this, fname, fval);
							_fieldBuffer[fname.toLowerCase()].modified = false;
						}
					}
				}
			}
		}
		
		
		//--compile a list of fields from buffer since it may contain some newer entries than what was retrieved
		result = {}
		if (fieldlist[0] === "*") {
			for (key in _fieldBuffer) {
				if (_fieldBuffer.hasOwnProperty(key)) {
					result[key] = _fieldBuffer[key].values;
				}
			}
		} else {
			result = {};
			for (var x = 0; x < fieldlist.length; x++) {
				if (_fieldBuffer[fieldlist[x].toLowerCase()]) {
					result[fieldlist[x].toLowerCase()] = _fieldBuffer[fieldlist[x].toLowerCase()].values;
				}
			}
		}

		return result;
	} //--end getFields

	/*----------------------------------------------------------------------------------------------
	 * Function: getFirstItem
	 * Returns DocovaField object for specified field in the document
	 * Inputs: fieldname - string - name of field
	 * Returns: DocovaField object
	 *-------------------------------------------------------------------------------------------------*/
	this.getFirstItem = function (fieldname) {
		var result = null;

		if (!fieldname || fieldname == "") {
			return result;
		}

		//return from memory
		if (_fieldBuffer && _fieldBuffer[fieldname.toLowerCase()]) {
			result = _fieldBuffer[fieldname.toLowerCase()];
		}
		if (result === null) {
			if (this.hasItem(fieldname)) {
				//result = new DocovaField(this, fieldname);
				if (_fieldBuffer[fieldname.toLowerCase()])
					result = _fieldBuffer[fieldname.toLowerCase()];
			}
		}
		if (result && result.type == "richtext") {
			return new DocovaRichTextItem(this, fieldname, result)
		}
		return result;
	} //--end getFirstItem
	/*----------------------------------------------------------------------------------------------
	 * Function: getItemValue
	 * Alias for getField - Returns the value of a field on a document
	 * Inputs: fieldname - string - name of field to retrieve
	 * Returns: string/numeric/date
	 *-------------------------------------------------------------------------------------------------*/
	this.getItemValue = function (fieldname) {
		return this.getField(fieldname);
	} //--end getItemValue

	/*----------------------------------------------------------------------------------------------
	 * Function: getURL
	 * Returns the url to a document
	 * Inputs: options - value pair object - options for url generation
	 * Returns: string - url to document
	 *-------------------------------------------------------------------------------------------------*/
	this.getURL = function (options) {
		var result = "";

		var defaultOptns = {
			editmode: false,
			mode: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var url = "";

		if (_DOCOVAEdition == "SE") {
			var docid = jQuery.trim(this.unid);

			if (this.isNewDocument) {
				if (typeof docid === 'undefined' || docid == "" || docid === null) {
					var contextdoc = (this.parentUIDoc ? this.parentUIDoc.htmldoc : document);
					docid = jQuery.trim(jQuery("input[name=unid]:first", contextdoc).val());
				}
			}

			if (!(typeof docid === 'undefined' || docid == "" || docid === null)) {
				if (opts.editmode) {
					url = docInfo.ServerUrl + docInfo.PortalWebPath + "/wViewForm/0/" + docid + "?EditDocument&AppID=" + this.parentApp.appID + (opts.mode != "" ? "&mode=" + opts.mode : "");
				} else {
					url = docInfo.ServerUrl + docInfo.PortalWebPath + "/wReadDocument/" + docid + "?OpenDocument&ParentUNID=" + this.parentApp.appID + (opts.mode != "" ? "&mode=" + opts.mode : "");
				}
			}
		} else {
			if (this.isNewDocument) {
				var dockey = this.getField("DocKey");
				if (dockey !== null && dockey !== "") {
					url = docInfo.ServerUrl + "/" + this.parentApp.filePath + "/luAllByDocKey/" + jQuery.trim(dockey) + (opts.editmode ? "?EditDocument" : "?OpenDocument") + (opts.mode != "" ? "&mode=" + opts.mode : "");
				}
			} else {
				url = docInfo.ServerUrl + "/" + this.parentApp.filePath + "/0/" + jQuery.trim(this.unid) + (opts.editmode ? "?EditDocument" : "?OpenDocument") + (opts.mode != "" ? "&mode=" + opts.mode : "");
			}
		}

		result = url;

		return result;
	}
	//--end getURL


	/*----------------------------------------------------------------------------------------------
	 * Function: hasItem
	 * Returns true if the document contains the specified field
	 * Inputs: fieldname - string - name of field
	 * Returns: boolean - true if field is found, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.hasItem = function (fieldname) {
		var result = false;

		if (!fieldname || fieldname == "") {
			return result;
		}

		var context = document;

		//--check if this document is accessed from a uidoc in edit mode
		if (this.parentUIDoc && this.parentUIDoc.isDocBeingEdited) {
			//-- try to retrieve data from front end ui doc

			//-- check if we can find an existing field on the doc
			var fieldnames = this.parentUIDoc.getFieldNames();
			if (fieldnames && fieldnames[fieldname.toLowerCase()]) {
				var res = this.parentUIDoc.getField(fieldname);
				var df = new DocovaField(this, fieldname, res)
					_fieldBuffer[fieldname.toLowerCase()].modified = false;
				result = true;
			}
		}
		//check in memory
		if (!result) {

			if (_fieldBuffer && _fieldBuffer[fieldname.toLowerCase()]) {
				result = true;
			}
		}
		//-- check if document is new back end doc
		if (!result && !this.isNewDocument) {
			if (this.unid == "") {
				return result;
			}

			var request = "<Request>";
			request += "<Action>GETDOCFIELDS</Action>";
			request += "<AppID>" + this.parentApp.appID + "</AppID>"
			request += "<Fields><![CDATA[" + fieldname.toLowerCase() + "]]></Fields>";
			request += "<Unid>" + this.unid + "</Unid>";
			request += "</Request>";

			var xmldata = _requestHelper(request);
			if (xmldata) {
				var tempresult = _parserHelper(xmldata, "field");
				if (tempresult && tempresult[fieldname.toLowerCase()] != undefined) {
					var obj = tempresult[fieldname.toLowerCase()];
					var df = new DocovaField(this, fieldname.toLowerCase(), obj.fieldvalue, "", obj.fieldtype)
						_fieldBuffer[fieldname.toLowerCase()].modified = false;
					result = true;
				}
			}
		}

		return result;
	} //--end hasItem

	/*----------------------------------------------------------------------------------------------
	 * Function: putInFolder
	 * Adds the current document to the specified folder
	 * Inputs: foldername - string - name of folder
	 * Returns: boolean - true if document is added to folder, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.putInFolder = function (foldername) {
		var result = false;

		if (!foldername) {
			return result;
		}

		var tempcol = new DocovaCollection(this.parentApp);
		tempcol.addEntry(this);
		result = tempcol.putAllInFolder(foldername);

		return result;
	} //--end putInFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: removeFromFolder
	 * Removes the current document from the specified folder
	 * Inputs: foldername - string - name of folder
	 * Returns: boolean - true if document is removed from folder, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.removeFromFolder = function (foldername) {
		var result = false;

		if (!foldername) {
			return result;
		}

		var tempcol = new DocovaCollection(this.parentApp);
		tempcol.addEntry(this);
		result = tempcol.removeAllFromFolder(foldername);

		return result;
	} //--end removeFromFolder


	/*----------------------------------------------------------------------------------------------
	 * Function: replaceItemValue
	 * Replaces value of field on document
	 * Inputs: fieldname - string - name of field
	 *         fieldvalue - various - value to update field with
	 * Returns: DocovaField object
	 *-------------------------------------------------------------------------------------------------*/
	this.replaceItemValue = function (fieldname, fieldvalue) {
		var result = null;

		if (!fieldname || fieldname == "") {
			return result;
		}

		if (fieldvalue === undefined) {
			return result;
		}

		this.setField(fieldname, fieldvalue);
		result = _fieldBuffer[fieldname.toLowerCase()]

			return result;
	} //--end replaceItemValue


	/*----------------------------------------------------------------------------------------------
	 * Function: save
	 * Saves changes to a docova document to the back end record or creates a new record
	 * Inputs:
	 * Returns: boolean - true or false depending upon whether save was successful
	 *-------------------------------------------------------------------------------------------------*/
	this.save = function () {
		var result = false;

		if (!this.isModified && !this.isNewDocument) {
			return result;
		}

		if (!this.isNewDocument && this.unid == "") {
			return result;
		}

		var formname = "";
		var doctype = "";
		var folderid = "";

		var isApp = this.parentApp.isApp;
		if (isApp == true) {
			if (_fieldBuffer && _fieldBuffer["form"]) {
				formname = _fieldBuffer["form"].values[0];
				delete _fieldBuffer["form"];
			} else {
				if (this.isUIDocOpen && this.parentUIDoc) {
					formname = this.parentUIDoc.formName
				}
			}

		} else {
			if (_fieldBuffer && _fieldBuffer["documenttypekey"]) {
				doctype = _fieldBuffer["documenttypekey"].values[0];
				delete _fieldBuffer["documenttypekey"];
			}
			if (!_parentFolder) {
				folderid = _parentFolder.id;
			}
			if (folderid == "" && _fieldBuffer && _fieldBuffer["folderid"]) {
				folderid = _fieldBuffer["folderid"].values[0];
				delete _fieldBuffer["folderid"];
			}
			if (_isNewDocument && (folderid == "" || doctype == "")) {
				return result;
			}
		}

		var request = "<Request>";
		request += "<Action>" + (this.isProfile ? "SETPROFILEFIELDS" : (this.isNewDocument ? "NEWDOC" : "SETDOCFIELDS")) + "</Action>";
		request += "<isApp>" + (isApp == true ? "1" : "") + "</isApp>";
		request += "<AppID>" + this.parentApp.appID + "</AppID>"
		request += "<CWF>" + (this.doComputeWithForm ? "1" : "0") + "</CWF>"
		request += "<ParentUNID>" + ((this.parentDoc && this.parentDoc.constructor_name == "DocovaDocument" && this.parentDoc.unid != "") ? this.parentDoc.unid : "") + "</ParentUNID>"
		request += "<FormName><![CDATA[" + formname + "]]></FormName>";
		if (this.isProfile) {
			request += "<ProfileName><![CDATA[" + formname + "]]></ProfileName>";
			request += "<ProfileKey><![CDATA[" + this.profileKey + "]]></ProfileKey>";
		}
		request += "<FolderID>" + folderid + "</FolderID>";
		request += "<DocumentType>" + doctype + "</DocumentType>";
		request += "<Unid>" + this.unid + "</Unid>";

		request += "<DeleteFiles>";
		if (_attachmentBuffer) {
			for (var propkey in _attachmentBuffer) {
				if (_attachmentBuffer.hasOwnProperty(propkey)) {
					if (_attachmentBuffer[propkey] === null) {
						request += "<FileName><![CDATA[" + propkey + "]]></FileName>";
					}
				}
			}
		}
		request += "</DeleteFiles>";

		request += "<Fields>";

		for (var itemkey in _fieldBuffer) {
			var item = _fieldBuffer[itemkey];

			if ((item.modified || item.removeField) && jQuery.trim(itemkey) != "")
				request += item.toXML;

		}
		request += "</Fields>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			var saveresult = _parserHelper(xmldata, "save");
			if (saveresult == true || (_isNewDocument && (saveresult !== null) && (saveresult !== false) && (saveresult !== ""))) {
				if (_isNewDocument) {
					_unid = saveresult;
					_id = "DK" + saveresult;
					if (this.parentUIDoc && this.parentUIDoc.isDocBeingEdited)
						this.parentUIDoc.backendDocSaved(_unid);

				}
				_attachmentBuffer = null;
				_fieldBuffer = null;
				_isModified = false;
				_isNewDocument = false;

				result = true;
			}
		}

		return result;
	} //--end save

	/*----------------------------------------------------------------------------------------------
	 * Function: send
	 * Sends an email for the current document
	 * Inputs:
	 *      includelink - whether to send a link to the document
	 *      recipients - string - array of strings (optional) - who to send email to
	 *      dontwait - boolean (optional) - true to send message asynchronously and not wait for response
	 *                                      defaults to false
	 *-------------------------------------------------------------------------------------------------*/
	this.send = function (includelink, recipients, dontwait) {

		var subject = this.getField("Subject");
		var sendTo = this.getField("SendTo");
		var copyTo = this.getField("CopyTo");
		var blindCopyTo = this.getField("BlindCopyTo");
		var bodyFields = "Body";
		var remark = "";
		var flags = (includelink ? "[INCLUDEDOCLINK]" : "");
		var targetdoc = this;

		if (recipients) {
			sendTo = recipients;
		}
		if (typeof subject == 'undefined' || subject == null || subject == "") {
			subject = "- No Subject -";
		}
		if (typeof sendTo == 'undefined' || sendTo == null) {
			sendTo = "";
		}
		if (typeof copyTo == 'undefined' || copyTo == null) {
			copyTo = "";
		}
		if (typeof blindCopyTo == 'undefined' || blindCopyTo == null) {
			blindCopyTo = "";
		}

		$$MailSend(sendTo, copyTo, blindCopyTo, subject, remark, bodyFields, flags, targetdoc, dontwait);

	} //--end send

	/*----------------------------------------------------------------------------------------------
	 * Function: _addItemToDoc
	 * Private  - records the docovaField object that has been added to it through new DocovaField call
	 *-------------------------------------------------------------------------------------------------*/
	this._addItemToDoc = function (objDocovaField) {
		if (_fieldBuffer === null) {
			_fieldBuffer = {};
		}
		_fieldBuffer[objDocovaField.name.toLowerCase()] = objDocovaField;
		_isModified = true;

	}

	/*----------------------------------------------------------------------------------------------
	 * Function: setField
	 * Sets the value of a field on a document.  Must call save method to save any changes made.
	 * Inputs: fieldname - string - name of field to update
	 *         fieldvalue - string/numeric/date - value to store
	 *         fieldtype - string (optional) - data type of field being set
	 *         ignoreuidoc - boolean (optional) - set to true to update just the doc and not its parent ui doc
	 * Returns: boolean - true if no errors encountered, false otherwise.
	 *-------------------------------------------------------------------------------------------------*/
	this.setField = function (fieldname, fieldvalue, fieldtype, ignoreuidoc) {
		if (fieldname == undefined || fieldname == null) {
			return false;
		}

		if (fieldvalue == undefined) {
			var fieldvalue = null;
		}

		//--check if this document is accessed from a uidoc in edit mode
		if (this.parentUIDoc && this.parentUIDoc.isDocBeingEdited && !ignoreuidoc) {
			//-- try to set value on front end ui doc
			this.parentUIDoc.setField(fieldname, fieldvalue);
		}

		//-- check if document has no id and is not new or profile
		if (this.unid == "" && !this.isNewDocument && !this.isProfile) {
			return false;
		}

		if (_fieldBuffer === null) {
			_fieldBuffer = {};
		}
		var df = new DocovaField(this, fieldname, fieldvalue, "", fieldtype);
		_isModified = true;

		return true;
	} //--end setField

	/*----------------------------------------------------------------------------------------------
	 * Function: _getNoteID
	 * Retrieves NoteID of the doument.
	 *-------------------------------------------------------------------------------------------------*/
	function _getNoteID() {
		var result = "";
		if (_isNewDocument || _unid == ""){
			return result;
		}
		var request = "<Request>";
		request += "<Action>GETDOCFIELDS</Action>";
		request += "<AppID>" + this.parentApp.appID + "</AppID>"
		request += "<Fields><![CDATA[noteid]]></Fields>";
		request += "<Unid>" + _unid + "</Unid>";
		request += "</Request>";
		var xmldata = _requestHelper(request);
		var fielddata = _parserHelper(xmldata, "fields");
		if (fielddata && (fielddata.noteid)) {
			result = fielddata.noteid;
			_noteid = result;
		}
		return result;
	} //--end _getChildren


	/*----------------------------------------------------------------------------------------------
	 * Function: _getChildren
	 * Retrieves listing of response documents for the current document.
	 *-------------------------------------------------------------------------------------------------*/
	function _getChildren() {
		var result = false;

		if (_unid == "") {
			return result;
		}

		_children = false;

		var request = "<Request>";
		request += "<Action>GETRESPONSES</Action>";
		request += "<AppID>" + this.parentApp.appID + "</AppID>"
		request += "<Unid>" + _unid + "</Unid>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			var respids = _parserHelper(xmldata, "responses");
			if (Array.isArray(respids) && (respids.length > 1 || (respids.length == 1 && respids[0] != ""))) {
				var doccol = new Array();
				for (var i = 0; i < respids.length; i++) {
					var docobj = new DocovaDocument(_self, respids[i]);
					doccol.push(docobj);
				}
				_children = doccol;
				result = true;
			}
		}

		return result;
	} //--end _getChildren


	/*----------------------------------------------------------------------------------------------
	 * Function: _requestHelper
	 * Internal function to help with making ajax requests for document data xml
	 * Inputs: request - string - xml string containing request to services agent
	 *         service - string (optional) - name of service to call - defaults to DocumentServices
	 * Returns: xml string - containing results of request
	 *-------------------------------------------------------------------------------------------------*/
	function _requestHelper(request, service) {
		var xmldata = null;
		if (!_parentApp) {
			return xmldata;
		}

		if (_DOCOVAEdition == "SE") {
			var serviceurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent&AppID=" + docInfo.AppID;
		} else {
			if (!_parentApp.filePath || _parentApp.filePath == "") {
				return xmldata;
			}
			var serviceurl = _parentApp.filePath + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent";
		}

		jQuery.ajax({
			type: "POST",
			url: serviceurl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				xmldata = xml;
			},
			error: function () {}
		});

		return xmldata;
	} //--end _requestHelper

	/*----------------------------------------------------------------------------------------------
	 * Function: _parserHelper
	 * Internal function to help with parsing document data out of xml response
	 * Inputs: xml string - xml string containing document result data
	 *         parsetype - string - action to perform
	 * Returns: string, array, or boolean
	 *-------------------------------------------------------------------------------------------------*/
	function _parserHelper(xmldata, parsetype) {
		var result = null;

		//      try{
		var xmlobj = $(xmldata);
		var statustext = xmlobj.find("Result").first().text();
		if (statustext == "OK") {
			var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
			//-- update fields action type
			if (parsetype.toLowerCase() == "fields" || parsetype.toLowerCase() == "field") {
				result = {};
				jQuery(resultxmlobj).find("Fields:first").children().each(function () {
					var fieldname = jQuery(this).prop("tagName");
					fieldname = fieldname.replace(/__S__/g, "$");
					var datatype = jQuery(this).attr("dt");
					var ismulti = (jQuery(this).attr("multi") === "1");
					var sep = jQuery(this).attr("sep") || ",";
					var fieldval = jQuery(this).text();
					//check if rich text..if so get value from the backend doc
					if (datatype == "richtext") {
						var data = null;
						if (_DOCOVAEdition == "SE") {
							// ********************* NEEDS TO BE DOUBLE CHECKED IN PRACTICE *********************** //
							data = this.getField(fieldname);
						} else {
							var url = _parentApp.filePath + "/0/" + _unid + "/" + fieldname + "?openField";
							$.ajax({
								url: url,
								async: false,
								dataType: "html",
								type: "GET"
							}).done(function (ajaxdata) {
								data = ajaxdata;
							});
						}

						if (data.length > 0) {
							if (data.substr(0, 4) == "&lt;") {
								data = data.replace(/<br>/g, '');
							}
							data = data.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"');

							//now cleanup data to match the format we expect for the rich text
							var tmpobj = jQuery(jQuery.parseHTML(data));
							var finalarr = $("<div></div>");
							var subarr = [];

							if (tmpobj.find("#dparagraph").length == 0) {
								var subarr = [];
								if (tmpobj.find("#dparagraph").length == 0) {
									for (var p = 0; p < tmpobj.length; p++) {
										var curnode = tmpobj[p];
										if (curnode.nodeName.toLowerCase() != "table" && curnode.nodeName.toLowerCase() != "br")
											subarr[subarr.length] = curnode;
										else {
											var t = $("<p id='dparagraph' style='margin:0'></p>").append(subarr)
												finalarr = finalarr.append(t);
											finalarr = finalarr.append(curnode);
											subarr = [];
										}

									}
									if (subarr.length > 0) {
										var t = $("<p id='dparagraph' style='margin:0'></p>").append(subarr)
											finalarr.append(t);
									}

								}
							} else
								finalarr.append(tmpobj)

								fieldval = finalarr.html();
						}

					}
					if (ismulti) {
						fieldval = fieldval.split(sep);
						for (var x = 0; x < fieldval.length; x++) {
							if (datatype == "date") {
								fieldval[x] = new Date(fieldval[x]);
							} else if (datatype == "number") {
								fieldval[x] = Number(fieldval[x]);
							}
						}
					} else {
						if (datatype == "date") {
							fieldval = new Date(fieldval);
						} else if (datatype == "number") {
							fieldval = Number(fieldval);
						}
					}
					var retobj = {};
					retobj.fieldvalue = fieldval;
					retobj.fieldtype = datatype;

					result[fieldname] = retobj;
				});
				//-- save action type
			} else if (parsetype.toLowerCase() == "save") {
				if (_isNewDocument) {
					result = xmlobj.find("Result[ID=Ret1]").text();
				} else {
					result = true;
				}
				//-- delete action type
			} else if (parsetype.toLowerCase() == "delete") {
				result = true;
			} else if (parsetype.toLowerCase() == "responses") {
				result = xmlobj.find("Result[ID=Ret1]").text();
				result = result.split(",");
				//-- get attachment listing
			} else if (parsetype.toLowerCase() == "attachment" || parsetype.toLowerCase() == "attachments") {
				result = new Array();
				jQuery(resultxmlobj).find("File").each(function () {
					var filename = jQuery(this).find("FileName").text();
					var filesize = jQuery(this).find("FileSize").text();
					filesize = (!filesize ? 0 : parseInt(filesize, 10));
					var filedate = jQuery(this).find("FileDate").text();
					filedate = (!filedate ? new Date() : filedate);
					var fileurl = jQuery(this).find("URL").text()
						result.push({
							'filename': filename,
							'filesize': filesize,
							'filedate': filedate,
							'fileurl': fileurl
						});
				});
				//-- return the result text as is
			} else if (parsetype.toLowerCase() == "text") {
				result = xmlobj.find("Result[ID=Ret1]").text();
			}
		}
		//      }catch(err){
		//          return null;
		//      }

		return result;
	} //--end _parserHelper


	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new document
	 *-------------------------------------------------------------------------------------------------*/
	if (parentobj === undefined || parentobj === null) {
		return;
	}
	if (parentobj.constructor_name == "DocovaApplication") {
		_parentApp = parentobj;
	} else if (parentobj.constructor_name == "DocovaUIView") {
		_parentUIView = parentobj;
		_parentApp = Docova.getApplication({
				"appid": parentobj.appID
			});
	} else if (parentobj.constructor_name == "DocovaView") {
		_parentView = parentobj;
		_parentApp = parentobj.parentApp;
	} else if (parentobj.constructor_name == "DocovaFolder") {
		_parentFolder = parentobj;
		_parentApp = parentobj.parentApp;
	} else if (parentobj.constructor_name == "DocovaDocument") {
		_parentDoc = parentobj;
		_parentView = parentobj.parentView;
		_parentApp = parentobj.parentApp;
	} else if (parentobj.constructor_name == "DocovaUIDocument") {
		_parentUIDoc = parentobj;
		_isProfile = parentobj.isProfile;
		_profileKey = parentobj.profileKey;
		_parentApp = Docova.getApplication({
				"appid": parentobj.appID,
				"appname": parentobj.appName
			});
	}
	if ((_parentUIDoc && _parentUIDoc.isNewDoc) || ((id == undefined || id == null || id == "") && (unid == undefined || unid == null || unid == ""))) {
		//parent ui doc is new or no existing id so treat as a new document
		_isNewDocument = true;
		_isModified = true;
	} else {
		_id = (id == undefined || id == "") ? "DK" + unid : id;
		_unid = (unid == undefined || unid == "") ? id.slice(2) : unid;
	}
	//--end constructor
} //end DocovaDocument class


/*
 * Class: DocovaField
 * Back end class and methods for interaction with DOCOVA field
 * Typically accessed from DocovaDocument.getFirstItem() or new DocovaField(document, fieldname, fieldvalue).
 * Parameters:
 * 		parentdoc: DocovaDocument object
 * 		fieldname: string - name of field
 * 		fieldvalue: various - value to assign to field
 *      specialtype: string - "NAMES", "READERS", "AUTHORS"
 **/
function DocovaField(parentdoc, fieldname, fieldvalue, specialtype, fieldType) {

	var _parentDoc = null;
	var _fieldName = "";
	var _isNames = false;
	var _isReaders = false;
	var _isAuthors = false;
	var _isSummary = true;
	var _dataType = "";
	var _self = this;
	var _remove = false;
	var _ismodified = true;
	var _fieldvalue = fieldvalue;
	var _sourceDocID = "";
	var _sourceDbPath = "";
	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return (_dataType == "richtext" ? "DocovaRichTextItem" : "DocovaField");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'dateTimeValue', {
		get: function () {
			var result = null;
			if (Object.prototype.toString.call(_fieldvalue) === '[object Date]') {
				result = _fieldvalue;
			} else {
				result = $$TextToTime(_fieldvalue);
			}
			return result;
		},
		set: function (newval) {
			var result = null;
			if (Object.prototype.toString.call(newval) === '[object Date]') {
				result = newval;
			} else {
				result = $$TextToTime(newval);
			}
			this.values = result;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'sourceDocID', {
		get: function () {
			return _sourceDocID
		},
		set: function (newval) {
			_sourceDocID = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'sourceDbPath', {
		get: function () {
			return _sourceDbPath
		},
		set: function (newval) {
			_sourceDbPath = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'modified', {
		get: function () {
			return _ismodified;
		},
		set: function (newval) {
			_ismodified = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'removeField', {
		get: function () {
			return _remove;
		},
		set: function (newval) {
			_remove = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isAuthors', {
		get: function () {
			return _isAuthors;
		},
		set: function (newval) {
			_isAuthors = (newval === true ? true : false);
			_ismodified = true;
			if (parentdoc) {
				parentdoc.isModified = true;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isNames', {
		get: function () {
			return _isNames;
		},
		set: function (newval) {
			_isNames = (newval === true ? true : false);
			_ismodified = true;
			if (parentdoc) {
				parentdoc.isModified = true;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isReaders', {
		get: function () {
			return _isReaders;
		},
		set: function (newval) {
			_isReaders = (newval === true ? true : false);
			_ismodified = true;
			if (parentdoc) {
				parentdoc.isModified = true;
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isSummary', {
		get: function () {
			return _isSummary;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isSigned', {
		get: function () {
			return false;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isProtected', {
		get: function () {
			return false;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isEncrypted', {
		get: function () {
			return false;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'name', {
		get: function () {
			return _fieldName;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parent', {
		get: function () {
			return _parentDoc;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'text', {
		get: function () {
			return this._ToText();
		},
		enumerable: true
	});
	Object.defineProperty(this, 'type', {
		get: function () {
			return _dataType;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'values', {
		get: function () {
			var tempval = null;
			if (_fieldvalue !== 'undefined') {
				if (!Array.isArray(_fieldvalue))
					tempval = [_fieldvalue];
				else
					tempval = _fieldvalue
			} else if (_parentDoc && _fieldName !== "") {
				tempval = _parentDoc._getFieldFromDoc(_fieldName);
			}
			return tempval;
		},
		set: function (newval) {
			_fieldvalue = newval;
			if (_dataType === 'undefined' || _dataType == "")
				_dataType = _getFieldType(newval);
			if (parentdoc) {
				parentdoc._addItemToDoc(this);
			}
			_ismodified = true;

		},
		enumerable: true
	});

	Object.defineProperty(this, 'toXML', {
		get: function () {
			return this._ToXML();
		},
		enumerable: true
	});

	function _getFieldType(itemvalue) {
		//determine data type
		if (!Array.isArray(itemvalue)) {
			var tmpval = itemvalue;
			tempval = new Array();
			tempval.push(tmpval);
		} else {
			tempval = itemvalue;
		}
		var datatype = "";
		for (var x = 0; x < tempval.length; x++) {
			if (datatype === "text") {
				break;
			}
			if (tempval[x] !== "") {
				if (Object.prototype.toString.call(tempval[x]) === '[object Date]') {
					datatype = "date";
				} else if (typeof tempval[x] === "number" || typeof tempval[x] === "boolean") {
					datatype = "number";
				} else if (typeof tempval[x] === "string") {
					datatype = "text";
				}
			}
		}
		if (datatype == "") {
			datatype = "text";
		}
		return datatype;
	}

	this._ToXML = function () {
		if (_DOCOVAEdition == "SE") {
			var seplist = new Array(",", ";", ":", "\u00A5");
		} else {
			var seplist = new Array(",", ";", ":", "Â¥");
		}

		var fieldname = this.name.replace(/\$/g, "__S__"); //--handle invalid xml field name char
		var tempval = this.values;
		var specialtype = "";
		if (this.isAuthors) {
			specialtype = "AUTHORS";
		} else if (this.isReaders) {
			specialtype = "READERS";
		} else if (this.isSummary) {
			specialtype = "SUMMARY";
		} else if (this.isNames) {
			specialtype = "NAMES";
		}

		var deletefield = false;

		if (this.removeField || tempval === null) {
			deletefield = true;
			tempval = [""];
		}

		var datatype = this.type

			for (var x = 0; x < tempval.length; x++) {
				if (datatype == "date") {
					if (tempval[x] != "" && Object.prototype.toString.call(tempval[x]) === '[object Date]') {
						var yvar = tempval[x].getFullYear();
						var mvar = tempval[x].getMonth() + 1;
						var dvar = tempval[x].getDate();
						var hvar = tempval[x].getHours();
						var nvar = tempval[x].getMinutes();
						var svar = tempval[x].getSeconds();
						tempval[x] = yvar.toString() + "-" + mvar.toString() + "-" + dvar.toString();
						if (!isNaN(hvar) && !isNaN(nvar) && !isNaN(svar)) {
							tempval[x] = tempval[x] + " " + hvar.toString() + ":" + nvar.toString() + ":" + svar.toString();
						}
					}
				} else if (datatype == "number") {
					tempval[x] = tempval[x].toString();
				} else {
					if (tempval[x] === null) {
						tempval[x] = "";
					} else if (typeof tempval[x] != "string") {
						tempval[x] = tempval[x].toString();
					}
				}

				for (var s = (seplist.length - 1); s > -1; s--) {
					if (tempval[x].indexOf(seplist[s]) > -1) {
						seplist.splice(s, 1);
					}
				}
			}

			var srcID = ""
			if (_sourceDocID && _sourceDocID != "")
				srcID = ' source="' + _sourceDocID + '" '

					var srcdbpath = "";
			if (_sourceDbPath && _sourceDbPath != "")
				srcID += ' sourcedbpath="' + _sourceDbPath + '" '
				var sep = seplist[0];
			var sptype = specialtype == "" ? ' specialType=""' : ' specialType="' + specialtype + '" '
			var attribs = srcID + ' dt="' + datatype + '"' + sptype + (deletefield ? ' ignoreblanks="1"' : '') + (tempval.length > 1 ? ' multi="1" sep="' + sep + '"' : '');

		request = "<" + fieldname + attribs;
		request += "><![CDATA[";
		request += (tempval.length > 1 ? tempval.join(sep) : tempval[0]);
		request += "]]></" + fieldname + ">";
		return request;
	}
	this._ToText = function (forcedelim) {
		var seplist = new Array(",", ";", "\n");
		var tempval = this.values;
		var deletefield = false;
		if (this.removeField || tempval === null) {
			deletefield = true;
			tempval = [""];
		}
		var datatype = this.type

			for (var x = 0; x < tempval.length; x++) {
				if (datatype == "date") {
					if (tempval[x] != "") {
						tempval[x] = Docova.Utils.convertDateFormat(tempval[x]);
					}
				} else if (datatype == "number") {
					tempval[x] = tempval[x].toString();
				} else {
					if (typeof tempval[x] == "undefined" || tempval[x] === null) {
						tempval[x] = "";
					} else if (typeof tempval[x] != "string") {
						tempval[x] = tempval[x].toString();
					}
				}

				for (var s = (seplist.length - 1); s > -1; s--) {
					if (tempval[x].indexOf(seplist[s]) > -1) {
						seplist.splice(s, 1);
					}
				}
			}
			var sep = ((typeof forcedelim == "undefined" || forcedelim === null || forcedelim == "") ? seplist[0] : forcedelim);
		return (tempval.length > 1 ? tempval.join(sep) : tempval[0]);
	}
	/*----------------------------------------------------------------------------------------------
	 * Methods:
	 *-------------------------------------------------------------------------------------------------*/

	/*----------------------------------------------------------------------------------------------
	 * Function: appendToTextList
	 * adds a string or an array of strings to an existing text value
	 * Input: newValue - string or array of strings - to be appended to the existing field value
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	this.appendToTextList = function (newValue) {
		if (newValue === undefined || newValue === null) {
			return;
		}
		if (_parentDoc && _fieldName !== "") {
			var tempval = _parentDoc.getField(_fieldName);
			if (tempval === null) {
				tempval = newValue;
			} else {
				tempval = tempval.concat(newValue);
			}
			this.values = tempval;

			//--check if this document is accessed from a uidoc in edit mode
			if (_parentDoc.parentUIDoc && _parentDoc.parentUIDoc.isDocBeingEdited) {
				//-- try to set value on front end ui doc
				_parentDoc.parentUIDoc.setField(_fieldName, tempval);
			}
		}
	} //--end appendToTextList

	/*----------------------------------------------------------------------------------------------
	 * Function: contains
	 * checks if a value is contained as one of the values in an array of values
	 * Input: searchValue - string, number, or date
	 * Returns: boolean - true if value found in the array, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.contains = function (searchValue) {
		var result = false;
		if (searchValue === undefined || searchValue === null) {
			return result;
		}
		if (_parentDoc && _fieldName !== "") {
			var tempval = _parentDoc.getField(_fieldName);
			if (tempval !== null) {
				result = (tempval.indexOf(searchValue) > -1);
			}
		}
		return result;
	} //--end contains

	/*----------------------------------------------------------------------------------------------
	 * Function: copyItemToDocument
	 * copies the item values to another document
	 * Input: targetdoc - DocovaDocument - target document to copy field values to
	 *        newname - string (optional) - new name to use for the field on target document
	 * Returns: DocovaField object - new DocovaField object on target document
	 *-------------------------------------------------------------------------------------------------*/
	this.copyItemToDocument = function (targetdoc, newname) {
		var newitem = null;
		if (targetdoc === undefined || targetdoc === null || targetdoc.constructor_name !== "DocovaDocument") {
			return newitem;
		}
		if (_parentDoc && _fieldName !== "") {
			var newfieldname = _fieldName;
			if (typeof newname != "undefined" && newname != null && newname != "") {
				newfieldname = newname;
			}
			var specialtype = "";
			if (_isAuthors = true) {
				specialtype == "AUTHORS";
			} else if (_isReaders) {
				specialtype == "READERS";
			} else if (_isNames) {
				specialtype == "NAMES";
			}
			targetdoc._addItemToDoc(new DocovaField(_parentDoc, newfieldname.toLowerCase(), _fieldvalue, specialtype, _datatype));
			newitem = targetdoc.fieldBuffer[newfieldname.toLowerCase()];
		}
		return newitem;
	} //--end copyItemToDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: remove
	 * deletes a field from the document
	 *-------------------------------------------------------------------------------------------------*/
	this.remove = function () {
		if (_parentDoc && _fieldName !== "") {
			_parentDoc.deleteField(_fieldName);
		}
	} //--end remove


	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new field
	 *-------------------------------------------------------------------------------------------------*/
	if (parentdoc === undefined || parentdoc === null || parentdoc.constructor_name !== "DocovaDocument" || fieldname === undefined || fieldname === null) {
		return;
	}
	_parentDoc = parentdoc;
	_fieldName = fieldname;
	if (specialtype !== undefined && specialtype !== null) {
		if (specialtype == "NAMES") {
			_isNames = true;
		} else if (specialtype == "READERS") {
			_isReaders = true;
		} else if (specialtype == "AUTHORS") {
			_isAuthors = true;
		}
	}
	if (typeof _fieldvalue !== 'undefined') {
		if (typeof fieldType !== 'undefined' && fieldType !== null) {
			_dataType = fieldType
		} else {
			_dataType = _getFieldType(_fieldvalue);
		}
		parentdoc._addItemToDoc(this);
	}

	//--end constructor
} //end DocovaField class


/*
 * Class: DocovaRichTextParagraphStyle
 * Represents rich text paragraph attributes.
 *
 **/
function DocovaRichTextParagraphStyle() {
	var _alignment = "left"; //text-align
	var _firstlineleftmargin = "0px"; //text indent
	var _leftmargin = "0px"; //left padding
	var _pagination = "0px"; //page-break
	var _rightmargin = "100%"; //width
	var _spacingabove = "0px"; //top padding
	var _spacingbelow = "0px"; //bottom padding
	var _interlinespacing = "100%" //line-height

		Object.defineProperty(this, 'alignment', {
			get: function () {
				return _alignment;
			},
			set: function (newval) {
				_alignment = newval;
			},
			enumerable: true
		});

	Object.defineProperty(this, 'interLineSpacing', {
		get: function () {
			return _interlinespacing;
		},
		set: function (newval) {
			_interlinespacing = newval;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'firstLineLeftMargin', {
		get: function () {
			return _firstlineleftmargin;
		},
		set: function (newval) {
			_firstlineleftmargin = newval;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'leftMargin', {
		get: function () {
			return _leftmargin;
		},
		set: function (newval) {
			_leftmargin = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'pagination', {
		get: function () {
			return _pagination;
		},
		set: function (newval) {
			_pagination = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'rightMargin', {
		get: function () {
			return _rightmargin;
		},
		set: function (newval) {
			_rightmargin = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'spacingAbove', {
		get: function () {
			return _spacingabove;
		},
		set: function (newval) {
			_spacingabove = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'spacingBelow', {
		get: function () {
			return _spacingbelow;
		},
		set: function (newval) {
			_spacingbelow = newval;
		},
		enumerable: true
	});

}

/*
 * Class: DocovaRichTextItem
 * Back end class and methods for interaction with DOCOVA RichTextItem
 * Typically accessed from DocovaDocument.getFirstItem() or new DocovaRichTextItem(document, fieldname, fieldvalue).
 * Parameters:
 * 		parentdoc: DocovaDocument object
 * 		fieldname: string - name of field
 * 		objfield: DocovaField - docova field object
 *
 **/
function DocovaRichTextItem(parentdoc, fieldname, objfield) {
	var _currentStyle = null;
	var _currentParagraphStyle = null;
	var _navigator = null;
	var _range = null;
	var _insertAtElem = null; //where to insert content.set using setBegin
	var _insertAtEnd = false;
	var _domArray = null;
	var _lastParagraph = null;
	var _insertionStarted = false;
	var _insertArray = $();

	/**** Properties ****/
	Object.defineProperty(this, 'navigator', {
		get: function () {
			return _navigator;
		},

		enumerable: true
	});

	Object.defineProperty(this, 'domArray', {
		get: function () {
			return _domArray;
		},
		set: function (newval) {
			_domArray = newval;
		},

		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Function: addNewLine
	 * Adds a new line to the rich text item
	 * Input: count - Integer - how many newlines to add
	 *-------------------------------------------------------------------------------------------------*/
	this.addNewLine = function (count) {
		if (!count || typeof count === 'undefined') {
			count = 1;
		}
		var html = "";
		var domArr = _domArray;

		//the first <br> will happen automatically due to the <p>
		//so we just add any additional ones
		var start = 1;

		if (_insertAtElem != null && _insertAtElem.nodeName.toLowerCase() == "td")
			start = 0;
		for (var p = start; p < count; p++)
			html += "<br>";

		var val;
		if (_insertAtElem != null) {
			_insertContent($(_insertAtElem), html)
			val = domArr;
		} else {

			val = domArr.append(html);
		}

		var c = $("<div></div>").append(val).html();
		this.values = c;

		_lastParagraph = null;
	} //--end addNewLine

	/*----------------------------------------------------------------------------------------------
	 * Function: addTab
	 * Adds 4 spaces (in place of tab)
	 * Input: count - Integer - how many simulated tabs to add
	 *-------------------------------------------------------------------------------------------------*/
	this.addTab = function (count) {

		if (!count || typeof count === 'undefined') {
			count = 1;
		}

		var temptext = "";

		for (var p = 0; p < count; p++) {
			temptext += "    ";
		}

		this.appendText(temptext);
	} //--end addTab

	/*----------------------------------------------------------------------------------------------
	 * Private Function: _insertContent
	 * Appends the added element to a private array
	 *
	 *-------------------------------------------------------------------------------------------------*/
	function _insertContent(targetElem, newContent) {
		_insertArray = _insertArray.add($(newContent))
			return;

	} //--end _insertContent

	/*----------------------------------------------------------------------------------------------
	 * Function: appendText
	 * Appends the text to the rich text item
	 * Input: intext - Text - value to append
	 *
	 *-------------------------------------------------------------------------------------------------*/
	this.appendText = function (intext) {
		var html = _styleText("span", intext);
		var domArr = _domArray;

		var val;
		if (_insertAtElem != null) {
			_insertContent($(_insertAtElem), html)
			val = domArr;
		} else {
			if (_lastParagraph == null)
				_lastParagraph = $("<p id='dparagrap' style='margin:0'></p>").append(html);
			else
				_lastParagraph.append(html);

			val = domArr.append(_lastParagraph);
		}

		var c = $("<div></div>").append(val).html();
		this.values = c;
	} //--end appendText


	/*----------------------------------------------------------------------------------------------
	 * Function: beginInsert
	 * Changes the insertion position from the end of the rich text item to the beginning or end of a specified element.
	 * Input: inelem - DocovaRichTextNavigator
	 *
	 *-------------------------------------------------------------------------------------------------*/
	this.beginInsert = function (inelem, after) {
		//TODO: add support for NotesRichTextTable, NotesRichTextSection and others( ?maybe )
		if (inelem.constructor_name == "DocovaRichTextNavigator") {
			_insertAtElem = inelem.currentElement;

			if (after) {
				_insertAtEnd = true;
			}
		}
	} //--end beginInsert

	/*----------------------------------------------------------------------------------------------
	 * Function: endInsert
	 * Resets the insertion position to the end of the rich text item. Must be paired with BeginInsert.
	 *
	 *-------------------------------------------------------------------------------------------------*/
	this.endInsert = function () {
		//insert all content inserted after the called to begin insert
		if (_insertArray.length > 0) {
			if (_insertAtElem != null) {
				if (!_insertAtEnd) {
					$(_insertAtElem).prepend(_insertArray)
				} else {
					$(_insertAtElem).append(_insertArray)
				}
				var c = $("<div></div>").append(_domArray).html();
				this.values = c;
			}
			_insertArray = $();
		}

		_insertAtElem = null;
		_insertAtEnd = false;
		_insertionStarted = false;
	} //--end endInsert

	/*----------------------------------------------------------------------------------------------
	 * Function: appendHTML
	 * Appends the HTML to the rich text item
	 * Input: intext - Text - value to append
	 *
	 *-------------------------------------------------------------------------------------------------*/
	this.appendHTML = function (intext) {
		var html = intext;
		var domArr = _domArray;

		var val;
		if (_insertAtElem != null) {
			_insertContent($(_insertAtElem), html)
			val = domArr;
		} else {
			val = domArr.append(html);
		}

		var c = $("<div></div>").append(val).html();
		this.values = c;
	} //--end appendText


	/*----------------------------------------------------------------------------------------------
	 * Function: appendRtItem
	 * Appends a richtext items contents to another rich text item
	 * Input: rtitem - DocovaRichTextItem - item to append
	 *-------------------------------------------------------------------------------------------------*/
	this.appendRTItem = function (rtitem) {

		var html = rtitem.values[0];
		var domArr = _domArray;

		var val;
		if (_insertAtElem != null) {
			_insertContent($(_insertAtElem), html)
			val = domArr;
			val = domArr;
		} else {
			val = domArr.append(html);
		}

		var c = $("<div></div>").append(val).html();
		this.values = c;
	} //--end appendRTItem

	/*----------------------------------------------------------------------------------------------
	 * Function: appendTable
	 * Appends a table to the rich text item
	 * Input: rows - Integer -Number of rows to add
	 *        columns - Integer - Number of columns to add
	 *        labels - string[] - Optional - Array of type String.  Text of labels for a tabbed table. The number of array elements must equal the number of rows. Omitting this parameter appends a basic table. Including this parameter appends a tabbed table.
	 *		  leftMargin - integer - Optional - Left margin of the table in pixels
	 *        rtpsStyleArray - NotesRichTextParagraphStyle[] - Optional. Creates a table with fixed-width columns and style attributes as specified. Omitting this parameter creates an auto-width table. The array must contain one element for each column in the table in sequence. Explicitly set the first line left margin and left margin, which control the start of text relative to the start of the column, and the right margin, which controls column width.
	 *-------------------------------------------------------------------------------------------------*/
	this.appendTable = function (rows, columns, labels, leftmargin, rtpsStyleArray) {
		if (rtpsStyleArray && typeof rtpsStyleArray !== 'undefined') {
			if (columns != rtpsStyleArray.length) {
				return;
			}
		}
		leftmargin = typeof leftmargin !== 'undefined' ? leftmargin : "";
		var tablestr = "<table style='margin-left:" + leftmargin + "; border-spacing: 0px; border-collapse: collapse; '>";
		for (var r = 0; r < rows; r++) {
			tablestr += "<tr>"
			for (var c = 0; c < columns; c++) {
				if (Array.isArray(rtpsStyleArray)) {
					var pstyle = rtpsStyleArray[c];
					tablestr += _styleText("TD", "", _currentStyle, pstyle);
				} else {
					tablestr += _styleText("TD", "");
				}

			}
			tablestr += "</tr>"
		}
		tablestr += "</table>"

		var html = tablestr;
		//var domArr = $(this.values[0]);
		var domArr = _domArray;
		var val;
		if (_insertAtElem != null) {
			_insertContent($(_insertAtElem), html)
			val = domArr;
		} else {
			val = domArr.append(html);
		}
		var c = $("<div></div>").append(val).html();

		this.values = c;
	} //--end appendTable

	/*----------------------------------------------------------------------------------------------
	 * Function: appendDocLink
	 * Appends a doclink to a richtext item
	 * Input: linkto - (DocovaDocument, DocovaView) - object to add a link to
	 * 		  commnet- Text - Text that will appear on the hover of this link
	 *        hotspottext - Text - The text that will appear as a link
	 *-------------------------------------------------------------------------------------------------*/
	this.appendDocLink = function (linkto, comment, hotspottext) {
		if (linkto.constructor_name == "DocovaDocument") {

			var linktext = "<a target = '_self' href='";
			linktext += linkto.getURL({'mode':'window'});
			linktext += "' title='" + (comment ? comment : "") + "'";
			linktext += ">" + (hotspottext ? hotspottext : "link") + "</a>";

			var html = _styleText("P", linktext); ;
			var domArr = _domArray;

			var val;
			if (_insertAtElem != null) {
				_insertContent($(_insertAtElem), html)
				val = domArr;
			} else {
				if (_lastParagraph == null)
					_lastParagraph = $("<p id='dparagrap' style='margin:0'></p>").append(html);
				else
					_lastParagraph.append(html);

				val = domArr.append(_lastParagraph);
			}

			var c = $("<div></div>").append(val).html();
			this.values = c;

		}
	} //--end appendDocLink

	/*----------------------------------------------------------------------------------------------
	 * Function: appendStyle
	 * Appends a DocovaRichTextStyle to the rich text item
	 * Input: objStyle - DocovaRichTextStyle - style to append
	 *-------------------------------------------------------------------------------------------------*/
	this.appendStyle = function (objStyle) { //DocovaRichTextStyle
		_currentStyle = objStyle;

	} //--end appendStyle

	/*----------------------------------------------------------------------------------------------
	 * Function: appendParagraphStyle
	 * Appends a DocovaRichTextParagraphStyle object to rich text item
	 * Input: objStyle - DocovaRichTextParagraphStyle - paragraph style to append
	 *-------------------------------------------------------------------------------------------------*/
	this.appendParagraphStyle = function (objStyle) { //DocovaRichTextParagraphStyle
		_currentParagraphStyle = objStyle;

	} //--end appendParagraphStyle

	/*----------------------------------------------------------------------------------------------
	 * Function: createNavigator
	 * Creates a NotesRichTextNavigator object.
	 * Output: DocovaRichTextNavigator
	 *-------------------------------------------------------------------------------------------------*/
	this.createNavigator = function () { //DocovaRichTextParagraphStyle
		var domarr;
		if (_domArray == null) {
			domarr = $(this.values[0])
				_domArray = domarr;
		} else
			domarr = _domArray;

		_navigator = new DocovaRichTextNavigator(domarr);
		return _navigator;
	} //--end createNavigator


	/*----------------------------------------------------------------------------------------------
	 * Function: createRange
	 * Creates a DocovaRichTextRange object.
	 * Output: DocovaRichTextRange
	 *-------------------------------------------------------------------------------------------------*/
	this.createRange = function () {
		var domarr;
		if (_domArray == null) {
			domarr = $(this.values[0])
				_domArray = domarr;
		} else
			domarr = _domArray;

		_range = new DocovaRichTextRange(domarr);
		return _range;
	} //--end createRange

	/*----------------------------------------------------------------------------------------------
	 * Function: getUnformattedText
	 * Returns text from a rich text item.
	 * Output: String
	 *-------------------------------------------------------------------------------------------------*/
	this.getUnformattedText = function () {
		return jQuery(_domArray).text();
	} //--end getUnformattedText

	/*----------------------------------------------------------------------------------------------
	 * Function: _styleText
	 * Private function that styles the incoming data based on the style and paragraph styles
	 * specified for this RichTextItem
	 *-------------------------------------------------------------------------------------------------*/
	function _styleText(elem, inputtext, style, paraStyle) {
		var fstyle = !style || typeof style === 'undefined' ? _currentStyle : style
			var pstyle = !paraStyle || typeof paraStyle === 'undefined' ? _currentParagraphStyle : paraStyle
			var elemstr = "<" + elem + " style='"
			var stylestr = "";
		if (fstyle != null) {
			objStyle = fstyle;
			stylestr += "font:" + objStyle.font
			stylestr += "; color:" + objStyle.color
			stylestr += "; font-weight:" + (objStyle.bold ? "bold" : "normal")
			stylestr += "; text-decoration:" + (objStyle.underline ? "underline" : "")
			stylestr += "; font-size:" + objStyle.fontSize
		}

		if (pstyle != null) {
			objStyle = pstyle;

			stylestr += stylestr == "" ? "" : ";" + "text-align:" + objStyle.alignment
			stylestr += "; text-indent:" + objStyle.firstLineLeftMargin
			stylestr += "; line-height:" + objStyle.interLineSpacing
			stylestr += "; margin-left:" + objStyle.leftMargin
			stylestr += "; width:" + objStyle.rightMargin
			stylestr += "; padding-top:" + objStyle.spacingAbove
			stylestr += "; padding-bottom:" + objStyle.spacingBelow
		}
		return elemstr + stylestr + "'>" + inputtext + "</" + elem + ">"

	} //--end _styleText

	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new RichTextField
	 *-------------------------------------------------------------------------------------------------*/

	if (objfield && typeof objfield !== 'undefined') {
		DocovaField.call(this, parentdoc, objfield.name, objfield.values, "", "richtext")
	} else {
		DocovaField.call(this, parentdoc, fieldname, "", "", "richtext");
	}
	container = $(this.values[0]).find("#rtcontainer");
	if (container.length == 0) {
		_domArray = $("<div id='rtcontainer'></div>").append($(this.values[0]))
	} else {
		_domArray = $(this.values[0]);
	}
}

/*
 * Class: DocovaRichTextNavigator
 * Represents a means of navigation in a rich text item.
 * Parameters:
 * 		itemDomArray: Jquery object of the html in the richtextitem.
 * 		startElem: domNode - optional - start element used by the richtextrange object
 * 		endElem: domNode - optional - end element used by the richtextrange object
 *
 **/

function DocovaRichTextNavigator(itemDomArray, startElem, endElem) {

	var _currentItem = null;
	var _textparagraphpos = 0;
	var _lastelement = "";
	var _curIndex = 0; //index of the current element within the dom tree
	var _curPosElem = null; //element at the current navigator position
	var _startIndex = null;
	var _endIndex = null;
	var _itemDomArray = [];
	var _includeLastElement = false;
	var _startElem = null;
	var _endElem = null;
	var _currentElement = null;

	//global varialbes for recursive function
	var _startLooking = false;
	var _currentCount = 0;

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaRichTextNavigator";
		},
		enumerable: true
	});

	Object.defineProperty(this, 'currentElement', {
		get: function () {
			return _currentElement;
		},
		set: function (newval) {
			_currentElement = newval;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'positionAtEnd', {
		get: function () {
			return _includeLastElement;
		},
		set: function (newval) {
			_includeLastElement = newval;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'currentIndex', {
		get: function () {
			return _curIndex;
		},
		set: function (newval) {
			_curIndex = newval;

		},
		enumerable: true
	});

	Object.defineProperty(this, 'startElement', {
		get: function () {
			return _startElem;
		},
		set: function (newval) {
			_startElem = newval;

		},
		enumerable: true
	});

	Object.defineProperty(this, 'endElement', {
		get: function () {
			return _endElem;
		},
		set: function (newval) {
			_endElem = newval;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Private Function: _recurseHelper
	 * Used to traverse the dom tree in the richtextitem
	 * output : domNode - the pointer to the requested element through the navigation
	 *-------------------------------------------------------------------------------------------------*/

	this._recurseHelper = {
		_startLooking: false,
		_currentCount: 0,
		_includelast: false,
		_getlast: false,
		_lastelemfound: null,
		reset: function () {
			this._currentCount = 0;
			this._startLooking = false;
			this._getlast = false;
			this._lastelemfound = null;
			this._includelast = false;
		},

		recurse: function (curelem, startelem, endelem, occurance, tosearch, findtype) {
			var that = this;
			var count = 0;
			var foundelem = null;

			if (typeof curelem == 'undefined') {
				return null;
			}

			//if we are at the end of the range then stop recursing
			if (endelem != null && curelem == endelem && !this._includelast) {
				return null;
			}

			//have we reached the starting point of our range
			if (!this._startLooking)
				this._startLooking = (startelem == null || curelem == startelem) ? true : false;

			if (this._startLooking) {
				if (curelem.nodeName.toLowerCase() == tosearch) {
					this._currentCount++;
					if (this._getlast) {
						this._lastelemfound = curelem;
					} else {
						if (this._currentCount == occurance)
							return curelem;
					}
				}
			}
			var retnode = null;

			if (endelem != null && endelem == curelem && this._includelast) {
				return "quit";
			}

			var node = curelem.firstChild;
			while (node && typeof node !== 'undefined') {
				var retnode = this.recurse(node, startelem, endelem, occurance, tosearch);
				if (retnode !== null || retnode == "quit") {
					break;
				}
				node = node.nextSibling;
			}

			return retnode;

		}

	} //-end _recurseHelper

	/*----------------------------------------------------------------------------------------------
	 * Private Function: _findElement
	 * Input:
	 * 		findtype : string - one of "first", "nth", "last"
	 * 		elemnumber: integer - the occurance of the element we are looking for
	 * 		typeofelement: string - one of "textparagraph", "doclink", "table", "tablecell"
	 * output : domNode - the element found through traversal.
	 *-------------------------------------------------------------------------------------------------*/

	this._findElement = function (findtype, elemnumber, typeofelement) {
		if (findtype == "first") {
			if (!typeofelement || typeof typeofelement === 'undefined'){
				return false;
			}
		} else {
			if (!typeofelement || typeof typeofelement === 'undefined')
				typeofelement = _lastelement;
			if (!typeofelement || typeof typeofelement === 'undefined')
				return false;
		}
		if (typeofelement == "textparagraph") {
			tosearch = "p";
		} else if (typeofelement == "doclink") {
			tosearch = "a";
		} else if (typeofelement == "table") {
			tosearch = "table";
		} else if (typeofelement == "tablecell") {
			tosearch = "td";
		}
		if (_itemDomArray.length == 0){
			return false;
		}

		startelem = _itemDomArray.get(0);

		var root = _itemDomArray.get(0);
		var startelem = _startElem != null ? _startElem : startelem;
		if (findtype == "next")
			startelem = _currentElement;

		var endelem = _endElem;
		var getlast = findtype == "last" ? true : false;

		var occurance = !elemnumber || typeof elemnumber === 'undefined' ? 1 : elemnumber
			if (findtype == "next")
				occurance++;

			var iterator = this._recurseHelper;
		iterator.reset();
		iterator._includelast = _includeLastElement;
		iterator._getlast = getlast;
		var foundelem = iterator.recurse(root, startelem, endelem, occurance, tosearch, findtype);

		if (getlast) {
			if (iterator._lastelemfound) {
				_currentElement = iterator._lastelemfound;
				_lastelement = typeofelement;
				return true;
			}
			return false;
		}

		if (foundelem == null)
			return false;
		else {
			_currentElement = foundelem;
			_lastelement = typeofelement;
			return true;
		}

	} //end -- _findElement

	/*----------------------------------------------------------------------------------------------
	 * Function: findFirstElement
	 * Moves the current position to the first element of a specified type in a rich text item.
	 * Inputs:
	 *   typeofelement - string - one of "textparagraph", "doclink", "table", "tablecell"
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	this.findFirstElement = function (typeofelement) {
		return this._findElement("first", 1, typeofelement);
	} //end -- findFirstElement

	/*----------------------------------------------------------------------------------------------
	 * Function: findNextElement
	 * Moves the current position to the next element of a specified type in a rich text item.
	 * Inputs:
	 *   typeofelement - string - one of "textparagraph", "doclink", "table", "tablecell"
	 *   occurance - integer - The position of the element, within elements of the specified type, relative to the current position; 1 means the next element, 2 means the element after the next element, and so on. Must be a positive integer; you cannot use this method to find preceding elements. Defaults to 1.
	 * Returns: boolean - True if the element exists and the current pointer is reset.
	 * 		      False if the element does not exist and the current pointer is not reset.
	 *-------------------------------------------------------------------------------------------------*/
	this.findNextElement = function (typeofelement, occurrence) {
		var itemind = !occurrence || typeof occurrence === 'undefined' ? 1 : parseInt(occurrence);

		return this._findElement("next", itemind, typeofelement);
	} //end -- findNextElement

	/*----------------------------------------------------------------------------------------------
	 * Function: findNthElement
	 * Moves the current position to the nth element of a specified type in a rich text item.
	 * Inputs:
	 *   typeofelement - string - one of "textparagraph", "doclink", "table", "tablecell"
	 *   occurance - integer - The position of the element, within elements of the specified type, relative to the current position; 1 means the next element, 2 means the element after the next element, and so on. Must be a positive integer; you cannot use this method to find preceding elements. Defaults to 1.
	 * Returns: boolean - True if the element exists and the current pointer is reset.
	 * 		      False if the element does not exist and the current pointer is not reset.
	 *-------------------------------------------------------------------------------------------------*/
	this.findNthElement = function (typeofelement, occurrence) {
		var itemind = !occurrence || typeof occurrence === 'undefined' ? 1 : parseInt(occurrence);
		return this._findElement("nth", itemind, typeofelement);
	} //end -- findNthElement

	/*----------------------------------------------------------------------------------------------
	 * Function: findLastElement
	 * Moves the current position to the nth element of a specified type in a rich text item.
	 * Inputs:
	 *   typeofelement - string - one of "textparagraph", "doclink", "table", "tablecell"
	 * Returns: boolean - True if the element exists and the current pointer is reset.
	 * 		      False if the element does not exist and the current pointer is not reset.
	 *-------------------------------------------------------------------------------------------------*/
	this.findLastElement = function (typeofelement) {
		return this._findElement("last", 1, typeofelement);
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: getElement
	 * Returns the last found element.
	 * Returns: domNode - last element found
	 *-------------------------------------------------------------------------------------------------*/
	this.getElement = function () {
		return _currentElement;
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: setPositionAtEnd
	 * Sets the current position at the end of a specified element in a rich text item.
	 * Inputs:
	 *   element - NotesRichTextNavigator -
	 *-------------------------------------------------------------------------------------------------*/
	this.setPositionAtEnd = function (element) {
		//TODO implement other types of elements
		if (element.constructor_name == "DocovaRichTextNavigator") {
			element.positionAtEnd = true;
		}
	}

	//constructor
	if (itemDomArray) {
		_itemDomArray = itemDomArray;

		if (startElem && typeof startElem != 'undefined')
			_startElem = startElem

				if (endElem && typeof endElem != 'undefined')
					_endElem == endElem
	}

}

/*
 * Class: DocovaRichTextRange
 * Represents a range of elements in a rich text item.
 * Parameters:
 * 		itemDomArray: Jquery object of the html in the richtextitem.
 *
 **/
function DocovaRichTextRange(itemArray) {
	var _nav = null;
	var _currentItemArray = null;

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaRichTextRange";
		},
		enumerable: true
	});

	Object.defineProperty(this, 'navigator', {
		get: function () {
			return _nav;
		},
		enumerable: true
	});

	Object.defineProperty(this, 'textParagraph', {
		get: function () {
			var obj = _nav.currentElement;
			return $(obj).text();
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Function: setBegin
	 * Defines the beginning of a range.
	 * Inputs:
	 *   element - NotesRichTextNavigator -
	 *-------------------------------------------------------------------------------------------------*/

	this.setBegin = function (elem) {
		//TODO implement other types of elem's
		if (elem.constructor_name == "DocovaRichTextNavigator") {

			if (_nav == null) {
				_nav = new DocovaRichTextNavigator(_currentItemArray, elem.currentElement)
			} else {
				_nav.startElem = elem.currentElement;
			}

			_nav.currentElement = elem.currentElement;

		}
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: setEnd
	 * Defines the ending of a range.
	 * Inputs:
	 *   element - NotesRichTextNavigator -
	 *-------------------------------------------------------------------------------------------------*/

	this.setEnd = function (elem) {
		//TODO implement other types of elem's

		if (elem.constructor_name == "DocovaRichTextNavigator") {
			if (_nav == null) {
				_nav = new DocovaRichTextNavigator(_currentItemArray, null, elem.currentElement)
			} else {

				_nav.endElement = elem.currentElement;
			}
			_nav.positionAtEnd = elem.positionAtEnd;
		}
	}

	_currentItemArray = itemArray;
}

/*
 * Class: DocovaRichTextStyle
 * Represents a style object in a rich text item.
 *
 **/
function DocovaRichTextStyle() {
	var _bold = false;
	var _font = "Arial";
	var _italic = false
		var _color = "black";
	var _fontsize = "10px";
	var _underline = false;

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaRichTextStyle";
		},
		enumerable: true
	});

	Object.defineProperty(this, 'bold', {
		get: function () {
			return _bold;
		},
		set: function (newval) {
			_bold = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'italic', {
		get: function () {
			return _italic;
		},
		set: function (newval) {
			_italic = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'underline', {
		get: function () {
			return _underline;
		},
		set: function (newval) {
			_underline = (newval === true ? true : false);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'font', {
		get: function () {
			return _font;
		},
		set: function (newval) {
			_font = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'color', {
		get: function () {
			return _color;
		},
		set: function (newval) {
			_color = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'fontSize', {
		get: function () {
			return _fontsize;
		},
		set: function (newval) {
			_fontsize = newval;
		},
		enumerable: true
	});
}

/*
 * Class: DocovaCollection
 * Back end class and methods for interaction with collections of objects
 * Primarily used for collections of documents
 * Parameters:
 *
 * Properties:
 *    count: integer - number of entries in the collection
 *    isSorted: boolean - true if collection contents are sorted, defaults to false
 *    parent: DocovaApplication / DocovaView - application/library object  or view object where collection was generated from
 *    query: string - query used to obtain the collection
 *    type: string - type of collection - currently only DocovaDocument is supported, but other types may be supported
 * Methods:
 *    see below
 **/
function DocovaCollection(parentobj) {
	var _isSorted = false;
	var _contents = [];
	var _parent = null;
	var _parentApp = null;
	var _query = "";
	var _type = "DocovaDocument";

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaCollection";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'count', {
		get: function () {
			return _contents.length;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isSorted', {
		get: function () {
			return _isSorted;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parent', {
		get: function () {
			return _parent;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentApp', {
		get: function () {
			return _parentApp;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'query', {
		get: function () {
			return _query;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'type', {
		get: function () {
			return _type;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Methods:
	 *-------------------------------------------------------------------------------------------------*/

	/*----------------------------------------------------------------------------------------------
	 * Function: addEntry
	 * Adds a new entry to the collection
	 * Inputs:
	 *   newEntry - object - object to add to collection
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	this.addEntry = function (newEntry) {
		if (!newEntry || typeof newEntry !== "object") {
			return;
		}
		_contents.push(newEntry);
	} //--end addEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: clone
	 * Returns a copy of this collection
	 * Returns: DocovaCollection object
	 *-------------------------------------------------------------------------------------------------*/
	this.clone = function () {
		var newCollection = new DocovaCollection(this);

		return newCollection;
	} //--end clone


	/*----------------------------------------------------------------------------------------------
	 * Function: contains
	 * Returns true if the collection contains a particular element or set of elements
	 * Inputs: itemstocheck -
	 * Returns: boolean - true if items passed exist in collection, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.contains = function (itemstocheck) {
		var result = false;

		var checkarray = [];
		if (Array.isArray(itemstocheck)) {
			for (var i = 0; i < itemstocheck.length; i++) {
				if (typeof itemstocheck[i] == "object") {
					if (itemstocheck[i].constructor_name == "DocovaDocument") {
						checkarray.push(itemstocheck[i].id);
					}
				} else if (typeof itemstocheck[i] == "string") {
					checkarray.push(itemstocheck[i]);
				}
			}
		}

		if (_contents.length > 0) {
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					if (checkarray.indexOf(_contents[x].id) > -1) {
						result = true;
					} else {
						result = false;
						break;
					}
				}
			}
		}
		return result;
	} //--end contains

	/*----------------------------------------------------------------------------------------------
	 * Function: deleteDocument
	 * Alias for deleteEntry
	 * Inputs: entryobj - entry object to be removed
	 *-------------------------------------------------------------------------------------------------*/
	this.deleteDocument = function (entryItem) {
		this.deleteEntry(entryItem);
	} //--end deleteDocument


	/*----------------------------------------------------------------------------------------------
	 * Function: deleteEntry
	 * Removes an entry from the collection
	 * Inputs: entryobj - entry object to be removed
	 *-------------------------------------------------------------------------------------------------*/
	this.deleteEntry = function (entryItem) {
		if (_contents.length === 0) {
			return;
		}

		//check to make sure the object passed is a match for this collection
		if (entryItem.constructor_name != _type) {
			return;
		}

		for (var x = 0; x < _contents.length; x++) {
			var objtype = _contents[x].constructor_name;
			if (objtype == "DocovaDocument") {
				if (_contents[x].id === entryItem.id) {
					_contents.splice(x, 1);
					break;
				}
			}
		}

	} //--end deleteEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: FTSearch
	 * Performs a search on the items in the collection and reduces the collection to just those results
	 * Inputs: query - string -  query to use to reduce the contents of the collection
	 *         maxEntries - integer - maximum number of entries to return
	 *-------------------------------------------------------------------------------------------------*/
	this.FTSearch = function (query, maxEntries) {
		//TODO - finish this code
	} //--end FTSearch

	/*----------------------------------------------------------------------------------------------
	 * Function: getEntry
	 * Returns an entry from the collection
	 * Inputs: entryItem - entry object to locate
	 *-------------------------------------------------------------------------------------------------*/
	this.getEntry = function (entryItem) {
		if (_contents.length === 0) {
			return null;
		}

		//check to make sure the object passed is a match for this collection
		if (entryItem.constructor_name != _type) {
			return null;
		}

		for (var x = 0; x < _contents.length; x++) {
			var objtype = _contents[x].constructor_name;
			if (objtype == "DocovaDocument") {
				if (_contents[x].id === entryItem.id) {
					return _contents[x];
					break;
				}
			}
		}
		return null;
	} //--end getEntry


	/*----------------------------------------------------------------------------------------------
	 * Function: getFirstEntry
	 * Returns an object for first entry in the collection
	 * Inputs:
	 * Returns: object
	 *-------------------------------------------------------------------------------------------------*/
	this.getFirstEntry = function () {
		if (_contents.length === 0) {
			return null;
		} else {
			return _contents[0];
		}
	} //--end getFirstEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: getFirstDocument
	 * Alias for getFirstEntry
	 *-------------------------------------------------------------------------------------------------*/
	this.getFirstDocument = function () {
		return this.getFirstEntry();
	} //--end getFirstDocument


	/*----------------------------------------------------------------------------------------------
	 * Function: getPrevEntry
	 * Returns an object for previous entry in the collection
	 * Inputs: entryItem - object of existing entry in collection
	 * Returns: object
	 *-------------------------------------------------------------------------------------------------*/
	this.getPrevEntry = function (entryItem) {
		if (_contents.length === 0) {
			return null;
		}

		//check to make sure the object passed is a match for this collection
		if (entryItem.constructor_name != _type) {
			return null;
		}

		for (var x = 0; x < _contents.length; x++) {
			var objtype = _contents[x].constructor_name;
			if (objtype == "DocovaDocument") {
				if (_contents[x].id === entryItem.id) {
					if (x > 0) {
						return _contents[x - 1];
					} else {
						return null;
					}
					break;
				}
			}
		}
		return null;
	} //--end getPrevEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: getPrevDocument
	 * Alias for getPrevEntry
	 *-------------------------------------------------------------------------------------------------*/
	this.getPrevDocument = function (entryItem) {
		return this.getPrevEntry();
	} //--end getPrevDocument


	/*----------------------------------------------------------------------------------------------
	 * Function: getNextEntry
	 * Returns an object for next entry in the collection
	 * Inputs: entryItem - object of existing entry in collection
	 * Returns: object
	 *-------------------------------------------------------------------------------------------------*/
	this.getNextEntry = function (entryItem) {
		if (_contents.length === 0) {
			return null;
		}

		//check to make sure the object passed is a match for this collection
		if (entryItem.constructor_name != _type) {
			return null;
		}

		for (var x = 0; x < _contents.length; x++) {
			var objtype = _contents[x].constructor_name;
			if (objtype == "DocovaDocument") {
				if (_contents[x].id === entryItem.id) {
					if (x < (_contents.length - 1)) {
						return _contents[x + 1];
					} else {
						return null;
					}
					break;
				}
			}
		}
		return null;
	} //--end getNextEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: getNextDocument
	 * Alias for getNextEntry
	 *-------------------------------------------------------------------------------------------------*/
	this.getNextDocument = function (entryItem) {
		return this.getNextEntry(entryItem);
	} //--end getNextDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getNthEntry
	 * Returns an object based on position in the collection
	 * Inputs: position - numeric position of object in the collection
	 * Returns: object
	 *-------------------------------------------------------------------------------------------------*/
	this.getNthEntry = function (position) {
		if (position > 0 && position <= _contents.length) {
			return _contents[position - 1];
		}

		return null;
	} //--end getNthEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: getNthDocument
	 * Alias for getNthEntry
	 *-------------------------------------------------------------------------------------------------*/
	this.getNthDocument = function (position) {
		return this.getNthEntry(position);
	} //--end getNthDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getLastEntry
	 * Returns an object for last entry in the collection
	 * Inputs:
	 * Returns: object
	 *-------------------------------------------------------------------------------------------------*/
	this.getLastEntry = function () {
		if (_contents.length === 0) {
			return null;
		} else {
			return _contents[_contents.length - 1];
		}
	} //--end getLastEntry

	/*----------------------------------------------------------------------------------------------
	 * Function: getLastDocument
	 * Alias for getLastEntry
	 *-------------------------------------------------------------------------------------------------*/
	this.getLastDocument = function () {
		return this.getLastEntry();
	} //--end getLastDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: intersect
	 * Removes entries in the collection that do not match a provided collection or list of elements
	 * Inputs: itemstocheck - items to check against the collection
	 *-------------------------------------------------------------------------------------------------*/
	this.intersect = function (itemstocheck) {

		var checkarray = [];
		if (Array.isArray(itemstocheck)) {
			for (var i = 0; i < itemstocheck.length; i++) {
				if (typeof itemstocheck[i] == "object") {
					if (itemstocheck[i].constructor_name == "DocovaDocument") {
						checkarray.push(itemstocheck[i].id);
					}
				} else if (typeof itemstocheck[i] == "string") {
					checkarray.push(itemstocheck[i]);
				}
			}
		}

		if (_contents.length > 0) {
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					if (checkarray.indexOf(_contents[x].id) === -1) {
						_contents.splice(x, 1);
					}
				}
			}
		}
	} //--end intersect

	/*----------------------------------------------------------------------------------------------
	 * Function: markAllRead
	 * Marks selected documents as read, if DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.markAllRead = function () {
		if (_contents.length === 0) {
			return null;
		} else {
			//TODO - add code
		}
	} //--end markAllRead

	/*----------------------------------------------------------------------------------------------
	 * Function: markAllUnread
	 * Marks selected documents as unread, if DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.markAllUnread = function () {
		if (_contents.length === 0) {
			return null;
		} else {
			//TODO - add code
		}
	} //--end markAllUnread

	/*----------------------------------------------------------------------------------------------
	 * Function: merge
	 * Adds a collection to the current one
	 * Inputs: itemsToAdd -
	 *-------------------------------------------------------------------------------------------------*/
	this.merge = function (itemsToAdd) {

		if (itemsToAdd && Array.isArray(itemsToAdd) && itemsToAdd.length > 0) {
			for (var x = 0; x < itemsToAdd.length; x++) {
				var existingObj = this.getEntry(itemsToAdd[x]);
				if (!existingObj) {
					this.addEntry(itemsToAdd[x]);
				}
			}
		}
	} //--end merge

	/*----------------------------------------------------------------------------------------------
	 * Function: putAllInFolder
	 * Inputs: foldername - string - name of folder
	 * Moves documents in collection to a specified folder
	 *-------------------------------------------------------------------------------------------------*/
	this.putAllInFolder = function (foldername) {
		var result = false;

		if (_contents.length === 0) {
			return result;
		}
		if (!foldername || foldername === "") {
			return result;
		}

		var request = "<Request>";
		request += "<Action>GETVIEWINFO</Action>";
		request += "<ViewName><![CDATA[" + foldername + "]]></ViewName>";
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<CreatePrivate></CreatePrivate>";
		request += "</Request>";

		var folderexists = false;
		var folderisprivate = false;
		var xmldata = _requestHelper(request, "ViewServices");
		if (xmldata) {
			var tempresult = _parserHelper(this, xmldata, "xmlnode", "IsFolder");
			if (tempresult) {
				if (tempresult.toUpperCase() == "TRUE" || tempresult.toUpperCase() == "YES" || tempresult == "1") {
					folderexists = true;
				}
			}
			var tempresult = _parserHelper(this, xmldata, "xmlnode", "IsPrivate");
			if (tempresult.toUpperCase() == "TRUE" || tempresult.toUpperCase() == "YES" || tempresult == "1") {
				folderisprivate = true;
			}
		}

		if (folderexists) {
			var temparray = [];
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					temparray.push(_contents[x].unid);
				}
			}

			var request = "<Request>";
			request += "<Action>PUTINFOLDER</Action>";
			request += "<FolderName><![CDATA[" + foldername + (folderisprivate ? ("_" + docInfo.UserNameAB) : "") + "]]></FolderName>";
			request += "<Unids>" + temparray.join(":") + "</Unids>";
			request += "</Request>";

			var xmldata = _requestHelper(request);
			if (xmldata) {
				result = _parserHelper(this, xmldata);
			}
		}

		return result;
	} //--end putAllInFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: removeAll
	 * Deletes all documents in the collection
	 *-------------------------------------------------------------------------------------------------*/
	this.removeAll = function () {
		if (_contents.length === 0) {
			return null;
		} else {
			//TODO - add code
		}
	} //--end removeAll

	/*----------------------------------------------------------------------------------------------
	 * Function: removeAllFromFolder
	 * Inputs: foldername - string - name of folder
	 * Removes documents in collection from a specified folder
	 *-------------------------------------------------------------------------------------------------*/
	this.removeAllFromFolder = function (foldername) {
		var result = false;

		if (_contents.length === 0) {
			return result;
		}
		if (!foldername || foldername === "") {
			return result;
		}

		var request = "<Request>";
		request += "<Action>GETVIEWINFO</Action>";
		request += "<ViewName><![CDATA[" + foldername + "]]></ViewName>";
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<CreatePrivate></CreatePrivate>";
		request += "</Request>";

		var folderexists = false;
		var folderisprivate = false;
		var xmldata = _requestHelper(request, "ViewServices");
		if (xmldata) {
			var tempresult = _parserHelper(this, xmldata, "xmlnode", "IsFolder");
			if (tempresult) {
				if (tempresult.toUpperCase() == "TRUE" || tempresult.toUpperCase() == "YES" || tempresult == "1") {
					folderexists = true;
				}
			}
			var tempresult = _parserHelper(this, xmldata, "xmlnode", "IsPrivate");
			if (tempresult.toUpperCase() == "TRUE" || tempresult.toUpperCase() == "YES" || tempresult == "1") {
				folderisprivate = true;
			}
		}

		if (folderexists) {
			var temparray = [];
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					temparray.push(_contents[x].unid);
				}
			}

			var request = "<Request>";
			request += "<Action>REMOVEFROMFOLDER</Action>";
			request += "<FolderName><![CDATA[" + foldername + (folderisprivate ? ("_" + docInfo.UserNameAB) : "") + "]]></FolderName>";
			request += "<Unids>" + temparray.join(":") + "</Unids>";
			request += "</Request>";

			var xmldata = _requestHelper(request);
			if (xmldata) {
				result = _parserHelper(this, xmldata);
			}
		} else {
			result = true;
		}

		return result;
	} //--end removeAllFromFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: stampAll
	 * Updates all documents in collection with a field value
	 * Inputs: fieldname - string - name of field to update on docs
	 *         fieldvalue - various - value to assign to the field
	 * Returns: boolean - true or false
	 *-------------------------------------------------------------------------------------------------*/
	this.stampAll = function (fieldname, fieldvalue) {
		var result = false;
		if (_contents.length > 0 && (typeof fieldname !== 'undefined') && (fieldname !== "") && (typeof fieldvalue !== 'undefined')) {
			var tempdoc = new DocovaDocument(this);
			var tempfield = new DocovaField(tempdoc, fieldname, fieldvalue, "");

			var temparray = [];
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					temparray.push(_contents[x].unid);
				}
			}

			var request = "<Request>";
			request += "<Action>SETDOCFIELDS</Action>";
			request += "<AppID>" + _parentApp.appID + "</AppID>";
			request += "<Unid>" + temparray.join(":") + "</Unid>";
			request += "<Fields>";
			request += tempfield.toXML;
			request += "</Fields>";
			request += "</Request>";

			tempfield = null;
			tempdoc = null;

			var xmldata = _requestHelper(request);
			if (xmldata) {
				result = _parserHelper(this, xmldata);
			}
		}
		return result;
	} //--end stampAll

	/*----------------------------------------------------------------------------------------------
	 * Function: stampAllMulti
	 * Updates all documents in collection with multiple field values
	 * Inputs: sourcedoc - DocovaDocument - source document containing field values
	 * Returns: boolean - true or false
	 *-------------------------------------------------------------------------------------------------*/
	this.stampAllMulti = function (sourcedoc) {
		var result = false;
		if (_contents.length > 0 && (typeof sourcedoc !== 'undefined')) {
			var temparray = [];
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					temparray.push(_contents[x].unid);
				}
			}

			var request = "<Request>";
			request += "<Action>SETDOCFIELDS</Action>";
			request += "<AppID>" + _parentApp.appID + "</AppID>";
			request += "<Unid>" + temparray.join(":") + "</Unid>";
			request += "<Fields>";
			var tempfb = sourcedoc.fieldBuffer;
			for (var itemkey in tempfb) {
				var item = tempfb[itemkey];
				request += item.toXML;
			}
			request += "</Fields>";
			request += "</Request>";
			tempfb = null;

			var xmldata = _requestHelper(request);
			if (xmldata) {
				result = _parserHelper(this, xmldata);
			}
		}
		return result;
	} //--end stampAllMulti

	/*----------------------------------------------------------------------------------------------
	 * Function: subtract
	 * Removes entries in the collection that match a provided collection or list of elements
	 * Inputs: itemstocheck - items to check against the collection
	 *-------------------------------------------------------------------------------------------------*/
	this.subtract = function (itemstocheck) {

		var checkarray = [];
		if (Array.isArray(itemstocheck)) {
			for (var i = 0; i < itemstocheck.length; i++) {
				if (typeof itemstocheck[i] == "object") {
					if (itemstocheck[i].constructor_name == "DocovaDocument") {
						checkarray.push(itemstocheck[i].id);
					}
				} else if (typeof itemstocheck[i] == "string") {
					checkarray.push(itemstocheck[i]);
				}
			}
		}

		if (_contents.length > 0) {
			for (var x = 0; x < _contents.length; x++) {
				var objtype = _contents[x].constructor_name;
				if (objtype == "DocovaDocument") {
					if (checkarray.indexOf(_contents[x].id) > -1) {
						_contents.splice(x, 1);
					}
				}
			}

		}
	} //--end subtract


	/*----------------------------------------------------------------------------------------------
	 * Function: _requestHelper
	 * Internal function to help with making ajax requests for document data xml
	 * Inputs: request - string - xml string containing request to services agent
	 *         service - string (optional) - name of service to call - defaults to DocumentServices
	 * Returns: xml string - containing results of request
	 *-------------------------------------------------------------------------------------------------*/
	function _requestHelper(request, service) {
		var xmldata = null;
		if (_DOCOVAEdition == "SE") {
			var serviceurl = "/" + docInfo.NsfName + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent";
		} else {
			var serviceurl = _parentApp.filePath + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent";
		}

		jQuery.ajax({
			type: "POST",
			url: serviceurl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				xmldata = xml;
			},
			error: function () {}
		});

		return xmldata;
	} //--end _requestHelper

	/*----------------------------------------------------------------------------------------------
	 * Function: _parserHelper
	 * Internal function to help with parsing document data out of xml response
	 * Inputs: objself - DocovaView object - current docova view object
	 *         xmldata - xml string - xml string containing document result data
	 *         parsetype - string (optional) - one of the following;
	 *                   text - returns the xml result text
	 *                   xmlnode - returns the xml result node value
	 *                   document - returns a single document object
	 *                   array - returns a document object array
	 *         xmlnodename - string (optional) - if parsetype is xmlnode - this specifies the name of the node
	 * Returns: array of DocovaDocument objects, or a single DocovaDocument object, or null
	 *-------------------------------------------------------------------------------------------------*/
	function _parserHelper(objself, xmldata, parsetype, xmlnodename) {
		var result = null;

		try {
			var xmlobj = $(xmldata);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				if (parsetype == "array" || parsetype == "document") {
					var returnarray = (parsetype == "array");
					if (returnarray) {
						result = new Array();
					}
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
					jQuery(resultxmlobj).find("Document" + (returnarray == true ? "" : ":first")).each(function () {
						var unid = jQuery(this).find("Unid").text();
						var docid = jQuery(this).find("ID").text();
						if (unid && unid != "") {
							docobj = new DocovaDocument(objself, unid, docid);
							if (docobj) {
								if (returnarray == true) {
									result.push(docobj);
								} else {
									result = docobj;
								}
							}
						}
					});
				} else if (parsetype == "xmlnode") {
					if (xmlnodename && xmlnodename !== "") {
						result = jQuery(xmlobj.find("Result[ID=Ret1]")).find(xmlnodename).text();
					}
				} else if (parsetype == "text") {
					result = xmlobj.find("Result[ID=Ret1]").text();
				} else {
					result = true;
				}
			}
		} catch (err) {
			return null;
		}

		return result;
	} //--end _parserHelper

	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new collection
	 *-------------------------------------------------------------------------------------------------*/
	if (parentobj) {
		_parent = parentobj;
		if (parentobj.constructor_name == "DocovaApplication") {
			_parentApp = parentobj;
		} else if (parentobj.constructor_name == "DocovaView") {
			_parentApp = parentobj.parentApp;
		} else if (parentobj.constructor_name == "DocovaUIView") {
			var app = new DocovaApplication({
					"appid": parentobj.appID
				});
			_parentApp = app;
		}
	}
	//--end Constructor
} //--end DocovaCollection class


/*
 * Class: DocovaView
 * Back end class and methods for interaction with DOCOVA views
 * Typically accessed from DocovaApplication.getView()
 * Parameters:
 *      appobj: DocovaApplication object
 *      viewname: string - name or alias of view
 * Properties:
 *      viewName: string - title of view
 *      viewAlias: string - alias of view
 *      parentApp: DocovaApplication - application/library object where view resides
 *      isFolder: boolean - true if folder, false if view
 *      isPrivate: boolean - true if folder is private
 **/
function DocovaView(appobj, viewname) {
	var _viewName = "";
	var _viewAlias = "";
	var _parentApp = null;
	var _isFolder = false;
	var _isPrivate = false;
	var _columns = [];

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaView";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'allEntries', {
		get: function () {
			return this.getAllDocuments();
		},
		enumerable: true
	});
	Object.defineProperty(this, 'columns', {
		get: function () {
			if (_columns.length == 0) {
				_getViewColumns();
			}
			return _columns;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isFolder', {
		get: function () {
			return _isFolder;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isPrivate', {
		get: function () {
			return _isPrivate;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'viewName', {
		get: function () {
			return _viewName;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'viewAlias', {
		get: function () {
			return _viewAlias;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentApp', {
		get: function () {
			return _parentApp;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Methods:
	 *-------------------------------------------------------------------------------------------------*/

	/*----------------------------------------------------------------------------------------------
	 * Function: createViewNav
	 * Returns a view navigator object for the current view
	 * Inputs:
	 * Returns: DocovaViewNavigator object
	 *-------------------------------------------------------------------------------------------------*/
	this.createViewNav = function () {
		return new DocovaViewNavigator(this);
	} //--end createViewNav

	/*----------------------------------------------------------------------------------------------
	 * Function: createViewNavFromCategory
	 * Returns a view navigator object for a given category in the current view
	 * Inputs: category - string - category to limit the results to
	 * Returns: DocovaViewNavigator object
	 *-------------------------------------------------------------------------------------------------*/
	this.createViewNavFromCategory = function (category) {
		if (typeof category == 'undefined' || category === null) {
			return null;
		}
		return new DocovaViewNavigator(this, {
			'category': category
		});
	} //--end createViewNavFromCategory

	/*----------------------------------------------------------------------------------------------
	 * Function: getAllDocuments
	 * Returns an array of Docova Document objects from the view
	 * Inputs:
	 * Returns: DocovaCollection of DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.getAllDocuments = function () {
		var docobjarray = new DocovaCollection(this);

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobjarray = _parserHelper(this, xmldata, true);
		}

		return docobjarray;
	} //--end getAllDocuments

	/*----------------------------------------------------------------------------------------------
	 * Function: getAllDocumentsByKey
	 * Returns an array of Docova Document objects based on a matching key
	 * Inputs: key - string or string array - lookup key(s) to use to look up matching entries
	 *         exactmatch - boolean - true to exactly match key. defaults to true
	 * Returns: DocumentCollection of DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.getAllDocumentsByKey = function (key, exactmatch) {
		var docobjarray = new DocovaCollection(this);

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<Key><![CDATA[" + key + "]]></Key>";
		request += "<ExactMatch>" + (exactmatch == true ? "1" : "0") + "</ExactMatch>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobjarray = _parserHelper(this, xmldata, true);
		}

		return docobjarray;
	} //--end getAllDocumentsByKey

	/*----------------------------------------------------------------------------------------------
	 * Function: getDocument
	 * Returns a Docova Document object based on a document id
	 * Inputs: docid - string - universalid or dockey of a document in the view
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getDocument = function (docid) {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<DocUnid>" + docid + "</DocUnid>";
		request += "<Count>1</Count>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getDocumentByKey
	 * Returns a Docova Document object based on a matching key
	 * Inputs: key - string or string array - lookup key(s) to use to look up matching entry
	 *         exactmatch - boolean - true to exactly match key. defaults to true
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getDocumentByKey = function (key, exactmatch) {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<Key><![CDATA[" + key + "]]></Key>";
		request += "<ExactMatch>" + (exactmatch == true ? "1" : "0") + "</ExactMatch>";
		request += "<Count>1</Count>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getDocumentByKey

	/*----------------------------------------------------------------------------------------------
	 * Function: getFirstDocument
	 * Returns a Docova Document object for first entry in the view
	 * Inputs:
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getFirstDocument = function () {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<DocUnid>FIRST</DocUnid>";
		request += "<Count>1</Count>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getFirstDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getPrevDocument
	 * Returns a Docova Document object for previous entry in the view
	 * Inputs: docovadoc - DocovaDocument object of existing entry in view
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getPrevDocument = function (docovadoc) {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<DocUnid>" + docovadoc.unid + "</DocUnid>";
		request += "<Skip>-1</Skip>"
		request += "<Count>1</Count>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getPrevDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getNextDocument
	 * Returns a Docova Document object for next entry in the view
	 * Inputs: docovadoc - DocovaDocument object of existing entry in view
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getNextDocument = function (docovadoc) {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<DocUnid>" + docovadoc.unid + "</DocUnid>";
		request += "<Skip>1</Skip>";
		request += "<Count>1</Count>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getNextDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getNthDocument
	 * Returns a Docova Document object based on position in the view
	 * Inputs: position - numeric position of document in the view
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getNthDocument = function (position) {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<Skip>" + (position - 1).toString() + "</Skip>";
		request += "<Count>1</Count>";
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getNthDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getLastDocument
	 * Returns a Docova Document object for last entry in the view
	 * Inputs:
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getLastDocument = function () {
		var docobj = null;

		request = "<Request>";
		request += "<Action>GETDOCUMENTS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "<DocUnid>LAST</DocUnid>";
		request += "<Count>1</Count>"
		request += "</Request>";

		var xmldata = _requestHelper(request);
		if (xmldata) {
			docobj = _parserHelper(this, xmldata, false);
		}

		return docobj;
	} //--end getLastDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: refresh
	 * Refreshes the view index to ensure any recent changes are represented
	 *-------------------------------------------------------------------------------------------------*/
	this.refresh = function () {
		if(_DOCOVAEdition == "SE"){
		//JAVAD >> In SE back-end refresh of a view neither have any context nor makes sense, so we do retun void
		return;
		}
		request = "<Request>";
		request += "<Action>REFRESHVIEW</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "</Request>";
		var xmldata = _requestHelper(request);
	} //--end refresh

	/*----------------------------------------------------------------------------------------------
	 * Function: search
	 * Return a collection of docova document objects
	 * Inputs: options - value pair object consisting of the following;
	 *              criteria: string - search formula or full text search query to use
	 *              ftsearch: boolean - whether to perform a full text search or not (defaults to false)
	 * Returns: array of DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.search = function (options) {
		//TODO - add code
	}
	//--end search


	/*----------------------------------------------------------------------------------------------
	 * Function: _getViewColumns
	 * Internal function to retrieve view column properties array
	 */
	function _getViewColumns() {
		var tempself = this;
		var tempcol = [];

		var request = "<Request>";
		request += "<Action>GETVIEWCOLUMNS</Action>";
		request += "<AppID>" + _parentApp.appID + "</AppID>";
		request += "<ViewName><![CDATA[" + _viewName + (_isPrivate ? ("_" + docInfo.UserNameAB) : "") + "]]></ViewName>";
		if (_parentApp.isNativeDb) {
			request += "<FilePath><![CDATA[" + _parentApp.filePath + "]]></FilePath>";
		}
		request += "</Request>";
		var xmldata = _requestHelper(request, (_DOCOVAEdition == "SE" ? "ApplicationServices" : ""));
		if (xmldata) {
			var xmlobj = $(xmldata);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
				jQuery(resultxmlobj).find("Column").each(function () {
					var title =

						tempcol.push({
							'Alignment': jQuery(this).find("Alignment").text(),
							'DateFmt': jQuery(this).find("DateFmt").text(),
							'FontColor': jQuery(this).find("FontColor").text(),
							'FontFace': jQuery(this).find("FontFace").text(),
							'FontPointSize': jQuery(this).find("FontPointSize").text(),
							'FontStyle': jQuery(this).find("FontStyle").text(),
							'Formula': jQuery(this).find("Formula").text(),
							'HeaderAlignment': jQuery(this).find("HeaderAlignment").text(),
							'HeaderFontColor': jQuery(this).find("HeaderFontColor").text(),
							'HeaderFontFace': jQuery(this).find("HeaderFontFace").text(),
							'HeaderFontPointSize': jQuery(this).find("HeaderFontPointSize").text(),
							'HeaderFontStyle': jQuery(this).find("HeaderFontStyle").text(),
							'IsAccentSensitiveSort': jQuery(this).find("IsAccentSensitiveSort").text(),
							'IsCaseSensitiveSort': jQuery(this).find("IsCaseSensitiveSort").text(),
							'IsCategory': jQuery(this).find("IsCategory").text(),
							'IsField': jQuery(this).find("IsField").text(),
							'IsFontBold': jQuery(this).find("IsFontBold").text(),
							'IsFontItalic': jQuery(this).find("IsFontItalic").text(),
							'IsFontStrikethrough': jQuery(this).find("IsFontStrikethrough").text(),
							'IsFontUnderline': jQuery(this).find("IsFontUnderline").text(),
							'IsFormula': jQuery(this).find("IsFormula").text(),
							'IsHeaderFontBold': jQuery(this).find("IsHeaderFontBold").text(),
							'IsHeaderFontItalic': jQuery(this).find("IsHeaderFontItalic").text(),
							'IsHeaderFontStrikethrough': jQuery(this).find("IsHeaderFontStrikethrough").text(),
							'IsHeaderFontUnderline': jQuery(this).find("IsHeaderFontUnderline").text(),
							'IsHidden': jQuery(this).find("IsHidden").text(),
							'IsHideDetail': jQuery(this).find("IsHideDetail").text(),
							'IsIcon': jQuery(this).find("IsIcon").text(),
							'IsNumberAttribParens': jQuery(this).find("IsNumberAttribParens").text(),
							'IsNumberAttribPercent': jQuery(this).find("IsNumberAttribPercent").text(),
							'IsNumberAttribPunctuated': jQuery(this).find("IsNumberAttribPunctuated").text(),
							'IsResize': jQuery(this).find("IsResize").text(),
							'IsResortAscending': jQuery(this).find("IsResortAscending").text(),
							'IsResortDescending': jQuery(this).find("IsResortDescending").text(),
							'IsResortToView': jQuery(this).find("IsResortToView").text(),
							'IsResponse': jQuery(this).find("IsResponse").text(),
							'IsSecondaryResort': jQuery(this).find("IsSecondaryResort").text(),
							'IsSecondaryResortDescending': jQuery(this).find("IsSecondaryResortDescending").text(),
							'IsShowTwistie': jQuery(this).find("IsShowTwistie").text(),
							'IsSortDescending': jQuery(this).find("IsSortDescending").text(),
							'IsSorted': jQuery(this).find("IsSorted").text(),
							'ItemName': jQuery(this).find("ItemName").text(),
							'ListSep': jQuery(this).find("ListSep").text(),
							'NumberAttrib': jQuery(this).find("NumberAttrib").text(),
							'NumberDigits': jQuery(this).find("NumberDigits").text(),
							'NumberFormat': jQuery(this).find("NumberFormat").text(),
							'Parent': tempself,
							'Position': jQuery(this).find("Position").text(),
							'ResortToViewName': jQuery(this).find("ResortToViewName").text(),
							'SecondaryResortColumnIndex': jQuery(this).find("SecondaryResortColumnIndex").text(),
							'TimeDateFmt': jQuery(this).find("TimeDateFmt").text(),
							'TimeFmt': jQuery(this).find("TimeFmt").text(),
							'TimeZoneFmt': jQuery(this).find("TimeZoneFmt").text(),
							'Title': jQuery(this).find("Title").text(),
							'Width': jQuery(this).find("Width").text()
						});
				});
			}
		}

		_columns = tempcol.slice();
	} //--end _getViewColumns

	/*----------------------------------------------------------------------------------------------
	 * Function: _requestHelper
	 * Internal function to help with making ajax requests for view data xml
	 * Inputs: request - string - xml string containing request to services agent
	 *         service - string (optional) - name of service to call - defaults to ViewServices
	 * Returns: xml string - containing results of request
	 *-------------------------------------------------------------------------------------------------*/
	function _requestHelper(request, service) {
		var xmldata = null;
		var serviceurl = (_DOCOVAEdition == "SE" ? docInfo.PortalWebPath : _parentApp.filePath);

			serviceurl += (serviceurl.substr(-1) == "/" ? "" : "/") + (!service ? "ViewServices" : service) + "?OpenAgent";

		if (!_parentApp.isNativeDb) {
			serviceurl += "&AppID=" + _parentApp.appID;
		}

		jQuery.ajax({
			type: "POST",
			url: serviceurl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				xmldata = xml;
			},
			error: function () {}
		});

		return xmldata;
	} //--end _requestHelper

	/*----------------------------------------------------------------------------------------------
	 * Function: _parserHelper
	 * Internal function to help with parsing document data out of xml response
	 * Inputs: objself - DocovaView object - current docova view object
	 *         xmldata - xml string - xml string containing document result data
	 *         returnarray - boolean - true to return a collection of results
	 *                       if false, return a single document object
	 * Returns: DocovaCollection of DocovaDocument objects, or a single DocovaDocument object, or null
	 *-------------------------------------------------------------------------------------------------*/
	function _parserHelper(objself, xmldata, returnarray) {
		var doccol = new DocovaCollection(objself);
		var docobj = null;

		try {
			var xmlobj = $(xmldata);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
				jQuery(resultxmlobj).find("Document" + (returnarray == true ? "" : ":first")).each(function () {
					var unid = jQuery(this).find("Unid").text();
					var docid = jQuery(this).find("ID").text();
					if (unid && unid != "") {
						docobj = new DocovaDocument(objself, unid, docid);
						if (docobj) {
							if (returnarray == true) {
								doccol.addEntry(docobj);
							} else {
								return false;
							}
						}
					}
				});
			}
		} catch (err) {
			return null;
		}

		if (returnarray == true) {
			return doccol;
		} else {
			return docobj;
		}
	} //--end _parserHelper

	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new view
	 *-------------------------------------------------------------------------------------------------*/
	if (appobj == undefined || appobj == null || viewname == undefined || viewname == "") {
		return null;
	}

	_parentApp = appobj;

	var serviceurl = '/' + docInfo.NsfName;
	serviceurl += (serviceurl.substr(-1) == "/" ? "" : "/") + "ViewServices?OpenAgent";
	
	request = "<Request>";
	request += "<Action>GETVIEWINFO</Action>";
	request += "<AppID>" + _parentApp.appID + "</AppID>";
	request += "<ViewName><![CDATA[" + viewname + "]]></ViewName>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<CreatePrivate>1</CreatePrivate>";
	if (_DOCOVAEdition == "Domino") {
		if (appobj.isNativeDb) {
			var uiw = Docova.getUIWorkspace(document);
			var tempappobj = uiw.getCurrentApplication();
			
			serviceurl = tempappobj.filePath;
			serviceurl += (serviceurl.substr(-1) == "/" ? "" : "/") + "ViewServices?OpenAgent";
			
			request += "<FilePath><![CDATA[" + appobj.filePath + "]]></FilePath>";
		} else {
			serviceurl = appobj.filePath;
			serviceurl += (serviceurl.substr(-1) == "/" ? "" : "/") + "ViewServices?OpenAgent";
		}
	}
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: serviceurl,
		data: encodeURI(request),
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			try {
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");

					var tempviewname = jQuery(resultxmlobj).find("ViewName").text();
					var tempviewalias = jQuery(resultxmlobj).find("ViewAlias").text();
					var isfolder = jQuery(resultxmlobj).find("IsFolder").text();
					var isprivate = jQuery(resultxmlobj).find("IsPrivate").text();

					if (tempviewname != "") {
						_viewName = tempviewname;
						_viewAlias = tempviewalias;
						_isFolder = (isfolder && isfolder === "1" ? true : false);
						_isPrivate = (isprivate && isprivate === "1" ? true : false);
						_parentApp = appobj;
					} else {
						return null;
					}
				}
			} catch (err) {
				return null;
			}
		},
		error: function () {
			return null;
		}
	}); //--end Constructor

} //end DocovaView class


/*
 * Class: DocovaViewNavigator
 * Back end class and methods for navigating DOCOVA view contents
 * Typically accessed from DocovaView.createViewNav() or DocovaView.createViewNavFromCategory()
 **/
function DocovaViewNavigator(parentview, params) {
	var _parentView = null;
	var _docColl = null;
	var _category = "";

	/*----------------------------------------------------------------------------------------------
	 * Properties:
	 *-------------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaViewNavigator";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'count', {
		get: function () {
			var result = (_docColl ? _docColl.count : 0);
			return result;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'parentView', {
		get: function () {
			return _parentView;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Methods:
	 *-------------------------------------------------------------------------------------------------*/

	/*----------------------------------------------------------------------------------------------
	 * Function: getFirstDocument
	 * Returns a Docova Document object for first entry in the navigator collection
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getFirstDocument = function () {
		return (_docColl && _docColl.count > 0 ? _docColl.getFirstDocument() : null);
	} //--end getFirstDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getPrev
	 * Returns a Docova Document object for previous entry in the navigator collection
	 * Inputs: docovadoc - DocovaDocument object of existing entry in collection
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getPrev = function (docovadoc) {
		return this.getPrevDocument(docovadoc);
	} //--end getPrev

	/*----------------------------------------------------------------------------------------------
	 * Function: getPrevDocument
	 * Returns a Docova Document object for previous entry in the navigator collection
	 * Inputs: docovadoc - DocovaDocument object of existing entry in collection
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getPrevDocument = function (docovadoc) {
		return (_docColl && _docColl.count > 0 ? _docColl.getPrevDocument(docovadoc) : null);
	} //--end getPrevDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getNext
	 * Returns a Docova Document object for next entry in the navigator collection
	 * Inputs: docovadoc - DocovaDocument object of existing entry in collection
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getNext = function (docovadoc) {
		return this.getNextDocument(docovadoc);
	} //--end getNext


	/*----------------------------------------------------------------------------------------------
	 * Function: getNextDocument
	 * Returns a Docova Document object for next entry in the navigator collection
	 * Inputs: docovadoc - DocovaDocument object of existing entry in collection
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getNextDocument = function (docovadoc) {
		return (_docColl && _docColl.count > 0 ? _docColl.getNextDocument(docovadoc) : null);
	} //--end getNextDocument


	/*----------------------------------------------------------------------------------------------
	 * Function: getLastDocument
	 * Returns a Docova Document object for last entry in the navigator collection
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getLastDocument = function () {
		return (_docColl && _docColl.count > 0 ? _docColl.getLastDocument() : null);
	} //--end getLastDocument


	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new view navigator
	 *-------------------------------------------------------------------------------------------------*/
	if (typeof parentview == 'undefined' || parentview === null) {
		return null;
	}

	_parentView = parentview;
	if (params && params.category && params.category !== "") {
		_category = params.category;
		_docColl = _parentView.getAllDocumentsByKey(params.category, true);
	} else {
		_docColl = _parentView.getAllDocuments();
	}
	//--end Constructor

} //end DocovaViewNavigator class


/*
 * Class: DocovaApplication
 * Back end class and methods for interaction with DOCOVA applications/libraries
 * Typically accessed from DocovaUIWorkspace.getCurrentApplication()
 * Parameters:
 *    options: (optional) value pair object consisting of
 *        appid: string - id/key of application/library
 *        appname: string - name/title of application
 *        filepath: string - path to application
 * Properties:
 *     appID: string - id/key of application/library
 *     appName: string - title of current library/application
 *     filePath: string - full path of current library/application
 **/
function DocovaApplication(options) {
	var defaultOptns = {
		appid: "",
		appname: "",
		filepath: ""
	};
	if (typeof options == "string") {
		options = {
			appid: options
		};
	}
	var opts = $.extend({}, defaultOptns, options);
	var _appID = "";
	var _appName = "";
	var _filePath = "";
	var _isApp = false;
	var _isNativeDb = false;
	var _appIcon = "";
	var _appIconColor = "";
	var _appDescription = "";
	var _appType = "";
	var _libraryList = ""; //for Library Group application
	var _currentUserAccessLevel = "";
	var _self = this;

	//Properties:
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaApplication";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'appID', {
		get: function () {
			return _appID;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'appType', {
		get: function () {
			return _appType;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'appName', {
		get: function () {
			return _appName;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'filePath', {
		get: function () {
			return _filePath;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'appIcon', {
		get: function () {
			return _appIcon;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'appIconColor', {
		get: function () {
			return _appIconColor;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'appDescription', {
		get: function () {
			return _appDescription;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isApp', {
		get: function () {
			return _isApp;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isNativeDb', {
		get: function () {
			return _isNativeDb;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'libraryList', {
		get: function () {
			return _libraryList;
		},
		set: function (newval) {
			_libraryList = newval;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'managers', {
		get: function () {
			//TODO - retrieve the list of managers from the app ACL
			return [""];
		},
		enumerable: true
	});	
	Object.defineProperty(this, 'server', {
		get: function () {
			return "";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'currentUserAccessLevel', {
		get: function () {
			return _currentUserAccessLevel;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'unprocessedDocuments', {
		get: function () {
			var result = new DocovaCollection(_self);
			var uiview = Docova.getUIView();
			if (uiview) {
				result = uiview.getSelectedDocuments();
			}
			return result;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Methods:
	 *-------------------------------------------------------------------------------------------------*/
	/*----------------------------------------------------------------------------------------------
	 * Function: launchApplication
	 * Launches the application in Docova
	 *-------------------------------------------------------------------------------------------------*/
	this.launchApplication = function () {
		if (this.appID == ""){
			return;
		}
		var AppWebPath = "";
		//-----Special case for DOCOVA System Apps, if app is the Libraries Repository then open Libraries tab
		if (this.appID == "Dashboard" || this.appID == "Libraries" || this.appID == "Search" || this.appID == "Designer" || this.appID == "Admin" || this.appID == "AppBuilder") {
			var currAppTitle = this.appName; //re-get app title in case it had changed
			AppWebPath = ""; //Path is blank for System Apps, resolved in AddAppTab function
			var findAppFrame = $("#iFrameMain", window.top.document).contents().find("#fra" + this.appID)
				if (findAppFrame.length == 0) {
					Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').AddAppTab(this.appID, this.appName, AppWebPath);
					Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame(this.appID)
					return;
				} else {
					Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame(this.appID)
					return;
				}
		}

		if (this.isApp == "1") {
			//Check User access to the application
			var resulttext = CheckUserAppAccess(this.appID);
			if (resulttext == "0") {
				window.top.Docova.Utils.messageBox({
					'title': "Insufficient Access",
					'prompt': "Sorry, you have insufficient access to open this application.",
					'icontype': 1,
					'msgboxtype': 0,
					'width': 300
				});
				return;
			}
			if (_DOCOVAEdition == "SE") {
				AppWebPath = "/" + docInfo.NsfName + "/AppLoader/" + this.appID + "?ReadForm";
			} else {
				AppWebPath = this.filePath + "/AppLoader?ReadForm";
			}
		} else {
			//-----If this is a Library Group, we need to get the current libraries in the group
			if (this.appType == "LG") {
				if (this.libraryList == "") { //if the library list for this library group is empty, prevent opening.
					window.top.Docova.Utils.messageBox({
						'title': "No Libraries in this Library Group",
						'prompt': "Please assign Libraries to this Library Group before opening it.",
						'icontype': 1,
						'msgboxtype': 0,
						'width': 450
					});
					return;
				};
				AppWebPath = "/" + docInfo.NsfName + "/wLibrariesFrame?ReadForm&LibList=" + this.libraryList;
			} else {
				AppWebPath = "/" + docInfo.NsfName + "/wLibrariesFrame?ReadForm&LibList=" + this.appID;
			}
		}

		var fraKey = "";
		fraKey = this.appID;
		var findAppFrame = $("#iFrameMain", window.top.document).contents().find("#fra" + fraKey)
			if (findAppFrame.length == 0) {
				Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').AddAppTab(fraKey, this.appName, AppWebPath);
				Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame(fraKey);
			} else {
				Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame(fraKey);
			}

	}

	/*--------------------------------------
	 * Function: openDialog
	 * Helper function to create a dialog with a form/document in it
	---------------------------------------*/
	this.openDialog = function (options) {
		var defaultOptns = {
			formname: "",
			docid: "",
			title: "",
			height: 500,
			width: 400,
			buttons: [],
			resizable: true,
			inherit: false,
			nsfpath: "",
			isresponse: false,
			onClose: function () {}
		};

		var opts = $.extend({}, defaultOptns, options);

		if (opts.nsfpath == "") {
			if (_DOCOVAEdition == "SE") {
				var serviceurl = "";
				if (docInfo && docInfo.PortalWebPath && docInfo.PortalWebPath != "") {
					serviceurl = docInfo.PortalWebPath;
				} else if (PortalWebPath && PortalWebPath != "") {
					serviceurl = PortalWebPath;
				}
				opts.nsfpath = serviceurl + '/wReadDocument/' + opts.docid + '?OpenDocument&ParentUNID=' + this.appID;
			} else {
				opts.nsfpath = this.filePath;
			}
		}
		return Docova.Utils.openDialog(opts);
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: getAgent
	 * Create a new back end docovaAgent object
	 * Inputs:  string - name of agent.
	 * Returns: DocovaAgent object
	 *-------------------------------------------------------------------------------------------------*/
	this.getAgent = function (name) {
		return new DocovaAgent(this, name);
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: createDocument
	 * Create a new back end document object
	 * Inputs: options - (optional) - value pair array
	 *            formname: (optional) - string - name of form. Only applicable for applications
	 *            doctypeid: (optional) - string - id of document type. Only applicable for libraries
	 *            folderid: (optional) - string - id of parent folder. Only applicable for libraries
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.createDocument = function (options) {
		var newdoc = null;

		newdoc = new DocovaDocument(this);
		if (options) {
			if (this.isApp) {
				if (options.formname) {
					newdoc.setField("Form", options.formname);
				}
			} else {
				if (options.doctypeid) {
					newdoc.setField("DocumentTypeKey", options.doctypeid);
				}
				if (options.folderid) {
					newdoc.setField("FolderID", options.folderid);
				}
			}
		}

		return newdoc;
	} //--end createDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getView
	 * Return a back end docova view object
	 * Inputs: viewname - string - name of view
	 * Returns: DocovaView object
	 *-------------------------------------------------------------------------------------------------*/
	this.getView = function (viewname) {
		if (viewname == "" || this.appID == "" || this.filePath == "") {
			return null;
		}
		var viewobj = new DocovaView(this, viewname);
		if (viewobj.viewName == "") {
			viewobj = null;
		}

		return viewobj;
	} //--end getView

	/*----------------------------------------------------------------------------------------------
	 * Function: getDocument
	 * Return a back end docova document object
	 * Inputs: docid - string - universalid or dockey of document
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getDocument = function (docid) {
		var result = null;

		var fields = "DocKey,Form";

		var request = "<Request>";
		request += "<Action>GETDOCFIELDS</Action>";
		request += "<AppID>" + this.appID + "</AppID>";
		request += "<Fields>" + (Array.isArray(fields) ? fields.join(",") : fields) + "</Fields>";
		request += "<Unid>" + docid + "</Unid>";
		request += "</Request>";

		var xmldata = _requestHelper(request, "DocumentServices");
		if (xmldata) {
			var fielddata = _parserHelper(this, xmldata, "fields");
			if (fielddata) {
				var dockey = null;
				for (var p in fielddata) {
					if (fielddata.hasOwnProperty(p) && "dockey" == (p + "").toLowerCase()) {
						$dockey = fielddata[p];
						break;
					}
				}
				result = new DocovaDocument(this, docid, (dockey ? dockey : docid));
			}
		}

		return result;
	} //--end getDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getProfileDocCollection
	 * Return a DocovaCollection of profile documents
	 * Inputs: profilename - string - name of profile document
	 * Returns: DocovaCollection object
	 *-------------------------------------------------------------------------------------------------*/
	this.getProfileDocCollection = function (profilename) {
		var result = null;

		var request = "<Request>";
		request += "<Action>GETPROFILEDOCCOLLECTION</Action>";
		request += "<ProfileName><![CDATA[" + (!profilename ? "" : profilename) + "]]></ProfileName>";
		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			result = _parserHelper(this, xmldata, "collection");
		}

		return result;
	} //--end getProfileDocCollection


	/*----------------------------------------------------------------------------------------------
	 * Function: getProfileDocument
	 * Return a back end docova document object for a profile document
	 * Inputs: profilename - string - name of profile document
	 *         key - string (optional) - unique key to identify profile
	 * Returns: DocovaDocument object
	 *-------------------------------------------------------------------------------------------------*/
	this.getProfileDocument = function (profilename, key) {
		var result = null;
		if (!profilename || profilename == "") {
			return result;
		}

		var request = "<Request>";
		request += "<Action>GETPROFILEDOC</Action>";
		request += "<AppID>" + this.appID + "</AppID>";
		request += "<ProfileName><![CDATA[" + profilename + "]]></ProfileName>";
		request += "<ProfileKey><![CDATA[" + (key && key != "" ? key : "") + "]]></ProfileKey>";
		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			var profileid = _parserHelper(this, xmldata, "text");
			if (profileid && profileid != "") {
				result = new DocovaDocument(this, profileid);
			}
		}

		return result;
	} //--end getProfileDocument


	/*----------------------------------------------------------------------------------------------
	 * Function: setProfileFields
	 * Sets one or more fields on a back end profile document
	 * Inputs: profilename - string - name of profile
	 *         fieldvalues - value pair array consisting of field name and value
	 *         key - string (optional) - unique key to identify profile
	 * Returns: boolean - true if successful, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.setProfileFields = function (profilename, fieldvalues, key) {
		var result = false;
		if (typeof profilename == 'undefined' || typeof fieldvalues == 'undefined') {
			return result;
		}
		var request = "<Request>";
		request += "<Action>SETPROFILEFIELDS</Action>";
		request += "<ProfileName><![CDATA[" + profilename + "]]></ProfileName>";
		request += "<AppID>" + this.appID + "</AppID>";
		request += "<ProfileKey><![CDATA[" + (key && key != "" ? key : "") + "]]></ProfileKey>";
		request += "<Fields>";
		for (var key in fieldvalues) {
			var deletefield = false;
			if (_DOCOVAEdition == "SE") {
				var seplist = new Array(",", ";", ":", "\u00A5");
			} else {
				var seplist = new Array(",", ";", ":", "Â¥");
			}
			if (fieldvalues.hasOwnProperty(key)) {
				var tempval = fieldvalues[key];
				if (tempval === null) {
					deletefield = true;
					tempval = "";
				}
				if (!Array.isArray(tempval)) {
					var tmpval = tempval;
					tempval = new Array();
					tempval.push(tmpval);
				}
				var datatype = "";
				for (var x = 0; x < tempval.length; x++) {
					if (datatype === "text") {
						break;
					}
					if (tempval[x] !== null && tempval[x] !== "") {
						if (Object.prototype.toString.call(tempval[x]) === '[object Date]') {
							datatype = "date";
						} else if (typeof tempval[x] === "number" || typeof tempval[x] === "boolean") {
							datatype = "number";
						} else if (typeof tempval[x] === "string") {
							datatype = "text";
						}
					}
				}
				if (datatype == "") {
					datatype = "text";
				}
				for (var x = 0; x < tempval.length; x++) {
					if(typeof tempval[x] == "undefined" || tempval[x] === null){
						tempval[x] = "";
					}else{
						if (datatype == "date") {
							tempval[x] = Docova.Utils.convertDateFormat(tempval[x])
						} else if (datatype == "number") {
							tempval[x] = tempval[x].toString();
						} else {
							if (typeof tempval[x] != "string") {
								tempval[x] = tempval[x].toString();
							}
						}
						for (var s = (seplist.length - 1); s > -1; s--) {
							if (tempval[x].indexOf(seplist[s]) > -1) {
								seplist.splice(s, 1);
							}
						}
					}
				}
				var sep = seplist[0];
				var attribs = ' dt="' + datatype + '"' + (deletefield ? ' ignoreblanks="1"' : '') + (tempval.length > 1 ? ' multi="1" sep="' + sep + '"' : '');
				request += "<" + key + attribs;
				request += "><![CDATA[";
				request += (tempval.length > 1 ? tempval.join(sep) : tempval[0]);
				request += "]]></" + key + ">";
			}
		}
		request += "</Fields>";
		request += "</Request>";
		var xmldata = _requestHelper(request, "DocumentServices");
		if (xmldata) {
			var tempresult = _parserHelper(this, xmldata, "");
			result = (tempresult === true);
		}
		return result;
	} //--end setProfileFields

	/*----------------------------------------------------------------------------------------------
	 * Function: getProfileFields
	 * Returns the value of one or more fields from a profile document
	 * Inputs: profilename - string - name of profile
	 *         fields - concatentated string of field names or array of field names to retrieve
	 *         key - string (optional) - unique key to identify profile
	 * Returns: value pair object - {fielname1:fieldvalue, fieldname2:fieldvalue, etc}
	 *-------------------------------------------------------------------------------------------------*/
	this.getProfileFields = function (profilename, fields, key) {
		var result = null;
		if (!profilename || profilename == "") {
			return result;
		}
		if (!fields || fields == "") {
			return result;
		}
		var request = "<Request>";
		request += "<Action>GETPROFILEFIELDS</Action>";
		request += "<ProfileName><![CDATA[" + profilename + "]]></ProfileName>";
		request += "<ProfileKey><![CDATA[" + (key && key != "" ? key : "") + "]]></ProfileKey>";
		request += "<Fields>" + (Array.isArray(fields) ? fields.join(",") : fields) + "</Fields>";
		request += "<Unid>" + this.unid + "</Unid>";
		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			result = _parserHelper(this, xmldata, "fields");
		}
		return result;
	} //--end getProfileFields
	
	/*----------------------------------------------------------------------------------------
	 * Function getFormFields
	 * Returns all design elements belong to the form
	 * Inputs: formname - string - name of form
	 * 		   subaction - string (optional) - Optional keyword to return fields array or field names string
	 * Returns: string|array
	 */
	this.getFormFields = function(formname) {
		var result = null;
		if (!formname || formname == '') {
			return result;
		}
		
		var request = "<Request>";
		request += "<Action>GETFORMFIELDS</Action>";
		request += "<FormName><![CDATA[" + formname + "]]></FormName>";
		request += "<AppID>" + (this.appID ? this.appID : docInfo.AppID) + "</AppID>";
		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			result = _parserHelper(this, xmldata, "text");
		}
		return result;
	}

	/*----------------------------------------------------------------------------------------------
	 * Function: search
	 * Return a collection of docova document objects
	 * Inputs: query: string - search formula
	 *         cutoffdate: datetime - (optional)  - date time cutoff for results, null for no cutoff
	 *         maxdocs: integer - (optional) - number of results to return, 0 for all
	 * Returns: DocovaCollection of DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.search = function (query, cutoffdate, maxdocs) {
		var doccol = new DocovaCollection(this);

		if (!query || query == "") {
			return doccol;
		}

		var request = "<Request>";
		request += "<Action>SEARCH</Action>";
		request += "<query><![CDATA[" + query + "]]></query>";
		request += "<cutoffdate>" + (cutoffdate ? cutoffdate.toString() : "") + "</cutoffdate>";
		request += "<maxresults>" + (maxdocs && Number.isInteger(maxdocs) ? maxdocs.toString() : "0") + "</maxresults>";

		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			var unidlist = _parserHelper(this, xmldata, "text");
			if (unidlist && unidlist != "") {
				unidlist = unidlist.split(":");
				for (var i = 0; i < unidlist.length; i++) {
					doccol.addEntry(new DocovaDocument(this, unidlist[i], unidlist[i]));
				}
			}
		}

		return doccol;
	} //--end search

	/*----------------------------------------------------------------------------------------------
	 * Function: FTSearch
	 * Return a collection of docova document objects
	 * Inputs: query: string - search query
	 *         maxdocs: integer - (optional) - number of results to return, 0 for all
	 *         sortoptions: - integer (optional) - sort order. One of
	 *             8 - sorts by relevance score (default).
	 *             32 - sorts by document date in descending order.
	 *             64 - sorts by document date in ascending order.
	 *             1542 - sorts by document creation date in descending order.
	 *             1543 - sorts by document creation date in ascending order.
	 *         otheroptions: - integer (optional) - other search options (combine options by adding)
	 *             512 - uses stem words as the basis of the search.
	 *             1024 - uses thesaurus synonyms.
	 * Returns: DocovaCollection of DocovaDocument objects
	 *-------------------------------------------------------------------------------------------------*/
	this.FTSearch = function (query, maxdocs, sortoptions, otheroptions) {
		var doccol = new DocovaCollection(this);

		if (!query || query == "") {
			return doccol;
		}

		var request = "<Request>";
		request += "<Action>FTSEARCH</Action>";
		request += "<query><![CDATA[" + query + "]]></query>";
		request += "<maxresults>" + (maxdocs && Number.isInteger(maxdocs) ? maxdocs.toString() : "0") + "</maxresults>";
		request += "<sortoptions>" + (sortoptions && Number.isInteger(sortoptions) ? sortoptions.toString() : "8") + "</sortoptions>";
		request += "<otheroptions>" + (otheroptions && Number.isInteger(otheroptions) ? otheroptions.toString() : "0") + "</otheroptions>";

		request += "</Request>";
		var xmldata = _requestHelper(request);
		if (xmldata) {
			var unidlist = _parserHelper(this, xmldata, "text");
			if (unidlist && unidlist != "") {
				unidlist = unidlist.split(":");
				for (var i = 0; i < unidlist.length; i++) {
					doccol.addEntry(new DocovaDocument(this, unidlist[i], unidlist[i]));
				}
			}
		}

		return doccol;
	} //--end FTSearch


	/*----------------------------------------------------------------------------------------------
	 * Function: _requestHelper
	 * Internal function to help with making ajax requests for application data xml
	 * Inputs: request - string - xml string containing request to services agent
	 *         service - string - where to retrieve data from; defaults to DocumentServices
	 * Returns: xml string - containing results of request
	 *-------------------------------------------------------------------------------------------------*/
	function _requestHelper(request, service) {
		var xmldata = null;

		if (_DOCOVAEdition == "SE") {
			var serviceurl = "/" + docInfo.NsfName + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent";
		} else {
			var serviceurl = _filePath + "/" + (!service ? "DocumentServices" : service) + "?OpenAgent";
		}

		jQuery.ajax({
			type: "POST",
			url: serviceurl,
			data: encodeURI(request),
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				xmldata = xml;
			},
			error: function () {}
		});

		return xmldata;
	} //--end _requestHelper

	/*----------------------------------------------------------------------------------------------
	 * Function: _parserHelper
	 * Internal function to help with parsing document data out of xml response
	 * Inputs: objself - current DocovaApplication object
	 * 	       xml string - xml string containing document result data
	 *         parsetype - string - action to perform
	 * Returns:
	 *-------------------------------------------------------------------------------------------------*/
	function _parserHelper(objself, xmldata, parsetype) {
		var result = null;

		//      try{
		var xmlobj = $(xmldata);
		var statustext = xmlobj.find("Result").first().text();
		if (statustext == "OK") {
			var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
			//-- update fields action type
			if (parsetype.toLowerCase() == "fields" || parsetype.toLowerCase() == "field") {
				result = {};
				jQuery(resultxmlobj).find("Fields:first").children().each(function () {
					var fieldname = jQuery(this).prop("tagName");
					var datatype = jQuery(this).attr("dt");
					var ismulti = (jQuery(this).attr("multi") === "1");
					var sep = jQuery(this).attr("sep") || ",";
					var fieldval = jQuery(this).text();
					if (ismulti) {
						fieldval = fieldval.split(sep);
						for (var x = 0; x < fieldval.length; x++) {
							if (datatype == "date") {
								fieldval[x] = new Date(fieldval[x]);
							} else if (datatype == "number") {
								fieldval[x] = Number(fieldval[x]);
							}
						}
					} else {
						if (datatype == "date") {
							fieldval = new Date(fieldval);
						} else if (datatype == "number") {
							fieldval = Number(fieldval);
						}
					}
					result[fieldname] = fieldval;
				});
			} else if (parsetype.toLowerCase() == "text") {
				result = jQuery(resultxmlobj).text();
			} else if (parsetype.toLowerCase() == "collection") {
				var doccol = new DocovaCollection(this);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
					jQuery(resultxmlobj).find("Document").each(function () {
						var unid = jQuery(this).find("Unid").text();
						var docid = "DK" + unid;
						if (unid && unid != "") {
							docobj = new DocovaDocument(objself, unid, docid);
							if (docobj) {
								doccol.addEntry(docobj);
							}
						}
					});
					result = doccol;
				}
			} else {
				result = true;
			}
		}
		//      }catch(err){
		//          return null;
		//      }

		return result;
	} //--end _parserHelper

	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new application
	 *-------------------------------------------------------------------------------------------------*/
	var serviceurl = "";
	if (docInfo && docInfo.PortalWebPath && docInfo.PortalWebPath != "") {
		serviceurl = docInfo.PortalWebPath;
	} else if (PortalWebPath && PortalWebPath != "") {
		serviceurl = PortalWebPath;
	}

	if (opts.appname == "" && opts.filepath == "" && opts.appid == "") {
		var ws = Docova.getUIWorkspace(document);
		opts.appid = ws.getCurrentAppId();
	}

	if (serviceurl == "") {
		return null;
	}

	if (window.top.Docova.GlobalStorage[opts.appid]) {
		var appobj = window.top.Docova.GlobalStorage[opts.appid];
		_appID = appobj.appID;
		_appName = appobj.appName;
		if (_DOCOVAEdition == "SE") {
			_filePath = appobj.appName;
		} else {
			_filePath = appobj.filePath;
		}
		_isApp = appobj.isApp;
		_appIcon = appobj.appIcon;
		_appIconColor = appobj.appIconColor;
		_appDescription = appobj.appDescription;
		_appType = appobj.appType;
		_libraryList = appobj.libraryList;
		_currentUserAccessLevel = appobj.currentUserAccessLevel;

		return this;
	}

	request = "<Request>";
	request += "<Action>GETLIBRARYINFO</Action>";
	request += "<InfoType>All</InfoType>"
	request += "<LibraryKey>" + opts.appid + "</LibraryKey>";
	request += "<LibraryName><![CDATA[" + opts.appname + "]]></LibraryName>";
	request += "<LibraryPath><![CDATA[" + opts.filepath + "]]></LibraryPath>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "</Request>";

	serviceurl += (serviceurl.substr(-1) == "/" ? "" : "/") + "LibraryServices?OpenAgent";
	
	var _self = this;

	jQuery.ajax({
		type: "POST",
		url: serviceurl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			try {
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var resultxmlobj = xmlobj.find("Result[ID=Ret1]");

					var libkey = jQuery(resultxmlobj).find("LibraryKey").text();
					var libtitle = jQuery(resultxmlobj).find("LibraryName").text();
					if (_DOCOVAEdition == "Domino") {
						var libpath = jQuery(resultxmlobj).find("LibraryPath").text();
					}
					var isapp = jQuery(resultxmlobj).find("IsApp").text();
					var isnativedb = jQuery(resultxmlobj).find("IsNativeDb").text();
					var apptype = jQuery(resultxmlobj).find("AppType").text();
					var appicon = jQuery(resultxmlobj).find("AppIcon").text();
					var appiconcolor = jQuery(resultxmlobj).find("AppIconColor").text();
					var appdescription = jQuery(resultxmlobj).find("AppDescription").text();
					var currentuseraccesslevel = jQuery(resultxmlobj).find("CurrentUserAccessLevel").text();
					var liblist = jQuery(resultxmlobj).find("LibraryList").text();
					if (libkey != "" && libtitle != "") {
						_appID = libkey;
						_appName = libtitle;
						if (_DOCOVAEdition == "SE") {
							_filePath = libtitle;
						} else {
							_filePath = libpath;
						}
						_isApp = isapp;
						_isNativeDb = (isnativedb == "true" || isnativedb == "1");
						_appIcon = appicon;
						_appIconColor = appiconcolor;
						_appDescription = appdescription;
						_currentUserAccessLevel = currentuseraccesslevel;
						_appType = apptype;
						_libraryList = liblist;
						if (!window.top.Docova.GlobalStorage[libkey])
							window.top.Docova.GlobalStorage[libkey] = _self;
					} else {
						return null;
					}
				}
			} catch (err) {
				return null;
			}
		},
		error: function () {
			return null;
		}
	}); //--end Constructor

} //end DocovaApplication class


/*
 * Class: DocovaSession
 * Helper class to assist with translations from Lotus Notes
 * Typically accessed as new DocovaSession()
 * Parameters:
 * Properties:
 **/
function DocovaSession() {
	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaSession";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'addressBooks', {
		get: function () {
			//TODO - implement this property
		},
		enumerable: true
	});
	Object.defineProperty(this, 'commonUserName', {
		get: function () {
			return (info && info.UserNameCN ? info.UserNameCN : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'currentAgent', {
		get: function () {
			//TODO - implement this property
		},
		enumerable: true
	});
	Object.defineProperty(this, 'currentDatabase', {
		get: function () {
			return new DocovaApplication();
		},
		enumerable: true
	});
	Object.defineProperty(this, 'effectiveUserName', {
		get: function () {
			return (info && info.UserName ? info.UserName : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'httpURL', {
		get: function () {
			//TODO - implement this property
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isOnServer', {
		get: function () {
			return true;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'serverName', {
		get: function () {
			return (info && info.ServerName ? info.ServerName : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'userGroupNameList', {
		get: function () {
			//TODO - implement this property
		},
		enumerable: true
	});
	Object.defineProperty(this, 'userName', {
		get: function () {
			return (info && info.UserName ? info.UserName : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'userNameList', {
		get: function () {
			var tempresult = (info && info.UserNameList ? info.UserNameList : "");
			return tempresult.split(":");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'userNameObject', {
		get: function () {
			return (info && info.UserName ? new DocovaName(info.UserName) : null);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'userRoles', {
		get: function () {
			var tempresult = (info && info.UserRoles ? info.UserRoles : "");
			return tempresult.split(":");
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Function: createName
	 * Returns a new DocovaName object
	 * Inputs: namestring - string - name of user
	 * Returns: DocovaName object
	 *-------------------------------------------------------------------------------------------------*/
	this.createName = function (namestring) {
		var result = null;
		if (typeof namestring === 'string' && namestring !== "") {
			result = new DocovaName(namestring);
		}
		return result;
	} //--end createName

	/*----------------------------------------------------------------------------------------------
	 * Function: getDatabase
	 * Gets a DOCOVA Application object
	 * Inputs: servername - string - name of server - ignored
	 *         filepath - string - path to application database
	 *         create - boolean - not implemented - ignored
	 * Returns: DocovaApplication object
	 *-------------------------------------------------------------------------------------------------*/
	this.getDatabase = function (servername, filepath, create) {
		var result = null;
		result = new DocovaApplication({
				"filepath": filepath
			});
		return result;
	} //--end getDatabase

	/*----------------------------------------------------------------------------------------------
	 * Function: getEnvironmentVar
	 * Sets an environment variable
	 * Inputs: varname - string - variable identifier
	 * Returns: string
	 *-------------------------------------------------------------------------------------------------*/
	this.getEnvironmentVar = function (varname) {
		var result = "";
		result = Docova.Utils.getCookie({
				keyname: varname,
				ignorecase: true,
				httpcookie: true
			});
		if (result == null || typeof result == 'undefined')
			result = "";
		return result;
	} //--end getEnvironmentVar

	/*----------------------------------------------------------------------------------------------
	 * Function: setEnvironmentVar
	 * Sets an environment variable
	 * Inputs: varname - string - variable identifier
	 *         varvalue - various - variable value (converted to string on save)
	 *-------------------------------------------------------------------------------------------------*/
	this.setEnvironmentVar = function (varname, varvalue) {
		Docova.Utils.setCookie({
			keyname: varname,
			keyvalue: varvalue.toString(),
			httpcookie: true
		});
	} //--end setEnvironmentVar

	/*----------------------------------------------------------------------------------------------
	 * Function: createDateRange
	 * creates a new DocovaDateRange object
	 *-------------------------------------------------------------------------------------------------*/
	this.createDateRange = function () {
		return new DocovaDateRange();
	} //--end createDateRange

	/*----------------------------------------------------------------------------------------------
	 * Function: createRichTextStyle
	 * creates a new DocovaRichTextStyle object
	 *-------------------------------------------------------------------------------------------------*/
	this.createRichTextStyle = function () {
		return new DocovaRichTextStyle();
	} //--end createRichTextStyle

	/*----------------------------------------------------------------------------------------------
	 * Function: createRichTextStyle
	 * creates a new DocovaRichTextStyle object
	 *-------------------------------------------------------------------------------------------------*/
	this.createRichTextParagraphStyle = function () {
		return new DocovaRichTextParagraphStyle();
	} //--end createRichTextStyle

	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new session
	 *-------------------------------------------------------------------------------------------------*/
	var serviceurl = "";
	if (docInfo && docInfo.PortalWebPath && docInfo.PortalWebPath != "") {
		serviceurl = docInfo.PortalWebPath;
	} else if (PortalWebPath && PortalWebPath != "") {
		serviceurl = PortalWebPath;
	}

	if (serviceurl == "") {
		return null;
	}
	//--end Constructor

} //end DocovaSession class


/*
 * Class: DocovaName
 * Converts between different user name formats
 * Typically accessed as new DocovaName("Jim Smith/Acme")
 * Parameters: inputname - string - name to use to initialize object
 **/
function DocovaName(inputname) {
	var _username = "";

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaName";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'abbreviated', {
		get: function () {
			return (_username === "" ? "" : $$Name("[ABBREVIATE]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'addr821', {
		get: function () {
			return (_username === "" ? "" : $$Name("[ADDRESS821]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'admd', {
		get: function () {
			return (_username === "" ? "" : $$Name("[A]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'canonical', {
		get: function () {
			return (_username === "" ? "" : $$Name("[CANONICALIZE]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'common', {
		get: function () {
			return (_username === "" ? "" : $$Name("[CN]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'country', {
		get: function () {
			return (_username === "" ? "" : $$Name("[C]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'generation', {
		get: function () {
			return (_username === "" ? "" : $$Name("[Q]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'given', {
		get: function () {
			return (_username === "" ? "" : $$Name("[G]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'initials', {
		get: function () {
			return (_username === "" ? "" : $$Name("[I]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isHierarchical', {
		get: function () {
			return (_username.indexOf("/") > -1)
		},
		enumerable: true
	});
	Object.defineProperty(this, 'organization', {
		get: function () {
			return (_username === "" ? "" : $$Name("[O]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'orgUnit1', {
		get: function () {
			return (_username === "" ? "" : $$Name("[OU1]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'orgUnit2', {
		get: function () {
			return (_username === "" ? "" : $$Name("[OU2]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'orgUnit3', {
		get: function () {
			return (_username === "" ? "" : $$Name("[OU3]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'orgUnit4', {
		get: function () {
			return (_username === "" ? "" : $$Name("[OU4]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'prmd', {
		get: function () {
			return (_username === "" ? "" : $$Name("[P]", _username));
		},
		enumerable: true
	});
	Object.defineProperty(this, 'surname', {
		get: function () {
			return (_username === "" ? "" : $$Name("[S]", _username));
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new name object
	 *-------------------------------------------------------------------------------------------------*/
	if (typeof inputname === 'string') {
		_username = inputname;
	}
	//--end Constructor

} //end DocovaName class


/*
 * Class: DocovaDateRange
 * Used to specify a date/time range
 * Typically accessed as DocovaSession.createDateRange()
 **/
function DocovaDateRange() {
	var _startdate = null;
	var _enddate = null;

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaDateRange";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'startDateTime', {
		get: function () {
			return _startdate;
		},
		set: function (newval) {
			_startdate = new DocovaDateTime(newval);
			if (!_enddate) {
				_enddate = new DocovaDateTime(newval);
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'endDateTime', {
		get: function () {
			return _enddate;
		},
		set: function (newval) {
			_enddate = new DocovaDateTime(newval);
			if (!_startdate) {
				_startdate = new DocovaDateTime(newval);
			}
		},
		enumerable: true
	});
	Object.defineProperty(this, 'text', {
		get: function () {
			//TODO - check format returned
			var result = "";
			var tempstart = "";
			var tempend = "";
			if (_startdate) {
				tempstart = _startdate.localTime;
			}
			if (_enddate) {
				tempend = _enddate.localTime;
			}
			if (tempstart !== "") {
				result = tempstart;
			}
			if (result == "" && tempend !== "") {
				result = tempend;
			}
			if (result !== "" && tempend !== "" && tempend !== tempstart) {
				result += " - " + tempend;
			}

			return result;
		},
		set: function (newval) {
			if (typeof newval !== 'string') {
				return;
			}
			var tempstart = "";
			var tempend = "";
			var pos = newval.indexOf(" - ");
			if (pos > -1) {
				tempstart = newval.slice(0, pos);
				tempend = newval.slice(pos + 3);
			} else {
				tempstart = newval;
				tempend = newval;
			}
			if (tempstart == "" || tempend == "") {
				return;
			}
			_startdate = new DocovaDateTime(tempstart);
			_enddate = new DocovaDateTime(tempend);
		},
		enumerable: true
	});

} //end DocovaDateRange class


/*
 * Class: DocovaDateTime
 * Used for date/time manipulation
 * Typically accessed as new DocovaDateTime("2017-05-02")
 * Parameters: inputdateval - string or date object - date/time string or datetime object
 **/
function DocovaDateTime(inputdateval) {
	var _dateval = null;

	/**** Properties ****/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaDateTime";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'dateOnly', {
		get: function () {
			//TODO check format returned
			return (_dateval ? _dateval.toDateString() : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'GMTTime', {
		get: function () {
			//TODO - check format returned
			return (_dateval ? _dateval.toUTCString() : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'isValidDate', {
		get: function () {
			return (_dateval !== null);
		},
		enumerable: true
	});
	Object.defineProperty(this, 'localTime', {
		get: function () {
			return (_dateval ? Docova.Utils.convertDateFormat(_dateval) : "");
		},
		set: function (newval) {
			var result = null;
			if (typeof newval === 'string') {
				result = $$TextToTime(newval);
			} else if (Object.prototype.toString.call(newval) === '[object Date]') {
				result = newval;
			}
			_dateval = result;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'LSLocalTime', {
		get: function () {
			return _dateval;
		},
		set: function (newval) {
			var result = null;
			if (typeof newval === 'string') {
				result = $$TextToTime(newval);
			} else if (Object.prototype.toString.call(newval) === '[object Date]') {
				result = newval;
			}
			_dateval = result;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'timeOnly', {
		get: function () {
			return (_dateval ? _dateval.toTimeString() : "");
		},
		enumerable: true
	});
	Object.defineProperty(this, 'timeZone', {
		get: function () {
			//TODO - function not implemented
			return "";
		},
		enumerable: true
	});

	/*
	 * Methods:
	 */

	/*----------------------------------------------------------------------------------------------
	 * Function: adjustYear
	 * Modifies the year of the date object
	 *-------------------------------------------------------------------------------------------------*/
	this.adjustYear = function (yearvar) {
		if (!_dateval || isNaN(yearvar)) {
			return;
		}

		_dateval = $$Adjust(_dateval, yearvar, 0, 0, 0, 0, 0);

	} //--end adjustYear

	/*----------------------------------------------------------------------------------------------
	 * Function: adjustMonth
	 * Modifies the month of the date object
	 *-------------------------------------------------------------------------------------------------*/
	this.adjustMonth = function (monthvar) {
		if (!_dateval || isNaN(monthvar)) {
			return;
		}

		_dateval = $$Adjust(_dateval, 0, monthvar, 0, 0, 0, 0);

	} //--end adjustMonth

	/*----------------------------------------------------------------------------------------------
	 * Function: adjustDay
	 * Modifies the day of the date object
	 *-------------------------------------------------------------------------------------------------*/
	this.adjustDay = function (dayvar) {
		if (!_dateval || isNaN(dayvar)) {
			return;
		}

		_dateval = $$Adjust(_dateval, 0, 0, dayvar, 0, 0, 0);

	} //--end adjustDay

	/*----------------------------------------------------------------------------------------------
	 * Function: adjustHour
	 * Modifies the hour of the date/time object
	 *-------------------------------------------------------------------------------------------------*/
	this.adjustHour = function (hourvar) {
		if (!_dateval || isNaN(hourvar)) {
			return;
		}

		_dateval = $$Adjust(_dateval, 0, 0, 0, hourvar, 0, 0);

	} //--end adjustHour

	/*----------------------------------------------------------------------------------------------
	 * Function: adjustMinute
	 * Modifies the minutes of the date/time object
	 *-------------------------------------------------------------------------------------------------*/
	this.adjustMinute = function (minutvar) {
		if (!_dateval || isNaN(minutvar)) {
			return;
		}

		_dateval = $$Adjust(_dateval, 0, 0, 0, 0, minutevar, 0);

	} //--end adjustMinute

	/*----------------------------------------------------------------------------------------------
	 * Function: adjustSecond
	 * Modifies the seconds of the date/time object
	 *-------------------------------------------------------------------------------------------------*/
	this.adjustSeconds = function (secondvar) {
		if (!_dateval || isNaN(secondvar)) {
			return;
		}

		_dateval = $$Adjust(_dateval, 0, 0, 0, 0, 0, secondvar);

	} //--end adjustSecond

	/*----------------------------------------------------------------------------------------------
	 * Function: setNow
	 * Sets the date to the current date time
	 *-------------------------------------------------------------------------------------------------*/
	this.setNow = function () {
		_dateval = new Date();
	} //--end setNow

	/*----------------------------------------------------------------------------------------------
	 * Function: timeDifference
	 * Returns the difference in seconds between this date and another time
	 *-------------------------------------------------------------------------------------------------*/
	this.timeDifference = function (otherdate) {
		if (!_dateval || !otherdate || Object.prototype.toString.call(otherdate) != '[object Date]') {
			return;
		}

		var result = 0;
		result = _dateval.getTime() - otherdate.getTime();
		if (result !== 0) {
			result = result / 1000;
			result = result.toFixed(0);
		}
		return result;
	} //--end timeDifference


	/*----------------------------------------------------------------------------------------------
	 * Constructor:
	 * Initializes the new date time object
	 *-------------------------------------------------------------------------------------------------*/
	if (typeof inputdateval === 'string') {
		_dateval = $$TextToTime(inputdateval);
	} else if (Object.prototype.toString.call(inputdateval) === '[object Date]') {
		_dateval = inputdateval;
	}
	//--end Constructor

} //end DocovaDateTime class


/*
 * Class: DocovaUIWorkspace
 * User interface classes and methods for interaction with DOCOVA documents and on-screen DOM elements
 * Typically accessed from Docova.getUIWorkspace(document)
 * Parameters:
 * 		curdoc: current html DOM document - if not set defaults to current dom doc
 * Properties:
 * 		currentDOM: html DOM object
 * 		CurrentDocument: DocovaUIDocument
 * 		currentDocument: alias of CurrentDocument
 **/
function DocovaUIWorkspace(curdoc) {
	if (curdoc == undefined || curdoc == null) {
		curdoc = document;
	}

	var _currentDOM = null;
	var _currentUIDoc = null;

	/*----------------------------------------------------------------------------------------------
	 * Properties;
	 *----------------------------------------------------------------------------------------------*/
	Object.defineProperty(this, 'constructor_name', {
		get: function () {
			return "DocovaUIWorkspace";
		},
		enumerable: true
	});
	Object.defineProperty(this, 'currentCalendarDateTime', {
		get: function () {
			var result = null;
			if (docInfo && docInfo.SelectedDate) {
				result = docInfo.SelectedDate;
			}
			return result;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'currentDOM', {
		get: function () {
			return _currentDOM;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'CurrentDocument', {
		get: function () {
			return _currentUIDoc;
		},
		enumerable: true
	});
	Object.defineProperty(this, 'currentDocument', {
		get: function () {
			return _currentUIDoc;
		},
		enumerable: true
	});

	/*----------------------------------------------------------------------------------------------
	 * Methods;
	 *----------------------------------------------------------------------------------------------*/

	this._getUIDocument = function () {
		var result = null;

		var targetwin = _currentDOM.defaultView || _currentDOM.parentWindow;
		if (typeof targetwin === 'undefined' || targetwin === null) {
			//strange case which should not occur but seems to happen when page is unloaded
		} else if (typeof targetwin.Docova_Self_UIdoc != "undefined") {
			result = targetwin.Docova_Self_UIdoc;
		} else if (typeof targetwin.info != "undefined" && targetwin.info !== null && typeof targetwin.info.isUIDoc != "undefined" && targetwin.info.isUIDoc) {
			result = new DocovaUIDocument(this, targetwin);
			targetwin.Docova_Self_UIdoc = result;
		} else {
			var parentwindow = targetwin.parent;
			while (parentwindow && parentwindow.self !== window.top) {
				if (parentwindow) {
					if (typeof parentwindow.Docova_Self_UIdoc != "undefined") {
						result = parentwindow.Docova_Self_UIdoc;
						break;
					} else if (typeof parentwindow.getinfovar != "undefined") {
						var tempinfo = parentwindow.getinfovar();
						if (typeof tempinfo != "undefined" && tempinfo !== null && typeof tempinfo.isUIDoc != "undefined" && tempinfo.isUIDoc) {
							result = new DocovaUIDocument(this, parentwindow);
							parentwindow.Docova_Self_UIdoc = result;
							break;
						}
					}
				}
				parentwindow = parentwindow.parent;
			}
		}

		return result;
	} //--end _getUIDocument


	/*----------------------------------------------------------------------------------------------
	 * Function: getDocovaFrame
	 * Helper function for retrieving a particular frame or frameset in the DOCOVA interface
	 * Inputs: framename - string - shortcut frame name or actual frame element name
	 *         returntype - string (optional) - one of the following
	 *                window - returns window object
	 *                document - returns DOM object
	 * Returns: frame element or window or document objects if returntype specified
	 *-------------------------------------------------------------------------------------------------*/
	this.getDocovaFrame = function (framename, returntype) {
		var result = null;

		var searchpath = new Array();
		var $tempobj = jQuery("#iFrameMain", window.top.document).contents().find("#fsMain");
		var $frame = null;

		var lookingfor = framename.toLowerCase();
		if (lookingfor == "fratoolbar" || lookingfor == "hometop" || lookingfor == "docovatabs") {
			searchpath.push("fraToolbar");
		} else if (lookingfor == "fradashboard" || lookingfor == "dashboard") {
			searchpath.push("fraDashboard");
		} else if (lookingfor == "fralibraries" || lookingfor == "libraries") {
			searchpath.push("fraLibraries");
		} else if (lookingfor == "frasearch" || lookingfor == "search") {
			searchpath.push("fraSearch");
		} else if (lookingfor == "fratabbedtablesearch" || lookingfor == "searchtabs") {
			searchpath.push("fraSearch");
			searchpath.push("fsContentFramesetSearch");
			searchpath.push("fraTabbedTableSearch");
		} else if (lookingfor == "fradesigner" || lookingfor == "designer") {
			searchpath.push("fraDesigner");
		} else if (lookingfor == "fraadmin" || lookingfor == "admin") {
			searchpath.push("fraAdmin");
		} else if (lookingfor == "frauserprofile" || lookingfor == "userprofile") {
			searchpath.push("fraUserProfile");
		} else if (lookingfor == "frarecordsmanager" || lookingfor == "rme") {
			searchpath.push("fraRecordsManager");
		} else if (lookingfor == "fraleftframe" || lookingfor == "foldercontrol") {
			searchpath.push("fraLibraries");
			searchpath.push("fsSubLayout");
			searchpath.push("fraLeftFrame");
		} else if (lookingfor == "fratabbedtable" || lookingfor == "tabs") {
			searchpath.push("fraLibraries");
			searchpath.push("fsSubLayout");
			searchpath.push("fsContentFrameset");
			searchpath.push("fraTabbedTable");
		} else if (lookingfor == "fracontenttop" || lookingfor == "contenttop") {
			searchpath.push("fraLibraries");
			searchpath.push("fsSubLayout");
			searchpath.push("fsContentFrameset");
			searchpath.push("fraContentTop");
		} else if (lookingfor == "fracontentbottom" || lookingfor == "contentbottom") {
			searchpath.push("fraLibraries");
			searchpath.push("fsSubLayout");
			searchpath.push("fsContentFrameset");
			searchpath.push("fraContentBottom");
		} else if (lookingfor == "fraworkspaceapps" || lookingfor == "fraworkspace" || lookingfor == "workspace") {
			searchpath.push("fraWorkspace");
			searchpath.push("fsWorkspace");
			searchpath.push("fraWorkspaceApps");
		} else {
			searchpath.push(framename);
		}
		for (var i = 0; i < searchpath.length; i++) {
			if ($tempobj == null || $tempobj.length < 1) {
				break;
			}

			var itemname = searchpath[i];
			if (itemname.slice(0, 3).toLowerCase() == "fra") {
				if ($tempobj[0].tagName.toUpperCase() == "FRAMESET") {
					$tempobj = $tempobj.find("frame[name='" + itemname + "']");
				} else {
					break; //-- not a frameset so stop
				}
			} else if (itemname.slice(0, 2).toLowerCase() == "fs") {
				if ($tempobj[0].tagName.toUpperCase() == "FRAMESET") {
					$tempobj = $tempobj.find("frameset[id='" + itemname + "']");
				} else if ($tempobj[0].tagName.toUpperCase() == "FRAME") {
					$tempobj = jQuery("#" + itemname, $tempobj[0].contentWindow.document)
				} else {
					break; //-- some other element so stop
				}
			}
			if ($tempobj.attr("id") == itemname) {
				$frame = $tempobj;
			}
		}

		//-- no frame found so search from top down
		if (!$frame) {
			$frame = this.getFrame(null, framename);
		}

		if ($frame && $frame.length > 0) {
			if (returntype && returntype == "window") {
				result = $frame[0].contentWindow;
			} else if (returntype && returntype == "document") {
				result = $frame[0].contentWindow.document;
			} else {
				result = $frame[0];
			}
		}

		return result;
	} //--end getDOCOVAFrame


	/*----------------------------------------
	 * Function: getFrame
	 * Finds a frame object. Starting with the provided document object doc param
	 * recursively searches down through DOM for the frame name that was provided.
	 * For all frames found, it searches the document objects of those frames for more frames
	 * until it finds the frame being searched for or all frames are exhausted.
	 * Parameters:
	 *      doc - document object for which find will search down DOM to locate frame
	 *      framename - name of the frame to find
	 * Returns: the found frame object
	 *----------------------------------------*/
	this.getFrame = function (doc, framename) {
		var startingdoc = (!doc ? window.top.document : doc);
		var thisuiw = this;

		var found = false;
		var frameObj;
		$(startingdoc).find("FRAME").each(function () {
			if ($(this).prop("id") == framename) {
				found = true;
				frameObj = $(this);
			}
			if (found == false) {
				frameObj = thisuiw.getFrame($(this)[0].contentWindow.document, framename)
			}
			if (frameObj) {
				return false;
			}
		});
		return frameObj;
	} //--end getFrame


	/*----------------------------------------
	 * Function: getPanel
	 * Finds the panel with the given name
	 * 
	 * Returns: window - window of the target panel
	 *----------------------------------------*/
	this.getPanel = function( appID, framename){

		var appFrameId = "fra" + appID;
		var doc = this.getDocovaFrame(appFrameId, "document");

		var startingdoc = (!doc ? window.top.document : doc);
		var thisuiw = this;

		var found = false;
		var frameObj;

		var appdiv = $(startingdoc).find ("#" + framename)

		if ( appdiv.length >  0){
			var ifrm = $(appdiv).find(".ifrmcontainer");
			if ( ifrm.length  > 0 ){
				frameObj = ifrm.get(0).contentWindow;
			}

		}

		
		return frameObj;
	}

	/*----------------------------------------
	 * Function: getCurrentPanel
	 * Finds the current panel that the code is running in
	 * 
	 * Returns: string - name of target frame or empty string
	 *----------------------------------------*/

	this.getCurrentPanel = function ()
	{
		function getPanelIframe(elem){
			return (  elem.frameElement && $(elem.frameElement).hasClass("ifrmcontainer")) ? elem : getPanelIframe(elem.parent );	 
		}

		return getPanelIframe(this.currentDOM.defaultView);
	}



	/*----------------------------------------
	 * Function: getTargetFrameName
	 * Finds the target frame for the specified/current element
	 * Parameters:
	 *      curelement - (optional) dom element that we are starting our search from
	 * Returns: string - name of target frame or empty string
	 *----------------------------------------*/
	this.getTargetFrameName = function (curelement) {
		var result = "";

		if (curelement) {
			var eTarget = $(curelement).attr("eTarget");
		}

		if (!eTarget || eTarget == "") {
			var parentFrame = frameElement;
			if (parentFrame) {
				eTarget = $(parentFrame).attr("target");
				if (eTarget && eTarget != "") {
					result = eTarget;
				}
			}
		}

		return result;
	} //--end getTargetFrameName

	/*-------------------------------------------
	 * Function: compose
	 * Method to compose a new document
	 * If docInfo is not available in the context of a compose then document.location properties are used
	 * to resolve serverurl, nsfname and docid
	--------------------------------------------*/
	this.compose = function (options) {
		var defaultOptns = {
			formname: "",
			tabtitle: "",
			isresponse: false,
			docid: "",
			inherit: false,
			targetframe: "",
			useajax: false,
			formdata: null
		};
		var opts = $.extend({}, defaultOptns, options);

		//-- special case of mobile interface
		try{
			if(window.parent && typeof window.parent.addAppDocument == "function"){
				window.parent.addAppDocument(opts.formname);
				return;
			}
		}catch(e){}
		
		var docid = "";
		var baseUrl = "";
		var docUrl = "";
		var serverurl = "";
		var docappid = "";

		if (typeof docInfo === "undefined") {
			var _protocol = document.location.protocol;
			var _hostname = document.location.hostname;
			var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
			var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
			_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

			serverurl = _protocol + "//" + _hostname + _port;
			nsfname = _pathname;
		} else {

			if ( docInfo.IsEmbedded ) 
			{ 
				//case of compose being called from embedded view on a form
				serverurl = window.parent.docInfo.ServerUrl;
				nsfname = window.parent.docInfo.NsfName;
				docid = window.parent.docInfo.DocID;
				docappid = window.parent.docInfo.AppID;

			}else{
				serverurl = docInfo.ServerUrl;
				nsfname = docInfo.NsfName;
				docid = docInfo.DocID;
				docappid = docInfo.AppID
			}
		}
		baseUrl = serverurl + "/" + nsfname + "/";

		if (opts.docid && opts.docid != "") {
			docid = opts.docid;
		}
		var fr = Math.random();
		if (opts.isresponse == true & opts.inherit == true) {
			if (_DOCOVAEdition == "SE") {
				docUrl = serverurl + "/" + nsfname + "/wViewForm/" + opts.formname + "?openform&AppID=" +docappid + "&ParentDocID=" + docid + "&ParentUNID=" + docid + "&isresponse=true&" + fr;
			} else {
				docUrl = baseUrl + opts.formname + "?openform&ParentDocID=" + docid + "&ParentUNID=" + docid + "&isresponse=true";
			}
		}
		if (opts.isresponse == true & opts.inherit == false) {
			if (_DOCOVAEdition == "SE") {
				docUrl = serverurl + "/" + nsfname + "/wViewForm/" + opts.formname + "?openform&AppID=" + docappid + "&ParentDocID=" + docid + "&isresponse=true&" + fr;
			} else {
				docUrl = baseUrl + opts.formname + "?openform&ParentDocID=" + docid + "&isresponse=true";
			}
		}
		if (opts.isresponse == false & opts.inherit == true) {
			if (_DOCOVAEdition == "SE") {
				docUrl = serverurl + "/" + nsfname + "/wViewForm/" + opts.formname + "?openform&AppID=" + docappid + "&ParentUNID=" + docid + '&' + fr;
			} else {
				docUrl = baseUrl + opts.formname + "?openform&ParentUNID=" + docid;
			}
		}
		if (opts.isresponse == false & opts.inherit == false) {
			if (_DOCOVAEdition == "SE") {
				docUrl = serverurl + "/" + nsfname + "/wViewForm/" + opts.formname + "?openform&AppID=" +docappid + '&' + fr;
			} else {
				docUrl = baseUrl + opts.formname + "?openform";
			}
		}
		
		var frameObj = null;
		var appobj = this.getCurrentApplication();
		if (appobj && appobj.isApp) 
		{
			var panelobj = null;
			if (opts.targetframe == "") {
				panelobj = this.getCurrentPanel();
			}else{
				panelobj = this.getPanel(appobj.appID, opts.targetframe);
			}

			if ( panelobj ){
				frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
			}

			
			if ( !frameObj )
			{
				frameObj = [panelobj.frameElement];
			}

		} else {
			frameObj = this.getDocovaFrame("fraTabbedTable");
		}

		

		if (frameObj) {
			var targettabwindow = frameObj[0].contentWindow;
			if (targettabwindow && targettabwindow.objTabBar) {
				var frameID = targettabwindow.objTabBar.GetNewDocID();
				if (opts.useajax) {
					//var tempurl = baseUrl + "about_blank?OpenPage";
					var tempurl = "about:blank";
					targettabwindow.objTabBar.CreateTab(opts.formname, frameID, "D", tempurl, docid, true);
					var newframe = this.getFrame(appFrameDoc, frameID.toString());

					if (_DOCOVAEdition == "SE") {
						docUrl = serverurl + "/" + nsfname + "/SubmitForRefresh/" + encodeURIComponent(opts.formname);

					docUrl += "?AppID=" + docInfo.AppID;
					}
					$.ajax({
						url: docUrl,
						type: "POST",
						data: opts.formdata,
						async: false
					}).done(function (data) {
						if (data) {

							DLIUploaderConfigs = null; //clear global variable as it may be appended to by this next update
							var targetwin = newframe[0].contentWindow;
							var temptargetdoc = newframe[0].contentDocument;
							try{
								temptargetdoc.open("text/html");
								temptargetdoc.write((typeof data == "string" ? data : (typeof data == "object" && data.hasOwnProperty("body") ? data.body : jQuery(data).html())));
								temptargetdoc.close();
							}catch(e){
								if(console){console.log(e.message);}
							}
							temptargetdoc.title = "New Document";
							targetwin.history.pushState({
								"html": data,
								"pageTitle": "New Document"
							}, "", docUrl);
						}
					})
					.fail(function () {
						if (console) {
							console.log("Error: unable to load page contents");
						}
					});
				} else {
					targettabwindow.objTabBar.CreateTab((opts.tabtitle ? opts.tabtitle : opts.formname), frameID, "D", docUrl, docid, true);
				}
			} else {
				jQuery(frameObj).prop("src", docUrl);
			}
		}
	} //--end compose
	
	/*-------------------------------------------
	 * Function: composeDocument
	 * Method to compose a new document in the ui
	 * Inputs: server - string (optional) - ignored
	 *         dbpath - string (optional) - path to application
	 *         form - string - name of form to use to create doc
	--------------------------------------------*/
	this.composeDocument = function (server, dbpath, form) {
		var result = null;
		var formname = "";
		var apppath = "";
		if (!server && !dbpath && !form) {
			return result;
		}
		if (typeof form == "string" & form !== "") {
			formname = form;
			apppath = dbpath;
		} else if (typeof dbpath == "string" & dbpath !== "") {
			formname = dbpath;
			apppath = server;
		} else if (typeof server == "string" & server !== "") {
			formname = server;
		}
		if (apppath !== "") {
			//TODO - add support for composing from another app
		}
		this.compose({
			"formname": formname
		});
		//TODO - see if we can return a ui doc object
		return result;
	} //--end composeDocument
	
	
	/*-------------------------------------------
	 * Function: composeResponseDocument
	 * Method to compose a new response document in the ui
	 * Inputs: server - string (optional) - ignored
	 *         dbpath - string (optional) - path to application
	 *         form - string - name of form to use to create doc
	--------------------------------------------*/
	this.composeResponseDocument = function (server, dbpath, form) {
		var result = null;
		var formname = "";
		var apppath = "";
		if (!server && !dbpath && !form) {
			return result;
		}
		if (typeof form == "string" & form !== "") {
			formname = form;
			apppath = dbpath;
		} else if (typeof dbpath == "string" & dbpath !== "") {
			formname = dbpath;
			apppath = server;
		} else if (typeof server == "string" & server !== "") {
			formname = server;
		}
		if (apppath !== "") {
			//TODO - add support for composing from another app
		}

		var opts = {'formname': formname, 'targetframe' : _targetframe, 'docid' : "", 'inherit' : false};
		var uidoc = this.currentDocument;
		if(uidoc && uidoc.isUIDoc && !uidoc.isNewDoc){
			opts.docid = uidoc.docID;
			opts.inherit = true;
			opts.isresponse = true;
		}else{
			return;
		}
		this.compose(opts);		
		
		//TODO - see if we can return a ui doc object
		return result;
	} //--end composeResponseDocument	

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: editDocument
	 * Opens a DOCOVA document in read mode or edit mode
	 * Inputs: editMode - boolean (optional) - true (default) to open in edit mode, false to open in read mode
	 *         targetDoc - DocovaDocument (optional) - document to open, if not specified currently highlighted document in a view will be used
	 *               if a document is currently open and this option is not specified the current doc edit format will be switched
	 * Return:
	 * Example: uiw.editDocument(true, doc)
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.editDocument = function (editMode, targetDoc) {
		var result = null;

		var desiredMode = (typeof editMode == 'undefined' ? true : editMode);
		var docurl = "";

		if (targetDoc && targetDoc.constructor_name && targetDoc.constructor_name == "DocovaDocument") {
			if (targetDoc.isNewDocument) {
				if (targetDoc.hasItem("Form") && targetDoc.getField("Form")[0] !== "") {
					var datetimefields = [];
					var numericfields = [];
					var formData = [];
					var tempitems = targetDoc.items;
					for (var i = 0; i < tempitems.length; i++) {
						if (tempitems[i].name != "$updatedby" && tempitems[i].name != "$revisions") {
							var dtype = tempitems[i].type;
							if(dtype == "date"){
								datetimefields.push(tempitems[i].name);
							}else if(dtype == "number"){
								numericfields.push(tempitems[i].name);
							}
							formData.push({
								'name': tempitems[i].name,
								'value': tempitems[i]._ToText(String.fromCharCode(31))
							});
						}
					}
					formData.push({
						name: "__dtmfields",
						value: datetimefields.join(String.fromCharCode(31))
					});
					formData.push({
						name: "__numfields",
						value: numericfields.join(String.fromCharCode(31))
					});
					if(_DOCOVAEdition == "Domino"){
					var refreshset = false;
					for (var i = 0; i < formData.length; i++) {
						if (formData[i].name == "__Click") {
							formData[i].value = "$Refresh";
							refreshset = true;
							break;
						}
					}
					if (!refreshset) {
						formData.push({
							"name": "__Click",
							"value": "$Refresh"
						});
						}
					}
					//TODO - see if we can return a ui doc object
					result = this.compose({
							"formname": targetDoc.getField("Form")[0],
							"useajax": true,
							"formdata": formData
						});
				}
			} else {
				docurl = targetDoc.getURL({
						'editmode': desiredMode
					});
			}
		} else if (this.currentDocument && this.currentDocument.constructor_name == "DocovaUIDocument") {
			var uidoc = this.currentDocument;
			result = uidoc;
			if (uidoc.editMode != desiredMode) {
				uidoc.editMode = desiredMode;
			}
		} else {
			//TODO - check if we are in a view object
		}

		if (docurl != "") {
			var doctitle = "Document";
			var curapp = this.getCurrentApplication();
			if (curapp.isApp) {
				var id = docurl.match(new RegExp("\/0\/D?K?([0-9A-F]{32})[\?&\/]", "i"));
				if (!id) {
					id = docurl.match(new RegExp("\/D?K?([0-9A-F]{32})[\?&\/]", "i"));
				}

				var panelobj = null;
				if (opts.targetframe == "") {
					panelobj = this.getCurrentPanel();
				}else{
					panelobj = this.getPanel(appobj.appID, opts.targetframe);
				}

				if ( panelobj ){
					frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
				}

			
				if ( !frameObj )
				{
					frameObj = [panelobj.frameElement];
				}

				if (frameObj) {
					var targettabwindow = frameObj[0].contentWindow;
					if (targettabwindow && targettabwindow.objTabBar) {
						if (!id) {
							id = targettabwindow.objTabBar.GetNewDocID();
						}
						targettabwindow.objTabBar.CreateTab(doctitle, id, "D", docurl, "", false);
					} else {
						jQuery(frameObj).prop("src", docurl);
					}
				}

			} else {
				var docovaTabWin = this.getDocovaFrame("docovatabs", "window");
				docovaTabWin.OpenLibraries();
				var folderControlWin = this.getDocovaFrame("foldercontrol", "window");
				if (folderControlWin) {
					folderControlWin.LoadFrame(docurl, false, doctitle);
				} else {
					alert("Error accessing folder listing.")
				}
			}
		}
		return result;
	} //--end editDocument

	/*--------------------------------------------------------------------------------------------------------------------------------------------
	 * Function: openDocument
	 * Opens a DOCOVA document in a new tab based on a url or mixture of id parameters
	 * Inputs: options - json value pair array consisting of
	 *            docurl - string - url path to document
	 *            title - string - name to assign to the tab showing the document
	 *            isapp - boolean (optional) - defaults to false, true if application, false if library
	 * Example: uiw.openDocument({url: "http://some.server.com/docova/somelib.nsf/0/abc13493983883939ab?opendocument"})
	 *------------------------------------------------------------------------------------------------------------------------------------------- */
	this.openDocument = function (options) {
		var defaultOptns = {
			docurl: "",
			docid: "",
			title: "",
			editmode: false,
			isapp: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.docurl == "" && opts.docid !== "") {
			var serverurl = "";
			var nsfname = "";
			if (typeof docInfo === "undefined") {
				var _protocol = document.location.protocol;
				var _hostname = document.location.hostname;
				var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
				var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
				_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

				serverurl = _protocol + "//" + _hostname + _port;
				nsfname = _pathname;
			} else {
				serverurl = docInfo.ServerUrl;
				nsfname = docInfo.NsfName;
			}
			if (_DOCOVAEdition == "SE") {
				if (opts.editmode === true) {
					
				}
				else {
					opts.docurl = serverurl + "/" + nsfname + "/wReadDocument/" + opts.docid + "?opendocument&ParentUNID=" + docInfo.AppID;
				}
			} else {
				opts.docurl = serverurl + "/" + nsfname + "/0/" + opts.docid + (opts.editmode == true ? '?editdocument' : "?opendocument");
			}
		}

		if (opts.isapp) {
			var id = opts.docurl.match(new RegExp("\/0\/D?K?([0-9A-F]{32})[\?&\/]", "i"));
			if (!id) {
				id = opts.docurl.match(new RegExp("\/D?K?([0-9A-F]{32})[\?&\/]", "i"));
			}

			var panelobj = null;
			if (opts.targetframe == "") {
				panelobj = this.getCurrentPanel();
			}else{
				panelobj = this.getPanel(appobj.appID, opts.targetframe);
			}

			if ( panelobj ){
				frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
			}

		
			if ( !frameObj )
			{
				frameObj = [panelobj.frameElement];
			}

			if (frameObj) {
				var targettabwindow = frameObj[0].contentWindow;
				if (targettabwindow && targettabwindow.objTabBar) {
					if (!id) {
						id = targettabwindow.objTabBar.GetNewDocID();
					}
					targettabwindow.objTabBar.CreateTab(opts.title, id, "D",opts.docurl, "", false);
				} else {
					jQuery(frameObj).prop("src", opts.docurl);
				}
			}
		} else {
			var docovaTabWin = this.getDocovaFrame("docovatabs", "window");
			docovaTabWin.OpenLibraries();
			var folderControlWin = this.getDocovaFrame("foldercontrol", "window");
			if (folderControlWin) {
				folderControlWin.LoadFrame(opts.docurl, false, opts.title);
			} else {
				alert("Error accessing folder listing.")
			}
		}
	} //--end openDocument
	
	this.openLayout = function(options) {
		var defaultOpts = {
			layoutid: '',
			title: '',
			forcenotab: false,
			targetframe: ''
		}
		
		var opts = $.extend({}, defaultOpts, options);
		
		if (opts.layoutid == '') {
			return;
		}
		
		if (opts.title == '') {
			opts.title = 'Layout: '+ opts.layoutid;
		}
		
		var layoutUrl = '',
			serverUrl = '';
		
		if (typeof docInfo === 'undefined') {
			var _protocol = document.location.protocol;
			var _hostname = document.location.hostname;
			var _port = (document.location.port == '80' || document.location.port == '') ? '' : ':' + document.location.port;
			var _pathname = (document.location.pathname).split('.nsf')[0] + '.nsf';// << for Domino
			_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

			serverurl = _protocol + '//' + _hostname + _port;
			nsfname = _pathname;
		} else {
			serverurl = docInfo.ServerUrl;
			nsfname = docInfo.NsfName;
			docid = docInfo.DocID;
		}

		if (_DOCOVAEdition == "SE") {
			layoutUrl = serverurl + "/" + nsfname + "/AppLoader/";
		} else {
			layoutUrl = serverurl + "/" + nsfname + "/AppLoader?ReadForm&";// << this should be changed for Domino, it always loads the default layout not the selected one!
		}
		
		var frameObj = null;
		var appobj = this.getCurrentApplication();
		if (appobj && appobj.isApp) {
			var panelobj = null;
			layoutUrl += appobj.appID + '?Layout=';
			layoutUrl += encodeURIComponent(opts.layoutid);
			if (opts.targetframe == "") {
				panelobj = this.getCurrentPanel();
			}else{
				panelobj = this.getPanel(appobj.appID, opts.targetframe);
			}

			if ( panelobj ){
				if (opts.forcenotab === true) {
					frameObj = [panelobj.frameElement];
				}
				else {
					frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
				}
			}
			
			if ( !frameObj )
			{
				frameObj = [panelobj.frameElement];
			}
		} else {
			frameObj = this.getDocovaFrame("fraTabbedTable");
		}

		if (frameObj) {
			if (opts.forcenotab === true) {
				jQuery(frameObj).prop('src', layoutUrl);
			}
			else {
				var targettabwindow = frameObj[0].contentWindow;
				if (targettabwindow && targettabwindow.objTabBar) {
					var frameID = targettabwindow.objTabBar.GetNewDocID();
					targettabwindow.objTabBar.CreateTab(opts.title, frameID, "D", layoutUrl);
				} else {
					jQuery(frameObj).prop("src", layoutUrl);
				}
			}
		}
	}// End openLayout

	/*-------------------------------------------
	 * Function: openPage
	 * Method to open a page in the ui
	 *------------------------------------------*/
	this.openPage = function (options) {
		var defaultOptns = {
			pagename: "",
			title: "",
			forcenotab: false,
			targetframe: ""
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.pagename == "") {
			return;
		}
		if (opts.title == "") {
			opts.title = opts.pagename;
		}

		var pageUrl = "";
		var serverurl = "";
		if (typeof docInfo === "undefined") {
			var _protocol = document.location.protocol;
			var _hostname = document.location.hostname;
			var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
			var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
			_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

			serverurl = _protocol + "//" + _hostname + _port;
			nsfname = _pathname;
		} else {
			serverurl = docInfo.ServerUrl;
			nsfname = docInfo.NsfName;
			docid = docInfo.DocID;
		}

		if (_DOCOVAEdition == "SE") {
			pageUrl = serverurl + "/" + nsfname + "/wViewPage/" + opts.pagename + "?openPage";
		} else {
			pageUrl = serverurl + "/" + nsfname + "/" + opts.pagename + "?openPage";
		}

		var frameObj = null;
		var appobj = this.getCurrentApplication();
		if (appobj && appobj.isApp) {
      		pageUrl += '&AppID=' + appobj.appID;
			var panelobj = null;
			if (opts.targetframe == "") {
				panelobj = this.getCurrentPanel();
			}else{
				panelobj = this.getPanel(appobj.appID, opts.targetframe);
			}

			if ( panelobj ){
				if (opts.forcenotab === true) {
					frameObj = [panelobj.frameElement];
				}
				else {
					frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
				}
			}
			
			if ( !frameObj )
			{
				frameObj = [panelobj.frameElement];
			}
		} else {
			frameObj = this.getDocovaFrame("fraTabbedTable");
		}

		if (frameObj) {
			if (opts.forcenotab === true) {
				jQuery(frameObj).prop('src', pageUrl);
			}
			else {
				var targettabwindow = frameObj[0].contentWindow;
				if (targettabwindow && targettabwindow.objTabBar) {
					var frameID = targettabwindow.objTabBar.GetNewDocID();
					targettabwindow.objTabBar.CreateTab(opts.title, frameID, "D", pageUrl);
				} else {
					jQuery(frameObj).prop("src", pageUrl);
				}
			}
		}
	} //--end openPage


	/*-------------------------------------------
	 * Function: openForm
	 * Method to open a form in the ui
	 *------------------------------------------*/
	this.openForm = function (options) {
		var defaultOptns = {
			formname: "",
			title: "",
			readmode: false,
			forcenotab: false,
			targetframe: "",
			urlparams: "",
			isprofile: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.formname == "") {
			return;
		}

		if (opts.title == "") {
			opts.title = opts.formname;
		}

		var formUrl = "";
		var serverurl = "";
		if (typeof docInfo === "undefined") {
			var _protocol = document.location.protocol;
			var _hostname = document.location.hostname;
			var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
			var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
			_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

			serverurl = _protocol + "//" + _hostname + _port;
			nsfname = _pathname;
		} else {
			serverurl = docInfo.ServerUrl;
			nsfname = docInfo.NsfName;
			docid = docInfo.DocID;
		}

		//if (opts.isprofile === false)
		if (_DOCOVAEdition == "SE") {
			formUrl = serverurl + "/" + nsfname + "/wViewForm/" + opts.formname + (opts.readmode ? "?readForm" : "?openForm");
		} else {
			formUrl = serverurl + "/" + nsfname + "/" + opts.formname + (opts.readmode ? "?readForm" : "?openForm");
		}

		if (opts.isprofile === true) {
			formUrl += "&profilename=" + opts.formname;
		}

		formUrl += '&AppID=' + docInfo.AppID;
		if (opts.urlparams != "") {
			formUrl += (opts.urlparams.slice(0, 1) !== "&" ? "&" : "") + opts.urlparams;
		}

		var frameObj = null;
		var appobj = this.getCurrentApplication();
		if (appobj && appobj.isApp) {

			var panelobj = null;
			if (opts.targetframe == "") {
				panelobj = this.getCurrentPanel();
			}else{
				panelobj = this.getPanel(appobj.appID, opts.targetframe);
			}

			if ( panelobj ){
				if (opts.forcenotab === true) {
					frameObj = [panelobj.frameElement];
				}
				else {
					frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
				}
			}
			
			if ( !frameObj )
			{
				frameObj = [panelobj.frameElement];
			}

		} else {
			frameObj = this.getDocovaFrame("fraTabbedTable");
		}

		if (frameObj) {
			if (opts.forcenotab === true) {
				jQuery(frameObj).prop('src', formUrl);
			}
			else {
				var targettabwindow = frameObj[0].contentWindow;
				if (targettabwindow && targettabwindow.objTabBar) {
					var frameID = targettabwindow.objTabBar.GetNewDocID();
					targettabwindow.objTabBar.CreateTab(opts.title, frameID, "D", formUrl);
				} else {
					jQuery(frameObj).prop("src", formUrl);
				}
			}
		}
	} //--end openForm

	/*-------------------------------------------
	 * Function: openView
	 * Method to open a view in the ui
	 *------------------------------------------*/
	this.openView = function (options) {
		var defaultOptns = {
			viewname: "",
			title: "",
			targetframe: "",
			viewtype: "",
			newtab: false
		};
		var opts = $.extend({}, defaultOptns, options);
		if (opts.title == "") {
			opts.title = opts.viewname;
		}

		if (opts.viewname == "") {
			return;
		}

		var openTabsUrl = "";
		var viewUrl = "";
		var serverurl = "";
		if (typeof docInfo === "undefined") {
			var _protocol = document.location.protocol;
			var _hostname = document.location.hostname;
			var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
			var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
			_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

			serverurl = _protocol + "//" + _hostname + _port;
			nsfname = _pathname;
		} else {
			serverurl = docInfo.ServerUrl;
			nsfname = docInfo.NsfName;
			docid = docInfo.DocID;
		}

		if (_DOCOVAEdition == "SE") {
			viewUrl = serverurl + "/" + nsfname + "/AppViewsAll/" + opts.viewname + "?OpenDocument&AppID=" + docInfo.AppID + "&viewType=" + opts.viewtype;
			encviewurl = encodeURIComponent("AppViewsAll/" + opts.viewname + "?OpenDocument&viewType=" + opts.viewtype + "&AppID=" + docInfo.AppID);
			openTabsUrl = serverurl + "/" + nsfname + "/wViewFrame?readForm&title=" + opts.viewname + "&surl=" + encviewurl + '&AppID=' + docInfo.AppID;
		} else {
			viewUrl = serverurl + "/" + nsfname + "/AppViewsAll/" + opts.viewname + "?OpenDocument&viewType=" + opts.viewtype;
			openTabsUrl = serverurl + "/" + nsfname + "/wViewFrame?readForm&title=" + opts.viewname + "&surl=" + "AppViewsAll/" + opts.viewname + "?OpenDocument&viewType=" + opts.viewtype;
		}

		var frameObj = null;
		var appobj = this.getCurrentApplication();
		if (appobj && appobj.isApp) {
			var panelobj = null;
			if (opts.targetframe == "") {
				panelobj = this.getCurrentPanel();
			}else{
				panelobj = this.getPanel(appobj.appID, opts.targetframe);
			}

			if ( panelobj ){
				frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
			}

			
			if ( !frameObj )
			{
				frameObj = [panelobj.frameElement];
			}

		} else {
			frameObj = this.getDocovaFrame("fraTabbedTable");
		}

		if (frameObj) {
			var targettabwindow = frameObj[0].contentWindow;
			if (targettabwindow && targettabwindow.objTabBar) {
				if (targettabwindow.objTabBar.IsFolderOpen("appViewMain")) {
					if (!opts.newtab) {
						targettabwindow.objTabBar.UpdateTab(opts.title, "appViewMain", viewUrl);
					} else {
						var frameid = hashCode(viewUrl).toString();
						targettabwindow.objTabBar.CreateTab(opts.title, frameid, "D", viewUrl);
					}
				} else {
					targettabwindow.objTabBar.CreateTab(opts.title, "appViewMain", "D", viewUrl);
				}
			} else {
				jQuery(frameObj).prop("src", openTabsUrl);
			}
		}
	} //--end openView

	/*-------------------------------------------
	 * Function: openUrl
	 * Method to open a url link in the ui
	 *------------------------------------------*/
	this.openUrl = function (options) {
		var defaultOptns = {
			url: "",
			title: "",
			forcenotab: false,
			targetframe: "",
			newwindow: false
		};
		var opts = $.extend({}, defaultOptns, options);

		if (opts.url == "") {
			return;
		}
		if (opts.title == "") {
			opts.title = opts.url;
		}

		var newUrl = "";
		var serverurl = "";
		if (typeof docInfo === "undefined" || docInfo === null || typeof docInfo.ServerUrl === "undefined" || typeof docInfo.ServerUrl == "") {
			var _protocol = document.location.protocol;
			var _hostname = document.location.hostname;
			var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
			var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
			_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash

			serverurl = _protocol + "//" + _hostname + _port;
			nsfname = _pathname;
		} else {
			serverurl = docInfo.ServerUrl;
			nsfname = docInfo.NsfName;
			docid = docInfo.DocID;
		}

		if (opts.url.toLowerCase().indexOf("http") === 0) {
			newUrl = opts.url;
			//-- check to see if we can launch this url in the same window
			if (!opts.newwindow) {
				var tempurl = newUrl.toLowerCase();
				var pos = tempurl.indexOf("/", 8);
				if (pos > -1) {
					tempurl = tempurl.slice(0, pos);
				}
				if (tempurl.indexOf("https://") == 0 && tempurl.slice(-4) == ":443") {
					tempurl = tempurl.slice(0, tempurl.length - 4);
				}
				if (tempurl.indexOf("http://") == 0 && tempurl.slice(-3) == ":80") {
					tempurl = tempurl.slice(0, tempurl.length - 3);
				}

				var tempurl2 = serverurl
					var pos = tempurl2.indexOf("/", 8);
				if (pos > -1) {
					tempurl2 = tempurl2.slice(0, pos);
				}
				if (tempurl2.indexOf("https://") == 0 && tempurl2.slice(-4) == ":443") {
					tempurl2 = tempurl2.slice(0, tempurl2.length - 4);
				}
				if (tempurl2.indexOf("http://") == 0 && tempurl2.slice(-3) == ":80") {
					tempurl2 = tempurl2.slice(0, tempurl2.length - 3);
				}

				if (tempurl != tempurl2) {
					opts.newwindow = true;
				}
			}
		} else if (opts.url.toLowerCase().indexOf("/") === 0) {
			newUrl = serverurl + opts.url;
		} else {
			newUrl = serverurl + "/" + nsfname + "/" + opts.url;
		}

		if (opts.newwindow) {
			window.open(decodeURIComponent(newUrl), opts.title);
		} else {
			if (appobj && appobj.isApp) {
				var panelobj = null;
				if (opts.targetframe == "") {
					panelobj = this.getCurrentPanel();
				}else{
					panelobj = this.getPanel(appobj.appID, opts.targetframe);
				}

				if ( panelobj ){
					if (opts.forcenotab === true) {
						frameObj = [panelobj.frameElement];
					}
					else {
						frameObj = this.getFrame(panelobj.document, "fraTabbedTable" );
					}
				}
				
				if ( !frameObj )
				{
					frameObj = [panelobj.frameElement];
				}
			} else {
				frameObj = this.getDocovaFrame("fraTabbedTable");
			}

			if (frameObj) {
				if (opts.forcenotab === true) {
					jQuery(frameObj).prop('src', newUrl);
				}
				else {
					var targettabwindow = frameObj[0].contentWindow;
					if (targettabwindow && targettabwindow.objTabBar) {
						var frameID = targettabwindow.objTabBar.GetNewDocID();
						targettabwindow.objTabBar.CreateTab(opts.title, frameID, "D", newUrl);
					} else {
						jQuery(frameObj).prop("src", newUrl);
					}
				}
			}
		}
	} //--end openUrl

	/*-----------------
	 * Function: getCurrentAppId
	 * Gets the currently open application id from the fraToolbar active tab
	 * Returns: application id
	 *-----------------*/
	this.getCurrentAppId = function () {
		var appid = "";
		if (docInfo && docInfo.LibraryKey && docInfo.LibraryKey !== "") {
			appid = docInfo.LibraryKey;
		} else if (docInfo && docInfo.AppID && docInfo.AppID !== "") {
			appid = docInfo.AppID;
		} else {
			var tabdoc = this.getDocovaFrame("fraToolbar", "document");
			appid = $("#tabitems li.ui-state-active", tabdoc).attr('id');
		}
		return appid;
	} //--end getCurrentAppId

	/*-----------------
	 * Function: getCurrentApplication
	 * Gets the currently open application based on id
	 * Returns: DocovaApplication object
	 *-----------------*/
	this.getCurrentApplication = function () {
		return new DocovaApplication();
	} //--end getCurrentApplication

	/*-------------------------------------------
	 * Function: editProfile
	 * Method to open a profile document for editing in the ui
	 *------------------------------------------*/
	this.editProfile = function (profileName, profileKey, targetFrame) {
		if (typeof profileName === 'undefined' || profileName == null || profileName == "") {
			return false;
		}

		var opts = {
			formname: profileName,
			readmode: false,
			targetframe : targetFrame,
			isprofile: true,
			urlparams: "&profilekey=" + (profileKey ? encodeURIComponent(profileKey) : "")
		};

		this.openForm(opts);

	} //--end editProfile

	/*-----------------
	 * Function: picklistCollection
	 * Displays a prompt window to select documents
	 * Inputs: type - ignored/unused
	 *         multiple - boolean
	 *         server - string
	 *         dbfilename - string
	 *         viewname - string
	 *         title - string
	 *         promptmsg - string
	 *         singlecategory - string (optional)
	 *         callback - function
	 *-----------------*/
	this.picklistCollection = function (type, multiple, server, dbfilename, viewname, title, promptmsg, singlecategory, cb) {
		var category = "";
		if (typeof singlecategory === "function" && typeof cb === "undefined") {
			var cb = singlecategory;
		} else if (typeof singlecategory == "string") {
			category = singlecategory;
		}

		var _dlgUrl = (docInfo.ServerUrl ? docInfo.ServerUrl : window.location.protocol + "://" + window.location.hostname);
		var temppath = (docInfo.NsfName ? docInfo.NsfName : window.location.pathname);
		_dlgUrl += (temppath.indexOf("/", temppath.length - 1) > -1 ? "" : "/") + temppath;
		_dlgUrl += (_dlgUrl.indexOf("/", _dlgUrl.length - 1) > -1 ? "" : "/");
		_dlgUrl += "dlgPickList?openform&mode=dialog&dialogid=PickList";
		if (_DOCOVAEdition == "SE") {
			_dlgUrl += "&dbpath=" + encodeURIComponent(dbfilename);
		} else {
			_dlgUrl += "&dbpath=" + encodeURIComponent((dbfilename.indexOf("/") === 0 ? "" : "/") + dbfilename);
		}
		_dlgUrl += "&viewname=" + encodeURIComponent(viewname);
		_dlgUrl += "&singlecat=" + (category && category !== "" ? encodeURIComponent(category) : "");
		_dlgUrl += "&prompt=" + encodeURIComponent(promptmsg);

		var _result = "";

		var _dlgId = "PickList";

		var _dlg = window.top.Docova.Utils.createDialog({
				id: _dlgId,
				url: _dlgUrl,
				title: title,
				height: 400,
				width: 825,
				resizable: true,
				useiframe: true,
				sourcewindow: window,
				sourcedocument: document,
				onResize: function (event, ui) {
					var ifrm = window.top.$("#" + _dlgId + "IFrame")[0];
					if (ifrm) {
						var newwidth = ui.size.width - 20;
						var newheight = ui.size.height - 50;

						ifrm.width = newwidth;
						ifrm.height = newheight;

						var viewifrm = jQuery(ifrm).contents().find("#viewPickListIFrame");
						if (viewifrm) {
							viewifrm.height(newheight - 60);

							var divview = jQuery(viewifrm).contents().find("#divViewContent");
							if (divview) {
								divview.height(newheight - 60);
							}
						}
					}

				},
				onClose: function () {
					if (cb && typeof cb == "function") {
						cb(_result);
					}
				},
				buttons: {
					"OK": function () {
						_result = null;
						var found = false;
						var doccollection = null;

						var $ifrm = window.top.$("#" + _dlgId + "IFrame");
						if ($ifrm) {
							var $viewifrm = $ifrm.contents().find("#viewPickListIFrame");
							if ($viewifrm) {
								var viewdocwin = $viewifrm[0].contentWindow;
								if (viewdocwin.objView) {
									var docids = viewdocwin.objView.selectedEntries;
									if (docids.length === 0 && viewdocwin.objView.currentEntry) {
										docids = [viewdocwin.objView.currentEntry];
									}
									if (docids.length === 0) {
										alert("Please select an entry from the list.");
									} else if (docids.length > 1 && !multiple) {
										alert("Please select a single entry.");
									} else {
										if (_DOCOVAEdition == "SE") {
											var appobj = new DocovaApplication({
												'appid': dbfilename
											});
										}
										else {
											var appobj = new DocovaApplication({
												'filepath': dbfilename
											});
										}
										doccollection = new DocovaCollection(appobj);

										for (var x = 0; x < docids.length; x++) {
											doccollection.addEntry(new DocovaDocument(appobj, docids[x]));
										}
										_result = doccollection;

										found = true;
									}
								}

							}
						}
						if (found) {
							_dlg.closeDialog();
						}
					},
					"Cancel": function () {
						_result = null;
						_dlg.closeDialog();
					}
				}
			})
	} //--end picklistCollection


	/*-----------------
	 * Function: picklistStrings
	 * Displays a prompt window to select documents
	 * Inputs: type - ignored/unused
	 *         multiple - boolean
	 *         server - string
	 *         dbfilename - string
	 *         viewname - string
	 *         title - string
	 *         promptmsg - string
	 *         column - integer
	 *         singlecategory - string (optional)
	 *         callback - function
	 *-----------------*/
	this.picklistStrings = function (type, multiple, server, dbfilename, viewname, title, promptmsg, column, singlecategory, cb) {

		var category = "";
		if (typeof singlecategory === "function" && typeof cb === "undefined") {
			var cb = singlecategory;
		} else if (typeof singlecategory == "string") {
			category = singlecategory;
		}

		//adjust column if single category selected
		if (category && category !== "") {
			if (column > 0) {
				column = column - 1;
			}
		}

		var _dlgUrl = (docInfo.ServerUrl ? docInfo.ServerUrl : window.location.protocol + "://" + window.location.hostname);
		var temppath = (docInfo.NsfName ? docInfo.NsfName : window.location.pathname);
		if (_DOCOVAEdition == "SE") {
			var targetdb = (dbfilename && dbfilename != "" ? dbfilename : docInfo.appName);
		} else {
			var targetdb = (dbfilename && dbfilename != "" ? dbfilename : temppath);

			if (targetdb !== temppath && targetdb.toLowerCase().indexOf(".nsf") == -1 && targetdb.indexOf("/") == -1) {
				//not the current db, does not contain slashes indicating a path, does not contain .nsf indicating a file name
				var tempapp = Docova.getApplication({
						'appid': targetdb,
						'appname': targetdb
					});
				if (tempapp && tempapp.filePath) {
					targetdb = tempapp.filePath;
				}
			}
			targetdb = (targetdb.indexOf("/") === 0 ? "" : "/") + targetdb;
		}

		_dlgUrl += (temppath.indexOf("/", temppath.length - 1) > -1 ? "" : "/") + temppath;
		_dlgUrl += (_dlgUrl.indexOf("/", _dlgUrl.length - 1) > -1 ? "" : "/");
		_dlgUrl += "dlgPickList?openform&mode=dialog&dialogid=PickList";
		_dlgUrl += "&dbpath=" + encodeURIComponent(targetdb);
		_dlgUrl += "&viewname=" + encodeURIComponent(viewname);
		_dlgUrl += "&singlecat=" + (category && category !== "" ? encodeURIComponent(category) : "");
		_dlgUrl += "&prompt=" + encodeURIComponent(promptmsg);

		var _result = "";

		var _dlgId = "PickList";

		var _dlg = window.top.Docova.Utils.createDialog({
				id: _dlgId,
				url: _dlgUrl,
				title: title,
				height: 400,
				width: 825,
				resizable: true,
				useiframe: true,
				sourcewindow: window,
				sourcedocument: document,
				onResize: function (event, ui) {
					var ifrm = window.top.$("#" + _dlgId + "IFrame")[0];
					if (ifrm) {
						var newwidth = ui.size.width - 20;
						var newheight = ui.size.height - 50;

						ifrm.width = newwidth;
						ifrm.height = newheight;

						var viewifrm = jQuery(ifrm).contents().find("#viewPickListIFrame");
						if (viewifrm) {
							viewifrm.height(newheight - 60);

							var divview = jQuery(viewifrm).contents().find("#divViewContent");
							if (divview) {
								divview.height(newheight - 60);
							}
						}
					}

				},
				onClose: function () {
					if (cb && typeof cb == "function") {
						cb(_result);
					}
				},
				buttons: {
					"OK": function () {
						_result = null;
						var found = false;
						var docdata = [];

						var $ifrm = window.top.$("#" + _dlgId + "IFrame");
						if ($ifrm) {
							var $viewifrm = $ifrm.contents().find("#viewPickListIFrame");
							if ($viewifrm) {
								var viewdocwin = $viewifrm[0].contentWindow;
								if (viewdocwin.objView) {
									var docids = viewdocwin.objView.selectedEntries;
									if (docids.length === 0 && viewdocwin.objView.currentEntry) {
										docids = [viewdocwin.objView.currentEntry];
									}
									if (docids.length === 0) {
										alert("Please select an entry from the list.");
									} else if (docids.length > 1 && !multiple) {
										alert("Please select a single entry.");
									} else {
										for (var x = 0; x < docids.length; x++) {
											var viewentry = viewdocwin.objView.GetEntryById(docids[x]);
											if (viewentry && viewentry.isRecord) {
												var coldata = viewentry.columnValues[(column > 0 ? column - 1 : 0)];
												if (coldata) {
													docdata.push(coldata);
												}
											}
										}
										_result = docdata.slice();

										found = true;
									}
								}

							}
						}
						if (found) {
							_dlg.closeDialog();
						}
					},
					"Cancel": function () {
						_result = null;
						_dlg.closeDialog();
					}
				}
			})
	} //--end picklistStrings

	/*-----------------
	 * Function: prompt
	 * Displays a prompt window
	 * Inputs: type - integer - one of the following;
	 * 				PROMPT_OK = 1
	 * 				PROMPT_YESNO = 2
	 *				PROMPT_OKCANCELEDIT = 3
	 *				PROMPT_OKCANCELLIST = 4
	 *				PROMPT_OKCANCELCOMBO = 5
	 *				PROMPT_OKCANCELEDITCOMBO = 6
	 *				PROMPT_OKCANCELLISTMULT = 7
	 *				PROMPT_PASSWORD = 10  -- not implemented
	 *				PROMPT_YESNOCANCEL = 11
	 *         title - string - title of prompt
	 *         promptmsg - string - message to display
	 *         defaultval - string - default value entered into editable window
	 *         values - string or array - value or list of values to edit or choose
	 *         callback - function - callback function that will be passed the chosen/edited value
	 * Returns: for types (1, 2, 3, 11) a value will be returned at the end of the function
	 *          or types (4, 5, 6, 7) the return value will be passed to the callback function if supplied
	 *-----------------*/
	this.prompt = function (type, title, promptmsg, defaultval, values, cb) {
		var result = false;
		var typetext = "";
		if (type === 1) {
			typetext = "OK";
		} else if (type === 2) {
			typetext = "YESNO";
		} else if (type === 3) {
			typetext = "OKCANCELEDIT";
		} else if (type === 4) {
			typetext = "OKCANCELLIST";
		} else if (type === 5) {
			typetext = "OKCANCELCOMBO";
		} else if (type === 6) {
			typetext = "OKCANCELEDITCOMBO";
		} else if (type === 7) {
			typetext = "OKCANCELLISTMULT";
		} else if (type === 11) {
			typetext = "YESNOCANCEL";
		}
		if (typetext !== "") {
			result = $$Prompt(typetext, title, promptmsg, defaultval, values, "", cb);
		}
		return result;
	} //-- end prompt

	/*-----------------
	 * Function: dialogBox
	 * Displays a dialog box
	 * Inputs: formName - string - name of form to use for dialog UI
	 *         autoHorzFit - boolean - (optional) - auto fit the dialog width to the form
	 *         autoVertFit - boolean - (optional) - auto fit the dialog height to the form
	 *         noCancel - boolean - (optional) -  hide the cancel button
	 *         noNewFields - boolean - (optional) - dont allow the addition of new fields
	 *         noFieldUpdate - boolean - (optional) - dont allow the updating of existing fields
	 *         readOnly - boolean - (optional) - display the form in read mode
	 *         title - string - (optional) - title to use for the dialog
	 *         sourceDoc - DocovaDocument - (optional) - document to use for source fields
	 *         sizeToTable - boolean - (optional) - size dialog to first table on form
	 *         noOkCancel - boolean - (optional) - hide Ok and Cancel buttons
	 *         okCancelAtBottom - boolean - (optional) - not implemented/ignored
	 *         cb - function - (optional) - callback function
	 *-----------------*/
	this.dialogBox = function (formName, autoHorzFit, autoVertFit, noCancel, noNewFields, noFieldUpdate, readOnly, title, sourceDoc, sizeToTable, noOkCancel, okCancelAtBottom, cb) {

		if (!formName || formName == "") {
			return false;
		}

		var profileDoc = null;
		if (sourceDoc) {
			var fieldlist = {};
			fieldlist = sourceDoc.getFields("*");
			if (fieldlist) {
				var appObj = this.getCurrentApplication();
				var curruser = (this.CurrentDocument ? this.CurrentDocument.username : (docInfo ? docInfo.UserName : ""));
				if (appObj.setProfileFields("tmpDialogBox", fieldlist, curruser)) {
					profileDoc = appObj.getProfileDocument("tmpDialogBox", curruser);
				}
			}
		}else if(readOnly && readOnly === true){
			var appObj = this.getCurrentApplication();
			var curruser = (this.CurrentDocument ? this.CurrentDocument.username : (docInfo ? docInfo.UserName : ""));
			if (appObj.setProfileFields("tmpDialogBox", {"tmpField" : ""}, curruser)) {
				profileDoc = appObj.getProfileDocument("tmpDialogBox", curruser);
			}			
		}

		var title = (title && title != "" ? title : "Dialog");
		var _dlgUrl = (docInfo.ServerUrl ? docInfo.ServerUrl : window.location.protocol + "://" + window.location.hostname);
		var temppath = (docInfo.NsfName ? docInfo.NsfName : window.location.pathname);
		_dlgUrl += (temppath.indexOf("/", temppath.length - 1) > -1 ? "" : "/") + temppath;
		_dlgUrl += (_dlgUrl.indexOf("/", _dlgUrl.length - 1) > -1 ? "" : "/");
		if (_DOCOVAEdition == "SE") {
			if (readOnly && readOnly === true) {
				_dlgUrl += 'wReadDocument/';
			} else {
				_dlgUrl += 'wViewForm/' + formName;
			}
			if (profileDoc && profileDoc.unid && profileDoc.unid !== "") {			
				_dlgUrl += (_dlgUrl.substr(-1) == '/' ? '' : '/') + profileDoc.unid;
			}
			_dlgUrl += "?AppID=" + docInfo.AppID + "&mode=dialog&dialogid=DialogBox";
			
			if (readOnly && readOnly === true && profileDoc && profileDoc.unid && profileDoc.unid !== "") {
				_dlgUrl += "&form=" + formName;
			}
			
		} else {
			_dlgUrl += formName + "?" + (readOnly && readOnly === true ? "readform" : "openform");
			if (profileDoc && profileDoc.unid && profileDoc.unid !== "") {
				_dlgUrl += "&ParentUNID=" + profileDoc.unid; ;
			}
		}
		var _dlgId = "DialogBox";
		var _result = false;

		var _container = this;

		var buttonObjects = {};
		if (!noOkCancel) {
			buttonObjects["OK"] = function () {

				if (noFieldUpdate) {
					_result = true;

					_dlg.closeDialog();
					return;
				}
				//do update
				var dialogDoc = _dlg.getUIDocument();
				var fldarr = [];
				//get all fields from the dialog doc
				var fieldlist = dialogDoc.getAllFields(true);
				//now write the vlaues back to the source document
				if (sourceDoc != null && typeof sourceDoc != "undefined" && sourceDoc.constructor_name == "DocovaDocument") {

					for (var key in fieldlist) {
						if (fieldlist.hasOwnProperty(key)) {
							if (key.substring(0, 1) != "%")
								sourceDoc.setField(key, fieldlist[key])
						}
					}
				} else {
					//update current ui document
					var curuidoc = _container.CurrentDocument;
					for (var key in fieldlist) {
						if (fieldlist.hasOwnProperty(key)) {
							if (key.substring(0, 1) != "%")
								curuidoc.setField(key, fieldlist[key])
						}
					}
				}

				_result = true;

				_dlg.closeDialog();
			}
		}
		if (!noCancel) {
			buttonObjects["Cancel"] = function () {
				_dlg.closeDialog();
			}
		}

		window.top.Docova.events.on("contentLoadedDialogBox", function (uidoc) {
			if (autoHorzFit || autoVertFit) {
				var ifrm = window.top.$("#" + _dlgId + "IFrame")[0];

				if(autoHorzFit){
					var wdth = 0;
					$("*", uidoc.htmldoc).each(function() {
						wdth = Math.max(wdth, parseInt($(this).width()));
					});
					$("#" + _dlgId, window.top.document).css("width", wdth + 20);
					if (ifrm) {
						ifrm.width = wdth;
					}
				}
				
				if(autoVertFit){
					var ht = $("#divDocPage", uidoc.htmldoc).height() + 20;
					$("#" + _dlgId, window.top.document).css("height", ht);
					if (ifrm) {
						ifrm.height = ht;
					}	
				}
			} else if (sizeToTable) {
				var docpgelem = $("#divDocPage", uidoc.htmldoc);
				var tblelem = $("table[elem=table]:first", docpgelem);
				if (tblelem.length > 0) {
					docpgelem.css("padding", 2);
					var ht = tblelem.height() + 20;
					var wdth = tblelem.width() + 20;
					$("#" + _dlgId, window.top.document).css("width", wdth).css("height", ht);
					var ifrm = window.top.$("#" + _dlgId + "IFrame")[0];
					if (ifrm) {
						ifrm.height = ht;
						ifrm.width = wdth;
					}
				}
			}
			if (!uidoc.isPostRefresh) {
				var callrefresh = false;
				if (sourceDoc != null && typeof sourceDoc != "undefined" && sourceDoc.constructor_name == "DocovaDocument") {
					var fldarr = [];
					//get all fields from the dialog doc
					var fieldlist = uidoc.getAllFields(true);
					for (var key in fieldlist) {
						if (fieldlist.hasOwnProperty(key)) {
							fldarr.push(key);
						}
					}
					//get the value of fields in the dialog doc from the
					//passed in docova document
					if (fldarr.length > 0) {
						var valarr = sourceDoc.getFields(fldarr)
							for (var jobj in valarr) {
								uidoc.setField({
									"field": jobj,
									"value": valarr[jobj]
								});
								callrefresh = true;
							}
					}
				} else {
					//move values from current Doc
					var curuidoc = _container.CurrentDocument;

					var fieldlist = curuidoc.getAllFields(true);

					for (var key in fieldlist) {
						if (fieldlist.hasOwnProperty(key)) {
							var sourceval = fieldlist[key];
							uidoc.setField({
								"field": key,
								"value": sourceval
							});
							callrefresh = true;
						}
					}
				}
				if (callrefresh) {
					uidoc.refresh();
				}
			}
		});

		var _dlg = window.top.Docova.Utils.createDialog({
				id: _dlgId,
				url: _dlgUrl,
				title: title,
				height: "auto",
				width: (autoHorzFit === true || sizeToTable === true ? "auto" : 600),
				resizable: true,
				useiframe: true,
				sourcewindow: window,
				sourcedocument: document,
				onResize: function (event, ui) {
					var ifrm = window.top.$("#" + _dlgId + "IFrame")[0];
					if (ifrm) {
						var newwidth = ui.size.width - 20;
						var newheight = ui.size.height - 50;

						ifrm.width = newwidth;
						ifrm.height = newheight;
					}

				},
				onClose: function () {

					window.top.Docova.events.removeTrigger("contentLoadedDialogBox");

					if (cb && typeof cb == "function") {
						cb(_result);
					}
				},
				buttons: buttonObjects
			})

	} //--end dialogBox


	/*-----------------
	 * Function: viewRefresh
	 * Refreshes the current UI view
	 *-----------------*/
	this.viewRefresh = function () {
		var uiview = Docova.getUIView();
		if (uiview) {
			uiview.refresh();
		}
	} //--end viewRefresh


	/*----------------------------------------------------------------------------------
	 * Constructor
	 *---------------------------------------------------------------------------------*/
	_currentDOM = curdoc;
	_currentUIDoc = this._getUIDocument();

} //end DocovaUIWorkspace class


/*
 * Class: docova
 * helper class used to initialize a docova object that contains
 * references to utils, workspace, and globalstorage objects
 * Properties:
 * Utils: DocovaUtils
 * GlobalStorage: object array containing global values
 **/
function docova() {
	this.Utils = new DocovaUtils();

	if (window.top.Docova && window.top.Docova.GlobalStorage) {
		this.GlobalStorage = window.top.Docova.GlobalStorage;
	} else {
		this.GlobalStorage = {};
	}

	this.events = new DocovaEvents();

	//variable used to store status of plugin
	this.IsPluginAlive = (window.top.Docova ? window.top.Docova.IsPluginAlive : false);

	/*----------------------------------------------------------------------------------------------
	 * Function: checkPluginAlive
	 * Method to help determine if plugin is running or not
	 * Parameters:
	 *      onSuccess: function to call on successful communication with the plugin
	 *      onFailure: function to call when plugin is not running
	 * Returns:
	 *      none
	-------------------------------------------------------------------------------------------------*/
	this.checkPluginAlive = function (options) {
		var defaultOptns = {
			onSuccess: function () {},
			onFailure: function () {}
		};

		var opts = $.extend({}, defaultOptns, options);

		var sessionCookie = this.Utils.getSessionCookie();
		if (!sessionCookie) {
			//--if no session cookie can be found then plugin won't be able to function so same as not alive
			try {
				opts.onFailure();
			} catch (e) {}
			return;
		}
		var surl = getPluginURL();
		surl += "action=isAlive&silent=true";

		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				try {
					opts.onSuccess();
				} catch (e) {}
			}
		}).fail(function (xhr, status, error) {
			try {
				opts.onFailure();
			} catch (e) {}
		});
	} //--end checkPluginAlive

	/*----------------------------------------------------------------------------------------------
	 * Function: getApplication
	 * Initialize a new DocovaApplication object.
	 * Parameters:
	 *      options: value pair object consisting of one of the following
	 *            appid: string - id of application
	 *            appname: string - name of application
	 *            filepath: string - path to application
	 *          if no lookup option passed, the function will attempt to use the currently open app
	 * Returns:
	 *      DocovaApplication object
	-------------------------------------------------------------------------------------------------*/
	this.getApplication = function (options) {
		return new DocovaApplication(options);
	} //--end getApplication


	/*----------------------------------------------------------------------------------------------
	 * Function: getUIWorkspace
	 * Initialize a new DocovaUIWorkspace object
	 * Parameters:
	 *      curdoc: current HTML DOM object
	 * Returns:
	 *      DocovaUIWorkspace object
	-------------------------------------------------------------------------------------------------*/
	this.getUIWorkspace = function (curdoc) {
		return new DocovaUIWorkspace(curdoc);
	} //--end getUIWorkspace


	/*----------------------------------------------------------------------------------------------
	 * Function: getUIDialogDocument
	 * Finds the Dialog given an id and returns a DocovaUIDocument object for the dialog
	 * Parameters:
	 *      id: id of the Dialog ( can be formname, pagename, or unid )
	 * Returns:
	 *      DocovaUIDocument object
	-------------------------------------------------------------------------------------------------*/
	this.getUIDialogDocument = function (id) {
		var dialogobj = Docova.GlobalStorage["obj" + id];
		if (dialogobj)
			return dialogobj.getUIDocument();
	} //--end getUIDialogDocument

	/*----------------------------------------------------------------------------------------------
	 * Function: getUIDialog
	 * Finds the Dialog given an id and returns the Dialog oject
	 * Parameters:
	 *      id: id of the Dialog ( can be formname, pagename, or unid )
	 * Returns:
	 *      Dialog object
	-------------------------------------------------------------------------------------------------*/
	this.getUIDialog = function (id) {

		var dialogobj = Docova.GlobalStorage["objdiv" + id];
		if (dialogobj)
			return dialogobj;
	} //--end getUIDialog

	/*------------------------------------------
	 * Function: getUIView
	 * Iniitalize a new DocovaUIView object
	 * Paramters:
	 * Returns:
	 *      DocovaUIView object
	--------------------------------------------*/
	this.getUIView = function (options) {
		var result = null;

		if (typeof window === 'undefined' || window === null) {
			return result; //strange case - shouldnt occur but seems to happen when page is unloaded
		}

		var defaultOptns = {
			framename: ""
		};
		var opts = $.extend({}, defaultOptns, options);
		var win = window;
		if (opts.framename == "") {
			if (typeof win.Docova_Self_UIview != "undefined") {
				result = win.Docova_Self_UIview;
			} else {
				var tview = new DocovaUIView();
				if (tview.viewobj === null) {
					tview = null;
				}
				win.Docova_Self_UIview = tview;
				result = tview;
			}
		}
		
		if(result === null){
			var ws = Docova.getUIWorkspace(document);
			var appid = ws.getCurrentAppId();
			var appFrameId = "fra" + appid;
			var appFrameDoc = ws.getDocovaFrame(appFrameId, "document");
			if(opts.framename != ""){
				var mainFrame = this.getFrame(appFrameDoc, opts.framename);
				if(typeof mainFrame != "undefined" && mainFrame !== null){
					var mainFrameDoc = mainFrame[0].contentWindow.document;
					var frameObj = this.getFrame(mainFrameDoc, "appViewMain");
				}
			}else{
				var frameObj = this.getFrame(appFrameDoc, "appViewMain");
			}

			if (typeof frameObj == "undefined" || frameObj == null) {
				result = null;
			} else {
				result = $(frameObj)[0].contentWindow.Docova.getUIView();
			}
		}
		
		return result;
	} //--end getUIView

	/*------------------------------------------
	 * Function: getUIDocument
	 * Initalize a new DocovaUIDocument object
	 * Parameters:
	 *     options - value pair array consisting of
	 *         framename - string (optional)
	 * Returns:
	 *      DocovaUIDocument object
	--------------------------------------------*/
	this.getUIDocument = function (options) {
		var defaultOptns = {
			framename: ""
		};

		var opts = $.extend({}, defaultOptns, options);

		//if we are getting this from an embedded view window, then use the parent
		//to get to the actual UI Document

		var win = window;
		if (opts.framename == "") {
			if (typeof win.Docova_Self_UIdoc != "undefined") {
				return win.Docova_Self_UIdoc
			} else {
				if (typeof info != "undefined" && info !== null && typeof info.isUIDoc != "undefined" && info.isUIDoc) {
					var ws = Docova.getUIWorkspace(document);
					var tdoc = new DocovaUIDocument(ws);
					win.Docova_Self_UIdoc = tdoc;
					return tdoc;
				} else {
					return null;
				}
			}
		} else {
			var ws = Docova.getUIWorkspace(document);
			var appid = ws.getCurrentAppId();
			var appFrameId = "fra" + appid;
			var appFrameDoc = ws.getDocovaFrame(appFrameId, "document");
			var frameObj = this.getFrame(appFrameDoc, opts.framename)
				if (frameObj == "undefined" || frameObj == null) {
					return null;
				} else {
					return $(frameObj)[0].contentWindow.Docova.getUIDocument();
				}
		}
	} //--end getUIDocument

	/*----------------------------------------
	 * Function: getFrame
	 * Finds a frame object.
	 * An alias for method in UIWorkspace
	 * Returns: the found frame object
	 *----------------------------------------*/
	this.getFrame = function (doc, framename) {
		var uiw = this.getUIWorkspace(document);
		var frameObj = uiw.getFrame(doc, framename);

		return frameObj;
	} //--end getFrame


	/*-------------------------------------------
	 * Function: compose
	 * Method to compose a new document.
	 * Alias for method in UIWorkspace
	--------------------------------------------*/
	this.compose = function (options) {
		var uiw = this.getUIWorkspace(document);
		uiw.compose(options);
	} //--end compose

	/*
	 * Function: fullscreen
	 * Toggles DOCOVA applications in/out of fullscreen mode
	 */
	this.fullscreen = function () {
		var $mainFrameset = jQuery("#iFrameMain", window.top.document).contents().find("#fsMain");
		var rowsArray = $mainFrameset.attr("rows").split(",")
			if ($.trim(rowsArray[0]) == "0") {
				$mainFrameset.attr("rows", "70,0,*")
			} else {
				$mainFrameset.attr("rows", "0,0,*")
			}
	} //--end fullscreen

} //--end docova class

/*
Class: DocovaExtensions
Helper class used to initialize a DocovaExtensions object that contains
methods for interacting with the DOCOVA desktop plugin for use in
performing desktop client side processes
 */
function DocovaExtensions() {

	/*----------------------------------------------------------------------------------------------
	 * Function: deleteFile
	 * Deletes a local file from the filesystem
	 * Parameters:
	 *      filepath: full path to the file to be deleted
	 * Returns:
	 *      true if the deletion is successful
	-------------------------------------------------------------------------------------------------*/
	this.deleteFile = function (filepath) {
		var surl = getPluginURL();
		surl += "action=deleteLocalFile";
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
			alert("error" + xhr.responseText);
		});

		return retval;
	} //--end deleteFile

	/*----------------------------------------------------------------------------------------------
	 * Function: deleteFolder
	 * Deletes a temporary folder from the local file system
	 * Parameters:
	 *      folderpath: full path to the folder to be deleted
	 *          (must be a sub directory of the system temporary folder)
	 * Returns:
	 *      true if the deletion is successful
	-------------------------------------------------------------------------------------------------*/
	this.deleteFolder = function (folderpath) {
		var result = false;

		var codestr = "";
		codestr += 'string result = @"false";\n'
		codestr += 'string deleteDir = @"' + folderpath + '";\n';
		codestr += 'string tempDir = System.IO.Path.GetTempPath();\n';
		codestr += 'if(deleteDir.ToLower().StartsWith(tempDir.ToLower())){\n';
		codestr += '  if(System.IO.Directory.Exists(deleteDir)){\n';
		codestr += '      string[] allFileNames = System.IO.Directory.GetFiles(deleteDir, "*.*", System.IO.SearchOption.AllDirectories);\n';
		codestr += '      foreach (string filename in allFileNames){\n';
		codestr += '          FileAttributes attr = File.GetAttributes(filename);\n';
		codestr += '          File.SetAttributes(filename, attr & ~FileAttributes.ReadOnly);\n';
		codestr += '      }\n';
		codestr += '     System.IO.Directory.Delete(deleteDir, true);\n';
		codestr += '     if(! System.IO.Directory.Exists(deleteDir)){\n';
		codestr += '         result = @"true";\n';
		codestr += '     }\n';
		codestr += '  }else{\n';
		codestr += '     result = @"true";\n';
		codestr += '  }\n';
		codestr += '}\n';
		codestr += 'return result;\n';

		var retval = this.executeCode(codestr, false, true);
		if (retval.status == "SUCCESS") {
			if (retval.results == "true") {
				result = true;
			};
		}

		return result;
	} //--end deleteFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: ConvertToPDF
	 * Convertsa file to a pdf using PDFCreator.  PDFCreator has to be installed on the machine.
	 * Parameters:
	 *      localfilename: full path to the file to be converted to pdf
	 *      preventprint : makes the pdf non pritable
	 *      password: password for the pdf filie
	 * Returns:
	 *      string : full path to the generated pdf file
	-------------------------------------------------------------------------------------------------*/
	this.ConvertToPDF = function (localfilename, preventprint, password) {
		var surl = getPluginURL();
		surl += "action=convertToPDF";
		surl += "&localfilename=" + encodeURIComponent(localfilename);
		surl += "&preventprint=" + preventprint;
		surl += "&password=" + encodeURIComponent(password);

		var retval = "";
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				retval = atobEx(data.filename);
			}
		})
		.fail(function (xhr, status, error) {
			alert("error" + xhr.responseText);
		});

		return retval;
	} //--end ConvertToPDF


	/*----------------------------------------------------------------------------------------------
	 * Function: isPDFCreatorInstalled
	 * Checks to see if PDFCreator is installed on the local machine
	 * Parameters:
	 *
	 * Returns:
	 *      true if PDFCreator is installed
	 *      false if PDFCreator is not installed
	-------------------------------------------------------------------------------------------------*/
	this.isPDFCreatorInstalled = function () {
		var surl = getPluginURL();
		surl += "action=isPDFCreatorInstalled";
		var retval = false;
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				retval = true;

			}

		})
		.fail(function (xhr, status, error) {
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end isPDFCreatorInstalled

	/*----------------------------------------------------------------------------------------------
	 * Function: LaunchFile
	 * Launches a file with its associated application.
	 * Parameters:
	 *      localfilename: path to the file to be launched
	 * Returns:
	 *      true if the launch is successful
	 *      false if the launch is un-successful
	-------------------------------------------------------------------------------------------------*/
	this.LaunchFile = function (localfilename) {
		var surl = getPluginURL();
		surl += "action=LaunchFile";
		surl += "&launchpath=" + encodeURIComponent(localfilename);
		var retval = false;
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				retval = true;

			}

		})
		.fail(function (xhr, status, error) {
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end LaunchFile

	/*----------------------------------------------------------------------------------------------
	 * Function: getTemporaryFolder
	 * Returns a path to the default system temporary folder
	 * Returns:
	 *      string - path to the system temporary folder
	 *-------------------------------------------------------------------------------------------------*/
	this.getTemporaryFolder = function () {
		var result = "";

		var codestr = "";
		codestr += 'string tempDir = System.IO.Path.GetTempPath();\n';
		codestr += 'return tempDir;\n';

		var retval = this.executeCode(codestr, false, true);
		if (retval.status == "SUCCESS") {
			result = retval.results;
		}

		return result;
	} //--end getTemporaryFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: CreateTempFolder
	 * Creates a temporary sub directory beneath the system temporary folder and returns the path
	 * Returns:
	 *      string - path to the new temporary folder
	 *-------------------------------------------------------------------------------------------------*/
	this.CreateTempFolder = function () {
		var result = "";

		var codestr = "";
		codestr += 'string tempDir = System.IO.Path.Combine(System.IO.Path.GetTempPath(), System.IO.Path.GetRandomFileName());\n';
		codestr += 'try{\n';
		codestr += '  Directory.CreateDirectory(tempDir);\n';
		codestr += '}catch(Exception e){\n';
		codestr += '  tempDir = @"";\n';
		codestr += '}\n';
		codestr += 'return tempDir;\n';

		var retval = this.executeCode(codestr, false, true);
		if (retval.status == "SUCCESS") {
			result = retval.results;
		} else {
			alert(retval.error);
		}

		return result;
	} //--end CreateTempFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: MyDocumentsFolder
	 * Returns a path to the My Documents folder.
	 * Parameters:
	 *
	 * Returns:
	 *      string : path to the mydocuments folder
	-------------------------------------------------------------------------------------------------*/
	this.MyDocumentsFolder = function () {
		var surl = getPluginURL();
		surl += "action=resolvePathNames";
		surl += "&downloadpath=1";
		var retval = "";
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				if (data.downloadpath)
					retval = atobEx(data.downloadpath);

			}

		})
		.fail(function (xhr, status, error) {
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;

	} //--end MyDocumentsFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: DownloadFileFromURL
	 * Downloads file from the specified url to a local file
	 * Parameters:
	 *          url: String url to the specified file
	 *          localfilename: String
	 *          silent: boolean (optional) - true to disable failure prompt
	 * Returns:
	 *      string - path to the saved file
	-------------------------------------------------------------------------------------------------*/
	this.DownloadFileFromURL = function (url, localfilename, silent) {

		var sessionCookie = Docova.Utils.getSessionCookie();
		if (!sessionCookie) {
			return;
		}
		var fileurl = url;
		var surl = getPluginURL();
		surl += "action=downloadFile";

		surl += "&fileurl=" + encodeURIComponent(fileurl);
		surl += "&sessioncookiename=" + sessionCookie.cookiename;
		surl += "&sessioncookie=" + sessionCookie.cookieval;
		surl += "&localfilename=" + encodeURIComponent(localfilename);
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
			if (silent == undefined || silent === false) {
				alert("Error! Unable to communicate with Docova plugin.");
			}
		});

		return downpath
	} //--end DownloadFileFromURL

	/*----------------------------------------------------------------------------------------------
	 * Function: SelectFolder
	 * Allows users to select a folder from the operating system
	 * Parameters:
	 *          titl: String title to show in the select folder dialog
	 *          onSuccess: function to call when the folder has been selected
	 *          onFailure: function to call when nothing is selected or there is an error
	 * Returns:
	 *      onSuccess and onFailure functions
	-------------------------------------------------------------------------------------------------*/
	this.SelectFolder = function (options) {
		var defaultOptns = {
			title: "Select a folder",
			onSuccess: function () {},
			onFailure: function () {}
		};

		var opts = $.extend({}, defaultOptns, options);

		var surl = getPluginURL();
		surl += "action=selectFolder";
		surl += "&title=" + encodeURIComponent(opts.title);
		$.ajax({
			url: surl,
			type: "POST",
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				opts.onSuccess.call(this, atobEx(data.foldername));
			}
		}).fail(function (xhr, status, error) {
			opts.onFailure();

		});
	} //--end SelectFolder

	/*----------------------------------------------------------------------------------------------
	 * Function: LocalFileExists
	 * Checks if the specified file exits given the full path to a file
	 * Parameters:
	 *          filepath: String full path to the file
	 *
	 * Returns:
	 *      boolean - if file is found or not
	-------------------------------------------------------------------------------------------------*/
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
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end LocalFileExists

	/*----------------------------------------------------------------------------------------------
	 * Function: importFolderTree
	 * Calls the plugin to import files and folders
	 * Parameters:
	 * 			RootFolder: UNID of the parentFolder
	 *
	 * Returns:
	 * 		boolean - if file is found or not
	-------------------------------------------------------------------------------------------------*/
	this.ImportFolderTree = function (options) {

		var defaultOptns = {
			RootFolder: "",
			onSuccess: function () {},
			onFailure: function () {},
			onDone: function () {},
			LibraryUrl: "",
			ParentFolderUNID: "",
			IncludeSubfolders: true,
			FolderPathLimit: "",
			FolderPath: "",
			DocumentType: "ATTACHMENTHOLDER"

		};
		var opts = $.extend({}, defaultOptns, options);

		var surl = getPluginURL();
		surl += "action=uploadFolderTree";
		var cookieObj = window.top.Docova.Utils.getSessionCookie();
		if (!cookieObj) {
			window.top.Docova.Utils.messageBox({
				'icontype': 1,
				'msgboxtype': 0,
				'prompt': "Error: Unable to read current session cookie value. Unable to submit with plugin.",
				'title': "Error Saving"
			});
			return 0;
		}
		surl += "&sessioncookiename=" + cookieObj.cookiename;
		surl += "&startfolder=" + encodeURIComponent(opts.RootFolder);
		surl += "&sessioncookie=" + cookieObj.cookieval;
		surl += "&nsfpath=" + encodeURIComponent(opts.LibraryUrl);
		surl += "&incsubfolders=" + opts.IncludeSubfolders;
		surl += "&defdoctype=" + opts.DocumentType;
		surl += "&folderpath=" + encodeURIComponent(opts.FolderPath);
		surl += "&folderlimit=" + opts.FolderPathLimit;
		surl += "&parentUNID=" + opts.ParentFolderUNID;
		var retval = false;

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

			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				retval = true;
				errorstr = "<b>Finished importing folder!</b>";
				opts.onSuccess.call(data);
			} else if (data.status == "FAIL" && data.reason == "CANCELLED") {
				errorstr = "Folder import has been cancelled!";
				retval = false;
				opts.onFailure.call(data);
			} else if (data.status == "FAIL") {
				if (data.files) {
					var errorstr = "<b>Error importing the following files: </b><br><br>";
					for (var p = 0; p < data.files.length; p++) {
						var obj = data.files[p];
						var flname = atob(data.files[p].fullname);
						var reason = atob(data.files[p].error)
							errorstr += flname + ":" + reason + "<br>"
					}

				} else {
					errorstr = "Folder Import Failed! " + data.reason
				}
				opts.onFailure.call(data);
			}
			$("#dlgFolderImportError", window.top.document).parent().show()
			$("#dlgFolderImportErrorText", window.top.document).html(errorstr);

		})
		.fail(function (xhr, status, error) {
			$("#dlgFolderImportError", window.top.document).parent().show()
			$("#dlgFolderImportErrorText", window.top.document).html("<b>Error Communicating with Docova plugin!</b>");

		});

	} //--end ImportFolderTree

	/*----------------------------------------------------------------------------------------------
	 * Function: LocalFolderExists
	 * Checks if the specified folder exits given the full path to it
	 * Parameters:
	 *          folderpath: String full path to the folder
	 *
	 * Returns:
	 *      boolean - if folder is found or not
	-------------------------------------------------------------------------------------------------*/
	this.LocalFolderExists = function (folderpath) {
		var surl = getPluginURL();
		surl += "action=localFolderExists";
		surl += "&filepath=" + encodeURIComponent(folderpath);
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
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end LocalFolderExists


	/*----------------------------------------------------------------------------------------------
	 * Function: printFiles
	 * Prints the files listed in the filelist parameter.  Files are to be separated by "*"
	 * The function launches the select printer dialog to allow the user to select the printer to use.
	 * Parameters:
	 *          filelist: "*" delimited list of files to print.  Must be full paths
	 *          onSuccess: function to call when all files have been sent to the printer
	 *          onFailure: function to call if the files did not print sucessfully
	 *          onCancel: function to call if the file print is cancelled by the user
	 * Returns:
	 *      function: onSuccess  - function to call on successfual print of the files provided
	 *      function: onFailure - function to call if there is an error in printing the files.
	-------------------------------------------------------------------------------------------------*/
	this.printFiles = function (options) {

		var defaultOptns = {
			filelist: "",
			onSuccess: function () {},
			onFailure: function () {},
			onCancel: function () {}
		};

		var opts = $.extend({}, defaultOptns, options);

		var surl = getPluginURL();
		surl += "action=printFiles";

		var mystr = opts.filelist;
		var sdata = {
			filelist: btoa(encodeURIComponent(mystr))
		};
		$.ajax({
			url: surl,
			type: "POST",
			data: sdata,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				opts.onSuccess();
			} else if (data.status == "CANCELLED") {
				opts.onCancel();
			} else {
				opts.onFailure();
			}
		})
		.fail(function () {
			opts.onFailure();
		});
	} //--end printFiles


	/*----------------------------------------------------------------------------------------------
	 * Function: executeCode
	 * Helper function for Excecuting Csharp code on the local machine
	 * code must return a string.
	 * Parameters:
	 *      code: String. Csharp code
	 *      keepgeneratedcsfiles :Boolean. Default is false.  keeps the compiler generated temp files around to assist in debugging the code
	 *      failsilent: Boolean.  Default is true.  Set to false to allow the code to show the error dialogs to the user
	 * Returns:
	 *  returnvalue object from the execution of the code.
	 *      returnvalue.status : String. status of the operation "SUCCESS" for successful completion
	 *      returnvalue.results : String. the string value returned from the C# function
	 *      returnvalue.error : String. the error text.
	 *-------------------------------------------------------------------------------------------------*/
	this.executeCode = function (code, keepgeneratedcsfiles, failsilent) {
		var surl = getPluginURL();
		surl += "action=execCode";
		var silent = failsilent ? failsilent : true;
		if (silent)
			surl += "&silent=true";

		if (keepgeneratedcsfiles)
			surl += "&mode=debug";
		var retval = {
			"status": "FAILED",
			"results": "",
			"error": ""
		};
		var mystr = code;
		var sdata = {
			code: btoa(encodeURIComponent(mystr))
		};

		$.ajax({
			url: surl,
			type: "POST",
			data: sdata,
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				retval.status = data.status;
				if (data.results) {
					var org = atob(data.results)
						retval.results = org;
				}
			} else {
				retval.status = data.status;
				if (data.error) {
					retval.error = atobEx(data.error);
				}
			}
		})
		.fail(function () {});
		return retval;
	} //--end excecuteCode

	/*----------------------------------------------------------------------------------------------
	 * Function: GetCBData
	 * Retrieves data from clipboard in specified format
	 * Parameters:
	 *      datatype: String - one of text, html, rtf, or bmp
	 * Returns:
	 *      string - clipboard data in string format or empty string
	 *               for bmp datatype image data is returned base64encoded
	 *-------------------------------------------------------------------------------------------------*/
	this.GetCBData = function (datatype) {
		var result = "";

		var dataformat = "";
		if (datatype.toLowerCase() == "html") {
			dataformat = "Html";
		} else if (datatype.toLowerCase() == "rtf") {
			dataformat = "Rtf";
		} else if (datatype.toLowerCase() == "bmp") {
			dataformat = "Bitmap";
		} else {
			dataformat = "Text";
		}

		mystr = 'string clipData = "";';
		mystr += 'string dataformat = "' + dataformat + '";';
		mystr += 'TextDataFormat txtDataFormat = TextDataFormat.Text;';
		mystr += 'if(dataformat == "Html"){';
		mystr += '     txtDataFormat = TextDataFormat.Html;';
		mystr += '}else if(dataformat == "Rtf"){';
		mystr += '     txtDataFormat = TextDataFormat.Rtf;';
		mystr += '}';
		mystr += 'bool isDataOnClipboard = Clipboard.ContainsData(dataformat);';
		mystr += 'if(isDataOnClipboard){';
		mystr += '   if(dataformat == "Bitmap"){';
		mystr += '      System.Drawing.Image clipboardImage = Clipboard.GetImage();';
		mystr += '      System.IO.MemoryStream ms = new System.IO.MemoryStream();';
		mystr += '      clipboardImage.Save(ms, System.Drawing.Imaging.ImageFormat.Bmp);';
		mystr += '      clipData = Convert.ToBase64String(ms.ToArray());';
		mystr += '   }else{';
		mystr += '     clipData = Clipboard.GetText(txtDataFormat);';
		mystr += '   }';
		mystr += '}';
		mystr += 'return clipData;';

		var retval = this.executeCode(mystr, false, true);
		if (retval.status == "SUCCESS") {
			result = retval.results;
		} else {
			alert(retval.error);
		}

		return result;
	} //--end GetCBData

	/*----------------------------------------------------------------------------------------------
	 * Function: SetCBData
	 * Stores text to clipboard in specified format
	 * Parameters:
	 *      datatype: String - one of text, html, rtf
	 *      data: String - string data to transfer to clipboard
	 *      (any string literal characters such as \ must be escaped eg. \\ )
	 * Returns:
	 *  boolean - true if code was copied to clipboard, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.SetCBData = function (datatype, data) {
		var result = false;

		var senddata = data;

		var dataformat = "";
		if (datatype.toLowerCase() == "html") {
			dataformat = "Html";

			var htmldata = "Version:0.9\r\n";
			htmldata += "StartHTML:<====1\r\n";
			htmldata += "EndHTML:<====2\r\n";
			htmldata += "StartFragment:<====3\r\n";
			htmldata += "EndFragment:<====4\r\n";
			htmldata += "SourceURL:file:///C:/temp/test.htm\r\n";
			htmldata += "<HTML>\r\n"
			htmldata += "<head>\r\n";
			htmldata += "<title>HTML clipboard</title>\r\n";
			htmldata += "</head>\r\n";
			htmldata += "<body>\r\n";
			htmldata += "<!--StartFragment-->";
			htmldata += data;
			htmldata += "<!--EndFragment-->\r\n";
			htmldata += "</body>\r\n";
			htmldata += "</html>\r\n";

			var param = "000000" + htmldata.search("<HTML>").toString();
			param = param.slice(-6);
			htmldata = htmldata.replace("<====1", param);

			var param = "000000" + htmldata.length.toString();
			param = param.slice(-6);
			htmldata = htmldata.replace("<====2", param);

			var param = htmldata.search("<!--StartFragment-->") + 20;
			param = "000000" + param.toString();
			param = param.slice(-6);
			htmldata = htmldata.replace("<====3", param);

			var param = "000000" + htmldata.search("<!--EndFragment-->").toString();
			param = param.slice(-6);
			htmldata = htmldata.replace("<====4", param);

			senddata = htmldata;
		} else if (datatype.toLowerCase() == "rtf") {
			dataformat = "Rtf";
		} else {
			dataformat = "Text";
		}

		senddata = senddata.replace(/"/g, '""');

		var mystr = 'string res = "false";';
		mystr += 'string cbdata = @"' + senddata + '";';
		mystr += 'Clipboard.SetData(DataFormats.' + dataformat + ', cbdata);';
		mystr += 'res = "true";';
		mystr += 'return res;';

		var retval = this.executeCode(mystr, false, true);
		if (retval.status == "SUCCESS") {
			result = true;
		} else {
			alert(retval.error);
		}

		return result;
	} //--end SetCBData

	/*----------------------------------------------------------------------------------------------
	 * Function: ProtectPDF
	 * Password protects a pdf file and optionally restricts copying and printing of the file
	 * Parameters:
	 *      sourceFilePath: String - file path of the source pdf
	 *      destinationFilePath: String - file path of the output pdf
	 *      userPassword: String - user password to restrict opening of the file
	 *      ownerPassword: String - owner password to lock copy and print restrictions
	 *      allowcopying: Boolean - whether to allow copying of the file contents
	 *      allowprinting: Boolean - whether to allow printing of the file
	 * Returns:
	 *  boolean - true if pdf was protected, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.ProtectPDF = function (sourceFilePath, destinationFilePath, userPassword, ownerPassword, allowcopying, allowprinting) {
		var surl = getPluginURL();

		var sallowcopying = "0";
		var sallowprinting = "0";

		if (allowcopying)
			sallowcopying = "1";
		if (allowprinting)
			sallowprinting = "1";

		surl += "action=protectPDF";
		surl += "&sourceFile=" + encodeURIComponent(sourceFilePath);
		surl += "&destinationFile=" + encodeURIComponent(destinationFilePath);
		surl += "&userpassword=" + encodeURIComponent(userPassword);
		surl += "&ownerpassword=" + encodeURIComponent(ownerPassword);
		surl += "&allowcopying=" + sallowcopying;
		surl += "&allowprinting=" + sallowprinting;

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
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end ProtectPDF

	/*----------------------------------------------------------------------------------------------
	 * Function: ImageToPDF
	 * Converts an image file to a PDF file
	 * Parameters:
	 *      sourceFilePath: String - file path of the source image file
	 *      destinationFilePath: String - file path of the output pdf
	 *      rotate: String - rotation degrees to rotate the image
	 * Returns:
	 *  boolean - true if image was converted to pdf, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.ImageToPDF = function (sourceFilePath, destinationFilePath, rotate) {
		var surl = getPluginURL();

		surl += "action=imageToPDF";
		surl += "&sourceFile=" + encodeURIComponent(sourceFilePath);
		surl += "&destinationFile=" + encodeURIComponent(destinationFilePath);
		surl += "&rotate=" + rotate;

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
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end ImageToPDF

	/*----------------------------------------------------------------------------------------------
	 * Function: getRegistryValue
	 * Retrieves a registry key value
	 * Parameters:
	 *      root: String - root registry hive
	 *      subkey: String - sub registry key within the chosen hive
	 *      value: String - registry entry to retrieve
	 * Returns:
	 *  String - value of registry entry or empty string
	 *-------------------------------------------------------------------------------------------------*/
	this.getRegistryValue = function (root, subkey, value) {
		var surl = getPluginURL();

		surl += "action=getRegistryValue";
		surl += "&root=" + root;
		surl += "&subkey=" + subkey;
		surl += "&value=" + value;

		var retval = "";
		$.ajax({
			url: surl,
			type: "POST",
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {

				retval = atobEx(data.result);

			}
		})
		.fail(function (xhr, status, error) {
			alert("Error! Unable to communicate with Docova plugin.");
		});

		return retval;
	} //--end getRegistryValue


	/*----------------------------------------------------------------------------------------------
	 * Function: DeleteWordMergeFile
	 * Deletes the word merge file for a given document.
	 * Parameters:
	 *     	filename: String - name of file to delete
	 *		docid: String - ID of the document
	 *		uploader: Optional - String - ID of uploader to which this file belongs
	 * Returns:
	 *  boolean - true if the merge file was deleted, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.DeleteWordMergeFile = function (filename, docid, uploadername) {
		var surl = "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";

		var request = "<Request>";
		request += "<Action>DeleteWordMergeFile</Action>";
		request += "<docid>" + docid + "</docid>";
		request += "<AppID>" + docInfo.AppID + "</AppID>";
		request += "<filename>" + filename + "</filename>";
		request += "</Request>";

		var res = false;
		$.ajax({
			url: surl,
			type: "POST",
			data: encodeURI(request),
			async: false,
			dataType: "xml"
		}).done(function (data) {
			var xmlobj = $(data);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				res = true;
				var uidoc = Docova.getUIDocument();
				var up = uidoc.getAttachmentsObj(uploadername);
				if (up.uploader && typeof up.uploader != "undefiend") {
					if (up.uploader.IsFileAttached(filename)) {
						up.uploader.RemoveFileFromDisplay(filename);
					}
				}
			}
		})
		.fail(function () {});
		return res;

	} //--end DeleteWordMergeFile


	/*----------------------------------------------------------------------------------------------
	 * Function: MergeWordFilesWithJSONData
	 * Merges data from a document with a word template.  Will user the json object to map data on the template
	 * identified as {field_name}
	 * Parameters:
	 *      templatename: String - Name of template stored admin/templates
	 *		docid: String - ID of the document this file is related to
	 *		newfilename: String - Name of the file to use after the merge
	 *		attachtodoc: String (optional) - "1" to attach the merged file to the document
	 *		uploadername: String (optional) - Name of the uploader to attach the file to.
	 *		dataobj : jsondata - Data in the format {"field_name" : "data", "field_name" : "data"}
	 * Returns:
	 *  boolean - true if files were merged, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.MergeWordFilesWithJSONData = function (templatename, docid, newfilename, attachtodoc, uploadername, dataobj) {
		var surl = "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";

		var sattachtodoc = attachtodoc && attachtodoc != "" ? attachtodoc : "0";
		var suploadername = uploadername && uploadername != "" ? uploadername : "";
		var odataobj = dataobj ? dataobj : {};

		var request = "<Request>";
		request += "<Action>MergeWordFilesWithJSONData</Action>";
		request += "<templatename>" + templatename + "</templatename>";
		request += "<docid>" + docid + "</docid>";
		request += "<AppID>" + docInfo.AppID + "</AppID>";
		request += "<filename>" + newfilename + "</filename>";
		request += "<attachtodoc>" + sattachtodoc + "</attachtodoc>";
		request += "<uploadername>" + suploadername + "</uploadername>";
		request += "<jsonstr>" + JSON.stringify(odataobj) + "</jsonstr>";
		request += "</Request>";

		var res = "";
		$.ajax({
			url: surl,
			type: "POST",
			data: encodeURI(request),
			async: false,
			dataType: "xml"
		}).done(function (data) {
			var xmlobj = $(data);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
				resulttext = resultxmlobj.first().text();
				res = resulttext;

				var resultsize = xmlobj.find("Result[ID=Ret2]");
				resultsizetxt = resultsize.first().text();

				if (attachtodoc == "1") {
					var uidoc = Docova.getUIDocument();
					var up = uidoc.getAttachmentsObj(uploadername);
					if (up.uploader && typeof up.uploader != "undefiend") {
						if (!up.uploader.IsFileAttached(newfilename)) {
							up.uploader.DisplayAddedFile(newfilename, "", new Date().toLocaleDateString(), resultsizetxt, "", "");
						}
					}
				}
			}
		})
		.fail(function () {});
		return res;

	} //--end MergeWordFilesWithJSONData


	/*----------------------------------------------------------------------------------------------
	 * Function: MergeAllWordFiles
	 * Merges data from a document with a word template.  Will match any fields on the template
	 * identified as {field_name} with matching field on the document
	 * Parameters:
	 *      templatename: String - Name of template stored admin/templates
	 *		docid: String - ID of the document
	 *		newfilename: String - Name of the file to use after the merge
	 *		attachtodoc: String - "1" to attach the merged file to the document
	 *		uploadername: String - Name of the uploader to attach the file to.
	 * Returns:
	 *  boolean - true if files were merged, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.MergeAllWordFiles = function (templatename, docid, newfilename, attachtodoc, uploadername) {
		var surl = "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";

		var sattachtodoc = attachtodoc && attachtodoc != "" ? attachtodoc : "0";
		var suploadername = uploadername && uploadername != "" ? uploadername : "";

		var request = "<Request>";
		request += "<Action>WORDFILEMERGEALL</Action>";
		request += "<templatename>" + templatename + "</templatename>";
		request += "<docid>" + docid + "</docid>";
		request += "<AppID>" + docInfo.AppID + "</AppID>";
		request += "<filename>" + newfilename + "</filename>";
		request += "<attachtodoc>" + sattachtodoc + "</attachtodoc>";
		request += "<uploadername>" + suploadername + "</uploadername>";
		request += "</Request>";

		var res = "";
		$.ajax({
			url: surl,
			type: "POST",
			data: encodeURI(request),
			async: false,
			dataType: "xml"
		}).done(function (data) {
			var xmlobj = $(data);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
				resulttext = resultxmlobj.first().text();
				res = resulttext;

				var resultsize = xmlobj.find("Result[ID=Ret2]");
				resultsizetxt = resultsize.first().text();

				if (attachtodoc == "1") {
					var uidoc = Docova.getUIDocument();
					var up = uidoc.getAttachmentsObj(uploadername);
					if (up.uploader && typeof up.uploader != "undefiend") {
						if (!up.uploader.IsFileAttached(newfilename)) {
							up.uploader.DisplayAddedFile(newfilename, "", new Date().toLocaleDateString(), resultsizetxt, "", "");
						}
					}
				}
			}
		})
		.fail(function () {});
		return res;

	} //--end MergeAllWordFiles


	/*----------------------------------------------------------------------------------------------
	 * Function: MergeFiles
	 * Merges a list of pdf files into a single pdf file
	 * Parameters:
	 *      xmldata: String - xml string containing list of source files to merge and output file
	 * Returns:
	 *  boolean - true if files were merged, false otherwise
	 *-------------------------------------------------------------------------------------------------*/
	this.MergeFiles = function (xmldata) {
		var surl = getPluginURL();
		surl += "action=mergeFiles";

		var mystr = xmldata;
		var sdata = {
			mergeFileXML: btoa(encodeURIComponent(mystr))
		};
		var res = 0;
		$.ajax({
			url: surl,
			type: "POST",
			data: sdata,
			async: false,
			dataType: "json"
		}).done(function (data) {
			if (data.status == "SUCCESS") {
				res = 1;
			} else {
				res = 0;
			}
		})
		.fail(function () {});
		return res;

	} //--end MergeFiles

	/*---------------------------------------------------------------------------------------------
	 * Function: PopulateBookmarks
	 * Update bookmark fields in a Word document using values retrieved from web form fields or
	 * passed in as string values.  Keep bookmark tags in place or remove.  Trigger optional macro.
	 * Parameters:
	 *                localfilename - string - Path to locally stored word document file
	 *                fieldList - string/string array - Comma separated list, or array of strings containing
	 *                             bookmark field names in the word document.
	 *                              If no valueList parameter is provided the values will be retrieved from the
	 *                              corresponding fields of the same name in the web document.  Alternately use
	 *                              a | delimiter to specify the SourceFieldName|TargetBookmarkFieldName.
	 *                 keepbookmarks - boolean (optional) - true to keep bookmark tags, false (default) to remove
	 *                 valueList - string/string array (optional) - Comma separated list, or array of strings containing
	 *                             values to insert into bookmark fields. If not specified the values will be retrieved from
	 *                             the web document using the field name provided in fieldList.
	 *                  macrotorun - string (optional) - Name of macro contained in word document to trigger after
	 *                             updating bookmarks.
	 *                  sourcedoc - html dom document (optional) - web document to retrieve field values from
	 * Return Value: boolean - true if no errors encountered, false otherwise
	 *
	 * Examples:
	 *          var result = PopulateBookmarks("c:\\temp\\mydoc.docx", "Subject|BookmarkA,Author|BookmarkX");
	 *          var result = PopulateBookmarks("c:\\temp\\mydoc.docx", "Subject|BookmarkA,Author|BookmarkX", false, "", "", document);
	 *          var result = PopulateBookmarks("c:\\temp\\mydoc.docx", "BookmarkA,BookmarkX", true, "Title XYZ,Jim Smith", "myFormatMacro", document);
	 *--------------------------------------------------------------------------------------------------*/
	this.PopulateBookmarks = function (localfilename, fieldList, keepbookmarks, valueList, macrotorun, sourcedoc) {
		var fieldArray = (Array.isArray(fieldList) ? fieldList.slice() : fieldList.split(","));
		if (fieldArray.length == 0) {
			return false;
		}

		var fieldValArray = (typeof valueList !== "undefined" && Array.isArray(valueList) ? valueList.slice() : (typeof valueList !== "undefined" && jQuery.trim(valueList) !== "" ? valueList.split(",") : new Array()));

		var bookmarkArray = new Array();

		for (j = 0; j < fieldArray.length; j++) {
			var tempnames = fieldArray[j].split("|");
			bookmarkArray.push(tempnames[tempnames.length - 1]);
			var tempval = (fieldValArray.length > j ? fieldValArray[j] : window.top.Docova.Utils.getField({
					"field": tempnames[0],
					"separator": ","
				}, (typeof sourcedoc !== "undefined" ? sourcedoc : document)));
			if (tempval === null) {
				tempval = "";
			}
			if (typeof tempval !== "string") {
				tempval = tempval.toString();
			}
			tempval = tempval.replace('"', '\"\"');
			fieldValArray.push(tempval);
		}

		var macroName = (macrotorun ? macrotorun : "");

		Docova.Utils.showProgressMessage("Writing Word data. Please wait...");

		var codestr = "";
		codestr += 'string result = @"{""runstatus"": ""FAILED""}";\n';
		codestr += '/*-- start parameters passed in from calling routine --*/\n';
		codestr += 'string opts_wordfile = @"' + localfilename + '";\n';
		codestr += 'string[] opts_valuelist = new string[] {' + "@\"" + fieldValArray.join("\",@\"") + "\"" + '};\n';
		codestr += 'string[] opts_fieldlist = new string[] {' + "@\"" + bookmarkArray.join("\",@\"") + "\"" + '};\n';
		codestr += 'string opts_macro = @"' + macroName + '";\n';
		codestr += 'string errormsg = "";\n';
		codestr += 'bool keepbookmarks = ' + (keepbookmarks ? "true" : "false") + ';\n';
		codestr += 'Type WordType = null;\n';
		codestr += 'Object WordApp = null;\n';
		codestr += 'Object WordDocs = null;\n';
		codestr += 'Object WordDoc = null;\n';
		codestr += 'Object AttachedTemplate = null;\n';
		codestr += 'Object Bookmarks = null;\n';
		codestr += 'Object Bookmark = null;\n';
		codestr += 'Object Range = null;\n';
		codestr += 'Object NewRange = null;\n';
		codestr += 'WordType = Type.GetTypeFromProgID("Word.Application");\n'
		codestr += 'if(WordType == null){ goto Cleanup;}\n';
		codestr += 'WordApp = Activator.CreateInstance(WordType);\n'
		codestr += 'if (WordApp == null){\n';
		codestr += '     errormsg = "Word has not been installed.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'WordApp.GetType().InvokeMember("Visible", BindingFlags.SetProperty, null, WordApp, new object[]{false});\n';
		codestr += 'WordDocs = WordApp.GetType().InvokeMember("Documents", BindingFlags.GetProperty, null, WordApp, new object[]{});\n';
		codestr += 'if (WordDocs == null){\n';
		codestr += '     errormsg = "Word documents not found.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'WordDoc = WordDocs.GetType().InvokeMember("Open", BindingFlags.InvokeMethod, null, WordDocs, new object[]{opts_wordfile});\n';
		codestr += 'if (WordDoc == null){\n';
		codestr += '     errormsg = "Word file could not be opened.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Bookmarks = WordDoc.GetType().InvokeMember("Bookmarks", BindingFlags.GetProperty, null, WordDoc, new object[] { });\n';
		codestr += 'if(Bookmarks == null){\n';
		codestr += '     errormsg = "No bookmarks present.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'for (int i = 0; i < 	opts_fieldlist.GetLength(0); i++){\n';
		codestr += '      var bookmarkexists = Bookmarks.GetType().InvokeMember("Exists", BindingFlags.InvokeMethod, null, Bookmarks, new object[] { opts_fieldlist[i] });\n';
		codestr += '      if((bool) bookmarkexists){\n';
		codestr += '           Bookmark = (Object) Bookmarks.GetType().InvokeMember("Item", BindingFlags.InvokeMethod, null, Bookmarks, new object[] { opts_fieldlist[i] });\n';
		codestr += '           Range = Range = Bookmark.GetType().InvokeMember("Range", BindingFlags.GetProperty, null, Bookmark, new object[] { });\n';
		codestr += '           if (Range != null){\n';
		codestr += '                 Range.GetType().InvokeMember("Text", BindingFlags.SetProperty, null, Range, new object[]{opts_valuelist[i]});\n';
		codestr += '                 NewRange = Range;\n';
		codestr += '                 Bookmarks.GetType().InvokeMember("Add", BindingFlags.InvokeMethod, null, Bookmarks, new object[] { opts_fieldlist[i], NewRange });\n';
		codestr += '                 if (!keepbookmarks){\n';
		codestr += '                      Bookmark = (Object)Bookmarks.GetType().InvokeMember("Item", BindingFlags.InvokeMethod, null, Bookmarks, new object[] { opts_fieldlist[i] });\n';
		codestr += '                      Bookmark.GetType().InvokeMember("Delete", BindingFlags.InvokeMethod, null, Bookmark, new object[] { });\n';
		codestr += '                 }\n';
		codestr += '           }\n';
		codestr += '     }\n';
		codestr += '}\n';
		codestr += 'if(opts_macro != ""){\n';
		codestr += '    try{\n';
		codestr += '       WordApp.GetType().InvokeMember("Run", BindingFlags.InvokeMethod, null, WordApp, new object[]{opts_macro});\n';
		codestr += '    }catch{}\n';
		codestr += '}\n';
		codestr += 'result = @"{""runstatus"": ""SUCCESS""}";\n';
		codestr += 'Cleanup:\n';
		codestr += '    if (errormsg != ""){\n';
		codestr += '        result = @"{""runstatus"": ""FAILURE"", ""data"": """ + System.Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes(errormsg)) + @"""}";\n';
		codestr += '     }\n';
		codestr += '    if(WordApp != null){\n';
		codestr += '        if(WordDoc != null){\n';
		codestr += '	         AttachedTemplate = WordDoc.GetType().InvokeMember("AttachedTemplate", BindingFlags.GetProperty, null, WordDoc, new object[] { });\n';
		codestr += '             if (AttachedTemplate != null){\n';
		codestr += '                  AttachedTemplate.GetType().InvokeMember("Saved", BindingFlags.SetProperty, null, AttachedTemplate, new object[] { true });\n'; //fix to tell Word not to prompt to save the default .dot template
		codestr += '             }\n';
		codestr += '             WordDoc.GetType().InvokeMember("Close", BindingFlags.InvokeMethod, null, WordDoc, new object[]{-1});\n'; // -1 saves without prompting with the close method, trying to save then close can cause Word to crash as there seems to be a timing conflict in some versions.
		codestr += '         }\n';
		codestr += '        WordApp.GetType().InvokeMember("Quit", BindingFlags.InvokeMethod, null, WordApp, new object[]{});\n';
		codestr += '    }\n';
		codestr += '    NewRange = null;\n';
		codestr += '    Range = null;\n';
		codestr += '    Bookmark = null;\n';
		codestr += '    Bookmarks = null;\n';
		codestr += '    AttachedTemplate = null;\n';
		codestr += '    WordDoc = null;\n';
		codestr += '    WordDocs = null;\n';
		codestr += '    WordApp = null;\n';
		codestr += '    WordType = null;\n';
		codestr += 'return result;\n';

		var result = false;

		var retval = this.executeCode(codestr, true, false);
		if (retval.status == "SUCCESS") {
			try {
				//-- try to parse the json data being returned into an object
				var tempjson = JSON.parse(retval.results);
				if (tempjson.runstatus && tempjson.runstatus == "SUCCESS") {
					result = true;
				} else {
					window.top.Docova.Utils.messageBox({
						'prompt': "An error has occurred updating Word data.",
						'title': "Write Word Data",
						'width': 400
					});
				}
			} catch (e) {
				window.top.Docova.Utils.messageBox({
					'prompt': "An error has occurred updating Word data.",
					'title': "Write Word Data",
					'width': 400
				});
			}
		} else {
			window.top.Docova.Utils.messageBox({
				'prompt': "An error has occurred updating Word data. Error [" + retval.error + "]",
				'title': "Write Word Data",
				'width': 400
			});
		}

		Docova.Utils.hideProgressMessage();

		return result;
	} //--end PopulateBookmarks


	/*---------------------------------------------------------------------------------------------
	 * Function: ReadExcelData
	 * Read cell data from an Excel document and write to fields on the web page
	 * Parameters:
	 *                excelFilePath - string - Path to locally stored excel file
	 *                xSheet - string - sheet number in the excel file to read values from
	 *                fieldList - string - Comma separated list containing field names in the web page
	 *                           that will be updated with values read from excel
	 *                cellList - string - Comma separated list containing cell references to retrieve
	 *                           excel values from
	 *                macrotorun - string (optional) - Name of macro contained in excel document
	 *                           to trigger before reading cell values
	 *                targetdoc - html dom document (optional) - target web page to update fields in
	 * Return Value: boolean - true if no errors encountered, false otherwise
	 *
	 * Examples:
	 *          var result = ReadExcelData("c:\\temp\\mysheet.xls", "1", "FiscalYear,Revenue", "A2,B2", "", document);
	 *          var result = ReadExcelData("c:\\temp\\mysheet.xls", "1", "FiscalYear,Revenue", "A2,B2", "CalcValues", document);
	 *--------------------------------------------------------------------------------------------------*/
	this.ReadExcelData = function (excelFilePath, xSheet, fieldList, cellList, macroName, targetdoc) {
		var retVal = false;

		var fieldArray = fieldList.split(",");
		var cellArray = cellList.split(",");
		if (fieldArray.length != cellArray.length) {
			window.top.Docova.Utils.messageBox({
				'prompt': "The number of cells does not match the number of fields.",
				'title': "Read Excel Data",
				'width': 400
			});
			return retVal;
		}
		if (xSheet == undefined || xSheet == null) {
			var xSheet = "1";
		}

		if (macroName == undefined || macroName == null) {
			var macroName = "";
		}

		window.top.Docova.Utils.showProgressMessage("Reading spreadsheet data. Please wait...");

		var codestr = "";
		codestr += 'string result = @"{""runstatus"": ""FAILED""}";\n';
		codestr += '/*-- start parameters passed in from calling routine --*/\n';
		codestr += 'string opts_excelfile = @"' + excelFilePath + '";\n';
		codestr += 'int opts_worksheet = ' + xSheet + ';\n';
		codestr += 'string[] opts_celllist = new string[] {' + "\"" + cellArray.join("\",\"") + "\"" + '};\n';
		codestr += 'string opts_macro = @"' + macroName + '";\n';
		codestr += 'string errormsg = "";\n';
		codestr += 'Type ExcelType = null;\n';
		codestr += 'Object ExcelApp = null;\n';
		codestr += 'Object Workbooks = null;\n';
		codestr += 'Object Workbook = null;\n';
		codestr += 'Object Worksheets = null;\n';
		codestr += 'Object Worksheet = null;\n';
		codestr += 'Object Range = null;\n';
		codestr += 'ExcelType = Type.GetTypeFromProgID("Excel.Application");\n'
		codestr += 'if(ExcelType == null){ goto Cleanup;}\n';
		codestr += 'ExcelApp = Activator.CreateInstance(ExcelType);\n'
		codestr += 'if (ExcelApp == null){\n';
		codestr += '     errormsg = "Excel has not been installed.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'ExcelApp.GetType().InvokeMember("Visible", BindingFlags.SetProperty, null, ExcelApp, new object[]{false});\n';
		codestr += 'Workbooks = ExcelApp.GetType().InvokeMember("Workbooks", BindingFlags.GetProperty, null, ExcelApp, new object[]{});\n';
		codestr += 'if (Workbooks == null){\n';
		codestr += '     errormsg = "Excel workbooks not found.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Workbook = Workbooks.GetType().InvokeMember("Open", BindingFlags.InvokeMethod, null, Workbooks, new object[]{opts_excelfile});\n';
		codestr += 'if (Workbook == null){\n';
		codestr += '     errormsg = "Excel file could not be opened.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Worksheets = Workbook.GetType().InvokeMember("Worksheets", BindingFlags.GetProperty, null, Workbook, new object[] {});\n';
		codestr += 'if (Worksheets == null){\n';
		codestr += '      errormsg = "Excel Worksheets not found.";\n';
		codestr += '      goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Worksheet = Worksheets.GetType().InvokeMember("Item", BindingFlags.GetProperty, null, Worksheets, new object[]{opts_worksheet});\n';
		codestr += 'if (Worksheet == null){\n';
		codestr += '     errormsg = "Excel Worksheet not found.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'if(opts_macro != ""){\n';
		codestr += '    try{\n';
		codestr += '       ExcelApp.GetType().InvokeMember("Run", BindingFlags.InvokeMethod, null, ExcelApp, new object[]{opts_macro});\n';
		codestr += '    }catch{}\n';
		codestr += '}\n';
		codestr += 'string celldata = "";\n';
		codestr += 'for (int i = 0; i < 	opts_celllist.GetLength(0); i++){\n';
		codestr += '      Range = Worksheet.GetType().InvokeMember("Range", BindingFlags.GetProperty, null, Worksheet, new object[] { opts_celllist[i] });\n';
		codestr += '      if (Range != null){\n';
		codestr += '           celldata += Convert.ToString(Range.GetType().InvokeMember("Value", BindingFlags.GetProperty, null, Range, new object[]{}));\n';
		codestr += '      }\n';
		codestr += '      celldata += ";";\n';
		codestr += '}\n';
		codestr += 'result = @"{""runstatus"": ""SUCCESS"", ""data"": """ + System.Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes(celldata)) + @"""}";\n';
		codestr += 'Cleanup:\n';
		codestr += '    if (errormsg != ""){\n';
		codestr += '        result = @"{""runstatus"": ""FAILURE"", ""data"": """ + System.Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes(errormsg)) + @"""}";\n';
		codestr += '     }\n';
		codestr += '    if(ExcelApp != null){\n';
		codestr += '        if(Workbook != null){\n';
		codestr += '             Workbook.GetType().InvokeMember("Close", BindingFlags.InvokeMethod, null, Workbook, new object[]{false});\n'; //close the file quietly without saving anything
		codestr += '         }\n';
		codestr += '        ExcelApp.GetType().InvokeMember("Quit", BindingFlags.InvokeMethod, null, ExcelApp, new object[]{});\n';
		codestr += '    }\n';
		codestr += '    Range = null;\n';
		codestr += '    Worksheet = null;\n';
		codestr += '    Worksheets = null;\n';
		codestr += '    Workbook = null;\n';
		codestr += '    Workbooks = null;\n';
		codestr += '    ExcelApp = null;\n';
		codestr += '    ExcelType = null;\n';
		codestr += 'return result;\n';

		var retval = this.executeCode(codestr, false, true);
		if (retval.status == "SUCCESS") {
			try {
				//-- try to parse the json data being returned into an object
				var tempjson = JSON.parse(retval.results);
				if (tempjson.runstatus && tempjson.runstatus == "SUCCESS") {
					tempjson.data = atob(tempjson.data);
					var tmpArr = tempjson.data.split(";");
					var fldList = fieldList.split(",");
					for (j = 0; j < tmpArr.length; j++) {
						window.top.Docova.Utils.setField({
							"field": fldList[j],
							"value": tmpArr[j]
						}, (typeof targetdoc !== "undefined" ? targetdoc : document));
					}
					retVal = true;
				} else {
					window.top.Docova.Utils.messageBox({
						'prompt': "An error has occurred retreiving Excel data.",
						'title': "Read Excel Data",
						'width': 400
					});
				}
			} catch (e) {
				window.top.Docova.Utils.messageBox({
					'prompt': "An error has occurred retrieving Excel data.",
					'title': "Read Excel Data",
					'width': 400
				});
			}
		} else {
			window.top.Docova.Utils.messageBox({
				'prompt': "An error has occurred retreiving Excel data. Error [" + retval.error + "]",
				'title': "Read Excel Data",
				'width': 400
			});
		}

		window.top.Docova.Utils.hideProgressMessage();

		return retVal;
	} //--end ReadExcelData


	/*---------------------------------------------------------------------------------------------
	 * Function: WriteExcelData
	 * Read field data from web page and update cell values in excel document
	 * Parameters:
	 *                excelFilePath - string - Path to locally stored excel file
	 *                xSheet - string - sheet number in the excel file to write values to
	 *                fieldList - string - Comma separated list containing field names in the web page
	 *                           that values will be read from
	 *                cellList - string - Comma separated list containing cell references to store
	 *                           field values to
	 *                macrotorun - string (optional) - Name of macro contained in excel document
	 *                           to trigger after updating cell values
	 *                sourcedoc - html dom document (optional) - web page to read field values from
	 * Return Value: boolean - true if no errors encountered, false otherwise
	 *
	 * Examples:
	 *          var result = WriteExcelData("c:\\temp\\mysheet.xls", "1", "FiscalYear,Revenue", "A2,B2", "", document);
	 *          var result = WriteExcelData("c:\\temp\\mysheet.xls", "1", "FiscalYear,Revenue", "A2,B2", "CalcValues", document);
	 *--------------------------------------------------------------------------------------------------*/
	this.WriteExcelData = function (excelFilePath, xSheet, fieldList, cellList, macroName, sourcedoc) {
		var retVal = false;

		var fieldArray = fieldList.split(",");
		var cellArray = cellList.split(",");
		if (fieldArray.length != cellArray.length) {
			window.top.Docova.Utils.messageBox({
				'prompt': "The number of cells does not match the number of fields.",
				'title': "Write Excel Data",
				'width': 400
			});
			return retVal;
		}

		var fieldValArray = new Array();
		for (j = 0; j < fieldArray.length; j++) {
			var tempval = window.top.Docova.Utils.getField({
					"field": fieldArray[j],
					"separator": ","
				}, (typeof sourcedoc !== "undefined" ? sourcedoc : document));
			tempval = tempval.replace('"', '\"\"');
			fieldValArray.push(tempval);
		}

		if (xSheet == undefined || xSheet == null) {
			var xSheet = "1";
		}

		if (macroName == undefined || macroName == null) {
			var macroName = "";
		}

		window.top.Docova.Utils.showProgressMessage("Writing spreadsheet data. Please wait...");

		var codestr = "";
		codestr += 'string result = @"{""runstatus"": ""FAILED""}";\n';
		codestr += '/*-- start parameters passed in from calling routine --*/\n';
		codestr += 'string opts_excelfile = @"' + excelFilePath + '";\n';
		codestr += 'int opts_worksheet = ' + xSheet + ';\n';
		codestr += 'string[] opts_valuelist = new string[] {' + "@\"" + fieldValArray.join("\",@\"") + "\"" + '};\n';
		codestr += 'string[] opts_celllist = new string[] {' + "@\"" + cellArray.join("\",@\"") + "\"" + '};\n';
		codestr += 'string opts_macro = @"' + macroName + '";\n';
		codestr += 'string errormsg = "";\n';
		codestr += 'Type ExcelType = null;\n';
		codestr += 'Object ExcelApp = null;\n';
		codestr += 'Object Workbooks = null;\n';
		codestr += 'Object Workbook = null;\n';
		codestr += 'Object Worksheets = null;\n';
		codestr += 'Object Worksheet = null;\n';
		codestr += 'Object Range = null;\n';
		codestr += 'ExcelType = Type.GetTypeFromProgID("Excel.Application");\n'
		codestr += 'if(ExcelType == null){ goto Cleanup;}\n';
		codestr += 'ExcelApp = Activator.CreateInstance(ExcelType);\n'
		codestr += 'if (ExcelApp == null){\n';
		codestr += '     errormsg = "Excel has not been installed.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'ExcelApp.GetType().InvokeMember("Visible", BindingFlags.SetProperty, null, ExcelApp, new object[]{false});\n';
		codestr += 'Workbooks = ExcelApp.GetType().InvokeMember("Workbooks", BindingFlags.GetProperty, null, ExcelApp, new object[]{});\n';
		codestr += 'if (Workbooks == null){\n';
		codestr += '     errormsg = "Excel workbooks not found.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Workbook = Workbooks.GetType().InvokeMember("Open", BindingFlags.InvokeMethod, null, Workbooks, new object[]{opts_excelfile});\n';
		codestr += 'if (Workbook == null){\n';
		codestr += '     errormsg = "Excel file could not be opened.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Worksheets = Workbook.GetType().InvokeMember("Worksheets", BindingFlags.GetProperty, null, Workbook, new object[] {});\n';
		codestr += 'if (Worksheets == null){\n';
		codestr += '      errormsg = "Excel Worksheets not found.";\n';
		codestr += '      goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'Worksheet = Worksheets.GetType().InvokeMember("Item", BindingFlags.GetProperty, null, Worksheets, new object[]{opts_worksheet});\n';
		codestr += 'if (Worksheet == null){\n';
		codestr += '     errormsg = "Excel Worksheet not found.";\n';
		codestr += '     goto Cleanup;\n';
		codestr += '}\n';
		codestr += 'for (int i = 0; i < 	opts_celllist.GetLength(0); i++){\n';
		codestr += '      Range = Worksheet.GetType().InvokeMember("Range", BindingFlags.GetProperty, null, Worksheet, new object[] { opts_celllist[i] });\n';
		codestr += '      if (Range != null){\n';
		codestr += '           Range.GetType().InvokeMember("Value", BindingFlags.SetProperty, null, Range, new object[]{opts_valuelist[i]});\n';
		codestr += '      }\n';
		codestr += '}\n';
		codestr += 'if(opts_macro != ""){\n';
		codestr += '    try{\n';
		codestr += '       ExcelApp.GetType().InvokeMember("Run", BindingFlags.InvokeMethod, null, ExcelApp, new object[]{opts_macro});\n';
		codestr += '    }catch{}\n';
		codestr += '}\n';
		codestr += 'result = @"{""runstatus"": ""SUCCESS""}";\n';
		codestr += 'Cleanup:\n';
		codestr += '    if (errormsg != ""){\n';
		codestr += '        result = @"{""runstatus"": ""FAILURE"", ""data"": """ + System.Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes(errormsg)) + @"""}";\n';
		codestr += '     }\n';
		codestr += '    if(ExcelApp != null){\n';
		codestr += '        if(Workbook != null){\n';
		codestr += '             Workbook.GetType().InvokeMember("Save", BindingFlags.InvokeMethod, null, Workbook, new object[]{});\n'; //save the file
		codestr += '             Workbook.GetType().InvokeMember("Close", BindingFlags.InvokeMethod, null, Workbook, new object[]{false});\n'; //close the file quietly without saving anything
		codestr += '         }\n';
		codestr += '        ExcelApp.GetType().InvokeMember("Quit", BindingFlags.InvokeMethod, null, ExcelApp, new object[]{});\n';
		codestr += '    }\n';
		codestr += '    Range = null;\n';
		codestr += '    Worksheet = null;\n';
		codestr += '    Worksheets = null;\n';
		codestr += '    Workbook = null;\n';
		codestr += '    Workbooks = null;\n';
		codestr += '    ExcelApp = null;\n';
		codestr += '    ExcelType = null;\n';
		codestr += 'return result;\n';

		var retval = this.executeCode(codestr, false, true);
		if (retval.status == "SUCCESS") {
			try {
				//-- try to parse the json data being returned into an object
				var tempjson = JSON.parse(retval.results);
				if (tempjson.runstatus && tempjson.runstatus == "SUCCESS") {
					retVal = true;
				} else {
					window.top.Docova.Utils.messageBox({
						'prompt': "An error has occurred updating Excel data.",
						'title': "Write Excel Data",
						'width': 400
					});
				}
			} catch (e) {
				window.top.Docova.Utils.messageBox({
					'prompt': "An error has occurred updating Excel data.",
					'title': "Write Excel Data",
					'width': 400
				});
			}
		} else {
			window.top.Docova.Utils.messageBox({
				'prompt': "An error has occurred updating Excel data. Error [" + retval.error + "]",
				'title': "Write Excel Data",
				'width': 400
			});
		}

		window.top.Docova.Utils.hideProgressMessage();

		return retVal;
	} //--end WriteExcelData


} //--end DocovaExtensions class


//-- instantiate a new docova object as a global variable
Docova = new docova();
DocovaExtensions = new DocovaExtensions();
