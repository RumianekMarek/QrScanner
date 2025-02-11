import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';

import { Link, usePage, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const props = usePage();
    const { message } = usePage().props;
    const csvData = usePage().props.flash;
    const user = usePage().props.auth.user;
    const status = usePage().props.auth.status;

    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    const logout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    }

    return (
        <div className="min-h-screen bg-gray-100 relative z-10">
            <nav className="border-b border-gray-100 bg-white">
            {user.admin || status != 'inactive' ? (
                <>
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('scanner.create')}
                                    active={route().current('scanner.create')}
                                >
                                     <PrimaryButton className="ms-4 bg-green-500 hover:bg-blue-800">Skaner</PrimaryButton>
                                </NavLink>
                            </div>
                        </div>

                        <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {header}
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        

                                        <Dropdown.Link
                                            method="get"
                                            href={route('scanner.list', { id: user.id })}
                                        >
                                            Lista Skanowania
                                        </Dropdown.Link>
         
                                        {user.admin ? (
                                            <>
                                            <Dropdown.Link
                                                href={route('profile.edit')}
                                            >
                                                Profil
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route('admin.users.index')}
                                                method="get"
                                                as="button"
                                            >
                                                Użytkownicy
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route('admin.users.scanner')}
                                                method="get"
                                                as="button"
                                            >
                                                Skany Użytkowników
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route('admin.fairs.index')}
                                                method="get"
                                                as="button"
                                            >
                                                Targi
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route('register')}
                                                method="get"
                                                as="button"
                                            >
                                                Rejestracja
                                            </Dropdown.Link>
                                            </>
                                        ) : null}
                                        
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href={route('scanner.create')}
                            active={route().current('scanner.create')}
                        >
                            Scanner
                        </ResponsiveNavLink>
                    </div>
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            method="get"
                            href={route('scanner.list', { id: user.id })}
                        >
                            Lista Skanowania
                        </ResponsiveNavLink>
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
                </>
                ) : (
                    <>
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                        <p className='uppercase'>{message}</p>
                    </div>
                    <button onClick={logout} className="absolute top-0 right-0 bg-red-500 text-white text-2xl color-white ps-4 pe-4 pt-1 pb-1 m-5 rounded"
                    >
                        Logout
                    </button>
                    </>
                )}
            </nav>

            <main className='mt-3'>{children}</main>
        </div>
    );
}
