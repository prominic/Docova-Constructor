var _DOCOVAEdition = "SE";  //-- set this variable to either SE or Domino depending upon where this code is installed

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * FormulaLanguage.js 
 * JavaScript equivalents for many @Formula @Command functions
 * Dependencies: jQuery, Docova, AppBuilderJS
 *------------------------------------------------------------------------------------------------------------------------------------------- */

/*---------------------------------
 * Globals
 *-------------------------------- */
var _targetframe = "";
var _lastreturnvalue = null;
var _pendingactions = null;
var _pendingactionstimer = null;

/*---------------------------------
 * Constants
 *-------------------------------- */
var ACLLEVEL_NOACCESS = 0;
var ACLLEVEL_DEPOSITOR = 1;
var ACLLEVEL_READER = 2;
var ACLLEVEL_AUTHOR = 3;
var ACLLEVEL_EDITOR = 4;
var ACLLEVEL_DESIGNER = 5;
var ACLLEVEL_MANAGER = 6;
var PICKLIST_NAMES = 0;
var PICKLIST_ROOMS = 1;
var PICKLIST_RESOURCES = 2;
var PICKLIST_CUSTOM = 3;
var PROMPT_OK = 1;
var PROMPT_YESNO = 2;
var PROMPT_OKCANCELEDIT = 3;
var PROMPT_OKCANCELLIST = 4;
var PROMPT_OKCANCELCOMBO = 5;
var PROMPT_OKCANCELEDITCOMBO = 6;
var PROMPT_OKCANCELLISTMULT = 7;
var PROMPT_PASSWORD = 10;
var PROMPT_YESNOCANCEL = 11;


/*----------------------------------
 * @Functions
 *--------------------------------- */

function $$Abs(argumentlist) {
	if(typeof argumentlist == 'undefined'){return;}
	if(Array.isArray(argumentlist)){
		for(var i=0; i<argumentlist.length; i++){
			argumentlist[i] = Math.abs(argumentlist[i]);
		}
	}else{
		argumentlist = Math.abs(argumentlist);
	}
	
	return argumentlist;
}

function $$Abstract() {}
function $$AbstractSimple() {}
function $$Accessed() {}

function $$ACos(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.acos(argument[i]);
		}
	}else{
		argument = Math.acos(argument);
	}
	
	return argument;
}

function $$AddToFolder(foldernameadd, foldernameremove) {
	var uidoc = Docova.getUIDocument();
	var doc = null;
	if(uidoc && !uidoc.isNewDoc){
		doc = uidoc.document;
	}else{
		var uiview = Docova.getUIView();
		if(uiview){
			var tempentry = uiview.getCurrentEntry();
			if(tempentry){
				doc = new DocovaDocument(uiview, tempentry, tempentry)
			}
		}	
	}
	
	if(doc){
		if(typeof foldernameadd == "string" && foldernameadd != ""){
			doc.putInFolder(foldernameadd);
		}
		if(typeof foldernameremove == "string" && foldernameremove != ""){
			doc.removeFromFolder(foldernameremove);
		}
	}

}

function $$Adjust(dateToAdjust, years, months, days, hours, minutes, seconds) {
	if(typeof dateToAdjust == 'undefined'){return;}

	if(typeof years == 'undefined'){var years = 0;}
	if(typeof months == 'undefined'){var months = 0;}
	if(typeof days == 'undefined'){var days = 0;}
	if(typeof hours == 'undefined'){var hours = 0;}
	if(typeof minutes == 'undefined'){var minutes = 0;}
	if(typeof seconds == 'undefined'){var seconds = 0;}
	
	var tempdates = []; 
	var isarray = Array.isArray(dateToAdjust);
	
	if(isarray){
		tempdates = dateToAdjust.slice();
	}else{
		tempdates.push(new Date(dateToAdjust.getTime()));
	}

	for(var i=0; i<tempdates.length; i++){
		var tempdate = tempdates[i];
		
		if(years != 0){
			tempdate.setFullYear(tempdate.getFullYear() + years);
		}
		if(months != 0){
			tempdate.setMonth(tempdate.getMonth() + months);		
		}
		if(days != 0){
			tempdate.setDate(tempdate.getDate() + days);
		}
		if(hours != 0){
			tempdate.setHours(tempdate.getHours() + hours);
		}
		if(minutes != 0){
			tempdate.setMinutes(tempdate.getMinutes() + minutes);
		}
		if(seconds != 0){
			tempdate.setSeconds(tempdate.getSeconds() + seconds);
		}
		tempdates[i] = tempdate;
	}

	return (isarray ? tempdates : tempdates[0]);	
}


function $$ArrayAppend(array1, array2) {
    var return_arrayappend = null;

     if(! Array.isArray(array1)){
         array1 = [array1];
     }
     if(! Array.isArray(array2)){
         array2 = [array2];
     }        

    if(Array.isArray(array1) && Array.isArray(array2)){
       return_arrayappend = array1.concat(array2); 
    }else if(Array.isArray(array1)){
        return_arrayappend = array1.slice();
    }else if(Array.isArray(array2)){
        return_arrayappend = array2.slice();
    }

    return return_arrayappend;
}

function $$ArrayConcat(argument) {
	if(typeof argument == 'undefined'){return "";}
	
	var isarray = Array.isArray(argument);
	var tempresult = [];
	
	var templist = (isarray ? argument.slice() : [argument]);
	for(var i=0; i<templist.length; i++){
		var tempval = templist[i];
		if(Array.isArray(tempval)){
			tempresult = tempresult.concat(tempval);
		}else{
			tempresult.push(tempval);
		}
	}
	
	return tempresult;
}

function $$ArrayGetIndex(arraytosearch, selection, compmethod) {
	//TODO - compmethod does not respect pitch insensitive option

	var result = null;
	if(typeof arraytosearch == 'undefined'){return result;}
	if(!Array.isArray(arraytosearch)){return result;}

	if(typeof selection == 'undefined'){return result;}

	var pos = -1;
		
	for(var i=0; i<arraytosearch.length; i++){
		if(typeof compmethod !== undefined && (compmethod === 1 || compmethod === 5)){
			if(arraytosearch[i].toString().toUpperCase() == selection.toString().toUpperCase()){
				result = i;
				break;
			}			
		}else{
			if(arraytosearch[i].toString() == selection.toString()){
				result = i;
				break;
			}			
		}
	}
	
	return result;	
}

function $$ArrayUnique(sourceArray, compMethod) {
	//TODO - compmethod does not respect pitch insensitive option

	var result = null;
	if(typeof sourceArray == 'undefined'){return result;}
	if(!Array.isArray(sourceArray)){
			sourceArray = [sourceArray];
	}

	var caseinsensitive = (typeof compMethod !== undefined && (compMethod === 1 || compMethod === 5));
	var tempArray = sourceArray.map(function(elem) { return (caseinsensitive ? elem.toString().toLowerCase() : elem.toString()); });

	result = [];
	for(var i=0; i<sourceArray.length; i++){
		var pos = tempArray.indexOf((caseinsensitive ? sourceArray[i].toString().toLowerCase() : sourceArray[i].toString()));
		if(pos === i){
			result.push(sourceArray[i]);
		}
	}
	
	return result;	
}

function $$Ascii(argument, flags) {
	if(typeof argument == 'undefined'){return;}
	var isarray = Array.isArray(argument);
	var allinrange = false;
	if(typeof flags != 'undefined'){
		if(Array.isArray(flags)){
			allinrange = (flags[0].toUpperCase() === "ALLINRANGE" || flags[0].toUpperCase() === "[ALLINRANGE]");
		}else{
			allinrange = (flags === true || flags.toUpperCase() === "ALLINRANGE" || flags.toUpperCase() === "[ALLINRANGE]");		
		}
	}

    var defaultDiacriticsRemovalMap = [
        {'base':'A', 'letters':'\u0041\u24B6\uFF21\u00C0\u00C1\u00C2\u1EA6\u1EA4\u1EAA\u1EA8\u00C3\u0100\u0102\u1EB0\u1EAE\u1EB4\u1EB2\u0226\u01E0\u00C4\u01DE\u1EA2\u00C5\u01FA\u01CD\u0200\u0202\u1EA0\u1EAC\u1EB6\u1E00\u0104\u023A\u2C6F'},
        {'base':'AA','letters':'\uA732'},
        {'base':'AE','letters':'\u00C6\u01FC\u01E2'},
        {'base':'AO','letters':'\uA734'},
        {'base':'AU','letters':'\uA736'},
        {'base':'AV','letters':'\uA738\uA73A'},
        {'base':'AY','letters':'\uA73C'},
        {'base':'B', 'letters':'\u0042\u24B7\uFF22\u1E02\u1E04\u1E06\u0243\u0182\u0181'},
        {'base':'C', 'letters':'\u0043\u24B8\uFF23\u0106\u0108\u010A\u010C\u00C7\u1E08\u0187\u023B\uA73E'},
        {'base':'D', 'letters':'\u0044\u24B9\uFF24\u1E0A\u010E\u1E0C\u1E10\u1E12\u1E0E\u0110\u018B\u018A\u0189\uA779'},
        {'base':'DZ','letters':'\u01F1\u01C4'},
        {'base':'Dz','letters':'\u01F2\u01C5'},
        {'base':'E', 'letters':'\u0045\u24BA\uFF25\u00C8\u00C9\u00CA\u1EC0\u1EBE\u1EC4\u1EC2\u1EBC\u0112\u1E14\u1E16\u0114\u0116\u00CB\u1EBA\u011A\u0204\u0206\u1EB8\u1EC6\u0228\u1E1C\u0118\u1E18\u1E1A\u0190\u018E'},
        {'base':'F', 'letters':'\u0046\u24BB\uFF26\u1E1E\u0191\uA77B'},
        {'base':'G', 'letters':'\u0047\u24BC\uFF27\u01F4\u011C\u1E20\u011E\u0120\u01E6\u0122\u01E4\u0193\uA7A0\uA77D\uA77E'},
        {'base':'H', 'letters':'\u0048\u24BD\uFF28\u0124\u1E22\u1E26\u021E\u1E24\u1E28\u1E2A\u0126\u2C67\u2C75\uA78D'},
        {'base':'I', 'letters':'\u0049\u24BE\uFF29\u00CC\u00CD\u00CE\u0128\u012A\u012C\u0130\u00CF\u1E2E\u1EC8\u01CF\u0208\u020A\u1ECA\u012E\u1E2C\u0197'},
        {'base':'J', 'letters':'\u004A\u24BF\uFF2A\u0134\u0248'},
        {'base':'K', 'letters':'\u004B\u24C0\uFF2B\u1E30\u01E8\u1E32\u0136\u1E34\u0198\u2C69\uA740\uA742\uA744\uA7A2'},
        {'base':'L', 'letters':'\u004C\u24C1\uFF2C\u013F\u0139\u013D\u1E36\u1E38\u013B\u1E3C\u1E3A\u0141\u023D\u2C62\u2C60\uA748\uA746\uA780'},
        {'base':'LJ','letters':'\u01C7'},
        {'base':'Lj','letters':'\u01C8'},
        {'base':'M', 'letters':'\u004D\u24C2\uFF2D\u1E3E\u1E40\u1E42\u2C6E\u019C'},
        {'base':'N', 'letters':'\u004E\u24C3\uFF2E\u01F8\u0143\u00D1\u1E44\u0147\u1E46\u0145\u1E4A\u1E48\u0220\u019D\uA790\uA7A4'},
        {'base':'NJ','letters':'\u01CA'},
        {'base':'Nj','letters':'\u01CB'},
        {'base':'O', 'letters':'\u004F\u24C4\uFF2F\u00D2\u00D3\u00D4\u1ED2\u1ED0\u1ED6\u1ED4\u00D5\u1E4C\u022C\u1E4E\u014C\u1E50\u1E52\u014E\u022E\u0230\u00D6\u022A\u1ECE\u0150\u01D1\u020C\u020E\u01A0\u1EDC\u1EDA\u1EE0\u1EDE\u1EE2\u1ECC\u1ED8\u01EA\u01EC\u00D8\u01FE\u0186\u019F\uA74A\uA74C'},
        {'base':'OI','letters':'\u01A2'},
        {'base':'OO','letters':'\uA74E'},
        {'base':'OU','letters':'\u0222'},
        {'base':'OE','letters':'\u008C\u0152'},
        {'base':'oe','letters':'\u009C\u0153'},
        {'base':'P', 'letters':'\u0050\u24C5\uFF30\u1E54\u1E56\u01A4\u2C63\uA750\uA752\uA754'},
        {'base':'Q', 'letters':'\u0051\u24C6\uFF31\uA756\uA758\u024A'},
        {'base':'R', 'letters':'\u0052\u24C7\uFF32\u0154\u1E58\u0158\u0210\u0212\u1E5A\u1E5C\u0156\u1E5E\u024C\u2C64\uA75A\uA7A6\uA782'},
        {'base':'S', 'letters':'\u0053\u24C8\uFF33\u1E9E\u015A\u1E64\u015C\u1E60\u0160\u1E66\u1E62\u1E68\u0218\u015E\u2C7E\uA7A8\uA784'},
        {'base':'T', 'letters':'\u0054\u24C9\uFF34\u1E6A\u0164\u1E6C\u021A\u0162\u1E70\u1E6E\u0166\u01AC\u01AE\u023E\uA786'},
        {'base':'TZ','letters':'\uA728'},
        {'base':'U', 'letters':'\u0055\u24CA\uFF35\u00D9\u00DA\u00DB\u0168\u1E78\u016A\u1E7A\u016C\u00DC\u01DB\u01D7\u01D5\u01D9\u1EE6\u016E\u0170\u01D3\u0214\u0216\u01AF\u1EEA\u1EE8\u1EEE\u1EEC\u1EF0\u1EE4\u1E72\u0172\u1E76\u1E74\u0244'},
        {'base':'V', 'letters':'\u0056\u24CB\uFF36\u1E7C\u1E7E\u01B2\uA75E\u0245'},
        {'base':'VY','letters':'\uA760'},
        {'base':'W', 'letters':'\u0057\u24CC\uFF37\u1E80\u1E82\u0174\u1E86\u1E84\u1E88\u2C72'},
        {'base':'X', 'letters':'\u0058\u24CD\uFF38\u1E8A\u1E8C'},
        {'base':'Y', 'letters':'\u0059\u24CE\uFF39\u1EF2\u00DD\u0176\u1EF8\u0232\u1E8E\u0178\u1EF6\u1EF4\u01B3\u024E\u1EFE'},
        {'base':'Z', 'letters':'\u005A\u24CF\uFF3A\u0179\u1E90\u017B\u017D\u1E92\u1E94\u01B5\u0224\u2C7F\u2C6B\uA762'},
        {'base':'a', 'letters':'\u0061\u24D0\uFF41\u1E9A\u00E0\u00E1\u00E2\u1EA7\u1EA5\u1EAB\u1EA9\u00E3\u0101\u0103\u1EB1\u1EAF\u1EB5\u1EB3\u0227\u01E1\u00E4\u01DF\u1EA3\u00E5\u01FB\u01CE\u0201\u0203\u1EA1\u1EAD\u1EB7\u1E01\u0105\u2C65\u0250'},
        {'base':'aa','letters':'\uA733'},
        {'base':'ae','letters':'\u00E6\u01FD\u01E3'},
        {'base':'ao','letters':'\uA735'},
        {'base':'au','letters':'\uA737'},
        {'base':'av','letters':'\uA739\uA73B'},
        {'base':'ay','letters':'\uA73D'},
        {'base':'b', 'letters':'\u0062\u24D1\uFF42\u1E03\u1E05\u1E07\u0180\u0183\u0253'},
        {'base':'c', 'letters':'\u0063\u24D2\uFF43\u0107\u0109\u010B\u010D\u00E7\u1E09\u0188\u023C\uA73F\u2184'},
        {'base':'d', 'letters':'\u0064\u24D3\uFF44\u1E0B\u010F\u1E0D\u1E11\u1E13\u1E0F\u0111\u018C\u0256\u0257\uA77A'},
        {'base':'dz','letters':'\u01F3\u01C6'},
        {'base':'e', 'letters':'\u0065\u24D4\uFF45\u00E8\u00E9\u00EA\u1EC1\u1EBF\u1EC5\u1EC3\u1EBD\u0113\u1E15\u1E17\u0115\u0117\u00EB\u1EBB\u011B\u0205\u0207\u1EB9\u1EC7\u0229\u1E1D\u0119\u1E19\u1E1B\u0247\u025B\u01DD'},
        {'base':'f', 'letters':'\u0066\u24D5\uFF46\u1E1F\u0192\uA77C'},
        {'base':'g', 'letters':'\u0067\u24D6\uFF47\u01F5\u011D\u1E21\u011F\u0121\u01E7\u0123\u01E5\u0260\uA7A1\u1D79\uA77F'},
        {'base':'h', 'letters':'\u0068\u24D7\uFF48\u0125\u1E23\u1E27\u021F\u1E25\u1E29\u1E2B\u1E96\u0127\u2C68\u2C76\u0265'},
        {'base':'hv','letters':'\u0195'},
        {'base':'i', 'letters':'\u0069\u24D8\uFF49\u00EC\u00ED\u00EE\u0129\u012B\u012D\u00EF\u1E2F\u1EC9\u01D0\u0209\u020B\u1ECB\u012F\u1E2D\u0268\u0131'},
        {'base':'j', 'letters':'\u006A\u24D9\uFF4A\u0135\u01F0\u0249'},
        {'base':'k', 'letters':'\u006B\u24DA\uFF4B\u1E31\u01E9\u1E33\u0137\u1E35\u0199\u2C6A\uA741\uA743\uA745\uA7A3'},
        {'base':'l', 'letters':'\u006C\u24DB\uFF4C\u0140\u013A\u013E\u1E37\u1E39\u013C\u1E3D\u1E3B\u017F\u0142\u019A\u026B\u2C61\uA749\uA781\uA747'},
        {'base':'lj','letters':'\u01C9'},
        {'base':'m', 'letters':'\u006D\u24DC\uFF4D\u1E3F\u1E41\u1E43\u0271\u026F'},
        {'base':'n', 'letters':'\u006E\u24DD\uFF4E\u01F9\u0144\u00F1\u1E45\u0148\u1E47\u0146\u1E4B\u1E49\u019E\u0272\u0149\uA791\uA7A5'},
        {'base':'nj','letters':'\u01CC'},
        {'base':'o', 'letters':'\u006F\u24DE\uFF4F\u00F2\u00F3\u00F4\u1ED3\u1ED1\u1ED7\u1ED5\u00F5\u1E4D\u022D\u1E4F\u014D\u1E51\u1E53\u014F\u022F\u0231\u00F6\u022B\u1ECF\u0151\u01D2\u020D\u020F\u01A1\u1EDD\u1EDB\u1EE1\u1EDF\u1EE3\u1ECD\u1ED9\u01EB\u01ED\u00F8\u01FF\u0254\uA74B\uA74D\u0275'},
        {'base':'oi','letters':'\u01A3'},
        {'base':'ou','letters':'\u0223'},
        {'base':'oo','letters':'\uA74F'},
        {'base':'p','letters':'\u0070\u24DF\uFF50\u1E55\u1E57\u01A5\u1D7D\uA751\uA753\uA755'},
        {'base':'q','letters':'\u0071\u24E0\uFF51\u024B\uA757\uA759'},
        {'base':'r','letters':'\u0072\u24E1\uFF52\u0155\u1E59\u0159\u0211\u0213\u1E5B\u1E5D\u0157\u1E5F\u024D\u027D\uA75B\uA7A7\uA783'},
        {'base':'s','letters':'\u0073\u24E2\uFF53\u00DF\u015B\u1E65\u015D\u1E61\u0161\u1E67\u1E63\u1E69\u0219\u015F\u023F\uA7A9\uA785\u1E9B'},
        {'base':'t','letters':'\u0074\u24E3\uFF54\u1E6B\u1E97\u0165\u1E6D\u021B\u0163\u1E71\u1E6F\u0167\u01AD\u0288\u2C66\uA787'},
        {'base':'tz','letters':'\uA729'},
        {'base':'u','letters': '\u0075\u24E4\uFF55\u00F9\u00FA\u00FB\u0169\u1E79\u016B\u1E7B\u016D\u00FC\u01DC\u01D8\u01D6\u01DA\u1EE7\u016F\u0171\u01D4\u0215\u0217\u01B0\u1EEB\u1EE9\u1EEF\u1EED\u1EF1\u1EE5\u1E73\u0173\u1E77\u1E75\u0289'},
        {'base':'v','letters':'\u0076\u24E5\uFF56\u1E7D\u1E7F\u028B\uA75F\u028C'},
        {'base':'vy','letters':'\uA761'},
        {'base':'w','letters':'\u0077\u24E6\uFF57\u1E81\u1E83\u0175\u1E87\u1E85\u1E98\u1E89\u2C73'},
        {'base':'x','letters':'\u0078\u24E7\uFF58\u1E8B\u1E8D'},
        {'base':'y','letters':'\u0079\u24E8\uFF59\u1EF3\u00FD\u0177\u1EF9\u0233\u1E8F\u00FF\u1EF7\u1E99\u1EF5\u01B4\u024F\u1EFF'},
        {'base':'z','letters':'\u007A\u24E9\uFF5A\u017A\u1E91\u017C\u017E\u1E93\u1E95\u01B6\u0225\u0240\u2C6C\uA763'}
    ];
    
     var diacriticsMap = {};
    for (var i=0; i < defaultDiacriticsRemovalMap .length; i++){
        var letters = defaultDiacriticsRemovalMap [i].letters;
        for (var j=0; j < letters.length ; j++){
            diacriticsMap[letters[j]] = defaultDiacriticsRemovalMap [i].base;
        }
    }
  
	var templist = [];
	if(isarray){
		templist = argument.slice();
	}else{
		templist.push(argument);
	}	
	
	for(var i=0; i<templist.length; i++){
		var tempstring = templist[i];
		tempstring =  tempstring.replace(/[^\u0000-\u007E]/g, function(a){ 
	           return diacriticsMap[a] || a; 
     	});
		
		var newtempstring = "";					 	 	
		for(var c=0; c<tempstring.length; c++){
			var charcode = tempstring.charCodeAt(c);
			if( charcode < 32 || charcode > 127){
				newtempstring += "?";
			}else{
				newtempstring += tempstring.charAt(c);
			}
		}
		if(allinrange){
			if(newtempstring.indexOf("?") > -1){
				newtempstring = "";
			}
		}		
		
		templist[i] = newtempstring;
	}
		
	return (isarray ? templist : templist[0]);
}

