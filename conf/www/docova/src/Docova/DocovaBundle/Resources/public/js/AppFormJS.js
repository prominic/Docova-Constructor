var _DOCOVAEdition = "SE";  //-- set this variable to either SE or Domino depending upon where this code is installed

if (_DOCOVAEdition == "Domino") {
	var aBar = new objCustomActionBar(false, "aBar");
}

var statusWin; // progress message status window
var checkin = false;
var allowClose = false;
var forceSave = false;
var clearLock = true;
var resizeUL = true;
var _thisUIDoc = null;

var dlgParams = new Array(); //params array that gets used by dialogs
var retValues = new Array(); //ret params array that can be used by dialogs
var DLExtensions = null;
var DLIUploaderConfigs = null;
var FormEventList = {
	'initialize': [],
	'queryopen': [],
	'postopen': [],
	'querymodechange': [],
	'queryrecalc': [],
	'postrecalc': [],
	'querysave': [],
	'postsave': [],
	'queryclose': [],
	'terminate': []
}

//---- global temp file variables----
var tmpSupportFolders = new Array();
var tmpFilePaths = new Array();

var srcWindow = null;
var openAction = null;

var defaultThreadBoxId = "thread-box-0";
var defaultCommentBoxId = "comment-box-0-0";

var defaultThreadBoxHTML = '<div id="thread-box-0" class="thread-box-off" style="position:relative;" tid=0 elem="thread-box">'
	defaultThreadBoxHTML += '<div class="thread-reply-link">Reply...</div>'
	defaultThreadBoxHTML += '<table style="width:100%; display:none;margin-top:20px;">'
	defaultThreadBoxHTML += '<tr>'
	defaultThreadBoxHTML += '<td style="width:32px;">'
	defaultThreadBoxHTML += '<div class="reply-avatar"><i class="far fa-comments fa-2x avatar-icon"></i></div>'
	defaultThreadBoxHTML += '</td>'
	defaultThreadBoxHTML += '<td>'
	defaultThreadBoxHTML += '<div style="position:relative;"><div class="reply-placeholder-text">Reply...</div></div>'
	defaultThreadBoxHTML += '<div class="reply-text-input" contenteditable="true"></div>'
	defaultThreadBoxHTML += '</td>'
	defaultThreadBoxHTML += '</tr>'
	defaultThreadBoxHTML += '<tr>'
	defaultThreadBoxHTML += '<td></td>'
	defaultThreadBoxHTML += '<td>'
	defaultThreadBoxHTML += '<div class="reply-post-button">'
	defaultThreadBoxHTML += '<button class="btn-reply-post-button" type="button"></button>'
	defaultThreadBoxHTML += '</div>'
	defaultThreadBoxHTML += '</td>'
	defaultThreadBoxHTML += '</tr>'
	defaultThreadBoxHTML += '</table>'
	defaultThreadBoxHTML += '</div>'

	var defaultCommentBoxHTML = '<div id="comment-box-0-0" class="comment-box" tid=0 cid=0 did=0>'
	defaultCommentBoxHTML += '<div class="comment-avatar"><i class="far fa-2x avatar-icon"></i></div>'
	defaultCommentBoxHTML += '<div class="comment-commentor"></div>'
	defaultCommentBoxHTML += '<div class="comment-time-ago"></div>'
	defaultCommentBoxHTML += '<div class="comment"></div>'
	defaultCommentBoxHTML += '<div class="comment-delete" style="display:none;">Delete...</div>'
	defaultCommentBoxHTML += '</div>'

	commentStartHTML = '<table style="width:100%;text-align:center;"><tr><td>'
	commentStartHTML += '<i class="far fa-comments fa-3x" style="color: cornflowerblue;">'
	commentStartHTML += '</td></tr>'
	commentStartHTML += '<tr><td>'
	commentStartHTML += '<span style="color: cornflowerblue;">Post a comment to start a discussion.</span>'
	commentStartHTML += '</td></tr></table>'

	$(document).ready(function () {
		var oktocontinue = true;
		var isAfterRefresh = $("#docRefreshed").val() == "1" ? true : false;
		if (isAfterRefresh) {
			docInfo.isPostRefresh = true;
			//reset the refresh flag.
			$("#docRefreshed").val("");
		}
		_thisUIDoc = Docova.getUIDocument();

		//check if we need to re-compute field formula's after an embedded view has refreshed
		if ( _thisUIDoc) {
			_thisUIDoc.on("EmbViewLoadComplete", function (which) 
			{
				
				if ( which ) {
					var vname = which.viewName;
				
					$("[fnembview]").each ( function ()
					{
						
						var embviewsec = $(this).attr("fnembview");
						if ( embviewsec && embviewsec != "") {
							var ifrm = $("#" + embviewsec);
							if ( ifrm.length > 0 )
							{
								var iframewindow= ifrm.get(0).contentWindow? ifrm.get(0).contentWindow : ifrm.get(0).contentDocument.defaultView;
								var viewname = iframewindow.docInfo.ViewName;
								if ( vname == viewname){
									var fformula = $(this).attr("fnrefreshscript");
									if ( fformula && fformula != "")
									{	
										try {
											var retval = eval ( fformula);
											var elmId = $(this).attr("id");
											if (elmId.indexOf('SPAN') == 0) {
												elmId = elmId.substring(4);
											}
											Docova.Utils.setField({'field' : elmId, 'value' : retval});
											if ($(this).attr('numformat') == 'auto') {
												var numdec = $(this).attr("textnumdecimals");
												var fnum = Docova.Utils.formatNumber({
													numstring: retval.toString(),
													numdecimals: numdec,
													decimalsymbol: docInfo.DecimalSeparator,
													thousandsseparator: docInfo.ThousandsSeparator
												});
												if ($.trim($(this).attr('placeholder')) == '' || fval !== "") {
													Docova.Utils.setField({'field' : elmId, 'value' : fnum});
												}
											}
										}catch(e){
											alert ( "Error running EmbViewLoadComplete code " + e);
										}
									}
								}
							}
						}
					});
				}
			});
		}
		
		DLExtensions = DocovaExtensions;

		//-- handle custom initialize event triggers
		if (FormEventList && FormEventList.initialize && Array.isArray(FormEventList.initialize) && FormEventList.initialize.length > 0) {
			for (var x = 0; x < FormEventList.initialize.length; x++) {
				if (typeof window[FormEventList.initialize[x]] == "function") {
					window[FormEventList.initialize[x]](_thisUIDoc);
				}
			}
		}

		//-- handle custom queryopen event triggers
		if (FormEventList && FormEventList.queryopen && Array.isArray(FormEventList.queryopen) && FormEventList.queryopen.length > 0) {
			for (var x = 0; x < FormEventList.queryopen.length; x++) {
				if (typeof window[FormEventList.queryopen[x]] == "function") {
					oktocontinue = window[FormEventList.queryopen[x]](_thisUIDoc, _thisUIDoc.editMode, _thisUIDoc.isNewDoc);
					if (!oktocontinue) {
						_thisUIDoc.close({
							savePrompt: false
						});
						break;
					}
				}
			}
		}

		//-- were there any issues in the query open check
		if (oktocontinue) {
			//-- set some event handlers that may get triggered later

			//-- querymodechange functions
			if (FormEventList && FormEventList.querymodechange && Array.isArray(FormEventList.querymodechange) && FormEventList.querymodechange.length > 0) {
				_thisUIDoc.on("queryModeChange", function () {
					var result = true;
					for (var x = 0; x < FormEventList.querymodechange.length; x++) {
						if (typeof window[FormEventList.querymodechange[x]] == "function") {
							result = window[FormEventList.querymodechange[x]](_thisUIDoc);
							if (!result) {
								break;
							}
						}
					}
					return result;
				});
			}

			//-- queryrecalc functions
			if (FormEventList && FormEventList.queryrecalc && Array.isArray(FormEventList.queryrecalc) && FormEventList.queryrecalc.length > 0) {
				_thisUIDoc.on("queryRecalc", function () {
					var result = true;
					for (var x = 0; x < FormEventList.queryrecalc.length; x++) {
						if (typeof window[FormEventList.queryrecalc[x]] == "function") {
							result = window[FormEventList.queryrecalc[x]](_thisUIDoc);
							if (!result) {
								break;
							}
						}
					}
					return result;
				});
			}

			//-- postrecalc functions
			if (FormEventList && FormEventList.postrecalc && Array.isArray(FormEventList.postrecalc) && FormEventList.postrecalc.length > 0) {
				_thisUIDoc.on("postRecalc", function () {
					for (var x = 0; x < FormEventList.postrecalc.length; x++) {
						if (typeof window[FormEventList.postrecalc[x]] == "function") {
							window[FormEventList.postrecalc[x]](_thisUIDoc);
						}
					}
				});
			}

			//-- querysave functions
			if (FormEventList && FormEventList.querysave && Array.isArray(FormEventList.querysave) && FormEventList.querysave.length > 0) {
				_thisUIDoc.on("querySave", function () {
					var result = true;
					for (var x = 0; x < FormEventList.querysave.length; x++) {
						if (typeof window[FormEventList.querysave[x]] == "function") {
							result = window[FormEventList.querysave[x]](_thisUIDoc);
							if (!result) {
								break;
							}
						}
					}
					return result;
				});
			}

			//-- postsave functions
			if (FormEventList && FormEventList.postsave && Array.isArray(FormEventList.postsave) && FormEventList.postsave.length > 0) {
				_thisUIDoc.on("postSave", function () {
					for (var x = 0; x < FormEventList.postsave.length; x++) {
						if (typeof window[FormEventList.postsave[x]] == "function") {
							window[FormEventList.postsave[x]](_thisUIDoc);
						}
					}
				});
			}

			//-- queryclose functions
			if (FormEventList && FormEventList.queryclose && Array.isArray(FormEventList.queryclose) && FormEventList.queryclose.length > 0) {
				_thisUIDoc.on("queryClose", function () {
					var result = true;
					for (var x = 0; x < FormEventList.queryclose.length; x++) {
						if (typeof window[FormEventList.queryclose[x]] == "function") {
							result = window[FormEventList.queryclose[x]](_thisUIDoc);
							if (!result) {
								break;
							}
						}
					}
					return result;
				});
			}

			//-- terminate functions
			if (FormEventList && FormEventList.terminate && Array.isArray(FormEventList.terminate) && FormEventList.terminate.length > 0) {
				_thisUIDoc.on("terminate", function () {
					for (var x = 0; x < FormEventList.terminate.length; x++) {
						if (typeof window[FormEventList.terminate[x]] == "function") {
							window[FormEventList.terminate[x]](_thisUIDoc);
						}
					}
				});
			}

			//--end custom event triggers

			//-- update page elements
			InitAppBuilderForm();

		//loadDataIsland
		loadDataIslandData();

		//check if this is a profile document
		if (_thisUIDoc && _thisUIDoc.isProfile && !_thisUIDoc.isPostRefresh) {
			_thisUIDoc.loadProfileFields();
		}

			//triggers the content loaded event used by the
			if (docInfo.Mode == "dialog") {
				window.top.Docova.events.triggerHandler("contentLoaded" + docInfo.DialogID, _thisUIDoc, false);
			}

			//-- handle custom postopen event triggers
			if (FormEventList && FormEventList.postopen && Array.isArray(FormEventList.postopen) && FormEventList.postopen.length > 0) {
				for (var x = 0; x < FormEventList.postopen.length; x++) {
					if (typeof window[FormEventList.postopen[x]] == "function") {
						window[FormEventList.postopen[x]](_thisUIDoc);
					}
				}
			}

		} //--end check ok to continue loading

		$('#divFormContentSection').on('change', ':input', function(e) {
			$('#divFormContentSection').data('changed',true);
		});

		$('a.docova_doclink').on('click', function (e) {
			e.preventDefault();
			if ($(this).attr('href').indexOf('Notes:') > -1 || $(this).attr('href').indexOf('Notes:') > -1) {
				alert("Doc link could not be located!");
				return;
			}

			if ($(this).attr('href').indexOf('app_dev.php') > -1 || $(this).attr('href').indexOf('app.php') > -1) {
				var docurl = docurl = docInfo.ServerUrl + $(this).attr('href');
			} else {
				var docurl = docurl = docInfo.ServerUrl + "/" + docInfo.NsfName.replace('/Docova', '') + $(this).attr('href');
			}
			var ws = Docova.getUIWorkspace(document);

			ws.openDocument({
				'docurl': docurl,
				'isapp': true
			});
			return false;
		});

	$('a.docova_dbdoclink').on('click', function (e) {
		e.preventDefault();
		var appid = $(this).attr('seid');
		var objApp = new DocovaApplication({
				appid: appid
			});
		objApp.launchApplication();

		return false;
	});
	if (_thisUIDoc ){
		_thisUIDoc.isValidForm(true);
	}

	handleDataIslands();

	$("[isrequired = 'Y']").each ( function () 
	{
		//we set the leftborder to be 4px red for required fiels...here we adjust the width to accomodate that
		var $targetelem = $(this);
		if ($(this).attr('elem') == 'select'){
			$targetelem = $('span[sourceelem="'+ $(this).prop('id') +'"]').find('input.custom-combobox-input');
		}
		$targetelem.width($targetelem.width() - 4);
	})
	
	//enable counter box
	$('div.docova-counterbox').each(function() {
		var $this = $(this);
		$this.counterBox({
			dir: $this.attr('cdir') ? $this.attr('cdir') : 'up',
			value: $.trim($this.attr('cval')),
			color: $this.attr('ctextcolor') ? $this.attr('ctextcolor') : '',
			size: $this.attr('cfontsize') ? $this.attr('cfontsize') : '',
			title: $this.attr('ctitle') ? $.trim($this.attr('ctitle')) : '',
			titlesize: $this.attr('csubtitlesize') ? $this.attr('csubtitlesize') : '',
			titlecolor: $this.attr('csubtitlecolor') ? $this.attr('csubtitlecolor') : '',
			icon: $this.attr('cicon') ? $this.attr('cicon') : null,
			iconpos: $this.attr('cicon') && $this.attr('ciconpos') ? $this.attr('ciconpos') : null,
			iconsize: $this.attr('cicon') && $this.attr('ciconsize') ? $this.attr('ciconsize') : null,
			starton: $this.attr('cstarton') ? $this.attr('cstarton') : 'topview',
			unit: $this.attr('cunit') ? $this.attr('cunit') : '',
			unitpos: $this.attr('cunitpos') ? $this.attr('cunitpos') : 'prefix',
			enablebox: $this.hasClass('counterbox-borders-on') ? true : false,
			speed: $this.attr('cspeed') ? parseInt($this.attr('cspeed')) : 2000
		});
	});
});

function handleDataIslands()
{
	if ( docInfo.isDocBeingEdited) 
	{
		$(".datatable > table").on ("click", "tbody.datatabletbody > tr", function () 
		{

			$(".disland_edit").hide();
			$(".disland_canceledit").hide();
			$(".disland_save").hide();
			$(".disland_delete").hide();

			$(this).parents(".datatable").find(".diselected_row").removeClass("diselected_row");
			$(this).addClass("diselected_row");

			var templrow = $(this).parent().find(".disland_templ_row");
			if ( $(this).hasClass("disland_templ_row"))
			{
				$(this).find(".disland_save").show();
				if ( $(this).attr("edited") == "1" ){
					$(this).find(".disland_canceledit").show();
					$(this).find(".disland_edit").hide();
					$(this).find(".disland_delete").show();
				}else{
					$(this).find(".disland_canceledit").hide();
					$(this).find(".disland_edit").hide();
					$(this).find(".disland_delete").hide();
				}
				
				return;
			}

			if ( templrow.attr("edited") == "1")
			{
				cancelDataIslandEdit($(this).closest(".datatable"),templrow);
			
			}
			
			editDataIslandRow($(this).closest(".datatable"), $(this));
			$(templrow).find(".disland_save").show();
			
			if ( $(templrow).attr("edited") && $(templrow).attr("edited") == "1" ){
				$(templrow).find(".disland_canceledit").show();
				$(templrow).find(".disland_delete").show();
				$(templrow).find(".disland_save").show();
			}

		});
	}

	$(".disland_templ_row").find(".disland_save").show();

	var inpcoll = $(".datatable").find(":input[isrequired='Y']");
	validateRequiredFields(inpcoll, true);

	//dataisland
	$(".datatable > table > tbody").on ("mouseenter", "tr", function()
	{	
			$(this).css("background", "#f0f2f5")		
	});

	$(".datatable > table > tbody").on ("mouseleave", "tr", function()
	{	
			$(this).css("background", "")		
	});

	$(".disland_save").click ( function () 
	{
		saveDataIslandRow($(this).closest(".datatable"));
	})

	$(".disland_canceledit").click ( function () 
	{	
		var tr = $(this).closest("tr");
		cancelDataIslandEdit($(this).closest(".datatable"), tr);
	})


	$(".disland_delete").click ( function () 
	{	
		var r = confirm("Are you sure you want to delete this row?");
		if ( r !== true ){
			return;
		}

		var tr = $(this).closest("tr");
		var origrow = $(this).parents("tbody").find("[removed='1']");
		cancelDataIslandEdit($(this).closest(".datatable"), tr);
		deleteDataIslandRow($(this).closest(".datatable"), origrow);
	})

	$(".datatable > table > tbody").on ("click", ".disland_edit", function () {
		var tr = $(this).closest("tr");
		editDataIslandRow($(this).closest(".datatable"), tr);
	})
}

function deleteDataIslandRow(parent, row)
{
	row.attr("dtremoved", "1");
	row.hide();
	$("[fndatatable]").each ( function ()
	{
		if ( $(this).attr("fndatatable") == parent.attr("id")){
			refreshComputedField ($(this));	
		}
	});
}

function clearTemplateCellFields( tdobj)
{
	tdobj.find('[docovafield]').each ( function () 
    {
    	var id = $(this).attr("id");
    	var ischeckbox = false;
    	if ( $(this).hasClass("checkradio"))
    	{
    		var crobj = getCheckRadioId(this);
    		id = crobj.id;
    		if ( crobj.type != "radio")
    		ischeckbox = true;
    	}
        var tmpid = id;
        if ( id.indexOf ("SPAN") == 0){
            tmpid = id.substring(4);
       	}
       	if ( ischeckbox){
       		Docova.Utils.setField({field: tmpid+"[]", value: "", separator: ","});
       	}else{
       		Docova.Utils.setField({field: tmpid, value: "", separator: ","});
       	}    
       	if ( $(this).hasClass("namepicker") )
        {
        	$("#slContainer"  + tmpid).html("");
        }   
    });
}

function cancelDataIslandEdit ( parent, row)
{
	var templrow = parent.find(".disland_templ_row");
	var removedrow =  parent.find ("tr[removed='1']");
	removedrow.show();
	removedrow.removeAttr("removed");
	templrow.children("td").each( function () 
	{
		clearTemplateCellFields ( $(this));
    });


	templrow.insertAfter(templrow.siblings(":last"));
	templrow.attr("edited", "0");
	templrow.attr("editedid", "");
}

function loadDataIslandData()
{

	$(".datatable").each ( function () {
		var fldname = $(this).attr("id") + "_values" ;
		var datajson = $("#" + fldname);
		datajson = datajson.text();
		if ( datajson != "" )
		{
			var curvaljson = JSON.parse(decodeURIComponent(datajson));
			for ( var p = 0; p < curvaljson.length; p ++ ){
				var rowobj = curvaljson[p];
				if ( rowobj.status != "removed")
				{
					saveDataIslandRow ($(this), rowobj);
				}
			}
		}
		if (! docInfo.isDocBeingEdited)
			$(this).find(".disland_templ_row").hide();
	})
}

function getCheckRadioId(obj)
{
	var inp = $(obj).find("input:first");
	id = inp.attr("name");
	if ( inp.attr("type") != "radio") {
		id = id.substring(0, id.length - 2);
	}
	return {"id" : id, "type" :inp.attr("type") } ;
}

function editDataIslandRow(parent, rowobj)
{
	var colno = 0;
	var curarr = [];
	var currarr = JSON.parse(decodeURIComponent($(rowobj).attr("valobj")))
	var templrow = parent.find(".disland_templ_row");
	if ( templrow.attr("edited") == "1")
	{
		//check if something is being edited currently
		return false;
	}

	templrow.children("td").each( function () 
	{
		 $(this).find('[docovafield]').each ( function () 
        {
        	var id = $(this).attr("id");
            var ischeckbox = false;
        	if ( $(this).hasClass("checkradio"))
        	{
        		var crobj = getCheckRadioId(this);
        		id = crobj.id;
        		if ( crobj.type != "radio")
        		ischeckbox = true;
        	}else if ( ( $(this).hasClass("namepicker") ) )
            {
            	if ( $(this).attr("selectiontype") != "single"  ){
            		id = $(this).attr("target");
            	}
            }
        	var tmpid = id;
            if ( id.indexOf ("SPAN") == 0){
                tmpid = id.substring(4);
            }
            var found = false;
            
         
            var origval = getDataTableValue ( currarr, tmpid);
            if ( ischeckbox){
            	Docova.Utils.setField({field: tmpid+"[]", value: origval, separator: ","});
            	
            }else{
            	//$$SetField(tmpid, origval);
            	Docova.Utils.setField({field: tmpid, value: origval});
            	if ( $(this).hasClass("namepicker")){
            		if ( $(this).attr("selectiontype") != "single"  )
            		{
            			var mvsep = $(this).attr("mvsep");
			        	if ( mvsep && mvsep != "" )
			        	{
				        	var dsp_delimiter = $(this).attr('mvdisep');
							if (dsp_delimiter && dsp_delimiter.indexOf('semicolon') == 0) {
								dsp_delimiter = ';';
							}
							else if (dsp_delimiter && dsp_delimiter.indexOf('comma') == 0) {
								dsp_delimiter = ',';
							}
							else if (dsp_delimiter && dsp_delimiter.indexOf('newline') == 0) {
								dsp_delimiter = '<br>';
							}
							else if (dsp_delimiter && dsp_delimiter.indexOf('blankline') == 0) {
								dsp_delimiter = '<br><br>';
							}
							else if (dsp_delimiter && dsp_delimiter.indexOf('space') == 0) {
								dsp_delimiter = '&nbsp;';
							}
						}
            			var list = origval.split(dsp_delimiter);
            			var htmlstr = "";
            			for ( var m = 0; m < list.length; m ++ ){
            				htmlstr += '<span>' + list[m] + '<i class="far fa-times removename"></i></span>';
            			}
            			$("#slContainer" + id).html ( htmlstr );
            		}
            	}
            	
            }
            
        });
		colno ++;
	});
	
	templrow.insertBefore(rowobj);
	templrow.attr("editedid", rowobj.attr("docid"));
	rowobj.attr("removed", "1");	
	rowobj.hide();
	templrow.attr("edited", "1");
}

