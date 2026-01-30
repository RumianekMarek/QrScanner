import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage} from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import NavLink from '@/Components/NavLink';

export default function QrSkanner({ children }) {
    const { props } = usePage();
    const user = usePage().props.auth.user;
    console.log('QRscanner');
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Qr Skaner
                </h2>
            }
        >
            {user.admin ? (
            <div className="py-12 flex">
                <div className="w-1/2 text-center">
                    <NavLink
                            href={route('scanner.create', { mode: 'device'}) }
                            active={route().current('scanner.create')}
                        >
                        <PrimaryButton>
                            Czytnik
                        </PrimaryButton>
                    </NavLink>
                </div>
                <div className="w-1/2 text-center">
                    <NavLink
                            href={route('scanner.create')}
                            active={route().current('scanner.create')}
                        >
                        <PrimaryButton className="justify-center">
                            Kamera
                        </PrimaryButton>
                    </NavLink>
                </div>
            </div>
            ) : ( null )}
            {children}
        </AuthenticatedLayout>
    );
}
