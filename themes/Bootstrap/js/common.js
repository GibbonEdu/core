/*
son of suckerfish menu script from:
http://www.htmldog.com/articles/suckerfish/dropdowns/
 */
 
 sfHover=function() {
	var sfEls=document.getElementById("nav").getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";
			this.style.zIndex=200; //this line added to force flyout to be above relatively positioned stuff in IE
		}
		sfEls[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(" sfhover\\b"), "");
		}
	}
}
if (window.attachEvent) window.attachEvent("onload", sfHover);



