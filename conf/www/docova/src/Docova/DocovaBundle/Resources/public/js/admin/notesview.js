///////////////////////////  View Column Object /////////////////////////// 
function ObjViewColumn()
{
this.isCategorized=false; //true if this is a category column
this.hasCustomSort=true; //true or false - enables/disables custom sort
this.totalType="0"; //"0" - none, "1" - sum, "2" - count, "3" - average 
this.parentObj=null; //parent view object
this.colIdx=null; //index of the current column
this.isFiltered=false; //used to show column is being filtered
this.isFrozen=false; //used to freeze column display
this.isFreezeControl=false; //used to freeze column display
this.showResponses =false;
this.responseFormula = "";
this.title=""; //column heading title
this.showMultiAsSeparate = false; //show multiple values as separate entries


this.xmlNodeName="" //xml node name (Fxx) corresponding to the requested field name
this.dataType="text"; // text/number/date/icon/href - used for constructing data cell html and sorting keys
this.sortOrder="none"; // none, ascending or descending
this.customSortOrder="none"; // none, ascending or descending
this.numberFormat="###.##;-###.##"; // number format string
this.numberPrefix="";  //prefix to insert before numbers
this.numberSuffix="";  //suffix to append after numbers
this.numberBlankFormat=""; //whether to display blank numbers as zero
this.dateFormat=""; // date format string
this.isHidden = "0";  //whether column is hidden
// text/cell appearance - overwrites the class settings
this.width=""; //width in pixels
this.align=""; //left,right, center
this.fontSize=""; 
this.fontFamily="";
this.color=""; // color string (CSS atribute value)
this.fontWeight=""; //bold or nothing (CSS atribute value)
this.fontStyle=""; //italic or nothing (CSS atribute value)
this.textDecoration=""; //uderline, italic etc (CSS atribute value)
this.backgroundColor=""; //cell background color
//custom total cell apparance - overwrites the class settings
this.alignT=""; //left,right, center
this.fontSizeT=""; 
this.fontFamilyT="";
this.colorT="#0000ff"; // color string (CSS atribute value)
this.fontWeightT="bold"; //bold or nothing (CSS atribute value)
this.fontStyleT=""; //italic or nothing (CSS atribute value)
this.textDecorationT=""; //uderline, etc (CSS atribute value)
this.backgroundColorT=""; //cell background color
//custom header cell apparance - overwrites the class settings
this.alignH=""; //left,right, center
this.fontSizeH=""; 
this.fontFamilyH="";
this.colorH=""; // color string (CSS atribute value)
this.fontWeightH=""; //bold or nothing (CSS atribute value)
this.fontStyleH=""; //italic or nothing (CSS atribute value)
this.textDecorationH=""; //uderline, italic etc (CSS atribute value)
this.backgroundColorH=""; //cell background color
this.columnFormula = "";
//-------------------- methods  --------------------


//==========================================================================================
//builds the xml column parameter string defining all column properites
//==========================================================================================

this.GetColumnParams = function()
{
var returnString="";

	returnString += "<column>";
			returnString += (this.isCategorized)? "<isCategorized>1</isCategorized>" : "<isCategorized/>";
			returnString += (this.showResponses)? "<showResponses>1</showResponses>" : "<showResponses/>";
			returnString += (this.showMultiAsSeparate)?  "<showMultiAsSeparate>1</showMultiAsSeparate>" : "<showMultiAsSeparate/>";
			returnString += (this.hasCustomSort) ? "<hasCustomSort>1</hasCustomSort>" : "<hasCustomSort/>";
			returnString += "<totalType>" + this.totalType + "</totalType>" ;
			returnString += (this.isFrozen) ? "<isFrozen>1</isFrozen>" : "<isFrozen/>";
			returnString += (this.isFreezeControl) ? "<isFreezeControl>1</isFreezeControl>" : "<isFreezeControl/>";
			returnString += "<title>" + this.title + "</title>";
			returnString += "<xmlNodeName>" + this.xmlNodeName + "</xmlNodeName>";
			returnString += "<dataType>" + this.dataType + "</dataType>";
			returnString += "<responseFormula><![CDATA[" + this.responseFormula + "]]></responseFormula>";
			returnString += "<isHidden>"+ (this.isHidden ? this.isHidden : "") + "</isHidden>";
			
			returnString += "<sortOrder>" + this.sortOrder + "</sortOrder>";
			var LexerObj = new Lexer(); 
			if (LexerObj && typeof LexerObj != typeof undefined) {
				var twigFormula = $.trim(this.columnFormula);
				var outputTxt = LexerObj.convertCode(twigFormula, "TWIG");	
				twigFormula = '{% docovascript "variable:array" %}' + outputTxt.replace(/[\n\r]+/g, '')  + "{% enddocovascript %}";
				returnString += "<translatedFormula><![CDATA[" + twigFormula + "]]></translatedFormula>";
			}
			else {
				returnString += "<translatedFormula><![CDATA[" + this.columnFormula + "]]></translatedFormula>";
			}
			returnString += "<columnFormula><![CDATA[" + this.columnFormula + "]]></columnFormula>";
			returnString += "<customSortOrder>" + this.customSortOrder + "</customSortOrder>";
			returnString += "<numberFormat>" + this.numberFormat + "</numberFormat>";
			returnString += "<numberPrefix>" + this.numberPrefix + "</numberPrefix>";
			returnString += "<numberSuffix>" + this.numberSuffix + "</numberSuffix>";
			returnString += "<numberBlankFormat>" + (this.numberBlankFormat ? this.numberBlankFormat : "") + "</numberBlankFormat>";
			returnString += "<dateFormat>" + (this.dateFormat ? this.dateFormat : "")+ "</dateFormat>";
			returnString += "<width>" + this.width + "</width>";
			returnString += "<align>" + this.align + "</align>";
			returnString += "<fontSize>" + this.fontSize + "</fontSize>"; 
			returnString += "<fontFamily>" + this.fontFamily + "</fontFamily>" ;
			returnString += "<color>" + this.color + "</color>";
			returnString += "<fontWeight>" + (this.fontWeight ? this.fontWeight : "") + "</fontWeight>";
			returnString += "<fontStyle>" + (this.fontStyle ? this.fontStyle : "") + "</fontStyle>";
			returnString += "<textDecoration>" + (this.textDecoration ? this.textDecoration : "") + "</textDecoration>";
			returnString += "<backgroundColor>" + this.backgroundColor + "</backgroundColor>";

			returnString += "<alignT>" + this.alignT + "</alignT>";
			returnString += "<fontSizeT>" + this.fontSizeT + "</fontSizeT>"; 
			returnString += "<fontFamilyT>" + this.fontFamilyT + "</fontFamilyT>" ;
			returnString += "<colorT>" + this.colorT + "</colorT>";
			returnString += "<fontWeightT>" + this.fontWeightT + "</fontWeightT>";
			returnString += "<fontStyleT>" + (this.fontStyleT ? this.fontStyleT : "") + "</fontStyleT>";
			returnString += "<textDecorationT>" + (this.textDecorationT ? this.textDecorationT : "") + "</textDecorationT>";
			returnString += "<backgroundColorT>" + this.backgroundColorT + "</backgroundColorT>";
			
			returnString += "<alignH>" + this.alignH + "</alignH>";
			returnString += "<fontSizeH>" + this.fontSizeH + "</fontSizeH>"; 
			returnString += "<fontFamilyH>" + this.fontFamilyH + "</fontFamilyH>" ;
			returnString += "<colorH>" + this.colorH + "</colorH>";
			returnString += "<fontWeightH>" + (this.fontWeightH ? this.fontWeightH : "") + "</fontWeightH>";
			returnString += "<fontStyleH>" + (this.fontStyleH ? this.fontStyleH : "") + "</fontStyleH>";
			returnString += "<textDecorationH>" + (this.textDecorationH ? this.textDecorationH : "") + "</textDecorationH>";
			returnString += "<backgroundColorH>" + this.backgroundColorH + "</backgroundColorH>";			
	returnString += "</column>"

return returnString ;
}

//==========================================================================================
//sets the xml column parameters from xml
//==========================================================================================

this.SetColumnParams = function(paramNodes)
{

	if(!paramNodes) {return false;}
	var evalString="";
	
	for(var k=0; k< paramNodes.length; k++)
	{
		node = paramNodes.item(k);
		if(node !=null && node.nodeName != "#text")
		{
			if ( node.nodeName != "responseFormula" && node.nodeName != "columnFormula"){
				evalString = "this." + node.nodeName;
				evalString += "='";
				var nodeval = (node.text)? node.text : ((node.textContent) ? node.textContent : "");
				//nodeval = nodeval.replace(/"/g, '\\"');
				//alert ( nodeval);
				evalString += nodeval
				evalString += "';";
				
				eval(evalString);
			}else if ( node.nodeName == "responseFormula") {
				var nodeval = (node.text)? node.text : ((node.textContent) ? node.textContent : "");
				this.responseFormula = nodeval;
				
			}else{
				var nodeval = (node.text)? node.text : ((node.textContent) ? node.textContent : "");
				this.columnFormula = nodeval;
				
			}
		}
	}

}

//==========================================================================================
// creates html for a column header cell to be used inside xsl
//==========================================================================================

this.GetHeaderCellHTML = function(specialStyle)
{
	//var sortOrder = (this.customSortOrder != "none") ? this.customSortOrder : this.sortOrder ; //this would display default sort in grey
	var sortOrder = (this.customSortOrder != "none") ? this.customSortOrder : "none" ;
	var sortType = (this.customSortOrder != "none") ? "custom" : "default" ;
	var sortIcon = "sort-" +  sortOrder.substr(0, 1) + "-" + sortType + ".gif";
	var htmlString = "";
	
	var isColFrozen = (this.isFrozen && !this.parentObj.disableFreeze)? true : false;
	if(isColFrozen && this.isFiltered)
	{
		htmlString += '<td class="listheaderfrfltr"'
	}
	else if (isColFrozen && !this.isFiltered)
	{
		htmlString += '<td class="listheaderfr"'
	}
	else if (!isColFrozen && this.isFiltered)
	{
		htmlString += '<td class="listheaderfltr"'
	}
	else //!isColFrozen && !this.isFiltered
	{
		htmlString += '<td class="listheader"'
	}
	
	htmlString += ' colIdx="' + this.colIdx + '" hasProperties="true"';
	if(this.alignH != "") {htmlString += ' align="' +  this.alignH + '"' ;}
	if(this.isHidden == "1" && !this.parentObj.buildMode){
		htmlString += ' width="0"';
	}else if(this.width >= 0){
		htmlString += ' width="' +  this.width + '"';
	}
	//----- apply custom style settings
	htmlString += ' style="' + ((this.isHidden == "1" && !this.parentObj.buildMode) ? "display:none;":"") + this.GetCustomStyleString("H", specialStyle) + '"';
	htmlString += '>';

	htmlString += (this.title)? this.title : '&#160;';

	//-------- if column can be custom sorted, display the sort selector icon next to the heading text ------------------
	if(this.hasCustomSort)
		{	
			var sortImgStyle = (this.title =="") ? 'margin: 2px 0px 2px 0px; border:0px;' : 'margin: 2px 0px 2px 10px; border:0px; ';
			htmlString += '<img class="listsorticon" id="listsorticon-' + this.colIdx + '" style="' + sortImgStyle + '" src="' + this.parentObj.imgPath ;
			htmlString += sortIcon + '?OpenImageResource" align="top"/>';
		}
		if(this.isFreezeControl && !this.parentObj.disableFreeze)
		{	
			var freezeImgStyle = 'margin: 2px 0px 2px 6px; border:0px;';
			htmlString += '<img class="listpinicon" id="listpinicon-' + this.colIdx + '" style="' + sortImgStyle + '" src="' + this.parentObj.imgPath ;
			htmlString += 'pincolumn.gif?OpenImageResource" align="top"/>';
		}
	htmlString += '</td>';

	return htmlString;
}

//==========================================================================================
// creates html for a data cell to be used inside xsl style sheet
//==========================================================================================

this.GetDataCellHTML = function(specialStyle, showFTScore, isResponseColumn, colspan)
{
		var htmlString = (this.isFrozen && !this.parentObj.disableFreeze)? '<td class="listitemfr"' : '<td class="listitem"';
		
		if(showFTScore)	{
			specialStyle += "border-left: solid 10px rgb({$ftScoreColor},{$ftScoreColor},{$ftScoreColor});"
		}
		
		if(this.align != "") {htmlString += ' align="' +  this.align + '"' ;}
		if(this.isHidden == "1" && !this.parentObj.buildMode){
			htmlString += ' width="0"';
		}else if(this.width > 0){
			htmlString += ' width="' +  this.width + '"';
		}		
		//----- apply custom style settings if any ----
		htmlString += ' style="' + ((this.isHidden == "1" && !this.parentObj.buildMode) ? "display:none;":"") + this.GetCustomStyleString("", specialStyle) + '"';
		
		if (isResponseColumn) {
			htmlString += ' showResponses="1" isCategoryHead="true"';
			}
		
		if ( colspan && colspan != "")
			htmlString += ' colspan="' + colspan + '"';
		
		htmlString += '>';
		
		
		
		if ( isResponseColumn){
				htmlString += '<xsl:if test="name()=\'respdoc\' and count(ancestor::*) &gt; 1">'
				htmlString += '<span style="padding-left:{count(ancestor::*) * 10}px"/>'
				htmlString += "</xsl:if>";
				htmlString += '<xsl:if test="count(responses) &gt; 0">'
				htmlString +=  '<span>' 
				htmlString += '<img class="listexpandericon" style="margin: 2px 2px 2px 0px; border:0px;" src="' + this.parentObj.imgPath + 'cat-collapse.gif?OpenImageResource" align="top"/>';
				htmlString += '</span></xsl:if>'
				
		}
		
			
		if(this.isCategorized && !this.parentObj.isFTSearch) //categorized columns do not display the cell value in the detail cells
		{
			htmlString += '&#160;'; 
		}
		else
		{
			//--- cell displays data differently based on the selected field data type ---------
			if(this.dataType=="icon") // data is the image tag source
			{
				htmlString += '<xsl:choose>';
					htmlString += '<xsl:when test="string-length(' + this.xmlNodeName + '/text())=0 or ' + this.xmlNodeName + '/text()=\'\'">';
						//do nothing if no icon specified
						htmlString += '';
					htmlString += '</xsl:when>';
				    htmlString += '<xsl:when test="string(number(' + this.xmlNodeName + '/text()))=\'NaN\'">';
				    	//if not numeric then assume an image name
				    	htmlString += '<img>';
				    		htmlString += '<xsl:attribute name="src">';
				    			htmlString += this.parentObj.imgPath; 
			    				htmlString += '<xsl:value-of select="' + this.xmlNodeName + '"/>';
			    			htmlString += '</xsl:attribute>';
				    	htmlString += '</img>';				    	
				    htmlString += '</xsl:when>'; 				    
				    htmlString += '<xsl:otherwise>';
			    		//if numeric then assume a predefined image icon
			    		htmlString += '<div>';
			    			htmlString += '<xsl:attribute name="class">';
			    				htmlString += 'viewicon vwicon'; 
			    				htmlString += '<xsl:value-of select="' + this.xmlNodeName + '/text()"/>';
			    			htmlString += '</xsl:attribute>';
			    		htmlString += '</div>';				    
		    		htmlString += '</xsl:otherwise>';
			    htmlString += '</xsl:choose>';											
			}
			else if(this.dataType=="number") // number is an attribute
			{
				htmlString += (this.numberPrefix)? '<![CDATA[' + this.numberPrefix  + ']]>' : "";
				htmlString += '<xsl:choose>';
					htmlString += '<xsl:when test="string-length(' + this.xmlNodeName + '/@val)=0 or ' + this.xmlNodeName + '/@val=\'\'">';
						if(this.numberBlankFormat == "1"){
	 					    htmlString += '<xsl:value-of select="format-number(0, \'' + this.numberFormat + '\')"/>';
						}
					htmlString += '</xsl:when>';
 				    htmlString += '<xsl:when test="string(number(' + this.xmlNodeName + '/@val))=\'NaN\'">';
 					    htmlString += '<xsl:value-of select="format-number(0, \'' + this.numberFormat + '\')"/>';
 				    htmlString += '</xsl:when>'; 				    
				    htmlString += '<xsl:otherwise>';
 					    htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@val, \'' + this.numberFormat + '\')"/>';
				    htmlString += '</xsl:otherwise>';
			    htmlString += '</xsl:choose>';				
				htmlString += (this.numberSuffix)? '<![CDATA[' + this.numberSuffix  + ']]>' : "";
			}
			else if(this.dataType=="date" || this.dataType == "datetime") // date is split into separate components returned as attributees
			{
				var changedateformat = false;
				var att1 = "";
				var att2 = "";
				var att3 = "";
				var d_sep = "";
				var datefmt = this.parentObj.dateFormat;

				if(typeof datefmt != "Undefined"  && typeof datefmt != "undefined" && datefmt != ""){
					d_sep = datefmt.indexOf(".")>-1 ?  "." : (datefmt.indexOf("-")>-1 ? "-" : (datefmt.indexOf("/") >-1 ? "/" : ""));
					var dateorder = datefmt.replace(/\/|-|\./g, " ");
					var d_order = "";
					switch(dateorder.toUpperCase()){
					case "MM DD YY":
						//fall through to next statement
					case "MM DD YYYY":
					     att1 = "M";
					     att2 = "D";
					     att3 = "Y";
					     changedateformat = true;
					     break;    
					case "DD MM YY": 
						//fall through to next statement
					case "DD MM YYYY":
					     att1 = "D";
					     att2 = "M";
					     att3 = "Y";
					     changedateformat = true;					     
					     break;
					case "YY MM DD":
						//fall through to next statement
					case "YYYY MM DD":
					     att1 = "Y";
					     att2 = "M";
					     att3 = "D";
					     changedateformat = true;
					     break;    
					}
				}
				if(changedateformat){
					htmlString += '<xsl:choose>';
						htmlString += '<xsl:when test="string-length(' + this.xmlNodeName + '/@' + att1 + ')>0 and not(' + this.xmlNodeName + '/@' + att1 + '=\'\')">';
							htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@' + att1 + ',\'00\')"/>' + d_sep;
							htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@' + att2 + ',\'00\')"/>' + d_sep;
							htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@' + att3 + ',\'00\')"/>' ;
							if (this.dataType == "datetime"){
								htmlString += '&#160;';
								htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@' + 'H' + ',\'00\')"/>' + ':';
								htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@' + 'MN' + ',\'00\')"/>' + ':';
								htmlString += '<xsl:value-of select="format-number(' + this.xmlNodeName + '/@' + 'S' + ',\'00\')"/>';
							}
						htmlString += '</xsl:when>';
						htmlString += '<xsl:otherwise>';
							htmlString += '<xsl:value-of select="' + this.xmlNodeName + '"/>';
						htmlString += '</xsl:otherwise>';
					htmlString += '</xsl:choose>';
				}else{
					htmlString += '<xsl:value-of select="' + this.xmlNodeName + '"/>';					
				}
			}
			else if (this.dataType == "html" ) //html is a node value
			{
				htmlString += '<xsl:value-of select="' + this.xmlNodeName + '" disable-output-escaping="yes"/>';
			}
			else
			{
					htmlString += '<xsl:value-of select="' + this.xmlNodeName + '"/>';
			}
		}
	htmlString += '</td>';
	
	//alert(htmlString)
	return htmlString;
}

//==========================================================================================
// creates html for a view total cell to be used inside xsl style sheet
//==========================================================================================

this.GetTotalCellHTML = function(nodeList, totalType)
{
	var xpathStr = (nodeList)? nodeList : '//document/' + this.xmlNodeName;
	var htmlString = "";
	var specialStyle='border-top: double 4px black;'; // default style for view grand total

	if(this.totalType != "0") //column has total
		{
		//------ apply special styles based on the total location
		if(totalType=="subtotal") //standard subtotal under the category or to the left of category heading
			{
				if(this.parentObj.usejQuery){
					specialStyle='';
				} else {
					specialStyle='border-top: solid 2px silver;';
				}
			}
		else if(totalType=="subtotalcat") //subtotal on the category row to the right of the category heading
			{
				specialStyle=this.parentObj.categoryBorderStyle;
			}
		//-------------------------------------
		htmlString += (this.isFrozen && !this.parentObj.disableFreeze)? '<td class="listitemfr"' : '<td class="listitem"';
		if(this.alignT != "") {htmlString += ' align="' +  this.alignT + '"' ;}	
		htmlString +=  ' style="' + this.GetCustomStyleString("T", specialStyle) + '"' + '>';	
		//-------------------------------------
		if(totalType=="subtotalcat") //subtotal to the right of category heading
			{
				htmlString += '<xsl:attribute name="isCategoryTotal"><xsl:value-of select="true()" /></xsl:attribute>';
				htmlString += '<span style="display:none;">';
			}
		htmlString += (this.numberPrefix)? '<![CDATA[' + this.numberPrefix  + ']]>' : "";
		//---- total computations
		htmlString += '<xsl:value-of select="format-number('; 
		if(this.totalType=="1") //sum
			{
				htmlString += 'sum(' + xpathStr + '/@val)';
			}
		else if(this.totalType=="2") //record count
			{
				htmlString += 'count(' + xpathStr + ')';
			}
		else if(this.totalType=="3") //average
			{
			htmlString += 'sum(' +xpathStr + '/@val) div count(' + xpathStr + ')';
			}
		htmlString += ', \'' + this.numberFormat + '\')"/>';
		//-------------------------------------
		htmlString += (this.numberSuffix)? '<![CDATA[' + this.numberSuffix  + ']]>' : "";
		
		if(totalType=="subtotalcat") //subtotal to the right of category heading
			{
				htmlString += '</span>&#160;';
			}
		//-------------------------------------
		htmlString += '</td>'
		}
	else //no total value - display empty cell
		{
			specialStyle = (totalType=="subtotalcat")? this.parentObj.categoryBorderStyle : "";
			htmlString = (this.isFrozen && !this.parentObj.disableFreeze)? '<td class="listitemfr"' : '<td class="listitem"';
			if(this.alignT != "") {htmlString += ' align="' +  this.alignT + '"' ;}	
			htmlString +=  ' style="' + this.GetCustomStyleString("T", specialStyle) + '"' + '>';	
			htmlString +='&#160;</td>';
		}
//alert(htmlString)
		return htmlString;
}

//==========================================================================================
// creates the style string
//==========================================================================================
this.GetCustomStyleString = function(styleSource, specialStyle)
{
		//apply custom style settings
		var customStyle='';	
		if(styleSource=="T") //asking for totals style
		{
		if(this.isFreezeControl && !this.parentObj.disableFreeze)
			{
	 			customStyle += 'border-right: groove 2px;';
			}
		if(this.fontSizeT != "") {customStyle += 'font-size:' + this.fontSizeT + ';' ;}
		if(this.fontFamilyT != "") {customStyle += 'font-family:' + this.fontFamilyT + ';' ;}
		if(this.colorT != "") {customStyle += 'color:' + this.colorT + ';' ;}
		if(this.fontWeightT != "") {customStyle += 'font-weight:' + this.fontWeightT + ';' ;}
		if(this.fontStyleT != "") {customStyle += 'font-style:' + this.fontStyleT + ';' ;}
		if(this.textDecorationT != "") {customStyle += 'text-decoration:' + this.textDecorationT + ';' ;}
		if(this.backgroundColorT != "") {customStyle += 'background-color:' + this.backgroundColorT + ';' ;}
		}
		else if(styleSource=="H") //asking for header style
		{
		if(this.fontSizeH != "") {customStyle += 'font-size:' + this.fontSizeH + ';' ;}
		if(this.fontFamilyH != "") {customStyle += 'font-family:' + this.fontFamilyH + ';' ;}
		if(this.colorH != "") {customStyle += 'color:' + this.colorH + ';' ;}
		if(this.fontWeightH != "") {customStyle += 'font-weight:' + this.fontWeightH + ';' ;}
		if(this.fontStyleH != "") {customStyle += 'font-style:' + this.fontStyleH + ';' ;}
		if(this.textDecorationH != "") {customStyle += 'text-decoration:' + this.textDecorationH + ';' ;}
		if(this.backgroundColorH != "") {customStyle += 'background-color:' + this.backgroundColorH + ';' ;} 		

		}
		else //data cell style
		{
		if(this.isFreezeControl && !this.parentObj.disableFreeze)
			{
	 			customStyle += 'border-right: groove 2px;';
			}
		if(this.fontSize != "") {customStyle += 'font-size:' + this.fontSize + ';' ;}
		if(this.fontFamily != "") {customStyle += 'font-family:' + this.fontFamily + ';' ;}
		if(this.color != "") {customStyle += 'color:' + this.color + ';' ;}
		if(this.fontWeight != "") {customStyle += 'font-weight:' + this.fontWeight + ';' ;}
		if(this.fontStyle != "") {customStyle += 'font-style:' + this.fontStyle + ';' ;}
		if(this.textDecoration != "") {customStyle += 'text-decoration:' + this.textDecoration + ';' ;}
		if(this.backgroundColor != "") {customStyle += 'background-color:' + this.backgroundColor + ';' ;}
		}
		
		return (specialStyle)? customStyle + specialStyle : customStyle ;
}

//==========================================================================================
// creates xsl sort key
//==========================================================================================

this.GetXslSortKey = function()
{
	if((this.sortOrder=="none" && this.customSortOrder == "none") || this.dataType=="icon") {return ""};
	var sortOrder = (this.customSortOrder != "none" && this.hasCustomSort) ? this.customSortOrder : this.sortOrder ;
	if(this.dataType == "date" || this.dataType == "number" || this.dataType == "datetime") //some special handling for dates an numbers
	{
	//for date column, xml contains date number in val (No of seconds) attribute
	var xlsKey = '<xsl:sort select="' + this.xmlNodeName + '/@val"'; // the val attribute contains the actual number to sort on
	xlsKey += ' data-type = "number"' ;
	}
	else //text sort
	{
	var xlsKey = '<xsl:sort select="' + this.xmlNodeName + '"';
	xlsKey +=  ' data-type = "text"';
	}
		
	xlsKey += (sortOrder == "") ? ' order = "ascending"' : ' order = "' + sortOrder + '"';
	xlsKey += '/>';
	
	return xlsKey;
}

//==========================================================================================
// helper function
//==========================================================================================
this.GetSortType = function()
{
	if(this.dataType=="icon") {return false;}
	if(this.customSortOrder != "none" && this.hasCustomSort)
	{
		return "custom";
	}
	else if(this.sortOrder != "none")
	{
		return "default";
	}
	return false;
}


//==========================================================================================
// helper function
//==========================================================================================
this.GetSortOrder = function()
{
	if((this.sortOrder=="none" && this.customSortOrder == "none") || this.dataType=="icon") {return "none"};
	return (this.customSortOrder != "none" && this.hasCustomSort) ? this.customSortOrder : this.sortOrder ;
}

//==========================================================================================
// clones current column
//==========================================================================================

this.Clone = function()
{
	var newColumn = new ObjViewColumn();
	for (i in this) 
	{
	newColumn[i] = this[i]; 
	}
return newColumn;
}

//==========================================================================================
// context menu handler
//==========================================================================================

this.ProcessContextAction = function(actionName) //handle action from contect menu
{
	if(actionName == "" ) {return false};

	var actionInfo = actionName.split("-"); //get clicked action info
	if(actionInfo[2]=="sort") //custom sort
	{
		if(this.customSortOrder.toString() == actionInfo[3].toString()) {return;} //no change
		this.parentObj.ToggleCustomSort(this.colIdx, actionInfo[3]);
		}
	else if(actionInfo[2]=="cat") //categorize
	{
		if(this.isCategorized)
		{
			this.isCategorized=false;
			this.sortOrder = "none";
		}
		else
		{
			this.isCategorized=true;
			this.sortOrder = (this.sortOrder == "none")? "ascending" : this.sortOrder;
		}
		this.parentObj.Refresh(false,true,true); //redraw the view
	}
	else if(actionInfo[2]=="freeze") //delete column
	{
		this.parentObj.FreezeColumnGroup(this.colIdx);
		this.parentObj.Refresh(false,true,true); //redraw the view
	}
	else if(actionInfo[2]=="delete") //delete column
	{
		this.parentObj.DeleteColumn(this.colIdx);
		this.parentObj.Refresh(false,true,true); //redraw the view
	}
	return;
}
	
} ////////////////////////////////////////////////// end of view column object //////////////////////////////////////////////////////////////////////////////// 


