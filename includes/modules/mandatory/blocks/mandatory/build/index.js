(()=>{"use strict";var e,r={178:(e,r,t)=>{const n=window.wp.blocks,a=window.wp.element,o=(window.wp.i18n,window.wp.blockEditor),s=window.wp.apiFetch;var i=t.n(s);const l=JSON.parse('{"u2":"sim/mandatorypages"}');(0,n.registerBlockType)(l.u2,{icon:"page",edit:()=>{const[e,r]=(0,a.useState)([]);return(0,a.useEffect)((async()=>{const e=await i()({path:sim.restApiPrefix+"/mandatory_content/must_read_documents"});r(e)}),[]),(0,a.createElement)(a.Fragment,null,(0,a.createElement)("div",(0,o.useBlockProps)(),wp.element.RawHTML({children:e})))},save:()=>null})}},t={};function n(e){var a=t[e];if(void 0!==a)return a.exports;var o=t[e]={exports:{}};return r[e](o,o.exports,n),o.exports}n.m=r,e=[],n.O=(r,t,a,o)=>{if(!t){var s=1/0;for(c=0;c<e.length;c++){t=e[c][0],a=e[c][1],o=e[c][2];for(var i=!0,l=0;l<t.length;l++)(!1&o||s>=o)&&Object.keys(n.O).every((e=>n.O[e](t[l])))?t.splice(l--,1):(i=!1,o<s&&(s=o));if(i){e.splice(c--,1);var p=a();void 0!==p&&(r=p)}}return r}o=o||0;for(var c=e.length;c>0&&e[c-1][2]>o;c--)e[c]=e[c-1];e[c]=[t,a,o]},n.n=e=>{var r=e&&e.__esModule?()=>e.default:()=>e;return n.d(r,{a:r}),r},n.d=(e,r)=>{for(var t in r)n.o(r,t)&&!n.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:r[t]})},n.o=(e,r)=>Object.prototype.hasOwnProperty.call(e,r),(()=>{var e={826:0,431:0};n.O.j=r=>0===e[r];var r=(r,t)=>{var a,o,s=t[0],i=t[1],l=t[2],p=0;if(s.some((r=>0!==e[r]))){for(a in i)n.o(i,a)&&(n.m[a]=i[a]);if(l)var c=l(n)}for(r&&r(t);p<s.length;p++)o=s[p],n.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return n.O(c)},t=self.webpackChunksim_mandatorypages=self.webpackChunksim_mandatorypages||[];t.forEach(r.bind(null,0)),t.push=r.bind(null,t.push.bind(t))})();var a=n.O(void 0,[431],(()=>n(178)));a=n.O(a)})();