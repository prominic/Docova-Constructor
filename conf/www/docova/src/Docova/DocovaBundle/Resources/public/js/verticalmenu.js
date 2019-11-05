$(function() {
	var parentFrame = null;
	var outline_html = '';
	var applied = initiateOutlineContainer();

	if (applied === true)
	{
		outline_html = buildVerticalOutline();
		$('#divOutline').html(outline_html);
	}

	//---if the outline is in an iframe, in a page
	if($(window.frameElement).attr("elem") == "outline"){ 
		isInIframe = true;

		//---if the outline is also in the DOCOVA tabbed structure	
		parentFrame = parent.frameElement;
		if($(parentFrame).prop("id") == "appViewMain"){
			isInTabs = true;
		}
	}else if ( window.frameElement){
		parentFrame = window.frameElement;
	}

	$("#divOutline > ul > li").each(function()
	{



		if (outlineJson.menuStyle )
		{
			if ( outlineJson.SubmenuPerspective == "VC" || outlineJson.SubmenuPerspective == "VS" )
			{	
				$(this).css("display", "flex");
				$(this).css("flex-direction", "column");
			}


			var bordertype = outlineJson.menuStyle.border_style;
			var bordercolor = outlineJson.menuStyle.bordercolor;
		}
		if ( bordertype && bordercolor && $(this).attr("isspacer") != "1")
			$(this).css("border-bottom", "1px " + bordertype + " " + bordercolor);
	})

	$(".expandablesys").hide();

	
	var p = $(parentFrame).parents(".grid-stack-item");
	var isinbuilder = false;
	if ( p.length >  0){
		var o = p.get(0).ownerDocument;
		if ( o.location.href.indexOf("AppLayout") > 0){
			isinbuilder = true;
		}
	
	}
	
	if ( ! isinbuilder && (outlineJson.SubmenuPerspective == "VC" || outlineJson.SubmenuPerspective == "VS") && outlineJson.menuStyle.IconMenuDropShadow == 1 )
	{
		
		if ( parseInt(p.find(".grid-stack-item-content").css("left")) == 0 && parseInt(p.find(".grid-stack-item-content").css("right")) == 0 )
		{
			p.css ( "box-shadow", "rgba(30, 31, 33, 0.25) 5px 4px 13px 0px");
			p.css ( "z-index", "1");
		}
	}


	$("#divOutline").css("overflow-y", "auto");
	$('#divOutline').on('click', 'li.header', function(e) {
		$('#divOutline .d-ui-state-active').each ( function () { 
			$(this).removeClass("d-ui-state-active");
			$(this).find(".expandablesys").hide();
		});

		
		$(this).addClass('d-ui-state-active');
		$(this).find(".expandablesys").show();
		var index = $(this).attr('itemindex').split(',');
		var menu = outlineJson.Items;
		for (var x = 0; x < index.length; x++) 
		{
			var menu = menu[index[x]];
		}
		var styleobj = outlineJson.menuStyle;	


		

		window.top.Docova.Utils.generateMenu({
			style :  (outlineJson.SubmenuPerspective == 'VP' || outlineJson.SubmenuPerspective == 'VS' )? 'pane' : 'cascade',
			themed : (outlineJson.Style == 'Themed'),
			width :  250,
			parent : $(this),
			styleobj : styleobj,
			items : menu.Items,
			yadjustment: $(this).outerWidth(),
			sourcewindow : window,
			position : {
				my: "left top",
				at: "right top",
				collision: "none flipfit"
			}
		});

		
	});

	$('#divOutline').on('mouseenter', 'li', function() 
	{
		if ( $(this).attr("isSpacer") == "1" ) return; 
		$(this).addClass('d-ui-state-hover');
		$(this).find(".expandablesys").show();
	});
	$('#divOutline').on('mouseleave', 'li', function() {
		$(this).removeClass('d-ui-state-hover');
		
		
		if ( ! $(this).hasClass("d-ui-state-active"))
			$(this).find(".expandablesys").hide();
	});

	$('li.hinkey_icon > a').on('click', function() {
		
		if ($('ul:first', $(this).parent().next()).is(':visible')) {
			$('ul:first', $(this).parent().next()).hide('slide', {direction: 'left'}, 700);
		}
		else {
			$('ul:first', $(this).parent().next()).show('slide', {direction: 'left'}, 700);
		}
	});
	
	$('#divOutline').on('click', 'li:not(.hinkey_icon)', function(){

		
		OpenMenuItem(this);
	});


	if ( isInIframe ) {
		var iframename = $("#DEName").text();
		var iframealias = $("#DEAlias").text();
	
	
	 	ifrm = $("iframe[outlinename='"+iframename+"']", window.parent.document)
		if ( ifrm.length ==0){
			ifrm =  $("iframe[outlinename=" + iframealias + "]", window.parent.document)
		}

		var presetheight = ($(ifrm).get(0).style.height || "");
		if(presetheight === "" || presetheight === "100%"){
			if(parentFrame){
				var parentframeheight = parseInt($(parentFrame).height(), 10);


				// Calculate the total offset top of given jquery element
	    		function totalOffsetTop($elem) {
	        		return $elem.offsetTop + ($elem.offsetParent ? totalOffsetTop($elem.offsetParent) : 0);
	    		}


				var offsettotal =  parseInt(totalOffsetTop(ifrm.get(0), 10));
				var paddingtop =  $(".grid-stack-item-content", window.parent.document ).css("padding-top");

				var paddingbottom =  $(".grid-stack-item-content", window.parent.document ).css("padding-bottom");

				paddingtop = paddingtop? parseInt(paddingtop) : 0;
				paddingbottom = paddingbottom? parseInt(paddingbottom) : 0;
				
				
				
				var newheight = parentframeheight - offsettotal -  ( paddingtop + paddingbottom) - 40 ;

				if ( $(".grid-stack-item", window.parent.document).length > 1 )
				{
					var parentgrid = $(ifrm).parent().parent().parent();
					
					if ( parentgrid && parentgrid.hasClass("grid-stack-item")){
						parentgrid.height(newheight + ifrm.get(0).offsetTop +2 );
					}
				}
				$(ifrm).attr("origHeight", presetheight);
				$(ifrm).height(newheight);
			}	
		}

		OLHeight = parseInt($(ifrm).height(), 10) - 10;
		$("#divOutline>ul:first").css("height", OLHeight);
		
	}else{
	     	//Calc height of containing div to show
		OLHeight = parseInt($("#divOutline>ul:first").css("padding-top")) + parseInt($("#divOutline>ul:first").css("padding-bottom")) + 20;
	
	
		$("#divOutline>ul:first").css("height", $(window).height()-OLHeight)
		$(window).resize(function(){
			if ( ! isInIframe ) $("#divOutline>ul:first").css("height", $(window).height()-OLHeight)
    		
		});
	} 
	
});

