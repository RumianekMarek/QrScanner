import React, { useState } from 'react';
import { Link, usePage, useForm} from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import UserDetailsPopup from '@/Components/UserDetailsPopup';
import DataComperer from '@/Components/DataComperer';
import QrCode from '@/Components/QrCode';

export default function UserList({ users }) {
    const { props } = usePage();
    const { post } = useForm();
    const [showModal, setShowModal] = useState(false);
    const [userDetails, setUserDetails] = useState(null);

    const openPopup = (details) => {
        setUserDetails(details);
        setShowModal(true);
    };

    const closePopup = () => {
        setUserDetails(null);
        setShowModal(false);
    };
    
    const setUserState = (user_id) => {
        post(route('admin.users.block' , {id: user_id,}));
    }

    const LoginToken = (user_id) => {
        post(route('admin.users.token' , {id: user_id,}))
    }

    const singleFair = (allFairs, detailsMeta) => { 
        const returner =  Object.values(allFairs).find(
            (targetFair) => targetFair.fair_meta === detailsMeta
        );
        return returner
    };

    return (
        <AuthenticatedLayout>
            <div className="m-10">
                <h1 className="text-lg font-bold">Lista Użytkowników</h1>
                <table className="table-auto w-full border-collapse border border-gray-300 mt-4">
                    <thead>
                        <tr>
                            <th className="border px-4 py-2 text-center">ID</th>
                            <th className="border px-4 py-2 text-center">Imię</th>
                            <th className="border px-4 py-2 text-center">Email</th>
                            <th className="border px-4 py-2 text-center">Telefon</th>
                            <th className="border px-4 py-2 text-center">Firma</th>
                            <th className="border px-4 py-2 text-center">LoginToken</th>
                            <th className="border px-4 py-2 text-center">Akcje</th>
                            <th className="border px-4 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.map((user) => (
                        
                            <tr className="align-center" key={user.id}>
                                <td className="border px-4 py-2 text-center">{user.id}</td>
                                <td className="border px-4 py-2 text-center">{user.name}</td>
                                <td className="border px-4 py-2 text-center">{user.email}</td>
                                <td className="border px-4 py-2 text-center">{user.details?.phone ?? 'brak'}</td>
                                <td className="border px-4 py-2 text-center capitalize">{user.admin ? 'pwe' :(user.details?.company_name ?? 'brak')}</td>
                                <td className="border px-4 py-2 text-center capitalize">
                                    {!user.admin && (
                                        <>
                                            {user.login_token && (
                                                <QrCode
                                                    qrCode={user.login_token}
                                                />
                                            )}
                                            {!user.login_token && (
                                                <button
                                                    onClick={() => LoginToken(user.id)}
                                                    className="ms-2 bg-orange-500 hover:bg-orange-700 text-white font-bold py-1 px-4 rounded"
                                                >
                                                    Create Token
                                                </button>
                                            )}
                                        </>
                                    )}
                                </td>
                                <td className="border px-4 py-2 text-center">
                                    {!user.admin && (
                                        <>
                                            <button
                                                onClick={() => openPopup(user)}
                                                className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded"
                                            >
                                                {user.details ? 'Edytuj' : 'Add Details'}
                                            </button>
                                            <button
                                                onClick={() => {setUserState(user.id)}}
                                                className="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-4 rounded"
                                            >
                                                {(user.details?.status !== 'blocked') ? 'Blokuj' : 'Odblokuj'}
                                            </button>
                                        </>
                                    )}
                                </td>
                                {user.admin ? (
                                    <td className="border px-4 py-2 text-center">Admin</td>
                                 ) : (
                                    user.details?.status == 'blocked' ? (
                                        <td className="border px-4 py-2 text-center">Zablokowany</td>
                                    ) : (
                                        <DataComperer
                                            firstDate={singleFair(props.fairs, user.details?.fair_meta)?.fair_start ?? null}
                                            secondDate={singleFair(props.fairs, user.details?.fair_meta)?.fair_end ?? null}
                                        />
                                    )
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>

                {/* User Details popup */}
                {showModal && (
                    <UserDetailsPopup
                        userDetails={userDetails}
                        fairs={props.fairs}
                        onClose={closePopup}
                        target_route='admin.users.update'
                    />
                )}

            </div>
        </AuthenticatedLayout>
    );
}