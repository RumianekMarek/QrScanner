import React from 'react';
import { Link, usePage, useForm} from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DataComperer from '@/Components/DataComperer';
import PrimaryButton from '@/Components/PrimaryButton';
import ButtonAction from '@/Components/ButtonAction';
import TextInput from '@/Components/TextInput';

export default function FairList({ fairs }) {
    const { props } = usePage();
    const { data, setData, post, processing} = useForm({
        domain: '',
    });
    
    const submit = (e) => {
        e.preventDefault();
        post(route('admin.fairs.store'));
    };

    return (
        <AuthenticatedLayout>
            <div className="m-10">
                <form onSubmit={submit}>
                    <div className="flex flex-row w-2/6">
                        <TextInput
                            id="domain"
                            name="domain"
                            placeholder="Wpisz domenę targową"
                            value={data.domain}
                            className="mt-1 block w-full w-4/6"
                            autoComplete="domain"
                            onChange={(e) => setData('domain', e.target.value)}
                            required
                        />

                        <PrimaryButton className="ms-4 bg-green-500 hover:bg-green-800" disabled={processing} >
                            Dodaj Targi
                        </PrimaryButton>
                    </div>
                </form>
                <h1 className="text-xl font-bold mt-10 text-center">Lista Targów</h1>
                <table className="table-auto w-full border-collapse border border-gray-300 mt-4">
                    <thead>
                        <tr>
                            <th className="border px-4 py-2 text-center">ID</th>
                            <th className="border px-4 py-2 text-center">Nazwa Targów</th>
                            <th className="border px-4 py-2 text-center">Klucz meta</th>
                            <th className="border px-4 py-2 text-center">Domena</th>
                            <th className="border px-4 py-2 text-center">Data Targów</th>
                            <th className="border px-4 py-2 text-center">QR Detale</th>
                            <th className="border px-4 py-2 text-center">Status</th>
                            <th className="border px-4 py-2 text-center">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        {fairs.map((fair) => (
                            <tr className="align-center" key={fair.id}>
                                <td className="border px-4 py-2 text-center">{fair.id}</td>
                                <td className="border px-4 py-2 text-center">{fair.fair_name}</td>
                                <td className="border px-4 py-2 text-center">{fair.fair_meta}</td>
                                <td className="border px-4 py-2 text-center">{fair.domain}</td>
                                <td className="border px-4 py-2 text-center">{fair.fair_start} - {fair.fair_end}</td>
                                {
                                    fair.fair_end !== null && fair.qr_details.length > 10 ? 
                                    <td className="border px-4 py-2 text-center">Wypełnione</td> :
                                    <td className="border px-4 py-2 text-center">Nie Wypełnione</td>
                                }
                                {
                                    <DataComperer
                                        firstDate={fair.fair_start}
                                        secondDate={fair.fair_end}
                                    />
                                }
                                {   
                                Object.values(fair).every((value) => value && value.name !== null) ? (
                                    <td className="border px-4 py-2 text-center text-green-500">
                                        <ButtonAction
                                            endpoint="/fair_update"
                                            data={{domain : fair.domain}}
                                            label="Powtórz pobranie formularzy"
                                            color="green"
                                        />
                                    </td>
                                ) : (
                                    <td className="border px-4 py-2 text-center text-red-500">Brakuje Danych</td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AuthenticatedLayout>
    );
}