function getDataTableValue ( rowobj, id)
{
	var found = false;
	var origval = "";
	for ( var k =  0; k < rowobj.fields.length; k ++){
		if ( rowobj.fields[k].id == id){
			origval = rowobj.fields[k].value;	
			break;
		}	
	}
    return origval;
}

function saveDataIslandRow (parentobj, rowobj)
{
	var colno = 0;
	var templrow = parentobj.find(".disland_templ_row");
	var colarr = [];
	var newrow = "<tr>";
	var valjson  = {};
	if ( rowobj )
	{
		valjson.docid = rowobj.docid;
	}else{
		//validate required before saving
		var inpcoll = parentobj.find(":input[isrequired='Y']");
		if ( ! validateRequiredFields(inpcoll, false)) return false;
		valjson.docid = templrow.attr("editedid") ? templrow.attr("editedid") : "";
	}
	valjson.fields = [];
	templrow.children("td").each( function () 
	{
		var templatetd = $(this);
		var newtd = templatetd.clone();
		
        $(this).find('[docovafield]').each ( function () 
        {
        	var id = $(this).attr("id");
            var ischeckbox = false;
            var ischeckradio = false;
        	//for radio/checkboxes we need to get the id from the input element not the table itselt
        	if ( $(this).hasClass("checkradio"))
        	{
        		var crobj = getCheckRadioId(this);
        		id = crobj.id;
        		if ( crobj.type != "radio"){
        			ischeckbox = true;
        		}
        		ischeckradio = true;
        	}

            var tmpid = id;
             if ( id.indexOf ("SPAN") == 0){
                tmpid = id.substring(4);
            }
            var value = "";
            if ( rowobj )
            {
            	value = getDataTableValue(rowobj, tmpid);
            } else {
            	if ( $(this).attr("elem") == "date")
            	{
            		value = $("#" + tmpid).val();
            	}else{
            		if ( $(this).hasClass("namepicker") )
            		{
            			if ( $(this).attr("selectiontype") != "single"  ){
            				tmpid = $(this).attr("target");
            			}
            		}
            		value = $$GetField(tmpid);	
            	}
            	
            }
	        value = value && value != "" ? value : "";
	        if ( Array.isArray(value) )
	        {
	        	var mvsep = $(this).attr("mvsep");
	        	if ( mvsep && mvsep != "" )
	        	{
		        	var dsp_delimiter = $(this).attr('mvdisep');
					if (dsp_delimiter && dsp_delimiter.indexOf('semicolon') == 0) {
						dsp_delimiter = ';';
					}
					else if (dsp_delimiter && dsp_delimiter.indexOf('comma') == 0) {
						dsp_delimiter = ',';
					}
					else if (dsp_delimiter && dsp_delimiter.indexOf('newline') == 0) {
						dsp_delimiter = '<br>';
					}
					else if (dsp_delimiter && dsp_delimiter.indexOf('blankline') == 0) {
						dsp_delimiter = '<br><br>';
					}
					else if (dsp_delimiter && dsp_delimiter.indexOf('space') == 0) {
						dsp_delimiter = '&nbsp;';
					}
		        	value = value.join(dsp_delimiter);
		        }else{
		        	value = value.join(",");
		        }
		    }

            valjson.fields.push ( {  "id": tmpid, "value": value});

            if ( ischeckradio)
            {
            	if ( ischeckbox){
            		var tmpinp = newtd.find("input[name='" + tmpid + "[]']:first");	
            	}else{
            		var tmpinp = newtd.find("input[name='" + tmpid + "']:first");	
            	}
            	
            	var replacenode = tmpinp.parents(".checkradio");
            }else{
            	var replacenode = newtd.find("#" + id);
            }
            newtd.find(".custom-combobox").remove();
            if ( $(this).hasClass("namepicker") )
            {
            	//remove the names address lookup button
            	newtd.find("[tiedto='" + id + "']").remove();
            	newtd.find(".slContainer").remove();
            	if ( $(this).attr("selectiontype") != "single" ){
            		tmpid = $(this).attr("target");
            		newtd.find("#" + tmpid).remove();	
            	}
            	
            }
            
            replacenode.replaceWith(value);
        });

       newrow += "<td class='diitem' style='" + ($(this).attr("style") && $(this).attr("style") != "" ? $(this).attr("style") : "" )  + "' >" + newtd.html() + '</td>';
       colno ++;
       clearTemplateCellFields($(this));    
	});
	newrow += "</tr>";
	var jsonstr = encodeURIComponent(JSON.stringify (valjson));

	var isedited = templrow.attr("edited") && templrow.attr("edited") == "1" ?  true : false;
	if (rowobj){
		$(newrow).insertBefore ( templrow).attr({"valobj": jsonstr , "docid": valjson.docid});	
	}else{
		$(newrow).insertBefore ( templrow).attr({"valobj": jsonstr , "docid": valjson.docid, "modified" : "1"});	
	}
	
	
	
	if (isedited)
	{
		templrow.insertAfter(templrow.siblings(":last"));
		templrow.attr("edited", "0");
		templrow.attr("editedid", "");
		templrow.parent().find("[removed]").remove();
	}

	if ( docInfo.isDocBeingEdited == "true") 
	{
		$("[fndatatable]").each ( function ()
		{
			if ( $(this).attr("fndatatable") == parentobj.attr("id")){
				refreshComputedField ($(this));	
			}
		});
	}
}

function refreshComputedField ( inelem )
{
	var fformula = inelem.attr("fnrefreshscript");
	if ( fformula && fformula != "")
	{	
		try {
			var retval = eval ( fformula);
			var elmId = inelem.attr("id");
			if (elmId.indexOf('SPAN') == 0) {
				elmId = elmId.substring(4);
			}
			Docova.Utils.setField({'field' : elmId, 'value' : retval});
			if ($(inelem).attr('numformat') == 'auto') {
				var numdec = inelem.attr("textnumdecimals");
				var fnum = Docova.Utils.formatNumber({
					numstring: retval.toString(),
					numdecimals: numdec,
					decimalsymbol: docInfo.DecimalSeparator,
					thousandsseparator: docInfo.ThousandsSeparator
				});
				if ($.trim(inelem.attr('placeholder')) == '' || fval !== "") {
					Docova.Utils.setField({'field' : elmId, 'value' : fnum});
				}
			}
		}catch(e){
			alert ( "Error running refresh code " + e);
		}
	}
}


function InitAppBuilderForm() {
	$("#tabs").tabs();

	var vermargin = $(".grid-stack").attr("horizontalSpacing");
  	vermargin = vermargin ? vermargin : "12";
  	vermargin = parseInt(vermargin);

 	var options = {
        verticalMargin: vermargin,
        disableDrag: true,
        cellHeight:10,
        disableResize : true
    };
 	
 	if(!((typeof info !== "undefined" && typeof info.isMobile !== "undefined" && info.isMobile === true) || (typeof info.ViewType !== 'undefined' && info.ViewType == 'Gantt'))){
 		$('.grid-stack').gridstack(options);


 		//if only one panel, then grow it to the size of the content.
 		if ( $(".grid-stack-item").length == 1 )
 		{

 	       $('.grid-stack').css("height", 'auto');
 	      
 	       $('.grid-stack-item-content').css("position", "relative");
 	       $('.grid-stack-item').css("position", "relative")
 	       $('.grid-stack-item').css("height", "100%");

        $('.grid-stack-item-content').css("margin-right", $('.grid-stack').attr("horizontalSpacing") + "px");
 	      $('.grid-stack-item-content').css("margin-left", $('.grid-stack').attr("horizontalSpacing") + "px");
 	      $('.grid-stack-item-content').css("left", "0px ");
 	      $('.grid-stack-item-content').css("right", "0px");
 	      $('.grid-stack-item').css("position", "relative");
 	   } 		
 	}

	//For sections
	$("#sectionAdvancedComments").accordion();
	
	//apply jQuery button styles to all dtoolbar buttons (not in Action Bar)
	$('.dtoolbar').each(function(){
		if ($(this).attr('elem') == 'button' || $(this).attr('elem') == 'picklist')
		{
			$(this).attr('class', 'btn dtoolbar');
			var primary = $(this).attr('elem') == 'picklist' ? $(this).attr('plprimaryicon') : $(this).attr('btnprimaryicon');
			var secondary = $(this).attr('elem') == 'picklist' ? $(this).attr('plsecondaryicon') : $(this).attr('btnsecondaryicon');
			var label = $(this).attr('elem') == 'picklist' ? $(this).attr('pllabel') : $(this).attr('btnlabel');
			primary = $.trim(primary) != '' ? primary : '';
			secondary = $.trim(secondary) != '' ? secondary : '';
			if ($(this).attr('elem') == 'picklist') {
				label = $(this).attr('plshowlabel') == '1' ? label : '';
			}
			else {
				label = $(this).attr('btntext') == '1' ? label : '';
			}
			$(this).button({
				text: $.trim(label) ? true : false,
				label: $.trim(label),
				icons: {
					primary: primary,
					secondary: secondary
				}
			});
		}
	});

	$("#btnMore").button({
		icons: {
			primary: "ui-icon-circle-arrow-e"
		},
		label: docInfo.MoreSectionLabel,
		text: false
	}).click(function (event) {
		ToggleOptions();
		event.preventDefault();
	})

	$("button").button().click(function (event) {
		event.preventDefault();
	}).addClass("dtoolbar");

	//Activities and Print buttons
	$("#btnShowActivities").button({
		icons: {
			primary: "ui-icon-alert"
		},
		label: docInfo.HasActivities,
		text: true
	}).click(function (event) {
		displayResponseActivities();
		event.preventDefault();
	}).tooltip();

	$("#btnCreateActivity").button({
		icons: {
			primary: "ui-icon-script"
		}
	}).click(function (event) {
		CreateActivity();
		event.preventDefault();
	}).tooltip();

	$("#btnPrintPage").button({
		icons: {
			primary: "ui-icon-print"
		}
	}).click(function (event) {
		printPage();
		event.preventDefault();
	}).tooltip();

	$("#btnDocComments").button({
		icons: {
			primary: "far fa-comments"
		}
	}).click(function (event) {
		HideShowDocComments();
		event.preventDefault();
	})

	// Address dialog buttons
	$("#btnOriginalAuthor").button({
		text: false
	}).click(function (event) {
		var restricttomembers = (docInfo.MembersEnabled) ? docInfo.LibraryKey : "";
		window.top.Docova.Utils.showAddressDialog({
			fieldname: "OriginalAuthor",
			dlgtype: "single",
			sourcedocument: document,
			"restricttolibrarymembers": restricttomembers
		});
		event.preventDefault();
	});
	$("#btnDocumentOwner").button({
		text: false
	}).click(function (event) {
		var restricttomembers = (docInfo.MembersEnabled) ? docInfo.LibraryKey : "";
		window.top.Docova.Utils.showAddressDialog({
			fieldname: "DocumentOwner",
			dlgtype: "single",
			sourcedocument: document,
			"restricttolibrarymembers": restricttomembers
		});
		event.preventDefault();
	});
	$("#btnAuthors").button({
		text: false
	}).click(function (event) {
		var restricttomembers = (docInfo.MembersEnabled) ? docInfo.LibraryKey : "";
		window.top.Docova.Utils.showAddressDialog({
			fieldname: "Authors",
			dlgtype: "multi",
			sourcedocument: document,
			"restricttolibrarymembers": restricttomembers
		});
		event.preventDefault();
	});
	$("#btnReaders").button({
		text: false
	}).click(function (event) {
		var restricttomembers = (docInfo.MembersEnabled) ? docInfo.LibraryKey : "";
		window.top.Docova.Utils.showAddressDialog({
			fieldname: "Readers",
			dlgtype: "multi",
			sourcedocument: document,
			"restricttolibrarymembers": restricttomembers
		});
		event.preventDefault();
	});
	$("#btnReviewers").button({
		text: false
	}).click(function (event) {
		var restricttomembers = (docInfo.MembersEnabled) ? docInfo.LibraryKey : "";
		window.top.Docova.Utils.showAddressDialog({
			fieldname: "Reviewers",
			dlgtype: "multi",
			sourcedocument: document,
			"restricttolibrarymembers": restricttomembers
		});
		event.preventDefault();
	});
	$(".btnAddrButton").button({ //For custom address dialog buttons created with Designer
		text: false
	}).click(function (event) {
		var fname = $(this).attr("fname");
		var dtype = $(this).attr("dtype");
		var restricttomembers = (docInfo.MembersEnabled) ? docInfo.LibraryKey : "";
		window.top.Docova.Utils.showAddressDialog({
			fieldname: fname,
			dlgtype: dtype,
			sourcedocument: document,
			"restricttolibrarymembers": restricttomembers
		});
		event.preventDefault();
	});

	$("#btnAddComment").button({
		icons: {
			primary: "far fa-comment"
		},
		label: "Add Comment",
		text: false
	}).click(function (event) {
		LogAdvancedComment(true)
		event.preventDefault();
	});
	$("#btnDeleteComment").button({
		icons: {
			primary: "far fa-trash"
		},
		label: "Delete Comment",
		text: false
	}).click(function (event) {
		DeleteComment()
		event.preventDefault();
	});

	if (_DOCOVAEdition == "SE") {
		//move all subform action buttons to forms action bar
		$(".subformactionbar").each(function () {
			$('#tdActionBar').append($(this).html());
			$(this).remove();
		});
	}

	$('#tdActionBar a').each(function(index,element) {
   		$(element).button({
			text: $.trim($(this).text()) && $(this).attr('btntext') == 1 ? true : false,
			label: $.trim($(this).text()),
			icons: {
		 		primary: ($.trim($(this).attr('primary'))) ? $(this).attr('Primary') : null,
				secondary: ($.trim($(this).attr('secondary'))) ? $(this).attr('secondary') : null
			}
		}).addClass("dtoolbar");
	});
	
	
	if ($("#tdActionBar > a").length == 0) {
		$("#FormHeader").hide();
		$("#divFormContentSection").css("top", "0px");
	}else{
		 $('#FormHeader').show();
	}

	//---Document Comments related items
	//---Set post button---
	$("#btn-main-comment-post-button")
	.button({
		text: true,
		label: "Post",
		icons: {
			primary: "far fa-comment"
		}
	})
	.click(function (event) {
		event.preventDefault();
		postMainComment();
	});

	//Start by disabling the main comment post button
	$("#btn-main-comment-post-button").button("disable");

	$("#main-comment-text-input").on("keyup", function () {
		if ($(this).text() == "") {
			$("#main-comment-placeholder-text").css("display", "block");
			$("#btn-main-comment-post-button").button("disable");
		} else {
			$("#main-comment-placeholder-text").css("display", "none");
			$("#btn-main-comment-post-button").button("enable");
		}
	});

	$("#comment-list").on("click", function () {
		$("[elem=thread-box]").each(function () {
			if ($(this).children("table").css("display") != "none") {
				$(this).children("table").css("display", "none")
				$(this).find(".comment-delete").css("display", "none");
			}
		});
	});

	//Get number of comments and show in comment button only if comments are enabled on a form
	if ($("#pageProperties").attr("enablecomments") == "on") {
		getDocumentCommentCount();
	}

	//app builder..if mode is appPreview, then we hide the action Bar...here we adjust the top to take that into account
	if (docInfo.Mode == "apppreview") {
		$("#divFormContentSection").css("top", "0");
	}

	$('#divFormContentSection').bind('keydown', function (event) {
		//the following commented code is to trap the Ctrl+s key combo, but is not preventing the browser save dialog from popping up.
		/*	if(event.ctrlKey && event.which === 83 && docInfo.isDocBeingEdited){ // Check for the Ctrl key being pressed, and if the key = [S] (83)
		event.preventDefault();
		Docova.currentUIDocument.save();
		return false;
		} */
		if (event.which === 27) { // Check for the Esc key being pressed
			Docova.getUIDocument().close()
		}
	});

	//Give focus to divFormContentSection for keypress functions
	$('#divFormContentSection').focus();


	//set all tables to be 100%
	$("table").not("table.noresize").css("width", "100%");

	convertRTtoHTML();

	if (docInfo.isDocBeingEdited) {
		//APP BUILDeR- Load all values from hidden fields that store the checkbox and radio button values.

		ProcessCheckAndRadio();
		try {
			//APP Builder - Process TinyMce
			ProcessTinyMCE();
		} catch (e) {}
	}
	//this function is stored in the sfCustomSection-AppDocovaEditor
	try {
		//APP Builder - Process Docova Editor
		ProcessDocovaEditor();
	} catch (e) {
		alert(e);
	}

	//this code handle tabs initiation
	$("#divDocPage [elem=tabset]").tabs( { activate: function( event, ui ) { $(ui.newTab).removeClass("dTabInactive").addClass("dTabActive");  $(ui.oldTab).removeClass("dTabActive").addClass("dTabInactive")}

	})

	//add style classes to tabs
	$("#divDocPage [elem=tabset]").each ( function () {
		$(this).find("li.ui-tabs-active").addClass("dTabActive");
		$(this).find("li:not(.ui-tabs-active)").addClass("dTabInactive");
		$(this).find("ul.ui-tabs-nav").addClass("dTabHeader");
	})

	if (_DOCOVAEdition == "Domino") {
		//handle images
		$("img[elem=image]").each(function () {
			var srcpath = "/" + docInfo.NsfName + "/" + $(this).attr("imagename");
			$(this).prop("src", srcpath);
		});
	}

	//handle buttons
	$("button[elem=button]").each(function () {

		var showText = ($(this).attr("btntext") == "0") ? false : true;
		var label = $(this).attr("btnlabel");
		var primaryicon = $(this).attr("btnprimaryicon");
		var secondaryicon = $(this).attr("btnsecondaryicon");
		$(this).button({
			text: showText,
			icons: {
				primary: primaryicon,
				secondary: secondaryicon
			},
			label: label
		});
	})

	var seccount = 1;
	//handle any expand/collapse sections
	jQuery("div[expandcollapse='1']").each(function () {

		var isca = $(this).attr("enablecasection") == "1" ? true : false;
		var title = $(this).attr("sectiontitle");
		var collapseonOpen = $(this).attr("cacop");
		var caheaderwidth = $(this).attr("caheaderwidth");

		var icn = "fa-caret-down";
		if (collapseonOpen == "1") {
			var icn = "fa-caret-right";
		}

		var tmpheaderhtml = "<div id='caheader" + seccount + "' class='ui-widget-header dWidgetHeader' style='line-height:25px;cursor:pointer;' ><i class='fas " + icn + "' style='padding-left:10px;'></i><span style='padding-left:8px'>" + title + "</span></div>";
		if (_DOCOVAEdition == "SE") {
			$(this).parent().prepend(tmpheaderhtml);
		} else {
			$(this).parent().parent().prepend(tmpheaderhtml);
		}

		if (caheaderwidth) {
			if (_DOCOVAEdition == "SE") {
				//$(this).closest("div.sectioncontent").css("width", caheaderwidth);
			} else {
				$(this).closest("div.cacontainer").css("width", caheaderwidth);
			}
		}
		if (collapseonOpen == "1") {
			$("#caheader" + seccount).next().hide();
		}
		$("#caheader" + seccount).click(function () {

			$header = $(this);
			//getting the next element
			$content = $header.next();
			var fai = $header.children().first();
			if ($content.is(":visible")) {
				fai.removeClass("fa-caret-down").addClass("fa-caret-right");
			} else {

				fai.removeClass("fa-caret-right").addClass("fa-caret-down");
			}
			//open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
			$content.slideToggle(500, function () {});

		});
		seccount++;
	});

	//this code handles all the names fields
	//TODO - check if this difference between SE and Domino still applies
	if (_DOCOVAEdition == "SE") {
		$("input.namepicker").each(function () {
			var seltype = $(this).attr("selectiontype");
			seltype = seltype && seltype == "single" ? "single" : 'multi';
			var type = seltype == 'single' ? 'single' : 'multiple';
			var containerSelector = seltype == 'single' ? '' : $(this).attr('target');
			var delimiter = $(this).attr('mvsep') || ";";
			delimiter = delimiter.split(" ")[0];
			
			var dsp_delimiter = $(this).attr('mvdisep');
			if (dsp_delimiter && dsp_delimiter.indexOf('semicolon') == 0) {
				dsp_delimiter = ';';
			}
			else if (dsp_delimiter && dsp_delimiter.indexOf('comma') == 0) {
				dsp_delimiter = ',';
			}
			else if (dsp_delimiter && dsp_delimiter.indexOf('newline') == 0) {
				dsp_delimiter = '<br>';
			}
			else if (dsp_delimiter && dsp_delimiter.indexOf('blankline') == 0) {
				dsp_delimiter = '<br><br>';
			}
			else if (dsp_delimiter && dsp_delimiter.indexOf('space') == 0) {
				dsp_delimiter = '&nbsp;';
			}
			
			if ($.isFunction($.fn.autoComplete)) {
				$(this).autoComplete({
					url: ('/' + docInfo.NsfName + '/getSearchNames'),
					//			ristrictTo: nameList,
					shortName: (docInfo.ShortName == '1' ? true : false),
					selectionContainer: containerSelector,
					type: type,
					delimiter: dsp_delimiter
				});
			}

			var id = $(this).attr("id");
			var fname = $(this).attr('target');
			var btn = $("button[tiedto='" + id + "']");
			if (btn.length > 0) {
				btn.click(function (event) {
					window.top.Docova.Utils.showAddressDialog({
						fieldname: fname,
						dlgtype: seltype,
						separator: delimiter,
						sourcedocument: document
					});
					event.preventDefault();
				});
			}

		});
	} else {
		$("textarea[elem='names'],input[namesselectiontype=single],input[namesselectiontype=multi],textarea[namesselectiontype=multi]").each(function () {
			var seltype = $(this).attr("namesselectiontype");
			seltype = seltype && seltype != "" ? seltype : "single";
			var id = $(this).attr("id");
			var btn = $("button[tiedto='" + id + "']");
			if (btn.length > 0) {
				btn.click(function () {
					window.top.Docova.Utils.showAddressDialog({
						fieldname: id,
						dlgtype: seltype,
						sourcedocument: document
					});
					event.preventDefault();
				});
			}
		});
	}

	//this code handles the date fields
	//TODO - check if this difference between SE and Domino still applies
	$((_DOCOVAEdition == "SE" ? "input.docovaDatepicker" : "input[elem='date']")).each(function () {
		var defaultdate = $(this).attr("fdefault");
		defaultdate = defaultdate && defaultdate != "" ? defaultdate : "";
		var dateformat = docInfo.SessionDateFormat;
		dateformat = dateformat.replace("yy", "Y");
		dateformat = dateformat.replace("mm", "m");
		dateformat = dateformat.replace("dd", "d");
		var timeformat = "h:i A";
		var shorttimeformat = "h:i A";			
		
		var choosedate = !($(this).attr("displayonlytime") == "true");
		var choosetime = ($(this).attr("displayonlytime") == "true" || $(this).attr("displaytime") == "true");
		
		$(this).datetimepicker({
			lazyInit: true,
			format: (choosetime && !choosedate ? timeformat : (choosetime && choosedate ? dateformat + " " + timeformat : dateformat)),
			formatDate: dateformat,
			formatTime: shorttimeformat,
			datepicker: choosedate,		
			timepicker: choosetime,
			hours12: false,
			step: 15,
			closeOnDateSelect: true,
			closeOnTimeSelect: true,
			validateOnBlur: false,
			defaultDate: defaultdate
		});		
	});

	//handle number formatting for numbers that are auto formatted by DOCOVA
	if (docInfo.isDocBeingEdited) {
		//if doc is being edited, format numbers if auto is on
		$("#divDocPage [numformat=auto]").each(function () {
			if(this.name){
				var numdec = $(this).attr("textnumdecimals");
				var fval = Docova.Utils.getField(this.name);
				if(typeof fval !== "undefined" && fval !== null){
					var fvallist = fval.split(";");
					for(var i=0; i<fvallist.length; i++){
						var fnum = Docova.Utils.formatNumber({
							numstring: fvallist[i],
							numdecimals: numdec,
							decimalsymbol: docInfo.DecimalSeparator,
							thousandsseparator: docInfo.ThousandsSeparator
						});
						fvallist[i] = fnum;
					}
					if ($.trim($(this).attr('placeholder')) == '' || fval !== "") {
						Docova.Utils.setField({'field' : this.name, 'value' : fvallist});
					}
				}
			}
		});
	} else {
		$(".dspNumber[numformat=auto]").each(function () {
		    var fval = $(this).text();
			var fnum = Docova.Utils.formatNumber({
				numstring: fval,
				numdecimals: $(this).attr("textnumdecimals"),
				decimalsymbol: docInfo.DecimalSeparator,
				thousandsseparator: docInfo.ThousandsSeparator
			});
			if ($.trim($(this).attr('placeholder')) == '' || fval !== "") {
				$(this).text(fnum);
			}
		})
	}

	//handle removal of first extra <br> in divDocPage div
	if (_DOCOVAEdition == "Domino") {
		$('#divDocPage br:first').remove();
	}
	
	//handle slider
	$('DIV.docova_sliderelem').each(function(){
		var $sld_obj = $(this);
		var $boundElem = $('#'+ $sld_obj.attr('boundto'));
		var slider = $sld_obj.slider({
			disabled: ($sld_obj.attr('disabled') && $sld_obj.attr('disabled') == 'true') || !docInfo.isDocBeingEdited ? true : false,
			min: parseInt($sld_obj.attr('minval')),
			max: parseInt($sld_obj.attr('maxval')),
			orientation: $sld_obj.attr('orientation').toLowerCase(),
			value: docInfo.isNewDoc ? parseInt($sld_obj.attr('minval')) : Docova.Utils.getField($sld_obj.attr('boundto')),
			slide: function(e, ui) {
				if (typeof $boundElem.prop('id') != 'undefined') {
					$boundElem.val(ui.value).trigger('change');
				}
			}
		});
		
		if (typeof $boundElem.prop('id') != 'undefined')
		{
			if (docInfo.isNewDoc) {
				$boundElem.val(parseInt($sld_obj.attr('minval')));
			}
			$boundElem.on('change', function(){
				slider.slider('value', $(this).val() + 1 );
			});
		}
		
		if ($sld_obj.attr('activecolor')) {
			slider.find('span.ui-slider-handle').css('background', $sld_obj.attr('activecolor'));
		}
		if (($sld_obj.attr('disabled') && $sld_obj.attr('disabled') == 'true') || !docInfo.isDocBeingEdited) {
			slider.find('span.ui-slider-handle').css('border-color', '#333');
		}
	});

	//handle form style
	$("#divFormContentSection").removeClass("divFormContentSectionPage divFormContentSectionPlain")
	if ($("#pageProperties").attr("formstyle") == "plain") {
		$("#divFormContentSection").addClass("divFormContentSectionPlain")
		$("#divDocPage").css("min-height", "1%");
		$("body").css("background", "#FFFFFF");
	} else {
		$("#divFormContentSection").addClass("divFormContentSectionPage");
	}
	//handle page margins, actually padding on divDocPage div
	$("#divDocPage").css("padding-top", $("#pageProperties").attr("topMargin") + "em")
	$("#divDocPage").css("padding-right", $("#pageProperties").attr("rightMargin") + "em")
	$("#divDocPage").css("padding-bottom", $("#pageProperties").attr("bottomMargin") + "em")
	$("#divDocPage").css("padding-left", $("#pageProperties").attr("leftMargin") + "em")

	//handle showing print button
	$("#pageProperties").attr("printbutton") == "on" ? $("#btnPrintPage").css("display", "") : $("#btnPrintPage").css("display", "none")

	//handle showing doc comments button
	$("#pageProperties").attr("enablecomments") == "on" ? $("#btnDocComments").css("display", "") : $("#btnDocComments").css("display", "none");

	//format any dropdown select fields that should have type to filter or allow new values option
	jQuery("select[elem=select]").each(function () {

		var multival = $(this).attr("mvdisep") && $(this).attr("textmv") == "true" ? true : false;
		var multivalsep = $(this).attr("mvdisep");
		if (multival && multivalsep) {
			if (multivalsep == "comma") {
				multivalsep = ", ";
			} else if (multivalsep == "semicolon") {
				multivalsep = "; ";
			} else if (multivalsep == "space") {
				multivalsep = " ";
			} else if (multivalsep == "newline" || multivalsep == "blankline") {
				multivalsep = "\n ";
			}			
		}
		Docova.Utils.createComboBoxField(this, {
			allowvaluesnotinlist: (jQuery(this).attr("allownewvals") == "1"),
			themefield: false,
			multivalue: multival,
			multivaluesep: multivalsep,
			customwidth: $(this).css('width'),
			additionalstyle: $(this).attr('style'),
			placeholder: $(this).attr('fplaceholder') ? $.trim($(this).attr('fplaceholder')) : ''
		});
	});
	
	//handle disabling elements within any controlled access sections that are currently restricted
	jQuery("div[enablecasection='1'][sectionaccess='0']").find("input, textarea, select").attr("disabled", "disabled");
	jQuery("div[enablecasection='1'][sectionaccess='0']").find("button").attr("disabled", "disabled").addClass("ui-state-disabled");
	jQuery("div[enablecasection='1'][sectionaccess='0']").find("span.custom-combobox").find("input").removeClass("ui-widget-content").attr("disabled", "disabled");
	jQuery("div[enablecasection='1'][sectionaccess='0']").find("span.custom-combobox").find("a").off().attr("disabled", "disabled").addClass("ui-state-disabled");
	jQuery("div[enablecasection='1'][sectionaccess='0']").find("i.removename").remove();
	

	//handle checkboxes and radio buttons inside P elements that that are not in TDs
	$(".keywordscontainer").each(function () {
		//if parent is not a TD then make the keywordscontainer and prev P display:inline-block
		if ($(this).parent().prop("tagName") != "TD") {
			$(this).css("display", "inline-block");
			if ($(this).prev().prop("tagName") == "P") {
				$(this).prev().css("display", "inline-block");
			} else {
				$(this).prevUntil("p").parent().css("display", "inline-block");
			}
		}
	})

	//number field validation
	if (docInfo.isDocBeingEdited) {
		jQuery("input[textrole=n]").on("change", function () {
			var fieldname = this.name;
			var fieldval = $$GetField(fieldname);
			if (!(fieldval === null || 
				(typeof fieldval == "string" && jQuery.trim(fieldval) == "") || 
				(Array.isArray(fieldval) && fieldval.length < 2 && typeof fieldval[0] == "string" && jQuery.trim(fieldvalue[0]) == ""))) {
				if (!$$IsNumber(fieldval)) {
					$$Command("[EditGotoField]", fieldname);
					$$Prompt("[Ok]", "Field Contains Incorrect Value", "Cannot convert text to a number for field [" + fieldname + "].");
				}
			}
		});
	}

	//date field validation
	if (docInfo.isDocBeingEdited) {
		jQuery("input[textrole=t]").on("change", function () {
			var fieldval = jQuery(this).val();
			var ftype = (jQuery(this).attr("displayonlytime") == "true" ? "time" : (jQuery(this).attr("displaytime") == "true" ? "datetime" : "date"));
			if (fieldval !== "") {
				if (fieldval.trim() === "") {
					jQuery(this).val("");
				} else if((ftype == "time" && !$$IsTime(fieldval)) || (ftype != "time" && !$$IsDate(fieldval))) {
					var fieldname = this.name;
					$$Command("[EditGotoField]", fieldname);
					$$Prompt("[Ok]", "Field Contains Incorrect Value", "Cannot convert text to a date/time value for field [" + fieldname + "].");
				}
			}
		});
	}
	
	//toggle switch field (for now jsut in SE)
	if (docInfo.isDocBeingEdited) {
		jQuery('span.docova-toggle').css('cursor', 'pointer');
		jQuery('span.docova-toggle').on('click', function(e){
			e.preventDefault();
			var toggle_input = $(this).attr('id').replace('tg_', '');
			if ($(this).hasClass('fa-toggle-on')) {
				$(this).removeClass('fa-toggle-on');
				$(this).addClass('fa-toggle-off');
				$(this).css('color', $(this).attr('offcolor'));
				$('#' + toggle_input).val('').trigger('change');
			}
			else {
				$(this).removeClass('fa-toggle-off');
				$(this).addClass('fa-toggle-on');
				$(this).css('color', $(this).attr('oncolor'));
				$('#' + toggle_input).val('true').trigger('change');
			}
		});
	}
	
	//handle chart rendering
	processChartElements();

    $('#loading_msg').hide();

	//divDocPage is initially display none, after setting the padding, make it show
	$("#divDocPage").css("display", "");
}


