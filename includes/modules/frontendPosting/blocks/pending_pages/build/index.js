(()=>{"use strict";var e,n={178:(e,n,r)=>{const t=window.wp.blocks,i=window.wp.element,o=(window.wp.i18n,window.wp.blockEditor),a=window.wp.apiFetch;var s=r.n(a);const p=JSON.parse('{"u2":"sim/pendingpages"}');(0,t.registerBlockType)(p.u2,{icon:"admin-page",edit:()=>{const[e,n]=(0,i.useState)([]);return(0,i.useEffect)((async()=>{const e=await s()({path:sim.restApiPrefix+"/frontendposting/pending_pages"});n(e)}),[]),(0,i.createElement)(i.Fragment,null,(0,i.createElement)("div",(0,o.useBlockProps)(),wp.element.RawHTML({children:e})))},save:()=>null})}},r={};function t(e){var i=r[e];if(void 0!==i)return i.exports;var o=r[e]={exports:{}};return n[e](o,o.exports,t),o.exports}t.m=n,e=[],t.O=(n,r,i,o)=>{if(!r){var a=1/0;for(c=0;c<e.length;c++){r=e[c][0],i=e[c][1],o=e[c][2];for(var s=!0,p=0;p<r.length;p++)(!1&o||a>=o)&&Object.keys(t.O).every((e=>t.O[e](r[p])))?r.splice(p--,1):(s=!1,o<a&&(a=o));if(s){e.splice(c--,1);var l=i();void 0!==l&&(n=l)}}return n}o=o||0;for(var c=e.length;c>0&&e[c-1][2]>o;c--)e[c]=e[c-1];e[c]=[r,i,o]},t.n=e=>{var n=e&&e.__esModule?()=>e.default:()=>e;return t.d(n,{a:n}),n},t.d=(e,n)=>{for(var r in n)t.o(n,r)&&!t.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:n[r]})},t.o=(e,n)=>Object.prototype.hasOwnProperty.call(e,n),(()=>{var e={826:0,431:0};t.O.j=n=>0===e[n];var n=(n,r)=>{var i,o,a=r[0],s=r[1],p=r[2],l=0;if(a.some((n=>0!==e[n]))){for(i in s)t.o(s,i)&&(t.m[i]=s[i]);if(p)var c=p(t)}for(n&&n(r);l<a.length;l++)o=a[l],t.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return t.O(c)},r=self.webpackChunksim_pendingpages=self.webpackChunksim_pendingpages||[];r.forEach(n.bind(null,0)),r.push=n.bind(null,r.push.bind(r))})();var i=t.O(void 0,[431],(()=>t(178)));i=t.O(i)})();