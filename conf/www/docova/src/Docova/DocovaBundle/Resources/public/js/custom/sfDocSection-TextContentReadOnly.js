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
	iframeBoxes= jQuery("IFRAME").get();
	if (iframeBoxes == null){
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

function ModifyLinkTargets(){
//------------------- Modify any a href links in the content so links open in their own window ----------
	var find = new RegExp("<a","gi");
	var source = $('#divRTContent').html();
	var modifiedHTML = source.replace(find, "<a target='_blank' ");
	$('#divRTContent').html(modifiedHTML);
}

//---------------------- fixes inline/embeded img src path  and attachments links -----------------
function fixInlineSrcPath()
{
	//check if the user mail setting is outlook and the message was imported from Outlook
	if((docInfo.UserMailSystem =='O') && (docInfo.UserMailImport=='O') )
	{
		var imgs = document.getElementById("divRTContent").getElementsByTagName("img");
		var divLinks = document.getElementById("outlookMsgAttachments");
	 	 var imgsCount=imgs.length
	 	 //check if there are any attachments
	 	 if (docInfo.DocAttachmentNames ==null || docInfo.DocAttachmentNames =="")
	 	 {
	 	 	return;
	 	 }
	  	var attachList = docInfo.DocAttachmentNames.split("*");
	 	var outlookAttachments = new Array();
	 	var outookEmdAttachments = new Array();
		for (var i = 0;i < imgsCount; i++) 
		{
	 		var newSrc= imgs[i].nameProp;

			if (attachList.exists(newSrc))
			{
				imgs[i].src='../openDocFile/'+ newSrc + '?OpenElement&doc_id=' + docInfo.DocID;
				outookEmdAttachments.push(newSrc);
			}
		}

		//check for regular attachments
		var emdAttLength = outookEmdAttachments.length;
		for( var x=0;x<emdAttLength;x++)
		{
			attachList.remove(outookEmdAttachments[x])
		}
		
		//check all the embeded links
		var links = document.getElementById("divRTContent").getElementsByTagName("link");
		for ( var t=0; t<links.length; t++)
		{
			attachList.remove(links[t].href );
		}
		
		//text to display with attachments
		var attachLinks="Attachments: &nbsp;";
		var attachSize=attachList.length;

		// if there is any embeded file exlude them from attachments
		for(var y=0;y<attachSize;y++)
		{
			//create an image tag for attachment
			var imgStr =returnImageIconForAttachment(attachList[y]);
			var attachSizes = docInfo.OutlookAttachmentsSize.split(",");
			var attNameSize = formatAttachmentNameSize(attachSizes,attachList[y]);
			attachLinks += '<a style=\"text-decoration: none\" target=\"_blank\" href=\"'+'../openDocFile/' + attachList[y] + '?OpenElement&doc_id=' + docInfo.DocID + '\">' + imgStr + attNameSize+ '</a>&nbsp;&nbsp';
		}
		divLinks.innerHTML =attachLinks;
		//if there are regular attachment then show the attachment links with icons
		if (attachSize > 0)
		{
			divLinks.style.display="block";
		}
}
 
//------------------- format Name and size for each regular attachment	 ---------------
function formatAttachmentNameSize(arrayAttachSizes, attachmentFileName)
{
	var attachmentLength = arrayAttachSizes.length;
	var strRetFileName=attachmentFileName;
	for (var a=0; a<attachmentLength;a++)
	{
		var tempArray = arrayAttachSizes[a].split("*");
		if (tempArray.exists(attachmentFileName))
		{
			strRetFileName=attachmentFileName+ " (" + tempArray[1] + ")";
		}
	}
	return strRetFileName;
}
//--------------- Creates icon for each recognized attachments -----------------
function returnImageIconForAttachment(attFileName)
{
	var imgTag='';
	var dbLibPath = docInfo.ImagesPath;
	if (attFileName !=null && attFileName !="")
	{
		//PDF
		if (attFileName.indexOf('.pdf') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'pdf.gif" />'
		}
		//Word Doc
		else if (attFileName.indexOf('.doc') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'doc.gif" />'
		}
		//Excel
		else if (attFileName.indexOf('.xls') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'excel.gif" />'
		}
		//ZIP
		else if (attFileName.indexOf('.zip') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'zip.gif" />'
		}
		//HTML
		else if (attFileName.indexOf('.html') !=-1 || attFileName.indexOf('.htm') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'html.gif" />'
		}
		//powerpoint
		else if (attFileName.indexOf('.ppt') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'powerpoint.gif" />'
		}
		// text file
		else if (attFileName.indexOf('.txt') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'text.gif" />'
		}
		//xml
		else if (attFileName.indexOf('.xml') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'xml.gif" />'
		}
		//Gif
		else if (attFileName.indexOf('.gif') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'gif.gif" />'
		}
		//JPEG
		else if (attFileName.indexOf('.jpg') !=-1 || attFileName.indexOf('.jpeg') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'jpeg.gif" />'
		}
		//bmp
		else if (attFileName.indexOf('.bmp') !=-1)
		{
			imgTag+='<img border="0" src="'+dbLibPath+'bmp.gif" />'
		}
		//Unknown fiel type
		else 
		{
			imgTag+='<img border="0" src="'+dbLibPath+'unkown.gif" />'
		}
	}
	return imgTag;
 }
}

//------------ 	Removes array element  ---------------
Array.prototype.remove=function(s){
  for(i=0;i<this .length;i++){
    if(s==this[i]) this.splice(i, 1);
  }
}
//------------ Checks if string exists in an array  ------------------
Array.prototype.exists = function (strCheck) {
    for (var i = 0; i < this.length; i++) 
   {
        if (this[i] == strCheck) return true;
    }
    return false;
}
//--------------- fixes Iframe for printing -------
function  fixMemoIframes()
{
	if (docInfo.DocumentTypeKey=="MAILMEMO")
	{
		var rtContent = document.getElementById("divRTContent");
		var iFrame = rtContent.getElementsByTagName('iframe');
		try
		{
			if( iFrame.length > 0)
			{
				var strURL=iFrame[0].src;
				var httpObj=null;
				var httpObj = new ActiveXObject ("Microsoft.XMLHTTP"); 
				httpObj.open( "GET", strURL,false)
				httpObj.Send(null);
				 var strHTML = new String();
				 strHTML =httpObj.ResponseText;
			 	 iFrame[0].style.display="none";
			 	 if( httpObj.readyState ==4)
					 rtContent.innerHTML=strHTML + rtContent.innerHTML;
				 httpObj=null;
			  }
				  return;
		  }
		  catch(e)
		  {
		  	alert("[ERROR -fixMemoIframes()]: " + e.message);
		  }
	  }
}

//---------------------- fixes inline/embeded img src path  and attachments links -----------------
function fixInlineSrcPathNotes()
{
	    var searchdocid = "/" + docInfo.DocID + "/";
		var imgs = document.getElementById("divRTContent").getElementsByTagName("img");
	 	var imgsCount=imgs.length

		var jsonImagesToMove = {};
		var jsonImagesToFix = {};
		for (var i = 0;i < imgsCount; i++) 
		{
			var imagename = imgs[i].src;
			if (imagename.slice(0,4).toLowerCase() == "cid:"){
				if (! imgs[i].complete) {
					var pos = imagename.indexOf("@");
					if (pos > -1){
						imagename = imagename.slice(4, pos);
						jsonImagesToFix[imagename.toLowerCase()] = {"domelem" : imgs[i]};
					}
				}	
			} else {	
				if ( imagename.toLowerCase().indexOf(searchdocid.toLowerCase()) > -1) {
					var pos = imagename.toLowerCase().indexOf("!openelement");
					if (pos > -1){
						imagename = imagename.slice(0, pos);
						var pos = imagename.lastIndexOf("/");
						if (pos > -1){
							imagename = imagename.slice(pos + 1);
							jsonImagesToMove[imagename.toLowerCase()] = {"domelem" : imgs[i]};
						}
					}					
				}
			}
		}
		
	for (fiximage in jsonImagesToFix){
		var imgtofix = jsonImagesToFix[fiximage].domelem;
		if(jsonImagesToMove[fiximage]){
			var imgtomove = jsonImagesToMove[fiximage].domelem;
			if (imgtomove != undefined){
				imgtofix.src = imgtomove.src;
				(imgtomove.parentNode).removeChild(imgtomove);
			}
		}
	}
}