///////////////////////////  View Entry object ///////////////////////////////
function ObjViewEntry(parentObj, dataRow)
{
this.entryId = null; //html row id
this.isCategory=false;
this.isSubtotal=false;
this.isRecord=false;
this.isTotal=false;
this.docId = null; //doc id
this.rowIdx=null; //corresponding table row index
this.columnValues = new Array(); //column values displyed in the view
this.checkbox=null; //checkbox object in the selection column
this.parentObj = parentObj; //parent view object reference
this.parentRow=dataRow; //reference to parent html table row
this.xmlNode=null;

//==========================================================================================
//used to check if the current entry is selected
//==========================================================================================

this.IsSelected = function()
{
	if(this.checkbox)
		{
			return this.checkbox.checked; 
		}
	return false;
}

//==========================================================================================
//used to check if the current entry is highlighted
//==========================================================================================

this.IsCurrent = function()
{
	if(this.parentObj.currentEntry)
		{
		return this.parentObj.currentEntry == this.entryId; 
		}
	return false;
}

//==========================================================================================
//returns the value of a specific  xml node represented by this entry
//==========================================================================================

this.GetElementValue = function(elName)
{
	if(!this.xmlNode) {return false;}
	var elNode = this.xmlNode.selectSingleNode(elName)
	if(!elNode) {return false;}
	return elNode.textContent || elNode.text ;
}

//==========================================================================================
//returns the value of an attribute of a specific xml node represented by this entry
//==========================================================================================

this.GetElementAttribute = function(elName, atrName)
{
	if(!this.xmlNode) {return false;}
	var elNode = this.xmlNode.selectSingleNode(elName)
	if(!elNode) {return false;}
	return elNode.getAttribute(atrName);
}

/*============================================
Constructor
============================================*/

	if(!dataRow || !parentObj) {return;}
	this.entryId = dataRow.id; //html id
	this.rowIdx=dataRow.rowIndex; //corresponding table row index

	var baseIndex=0;
	try{
	    if(this.parentObj.showSelectionMargin) //selection column is enabled but not created yet
		{
			this.checkbox = dataRow.cells[0].getElementsByTagName("INPUT")[0]; //selection checkbox
			baseIndex++; //starting index for data columns
		}

	    for( var k = baseIndex; k < dataRow.cells.length ; k++)
		{
		    this.columnValues[this.columnValues.length] = dataRow.cells[k].innerText;
		}
	}catch (e){	}
		
	this.isCategory=jQuery(dataRow).attr("isCategory");
	this.isSubtotal=jQuery(dataRow).attr("isSubtotal");
	this.isRecord=jQuery(dataRow).attr("isRecord");
	this.isTotal=jQuery(dataRow).attr("isTotal");	
	if(!this.parentObj.oXml) {return;}
	
	this.xmlNode = this.parentObj.oXml.selectSingleNode( 'documents/document[docid="' + this.entryId + '"]' );

} //////////////////////////////////////////////////////////////////  end of entry object