function processChartElements(){

	$("canvas[elemtype=chart]").each(function(){
		var sourcetype = ($(this).attr("chartsourcetype")||"");
		var sourceapp = ($(this).attr("sourceapp")||"");
		var source = ($(this).attr("chartsource")||"");
			
		if(sourcetype == "function"){
			if(source != ""){
				if(typeof window[source] == "function"){
					updateChartElementData(this, window[source]());
				}
			}
		}else if(sourcetype == "view"){
			if(source != ""){	
				var appid = sourceapp;
				if(!appid){
					var sourceappobj = Docova.getApplication();
					appid = sourceappobj.appID;
				}
				if(appid == ""){
					return false;
				}
				
				var charttype = ($(this).attr("charttype")||"");
				
				var viewcat = ($(this).attr("singlecat")||"");
				
				var chartlegenditems = ($(this).attr("chartlegenditems")||"");
				var chartaxisitems = ($(this).attr("chartaxisitems")||"");
				var chartvalueitems = ($(this).attr("chartvalueitems")||"");
				
				if(chartvalueitems == "" || (chartlegenditems == "" && chartaxisitems == "")){
					return false;
				}
				
				var _self = this;
				
				var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/getchartdata";
				
				var request = "";
				request += "<Request>";
				request += "<Action>GETCHARTDATA</Action>";
				request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
				request += "<AppID>" + appid + "</AppID>";
				request += "<SourceType>VIEW</SourceType>";
				request += "<SourceName><![CDATA[" + source + "]]></SourceName>";
				request += "<Category><![CDATA[" + viewcat + "]]></Category>";
				request += "<ChartType>" + charttype + "</ChartType>";				
				request += "<LegendItems><![CDATA[" + chartlegenditems + "]]></LegendItems>";
				request += "<AxisItems><![CDATA[" + chartaxisitems + "]]></AxisItems>";
				request += "<ValueItems><![CDATA[" + chartvalueitems + "]]></ValueItems>";
				request += "</Request>";
				
				jQuery.ajax({
					type: "POST",
					url: url,
					data: request,
					cache: false,
					async: true,
					dataType: "json",
					success: function (tempdata) {
						updateChartElementData(_self, tempdata);	
					},
					error: function () {
						
					}
				});				
			}	
		}else if(sourcetype == "agent"){
			if(source != ""){
				var sourceappobj = Docova.getApplication({"appid": sourceapp});
				var appagent = sourceappobj.getAgent(source);
				var _self = this;
				
				appagent.run(null, true, function(tempdata){
					updateChartElementData(_self, tempdata);	
				});
			}
		}		
	});	
	
}

function updateChartElementData(chartelem, chartdata){
	if(typeof chartelem == "undefined" || typeof chartdata == "undefined" || chartelem == null || chartdata == null){
		return;
	}

	if(typeof chartelem == "string"){
		chartelem = $("#"+chartelem);
	}
	
	var charttype = ($(chartelem).attr("charttype")||"");
	var newcharttype = charttype;
	if(charttype == "barStacked"){
		newcharttype = "bar";
	}else if(charttype == "horizontalBarStacked"){
		newcharttype = "horizontalBar";
	}else if(charttype == "area"){
		newcharttype = "line";
	}
	
	var charttitle = ($(chartelem).attr("charttitle")||"");	
	var charthorzlabel = ($(chartelem).attr("charthorzlabel")||"");	
	var chartvertlabel = ($(chartelem).attr("chartvertlabel")||"");	
	var charthidevalues = ($(chartelem).attr("charthidevalues")||"") ;	
	var charthidelegend = ($(chartelem).attr("charthidelegend")||"") ;	
	
	if(typeof chartdata == "string"){
		try{
			chartdata = JSON.parse(chartdata);
		}catch(e){
			chartdata = null;
		}
	}
	
	//-- check that an object has been passed
	if(typeof chartdata == "object"){
		//-- check that object contains a data property
		if(chartdata.hasOwnProperty("data")){					
			if(! chartdata.hasOwnProperty("type")){
				//--set chart type if not configured by function
				chartdata["type"] = newcharttype;
			}
			if(! chartdata.hasOwnProperty("options")){
				chartdata["options"] = {};
			}
			if(! chartdata["options"].hasOwnProperty("responsive")){
				//--disable responsive property if not already set so that canvas does not resize
				chartdata["options"]["responsive"] = false;
			}
			
			if(! chartdata["options"].hasOwnProperty("scales")){
				chartdata["options"]["scales"] = {};
			}
			
			if(! chartdata["options"].hasOwnProperty("title") && charttitle != ""){
				chartdata["options"]["title"] = {"display": true, "text": charttitle};
			}
			
			//-- dont include x and y axis markers and gridlines for pie, doughnut, polarArea, and radar charts
			var hideaxis = (charttype == "pie" || charttype == "doughnut" || charttype == "polarArea" || charttype == "radar");

			if(! chartdata["options"]["scales"].hasOwnProperty("xAxes")){
				chartdata["options"]["scales"]["xAxes"] = [{}];
			}
			
			if(! chartdata["options"]["scales"]["xAxes"][0].hasOwnProperty("gridLines")){
				if(hideaxis){
					chartdata["options"]["scales"]["xAxes"][0]["gridLines"] = {"display": false, "drawBorder" : false};
				}
			}		
			
			if(! chartdata["options"]["scales"]["xAxes"][0].hasOwnProperty("ticks")){
				if(hideaxis){
					chartdata["options"]["scales"]["xAxes"][0]["ticks"] = {"display": false};	
				}else{
					chartdata["options"]["scales"]["xAxes"][0]["ticks"] = {"beginAtZero": true};					
				}
			}
				
			if(! chartdata["options"]["scales"]["xAxes"][0].hasOwnProperty("scaleLabel") && charthorzlabel != ""){
				chartdata["options"]["scales"]["xAxes"][0]["scaleLabel"] = {"display": true, "labelString" : charthorzlabel};
			}

				
			if(! chartdata["options"]["scales"].hasOwnProperty("yAxes")){
				chartdata["options"]["scales"]["yAxes"] = [{}];
			}

			if(! chartdata["options"]["scales"]["yAxes"][0].hasOwnProperty("gridLines")){
				if(hideaxis){
					chartdata["options"]["scales"]["yAxes"][0]["gridLines"] = {"display": false, "drawBorder" : false};										
				}
			}			
			
			if(! chartdata["options"]["scales"]["yAxes"][0].hasOwnProperty("ticks")){
				if(hideaxis){
					chartdata["options"]["scales"]["yAxes"][0]["ticks"] = {"display": false};
				}else{
					chartdata["options"]["scales"]["yAxes"][0]["ticks"] = {"beginAtZero": true};
				}
			}
				
			if(! chartdata["options"]["scales"]["yAxes"][0].hasOwnProperty("scaleLabel") && chartvertlabel != ""){
				chartdata["options"]["scales"]["yAxes"][0]["scaleLabel"] = {"display": true, "labelString" : chartvertlabel};
			}				
			
			if(! chartdata["options"].hasOwnProperty("legend")){
				chartdata["options"]["legend"] = {};				
			}
			if(charthidelegend){
				chartdata["options"]["legend"]["display"] = false;
			}
			
			
			
			if(! chartdata["options"].hasOwnProperty("animation")){
				chartdata["options"]["animation"] = {};
			}
			
			//-- for pie and doughnut charts display values in the chart
			if((charttype == "pie" || charttype == "doughnut") && !charthidevalues){
				if(! chartdata["options"]["animation"].hasOwnProperty("onProgress")){
					chartdata["options"]["animation"]["onProgress"] = function (animation) {							
							  var ctx = animation.chart.ctx;
						      ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
						      ctx.textAlign = 'center';
						      ctx.textBaseline = 'bottom';

						      animation.chart.data.datasets.forEach(function (dataset) {
						        for (var i = 0; i < dataset.data.length; i++) {
						        	if(!dataset._meta[Object.keys(dataset._meta)[0]].data[i].hidden){
						        	  var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model;
						        	  var total = dataset._meta[Object.keys(dataset._meta)[0]].total;
						        	  var mid_radius = model.innerRadius + (model.outerRadius - model.innerRadius)/2;
						        	  var start_angle = model.startAngle;
						        	  var end_angle = model.endAngle;
						        	  var mid_angle = start_angle + (end_angle - start_angle)/2;

						        	  var x = mid_radius * Math.cos(mid_angle);
						        	  var y = mid_radius * Math.sin(mid_angle);

						        	  ctx.fillStyle = '#696969';
						         
						        	  var percent = String(Math.round(dataset.data[i]/total*100)) + "%";
						        	  ctx.fillText(dataset.data[i], model.x + x, model.y + y);
						        	  // Display percent in another line, line break doesn't work for fillText
						        	  ctx.fillText(percent, model.x + x, model.y + y + 15);
						          }
						        }
						   });
					}
				}				
			}
			
			//-- some special handling for stacked bar
			if(charttype == "barStacked" || charttype == "horizontalBarStacked"){
				if(! chartdata["options"]["scales"]["xAxes"][0].hasOwnProperty("stacked")){
					chartdata["options"]["scales"]["xAxes"][0]["stacked"] = true;
				}
												
				if(! chartdata["options"]["scales"]["yAxes"][0].hasOwnProperty("stacked")){
					chartdata["options"]["scales"]["yAxes"][0]["stacked"] = true;
				}
			}
			
			//-- set some defaults
			if(charttype == "line"){
				Chart.defaults.global.elements.line.fill = false;	
				Chart.defaults.global.elements.line.lineTension = 0.1;
				Chart.defaults.global.elements.line.tension = 0.1;
				Chart.defaults.line.spanGaps = true;
			}else if(charttype == "area"){
				Chart.defaults.global.elements.line.fill = true;	
				Chart.defaults.global.elements.line.lineTension = 0.1;
				Chart.defaults.global.elements.line.tension = 0.1;
			}
			
			new Chart(chartelem, chartdata);
		}
	}
	
}

function renderDynamicSelect(values, obj) {
	if (!values || values == ""){
		return;
	}
	obj.empty();
	var optionlistarray = values.split(";")
	obj.append($("<option></option>").attr("value", "").text(""));
		//obj.append($("<option selected></option>").attr("value", "-Select-").text("-Select-"));
	for (var x = 0; x < optionlistarray.length; x++) {
		if (optionlistarray[x] != "") {
			if (optionlistarray[x].indexOf("|") != "-1") { //if alias/synonyms where used then
				var singleoptionarray = optionlistarray[x].split("|")
					obj.append($("<option></option>").attr("value", singleoptionarray[1]).text(singleoptionarray[0]));
			} else {
				obj.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
			}
		}
	}

	//set defaults for looked up keywords selection field
	if ($(obj).attr("fdefault") != "") {
		var defaultval = $(obj).attr("fdefault");
		if (!defaultval || defaultval == ""){
			return;
		}
		var fdefaultarray = defaultval.split(",")
		$(obj).find("option").each(function () {
			var fldval = $(this).val();
			if ($.inArray(fldval, fdefaultarray) != -1) {
				$(this).prop("selected", "true");
			}
		})
	}
}

function getRadioCheckTable(values, ColumnNumber, elemType, elemName, jsEvents) {
	var optionlistarray = values.split(";");
	var ColumnCount = 1
	//-----Set element foptionlist to the newly entered list -----
	var colarray = new Array()
	//-----Initialize array with blanks-----
	for (var k = 0; k < ColumnNumber; k++) {
		colarray[k] = "";
	}
	var newoptionHTML = ""
	var idx
	var isalias = false;
	var selval = "";
	for (var x = 0; x < optionlistarray.length; x++) {
		if (optionlistarray[x] != "") {
			if (optionlistarray[x].indexOf("|") != -1) { //if alias is present then split option
				isalias = true;
				var cboptionarray = optionlistarray[x].split("|");
				var cbtext = cboptionarray[0]
				var cbvalue = cboptionarray[1]
				if (cboptionarray.length == 3) {
					selval = cboptionarray[2];
					if (selval == "true")
						selval = "checked"
				}
			} else {
				isalias = false;
				var cbtext = optionlistarray[x]
			}

			idx = ColumnCount - 1;
			colarray[idx] += "<input " + selval + " type='" + elemType + "' name='" + elemName + "' ";
			colarray[idx] += " value='" + (isalias ? cbvalue : cbtext) + "' ";
			if (typeof jsEvents != 'undefined') {
				for (var eventName in jsEvents) {
					if (jsEvents.hasOwnProperty(eventName)) {
						if (jsEvents[eventName] !== "") {
							colarray[idx] += " " + eventName + "=\'" + safe_quotes_js(jsEvents[eventName], false) + "\'";
						}
					}
				}
			}
			colarray[idx] += ">";
			colarray[idx] += "<span style='padding:0 0 0 5;'>" + cbtext + "</span></br>";

			if (ColumnCount == ColumnNumber) {
				ColumnCount = 1
			} else {
				ColumnCount++
			}
		}
	}
	//-----Generate table html for checkboxes-----
	for (var y = 0; y < ColumnNumber; y++) {
		newoptionHTML += "<td style='padding:0 5 0 5; border:0px;'>" + colarray[y] + "</td>"
	}
	return newoptionHTML;
}

