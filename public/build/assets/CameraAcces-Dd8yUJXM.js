import{r as s,j as e}from"./app-D_MHyhh1.js";import l from"./CameraScan-CUoYA-bR.js";function p({scanned:n}){const t=s.useRef(null),[a,o]=s.useState(null),c=s.useRef(!1),u=r=>{c.current||r!==a&&(o(r),c.current=!0,setTimeout(()=>{c.current=!1},1e3),console.log(r),n(r))};return s.useEffect(()=>()=>{t.current&&t.current.srcObject&&t.current.srcObject.getTracks().forEach(f=>f.stop())},[]),e.jsx(e.Fragment,{children:e.jsx("div",{className:"w-4/5 mt-10 m-auto h-auto max-w-lg",children:e.jsx(l,{onScan:u})})})}export{p as default};