function $$ASin(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.asin(argument[i]);
		}
	}else{
		argument = Math.asin(argument);
	}
	
	return argument;
}

function $$ATan(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.atan(argument[i]);
		}
	}else{
		argument = Math.atan(argument);
	}
	
	return argument;
}

function $$ATan2(argument1, argument2) {
	if(typeof argument1 == 'undefined' || typeof argument2 == 'undefined'){return;}
	var isarray1 = Array.isArray(argument1);
	var isarray2 = Array.isArray(argument2);
	
	if(isarray1 && isarray2 && argument1.length != argument2.length){
		return;
	}

	var tempresult = [];
	if(isarray1 && isarray2){
		for(var i=0; i<argument1.length; i++){
			tempresult.push(Math.atan2(argument2[i], argument1[i]));	
		}	
	}else if(isarray2 && !isarray1){
		for(var i=0; i<argument2.length; i++){
			tempresult.push(Math.atan2(argument2[i], argument1));	
		}
	}else if(isarray1 && !isarray2){
		for(var i=0; i<argument1.length; i++){
			tempresult.push(Math.atan2(argument2, argument1[i]));	
		}		
	}else{
		tempresult.push(Math.atan2(argument2, argument1));
	}

	return (tempresult.length > 1 ? tempresult : tempresult[0]);
}

function $$AttachmentLengths() {}
function $$AttachmentModifiedTimes() {}

function $$AttachmentNames() {
	//TODO - account for edit mode and attachments added but not yet posted
	var result = [];
	
	if(docInfo && docInfo.DocAttachmentNames){
		result = docInfo.DocAttachmentNames.split("*");
	}
	
	return result;
}

function $$Attachments() {
	//TODO - account for edit mode and attachments added but not yet posted
	var result = 0;

	if(docInfo && docInfo.DocAttachmentNames){
		result = docInfo.DocAttachmentNames.split("*").length;
	}
		
	return result;
}

function $$Author() {
	var result = "";
	
	var uidoc = Docova.getUIDocument();
	if(uidoc){
		if (uidoc.isNewDoc){
			//use current user's name
			result = uidoc.usernameAB;
		}else{
			//retrieve field from back end
			var doc = uidoc.document;
			if(doc){
				result = Docova.Utils.evaluateFormula("@Author", doc); 				
			}
		}		
	}

	return result;
}

function $$Begins(contentstring, searchstring) {
	var result = false;	
	
	if(typeof contentstring == 'undefined' || typeof contentstring == 'undefined'){return result;}
	var isarray1 = Array.isArray(contentstring);
	var isarray2 = Array.isArray(searchstring);

	var tempcontentlist = (isarray1 ? contentstring.slice()	: [contentstring]);
	var tempsearchlist = (isarray2 ? searchstring.slice()	: [searchstring]);

	for(var c=0; c<tempcontentlist.length; c++){
		for(var s=0; s<tempsearchlist.length; s++){
			if(tempcontentlist[c].indexOf(tempsearchlist[s])===0){
				result = true;
				return result;
			}
		}
	}
	
	return result;		
}

function $$BrowserInfo() {}
function $$BusinessDays() {}

function $$Char(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = String.fromCharCode(argument[i]);
		}
	}else{
		argument = String.fromCharCode(argument);
	}
	
	return argument;
}

function $$ClientType() {
	return "Notes";
}

function $$Command(action, param1, param2, param3, param4, param5) {
	//TODO - need to add additional Commands
	if(typeof action == 'undefined' || action === null || action == ""){
		return;
	}
	
	action = action.toUpperCase();
	
	if(action == "[CLEAR]"){
		_Cmd_Clear();
	}else if(action == "[CLOSEWINDOW"){
		_Cmd_FileCloseWindow(param1);
	}else if(action == "[COMPOSE]"){
		_Cmd_Compose(param1, param2, param3, param4);
	}else if(action == "[COMPOSERESPONSE]"){
		_Cmd_ComposeResponse(param1, param2, param3);
	}else if(action == "[EDITCLEAR]"){
		_Cmd_Clear();
	}else if(action == "[EDITDOCUMENT]"){
		_Cmd_EditDocument(param1, param2);
	}else if(action == "[EDITGOTOFIELD]"){
		_Cmd_EditGotoField(param1);
	}else if(action == "[EDITPROFILE]"){
		_Cmd_EditProfile(param1, param2, false);
	}else if(action == "[EDITPROFILEDOCUMENT]"){
		_Cmd_EditProfile(param1, param2, true);
	}else if(action == "[FILECLOSEWINDOW]"){
		_Cmd_FileCloseWindow(param1);
	}else if(action == "[FILESAVE]"){
		return _Cmd_FileSave();
	}else if(action == "[FILESAVEANDCLOSE]"){
			return _Cmd_FileSaveAndClose();		
	}else if(action == "[FILEPRINT]"){
		_Cmd_FilePrint();
	}else if(action == "[MAILADDRESS]"){
		_Cmd_MailAddress(param1);
	}else if(action == "[MAILSEND]"){
		alert("--Command not implemented.--");
	}else if(action == "[MAILFORWARD]"){
		alert("--Command not implemented.--");
	}else if(action == "[MOVETOFOLDER]"){
		alert("--Command not implemented.--");	
	}else if(action == "[NAVIGATENEXT]"){
		_Cmd_NavigateNext();
	}else if(action == "[NAVIGATEPREV]"){
		_Cmd_NavigatePrev();
	}else if(action == "[OPENDOCUMENT]"){
		_Cmd_OpenDocument(param1, param2);
	}else if(action == "[OPENPAGE]"){
		_Cmd_OpenPage(param1);
	}else if (action == "[OPENINNEWWINDOW]"){
		alert("--Command not implemented.--");
	}else if (action == "[OPENVIEW]"){
		_Cmd_OpenView(param1, param2, param3);
	}else if(action == "[RELOADWINDOW]"){
		_Cmd_ReloadWindow();
	}else if(action == "[REFRESHFRAME]"){
		_Cmd_RefreshFrame(param1);
	}else if(action == "[REMOVEFROMFOLDER]"){
		alert("--Command not implemented.--");		
	}else if(action == "[TOOLSCATEGORIZE]"){
		alert("--Command not implemented.--");
	}else if(action == "[TOOLSRUNMACRO]"){
		_Cmd_ToolsRunMacro(param1);
	}else if(action == "[VIEWREFRESHFIELDS]"){
		_Cmd_ViewRefreshFields(true);
	}else if(action == "[VIEWEXPANDALL]"){
		_Cmd_ViewExpandAll();
	}else if(action == "[VIEWCOLLAPSEALL]"){
		_Cmd_ViewCollapseAll();
	}
	
}

function $$Compare(list1, list2, flags) {
	if(typeof list1 == 'undefined' || typeof list2 == 'undefined'){return;}
	
	var casesensitive = true;
	var accentsensitive = true;
	var pitchsensitive = true;
	
	if(typeof flags !== 'undefined'){
		if(flags.indexOf("CASEINSENSITIVE") > -1){
			casesensitive = false;
		}
		if(flags.indexOf("ACCENTINSENSITIVE") > -1){
			accentsensitive = false;
		}		
		if(flags.indexOf("PITCHINSENSITIVE") > -1){
			pitchsensitive = false;
		}		
	}
	
	var isarray1 = Array.isArray(list1);
	var isarray2 = Array.isArray(list2);

	var templist1 = (isarray1 ? list1.slice()	: [list1]);
	var templist2 = (isarray2 ? list2.slice()	: [list2]);

	var maxcount = Math.max(templist1.length, templist2.length);
	var outputlist = new Array();
	
	for(var i=0; i<maxcount; i++){
		var outputnum = 0;
		
		var val1 = templist1[Math.min(i, templist1.length-1)];
		var val2 = templist2[Math.min(i, templist2.length-1)];
	
		if(!casesensitive){
			val1 = val1.toUpperCase();
			val2 = val2.toUpperCase();
		}
		
		if(!pitchsensitive || !accentsensitive){
			val1 = $$Ascii(val1);
			val2 = $$Ascii(val2);
		}
		outputnum = val1.localeCompare(val2);
		outputnum = (outputnum > 0 ? 1 : (outputnum < 0 ? -1 : 0));
		outputlist.push(outputnum);
	}
	
	return outputlist;		
}

function $$Contains(contentstring, searchstring) {
	var result = false;	
	
	if(typeof contentstring == 'undefined' || typeof contentstring == 'undefined'){return result;}
	var isarray1 = Array.isArray(contentstring);
	var isarray2 = Array.isArray(searchstring);

	var tempcontentlist = (isarray1 ? contentstring.slice()	: [contentstring]);
	var tempsearchlist = (isarray2 ? searchstring.slice()	: [searchstring]);

	for(var c=0; c<tempcontentlist.length; c++){
		for(var s=0; s<tempsearchlist.length; s++){
			if(tempsearchlist[s] === "" || tempcontentlist[c].indexOf(tempsearchlist[s]) > -1){
				result = true;
				return result;
			}
		}
	}
	
	return result;		
}

function $$Cos(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.cos(argument[i]);
		}
	}else{
		argument = Math.cos(argument);
	}
	
	return argument;
}

function $$Count(argument) {
	var result = 1;
	if(typeof argument == 'undefined'){return result;}
	if(Array.isArray(argument)){
		result = argument.length;
	}
	
	return result;
}

function $$Created() {
	var result = null;
	
	if(docInfo && docInfo.CreatedDate){
		result = $$TextToTime(docInfo.CreatedDate);
	}
	
	return result;
}

