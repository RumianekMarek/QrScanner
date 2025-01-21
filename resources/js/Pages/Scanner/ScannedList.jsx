import { useEffect, useState} from "react";
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';

export default function DeviceScannerAcces({ scannerData }) {
    const { props } = usePage();
    const { flash } = usePage().props;
    const csvData = usePage().props.flash;
    const user = usePage().props.auth.user;
    const scannerArray = (scannerData ? scannerData.split(';;') : []) ?? [];
    const [showflash, setShowflash] = useState(false);
    const [flashMessage, setflashMessage] = useState(null);

    const downloadCsv = async (csvData) => {
        try {
            // Tworzenie pliku CSV
            const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            // Tworzenie linku do pobrania
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'dane.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

        } catch (error) {
            console.error('Błąd podczas pobierania pliku:', error);
        }
    };

    const sendCsv = async (csvData) => {
        router.post(route('scanner.send'), {
            csvData,
        })
    }

     useEffect(() => {
        if (flash && flash.message){
            let flashMessage = (
                <p className="text-center">
                    {flash.message} <br/>
                    <span className="text-xl">{props.auth.user.email}</span>
                </p>
            )

            setflashMessage(flashMessage);
            setShowflash(true);
        }
        setTimeout(() => {
            closeflash();
        }, 5000)
    }, [flash])

    const closeflash = () => {
        setShowflash(null);
        setflashMessage(false);
    };

    if (typeof csvData === 'string' && csvData.trim() !== '') {
        sendCsv(csvData);
    }
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Lista skonawania
                </h2>
            }
        >
            <Head title="Qr Skaner" />

            <div className="float-right me-5">
                <NavLink
                    href={route('scanner.download', { id: user.id })}
                    active={route().current('scanner.download')}
                    method="post"
                    className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                >
                    Pobierz
                </NavLink>
            </div>
            <div className="m-5">
                <table className="table-auto w-full border-collapse border border-gray-300 mt-4">
                    <thead>
                        <tr>
                            <th className="border px-4 py-2 text-center">ID</th>
                            <th className="border px-4 py-2 text-center">Imię</th>
                            <th className="border px-4 py-2 text-center">Email</th>
                            <th className="border px-4 py-2 text-center">Telefon</th>
                            <th className="border px-4 py-2 text-center">Kod QR</th>
                        </tr>
                    </thead>
                    <tbody>
                        { Object.entries(scannerArray).map(([key, value]) => {
                            if(value.length < 10 ){
                                return null;
                            }
                            const single = JSON.parse(value);

                            return (
                                <tr className="align-center"  key={key}>
                                    <td className="border px-4 py-2 text-center">{key}</td>
                                    <td className="border px-4 py-2 text-center">{single.name}</td>
                                    <td className="border px-4 py-2 text-center">{single.email}</td>
                                    <td className="border px-4 py-2 text-center">{single.phone}</td>
                                    <td className="border px-4 py-2 text-center">{single.qrCode ?? ''}</td>
                                </tr>
                            )
                        })}
                    </tbody>
                </table>
            </div>
            {showflash && (
                <div className="popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20">
                    <div className="popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80">
                        {flashMessage}
                        <PrimaryButton className="mt-10 bg-red-500 active:bg-red-900" onClick={closeflash}>Close</PrimaryButton>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
        

    )
}