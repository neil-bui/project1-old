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
!function(e){"use strict";var n="shadowbox",o="player",t=TubePressJsConfig.urls.usr+"/vendor/"+n+"js/v3/"+n,i="html",s="gallery",a=s+"Id",r="embedded",l=e.Beacon,u=l.subscribe,d=e.Lang.Utils,h=e.DomInjector,c="tubepress."+s+"."+o+".",b="#sb-"+o,p=e.Vendors.jQuery,f=function(){return d.isDefined(window.Shadowbox)},w=function(){Shadowbox.path=t,Shadowbox.init({initialHeight:160,initialWidth:320,skipSetup:!0,players:[i],useSizzle:!1}),Shadowbox.load()},g=function(){f()||(h.loadJs(t+".js"),h.loadCss(t+".css"),d.callWhenTrue(w,f,300))},S=function(n,o){var t=e.Gallery,s=o[a],l=t.Options,u=l.getOption,d=u(s,r+"Height"),h=u(s,r+"Width");Shadowbox.open({player:i,height:d,width:h,content:"&nbsp;"})},v=function(e){p(b).html(e)},x=function(e,n){var o=function(){v(n.html)},t=function(){return p(b).length>0};d.callWhenTrue(o,t,100)};u(c+"invoke."+n,S),u(c+"populate."+n,x),g()}(TubePress);
