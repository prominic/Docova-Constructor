var resizeTimer = null;

$(function() {
	var parentFrame = null;
	var outline_html = '';
	var expicon = 'fas fa-caret-down';
	var colicon = 'fas fa-caret-right'
	var applied = initiateOutlineContainer();

	if (applied === true)
	{
		outline_html = buildBasicOutline();
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
	}

	
	
	
	if ( docInfo.Expand == "all" ) {
		//---Now expand any ULs where sibling LI has eInitiallyExpanded
    	$("li").each(function(){
    			var tmpcolicon = $(this).attr("expicon") &&  $(this).attr("expicon") != "" ?  $(this).attr("expicon") : expicon;
    			
				if ( $(this).next("ul").length > 0 ) 
				{
					$(this).next("ul").css("display", ""); //shows LI's next UL
	    			$(this).find("span").removeClass().addClass("expandable " + tmpcolicon + " fa-1x"); //sets icon to expanded icon			
				}
    	});
	}	

	$(".OutlineItems li").hover(function(){
		if ( $(this).attr("emenuitemtype") == "H" ) return;
		if ( $(this).attr("isSpacer") == "1" ) return;
		$("d-ui-state-hover").removeClass("d-ui-state-hover");
    	$(this).addClass("d-ui-state-hover");
	}, function(){
		$(this).removeClass("d-ui-state-hover");
	});

	$(".OutlineItems li[eMenuItemType='M']").click(function(){
		if ( $(this).attr("isSpacer") == "1" ) return;
		$(".OutlineItems li[eMenuItemType='M']").removeClass("d-ui-state-active").addClass(liclass);
		$(".OutlineItems li[eMenuItemType='H']").removeClass("d-ui-state-active").addClass(liheaderclass);
		$(this).addClass("d-ui-state-active");
		OpenMenuItem(this);
	});
	
	$(".OutlineItems li[eMenuItemType='H']").click(function(event){
		var etype = $(this).attr("etype");
		var eelement = $(this).attr("eelement");
		if(etype !== null && etype !== "" && eelement !== null && eelement !== ""){
			$(".OutlineItems li[eMenuItemType='M']").removeClass("d-ui-state-active").addClass(liclass);
			$(".OutlineItems li[eMenuItemType='H']").removeClass("d-ui-state-active").addClass(liheaderclass);
			$(this).addClass("d-ui-state-active");
			OpenMenuItem(this);			
		}else{
			event.stopPropagation();
    			ToggleMenuItem(this);
		}
    });
	
	//find menu item with initially selected attribute and select it
	$('.OutlineItems li[eInitiallySelected="1"]').removeClass(liclass).addClass("d-ui-state-active");

	$(".OutlineItems li[eMenuItemType='H']").dblclick(function(){
		event.stopPropagation();
    		ToggleMenuItem(this);
	});

	$("[isspacer='1'").each ( function () {
	
		$(this).css("border-bottom",  "0px")
	})


	if ( isInIframe ) {
		updateIframeHeight(parentFrame);
		$(window).on('resize', function(e) {

 			 clearTimeout(resizeTimer);
  			 resizeTimer = setTimeout(function() {
  			 		updateIframeHeight(parentFrame);
  			}, 250);

		});
	} 

	
   
});