function CTopLeft(i_nTop, i_nLeft) {
   this.nTop = i_nTop;
   this.nLeft = i_nLeft;
}

function GetTopLeftFromIframe(i_oElem) {
   var cTL = new CTopLeft(0, 0);
   var oElem = i_oElem;
   var oWindow = window;

   do {
      cTL.nLeft += oElem.offsetLeft;
      cTL.nTop += oElem.offsetTop;
      oElem = oElem.offsetParent;

      if (oElem == null) { // If we reach top of the ancestor hierarchy
         oElem = oWindow.frameElement; // Jump to IFRAME Element hosting the document
         if ( oElem && oElem.parentElement.tagName.toLowerCase() == "frameset"){
         	cTL.nLeft += oElem.parentElement.offsetLeft;
      		cTL.nTop += oElem.parentElement.offsetTop;
      	}
         oWindow = oWindow.parent;   // and switching current window to 1 level up
      }
   } while (oElem)
  
   return cTL;
}

function buildVerticalOutline(submenu, parentIndex)
{
	var html = '',
		menu_items = !submenu || typeof submenu == typeof undefined ? outlineJson.Items : submenu;

	parentIndex = !parentIndex || typeof parentIndex == typeof undefiend ? '' : parentIndex + ',';

	html = '<ul class="OutlineItems">';

	var liclassheader = "d-ui-widget-header";
	var liclass = "d-ui-widget-header";

	for (var item in menu_items)
	{
		html += '<li class="' + (menu_items[item].type == 'header' || (menu_items[item].items && menu_items[item].items.length) ? 'header  ' + liclassheader :  liclass) + '" ';
		if (menu_items[item].type == 'header') {
			html += 'itemindex="' + parentIndex + item + '" ';
		}
		html += 'emenuitemtype="' + (menu_items[item].type == 'header' ? 'H' : 'M') + '" ';
		html += 'etarget="' + menu_items[item].etarget + '" ';
		html += 'eelement="' + menu_items[item].eelement + '" ';
		html += 'isSpacer="' + menu_items[item].isSpacer + '" ';
		html += 'eNoTab="' + menu_items[item].enotab +'" ';
		html += 'title="' + (menu_items[item].helptext ? menu_items[item].helptext  : '' )  + '" ';
		html += 'etype="' + menu_items[item].etype + '" ';
		html += 'eviewtype="' + menu_items[item].eviewtype + '">';

		

		if ( (outlineJson.SubmenuPerspective == 'VS' || outlineJson.SubmenuPerspective == 'VC' ) && ! menu_items[item].isSpacer  ){
			//icon only menus

			html += "<div class='iconwrapper'>";


			html += '<span class="';
				
				html += ' expandable ' + menu_items[item].expandicon;
				html += '" style="';
				
				html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
				html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
				html += menu_items[item].iconplacement ? 'float:' + menu_items[item].iconplacement + '; ' : '';
				html += '"></span>'
		}else{
			if (menu_items[item].icontitle && menu_items[item].type != 'header' && ! menu_items[item].isSpacer)
			{
				html += '<span class="';
				html += menu_items[item].icontitle ? 'nicon  fa-1x ' + menu_items[item].icontitle + '" ' : '" ';
				html += 'style="left: 7px; ';
				html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
				html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
				html += '" ></span>';
			}
			else if (menu_items[item].type == 'header' )
			{
				
				var cls = " expandable ";
				
				if ( ! outlineJson.SubmenuDetector )
				{
					cls += " expandablesys ";
				}

				html += '<span class="';
				
				html += cls + menu_items[item].expandicon;
				html += '" style="';
				
				if ( menu_items[item].customicon === "1" && ! menu_items[item].isSpacer )
				{
					html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
					html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
					html += menu_items[item].iconplacement ? 'float:' + menu_items[item].iconplacement + '; ' : '';
				}
				html += '"></span>'
				
			}
		}
		html += '<span class="itemlabel" style="';
		html += menu_items[item].size ? 'font-size:' + menu_items[item].size + '; ' : '';
		html += menu_items[item].isbold ? 'font-weight: bold; ' : '';
		html += menu_items[item].isitalic ? 'font-style: italic; ' : '';
		html += menu_items[item].fontcolor ? 'color: ' + menu_items[item].fontcolor + ';' : '';
		html += '">' + menu_items[item].context + '</span>';

		if ( outlineJson.SubmenuPerspective == 'VS' || outlineJson.SubmenuPerspective == 'VC' ){
			html += "</div>"
		}

		html += '</li>';
	}
/*
	if (!parentIndex && outlineJson.HinkeyMenu === true)
	{
		html += '</ul>';
	}
*/
	html += '</ul>';
	return html;
}