function $$Date(timedateoryear, month, day, hour, minute, second) {
	if(typeof timedateoryear == 'undefined'){return;}
	
	var isarray = false;
	var tempresult = [];
	if(typeof hour != 'undefined' && typeof minute != 'undefined' && typeof second != 'undefined'){
		month = month - 1;
		var tempdate = new Date(timedateoryear, month, day, hour, minute, second);		
		tempresult.push(tempdate);	
	}else if(typeof month != 'undefined' && typeof day != 'undefined'){
		month = month - 1;
		var tempdate = new Date(timedateoryear, month, day, 0, 0, 0);
		if(isNaN(tempdate)){
			tempdate = "";
		}
		tempresult.push(tempdate);					
	}else{
		isarray = Array.isArray(timedateoryear);
		var templist = (isarray ? timedateoryear.slice() : [timedateoryear]);
		for(var i=0; i<templist.length; i++){
			var tempdate = templist[i];
			if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				tempdate.setHours(0);
				tempdate.setMinutes(0);
				tempdate.setSeconds(0);	
				if(isNaN(tempdate)){
					tempdate = "";
				}				
			}else if(typeof tempdate == 'string'){
				tempdate = Docova.Utils.convertStringToDate(tempdate);
				if(tempdate !== null){				
					tempdate.setHours(0);
					tempdate.setMinutes(0);
					tempdate.setSeconds(0);
					if(isNaN(tempdate)){
						tempdate = "";
					}
				}else{
					tempdate = "";
				}
			}else{
				tempdate = "";
			}
			tempresult.push(tempdate);
		}
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$Day(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getDate();			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$DbCalculate(serverdb, viewname, action, columnorfield, restriction)
{
	var appname = ((Array.isArray(serverdb) && serverdb.length > 0) ? serverdb[1] : serverdb);
	if(appname == ""){
		appname = (typeof docInfo != "undefined" && typeof docInfo.appName != "undefined" ? docInfo.appName : (typeof docInfo != "undefined" && typeof docInfo.AppID != "undefined" ? docInfo.AppID : ""));
	}
	var tempresult = window.top.Docova.Utils.dbCalculate({
		"servername" : (Array.isArray(serverdb)? serverdb[0] : ""), 
		"appname" : appname, 
		"viewname" : viewname, 
		"colum" : columnorfield.toString(),
		"action" : action,
		"criteria" : restriction
	});

	if(Array.isArray(tempresult)){
		tempresult = tempresult[0];
	}
	
	return tempresult;
}

function $$DbColumn(notused, serverdb, viewname, column) {
	var nsfname = ((Array.isArray(serverdb) && serverdb.length > 0) ? serverdb[1] : serverdb);
	if(nsfname == ""){
		if(_DOCOVAEdition == "SE"){
			nsfname = (typeof docInfo != "undefined" && typeof docInfo.appName != "undefined" ? docInfo.appName : (typeof docInfo != "undefined" && typeof docInfo.AppID != "undefined" ? docInfo.AppID : ""));
		}else{
			nsfname = (docInfo && docInfo.NsfName ? docInfo.NsfName : "");		
		}
	
	}
	
	
	if(_DOCOVAEdition == "SE"){
		var tempresult = window.top.Docova.Utils.dbColumnNEW({
			"servername" : (Array.isArray(serverdb)? serverdb[0] : ""), 
			"nsfname" : nsfname, 
			"viewname" : viewname, 
			"column" : column.toString(), 
			"delimiter" : ":", 
			"alloweditmode" : false, 
			"secure" : docInfo.SSLState,
			"returnarray" : true
		});
	}else{
		var tempresult = window.top.Docova.Utils.dbColumn({
			"servername" : (Array.isArray(serverdb)? serverdb[0] : ""), 
			"nsfname" : nsfname, 
			"viewname" : viewname, 
			"column" : column.toString(), 
			"delimiter" : ":", 
			"alloweditmode" : false, 
			"secure" : docInfo.SSLState,
			"returnarray" : true
		});	
	}
	
	if(!Array.isArray(tempresult) && tempresult != ""){
		if(tempresult === "404"){
			tempresult = "";
		}else if ( typeof tempresult === "undefined"){
			tempresult = "";
		}
	}
	if(Array.isArray(tempresult) && tempresult.length == 1){
		tempresult = tempresult[0];
	}

	if(!Array.isArray(tempresult) && tempresult && tempresult != ""){
		tempresult = tempresult.split(":");
	}
	
	return tempresult;
}

function $$DbExists() {}

function $$DbLookup(notused, serverdb, viewname, key, columnorfield, keywords) {
	var failsilent = false;
	var returndocid = false;
	
	if(typeof keywords != "undefined"){
		var tempkeywords = (Array.isArray(keywords) ? keywords.join(",") : keywords);
		if(tempkeywords.toUpperCase().indexOf("FAILSILENT") > -1){
			failsilent = true;	
		}
		if(tempkeywords.toUpperCase().indexOf("RETURNDOCUMENTUNIQUEID") > -1){
			returndocid = true;	
		}		
	}
	
	var nsfname = ((Array.isArray(serverdb) && serverdb.length > 0) ? serverdb[1] : serverdb);
	if(nsfname == ""){
		if(_DOCOVAEdition == "SE"){
			nsfname = (docInfo && docInfo.appName ? docInfo.appName : "");
		}else{
			nsfname = (docInfo && docInfo.NsfName ? docInfo.NsfName : "");	
		}
	}

	var tempresult = window.top.Docova.Utils.dbLookup({
		"servername" : "", 
		"nsfname" : nsfname, 
		"viewname" : viewname, 
		"key" : key,
		"columnorfield" : columnorfield.toString(), 
		"delimiter" : ":", 
		"alloweditmode" : false, 
		"failsilent" : failsilent,
		"returndocid" : returndocid,
		"secure" : docInfo.SSLState
	});
	
	if(tempresult && tempresult != ""){
		tempresult = tempresult.split(":");
	}else{
		tempresult = "";
	}
	
	return tempresult;
}

function $$DbManager() {}

function $$DbName() {
	if(_DOCOVAEdition == "SE"){
		var server = "";
		var db = (docInfo && docInfo.appName ? docInfo.appName : "");
	}else{
		var server = (docInfo && docInfo.ServerName ? docInfo.ServerName : "");
		var db = (docInfo && docInfo.NsfName ? docInfo.NsfName : "");		
	}
	
	var result = [server, db];
	return result;
}

function $$DbTitle() {
	var result = "";
	
	var uiw = Docova.getUIWorkspace(document);
	appobj = uiw.getCurrentApplication();
	if(appobj){
		result = appobj.appName;		
	}
	
	return result;
}

function $$DeleteDocument() {}
function $$DeleteField() {}

function $$DialogBox(form, flags, title, cb) {
	var uidoc = Docova.getUIDocument();
	var doc = null;
	if(uidoc && !uidoc.isNewDoc){
		doc = uidoc.document;
	}else{
		var uiview = Docova.getUIView();
		if(uiview){
			var tempentry = uiview.getCurrentEntry();
			if(tempentry){
				doc = new DocovaDocument(uiview, tempentry, tempentry)
			}
		}	
	}
	
	var autoHorzFit = false;
	var autoVertFit = false;
	var noCancel = false;
	var noNewFields = false;
	var noFieldUpdate = false;
	var readOnly = false;
	var sizeToTable = false;
	var noOkCancel = false;
	var okCancelAtBottom = false;
	var noNote = false;
	
	if(typeof flags != 'undefined'){
		if(Array.isArray(flags)){
			flags = flags.join(":");
		}
		flags = flags.toUpperCase();
		autoHorzFit = (flags.indexOf("[AUTOHORZFIT]") > -1 || flags.indexOf("AUTOHORZFIT") > -1);
		autoVertFit = (flags.indexOf("[AUTOVERTFIT]") > -1 || flags.indexOf("AUTOVERTFIT") > -1);
		noCancel = (flags.indexOf("[NOCANCEL]") > -1 || flags.indexOf("NOCANCEL") > -1);
		noNewFields = (flags.indexOf("[NONEWFIELDS]") > -1 || flags.indexOf("NONEWFIELDS") > -1);
		noFieldUpdate = (flags.indexOf("[NOFIELDUPDATE]") > -1 || flags.indexOf("NOFIELDUPDATE") > -1);
		readOnly = (flags.indexOf("[READONLY]") > -1 || flags.indexOf("READONLY") > -1);
		sizeToTable = (flags.indexOf("[SIZETOTABLE]") > -1 || flags.indexOf("SIZETOTABLE") > -1);
		noOkCancel = (flags.indexOf("[NOOKCANCEL]") > -1 || flags.indexOf("NOOKCANCEL") > -1);
		okCancelAtBottom = (flags.indexOf("[OKCANCELATBOTTOM]") > -1 || flags.indexOf("OKCANCELATBOTTOM") > -1);
		noNote = (flags.indexOf("[NONOTE]") > -1 || flags.indexOf("NONOTE") > -1);
		if(noNote){
			noNewFields = true;
			noFieldUpdate = true;
			doc = null;
		}
	}	

	
	if(doc || noNote){	
		var uiw = Docova.getUIWorkspace();
		uiw.dialogBox(form, autoHorzFit, autoVertFit, noCancel, noNewFields, noFieldUpdate, readOnly, title, doc, sizeToTable, noOkCancel, okCancelAtBottom, cb);
	}
}

function $$Do(argumentlist) {
	for (var x = 0; x < argumentlist.length; x++) {
		if (x == argumentlist.length - 1) {
			return argumentlist[x]();
		} else {
			argumentlist[x]();
		}
	}
}

function $$DocFields() {
	var result = [];
	var uidoc = Docova.getUIDocument();
	if(uidoc){
		var tempfields = uidoc.getFieldNames();
		for(var fieldobj in tempfields){
			if(tempfields.hasOwnProperty(fieldobj)){
				result.push(tempfields[fieldobj][0]);
			}
		}
	}
	
	return result;
}

function $$DocLength() {}

function $$DocNumber() { return "";}

function $$DocumentUniqueID() {
	var result = "";
	
	if($$IsNewDoc()){		
		var docid = jQuery.trim(jQuery("input[name=unid]:first").val());
		if(typeof docid !== 'undefined' && docid !== null && docid !== ""){
			result = docid;
		}
	}else{
		if(docInfo && docInfo.DocID){
			result = docInfo.DocID;
		}		
	}	
	
	return result;
}

function $$Domain() {}

function $$DoWhile(argumentlist) {
	var result = false;
	if(!(argumentlist && Array.isArray(argumentlist) && argumentlist.length > 1)){
		return result;
	}
	var conditionindex = (argumentlist.length - 1);
	var conditionmet = true;
	while(conditionmet == true){
		for (var x = 0; x < conditionindex; x++) {
			argumentlist[x]();
		}		
		conditionmet = argumentlist[conditionindex]();
	}
	result = true;
	
	return result;
}

function $$Elements(argument) {
	var result = 0;
	if(typeof argument == 'undefined'){return result;}
	if(Array.isArray(argument)){
		result = argument.length;
	}
	
	return result;
}

function $$EmbCalculate(embview, embfunction, embcolumn) {
	var result = 0;
	
	embview = $('#' + embview);
	if (typeof embview == 'undefined' || !embview || !embview.length) { return result; }
	
	var table_content;
	if ( embview.hasClass("datatable")){
		table_content = embview;
	}else{
		 table_content= embview.get(0).contentWindow.document.getElementById('divViewContent');
		
	}
	if (!table_content || typeof table_content == 'undefined') { return result; }
	
	var rwcount = 1;
	var value = null;
	table_content = $(table_content);
	table_content.find('tbody tr').each(function() {
		if ($(this).attr('isResponse') != 'true' && $(this).attr('isCategory') != 'true' && !$(this).hasClass("disland_templ_row") && $(this).attr("dtremoved") != "1")
		{
			var clcount = 0;
			var cellval = 0;
			$(this).children('td').each(function(){
				if ( $(this).hasClass("listitem") || $(this).hasClass("diitem")){
					clcount++;	
				}
				if (clcount == embcolumn) {
					cellval = $(this).text();
					if (cellval.indexOf('$') == 0 || cellval.indexOf('+') == 0 || cellval.indexOf('%') == 0 || cellval.indexOf('#') == 0) {
						cellval = cellval.substring(1);
					}
					cellval = parseFloat(cellval);
					return false;
				}
				
			});

			switch(embfunction) {
				case 'count':
					value = rwcount;
					break;
				case 'avg':
				case 'sum':
					if (value !== null) {
						value += cellval;
					}
					else {
						value = cellval;
					}
					break;
				case 'min':
					if (value === null) {
						value = cellval;
					}
					else if (cellval < value) {
						value = cellval;
					}
					break;
				case 'max':
					if (value === null) {
						value = cellval;
					}
					else if (cellval > value) {
						value = cellval;
					}
					break;
			}
			
			rwcount++;
		}
	});
	
	if (!value) { return result; }
	if (embfunction == 'avg') {
		result = Math.round(value / rwcount);
	}
	else {
		result = value;
	}
	
	return result;
}

function $$Ends(contentstring, searchstring) {
	var result = false;	
	
	if(typeof contentstring == 'undefined' || typeof contentstring == 'undefined'){return result;}
	var isarray1 = Array.isArray(contentstring);
	var isarray2 = Array.isArray(searchstring);

	var tempcontentlist = (isarray1 ? contentstring.slice()	: [contentstring]);
	var tempsearchlist = (isarray2 ? searchstring.slice()	: [searchstring]);

	for(var c=0; c<tempcontentlist.length; c++){
		for(var s=0; s<tempsearchlist.length; s++){
			if(tempcontentlist[c].indexOf(tempsearchlist[s], tempcontentlist[c].length - tempsearchlist[s].length) > -1){
				result = true;
				return result;
			}
		}
	}
	
	return result;
}

function $$Environment(varname, varvalue) {
  var result = "";
  if(varname === undefined || varname === ""){
	  return result;
  }
  if(varvalue === undefined || varvalue === null){
		result = Docova.Utils.getCookie({keyname: varname, ignorecase: true, httpcookie: true});	  
  }else{
	Docova.Utils.setCookie({keyname: varname, keyvalue: varvalue.toString(), httpcookie: true});
  }
  return result;
}

function $$Error() {
	return "@ERROR";
}

function $$Eval() {}

function $$Exp(argument) {	
	if(typeof argument == 'undefined'){return $$Error();}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.exp(argument[i]);
		}
	}else{
		argument = Math.exp(argument);
	}
	
	return argument;
}

function $$Explode(content, separators, includeempties, breakonnewline, noescape) {
	if(typeof content == 'undefined'){return false;}
	
	var contentarray = (Array.isArray(content) ? content.slice() : [content]);

	var delimeters = (typeof separators == 'undefined' ? " ,;".split("") : (Array.isArray(separators) ? separators.slice() : [separators]));
	if(typeof includeempties == 'undefined'){
		var includeempties = false;
	}
	
	if(typeof breakonnewline == 'undefined'){
		var breakonnewline = true;
	}	
	
	if(breakonnewline == true){
		delimeters.push("\n");
	}	

	if(typeof noescape == 'undefined'){
		var noescape = false;
	}

	var escapechar = (typeof noescape !== "undefined" && noescape === true ? '' : '\\');

	while (delimeters.length > 0) {
		for (var i = 0; i < contentarray.length; i++) {
			var templist = [];
			templist.length = 0;
			
			var startpos = 0;
			var endpos = 0;
			var keepgoing = true;
			while(keepgoing){
				var performslice = false;
				var endpos = contentarray[i].indexOf(delimeters[0], endpos);
				if(endpos > startpos){
					if(escapechar != "" && contentarray[i].slice(endpos-1, endpos) == escapechar){
						endpos = endpos + delimeters[0].length;
					}else{
						performslice = true;
					}
				}else if(endpos == startpos){
					performslice = true;
				}else{
					endpos = contentarray[i].length;
					performslice = true;
					keepgoing = false;
	            }
				if(performslice){
					templist.push(contentarray[i].slice(startpos, endpos));
					startpos = endpos + delimeters[0].length;
					endpos = startpos;
				}    		
			}
	        	
			if(Array.isArray(templist) && templist.length > 0){
				contentarray = contentarray.slice(0, i).concat(templist).concat(contentarray.slice(i + 1));
			}
		}
		delimeters.shift();
	}
	if(!includeempties){
		for(var k=contentarray.length; k>=0; k--){
			if(contentarray[k] == ""){
				contentarray.splice(k, 1);
			}
		}
	}
	
	return contentarray;
}

function $$Failure(message) {
	return messsage;
}

function $$False() {
	return false;
}

function $$FileDir() {}
function $$FloatEq() {}

function $$For(argumentlist) {
	var result = false;
	if(!(argumentlist && Array.isArray(argumentlist) && argumentlist.length > 3)){
		return result;
	}
	argumentlist[0]();
	var conditionmet = argumentlist[1]();
	while(conditionmet == true){
		for (var x = 3; x < argumentlist.length; x++) {
			argumentlist[x]();
		}		
		argumentlist[2]();
		conditionmet = argumentlist[1]();
	}
	result = true;
	
	return result;
}

function $$Format(expr, fmt){
	var result = expr;
	
	var isstring = false;
	var isnumber = false;
	var isdate = false;
	
	var tempval = $$Date(expr);
	if(tempval !== ""){
		isdate = true;
	}else{
		isnumber = jQuery.isNumeric(expr);
		if(isnumber){
			tempval = Number(expr);
		}else{
			isstring = true;
			tempval = expr;
		}		
	}
	
	
	if(fmt){
		if(fmt == "General Number"){
			if(isnumber){
				result = tempval.toString();
			}
		}else if(fmt == "Currency"){
			if(isnumber){
				if(docInfo && docInfo.ThousandsSeparator){
					result =  parseInt(tempval).toString().replace(/\B(?=(\d{3})+(?!\d))/g, docInfo.ThousandsSeparator) + docInfo.DecimalSeparator + (tempval % 1).toFixed(2).substring(2);
				}else{
					result =  parseInt(tempval).toString() + docInfo.DecimalSeparator + (tempval % 1).toFixed(2).substring(2);					
				}
			}				
		}else if(fmt == "Fixed"){
			if(isnumber){
				result =  parseInt(tempval).toString() + docInfo.DecimalSeparator + (tempval % 1).toFixed(2).substring(2);									
			}							 
		}else if(fmt == "Standard"){
			if(isnumber){
				if(docInfo && docInfo.ThousandsSeparator){
					result =  parseInt(tempval).toString().replace(/\B(?=(\d{3})+(?!\d))/g, docInfo.ThousandsSeparator) + docInfo.DecimalSeparator + (tempval % 1).toFixed(2).substring(2);
				}else{
					result =  parseInt(tempval).toString() + (docInfo && docInfo.DecimalSeparator ? docInfo.DecimalSeparator : ".") + (tempval % 1).toFixed(2).substring(2);					
				}				
			}								
		}else if(fmt == "Percent"){
			if(isnumber){
				result = parseInt(tempval * 100).toString();
				if(((tempval * 100) % 1).toFixed(2).substring(2) !== "00"){
					result += (docInfo && docInfo.DecimalSeparator ? docInfo.DecimalSeparator : ".");
					result += ((tempval * 100) % 1).toFixed(2).substring(2);					
				}
				result += "%";
			}						
		}else if(fmt == "Scientific"){
			if(isnumber){
				result = tempval.toExponential(2);
			}						
		}else if(fmt == "Yes/No"){
			if(isnumber){
				if(tempval === 0){
					result = "No";
				}else{
					result = "Yes";
				}
			}							
		}else if(fmt == "True/False"){
			if(isnumber){
				if(tempval === 0){
					result = "False";
				}else{
					result = "True";
				}
			}		
		}else if(fmt == "On/Off"){
			if(isnumber){
				if(tempval === 0){
					result = "Off";
				}else{
					result = "On";
				}
			}		
		}else if(fmt == "General Date"){
			if(isdate){
				var dateformat = (docInfo && docInfo.SessionDateFormat ? docInfo.SessionDateFormat : "dd-mmm-yy").replace('yyyy', 'yy').replace('yy', 'yyyy');
				result = $$FormatDate(tempval, dateformat);
			}
		}else if(fmt == "Long Date"){
			if(isdate){
				var userLang = navigator.language || navigator.userLanguage;
				var options = { year: "numeric", month: "long", day: "numeric" };
				result = tempval.toLocaleDateString(userLang, options);
			}
		}else if(fmt == "Medium Date"){
			if(isdate){
				var dateformat = (docInfo && docInfo.SessionDateFormat ? docInfo.SessionDateFormat : "dd-mmm-yy").replace('yyyy', 'yy').replace('yy', 'yyyy');
				result = $$FormatDate(tempval, dateformat);
			}
		}else if(fmt == "Short Date"){
			if(isdate){
				var dateformat = (docInfo && docInfo.SessionDateFormat ? docInfo.SessionDateFormat : "dd-mmm-yy").replace('yyyy', 'yy').replace('yy', 'yyyy');
				result = $$FormatDate(tempval, dateformat);
			}
		}else if(fmt == "Long Time"){
			if(isdate){
				result = $$FormatDate(tempval, "hh:nn:ss");
			}
		}else if(fmt == "Medium Time"){
			if(isdate){
				result = $$FormatDate(tempval, "hh:nn AM/PM");	
			}
		}else if(fmt == "Short Time"){
			result = $$FormatDate(tempval, "hh:nn");
		}else if(isdate){
			result = $$FormatDate(tempval, fmt);
		}
	}else{
		result = tempval.toString();
	}

	return result;
}