///////////////////////////  View Object /////////////////////////// 
function ObjView(viewContainerId)
{


this.hasData=false;	//embView support - tracks whether any data was found for the requested ID
this.embView=false;	//embView support - if true, disables all status messages
this.baseLookupView="ludocumentsbyfolder"	//embView support - view to retrieve the document XML from
this.suffix="";		//embView support - Related Docs=RD; Memo=M; Comment=C; Related Link=RL
this.embViewPage="embeddedView.xml"; //embView support - default page for handling embedded view data
this.rowHeight = 0;	//default row height, set by emb view object, required for expand/collapse all
this.maxHeight = 0;	//default max height of the embedded view, set by emb view object, required for expand/collapse all
this.lookupURL = "";  //embView support - allows a custom url to be passed to get the xml from
this.usejQuery = false;  //embView support - control whether jQuery themes applied or not
this.responseColspan =0;
this.buildMode = false;
this.restrictToCategory = null;


this.columns = new Array(); //array of column objects
this.allEntries = new Array(); //array of all entry ids (maybe too much in large view)
this.collapsedCategories = new Array();
this.selectedEntries = new Array(); //array of selected (checked) entry ids
this.currentEntry = ""//hightlighted entry object
this.dataTableContainer = document.getElementById(viewContainerId);
this.dataTable = null; //table containing the data rows
this.showSelectionMargin=true; //shows/hides selection margin
this.allowCustomization=true; //allows adding/removing columns and changing columns properties
this.statusWin = null; //thing Factory status window
this.extendLastColumn=false; //controls the extend the last column to window width 
this.categoryBorderStyle = 'border-bottom : solid 2px #aaccff;';
this.idPrefix="V"; //prefix attached to all category and total row ids (in case there are more view objects) 
//set after the object is instantiated  - contains the base urls (no query string) of the resources/processing agents/servlets
this.baseXmlUrl=""; //data request goes here
this.thingFactory = null; //thing factory object declared outside
this.iconBaseUrl = ""; //base url for displaying icons in the icon column
this.baseUrl = ""; // general base url for view resources (sort icons/dialogs etc)
this.imgPath = "";
this.folderID=""; //id of the current folder
this.folderViewName=""; //name of view if view used instead of folder
this.userName="";
this.serverName="";
this.nsfName="";
this.serviceAgent="";
this.columnPropertiesDialogUrl = "dlgViewColumnProperties?OpenForm" ;

this.isFTSearch=false; //set and reset by the search methods true when view is displaying the search results
this.FTSearchParams=""; //xml with additional elements needed by FT search
this.isSummary=false; //specifies if the records are displayed in summary mode/ overrides other settings
this.isCategorized=false; //flag indicating that the view is categorized
this.disableFreeze=false; //when set for a large data set, column freezing is disabled
this.queryOptions=""; //additional options to be passed to the agents within <options> request element, not stored in perspective
this.isXmlDataRequest=true; //true to use http object the send xml data request, false to use query string
this.xmlNodeList=""; //list of xml data nodes to retrieve
this.viewScope = "";
this.versionOption = "";
this.contentPaging=false;  //flag to track whether content paging is on or off
this.docCount="";		//number of docs to load
this.startCount=1;		//starting index. 1 unless modified by Folder search
this.totalDocCount=0;	//tracks total # of docs in Folder (for Folder search)
this.docSubject="";		//subject of the doc that was saved (for Folder search)
this.exactMatch=true;	//when finding the doc in the view, exact match or not on Subject
this.getTotal=true;		//get the total number of docs in the folder. Performance hit so don't do it when just navigating folder contents
this.dateFormat="";     //global date format to use to format date values

this.isThumbnails=false;
this.thumbnailID = "";
this.thumbnailWidth = 100;
this.thumbnailHeight = 100;
this.thumbnailSort = "Filename";

//---------- summary settings ------
this.xmlSummaryNodeList = "F1,F4,F5,F7,F8,F9,F10,F17" //list of xml nodees required to build a summary view
this.summarySortXsl = '<xsl:sort select="F8" data-type="text"  order="ascending"/>';
this.summaryXsl='<div class="summaryTitle"><xsl:value-of select="F9"/> - <xsl:value-of select="F8"/></div>';
this.summaryXsl+='<div class="summaryDesc"><xsl:value-of select="F5"/></div>';
this.summaryXsl+='<div class="summaryEdit">Author: <xsl:value-of select="F1"/>, Created: <xsl:value-of select="F4"/>,';
this.summaryXsl+=' Version: <xsl:value-of select="F10"/>, Status: <xsl:value-of select="F7"/></div>';
this.summaryXsl+='<div class="summaryFiles">Attachments: <xsl:value-of select="F17"/></div>';

this.xmlThumbnailNodeList = "F1,F8,FILES"
	
this.hiddenColCount = 0;


/*
this.xmlSummaryNodeList = "F1,F4,F5,F8,F12,F17" //list of xml nodees required to build a summary view
this.summarySortXsl = '<xsl:sort select="F8" data-type="text"  order="ascending"/>';
this.summaryXsl='<div class="summaryTitle"><xsl:value-of select="F8"/></div>';
this.summaryXsl+='<div class="summaryDesc"><xsl:value-of select="F5"/></div>';
this.summaryXsl+='<div class="summaryEdit">Created by: <xsl:value-of select="F1"/> on: <xsl:value-of select="F4"/>,';
this.summaryXsl+=' Last modified on: <xsl:value-of select="F12"/></div>';
this.summaryXsl+='<div class="summaryFiles">Attachments: <xsl:value-of select="F17"/></div>';
*/

//==========================================================================================
//get all the view and column settings as an xml string
//==========================================================================================

this.GetViewParams = function()
{

var returnString="";

	returnString += "<viewsettings>";
		returnString += "<viewproperties>";
			returnString += (this.showSelectionMargin)? "<showSelectionMargin>1</showSelectionMargin>" : "<showSelectionMargin/>";
			returnString += (this.allowCustomization)? "<allowCustomization>1</allowCustomization>" : "<allowCustomization/>";
			returnString += (this.extendLastColumn)? "<extendLastColumn>1</extendLastColumn>" : "<extendLastColumn/>";
			returnString += (this.isSummary)? "<isSummary>1</isSummary>" : "<isSummary/>";
			returnString += (this.responseColspan > 0 )? "<responseColspan>" + this.responseColspan + "</responseColspan>" : "<responseColspan>0</responseColspan>";
			if (this.isThumbnails){
				returnString += "<isThumbnails>1</isThumbnails>";
				returnString += "<thumbnailSort>" + this.thumbnailSort + "</thumbnailSort>";
				returnString += "<thumbnailHeight>" + this.thumbnailHeight + "</thumbnailHeight>";
				returnString += "<thumbnailWidth>" + this.thumbnailWidth + "</thumbnailWidth>";
			}else{
				returnString +=  "<isThumbnails/>";
			}
			returnString += "<categoryBorderStyle>" + this.categoryBorderStyle + "</categoryBorderStyle>";
		returnString += "</viewproperties>";
		returnString += "<columns>";
			for(var k=0; k< this.columns.length; k++)
				{
					returnString += this.columns[k].GetColumnParams();
				}
		returnString += "</columns>";			
	returnString += "</viewsettings>";
	
	return returnString;
}

//==========================================================================================
//sets the view and column parameters from xml
//==========================================================================================

this.SetViewParams = function(xmlString)
{
	var xmlDoc = null;
	var propertyNodes = null;
	var columnNodes = null;
	var tmpNode = null;
	var node = null;
	var evalString = ""
	this.isThumbnails = false;	

	var xmlDoc = (new DOMParser()).parseFromString(xmlString, "text/xml");
	if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
  		var errorText = Sarissa.getParseErrorText(xmlDoc);
		alert("Error loading view settings: " + errorText);
		xmlDoc = null;
		return false;
	}

	tmpNode = xmlDoc.selectSingleNode("/viewperspective/viewsettings/viewproperties");	
	if(tmpNode != null){
		propertyNodes = tmpNode.childNodes;
		//------ update base view settings
		for(var k=0; k< propertyNodes.length; k++)
		{
			node = propertyNodes.item(k);
			if(node != null && node.nodeName != "#text")
			{
				evalString = "this." + node.nodeName;
				evalString += "=\"";
				evalString += (node.text)? node.text : ((node.textContent) ? node.textContent : "");
				evalString += "\";";
				eval(evalString);
			}
		}		
	}

	columnNodes = xmlDoc.selectNodes("/viewperspective/viewsettings/columns/column");	
	
	//---- create columns ---------
	if(columnNodes)
	{
		this.columns=new Array();  //clear all the existing columns
		var column = null;
		
		//if restrict to category, then we skip the first column since that will be omitted from the json that domino returns
		var k  =  this.restrictToCategory && this.restrictToCategory != "" ? 1: 0;
		
		//k = 0;
		//------ create new columns from perspective settings
		for(k; k< columnNodes.length; k++)
		{
			node = columnNodes.item(k);
			if(node)
				{
					column = new ObjViewColumn();
					column.SetColumnParams(node.childNodes);
					this.AppendColumn(column);
				}
		}
	}
	
	xmlDoc = null;
	return true;
}

//==========================================================================================
//selects all entries
//==========================================================================================

this.SelectAllEntries = function()
{
	if(!this.dataTable || !this.showSelectionMargin) {return ;}
	var checkbox=null;
	var rowId = "";
	var cnt =0;
	for(var k=1; k < $(this.dataTable).prop('rows').length; k++) //skip header row (0)
	{
		if($(this.dataTable.rows[k]).attr('isRecord')) //category and total rows have no checkboxes
		{
		checkbox = this.dataTable.rows[k].cells[0].getElementsByTagName("INPUT")[0]; //selection checkbox
		rowId = $(this.dataTable.rows[k]).attr('id');
		if(checkbox && rowId)
			{
			checkbox.checked = true;
			this.selectedEntries[cnt] = rowId;
			cnt ++;
			}
		}
	}
}


//==========================================================================================
//deselects all selected entries
//==========================================================================================

this.DeselectAllEntries = function()
{
	if(! this.dataTable || this.selectedEntries.length==0) {return ;}

	var checkbox=null;
	var rowId = "";
	for(var k=1; k < $(this.dataTable).prop('rows').length; k++) //skip header row (0)
	{
		if($(this.dataTable.rows[k]).attr('isRecord')) //category and total rows have no checkboxes
		{
		checkbox = this.dataTable.rows[k].cells[0].getElementsByTagName("INPUT")[0]; //selection checkbox
		if(checkbox) 
			{
			checkbox.checked = false;
			}
		}
	}
	
	this.selectedEntries = new Array(); //selected entries will now be an empty array
}

//==========================================================================================
//reselects all selected entries after the view refresh
//==========================================================================================

this.ReselectEntries = function()
{
	if(! this.dataTable || this.selectedEntries.length==0) {return ;}
	var tmpArray = new Array();
	for(var k=0; k < this.selectedEntries.length; k++) //must copy the selected entries array as it will get overwritten during reselection
	{
	tmpArray[k] = this.selectedEntries[k];
	}
	this.selectedEntries = new Array();	
	for(var k=0; k < tmpArray.length; k++) //now reselect the entries
	{
		this.ToggleSelectEntryById(tmpArray[k], "check"); //force checked state
	}

}


//==========================================================================================
//selects/deselects/toggles the entry pointed by a specific html id (set state forces the check/uncheck)
//==========================================================================================

this.ToggleSelectEntryById = function(entryId, setState)
{
	if(! this.dataTable || ! entryId || ! this.showSelectionMargin) {return ;}
		var dataRow = document.getElementById(entryId);
		
		if(dataRow)
		{
			if(jQuery(dataRow).attr('isRecord'))
			{
				checkbox = jQuery(dataRow).find('input:first').get(0); //selection checkbox
				if(checkbox) 
				{
					if((checkbox.checked && setState=="check") || (!checkbox.checked && setState=="uncheck")) {return;} //no change already checked/unchecked
					//just toggle current state
					(checkbox.checked) ? checkbox.checked=false :	checkbox.checked=true;
				}
			}
		
		//update selected entries array 
			if ( checkbox.checked ) { 
				this.selectedEntries[this.selectedEntries.length] = dataRow.id;
			}else{
				for ( var q= 0; q < this.selectedEntries.length ; q ++ ) {
					if ( this.selectedEntries[q] == dataRow.id ) {
						this.selectedEntries.splice ( q, 1 );
						break;
					}
				}
				// this.selectedEntries.splice(dataRow.id, 1);
			}
		}
}


//==========================================================================================
//delete column
//==========================================================================================

this.DeleteColumn = function(colIdx)
{
	if( !this.IsValidColumnIndex(colIdx) ) {return;}
	this.columns.splice(colIdx, 1);
	this.ComputeColumnReferences();
}


//==========================================================================================
//freeze column group to the left of selected column (inclusive)
//==========================================================================================

this.FreezeColumnGroup = function(colIdx)
{
	if( !this.IsValidColumnIndex(colIdx) ) {return;}
	if(this.columns[colIdx].isFreezeControl) //reset freeze
	{
		for(var k=0; k<this.columns.length; k++)
		{
			this.columns[k].isFrozen=false;
			this.columns[k].isFreezeControl=false;
		}
	}
	else //set freeze
	{
		for(var k=0; k<this.columns.length; k++)
		{
			(k<=colIdx)?	this.columns[k].isFrozen=true : this.columns[k].isFrozen=false;
			(k!=colIdx)?	this.columns[k].isFreezeControl=false : this.columns[k].isFreezeControl=true;
		}
	}
}

//==========================================================================================
// append column object
//==========================================================================================

this.AppendColumn = function(columnObj)
{
	var newColIdx = this.columns.length;
	if(columnObj) //column object was created
	{
		this.columns[newColIdx] = columnObj.Clone();
	}
	else
	{
		columnObj = new ObjViewColumn(); //create new empty column
		this.columns[newColIdx] = columnObj;
	}
	this.columns[newColIdx].parentObj=this;
	this.ComputeColumnReferences();
	return columnObj;
}

//==========================================================================================
//moves the columns as a result of drag/drop operation 
//==========================================================================================

this.MoveColumn = function(fromIdx, toIdx)
{
	if( !this.IsValidColumnIndex(fromIdx) || !this.IsValidColumnIndex(toIdx) || fromIdx==toIdx) {return;}

	var tmpCol = this.columns[fromIdx];
	this.DeleteColumn(fromIdx);
	this.InsertColumn(toIdx, tmpCol );
	this.ComputeColumnReferences();
}

//==========================================================================================
//inserts the column at the specific index
//==========================================================================================

this.InsertColumn = function(toIdx, columnObj)
{

	if(toIdx == null) {return null;}
	if(!columnObj) //column object was not passed
		{
			columnObj = new ObjViewColumn(); //create new empty column
		}
	columnObj.parentObj=this;

	if(toIdx >= this.columns.length) //append at the end
		{
			this.columns[this.columns.length] = columnObj; 
		}
	else if(toIdx <= 0)  //put the new column at the begining
		{
			columnObj.isFrozen=this.columns[0].isFrozen;
			this.columns.unshift(columnObj);
		}
	else //insert inside
		{
			columnObj.isFrozen=this.columns[toIdx].isFrozen;
			var tmpColumns = new Array();
			this.columns = tmpColumns.concat( this.columns.slice(0, toIdx), columnObj, this.columns.slice(toIdx));
		}
	this.ComputeColumnReferences();
	return columnObj;
}

//==========================================================================================
// add/remove column handler
//==========================================================================================
//this.AddNewColumn = function(colIdx)
//{
//	var newColumn = new ObjViewColumn(); //create new empty column
//	newColumn.parentObj=this;
//	var propReturn = newColumn.ShowPropertiesDialog("insert");
//	if(!propReturn){return; } //cancelled
//	this.InsertColumn(colIdx, newColumn)
//	this.Refresh(propReturn[0],true,true);
//}	


this.ToggleThumbnailSort = function(id){
	
	this.thumbnailSort =id;
		
	this.Refresh(false, true,true);
	
}

this.GetThumbnailSort = function(){
	var xlsStyle = "";
	 if ( this.thumbnailSort == "Date"){
		 xlsStyle = '<xsl:sort select="@val" data-type="number"  order="ascending"/>';
	 }else if (this.thumbnailSort == "Author"){
		 xlsStyle = '<xsl:sort select="author" data-type="text"  order="ascending"/>';	 
	 }else if (this.thumbnailSort == "Filename"){
		 xlsStyle = '<xsl:sort select="name" data-type="text"  order="ascending"/>'; 
	 }
	
	return xlsStyle;
}


//==========================================================================================
//toggles the custom sort on a column
//==========================================================================================

this.ToggleCustomSort = function(colIdx, sortOrder)
{
	if(!this.IsValidColumnIndex(colIdx)) {return;}
	if(!$(this.columns[colIdx]).attr("hasCustomSort")) {return;} //custom sort not enabled for this column
	if(sortOrder)
		{
		//this.columns[colIdx].customSortOrder=sortOrder;
		$(this.columns[colIdx]).attr("customSortOrder", sortOrder);
		}
	else if($(this.columns[colIdx]).attr("customSortOrder")=="none")
		{
		//this.columns[colIdx].customSortOrder="ascending";
		$(this.columns[colIdx]).attr("customSortOrder", "ascending");
		}
	else if($(this.columns[colIdx]).attr("customSortOrder")=="ascending")
		{
		//this.columns[colIdx].customSortOrder="descending";
		$(this.columns[colIdx]).attr("customSortOrder","descending");
		}
	else
		{
		//this.columns[colIdx].customSortOrder="none";
		$(this.columns[colIdx]).attr("customSortOrder", "none");
		}
	
	this.Refresh(false, true,true); //rebuild the view restoring the view state
}

this.CtrlSelectEntries = function ( id)
{
	if(! this.dataTable || ! id) {return ;}
	var dataRow = document.getElementById(id);
	var selindex = -1;
	for ( var q= 0; q < this.selectedEntries.length ; q ++ ) {
		if ( this.selectedEntries[q] == dataRow.id ) {
			selindex = q;
			break;
		}
	}
	if(dataRow)
	{
		if ( selindex > -1){
				if (! this.isThumbnails && !this.isCoverflow){
					checkbox = dataRow.cells[0].getElementsByTagName("INPUT")[0]; //selection checkbox
					if(checkbox)
					{
						checkbox.checked = false;
					}
					/*if(this.usejQuery){
						jQuery(dataRow).removeClass("ui-state-hover");
						jQuery(dataRow).addClass("listrow");
					} else {
						jQuery(dataRow.cells).css({"backgroundColor":"", "color":""});
					}*/
				}
				this.selectedEntries.splice ( selindex, 1 );
		}else{
			checkbox = dataRow.cells[0].getElementsByTagName("INPUT")[0]; //selection checkbox
			if(checkbox)
			{
				checkbox.checked = true;
			}
			this.selectedEntries[this.selectedEntries.length] = id;
		}
	}
}
//handles the SHIFT-Click to select rows like windows explorer
this.ShiftSelectEntries = function (startid, endid )
{
	var dataRowStart = document.getElementById(startid);
	var dataRowEnd = document.getElementById(endid);
	var start = dataRowStart.rowIndex < dataRowEnd.rowIndex ? dataRowStart.rowIndex : dataRowEnd.rowIndex;
	var end = dataRowStart.rowIndex < dataRowEnd.rowIndex ? dataRowEnd.rowIndex : dataRowStart.rowIndex;
	this.selectedEntries = [];
	var cnt = end - start;
	for ( var p = 0; p <= cnt; p ++){
		var dataRow = this.dataTable.rows[start];
		if($(dataRow).attr('isRecord')) //category and total rows have no checkboxes
		{
			checkbox = dataRow.cells[0].getElementsByTagName("INPUT")[0]; //selection checkbox
			if(checkbox)
			{
				checkbox.checked = true;
			}
			rowId = $(dataRow).attr('id');
			this.selectedEntries[this.selectedEntries.length] = rowId;
		}
		if ( start < end )
			start ++ 
		else 
			start --;
	}
}
//==========================================================================================
//highlights data row by html id
//==========================================================================================

this.HighlightEntryById = function(entryId)
{

if(! this.dataTable || ! entryId) {return ;}
	var dataRow = document.getElementById(entryId);
	if(dataRow)
	{
		this.ResetHighlight() //reset current entry highlight
		if (! this.isThumbnails ){
			if(this.usejQuery){
				jQuery(dataRow).addClass("ui-state-hover");
			} else {
				jQuery(dataRow.cells).css({"backgroundColor":"#2070B0", "color":"#ffffff"});
			}
			this.currentEntry = entryId;
			$(dataRow).attr("isSel", "1");
		}else{
			this.thumbnailID = entryId;
			this.currentEntry = entryId.substring(0, entryId.indexOf("~") );
		}
		try
		{
			dataRow.focus();
		} 
			catch(e)
		{
			//this.HideProgressMessage();
			Docova.Utils.hideProgressMessage();
		}
		
	}
}

//==========================================================================================
//highlights data row by row index.  Needed due to 'show multiple values as separate entries' in 
//a view where you can have multiple rows with the same id
//==========================================================================================

this.HighlightEntryByRowIndex = function(rowIndex)
{
	if(! this.dataTable || ! rowIndex) {return ;}
	var dataRow = this.dataTable.rows[rowIndex];
	if(dataRow)
	{
		this.ResetHighlight() //reset current entry highlight
		if (! this.isThumbnails ){
			if(this.usejQuery){
				jQuery(dataRow).addClass("ui-state-hover");
			} else {
				jQuery(dataRow.cells).css({"backgroundColor":"#2070B0", "color":"#ffffff"});
			}
			this.currentEntry = dataRow.id;
			$(dataRow).attr("isSel", "1");
		}else{
			this.thumbnailID = entryId;
			this.currentEntry = entryId.substring(0, entryId.indexOf("~") );
		}
		try
		{
			dataRow.focus();
		} 
			catch(e)
		{
			//this.HideProgressMessage();
			Docova.Utils.hideProgressMessage();
		}
		
	}
}

