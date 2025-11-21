import { useEffect, useState} from "react";
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';
import Select from '@/Components/Select';
import Checkbox from "@/Components/Checkbox";

export default function UserScans({ usersList }) {
    const { props } = usePage();
    const { flash, message , scannerData} = usePage().props;
    const csvData = usePage().props.flash;
    const user = usePage().props.auth.user;

    const [showflash, setShowflash] = useState(false);
    const [flashMessage, setflashMessage] = useState(null);
    const [selectedUser, setSelectedUser] = useState("");
    const [tableData, setTableData] = useState([]);

    const [force, setForce] = useState(false);

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

    if (typeof csvData === 'string' && csvData.trim() !== '') {
        sendCsv(csvData);
    }

    useEffect(() => {
        if (selectedUser) {
           router.post(route('admin.users.list', { id: selectedUser }));
        }
    }, [selectedUser]);

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

    useEffect(() => {
        if (scannerData) {
            const scannerArray = scannerData.split(';;');
            const parsedData = Object.entries(scannerArray)
                .filter(([key, value]) => value.length >= 10)
                .map(([key, value]) => ({ key, ...JSON.parse(value) }));

            setTableData(parsedData);
        }
    }, [scannerData]);
    
    const closeflash = () => {
        setShowflash(null);
        setflashMessage(false);
    };

    const restoreAction = (id, qrCode) => {
        router.post(route('admin.users.restore', {id: id, qrCode: qrCode}));
    }

    return (
        <AuthenticatedLayout>
            <div  className="w-1/2 ms-20">
                <Select
                    name = "userSelector"
                    options = {usersList}
                    onChange={(e) => setSelectedUser(e.target.value)}
                >
                </Select>
                <Checkbox
                    name="force_change"
                    onChange={() => setForce(prev => !prev)}
                />
            </div>
             <div className="sm:m-5">
                <div className="float-right me-5">
                    <NavLink
                        href={route('scanner.download', { id: selectedUser })}
                        method="post"
                        className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                    >
                        Pobierz
                    </NavLink>
                </div>
                <table className="table-auto border-collapse border border-gray-300 mt-4 max-w-full">
                    <thead>
                        <tr>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">ID</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Imię</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Firma</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Email</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Telefon</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Kod QR</th>
                            <th className="border px-4 py-2 text-center hidden sm:table-cell">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        { Object.entries(tableData).map(([key, single]) => (
                            <tr className="align-center"  key={key}>
                                <td className="border px-4 py-2 text-center hidden sm:table-cell">{key}</td>
                                <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.name}</td>
                                <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.company}</td>
                                <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.email}</td>
                                <td className="border px-4 py-2 text-center hidden sm:table-cell">{single.phone}</td>
                                <td className="border px-4 py-2 text-center ">{single.qrCode ?? ''}<span className="sm:hidden"><br/>{single.email}<br/>{single.phone}</span></td>
                                <td className="border px-4 py-2 text-center ">{ 
                                    (single.status && single.status != "false" && force != true) ? 
                                    single.status : 

                                    <button
                                        onClick={() => restoreAction(selectedUser, single.qrCode)}
                                        className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 rounded"
                                    >
                                        Ponów
                                    </button>
                                    }
                                </td>
                            </tr>
                        ))}
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
    );
}