function $$FormatDate(dateval, dateformat){
	var result = "";
		    
    if(dateval instanceof Date){
       var outputstr = dateformat;
             
       var monthnames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
       var daysofweek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
              
	   var yval = dateval.getFullYear();
	   var mval = dateval.getMonth() + 1;
	   var dval = dateval.getDate();
	   var hval = dateval.getHours(); 
	   var nval = dateval.getMinutes();
	   var sval = dateval.getSeconds();
	   var wval = dateval.getDay() + 1;
	   
	   var isampm = ((dateformat.toLowerCase().indexOf("am/pm") > -1) || (dateformat.toLowerCase().indexOf("a/p") > -1) || (dateformat.toLowerCase().indexOf("ampm") > -1));
	   
	   var isleap =   (((yval & 3) != 0) ? false : ((yval % 100) != 0 || (yval % 400) == 0));
	   var dayCount = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
	   var doy = dayCount[mval -1 ] + dval + ((mval > 2 && isleap) ? 1 : 0);
	   
	   if(!isNaN(yval) && !isNaN(mval) && !isNaN(dval) && !isNaN(hval) && !isNaN(nval) && !isNaN(sval)){
		  //c
		  outputstr = outputstr.replace(/c/g, (("0" + mval.toString()).slice(-2) + "/" + ("0" + dval.toString()).slice(-2) + "/" + yval.toString().slice(-2) + " " + (hval > 12 && isampm ? hval - 12 : hval).toString() + ":" + ("0" + nval.toString()).slice(-2) + ":" + ("0" + sval.toString()).slice(-2)));   
	   }  
	   
	   var correctcase = false;
	   if(!isNaN(hval) && !isNaN(nval) && !isNaN(sval)){
			  //hh
			  outputstr = outputstr.replace(/hh/g, ("0" + (hval > 12 && isampm ? hval - 12 : hval).toString()).slice(-2));
			  //h
			  outputstr = outputstr.replace(/h/g, (hval > 12 && isampm ? hval - 12 : hval).toString());		  		  
			  //nn
			  outputstr = outputstr.replace(/nn/g, ("0" + nval.toString()).slice(-2));
			  //n
			  outputstr = outputstr.replace(/n/g, nval.toString());		  		  
			  //ss
			  outputstr = outputstr.replace(/ss/g, ("0" + sval.toString()).slice(-2));
			  //s
			  outputstr = outputstr.replace(/s/g, sval.toString());		  		  
			  //ttttt
			  outputstr = outputstr.replace(/ttttt/g, ((hval > 12 && isampm ? hval - 12 : hval).toString() + ":" + ("0" + nval.toString()).slice(-2) + ":" + ("0" + sval.toString()).slice(-2)));		   
			  //c
			  outputstr = outputstr.replace(/c/g, ((hval > 12 && isampm ? hval - 12 : hval).toString() + ":" + ("0" + nval.toString()).slice(-2) + ":" + ("0" + sval.toString()).slice(-2)));   		  
			  //AM/PM
			  outputstr = outputstr.replace(/AM\/PM/g, (hval >= 12 ? "PM" : "AM"));   		  
			  //am/pm
			  if(outputstr.indexOf("am/pm") > -1){
				  outputstr = outputstr.replace(/am\/pm/g, (hval >= 12 ? "PM" : "AM"));
				  correctcase = true;
			  }
			  //A/P
			  outputstr = outputstr.replace(/A\/P/g, (hval >= 12 ? "P" : "A"));   		  
			  //a/p
			  outputstr = outputstr.replace(/a\/p/g, (hval >= 12 ? "p" : "a"));   		  
			  //AMPM
			  outputstr = outputstr.replace(/AMPM/g, (hval >= 12 ? "PM" : "AM"));   		  		  
	   }		   	   
	   
	   if(!isNaN(yval) && !isNaN(mval) && !isNaN(dval)){
		  //dddddd
		  outputstr = outputstr.replace(/dddddd/g, ("~M" + (mval-1).toString() + "~ " + dval.toString() + " " + yval.toString()));
		  //ddddd
		  outputstr = outputstr.replace(/ddddd/g, (("0" + mval.toString()).slice(-2) + "/" + ("0" + dval.toString()).slice(-2) + "/" + yval.toString().slice(-2)));		  
		  //dddd
		  outputstr = outputstr.replace(/dddd/g, "~W" + (wval-1).toString() + "~");		  
		  //dd
		  outputstr = outputstr.replace(/dd/g, ("0" + dval.toString()).slice(-2));
		  //d
		  outputstr = outputstr.replace(/d/g, dval.toString());
		  //yyyy
		  outputstr = outputstr.replace(/yyyy/g, ("000" + yval.toString()).slice(-4));
		  //yy
		  outputstr = outputstr.replace(/yy/g, ("0" + yval.toString()).slice(-2));
		  //y
		  outputstr = outputstr.replace(/y/g, doy.toString());
		  //mmmm
		  outputstr = outputstr.replace(/mmmm/g, "~M" + (mval-1).toString() + "~");
		  //mmm
		  outputstr = outputstr.replace(/mmm/g, "~MS" + (mval-1).toString() + "~");
		  //mm
		  outputstr = outputstr.replace(/mm/g, ("0" + mval.toString()).slice(-2));
		  //m
		  outputstr = outputstr.replace(/m/g, mval.toString());		  
		  //ww
		  outputstr = outputstr.replace(/ww/g, ("0" + wval.toString()).slice(-2));
		  //w
		  outputstr = outputstr.replace(/w/g, wval.toString());	
		  //c
		  outputstr = outputstr.replace(/c/g, (("0" + mval.toString()).slice(-2) + "/" + ("0" + dval.toString()).slice(-2) + "/" + yval.toString().slice(-2)));   		  
		  //q
		  outputstr = outputstr.replace(/q/g, (mval > 9 ? "4" : (mval > 6 ? "3" : (mval > 3 ? "2" : "1"))));		  
		  
		  //as a final step resolve any month names which were left to the end so as to not conflict with other codes
		  for(var x=0; x<monthnames.length; x++){
			  var re = new RegExp("~M" + x.toString() + "~");
			  outputstr = outputstr.replace(re, monthnames[x]);
			  
			  re = new RegExp("~MS" + x.toString() + "~");
			  outputstr = outputstr.replace(re, monthnames[x].slice(0, 3));	  
		  }
		  //as a final step resolve any week day names which were left to the end so as to not conflict with other codes
		  for(var x=0; x<daysofweek.length; x++){
			  var re = new RegExp("~W" + x.toString() + "~");
			  outputstr = outputstr.replace(re, daysofweek[x]);	  
		  }		  
       }    

	   if(correctcase){
		  outputstr = outputstr.replace(/AM/g, "am");
		  outputstr = outputstr.replace(/PM/g, "pm");
	   }
	   
	  result = outputstr;
		  
	}

	return result;
}

function $$FormLanguage() {}
function $$GetAddressBooks() {}
function $$GetCurrentTimeZone() {}

function $$GetDocField(docunid, fieldname) {
	var result = null;
	
	if(!docunid || (docunid == $$DocumentUniqueID())){
		//-- get field from current doc
		var uiw = Docova.getUIWorkspace(document);
		var uidoc = uiw.currentDocument;		
		result = uidoc.getField(fieldname);
	}else if(docunid && docunid != ""){
		//-- get field from back end doc
		var uiw = Docova.getUIWorkspace(document);
		var app = uiw.getCurrentApplication();
		var doc = app.getDocument(docunid);
		if(doc){
			result = doc.getField(fieldname);
		}
	}
	return result;
}

function $$GetField(fieldname) {
	return $$GetDocField(null, fieldname);
}

function $$GetFocusTable() {}
function $$GetHTTPHeader() {}
function $$GetIMContactListGroupNames() {}

function $$GetProfileField(profilename, fieldname, key) {
	if(typeof profilename == 'undefined' || profilename == ""){
		return false;
	}
	if(typeof fieldname == 'undefined' || fieldname == ""){
		return false;
	}
	
	var uiw = Docova.getUIWorkspace(document);
	var app = uiw.getCurrentApplication();
	
	var fieldvals = app.getProfileFields(profilename, fieldname, key);
	if(fieldvals && fieldvals[fieldname]){
		return fieldvals[fieldname];
	}else{
		return "";
	}
}

function $$GetViewInfo() {}
function $$HardDeleteDocument() {}
function $$HashPassword() {}

function $$Hour(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getHours();			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$Hide(argument) {
	if (!argument) { return; }
	
	if (typeof argument == 'string') {
		jQuery(argument).hide();
	}
	else if (argument instanceof jQuery) {
		argument.hide();
	}
	else if (typeof HTMLElement === 'object' && argument instanceof HTMLElement) {
		jQuery(argument).hide();
	}
	else if (typeof argument === "object" && argument !== null && argument.nodeType === 1 && typeof argument.nodeName === 'string') {
		jQuery(argument).hide();
	}
}

function $$If(argumentlist) {
	//if only a single argument return its evaluated value
	if(argumentlist.length == 1){
		return argumentlist[0]();
	}
	
	//compute the comparison and result
	for (var x = 0; x < argumentlist.length; x+=2) {
		if (x == argumentlist.length - 1) {
			return argumentlist[x]();
		} else if (argumentlist[x]() === true) {
			return argumentlist[x + 1]();
		}
	}
}

function $$Implode(valuelist, separator) {
	if(typeof valuelist == 'undefined'){return;}
	
	var sep = (typeof separator == 'undefined' ? " " : separator);
	
	var result = "";
	if(Array.isArray(valuelist)){
	    result = valuelist.join(sep);
	 }else{
		result = valuelist;
	 }
	
	return result;
}


function $$InheritedDocumentUniqueID() {}

function $$InStr(param1, param2, param3, param4) {
	var result = 0;
	
	var beginpos = 0;
	var sourcestring = "";
	var searchstring = "";
	var comparetype = 0;
	   
	if(typeof param1 == "number"){
		beginpos = param1 - 1;  
		sourcestring = param2;
		searchstring = param3;
		if(typeof param4 == "number"){
			comparetype = param4; 
		}
	}else if(typeof param1 == "string"){
		sourcestring = param1;
		searchstring = param2;
		if(typeof param3 == "number"){
			 comparetype = param3;
		}
	}
	
	var temppos = -1;
	if(comparetype == 0){
		//-- case sensitive pitch sensitive
		temppos = sourcestring.indexOf(searchstring, beginpos);
	}else if(comparetype == 1){
		//-- case insensitive pitch sensitive
		temppos = sourcestring.toUpperCase().indexOf(searchstring.toUpperCase(), beginpos);
	}else if(comparetype == 4){
		//-- case sensitive pitch insensitive
		var tempsourcestring = $$Ascii(sourcestring);
		temppos = tempsourcestring.indexOf($$Ascii(searchstring), beginpos);		
	}else if(comparetype == 5){
		//-- case insensitive pitch insensitive
		var tempsourcestring = $$Ascii(sourcestring);
		temppos = tempsourcestring.toUpperCase().indexOf($$Ascii(searchstring.toUpperCase()), beginpos);		
	}     
	
	result = temppos + 1;
	return result;
}

function $$Integer(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			var tempnum = Number(argument[i]);
			if(isNaN(tempnum)){
				tempnum = $$Error();
			}else{
				tempnum = Math.floor(tempnum);
			}
			argument[i] = tempnum;
		}
	}else{
		var tempnum = Number(argument);
		if(isNaN(tempnum)){
			tempnum = $$Error();
		}else{
			tempnum = Math.floor(tempnum);
		}
		argument = tempnum;		
	}
	return argument;	
}
function $$IsAgentEnabled() {}
function $$IsAppInstalled() {}
function $$IsAvailable() {}

function $$IsDate(dateval){
	var result = false;
	
	if(typeof dateval === 'undefined' || dateval === null){
		return result;
	}
	
	var tempdate = dateval;

	if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
		tempdate.setHours(0);
		tempdate.setMinutes(0);
		tempdate.setSeconds(0);	
		if(!isNaN(tempdate)){
			result = true;
		}				
	}else if(typeof tempdate == 'string'){
		tempdate = Docova.Utils.convertStringToDate(tempdate);
		if(tempdate !== null){
			tempdate.setHours(0);
			tempdate.setMinutes(0);
			tempdate.setSeconds(0);
			if(!isNaN(tempdate)){
				result = true;
			}
		}
	}
	
	return result;
}

function $$IsDocBeingEdited() {
	var result = false;

	if(info && info.isDocBeingEdited && (info.isDocBeingEdited == "1" || info.isDocBeingEdited == "true" || info.isDocBeingEdited === true)){
		result = true;
	}
	
	return result;
}

function $$IsDocBeingLoaded() {}

function $$IsDocBeingRecalculated() {
	var result = false;
	
	if(info && info.isRefresh){
		result = true;
	}
	
	return result;
}

function $$IsDocBeingSaved() {}

function $$IsElement(value){
	var result = true;
	if(value === undefined){
		result = false;
	}
	return result;
}

function $$IsEmpty(value){
	var result = false;
	if(value === null || value === undefined){
		result = true;
	}
	return result;
}

function $$IsError(value) {
	return (value === "@ERROR");
}

function $$IsMember(value1, value2) {
	var result = false;
   if((typeof value1 == 'undefined') || (typeof value2 == 'undefined')){
	  return result;
   }
   
   var tempval2 = (Array.isArray(value2) ? value2.slice() : [value2]);
   
   if(Array.isArray(value1)){
	  for(var x=0; x<value1.length; x++){
		  if(tempval2.indexOf(value1[x]) > -1){
			  result = true;
		  }else{
			  result = false;
			  break;			  
		  }
	  } 
   }else{
	  if(tempval2.indexOf(value1) > -1){
		  result = true;
	  } 
   }
   
   return result;
}

function $$IsMobile() {
	var result = false;
	result = (docInfo && docInfo.isMobile && (docInfo.isMobile == true || docInfo.isMobile == "true" || docInfo.isMobile == 1 || docInfo.isMobile == "1"))
	return false;
}

function $$IsNewDoc() {
	return (docInfo && docInfo.isNewDoc && (docInfo.isNewDoc == "1" || docInfo.isNewDoc == "true" || docInfo.isNewDoc == true));
}

function $$IsNotMember(value1, value2) {
	var result = true;
	   if((typeof value1 == 'undefined') || (typeof value2 == 'undefined')){
		  return result;
	   }
	   
	   var tempval2 = (Array.isArray(value2) ? value2.slice() : [value2]);
	   
	   if(Array.isArray(value1)){
		  for(var x=0; x<value1.length; x++){
			  if(tempval2.indexOf(value1[x]) > -1){
				  result = false;
				  break;
			  }else{
				  result = true;
			  }
		  } 
	   }else{
		  if(tempval2.indexOf(value1) > -1){
			  result = false;
		  } 
	   }
	   
	   return result;
}

function $$IsNull(value) {
	return (value === null);
}

function $$IsNumber(argument) {
	var result = false;
	if(typeof argument == 'undefined'){return result;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			if(isNaN(argument[i])){
				result = false;
				break;
			}else{
				result = true;
			}
		}
	}else{
		result = ! isNaN(argument);
	}
	return result;	
}

function $$IsNumeric(argument){
	var result = true;
	if(typeof argument == 'undefined'){
		result = false;
		return result;
	}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){	
			var tempnum = Number(argument[i]);
			if(isNaN(tempnum)){
				result = false;
			}
		}
	}else{
		var tempnum = Number(argument);
		if(isNaN(tempnum)){
			result = false;
		}
	}
	
	return result;	
}

function $$IsResponseDoc() {
	var result = false;
	
	if(info && info.ParentDocID){
		result = true;
	}
	
	return result;
}

function $$IsText(argument) {
	var result = false;
	if(typeof argument == 'undefined'){return result;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			if(typeof argument[i] === "string"){
				result = true;
			}else{
				result = false;
				break;
			}
		}
	}else{
		result = (typeof argument === "string");
	}
	
	return result;		
}

function $$IsTime(argument) {
	var result = false;
	
	var tempdate = null;
	if(typeof argument == 'undefined'){return result;}
	
	var argarray = (Array.isArray(argument) ? argument.slice() : [argument]);
	
	for(var i=0; i<argarray.length; i++){
		tempdate = null;
		if(typeof argarray[i] == 'object' && Object.prototype.toString.call(argarray[i]) === '[object Date]'){
			tempdate = argarray[i];
		}else if(typeof argarray[i] == 'string'){
			tempdate = Docova.Utils.convertStringToDateTime(argarray[i]);
		}
			
		if(tempdate !== null){
			var yval = tempdate.getFullYear();
			var mval = tempdate.getMonth() + 1;
			var dval = tempdate.getDate();
			var hval = tempdate.getHours();
			var minval = tempdate.getMinutes();
			var sval = tempdate.getSeconds();
			if(yval > 0 || mval > 0 || dval > 0 || hval > 0 || minval > 0 || sval > 0){
				result = true;
			}else{
				result = false;
				break;
			}			
		}
	}
	
	return result;			
}

function $$IsUnavailable(fieldname) {
	var result = true;
	
	if(jQuery("#"+fieldname).length > 0){
		result = false;
	}
	
	if(result && jQuery("[name=" + fieldname + "]").length > 0){
		result = false;
	}
	
	if(result && docInfo && docInfo.DocID && docInfo.DocID != ""){
		//-- get field from back end doc
		var uiw = Docova.getUIWorkspace(document);
		var app = uiw.getCurrentApplication();
		var doc = app.getDocument(docInfo.DocID);
		if(doc){
			var fieldval = doc.getField(fieldname);
			if(fieldval !== null){
				result = false;
			}
		}
	}	
	
	return result;
}

function $$IsValid() {
	var result = true;
	
	 if(typeof window["InputValidation"] == "function"){
		 result = window["InputValidation"](_thisUIDoc);
	 }
	
	return result;
}

function $$Keywords() {}
function $$LanguagePreference() {}
function $$LaunchApp() {}

function $$LBound(arrayvar) {
	var result = false;
	if(Array.isArray(arrayvar)){
		result = 0;
	}
	return result;
}

