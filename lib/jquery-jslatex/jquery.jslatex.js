/*
 * jsLaTeX v1.2 - jQuery plugin
 * 
 *  Copyright (c) 2009 Andreas Grech
 *
 *  Dual licensed under the MIT and GPL licenses:
 *    http://www.opensource.org/licenses/mit-license.php
 *    http://www.gnu.org/licenses/gpl.html
 *
 * http://knowledge-aholic.blogspot.com
 */

(function($){var attachToImage=function(){return $("<img/>").attr({src:this.src})},formats={'gif':attachToImage,'png':attachToImage,'swf':function(){return $("<embed/>").attr({src:this.src,type:'application/x-shockwave-flash'})}},sections={'{f}':'format','{e}':'equation'},escapes={'+':'2B','=':'3D'};$.fn.latex=function(opts){opts=$.extend({},$.fn.latex.defaults,opts);opts.format=formats[opts.format]?opts.format:'gif';return this.each(function(){var $this=$(this),format,s,element,url=opts.url;opts.equation=$.trim($this.text());for(s in sections){if(sections.hasOwnProperty(s)&&(format=url.indexOf(s))>=0){url=url.replace(s,opts[sections[s]])}}for(s in escapes){if(escapes.hasOwnProperty(s)&&(format=url.indexOf(s))>=0){url=url.replace(s,'%'+escapes[s])}}opts.src=url;element=formats[opts.format].call(opts);$this.html('').append(element);if(opts.callback){opts.callback.call(element)}})};$.fn.latex.defaults={format:'gif',url:'http://latex.codecogs.com/{f}.latex?{e}'}}(jQuery));
