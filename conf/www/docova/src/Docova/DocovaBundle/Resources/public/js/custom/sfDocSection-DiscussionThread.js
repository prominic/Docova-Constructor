function LoadDiscussionThread()
{
	var oXsl = null;
	var oXml = null;
	
	var xmlBaseUrl = "/" + NsfName + "/discussionbyparent.xml?OpenView&RestricttoCategory=" + docInfo.ThreadKey;
	var xslBaseUrl = "/" + NsfName +  "/discussionthread.xsl?openpage";
	var processor = new XSLTProcessor();
	
//Get the discussion thread xml	
	jQuery.ajax({
		type: "GET",
		url: xmlBaseUrl,
		cache: false,
		async: false,
		dataType: "xml", //"xml" returns the xml DOM object.  "text" returns the xml text for which you would then need to parse to an xml DOM with DOMParser
		success: function(xml){
			oXml = xml
		},
		error: function(){
			alert("Error: Could not retrieve the discussion XML");
		}
	})
	
//Get the discussion thread xsl stylesheet xml
	jQuery.ajax({
		type: "GET",
		url: xslBaseUrl,
		cache: false,
		async: false,
		dataType: "xml", //"xml" returns the xml DOM object.  "text" returns the xml text for which you would then need to parse to an xml DOM with DOMParser
		success: function(xml){
			processor.importStylesheet(xml);
		},
		error: function(){
			alert("Error:  Could not retrieve the XSL stylesheet XML");
		}
	})

	var discThread = processor.transformToFragment(oXml, document);	
	$("#divThreadContent").html("");
	$("#divThreadContent").append(discThread);
	$("#divThreadContainer").css("display", "");
}


function OpenTopic(obj)
{
if(obj)
	{
		var topicUrl = "/" + NsfName +  "/OpenTopic/" + obj.id;
		topicUrl += (docInfo.Mode)? "?mode=" + docInfo.Mode : "";
		location.href = topicUrl;
	}
}

function HighlightTopic(obj)
{
	var imgsrc = $(obj).find("IMG").prop("src")
	var newimgsrc = imgsrc.replace("discchatbw", "discchat")
	$(obj).find("IMG").prop("src", newimgsrc)
	$(obj).prop("className", "discitemhighlight")
}

function NormalTopic(obj)
{
	var imgsrc = $(obj).find("IMG").prop("src")
	var newimgsrc = imgsrc.replace("discchat", "discchatbw")
	$(obj).find("IMG").prop("src", newimgsrc)
	$(obj).prop("className", "discitem")
}