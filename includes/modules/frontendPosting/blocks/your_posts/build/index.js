(()=>{"use strict";var e,t={452:(e,t,r)=>{const n=window.wp.blocks,o=window.wp.element,s=(window.wp.i18n,window.wp.blockEditor),i=window.wp.apiFetch;var a=r.n(i);const l=window.wp.components,p=JSON.parse('{"u2":"sim/yourposts"}');(0,n.registerBlockType)(p.u2,{icon:"admin-post",edit:e=>{let{attributes:t,setAttributes:r}=e;const[n,i]=(0,o.useState)((0,o.createElement)(l.Spinner,null));return(0,o.useEffect)((async()=>{const e=await a()({path:sim.restApiPrefix+"/frontendposting/your_posts"});i(e)}),[]),(0,o.createElement)(o.Fragment,null,(0,o.createElement)("div",(0,s.useBlockProps)(),wp.element.RawHTML({children:n})))},save:()=>null})}},r={};function n(e){var o=r[e];if(void 0!==o)return o.exports;var s=r[e]={exports:{}};return t[e](s,s.exports,n),s.exports}n.m=t,e=[],n.O=(t,r,o,s)=>{if(!r){var i=1/0;for(u=0;u<e.length;u++){r=e[u][0],o=e[u][1],s=e[u][2];for(var a=!0,l=0;l<r.length;l++)(!1&s||i>=s)&&Object.keys(n.O).every((e=>n.O[e](r[l])))?r.splice(l--,1):(a=!1,s<i&&(i=s));if(a){e.splice(u--,1);var p=o();void 0!==p&&(t=p)}}return t}s=s||0;for(var u=e.length;u>0&&e[u-1][2]>s;u--)e[u]=e[u-1];e[u]=[r,o,s]},n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={826:0,431:0};n.O.j=t=>0===e[t];var t=(t,r)=>{var o,s,i=r[0],a=r[1],l=r[2],p=0;if(i.some((t=>0!==e[t]))){for(o in a)n.o(a,o)&&(n.m[o]=a[o]);if(l)var u=l(n)}for(t&&t(r);p<i.length;p++)s=i[p],n.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return n.O(u)},r=self.webpackChunksim_events=self.webpackChunksim_events||[];r.forEach(t.bind(null,0)),r.push=t.bind(null,r.push.bind(r))})();var o=n.O(void 0,[431],(()=>n(452)));o=n.O(o)})();