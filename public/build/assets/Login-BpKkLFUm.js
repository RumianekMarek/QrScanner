import{a as C,r as o,j as e,M as L,U}from"./app-CQ4f3GVW.js";import{C as E}from"./Checkbox-n4ULevqI.js";import{I as h}from"./InputError-m5cpulIY.js";import{I as g}from"./InputLabel-Fr0YsOxJ.js";import{P as l}from"./PrimaryButton-CWL8fghW.js";import{T as n}from"./TextInput-DNoGQNyK.js";import{G as F}from"./GuestLayout-mgluXXmR.js";function q({status:m,canResetPassword:j}){const{data:t,setData:a,post:i,processing:b,errors:c,reset:d}=C({email:"",password:"",remember:!1,targetUrl:""}),[v,u]=o.useState(!1),[w,x]=o.useState(null),r=o.useRef(null),N=s=>{s.preventDefault(),i(route("login"),{onFinish:()=>d("password")})},k=s=>{s.preventDefault(),i(route("post.token"),{onFinish:()=>{f(),d("targetUrl")}})};o.useEffect(()=>{if(r){const s=I=>{setTimeout(()=>{var p;(p=r.current)==null||p.focus()},100)};return document.addEventListener("click",s),()=>{document.removeEventListener("click",s)}}},[r]);const y=()=>{const s=e.jsx(e.Fragment,{children:e.jsx("h4",{className:"text-4xl font-bold m-20 text-center",children:"Zeskanuj Kod Logowania"},"key")});x(s),u(!0)},f=()=>{u(null),x(!1)};return e.jsxs(F,{children:[e.jsx(L,{title:"Log in"}),m&&e.jsx("div",{className:"mb-4 text-sm font-medium text-green-600",children:m}),e.jsx("div",{className:"text-center mb-10",children:e.jsx(l,{className:"ms-4",onClick:y,children:"Logowanie do skanera"})}),e.jsxs("form",{onSubmit:N,children:[e.jsxs("div",{children:[e.jsx(g,{htmlFor:"email",value:"Email"}),e.jsx(n,{id:"email",type:"email",name:"email",value:t.email,className:"mt-1 block w-full",autoComplete:"username",onChange:s=>a("email",s.target.value)}),e.jsx(h,{message:c.email,className:"mt-2"})]}),e.jsxs("div",{className:"mt-4",children:[e.jsx(g,{htmlFor:"password",value:"Password"}),e.jsx(n,{id:"password",type:"password",name:"password",value:t.password,className:"mt-1 block w-full",autoComplete:"current-password",onChange:s=>a("password",s.target.value)}),e.jsx(h,{message:c.password,className:"mt-2"})]}),e.jsx("div",{className:"mt-4 block",children:e.jsxs("label",{className:"flex items-center",children:[e.jsx(E,{name:"remember",checked:t.remember,onChange:s=>a("remember",s.target.checked)}),e.jsx("span",{className:"ms-2 text-sm text-gray-600",children:"Remember me"})]})}),e.jsxs("div",{className:"mt-4 flex items-center justify-end",children:[j&&e.jsx(U,{href:route("password.request"),className:"rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2",children:"Forgot your password?"}),e.jsx(l,{className:"ms-4",disabled:b,children:"Log in"})]})]}),v&&e.jsx("div",{className:"popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20",children:e.jsxs("div",{className:"popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80",children:[w,e.jsx("form",{onSubmit:k,className:"h-0",children:e.jsx(n,{ref:r,id:"targetUrl",type:"text",name:"targetUrl",value:t.targetUrl,className:"mt-1 block w-full h-0 p-0",autoComplete:"off",isFocused:!0,onChange:s=>a("targetUrl",s.target.value)})}),e.jsx(l,{className:"mt-10 bg-red-500 active:bg-red-900",onClick:f,children:"Close"})]})})]})}export{q as default};
