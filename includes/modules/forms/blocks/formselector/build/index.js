!function(){"use strict";var e,n={452:function(e,n,r){var t=window.wp.blocks,o=window.wp.element,i=(window.wp.i18n,window.wp.blockEditor),u=window.wp.apiFetch,c=r.n(u),f=window.wp.components,l=JSON.parse('{"u2":"sim/formselector"}');(0,t.registerBlockType)(l.u2,{icon:"forms",edit:()=>{const[e,n]=(0,o.useState)((0,o.createElement)(f.Spinner,null));return(0,o.useEffect)((()=>{!async function(){n((0,o.createElement)(f.Spinner,null));const e=await c()({path:sim.restApiPrefix+"/forms/form_selector"});n(e)}()}),[]),(0,o.createElement)(o.Fragment,null,(0,o.createElement)("div",(0,i.useBlockProps)(),wp.element.RawHTML({children:e})))},save:()=>null})}},r={};function t(e){var o=r[e];if(void 0!==o)return o.exports;var i=r[e]={exports:{}};return n[e](i,i.exports,t),i.exports}t.m=n,e=[],t.O=function(n,r,o,i){if(!r){var u=1/0;for(s=0;s<e.length;s++){r=e[s][0],o=e[s][1],i=e[s][2];for(var c=!0,f=0;f<r.length;f++)(!1&i||u>=i)&&Object.keys(t.O).every((function(e){return t.O[e](r[f])}))?r.splice(f--,1):(c=!1,i<u&&(u=i));if(c){e.splice(s--,1);var l=o();void 0!==l&&(n=l)}}return n}i=i||0;for(var s=e.length;s>0&&e[s-1][2]>i;s--)e[s]=e[s-1];e[s]=[r,o,i]},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,{a:n}),n},t.d=function(e,n){for(var r in n)t.o(n,r)&&!t.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:n[r]})},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},function(){var e={826:0,431:0};t.O.j=function(n){return 0===e[n]};var n=function(n,r){var o,i,u=r[0],c=r[1],f=r[2],l=0;if(u.some((function(n){return 0!==e[n]}))){for(o in c)t.o(c,o)&&(t.m[o]=c[o]);if(f)var s=f(t)}for(n&&n(r);l<u.length;l++)i=u[l],t.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return t.O(s)},r=self.webpackChunksim_formselector=self.webpackChunksim_formselector||[];r.forEach(n.bind(null,0)),r.push=n.bind(null,r.push.bind(r))}();var o=t.O(void 0,[431],(function(){return t(452)}));o=t.O(o)}();