function updateIframeHeight(parentFrame)
{
	var iframename = $("#DEName").text();
	var iframealias = $("#DEAlias").text();

 	ifrm = $("iframe[outlinename='"+iframename+"']", window.parent.document)
	if ( ifrm.length ==0){
		ifrm =  $("iframe[outlinename=" + iframealias + "]", window.parent.document)
	}

	var presetheight = ($(ifrm).attr("origHeight") ? $(ifrm).attr("origHeight") : ($(ifrm).get(0).style.height || ""));
	if(presetheight === "" || presetheight === "100%"){
		if(parentFrame){
			var parentframeheight = parseInt($(parentFrame).height(), 10);

			// Calculate the total offset top of given jquery element
    		function totalOffsetTop($elem) {
        		return $elem.offsetTop + ($elem.offsetParent ? totalOffsetTop($elem.offsetParent) : 0);
    		}


			var offsettotal = parseInt(totalOffsetTop(ifrm.get(0), 10));
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
	$(".OutlineItems:first").css("height", OLHeight);	

	
	//if only one panel, then grow it to the size of the content.
	if ( $(".grid-stack-item", window.parent.document).length == 1 )
	{

		$('.grid-stack', window.parent.document).css("height", 'auto');
 
  		$('.grid-stack-item-content', window.parent.document).css("position", "relative");
  		$('.grid-stack-item', window.parent.document).css("position", "relative")
  		$('.grid-stack-item', window.parent.document).css("height", "100%");




	}

}


function buildBasicOutline(submenu, collapsed, iteration)
{
	var liclass = 'd-ui-state-default';
	var liheaderclass = 'd-ui-widget-header';
	


	var menu_items = !submenu || typeof submenu == typeof undefined ? outlineJson.Items : submenu;

	var cls = "OutlineItems";

	if ( typeof submenu !== typeof undefined){
		cls += " " + liclass + " ";

	}else{
		liclass= liheaderclass;
	}

	
	

	html = '<ul class="' + cls + '" ';


	html += 'style="';


	if (collapsed === false) {
		html += 'display:none;';
	}
	
	html += '"';

	
	html += '>';

	if ( typeof iteration == typeof undefined)
		iteration = 0

	
	for (var item = 0; item < menu_items.length; item++)
	{
		if ( iteration > 0  )
			liclass = "";

		html += '<li class="' + (menu_items[item].type == 'header' ? liheaderclass : liclass) + '" ';
		html += 'emenuitemtype="' + (menu_items[item].type == 'header' ? 'H' : 'M') + '" ';
		html += 'etarget="' + menu_items[item].etarget + '" ';
		html += 'eelement="' + safe_quotes_js(menu_items[item].eelement)+ '" ';
		html += 'isSpacer="' + menu_items[item].isSpacer + '" ';
		html += 'etype="' + menu_items[item].etype + '" ';
		html += 'eviewtype="' + menu_items[item].eviewtype + '" ';
		html += 'expicon="' + (menu_items[item].expandicon ? menu_items[item].expandicon  : '' )  + '" ';
		html += 'title="' + (menu_items[item].helptext ? menu_items[item].helptext  : '' )  + '" ';
		html += 'colicon="' + (menu_items[item].collapseicon ? menu_items[item].collapseicon : '' ) + '" ';
		html += 'eNoTab="' + menu_items[item].enotab +'" ';
		html += 'einitiallyselected="' + (menu_items[item].initselected == true || menu_items[item].initselected == "1" ? "1" : "0") + '" ';
		html += 'einitiallyexpanded="' + (menu_items[item].initexpand == true || menu_items[item].initexpand == "1" ? "1" : "0") + '" ';
		html += 'style = "';
		if ( iteration > 0)
			html +=  ' padding-left : ' + (1.5 * iteration ) + 'em ';
		
		html += '">';
	
		
		html += '<span class="itm_label ';
		if ( menu_items[item].type == 'header' )
		{
			
			if ( menu_items[item].initexpand === false ){
				html += '  expandable  fa-1x ' + menu_items[item].collapseicon;
			}else{
				html += ' expandable fa-1x ' + menu_items[item].expandicon;
			}

			if ( menu_items[item].customicon === "1"){
				html += '" style="';
				html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
				html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
				html += menu_items[item].iconplacement ? 'float:' + menu_items[item].iconplacement + '; ' : '';
			}
		}else{
			
			html += menu_items[item].icontitle ? ' fa-1x ' + menu_items[item].icontitle + '" ' : '" ';
			html += 'style="';
			html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
			html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
		}


		
		
		html += '" ></span>';
		
		html += '<div class="itemlabel" style="';
		
		html += menu_items[item].size ? 'font-size:' + menu_items[item].size + '; ' : '';
		html += menu_items[item].isbold ? 'font-weight: bold; ' : '';
		html += menu_items[item].isitalic ? 'font-style: italic; ' : '';
		html += menu_items[item].fontcolor ? 'color: ' + menu_items[item].fontcolor + ';' : '';
		html += '">' + menu_items[item].context + '</div></li>';
		iteration ++;
		if (menu_items[item].Items && menu_items[item].Items.length) {
			html += buildBasicOutline(menu_items[item].Items, menu_items[item].initexpand, iteration);
			
		}
		iteration --;
	}
	html += '</ul>';
	return html;
}