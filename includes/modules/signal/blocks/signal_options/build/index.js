(()=>{"use strict";const e=window.wp.element,t=window.wp.components,n=window.wp.data,s=window.wp.coreData,l=(window.wp.htmlEntities,window.wp.editPost),{__}=wp.i18n,{createHigherOrderComponent:a}=wp.compose,{Fragment:o}=wp.element,{InspectorControls:i}=wp.blockEditor,{PanelBody:r,ToggleControl:g,CheckboxControl:c}=wp.components;wp.hooks.addFilter("blocks.registerBlockType","sim/signal-attribute",(function(e){return void 0!==e.attributes&&(e.attributes=Object.assign(e.attributes,{send_signal:{type:"boolean"},signal_message_type:{type:"string"},signal_extra_message:{type:"string"},signal_url:{type:"boolean"}})),e}));const m=a((a=>i=>{const r=(0,n.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[c,m]=(0,s.useEntityProp)("postType",r,"meta"),p=c.send_signal,d=c.signal_message_type,w=c.signal_extra_message,u=c.signal_url,h=(e,t)=>{let n={...c};n[t]=e,m(n)};return(0,e.createElement)(o,null,(0,e.createElement)(a,i),(0,e.createElement)(l.PluginDocumentSettingPanel,{name:"signal-options",title:__("Signal Options","sim"),className:"signal-options"},(0,e.createElement)(g,{label:__("Send signal message on publish","sim"),checked:p,onChange:e=>h(e,"send_signal")}),(0,e.createElement)(t.RadioControl,{selected:d,options:[{label:__("Send a summary"),value:"summary"},{label:__("Send the whole post content"),value:"all"}],onChange:e=>h(e,"signal_message_type")}),(0,e.createElement)("br",null),(0,e.createElement)(t.TextareaControl,{label:__("Add this sentence to the signal message:"),value:w,onChange:e=>h(e,"signal_extra_message")}),(0,e.createElement)(g,{label:__("Include the url in the message even if the whole content is posted","sim"),checked:u,onChange:e=>h(e,"signal_url")})))}),"signalControls");wp.hooks.addFilter("editor.BlockEdit","sim/signal-controls",m)})();