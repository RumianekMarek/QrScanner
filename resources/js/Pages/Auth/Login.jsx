import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useRef, useEffect } from "react";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
        targetUrl: '',
    });

    const [showflash, setShowflash] = useState(false);
    const [flashMessage, setflashMessage] = useState(null);
    const inputRef = useRef(null);

    const openflash = () => {
        const flashMessage = (
            <>
                <h4 key="key" className="text-4xl font-bold m-20 text-center">
                    Zeskanuj Kod Logowania
                </h4>
            </>
        );

        setflashMessage(flashMessage);
        setShowflash(true);
    }
    
    const closeflash = () => {
        setShowflash(false);
        setflashMessage(null);
    };

    useEffect(() => {
        const handleFocus = () => {
            if (inputRef.current) {
                inputRef.current.focus();
            }
        };

        // Obsługa kliknięcia w dowolne miejsce
        document.addEventListener('click', handleFocus);

        return () => {
            document.removeEventListener('click', handleFocus);
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const submitter = (e) => {
        e.preventDefault();
        post(route('post.token'), {
            onFinish: () => reset('targetUrl'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 text-lg font-bold text-red-600 text-center">
                    {status}
                </div>
            )}
            <div className="text-center mb-10">
                <PrimaryButton className="ms-4" onClick={openflash}>
                    Logowanie do skanera
                </PrimaryButton>
            </div>
            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Forgot your password?
                        </Link>
                    )}

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Log in
                    </PrimaryButton>
                </div>
            </form>
            {showflash && (
                <div className="popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20">
                    <div className="popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80">
                    {flashMessage}
                    <form onSubmit={submitter} className="qr-form h-0 opacity-0">
                        <TextInput
                            ref={inputRef}
                            id="targetUrl"
                            type="text"
                            name="targetUrl"
                            value={data.targetUrl}
                            className="mt-1 block w-full w-4/6 pointer-events-none opacity-0"
                            isFocused={true}
                            autoComplete='off'
                            onChange={(e) => setData('targetUrl', e.target.value)}
                        />
                    </form>
                        <PrimaryButton className="mt-10 bg-red-500 active:bg-red-900" onClick={closeflash}>Close</PrimaryButton>
                    </div>
                </div>
            )}
        </GuestLayout>
    );
}