function $$Left(stringtosearch, selection, compmethod) {
	//TODO - compmethod does not respect pitch insensitive option
	if(typeof stringtosearch == 'undefined'){return;}
	
	var searchtype = (typeof selection);
	if(searchtype == 'undefined'){return;}

	var result = "";
	var pos = -1;
	
	var isarray = Array.isArray(stringtosearch);
	var templist = (isarray ? stringtosearch.slice() : [stringtosearch]);
	
	for(var i=0; i<templist.length; i++){
		if(searchtype == "string"){
			if(compmethod !== undefined && (compmethod === 1 || compmethod === 5)){
				pos = templist[i].toUpperCase().indexOf(selection.toUpperCase());				
			}else{
				pos = templist[i].indexOf(selection);				
			}
			if(pos > -1){
				templist[i] = templist[i].substring(0, pos);
			}else{
				templist[i] = "";
			}
		}else if(searchtype == "number"){
			if(selection > -1){
				templist[i] = templist[i].substring(0, selection);
			}else if(selection == 0){
				templist[i] = "";
			}				
		}
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$LeftBack(stringtosearch, selection) {
	if(typeof stringtosearch == 'undefined'){return;}
	
	var searchtype = (typeof selection);
	if(searchtype == 'undefined'){return;}

	var result = "";
	var pos = -1;
	
	var isarray = Array.isArray(stringtosearch);
	var templist = (isarray ? stringtosearch.slice() : [stringtosearch]);
	
	for(var i=0; i<templist.length; i++){
		if(searchtype == "string"){
			pos = templist[i].lastIndexOf(selection);				
			if(pos > -1){
				templist[i] = templist[i].substring(0, pos);
			}else{
				templist[i] = "";
			}
		}else if(searchtype == "number"){
			if(selection > -1){
				templist[i] = templist[i].substring(0, templist[i].length - selection);
			}				
		}
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$Length(stringvalues) {
	if(typeof stringvalues == 'undefined'){return 0;}
	
	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	var lengthlist = new Array();
	
	for(var i=0; i<templist.length; i++){
		lengthlist.push(templist[i].length);
	}
	result = (isarray ? lengthlist.slice() : lengthlist[0]);
	
	return result;		
}

function $$Like(stringvars, patternvar, escapevar) {
	var result = false;
	
	if(typeof stringvars == 'undefined' || typeof patternvar == 'undefined'){
		return result;
	}
	
	var regex = null;
	var tempesc = (typeof escapevar !== 'undefined' && escapevar !== "" ? escapevar : "");
	var temppattern = "";
	var bufflen = tempesc.length;
	var buff = "";

	for(var i=0; i<patternvar.length; i++){
		curchar = patternvar.slice(i,i+1);
		if(curchar == "_" || curchar == "%"){
			if(tempesc !== "" && buff === tempesc){
				temppattern += curchar;
			}else{
				temppattern += buff.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');				
				temppattern += (curchar == "_" ? "." : ".*");				
			}
			buff = "";
		}else{
			buff += curchar;
			if(tempesc !== "" && buff.length > bufflen){
				temppattern += buff.slice(0, buff.length - bufflen).replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
				buff = buff.slice(buff.length - bufflen);
			}			
		}
	}
	temppattern += buff.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');

	regex = new RegExp(temppattern, "g");
	
	var tempvals = [];
	if(Array.isArray(stringvars)){
		tempvals = stringvars.slice();
	}else{
		tempvals = [stringvars];
	}
	
	for(var e=0; e<tempvals.length; e++){
		if(tempvals[e].match(regex)){
			result = true;
			break;
		}		
	}
	
	return result;
}

function $$Ln(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.log(argument[i]);
		}
	}else{
		argument = Math.log(argument);
	}
	
	return argument;
}

function $$Locale() {}

function $$LocationGetInfo() {
	return "";
}

function $$Log(numbervalues) {
	if(typeof numbervalues == 'undefined'){return;}
	
	var isarray = Array.isArray(numbervalues);
	var templist = (isarray ? numbervalues.slice() : [numbervalues]);
	
	for(var i=0; i<templist.length; i++){
		templist[i] = Math.log(Number(templist[i]))/ Math.LN10;
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;		
}

function $$LowerCase(stringvalues) {
	if(typeof stringvalues == 'undefined'){return;}
	
	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	
	for(var i=0; i<templist.length; i++){
		if(typeof templist[i] !== 'string'){
			templist[i] = templist[i].toString();
		}		
		templist[i] = templist[i].toLowerCase();
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;			
}

function $$MailSend( sendTo,  copyTo,  blindCopyTo,  subject,  remark,  bodyFields, flags, targetdoc, dontwait) {
	   var result = false;

		var includeLink = false;
		if(typeof flags != 'undefined'){
			if(Array.isArray(flags)){
				flags = flags.join(":");
			}
			flags = flags.toUpperCase();
			includeLink = (flags.indexOf("[INCLUDEDOCLINK]") > -1 || flags.indexOf("INCLUDEDOCLINK") > -1) 
		}
	   
		var uidoc = null;
		var doc = null;
		if(bodyFields || includeLink){
			if(targetdoc){
				doc = targetdoc;
			}else{
				uidoc = Docova.getUIDocument();
				if(uidoc){
					doc = uidoc.document;
				}
			}
		}
		
		var msgtxt = "";
		msgtxt = (remark ? remark + "\n" : "");
		if(bodyFields){
			if (!Array.isArray(bodyFields)){
				bodyFields = [bodyFields];
			}
			for(var x=0; x<bodyFields.length; x++){
				var fieldval = null;				
				if(doc){
					if(bodyFields[x] !== null && bodyFields[x] !== "" && bodyFields[x].match(/[:,'";#\+\?\=\s\\\/]/) === null){
						if(doc.hasItem(bodyFields[x])){
							fieldval = doc.getField(bodyFields[x]);
						}
					}
				}
				if(fieldval !== null){
					msgtxt += (Array.isArray(fieldval) ? fieldval.join("\n") : fieldval);
				}else{
					if(bodyFields[x] !== null){
						msgtxt += bodyFields[x];
					}
				}				
			}
		}
		
		var linktxt = "";
		if(includeLink && doc){
			linktxt = doc.getURL({mode: 'window'})	
		}

		
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/MessagingServices?OpenAgent";
		
		var request="";
		request += "<Request>";
		request += "<Action>MAILSEND</Action>";
		request += "<From></From>";
		request += "<SendTo><![CDATA[" + (sendTo ? (Array.isArray(sendTo) ? sendTo.join(",") : sendTo) : "") + "]]></SendTo>";
		request += "<CopyTo><![CDATA[" + (copyTo ? (Array.isArray(copyTo) ? copyTo.join(",") : copyTo) : "") + "]]></CopyTo>";
		request += "<BCC><![CDATA[" + (blindCopyTo ? (Array.isArray(blindCopyTo) ? blindCopyTo.join(",") : blindCopyTo) : "") + "]]></BCC>";
		request += "<Subject><![CDATA[" + (subject ? subject : "") + "]]></Subject>";
		if(_DOCOVAEdition == "SE"){
			request += "<Body><![CDATA[" + msgtxt + "]]></Body>";
		}else{
			request += "<Message><![CDATA[" + msgtxt + "]]></Message>";
		}
		request += "<IncludeLink>" + includeLink + "</IncludeLink>";
		if(includeLink === true){
			request += "<Link><![CDATA[";
	    		request += linktxt;
	    	request += "]]></Link>";						
		}
		request += "</Request>";

		
		if(typeof dontwait != 'undefined' && dontwait === true){
			$.ajax({
				'type' : "POST",
				'url' : url,
				'processData' : false,
				'data' : encodeURIComponent(request),
				'contentType': false,
				'async' : true,
				'dataType' : 'xml'
			});	
		}else{
			var httpObj = new objHTTP();
			httpObj.supressWarnings = true ;
			if(httpObj.PostData(encodeURIComponent(request), url))
			{
				 if(httpObj.status=="SUCCESS" || httpObj.status=="OK") //all OK
				{
			            result = true;
				}
			}			
		}		

		return result;
}

function $$Matches() {}

function $$Max(param1, param2) {
	var result = null;

	if(typeof param1 == "undefined" && typeof param2 == "undefined"){
	    //do nothing
	}else if(typeof param2 == "undefined"){
		if(!Array.isArray(param1)){
			result = param1;
		}else if(param1.length < 2){
			result = param1[0];
		}else{
			var tempres = param1[0];
			for(var i=1; i<param1.length; i++){
				if(tempres < param1[i]){
					tempres = param1[i];
				}
			}
			result = tempres;
		}
	}else{
		var tempres = [];
		
		var temparray1 = (Array.isArray(param1) ? param1.slice() : [param1]);
		var temparray2 = (Array.isArray(param2) ? param2.slice() : [param2]);
		
		var a1len = temparray1.length;
		var a2len = temparray2.length;
		var maxlen = Math.max(a1len, a2len);
		var a1i = 0;
		var a2i = 0;
		
		for(var i=0; i<maxlen; i++){
			a1i = (i>=a1len ? a1i : i);
			a2i = (i>=a2len ? a2i : i);
			
			if(temparray1[a1i] >= temparray2[a2i]){
				tempres.push(temparray1[a1i]);
			}else if(temparray1[a1i] < temparray2[a2i]){
				tempres.push(temparray2[a2i]);
			}
		}
		
		result = tempres.slice();
	}
	
	return (Array.isArray(result) && result.length == 1 ? result[0] : result);	
	
}

function $$Member(searchvalue, arrayvalue) {
	var result = 0;

	var pos = $$ArrayGetIndex(arrayvalue, searchvalue, 0);	
	if(pos !== null && pos > -1){
		result = pos + 1;
	}
	
	return result;
}

function $$MessageBox(promptmsg, flags, title){
	var result = 0;

	if(typeof flags == 'undefined'){
		var flags = 0;
	}

	if(typeof title == 'undefined'){
		var title = "";
	}	
	
	var type = "";
	var msg = "";
	if(title !== ""){
		var headerwidth = Math.max(title.length + 2, 75);
		var headergap = parseInt((headerwidth - title.length) / 2, 10);
		msg += Array(headerwidth).join("-");
		msg += "\n";
		msg += Array(headergap).join(" ");
		msg += title;
		msg += Array(headergap).join(" ");		
		msg += "\n";
		msg += Array(headerwidth).join("-");
		msg += "\n";
	}
	if(promptmsg !== ""){
		msg += "\n";
		msg += promptmsg;
	}
	
	var tempflags = flags;
	if(tempflags >= 4096){
		tempflags = tempflags - 4096;
	}
	if(tempflags >= 512){
		tempflags = tempflags - 512;
	}
	if(tempflags >= 256){
		tempflags = tempflags - 256;
	}
	if(tempflags >= 64){
		tempflags = tempflags - 64;
	}
	if(tempflags >= 48){
		tempflags = tempflags - 48;
	}
	if(tempflags >= 32){
		tempflags = tempflags - 32;
	}
	if(tempflags >= 16){
		tempflags = tempflags - 16;
	}	
	
	if(tempflags == 5){
		type = "MB_RETRYCANCEL";	
		msg += "\n\nPress [Ok] to Retry, or [Cancel] to stop.";		
	}else if(tempflags == 4){
		type = "MB_YESNO";	
		msg += "\n\nPress [Ok] for Yes, or [Cancel] for No.";
	}else if(tempflags == 3){
		type = "MB_YESNOCANCEL";
		msg += "\n\nPress [Ok] for Yes, or [Cancel] for No.";
	}else if(tempflags == 2){
		type = "MB_ABORTRETRYIGNORE";	
		msg += "\n\nPress [Ok] to Retry, or [Cancel] to Abort.";			
	}else if(tempflags == 1){
		type = "MB_OKCANCEL";	
	}else{
		type = "MB_OK";		
	}	
	
	if(type == "MB_OK"){
		//just ok button
		alert(msg);
		result = 1;
	}else{	
		//more than just ok button		
		var ans = confirm(msg);
		if(type == "MB_OKCANCEL"){
			result = (ans === true ? 1 : 2);
		}else if(type == "MB_ABORTRETRYIGNORE"){
			result = (ans === true ? 4 : 3);  //TODO ignore not implemented	
		}else if(type == "MB_YESNOCANCEL"){
			result = (ans === true ? 6 : 7); //TODO cancel not implemented
		}else if(type == "MB_YESNO"){	
			result = (ans === true ? 6 : 7);
		}else if(type == "MB_RETRYCANCEL"){		
			result = (ans === true ? 4 : 2);
		}
	}
	
	return result;	
}

function $$Mid(stringvalue, start, end) {
	if(typeof stringvalue == 'undefined'){return;}
	if(typeof start != 'number'){return;}
	
	return $$Middle(stringvalue, start - 1, end);
}

function $$Middle(stringvalues, startoroffset, endorchars) {
	if(typeof stringvalues == 'undefined'){return;}

	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	
	for(var i=0; i<templist.length; i++){
		var startpos = -1;
		if(typeof startoroffset == "string"){
			startpos = templist[i].indexOf(startoroffset);
			if(startpos > -1){
				startpos = startpos + startoroffset.length;
			}
		}else if(typeof startoroffset == "number"){
			startpos = startoroffset;
		}
		var endpos = templist[i].length;	
		if(typeof endorchars == "string"){
			endpos = templist[i].indexOf(endorchars);
			if(endpos == -1){
				endpos = templist[i].templist[i].length;
			}
		}else if(typeof endorchars == "number"){
			if(endorchars < 0){
				endpos = startpos;
				if(typeof startoroffset == "string"){
					endpos = endpos - startoroffset.length;
				}
				startpos = endpos + endorchars;
			}else{
				endpos = startpos + endorchars;
			}
		}
		
		if(startpos < 0){
			templist[i] = "";
		}else{
			templist[i] = templist[i].slice(startpos, endpos);
		}
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$MiddleBack() {}

function $$Min() {}

function $$Minute(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getMinutes();			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$Modified() {}
function $$Modulo() {}

function $$Month(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getMonth() + 1;			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$Name(action, name) {
	//TODO - complete formatting options
	
	var result = "";

	action = action.toUpperCase();
	
	var tempnames = [];
	if(Array.isArray(name)){
		tempnames = name.slice();
	}else{
		tempnames.push(name);
	}
	
	for(var i=0; i<tempnames.length; i++){
		var tempname = tempnames[i];
		
		if(action == "[A]"){
			var matchval = "";
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var nameparts = tempname.split("/");
				for(var j=0; j<nameparts.length; j++){
					var namepart = nameparts[j].split("=");
					if(namepart[0] == "A"){
						matchval = namepart[1];
					}
				}
			}
			tempname = matchval;
		}else if(action == "[ABBREVIATE]"){
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var nameparts = $$Explode(tempname, "/", true, false);
				tempname = "";
				for(var j=0; j<nameparts.length; j++){
					var namepart = nameparts[j].split("=");
					if(tempname != ""){
						tempname += "/";
					}
					tempname += namepart[1];
				}
			}
		}else if(action == "[ADDRESS821]"){
			if(! /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/.test(tempname)){
				tempname = "";
			}
		}else if(action == "[C]"){
			var matchval = "";
			if(tempname.toUpperCase().indexOf("CN=")==0){
				var nameparts = $$Explode(tempname, "/", true, false);
				for(var j=0; j<nameparts.length; j++){
					var namepart = nameparts[j].split("=");
					if(namepart[0] == "C"){
						matchval = namepart[1];
					}
				}
			}
			tempname = matchval;
		}else if(action == "[CANONICALIZE]"){
			if((tempname.toUpperCase().indexOf("CN=")!== 0) && (tempname.indexOf("/") > -1)){
				var nameparts = $$Explode(tempname, "/", true, false);
				tempname = "";
				for(var j=0; j<nameparts.length; j++){
					if(tempname != ""){
						tempname += "/";
					}
					if(j==0){
						tempname += "CN=";
					}else if(j==nameparts.length-1){
						tempname += "O=";
					}else{
						tempname += "OU=";
					}
					tempname += nameparts[j];
				}
			}		
		}else if(action == "[CN]"){
			if(tempname.indexOf("/") > -1){
				var nameparts = $$Explode(tempname, "/", true, false);
				tempname = nameparts[0];
				if(tempname.toUpperCase().indexOf("CN=")===0){
					var namepart = tempname.split("=");
					tempname = namepart[1];
				}				
			}				
		}else if(action == "[G]"){
			if(tempname.indexOf("/") > -1){
				var nameparts = $$Explode(tempname, "/", true, false);
				tempname = nameparts[0];
				if(tempname.toUpperCase().indexOf("CN=")===0){
					var namepart = tempname.split("=");
					tempname = namepart[1];
				}				
			}				
			if(tempname.indexOf(" ") > -1){
				tempname = tempname.slice(0, tempname.indexOf(" "));
			}
		}else if(action == "[HIERARCHYONLY]"){
			if((tempname.toUpperCase().indexOf("CN=")!==0) && (tempname.indexOf("/") > -1)){
				var nameparts = $$Explode(tempname, "/", true, false);				
				tempname = nameparts.slice(1).join("/");
			}else{
				tempname = "";
			}
		}else if(action == "[I]"){
			tempname = "";			
		}else if(action == "[LP]"){
			tempname = "";			
		}else if(action == "[O]"){
			var matchvar = "";
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var pos = tempname.toUpperCase().indexOf("/O=");
				if(pos > -1){
					matchvar = tempname.slice(pos + 3);
				}
				var pos = matchvar.indexOf("/");
				if(pos > - 1){
					matchvar = matchvar.slice(0, pos);
				}
			}else if(tempname.indexOf("/") > -1){
				var nameparts = tempname.split("/");
				matchvar = nameparts[nameparts.length -1];
				if(matchvar.indexOf("=") > -1){
					matchvar = matchvar.split("=")
					matchvar = matchvar[matchvar.length -1];
				}
			}					
			tempname = matchvar;
		}else if(action == "[OU1]"){
			var matchvar = "";
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var nameparts = tempname;
				var pos = nameparts.toUpperCase().indexOf("OU=");
				if(pos > -1){
					nameparts = tempname.slice(pos);
				
					var pos = nameparts.toUpperCase().indexOf("/O=");
					if(pos > -1){
						nameparts = nameparts.slice(0, pos);
					}				
					nameparts = nameparts.split("/");
					nameparts = nameparts[0].split("=");
					matchvar = nameparts[1];
				}
			}else if(tempname.indexOf("/") > -1){
				var nameparts = tempname;
				nameparts = nameparts.split("/");
				if(nameparts.length > 2){
					matchvar = nameparts[1];
				}
			}					
			tempname = matchvar;		
		}else if(action == "[OU2]"){
			var matchvar = "";
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var nameparts = tempname;
				var pos = nameparts.toUpperCase().indexOf("OU=");
				if(pos > -1){
					nameparts = tempname.slice(pos);
					var pos = nameparts.toUpperCase().indexOf("/O=");
					if(pos > -1){
						nameparts = nameparts.slice(0, pos);
					}				
					nameparts = nameparts.split("/");
					if(nameparts.length > 1){
						nameparts = nameparts[1].split("=");
						matchvar = nameparts[1];
					}
				}
			}else if(tempname.indexOf("/") > -1){
				var nameparts = tempname;
				nameparts = nameparts.split("/");
				if(nameparts.length > 3){
					matchvar = nameparts[2];
				}	
			}					
			tempname = matchvar;		
		}else if(action == "[OU3]"){
			var matchvar = "";
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var nameparts = tempname;
				var pos = nameparts.toUpperCase().indexOf("OU=");
				if(pos > -1){
					nameparts = tempname.slice(pos);
					var pos = nameparts.toUpperCase().indexOf("/O=");
					if(pos > -1){
						nameparts = nameparts.slice(0, pos);
					}				
					nameparts = nameparts.split("/");
					if(nameparts.length > 2){				
						nameparts = nameparts[2].split("=");
						matchvar = nameparts[1];
					}
				}
			}else if(tempname.indexOf("/") > -1){
				var nameparts = tempname;
				nameparts = nameparts.split("/");
				if(nameparts.length > 4){
					matchvar = nameparts[3];
				}
			}					
			tempname = matchvar;				
		}else if(action == "[OU4]"){
			var matchvar = "";
			if(tempname.toUpperCase().indexOf("CN=")===0){
				var nameparts = tempname;
				var pos = nameparts.toUpperCase().indexOf("OU=");
				if(pos > -1){
					nameparts = tempname.slice(pos);
					var pos = nameparts.toUpperCase().indexOf("/O=");
					if(pos > -1){
						nameparts = nameparts.slice(0, pos);
					}				
					nameparts = nameparts.split("/");
					if(nameparts.length > 3){	
						nameparts = nameparts[3].split("=");
						matchvar = nameparts[1];
					}
				}
			}else if(tempname.indexOf("/") > -1){
				var nameparts = tempname;
				nameparts = nameparts.split("/");
				if(nameparts.length > 5){
					matchvar = nameparts[4];
				}
			}					
			tempname = matchvar;							
		}else if(action == "[P]"){
			tempname = "";			
		}else if(action == "[PHRASE]"){
			tempname = "";		
		}else if(action == "[Q]"){
			tempname = "";		
		}else if(action == "[S]"){
			var matchvar = "";
			if(tempname.indexOf("/") > -1){
				var nameparts = $$Explode(tempname, "/", true, false);
				matchvar = nameparts[0];
				if(matchvar.toUpperCase().indexOf("CN=")===0){
					var namepart = matchvar.split("=");
					matchvar = namepart[1];
				}				
			}else{
				matchvar = tempname;
			}				
			matchvar = matchvar.split(" ");
			tempname = matchvar[matchvar.length - 1];
		}else if(action == "[TOAT]"){
			tempname = "";		
		}else if(action == "[TODATATYPE]"){
			tempname = "";		
		}else if(action == "[TOFIELD]"){
			tempname = "";		
		}else if(action == "[TOFORM]"){
			tempname = "";		
		}else if(action == "[TOKEYWORD]"){
			tempname = "";		
		}else if(action == "[TOOC]"){
			tempname = "";		
		}else if(action == "[TOSYNTAX]"){
			tempname = "";
		}
		
		tempnames[i] = tempname;
	}
	
	if(Array.isArray(name)){
		result = tempnames.slice()
	}else{
		result = tempnames[0];
	}
	
	return result;
}

function $$NameLookup() {}

function $$NewLine() {
	return "\n\r";
}

function $$No() {
	return false;
}

function $$NoteID() {
	var result = "";
	
	if(info && info.DocID){
		//Note: technically this is UNID but we don't track NoteID in DOCOVA
		result = info.DocID;
	}
	
	return result;
}

function $$Nothing() {
	return null;
}

function $$Now() {
	return new Date();
}

function $$OpenInNewWindow() {}
function $$OptimizeMailAddress() {}

function $$PasswordQuality() {}

function $$Pi() {
	return Math.PI;
}

function $$PickList(flags, targetdb, view, title, promptmsg, column, category, cb) {
	var picklistflags = (typeof flags == 'undefined' ? "[CUSTOM]" : (Array.isArray(flags) ? flags.join(":").toUpperCase() : flags.toUpperCase()));
	
	var iscustom = (picklistflags.indexOf("[CUSTOM]") > -1);
	var isnames = (picklistflags.indexOf("[NAME]") > -1);
	var issingle = (picklistflags.indexOf("[SINGLE]") > -1);

	if(iscustom){
		if (typeof targetdb == 'undefined' || typeof view == 'undefined' || typeof title == 'undefined' || typeof promptmsg == 'undefined' || typeof column == 'undefined'){
			return;
		}
		var uiw = Docova.getUIWorkspace(document);
		uiw.picklistStrings(flags, !issingle, (Array.isArray(targetdb) && targetdb.length > 1 ? targetdb[0] : ""), (Array.isArray(targetdb) ? (targetdb.length > 1 ? targetdb[1] : targetdb[0]) : targetdb), view, title, promptmsg, column, (typeof category == 'undefined' ? "" : category), cb);
	}else if(isnames){
		//TODO - add support for name dialog
	}
}

function $$Platform() {}

function $$PostedCommand(action, param1, param2, param3, param4, param5) {
	$$Command(action, param1, param2, param3, param4, param5);
}


function $$Power(base, exponent) {
	if(typeof base == 'undefined' || typeof exponent == 'undefined'){return;}
	if(Array.isArray(base)){
		for(var i=0; i<base.length; i++){
			base[i] = Math.pow(base[i], exponent);
		}
	}else{
		base = Math.pow(base, exponent);
	}
	
	return base;
}

function $$Prompt(flags, title, promptmsg, defaultchoice, choicelist, filetype, cb) {
	result = false;
	if(typeof flags == "undefined" || typeof title == "undefined" || typeof promptmsg == "undefined"){ return result;}

	defaultchoice = (typeof defaultchoice === 'undefined' || defaultchoice === null ?  "" : defaultchoice);
	
	var icon = 0;
	var boxtype = 0;
	var allowmulti = false;
	var allowothervals = false;
	var usemsgbox = false;
	var usekeyword = false;
	var sortlist = true;
	var tempflags = (Array.isArray(flags) ? flags.slice(): [flags]);
	for(var i=0; i<tempflags.length; i++){
		var flagvar = tempflags[i].toUpperCase();
		if(flagvar.indexOf("[")===0){flagvar = flagvar.slice(1)};
		if(flagvar.indexOf("]", flagvar.length - 1) > -1){flagvar = flagvar.slice(0, flagvar.length-1)};				
		if(flagvar == "NOSORT"){
			sortlist = false;			
		}else if(flagvar == "CHOOSEDATABASE"){
		}else if(flagvar == "LOCALBROWSE"){
		}else if(flagvar == "OK"){
			result = $$MessageBox(promptmsg, 0, title);
			if(cb && typeof cb === "function"){
				cb(result);
			}
			return result;
		}else if(flagvar == "OKCANCELCOMBO"){
			usekeyword = true;					
		}else if(flagvar == "OKCANCELEDIT"){
			var msg = "";
			if(title !== ""){
				var headerwidth = Math.max(title.length + 2, 75);
				var headergap = parseInt((headerwidth - title.length) / 2, 10);
				msg += Array(headerwidth).join("-");
				msg += "\n";
				msg += Array(headergap).join(" ");
				msg += title;
				msg += Array(headergap).join(" ");		
				msg += "\n";
				msg += Array(headerwidth).join("-");
				msg += "\n";
			}
			if(promptmsg !== ""){
				msg += "\n";
				msg += promptmsg;
			}			

			var tempresult = window.prompt(msg, defaultchoice);
			if(tempresult !== null){
				result = tempresult;
			}
			if(cb && typeof cb === "function"){
				cb(result);
			}			
			return result;
		}else if(flagvar == "OKCANCELEDITCOMBO"){
			usekeyword = true;			
			allowothervals = true;
		}else if(flagvar == "OKCANCELLIST"){
			usekeyword = true;			
		}else if(flagvar == "OKCANCELLISTMULT"){
			usekeyword = true;
			allowmulti = true;
		}else if(flagvar == "PASSWORD"){
		}else if(flagvar == "YESNO"){
			var tempresult = $$MessageBox(promptmsg, 4, title);
			result = (tempresult === 6 ? 1 : 0);
			if(cb && typeof cb === "function"){
				cb(result);
			}			
			return result;
		}else if(flagvar == "YESNOCANCEL"){
			var tempresult = $$MessageBox(promptmsg, 3, title);
			result = (tempresult === 4 ? 1 : 0);
			if(cb && typeof cb === "function"){
				cb(result);
			}			
			return result;
		}		
	}
	//-- if we have gotten this far none of the other cases have caught the prompt type 
	
	if(usemsgbox){
		window.top.Docova.Utils.messageBox({
			'icontype': icon, 
			'msgboxtype' : boxtype, 
			'prompt': promptmsg, 
			'title': title,
			'onOk' : function(){if(cb && typeof cb === 'function'){cb(1)}},
			'onCancel' : function(){if(cb && typeof cb === 'function'){cb(2)}},
			'onYes' : function(){if(cb && typeof cb === 'function'){cb(6)}},
			'onNo' : function(){if(cb && typeof cb === 'function'){cb(7)}},
			'onAbort' : function(){if(cb && typeof cb === 'function'){cb(3)}},
			'onRetry' : function(){if(cb && typeof cb === 'function'){cb(4)}},
			'onIgnore' : function(){if(cb && typeof cb === 'function'){cb(5)}}			
		});
	}else if(usekeyword){
		window.top.Docova.Utils.selectKeyword({
			'choicelist': choicelist, 
			'defaultvalues' : defaultchoice,
			'prompt': promptmsg, 
			'windowtitle': title,
			'multiselect' : allowmulti,
			'delimiterin' : ":",
			'returnarray' : allowmulti,
			'allowothervals' : allowothervals,
			'delegate' : window,
			'oncomplete' : (cb && typeof cb === 'function' ? cb : function(){}),
			'oncancel' : (cb && typeof cb === 'function' ? cb : function(){})			
		});	
	}

}

function $$ProperCase(argument) {
	if(typeof argument == 'undefined'){return;}
	var isarray = Array.isArray(argument);	
	var temparray = (isarray ? argument.slice() : [argument]);
	
	for(var i=0; i<temparray.length; i++){	
 		temparray[i] = temparray[i].replace(/[A-Za-z0-9\u00C0-\u00FF]+[^\s-]*/g, function(match, index, title){
	    		return match.charAt(0).toUpperCase() + match.substr(1).toLowerCase();
  		});
	}
	
	return (isarray ? temparray : temparray[0]);
}

function $$Random() {
	return Math.random();
}

function $$RegQueryValue() {}

function $$Repeat(argument, count, maxchars) {
	if(typeof argument == 'undefined' || typeof count == 'undefined'){return;}
	var isarray = Array.isArray(argument);	
	var temparray = (isarray ? argument.slice() : [argument]);
	
	for(var i=0; i<temparray.length; i++){	
 		temparray[i] = temparray[i].repeat(count);	
 		if(typeof argument != 'undefined'){
 			temparray[i] = temparray[i].substr(0, maxchars);
 		}
	}
	
	return (isarray ? temparray : temparray[0]);
}


function $$Replace(sourcelist, fromlist, tolist) {
	if(typeof sourcelist == 'undefined' || typeof fromlist == 'undefined' || typeof tolist == 'undefined'){return;}
	var isarray1 = Array.isArray(sourcelist);	
	var temparray = (isarray1 ? sourcelist.slice() : [sourcelist]);
	var tempfromlist = (Array.isArray(fromlist) ? fromlist.slice() : [fromlist]);
	var temptolist = (Array.isArray(tolist) ? tolist.slice() : [tolist]);
	var lastitem = temptolist.length -1;
	
	for(var i=0; i<temparray.length; i++){	
 		var pos = tempfromlist.indexOf(temparray[i]);
 		if(pos > -1){
 			temparray[i] = temptolist[Math.min(lastitem, pos)];
 		}
	}
	
	return (isarray1 ? temparray : temparray[0]);
}

function $$ReplaceSubstring(sourcelist, fromlist, tolist) {
	if(typeof sourcelist == 'undefined' || typeof fromlist == 'undefined' || typeof tolist == 'undefined'){return;}
	var isarray1 = Array.isArray(sourcelist);	
	var temparray = (isarray1 ? sourcelist.slice() : [sourcelist]);
	var tempfromlist = (Array.isArray(fromlist) ? fromlist.slice() : [fromlist]);
	var temptolist = (Array.isArray(tolist) ? tolist.slice() : [tolist]);
	var lastitem = temptolist.length -1;
	
	for(var i=0; i<temparray.length; i++){	
		for(var r=0; r<tempfromlist.length; r++){
			temparray[i] = temparray[i].split(tempfromlist[r]).join(temptolist[Math.min(r, lastitem)]);
  		}
	}
	
	return (isarray1 ? temparray : temparray[0]);
}

function $$ReplicaID() {}
function $$Responses() {}

function $$Return(returnvalue) {
	_lastreturnvalue = returnvalue;
	throw new _HandleReturnValue(returnvalue);
}

function $$Right(stringtosearch, selection, compmethod) {
	//TODO - compmethod does not respect pitch insensitive option
	if(typeof stringtosearch == 'undefined'){return;}
	
	var searchtype = (typeof selection);
	if(searchtype == 'undefined'){return;}

	var result = "";
	var pos = -1;
	
	var isarray = Array.isArray(stringtosearch);
	var templist = (isarray ? stringtosearch.slice() : [stringtosearch]);
	
	for(var i=0; i<templist.length; i++){
		if(searchtype == "string"){
			if(compmethod !== undefined && (compmethod === 1 || compmethod === 5)){
				pos = templist[i].toUpperCase().indexOf(selection.toUpperCase());				
			}else{
				pos = templist[i].indexOf(selection);				
			}
			if(pos > -1){
				templist[i] = templist[i].substring(pos + selection.length);
			}else{
				templist[i] = "";
			}
		}else if(searchtype == "number"){
			if(selection > -1){
				templist[i] = templist[i].substring(templist[i].length - selection);
			}else if(selection == 0){
				templist[i] = "";
			}				
		}
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$RightBack(stringtosearch, selection) {
	if(typeof stringtosearch == 'undefined'){return;}
	
	var searchtype = (typeof selection);
	if(searchtype == 'undefined'){return;}

	var result = "";
	var pos = -1;
	
	var isarray = Array.isArray(stringtosearch);
	var templist = (isarray ? stringtosearch.slice() : [stringtosearch]);
	
	for(var i=0; i<templist.length; i++){
		if(searchtype == "string"){
			pos = templist[i].lastIndexOf(selection);				
			if(pos > -1){
				templist[i] = templist[i].substring(pos + selection.length);
			}else{
				templist[i] = "";
			}
		}else if(searchtype == "number"){
			if(selection > -1){
				templist[i] = templist[i].substring(selection);
			}				
		}
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$Round(argument, factor) {
	if(typeof argument == 'undefined'){return;}
	var roundfactor = (typeof factor == 'undefined' ? 1 : factor);
	
	var temparray = Array.isArray(argument) ? argument.slice() : [argument];
	
	for(var i=0; i<temparray.length; i++){
		temparray[i] = Math.round(temparray[i] / roundfactor) * roundfactor;			
	}
	
	var digits = (Math.floor(roundfactor) === roundfactor ? 0 : (roundfactor.toString().split(".")[1].length || 0));	
	temparray = $$Trunc(temparray, digits);
	
	return (Array.isArray(argument) ? temparray : temparray[0]);
}

function $$Second(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getSeconds();			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$Select(param1, param2) {
	var result = null;

	if(typeof param1 == "undefined" && typeof param2 == "undefined"){
	    return result;
	}else if(typeof param2 == "undefined" && (!Array.isArray(param1) || param1.length < 2)){
		return result;
	}
	
	var temparray = (typeof param2 == "undefined" ? param1.slice(1) : param2);
	var index = (typeof param2 == "undefined" ? param1[0] : param1);
	
	if(index < 1){
		result = temparray[0];
	}else if(index >= temparray.length){
		result = temparray[temparray.length - 1]; 
	}else{
		result = temparray[index - 1];
	}
	
	return result;
}

function $$ServerAccess() {}

function $$ServerName() {
	var result = "";
	
	if(info && info.ServerName){
		result = info.ServerName;
	}
	
	return result;
}

function $$Set(variablename, value) {
	if(typeof variablename == "undefined" || typeof value == "undefined"){
		return;
	}
	window[variablename] = value;
}

function $$SetDocField(docunid, fieldname, fieldvalue) {
	if((docInfo && docInfo.isDocBeingEdited) && ((docInfo.DocID && docunid && docunid == docInfo.DocID) || !docunid)){
		var uiw = Docova.getUIWorkspace();
		var uidoc = uiw.currentDocument;
		//-- update current doc
		uidoc.setField({'field' : fieldname, 'value' : fieldvalue});
	}else if(docunid && docunid != ""){
		//-- update back end doc
		var uiw = Docova.getUIWorkspace(document);
		var app = uiw.getCurrentApplication();
		var doc = app.getDocument(docunid);
		if(doc){
			if(doc.setField(fieldname, fieldvalue)){
				doc.save();
			}
			
		}
	}
}

function $$SetEnvironment(varname, varvalue) {
	if(varname === undefined || varname === "" || varvalue === undefined || varvalue === null ){
		  return false;
	}
	Docova.Utils.setCookie({keyname: varname, keyvalue: varvalue.toString(), httpcookie: true});
	return true;
}

function $$SetField(fieldname, fieldvalue) {
	$$SetDocField(null, fieldname, fieldvalue);
}

function $$SetHTTPHeader() {}

function $$SetProfileField(profilename, fieldname, value, key) {
	if(typeof profilename == 'undefined' || profilename == ""){
		return false;
	}
	if(typeof fieldname == 'undefined' || fieldname == ""){
		return false;
	}
	if(typeof value == 'undefined' || value == ""){
		return false;
	}
	
	var uiw = Docova.getUIWorkspace(document);
	var app = uiw.getCurrentApplication();

	var fieldobj = {};
	fieldobj[fieldname] = value;
	
	var result = app.setProfileFields(profilename, fieldobj, key);
	if(result == true){
		return value;
	}else{
		return "";
	}
}

function $$SetTargetFrame(framename) 
{
	_targetframe = framename;
}


function $$SetViewInfo() {}
function $$ShowParentPreview() {}

function $$Show(argument) {
	if (!argument) { return; }
	
	if (typeof argument == 'string') {
		jQuery(argument).show();
	}
	else if (argument instanceof jQuery) {
		argument.show();
	}
	else if (typeof HTMLElement === 'object' && argument instanceof HTMLElement) {
		jQuery(argument).show();
	}
	else if (typeof argument === "object" && argument !== null && argument.nodeType === 1 && typeof argument.nodeName === 'string') {
		jQuery(argument).show();
	}
}

function $$Sign(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = (argument[i] == 0 ? 0 : (argument[i] < 0 ? -1 : 1));
		}
	}else{
			argument = (argument == 0 ? 0 : (argument < 0 ? -1 : 1));
	}
	
	return argument;
}

function $$Sin(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.sin(argument[i]);
		}
	}else{
		argument = Math.sin(argument);
	}
	
	return argument;
}

function $$Sort(arrayvalues, sortorder, customsort) {
	//TODO - does not support accent or pitch insensitive sorting
	var result = null;
	if(typeof arrayvalues == 'undefined'){
		return result;
	}
	var sortoptions = (typeof sortorder == 'undefined' ? "[ASCENDING]:[CASESENSITIVE]:[ACCENTSENSITIVE]:[PITCHSENSITIVE]" : sortorder.toUpperCase());
	var ignorecase = (sortoptions.indexOf("[CASEINSENSITIVE]") > -1);
	var sortascending = !(sortoptions.indexOf("[DESCENDING]") > -1);
	
	var temparray = (Array.isArray(arrayvalues) ? arrayvalues.slice() : [arrayvalues]);
	
	temparray.sort(function(a, b){
			var sresult = 0;
			if(typeof a == 'number' && typeof b == 'number'){
				sresult = a-b;
			}else{
				var val1 = a.toString();
				var val2 = b.toString();				
				if(ignorecase){
					val1 = val1.toUpperCase();
					val2 = val2.toUpperCase();
				}
				if(val1 > val2){
					sresult = (sortascending ? 1 : -1);
				}else if(val1 < val2){
					sresult = (sortascending ? -1 : 1);
				}										
			}
			return sresult;
	});
		
	return temparray;
}

function $$Soundex() {}

function $$Sqrt(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.sqrt(argument[i]);
		}
	}else{
		argument = Math.sqrt(argument);
	}
	
	return argument;
}

function $$Subset(itemlist, itemnumber) {
	if(!itemnumber || itemnumber == 0){
		return "ERROR: The second argument to @Subset must not be zero";
	}
	var result = "";
	
	if(itemlist && itemnumber){
		if(Array.isArray(itemlist)){
			if(itemnumber > 0){
				result = itemlist.slice(0, itemnumber);				
			}else if(itemnumber < 0){
				result = itemlist.slice(itemnumber);				
			}
			if(result.length == 1){
				result = result[0];
			}
		}else{
			result = itemlist;
		}
	}
	
	return result;
}

function $$Success() {
	return true;
}

function $$Sum(argument) {
	if(typeof argument == 'undefined'){return;}
	var result = 0;
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			if(! isNaN(argument[i])){
				result = result + argument[i];
			}else{
				return $$Error();
			}
		}
	}else{
		if(! isNaN(argument)){
			result = argument;
		}else{
			return $$Error();
		}
	}
	
	return result;	
}

function $$Tan(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			argument[i] = Math.tan(argument[i]);
		}
	}else{
		argument = Math.tan(argument);
	}
	
	return argument;
}