//==========================================================================================
// resets the current row highlight
//==========================================================================================

this.ResetHighlight = function()
{
if(! this.dataTable || ! this.currentEntry) {return ;}

	var id = (this.isThumbnails) ? this.thumbnailID : this.currentEntry;
	var dataRow = $(this.dataTable).find("tr[isSel='1']").get(0);
	if(dataRow)
	{
		if (! this.isThumbnails && !this.isCoverflow){
			if(this.usejQuery){
				jQuery(dataRow).removeClass("ui-state-hover");
				jQuery(dataRow).addClass("listrow");
			} else {
				jQuery(dataRow.cells).css({"backgroundColor":"", "color":""});
			}
		}
		this.currentEntry = "";
		$(dataRow).attr("isSel", "");
	}
}


//==========================================================================================
//moves highlight up or down
//==========================================================================================

this.MoveEntryHighlight = function(dir)
{
	var newRow = null;
	var highlightedEntryId = this.currentEntry;
	
	if(highlightedEntryId) //move highlight only if something is already highlighted
		{
			var dataRow = document.getElementById(highlightedEntryId);
			if($(dataRow).attr("isCategory") && $(dataRow).attr("isCollapsed")) //collapsed category, jump to the next category row
			{
				newRow = this.GetAdjacentCategory(dir, highlightedEntryId);
			}
			else
			{
				newRow = (dir == "up") ? this.GetPreviousDataRow(highlightedEntryId) : this.GetNextDataRow(highlightedEntryId);
			}
			
			if(newRow)
			{
			this.HighlightEntryById($(newRow).prop("id"));
			newRow.scrollIntoView(false) ;
			try
				{
					newRow.focus() ;
				} 
					catch(e)
				{
					//this.HideProgressMessage();
					Docova.Utils.hideProgressMessage();
				}
				
			
			}
		}
}



//==========================================================================================
// clears existing view data 
//==========================================================================================
this.ClearViewData = function()
{
	var xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><documents></documents>";
	var xmlDoc = (new DOMParser()).parseFromString(xmlString, "text/xml");
	if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
  		var errorText = Sarissa.getParseErrorText(xmlDoc);
		alert("Error clearing view data: " + errorText);
		xmlDoc = null;
		return false;
	}
	
	this.oXml = xmlDoc;
	this.Refresh(false, true, false);
	
	xmlDoc = null;
	return true;
}

this.RecordExpandedCategories = function (){
	if(! this.dataTable) {return null;}
	var catId = "";
	var cat = null; 
	delete this.collapsedCategories;
	this.collapsedCategories = new Array();
	for(var k=1; k < this.dataTable.rows.length; k++)
		{
			cat = this.dataTable.rows[k];
			
			if(cat)
				{
					var isCategory = cat.getAttribute ("isCategory");
					var iscollapsed = cat.getAttribute("isCollapsed");
					
					if ( isCategory=="true" && iscollapsed=="true" ){
						this.collapsedCategories[this.collapsedCategories.length] = cat.id;
					}
				}
				else
				{
					return;
				} 
		}
}

this.ResetCollapsedCategories = function(){
	
	for ( var j = 0; j<this.collapsedCategories.length; j ++ ){
		
		this.CollapseCategory(this.collapsedCategories[j]);
	}
	
}

//==========================================================================================
// redraws the view, and if necessary gets new xsl/xml and reloads 
//==========================================================================================
this.Refresh = function(loadXml, loadXsl, restoreState, tagCloudRedrawFlag, _ApplyDefaultFolderFilter, _ApplyCurrentFolderFilter)
{
	if(!this.embView)
		this.RecordExpandedCategories();
	
	if (typeof tagCloudRedrawFlag === "undefined") 
		tagCloudRedrawFlag=true
		
	if(!this.dataTableContainer) {return;} //no data table container provided during object initialization - quit
	this.leftBound = (this.dataTableContainer)? this.dataTableContainer.offsetLeft : 0;

	if(loadXml) //asked to load fresh version of xml data
	{
		this.ComputeColumnReferences();
		if(!this.embView){Docova.Utils.showProgressMessage("Retrieving records. Please wait...");}
		this.GetXmlDocument();
	}
	
	if(loadXsl) //asked to build fresh version of xsl 
	{
		this.GetXslDocument();
	}	
	if(!this.embView){Docova.Utils.showProgressMessage("Building view. Please wait...");}

	if(!this.oXml){
		//if oXml is null it needs to be set to a minimal XML doc else the transformToFragment call will fail
		var parser = new DOMParser();
		this.oXml = parser.parseFromString('<?xml version="1.0" encoding="UTF-8"?>',"text/xml");
	}

	var processor = new XSLTProcessor(); 
	processor.importStylesheet(this.oXsl);
	var parser = new DOMParser();	
	var elTable = processor.transformToFragment(this.oXml,document);	

	this.dataTableContainer.innerHTML = "";
	this.dataTableContainer.appendChild(elTable);
	this.dataTable = this.dataTableContainer.getElementsByTagName("TABLE")[0];
	this.hasData=false;
	if(this.dataTable.rows.length > 1) {
		this.hasData=true;
	}
	
	if(!this.extendLastColumn) //reset filler column width to be dynamic
	{
		this.dataTable.rows[0].cells[this.dataTable.rows[0].cells.length-1].width=""
	}
	if(restoreState) //re-select and highlight entries
	{
		if(!this.embView){Docova.Utils.showProgressMessage("Restoring view state. Please wait...");}
		this.HighlightEntryById(this.currentEntry); //restore entry highlight if any
		this.ReselectEntries(); //re-check the checkboxes if any
	}
	
	if(!this.embView){Docova.Utils.hideProgressMessage();}
	if(tagCloudRedrawFlag)
	{
//		initTagCloud(); // to refresh facet map panel - note: disabled to speed up large perspective loading
	}
	//apply the currently active filter...this is used from the refresh icon as well as tabFunctions
	//save and close to re-apply the current filters that the user might have applied.
	if ( _ApplyCurrentFolderFilter)
	{
		this.oOriginalXml = this.oXml;
		ApplyCurrentFolderFilter(false);
		
	}else if(_ApplyDefaultFolderFilter)  //apply the default folder filter.
	{
		this.oOriginalXml = this.oXml;
		ApplyDefaultFolderFilter(false);
	}
	
	if(!this.embView)
		this.ResetCollapsedCategories();
	
	try{
		if ( docInfo.IsEmbedded == "true"){
			//imbedded view is in an iframe..so we get the parent
			var parentwin = window.parent;
			uiDoc = parentwin.Docova.getUIDocument();
			if ( uiDoc)
				uiDoc.triggerHandler('EmbViewLoadComplete', Docova.getUIView());
		}else{
			 var uiview = Docova.getUIView()
			 uiview.triggerHandler('LoadComplete', uiview);
		}
	}catch(e){}
	
	 
}

//==========================================================================================
//redraw the screen computing all dynamic properties
//==========================================================================================
this.ReflowScreen = function()
{
	document.recalc(true);
	this.dataTable.refresh();
	document.recalc(true);
}

//==========================================================================================
//retrieves the xml and xsl and is necessary highlights the specific entry (by id)
//==========================================================================================

this.Load = function(entryId)
{
	this.Refresh(true,true, false); //load new xml/xsl
	if(entryId) //if request included entry id to highlight - highlight it now
		{
		this.HighlightEntryById(entryId);
		}
}


this.Parser = {
		currentIndex : 0,
		xmlStr : "",
		arrCategories: [],
		restrictToCategory: "",
		reset: function (restrictVal){
			this.currentIndex = 0;
			this.xmlStr = "";
			this.arrCategories = [];
			this.restrictToCategory = restrictVal;
		},
		
		isResponse: function ( entry )
		{
			var children = entry["@children"];
			var category = entry.entrydata[0]["@category"]
			var column = entry.entrydata[0]["@columnnumber"]
			
			if ( ! children )
				return false;
			else{
				if ( category && category == "true" ) {
							return false;
				}else{
						return true;
				}
			}
		},
		
		isCategory: function (entry){
			var children = entry["@children"];
			var category = entry.entrydata[0]["@category"]
			var column = entry.entrydata[0]["@columnnumber"]
			
			if ( ! children )
				return false;
			else{
				if ( category && category == "true" ) {
							return true;
				}else{
						return false;
				}
			}
		
		},
		
		isRestrictToCategory: function ( entry )
		{
			if ( entry.entrydata[0] && entry.entrydata[0].text[0] && entry.entrydata[0].text[0].toUpperCase() == this.restrictToCategory.toUpperCase()){
				return true;
			}else
				return false;
		},
		
		getColumnValues: function ( entry){
			
				
				indx =0;
				for ( t=0; t < this.arrCategories.length; t++ ){
						this.xmlStr  += "<CF" + indx + "><![CDATA[" + this.arrCategories[t].category + "]]></CF" + indx + ">";
						indx++;
				}
				
				this.xmlStr += "<dockey>" + entry["@unid"] + "</dockey>";
				this.xmlStr += "<docid>" + entry["@unid"] + "</docid>";
		
				for (p =0; p < entry.entrydata.length; p ++  ){
					var entrydata = entry.entrydata[p]
					if ( entrydata.text ) {
						if ( entrydata.text[0]  ){
							var colname = entrydata["@name"];
							if ( colname && colname != "" ){
								if ( colname.indexOf("_HTML")> 0 )
									this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + ">" + entrydata.text[0] + "</CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
								else
									this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "><![CDATA[" + entrydata.text[0] + "]]></CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
							}else
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "><![CDATA[" + entrydata.text[0] + "]]></CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
						}else
							this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
						indx ++;
					}
					else if (entrydata.html) {
						if (entrydata.html[0]) {
							this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + ">" + entrydata.html[0] + "</CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
						}
						else 
							this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
						indx++;
					}
					else if ( entrydata.datetime ){
						
							
							if ( entrydata.datetime[0]  ){
								
								
								var dtstring = entrydata.datetime[0]
								
								                             
								var year = dtstring.substring(0,4);
								var month =  dtstring.substring(4,6);
								var day =  dtstring.substring(6,8);
								var hour = dtstring.substring(9,11);
								var minute = dtstring.substring(11,13);
								var sec = dtstring.substring(13,15);
								var dt2 = new Date(year +'/'+month+'/'+day+' '+hour+":"+minute+":"+sec);
								var dt1 = new Date("1/1/1000 01:02:00 PM")
								var diff = dt2-dt1/1000
								
								     
								var dtattr = "val='" + diff + "' Y='" + year + "' M='"+month + "' D='" + day + "' H='" +hour + "' MN='" + minute + "' S='"+sec + "'";
								
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + " " + dtattr + "><![CDATA[" + entrydata.datetime[0] + "]]></CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + ">";
							}else
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
								indx ++;
					}else if ( entrydata.textlist)
					{
						if ( entrydata.textlist.text){
							var concattext = "";
							for ( var l = 0; l < entrydata.textlist.text.length; l ++) {
								if ( l ==0 )
									concattext += entrydata.textlist.text[l][0];
								else
									concattext += "; " + entrydata.textlist.text[l][0];
							}
							if ( concattext != ""  )
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "><![CDATA[" + concattext + "]]></CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
							else
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
								indx ++;
						}
					}else if (entrydata.number)
					{
						if ( entrydata.number[0]  )
							this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + " val='" +entrydata.number[0] +"'>" + entrydata.number[0] + "</CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
						else
							this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
							indx ++;
					}else if (entrydata.numberlist){
						if ( entrydata.numberlist.number){
							var concattext = "";
							for ( var l = 0; l < entrydata.numberlist.number.length; l ++) {
								if ( l ==0 )
									concattext += entrydata.numberlist.number[l][0];
								else
									concattext += "; " + entrydata.numberlist.number[l][0];
							}
							if ( concattext != ""  )
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + " val='" + concattext +"'>" + concattext + "</CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) +  ">";
							else
								this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
								indx ++;
						}										
					}else{
						
						this.xmlStr  += "<CF" + (entrydata['@columnnumber'] ? entrydata['@columnnumber'] : indx) + "/>"
						indx ++;
					}
						
						
					
					
				}
				
		},
		
		parse: function ( data)
		{
			var groups = {};
			
			var viewentry = data.viewentry;
			if (!viewentry){
				return "<documents></documents>"
			}
			this.xmlStr = "<documents>"
		
			var entryIndex = 0;
		
			while( this.currentIndex < viewentry.length ){ 
				
				
				var entry = viewentry[ this.currentIndex];
				var children = entry["@children"];
				var totalscol = entry["@categorytotal"];
				
				if ( this.restrictToCategory && this.restrictToCategory != "")
					if ( !this.isRestrictToCategory(entry)) break;
				
		
				
				if ( totalscol && totalscol == "true" ){
					//do nothing
				}else{	
					if ( this.isCategory(entry))
					{
						//if category...store them in an array
						var category = entry.entrydata[0]["@category"]
						var column = entry.entrydata[0]["@columnnumber"]
						var catvalue =entry.entrydata[0].text[0];
						var catObj = {};
						catObj.column = column;
						catObj.category  = catvalue;
						var found = false;
						for ( k = 0; k< this.arrCategories.length; k ++ ){
							if ( this.arrCategories[k].column == column )
								found = true;
							if ( this.arrCategories[k].column == column && this.arrCategories[k].category != catvalue )
								this.arrCategories[k].category = catvalue;
						}
						if ( ! found )
							this.arrCategories.push(catObj);
					
					}else if ( this.isResponse(entry)){
						//responses
						this.xmlStr  += "<document>"
						
							this.getColumnValues(entry);
					
						this.parseResponse(viewentry)
						this.xmlStr  += "</document>"
					}else{
						this.xmlStr  += "<document>"
						
							this.getColumnValues(entry);
						this.xmlStr  += "</document>"
					
					}
				}
				 this.currentIndex ++;
			}
			this.xmlStr  += "</documents>"
		
			return this.xmlStr;
			
		}, 
		
		parseResponse: function ( viewentry )
		{
			var entry = viewentry[this.currentIndex];
			var children = entry["@children"];
			this.xmlStr += "<responses>"
			for (var t = 0; t < parseInt ( children ); t ++ )
			{
			     this.currentIndex ++;
			     if ( this.currentIndex >=  viewentry.length ) return;
				entry = viewentry[this.currentIndex]
				this.xmlStr += "<respdoc>"
				
				this.getColumnValues(entry);
				  
				  if ( this.isResponse(entry))
				  		this.parseResponse(viewentry)
				
				this.xmlStr += "</respdoc>"
			
			}
			this.xmlStr += "</responses>"
		
		
		}

	}

	

//==========================================================================================
//retrieve xml data from the server
//==========================================================================================
this.GetXmlDocument = function()
{
	var dt = new Date();

		
		var status = "";	
		var error = "";
		var xmlRequest = this.GetXmlRequestString();
		var prefx  = "";
		var responseXml = null;
		var responseTextXml = "";
		var container = this;
		
		if ( ! docInfo.ViewName ||  docInfo.ViewName == "" ) return;
		
		
		
		if ( this.isFTSearch ){
			var url = this.baseUrl + "ViewServices?OpenAgent"
			var request="";
			request += "<Request>";
			request += "<Action>SEARCHNOTESVIEW</Action>";
			request += "<Query>" + this.FTSearchParams + "</Query>";
			request += "<ViewName>" + docInfo.ViewName + "</ViewName>";
			request += "</Request>";
			
			$.ajax({
				'type' : "POST",
				'url' : url,
				'data': request,
				'contentType': false,
				'async' : false,
				'dataType' : 'xml'
			})
			.done(function(data) {
				if(data) {
					
					var rootNode = data.childNodes[0];
					if ( rootNode.nodeName == "documents" )
						container.oXml = data;
					else
						alert ( "There was an error conducting this search.  Please check the logs for error details.");
				}
			})
			
			return;

		}
		var restrict = "";
		
		if (this.restrictToCategory != null && this.restrictToCategory != "")
			restrict = "&startKey=" + this.restrictToCategory
		
	    //there is a restrict to category but its blank...so we don't have to get any more data
		if ( this.restrictToCategory != null && this.restrictToCategory == "")
		{
			var xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><documents></documents>";
			var xmlDoc = (new DOMParser()).parseFromString(xmlString, "text/xml");
			if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
		  		var errorText = Sarissa.getParseErrorText(xmlDoc);
				alert("Error getting view data: " + errorText);
				xmlDoc = null;
				return false;
			}
			
			this.oXml = xmlDoc;
			return;
		} 
		
		if ( this.contentPaging){
			var maxDocs = this.docCount;	
			if((this.totalDocCount - this.startCount) == maxDocs){
				maxDocs = maxDocs + 1;
			}
			if (docInfo.ViewID != '') {
				var url =  this.baseUrl + 'wLoadView/' + docInfo.ViewID + "/" + docInfo.AppID + "?readviewEntries&outputformat=json&start=" + this.startCount + "&count=" + maxDocs + restrict + "&" + Math.random();
			}
			else {
				var url =  this.baseUrl + 'loadView/' + docInfo.ViewName + "/" + docInfo.AppID + "?readviewEntries&outputformat=json&start=" + this.startCount + "&count=" + maxDocs + restrict + "&" + Math.random();
			}
		}else{
			if (docInfo.ViewID != '') {
				var url =  this.baseUrl + 'wLoadView/' + docInfo.ViewID + "/" + docInfo.AppID + "?readviewEntries&outputformat=json&start=" + this.startCount + "&count=" + maxDocs + restrict + "&" + Math.random();
			}
			else {
				var url = this.baseUrl + 'loadView/' + docInfo.ViewName + "/" + docInfo.AppID + "?readviewEntries&outputformat=json" + restrict + "&" +Math.random() ;
			}
		}
		
		$.ajax({
			  url: url,
			  dataType: 'json',
			  async: false,
			  success: function(data) {
					var Parser = container.Parser;
					var restrictToCategory = container.restrictToCategory;
					Parser.reset(restrictToCategory);
					var responseXml = Parser.parse(data);
					
					//if ( console)
						//console.log(responseXml);
			
					var xslDoc = (new DOMParser()).parseFromString(responseXml, "text/xml");
			
					responseTextXml = ((typeof XMLSerializer!=="undefined") ? 
			           (new window.XMLSerializer()).serializeToString(xslDoc) : 
			           data.xml);
					container.oXml = xslDoc;
					var tmpCount = data["@toplevelentries"];
					container.totalDocCount = tmpCount ? tmpCount : 0;		
					if(container.totalDocCount == 0) {
						disableContentPaging(true);
					} else {
						disableContentPaging(false);
					}
					
					var newCnt = (container.startCount-1) + parseInt(objView.docCount);
					var total = container.totalDocCount;
					if(newCnt > total) { newCnt = total;}
					doc.currCount.innerHTML = newCnt;					
					
								
					
			  }
			});
		
		return;
		
	
}//--end GetXmlDocument





