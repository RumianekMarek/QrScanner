import { useEffect } from "react";
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';

export default function DeviceScannerAcces({ scannerData }) {
    const { csvData } = usePage().props;
    const user = usePage().props.auth.user;
    const scannerArray = (scannerData ? scannerData.split(';;') : []) ?? [];

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

    if (typeof csvData === 'string' && csvData.trim() !== '') {
        downloadCsv(csvData);
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
                >
                        <PrimaryButton>Pobierz</PrimaryButton>
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
        </AuthenticatedLayout>
    )
}