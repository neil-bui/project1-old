/**
 * @license
 *
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com)
 *
 * This file is part of TubePress (http://tubepress.com)
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
!function(t,e,o){"use strict";var i="popup",n=t.Beacon.subscribe,l={},r="gallery",a="Id",h="tubepress."+r+".player.",s=r+a,c="item",d=c+a,p="height",m="width",u="embedded",w="mediaItem",b=function(t){return t/2},g=function(i,n){var r=t.Gallery,a=n[s],h=r.Options,c=h.getOption,w=c(a,u+"Height"),g=c(a,u+"Width"),T=b(e[p])-b(w),x=b(e[m])-b(g);l[a+n[d]]=o.open("","","location=0,directories=0,menubar=0,scrollbars=0,status=0,toolbar=0,width="+g+"px,height="+w+"px,top="+T+",left="+x)},T=function(t,e){var o=e[w],i=o.title,n='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title>'+i+'</title></head><body style="margin: 0pt; background-color: black;">',r="</body></html>",a=l[e[s]+e[d]].document;a.write(n+e.html+r),a.close()};n(h+"invoke."+i,g),n(h+"populate."+i,T)}(TubePress,screen,window);
