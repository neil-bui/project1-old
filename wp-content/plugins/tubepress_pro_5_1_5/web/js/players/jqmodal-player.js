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
!function(e){"use strict";var o="jqmodal",n=e.Beacon.subscribe,t="web/vendor/jqmodal/jqModal.",i=e.DomInjector,d="gallery",s="embedded",r="px",a="Id",l="id",m="tubepress."+d+".player.",u=d+a,c="item"+a,j=e.Vendors.jQuery,h=function(e){e.o.remove(),e.w.remove()},p=function(e){return o+e[u]+e[c]},v=function(o,n){var t=j("<div />"),i=e.Gallery,d=n[u],a=i.Options,m=a.getOption,c=m(d,s+"Width",640),v=m(d,s+"Height",360),b=-1*(c/2);t.attr(l,p(n)),t.hide(),t.height(v+r),t.width(c+r),t.addClass("jqmWindow"),t.css("margin-left",b+r),t.appendTo("body"),t.jqm({onHide:h}).jqmShow()},b=function(e,o){var n=j("#"+p(o));n.html(o.html)};j.isFunction(j.fn.jqm)||(i.loadJs(t+"js"),i.loadCss(t+"css")),n(m+"invoke."+o,v),n(m+"populate."+o,b)}(TubePress);
