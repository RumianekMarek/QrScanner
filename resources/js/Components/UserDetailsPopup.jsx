import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Select from '@/Components/Select';
import { useForm } from '@inertiajs/react';

export default function UserDetailsPopup({ userDetails, fairs, onClose, target_route }) {
    if (!userDetails) return null;

    const { data, setData, post, processing, errors} = useForm({
        user_id: userDetails.id,
        fair_meta: userDetails.details?.fair_meta ?? '',
        phone: userDetails.details?.phone ?? '',
        company_name: userDetails.details?.company_name ?? '',
        placement: userDetails.details?.placement ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route(target_route), {
            onFinish: () => {onClose();}
        });
    };

    return (
        <div className="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center">
            <div className="bg-white p-6 rounded shadow-lg w-1/2">
                <h2 className="text-xl font-bold">Edit User Details</h2>
                <form onSubmit={submit}>
                    <div className="mt-5">
                        <InputLabel htmlFor="company" value="Nazwa Firmy" />

                        <TextInput
                            id="company"
                            name="company"
                            value={data.company_name}
                            className="mt-1 block w-full"
                            autoComplete="company"
                            isFocused={true}
                            onChange={(e) => setData('company_name', e.target.value)}
                            required
                        />

                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div className="mt-5">
                        <InputLabel htmlFor="phone" value="Numer Telefonu" />

                        <TextInput
                            id="phone"
                            name="phone"
                            value={data.phone}
                            className="mt-1 block w-full"
                            autoComplete="phone"
                            onChange={(e) => setData('phone', e.target.value)}
                            required
                        />

                        <InputError message={errors.phone} className="mt-2" />
                    </div>
                    <div className="mt-5">
                        <InputLabel htmlFor="fair_meta" value="Nazwa Targów" />

                        <Select
                            id ="fair_meta"
                            name="fair_meta"
                            label="Wybierz targi"
                            value={data.fair_meta}
                            options={fairs}
                            valueKey='fair_meta'
                            labelKey ='fair_name' 
                            onChange={(e) => setData('fair_meta', e.target.value)}
                        />
                    </div>
                    <div className="mt-5">
                        <InputLabel htmlFor="placement" value="Stoisko" />

                        <TextInput
                            id="placement"
                            name="placement"
                            value={data.placement}
                            className="mt-1 block w-full"
                            autoComplete="placement"
                            onChange={(e) => setData('placement', e.target.value)}
                            required
                        />

                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div className="mt-4 flex justify-end">
                        <PrimaryButton className="ms-4 bg-green-500 hover:bg-green-800" disabled={processing}>
                            Zapisz
                        </PrimaryButton>
                        <PrimaryButton className="ms-4 bg-red-500 hover:bg-red-800" onClick={onClose}>
                            Anuluj
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    );
}
