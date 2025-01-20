import { useEffect, useRef, useState } from "react";
import { usePage, useForm } from '@inertiajs/react';
import QrScanner from '@/Layouts/QrScanner';
import CameraAcces from './CameraAcces';
import DeviceScannerAcces from './DeviceScannerAcces';
import TextInput from '@/Components/TextInput';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Scanner({ mode }) {
    const { props } = usePage();
    const { flash } = usePage().props;
    // const [showflash, setShowflash] = useState(false);
    // const [flashMessage, setflashMessage] = useState(null);

    const { data, setData, post, processing, reset} = useForm({
        qrCode: '',
    });

    // const translations = {
    //     name: "Imię",
    //     email: "Email",
    //     phone: "Telefon"
    // };

    const htmlLast3 = (
        <>
        <h3 className="text-2xl font-bold">Ostatnie Skanowania</h3>

        {Object.entries(props.lastScans).map(([key, value]) => {
            if(value.length < 5){
                return;
            }
            const entry = JSON.parse(value);

            return (
                <p key={key}>
                    {entry['email'] ? entry['email'] : entry['qrCode']}
                </p>
            )
        })}
        </>
    )

    // useEffect(() => {
    //     if (flash){
    //         console.log(flash.data);
    //         let flashMessage = '';
    //         if (flash.status) {
    //             flashMessage = Object.entries(flash.data).map(([key, value]) => (
    //                 <p key={key}>
    //                     {translations[key] || key} = {value}
    //                 </p>
    //             ));
    //         } else {
    //             flashMessage = Object.entries(flash.data).map(([key, value]) => (
    //                 <p key={key}>
    //                     {translations[key] || key} = {value}
    //                 </p>
    //             ));
    //         }

    //         setflashMessage(flashMessage);
    //         setShowflash(true);
            
    //         const timeout = setTimeout(() => {
    //             closeflash();
    //         }, 5000);
    
    //         return () => clearTimeout(timeout);
    //     }
    // }, [flash])

    // const closeflash = () => {
    //     setShowflash(null);
    //     setflashMessage(false);
    // };

    const qrCodeInput = useRef(null);
    const qrCodeForm = useRef(null);
    const qrCodeSubmit = useRef(null);

    const submiter = (e) => {
        e.preventDefault();
        post(route('scanner.store'), {
            onFinish: () => {
                reset('qrCode');
            }
        });
    };

    const logout = (e) => {
        e.preventDefault();
        post(route('logout'));
    }
    useEffect(() => {
        if (data.qrCode && mode === 'camera') {
            qrCodeSubmit.current.click();
        }
    }, [data.qrCode]);

    useEffect(() => {
        const handleClick = (event) => {
            setTimeout(() => {
                qrCodeInput.current?.focus();
            }, 100);
        };
        document.addEventListener('click', handleClick);
        return () => {
            document.removeEventListener('click', handleClick);
        };
    }, []);

    return (
        <>
           <form ref={qrCodeForm} onSubmit={submiter} className="qr-form invisible h-0" >
                <TextInput
                    ref={qrCodeInput}
                    id="qrCode"
                    name="qrCode"
                    placeholder="QrCode"
                    value={data.qrCode}
                    className="mt-1 block w-full w-4/6"
                    autoComplete="off"
                    isFocused="true"
                    onChange={(e) => setData('qrCode', e.target.value)}
                    disabled={mode === 'camera'}
                    required
                />
                <button id="submittt" ref={qrCodeSubmit} type="submit">Submit</button>
            </form>

            {mode === 'camera' && 
            <QrScanner>
                <div className="text-center">
                    {htmlLast3}
                    <CameraAcces scanned={(text) => {
                        setData('qrCode', text);
                    }}/>
                </div>
            </QrScanner>
            }
            
            {mode === 'device' && 
                <>
                    <button onClick={logout} className="absolute right-0 bg-green-500 text-white text-2xl color-white ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                    >
                        Logout
                    </button>
                    <div className="text-center mt-10">
                        {htmlLast3}
                    </div>
                    <DeviceScannerAcces />
                </>
            }
        </>
    )
}