//==========================================================================================
//deletes selected documents
//==========================================================================================

this.DeleteSelectedEntries = function(currentOnly)
{
	if (currentOnly && this.currentEntry == "" ) {return false;} //nothing selected
	if (this.selectedEntries.length==0 && this.currentEntry == "" ) {return false;} //nothing selected

	var isRMEEnabled = docInfo.RMEEnabled;
	var docNode = "";
	var isRecNode = "";
	var isReleasedFlag = "";
	var isVerSFlag = "";
	var isVerFlag = "";
	var rmeCount = 0;
	var verCount = 0;
	var verSCount = 0;
	var noDelCount = 0;
	var isWfStarted = "";
	var isDelDisabled = "";
	var isBookmark = "";
	var ReleasedCount = 0;
	var EntriesToProcessCount = 0;
	var tmpArray = new Array();
	(this.selectedEntries.length==0 || currentOnly) ? tmpArray[0] = this.currentEntry : tmpArray = this.selectedEntries;
	var request = "";

	//if (!confirm("Are you sure you want to delete the " + tmpArray.length +  " selected document(s)?")) {return false;}
	//-------------------------------------------------------
	request = "<Request><Action>DELETESELECTED</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";

	for(var i=0; i < tmpArray.length ; i++)
	{
		docNode = this.oXml.selectSingleNode('documents/document[docid="' + tmpArray[i] +'"]');
		isRecNode = docNode.selectSingleNode('recflag');
		isReleasedFlag = docNode.selectSingleNode('statno');
		isVerFlag = docNode.selectSingleNode('verflag');
		isVerSFlag = docNode.selectSingleNode('versflag');
		isWfStarted = docNode.selectSingleNode('wfstarted');
		isDelDisabled = docNode.selectSingleNode('delflag');
		
		var elNode = docNode.selectSingleNode('bmk/img');
		isBookmark = (elNode ? (!elNode.getAttribute('src') == '') : false);
				
		if (isBookmark){
			//go ahead and delete a bookmark even if other flags exist, since bookmark is just a copy
			EntriesToProcessCount = EntriesToProcessCount + 1;
			request += "<Unid>" + tmpArray[i] + "</Unid>";
		}else if (isRMEEnabled == "1" && (isRecNode.text || isRecNode.textContent) == "1"){ //Document is being records managed, can not be deleted.
			rmeCount = rmeCount + 1;
		}else if ((isReleasedFlag!=null && (isReleasedFlag.text || isReleasedFlag.textContent) == "1") && (isVerFlag.text || isVerFlag.textContent) == "1" && (isVerSFlag.text || isVerSFlag.textContent) != "1"){//Document is Released but version controlled, user must use Retract.
			verCount = verCount + 1;
		}else if ((isReleasedFlag!=null && (isReleasedFlag.text || isReleasedFlag.textContent) == "1") && (isVerSFlag.text || isVerSFlag.textContent) == "1"){//Document is Released and strict version controlled, no delete allowed, no retract allowed.
			verSCount = verSCount + 1;
		}else if ((isWfStarted!=null && (isWfStarted.text || isWfStarted.textContent) == "1") && (isDelDisabled!=null && (isDelDisabled.text || isDelDisabled.textContent) == "1")) {
			noDelCount = noDelCount + 1; 
		}else{
			EntriesToProcessCount = EntriesToProcessCount + 1;
			request += "<Unid>" + tmpArray[i] + "</Unid>";
		} 
	}

	if(rmeCount > 0){
		alert("One or more selected documents can not be deleted because they are records managed.")
	}
	if(verCount > 0){
		alert("One or more selected documents can not be deleted because they are Released and version controlled. Use Retract instead.")
	}
	if(verSCount > 0){
		alert("One or more selected documents can not be deleted because they are Released and strict version controlled.")
	}
	if(noDelCount > 0){
		alert("One or more selected documents can not be deleted because workflow has started and deletion during workflow has been disabled.")
	}	
	if (EntriesToProcessCount == 0){
		return(false)
	}

	request += "<FolderName><![CDATA[" + docInfo.FolderName + "]]></FolderName></Request>";
	Docova.Utils.showProgressMessage("Processing request. Please wait...")
	var flag = this.SendData(request);
	(this.selectedEntries.length!=0) ? this.selectedEntries = new Array() : this.currentEntry=""; //selected/current are deleted now
	this.Refresh(true,true,true); //reload xml data with current xsl
	//-------------------------------------------------------		
	Docova.Utils.hideProgressMessage()
}


//==========================================================================================
//Undeletes the selected entries
//==========================================================================================

this.UndeleteSelectedEntries = function(currentOnly)
{
	if (currentOnly && this.currentEntry == "" ) {return false;} //nothing selected
	if (this.selectedEntries.length==0 && this.currentEntry == "" ) {return false;} //nothing selected

	var tmpArray = new Array();
	(this.selectedEntries.length==0 || currentOnly) ? tmpArray[0] = this.currentEntry : tmpArray = this.selectedEntries;
	var request = "";
	
	if (!confirm("Do you want to restore the " + tmpArray.length +  " selected document(s)?")){
		return false;
	}
	
	//-------------------------------------------------------		
	request = "<Request><Action>UNDELETESELECTED</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	for(var i=0; i < tmpArray.length ; i++)
		{
		request += "<Unid>" + tmpArray[i] + "</Unid>";
		}
	request += "<FolderName>" + docInfo.FolderName + "</FolderName></Request>";
	Docova.Utils.showProgressMessage("Processing request. Please wait...")
	var flag = this.SendData(request);
	(this.selectedEntries.length !=0 ) ? this.selectedEntries = new Array() : this.currentEntry=""; //selected/current are deleted now
	this.Refresh(true,true,true); //reload xml data with current xsl
	//-------------------------------------------------------			
	Docova.Utils.hideProgressMessage()
}

//==========================================================================================
//permanently deletes selected documents
//==========================================================================================

this.RemoveSelectedEntries = function(currentOnly)
{
	if (currentOnly && this.currentEntry == "" ) {return false;} //nothing selected
	if (this.selectedEntries.length==0 && this.currentEntry == "" ) {return false;} //nothing selected

	var tmpArray = new Array();
	(this.selectedEntries.length==0 || currentOnly) ? tmpArray[0] = this.currentEntry : tmpArray = this.selectedEntries;
	var request = "";
	
	//if (!confirm("Are you sure you want to permanently delete the " + tmpArray.length +  " selected document(s)?")) {return false;}
	
	//-------------------------------------------------------		
	request = "<Request><Action>REMOVE</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	for(var i=0; i < tmpArray.length ; i++)
		{
		request += "<Unid>" + tmpArray[i] + "</Unid>";
		}
	if (docInfo.IsAppBuilder === '1')
	{
		request += '<SourceType>' + docInfo.ViewName + '</SourceType>';
	}
	else {
		request  += '<SourceType><![CDATA[application-'+ docInfo.ViewName +']]></SourceType>';
	}
	request += "<FolderName>" + docInfo.FolderName + "</FolderName></Request>";

	Docova.Utils.showProgressMessage("Processing request. Please wait...")
	var flag = this.SendData(request);
	(this.selectedEntries.length!=0) ? this.selectedEntries = new Array() : this.currentEntry=""; //selected/current are deleted now
	this.Refresh(true,true,true); //reload xml data with current xsl
	//-------------------------------------------------------		
	Docova.Utils.hideProgressMessage()
}

//==========================================================================================
//Export to excel
//==========================================================================================
this.ExportToExcel = function(selectedOnly)
{

	if(! window.top.Docova.IsPluginAlive){
		window.top.Docova.Utils.messageBox({prompt: "DOCOVA Plugin is not running.  This functionality requires the use of the DOCOVA Plugin.", title: "Export to Excel", width: 400});
		return false;
	}	
	
	if(!this.dataTable) {return false;}
	
	if(selectedOnly && this.selectedEntries.length==0) {		
		alert("Please select at least one document for export.");
		return false;
	}
		
	var baseIndex=1;
	if(this.showSelectionMargin) 
	{
			baseIndex++; //starting index for data columns
	}
	window.top.Docova.Utils.showProgressMessage("Preparing Excel for export. Please wait...")
	var currow = 1;

	var codestr = "";
	codestr += 'string result = @"{""runstatus"": ""FAILED""}";\n';
	codestr += '/*-- start parameters passed in from calling routine --*/\n';
	codestr += 'string errormsg = "";\n';
	codestr += 'Type ExcelType = null;\n';
	codestr += 'Object ExcelApp = null;\n';	
	codestr += 'Object Workbooks = null;\n';	
	codestr += 'Object Workbook = null;\n';	
	codestr += 'Object Worksheets = null;\n';
	codestr += 'Object Worksheet = null;\n';
	codestr += 'Object Columns = null;\n';	
	codestr += 'Object Range = null;\n';
	codestr += 'ExcelType = Type.GetTypeFromProgID("Excel.Application");\n'
	codestr += 'if(ExcelType == null){ goto Cleanup;}\n';	
	codestr += 'ExcelApp = Activator.CreateInstance(ExcelType);\n'
	codestr += 'if (ExcelApp == null){\n';
	codestr += '     errormsg = "Excel has not been installed.";\n';
	codestr += '     goto Cleanup;\n';
	codestr += '}\n';	
	codestr += 'ExcelApp.GetType().InvokeMember("Visible", BindingFlags.SetProperty, null, ExcelApp, new object[]{true});\n';		
	codestr += 'Workbooks = ExcelApp.GetType().InvokeMember("Workbooks", BindingFlags.GetProperty, null, ExcelApp, new object[]{});\n';			
	codestr += 'if (Workbooks == null){\n';
	codestr += '     errormsg = "Excel workbooks not found.";\n';
	codestr += '     goto Cleanup;\n';
	codestr += '}\n';		
	codestr += 'Workbook = Workbooks.GetType().InvokeMember("Add", BindingFlags.InvokeMethod, null, Workbooks, new object[]{});\n';		
	codestr += 'if (Workbook == null){\n';
	codestr += '     errormsg = "Excel workbook could not be created.";\n';
	codestr += '     goto Cleanup;\n';
	codestr += '}\n';	
	codestr += 'Worksheets = Workbook.GetType().InvokeMember("Worksheets", BindingFlags.GetProperty, null, Workbook, new object[] {});\n';
	codestr += 'if (Worksheets == null){\n';
	codestr += '      errormsg = "Excel Worksheets not found.";\n';
	codestr += '      goto Cleanup;\n';
	codestr += '}\n';
	codestr += 'Worksheet = Worksheets.GetType().InvokeMember("Item", BindingFlags.GetProperty, null, Worksheets, new object[]{1});\n';		
	codestr += 'if (Worksheet == null){\n';
	codestr += '     errormsg = "Excel Worksheet not found.";\n';			
	codestr += '     goto Cleanup;\n';
	codestr += '}\n';

	jQuery(this.dataTable).find('> thead > tr').each(function() {	    	
	    	var curcell = 1;
			jQuery(this).children("td").each(function() {
				if ( curcell >= baseIndex ){
					var val = $(this).text().replace(/"/g, '""');
					var cellInd = (curcell - baseIndex) + 1;
					codestr += '      Range = Worksheet.GetType().InvokeMember("Cells", BindingFlags.GetProperty, null, Worksheet, new object[] { ' + currow.toString() + ',' + cellInd.toString() + '});\n';
					codestr += '      if (Range != null){\n';
					codestr += '           Range.GetType().InvokeMember("Value", BindingFlags.SetProperty, null, Range, new object[]{@"' + val + '"});\n';
					codestr += '      }\n';					
				}
				curcell ++;
			})
			currow ++;
	});
	
	 jQuery(this.dataTable).find('> tbody > tr').each(function() {
		    var inc = true;
		 	if ( selectedOnly)
			{
				if ($(this).attr('ischecked')=="true") {
					 inc  = true;
				}else{
					inc = false;
				}
			}else{
				inc = true;
			}
			
	    	var curcell = 1;
	    	
	    	if ( inc )
	    	{
	    		$(this).children("td").each(function() {
				
	    			if ( curcell >= baseIndex ){
	    				var val = $(this).text().replace(/"/g, '""');
	    				var cellInd = (curcell - baseIndex) + 1;
						codestr += '      Range = Worksheet.GetType().InvokeMember("Cells", BindingFlags.GetProperty, null, Worksheet, new object[] { ' + currow.toString() + ',' + cellInd.toString() + '});\n';
						codestr += '      if (Range != null){\n';
						codestr += '           Range.GetType().InvokeMember("Value", BindingFlags.SetProperty, null, Range, new object[]{@"' + val + '"});\n';
						codestr += '      }\n';						    									
	    			}
	    			curcell ++;
	    		})
	    		currow ++;
	    	}
	});	
	 
	codestr += 'Columns = Worksheet.GetType().InvokeMember("Columns", BindingFlags.GetProperty, null, Worksheet, new object[]{});\n';
	codestr += 'if(Columns != null){\n';
	codestr += '   Columns.GetType().InvokeMember("AutoFit", BindingFlags.InvokeMethod, null, Columns, new object[]{});\n';
	codestr += '}\n';
	codestr += 'result = @"{""runstatus"": ""SUCCESS""}";\n';			
	codestr += 'Cleanup:\n';
	codestr += '    if (errormsg != ""){\n';
	codestr += '        result = @"{""runstatus"": ""FAILURE"", ""data"": """ + System.Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes(errormsg)) + @"""}";\n';
	codestr += '     }\n';	
	codestr += '    Range = null;\n';	
	codestr += '    Columns = null;\n';
	codestr += '    Worksheet = null;\n';
	codestr += '    Worksheets = null;\n';		
	codestr += '    Workbook = null;\n';
	codestr += '    Workbooks = null;\n';	
	codestr += '    ExcelApp = null;\n';	
	codestr += '    ExcelType = null;\n';
	codestr += 'return result;\n';        	
	 
	  var retval = window.top.DocovaExtensions.executeCode(codestr, false, true);
	 	if ( retval.status == "SUCCESS" ){
	 			 window.top.Docova.Utils.hideProgressMessage();
	 	}else{
	 		 	window.top.Docova.Utils.hideProgressMessage();
	 			if ( retval.error && retval.error != "")
	 				alert ( retval.status + "\r\n" + retval.error );
	 			else
	 				alert ("Unable to export data to Excel.  Is the Docova Plugin running?");
	 	}
	
}//--end ExportToExcel


// ----------------------------------------------------------- helper methods ----------------------------------------------------------------

//==========================================================================================
//create  xsl document object based on the column properties 
//==========================================================================================

this.GetXslDocument = function()
{
	this.isCategorized=false;
	
	
	
	if ( this.isFTSearch){
		var xslText = this.GetXslStringFlat();
	
	}else if(this.isSummary) 
	{ 
		var xslText = this.GetXslStringSummary();
	}
	else if ( this.isThumbnails)
	{
		var xslText = this.GetXslStringThumbnails();
	}
	else
	{
		for(var k = 0; k < this.columns.length; k++) //check if any of the columns is categorized
		{
			if(this.columns[k].isCategorized)  //at least one is categorized - return grouped xsl
			{
				this.isCategorized=true;
				var xslText = this.GetXslStringCat();
			}
		}  
		if(! this.isCategorized){
			//no categorized columns, flat view
			var xslText = this.GetXslStringFlat();			
		}
	}

	var xslDoc = (new DOMParser()).parseFromString(xslText, "text/xml");
	if(Sarissa.getParseErrorText(xslDoc) != Sarissa.PARSED_OK){  
  		var errorText = Sarissa.getParseErrorText(xslDoc);
		alert("Error parsing xsl: " + errorText);
		xslDoc = null;
		return;
	}		
	this.oXsl = xslDoc;
	xslDoc = null;
}

//==========================================================================================
//build a complete flat structure xsl based on the column properties 
//==========================================================================================

this.GetXslStringFlat = function()
{
     //----- xsl header -----
	var xlsStyle = '<?xml version="1.0"?>';
	xlsStyle += '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >';
	//----- view header -----
	xlsStyle += '<xsl:template match="/">';
	xlsStyle += '<table class="listtable" style="table-layout: fixed; width:100%;" id="' + this.idPrefix + 'DataTable" cellspacing="0" cellpadding="0" border="0">';
	xlsStyle += '<thead><tr class="ui-widget-header ui-state-hover" isHeader="true">'; 
	 //----- selection margin header cell -----
	if(this.showSelectionMargin) 
	{
		xlsStyle += '<td class="listselheader" align="center" width="30" hasProperties="true">';
		xlsStyle += '<img class="listviewrefresh" id="listviewrefresh"  alt="Refresh" src="' + this.imgPath ;
		xlsStyle +=  '/viewRefreshGreen.gif?OpenImageResource" align="top"/>';
		xlsStyle += '</td>';
	}
     //----- write header cells html and check if view has totals -----	
	var hasTotals=false; //predicate used to add totals at the bottom of the view
	for(var k = 0; k < this.columns.length; k++) //get header cell html from each column object
	{
		xlsStyle += this.columns[k].GetHeaderCellHTML();
		if(this.columns[k].totalType != "0") {hasTotals = true;} //at least one total
	}   
	
	xlsStyle += '<td class="listheader" width="25">&#160;</td>';
	
	xlsStyle += '</tr></thead><tbody>';
	//----- process documents -----
	xlsStyle += '<xsl:apply-templates select="documents/document">';
	
	var customSortKeys = "";
	var defaultSortKeys = "";
	var column;

     //----- add sort keys -----
	for(var k = 0; k < this.columns.length; k++) //get a list of xsl sort keys
		{
			column = this.columns[k];
			(column.GetSortType() == "custom") ? customSortKeys += column.GetXslSortKey() : defaultSortKeys += column.GetXslSortKey();
		}  
		
		if(this.isFTSearch && customSortKeys == "" ) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:sort select="ftscore/@val" data-type="number" order = "descending"/>'; 
		}

	xlsStyle += customSortKeys + defaultSortKeys; //custom sort keys are applied before default
	xlsStyle += '</xsl:apply-templates>';
	
     //----- optional bottom row containing column totals -----
	if(hasTotals) 
	{
		xlsStyle += '<tr class="listrow"><xsl:attribute name="isTotal"><xsl:value-of select="true()" /></xsl:attribute>';
		xlsStyle += '<xsl:attribute name="id">' + this.idPrefix + 'ViewTotal</xsl:attribute>';
		if(this.showSelectionMargin) 
			{
			xlsStyle += '<td class="listsel" width="28px">&#160;</td>';
			}
		for(var k = 0; k < this.columns.length; k++) //get data cell html from each column object
		{
			xlsStyle += this.columns[k].GetTotalCellHTML();
		}   
		xlsStyle += '<td class="listitem">&#160;</td>';
		
		xlsStyle += '</tr>';
	}
	
	xlsStyle += '</tbody></table>';
	xlsStyle += '</xsl:template>';

     //----- document row template -----
	xlsStyle += '<xsl:template match="document">';
	if(this.isFTSearch) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:variable name="ftScoreColor" select="(10 - floor(ftscore div 10)) * 25"/>'; 
		}
		
	xlsStyle += '<tr class="listrow">';
	xlsStyle += '<xsl:attribute name="dockey"><xsl:value-of select="dockey" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarykey"><xsl:value-of select="libid" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarynsf"><xsl:value-of select="libnsf" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="id"><xsl:value-of select="docid" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="isRecord"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="hasResponses"><xsl:value-of select="./@hasresp" /></xsl:attribute>'
	xlsStyle += '<xsl:attribute name="hasProperties"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="responses"><xsl:value-of select="count(responses//respdoc)"/></xsl:attribute>' ; //position number

	xlsStyle += '<xsl:attribute name="pos"><xsl:value-of select="position()"/></xsl:attribute>' ; //position number

	if(this.showSelectionMargin) 
	{
		xlsStyle += '<td class="listsel" width="28px" align="center">';
		xlsStyle += '<input type="checkbox" id="ExportSelectCb" onclick="return false;" ';
		xlsStyle += 'style="height: 14px; width:14px;  margin: 0px; padding:0px;border:0px;"/></td>';
	}

	for(var k = 0; k < this.columns.length; k++) //get data cell html from each column object
	{
		if (this.isFTSearch && k == 0) 
		{
			if ( this.columns[k].showResponses )
				xlsStyle += this.columns[k].GetDataCellHTML("", true, true )
			else
				xlsStyle += this.columns[k].GetDataCellHTML("", true);
		}else{
			if ( this.columns[k].showResponses )
				xlsStyle += this.columns[k].GetDataCellHTML("", false, true )
			else
				xlsStyle += this.columns[k].GetDataCellHTML("");
		}
	}   
	
	xlsStyle += '<td class="listitem">&#160;</td>';
	

	xlsStyle += '	</tr>';
	xlsStyle += '<xsl:apply-templates select="responses/respdoc"/>';
	
	xlsStyle += '</xsl:template>';
	
	
	xlsStyle += '<xsl:template match="respdoc">'
		xlsStyle += '<tr class="listrow" isRecord="true">';
		xlsStyle += '<xsl:attribute name="pos"><xsl:value-of select="position()"/></xsl:attribute>' ; //position number
		xlsStyle += '<xsl:attribute name="id"><xsl:value-of select="dockey"/></xsl:attribute>';
		xlsStyle += '<xsl:attribute name="isresponse">1</xsl:attribute>';
		xlsStyle += '<xsl:if test="count(responses/respdoc) &gt; 0">';
		xlsStyle += '<xsl:attribute name="hasresponses"><xsl:value-of select="count(responses)"/></xsl:attribute>';
		xlsStyle += "</xsl:if>"
		xlsStyle += '<xsl:attribute name="responses"><xsl:value-of select="count(responses//respdoc)"/></xsl:attribute>' ; //position number

		if(this.showSelectionMargin) 
		{
			xlsStyle += '<td class="listsel" width="28px" align="center">';
			xlsStyle += '<input type="checkbox" id="ExportSelectCb" onclick="return false;" ';
			xlsStyle += 'style="height: 14px; width:14px;  margin: 0px; padding:0px;border:0px;"/></td>';
		}
		
		var ignorecol = false;
		var respcol = -1;
		
		var colspan;
		if ( this.responseColspan > 0) {
			colspan = this.responseColspan;
			
		}
		for(var k = 0; k < this.columns.length; k++) //get data cell html from each column object
		{
				if (  this.columns[k].isCategorized ){
					xlsStyle += '<td class="listitem"/>'
				}else
				{
					if ( this.columns[k].showResponses) {
						if ( ! colspan)
							colspan = this.columns.length - k;
						else{
							
							if ( colspan >  this.columns.length - k)
								colspan = this.columns.length - k
							
						}
							
						xlsStyle += this.columns[k].GetDataCellHTML("", false, true, colspan )	
						respcol = k;
					} else {
						
						if ( respcol >= 0 && k > respcol && k <= (respcol + (colspan-1)))
							ignorecol = true
						else
							ignorecol = false;
						
						if ( !ignorecol)
							xlsStyle += this.columns[k].GetDataCellHTML("" )
						
							
					}
				}
		
			
		}  
		
		if(!(respcol && respcol== this.columns.length) )
		{
			xlsStyle += '<td class="listitem">&#160;</td>';
		}
	    xlsStyle += '</tr>';
	    xlsStyle += '<xsl:apply-templates select="responses/respdoc"/>';
	    xlsStyle += '</xsl:template>';
		
		
		
	
	xlsStyle += '</xsl:stylesheet>';
	return xlsStyle;
}