function getDynamicKeywords(obj, inkey) {
	
	var lunsfname = obj.attr("lunsfname");

	
	if (lunsfname == "undefined" || lunsfname == "" || lunsfname == null) {
		lunsfname = "";
	}

	var view = obj.attr("luview");
	var elemName = obj.attr("id");
	var elemType = obj.attr("elem");
	var keyType = obj.attr("lukeytype");

	if (typeof inkey !== "undefined") {
		key = inkey;
	} else {
		if (keyType == "Field") {
			var id = obj.attr("lukeyfield");
			var sourceField = $('#' + id);
			if (sourceField.length > 0) {
				var type = sourceField.attr("elem");
				
				key = sourceField.attr('fvalue');
			
				if (key == ""){
					return;
				}

			}
		} else {
			key = obj.attr("lukey");
			key = key && key != "" ? key : "";
		}
	}

	//clear all the values from the obj
	if (key == "$$clear") {
		if (elemType == "select") {
			obj.empty();
			//obj.append($("<option></option>").attr("value", "-Select-").text("-Select-"));
		} else
			obj.find("tr:first").html("");

		return;
	}
	var colno = obj.attr("lucolumn");

			if ( key && key != ""){
				var values = $$DbLookup("", lunsfname, view, key, colno);
			}else{

				var values = $$DbColumn("", lunsfname, view, colno) ;
			}

	if (values == ""){return;}
	if(Array.isArray(values)){
		values = values.join(";");
	}
	//Make values a unique list, no duplicates
	values = Docova.Utils.unique({
			"inputstr": values,
			"delimiterin": ";",
			"delimiterout": ";"
		});

	if (elemType == "select") {
		renderDynamicSelect(values, obj);
		return;
	}

	var colmns = obj.attr("cbcolumns");
	var ColumnNumber = colmns && colmns != "" ? colmns : 1;
	ColumnNumber = values.split(";").length > ColumnNumber ? ColumnNumber : values.split(";").length;
	var type = obj.attr("elem");
	if ( type == "checkbox"){
		if(_DOCOVAEdition == "SE"){
			elemName = elemName + '[]';
		}
	}
	var jsEvents = {'onClick' : "", 'onChange' : '', 'onFocus' : '', 'onBlur' : ''};
	if(jQuery(obj).attr("onclick")){
		jsEvents.onClick = jQuery(obj).attr("onclick");
		jQuery(obj).attr("onclick", "");
	};
	if(jQuery(obj).attr("onchange")){
		jsEvents.onChange = jQuery(obj).attr("onchange");
		jQuery(obj).attr("onchange", "");
	};
	if(jQuery(obj).attr("onfocus")){
		jsEvents.onFocus = jQuery(obj).attr("onfocus");
		jQuery(obj).attr("onfocus", "");
	};
	if(jQuery(obj).attr("onBlur")){
		jsEvents.onBlur = jQuery(obj).attr("onblur");
		jQuery(obj).attr("onblur", "");
	};
	var newoptionHTML = getRadioCheckTable(values, ColumnNumber, elemType, elemName, jsEvents)

	obj.find("tr:first").html(newoptionHTML);

	//set any default values for checkbox/radio
	if ($(obj).attr("fdefault") != "") {
		var defaultval = $(obj).attr("fdefault");
		if (!defaultval || defaultval == ""){
			return;
		}
		defaultval = defaultval.replace(/"/g, "");
		var fdefaultarray = defaultval.split(",");
		$("input[name=" + elemName + "]").each(function () {
			var fldval = $(this).val();
			if ($.inArray(fldval, fdefaultarray) != -1) {
				$(this).attr("checked", "true");
			}
		});
	}
	return values;
}

//this function updates the checkboxes and radiobuttons to reflect the values
//that have been stored in hidden fields

function ProcessCheckAndRadio() {

	//format check and radio to add the columns into td's for better alignment
	//this function removes the extra p tags that put in since appbuilder adds a <table> element inside a <p> tag..which create a <p> tag before and after the table
	 $(".checkradio").each ( function () { 
		
		var prev = $(this).prev("p");

		if ( prev.length > 0 ){
			prev.remove();
		}

		var next = $(this).next("p");
		if ( next.length > 0 ){
			next.remove();
		}


	 });


	 //handle select/checkbox/radio when used through a lookup to a view/column
	$('[require="initiateInputs"]').each ( function () { 
		getDynamicKeywords($(this));
		var vals = $(this).attr("fvalue");
		var fieldid = $(this).attr("id");
		var fieldtype = $(this).attr("elem");
		if ( fieldtype && fieldtype == "checkbox"){
			if(_DOCOVAEdition == "SE"){
				fieldid += "[]";
			}
		}

		
		
		Docova.Utils.setField({
			field: fieldid,
			value: vals,
			separator: ","
		})
	});

	$('[lukeytype="Field"]').each(function () {
		var key = $(this).attr("lukeyfield");
		var sourceobj = $("#" + key);
		if (sourceobj.length == 0){
			return;
		}
		var objtype = $(sourceobj).attr("elem");
		var targetobj;
		if (objtype && objtype == "checkbox"){
			targetobj = $("input[name='" + key + (_DOCOVAEdition == "SE" ? "[]" : "") + "']");
		}else if (objtype &&  objtype== "radio" ){
			targetobj = $("input[name='" + key + "']");
		} else {
			targetobj = sourceobj;
		}

		targetobj.change(function () {
			var tobj = this;
			var type = $(tobj).attr("type");

			if ( type == "radio"  ){
				var id = $(tobj).attr("name");
			}else if (  type=="checkbox") {
				var id = $(tobj).attr("name");
				if ( id.length > 0 ){
					if(_DOCOVAEdition == "SE"){
						id = id.substring(0, id.length-2);
					}
				}
			} else {
				var id = $(this).attr('id');
			}

			$("[lukeyfield='" + id + "']").each(function () {
				var keyval = "";
				if (type == "radio") {
					if ($(tobj).is(":checked")) {
						keyval = $(tobj).val();
					} else {
						keyval = "$$clear";
					}
				} else if (type == "checkbox") {
					$('input[name="' + id + (_DOCOVAEdition == "SE" ? "[]" : "") + '"]:checked').each(function() {
						if (keyval == "") {
							keyval += this.value;
						} else {
							keyval += ";" + this.value;
						}
					});
				} else {
					keyval = $(tobj).val();
				}

				if (keyval == "-Select-" || keyval == ""){
					keyval = "$$clear";
				}
				var vals = getDynamicKeywords($(this), keyval);

			})
		});
	});
}

//------ document attachment comparison functions --------------------------------------
function CompareVersions() {

	var PDFCreatorAvailable = true;
	//check whether PDF Creator is installed, which is required to view the comparison results
	//unless the user has printing rights, in which case comparison results may be viewed in Word.
	if (!DLExtensions.isPDFCreatorInstalled()) {
		PDFCreatorAvailable = false;
	}

	if (docInfo.RestrictPrinting && PDFCreatorAvailable == false) {
		alert("Unable to run document comparison.  PDF Creator is not installed.");
		return;
	}

	var progressMsg = ""
		var docID;
	var docKey;
	var docLocation;
	var rs = VersionLogData.recordset;
	var idList = new Array();
	var x = 0;
	rs.MoveFirst();
	while (!rs.EOF()) {
		if (rs.getFIELDSCount() > 0) {
			if (rs.Fields("Selected").getValue() == "1") {
				//idList[x] = rs.Fields("ParentDocKey").getValue();
				idList[x] = rs.Fields("ParentDocID").getValue();
				docLocation = rs.Fields("Location").getValue();
				docID = rs.Fields("ParentDocID").getValue();
				x++;
			}
		}
		rs.MoveNext();
	}
	if (idList.length > 1) {
		alert("Please select only one document to compare to the current document");
		return false;
	}
	if (idList.length == 0) {
		alert("Please select a document from the Version History tab to compare this document to.");
		if ($("#btnMore").attr("isOpen") == "false") { //ensure More section is open
			ToggleOptions();
		}
		return false;
	}
	if (docID == docInfo.DocID) {
		alert("You selected the current document.  Please select a different version.")
		return;
	}

	docKey = idList[0];

	//-----------------------------------------------------------
	//check current user access
	progressMsg += "Verifying you access to the files....<br>";
	Docova.Utils.showProgressMessage(progressMsg);
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/AccessServices?OpenAgent";
	var request = "";

	request += "<Request>";
	request += "<Action>QUERYACCESS</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docID + "</Unid>";
	request += "<Location>" + docLocation + "</Location>";
	request += "<DocKey>" + docKey + "</DocKey>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var accessLevel = xmlobj.find('Results Result[ID=Ret1]').text()
					if (accessLevel == 0) {
						Docova.Utils.hideProgressMessage()
						window.top.Docova.Utils.messageBox({
							prompt: "You are not authorized to access this document.",
							icontype: 1,
							msgboxtype: 0,
							title: "Not Authorized.",
							width: 400
						})
						return false;
					} else if (accessLevel == 99) {
						Docova.Utils.hideProgressMessage()
						window.top.Docova.Utils.messageBox({
							prompt: "The selected document could not be accessed.  It has most likely been deleted.",
							icontype: 1,
							msgboxtype: 0,
							title: "Document Deleted.",
							width: 400
						})
						return false;
					}

					//-----Compare the documents
					var arr = new Array()
					arr[0] = docInfo.DocID;
				arr[1] = docKey;
				arr[2] = docLocation //"A" if archived - agent handles case
					CompareWordAttachments(arr, function (saveCompareDocPath) {
						if (!saveCompareDocPath) {
							Docova.Utils.hideProgressMessage()
							return;
						}
						if (docInfo.RequirePDFCreator || docInfo.RestrictPrinting) {
							Docova.Utils.showProgressMessage("Converting comparison results to PDF...")

							//------------------ convert the compare results to PDF ----------------------------------
							if (docInfo.RestrictPrinting) {
								var pdfPath = DLExtensions.ConvertToPDF(saveCompareDocPath, true, "");
							} else {
								var pdfPath = DLExtensions.ConvertToPDF(saveCompareDocPath, false, "");
							}
							Docova.Utils.hideProgressMessage()

							//--------------- launch the pdf for viewing ----------------------------
							DLExtensions.LaunchFile(pdfPath);
						} else {
							//launch in Word
							Docova.Utils.hideProgressMessage()
							DLExtensions.LaunchFile(saveCompareDocPath);
						}
					})
			}
		},
		error: function () {
			Docova.Utils.hideProgressMessage();
			alert("Error:  An error occured while trying to get Users access level for a compare document operation.");
		}
	});
}

function getSelectedDocumentID() {

	//get the DocumentUNID from the Version History document that has been selected
	var inputs = document.getElementsByTagName("INPUT");
	var selDoc = new Array();
	var count = 0;
	for (x = 0; x < inputs.length; x++) {
		if (inputs[x].id == "rowSelect") {
			if (inputs[x].checked) {
				selDoc[count] = getDocKey(inputs[x]);
				count += 1;
			}
		}
	}
	if (selDoc.length > 1) {
		alert("Please select only one document to compare to the current document");
		return false;
	} else if (selDoc == "") {
		if ($("#btnMore").attr("isOpen") == "false") { //ensure More section is open
			ToggleOptions();
		}
		alert("Please select a document from the Version History tab to compare this document to.");
	} else {
		return selDoc[0]
	}
}

function getDocKey(clickObj) {
	//returns the ParentDocID from the XML record set for the selected row
	var rs = doc.xmlDataVersionLog.recordset;
	var recNo = clickObj.recordNumber;
	rs.AbsolutePosition = recNo;
	if (rs.Fields("Location").Value == "A") {
		return ([rs.Fields("ParentDocKey").Value, "A"]);
	} else {
		return ([rs.Fields("ParentDocID").Value, ""]);
	}
}
//------ END document attachment comparison functions ----------------------------------------

function bytesToSize(bytes, precision) {
	var kilobyte = 1024;
	var megabyte = kilobyte * 1024;
	var gigabyte = megabyte * 1024;
	var terabyte = gigabyte * 1024;

	if ((bytes >= 0) && (bytes < kilobyte)) {
		return bytes + ' B';

	} else if ((bytes >= kilobyte) && (bytes < megabyte)) {
		return (bytes / kilobyte).toFixed(precision) + ' KB';

	} else if ((bytes >= megabyte) && (bytes < gigabyte)) {
		return (bytes / megabyte).toFixed(precision) + ' MB';

	} else if ((bytes >= gigabyte) && (bytes < terabyte)) {
		return (bytes / gigabyte).toFixed(precision) + ' GB';

	} else if (bytes >= terabyte) {
		return (bytes / terabyte).toFixed(precision) + ' TB';

	} else {
		return bytes + ' B';
	}
}

function printfix() {

	if (!docInfo.AttachmentsHidden) {
		var Uploader = document.all.DLIUploader1;
		if (Uploader == null){
			return;
		}
		var FNames = doc.DLIUploader1.GetAllFileNames(";");
		var aFnames = FNames.split(";")
			var aFDates = doc.DLIUploader1.GetAllFileDates(";").split(";");
		var aFSize = doc.DLIUploader1.GetAllFileLengths(";").split(";");
		document.all.divAttachmentSection.style.display = "none";
		var printDiv = document.all.printAttach;
		var tmpHtml = '<font face=Verdana  size=1><table style ="width=100%;border: 1px solid;"><tr><th width="50%" style="border-bottom: silver 1px solid;">File Name</th><th width="25%" style="border-bottom: silver 1px solid;">Date</th><th width="25%" style="border-bottom: silver 1px solid;">File Size</th></tr>';
		for (i = 0; i < Uploader.FileCount; i++) {
			tmpHtml += '<tr><td style="border-bottom: silver 1px solid;">' + aFnames[i] + '</td><td style="border-bottom: silver 1px solid;">' + aFDates[i] + '</td><td style="border-bottom: silver 1px solid;">' + bytesToSize(aFSize[i], 2) + "</td></tr>";
		}
		tmpHtml += "</table></font><br/>";
		printDiv.innerHTML = tmpHtml;
		printDiv.style.display = "block";
	}
}

function removeprintfix() {
	if (!docInfo.AttachmentsHidden) {
		var Uploader = document.all.DLIUploader1;
		if (Uploader == null){
			return;
		}
		document.all.divAttachmentSection.style.display = "block";
		document.all.printAttach.style.display = "none";
	}
}

function IsValidParent() {
	try {
		srcWindow = window.parent.fraContentTop;
		if (srcWindow.ViewLoadDocument) {
			return true;
		}
	} catch (e) {
		return false;
	}
	return false;
}

function ResetMenu() {
	if (typeof aBar != "undefined" && aBar !== null) {
		if (aBar.curSubactionParent) {
			aBar.resetSubactionPanel();
		}
	}
}

function ToggleOptions() {
	if ($("#btnMore").attr("isOpen") == "true") {
		$('#btnMore .ui-icon').addClass('ui-icon-circle-arrow-e').removeClass('ui-icon-circle-arrow-s');
		$("#divHeaderOptions").hide("blind", 1000);
		$("#btnMore").attr("isOpen", false)
	} else {
		$('#btnMore .ui-icon').addClass('ui-icon-circle-arrow-s').removeClass('ui-icon-circle-arrow-e');
		$("#divHeaderOptions").show("blind", 1000);
		$("#btnMore").attr("isOpen", true)
	}
}

function CloseDocument(refreshView) {
	clearLock = true;

	var uidoc;
	if (!_thisUIDoc) {
		uidoc = Docova.getUIDocument();
	} else {
		uidoc = _thisUIDoc;
	}

	if (!uidoc.triggerHandlerAsync("queryClose")) {
		return false;
	}

	//if(!checkActivities()) { return false; }
	//If doc is new, check to see if any activities were created in draft mode and confirm user wants to remove them
	if (docInfo.isNewDoc) {
		if ($("#tmpActivity").val() == "1" && $("#tmpDiscardActivities").val() == "0") {
			checkActivities();
			return false;
		}
	}

	//If this is a new doc and not being saved, but items like Related Documents have been linked, we need to clean those temporary orphans up.
	if (docInfo.isNewDoc) {
		//xmlDoc = tmpOrphanXml;
		orphanData = document.getElementById("tmpOrphanXML");
		var xmlString = $(orphanData).text();
		var parser = new DOMParser;
		xmlDoc = parser.parseFromString(xmlString, "text/xml")

			var nodeList = xmlDoc.selectNodes('Libraries/Library');
		if (nodeList.length > 0) {
			ClearTmpOrphans(xmlDoc);
		}
	}

	//var Uploader = DLIUploader1;  //Attachment Recovery
	if (typeof DLIUploader1 == 'undefined') {
		var Uploader = false;
	} else {
		var Uploader = DLIUploader1
	}

	allowClose = true; //to let the onUnload event fall through
	//	if(docInfo.isDocBeingEdited && docInfo.isLocked)	{
	//		Unlock();
	//	}

	//----------------------------------------------------------------
	if (docInfo.Mode == "dle") {
		doc.tmpDleDataXml.value = "<Results><Result ID='Status'>OK</Result></Results>";
		window.external.DocLogic_SubmitCancel();
		return false;
	} else if (docInfo.Mode == "window") {
		if (docInfo.isDocBeingEdited) { //attachment recovery
			if (Uploader) {
				Uploader.EnableFileCleanup = 1;
				removeEditInPlaceLogs();
			}
		}
		window.close();
		//if the doc was opened from a e-mail link, then the browser won't allow us to close the window.
		//in this case we just re-direct the user to another page.
		window.location.href = docInfo.ServerUrl + docInfo.PortalWebPath + "/HomePage?OpenPage"
		return false;
	} else if (docInfo.Mode == "dialog" && !docInfo.isRefresh) {
		window.top.$("#" + docInfo.DialogID).dialog("close");
		return false;
	} else if (docInfo.Mode == "preview") {
		if ((window.parent.fraTabbedTable && window.parent.fraTabbedTable.objTabBar) || (window.parent.fraTabbedTableSearch && window.parent.fraTabbedTableSearch.objTabBarSearch)) {

			//archive open throught the search results...
			//in this case it will open in the tabbed interface...if so don't do anything
			//the following code will close the tab.
		} else {
			window.close();
			return false;
		}
	}
	//----------------------------------------------------------------
	if (IsValidParent()) {
		if (docInfo.isDocBeingEdited) { //attachment recovery
			if (Uploader) {
				Uploader.EnableFileCleanup = 1;
				removeEditInPlaceLogs();
			}
		}
		srcWindow.ViewUnloadDocument(refreshView, docInfo.DocID);
	} else {
		if (docInfo.isDocBeingEdited) { //attachment recovery
			if (Uploader) {
				Uploader.EnableFileCleanup = 1;
				removeEditInPlaceLogs();
			}
		}
	}

	var currDocID = "";
	if (self.frameElement) {
		currDocID = self.frameElement.id;
	}
	if (!docInfo.isNewDoc) {
		currDocID = docInfo.DocID;
	}

	if (window.parent.fraTabbedTable && window.parent.fraTabbedTable.objTabBar) {
		window.parent.fraTabbedTable.objTabBar.CloseTab(currDocID, true, "appViewMain");
		return;
	}
	//is this from search results
	if (window.parent.fraTabbedTableSearch && window.parent.fraTabbedTableSearch.objTabBarSearch) {
		window.parent.fraTabbedTableSearch.objTabBarSearch.CloseTab(currDocID);
	}
}

function checkConnectionActive() {
	var result = false;

	//--- url to check connectivity
	var url = docInfo.ServerUrl + docInfo.PortalWebPath + "/LoginConfirmation.xml?OpenPage&" + Math.random();

	jQuery.ajax({
		type: "POST",
		url: url,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			var statustext = xmlobj.find("status").text();
			result = true;
		},
		error: function () {
			alert("Your session has timed out, or your network connection was lost.\n In the following dialog please try logging back in.\n Then try saving this document again.");
			var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/LoginPublic?ReadForm&" + Math.random();
			var dlgLoginPublic = Docova.Utils.createDialog({
					id: "divLoginPublic",
					url: dlgUrl,
					title: "Login",
					height: 600,
					width: 800,
					useiframe: true,
					sourcewindow: window,
					sourcedocument: document,
					buttons: {
						"Close": function () {
							dlgLoginPublic.closeDialog();
						}
					}
				})
				result = false;
		}
	});
	return result;
}

function SaveAndClose(refreshView) {
	//-- special case of mobile phone interface put up a loading message
	if(docInfo && docInfo.isMobile){
		try{
			if(window.parent && typeof window.parent.showLoadingMessage == "function"){
				window.parent.showLoadingMessage("saving...");
			}
		}catch(e){}
	}
	
	var uidoc = Docova.getUIDocument();
	if (_DOCOVAEdition == "SE") {
		uidoc.save({
			andclose: true,
			onOk: function () {
				if (refreshView){
					uidoc.close({
						savePrompt: false
					});
				}
			}
		});
	} else {
		uidoc.save({
			andclose: true
		});
	}

	return;
}

