/**
 * @license
 *
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com/).
 * This file is part of TubePress Pro.
 * REDISTRIBUTION PROHIBITED. Please see http://tubepress.com/license for details.
 */
!function(n,t){"use strict";var r=t.Beacon,e=r.publish,i=t.Lang.Utils.isDefined,u="tubepress",o="start",f="stop",s="paused",a="buffering",c="error",g="publish",l=u+".item.",p={},d={},h=t.Vendors.jQuery,y=function(){d[o]=l+o,d[f]=l+f,d[s]=l+s,d[a]=l+a,d[c]=l+c},b=function(){var n=function(n,t){t.guid=n,p[n]=t,e(l+"load",t)},t=function(n,t){var r,e,u,o,f=[];n=i(n)?n:{},t=!!i(t)&&t;for(r in p)if(p.hasOwnProperty(r)){e=!0,u=p[r];for(o in n)if(n.hasOwnProperty(o)){if(u.hasOwnProperty(o)&&u[o]!==n[o]){e=!1;break}}else if(t){e=!1;break}e&&f.push(h.extend({},u))}return f},r=function(n){var r=t({guid:n});return 0===r.length?null:r[0]};return{getItemByGuid:r,getItemsMatchingFilter:t,register:n}}(),v=function(){var n=function(n,t){var e=b.getItemByGuid(t);e&&r.publish(n,e)},t=function(t){n(d[o],t)},e=function(t){n(d[f],t)},i=function(t){n(d[s],t)},u=function(t){n(d[a],t)},l=function(t){n(d[c],t)},p={};return p[g+"Start"]=t,p[g+"Stop"]=e,p[g+"Pause"]=i,p[g+"Buffering"]=u,p[g+"Error"]=l,p}();y(),t.PlayerApi={EventUtility:v,Registry:b}}(window,TubePress);