function $$TemplateVersion() {}

function $$Text(argument, formatstring) {
	if(typeof argument == 'undefined'|| argument === null){return;}

	var isarray = false;
	var tempresult = [];

	isarray = Array.isArray(argument);
	var templist = (isarray ? argument.slice() : [argument]);
	for(var i=0; i<templist.length; i++){
		var tempval = templist[i];
		if(typeof tempval == 'string'){
			//--nothing to do already a string
		}else if(typeof tempval == 'object' && Object.prototype.toString.call(tempval) === '[object Date]'){
			//-- found a date so check to see if we have any formatting options
			var datefmt = (docInfo && docInfo.SessionDateFormat ? docInfo.SessionDateFormat : "dd-mmm-yy").replace('yyyy', 'yy').replace('yy', 'yyyy');
			var timefmt = 'hh:nn:ss AM/PM';
			
			var datesep = (datefmt.indexOf("-") > -1 ? "-" : (datefmt.indexOf("/") > -1 ? "/" : (datefmt.indexOf(".") > -1 ? "." : "/")));
			
			if(typeof formatstring != 'undefined' && formatstring !== null && formatstring !== ''){
				if(formatstring.indexOf("D0")>-1){
					//-- leave date format as is
				}else if(formatstring.indexOf("D1")>-1){
					//-- remove year if same as current year
					if($$Year(tempval) == $$Year($$Today())){
						datefmt = datefmt.replace(datesep+'yyyy', '').replace('yyyy'+datesep, '').replace(datesep+'yy', '').replace('yy'+datesep, '');						
					}
				}else if(formatstring.indexOf("D2")>-1){
					//-- remove year
					datefmt = datefmt.replace(datesep+'yyyy', '').replace('yyyy'+datesep, '').replace(datesep+'yy', '').replace('yy'+datesep, '');
				}else if(formatstring.indexOf("D3")>-1){
					//-- remove day
					datefmt = datefmt.replace(datesep+'dd', '').replace('dd'+datesep, '');
				}
				
				if(formatstring.indexOf("S0")>-1){
					//-- remove time
					timefmt = '';
				}else if(formatstring.indexOf("S1")>-1){
					//-- remove date
					datefmt = '';
				}else if(formatstring.indexOf("S2")>-1){
					//--leave time format as is					
				}		
				
				if(timefmt != ''){
					if(formatstring.indexOf("T0")>-1){
						//-- leave time format as is
					}else if(formatstring.indexOf("T1")>-1){
						//-- remove seconds
						timefmt = timefmt.replace(':ss', '');
					}			
				}				
			}	
			var datetimefmt = $$Trim(datefmt + " " + timefmt);
			tempval = $$FormatDate(tempval, datetimefmt);
		}else if(jQuery.isNumeric(tempval)){
			//-- found a number so check to see if we have any formatting options
			formatstring = (typeof formatstring != 'undefined' && formatstring !== null) ? formatstring : '';
			
			var decsep = (docInfo && docInfo.DecimalSeparator ? docInfo.DecimalSeparator : ".");
			var bracketneg = (formatstring.indexOf('()')>-1);
			var isfixed = (formatstring.indexOf('F')>-1);
			
			var sigdigits = formatstring.match(/\d+/);
			if(sigdigits == null){
				sigdigits = 2;
			}else{
				sigdigits = sigdigits[0];
			}
			
			var thousep = "";
			if(formatstring.indexOf(',')>-1){
				thousep = ((docInfo && typeof(docInfo.ThousandsSeparator)!= "undefined") ? docInfo.ThousandsSeparator : "");
				if(thousep == ""){
					thousep = ",";
				}
			}
							
			if(formatstring.indexOf('S')>-1){
				var newtempval = (bracketneg ? Math.abs(tempval) : tempval).toExponential(sigdigits);
				if(bracketneg && tempval < 0){
					newtempval = "(" + newtempval + ")";
				}
				tempval = newtempval;

			}else if(formatstring.indexOf('%')>-1){
				var newtempval = parseInt((bracketneg ? Math.abs(tempval) : tempval) * 100).toString();
				if(sigdigits > 0){
					if(((Math.abs(tempval) * 100) % 1).toFixed(sigdigits).substring(2) !== ("0").repeat(sigdigits)){
						newtempval += decsep;
						newtempval += ((Math.abs(tempval) * 100) % 1).toFixed(sigdigits).substring(2);					
					}
				}
				newtempval += "%";
				if(bracketneg && tempval < 0){
					newtempval = "(" + newtempval + ")";
				}
				tempval = newtempval
			}else if(formatstring.indexOf('C')>-1){
				newtempval = "$"+parseInt((bracketneg ? Math.abs(tempval) : tempval)).toString();
				if(thousep != ""){
					newtempval = newtempval.replace(/\B(?=(\d{3})+(?!\d))/g, thousep);
				}
				if(sigdigits > 0){
					newtempval += decsep + (Math.abs(tempval) % 1).toFixed(sigdigits).substring(2);
				}
				if(bracketneg && tempval < 0){
					newtempval = "(" + newtempval + ")";
				}
				tempval = newtempval;
			}else{
				newtempval = parseInt((bracketneg ? Math.abs(tempval) : tempval)).toString();
				if(thousep != ""){
					newtempval = newtempval.replace(/\B(?=(\d{3})+(?!\d))/g, thousep);
				}
				if(sigdigits > 0){
					if(((Math.abs(tempval)) % 1).toFixed(sigdigits).substring(2) !== ("0").repeat(sigdigits)){
						newtempval += decsep;
						newtempval += (Math.abs(tempval) % 1).toFixed(sigdigits).substring(2);
					}
				}
				if(bracketneg && tempval < 0){
					newtempval = "(" + newtempval + ")";
				}
				tempval = newtempval;					
			}			
		}else{			
				tempval = String(tempval);
		}
		tempresult.push(tempval);
	}
	
	return (isarray ? tempresult : tempresult[0]);		
	
}

