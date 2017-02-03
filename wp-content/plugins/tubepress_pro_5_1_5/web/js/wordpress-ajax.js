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
!function(n){"use strict";var r,e="url",t="tubepress",a="action",i="data",o=i+"Type",s=n.Vendors.jQuery,u=function(n,u,c){var f,p,d,l,j=u[i],m=j&&j.hasOwnProperty(t+"_"+a);m&&(f=n[e],d=f===r,p="html"===n[o],l=0===f.indexOf(r+" "),(d||p&&l)&&(j[a]=t,n[i]=s.param(j)))},c=function(){s.ajaxPrefilter(u),r=n.Environment.getAjaxEndpointUrl()};s(c)}(TubePress);
