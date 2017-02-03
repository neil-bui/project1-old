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
!function(e){"use strict";var t="tubepress",a=".",o="gallery",r=o+"Id",n="player",l="detached",s="html",i="-",u=t+a+o+a+n+a,c=e.Vendors.jQuery,d=e.Beacon,p=d.subscribe,y=d.publish,b=e.Ajax.LoadStyler,f=b.applyLoadingStyle,h=b.removeLoadingStyle,m=t+a+o+a+n+a,v=function(e){return"#js-"+t+i+n+i+l+i+e},g=function(e,t){var a,o,s,i={},u="disable",d="target",p="duration";e.length>0&&(i[n+"Name"]=l,i[u]=!1,i[r]=t,i[d]=e.offset().top,i[p]=0,y(m+"scroll",i),o=i[u]!==!1,s=i[d],a=i[p],o||c("html, body").animate({scrollTop:s},a))},j=function(e,t){var a=t[r],o=v(a),n=c(o);f(o),g(n,a)},L=function(e,t){var a=t[r],o=t[s],n=v(a),l=c(n);l.html(o),h(n)};p(u+"invoke."+l,j),p(u+"populate."+l,L)}(TubePress);