function $$TextToNumber(argument){
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			if(typeof argument[i] != "string"){
				throw new Error("Invalid type of argument");
			}			

			var tempnum = Number(argument[i]);
			if(isNaN(tempnum)){
				tempnum = $$Error();
			}
			argument[i] = tempnum;
		}
	}else{
		if(typeof argument != "string"){
			throw new Error("Invalid type of argument");
		}
		var tempnum = Number(argument);
		if(isNaN(tempnum)){
			tempnum = $$Error();
		}
		argument = tempnum;		
	}
	
	return argument;
}

function $$TextToTime(datestring) {
	if(typeof datestring == 'undefined'){return;}
	
	var isarray = false;
	var tempresult = [];

	isarray = Array.isArray(datestring);
	var templist = (isarray ? datestring.slice() : [datestring]);
	for(var i=0; i<templist.length; i++){
		var tempdate = templist[i];
		if(typeof tempdate == 'string'){
			if(tempdate == "Today"){
				tempdate = $$Today();
			}else if(tempdate == "Tomorrow"){
				tempdate = $$Tomorrow();
			}else if(tempdate == "Yesterday"){
				tempdate = $$Yesterday();
			}else{
				tempdate = Docova.Utils.convertStringToDate(tempdate);	
				if(tempdate !== null){
					if(isNaN(tempdate)){
						tempdate = "";
					}
				}else{
					tempdate = "";
				}
			}					
		}else{
			return $$Error();
		}
		tempresult.push(tempdate);
	}
	
	return (isarray ? tempresult : tempresult[0]);		
}

function $$ThisName() {
	var result = "";
	result = (document.activeElement.name || document.activeElement.id || (event && event.target ? event.target.id : "") || ""); 
	return result;
}

function $$ThisValue() {
	var result = "";
	var fieldname = "";
	fieldname = (document.activeElement.name || document.activeElement.id || (typeof event != 'undefined' && event.target ? event.target.id : "") || "");
	if(fieldname){
		result = $$GetField(fieldname);
	}
	return result;	
}

function $$Time(timedateoryearorhour, monthorminute, dayorsecond, hour, minute, second) {
	if(typeof timedateoryearorhour == 'undefined'){return;}
	
	var isarray = false;
	var tempresult = [];
	if(typeof hour != 'undefined' && typeof minute != 'undefined' && typeof second != 'undefined'){
		monthorminute = monthorminute - 1;
		var tempdate = new Date(timedateoryearorhour, monthorminute, dayorsecond, hour, minute, second);		
		if(isNaN(tempdate)){
			tempdate = "";
		}
		tempresult.push(tempdate);	
	}else if(typeof monthorminute != 'undefined' && typeof dayorsecond != 'undefined'){
		monthorminute = monthorminute - 1;
		var tempdate = new Date(1,0,1,timedateoryearorhour, monthorminute, dayorsecond);
		if(isNaN(tempdate)){
			tempdate = "";
		}else{
			tempdate = tempdate.toTimeString().split(" ")[0];	
		}
		tempresult.push(tempdate);					
	}else{
		isarray = Array.isArray(timedateoryearorhour);
		var templist = (isarray ? timedateoryearorhour.slice() : [timedateoryearorhour]);
		for(var i=0; i<templist.length; i++){
			var tempdate = templist[i];
			if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				tempdate.setFullYear(1,0,1);
				if(isNaN(tempdate)){
					tempdate = "";
				}else{
					tempdate = tempdate.toTimeString().split(" ")[0];
				}		
			}else if(typeof tempdate == 'string'){
				tempdate = Docova.Utils.convertStringToDate(tempdate);
				if(tempdate !== null){
					tempdate.setFullYear(1,0,1);
					if(isNaN(tempdate)){
						tempdate = "";
					}else{
						tempdate = tempdate.toTimeString().split(" ")[0];
					}
				}else{
					tempdate = "";
				}
			}else{
				tempdate = "";
			}
			tempresult.push(tempdate);
		}
	}
	
	return (isarray ? tempresult : tempresult[0]);	
	
}

function $$TimeMerge() {}
function $$TimeToTextInZone() {}
function $$TimeZoneToText() {}

function $$Today() {
	var result = new Date();

	return result;
}

function $$Tomorrow() {
	var result = new Date();
	result.setDate(result.getDate() + 1);

	return result;
}

function $$ToNumber(argument) {
	if(typeof argument == 'undefined'){return;}
	if(Array.isArray(argument)){
		for(var i=0; i<argument.length; i++){
			var tempnum = Number(argument[i]);
			if(isNaN(tempnum)){
				tempnum = "The value cannot be converted to a Number.";
			}
			argument[i] = tempnum;
		}
	}else{
			var tempnum = Number(argument);
			if(isNaN(tempnum)){
				tempnum = "The value cannot be converted to a Number.";
			}
			argument = tempnum;
	}
	
	return argument;
}

function $$ToTime() {}
function $$Transform() {}

function $$Trim(argument) {
	if(typeof argument == 'undefined' || argument === null){return "";}
	
	var isarray = Array.isArray(argument);
	var tempresult = [];
	
	var templist = (isarray ? argument.slice() : [argument]);
	for(var i=0; i<templist.length; i++){
		var tempval = templist[i].toString();
		tempval = tempval.replace(/  +/g, ' ');
		tempval = tempval.trim();
		if(tempval != ""){
			tempresult.push(tempval);
		}
	}
	
	return (tempresult.length > 1 ? tempresult : (tempresult.length === 0 ? "" : tempresult[0]));
}


function $$True() {
	return true;
}


function $$Trunc(argument, digits){
	if(typeof argument == 'undefined'){return;}

	var digvar = (typeof digits == 'undefined' ? 0 : digits);
		
	var temparray = Array.isArray(argument) ? argument.slice() : [argument];
	
	for(var i=0; i<temparray.length; i++){
		var numS = temparray[i].toString();
	    var decPos = numS.indexOf('.');
	    var substrLength = decPos == -1 ? numS.length : 1 + decPos + digvar;
	    var trimmedResult = numS.substr(0, substrLength);
	    var finalResult = isNaN(trimmedResult) ? 0 : trimmedResult;
	    temparray[i] = parseFloat(finalResult);
	}
	
	return (Array.isArray(argument) ? temparray : temparray[0]);	
}

function $$UBound(arrayvar) {
	var result = false;
	if(Array.isArray(arrayvar)){
		result = arrayvar.length - 1;
	}
	return result;
}

function $$Unavailable() {
	return null;
}

function $$UndeleteDocument() {}

function $$Unique(arrayval) {
	var result = null;
	if(typeof arrayval === 'undefined'){
		this.s4 = function() {
			return Math.floor((1 + Math.random()) * 0x10000)
     	        .toString(16)
          	   .substring(1);
		};		
		result = this.s4().toUpperCase() + '-' + this.s4().toUpperCase() + this.s4().toUpperCase();
	}else{
		result = $$ArrayUnique(arrayval);
	}
	
	return result;
}

