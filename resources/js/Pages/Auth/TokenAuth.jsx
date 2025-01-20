import { Head, useForm, usePage } from '@inertiajs/react';

export default function VerifyEmail({ token }) {
    const { props } = usePage(); 

    console.log(token);
    console.log(props);

    // secret = SECRET_KEY;
    // signature = hash_hmac('sha256', "$username|$randomData|$timestamp", secret);
    // token = base64_encode("$username|$randomData|$timestamp|$signature");

    console.log(token);

    return (
        <>
        
        </>
    );
}