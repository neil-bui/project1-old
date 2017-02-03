/**
 * @license
 *
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com/)
 *
 * This file is part of TubePress Pro.
 *
 * License summary
 *   - Can be used on 1 site, 1 server
 *   - Cannot be resold or distributed
 *   - Commercial use allowed
 *   - Can modify source-code but cannot distribute modifications (derivative works)
 *
 * Please see http://tubepress.com/license for details.
 */
!function(e){"use strict";var n="fancybox",t=n+"2",i="gallery",o="player",r="embedded",s="jquery",a="mediaItem",c="title",d=".",l=i+"Id",u=e.Beacon.subscribe,f=e.DomInjector,y=e.Lang.Utils,b=y.isDefined,g="tubepress."+i+d+o+d,h=e.Vendors.jQuery,p=function(){return b(h[n])},m=function(){if(!p()){var i=e.Environment,o=i.getUserContentUrl(),r=o+"/vendor/"+t+"/"+s+d+n+d;f.loadJs(r+"js"),f.loadCss(r+"css")}},v=function(){h.fancybox.showLoading()},j=function(n,t){var i=t[l],o=e.Gallery,s=o.Options,d=s.getOption,u=d(i,r+"Height"),f=d(i,r+"Width"),y={content:t.html,height:u,width:f,type:"inline"};b(t[a])&&b(t[a][c])&&(y[c]=t[a][c]),h.fancybox(y)};u(g+"invoke."+t,v),u(g+"populate."+t,j),m()}(TubePress);
