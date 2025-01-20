import { Link, usePage } from '@inertiajs/react';

export default function NavBar({ auth }) {
    const { url } = usePage();

    return(
        <nav className="-mx-3 flex flex-1 justify-end">
            {auth.user ? (
                <>
                    <Link
                        href={route('dashboard')}
                        className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                    >
                        Dashboard
                    </Link>
                    {auth.user.admin ? (
                        <>
                        <Link
                            href={route('admin.users.index')}
                            className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Użytkownicy
                        </Link>
                        <Link
                            href={route('admin.fairs.index')}
                            className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Targi
                        </Link>
                        <Link
                            href={route('register')}
                            className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Register
                        </Link>
                        <Link
                            method="post"
                            href={route('logout')}
                            as="button"
                        >
                            Log Out
                        </Link>
                        </>
                    ) : null}
                </>
            ) : (
                <Link
                    href={route('login')}
                    className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                >
                    Log in
                </Link>
            )}
        </nav>
    )
}