//==========================================================================================
//build a document summary structure xsl 
//==========================================================================================

this.GetXslStringSummary= function()
{
     //----- xsl header -----
	var xlsStyle = '<?xml version="1.0"?>';
	xlsStyle += '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >';
	//----- view header -----
	xlsStyle += '<xsl:template match="/">';
	xlsStyle += '<table class="listtable" style="table-layout: fixed; width:100%;" id="' + this.idPrefix + 'DataTable" cellspacing="0" cellpadding="0" border="0">';
	xlsStyle += '<thead><tr class="ui-widget-header ui-state-hover" isHeader="true">'; 
	 //----- selection margin header cell -----
	if(this.showSelectionMargin) 
	{
		xlsStyle += '<td class="listselheader" align="center" width="30" hasProperties="true">';
		xlsStyle += '<img class="listviewrefresh" id="listviewrefresh"  alt="Refresh" src="' + this.imgPath ;
		xlsStyle +=  '/viewRefreshGreen.gif?OpenImageResource" align="top"/>';
		xlsStyle += '</td>';
	}
     //----- write header cell html and check if view has totals -----	

	xlsStyle += '<td class="listheader">&#160;</td>';

	xlsStyle += '</tr></thead><tbody>';
	//----- process documents -----
	xlsStyle += '<xsl:apply-templates select="documents/document">';
	
	var customSortKeys = "";
	var defaultSortKeys = "";
	var column;

     //----- add sort keys -----
     
	if(this.isFTSearch) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:sort select="ftscore/@val" data-type="number" order = "descending"/>'; 
		}
     xlsStyle += this.summarySortXsl;
	xlsStyle += defaultSortKeys; //custom sort keys are applied before default
	xlsStyle += '</xsl:apply-templates>';
	
	xlsStyle += '</tbody></table>';
	xlsStyle += '</xsl:template>';

     //----- document row template -----
	xlsStyle += '<xsl:template match="document">';
	if(this.isFTSearch) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:variable name="ftScoreColor" select="(10 - floor(ftscore div 10)) * 25"/>'; 
		}
		
	xlsStyle += '<tr class="listrow">';
	xlsStyle += '<xsl:attribute name="dockey"><xsl:value-of select="dockey" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarykey"><xsl:value-of select="libid" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarynsf"><xsl:value-of select="libnsf" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="id"><xsl:value-of select="docid" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="isRecord"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="hasProperties"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="pos"><xsl:value-of select="position()"/></xsl:attribute>' ; //position number

	if(this.showSelectionMargin) 
	{
		xlsStyle += '<td class="listsel" width="28px" align="center">';
		xlsStyle += '<input type="checkbox" id="ExportSelectCb" onclick="return false;" ';
		//xlsStyle += '<input type="checkbox" ';
		xlsStyle += 'style="height: 14px; width:14px;  margin: 0px; padding:0px;border:0px;"/></td>';
	}

	xlsStyle += '<td class="listitem"';
	if(this.isFTSearch)
		{
			xlsStyle +=  ' style="border-left: solid 10px rgb({$ftScoreColor},{$ftScoreColor},{$ftScoreColor});"';
		}
	xlsStyle += '>';
	xlsStyle += this.summaryXsl;
	xlsStyle += '</td>';
	xlsStyle += '</tr>';
	xlsStyle += '</xsl:template>';
	xlsStyle += '</xsl:stylesheet>';
	return xlsStyle;
}

this.GetThumbnailSortOptions = function(){
	
	var xlsStyle = "";
	xlsStyle += '<select onchange="ViewSortThumbnails(this.options[this.selectedIndex].value)">';
	xlsStyle += '<option value="blank">Select</option>';
	if ( this.thumbnailSort == "Filename")
		xlsStyle += '<option value="Filename" SELECTED="true">Filename</option>';
	else
		xlsStyle += '<option value="Filename">Filename</option>';
	
	if ( this.thumbnailSort == "Author")
		xlsStyle += '<option value="Author" SELECTED="true">Author</option>';
	else
		xlsStyle += '<option value="Author">Author</option>';	
	
	if ( this.thumbnailSort == "Date")
		xlsStyle += '<option value="Date" SELECTED="true">Date</option>';
	else
		xlsStyle += '<option value="Date">Date</option>';
	
	xlsStyle += '</select>';
	return xlsStyle;
}


this.GetXslStringThumbnails= function()
{
	
     //----- xsl header -----
	var xlsStyle = '<?xml version="1.0"?>';
	xlsStyle += '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >';
	//----- view header -----
	xlsStyle += '<xsl:template match="/">';
	//xlsStyle += '<link rel="stylesheet" title="Standard" href="' + this.baseUrl + 'contentflow.css?openPage" type="text/css" media="screen" />';
	xlsStyle += '<table class="listtable" style="table-layout: fixed; width:100%;" id="' + this.idPrefix + 'DataTable" cellspacing="0" cellpadding="0" border="0">';
	xlsStyle += '<thead><tr class="ui-widget-header ui-state-hover" isHeader="true">'; 
	 //----- selection margin header cell -----
	
     //----- write header cell html and check if view has totals -----	

	xlsStyle += '<td align="center" class="listheader" width="30px">';
	xlsStyle += '<img class="listviewrefresh" id="listviewrefresh" alt="Refresh" src="' + this.imgPath ;
	xlsStyle +=  '/viewRefreshGreen.gif?OpenImageResource" align="top"/></td><td class="listheader">';
	xlsStyle +=  'Sort By : ';
	xlsStyle +=  this.GetThumbnailSortOptions();
	xlsStyle += '</td>';

	xlsStyle += '</tr></thead><tbody><tr><td colspan="2">';
	//----- process documents -----
	xlsStyle += '<xsl:apply-templates select="documents/document/FILES/FILE">';
	
	var customSortKeys = "";
	var defaultSortKeys = "";
	var column;

     //----- add sort keys -----   
	if(this.isFTSearch) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:sort select="ftscore/@val" data-type="number" order = "descending"/>'; 
		}
    xlsStyle += this.GetThumbnailSort();
	xlsStyle += defaultSortKeys; //custom sort keys are applied before default
	xlsStyle += '</xsl:apply-templates>';
	
	xlsStyle += '</td></tr></tbody></table>';
	xlsStyle += '</xsl:template>';

     //----- document row template -----
	xlsStyle += '<xsl:template match="FILE">';
	if(this.isFTSearch) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:variable name="ftScoreColor" select="(10 - floor(ftscore div 10)) * 25"/>'; 
		}
	xlsStyle += '<xsl:variable name="alt">';
	xlsStyle +=	'<xsl:text>Name: </xsl:text>';
	xlsStyle +=	'<xsl:value-of select="name" />';
	xlsStyle +=	'<xsl:text>\nAuthor: </xsl:text>';
	xlsStyle +=	'<xsl:value-of select="author" />';
	xlsStyle +=	'<xsl:text>\nDate: </xsl:text>';
	xlsStyle +=	'<xsl:value-of select="@origdate" />';
	xlsStyle += '</xsl:variable>';
	
	
	xlsStyle += '<span class="thumbnail">';
	xlsStyle += '<xsl:attribute name="dockey"><xsl:value-of select="../../dockey" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarykey"><xsl:value-of select="../../libid" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarynsf"><xsl:value-of select="../../libnsf" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="id"><xsl:value-of select="../../docid" />~<xsl:value-of select="name" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="isRecord"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="filename"><xsl:value-of select="name" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="hasProperties"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="pos"><xsl:value-of select="position()"/></xsl:attribute>' ; //position number

	
	xlsStyle += '<img class="shadow" width="' + this.thumbnailWidth +'" height="' + this.thumbnailHeight + '"';
	
	xlsStyle += '>';
	
	xlsStyle += '<xsl:choose>';
	xlsStyle += '<xsl:when test ="contains(.,\'~dthmb.bmp\')">';
	xlsStyle += '<xsl:attribute name="src">';
	xlsStyle += '<xsl:value-of select="src" />';
	xlsStyle += '</xsl:attribute>';
	xlsStyle += '</xsl:when>';
	xlsStyle += '<xsl:otherwise>';
	xlsStyle += '<xsl:attribute name="src">' + this.imgPath + '/nothumb.gif?openImageResource</xsl:attribute>';
	xlsStyle += '</xsl:otherwise>';
	xlsStyle += '</xsl:choose>';
	xlsStyle += '<xsl:attribute name="alt"><xsl:value-of select="$alt"/></xsl:attribute>';
	xlsStyle += '</img>';
	
	
	xlsStyle += '</span>';
	xlsStyle += '</xsl:template>';
	xlsStyle += '</xsl:stylesheet>';
	
	
	return xlsStyle;
}


//==========================================================================================
//build a complete groupped xsl based on the column properties  (groupped using variation of Muench method)
//==========================================================================================

