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
!function(e){"use strict";var t="fancybox",n="gallery",i="player",o="embedded",s="jquery",r="mediaItem",a="title",c="-1.3.4.",u=".",d=n+"Id",l=e.Beacon.subscribe,f=e.DomInjector,b=e.Lang.Utils,y=b.isDefined,h="web/vendor/"+t+"/",m="tubepress."+n+u+i+u,p=e.Vendors.jQuery,v=function(){return y(p[t])},g=function(){if(!v()){var e=h+s+u+t+c;f.loadJs(e+"js"),f.loadCss(e+"css")}},j=function(){p.fancybox.showActivity()},w=function(t,n){var i=n[d],s=e.Gallery,c=s.Options,u=c.getOption,l=u(i,o+"Height"),f=u(i,o+"Width"),b={content:n.html,height:l,width:f,autoDimensions:!1};y(n[r])&&y(n[r][a])&&(b[a]=n[r][a]),p.fancybox(b)};l(m+"invoke."+t,j),l(m+"populate."+t,w),g()}(TubePress);