function InitDoc() {

	if (IsValidParent()) {
		try {
			if (docInfo.LoadAction == "refreshview") {
				srcWindow.ViewReload(docInfo.DocID)
			}
		} catch (e) {
			alert(e);
		}
	}

	//catch for opening related doc, if archived and deleted
	if (docInfo.DocIsDeleted == "1") {
		alert('This document was deleted in the past. You are viewing from the recycle bin.')
	}

	//check for any attachment edit logs (attachments edited but not saved properly)
	if (docInfo.isDocBeingEdited) {
		//var Uploader = DLIUploader1;
		if (typeof DLIUploader1 == "undefined") {
			var Uploader = false;
		} else {
			var Uploader = DLIUploader1;
		}
		if (Uploader) {
			var incompEdits = docInfo.IncompleteEdits;
			if (incompEdits) {
				attachmentEditLogManager(incompEdits);
			}
		}
	}

	//---- check to see if we should auto attach any template
	if (docInfo.TemplateAutoAttach == "1") {
		if (docInfo.isNewDoc && docInfo.isDocBeingEdited) {
			try {
				AttachTemplate();
			} catch (e) {}
		}
	}

	//Update the tabbed table entry to show the path to this document
	if (window.parent.fraTabbedTable && window.parent.fraTabbedTable.objTabBar) {
		window.parent.fraTabbedTable.objTabBar.UpdateTitle(docInfo.DocID, docInfo.LibraryTitle + "\\" + docInfo.FolderPath + "\\" + docInfo.DocTitle);
	}

	//if open from search resutls
	if (window.parent.fraTabbedTableSearch && window.parent.fraTabbedTableSearch.objTabBarSearch) {
		window.parent.fraTabbedTableSearch.objTabBarSearch.UpdateTitle(docInfo.DocID, docInfo.LibraryTitle + "\\" + docInfo.FolderPath + "\\" + docInfo.DocTitle);
	}

	//---- check if the discussion JS header is loaded---
	try {
		LoadDiscussionThread();
		//HighlightCurrent();
	} catch (e) {}
	//----- check if the mail correspondence header is loaded----
	try {
		LoadMailCorrespondence()
	} catch (e) {}

	if (_DOCOVAEdition == "Domino") {
		document.body.focus(); //just opening the doc - focus so the doc event handlers start working
	}
}

function EditDocument() {
	clearLock = true;
	//onquery close
	if (!_thisUIDoc.triggerHandlerAsync("queryModeChange")) {
		return false;
	}

	allowClose = true; //to let the onUnload event fall through
	if (_DOCOVAEdition == "SE") {
		var curUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wViewForm/" + docInfo.DocumentType + '/' + docInfo.DocKey + "?EditDocument";
		curUrl += docInfo.Query_String.substring(8);
	} else {
		var curUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + docInfo.DocID + "?EditDocument";
	}
	curUrl += (docInfo.isMobile && curUrl.indexOf("&Mobile=")==-1) ? "&Mobile=true" : "";
	curUrl += (docInfo.MobileAppDevice && curUrl.indexOf("&device=")==-1) ? "device=" + docInfo.MobileAppDevice : "";
	curUrl += (docInfo.Mode && curUrl.indexOf("&mode=")==-1) ? "&mode=" + docInfo.Mode : "";
	curUrl += (docInfo.AppID && curUrl.indexOf("&ParentUNID=")==-1) ? "&ParentUNID=" + docInfo.AppID : "";
	curUrl += (docInfo.AppID && curUrl.indexOf("&AppID=")==-1) ? "&AppID=" + docInfo.AppID : "";
	curUrl += (docInfo.DialogID && curUrl.indexOf("&dialogid=")==-1) ? "&dialogid=" + docInfo.DialogID : "";
	
	if (!Lock()) {
		return false;
	}
	clearLock = false; //to not clear the document lock

	if (IsValidParent()) {
		srcWindow.ViewLoadDocument(curUrl);
	} else {
		location.replace(curUrl)
	}
}

function SaveBeforeClosing(noCancel) {
	if (docInfo.isRefresh) {
		return;
	}
	if (!docInfo.isDocBeingEdited) {
		return false;
	}

	var doctitle = Docova.Utils.getField("Subject")
		if (doctitle == "" || doctitle === null) {
			doctitle = "-untitled-";
		}

		//--check to see if saveoptions field exists and is set to 0 or 1
		var saveoptions = "";
	if (_thisUIDoc) {
		saveoptions = _thisUIDoc.getField("SaveOptions");
	}
	if (saveoptions == "1") {
		//-- save without prompting
		HandleSaveClick(true);
		return;
	} else if (saveoptions == "0") {
		//-- close without prompting
		CloseDocument();
		return;
	}
	
	//then call this to see if anything has changed.
	if ($('#divFormContentSection').data('changed') !== true) {
		CloseDocument();
		return;
	}

	//-- no save options field so prompt user if they want to save

	var boxType = (noCancel) ? 4 : 3; //4 = Yes, No   3 = Yes, No, Cancel
	var msg = "Do you want to save the changes you made?"
		window.top.Docova.Utils.messageBox({
			prompt: msg,
			title: "Closing Document",
			width: 400,
			icontype: 2,
			msgboxtype: boxType,
			onYes: function () {
				HandleSaveClick(true);
			},
			onNo: function () {
				CloseDocument();
			},
			onCancel: function () {
				return false;
			}
		});
}

//-------------------- document fields validation called from IsValidData function
function ValidateFields(items, hideErrorMsg) {
	var itemList = items.split(";");
	if (itemList.length == 0) {
		return true;
	}
	var fieldValue;
	var errorMsg = "";
	var firstBadField;
	var itemProperties;

	for (var k = 0; k < itemList.length; k++) {
		itemProperties = itemList[k].split("~");
		if (itemProperties[0]) //field name was supplied
		{
			if (document.getElementsByName(itemProperties[0]).length == 0) {
				continue; //field not found
			}
			fieldValue = Docova.Utils.getField(itemProperties[0]);
			if (Docova.Utils.allTrim(fieldValue)) //has some text
			{
				//check for data type errors if applicable
				if ((itemProperties[1] == "number" && !isNumeric(fieldValue)) || (itemProperties[1] == "date" && !Docova.Utils.checkDate(fieldValue))) {
					firstBadField = (firstBadField) ? firstBadField : itemProperties[0];
					errorMsg += (itemProperties[2]) ? itemProperties[2] : itemProperties[0];
					errorMsg += " (invalid " + itemProperties[1] + ")\r";
				}
			} else //empty
			{
				firstBadField = (firstBadField) ? firstBadField : itemProperties[0];
				errorMsg += (itemProperties[2]) ? itemProperties[2] : itemProperties[0];
				errorMsg += " (missing value)\r";
			}
		}
	}

	if (errorMsg && !hideErrorMsg) {
		window.top.Docova.Utils.messageBox({
			prompt: "The following fields did not pass the validation:<br><br>" + errorMsg + "<br><br>Please complete/correct the invalid entries.",
			icontype: 1,
			msgboxtype: 0,
			title: "Validation error",
			width: 400
		})
		try {
			var focusField = document.getElementsByName(firstBadField);
			focusField.focus();
		} catch (e) {}

		return false;
	}

	if (errorMsg) {
		return false;
	}
	return true;
}

//--- attempts to lock current document -----
function Lock() {
	if (docInfo.isLocked) {
		if (docInfo.isLockEditor) {
			return true; //alreadylocked by currnet user, nothing to do
		} else {
			var msg = "The document has been locked for editing by " + docInfo.LockEditor;
			msg += " on " + docInfo.LockDate;
			//alert(msg);
			//return false;

			window.top.Docova.Utils.messageBox({
				prompt: msg,
				title: "Warning",
				width: 400,
				icontype: 2,
				msgboxtype: 4,
				onYes: function () {
					Unlock(afterUnlock);
					return true;
				},
				onNo: function () {
					return false;
				}
			});
		}
	}
	//--- document is automatically locked for editing
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
		var request = "";

	//--build the Lock request
	request += "<Request>";
	request += "<Action>LOCK</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "</Request>";
	var httpObj = new objHTTP();

	if (httpObj.PostData(request, url)) {
		if (httpObj.status == "LOCKED" && httpObj.resultCount > 0) {
			alert(httpObj.results[1]);
			return false;
		} else if (httpObj.status == "OK") {
			docInfo.isLocked = true;
			return true;
		}
	}
	return false;
}

//callback function to call after a successful unlocking of document
function afterUnlock() {
	EditDocument();
}

//--- attempts tounlock current document -----
function Unlock(cb) {
	//--- document is automatically checked out for editing
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
		var request = "";

	//--build the UNLOCK request
	request += "<Request>";
	request += "<Action>UNLOCK</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			result = true;
			var xmlobj = jQuery(xml);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				docInfo.isLocked = false;
				docInfo.LockEditor = "";
				docInfo.LockDate = "";
				if (typeof(cb) == "function") {
					try {
						cb();
					} catch (e) {};
				}
			}
		},
		error: function () {
			alert("Error: Could not release the lock on this document.  Please contact your Administrator.")
			result = false;
		}
	});

	return result;
}

function NewVersionDiscardDrafts() {
	var customMsg = Docova.Utils.dbLookup({
			servername: "",
			nsfname: docInfo.KeywordsWebName,
			viewname: "luKeywordsByKey",
			key: "DiscardWarning",
			columnorfield: "KeywordAlias",
			delimiter: ";",
			alloweditmode: false,
			secure: docInfo.SSLState,
			failsilent: false
		})
		customMsg = eval(customMsg)
		var msg = "Warning:  An existing draft or pending version of this document,\ncreated by " + hasDrafts + ", was found.\n\nIf you continue it will be discarded.\n\nContinue?"
		if (customMsg) {
			msg = customMsg;
		}

		window.top.Docova.Utils.messageBox({
			prompt: msg,
			title: "Warning",
			width: 400,
			icontype: 2,
			msgboxtype: 4,
			onYes: function () {
				return true;
			},
			onNo: function () {
				return false;
			}
		});
}

function NewVersionDelFiles(versionType, docAttachmentNames) {
	dlgParams.length = 0; //reset dlgParams array.
	dlgParams[0] = docAttachmentNames;
	var dlgUrl = "/" + docInfo.PortalNsfName + "/" + "dlgSelectAttachments?OpenForm";
	var SelDelFiles = window.top.Docova.Utils.createDialog({
			id: "divDlgSelectAttachments",
			url: dlgUrl,
			title: "Select Attachments",
			height: 300,
			width: 450,
			useiframe: true,
			sourcedocument: document,
			sourcewindow: window,
			buttons: {
				"OK": function () {
					var dlgDoc = window.top.$("#divDlgSelectAttachmentsIFrame")[0].contentWindow.document;
					var delFile = $("input[name='FileAttach']:checked", dlgDoc).map(function () {
							return this.value;
						}).get().join("*"); //gets * delimited file list from dialog
					DoNewVersion(versionType, delFile);
					SelDelFiles.closeDialog();
				},
				"Cancel": function () {
					SelDelFiles.closeDialog();
				}
			}
		})
}

//--- attempts to create new version of the current document -----
function NewVersion(versionType) {
	//warn user if strict versioning or restrict drafts on and drafts found - they will be discarded if continue.
	if (docInfo.StrictVersioning || docInfo.RestrictLiveDrafts) {
		var hasDrafts = docInfo.hasDrafts;
		if (hasDrafts) {
			window.top.Docova.Utils.messageBox({
				icontype: 4,
				msgboxtype: 0,
				prompt: "This document type only allows one live draft at a time<br>and a live draft already exists.",
				title: "Drafts are Restricted",
				width: 400,
				onOk: function () {
					return;
				}
			})
			if (!DiscardDrafts()) {
				return false;
			}
		}
	}

	var attOption = docInfo.AttachmentOptions;
	var delFile = ""; //default, keep all attachments
	if (attOption == "1") {
		delFile = "ALL";
	}
	if (attOption == "2") {
		NewVersionDelFiles(versionType, docInfo.DocAttachmentNames)
	} else {
		DoNewVersion(versionType, delFile)
	}
}

function DoNewVersion(versionType, delFile) {
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
		var request = "";

	//--build the NEWVERSION request
	request += "<Request>";
	request += "<Action>NEWVERSION</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "<VersionType>" + versionType + "</VersionType>";
	request += "<Delfile><![CDATA[" + delFile + "]]></Delfile>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var newDocID = xmlobj.find('Results Result[ID=Ret1]').text()

				if (docInfo.FolderID) {
					if (window.parent.fraTabbedTable && window.parent.fraTabbedTable.objTabBar) {
						var newUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + newDocID + "?EditDocument";
						newUrl += (docInfo.Mode) ? "&mode=" + docInfo.Mode : "";
						newUrl += "&loadaction=refreshview";
						var fid = docInfo.FolderID.substring(2);
						window.parent.fraTabbedTable.objTabBar.CreateTab(docInfo.DocTitle, newDocID, "D", newUrl, fid, true);
					}
				}
				else {
					var newUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wViewForm/0/" + newDocID + "?EditDocument";
					newUrl += '&AppID=' + docInfo.AppID;
					newUrl += (docInfo.Mode) ? "&mode=" + docInfo.Mode : "";
					newUrl += "&loadaction=refreshview";
					var ws = Docova.getUIWorkspace(document);
					ws.openDocument({
						'docurl': newUrl,
						'isapp': true
					});
				}
			}
		},
		error: function () {
			alert("Error creating new version.  Please try again.")
		}
	})
}

//--- retract current version of the document -----
function RetractVersion() {
	if (!CanModifyDocument(true)) {
		return false;
	}

	var requestOptions = "";
	if (docInfo.EnableVersions) {
		var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgRetractRelease?OpenForm"
			var dlgRetractRelease = window.top.Docova.Utils.createDialog({
				id: "divDlgRetractRelease",
				url: dlgUrl,
				title: "Retract Release",
				height: 300,
				width: 500,
				useiframe: true,
				sourcewindow: window,
				buttons: {
					"OK": function () {
						var dlgDoc = window.top.$("#divDlgRetractReleaseIFrame")[0].contentWindow.document
							var curAction = $("input[name=CurVersionAction]:checked", dlgDoc).val()
							var prevAction = $("input[name=PrevVersionAction]:checked", dlgDoc).val()
							curAction = (curAction == null) ? "" : curAction;
						prevAction = (prevAction == null) ? "" : prevAction;
						requestOptions = "<CurAction>" + curAction + "</CurAction><PrevAction>" + prevAction + "</PrevAction>"
							DoRetractVersion(requestOptions);
						dlgRetractRelease.closeDialog();
					},
					"Cancel": function () {
						dlgRetractRelease.closeDialog();
					}
				}
			})
	} else {
		var msg = "You are about to retract a published document.<br><br>Are you sure?"
			window.top.Docova.Utils.messageBox({
				prompt: msg,
				title: "Retract Version?",
				width: 400,
				icontype: 2,
				msgboxtype: 4,
				onYes: function () {
					DoRetractVersion(requestOptions);
				},
				onNo: function () {
					return false;
				}
			});
	}
}

function DoRetractVersion(requestOptions) {
	var request = "";
	//--build the RETRACTVERSION request
	request += "<Request>";
	request += "<Action>RETRACTVERSION</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += requestOptions;
	request += "</Request>";

	if (docInfo.isDocBeingEdited) {
		$("#tmpRequestDataXml").val(request);
		HandleSaveClick();
		return;
	}
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/WorkflowServices?OpenAgent"
		jQuery.ajax({
			type: "POST",
			url: url,
			data: request,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				result = true;
				var xmlobj = jQuery(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					location.replace(location.href + "&loadaction=refreshview");
				}
			},
			error: function () {
				alert("Error: Could not retract document.  Please contact your Administrator.")
				result = false;
			}
		});
}

//--- reactivate discarded version of the document -----

function ActivateVersion() {
	if (!CanModifyDocument(true)) {
		return false;
	}

	var requestOptions = "";

	window.top.Docova.Utils.messageBox({
		prompt: "You are about to reactivate discarded draft document.<br>Are you sure?",
		title: "Activate Version?",
		width: 400,
		icontype: 2,
		msgboxtype: 4,
		onYes: function () {
			var request = "";
			//--build the ACTIVATEVERSION request
			request += "<Request>";
			request += "<Action>ACTIVATEVERSION</Action>";
			request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
			request += "<Unid>" + docInfo.DocID + "</Unid>";
			request += "</Request>";
			if (docInfo.isDocBeingEdited) {
				$("#tmpRequestDataXml").val(request);
				HandleSaveClick();
				return;
			} else {
				var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/WorkflowServices?OpenAgent"
					jQuery.ajax({
						type: "POST",
						url: url,
						data: request,
						cache: false,
						async: false,
						dataType: "xml",
						success: function (xml) {
							result = true;
							var xmlobj = jQuery(xml);
							var statustext = xmlobj.find("Result").first().text();
							if (statustext == "OK") {
								location.replace(location.href + "&loadaction=refreshview");
							}
						},
						error: function () {
							alert("Error: Could not reactivate this document.  Please contact your Administrator.")
							result = false;
						}
					});
			}
		},
		onNo: function () {
			return false;
		}
	});

}

//-------- release the document --------
function ReleaseDocument(keepVersion) {
	if (!CanModifyDocument(true)) {
		return false;
	}

	//----Run custom on before release JS function if identified---//
	if (!CustomOnBeforeReleaseHandler()) {
		return false;
	}

	if (UserHasFilesCheckedOut()) {
		alert("Before you can release this document you must check in any files you have checked out")
		return false
	}

	var userComment = "";
	var requestOptions = "";
	var version = "";

	if (docInfo.EnableVersions && !keepVersion) {
		dlgParams.length = 0; //reset dlgParams array.
		dlgParams[0] = docInfo.AvailableVersionList;
		dlgParams[1] = docInfo.FullVersion;
		dlgParams[2] = docInfo.isInitialVersion;
		dlgParams[3] = docInfo.StrictVersioning;

		var dlgUrl = "/" + NsfName + "/" + "dlgWorkflowDocRelease?OpenForm";
		var dlgWorkflowDocRelease = window.top.Docova.Utils.createDialog({
				id: "divDlgWorkflowDocRelease",
				url: dlgUrl,
				title: "Release Document",
				height: 270,
				width: (_DOCOVAEdition == "SE" ? 455 : 435),
				useiframe: true,
				sourcewindow: window,
				buttons: {
					"OK": function () {
						var dlgDoc = window.top.$("#divDlgWorkflowDocReleaseIFrame")[0].contentWindow.document
							var userComment = $("#Comment", dlgDoc).val()
							var isFirstRelease = dlgParams[2];
						if (isFirstRelease) {
							if (!isNumeric($("#MajorVersion", dlgDoc).val()) || !isNumeric($("#MinorVersion", dlgDoc).val()) || !isNumeric($("#Revision", dlgDoc).val())) {
								alert("Sorry.  You must enter numeric values for major and minor version number.");
								$("#MajorVersion", dlgDoc).focus();
								return false;
							}
							version = $("#MajorVersion", dlgDoc).val() + "." + $("#MinorVersion", dlgDoc).val() + "." + $("#Revision", dlgDoc).val()
						} else {
							version = $("input[name=VersionSelect]:checked", dlgDoc).val()
								if (version == "") {
									alert("Sorry.  You must select the major or minor version increment.")
									return false;
								}
						}
						if (!isFirstRelease && !userComment) { //comment is required for auto incremented versions
							alert("Comment required. Please describe the changes to be included in this version.")
							$("#Comment", dlgDoc).focus();
							return false;
						}
						requestOptions += "<Version>" + version + "</Version>";
						requestOptions += "<UserComment><![CDATA[" + userComment + "]]></UserComment>";
						DoReleaseDocument(requestOptions, version, keepVersion);
						dlgWorkflowDocRelease.closeDialog();
					},
					"Cancel": function () {
						dlgWorkflowDocRelease.closeDialog();
					}
				}
			})
	} else {
		var fieldMsg = "Please enter the release comments (optional): "
			dlgParams.length = 0; //reset dlgParams array.
		dlgParams[0] = fieldMsg; //Message to pass to dialog.
		dlgParams[1] = false; //Are comments required? Picked up by dialog. For Release its false
		var dlgUrl = "/" + NsfName + "/" + "dlgWorkflowComment?OpenForm";
		var dlgWorkflowComment = window.top.Docova.Utils.createDialog({
				id: "divDlgWorkflowComments",
				url: dlgUrl,
				title: "Comments",
				height: 230,
				width: 435,
				useiframe: true,
				sourcewindow: window,
				buttons: {
					"OK": function () {
						//$("#divDlgWorkflowCommentsIFrame")[0].contentWindow.completeWizard(); //Call completeWizard in iframe in dialog
						var dlgDoc = window.top.$("#divDlgWorkflowCommentsIFrame")[0].contentWindow.document
							var userComment = $("#Comment", dlgDoc).val()
							if (dlgParams[1] && !userComment) {
								window.top.Docova.Utils.messageBox({
									icontype: 1,
									msgboxtype: 0,
									prompt: "Please enter a comment",
									title: "Comment Required",
									width: 200,
									onOk: function () {
										return;
									}
								});
							} else {
								requestOptions += "<Version>" + docInfo.FullVersion + "</Version>";
								requestOptions += "<UserComment><![CDATA[";
								requestOptions += (userComment) ? userComment : ""; //userComment will be false if the dialog is cancelled
								requestOptions += "]]></UserComment>";
								DoReleaseDocument(requestOptions, keepVersion);
								dlgWorkflowComment.closeDialog();
							}
					},
					"Cancel": function () {
						dlgWorkflowComment.closeDialog();
					}
				}
			})
	}
}

function DoReleaseDocument(requestOptions, version, keepVersion) {
	var result = false;
	var request = "";
	//--build the RELEASEVERSION request
	request += "<Request>";
	request += "<Action>RELEASEVERSION</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += requestOptions;
	request += "</Request>";

	//--- process on server --
	if (docInfo.isDocBeingEdited) {
		document.getElementById("tmpRequestDataXml").value = request;
		//-----Set tmpVersion field to make available for onAfterRelease event------
		if (docInfo.EnableVersions && !keepVersion) {
			document.getElementById("tmpVersion").value = version
		} else {
			document.getElementById("tmpVersion").value = docInfo.FullVersion
		}
		//-----Run custom on after release handler
		if (!CustomOnAfterReleaseHandler()) {
			return false;
		}
		HandleSaveClick();
		return;
	} else {
		//--- processing agent url
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/WorkflowServices?OpenAgent"
			jQuery.ajax({
				type: "POST",
				url: url,
				data: request,
				cache: false,
				async: false,
				dataType: "xml",
				success: function (xml) {
					var xmlobj = jQuery(xml);
					var statustxt = xmlobj.find("Result").first().text()
						if (statustxt == "OK") {
							if (!CustomOnAfterReleaseHandler()) {
								result = false;
							} else {
								//add this folder to the refresh list so that when the tab is closed in readmode, the view refreshes to show the new status
								window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList(docInfo.FolderUNID, docInfo.DocID);
								location.replace(location.href + "&loadaction=refreshview");
								result = true;
							}
						}
				}
			})
	}
}

// ---------- workflow/lifecycle comment dialog --------
function GetComment(fieldMsg, required) {
	//var params = new Array();
	//params[0] = (fieldMsg)? fieldMsg : "";
	//params[1] = (required)? required : false;

	//	var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowComment?OpenForm";
	//	var dlgSettings = "dialogHeight:200px;dialogWidth:420px;center:yes; help:no; resizable:no; status:no;";
	//	return window.showModalDialog(dlgUrl,params,dlgSettings); //Display the comment dialog
}

