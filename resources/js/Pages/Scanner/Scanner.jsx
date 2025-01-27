import { useEffect, useRef, useState } from "react";
import { usePage, useForm, router } from '@inertiajs/react';
import QrScanner from '@/Layouts/QrScanner';
import CameraAcces from './CameraAcces';
import DeviceScannerAcces from './DeviceScannerAcces';
import TextInput from '@/Components/TextInput';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Scanner({ mode }) {
    const { data, setData, post, processing, reset} = useForm({
        qrCode: '',
    });
    const { props } = usePage();
    const { flash } = usePage().props;
    const csvData = usePage().props.flash;
    const [showflash, setShowflash] = useState(false);
    const [flashMessage, setflashMessage] = useState(null);
    const [skanmode, setScanMode] = useState(true);
    const inputRef = useRef(null);

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

    const closeflash = () => {
        setShowflash(null);
        setflashMessage(false);
    };

    const logout = (e) => {
        e.preventDefault();
        post(route('logout'));
    }

    useEffect(() => {
        if (flash) {
            setScanMode(true);
        }

        if (flash && flash.message){
            let flashMessage = (
                <p className="text-center">
                    {flash.message} <br/>
                    <span>{props.auth.user.email}</span>
                </p>
            )

            setflashMessage(flashMessage);
            setShowflash(true);
        }
        // setTimeout(() => {
        //     closeflash();
        // }, 5000)
    }, [flash])

    const createCsv = (e) => {
        e.preventDefault();
        post(route('scanner.download', {'id': props.auth.user.id}));
    }

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

    useEffect(() => {
        const sendCsv = async (csvData) => {
            router.post(route('scanner.send'), {
                csvData,
            })
        }
        
        if (typeof csvData === 'string' && csvData.trim() !== '') {
            sendCsv(csvData);
        }
    }, [csvData]);

    const cameraStore = (qrCode) => {
        router.post(route('scanner.store'), {
            qrCode,
        }, {
            onFinish: () => {
                setScanMode(false);
                reset('qrCode');
            }
        });
    }

    const submiter = (e) => {
        e.preventDefault();
        post(route('scanner.store'), {
            onFinish: () => {
                reset('qrCode');
            }
        });
    };

    return (
        <>
           <form onSubmit={submiter} className="qr-form h-0 opacity-0">
                <TextInput
                    ref={inputRef}
                    id="qrCode"
                    name="qrCode"
                    placeholder="QrCode"
                    value={data.qrCode}
                    className="mt-1 block w-full w-4/6 pointer-events-none opacity-0"
                    autoComplete="off"
                    isFocused={true}
                    onChange={(e) => setData('qrCode', e.target.value)}
                    disabled={mode === 'camera'}
                    required
                />
            </form>

            {mode === 'camera' && skanmode &&
                <QrScanner>
                    <div className="text-center">
                        {htmlLast3}
                        <CameraAcces scanned={cameraStore} />
                    </div>
                </QrScanner>
            }
            
            {mode === 'device' && skanmode &&
                <>
                    <button onClick={createCsv} className="absolute top-0 left-0 bg-green-500 text-white text-2xl color-white ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                    >
                        Wyślij Dane
                    </button>
                    <button onClick={logout} className="absolute top-0 right-0 bg-red-500 text-white text-2xl color-white ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                    >
                        Logout
                    </button>
                    <div className="text-center mt-20">
                        {htmlLast3}
                    </div>
                    <DeviceScannerAcces />
                </>
            }

            {showflash && (
                <div className="popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20">
                    <div className="popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80">
                        {flashMessage}
                        <PrimaryButton className="mt-10 bg-red-500 active:bg-red-900" onClick={closeflash}>Close</PrimaryButton>
                    </div>
                </div>
            )}
        </>
    )
}