this.GetXslStringCat = function()
{
     //----- xsl header -----
	var xlsStyle = '<?xml version="1.0"?>';
	xlsStyle += '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >';
	
	//------- build the grouping key strings  for categorizing columns-------
	var catKeys = new Array(); //strings holding the names of the grouping nodes
	var catIndices = new Array(); //stores indices of categorized columns
	var catKeyString=""; //tmp string to hold the xsl grouping key
	var catKeyCount=0;
	var hasTotals=false; //predicate used to add totals at the bottom of each category
	var totalhidden = 0;
	var firstTotalColumn = null;
	//iterate through all the columns and create the grouping keys for the ones that are categorized
	for(var k = 0; k < this.columns.length; k++)
	{
		if ( this.columns[k].isHidden )
			totalhidden++;

		if(this.columns[k].isCategorized)
		{
			if(catKeyCount == 0) //first key - use the xmlNode name
			{
				catKeyString=this.columns[k].xmlNodeName;
				catKeys[catKeyCount] = catKeyString; //add the key text string to the key array
				xlsStyle += '<xsl:key name="cat' + catKeyCount + '" match="document" use="' + catKeyString + '"/>'
				catIndices[catKeyCount] = k; //array storing indices to categorized columns
				catKeyCount++;
			}
			else //consecutive key - concatenate all previous and current grouping nodes
			{
				catKeyString = catKeyString + ',\' \',' + this.columns[k].xmlNodeName;
				catKeys[catKeyCount] = 'concat(' + catKeyString + ')';
				catIndices[catKeyCount] = k;
				xlsStyle += '<xsl:key name="cat' + catKeyCount + '" match="document" use="' + catKeys[catKeyCount] + '"/>'
				catKeyCount++;
			}
		}else if( this.columns[k].totalType != "0" ) {
				hasTotals = true;
				if ( firstTotalColumn == null)
					firstTotalColumn = k;
		}
	}   

     //----- view header -----
	xlsStyle += '<xsl:template match="/">';

	//--------- add view table header ------------
	xlsStyle += '<table class="listtable" style="table-layout: fixed; width:100%;" id="' + this.idPrefix + 'DataTable" cellspacing="0" cellpadding="0" border="0">';
	xlsStyle += '<thead><tr class="ui-widget-header" isHeader="true">'; 
	
	if(this.showSelectionMargin) //view has a selection margin
	{
		xlsStyle += '<td class="listselheader" align="center" width="30" hasProperties="true">';
		xlsStyle += '<img class="listviewrefresh" id="listviewrefresh" alt="Refresh" src="' + this.imgPath ;
		xlsStyle +=  '/viewRefreshGreen.gif?OpenImageResource" align="top"/>';
		xlsStyle += '</td>';
	}

	for(var k = 0; k < this.columns.length; k++) //get header cell html from each column object
	{
		xlsStyle += this.columns[k].GetHeaderCellHTML();
	}   
	xlsStyle += '<td class="listheader" width="25">&#160;</td>';
	
	xlsStyle += '</tr></thead><tbody>';
	
	//--------- add grouping recursion using grouping keys strings-------
	
	catKeyCount=0; //categorized column counter
	
	//------ nested for-each for each categorized column --------
	for(var i = 0; i < catKeys.length; i++) 
	{
		if(i==0) //top level category node set is selected differently from levels below
		{
			xlsStyle += '<xsl:for-each select="//document[generate-id(.)=generate-id(key(\'cat' + i +  '\', ' + catKeys[i] + ')[1])]">';
		}
		else //lower level categories are subsets of the ones above
		{
			xlsStyle += '<xsl:for-each select="$cat' + (i-1) + 'nodes[generate-id(.)=generate-id(key(\'cat' + i +  '\', ' + catKeys[i] + ')[1])]">';
		}
		xlsStyle += this.columns[catIndices[i]].GetXslSortKey(); //get sort key for current category
		xlsStyle += '<xsl:variable name="cat' + i + 'nodes" select="key(\'cat' + i +  '\', ' + catKeys[i] + ')"/>';
		xlsStyle += '<xsl:variable name="pos' + i + '" select="position()"/>'; //stores position value of the current context node
		
		// ------ add category position values to be used as category ids ------
		if(i==0) //top level category
			{
				var catPos= '<xsl:value-of select="position()"/>';
			}
		else //lower levels
			{
				// --- concatenate previous positions to have 1.1.2.... etc----
				var catPos='<xsl:value-of select="concat(';
				for(var pCtr=0; pCtr < i; pCtr++)
				{
				catPos += '$pos' + pCtr + ',\'.\',';
				}
				// ------ currrent node position added at the end -----
				catPos +='position())"/>'; 
			}
		
		//----- add category table row -------------
		xlsStyle += '<tr class="listrow"><xsl:attribute name="isCategory"><xsl:value-of select="true()" /></xsl:attribute>';
		//------ category row id contains the positionnumber -----
		xlsStyle += '<xsl:attribute name="id">' + this.idPrefix + 'CAT' + catPos + '</xsl:attribute>';  
		//----- just a position number ----
		xlsStyle += '<xsl:attribute name="pos">' + catPos + '</xsl:attribute>' ; 
		 //----- add selection margin cell ----
		if(this.showSelectionMargin)
		{
			xlsStyle += '<td class="listsel" width="28px">&#160;</td>'; //blank cell since category has no checkbox
		}
		
		var insertatend = "";
		var hascat = false;
		var colsbeforecat = 0;
		var totalcolcount = firstTotalColumn != null ? firstTotalColumn+1 : this.columns.length+1 ;
		
		if ( this.showSelectionMargin){
			colsbeforecat=1;
			totalcolcount++;
		}

		//--- display all relevant cells in the category row ---	
		for(var k = 0; k < this.columns.length; k++) 
		{
			if(catIndices[i] == k){ //categorized column's title cell
					var cspan =  totalcolcount - totalhidden  - colsbeforecat-1;
					
					xlsStyle += (this.columns[k].isFrozen && !this.disableFreeze)? '<td class="listcatfr" colspan="' + cspan + '" width="' + this.columns[k].width + '" ' :  '<td class="listcatfr" colspan="' + cspan + '" width="' + this.columns[k].width + '"';
					xlsStyle += ' style="' + this.columns[k].GetCustomStyleString() + '">';
					xlsStyle += '<xsl:attribute name="isCategoryHead"><xsl:value-of select="true()" /></xsl:attribute>';
					xlsStyle += '<xsl:attribute name="hasProperties"><xsl:value-of select="true()" /></xsl:attribute>';
					xlsStyle += '<div style="float: left;">' 
						xlsStyle += '<img class="listexpandericon" style="margin: 2px 2px 2px 0px; border:0px;" src="' + this.imgPath + 'cat-collapse.gif?OpenImageResource" align="top"/>';
					xlsStyle += '</div>'
				
					if ( this.columns[k].dataType == "html" ) {
						xlsStyle += '<xsl:value-of select="' + this.columns[k].xmlNodeName + '"/></td>';				
					}else{
						xlsStyle += '<xsl:value-of select="' + this.columns[k].xmlNodeName + '"/></td>';
					}
			}else if(this.columns[k].totalType != "0"){ // subtotal		
				if ( k < catIndices[i] )
					colsbeforecat++;		
				hasTotals=true; //used to indicate that totals should be added at the bottom later on
				//---- totals are displayed differently before and after the category column ---
				catIndices[i] > k ? subtotalClass = "subtotal" : subtotalClass = "subtotalcat"; 
		
				nodeList = '$cat' + i + 'nodes/' + this.columns[k].xmlNodeName;
				xlsStyle += this.columns[k].GetTotalCellHTML(nodeList, subtotalClass);
			}else{ //not a category heading or total - display blank cell
				if ( k < catIndices[i] || ( k >= firstTotalColumn && hasTotals) ){
					if(this.columns[k].isHidden == "1"){
						xlsStyle += '<td class="listitem" width="0px" style="display:none;">&#160;</td>';	
					}else{
						if (  k < catIndices[i] )
							colsbeforecat++;
						xlsStyle += (this.columns[k].isFrozen && !this.disableFreeze)? '<td class="listitemfr" width=' + this.columns[k].width + 'px style="' :  '<td class="listitem" width="' + this.columns[k].width + '" style="';
						xlsStyle += catIndices[i] > k ? this.columns[k].GetCustomStyleString("", "") : this.columns[k].GetCustomStyleString("", this.categoryBorderStyle);
						xlsStyle += '">&#160;</td>';
					}
				}
			}
		}
		xlsStyle += '<td class="listitem" style="' + this.categoryBorderStyle + '">&#160;</td>';
		
		xlsStyle += '</tr>';
	}
	//----- process data rows -----
	xlsStyle += '<xsl:for-each select="$cat' + (i-1) + 'nodes">'; 

	//------- data rows sort keys first
	var customSortKeys = "";
	var defaultSortKeys = "";
	var column;

	
		
	for(var k = 0; k < this.columns.length; k++) //get a list of xsl sort keys - same as in flat view
		{
			column = this.columns[k];
			(column.GetSortType() == "custom") ? customSortKeys += column.GetXslSortKey() : defaultSortKeys += column.GetXslSortKey();
		}  
		
	if(this.isFTSearch && customSortKeys == "" ) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:sort select="ftscore/@val" data-type = "number" order = "descending"/>'; 
		}
		
	xlsStyle += customSortKeys + defaultSortKeys; //custom sort keys are applied before default

	//------- data rows ----------
	if(this.isFTSearch) // if ft searching, sort by score first
		{
			xlsStyle += '<xsl:variable name="ftScoreColor" select="(10 - floor(ftscore div 10)) * 25"/>'; 
		}
	xlsStyle += '<tr class="listrow">';
	xlsStyle += '<xsl:attribute name="dockey"><xsl:value-of select="dockey" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarykey"><xsl:value-of select="libid" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="librarynsf"><xsl:value-of select="libnsf" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="id"><xsl:value-of select="dockey" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="isRecord"><xsl:value-of select="true()" /></xsl:attribute>';
	xlsStyle += '<xsl:attribute name="responses"><xsl:value-of select="count(responses//respdoc)"/></xsl:attribute>'
	xlsStyle += '<xsl:attribute name="pos">' + catPos + '.<xsl:value-of select="position()"/></xsl:attribute>' ; //position number
	xlsStyle += '<xsl:attribute name="hasProperties"><xsl:value-of select="true()" /></xsl:attribute>';
	
	if(this.showSelectionMargin) 
	{
		xlsStyle += '<td class="listsel" width="28px" align="center">';
		xlsStyle += '<input type="checkbox" id="ExportSelectCb" onclick="return false;" ';
		//xlsStyle += '<input type="checkbox" ';
		xlsStyle += 'style="height: 14px; width:14px;  margin: 0px; padding:0px;border:0px;"/></td>';
	}

	for(var k = 0; k < this.columns.length; k++) //get data cell html from each column object
	{
		if (this.isFTSearch && k == 0) 
		{
			if ( this.columns[k].showResponses )
				xlsStyle += this.columns[k].GetDataCellHTML("", true, true )
			else
				xlsStyle += this.columns[k].GetDataCellHTML("", true);
		}else{
			if ( this.columns[k].showResponses )
				xlsStyle += this.columns[k].GetDataCellHTML("", false, true )
			else
				xlsStyle += this.columns[k].GetDataCellHTML("");
		}
		
		
	}   
	
	xlsStyle += '<td class="listitem">&#160;</td>';
		
	
	xlsStyle += '</tr>'; // close data row	
	
	xlsStyle += '<xsl:apply-templates select="responses/respdoc"/>';
	
	
	//-------------------------------------------------
	for(var i = catKeys.length-1; i >= 0; i-- ) //close all "for-each" starting from the lowest level one
	{
		//----- total position value matches the corresponding category -----
		catPos=(i==0)? '<xsl:value-of select="$pos0"/>' : '<xsl:value-of select="concat($pos' + (i-1) + ',\'.\', $pos' + i + ')" />'; 
	
		if(hasTotals)
		{
			//----- add category subtotal row at the bottom of the category -------------
			xlsStyle += '<xsl:if test="position()=last()">';
			xlsStyle += '<tr class="listrow"><xsl:attribute name="isSubtotal"><xsl:value-of select="true()" /></xsl:attribute>';
			xlsStyle += '<xsl:attribute name="id">' + this.idPrefix + 'ST' + catPos + '</xsl:attribute>';
			xlsStyle += '<xsl:attribute name="pos">' + catPos + '</xsl:attribute>' ; //position number
			if(this.showSelectionMargin) 
				{
				xlsStyle += '<td class="listsel" width="28px">&#160;</td>';
				}
				
			for(var k = 0; k < this.columns.length; k++) 
				{
					var nodeList = '$cat' + i + 'nodes/' + this.columns[k].xmlNodeName;
					xlsStyle += this.columns[k].GetTotalCellHTML(nodeList, "subtotal");
				}
				
				xlsStyle += '<td class="listitem">&#160;</td>';
					
			xlsStyle += '</tr></xsl:if>';
		}
	
		xlsStyle += '</xsl:for-each>'
	} 

	//----- remining top level for-each ----
	xlsStyle += '</xsl:for-each>'; 

	//-------- add view grand totals at the bottom ------
	if(hasTotals) 
	{
		xlsStyle += '<tr class="listrow"><xsl:attribute name="isTotal"><xsl:value-of select="true()" /></xsl:attribute>';
		xlsStyle += '<xsl:attribute name="id">' + this.idPrefix + 'ViewTotal</xsl:attribute>';
		if(this.showSelectionMargin) 
			{
			xlsStyle += '<td class="listsel" width="28px">&#160;</td>';
			}

		for(var k = 0; k < this.columns.length; k++) //get data cell html from each column object
			{
				xlsStyle += this.columns[k].GetTotalCellHTML();
			}  

			xlsStyle += '<td class="listitem">&#160;</td>';
			
			xlsStyle += '</tr>';
	}

	//----- close everything ------
	xlsStyle += '</tbody></table>';
	xlsStyle += '</xsl:template>';
	
	
	xlsStyle += '<xsl:template match="respdoc">'
		xlsStyle += '<tr class="listrow" isRecord="true">';
		xlsStyle += '<xsl:attribute name="pos"><xsl:value-of select="position()"/></xsl:attribute>' ; //position number
		xlsStyle += '<xsl:attribute name="id"><xsl:value-of select="dockey"/></xsl:attribute>';
		xlsStyle += '<xsl:attribute name="isresponse">1</xsl:attribute>';
		xlsStyle += '<xsl:if test="count(responses/respdoc) &gt; 0">';
		xlsStyle += '<xsl:attribute name="hasresponses"><xsl:value-of select="count(responses)"/></xsl:attribute>';
		xlsStyle += "</xsl:if>"
		xlsStyle += '<xsl:attribute name="responses"><xsl:value-of select="count(responses//respdoc)"/></xsl:attribute>' ; //position number

		if(this.showSelectionMargin) 
		{
			xlsStyle += '<td class="listsel" width="28px" align="center">';
			xlsStyle += '<input type="checkbox" id="ExportSelectCb" onclick="return false;" ';
			xlsStyle += 'style="height: 14px; width:14px;  margin: 0px; padding:0px;border:0px;"/></td>';
		}
		
		var ignorecol = false;
		var respcol ;
		
		var colspan;
		if ( this.responseColspan > 0) {
			colspan = this.responseColspan;
			
		}
		for(var k = 0; k < this.columns.length; k++) //get data cell html from each column object
		{
				if (  this.columns[k].isCategorized ){
					xlsStyle += '<td class="listitem"/>'
				}else
				{
					if ( this.columns[k].showResponses) {
						if ( ! colspan)
							colspan = this.columns.length - k;
						else{
							
							if ( colspan >  this.columns.length - k)
								colspan = this.columns.length - k
							
						}
							
						xlsStyle += this.columns[k].GetDataCellHTML("", false, true, colspan )	
						respcol = k;
					} else {
						
						if ( respcol && k > respcol && k <= (respcol + (colspan-1)))
							ignorecol = true
						else
							ignorecol = false;
						
						if ( !ignorecol)
							xlsStyle += this.columns[k].GetDataCellHTML("" )
						
							
					}
				}
		
			
		}  
		
		if( !(respcol && respcol== this.columns.length) )
		{
			xlsStyle += '<td class="listitem">&#160;</td>';
		}
	    xlsStyle += '</tr>';
	    xlsStyle += '<xsl:apply-templates select="responses/respdoc"/>';
	    xlsStyle += '</xsl:template>';
		
	
	
	
	

	xlsStyle += '</xsl:stylesheet>';
	return xlsStyle;
}

//==========================================================================================
//build a complete FT search xml request and request teh search results
//==========================================================================================

this.DoFTSearch = function(searchQuery, searchScope, libraryList, maxResults, searchArchive)
{
	//searchScope is FOLDER | TREE | LIBRARY | GLOBAL default is FOLDER
	//library list is required for global searches
	
	if(!searchQuery || (searchScope == "GLOBAL" && !libraryList)) {return false;}
//	if(this.isXmlDataRequest) // build xml request
//	{
//	var searchXml = "";
//	searchXml += "<Action>";
//	searchXml += (searchScope)? searchScope.toUpperCase() : "FOLDER";
//	searchXml += "SEARCH</Action>";
//	searchXml += (libraryList)? "<libraries>" + libraryList + "</libraries>" : "";
//	searchXml += "<ftsearch>";
//	searchXml += "<query><![CDATA[" + searchQuery + "]]></query>";
//	searchXml += "<maxresults>" ;
//	searchXml += (maxResults)? maxResults : "0";
//	searchXml += "</maxresults>";
//	searchXml += "<searcharchive>" +  searchArchive +  "</searcharchive>";
//	searchXml += "</ftsearch>";
//	
//	this.FTSearchParams = searchXml;
//	}
//	else // build query string
//	{
	var qs = ""
	qs += "action=" ;
	qs += (searchScope)? searchScope.toUpperCase() : "FOLDER";
	qs += "SEARCH";
	qs += (libraryList)? "&lib=" + libraryList  : "";
	qs += "&query=" + escape(searchQuery);
	qs += "&maxres=";
	qs += (maxResults)? maxResults : "0";
	this.FTSearchParams = escape(searchQuery);
//	}
	console.log("FTSearchParams: " + this.FTSearchParams)
	this.isFTSearch = true;
	this.Refresh(true,true,false);
	return true;
}

//==========================================================================================
//reset FT search
//==========================================================================================

this.ResetFTSearch = function(isglobalsearch)
{
	this.FTSearchParams = "";
	this.isFTSearch = false;
	if(isglobalsearch){  //used in case of global search where we dont want to reload data
		this.ClearViewData();
	} else {
		this.Refresh(true,true,true);
	}
	return true;
}


//==========================================================================================
//build a complete xml data request strings based on the column properties -- it will get all or selected nodes
//==========================================================================================

this.GetXmlRequestString = function(recordIdList)
{
	var requestString = "";
	var nodeList = (this.isSummary)? this.xmlSummaryNodeList : this.xmlNodeList;
	if ( this.isSummary){
		nodeList = this.xmlSummaryNodeList;
	}else if ( this.isThumbnails || this.isCoverflow){
		nodeList = this.xmlThumbnailNodeList;
	}else{
		nodeList = this.xmlNodeList;
	}
	if(this.isXmlDataRequest)
		{
			requestString += "<Request>";
			if(this.isFTSearch && this.FTSearchParams) //ft search request has aditional elements
				{
					requestString += this.FTSearchParams;
				}
			else
				{
					requestString += "<Action>READFOLDER</Action>";
				}
		
			requestString += "<FolderID>" + this.folderID +  "</FolderID>";
			requestString += "<FolderViewName>" + this.folderViewName + "</FolderViewName>";
			requestString += "<UserName><![CDATA[" + this.userName +  "]]></UserName>";
			requestString += "<options>" + this.queryOptions + "</options>";
			if(recordIdList ){
				requestString += "<recordIdlist>" + recordIdList +  "</recordIdlist>";
				}
			requestString += "<nodes>";
			requestString += nodeList
			requestString += "</nodes>"
			requestString += "</Request>";
		}
	else
		{
			if(this.isFTSearch && this.FTSearchParams) //ft search request has aditional elements
				{
					requestString += this.FTSearchParams;
				}
			else
				{
					requestString += "action=READFOLDER";
				}
			requestString += "&folderid=" + this.folderID;
			if(recordIdList ){
				requestString += "&recordIdlist=" + this.recordId;
				}
			requestString += "&nodes=" + nodeList;
		}
	return requestString;

}