// ---------- log workflow/lifecycle dialog --------
function LogLifecycleComment() {
	if (document.getElementById("AdvComments")) {
		LogAdvancedComment(true);
	} else {
		dlgParams.length = 0;
		dlgParams[0] = "Please enter your comments below:"
			dlgParams[1] = true;
		var dlgUrl = "/" + NsfName + "/" + "dlgWorkflowComment?OpenForm";
		var dlgWorkflowComment = window.top.Docova.Utils.createDialog({
				id: "divDlgGetComment",
				url: dlgUrl,
				title: "Comment",
				height: 250,
				width: 420,
				useiframe: true,
				sourcewindow: window,
				buttons: {
					"OK": function () {
						var dlgDoc = window.top.$("#divDlgGetCommentIFrame")[0].contentWindow.document
							var userComment = $("#Comment", dlgDoc).val()
							if (dlgParams[1] && !userComment) {
								window.top.Docova.Utils.messageBox({
									icontype: 1,
									msgboxtype: 0,
									prompt: "Please enter a comment",
									title: "Comment Required",
									width: 200,
									onOk: function () {
										return;
									} //onOk
								}); //messagebox
							} else {
								var request = "<UserComment><![CDATA[" + userComment + "]]></UserComment>";
								ProcessLifecycleRequest("ADDCOMMENT", request, true)
								dlgWorkflowComment.closeDialog();
							} //if else
					}, //OK
					"Cancel": function () {
						dlgWorkflowComment.closeDialog();
					} //Cancel
				}
			})
	}
}

// ---------- mark document reviewed --------

function ReviewDocument(forceComplete) {
	if (!CanModifyDocument(true)) {
		return false;
	}

	dlgParams.length = 0;
	dlgParams[0] = "Enter your review comments below:"
		dlgParams[1] = true;
	var dlgUrl = "/" + NsfName + "/" + "dlgWorkflowComment?OpenForm";
	var dlgWorkflowComment = window.top.Docova.Utils.createDialog({
			id: "divDlgGetComment",
			url: dlgUrl,
			title: "Comment",
			height: 250,
			width: 420,
			useiframe: true,
			sourcewindow: window,
			buttons: {
				"OK": function () {
					var dlgDoc = window.top.$("#divDlgGetCommentIFrame")[0].contentWindow.document
						var userComment = $("#Comment", dlgDoc).val()
						if (dlgParams[1] && !userComment) {
							window.top.Docova.Utils.messageBox({
								icontype: 1,
								msgboxtype: 0,
								prompt: "Please enter a comment",
								title: "Comment Required",
								width: 200,
								onOk: function () {
									return;
								} //onOk
							}); //messagebox
						} else {
							var request = "<UserComment><![CDATA[" + userComment + "]]></UserComment>";
							request += (forceComplete) ? "<ForceComplete>1</ForceComplete>" : "<ForceComplete/>";
							if (ProcessLifecycleRequest("REVIEW", request, true)) {
								if (docInfo.isLinkComments) {
									var addrequest = "<UserComment><![CDATA[" + "Scheduled Review: " + userComment + "]]></UserComment>";
									addrequest += "<CommentType>LC</CommentType>";
									ProcessComment("LOGCOMMENT", addrequest, true)
								}
								HandleSaveClick(true);
							}
							dlgWorkflowComment.closeDialog();
						} //if else
				}, //OK
				"Cancel": function () {
					dlgWorkflowComment.closeDialog();
				} //Cancel
			}
		})
}

//--- processess the lifecycle on the server ---
function ProcessLifecycleRequest(action, additionalHeader, submitNow) {
	var returnval = false;
	var request = "";

	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<ServerUrl>" + docInfo.ServerUrl + "</ServerUrl>";
	request += (additionalHeader) ? additionalHeader : "";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";

	request += "</Request>";

	if (docInfo.isDocBeingEdited && !submitNow) //edit mode, doc WQS agent will take care of the request
	{
		$("#tmpRequestDataXml").val(request)
		alert("Your comments will be added when on document save.")
	} else // read mode, process via workflow services agent
	{
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/WorkflowServices?OpenAgent"
			jQuery.ajax({
				type: "POST",
				url: url,
				data: request,
				cache: false,
				async: false,
				dataType: "xml",
				success: function (xml) {
					returnval = true;
					var xmlobj = $(xml);
					var statustext = $(xmlobj).find("Result").first().text()
						if (statustext == "OK") {
							CommentLogData.reload();
							alert("Your comment has been added.")
						}
				},
				error: function () {
					returnval = false;
				}
			});
	}
}

//--- attempts to lock the document -----

function NewCopy() {
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
		var request = "";

	//--build the NEWCOPY request
	request += "<Request>";
	request += "<Action>NEWCOPY</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "</Request>";

	var httpObj = new objHTTP()

		if (httpObj.PostData(request, url)) {
			if (httpObj.status == "OK") //all OK
			{
				if (httpObj.results.length > 0) {
					var newUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/0/" + httpObj.results[0] + "?EditDocument";
					curUrl += (docInfo.Mode) ? "&mode=" + docInfo.Mode : "";
					curUrl += "&loadaction=refreshview";
					location.replace(newUrl);
					return true;
				}
			}
		}
		return false;
}

//--- updates docInfo from stub document data stored in the master home database -----

function UpdateStubInfo() {
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
		var request = "";
	var result = true;

	//--build the GETSTUBINFO request
	request += "<Request>";
	request += "<Action>GETSTUBINFO</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			result = true;
			var xmlobj = jQuery(xml);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				docInfo.LastModifiedBy = xmlobj.find("LastModifiedBy").text();
				docInfo.LastModifiedDate = xmlobj.find("LastModifiedDate").text();
				docInfo.LastModifiedServer = xmlobj.find("LastModifiedServer").text();
				docInfo.isReplicated = xmlobj.find("IsReplicated").text();
				docInfo.LockEditor = xmlobj.find("LockEditor").text();
				docInfo.LockDate = xmlobj.find("LockDate").text();
				docInfo.LockServer = xmlobj.find("LockServer").text();
				docInfo.LockStatus = xmlobj.find("LockStatus").text();
				docInfo.isLockEditor = xmlobj.find("IsLockEditor").text();
				docInfo.isLocked = xmlobj.find("IsLocked").text();
			}
		},
		error: function () {
			alert("Error: Could not retrieve stub information in UpdateStubInfo() function of wDocument.\rIf the issue persists, please contact your Administrator.")
			result = false;
		}
	});
	return result;
}

// boolean, checks if the document can be modified at this moment
function CanModifyDocument(noMsg) {

	if (!docInfo.isDocBeingEdited) {
		UpdateStubInfo();
	}

	var isDelegate = false;
	if (docInfo.HasWorkflow && docInfo.isWorkflowCreated && !docInfo.isWorkflowCompleted) {
		if (wfInfo.isDelegate == "1") {
			isDelegate = true;
		}
	}

	var msg = "";
	//	if(docInfo.DocAccessLevel < "3" && !isDelegate){
	//		if(noMsg){return false;}
	//		alert("You do not have sufficient access to modify this document.")
	//		return false;
	//	}

	if (!docInfo.isReplicated) {
		if (noMsg) {
			return false;
		}
		msg += "The document updates done on " + docInfo.LastModifiedDate + " by " + docInfo.LastModifiedBy;
		msg += " on server " + docInfo.LastModifiedServer + " have not yet replicated to your server. ";
		msg += "You cannot modify the current document until the latest changes are available to you. Please try again in few minutes."
		alert(msg);
		return false;
	}

	if (docInfo.LockStatus == "2") {
		if (noMsg) {
			return false;
		}
		msg += "The document has been locked on " + docInfo.LockDate + " by " + docInfo.LockEditor;
		msg += " on server " + docInfo.LockServer + ". ";
		msg += "Do you want to take over this document?"
		//alert(msg);
		window.top.Docova.Utils.messageBox({
			prompt: msg,
			title: "Warning",
			width: 400,
			icontype: 2,
			msgboxtype: 4,
			onYes: function () {
				Unlock(afterUnlock);
			},
			onNo: function () {
				return false;
			}
		});
		return false;
	}
	return true;
}

//=================================================
// Create discussion topic
//=================================================

function CreateDiscussionTopic() {
	var curUrl = "/" + docInfo.NsfName + "/DiscussionTopic?OpenForm&ParentUNID=" + docInfo.DocID;
	curUrl += (docInfo.Mode) ? "&mode=" + docInfo.Mode : "";
	if (IsValidParent()) {
		srcWindow.ViewLoadDocument(curUrl);
	} else {
		window.location.href = curUrl;
	}
}

//==========================================================================================
// Tools submenu
//==========================================================================================

function CreateToolsSubmenu(actionButton) {
	if (!actionButton) {
		return;
	}

	var showDropBox = docInfo.isDocBeingEdited && docInfo.EnableDropboxAcquire;
	var showMailbox = (docInfo.isDocBeingEdited && docInfo.EnableMailAcquire);
	var showScanner = (docInfo.isDocBeingEdited && docInfo.EnableLocalScan);
	var showDiscussion = (!docInfo.isDocBeingEdited && !docInfo.isNewDoc && docInfo.EnableDiscussion && !docInfo.isBookmark);
	var showCompare = (!docInfo.isNewDoc && docInfo.HasLifecycle && !docInfo.isBookmark);
	var showUnlock = (docInfo.DocAccessLevel >= "6" && docInfo.isLocked && !docInfo.isDocBeingEdited && !docInfo.isBookmark);
	var showArchive = (docInfo.DocAccessLevel >= "6" && !docInfo.isDocBeingEdited && !docInfo.isBookmark);
	var showNotify = !docInfo.isNewDoc;
	var showForwardDocument = !docInfo.isNewDoc && !docInfo.isDocBeingEdited && docInfo.EnableForwarding;
	var showBookmark = !docInfo.isNewDoc && !docInfo.isDocBeingEdited && !docInfo.isBookmark && !docInfo.DisableBookmarks;
	var showComment = !docInfo.isNewDoc && !docInfo.isBookmark;
	var showCompleteReview = docInfo.HasPendingReview && docInfo.DocAccessLevel > "3" && !docInfo.isBookmark;

	//-----Build menu-----
	Docova.Utils.menu({
		delegate: actionButton,
		menuid: "DocToolsMenu",
		width: 200,
		menus: [{
				title: "Acquire from email",
				itemicon: "ui-icon-mail-open",
				action: "AcquireEmail()",
				disabled: !showMailbox
			}, {
				title: "Acquire from scanner",
				itemicon: "ui-icon-extlink",
				action: "HandleScanClick()",
				disabled: !showScanner
			}, {
				separator: true
			}, {
				title: "Send Email Notification",
				itemicon: "ui-icon-extlink",
				action: "TriggerSendDocumentMessage()",
				disabled: !showNotify
			}, {
				title: "Forward Document",
				itemicon: "ui-icon-arrowthick-1-ne",
				action: "ForwardDocument()",
				disabled: !showForwardDocument
			}, {
				separator: true
			}, {
				title: "Add Comment",
				itemicon: "far fa-comment",
				action: "LogLifecycleComment()",
				disabled: !showComment
			}, {
				title: "Discuss",
				itemicon: "ui-icon-volume-on",
				action: "CreateDiscussionTopic()",
				disabled: !showDiscussion
			}, {
				separator: true
			}, {
				title: "Compare Documents",
				itemicon: "ui-icon-transferthick-e-w",
				action: "CompareVersions()",
				disabled: !showCompare
			}, {
				separator: true
			}, {
				title: "Copy Link",
				itemicon: "ui-icon-link",
				action: "CopyLink()",
				disabled: docInfo.IsNewDoc
			}, {
				title: "Create Bookmark",
				itemicon: "ui-icon-bookmark",
				action: "CreateBookmark()",
				disabled: !showBookmark
			}, {
				separator: true
			}, {
				title: "Close Review",
				itemicon: "ui-icon-circe-check",
				action: "ReviewDocument(true)",
				disabled: !showCompleteReview
			}, {
				separator: true
			}, {
				title: "Unlock",
				itemicon: "ui-icon-unlocked",
				action: "Unlock()",
				disabled: !showUnlock
			}, {
				title: "Archive Document",
				itemicon: "ui-icon-tag",
				action: "ArchiveDocument()",
				disabled: !showArchive
			}
		]
	});
}

//==========================================================================================
// Lifecycle submenu
//==========================================================================================
function CreateLifecycleSubmenu(actionButton) {
	if (!actionButton) {
		return;
	}

	//-- check to see if user can create revisions for this document due to their document access
	if (!docInfo.CanCreateRevisions) {
		//-- if not, then let's check to see if the folder properties grant them rights
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/xFolderAccess.xml?ReadForm&ParentUNID=" + docInfo.FolderUNID;
		jQuery.ajax({
			type: "GET",
			url: url,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				var xmlobj = jQuery(xml);
				var foldercancreaterevisions = xmlobj.find("CanCreateRevisions").text();
				if (foldercancreaterevisions == "1") {
					docInfo.CanCreateRevisions = "true";
				}
			}
		});
	}

	if (!docInfo.StrictVersioning) { //-----Standard versioning
		var showAddMajorVersion = docInfo.CanCreateRevisions && docInfo.EnableVersions && !docInfo.isDiscardedVersion && !docInfo.isDocBeingEdited;
		var showAddMinorVersion = docInfo.CanCreateRevisions && docInfo.EnableVersions && !docInfo.isDiscardedVersion && !docInfo.isDocBeingEdited;
		var showAddRevision = docInfo.CanCreateRevisions && docInfo.EnableVersions && !docInfo.isDiscardedVersion && !docInfo.isDocBeingEdited;
		var showRetractVersion = docInfo.DocAccessLevel > "2" && docInfo.isCurrentVersion && docInfo.HasLifecycle && !docInfo.isDocBeingEdited && CanModifyDocument();
		var showPromoteVersion = docInfo.DocAccessLevel > "2" && docInfo.isSupersededVersion && docInfo.HasLifecycle && !docInfo.isDocBeingEdited && CanModifyDocument();
		var showActivateDiscarded = docInfo.DocAccessLevel > "2" && docInfo.isDiscardedVersion && docInfo.HasLifecycle && !docInfo.isDocBeingEdited && CanModifyDocument();
	} else { //-----Strict versioning on
		var showAddMajorVersion = false;
		if (docInfo.RestrictDrafts) {
			var showAddMinorVersion = docInfo.CanCreateRevisions && docInfo.EnableVersions && docInfo.isCurrentVersion && !docInfo.isDocBeingEdited;
		} else {
			var showAddMinorVersion = docInfo.CanCreateRevisions && docInfo.EnableVersions && !docInfo.isDiscardedVersion && !docInfo.isDocBeingEdited;
		}
		var showAddRevision = false;
		var showRetractVersion = docInfo.DocAccessLevel > "2" && docInfo.isCurrentVersion && docInfo.HasLifecycle && !docInfo.isDocBeingEdited && docInfo.AllowRetract;
		var showPromoteVersion = false;
		var showActivateDiscarded = false;
	}

	//-----Build menu-----
	Docova.Utils.menu({
		delegate: actionButton,
		width: 170,
		menus: [{
				title: "New major version",
				itemicon: "ui-icon-extlink",
				action: "NewVersion('MAJOR')",
				disabled: !showAddMajorVersion
			}, {
				title: "New minor version",
				itemicon: "ui-icon-extlink",
				action: "NewVersion('MINOR');",
				disabled: !showAddMinorVersion
			}, {
				title: "New revision",
				itemicon: "ui-icon-extlink",
				action: "NewVersion('REVISION');",
				disabled: !showAddRevision
			}, {
				separator: true
			}, {
				title: "Retract release",
				itemicon: "ui-icon-arrowreturnthick-1-s",
				action: "RetractVersion()",
				disabled: !showRetractVersion
			}, {
				title: "Promote release",
				itemicon: "ui-icon-arrowreturnthick-1-n",
				action: "ReleaseDocument(true)",
				disabled: !showPromoteVersion
			}, {
				title: "Activate discarded",
				itemicon: "ui-icon-arrowrefresh-1-e",
				action: "ActivateVersion()",
				disabled: !showActivateDiscarded
			}
		]
	});
}

//-------- mail acquire handler -----------
function AcquireEmail() {
	var dlgUrl = docInfo.MailAcquireDialogUrl;
	if (dlgUrl == '') {
		window.top.Docova.Utils.messageBox({
			title: "Import Messages Not Available",
			prompt: "Import Messages is not available for your current mail configuration.",
			icontype: 4,
			msgboxtype: 0,
			width: 400
		});
		return false;
	}

	if (docInfo.UserMailSystem == "O" && !window.top.Docova.IsPluginAlive) {
		window.top.Docova.Utils.messageBox({
			title: "DOCOVA Plugin Not Running",
			prompt: "The DOCOVA Plugin is required for the importing of messages from Outlook.",
			width: 400,
			icontype: 4,
			msgboxtype: 0
		});
		return false;
	}

	var dlgmail = window.top.Docova.Utils.createDialog({
			id: "divDlgAcquireMessages",
			url: dlgUrl,
			title: "Import Mail Messages",
			height: 600,
			width: 800,
			useiframe: true,
			sourcedocument: document,
			sourcewindow: window,
			buttons: {
				"Close": function () {
					var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.GetImportCount();
					dlgmail.closeDialog();
				},
				"Acquire": function () {
					var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.CompleteDialog(function (returnValue) {
							if (returnValue) {
								dlgmail.closeDialog();
							}
						});
				}
			}
		});
} //--end AcquireEmail


//-------- related links handler -----------
function AddRelatedLinks() {
	var dlgParams = window;
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgRelatedDocMain?ReadForm&goto=" + docInfo.LibraryKey + "," + docInfo.FolderID
		var dlgSettings = "dialogHeight:500px;dialogWidth:810px;center:yes; help:no; resizable:yes; status:no;";
	return window.showModalDialog(dlgUrl, dlgParams, dlgSettings); //Display the address dialog

}

//-- initialize default subject and call send email dialog
function TriggerSendDocumentMessage() {
	var defsubject = jQuery("input[name=Subject]").val();
	if (defsubject == undefined) {
		defsubject = docInfo.DocTitle;
	}
	if (defsubject == undefined) {
		defsubject = "";
	} else {
		if (defsubject != "") {
			defsubject = "Re:" + defsubject;
		}
	}
	SendDocumentMessage(defsubject);
} //--end TriggerSendDocumentMessage

// ---------- send mail memo with link to document, files of document or via Public Access --------
function SendDocumentMessage(optionalDefaultSubject, optionalDefaultBody) {
	var fwdAttachments = "Yes";
	if (docInfo.EnableForwarding == "") {
		fwdAttachments = "0";
	}

	dlgParams.length = 0; //reset dlgParams array.
	dlgParams[0] = optionalDefaultSubject;
	dlgParams[1] = optionalDefaultBody;
	//See dlgParams[2] also at the end of this function which is set to the dialog object so the dialog can use .closeDialog()

	if (_DOCOVAEdition == "SE") {
		var dlgUrl = docInfo.ServerUrl + "/" + NsfName + "/" + "dlgSendLinkMessage?OpenForm&DocUNID=" + docInfo.DocID + "&FwdAtt=" + fwdAttachments;
	} else {
		var dlgUrl = docInfo.ServerUrl + "/" + NsfName + "/" + "dlgSendLinkMessage?OpenForm&ParentUNID=" + docInfo.DocID + "&FwdAtt=" + fwdAttachments;
	}

	var dlgSendLinkMessage = window.top.Docova.Utils.createDialog({
			id: "divDlgSendDocMessage",
			url: dlgUrl,
			title: "Send Email Notification",
			height: 500,
			width: 420,
			useiframe: true,
			sourcedocument: document,
			sourcewindow: window,
			buttons: {
				"Send": function () {
					var dlgDoc = window.top.$("#divDlgSendDocMessageIFrame")[0].contentWindow.document;
					var dlgWin = window.top.$("#divDlgSendDocMessageIFrame")[0].contentWindow;
					var sendto = $.trim($("#SendTo", dlgDoc).val());
					var subject = $.trim($("#Subject", dlgDoc).val());
					var body = $.trim($("#Body", dlgDoc).val());
					var contentinclude = $("input[name=ContentInclude]:checked", dlgDoc).val();
					//--- If activity type is not selected
					if (sendto == "") {
						window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter the recipient names.",
							width: 300,
							icontype: 1,
							msgboxtype: 0
						});
						return false;
					}
					//--- If recipient is not document owner and/or sendto list
					if (subject == "") {
						window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter the subject",
							width: 300,
							icontype: 1,
							msgboxtype: 0
						});
						return false;
					}
					//--- If subject for activity email is blank.
					if (body == "") {
						window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter a message.",
							width: 300,
							icontype: 1,
							msgboxtype: 0
						});
						return false;
					}

					if (contentinclude == "P") {
						dlgWin.completeWizard()
					} else {
						if (_DOCOVAEdition == "SE") {
							var docurl = docurl = docInfo.ServerUrl + docInfo.PortalWebPath + "/wReadDocument/" + docInfo.DocID + "?OpenDocument&ParentUNID=" + docInfo.AppID + "&mode=window";
						} else {
							var docurl = "";
						}

						//--- If all ok, generate request
						var request = "<?xml version='1.0' encoding='UTF-8' ?>";
						request += "<Request>";
						request += "<Action>";
						request += (contentinclude == "A") ? "SENDATTACHMENTMSG" : "SENDLINKMSG";
						request += "</Action>";
						request += "<SendTo><![CDATA[" + sendto + "]]></SendTo>";
						request += "<Subject><![CDATA[" + subject + "]]></Subject>";
						request += "<Body><![CDATA[" + body + "]]></Body>";
						request += "<UserName><![CDATA[" + docInfo.UserName + "]]></UserName>";
						request += "<FolderName></FolderName>";
						request += "<FolderPath></FolderPath>";
						request += "<Unid>" + docInfo.DocID + "</Unid>";
						request += "<Link><![CDATA[" + docurl + "]]></Link>";
						request += "</Request>"
						DoSendDocumentMessage(request);
						dlgSendLinkMessage.closeDialog();
					}
				},
				"Cancel": function () {
					dlgSendLinkMessage.closeDialog();
				}
			}
		})

		//Puts the dialog into the dlgParams array so that it is available to be closed with closeDialog() within the dialog itself.
		dlgParams[2] = dlgSendLinkMessage;
}

