!function(){"use strict";var e,n={452:function(e,n,l){var r=window.wp.blocks,t=window.wp.element,a=window.wp.i18n,o=window.wp.blockEditor,i=window.wp.components,c=window.wp.apiFetch,u=l.n(c),s=JSON.parse('{"u2":"sim/show-children"}');(0,r.registerBlockType)(s.u2,{icon:"category",edit:e=>{let{attributes:n,setAttributes:l,context:r}=e;console.log(r);const{title:c,listtype:s,grandchildren:p,parents:d,grantparents:m}=n,{postId:h}=r,[g,v]=(0,t.useState)((0,t.createElement)(i.Spinner,null));return(0,t.useEffect)((async()=>{const e=await u()({path:sim.restApiPrefix+"/show_children",method:"POST",data:{title:c,listtype:s,grandchildren:p,parents:d,grantparents:m,postid:h}});v(e)}),[n]),(0,t.createElement)(t.Fragment,null,(0,t.createElement)(o.InspectorControls,null,(0,t.createElement)(i.Panel,null,(0,t.createElement)(i.PanelBody,null,(0,t.createElement)(i.ToggleControl,{label:(0,a.__)("Show title","sim"),checked:!!n.title,onChange:()=>l({title:!n.title})}),(0,t.createElement)(i.SelectControl,{label:"List style",value:n.listtype,options:[{label:"none",value:"none"},{label:"disc",value:"disc"},{label:"circle",value:"circle"},{label:"square",value:"square"},{label:"decimal",value:"decimal"},{label:"decimal-leading-zero",value:"decimal-leading-zero"},{label:"lower-roman",value:"lower-roman"},{label:"upper-roman",value:"upper-roman"},{label:"lower-greek",value:"lower-greek"},{label:"lower-latin",value:"lower-latin"},{label:"upper-latin",value:"upper-latin"},{label:"armenian",value:"armenian"},{label:"georgian",value:"georgian"},{label:"lower-alpha",value:"lower-alpha"},{label:"upper-alpha",value:"upper-alpha"}],onChange:e=>l({listtype:e}),__nextHasNoMarginBottom:!0}),(0,t.createElement)(i.ToggleControl,{label:(0,a.__)("Show grandchildren","sim"),checked:!!n.grandchildren,onChange:()=>l({grandchildren:!n.grandchildren})}),(0,t.createElement)(i.ToggleControl,{label:(0,a.__)("Show parents","sim"),checked:!!n.parents,onChange:()=>l({parents:!n.parents})}),(0,t.createElement)(i.__experimentalNumberControl,{label:(0,a.__)("Show grantparents","sim"),value:n.grantparents,onChange:e=>l({grantparents:parseInt(e)}),min:1,max:12})))),(0,t.createElement)("div",(0,o.useBlockProps)(),(0,t.createElement)("div",(0,o.useBlockProps)(),wp.element.RawHTML({children:g}))))},save:()=>null})}},l={};function r(e){var t=l[e];if(void 0!==t)return t.exports;var a=l[e]={exports:{}};return n[e](a,a.exports,r),a.exports}r.m=n,e=[],r.O=function(n,l,t,a){if(!l){var o=1/0;for(s=0;s<e.length;s++){l=e[s][0],t=e[s][1],a=e[s][2];for(var i=!0,c=0;c<l.length;c++)(!1&a||o>=a)&&Object.keys(r.O).every((function(e){return r.O[e](l[c])}))?l.splice(c--,1):(i=!1,a<o&&(o=a));if(i){e.splice(s--,1);var u=t();void 0!==u&&(n=u)}}return n}a=a||0;for(var s=e.length;s>0&&e[s-1][2]>a;s--)e[s]=e[s-1];e[s]=[l,t,a]},r.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(n,{a:n}),n},r.d=function(e,n){for(var l in n)r.o(n,l)&&!r.o(e,l)&&Object.defineProperty(e,l,{enumerable:!0,get:n[l]})},r.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},function(){var e={826:0,431:0};r.O.j=function(n){return 0===e[n]};var n=function(n,l){var t,a,o=l[0],i=l[1],c=l[2],u=0;if(o.some((function(n){return 0!==e[n]}))){for(t in i)r.o(i,t)&&(r.m[t]=i[t]);if(c)var s=c(r)}for(n&&n(l);u<o.length;u++)a=o[u],r.o(e,a)&&e[a]&&e[a][0](),e[a]=0;return r.O(s)},l=self.webpackChunksim_children=self.webpackChunksim_children||[];l.forEach(n.bind(null,0)),l.push=n.bind(null,l.push.bind(l))}();var t=r.O(void 0,[431],(function(){return r(452)}));t=r.O(t)}();