!function(){"use strict";var e,n={452:function(e,n,t){var r=window.wp.blocks,i=window.wp.element,o=(window.wp.i18n,window.wp.blockEditor),u=window.wp.apiFetch,a=t.n(u),c=window.wp.components,s=JSON.parse('{"u2":"sim/statistics"}');(0,r.registerBlockType)(s.u2,{icon:"page",edit:()=>{const[e,n]=(0,i.useState)((0,i.createElement)(c.Spinner,null));return(0,i.useEffect)((async()=>{n((0,i.createElement)(c.Spinner,null));const e=await a()({path:sim.restApiPrefix+"/statistics/page_statistics"});n(e)}),[]),(0,i.createElement)(i.Fragment,null,(0,i.createElement)("div",(0,o.useBlockProps)(),wp.element.RawHTML({children:e})))},save:()=>null})}},t={};function r(e){var i=t[e];if(void 0!==i)return i.exports;var o=t[e]={exports:{}};return n[e](o,o.exports,r),o.exports}r.m=n,e=[],r.O=function(n,t,i,o){if(!t){var u=1/0;for(l=0;l<e.length;l++){t=e[l][0],i=e[l][1],o=e[l][2];for(var a=!0,c=0;c<t.length;c++)(!1&o||u>=o)&&Object.keys(r.O).every((function(e){return r.O[e](t[c])}))?t.splice(c--,1):(a=!1,o<u&&(u=o));if(a){e.splice(l--,1);var s=i();void 0!==s&&(n=s)}}return n}o=o||0;for(var l=e.length;l>0&&e[l-1][2]>o;l--)e[l]=e[l-1];e[l]=[t,i,o]},r.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(n,{a:n}),n},r.d=function(e,n){for(var t in n)r.o(n,t)&&!r.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},r.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},function(){var e={826:0,431:0};r.O.j=function(n){return 0===e[n]};var n=function(n,t){var i,o,u=t[0],a=t[1],c=t[2],s=0;if(u.some((function(n){return 0!==e[n]}))){for(i in a)r.o(a,i)&&(r.m[i]=a[i]);if(c)var l=c(r)}for(n&&n(t);s<u.length;s++)o=u[s],r.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return r.O(l)},t=self.webpackChunksim_pendingpages=self.webpackChunksim_pendingpages||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))}();var i=r.O(void 0,[431],(function(){return r(452)}));i=r.O(i)}();