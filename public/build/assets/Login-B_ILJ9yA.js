import{r as i,a as C,j as e,M as E,U as L}from"./app-hh59bGxm.js";import{C as S}from"./Checkbox-DvCStw7V.js";import{I as x}from"./InputError-Cens1rib.js";import{I as h}from"./InputLabel-COzHv0Dw.js";import{P as c}from"./PrimaryButton-BheXY4oj.js";import{T as g}from"./TextInput-CQOf6QW-.js";import{G as F}from"./GuestLayout-DNSo4fLI.js";function I(t,n,s){s===void 0&&(s=!1),i.useEffect(function(){return document.addEventListener(t,n,s),function(){document.removeEventListener(t,n)}})}function K(t,n){var s=i.useState(""),r=s[0],l=s[1],m=function(o){o.key===t?(n(r),l("")):l("".concat(r).concat(o.key))};return I("keydown",m)}function G({status:t,canResetPassword:n}){const{data:s,setData:r,post:l,processing:m,errors:o,reset:j}=C({email:"",password:"",remember:!1,targetUrl:""}),[d,u]=i.useState(!1),[w,f]=i.useState(null),y=()=>{u(!0)},b=a=>{if(d){const p=a.replace(/undefined/g,"").replace(/Shift/g,"");console.log(p);const k=e.jsxs(e.Fragment,{children:[e.jsxs("h4",{children:["Zeskanowany kod: ",p]}),e.jsx("p",{children:"Token został przesłany pomyślnie!"})]});f(k)}},v=()=>{u(!1),f(null)};K("Enter",b);const N=a=>{a.preventDefault(),l(route("login"),{onFinish:()=>j("password")})};return e.jsxs(F,{children:[e.jsx(E,{title:"Log in"}),t&&e.jsx("div",{className:"mb-4 text-sm font-medium text-green-600",children:t}),e.jsx("div",{className:"text-center mb-10",children:e.jsx(c,{className:"ms-4",onClick:y,children:"Logowanie do skanera"})}),e.jsxs("form",{onSubmit:N,children:[e.jsxs("div",{children:[e.jsx(h,{htmlFor:"email",value:"Email"}),e.jsx(g,{id:"email",type:"email",name:"email",value:s.email,className:"mt-1 block w-full",autoComplete:"username",onChange:a=>r("email",a.target.value)}),e.jsx(x,{message:o.email,className:"mt-2"})]}),e.jsxs("div",{className:"mt-4",children:[e.jsx(h,{htmlFor:"password",value:"Password"}),e.jsx(g,{id:"password",type:"password",name:"password",value:s.password,className:"mt-1 block w-full",autoComplete:"current-password",onChange:a=>r("password",a.target.value)}),e.jsx(x,{message:o.password,className:"mt-2"})]}),e.jsx("div",{className:"mt-4 block",children:e.jsxs("label",{className:"flex items-center",children:[e.jsx(S,{name:"remember",checked:s.remember,onChange:a=>r("remember",a.target.checked)}),e.jsx("span",{className:"ms-2 text-sm text-gray-600",children:"Remember me"})]})}),e.jsxs("div",{className:"mt-4 flex items-center justify-end",children:[n&&e.jsx(L,{href:route("password.request"),className:"rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2",children:"Forgot your password?"}),e.jsx(c,{className:"ms-4",disabled:m,children:"Log in"})]})]}),d&&e.jsx("div",{className:"popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20",children:e.jsxs("div",{className:"popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80",children:[w,e.jsx(c,{className:"mt-10 bg-red-500 active:bg-red-900",onClick:v,children:"Close"})]})})]})}export{G as default};
