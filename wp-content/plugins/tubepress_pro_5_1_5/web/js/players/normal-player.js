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
!function(e){"use strict";var t="tubepress",r="#",n=t+"_",o="embedded_",l="player",a="gallery",u=a+"Id",i="html",c=".",s=" ",d="-",f="normal",p="title",b=":first",m=c+"js"+d+t+d+l+d+f+b,y=t+c+a+c+l+c,g=e.Beacon,h=g.subscribe,v=g.publish,S=e.Ajax.LoadStyler,j=S.applyLoadingStyle,_=S.removeLoadingStyle,L=!1,T=e.Vendors.jQuery,k=function(t){return e.Gallery.Selectors.getOutermostSelectorModern(t)+s+m},x=function(e){return r+n+o+p+"_"+e},A=function(e){return T(x(e))},B=function(e){return r+n+o+"object_"+e},G=function(e){return T(B(e))},I=function(e,t){var r,n,o,a={},i="disable",c="target",s="duration";e.length>0&&(a[l+"Name"]=f,a[i]=L,a[u]=t,a[c]=e.offset().top,a[s]=0,v(y+"scroll",a),n=a[i]!==L,o=a[c],r=a[s],n||T("html, body").animate({scrollTop:o},r))},M=function(e,t){var r=t[u],n=x(r),o=A(r),l=B(r),a=k(r),i=T(a);j(a),j(n),j(l),I(i,r),I(o,r)},N=function(e,t){var r=t[u],n=A(r),o=G(r),l=t[i],a=k(r),c=T(a);o.length>0&&n.length>0?n.parent("div").replaceWith(l):(c.html(l),_(a))};h(y+"invoke"+c+f,M),h(y+"populate"+c+f,N)}(TubePress);