//==========================================================================================
// given entry row index returns the entry object
//==========================================================================================

this.GetEntryByRow = function(rowIdx)
{
	if(! this.dataTable ) {return null;}
	if(rowIdx < 1 || rowIdx >= this.dataTable.rows.length) {return null;}
	var dataRow = this.dataTable.rows[rowIdx];
	var entry = new ObjViewEntry(this, dataRow);
	return entry;
}

//==========================================================================================
//escapes the id so that jquery find can find the appropriate id such as vcat1.1
//==========================================================================================

this.escapeId = function(id)
{
	if ( id.indexOf(".") > -1 && id.indexOf("\\.") == -1 )
		return id.replace( /(:|\.|\[|\])/g, "\\$1" );
	else
		return id;
}



//==========================================================================================
// given entry html Id returns the entry object
//==========================================================================================

this.GetEntryById = function(entryId)
{
	if(! this.dataTable || ! entryId ) {return null;}
	entryId = this.escapeId(entryId);
	var dataRow = $(this.dataTable).find("#"+entryId).get(0);
	if(!dataRow) {return null;}	
	return this.GetEntryByRow(dataRow.rowIndex);
}

//return the id of the doc holding this file

this.GetThumbFileDocId = function(){
	if (! this.isThumbnails ){return null;}
	if ( ! this.currentEntry ){ return null;}
	var fullUnid = this.currentEntry;
	var parentUnid = fullUnid.substring(0, fullUnid.indexOf("~") );
	return parentUnid;
}


//==========================================================================================
// returns the current entry object
//==========================================================================================

this.GetCurrentEntry = function()
{
	if(! this.dataTable || ! this.currentEntry ) {return null;}
	var id = (this.isThumbnails) ? this.thumbnailID : this.currentEntry;
	var dataRow = document.getElementById(id);
	if(!dataRow) {return null;}	
	if ( this.isThumbnails  ){
		var tmpentry = new ObjViewEntry(this, dataRow);
		return tmpentry;
	}
	return this.GetEntryByRow(dataRow.rowIndex);
}
//==========================================================================================
// returns the first entry object
//==========================================================================================

this.GetFirstEntry = function()
{
	return this.GetEntryByRow(1);
}

//==========================================================================================
//returns the last entry object
//==========================================================================================

this.GetLastEntry = function()
{
	if(!this.dataTable) {return null;}
	if(this.dataTable.rows.length < 2 ) {return null;}
	return this.GetEntryByRow(this.dataTable.rows.length - 1);
}

//==========================================================================================
//given entry object returns the next entry object
//==========================================================================================

this.GetNextEntry = function(entry)
{
	return this.GetEntryByRow(entry.rowIdx + 1);
}

//==========================================================================================
//given entry object returns the previous entry object
//==========================================================================================

this.GetPreviousEntry = function(entry)
{
	return this.GetEntryByRow(entry.rowIdx - 1);
}


//==========================================================================================
//gets the ids of all entries in the view table
//==========================================================================================

this.GetAllEntryIds = function(recordsOnly)
{
	if(! this.dataTable) {return ;}
	this.allEntries = new Array();
	for(var k=1; k < this.dataTable.rows.length; k++)
	{
	if(recordsOnly)
		{
			if(jQuery(this.dataTable.rows[k]).attr("isRecord")) {this.allEntries[this.allEntries.length] = this.dataTable.rows[k].id;}
		}
	else
		{
			this.allEntries[this.allEntries.length] = this.dataTable.rows[k].id;
		}
	
	}
}

//==========================================================================================
//helper function to check if column index is within the column collection
//==========================================================================================

this.IsValidColumnIndex = function(colIdx)
{
	if(colIdx = null) {return false;}
	if(colIdx < 0 || colIdx >= this.columns.length || this.columns.length == 0 ) { return false;}
	return true;
}


//==========================================================================================
//-helper function to recompute column indices/fields and aliases after column add/remove/swap/update operations
//==========================================================================================

this.ComputeColumnReferences = function()
{
	this.xmlNodeList = "";
	for (var k=0; k < this.columns.length ; k++) 
		{
			this.columns[k].colIdx = k; //so the column knows its index...
			this.xmlNodeList += (this.xmlNodeList)? "," + this.columns[k].xmlNodeName : this.columns[k].xmlNodeName;
		}
}

//==========================================================================================
//gets reference to the data table the row preceedding the one with given id
//==========================================================================================

this.GetPreviousDataRow = function(entryId)
{
	if(! this.dataTable || ! entryId) {return null;}

	var dataRow = document.getElementById(entryId);
	if(dataRow)
	{
	var idx = dataRow.rowIndex;
	if(idx == 1) {return null;} //already at the begining
	return this.dataTable.rows[idx-1]; //previous row
	}
}


//==========================================================================================
//gets reference to the data table row following the one with given id
//==========================================================================================

this.GetNextDataRow = function(entryId)
{
	if(! this.dataTable || ! entryId) {return null;}

	var dataRow = document.getElementById(entryId);
	if(dataRow)
	{
	var idx = dataRow.rowIndex;
	if(idx == this.dataTable.rows.length-1) {return null;} //already at the end
	return this.dataTable.rows[idx+1]; //next row
	}
}


//==========================================================================================
//toggles expand/collapse of category tree given category id
//==========================================================================================


this.ToggleCategory = function(catRowId)
{

	if(! this.dataTable|| ! catRowId ) {return null;}
	

	var dataRow = document.getElementById(catRowId);
	if(dataRow)
	{		
			for(var i=0; i < dataRow.cells.length; i++)
			{
				if($(dataRow.cells[i]).attr('isCategoryHead'))
				{
					expanderImg = dataRow.cells[i].getElementsByTagName("IMG")[0];
					if(expanderImg) 
						{
						(expanderImg.src.indexOf("collapse") != -1) ? this.CollapseCategory(catRowId) : this.ExpandCategory(catRowId);
						}
				}
			}
	}
}

//==========================================================================================
//given category id returns next/previous category
//==========================================================================================

this.GetAdjacentCategory = function(dir, catRowId)
{
	if(! this.dataTable || ! catRowId) {return null;}
	var dataRow = document.getElementById(catRowId);
	offset = (dir=="up")? -1: 1;
	var nextRow=null;
	if(dataRow)
	{
		if(!$(dataRow).attr("pos")) {return;}
		var tmpArray = $(dataRow).attr("pos").split(".")
		var nextPos = parseInt(tmpArray[tmpArray.length-1]) + offset ; 
		tmpArray[tmpArray.length-1] = nextPos;
		//-- reassemble the adjacent category id --
		var nextCatId = this.idPrefix + "CAT" + tmpArray.join(".");
		nextRow = document.getElementById(nextCatId); // check if the category exists
		if(!nextRow && tmpArray.length >1) // no more categories at this level - try next level up
		{
			while(tmpArray.length != 1)
			{
				tmpArray = tmpArray.slice(0, -1); //discard last position element
			 	nextPos = parseInt(tmpArray[tmpArray.length-1]) + offset ; //increment what's left
				tmpArray[tmpArray.length-1] = nextPos;
				nextCatId = this.idPrefix + "CAT" + tmpArray.join(".");
				nextRow = document.getElementById(nextCatId); // check if the category exists
				if(nextRow) {break;}
			}
		}
	}
	return nextRow;
}
//==========================================================================================
//collapse category tree given category id
//==========================================================================================

this.CollapseCategory = function(catRowId)
{

	if(! this.dataTable || ! catRowId) {return null;}

	var dataRow = document.getElementById(catRowId);
	if(dataRow)
	{
		var rescount = $(dataRow).attr("responses")
		if ( rescount && parseInt(rescount) > 0 )
		{
			for (var t=0; t < rescount; t++ )
			{
				$(this.dataTable.rows[dataRow.rowIndex+1 + t]).css("display", "none")
			}
			for(var i=0; i < dataRow.cells.length; i++)
			{
				if($(dataRow.cells[i]).attr("isCategoryHead"))
				{
					expanderImg = dataRow.cells[i].getElementsByTagName("IMG")[0];
					//if(expanderImg) {expanderImg.src = expanderImg.src.replace("collapse", "expand");}
					if(expanderImg) 
					{
						$(expanderImg).prop("src", $(expanderImg).prop("src").replace("collapse", "expand"));
					}
				}
				//----- show category totals next to the heading of collapsed categories
				else if($(dataRow.cells[i]).attr("isCategoryTotal"))
				{
					totalSpan = dataRow.cells[i].getElementsByTagName("SPAN")[0];
					if(totalSpan) {$(totalSpan).css("display", "");}
				}
			}
			
			return;
		}
		
		
		if(!$(dataRow).attr("pos")) {return;}
		var endRow = this.GetAdjacentCategory("down", catRowId);
		//---- get the last table row if there is no more categories ----
		if(!endRow)
			{
				var endRow = this.dataTable.rows[this.dataTable.rows.length-1]; //last row
				var stopIndex = this.dataTable.rows.length; //hide all including the last row
			}
			
		if($(endRow).attr("isCategory") || $(endRow).attr("isTotal"))
			{
				var stopIndex = endRow.rowIndex; //hide all up to but not including the row
			}

		//---- hide all rows until the next category or the end of the view ----
		for(var k=dataRow.rowIndex + 1; k < stopIndex; k++)
			{
				$(this.dataTable.rows[k]).css("display", "none")
//				this.dataTable.rows[k].runtimeStyle.display="none";
			}

		//----- additional processing for the collapsed category row cells -----	
		for(var i=0; i < dataRow.cells.length; i++)
			{
				if($(dataRow.cells[i]).attr("isCategoryHead"))
				{
					expanderImg = dataRow.cells[i].getElementsByTagName("IMG")[0];
					//if(expanderImg) {expanderImg.src = expanderImg.src.replace("collapse", "expand");}
					if(expanderImg) 
					{
						$(expanderImg).prop("src", $(expanderImg).prop("src").replace("collapse", "expand"));
					}
				}
				//----- show category totals next to the heading of collapsed categories
				else if($(dataRow.cells[i]).attr("isCategoryTotal"))
				{
					totalSpan = dataRow.cells[i].getElementsByTagName("SPAN")[0];
					if(totalSpan) {$(totalSpan).css("display", "");}
				}
			}
		$(dataRow).attr("isCollapsed", true); // set the flag on the category row
	}
}


//==========================================================================================
//expand category tree given category id
//==========================================================================================

this.ExpandCategory = function(catRowId)
{
	if(! this.dataTable || ! catRowId) {return null;}
	var dataRow = document.getElementById(catRowId);
	if(dataRow)
	{
		var rescount = $(dataRow).attr("responses")
		if ( rescount && parseInt(rescount) > 0 )
		{
			for (var t=0; t < rescount; t++ )
			{
				$(this.dataTable.rows[dataRow.rowIndex+1 + t]).css("display", "")
			}
			
			for(var i=0; i < dataRow.cells.length; i++)
			{
				if($(dataRow.cells[i]).attr("isCategoryHead"))
				{
					expanderImg = dataRow.cells[i].getElementsByTagName("IMG")[0];
					if(expanderImg)
					{
						//expanderImg.src = expanderImg.src.replace("expand", "collapse");
						$(expanderImg).prop("src", $(expanderImg).prop("src").replace("expand", "collapse"))
					}
				}
				else if($(dataRow.cells[i]).attr("isCategoryTotal"))
				{
					totalSpan = dataRow.cells[i].getElementsByTagName("SPAN")[0];
					if(totalSpan) {$(totalSpan).css("display","none");}
				}
			}
			//$(this.dataTable.rows[k]).attr("isCollapsed", false);
			return;
		}
		
		
		if(!$(dataRow).attr("pos")) {return;}
		var endRow = this.GetAdjacentCategory("down", catRowId);
		//---- get the last table row if there is no more categories ----
		if(!endRow)
			{
				var endRow = this.dataTable.rows[this.dataTable.rows.length-1]; //last row
				var stopIndex = this.dataTable.rows.length; //show all including the last row
			}
		if($(endRow).attr("isCategory") || $(endRow).attr("isTotal"))
			{
				var stopIndex = endRow.rowIndex; //show all up to but not including the row
			}

		//---- show all rows or the next categorized column until the next main category or the end of the view ----
		var startRow = -1;
		//counting from left to right
		var nextCatIndex = 0; //the next categorized column
		for(var k=dataRow.rowIndex; k < stopIndex; k++)
			{
			if(startRow == -1){startRow = k}
			var catCellIndex = 0;  //categorized cell being processed
			var catIndex = 0; //index of the categorized column cell
			var cellClass = "";

			for(var c=0; c < this.dataTable.rows[k].cells.length; c++){
				if(catCellIndex == 0){
					var srcImg = this.dataTable.rows[k].cells[c].getElementsByTagName("IMG")[0];
					if(srcImg){cellClass = $(srcImg).prop("className");}
					if(cellClass == "listexpandericon") { 
						catCellIndex = c;
						if(k > startRow) {
							if(nextCatIndex == 0) {
								nextCatIndex = catCellIndex; 
							}
							if(catIndex == 0){
								catIndex = catCellIndex;
							}
						}
					}
				}
			}
			
			//adjust the row column's properties before it is displayed
			if($(this.dataTable.rows[k]).attr("isCollapsed")) 
				{
					for(var i=0; i < this.dataTable.rows[k].cells.length; i++)
						{
							if($(this.dataTable.rows[k].cells[i]).attr("isCategoryHead"))
							{
								expanderImg = this.dataTable.rows[k].cells[i].getElementsByTagName("IMG")[0];
								if(expanderImg)
								{
									//expanderImg.src = expanderImg.src.replace("expand", "collapse");
									$(expanderImg).prop("src", $(expanderImg).prop("src").replace("expand", "collapse"))
								}
							}
							else if($(this.dataTable.rows[k].cells[i]).attr("isCategoryTotal"))
							{
								totalSpan = this.dataTable.rows[k].cells[i].getElementsByTagName("SPAN")[0];
								if(totalSpan) {$(totalSpan).css("display","none");}
							}
						}
					$(this.dataTable.rows[k]).attr("isCollapsed", false);
				}
		
			//display a categorized row (or row in a category)
			if(catCellIndex == nextCatIndex){
				expanderImg = this.dataTable.rows[k].cells[catIndex].getElementsByTagName("IMG")[0];
				if(expanderImg) {
					//expanderImg.src = expanderImg.src.replace("collapse", "expand");
					$(expanderImg).prop("src", $(expanderImg).prop("src").replace("collapse", "expand"))
					$(this.dataTable.rows[k]).attr("isCollapsed", true);
				}
//				this.dataTable.rows[k].runtimeStyle.display="";
				$(this.dataTable.rows[k]).css("display","")
			}
		}
	}
}

//==========================================================================================
//collapse all categories
//==========================================================================================

this.CollapseAll = function()
{
	if(! this.dataTable) {return null;}
	var catId = "";
	var cat = null; 
	var rows = 1;
	
	for(var k=1; k < this.dataTable.rows.length; k++)
		{
			cat = document.getElementById(this.idPrefix + "CAT" + k);
			if(cat)
				{
					this.CollapseCategory(this.idPrefix + "CAT" + k);
					rows += 1;
				}
				else
				{
					//return;
				} 
		}
	if(this.embView) {
		this.AdjustEmbViewHeight(rows);	
	}
	return
}

//==========================================================================================
//expand all categories
//==========================================================================================

this.ExpandAll = function()
{
	if(! this.dataTable) {return null;}
	var catId = "";
	var cat = null; 

	$(this.dataTable.rows).css("display", "");
	for(var k=1; k < this.dataTable.rows.length; k++)
		{
			//show all rows
//			this.dataTable.rows[k].runtimeStyle.display="";
			
			//adjust +/- icons
			for(var i=0; i < this.dataTable.rows[k].cells.length; i++){
				if(this.dataTable.rows[k].cells[i].isCategoryHead){
					expanderImg = this.dataTable.rows[k].cells[i].getElementsByTagName("IMG")[0];
					if(expanderImg)
					{
						//expanderImg.src = expanderImg.src.replace("expand", "collapse");
						$(expanderImg).prop("src", $(expanderImg).prop("src").replace("expand", "collapse"))
					}
					$(this.dataTable.rows[k]).attr("isCollapsed", false);
				}
			}
		}
	if(this.embView) {
		this.AdjustEmbViewHeight(this.dataTable.rows.length);	
	}	
}

//==========================================================================================
//if an embedded view and categorized then adjust the div height when refreshed/expanded/collapsed
//==========================================================================================

this.AdjustEmbViewHeight = function(rows)
{
	if(this.embView) {
		if($(this).attr("isCategorized")){
			if(!this.maxHeight == 0){
				var eViewID = $(this.dataTableContainer).prop("id");
				//document.all[eViewID].style.display = ""
				$(document.getElementById("eViewID")).css("display", "");
				var calcHt = 4 + (parseInt(rows) * this.rowHeight);
				//document.all[eViewID].style.height = (calcHt > this.maxHeight) ? this.maxHeight + "px" : calcHt + "px";
				$(document.getElementById("eViewID")).css("height", (calcHt > this.maxHeight) ? this.maxHeight + "px" : calcHt + "px");
			}
		}
	}
}

//==========================================================================================
//sends data to the view action processing agent
//==========================================================================================

this.SendData = function(request)
{
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, this.serviceAgent) || httpObj.status=="FAILED"){
		httpObj = null;
		return false;
	}
}

//==========================================================================================
//Object initialization
//==========================================================================================
this.oXsl = null;
this.oXml = null;
this.oOriginalXml = null;  //used for storing original xml for folder filtering

} // end of view object