function DoSendDocumentMessage(request) {
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/MessagingServices?OpenAgent"

		jQuery.ajax({
			type: "POST",
			url: url,
			data: request,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				var xmlobj = $(xml);
				var statustext = $(xmlobj).find("Result").first().text()
					if (statustext == "OK") {
						alert("Message was sent.")
					}
			},
			error: function () {
				alert("Error.  Message was not sent.  Please check error logs for more information.")
			}
		})
}

//Forward a document as an email with an optional introduction
function ForwardDocument() {
	var selecteddocid = docInfo.DocID;

	//-----------------------------------------------------------
	//check current user access to parent folder
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/FolderServices?OpenAgent"
		var request = "";

	request += "<Request>";
	request += "<Action>QUERYACCESS</Action>";
	request += "<AccessType>CANCREATEDOCUMENTS</AccessType>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<DocKey>" + docInfo.FolderID + "</DocKey>";
	request += "</Request>";

	request += encodeURIComponent(request);

	var httpObj = new objHTTP();

	var canadddocs = 0;

	if (httpObj.PostData(request, url)) {
		if (httpObj.status == "OK" && httpObj.resultCount > 0) {
			canadddocs = httpObj.results[0];
		}
	}

	var forcesave = (docInfo.ForwardSave == "1"); //1 indicates Force Save of Forwards
	var promptsave = (docInfo.ForwardSave == "2"); //2 indicates Prompt for Save of Forwards
	var savecopyoption = (canadddocs == 1) ? (forcesave ? "1" : (promptsave ? "2" : "0")) : "0"; //if user is unable to make revisions disable saving of forwards
	var defsubject = encodeURIComponent(docInfo.DocTitle);

	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgForwardDocument?OpenForm&SourceDocUNID=" + selecteddocid + "&savecopy=" + savecopyoption + "&DefaultSubject=" + defsubject;

	var fwdDialog = window.top.Docova.Utils.createDialog({
			id: "divForwardDoc",
			url: dlgUrl,
			title: "Forward Document",
			height: 610,
			width: 700,
			useiframe: true,
			defaultButton: 1,
			sourcedocument: document,
			buttons: {
				"Send": function () {
					var dlg = "";
					var dlgDoc = "";
					if ($("#divForwardDocIFrame", this)[0].contentWindow) {
						dlg = $("#divForwardDocIFrame", this)[0].contentWindow;
					} else {
						dlg = $("#divForwardDocIFrame", this)[0].window;
					}
					if (dlg.completeWizard()) {
						fwdDialog.closeDialog();
						window.top.Docova.Utils.messageBox({
							prompt: "Message was forwarded.",
							title: "Message Forwarded"
						})
					}
				},
				"Cancel": function () {
					fwdDialog.closeDialog();
				}
			}
		})

		return false;
}

//--------------- deletes temporary files ad folder after succesful submision
function ClearTempFiles() {
	if (tmpFilePaths.length == 0 && tmpSupportFolders.length == 0) {
		return true;
	}
	try {
		for (var i = 0; i < tmpSupportFolders.length; i++) {
			DocovaExtensions.deleteFolder(tmpSupportFolders[i]);
		}
		for (var i = 0; i < tmpFilePaths.length; i++) {
			DocovaExtensions.deleteFile(tmpFilePaths[i], true);
		}
	} catch (e) {
		alert("Error deleting temporary files.\rError: " + e.message);
	}
}

//----------------- file operation logging ------------------
function GetFileChanges(controlNo, append) {

	var ctrlno = 1;
	var uploader = window["DLIUploader" + ctrlno];
	while (uploader) {

		// Getting original date list from newly added files
		var newFileList = uploader.GetNewFileNames("*", false);
		var editedFileList = uploader.GetEditedFileNames("*");
		var deletedFileList = uploader.GetDeletedFileNames("*");
		var changedFileList = "";
		var hasNewFiles = false;
		var hasEditedFiles = false;
		var hasDeletedFiles = false;

		//----- If there are new files, then manage the new files
		if (newFileList != "") {
			var result = ManageNewFiles(newFileList, uploader)
		}



		ctrlno++;
		uploader = window["DLIUploader" + ctrlno];
	}

}

//-------------------------- manually archive document ----------------------

function ArchiveDocument() {

	if (!CanModifyDocument(true)) {
		return false;
	}
	var msg = "You are about to archive the current document.  Are you sure?"
		window.top.Docova.Utils.messageBox({
			prompt: msg,
			title: "Archive document?",
			width: 400,
			icontype: 2,
			msgboxtype: 4,
			onYes: function () {
				var request = "";
				//--collect the xml for all nodes to be processed
				request += "<Request>";
				request += "<Action>ARCHIVESELECTED</Action>";
				request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
				request += "<Unid>" + docInfo.DocID + "</Unid>";
				request += "</Request>";

				//--- processing agent url
				var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent"
					jQuery.ajax({
						type: "POST",
						url: url,
						data: request,
						cache: false,
						async: false,
						dataType: "xml",
						success: function (xml) {
							var xmlobj = $(xml);
							var statustext = $(xmlobj).find("Results").first().text();
							if (statustext == "OK") {
								alert("Document was successfully archived.")
								CloseDocument(true);
							}
						},
						error: function () {
							alert("Error: There was a problem archiving this document. Please check the error logs for more information.");
						}
					})
			},
			onNo: function () {
				return false;
			}
		});
}

function CopyLink() {
	var docUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/wHomeFrame?ReadForm&goto=" + docInfo.LibraryKey + "," + docInfo.FolderID + "," + docInfo.DocKey;
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgCopyDocURL?OpenForm"
		dlgParams.length = 0;
	dlgParams[0] = docUrl
		var dlgCopyDocURL = window.top.Docova.Utils.createDialog({
			id: "divDocUrl",
			url: dlgUrl,
			title: "Document Link URL",
			height: 300,
			width: 400,
			useiframe: true,
			sourcewindow: window,
			buttons: {
				"Close": function () {
					dlgCopyDocURL.closeDialog();
				}
			}
		})
}

function ManageNewFiles(newFileList, uploader) {

	var newFileArray = newFileList.split("*");
	var currOFileNames = $("#OFileNames").val();
	var currOFileDates = $("#OFileDates").val();
	var newFileList = "";
	var newFileDateList = "";

	var currindx;
	var origdate;
	var count;
	var FileName;
	var fullFileName;
	var FileNameArray;
	var FileNameAndDate;
	var trackingFileList = "";

	//----- Get original dates from new files -----
	count = newFileArray.length - 1;
	for (var n = 0; n <= count; n++) {
		fullFileName = newFileArray[n]
			FileNameArray = fullFileName.split("\\")
			FileName = FileNameArray[FileNameArray.length - 1];
		currindx = uploader.GetFileIndex(fullFileName)
			origdate = uploader.GetFileDate(currindx)

			//-- check to see if file has been renamed. if so use the new name
			var tmpRenFileName = "";
		try {
			tmpRenFileName = uploader.GetRenamedFileName(currindx);
		} catch (err) {}
		if (tmpRenFileName != ""){
			FileName = tmpRenFileName;
		}
		//-------------------------------------------------------------------------------------
		if (n != count) {
			newFileList += FileName + ";";
			newFileDateList += origdate + ";";
		} else {
			newFileList += FileName;
			newFileDateList += origdate;
		}
	}
	if (currOFileDates != "") {
		currOFileNames += ";" + newFileList;
		currOFileDates += ";" + newFileDateList;
	} else {
		currOFileNames = newFileList;
		currOFileDates = newFileDateList;
	}

	$("#OFileNames").val(currOFileNames);
	$("#OFileDates").val(currOFileDates);
}

function ManageChangedFiles(changedFileList) {
	var changedFileArray = changedFileList.split("*");
	var currOFileNames = $("#OFileNames").val();
	var currOFileNamesArray = currOFileNames.split(";");
	var currOFileDates = $("#OFileDates").val();
	var currOFileDatesArray = currOFileDates.split(";");

	var currOFileDate;
	var currOFileName;
	var newOFileNameArray;
	var newOFileDateArray;
	var i;
	var j;
	var resultOFileList = "";
	var resultODateList = "";
	var matchfound = false;

	for (i = 0; i <= currOFileNamesArray.length - 1; i++) {
		currOFileName = currOFileNamesArray[i]
			currOFileDate = currOFileDatesArray[i]
			matchfound = false
			for (j = 0; j <= changedFileArray.length - 1; j++) {
				currChangedFile = changedFileArray[j];
				currOFileName = ltrim(currOFileName);
				if (currChangedFile == currOFileName) {
					matchfound = true;
				}
			}
			if (!matchfound) {
				if (resultOFileList == "") {
					resultOFileList = currOFileName;
					resultODateList = currOFileDate;
				} else {
					resultOFileList += ";" + currOFileName;
					resultODateList += ";" + currOFileDate;
				}
			}
	}

	$("#OFileNames").val(resultOFileList);
	$("#OFileDates").val(resultODateList);
	return (true)
}

function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g, "");
}
function ltrim(stringToTrim) {
	return stringToTrim.replace(/^\s+/, "");
}
function rtrim(stringToTrim) {
	return stringToTrim.replace(/\s+$/, "");
}

/*******************************************************************
 * Prints page after adjusting the display
 *********************************************************************/
function standardPrintPage() {
	try {
		$("#btnPrintPage").tooltip("destroy");
	} catch (e) {}

	$("#DocToolsMenu").hide();

	var curoverflow = jQuery("div.divFormContentSectionPage").css("overflow");
	jQuery(".hideOnPrint").hide();
	jQuery("div.divFormContentSectionPage").css("overflow", "initial");
	window.print();
	jQuery(".hideOnPrint").show()
	jQuery("div.divFormContentSectionPage").css("overflow", curoverflow);

	return;
}

function ClearTmpOrphans(xmlDoc) {
	var request = "<Request><Action>REMOVETMPORPHANS</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
		request += xmlDoc.xml
		request += "</Request>"

		//Send request to server
		var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent"
		jQuery.ajax({
			type: "POST",
			url: processUrl,
			data: request,
			cache: false,
			async: false,
			dataType: "xml",
			success: function (xml) {
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					alert("Should be deleted")
				}
			},
			error: function () {}
		})
}

function UserHasFilesCheckedOut() {
	if (!document.getElementById("xmlFileLog")) {
		return false;
	}

	var parser = new DOMParser;
	var xmlString = $("#xmlFileLog").html()
		var objCoFiles = parser.parseFromString(xmlString, "text/xml")

		var nodeList = objCoFiles.selectNodes('cofiles/file[editor="' + docInfo.UserNameAB + '"]');
	var userHasFiles = false;

	if (nodeList != null) {
		userHasFiles = (nodeList.length > 0);
	}

	return userHasFiles;
}

/*******************************************************************
 * Creates a bookmark copy of a document in a specified folder
 *********************************************************************/
function CreateBookmark() {
	window.top.Docova.Utils.messageBox({
		title: "Create Bookmark?",
		prompt: "Would you like to create a Bookmark entry for the current document?",
		icontype: 2,
		msgboxtype: 4,
		width: 400,
		onYes: function () {
			//-- choose target folder
			var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgFolderSelect?ReadForm&flags=create,notcurrent,norecycle";
			var folderdbox = window.top.Docova.Utils.createDialog({
					id: "divDlgFolderSelect",
					url: dlgUrl,
					title: "Select Bookmark Folder",
					height: 420,
					width: 420,
					useiframe: true,
					sourcedocument: document,
					buttons: {
						"Create Bookmark": function () {
							var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();

							//-- returnValue [0]=LibraryID, [1]=FolderID, [2]=FolderUNID, [3]=FolderAccessLevel
							if (returnValue) {
								if (returnValue[1] == docInfo.FolderID) {
									alert("Unable to create bookmark in the same folder as the source document. Please choose an alternate folder.");
									return;
								}
								//---------------------------------- Check Folder Access Level -----------------------------------------
								if (Number(returnValue[3]) < 3) {
									alert("You do not have sufficient rights to create documents in the selected folder. Please choose an alternate folder.");
									return;
								}
								folderdbox.closeDialog();
								//--- processing agent url
								var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
								//--build the CREATEBOOKMARK request
								var request = "";
								request += "<Request>";
								request += "<Action>CREATEBOOKMARK</Action>";
								request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
								request += "<Unid>" + docInfo.DocID + "</Unid>";
								request += "<LibraryID>" + returnValue[0] + "</LibraryID>";
								request += "<FolderID>" + returnValue[1] + "</FolderID>";
								request += "</Request>";
								var httpObj = new objHTTP();
								if (httpObj.PostData(request, url)) {
									if (httpObj.status == "OK") {
										if (httpObj.results.length > 0) {
											alert("Bookmark successfully created in chosen folder.");
										}
									}
								}
							}
						},
						"Cancel": function () {
							folderdbox.closeDialog();
						}
					}
				}); //end createDialog
		} //end onYes
	})
} //--end CreateBookmark

function BookmarkOpenParent() {
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"
		var request = "";

	//--build the BOOKMARKPARENT request
	request += "<Request>";
	request += "<Action>GETURL</Action>";
	request += "<Type>BOOKMARKPARENT</Type>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "</Request>";

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if (httpObj.PostData(request, url)) {
		if (httpObj.status == "OK") {
			if (httpObj.results.length > 0) {
				var docUrl = httpObj.results[0];
				var height = document.body.clientHeight;
				var width = Math.round(document.body.clientWidth * .9);
				window.open(docUrl, "Preview", "height=" + height + ",width=" + width + ",status=no,toolbar=no,menubar=no,location=no,resizable=yes");
			}
		}
	}
}

//TinyMCE functions
function ProcessTinyMCE() {

	$("div[processMceEditor='1']").each(function () {

		var fldname = $(this).attr("id");
		var fname = $(this).attr("name");

		var editorsettings = ($(this).attr("editorSettings") || "").split(";");
		var editorheight = ($(this).attr("editorHeight") || "");
		var editorwidth = ($(this).attr("editorWidth") || "");

		srcField = document.getElementById(fname);
		//if srcField is nothere..its because Notes is not showing it
		//because of controlled access..
		if (typeof srcField == 'undefined' || srcField == null) {
			var htmlval = $("#rdOnly" + fname).html();
			$(this).html(htmlval);
			$(this).attr("contenteditable", "false");
			return;
		}
		var wrapperDiv = this;

		$("#" + fldname).html($(srcField).val());

		//-- if doc is being refreshed then we can re-initialize the existing tinymce editor instance
		if (info && info.isRefresh) {
			tinyMCE.EditorManager.execCommand('mceRemoveEditor', true, fldname);
		}

		tinyMCE.init({
			mode: "exact",
			elements: fldname,
			plugins: "link, image, imagetools, table, preview, code, contextmenu, hr, textcolor, lists, advlist, autoresize",
			autoresize_bottom_margin: "0px",
			menu: (editorsettings.indexOf("HM") > -1 ? {}
				 : { // this is the complete default configuration
				edit: {
					title: 'Edit',
					items: 'undo redo | cut copy paste pastetext | selectall'
				},
				table: {
					title: 'Table',
					items: 'inserttable tableprops deletetable | cell row column'
				}
			}),
			toolbar: (editorsettings.indexOf("HT") > -1 ? [] : ["bold italic underline strikethrough removeformat | forecolor backcolor | alignleft aligncenter alignright alignjustify | styleselect | formatselect | fontselect fontsizeselect",
					"cut copy paste | bullist numlist | outdent indent | undo redo | subscript superscript | hr anchor | link unlink image | preview code"]),
			statusbar: (editorsettings.indexOf("HS") > -1 ? false : true),
			branding: false,
			height: editorheight,
			width: editorwidth,
			relative_urls: false,
			remove_script_host: false,
			convert_urls: false,
			valid_elements: "*[*]",
			document_base_url: "/" + docInfo.NsfName + "/luAllByDocKey/" + docInfo.DocKey + "/$File/",
			resize: true,
			paste_data_images: true,
			browser_spellcheck: true,
			init_instance_callback: function (editor) {
				//call function to perform conversion on migrated image content
				convertImagesToInline(editor);
				// Loads a CSS file into the currently active editor instance
				if (_DOCOVAEdition == "SE") {
					editor.dom.loadCSS("/docova/web/bundles/docova/font-awesome/css/all.min.css");
				} else {
					editor.dom.loadCSS(docInfo.PortalWebPath + "/font-awesome/css/all.min.css");
				}
			},
			setup: function (editor) {
				editor.on('init', function (e) {
					if (editorheight) {
						document.getElementById(editor.id + '_ifr').style.height = editorheight + 'px';
					}
					if (editorwidth) {
						var unittype = (editorwidth.indexOf("px") == -1 && editorwidth.indexOf("%") == -1 ? 'px' : "");
						jQuery("#" + editor.id + '_ifr')
						.css("width", editorwidth + unittype);
						jQuery("#" + editor.id + '_ifr')
						.closest("div.mce-tinymce.mce-container.mce-panel")
						.css("width", editorwidth + unittype);
					}
				});

				editor.on('click', function (e) {
					if (wrapperDiv) {
						jQuery(wrapperDiv).click();
					}
				});

				editor.on('focus', function (e) {
					if (wrapperDiv) {
						$(wrapperDiv).focus();
					}
				});

				editor.on('blur', function (e) {
					if (wrapperDiv) {
						$(wrapperDiv).blur();
					}
				});

				editor.on('change', function (e) {
					if (wrapperDiv) {
						$(wrapperDiv).change();
					}
				});

				editor.on('paste', function (e) {
					var result = true;

					var cbData = null;
					if (e.clipboardData) {
						cbData = e.clipboardData;
					} else if (window.clipboardData) {
						cbData = window.clipboardData;
					}

					if (cbData && cbData.items) {
						if ((text = cbData.getData("text/plain"))) {
							// Text pasting is already handled
						} else {
							for (var i = 0; i < cbData.items.length; i++) {
								if (cbData.items[i].type.indexOf('image') !== -1) {
									var blob = cbData.items[i].getAsFile();
									readPastedBlob(blob);
									result = false; //stops browser from double paste
								}
							}
						}
					}

					function readPastedBlob(blob) {
						if (blob) {
							reader = new FileReader();
							reader.onload = function (evt) {
								pasteImage(evt.target.result);
							};
							reader.readAsDataURL(blob);
						}
					}

					function pasteImage(source) {
						var image = "<img src='" + source + "' data-mce-selected='1'></img>";
						window.tinyMCE.execCommand('mceInsertContent', false, image);
					}

					return result;
				});

			}
		});

		//this code is taken from the sfDocSection-RTEditor to fix inline urls
		//check if there are any attachments
		if (docInfo.DocAttachmentNames != null || docInfo.DocAttachmentNames != "") {

			var attachList = docInfo.DocAttachmentNames.split("*");

			jQuery(this).find("img").each(function () {
				var newSrc = jQuery(this).attr("src");
				newSrc = newSrc.replace(/^.*[\\\/]/, '');
				if (attachList.indexOf(newSrc) > -1) {
					newSrc = "/" + docInfo.NsfName + "/luAllByDocKey/" + docInfo.DocKey + "/$File/" + newSrc;
					jQuery(this).attr("src", newSrc);
				}
			});
		}

		//this code is taken from the sfDocSection-RTEditor to expand all the iframes
		//expand html table iframes
		iframeBoxes = jQuery(this).find("IFRAME");
		if (iframeBoxes.length > 0) {
			//-------------------------------------------------------------------------
			count = iframeBoxes.length;

			//-------------------------------------------------------------------------
			for (a = 0; a < count; a++) {
				obj = iframeBoxes[a];
				obj.style.height = obj.document.body.scrollHeight
			}
		}

	});

}

function saveDocovaRichTextTinyMCE() {

	$("div[processMceEditor='1']").each(function () {
		try {
			var id = $(this).attr("id");
			var html = tinyMCE.get(id).getContent();

			sourceDivName = $(this).attr("name");
			targetField = document.getElementById(sourceDivName);

			$(targetField).val(html);
		} catch (e) {}
	});

}

//docova editor functions
function ProcessDocovaEditor() {
	var targetobj = null;
	$("div[processDocovaEditor='1']").each(function () {
		var editorheight = $(this).attr("editorheight");
		if (editorheight) {
			$(this).css("min-height", editorheight + "px");
		} else {
			$(this).css("min-height", "100px");
		}
		loadIcons(this);
		if (targetobj === null) {
			targetobj = [];
		}
		targetobj.push(this);
	});
	if (targetobj) {
		//add an event call to convert migrated inline images
		window.onload = function () {
			convertImagesToInline(targetobj);
		};
	}
}