function $$UpperCase(stringvalues) {
	if(typeof stringvalues == 'undefined'){return;}
	
	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	
	for(var i=0; i<templist.length; i++){
		if(typeof templist[i] !== 'string'){
			templist[i] = templist[i].toString();
		}
		templist[i] = templist[i].toUpperCase();
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;
}

function $$URLDecode(decodetype, stringvalues) {
	//TODO - only supports Domino decodetype
	var result = "";
	
	if(typeof stringvalues == 'undefined'){return result;}
	
	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	
	for(var i=0; i<templist.length; i++){
		templist[i] = decodeURIComponent(templist[i]);
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$URLEncode(encodetype, stringvalues) {
	//TODO - only supports Domino encodetype
	var result = "";
	
	if(typeof stringvalues == 'undefined'){return result;}
	
	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	
	for(var i=0; i<templist.length; i++){
		templist[i] = encodeURIComponent(templist[i]);
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;	
}

function $$URLGetHeader() {}

function $$URLOpen(urlstring) {
	var uiw = Docova.getUIWorkspace(document);
	uiw.openUrl({'url': urlstring, 'newwindow': true});
}

function $$UrlQueryString() {}
function $$UserAccess() {}

function $$UserName() {
	if(docInfo && docInfo.UserName){
		return docInfo.UserName;
	}else if(info && info.UserName){
		return info.UserName;
	}else{
		return "";
	}
}

function $$UserNameLanguage() {}

function $$UserNamesList() {
	var tempresult = (info && info.UserNameList ? info.UserNameList : "");
	return tempresult.split(":");
}

function $$UserPrivileges() {}

function $$UserRoles() {
	var tempresult = (info && info.UserRoles ? info.UserRoles : "");
	return tempresult.split(":");	
}

function $$UString(count, stringval){
	var tempchar = "";
	if(typeof stringval === "number"){
	   tempchar = String.fromCharCode(stringval);
	}else{
	  tempchar = stringval.substring(0,1)
	}
	return $$Repeat(tempchar, count);
}

function $$ValidateInternetAddress() {}

function $$V3UserName() {
	if(docInfo && docInfo.UserNameAB){
		return docInfo.UserNameAB;
	}else{
		return "";
	}	
}

function $$Version() {}
function $$ViewTitle() {
	var result = "";

	var title = "";
	var retarray =[];
	var uiview = Docova.getUIView();
	if(uiview !== null){
		title = uiview.viewName;
		var alias = uiview.viewAlias;
		if ( alias && alias != ""){
			retarray[0] = title;
			retarray[1] = alias;
			result = retarray;
		}else{
			result = title;
		}
	}

	return result;
}

function $$WebDbName() {
	return $$DbName();
}

function $$Weekday(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getDay() + 1;			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$WhichFolders() {}

function $$While(argumentlist) {
	var result = false;
	if(!(argumentlist && Array.isArray(argumentlist) && argumentlist.length > 1)){
		return result;
	}
	var conditionmet = argumentlist[0]();
	while(conditionmet == true){
		for (var x = 1; x < argumentlist.length; x++) {
			argumentlist[x]();
		}		
		conditionmet = argumentlist[0]();
	}
	result = true;
	
	return result;	
}

function $$Wide() {}

function $$Word(stringvalues, delim, pos) {
	if(typeof stringvalues == 'undefined' || typeof delim == 'undefined' || typeof pos == 'undefined'){return;}
	
	var isarray = Array.isArray(stringvalues);
	var templist = (isarray ? stringvalues.slice() : [stringvalues]);
	
	for(var i=0; i<templist.length; i++){
		if(typeof templist[i] !== 'string'){
			templist[i] = templist[i].toString();
		}
		var templist2 = templist[i].split(delim);
		var temppos = (pos < 0 ? templist2.length + pos : (pos > 0 ? pos - 1 : 0));
		if(temppos < 0 || temppos > templist2.length - 1){
			templist[i] = ""
		}else{
			templist[i] = templist2[temppos];
		}		
	}
	result = (isarray ? templist.slice() : templist[0]);
	
	return result;
}

function $$Year(timedateval) {
	if(typeof timedateval == 'undefined'){return;}
	
	var isarray = Array.isArray(timedateval);;
	var tempresult = [];
	var templist = (isarray ? timedateval.slice() : [timedateval]);
	for(var i=0; i<templist.length; i++){
		var outputval = -1;
		var tempdate = templist[i];
		if(typeof tempdate == 'object' && Object.prototype.toString.call(tempdate) === '[object Date]'){
				if(! isNaN(tempdate)){
					outputval = tempdate.getFullYear();			
				}				
		}
		tempresult.push(outputval);
	}
	
	return (isarray ? tempresult : tempresult[0]);	
}

function $$Yes() {
	return true;
}

function $$Yesterday() {
	var result = new Date();
	result.setDate(result.getDate() - 1);

	return result;
}

function $$Zone() {}


/*----------------------------------
 * @Commands
 * --------------------------------- */

function _Cmd_Clear(){
	var uidoc = Docova.getUIDocument();
	if(uidoc){
		uidoc.deleteDocument();
	}	
}

function _Cmd_Compose(param1, param2, param3){
	if (typeof param2 == 'boolean') {
		var formname = param1;
		var inherit = param2;
		var docid = param3
	}
	else {
		var formname = (typeof param2 == 'undefined' || !param2 ? param1 : param2);
	}

	if(!formname || formname == ""){
		return;
	}	
	
	var uiw = Docova.getUIWorkspace(document); 	
	var opts = {'formname': formname, 'targetframe' : _targetframe, 'docid' : (docid ? docid : ''), 'inherit' : (inherit ? true : false)};
	var uidoc = uiw.currentDocument;
	if(uidoc && uidoc.isUIDoc && !uidoc.isNewDoc){
		opts.docid = uidoc.docID;
		opts.inherit = true;
	}
	uiw.compose(opts);
}


function _Cmd_ComposeResponse(param1, param2, param3){
	if (typeof param2 == 'boolean') {
		var formname = param1;
		var inherit = param2;
		var docid = param3
	}
	else {
		var formname = (typeof param2 == 'undefined' || !param2 ? param1 : param2);
	}
	
	if(!formname || formname == ""){
		return;
	}	
	
	var uiw = Docova.getUIWorkspace(document); 	
	var opts = {'formname': formname, 'targetframe' : _targetframe, 'docid' : (docid ? docid : ''), 'inherit' : (inherit ? true : false)};
	var uidoc = uiw.currentDocument;
	if(uidoc && uidoc.isUIDoc && !uidoc.isNewDoc){
		opts.docid = uidoc.docID;
		opts.inherit = true;
		opts.isresponse = true;
	}else{
		if (!docid) {
			return;
		}
	}
	uiw.compose(opts);
}


function _Cmd_EditDocument(mode, previewpane){	
	var uidoc = Docova.getUIDocument();
	if(uidoc && uidoc.docID && uidoc.docID != ""){
		if(typeof mode != 'undefined' && mode === "0"){
			if(uidoc.editMode){
				uidoc.editMode = false;
			}
		}else{
			if(!uidoc.editMode){
				uidoc.editMode = true;				
			}
		}
	}else{
		//check if call is being made from a view
		var uiview = Docova.getUIView();
		var uiw = Docova.getUIWorkspace(document); 	
		if ( uiview ){
			var selid = uiview.getCurrentEntry();
			if ( selid ) {
				var doc = new DocovaDocument(uiview, selid, selid);
				uiw.editDocument(true, doc);
			}
		}

	}
}

function _Cmd_EditGotoField(fieldname){
	if(typeof fieldname == 'undefined' || fieldname === null || fieldname == ""){
		return;
	}
	var uidoc = Docova.getUIDocument();
	if(uidoc){
		uidoc.goToField(fieldname);
	}
}

function _Cmd_EditProfile(profilename, profilekey, immediate){	
	if(typeof profilename === 'undefined' || profilename === null || profilename == ""){
		return;
	}
	
	//-- perform the edit right now
	if(typeof immediate !== 'undefined' && (immediate == true || immediate == 1)){
		var uiw = Docova.getUIWorkspace(); 
		uiw.editProfile(profilename, (profilekey ? profilekey : ""), _targetframe);
	//-- schedule the edit for later
	}else{
		_AddPendingAction("EditProfile", 98, function(){_Cmd_EditProfile(profilename, profilekey, true)});			
	}
}

function _Cmd_FileCloseWindow(immediate){
	//-- perform the close right now
	if(typeof immediate !== 'undefined' && (immediate == true || immediate == 1)){
		var uidoc = Docova.getUIDocument();
		if(uidoc){
			if(uidoc.savepending){
				//-- schedule the close for later since document is still saving
				return _AddPendingAction("FileCloseWindow", 98, function(){_Cmd_FileCloseWindow(true)}, true);					
			}else{
				uidoc.close({"savePrompt" : !uidoc.saved});
			}
		}	
	//-- schedule the close for later
	}else{
		var notimer = false;
		var uidoc = Docova.getUIDocument();
		if(uidoc){
			if(uidoc.savepending){
				notimer = true;
			}
		}
		return _AddPendingAction("FileCloseWindow", 98, function(){_Cmd_FileCloseWindow(true)}, notimer);	
	}
}

function _Cmd_NavigateNext(){
	var uiview = Docova.getUIView();
	if(uiview){
		uiview.moveEntryHighlight({'direction': 'down'});
	}else{
		var uidoc = Docova.getUIDocument();
		if(uidoc && !uidoc.isNewDoc){
			var ws = Docova.getUIWorkspace(document);
			var appid = ws.getCurrentAppId();
			var appFrameId = "fra" + appid;
			var appFrameDoc = ws.getDocovaFrame(appFrameId, "document");
			var frameObj = Docova.getFrame(appFrameDoc, "appViewMain")
			if (frameObj) {
				uiview = $(frameObj)[0].contentWindow.Docova.getUIView();
			}
			if(uiview){
				var viewentry = uiview.getEntryByID({'entryid': uidoc.document.universalID});
				var targetdocid = null;
				while (viewentry){
					viewentry = uiview.getNextEntry({'entry' : viewentry});
					if(viewentry && viewentry.isRecord){
						targetdocid = viewentry.entryId;
						break;
					}	
				}
				if(targetdocid){					
					uiview.highlightEntry({'entryid': targetdocid});
					
					var docurl = "";
					if (typeof docInfo === "undefined") {
						var _protocol = document.location.protocol;
						var _hostname = document.location.hostname;
						var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
						var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
						_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash
						
						docurl = _protocol + "//" + _hostname + _port + "/" + _pathname;			
					}else{
						docurl = docInfo.ServerUrl + "/" + docInfo.NsfName;
					}			
					docurl += "/wReadDocument/" + targetdocid + "?opendocument&AppID="+ docInfo.AppID;	
					
					window.location = docurl;
				}
			}
		}
	}	
}

function _Cmd_NavigatePrev(){
	var uiview = Docova.getUIView();
	if(uiview){
		uiview.moveEntryHighlight({'direction': 'up'});
	}else{
		var uidoc = Docova.getUIDocument();
		if(uidoc && !uidoc.isNewDoc){
			var ws = Docova.getUIWorkspace(document);
			var appid = ws.getCurrentAppId();
			var appFrameId = "fra" + appid;
			var appFrameDoc = ws.getDocovaFrame(appFrameId, "document");
			var frameObj = Docova.getFrame(appFrameDoc, "appViewMain")
			if (frameObj) {
				uiview = $(frameObj)[0].contentWindow.Docova.getUIView();
			}
			if(uiview){
				var viewentry = uiview.getEntryByID({'entryid': uidoc.document.universalID});
				var targetdocid = null;
				while (viewentry){
					viewentry = uiview.getPreviousEntry({'entry' : viewentry});
					if(viewentry && viewentry.isRecord){
						targetdocid = viewentry.entryId;
						break;
					}	
				}
				if(targetdocid){					
					uiview.highlightEntry({'entryid': targetdocid});

					var docurl = "";
					if (typeof docInfo === "undefined") {
						var _protocol = document.location.protocol;
						var _hostname = document.location.hostname;
						var _port = (document.location.port == "80" || document.location.port == "") ? "" : ":" + document.location.port;
						var _pathname = (document.location.pathname).split(".nsf")[0] + ".nsf";
						_pathname = _pathname.substring(1, _pathname.length); //remove first forward slash
						
						docurl = _protocol + "//" + _hostname + _port + "/" + _pathname;			
					}else{
						docurl = docInfo.ServerUrl + "/" + docInfo.NsfName;
					}			
					docurl += "/wReadDocument/" + targetdocid + "?opendocument&AppID="+ docInfo.AppID;
					
					window.location = docurl;
				}
			}
		}
	}	
}

function _Cmd_OpenDocument(readoredit, unid){
	//TODO - add support for no unid
	
	var ws = Docova.getUIWorkspace(document);
	ws.openDocument({'docid': unid, editmode: readoredit, 'isapp': true});
}

function _Cmd_ViewExpandAll()
{
	var uiview = Docova.getUIView();
	if(uiview){
		uiview.expandAll();
	}
}

function _Cmd_ViewCollapseAll()
{
	var uiview = Docova.getUIView();
	if(uiview){
		uiview.collapseAll();
	}
}

function _Cmd_FileSave(){	
	var result = false;
	
	var uidoc = Docova.getUIDocument();
	if(uidoc && uidoc.isDocBeingEdited){
		return uidoc.save({"async": false, "andclose": false, "Navigate" : false});
		//result = true;
	}	
	return result;
}

function _Cmd_FileSaveAndClose(){	
	var result = false;
	
	var uidoc = Docova.getUIDocument();
	if(uidoc && uidoc.isDocBeingEdited){
		return uidoc.save({"async": false, "andclose": true, "Navigate" : true});
		//result = true;
	}	
	return result;
}

function _Cmd_FilePrint(){
	//-- use defined printPage function if defined
	if(typeof printPage === "function"){
		printPage();
	}else{
		var curoverflow = jQuery("div.divFormContentSectionPage").css("overflow");
		jQuery(".hideOnPrint").hide();  
		jQuery("div.divFormContentSectionPage").css("overflow", "initial");
		window.print();
		jQuery(".hideOnPrint").show()		
		jQuery("div.divFormContentSectionPage").css("overflow", curoverflow);		
	}
}

function _Cmd_MailAddress(param1){
	var options = {dlgtype: "multi", separator: ","};
	if(typeof param1 === "function"){
		options.cb = param1;
	}else{
		options.fieldname = "SendTo";
	}

	Docova.Utils.showAddressDialog(options);
}

function _Cmd_OpenPage(pagename){
	if(! pagename || pagename == ""){
		return;
	}

	var uiw = Docova.getUIWorkspace(document); 	
	uiw.openPage({'pagename': pagename, 'targetframe' : _targetframe});	
}

function _Cmd_OpenInNewWindow(){
	//TODO - finish function
}

function _Cmd_OpenView(viewname, key, newinstance){
	//TODO - add support for key and newinstance
	if(! viewname || viewname == ""){
		return;
	}

	var uiw = Docova.getUIWorkspace(document); 	
	uiw.openView({'viewname': viewname, 'targetframe' : _targetframe});
	
}


function _Cmd_ReloadWindow(){
	location.reload(true);
}

function _Cmd_RefreshFrame(framename)
{
	if(! framename || framename == ""){
		return;
	}
	var uiw = Docova.getUIWorkspace(document); 	
	var appobj = uiw.getCurrentApplication();
	if(appobj && appobj.isApp){
		var appFrameId = "fra" + appobj.appID;
    	var appFrameDoc = uiw.getDocovaFrame(appFrameId, "document");
		frameObj = uiw.getFrame(appFrameDoc, framename);
		if(frameObj){
			
			frameObj[0].contentWindow.location.href = frameObj[0].contentWindow.location.href;
    		
		}
		
	}
	
}

function _Cmd_ToolsRunMacro(agentName){
	var result = false;

    var uiw = Docova.getUIWorkspace(document);
	var appobj = uiw.getCurrentApplication();
	var agentobj = appobj.getAgent(agentName);
	result = agentobj.run();

	return result;
}

function _Cmd_ViewRefreshFields(immediate){
	//-- perform the refresh right now
	if(typeof immediate !== 'undefined' && (immediate == true || immediate == 1)){
		var uidoc = Docova.getUIDocument();
		if(uidoc){
			uidoc.refresh();
		}else{
			var uiview = Docova.getUIView();
			if(uiview){
				uiview.refresh();
			}
		}	
	//-- schedule the refresh for later
	}else{
		_AddPendingAction("ViewRefreshFields", 99, function(){_Cmd_ViewRefreshFields(true)});									
	}
}


/*----------------------------------
 * Helper Functions
 * --------------------------------- */

function _ProcessPendingActions(){
	if(typeof _pendingactionstimer !== null){
		window.clearTimeout(_pendingactionstimer);
		_pendingactionstimer = null;
	}
	
	if(_pendingactions && _pendingactions.length > 0){
		//-- sort the pending actions
		_pendingactions = _pendingactions.sort(
				function(x, y){
					return x.rank - y.rank;
				}
		);
		//-- execute the pending actions 
		while(_pendingactions.length > 0){
			var curaction = _pendingactions.shift();
			if(curaction !== null && typeof curaction == "object"){
				curaction.action();
				curaction.action = null;
				curaction.name = "";
				curaction.rank = 0;
				curaction = null;
			}
		}
	}
}

function _AddPendingAction(actionname, actionrank, actioncode, notimer){
	var result = false;
	
	if(typeof actionname !== 'string' || typeof actionrank !== 'number' || typeof actioncode !== "function"){
		return result;
	}
	
	if(_pendingactions === null){
		_pendingactions = [];
	}

	var addaction = true;
	//-- check to see if action already added if so don't add it again
	for(var i=0; i<_pendingactions.length; i++){
		if(_pendingactions[i].name == actionname){
			addaction = false;
		}
	}
	if(addaction){
		_pendingactions.push({
			name: actionname, 
			rank: actionrank,
			action: actioncode
		});
		if(typeof _pendingactionstimer !== null){
			window.clearTimeout(_pendingactionstimer);
		}
		if(!(typeof notimer !== "undefined" && notimer === true)){
			_pendingactionstimer = window.setTimeout(_ProcessPendingActions, 10); 
		}
		result = true;
	}	
	
	return result;
}

function _HandleReturnValue(){ 
	Error.apply(this, arguments); 
	this.name = "_HandleReturnValue"; 
}
_HandleReturnValue.prototype = Object.create(Error.prototype);

