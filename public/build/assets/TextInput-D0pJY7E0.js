import{i as u,s as c,o as s,f as t,b as d,t as l,r as m,A as p,j as _,p as f,k as g}from"./app-C3rSk9qy.js";const v={class:"text-sm text-red-600"},S={__name:"InputError",props:{message:{type:String}},setup(e){return(a,o)=>u((s(),t("div",null,[d("p",v,l(e.message),1)],512)),[[c,e.message]])}},x={class:"block text-sm font-medium text-gray-700"},h={key:0},y={key:1},V={__name:"InputLabel",props:{value:{type:String}},setup(e){return(a,o)=>(s(),t("label",x,[e.value?(s(),t("span",h,l(e.value),1)):(s(),t("span",y,[m(a.$slots,"default")]))]))}},M={__name:"TextInput",props:{modelValue:{type:String,required:!0},modelModifiers:{}},emits:["update:modelValue"],setup(e,{expose:a}){const o=p(e,"modelValue"),n=_(null);return f(()=>{n.value.hasAttribute("autofocus")&&n.value.focus()}),a({focus:()=>n.value.focus()}),(b,r)=>u((s(),t("input",{class:"rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500","onUpdate:modelValue":r[0]||(r[0]=i=>o.value=i),ref_key:"input",ref:n},null,512)),[[g,o.value]])}};export{V as _,M as a,S as b};