function saveDocovaRichText() {
	var sourceDiv;
	var sourceDivName;
	var targetField;

	$("div[processDocovaEditor='1']").each(function () {
		sourceDivName = $(this).attr("name");
		targetField = document.getElementById(sourceDivName);
		$(targetField).val($(this).html())
	});

}
/*--------------------------------------------------------------------------------------------------------------------------------------------
 * Function: convertRTtoHTML
 * Looks for rich text based fields on the form that need to be migrated from RT to HTML
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function convertRTtoHTML() {
	if (!docInfo.isNewDoc && docInfo.ConvertRTtoHTML == "1") {

		jQuery("span[elemtype='richtext'],div[elemtype='richtext']").each(function () {
			var spanelem = this;
			var elemid = jQuery(spanelem).attr("id");
			var srcfieldname = (elemid.indexOf("rdOnly") == 0 ? elemid.slice(6) : elemid);
			var RTFUrl = docInfo.DataDocUrl + "/" + srcfieldname + "?OpenField";
			jQuery.ajax({
				type: "GET",
				url: RTFUrl,
				cache: false,
				async: false,
				success: function (data) {
					var converted = false;
					if (docInfo.isDocBeingEdited) {
						//try to apply to an editable field
						var rtTextArea = jQuery("textarea[name=" + srcfieldname + "]");
						if (rtTextArea.length > 0) {
							var tempdata = sanitizeHTML(data);
							rtTextArea.val(tempdata);
							converted = true;
						}
					}
					//not converted yet. maybe because in read mode. maybe because of controlled access section.
					if (!converted) {
						var tempdata = sanitizeHTML(data);
						jQuery(spanelem).html(tempdata);
					}
				},
				error: function () {
					//--do nothing
				}
			});

		});
	}
} //--end convertRTtoHTML

/*--------------------------------------------------------------------------------------------------------------------------------------------
 * Function: sanitizeHTML
 * Replaces certain tags in html text that can be problematic when later loaded into form
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function sanitizeHTML(htmldata) {

	htmldata = htmldata.replace(/\[\</gi, "[ <");
	htmldata = htmldata.replace(/\>\]/gi, "> ]");

	return htmldata;
} //--end sanitizeHTML

/*--------------------------------------------------------------------------------------------------------------------------------------------
 * Function: convertImagesToInline
 * Searches rich text areas for image tags with a source reference and converts them to inline base64 img tags
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function convertImagesToInline(targetobj) {
	if (!docInfo.isNewDoc && docInfo.isDocBeingEdited && docInfo.ConvertRTtoHTML == "1") {
		var tempobjarray = (Array.isArray(targetobj) ? targetobj : [targetobj]);

		for (var i = 0; i < tempobjarray.length; i++) {
			var targetobj = tempobjarray[i];
			//-- code to handle TinyMCE content
			if ((typeof targetobj.type !== 'undefined' && targetobj.type == "setupeditor") || (typeof targetobj.editorManager !== 'undefined')) {

				targetobj.$("img").each(function () {
					var b64img = getBase64Image(this, false, "png");
					targetobj.$(this).attr("src", b64img).removeAttr("data-mce-src");
				});
				//-- code to handle Docova Rich Text Editor content
			} else {
				jQuery(targetobj).find("img").each(function () {
					var b64img = getBase64Image(this, false, "png");
					jQuery(this).attr("src", b64img);
				});
			}
		}
	}

} //--end convertImagesToInline


/*--------------------------------------------------------------------------------------------------------------------------------------------
 * Function: getBase64Image
 * Retrieves an image based on url reference and returns the base64 text rendition
 * Inputs: img - domelement - image node
 *         imageonly - boolean (optional) - true to retrieve just the image data without the mime header
 *         imagetype - string (optional) - png, jpeg, gif, bmp
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function getBase64Image(img, imageonly, imagetype) {
	var result = "";

	if (!img.complete || (typeof img.naturalWidth !== 'undefined' && img.naturalWidth === 0)) {
		return result;
	}

	var imgtype = "image/" + (!imagetype ? "png" : imagetype);
	var dataURL = "";
	var canvas = document.createElement("canvas");
	var ctx = canvas.getContext("2d");
	canvas.width = img.width;
	canvas.height = img.height;

	ctx.drawImage(img, 0, 0, img.width, img.height);
	dataURL = canvas.toDataURL(imgtype);

	if (!imageonly) {
		result = dataURL;
	} else {
		result = dataURL.replace(/^data:image\/(png|jpg|gif|bmp);base64,/, "");
	}

	return result;
} //--end getBase64Image


function setTargets() {
	var link;
	var i = 0;
	while (link = document.links[i++]) {
		link.target = '_blank';
	}
}

function showHelp(clickObject) {
	Docova.Utils.messageBox({
		title: "DOCOVA Richtext Editor Help",
		prompt: helpText,
		width: 400,
		icontype: 4,
		msgboxtype: 0
	})
}

var currEditAction = "" //global so that it can be set in setColor where colorpicker utilizes a callback funtion to setTextColor
function setTextColor(colorval) {
	document.execCommand(currEditAction, "", colorval)
}

function setColor(event) {
	var source = event.target;
	var editAction = $(source).attr("editaction")
		currEditAction = editAction;

	Docova.Utils.colorPicker(event, "", setTextColor)
}

function applyTool(editObj, reset) {
	var editAction = $(editObj).attr("editaction")

		if (editAction == "FontSize" || editAction == "FontName") {
			var editValue = $(editObj).val();
			document.execCommand(editAction, "", editValue);
		} else {
			document.execCommand(editAction);
		}

		if (reset) {
			$(editObj).val("null");
		}
}

function loadIcons(obj) {
	var obj = $(obj);
	var spanrepeat = 0;
	setTargets(); //force all links to open in a new window
	var name = obj.attr("name");
	var targetDiv = document.getElementById("dEdit" + name);
	var srcField = document.getElementById($(targetDiv).attr("name"));

	//if srcField is nothere..its because Notes is not showing it
	//because of controlled access..
	if (srcField == null || typeof srcField == 'undefined') {
		var htmlval = $("#rdOnly" + name).html();
		obj.html(htmlval);
		obj.attr("contenteditable", "false");
		return;
	}

	if (docInfo.isDocBeingEdited) {

		var tools = ["sep", "Bold", "Italic", "Underline", "StrikeThrough", "sep", "JustifyLeft", "JustifyCenter",
			"JustifyRight", "sep", "Cut", "Copy", "Paste", "sep", "ForeColor", "BackColor", "sep", "InsertUnorderedList",
			"InsertOrderedList", "Indent", "Outdent", "sep", "CreateLink", "Unlink", "sep", "Undo", "Redo",
			"InsertHorizontalRule", "RemoveFormat", "sep", "Help", "FontName", "FontSize"];
		var name = obj.attr("name");
		tbar = document.getElementById("dToolbar" + name)
			tbarHTML = "";
		for (x = 0; x < tools.length; x++) {
			if (tools[x] == "sep") { //uses sep to make buttonsets
				spanrepeat = spanrepeat + 1;
				if (spanrepeat == 1) {
					tbarHTML += "<span class='btngroup'>"
				}
				if (spanrepeat > 1) {
					tbarHTML += "</span><span class='btngroup'>"
				}
			} else if (tools[x] == "FontName") {
				tbarHTML += "<br><select unselectable='on' class='DropDown' id=" + tools[x] + name + " onchange='applyTool(this, true);' editaction='" + tools[x] + "'>" +
				"<option value='null'>- Select Font -<option value='Arial'>Arial" +
				"<option value='Book Antiqua'>Book Antiqua</font><option value='Comic Sans MS'>Comic Sans<option value='Courier New'>Courier New" +
				"<option value='Tahoma'>Tahoma<option value='Times New Roman'>Times New Roman<option value='Verdana'>Verdana</select>"
			} else if (tools[x] == "FontSize") {
				tbarHTML += " <select unselectable='on' class='DropDown' id=" + tools[x] + name + " onchange='applyTool(this, true);' editaction='" + tools[x] + "'>" +
				"<option value='null'>- Font Size -<option value='1'>1 (8pt)" +
				"<option value='2'>2 (10pt)<option value='3'>3 (12pt)<option value='4'>4 (14pt)<option value='5'>5 (18pt)<option value='6'>6 (24pt)<option value='7'>7 (36pt)</select>"
			} else if (tools[x] == "ForeColor" || tools[x] == "BackColor") {
				tbarHTML += "<button unselectable='on' type='button' class='btnDEditor " + "edt" + tools[x] + "' onclick='setColor(event)' id=" + tools[x] + name + " editaction='" + tools[x] + "'></button>"
			} else if (tools[x] == "Help") {
				tbarHTML += "<button unselectable='on' type='button' class='btnDEditor " + "edt" + tools[x] + "' onclick='showHelp(this);' id=" + tools[x] + name + " editaction='" + tools[x] + "'></button>"
			} else {
				tbarHTML += "<button unselectable='on' type='button' class='btnDEditor " + "edt" + tools[x] + "' onclick='applyTool(this)' id=" + tools[x] + name + " editaction='" + tools[x] + "'></button>"
			}
		}
		if (spanrepeat != 0) {
			tbarHTML += "</span>" //close off last span tag for button groups if any
		}
		$(tbar).html(tbarHTML)

		//load any existing content into divs from source fields
		targetDiv = document.getElementById("dEdit" + name);

		srcField = document.getElementById($(targetDiv).attr("name"));

		targetDiv.innerHTML = $(srcField).val()
			//for docova rich text editor
			$("button").button().click(function (event) {
				event.preventDefault();
			});
		$(".btngroup").buttonset();

	}
}
function HideShowDocComments() {
	if (_DOCOVAEdition == "SE") {
		if (docInfo.isNewDoc == 'true') {
			alert('Document needs to be saved first to be able to add/delete/view a comment.');
			return false;
		}
	}
	if ($("#comment-list").css("display") == "none") {
		getDocumentComments();
	}
	var effect = 'slide';
	var options = {
		direction: "right"
	}; //left, right, up, down
	var duration = 500;
	$('#comment-list').toggle(effect, options, duration);
}

function postMainComment() {
	//if the main comment input is empty, return
	if ($.trim($("#main-comment-text-input").text()) == "") {
		return false;
	}

	//get next thread index and id
	var nextThreadBoxIndex = getNextThreadBoxIndex();
	var nextThreadBoxId = "thread-box-" + nextThreadBoxIndex;
	var nextCommentBoxIndex = getNextCommentBoxIndex(nextThreadBoxId);
	var nextCommentBoxId = "comment-box-" + nextThreadBoxIndex + "-" + nextCommentBoxIndex;

	//Create backend comment document
	var app = Docova.getApplication();
	var newdoc = app.createDocument();
	newdoc.setField("Form", "d_DocComment");
	newdoc.setField("ParentDocKey", docInfo.DocKey);
	newdoc.setField("ThreadIndex", parseInt(nextThreadBoxIndex));
	newdoc.setField("CommentIndex", parseInt(nextCommentBoxIndex));
	newdoc.setField("AvatarIcon", "fa-comment-o");
	newdoc.setField("Commentor", docInfo.UserNameAB);
	newdoc.setField("Comment", $("#main-comment-text-input").html());

	if (newdoc.save()) { //If the backend comment was created the show the comment.
		//add new threadbox
		if (nextThreadBoxIndex == 1) {
			$("#thread-list").html(defaultThreadBoxHTML)
		} else {
			$("#thread-list").children(":first").before(defaultThreadBoxHTML);
		}
		var newThreadBox = $("#" + defaultThreadBoxId); //get new thread-box object
		setThreadBoxProperties(newThreadBox)
		$(newThreadBox).prop("id", nextThreadBoxId);
		$(newThreadBox).attr("tid", nextThreadBoxIndex);

		//add comment box to the thread box
		$(newThreadBox).children(":last").before(defaultCommentBoxHTML);
		var newCommentBox = $("#" + defaultCommentBoxId);
		$(newCommentBox).prop("id", nextCommentBoxId);
		$(newCommentBox).attr("tid", nextThreadBoxIndex);
		$(newCommentBox).attr("cid", nextCommentBoxIndex);
		$(newCommentBox).attr("did", newdoc.unid);
		$(newCommentBox).find(".comment-avatar i").addClass(" fa-comment-o");
		$(newCommentBox).children(".comment-commentor").text(docInfo.UserNameAB);
		var dtFormat = docInfo.SessionDateFormat + ", h:MM TT";
		var dt = Docova.Utils.formatDate(new Date(), dtFormat);
		$(newCommentBox).children(".comment-time-ago").text(dt);
		$(newCommentBox).children(".comment").html($("#main-comment-text-input").html()); //add main comment to comment box
		setCommentBoxProperties(newCommentBox);

		//Clear main comment
		$("#main-comment-text-input").html("");
		$("#main-comment-placeholder-text").css("display", "block");
		$("#btn-main-comment-post-button").button("disable");
		var currNumberOfComments = $(".comment-box").length
			$("#btnDocComments").button("option", "label", currNumberOfComments);
	} else {
		alert("Error: The Comment could not be posted. Please try again.");
	}
}

function postReplyComment(btnObj) {
	var currThreadBox = $(btnObj).parentsUntil("[elem=thread-box]").parent();
	var currThreadBoxId = $(currThreadBox).prop("id");
	var currThreadBoxIndex = $(currThreadBox).attr("tid");
	var nextCommentBoxIndex = getNextCommentBoxIndex(currThreadBoxId);
	var newCommentBoxId = "comment-box-" + currThreadBoxIndex + "-" + nextCommentBoxIndex;
	currThreadBox = $("#" + currThreadBoxId); //re-getting currThreadBox element...the above declaration seems like it doesn't work
	var ReplyPlaceholderText = $(currThreadBox).find(".reply-placeholder-text")
		var ReplyComment = $(currThreadBox).find(".reply-text-input");

	//Create backend comment document
	var app = Docova.getApplication();
	var newdoc = app.createDocument();
	newdoc.setField("Form", "d_DocComment");
	newdoc.setField("ParentDocKey", docInfo.DocKey);
	newdoc.setField("ThreadIndex", parseInt(currThreadBoxIndex));
	newdoc.setField("CommentIndex", parseInt(nextCommentBoxIndex));
	newdoc.setField("AvatarIcon", "fa-comments-o");
	newdoc.setField("Commentor", docInfo.UserNameAB);
	newdoc.setField("Comment", ReplyComment.html());

	if (newdoc.save()) { //If the backend comment doc is properly created then show the comment.
		tbReplyButton = $(currThreadBox).find(".btn-reply-post-button");
		$(currThreadBox).children(".comment-box:last").after(defaultCommentBoxHTML)
		var newCommentBox = $("#" + defaultCommentBoxId);
		$(newCommentBox).prop("id", newCommentBoxId);
		$(newCommentBox).attr("tid", currThreadBoxIndex);
		$(newCommentBox).attr("cid", nextCommentBoxIndex);
		$(newCommentBox).attr("did", newdoc.unid);

		$(newCommentBox).find(".comment-avatar i").addClass(" fa-comments-o");
		$(newCommentBox).children(".comment-commentor").text(docInfo.UserNameAB);
		var dtFormat = docInfo.SessionDateFormat + ", h:MM TT";
		var dt = Docova.Utils.formatDate(new Date(), dtFormat);
		$(newCommentBox).children(".comment-time-ago").text(dt);
		$(newCommentBox).children(".comment").html(ReplyComment.html());
		$(newCommentBox).children(".comment-delete").css("display", "");
		setCommentBoxProperties(newCommentBox);

		//Clear reply comment
		ReplyComment.html("");
		ReplyPlaceholderText.css("display", "");
		$(tbReplyButton).button("disable");
		var currNumberOfComments = $(".comment-box").length
			$("#btnDocComments").button("option", "label", currNumberOfComments);
	} else {
		alert("Error: The Reply could not be posted. Please try again.");
	}
}

function getNextThreadBoxIndex() {
	var threadIndex = 0;
	$("#thread-list").children().each(function () {
		var tid = parseInt($(this).attr("tid"));
		if (tid > threadIndex) {
			threadIndex = tid;
		}
	})
	var nextThreadIndex = threadIndex + 1;
	return nextThreadIndex;
}

function getNextCommentBoxIndex(threadBoxId) {
	var commentIndex = 0;
	$("#" + threadBoxId).children().each(function () {
		var cid = parseInt($(this).attr("cid"));
		if (cid > commentIndex) {
			commentIndex = cid;
		}
	})
	var nextCommentIndex = commentIndex + 1;
	return nextCommentIndex;
}

function setThreadBoxProperties(ThreadBoxObj) {
	$(ThreadBoxObj).on("mouseover", function () {
		$(this).removeClass("thread-box-off").addClass("thread-box-on");
	})
	$(ThreadBoxObj).on("mouseout", function () {
		$(this).removeClass("thread-box-on").addClass("thread-box-off");
	})
	$(ThreadBoxObj).on("click", function (e) {
		e.stopPropagation();
		if ($(this).children("table").css("display") == "none") {
			$(this).children("table").css("display", "")
			$(this).find(".comment-delete").css("display", "");
			$(this).children(".thread-reply-link").css("display", "none");
		} else {
			$(this).children("table").css("display", "none")
			$(this).find(".comment-delete").css("display", "none");
			$(this).children(".thread-reply-link").css("display", "");
		}
	})
	$(ThreadBoxObj).find(".reply-text-input").on("click", function (e) {
		e.stopPropagation();
	})
	//Set reply button properties in the current thread box, this is so the properties is only applied once
	var tbReplyButton = $(ThreadBoxObj).find(".btn-reply-post-button");
	$(tbReplyButton).button({
		text: true,
		label: "Post",
		icons: {
			primary: "far fa-comment"
		}
	})
	.click(function (event) {
		event.preventDefault();
		event.stopPropagation();
		postReplyComment(this);
	});

	$(tbReplyButton).button("disable");

	//set reply text input properties
	$(ThreadBoxObj).find(".reply-text-input").on("keyup", function () {
		if ($.trim($(this).text()) == "") {
			$(ThreadBoxObj).find(".reply-placeholder-text").css("display", ""); //prev is previous reply-placeholder-text
			$(tbReplyButton).button("disable");
		} else {
			$(ThreadBoxObj).find(".reply-placeholder-text").css("display", "none");
			$(tbReplyButton).button("enable");
		}
	})
}

function setCommentBoxProperties(CommentBoxObj) {
	$(CommentBoxObj).children(".comment-delete").on("click", function (e) {
		e.stopPropagation();
		deleteCommentBox(CommentBoxObj);
	})
}

function deleteCommentBox(CommentBoxObj) {
	var parentThreadBox = $(CommentBoxObj).parent();
	var numberOfComments = $(parentThreadBox).children(".comment-box").length;

	//check to ensure User can delete the current comment.
	//If the user has app access of 6 or greater then they can delete
	var app = Docova.getApplication();
	var cDocId = $(CommentBoxObj).attr("did");
	var cDoc = app.getDocument(cDocId);
	var allowDelete = false;
	if (parseInt(app.currentUserAccessLevel) >= 6) {
		allowDelete = true;
	} else {
		var currUser = cDoc.getFields("Commentor, Form");
		if (currUser['commentor'] == docInfo.UserNameAB) {
			allowDelete = true;
		}
	}

	if (allowDelete) {
		delmsgtxt = "You are about to delete this comment.<br><br>Are you sure?"
			var choice = window.top.Docova.Utils.messageBox({
				prompt: delmsgtxt,
				icontype: 2,
				title: "Delete Comment",
				width: 400,
				msgboxtype: 4,
				onNo: function () {
					return;
				},
				onYes: function () {
					var docdeleted = cDoc.deleteDocument();
					if (docdeleted) {
						if (numberOfComments == 1) { //if this is the last comment, remove the thread, else just remove the comment.
							$(parentThreadBox).remove();
							if ($("[elem=thread-box]").length == 0) { //if there are no more threads then show the welcome html
								$(".thread-list").html(commentStartHTML);
							}
						} else {
							$(CommentBoxObj).remove();
						}
						var currNumberOfComments = $(".comment-box").length
							$("#btnDocComments").button("option", "label", currNumberOfComments);
					}
				}
			});
	} else { //no access to delete
		window.top.Docova.Utils.messageBox({
			title: "No Access",
			prompt: "You do not have access to delete this comment.",
			icontype: 1,
			msgboxtype: 0,
			width: 500
		});
	}
}


function getDocumentCommentCount(){
	var serviceurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"

	request = "<Request>";
	request += "<Action>GETCOMMENTS</Action>";
	request += "<Key>" + docInfo.DocID + "</Key>";
	request += "</Request>";
	var commentCount = "0";
	jQuery.ajax({
		type: "POST",
		url: serviceurl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
				commentCount = jQuery(resultxmlobj).find("Document").length;
				
			}
			$("#btnDocComments").button("option", "label", commentCount);
		},
		error: function () {
			$("#btnDocComments").button("option", "label", commentCount);
		}
	});

}


function getDocumentComments() {

	var serviceurl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"

	request = "<Request>";
	request += "<Action>GETCOMMENTS</Action>";
	request += "<Key>" + docInfo.DocID + "</Key>";
	request += "</Request>";

	jQuery.ajax({
		type: "POST",
		url: serviceurl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if (statustext == "OK") {
				var resultxmlobj = xmlobj.find("Result[ID=Ret1]");
				var commentCount = jQuery(resultxmlobj).find("Document").length;


				if (commentCount > 0 ){
					var currThreadBoxIndex = 0;
					var currThreadBox = null;
					var cnt = 0;
					var currThreadBoxId = "thread-box-0";
					var commentBoxId = "comment-box-0-0";
					$("#btnDocComments").button("option", "label", commentCount); //shows # of comments in comment button
					jQuery(resultxmlobj).find("Document").each(function () {
							var parentdockey = jQuery(this).find("parentdockey").text();
							var docid = jQuery(this).find("ID").text();
							var threadindex = jQuery(this).find("threadIndex").text();
							var commentindex = jQuery(this).find("commentIndex").text();
							var avataricon = jQuery(this).find("avatar").text();
							var commentor = jQuery(this).find("commentor").text();
							var datecreated = jQuery(this).find("dateCreated").text();
							var comment = jQuery(this).find("comment").text();
							cnt ++;
							//Add a thread box
							if (threadindex != currThreadBoxIndex) {
								currThreadBoxIndex =threadindex;
								currThreadBoxId = "thread-box-" + threadindex;
									if (cnt == 1) {
										$("#thread-list").html(defaultThreadBoxHTML)
									} else {
										$("#thread-list").append(defaultThreadBoxHTML);
									}
									currThreadBox = $("#" + defaultThreadBoxId);
								setThreadBoxProperties(currThreadBox)
								$(currThreadBox).prop("id", currThreadBoxId);
								$(currThreadBox).attr("tid", threadindex);
							}
							//Add a comment to the current threadbox
							commentBoxId = "comment-box-" + threadindex + "-" + commentindex;
								$(currThreadBox).children(":last").before(defaultCommentBoxHTML)
								var newCommentBox = $("#" + defaultCommentBoxId);
							$(newCommentBox).prop("id", commentBoxId);
							$(newCommentBox).attr("tid", threadindex);
							$(newCommentBox).attr("cid", commentindex);
							$(newCommentBox).attr("did",  docid);
							$(newCommentBox).find(".comment-avatar i").addClass(avataricon);
							$(newCommentBox).children(".comment-commentor").text(commentor);
							var dtFormat = docInfo.SessionDateFormat + ", h:MM TT";
							var docDate = datecreated;
							var dt = Docova.Utils.formatDate(docDate, dtFormat);
							$(newCommentBox).children(".comment-time-ago").text(dt);
							$(newCommentBox).children(".comment").html(comment)
							setCommentBoxProperties(newCommentBox)
							
					});
				}else{
					$(".thread-list").html(commentStartHTML)
				}
			}
		},
		error: function () {$(".thread-list").html(commentStartHTML)}
	});

	
}

function PopulateBookmarks(localfilename, fieldList, keepbookmarks, valueList, macrotorun) {
	return window.top.DocovaExtensions.PopulateBookmarks(localfilename, fieldList, keepbookmarks, valueList, macrotorun, document);
}

function ReadExcelData(excelFilePath, xSheet, fieldList, cellList, macroName) {
	return window.top.DocovaExtensions.ReadExcelData(excelFilePath, xSheet, fieldList, cellList, macroName, document);
}

function WriteExcelData(excelFilePath, xSheet, fieldList, cellList, macroName) {
	return window.top.DocovaExtensions.WriteExcelData(excelFilePath, xSheet, fieldList, cellList, macroName, document);
}

function safe_quotes_js(str, escape, undo) {
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
