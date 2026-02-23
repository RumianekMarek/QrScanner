import { useEffect, useState, useRef} from "react";
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';
import Select from '@/Components/Select';
import Checkbox from "@/Components/Checkbox";
import Pagination from '@/Components/Pagination';

export default function UserScans({ usersList }) {
    const { props } = usePage();
    const { flash, message} = usePage().props;
    const csvData = usePage().props.flash;
    const user = usePage().props.auth.user;

    const [showflash, setShowflash] = useState(false);
    const [flashMessage, setflashMessage] = useState(null);
    const [selectedUser, setSelectedUser] = useState(props.selectedUser || '');
    const [userData, setUserData] = useState(props.userData || null);

    const [loadingState, setLoadingState] = useState({});

    const [force, setForce] = useState(false);

    const [tableData, setTableData] = useState([]);

    const pageLength = useRef(200);
    const scannerArray = useRef([]);
    const currentPage = useRef(1);

    const totalEntries = useRef(0);

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
        });
    }

    if (typeof csvData === 'string' && csvData.trim() !== '') {
        sendCsv(csvData);
    }

    useEffect(() => {
        if (selectedUser) {
            axios.post(route('admin.users.list', { id: selectedUser }))
                .then(res => {
                    setUserData(res.data.userData);

                    const baseUrl = '/admin/users/scanner';
                    const newUrl = `${baseUrl}/${selectedUser}`;
                    window.history.replaceState(null, '', newUrl);
                });
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
        if (userData) {

            const arr = userData.scannerData
                ?.split(';;')
                .filter(item => item.trim() !== '') ?? [];

            scannerArray.current = arr.filter((value) => value.length >= 10)
                .map((value) => ({...JSON.parse(value) }));

            totalEntries.current = scannerArray.current.length;
            const currentPageDataArray = scannerArray.current.slice(0, pageLength.current);
            setTableData(currentPageDataArray);
        }
    }, [userData]);

    function changeLength(lPage){
        pageLength.current = lPage;
        changePage(currentPage.current);
    }

    function changePage(cPage){
        currentPage.current = cPage;

        const currentPageDataArray = scannerArray.current.slice(
            (cPage - 1) * pageLength.current,
            cPage * pageLength.current,
        );

        setTableData(currentPageDataArray);
    }

    const closeflash = () => {
        setShowflash(null);
        setflashMessage(false);
    };

    const restoreAction = (id, qrCode) => {
        setLoadingState(prev => ({...prev, [qrCode]: true}));
        axios.post(route('admin.users.restore', {id: id, qrCode: qrCode}))
            .then(res => {
                setUserData(res.data.userData);
            })
            .finally(() => {
                setLoadingState(prev => ({...prev, [qrCode]: false}));
            });
    }

    const restoreAll = (id) => {        
        setLoadingState(prev => ({...prev, allRestore: true}));
        axios.post(route('admin.users.allrestore', {id: id}))
            .then(res => {
                setUserData(res.data.userData);
            })
            .finally(() => {
                setLoadingState(prev => ({...prev, allRestore: false}));
            });
    }

    return (
        <AuthenticatedLayout>
            <div  className="w-1/2 ms-20">
                <Select
                    name = "userSelector"
                    options = {usersList}
                    value = {selectedUser}
                    onChange={(e) => setSelectedUser(e.target.value)}
                >
                </Select>
                <Checkbox
                    name="force_change"
                    onChange={() => setForce(prev => !prev)}
                />
            </div>
             <div className="sm:p-[50px]">
                <div className="flex justify-between items-center me-5">
                    <h3 className="text-lg">{ totalEntries.current } Skanów</h3>
                    {force === true && (
                        <button
                            disabled={loadingState['allRestore']}
                            onClick={() => restoreAll(selectedUser)}
                            className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 rounded"
                        >
                            {loadingState['allRestore'] ? 'Przetwarzanie...' : ' Pobierz Wszystko'}
                        </button>
                    )}
                    <NavLink
                        href={route('scanner.download', { id: selectedUser })}
                        method="post"
                        className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                    >
                        Pobierz
                    </NavLink>
                </div>
                <table className="table-auto border-collapse border border-gray-300 mt-4 w-full sm:text-[14px]">
                    <thead>
                        <tr>
                            <th className="border px-1 py-1 text-center hidden sm:table-cell">ID</th>
                            <th className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">Imię</th>
                            <th className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">Firma/Nip</th>
                            <th className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">Email</th>
                            <th className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">Telefon</th>
                            <th className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">Adres</th>
                            <th className="border w-2/12 px-1 py-1 text-center hidden sm:table-cell">Kod QR</th>
                            <th className="border w-2/12 px-1 py-1 text-center hidden sm:table-cell">Zainteresowania</th>
                            <th className="border w-2/12 px-1 py-1 text-center hidden sm:table-cell sm:w-[400px]">Notatka</th>
                            <th className="border px-1 py-1 text-center hidden sm:table-cell">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        { Object.entries(tableData).map(([key, single]) => {
                            const noteArray = userData?.notes.find($val => $val.qr_code == single.qrCode);

                            const trueKey = (currentPage.current - 1) * pageLength.current;

                            const note = noteArray?.note ?? '';
                            return (
                                <tr className="align-center"  key={key}>
                                    <td className="border px-1 py-1 text-center hidden sm:table-cell">{parseInt(key) + parseInt(trueKey) + 1}</td>
                                    <td className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">{single.name}</td>
                                    <td className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">{single.company}</td>
                                    <td className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">{single.email}</td>
                                    <td className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">{single.phone}</td>
                                    <td className="border w-1/12 px-1 py-1 text-center hidden sm:table-cell">{single.adress}</td>

                                    <td className="border w-2/12 px-1 py-1 text-center ">{single.qrCode ?? ''}
                                        <span className="sm:hidden">
                                            {single.email}<br/>
                                            {single.phone}<br/>
                                            {
                                                (single.status && single.status != "false" && force != true) ? 
                                                single.status : 

                                                <button
                                                    disabled={loadingState[single.qrCode]}
                                                    onClick={() => restoreAction(selectedUser, single.qrCode)}
                                                    className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 rounded"
                                                >
                                                    {loadingState[single.qrCode] ? 'Przetwarzanie...' : 'Ponów'}
                                                </button>
                                            }
                                        </span>
                                    </td>

                                    <td className="border w-2/12 px-1 py-1 text-center hidden sm:table-cell">{single.interests}</td>
                                    <td className="border w-2/12 px-1 py-1 text-center hidden sm:table-cell">{note ?? ''}</td>
                                    <td className="border px-1 py-1 text-center hidden sm:table-cell">{ 
                                        (single.status && single.status != "false" && force != true) ? 
                                        single.status : 

                                        <button
                                            disabled={loadingState[single.qrCode]}
                                            onClick={() => restoreAction(selectedUser, single.qrCode)}
                                            className=" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 rounded"
                                        >
                                            {loadingState[single.qrCode] ? '...' : 'Ponów'}
                                        </button>
                                        }
                                    </td>
                                </tr>
                            );
                        })}
                        

                    </tbody>
                </table>
                <Pagination
                    currentPage={currentPage.current}
                    totalPages={Math.ceil(scannerArray.current.length / pageLength.current)}
                    totalEntries={totalEntries.current}

                    onPageChange={(cPage) => changePage(cPage)}
                    onPageLengthChange={(lPage) => changeLength(lPage)}
                />
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