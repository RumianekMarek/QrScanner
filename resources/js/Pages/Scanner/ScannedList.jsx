import { useEffect, useState} from "react";
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';
import NotePopup from '@/Components/NotePopup';

export default function DeviceScannerAcces({ scannerData }) {
    const { props } = usePage();
    const { flash, message } = usePage().props;
    const csvData = usePage().props.flash;
    const user = usePage().props.auth.user;
    const scannerArray = (scannerData ? scannerData.split(';;') : []) ?? [];
    const [showNote, setShowNote] = useState(false);
    const [noteDetails, setNoteDetails] = useState(false);
    const [qrCode, setQrCode] = useState(false);
    const [showflash, setShowflash] = useState(false);
    const [flashMessage, setflashMessage] = useState(null);
    const [senderAllower, setSenderAllower] = useState(true);

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

    const openNote = (note, qrCode) => {
        setNoteDetails(note);
        setQrCode(qrCode);
        setShowNote(true);
    };

    const closeNote = () => {
        setNoteDetails(null);
        setShowNote(false);
    };

    useEffect(() => {
        if (message){
            if(props.auth.user.email.length > 30){
                const emailArray = props.auth.user.email.split('@');
                const emailadress = emailArray[0] + '</p><p>' + emailArray[0];
            }
            let flashMessage = (
                <>
                    <p className="text-center">{message}</p>
                    <p className="text-xl">{props.auth.user.email}</p>
                </>
            )
            setflashMessage(flashMessage);
            setShowflash(true);
        }
        setTimeout(() => {
            closeflash();
        }, 5000)
    }, [flash])

    const createCsv = (id) =>{
        if (senderAllower){
            router.post(route('scanner.download', { id }));

            setSenderAllower(false);

            setTimeout(() => {
                setSenderAllower(true);
            }, 5000)
        }
    }

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
                <PrimaryButton
                    onClick={() => createCsv(user.id)}
                    className=" bg-green-500 ps-8 pe-8 pt-2 pb-2 m-5 rounded"
                    style={{fontSize: '20px'}}
                    disabled={!senderAllower}
                >
                    Pobierz
                </PrimaryButton>
            </div>
            <div className="sm:m-5">
                <table className="table-auto border-collapse border border-gray-300 mt-4 max-w-full">
                    <thead>
                        <tr>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">ID</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Imię</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Firma</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Email</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Telefon</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Kod QR</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Notatka</th>
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
                                    <td className="border px-4 py-2 text-center hidden sm:table-cell">{key}</td>
                                    <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.name}</td>
                                    <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.company}</td>
                                    <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.email}</td>
                                    <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.phone}</td>
                                    <td className="border px-4 py-2 text-center ">{single.qrCode ?? ''}<span className="sm:hidden"><br/>{single.email}<br/>{single.phone}</span></td>
                                    <td className="border px-4 py-2 text-center hidden sm:table-cell">
                                        <button
                                            onClick={() => openNote(single.note, single.qrCode ?? '')}
                                            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded"
                                        >
                                            {single.note ? 'Edytuj' : 'Dodaj'}
                                        </button>
                                    </td>
                                </tr>
                            )
                        })}
                    </tbody>
                </table>
                    {/* User Details popup */}
                    {showNote && (
                        <NotePopup
                            noteDetails={noteDetails}
                            qrCode = {qrCode}
                            user_id = {user.id}
                            onClose={closeNote}
                            target_route='scanner.saveNote'
                        />
                    )}
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