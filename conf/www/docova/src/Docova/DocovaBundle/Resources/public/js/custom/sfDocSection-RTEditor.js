
function initEditor(){
	if (_show_editor){	
		$("div[processMceEditor='1']").each ( function () {
			var fldname = $(this).attr("id");
			var fname = $(this).attr("name");
	
			var editorsettings = ($(this).attr("editorSettings") || "").split(";");
			var editorheight = ($(this).attr("editorHeight") || parseInt($('#divRTContent').attr('editorHeight'),10));
			var editorwidth = ($(this).attr("editorWidth") || "");
		
			srcField = document.getElementById(fname);
			//if srcField is nothere..its because Notes is not showing it
			//because of controlled access..
			if (typeof srcField == 'undefined' || srcField == null)
			{
				var htmlval = $("#rdOnly" + fname).html();
				$(this).html(htmlval);
				$(this).attr("contenteditable", "false");
				return;
			}
			var wrapperDiv = this;

			$("#" + fldname).html($(srcField).val() );
			
			tinyMCE.init({
				mode : "exact",	
				elements : fldname,
				plugins: "link, image, table, preview, code, contextmenu, hr, textcolor, lists, advlist",
				autoresize_bottom_margin : "0px",
				menu : { // this is the complete default configuration
        			edit   : {title : 'Edit'  , items : 'undo redo | cut copy paste pastetext | selectall'},
       	 			table  : {title : 'Table' , items : 'inserttable tableprops deletetable | cell row column'}
    				},		
	    			toolbar: ["bold italic underline strikethrough removeformat | forecolor backcolor | alignleft aligncenter alignright alignjustify | styleselect | formatselect | fontselect fontsizeselect",
	    			          "cut copy paste | bullist numlist | outdent indent | undo redo | subscript superscript | hr anchor | link unlink image | preview code"],
				statusbar: true,
				branding: false,
				height: editorheight,
				width: editorwidth,
				relative_urls: false,
				remove_script_host: false,
				convert_urls: false,
				document_base_url: "/" + docInfo.NsfName + "/openDocFile/?doc_id=" + docInfo.DocKey,
	    			resize: true,
	    			paste_data_images: true,
	    			browser_spellcheck: true
			});
		});				
	}
}

function saveDocovaRichTextTinyMCE(){		
	$("div[processMceEditor='1']").each ( function () 
	{
		try{
			var id = $(this).attr("id");			
			var html = tinyMCE.get(id).getContent();
			sourceDivName = $(this).attr("name");
			targetField = document.getElementById(sourceDivName);
			$(targetField).val(html);
		}catch(e){}
	});		
}
	
function HideAttachmentTables(){
	var count, tables;	
	//-------------------------------------------------------------------------
	tables = jQuery("#divRTContent").find("TABLE");
	if (tables.length == 0){
		return(0);		
	}
	//-------------------------------------------------------------------------
	count = tables.length;
	if (count <= 0){
		return(0);		
	}
	//-------------------------------------------------------------------------
	for (a = 0; a < count; a++){
		obj = tables[a];
		if ((obj.border == 1) && (obj.cellSpacing == 2) && (obj.cellPadding == 4)){
			obj.style.display = "none";
		}
	}
}


function ExpandHTMLIframes(){
//------------------- set html content iframe display -----------
	iframeBoxes= jQuery("#divRTContent").find("IFRAME");
	if (iframeBoxes.length == 0){
		return(0);		
	}
	
	//-------------------------------------------------------------------------
	count = iframeBoxes.length;
	if (count <= 0){
		return(0);		
	}
//-------------------------------------------------------------------------
	for (a = 0; a < count; a++){
		obj = iframeBoxes[a];
			obj.style.height = obj.document.body.scrollHeight
	}
}


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: convertRTtoHTML
 * If editing a rich text area, makes sure to convert any stored rich text data to valid HTML in the edit field
 * Inputs: srcfieldname - string - database field name where rich text is stored
 *              destfieldname - string - target field name where html is being edited
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function convertRTtoHTML(srcfieldname, destfieldname) {
	if (!docInfo.isNewDoc && docInfo.isDocBeingEdited &&  docInfo.EditorReadOnly !="1") {
		var RTFUrl =  docInfo.DataDocUrl  + "/" + srcfieldname + "?OpenField";
	
		jQuery.ajax({
			type: "GET",
			url: RTFUrl,
			cache: false,
			async: false,
			success: function(data){
  				var fieldlist = document.getElementsByName(destfieldname);
				for(var i=0;i<fieldlist.length;i++){
					if (fieldlist[i].tagName == "TEXTAREA") {
						fieldlist[i].value = data;		
					}
				}
			},
			error: function(){
				//--do nothing
			}
		});
	}
}//--end convertRTtoHTML


function fixRelativeImagePaths(){
  	//check if there are any attachments
 	 if (docInfo.DocAttachmentNames ==null || docInfo.DocAttachmentNames ==""){
	 	 	return;
 	 }
  	var attachList = docInfo.DocAttachmentNames.split("*"); 	 

    jQuery("#divRTContent").find("img").each(function(){
	 	var newSrc= jQuery(this).attr("src");
	 	newSrc = newSrc.replace(/^.*[\\\/]/, '');
		if (attachList.indexOf(newSrc) > -1){
			newSrc = "/" + docInfo.NsfName + "/openDocFile/" + newSrc + '?doc_id=' + docInfo.DocKey;
			jQuery(this).attr("src", newSrc);
		}
    });
}